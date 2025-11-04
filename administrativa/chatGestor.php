<?php
session_start();

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
// Quem é o gestor logado?
// ======================
$gestorId = isset($_SESSION['id_usuario']) ? intval($_SESSION['id_usuario']) : 31; // fallback no 31

// ======================
// Variáveis
// ======================
$codigo = '';
$erroEnvio = '';
$okEnvio  = '';

// ======================
// Abrir conversa por protocolo (GET)
// ======================
if (isset($_GET['protocolo'])) {
    $codigo = trim($_GET['protocolo']);

    $stmt = $conn->prepare("
        SELECT m.*, 
               CASE WHEN m.tipo_remetente='denunciante' THEN 'Denunciante' ELSE 'SDAC' END AS remetente
        FROM mensagens m
        WHERE m.protocolo = ?
           OR EXISTS (SELECT 1 FROM denuncias d WHERE d.id = m.id_denuncia AND d.protocolo = ?)
        ORDER BY m.data_envio ASC
    ");
    $stmt->bind_param("ss", $codigo, $codigo);
    $stmt->execute();
    $resMensagens = $stmt->get_result();
}

// ======================
// Ajax: carregar mensagens periodicamente
// ======================
if (isset($_GET['acao']) && $_GET['acao'] === 'getMensagens') {
    $prot = $_GET['protocolo'] ?? '';
    $stmt = $conn->prepare("
        SELECT m.*, 
               CASE WHEN m.tipo_remetente='denunciante' THEN 'Denunciante' ELSE 'SDAC' END AS remetente
        FROM mensagens m
        WHERE m.protocolo = ?
           OR EXISTS (SELECT 1 FROM denuncias d WHERE d.id = m.id_denuncia AND d.protocolo = ?)
        ORDER BY m.data_envio ASC
    ");
    $stmt->bind_param("ss", $prot, $prot);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($msg = $res->fetch_assoc()) {
        $classe = ($msg['tipo_remetente'] === 'denunciante') ? "denunciante" : "gestor";
        $bg     = ($msg['tipo_remetente'] === 'denunciante') ? "#d1ecf1" : "#c3e6cb"; // azul claro vs verde claro
        $rem    = htmlspecialchars($msg['remetente']);
        $texto  = htmlspecialchars($msg['mensagem']);
        $quando = htmlspecialchars($msg['data_envio']);
        echo "<div class='chat-msg {$classe}' style='background:{$bg}; padding:6px 10px; border-radius:8px; margin-bottom:6px;'>
                <strong>{$rem}:</strong> {$texto}
                <br><small>{$quando}</small>
              </div>";
    }
    exit;
}

// ======================
// Enviar mensagem do Gestor
// ======================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_mensagem'])) {
    $codigo_chat = trim($_POST['codigo_chat'] ?? '');
    $mensagem    = trim($_POST['mensagem'] ?? '');

    if ($codigo_chat === '' || $mensagem === '') {
        $erroEnvio = "Protocolo e mensagem são obrigatórios.";
    } else {
        // Obter ID da denúncia
        $stmt = $conn->prepare("SELECT id FROM denuncias WHERE protocolo = ?");
        $stmt->bind_param("s", $codigo_chat);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($den = $res->fetch_assoc()) {
            $id_denuncia = intval($den['id']);

            // IMPORTANTE:
            // - id_remetente = gestor logado (ou 31)
            // - id_destinatario = NULL (denunciante anónimo)
            // - tipo_remetente = 'gestor'
            $stmt2 = $conn->prepare("
                INSERT INTO mensagens 
                    (id_denuncia, id_remetente, id_destinatario, mensagem, tipo_remetente, protocolo) 
                VALUES (?, ?, NULL, ?, 'gestor', ?)
            ");
            $stmt2->bind_param("iiss", $id_denuncia, $gestorId, $mensagem, $codigo_chat);

            if (!$stmt2->execute()) {
                $erroEnvio = "Erro ao enviar mensagem: " . $stmt2->error;
            } else {
                $okEnvio = "Mensagem enviada.";
                // manter $codigo para continuar a mostrar a conversa
                $codigo = $codigo_chat;
            }
            $stmt2->close();
        } else {
            $erroEnvio = "Protocolo não encontrado.";
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
<title>Painel do Gestor - SDAC</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body { font-family: 'Segoe UI', sans-serif; background-color: #fff8e1; }
  .bg-azul-escuro { background-color: #002d62 !important; }
  .barra-branca { height: 15px; background-color: white; }
  header.titulo-header { background-color: #002d62; color: white; padding: 2rem 0; text-align: center; box-shadow: 0 4px 15px rgba(0,0,0,0.3); }
  header.titulo-header h1 { margin:0; font-size:2rem; font-weight:700; }
  .container { margin-top: 2rem; }
  .chat-box { border:1px solid #ccc; background:#f9f9f9; height:300px; overflow-y:scroll; padding:10px; margin-bottom:1rem; border-radius:8px; }
  .chat-msg { margin-bottom:10px; }
  .chat-msg.denunciante strong { color:#002d62; }
  .chat-msg.gestor strong { color:#115e2e; }
  textarea { width:100%; padding:10px; border-radius:5px; border:1px solid #ccc; resize:none; margin-bottom:10px; }
  button.btn-success { background:#115e2e; border:none; padding:0.5rem 1.5rem; color:white; border-radius:50px; }
  button.btn-success:hover { background:#1f7c3c; }
  .flash { margin: 10px 0; }
</style>
</head>
<body>

<!-- Navbar (opcional, igual ao denunciante) -->
<nav class="navbar navbar-expand-lg navbar-dark bg-azul-escuro">
  <div class="container">
    <a class="navbar-brand" href="#">SDAC - Moçambique</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
  </div>
</nav>

<div class="barra-branca"></div>

<header class="titulo-header">
  <h1>Painel do Gestor - SDAC</h1>
  <p>Visualize e responda às denúncias recebidas</p>
</header>

<div class="container">

  <!-- Seleção de Protocolo -->
  <form method="get" class="mb-4">
    <div class="input-group">
      <input type="text" name="protocolo" class="form-control" placeholder="Digite o protocolo da denúncia (xxxx-xxxx-xxxx-xxxxx-xxxxxx)" required>
      <button type="submit" class="btn btn-success">Abrir Denúncia</button>
    </div>
  </form>

  <?php if (!empty($erroEnvio)): ?>
    <div class="alert alert-danger flash"><?= htmlspecialchars($erroEnvio) ?></div>
  <?php endif; ?>
  <?php if (!empty($okEnvio)): ?>
    <div class="alert alert-success flash"><?= htmlspecialchars($okEnvio) ?></div>
  <?php endif; ?>

  <?php if (!empty($codigo)): ?>
    <h4>Protocolo: <?= htmlspecialchars($codigo) ?></h4>

    <!-- Chat -->
    <div class="chat-box" id="chat-box">
      <?php if (isset($resMensagens) && $resMensagens instanceof mysqli_result): ?>
        <?php while ($msg = $resMensagens->fetch_assoc()): 
              $classe = ($msg['tipo_remetente'] === 'denunciante') ? "denunciante" : "gestor";
              $bg     = ($msg['tipo_remetente'] === 'denunciante') ? "#d1ecf1" : "#c3e6cb";
        ?>
          <div class="chat-msg <?= $classe ?>" style="background:<?= $bg ?>; padding:6px 10px; border-radius:8px; margin-bottom:6px;">
            <strong><?= htmlspecialchars($msg['remetente']) ?>:</strong> <?= htmlspecialchars($msg['mensagem']) ?>
            <br><small><?= htmlspecialchars($msg['data_envio']) ?></small>
          </div>
        <?php endwhile; ?>
      <?php endif; ?>
    </div>

    <!-- Enviar Mensagem -->
    <form method="POST">
      <input type="hidden" name="codigo_chat" value="<?= htmlspecialchars($codigo) ?>">
      <textarea name="mensagem" class="form-control" placeholder="Digite sua resposta..." required></textarea>
      <button type="submit" name="enviar_mensagem" class="btn btn-success mt-2">Enviar Mensagem</button>
    </form>
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Atualização automática do chat
function carregarMensagens() {
  const chatBox = document.getElementById("chat-box");
  const protocolo = "<?= $codigo ? $codigo : '' ?>";
  if (chatBox && protocolo !== "") {
    fetch("?acao=getMensagens&protocolo=" + encodeURIComponent(protocolo))
      .then(res => res.text())
      .then(html => {
        chatBox.innerHTML = html;
        chatBox.scrollTop = chatBox.scrollHeight;
      });
  }
}
setInterval(carregarMensagens, 2000);
carregarMensagens();
</script>

</body>
</html>

