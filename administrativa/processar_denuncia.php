<?php
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
    // Pega o último número do protocolo (ex: SDAC-2025-03 → 03)
    $ultimo = intval(substr($row['protocolo'], -2));
    $novoNumero = str_pad($ultimo + 1, 2, "0", STR_PAD_LEFT);
} else {
    $novoNumero = "01"; // Primeiro protocolo
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
$data = $_POST['hora'] ?? '';
$data = $_POST['data'] ?? '';

$descricao = $_POST['descricao'] ?? '';
$data = $_POST['hora_acontecimento'] ?? '';

// ======================
// UPLOAD DE ANEXO
// ======================
$anexo = NULL;
if (isset($_FILES['anexo']) && $_FILES['anexo']['error'] == 0) {
    $anexo = basename($_FILES['anexo']['name']);
    if (!is_dir("uploads")) {
        mkdir("uploads", 0755, true);
    }
    move_uploaded_file($_FILES['anexo']['tmp_name'], "uploads/" . $anexo);
}

// ======================
// INSERIR NA TABELA denuncias
// ======================
// Estado inicial = Recebida (id_estado = 1)
$stmt = $conn->prepare("INSERT INTO denuncias 
    (protocolo, anonimato, tipo_denunciante, nome, telefone, email, cargo, departamento, 
    assunto, local_acontecimento, data_acontecimento, hora_acontecimento, descricao, anexo, id_estado)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?, ?, 1)");

$stmt->bind_param("sssssssssssss", 
    $protocolo, $anonimo, $tipoDenunciante, $nome, $telefone, $email,
    $cargo, $departamento, $assunto, $local, $data, $hora, $descricao, $anexo
);

if ($stmt->execute()) {
    echo "✅ Denúncia enviada com sucesso!<br> 
          Número de protocolo: <strong>$protocolo</strong>";
} else {
    echo "❌ Erro ao enviar denúncia: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>



