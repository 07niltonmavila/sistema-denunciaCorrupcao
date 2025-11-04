<?php
session_start();
$host = 'localhost'; $db = 'sdac'; $user = 'root'; $pass = ''; $charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC];

try { $pdo = new PDO($dsn,$user,$pass,$options); } 
catch (\PDOException $e) { die("Erro ao conectar: ".$e->getMessage()); }

// Buscar denúncias pendentes
$stmt = $pdo->prepare("SELECT d.*, e.nome_estado FROM denuncias d JOIN estados e ON d.id_estado = e.id_estado WHERE e.nome_estado='Pendente' ORDER BY d.data_envio DESC");
$stmt->execute(); $denuncias = $stmt->fetchAll();

// Buscar órgãos
$orgaos = $pdo->query("SELECT id_usuario, nome FROM usuarios WHERE tipo='orgao'")->fetchAll();
$estados = $pdo->query("SELECT * FROM estados")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Denúncias Pendentes | SDAC</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
body { font-family: 'Segoe UI', sans-serif; background: #f9f9f9; }
header { background-color: #004080; color: white; }
.barra-branca { height:10px; background:white; }
.tabela-box { background:white; padding:25px; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.08); margin-top:30px; }
.badge-status { font-size:0.8rem; padding:6px 10px; }
footer { text-align:center; padding:20px; color:#777; font-size:0.9rem; }
</style>
</head>
<body>

<header class="py-3">
<div class="container d-flex justify-content-between align-items-center">
<div>
<a href="painelGestor.php" class="btn btn-light btn-sm"><i class="bi bi-arrow-left"></i> Voltar ao Painel</a>
</div>
<div class="flex-grow-1 text-center">
<h4 class="m-0">SDAC - Denúncias Pendentes</h4>
</div>
<div>
<a href="" class="btn btn-danger btn-sm"><i class="bi bi-file-earmark-pdf"></i> Exportar PDF</a>

</div>
</div>
</header>




<div class="barra-branca"></div>
<div class="container tabela-box mt-3">
<div class="table-responsive">
<table class="table table-bordered text-center align-middle">
<thead>
<tr>
<th>Protocolo</th><th>Data</th><th>Assunto</th><th>Sector</th><th>Tipo</th><th>Estado</th><th>Ações</th>
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
<td><span class="badge bg-warning text-dark badge-status"><?= htmlspecialchars($d['nome_estado']) ?></span></td>
<td>
<button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modal<?= $d['id'] ?>"><i class="bi bi-gear-fill"></i> Gerir</button>
</td>
</tr>

<!-- Modal -->
<div class="modal fade" id="modal<?= $d['id'] ?>" tabindex="-1">
<div class="modal-dialog">
<div class="modal-content">
<form method="POST" action="gerirPendentes.php">
<input type="hidden" name="id_denuncia" value="<?= $d['id'] ?>">
<div class="modal-header">
<h5 class="modal-title">Gerir Denúncia <?= htmlspecialchars($d['protocolo']) ?></h5>
<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
<div class="mb-3">
<label>Encaminhar para:</label>
<select class="form-select" name="id_orgao" required>
<option value="">Selecionar órgão</option>
<?php foreach($orgaos as $org): ?>
<option value="<?= $org['id_usuario'] ?>"><?= htmlspecialchars($org['nome']) ?></option>
<?php endforeach; ?>
</select>
</div>
<div class="mb-3">
<label>Observações:</label>
<textarea class="form-control" name="comentarios" rows="3"></textarea>
</div>
<div class="mb-3">
<label>Atualizar Status:</label>
<select class="form-select" name="id_estado" required>
<option value="">Selecionar status</option>
<?php foreach($estados as $est): ?>
<option value="<?= $est['id_estado'] ?>" <?= ($d['id_estado']==$est['id_estado'])?'selected':'' ?>><?= htmlspecialchars($est['nome_estado']) ?></option>
<?php endforeach; ?>
</select>
</div>
</div>
<div class="modal-footer">
<button type="submit" class="btn btn-success">Salvar</button>
<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
</div>
</form>
</div>
</div>
</div>

<?php endforeach; ?>
</tbody>
</table>
</div>
</div>

<footer class="mt-5">&copy; 2025 SDAC - Sistema de Denúncias</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
