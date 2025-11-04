<?php
session_start();

// ===== Config DB =====
$host = 'localhost';
$db   = 'sdac';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    echo json_encode(["status"=>"erro","mensagem"=>"Falha na conexão com a base de dados."]);
    exit;
}

$id_denuncia = $_POST['id_denuncia'] ?? null;
$id_orgao    = $_POST['id_orgao'] ?? null;
$id_gestor   = $_SESSION['id_usuario'] ?? 1; // ajuste conforme seu login

if (!$id_denuncia || !$id_orgao) {
    echo json_encode(["status"=>"erro","mensagem"=>"Dados insuficientes."]);
    exit;
}

try {
    $pdo->beginTransaction();

    // registra encaminhamento
    $st = $pdo->prepare("INSERT INTO encaminhamentos (id_denuncia, id_gestor, id_orgao) VALUES (?, ?, ?)");
    $st->execute([$id_denuncia, $id_gestor, $id_orgao]);

    // atualiza estado para 'Encaminhada'
    $st = $pdo->prepare("
        UPDATE denuncias 
           SET id_estado = (SELECT id_estado FROM estados WHERE nome_estado='Encaminhada' LIMIT 1)
         WHERE id = ?
    ");
    $st->execute([$id_denuncia]);

    $pdo->commit();
    echo json_encode(["status"=>"ok"]);
} catch (Exception $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(["status"=>"erro","mensagem"=>"Erro ao encaminhar: ".$ex->getMessage()]);
}
