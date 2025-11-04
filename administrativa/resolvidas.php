<?php
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
} catch (\PDOException $e) {
    die("Erro ao conectar: " . $e->getMessage());
}

// Buscar denúncias resolvidas
$stmt = $pdo->prepare("
    SELECT d.*, e.nome_estado, c.nome_categoria, u.nome AS denunciante
    FROM denuncias d
    LEFT JOIN estados e ON d.id_estado = e.id_estado
    LEFT JOIN categorias c ON d.id_categoria = c.id_categoria
    LEFT JOIN usuarios u ON d.id_denunciante = u.id_usuario
    WHERE e.nome_estado = 'Resolvida'
    ORDER BY d.data_envio DESC
");
$stmt->execute();
$denuncias = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Denúncias Resolvidas | SDAC</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
body { font-family: 'Segoe UI', sans-serif; background: #e9f0f5; }
header { background-color: #002d62; color: white; }
.barra-branca { height: 10px; background-color: white; }
.tabela-box { background: white; padding: 25px; border-radius: 10px; margin-top: 30px; box-shadow: 0 4px 12px rgba(0,0,0,0.08);}
.badge-status { font-size: 0.8rem; padding: 6px 10px; }
</style>
</head>
<body>

<header class="py-3">
<div class="container d-flex justify-content-between align-items-center">
<div>
<a href="painelGestor.php" class="btn btn-light btn-sm"><i class="bi bi-arrow-left"></i> Voltar ao Painel</a>
</div>
<div class="flex-grow-1 text-center">
<h4 class="m-0">SDAC - Denúncias Resolvidas</h4>
</div>
<div></div>
</div>
</header>
<div class="barra-branca"></div>

<div class="container tabela-box mt-3">
<div class="table-responsive">
<table class="table table-bordered align-middle text-center">
<thead>
<tr>
<th>Protocolo</th>
<th>Data</th>
<th>Assunto</th>
<th>Sector</th>
<th>Tipo Denunciante</th>
<th>Nome Denunciante</th>
<th>Email</th>
<th>Telefone</th>
<th>Categoria</th>
<th>Status</th>
</tr>
</thead>
<tbody>
<?php foreach($denuncias as $d): ?>
<tr>
<td><?= htmlspecialchars($d['protocolo']) ?></td>
<td><?= date('d/m/Y', strtotime($d['data_acontecimento'])) ?></td>
<td><?= htmlspecialchars($d['assunto']) ?></td>
<td><?= htmlspecialchars($d['departamento'] ?? '-') ?></td>
<td><?= htmlspecialchars($d['tipo_denunciante'] ?? '-') ?></td>
<td><?= htmlspecialchars($d['denunciante'] ?? '-') ?></td>
<td><?= htmlspecialchars($d['email'] ?? '-') ?></td>
<td><?= htmlspecialchars($d['telefone'] ?? '-') ?></td>
<td><?= htmlspecialchars($d['nome_categoria'] ?? '-') ?></td>
<td><span class="badge bg-success text-white"><?= htmlspecialchars($d['nome_estado']) ?></span></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
