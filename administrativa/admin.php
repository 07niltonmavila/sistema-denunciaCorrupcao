<?php
session_start();

// Conexão PDO
$host = 'localhost';
$db   = 'sdac';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Erro ao conectar: " . $e->getMessage());
}

// Processar criação/edição de usuário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $tipo = $_POST['tipo'];
    $senha = isset($_POST['senha']) && $_POST['senha'] !== '' ? password_hash($_POST['senha'], PASSWORD_DEFAULT) : null;

    if (isset($_POST['id_usuario']) && !empty($_POST['id_usuario'])) {
        // Editar usuário
        if ($senha) {
            $stmt = $pdo->prepare("UPDATE usuarios SET nome=?, email=?, tipo=?, senha=? WHERE id_usuario=?");
            $stmt->execute([$nome, $email, $tipo, $senha, $_POST['id_usuario']]);
        } else {
            $stmt = $pdo->prepare("UPDATE usuarios SET nome=?, email=?, tipo=? WHERE id_usuario=?");
            $stmt->execute([$nome, $email, $tipo, $_POST['id_usuario']]);
        }
    } else {
        // Novo usuário
        $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nome, $email, $senha, $tipo]);
    }

    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// Buscar usuários
$usuarios = $pdo->query("SELECT * FROM usuarios")->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8" />
<title>Administração de Usuários | SDAC</title>
<meta name="viewport" content="width=device-width, initial-scale=1" />
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
<style>
body { font-family: 'Segoe UI', sans-serif; background: #f2f6fb; margin: 0; }
header { background-color: #002d62; color: white; padding: 20px; display: flex; justify-content: space-between; align-items: center; }
header h1 { font-size: 1.8rem; margin: 0; font-weight: 600; }
header a { color: #fff; text-decoration: underline; font-size: 0.9rem; }
.container { padding: 2rem; }
.password-eye { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #555; }
.position-relative { position: relative; }
</style>
</head>
<body>

<header>
<h1>SDAC - Painel de Administração</h1>
<a href="login.php">Login</a>
</header>

<div class="container">
<h2 class="mb-4">Usuários do Sistema</h2>

<button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#usuarioModal">Novo Usuário</button>

<table class="table table-striped">
<thead>
<tr>
<th>ID</th>
<th>Nome</th>
<th>Email</th>
<th>Tipo</th>
<th>Ações</th>
</tr>
</thead>
<tbody>
<?php foreach($usuarios as $u): ?>
<tr>
<td><?= $u['id_usuario'] ?></td>
<td><?= htmlspecialchars($u['nome']) ?></td>
<td><?= htmlspecialchars($u['email']) ?></td>
<td><?= htmlspecialchars($u['tipo']) ?></td>
<td>
<button class="btn btn-sm btn-primary" 
onclick="editarUsuario(<?= $u['id_usuario'] ?>, '<?= addslashes($u['nome']) ?>', '<?= $u['email'] ?>', '<?= $u['tipo'] ?>')">Editar</button>
<a href="remover_usuario.php?id=<?= $u['id_usuario'] ?>" 
class="btn btn-sm btn-danger" 
onclick="return confirm('Tem certeza que deseja revogar este usuário?')">Revogar</a>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<!-- Modal -->
<div class="modal fade" id="usuarioModal" tabindex="-1">
<div class="modal-dialog">
<div class="modal-content">
<form method="post">
<div class="modal-header">
<h5 class="modal-title" id="modalTitle">Novo Usuário</h5>
<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
<input type="hidden" name="id_usuario" id="id_usuario">
<div class="mb-3">
<label class="form-label">Nome</label>
<input type="text" class="form-control" name="nome" id="nome" required>
</div>
<div class="mb-3">
<label class="form-label">Email</label>
<input type="email" class="form-control" name="email" id="email" required>
</div>
<div class="mb-3 position-relative">
<label class="form-label">Senha</label>
<input type="password" class="form-control" name="senha" id="senha">
<span class="password-eye" onclick="toggleSenha()"><i class="bi bi-eye-fill"></i></span>
</div>
<div class="mb-3">
<label class="form-label">Tipo</label>
<select class="form-select" name="tipo" id="tipo" required>
<option value="administrador">Administrador</option>
<option value="gestor">Gestor</option>
<option value="orgao">Órgão</option>
<option value="denunciante">Denunciante</option>
</select>
</div>
</div>
<div class="modal-footer">
<button type="submit" class="btn btn-primary">Salvar</button>
<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
</div>
</form>
</div>
</div>
</div>

<script>
function editarUsuario(id, nome, email, tipo) {
document.getElementById('modalTitle').innerText = "Editar Usuário";
document.getElementById('id_usuario').value = id;
document.getElementById('nome').value = nome;
document.getElementById('email').value = email;
document.getElementById('tipo').value = tipo;
document.getElementById('senha').required = false; 
var modal = new bootstrap.Modal(document.getElementById('usuarioModal'));
modal.show();
}

function toggleSenha() {
const campo = document.getElementById('senha');
campo.type = campo.type === 'password' ? 'text' : 'password';
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet"/>
</body>
</html>
