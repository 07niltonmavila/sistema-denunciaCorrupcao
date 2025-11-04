
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
                            ORDER BY m.data_envio ASC");
    $stmt->bind_param("s", $protocolo);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($msg = $res->fetch_assoc()) {
        // Se for gestor, mostra sempre "SDAC"
        $remetente = ($msg['tipo_remetente'] === 'denunciante') ? "Denunciante" : "SDAC";
        $cor = ($msg['tipo_remetente'] === 'denunciante') ? "#d1ecf1" : "#c3e6cb";

        echo "<div class='chat-msg' style='background-color: {$cor}; padding:5px 10px; border-radius:8px; margin-bottom:5px;'>
                <strong>{$remetente}:</strong> " . htmlspecialchars($msg['mensagem']) . "
                <br><small>{$msg['data_envio']}</small>
              </div>";
    }
    exit;
}

// ======================
// Envio de mensagem (gestor -> denunciante)
// ======================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_mensagem'])) {
    $codigo_chat = trim($_POST['codigo_chat']);
    $mensagem = trim($_POST['mensagem']);
    $tipo = "gestor"; // fixo

    if (!empty($codigo_chat) && !empty($mensagem)) {
        $stmt = $conn->prepare("SELECT id FROM denuncias WHERE protocolo = ?");
        $stmt->bind_param("s", $codigo_chat);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($den = $res->fetch_assoc()) {
            $id_denuncia = $den['id'];
            $id_destinatario = null; // não precisa, pq vai sempre para o denunciante

            $stmt2 = $conn->prepare("INSERT INTO mensagens 
                (id_denuncia, id_destinatario, mensagem, tipo_remetente, protocolo) 
                VALUES (?, ?, ?, ?, ?)");
            $stmt2->bind_param("iisss", $id_denuncia, $id_destinatario, $mensagem, $tipo, $codigo_chat);

            if(!$stmt2->execute()){
                echo "<p style='color:red;'>Erro ao enviar mensagem: " . $stmt2->error . "</p>";
            }
            $stmt2->close();
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Painel do Gestor</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    body { background-color: #f5f5f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
    .bg-azul-escuro { background-color: #002d62 !important; }
    .barra-branca { height: 15px; background-color: white; }
    header.titulo-header { 
        background-color: #002d62; 
        padding: 2rem 0; 
        text-align: center; 
        color: white; 
        box-shadow: 0 4px 15px rgba(0,0,0,0.3); 
        position: relative; 
    }
    header.titulo-header h1 { font-weight: 700; font-size: 2rem; margin: 0; text-shadow: 1px 1px 3px rgba(0,0,0,0.5); }
    .btn-voltar { 
        position: absolute; 
        top: 15px; 
        left: 15px; 
        background: white; 
        color: #002d62; 
        font-weight: bold; 
        border-radius: 6px; 
        padding: 6px 12px; 
        text-decoration: none; 
        transition: 0.3s; 
    }
    .btn-voltar:hover { background: #ddd; color: #000; }
    form { background: #ffffff; padding: 2rem; border-radius: 12px; box-shadow: 0 6px 20px rgba(0,0,0,0.1); margin-top: 2rem; }
    button.btn-success { background: linear-gradient(45deg, #002d62, #002d62); border: none; font-weight: 600; padding: 0.6rem 1.5rem; border-radius: 50px; color: white; }
    button.btn-success:hover { background: linear-gradient(45deg, #115e2e, #1f7c3c); }
    .mensagem-status { margin-top: 1rem; font-weight: bold; color: #333; text-align: center; font-size: 1.2rem; }
    h2.chat-titulo { text-align: center; margin-top: 2rem; color: #002d62; }
    .chat-box { border: 1px solid #ccc; padding: 10px; height: 300px; overflow-y: scroll; background: #f9f9f9; margin-top: 1rem; border-radius: 8px; }
    .chat-msg { margin-bottom: 10px; padding:5px 10px; border-radius:8px; }
    footer { margin-top: 4rem; padding: 1rem 0; background-color: #f1f1f1; text-align: center; font-size: 0.9rem; color: #555; }
    .novas-mensagens { margin-top: 1rem; margin-bottom: 1rem; }
    .novas-mensagens a { margin-right: 6px; margin-bottom: 6px; display:inline-block; }
  </style>
</head>
<body>

<header class="titulo-header">
  <!-- 🔙 Botão canto esquerdo -->
  <a href="painelGestor.php" class="btn-voltar">⬅ Voltar</a>

  <h1>SDAC -  Conversar com o denunciante! </h1>
  <p>Visualize e responda às mensagens recebidas</p>
</header>

<div class="container">

  <!-- Bloco de notificações -->
  <div class="novas-mensagens">
    <h5>Novas mensagens:</h5>
    <?php
      $res = $conn->query("SELECT DISTINCT protocolo FROM mensagens WHERE tipo_remetente='denunciante' AND lida=0 ORDER BY data_envio DESC");
      if ($res && $res->num_rows > 0) {
          while ($row = $res->fetch_assoc()) {
              $prot = htmlspecialchars($row['protocolo']);
              echo '<a href="?protocolo=' . $prot . '" class="btn btn-warning btn-sm">' . $prot . '</a>';
          }
      } else {
          echo '<span>Nenhuma nova mensagem</span>';
      }
    ?>
  </div>

  <!-- Seleção de Protocolo -->
  <form method="get" class="mb-4">
    <div class="input-group">
      <input type="text" name="protocolo" class="form-control" placeholder="Digite o protocolo da denúncia" required>
      <button type="submit" class="btn btn-success">Abrir Mensagem</button>
    </div>
  </form>

  <?php if (!empty($_GET['protocolo'])): ?>
    <h2 class="chat-titulo">Protocolo: <?= htmlspecialchars($_GET['protocolo']) ?></h2>

    <?php
      // 🚨 Marca todas mensagens do denunciante como lidas assim que o protocolo é aberto
      $protAtual = $_GET['protocolo'];
      $stmt = $conn->prepare("UPDATE mensagens SET lida=1 WHERE protocolo=? AND tipo_remetente='denunciante'");
      $stmt->bind_param("s", $protAtual);
      $stmt->execute();
      $stmt->close();
    ?>

    <!-- Chat -->
    <div class="chat-box" id="chat-box"></div>

    <!-- Enviar Mensagem -->
    <form method="POST">
      <input type="hidden" name="codigo_chat" value="<?= htmlspecialchars($_GET['protocolo']) ?>">
      <textarea name="mensagem" class="form-control" placeholder="Digite sua resposta..." required></textarea>
      <button type="submit" name="enviar_mensagem" class="btn btn-success mt-2">Enviar Mensagem</button>
    </form>
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function carregarMensagens() {
  const chatBox = document.getElementById("chat-box");
  const protocolo = "<?= isset($_GET['protocolo']) ? $_GET['protocolo'] : '' ?>";

  if (protocolo !== "") {
    fetch("?acao=getMensagens&protocolo=" + protocolo)
      .then(res => res.text())
      .then(data => {
        chatBox.innerHTML = data;
        chatBox.scrollTop = chatBox.scrollHeight;
      });
  }
}
setInterval(carregarMensagens, 2000);
carregarMensagens();
</script>

<footer>
  <p>&copy; <?= date("Y") ?>   © 2025 SDAC - Todos os direitos reservados.</p>
</footer>
</body>
</html>

