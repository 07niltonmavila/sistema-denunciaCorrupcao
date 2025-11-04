<?php
session_start();
header('Content-Type: application/json');

if(!isset($_POST['id_denuncia'], $_POST['id_orgao'])){
    echo json_encode(['success'=>false, 'message'=>'Dados insuficientes']);
    exit;
}

$id_denuncia = (int)$_POST['id_denuncia'];
$id_orgao = (int)$_POST['id_orgao'];

// Conexão DB
$host = 'localhost';
$db   = 'sdac';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];
$pdo = new PDO($dsn,$user,$pass,$options);

// ID do gestor logado (aqui simulamos com session, ajustar se houver login)
$id_gestor = $_SESSION['id_usuario'] ?? 1;

try{
    $pdo->beginTransaction();

    // Atualiza estado da denúncia
    $stmt = $pdo->prepare("UPDATE denuncias SET id_estado=(SELECT id_estado FROM estados WHERE nome_estado='Encaminhada') WHERE id=?");
    $stmt->execute([$id_denuncia]);

    // Insere no encaminhamento
    $stmt = $pdo->prepare("INSERT INTO encaminhamentos (id_denuncia,id_gestor,id_orgao) VALUES (?,?,?)");
    $stmt->execute([$id_denuncia,$id_gestor,$id_orgao]);

    $pdo->commit();
    echo json_encode(['success'=>true]);
} catch(Exception $e){
    $pdo->rollBack();
    echo json_encode(['success'=>false, 'message'=>$e->getMessage()]);
}

