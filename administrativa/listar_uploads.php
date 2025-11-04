<?php
// listar_uploads.php
session_start();

// --- Config DB (ajuste se necessário) ---
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

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Erro ao conectar: " . $e->getMessage());
}

// --- Caminhos para os uploads ---
// Ajuste $uploadsUrl se a sua app estiver num subdiretório diferente.
// Ex.: se o site estiver em http://localhost/sdac/ deixar '/sdac/uploads/'.
// Se estiver a usar URLs relativas, pode usar '/uploads/' ou 'uploads/'.
$uploadsDir = __DIR__ . '/uploads/';         // caminho físico (filesystem)
$uploadsUrl = '/sdac/administrativa/uploads/';              // URL público (mude se necessário)

// --- Filtro opcional por protocolo (GET) ---
$filtroProt = trim($_GET['protocolo'] ?? '');

// --- Buscar denúncias com anexo(s) ---
if ($filtroProt !== '') {
    $stmt = $pdo->prepare("SELECT protocolo, anexo, data_envio FROM denuncias
                           WHERE anexo IS NOT NULL AND TRIM(anexo) <> '' AND protocolo = :prot
                           ORDER BY data_envio DESC");
    $stmt->execute(['prot' => $filtroProt]);
} else {
    $stmt = $pdo->query("SELECT protocolo, anexo, data_envio FROM denuncias
                        WHERE anexo IS NOT NULL AND TRIM(anexo) <> ''
                        ORDER BY data_envio DESC");
}
$rows = $stmt->fetchAll();

// --- Funções auxiliares ---
function file_icon_html($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    // usa ícones do bootstrap-icons
    if (in_array($ext, ['jpg','jpeg','png','gif','webp','bmp'])) {
        return '<i class="bi bi-file-earmark-image text-primary fs-4"></i>';
    } elseif (in_array($ext, ['mp4','mov','avi','webm','mkv'])) {
        return '<i class="bi bi-file-earmark-play text-danger fs-4"></i>';
    } elseif ($ext === 'pdf') {
        return '<i class="bi bi-file-earmark-pdf text-danger fs-4"></i>';
    } elseif (in_array($ext, ['doc','docx'])) {
        return '<i class="bi bi-file-earmark-word text-primary fs-4"></i>';
    } elseif (in_array($ext, ['xls','xlsx','csv'])) {
        return '<i class="bi bi-file-earmark-excel text-success fs-4"></i>';
    } else {
        return '<i class="bi bi-file-earmark text-secondary fs-4"></i>';
    }
}

// Se a coluna anexo puder conter múltiplos ficheiros separados por vírgula/pipe/ponto-e-vírgula,
// separa-os — caso contrário será apenas um ficheiro por denúncia.
function explode_arquivos($anexoStr) {
    $parts = preg_split('/[|,;]+/', $anexoStr);
    $files = [];
    foreach ($parts as $p) {
        $p = trim($p);
        if ($p !== '') $files[] = $p;
    }
    return $files;
}
?>



<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Provas / Uploads - SDAC</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
  body { background:#f8f9fa; font-family: 'Segoe UI', sans-serif; }
  .page-header { background:#0d6efd; color:white; padding:16px; border-radius:8px; margin-top:16px; }
  .file-card { background:#fff; padding:12px; border-radius:8px; box-shadow:0 2px 6px rgba(0,0,0,0.06); margin-bottom:10px; }
  .file-meta { font-size:0.9rem; color:#666; }
  .btn-back { position:relative; margin-bottom:12px; }
  .badge-prot { font-size:0.85rem; }
</style>
</head>
<body>



<div class="container">



  <div class="page-header d-flex align-items-center gap-3">
    
    <!-- botão voltar -->
  <a href="painelGestor.php" class="btn btn-light btn-back mt-3">
    <i class="bi bi-arrow-left"></i> Voltar ao Painel
  </a>
  <i class=""></i>
    <i class=""></i> <i class=""></i> <i class=""></i> <i class=""></i>
    <i class=""></i> <i class=""></i> <i class=""></i> <i class=""></i>

  <i class=""></i> <i class=""></i> <i class=""></i> <i class=""></i>
    <i class=""></i> <i class=""></i> <i class=""></i> <i class=""></i>
    <div>
      <h3 class="mb-0">Arquivos enviados </h3>
       <!-- <small class="text-white-50">Listagem de anexos por protocolo (mais recentes em cima)</small>-->
    
    </div>
  </div>

  <!-- Filtro rápido / mostrar todos -->
  <div class="mt-3 mb-3 d-flex gap-2 align-items-center">
    <form class="d-flex" method="get" action=""> 
      <input name="protocolo" class="form-control form-control-sm" placeholder="Filtrar por protocolo (ex: SDAC-2025-01)" value="<?= htmlspecialchars($filtroProt) ?>">
      <button class="btn btn-primary btn-sm ms-2">Filtrar</button>
      <a href="listar_uploads.php" class="btn btn-outline-secondary btn-sm ms-2">Mostrar todos</a>
    </form>
  </div>

  <?php if (empty($rows)): ?>
    <div class="alert alert-warning">Nenhum anexo encontrado.</div>
  <?php else: ?>

    <?php
    // Agrupa por protocolo para exibir os ficheiros por cada protocolo
    $grouped = [];
    foreach ($rows as $r) {
        $prot = $r['protocolo'];
        $anexo = $r['anexo'];
        $data_upload = $r['data_envio'] ?? null;
        $files = explode_arquivos($anexo);
        foreach ($files as $f) {
            $grouped[] = [
                'protocolo' => $prot,
                'arquivo' => $f,
                'data_envio' => $data_upload
            ];
        }
    }
    // já ordenados por data_envio desc pela query; se quiser garantir, pode sortear aqui.
    ?>

    <!-- Lista -->
    <?php foreach ($grouped as $idx => $item): 
        $arquivo = $item['arquivo'];
        $protocolo = $item['protocolo'];
        $data_envio = $item['data_envio'];
        $filepath = $uploadsDir . $arquivo;
        // URL público para abrir o ficheiro (mude $uploadsUrl se necessário)
        $fileUrl = rtrim($uploadsUrl, '/') . '/' . rawurlencode($arquivo);
        $exists = is_file($filepath);
    ?>
      <div class="file-card d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-start gap-3">
          <div><?= file_icon_html($arquivo) ?></div>
          <div>
            <div><strong><?= htmlspecialchars($protocolo) ?></strong>
              <span class="badge bg-secondary ms-2 badge-prot">#<?= $idx+1 ?></span>
            </div>
            <div class="file-meta"><?= htmlspecialchars($arquivo) ?> 
              <?php if ($data_envio): ?>
                &middot; <small><?= date('d/m/Y H:i', strtotime($data_envio)) ?></small>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <div class="text-end">
          <?php if ($exists): ?>
            <a href="<?= htmlspecialchars($fileUrl) ?>" target="_blank" class="btn btn-sm btn-primary">
              <i class="bi bi-eye"></i> Ver
            </a>
            <a href="<?= htmlspecialchars($fileUrl) ?>" download class="btn btn-sm btn-outline-secondary ms-1">
              <i class="bi bi-download"></i> Baixar
            </a>
            <!-- link para abrir esta página já filtrada pelo protocolo -->
            <a href="listar_uploads.php?protocolo=<?= urlencode($protocolo) ?>" class="btn btn-sm btn-light ms-1" title="Ver todos os anexos deste protocolo">
              <i class="bi bi-folder2-open"></i>
            </a>
          <?php else: ?>
            <span class="text-danger small">Ficheiro não encontrado no servidor</span>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>

  <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
