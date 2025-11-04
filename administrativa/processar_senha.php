<?php
session_start();
require "conexao.php"; // conecta à BD

// Verifica se o usuário está logado
if (!isset($_SESSION['id_usuario'])) {
    die("Acesso negado.");
}

$id_usuario      = intval($_POST['id_usuario']);
$senha_atual     = $_POST['senha_atual'];
$nova_senha      = $_POST['nova_senha'];
$confirmar_senha = $_POST['confirmar_senha'];

// Verifica se a nova senha e confirmação coincidem
if ($nova_senha !== $confirmar_senha) {
    $_SESSION['msg'] = "❌ A nova senha e a confirmação não coincidem.";
    header("Location: painelGestor.php");
    exit;
}

// Busca senha atual do banco
$sql = "SELECT senha FROM usuarios WHERE id_usuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$stmt->bind_result($senha_hash);
$stmt->fetch();
$stmt->close();

// Verifica senha atual
if (!password_verify($senha_atual, $senha_hash)) {
    $_SESSION['msg'] = "❌ A senha atual está incorreta.";
    header("Location: painelGestor.php");
    exit;
}

// Gera hash da nova senha
$novo_hash = password_hash($nova_senha, PASSWORD_DEFAULT);

// Atualiza no banco
$sql_update = "UPDATE usuarios SET senha = ? WHERE id_usuario = ?";
$stmt_update = $conn->prepare($sql_update);
$stmt_update->bind_param("si", $novo_hash, $id_usuario);

if ($stmt_update->execute()) {
    $_SESSION['msg'] = "✅ Senha alterada com sucesso.";
} else {
    $_SESSION['msg'] = "❌ Erro ao atualizar senha.";
}

$stmt_update->close();
$conn->close();

header("Location: painelGestor.php");
exit;
?>

