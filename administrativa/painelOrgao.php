
<?php
session_start();

// Config DB
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
} catch (\PDOException $e) {
    die("Erro ao conectar: " . $e->getMessage());
}

// ID do órgão logado (exemplo)
$id_orgao = $_SESSION['id_orgao'] ?? 1;

// Função para mascarar/hash do protocolo
function hashProtocolo($protocolo) {
    return substr(hash('sha256', $protocolo), 0, 10);
}

// Buscar denúncias encaminhadas para este órgão
$stmt = $pdo->prepare("
    SELECT d.id, d.protocolo, d.assunto, d.descricao, d.data_envio, d.anexo
    FROM denuncias d
    JOIN encaminhamentos e ON d.id = e.id_denuncia
    WHERE e.id_orgao = ?
    ORDER BY d.data_envio DESC
");
$stmt->execute([$id_orgao]);
$denuncias = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Painel do Órgão - SDAC</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
body { font-family: 'Segoe UI', sans-serif; background: #f5f5f5; }
header { background-color: #004080; color: white; padding: 20px; text-align: center; }
.table thead th { background: #cce5ff; }
</style>
</head>
<body>

<header>
  <h2>Painel do Órgão</h2>
  <a href="index_orgao.php" class="btn btn-light btn-sm mt-2"><i class="bi bi-arrow-left"></i> Voltar ao Painel</a>
</header>

<div class="container mt-4">
    <h4>Denúncias Encaminhadas</h4>
    <table class="table table-bordered table-hover mt-3">
        <thead>
            <tr>
                <th>Protocolo</th>
                <th>Assunto</th>
                <th>Descrição</th>
                <th>Data Envio</th>
                <th>Anexo</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($denuncias)): ?>
                <tr><td colspan="5" class="text-center">Nenhuma denúncia encontrada</td></tr>
            <?php else: foreach($denuncias as $d): ?>
                <tr>
                    <td><?= hashProtocolo($d['protocolo']) ?></td>
                    <td><?= htmlspecialchars($d['assunto']) ?></td>
                    <td style="white-space: pre-wrap;"><?= htmlspecialchars($d['descricao']) ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($d['data_envio'])) ?></td>
                   
                               <!-- Coluna Anexo com botão "Abrir pasta" -->
              <td>
  <?php if(!empty($d['anexo'])): ?>
    <?= htmlspecialchars($d['anexo']) ?><br>
    <a href="uploads/<?= urlencode($d['anexo']) ?>" target="_blank" class="btn btn-sm btn-primary">Abrir anexo</a>
  <?php else: ?>
    -
  <?php endif; ?>
</td>
                    
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

