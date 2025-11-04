
<?php
session_start();

// Verifica se usuário está logado (gestor)
if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

// Verifica se os dados foram enviados
if (!isset($_POST['id_denuncia'], $_POST['assunto'], $_POST['id_estado'])) {
    echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
    exit;
}

$id_denuncia = intval($_POST['id_denuncia']);
$novo_assunto = trim($_POST['assunto']);
$novo_estado = intval($_POST['id_estado']);
$id_usuario = intval($_SESSION['id_usuario']);
$observacao = isset($_POST['observacao']) ? trim($_POST['observacao']) : null;

// Conexão PDO
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

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    // Buscar dados atuais da denúncia
    $stmt = $pdo->prepare("SELECT assunto, id_estado FROM denuncias WHERE id = ?");
    $stmt->execute([$id_denuncia]);
    $denuncia = $stmt->fetch();

    if (!$denuncia) {
        echo json_encode(['success' => false, 'message' => 'Denúncia não encontrada']);
        exit;
    }

    $assunto_anterior = $denuncia['assunto'];
    $estado_anterior = $denuncia['id_estado'];

    // Atualizar a denúncia
    $stmt = $pdo->prepare("UPDATE denuncias SET assunto = ?, id_estado = ? WHERE id = ?");
    $stmt->execute([$novo_assunto, $novo_estado, $id_denuncia]);

    // Inserir histórico apenas se houver alteração
    if ($assunto_anterior !== $novo_assunto || $estado_anterior != $novo_estado) {
        $stmt = $pdo->prepare("
            INSERT INTO historico_denuncia 
                (id_denuncia, assunto_anterior, assunto_novo, estado_anterior, estado_novo, alterado_por)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $id_denuncia,
            $assunto_anterior,
            $novo_assunto,
            $estado_anterior,
            $novo_estado,
            $id_usuario
        ]);
    }

    echo json_encode(['success' => true, 'message' => 'Denúncia atualizada com sucesso']);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: '.$e->getMessage()]);
}

