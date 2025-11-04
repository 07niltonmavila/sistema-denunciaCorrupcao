<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "sdac";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Erro de conexão: " . $conn->connect_error);

$codigo = $_GET['codigo'] ?? '';
if ($codigo) {
    $stmt = $conn->prepare("SELECT id, id_denunciante, id_gestor FROM denuncias WHERE protocolo = ?");
    $stmt->bind_param("s", $codigo);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($den = $res->fetch_assoc()) {
        $id_denuncia = $den['id'];
        $stmt2 = $conn->prepare("SELECT m.mensagem, m.data_envio, u.nome AS remetente
                                 FROM mensagens m
                                 JOIN usuarios u ON m.id_remetente = u.id_usuario
                                 WHERE m.id_denuncia = ?
                                 ORDER BY m.data_envio ASC");
        $stmt2->bind_param("i", $id_denuncia);
        $stmt2->execute();
        $msgs = $stmt2->get_result();
        while ($msg = $msgs->fetch_assoc()) {
            echo "<div class='chat-msg'><strong>".htmlspecialchars($msg['remetente']).":</strong> ".htmlspecialchars($msg['mensagem'])."<br><small>".$msg['data_envio']."</small></div>";
        }
        $stmt2->close();
    }
    $stmt->close();
}
$conn->close();
?>


