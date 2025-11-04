<?php
// ======================
// Conexão com MySQL
// ======================
$host = "localhost";
$user = "root";
$pass = "";
$db   = "sdac";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}

// ======================
// Ajax para carregar mensagens
// ======================
if (isset($_GET['acao']) && $_GET['acao'] === 'getMensagens') {
    $protocolo = $_GET['protocolo'] ?? '';

    $stmt = $conn->prepare("SELECT m.*, u.nome AS remetente
                            FROM mensagens m
                            LEFT JOIN usuarios u ON m.id_remetente = u.id_usuario
                            WHERE m.protocolo = ? 
                               OR EXISTS (SELECT 1 FROM denuncias d WHERE d.id = m.id_denuncia AND d.protocolo = ?)
                            ORDER BY m.data_envio ASC");
    $stmt->bind_param("ss", $protocolo, $protocolo);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($msg = $res->fetch_assoc()) {
        // Denunciante exibe "Denunciante", gestor exibe "SDAC"
        $remetente = ($msg['tipo_remetente'] === 'denunciante') ? "Denunciante" : "SDAC";

        // Diferencia cores por autor
        $cor = ($msg['tipo_remetente'] === 'denunciante') ? "#d1ecf1" : "#c3e6cb";

        echo "<div class='chat-msg' style='background-color: {$cor}; padding:5px 10px; border-radius:8px; margin-bottom:5px;'>
                <strong>{$remetente}:</strong> " . htmlspecialchars($msg['mensagem']) . "
                <br><small>{$msg['data_envio']}</small>
              </div>";
    }
    exit;
}

// ======================
// Variáveis
// ======================
$status = '';
$codigo = '';

// ======================
// Consultar denúncia
// ======================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['consultar'])) {
    $codigo = $_POST['codigo_chat'] ?? '';

    $stmt = $conn->prepare("SELECT d.protocolo, e.nome_estado 
                            FROM denuncias d
                            LEFT JOIN estados e ON d.id_estado = e.id_estado
                            WHERE d.protocolo = ?");
    $stmt->bind_param("s", $codigo);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $status = $row['nome_estado'] ?? 'Recebida';
    } else {
        $status = 'Código não encontrado';
    }

    $stmt->close();
}

// ======================
// Envio de mensagem (denunciante -> super gestor id 31)
// ======================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_mensagem'])) {
    $codigo_chat = trim($_POST['codigo_chat']);
    $mensagem = trim($_POST['mensagem']);

    if (!empty($codigo_chat) && !empty($mensagem)) {
        $stmt = $conn->prepare("SELECT id FROM denuncias WHERE protocolo = ?");
        $stmt->bind_param("s", $codigo_chat);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($den = $res->fetch_assoc()) {
            $id_denuncia = $den['id'];
            $id_destinatario = 31; // super gestor fixo

            $stmt2 = $conn->prepare("INSERT INTO mensagens 
                (id_denuncia, id_destinatario, mensagem, tipo_remetente, protocolo) 
                VALUES (?, ?, ?, 'denunciante', ?)");
            $stmt2->bind_param("iiss", $id_denuncia, $id_destinatario, $mensagem, $codigo_chat);
            if(!$stmt2->execute()){
                echo "<p style='color:red;'>Erro ao enviar mensagem: " . $stmt2->error . "</p>";
            }
            $stmt2->close();
        }
        $stmt->close();
        $codigo = $codigo_chat;
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Consultar Denúncia</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    body { background-color: #f5f5f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
    .bg-azul-escuro { background-color: #002d62 !important; }
    .barra-branca { height: 15px; background-color: white; }
    header.titulo-header { background-color: #002d62; padding: 2rem 0; text-align: center; color: white; box-shadow: 0 4px 15px rgba(0,0,0,0.3); }
    header.titulo-header h1 { font-weight: 700; font-size: 2rem; margin: 0; text-shadow: 1px 1px 3px rgba(0,0,0,0.5); }
    form { background: #ffffff; padding: 2rem; border-radius: 12px; box-shadow: 0 6px 20px rgba(0,0,0,0.1); margin-top: 2rem; }
    button.btn-success { background: linear-gradient(45deg, #002d62, #002d62); border: none; font-weight: 600; padding: 0.6rem 1.5rem; border-radius: 50px; color: white; }
    button.btn-success:hover { background: linear-gradient(45deg, #115e2e, #1f7c3c); }
    .mensagem-status { margin-top: 1rem; font-weight: bold; color: #333; text-align: center; font-size: 1.2rem; }
    h2.chat-titulo { text-align: center; margin-top: 2rem; color: #002d62; }
    .chat-box { border: 1px solid #ccc; padding: 10px; height: 300px; overflow-y: scroll; background: #f9f9f9; margin-top: 1rem; border-radius: 8px; }
    .chat-msg { margin-bottom: 10px; padding:5px 10px; border-radius:8px; }
    footer { margin-top: 4rem; padding: 1rem 0; background-color: #f1f1f1; text-align: center; font-size: 0.9rem; color: #555; }
  </style>
</head>
<body>
<div class="container mb-5">
  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-azul-escuro">
    <div class="container">
      <a class="navbar-brand" href="#">SDAC - Moçambique</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item"><a class="nav-link" href="index.html">Início</a></li>
          <li class="nav-item"><a class="nav-link" href="formulario.html">Denúncia</a></li>
          <li class="nav-item"><a class="nav-link active" href="#">Consultar</a></li>
          <li class="nav-item"><a class="nav-link" href="ajuda.html">Ajuda</a></li>
          <li class="nav-item"><a class="nav-link" href="sobre.html">Sobre</a></li>
        </ul>
      </div>
    </div>
  </nav>

  <div class="barra-branca"></div>
  <header class="titulo-header"><h1>Consultar Denúncia</h1></header>

  <!-- Formulário de consulta -->
  <form method="POST">
    <div class="mb-3">
      <label for="codigo_chat" class="form-label">Código de Acompanhamento:</label>
      <input type="text" id="codigo_chat" name="codigo_chat" class="form-control" placeholder="xxxx-xxxx-xxxx-xxxxx-xxxxxx" required value="<?= htmlspecialchars($codigo) ?>">
    </div>
    <button type="submit" class="btn btn-success" name="consultar">Confirmar</button>
  </form>

  <!-- Status da denúncia -->
  <?php if ($status): ?>
    <div class="mensagem-status">
      Status: <strong><?= htmlspecialchars($status) ?></strong>
    </div>
  <?php endif; ?>

  <!-- Chat com Gestor -->
  <?php if ($codigo): ?>
    <h2 class="chat-titulo">Conversar com Gestor</h2>
    <div class="chat-box" id="chat-box"></div>

    <form method="POST">
      <input type="hidden" name="codigo_chat" value="<?= htmlspecialchars($codigo) ?>">
      <textarea name="mensagem" class="form-control mb-2" placeholder="Digite sua mensagem..." required></textarea>
      <button type="submit" class="btn btn-success" name="enviar_mensagem">Enviar</button>
    </form>

    <script>
      function carregarMensagens() {
          var xhr = new XMLHttpRequest();
          xhr.open("GET", "consultarDenuncia.php?acao=getMensagens&protocolo=<?= urlencode($codigo) ?>", true);
          xhr.onload = function() {
              if (xhr.status === 200) {
                  document.getElementById("chat-box").innerHTML = xhr.responseText;
                  document.getElementById("chat-box").scrollTop = document.getElementById("chat-box").scrollHeight;
              }
          };
          xhr.send();
      }
      setInterval(carregarMensagens, 3000);
      carregarMensagens();
    </script>
  <?php endif; ?>
</div>

<footer>
  © 2025 SDAC - Todos os direitos reservados.
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

