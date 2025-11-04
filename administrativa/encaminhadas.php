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

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Erro ao conectar: " . $e->getMessage());
}

// ===== Dados =====
// Denúncias pendentes/encaminhadas
$denuncias = $pdo->query("
    SELECT d.id, d.protocolo, d.assunto, e.nome_estado
    FROM denuncias d
    JOIN estados e ON d.id_estado = e.id_estado
    WHERE e.nome_estado IN ('Pendente','Encaminhada')
    ORDER BY d.data_envio DESC
")->fetchAll();

// Órgãos para o combo do modal
$orgaos = $pdo->query("SELECT * FROM orgaos ORDER BY nome_orgao ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Encaminhar Denúncias - SDAC</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
  body { font-family: 'Segoe UI', sans-serif; background:#f5f5f5; color:#333; }
  header { background:#004080; color:#fff; padding:20px; text-align:center; position:relative; }
  header .back-btn { position:absolute; left:16px; top:16px; }
  .table thead th { background:#cce5ff; }
</style>
</head>
<body>

<header>
  <!-- ajuste o href para o seu painel principal se não for index.php -->
  <a href="painelGestor.php" class="btn btn-light btn-sm back-btn">
    <i class="bi bi-arrow-left"></i> Voltar ao Painel
  </a>
  <h2 class="m-0">Encaminhar Denúncias</h2>
</header>

<div class="container mt-4">
  <div class="card">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
          <thead>
            <tr>
              <th>Protocolo</th>
              <th>Assunto</th>
              <th>Status</th>
              <th style="width:140px">Ações</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($denuncias)): ?>
              <tr><td colspan="4" class="text-center">Nenhuma denúncia encontrada</td></tr>
            <?php else: foreach ($denuncias as $d): ?>
              <tr>
                <td><?= htmlspecialchars($d['protocolo']) ?></td>
                <td><?= htmlspecialchars($d['assunto']) ?></td>
                <td><?= htmlspecialchars($d['nome_estado']) ?></td>
                <td>
                  <button type="button"
                          class="btn btn-sm btn-warning btn-encaminhar"
                          data-id="<?= (int)$d['id'] ?>"
                          data-protocolo="<?= htmlspecialchars($d['protocolo']) ?>">
                    <i class="bi bi-send-fill"></i> Encaminhar
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

<!-- ===== Modal Encaminhar ===== -->
<div class="modal fade" id="modalEncaminhar" tabindex="-1" aria-labelledby="modalEncaminharLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="formEncaminhar" autocomplete="off">
        <div class="modal-header">
          <h5 class="modal-title" id="modalEncaminharLabel">Encaminhar Denúncia</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id_denuncia" id="id_denuncia">
          <div class="mb-2">
            <small class="text-muted">Protocolo: <span id="infoProtocolo">—</span></small>
          </div>
          <label for="id_orgao" class="form-label">Selecionar Órgão</label>
          <select class="form-select" id="id_orgao" name="id_orgao" required>
            <option value="">-- Selecione --</option>
            <?php foreach ($orgaos as $org): ?>
              <option value="<?= (int)$org['id_orgao'] ?>">
                <?= htmlspecialchars($org['nome_orgao']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div id="msgErro" class="text-danger mt-2" style="display:none;"></div>
          <div id="msgOk" class="text-success mt-2" style="display:none;"></div>
        </div>
        <div class="modal-footer">
          <button type="submit" id="btnConfirmar" class="btn btn-success">
            <i class="bi bi-check2-circle"></i> Confirmar
          </button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            Cancelar
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Scripts (Bootstrap bundle já inclui Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ==== JS sem jQuery ====

// Referências
const modalEl = document.getElementById('modalEncaminhar');
const modal   = new bootstrap.Modal(modalEl);
const form    = document.getElementById('formEncaminhar');
const inpId   = document.getElementById('id_denuncia');
const selOrg  = document.getElementById('id_orgao');
const msgErro = document.getElementById('msgErro');
const msgOk   = document.getElementById('msgOk');
const infoProt= document.getElementById('infoProtocolo');
const btnConfirmar = document.getElementById('btnConfirmar');

// Abrir modal ao clicar no botão "Encaminhar"
document.addEventListener('click', (e) => {
  const btn = e.target.closest('.btn-encaminhar');
  if (!btn) return;

  const id = btn.getAttribute('data-id');
  const protocolo = btn.getAttribute('data-protocolo') || '—';

  inpId.value = id;
  selOrg.value = '';
  msgErro.style.display = 'none';
  msgOk.style.display = 'none';
  infoProt.textContent = protocolo;

  modal.show();
});

// Enviar formulário via fetch (AJAX)
form.addEventListener('submit', async (e) => {
  e.preventDefault();

  const id_denuncia = inpId.value.trim();
  const id_orgao    = selOrg.value.trim();

  if (!id_denuncia || !id_orgao) {
    msgErro.textContent = 'Selecione um órgão para encaminhar.';
    msgErro.style.display = 'block';
    return;
  }

  // desabilita botões durante envio
  btnConfirmar.disabled = true;

  try {
    const resp = await fetch('processar_encaminhamento.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ id_denuncia, id_orgao })
    });

    const data = await resp.json();

    if (data.status === 'ok') {
      msgOk.textContent = 'Denúncia encaminhada com sucesso!';
      msgOk.style.display = 'block';
      // fecha modal e recarrega a página p/ refletir mudanças
      setTimeout(() => {
        modal.hide();
        window.location.reload();
      }, 700);
    } else {
      msgErro.textContent = data.mensagem || 'Falha ao encaminhar.';
      msgErro.style.display = 'block';
    }
  } catch (err) {
    console.error(err);
    msgErro.textContent = 'Erro no servidor.';
    msgErro.style.display = 'block';
  } finally {
    btnConfirmar.disabled = false;
  }
});
</script>
</body>
</html>
