
<?php
session_start();

// Configurações DB
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

// =====================
// Salvar alterações via AJAX
// =====================
if(isset($_POST['salvar']) && isset($_POST['id_denuncia'])){
    $id_denuncia = intval($_POST['id_denuncia']);
    $id_estado = intval($_POST['id_estado'] ?? 0);
    $assunto = trim($_POST['assunto'] ?? '');
    $comentarios = trim($_POST['comentarios'] ?? '');

    // Atualiza o status e assunto da denúncia
    $stmt = $pdo->prepare("UPDATE denuncias SET id_estado=?, assunto=COALESCE(?, assunto) WHERE id=?");
    $stmt->execute([$id_estado, $assunto, $id_denuncia]);

    // Insere comentário do gestor se fornecido
    if(!empty($comentarios)){
        $stmt = $pdo->prepare("INSERT INTO respostas (id_denuncia, id_usuario, texto) VALUES (?, ?, ?)");
        $stmt->execute([$id_denuncia, $_SESSION['id_usuario'] ?? 1, $comentarios]);
    }

    echo json_encode(['success' => true]);
    exit;
}

// =====================
// Gerar modal via AJAX
// =====================
if(isset($_POST['id_denuncia'])){
    $id_denuncia = intval($_POST['id_denuncia']);
    $stmt = $pdo->prepare("SELECT * FROM denuncias WHERE id=?");
    $stmt->execute([$id_denuncia]);
    $denuncia = $stmt->fetch();

    if(!$denuncia){
        exit('Denúncia não encontrada.');
    }

    // Buscar todos os estados
    $estados = $pdo->query("SELECT * FROM estados")->fetchAll();
?>
<div class="modal fade" id="gerirPainelModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Gerir Denúncia - <?= htmlspecialchars($denuncia['protocolo']) ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="gerirPainelForm" method="post">
          <input type="hidden" name="id_denuncia" value="<?= $denuncia['id'] ?>">

          <!-- Assunto -->
          <div class="mb-3">
            <label>Assunto</label>
            <input type="text" class="form-control" name="assunto" value="<?= htmlspecialchars($denuncia['assunto'] ?? '') ?>">
          </div>

          <!-- Status -->
          <div class="mb-3">
            <label>Status</label>
            <select class="form-select" name="id_estado">
              <?php foreach($estados as $e): ?>
              <option value="<?= $e['id_estado'] ?>" <?= ($e['id_estado'] == $denuncia['id_estado']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($e['nome_estado']) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Comentários -->
          <div class="mb-3">
            <label>Comentários</label>
            <textarea class="form-control" name="" rows="3"></textarea>
          </div>

          <button type="submit" class="btn btn-primary">Salvar Alterações</button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php
    exit;
}
?>



