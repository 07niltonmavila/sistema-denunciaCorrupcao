<?php
session_start();

// ======================
// Funções AES para descriptografar a descrição
// ======================
function decryptAES($data) {
    $key = "MinhaChaveSuperSecreta2025"; // mesma chave usada no formulário
    $iv = substr(hash('sha256', $key), 0, 16);
    return openssl_decrypt(base64_decode($data), "AES-256-CBC", $key, 0, $iv);
}

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

// Contagens por estado
$contagemPendentes = (int)$pdo->query("SELECT COUNT(*) FROM denuncias d JOIN estados e ON d.id_estado=e.id_estado WHERE e.nome_estado='Pendente'")->fetchColumn();
$contagemEncaminhadas = (int)$pdo->query("SELECT COUNT(*) FROM denuncias d JOIN estados e ON d.id_estado=e.id_estado WHERE e.nome_estado='Encaminhada'")->fetchColumn();
$contagemResolvidas = (int)$pdo->query("SELECT COUNT(*) FROM denuncias d JOIN estados e ON d.id_estado=e.id_estado WHERE e.nome_estado='Resolvida'")->fetchColumn();
$contagemArquivadas = (int)$pdo->query("SELECT COUNT(*) FROM denuncias d JOIN estados e ON d.id_estado=e.id_estado WHERE e.nome_estado='Arquivada'")->fetchColumn();

// Captura filtro por estado ou denúncia selecionada no combo
$estadoFiltro = $_GET['estado'] ?? '';
$protocoloSelecionado = $_GET['protocolo_combo'] ?? '';

// Buscar denúncias
if (!empty($protocoloSelecionado)) {
    $stmt = $pdo->prepare("
        (SELECT d.id, d.protocolo, d.data_envio, d.assunto, d.local_acontecimento, d.tipo_denunciante,
               d.descricao, d.anexo, e.nome_estado, h.hora
         FROM denuncias d
         JOIN estados e ON d.id_estado = e.id_estado
         LEFT JOIN hora_acontecimento h ON d.id = h.id_denuncia
         WHERE d.protocolo = ?)
        UNION ALL
        (SELECT d.id, d.protocolo, d.data_envio, d.assunto, d.local_acontecimento, d.tipo_denunciante,
               d.descricao, d.anexo, e.nome_estado, h.hora
         FROM denuncias d
         JOIN estados e ON d.id_estado = e.id_estado
         LEFT JOIN hora_acontecimento h ON d.id = h.id_denuncia
         WHERE d.protocolo <> ?
         ORDER BY d.data_envio DESC
         LIMIT 4)
    ");
    $stmt->execute([$protocoloSelecionado, $protocoloSelecionado]);
    $denuncias = $stmt->fetchAll();
} elseif(!empty($estadoFiltro)) {
    $stmt = $pdo->prepare("
        SELECT d.id, d.protocolo, d.data_envio, d.assunto, d.local_acontecimento, d.tipo_denunciante,
               d.descricao, d.anexo, e.nome_estado, h.hora
        FROM denuncias d
        JOIN estados e ON d.id_estado = e.id_estado
        LEFT JOIN hora_acontecimento h ON d.id = h.id_denuncia
        WHERE e.nome_estado = ?
        ORDER BY d.data_envio DESC
    ");
    $stmt->execute([$estadoFiltro]);
    $denuncias = $stmt->fetchAll();
} else {
    $stmt = $pdo->query("
        SELECT d.id, d.protocolo, d.data_envio, d.assunto, d.local_acontecimento, d.tipo_denunciante,
               d.descricao, d.anexo, e.nome_estado, h.hora
        FROM denuncias d
        JOIN estados e ON d.id_estado = e.id_estado
        LEFT JOIN hora_acontecimento h ON d.id = h.id_denuncia
        ORDER BY d.data_envio DESC
        LIMIT 5
    ");
    $denuncias = $stmt->fetchAll();
}

// Buscar todas as denúncias para popular o combo
$allDenuncias = $pdo->query("SELECT protocolo FROM denuncias ORDER BY data_envio DESC")->fetchAll();

// Buscar todos os estados disponíveis para o select do modal
$estados = $pdo->query("SELECT * FROM estados ORDER BY nome_estado ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>SDAC - Área do Gestor</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
/* Mantive todo o CSS original */
body { font-family: 'Segoe UI', sans-serif; background: #f5f5f5; color: #333; }
header { background-color: #002d62; color: white; padding: 20px 0; text-align: center; position: relative; }
.barra-branca { height: 10px; background-color: #ffffff; }
.card-stats { background: white; border-left: 4px solid #0d6efd; border-radius: 8px; padding: 12px; cursor:pointer; }
.card-stats h5 { margin:0; font-size:1.25rem; }
.card-stats span { display:block; font-size:1.2rem; font-weight:700; margin-top:6px; }
.card-row { display:flex; gap:12px; flex-wrap:wrap; align-items:flex-start; }
.card-row .card-stats { flex:1 1 150px; }
.container-dashboard { padding: 20px; }
.table thead th { background: #f5e6d0; }
.badge-status { font-size:0.85rem; padding:6px 8px; }
.modal-lg { max-width:900px; }
.top-icons { position: absolute; top: 18px; right: 18px; display: flex; gap: 15px; align-items: center; }
.top-icons .dropdown-toggle::after { display:none; }
</style>
</head>
<body>

<header>
  <div class="container">
    <h1><i class="bi bi-shield-check"></i> SDAC - Moçambique</h1>
    <p class="intro-text">Sistema de Denúncia de Actos de Corrupção</p>
  </div>
  <div class="top-icons">
    <a href="mensagensPainel.php" class="position-relative text-white">
      <i class="bi bi-bell-fill fs-4"></i>
      <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">1</span>
    </a>
    <div class="dropdown">
      <a href="#" class="text-white dropdown-toggle" data-bs-toggle="dropdown">
        <i class="bi bi-gear-fill fs-4"></i>
      </a>
      <ul class="dropdown-menu dropdown-menu-end">
        <li><a class="" href=""><i class="bi bi-person-badge"></i> Dados de acesso</a></li>
        <li><a class="dropdown-item" href=""><i class="bi bi-key-fill"></i> Alterar senha</a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
      </ul>
    </div>
  </div>
</header>

<div class="barra-branca"></div>

<header style="background-color: #004080;">
  <div class="container">
    <h2 style="margin:0; color:#fff"><i class="bi bi-person-gear"></i> Área do Gestor</h2>
  </div>
</header>

<div class="container container-dashboard">
  <div class="card-row mb-3">
    <div class="card-stats text-center" onclick="window.location.href='encaminhadas.php'">
      <h5><i class="bi bi-exclamation-circle-fill text-warning"></i> Pendente</h5>
      <span><?= $contagemPendentes ?></span>
    </div>
    <div class="card-stats text-center" onclick="window.location.href='encaminhadas.php'">
      <h5><i class="bi bi-send-check-fill text-success"></i> Encaminhadas</h5>
      <span><?= $contagemEncaminhadas ?></span>
    </div>
    <div class="card-stats text-center" onclick="window.location.href='resolvidas.php'">
      <h5><i class="bi bi-check-circle-fill text-primary"></i> Resolvidas</h5>
      <span><?= $contagemResolvidas ?></span>
    </div>
    <div class="card-stats text-center" onclick="window.location.href='arquivadas.php'">
      <h5><i class="bi bi-archive-fill text-secondary"></i> Arquivadas</h5>
      <span><?= $contagemArquivadas ?></span>
    </div>
    <div class="card-stats text-center">
      <h5><i class="bi bi-bar-chart-line-fill text-info"></i> Relatórios</h5>
      <a href="relatorios.php" class="btn btn-sm btn-outline-primary mt-2">Ver Relatórios</a>
    </div>
  </div>

  <!-- Combo de seleção de denúncia -->
  <form method="get" class="mb-3">
    <div class="input-group">
      <select name="protocolo_combo" class="form-select">
        <option value="">-- Selecionar denúncia --</option>
        <?php foreach($allDenuncias as $den): ?>
          <option value="<?= htmlspecialchars($den['protocolo']) ?>" <?= ($den['protocolo']==$protocoloSelecionado)?'selected':'' ?>>
            <?= htmlspecialchars($den['protocolo']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-success">Filtrar</button>
    </div>
  </form>

  <!-- Tabela de denúncias -->
  <div class="card mb-3">
    <div class="card-body">
      <h5 class="card-title"><i class="bi bi-list-ul"></i> Denúncias <?= $estadoFiltro ? " - $estadoFiltro" : "(últimas 5)" ?></h5>
      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
          <thead>
            <tr>
              <th>Protocolo</th>
              <th>Data</th>
              <th>Hora</th>
              <th>Assunto/ Categoria</th>
              <th>Local</th>
              <th>Tipo denunciante</th>
              <th>Descrição</th>
              <th>Anexo</th>
              <th>Status</th>
              <th>Acções</th>
            </tr>
          </thead>
          <tbody>
            <?php if(empty($denuncias)): ?>
              <tr><td colspan="10" class="text-center">Nenhuma denúncia encontrada</td></tr>
            <?php else: foreach($denuncias as $d): ?>
              <tr>
                <td><?= htmlspecialchars($d['protocolo']) ?></td>
                <td><?= date('d/m/Y', strtotime($d['data_envio'])) ?></td>
                <td><?= !empty($d['hora']) ? date('H:i', strtotime($d['hora'])) : '-' ?></td>
                <td><?= htmlspecialchars($d['assunto']) ?></td>
                <td><?= htmlspecialchars($d['local_acontecimento']) ?></td>
                <td><?= htmlspecialchars($d['tipo_denunciante'] ?? '-') ?></td>
                <!-- Descriptografar descrição -->
                <td style="max-width:260px; white-space:pre-wrap;"><?= htmlspecialchars(decryptAES($d['descricao'])) ?></td>

                <!-- Coluna Anexo com botão "Abrir pasta" -->
    <td>
  <?= !empty($d['anexo']) 
        ? '<a href="uploads/' . htmlspecialchars($d['anexo']) . '" target="_blank" class="btn btn-sm btn-success">Abrir Anexo</a>' 
        : '-' 
  ?>
</td>



                <td>
                  <?php
                  $cls = 'bg-light text-dark';
                  if ($d['nome_estado']=='Pendente') $cls='bg-warning text-dark';
                  elseif ($d['nome_estado']=='Encaminhada') $cls='bg-success';
                  elseif ($d['nome_estado']=='Resolvida') $cls='bg-primary';
                  elseif ($d['nome_estado']=='Arquivada') $cls='bg-secondary';
                  ?>
                  <span class="badge <?= $cls ?> badge-status"><?= htmlspecialchars($d['nome_estado']) ?></span>
                </td>
                <td>
                  <button class="btn btn-sm btn-primary btn-gerir" data-id="<?= $d['id'] ?>">
                    <i class="bi bi-gear-fill"></i> Gerir
                  </button>
                </td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Container para injetar modal -->
<div id="modalContainer"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Abrir modal de gerir denúncia via botão
document.addEventListener('click', function(e){
  if (e.target.closest('.btn-gerir')) {
    const btn = e.target.closest('.btn-gerir');
    const id = btn.getAttribute('data-id');
    fetch('gerirPainel.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ id_denuncia: id })
    })
    .then(r => r.text())
    .then(html => {
      document.getElementById('modalContainer').innerHTML = html;
      const modalEl = document.getElementById('gerirPainelModal');
      const modal = new bootstrap.Modal(modalEl);
      modal.show();

      const form = modalEl.querySelector('#gerirPainelForm');
      form.addEventListener('submit', function(ev){
        ev.preventDefault();
        const data = new URLSearchParams(new FormData(form));
        data.append('salvar', '1');

        fetch('atualizar_relatorio.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: data
        })
        .then(r => r.json())
        .then(j => {
          if(j.success){
            modal.hide();
            window.location.reload();
          } else {
            alert(j.message || 'Erro ao salvar alterações');
          }
        }).catch(err=>{
          console.error(err);
          alert('Erro na comunicação com o servidor.');
        });
      });
    })
    .catch(err=>{
      console.error(err);
      alert('Erro ao carregar modal.');
    });
  }
});
</script>

<div style="display: flex; justify-content:center; align-items: center; height:100px;">
<p>© 2025 SDAC - Todos os direitos reservados.</p>
</div>

</body>
</html>


