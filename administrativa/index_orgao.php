<?php
session_start();

// Verifica se usuário é órgão
if(!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'orgao'){
    header("Location: login.php");
    exit;
}

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

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Erro ao conectar: " . $e->getMessage());
}

// ID do órgão logado
$id_orgao = $_SESSION['id_usuario'];

// Contagem de denúncias encaminhadas para este órgão
$contEncaminhadas = (int)$pdo->prepare("
    SELECT COUNT(*) FROM encaminhamentos e
    JOIN denuncias d ON e.id_denuncia = d.id
    WHERE e.id_orgao = ?
")->execute([$id_orgao]);
$contEncaminhadas = $pdo->prepare("
    SELECT COUNT(*) as total FROM encaminhamentos e
    JOIN denuncias d ON e.id_denuncia = d.id
    WHERE e.id_orgao = ?
");
$contEncaminhadas->execute([$id_orgao]);
$contEncaminhadas = $contEncaminhadas->fetchColumn();
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
header { background-color: #004080; color: white; padding: 20px; text-align: center; position: relative; }
.card-stats { background: white; border-left: 4px solid #0d6efd; border-radius: 8px; padding: 12px; cursor:pointer; }
.card-stats h5 { margin:0; font-size:1rem; }
.card-stats span { display:block; font-size:1.5rem; font-weight:700; margin-top:6px; }
.card-row { display:flex; gap:12px; flex-wrap:wrap; align-items:flex-start; }
.card-row .card-stats { flex:1 1 150px; }
.container-dashboard { padding: 20px; }
</style>
</head>
<body>

<header>
  <h2>Painel do Órgão</h2>
  <div style="position:absolute; top:20px; right:20px;">
      <a href="logout.php" class="btn btn-light btn-sm"><i class="bi bi-box-arrow-right"></i> Logout</a>
  </div>
</header>

<div class="container container-dashboard mt-4">
    <div class="card-row mb-3">
        <div class="card-stats text-center" onclick="window.location.href='painelOrgao.php'">
            <h5><i class="bi bi-send-check-fill text-success"></i> Denúncias Encaminhadas</h5>
            <span><?= $contEncaminhadas ?></span>
        </div>

        <div class="card-stats text-center" onclick="window.location.href='relatorios_orgao.php'">
            <h5><i class="bi bi-bar-chart-line-fill text-info"></i> Relatórios</h5>
            <span>--</span>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


