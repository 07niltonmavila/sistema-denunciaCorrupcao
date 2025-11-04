<?php
session_start();
if(!isset($_POST['id_denuncia'])) exit;

$id_denuncia = (int)$_POST['id_denuncia'];

// Conexão DB (mesma que encaminhadas.php)
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

// Buscar órgãos
$orgaos = $pdo->query("SELECT * FROM orgaos ORDER BY nome_orgao ASC")->fetchAll();
?>

<div class="modal fade" id="modalEncaminhar" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="formEncaminhar">
        <div class="modal-header">
          <h5 class="modal-title">Encaminhar Denúncia #<?= $id_denuncia ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id_denuncia" value="<?= $id_denuncia ?>">
          <div class="mb-3">
            <label for="id_orgao" class="form-label">Selecionar órgão</label>
            <select class="form-select" name="id_orgao" required>
              <option value="">-- Selecionar --</option>
              <?php foreach($orgaos as $o): ?>
                <option value="<?= $o['id_orgao'] ?>"><?= htmlspecialchars($o['nome_orgao']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Encaminhar</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        </div>
      </form>
    </div>
  </div>
</div>

