
<?php
// ======================
// IMPORT PHPMailer
// ======================
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/PHPMailer-master/src/Exception.php';
require __DIR__ . '/PHPMailer-master/src/PHPMailer.php';
require __DIR__ . '/PHPMailer-master/src/SMTP.php';

// ======================
// Funções AES
// ======================
function encryptAES($data) {
    $key = "MinhaChaveSuperSecreta2025"; // chave AES
    $iv = substr(hash('sha256', $key), 0, 16); // IV de 16 bytes
    return base64_encode(openssl_encrypt($data, "AES-256-CBC", $key, 0, $iv));
}

function decryptAES($data) {
    $key = "MinhaChaveSuperSecreta2025";
    $iv = substr(hash('sha256', $key), 0, 16);
    return openssl_decrypt(base64_decode($data), "AES-256-CBC", $key, 0, $iv);
}

// ======================
// Configuração de conexão
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
// GERAR PROTOCOLO
// ======================
$result = $conn->query("SELECT protocolo FROM denuncias ORDER BY id DESC LIMIT 1");
if ($result && $row = $result->fetch_assoc()) {
    $ultimo = intval(substr($row['protocolo'], -2));
    $novoNumero = str_pad($ultimo + 1, 2, "0", STR_PAD_LEFT);
} else {
    $novoNumero = "01";
}
$protocolo = "SDAC-2025-" . $novoNumero;

// ======================
// RECEBER DADOS DO FORMULÁRIO
// ======================
$anonimo = $_POST['anonimo'] ?? 'sim';

if ($anonimo === 'sim') {
    $tipoDenunciante = NULL;
    $nome = NULL;
    $telefone = NULL;
    $email = NULL;
    $cargo = NULL;
    $departamento = NULL;
} else {
    $tipoDenunciante = $_POST['tipoDenunciante'] ?? NULL;
    $nome = $_POST['nome'] ?? NULL;
    $telefone = $_POST['telefone'] ?? NULL;
    $email = $_POST['email'] ?? NULL;
    $cargo = $_POST['cargo'] ?? NULL;
    $departamento = $_POST['departamento'] ?? NULL;
}

$assunto = $_POST['assunto'] ?? '';
$local = $_POST['local'] ?? '';
$data_acontecimento = $_POST['data'] ?? date('Y-m-d');
$hora_acontecimento = $_POST['hora'] ?? date('H:i:s');
$descricao = $_POST['descricao'] ?? '';

// ======================
// UPLOAD DE ANEXO
// ======================
$anexo = NULL;
if (isset($_FILES['anexo']) && $_FILES['anexo']['error'] == 0) {
    $anexo = basename($_FILES['anexo']['name']);
    $uploadDir = __DIR__ . "/administrativa/uploads/";
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    $destino = $uploadDir . $anexo;
    if (!move_uploaded_file($_FILES['anexo']['tmp_name'], $destino)) {
        die("Erro ao mover o ficheiro para a pasta de uploads.");
    }
}

// ======================
// Criptografar apenas a descrição
// ======================
$descricaoEnc = encryptAES($descricao);

// ======================
// INSERIR NA TABELA denuncias
// ======================
$stmt = $conn->prepare("INSERT INTO denuncias 
    (protocolo, anonimato, tipo_denunciante, nome, telefone, email, cargo, departamento, 
     assunto, local_acontecimento, data_acontecimento, descricao, anexo, id_estado)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");

$stmt->bind_param("sssssssssssss", 
    $protocolo, $anonimo, $tipoDenunciante, $nome, $telefone, $email,
    $cargo, $departamento, $assunto, $local, $data_acontecimento, $descricaoEnc, $anexo
);

if ($stmt->execute()) {
    $id_denuncia = $stmt->insert_id;

    // ======================
    // INSERIR HORA
    // ======================
    $stmtHora = $conn->prepare("INSERT INTO hora_acontecimento (id_denuncia, hora) VALUES (?, ?)");
    $stmtHora->bind_param("is", $id_denuncia, $hora_acontecimento);
    $stmtHora->execute();
    $stmtHora->close();

    echo "✅ Denúncia enviada com sucesso!<br>Protocolo: <strong>$protocolo</strong>";

    // ======================
    // ENVIO DE EMAIL DE CONFIRMAÇÃO
    // ======================
    if (!empty($email)) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'sdac.confirmacao@gmail.com';
            $mail->Password   = 'qgkcpexuzswziesw';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('sdac.confirmacao@gmail.com', 'Sistema SDAC');
            $mail->addAddress($email, $nome ?: 'Denunciante');

            $mail->isHTML(true);
            $mail->Subject = 'Confirmação de Recebimento de Denúncia';
            $mail->Body    = "
                <p>Olá ".htmlspecialchars($nome ?: 'Cidadão').",</p>
                <p>Sua denúncia foi registrada com sucesso.</p>
                <p><strong>Protocolo:</strong> $protocolo</p>
                <p>Obrigado por utilizar o SDAC.</p>
            ";

            $mail->send();
            echo "<br>📧 Email de confirmação enviado com sucesso!";
        } catch (Exception $e) {
            echo "<br>⚠ Erro ao enviar email: {$mail->ErrorInfo}";
        }
    }

} else {
    echo "❌ Erro ao enviar denúncia: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>

