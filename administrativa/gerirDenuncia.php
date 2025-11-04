<?php
session_start();

$host = 'localhost';
$db   = 'sdac';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Erro ao conectar: " . $e->getMessage());
}

// Receber dados do formulário
$id_denuncia = $_POST['id_denuncia'] ?? null;
$id_estado   = $_POST['id_estado'] ?? null;
$id_orgao    = $_POST['id_orgao'] ?? null;
$comentarios = $_POST['comentarios'] ?? '';

if($id_denuncia && $id_estado){
    // Atualizar status da denúncia
    $stmt = $pdo->prepare("UPDATE denuncias SET id_estado = ?, id_gestor = ? WHERE id = ?");
    $stmt->execute([$id_estado, $_SESSION['id_usuario'], $id_denuncia]);

    // Registrar encaminhamento, se foi selecionado órgão
    if($id_orgao){
        $stmt2 = $pdo->prepare("INSERT INTO encaminhamentos (id_denuncia,id_gestor,id_orgao) VALUES (?,?,?)");
        $stmt2->execute([$id_denuncia, $_SESSION['id_usuario'], $id_orgao]);
    }

    // Registrar comentário/resposta
    if(!empty($comentarios)){
        $stmt3 = $pdo->prepare("INSERT INTO respostas (id_denuncia,id_usuario,texto) VALUES (?,?,?)");
        $stmt3->execute([$id_denuncia, $_SESSION['id_usuario'], $comentarios]);
    }

    header("Location: PainelGestor.php?sucesso=1");
    exit;
} else {
    die("Dados incompletos.");
}
