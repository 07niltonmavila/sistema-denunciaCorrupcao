<?php
session_start();

/* ---------- Config DB ---------- */
$host = "localhost";
$db   = "sdac";
$user = "root";   
$pass = "";       

$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro ao conectar à base de dados.");
}

/* ---------- Lógica do login ---------- */
$erro = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = trim($_POST["password"] ?? "");

    if ($username === "" || $password === "") {
        $erro = "Informe o utilizador e a senha.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE nome = :nome LIMIT 1");
        $stmt->execute([":nome" => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && $user["senha"] === $password) { // senha simples
            session_regenerate_id(true);
            $_SESSION["id_usuario"] = $user["id_usuario"];
            $_SESSION["nome"]       = $user["nome"];
            $_SESSION["tipo"]       = $user["tipo"];

            switch ($user["tipo"]) {
                case "administrador":
                    header("Location: admin.php"); exit;
                case "gestor":
                    header("Location: PainelGestor.php"); exit;
                case "orgao":
                    header("Location: index_orgao.php"); exit;
                case "denunciante":
                    header("Location: PainelDenunciante.php"); exit;
                default:
                    $erro = "Tipo de utilizador desconhecido.";
            }
        } else {
            $erro = "Credenciais inválidas.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Acesso ao Sistema | SDAC</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
*{box-sizing:border-box;}
body{
  margin:0;
  font-family:'Segoe UI',sans-serif;
  background: #e5e5e5; /* cor neutra suave */
}
header{
  background-color: #002d62;
  color:white;
  padding:40px 0;
  text-align:center;
}
header h1{
  font-size:2.2rem;
  font-weight:bold;
}
header .intro-text{
  font-size:1.1rem;
  margin-top:8px;
}
.login-container{
  max-width:450px;
  margin:40px auto;
  background:#ffffffee;
  padding:2.5rem;
  border-radius:15px;
  box-shadow:0 10px 30px rgba(0,0,0,0.15);
}
.login-container h2{
  text-align:center;
  margin-bottom:1.5rem;
  color:#2c3e50;
}
.form-group{margin-bottom:1.2rem;}
.form-group label{
  font-weight:bold;
  margin-bottom:0.4rem;
  display:block;
}
.form-group input{
  width:100%;
  padding:0.75rem;
  border:1px solid #ccc;
  border-radius:5px;
  background:#fff;
}
.form-group input:focus{
  outline:none;
  border-color:#2980b9;
}
.show-password{
  display:flex;
  align-items:center;
  margin-top:0.5rem;
  margin-bottom:1.8rem;
}
.show-password input{
  margin-right:0.5rem;
}
.login-btn{
  width:100%;
  padding:0.8rem;
  background-color:#2980b9;
  color:white;
  border:none;
  border-radius:5px;
  font-size:1rem;
  cursor:pointer;
  transition:background .3s;
}
.login-btn:hover{
  background-color:#1c5980;
}
.login-footer{
  margin-top:2.5rem;
  text-align:center;
  font-size:.9rem;
  color:#555;
}
.error-message{
  color:red;
  font-size:.9rem;
  margin-bottom:1rem;
  text-align:center;
}
</style>
</head>
<body>

<header>
<div class="container">
<h1><i class="bi bi-shield-check"></i> SDAC - Moçambique</h1>
<p class="intro-text"></p>
</div>
</header>

<div class="login-container">
<h2>Acesso ao Sistema</h2>

<?php if($erro): ?>
  <div class="error-message"><?= htmlspecialchars($erro) ?></div>
<?php endif; ?>

<form method="post" id="login-form" action="login.php" autocomplete="off">
<div class="form-group">
<label for="username">Nome de utilizador</label>
<input type="text" id="username" name="username" required value="">
</div>

<div class="form-group position-relative">
<label for="password">Palavra-passe</label>
<input type="password" id="password" name="password" required value="">
<button type="button" id="togglePass" style="position:absolute;top:50%;right:10px;transform:translateY(-50%);border:none;background:none;color:#555;"><i class="bi bi-eye"></i></button>
</div>

<div class="show-password">
<input type="checkbox" id="showPassword">
<label for="showPassword">Mostrar palavra-passe</label>
</div>

<button type="submit" class="login-btn">Entrar</button>
</form>

<div class="login-footer">
<p>© 2025 SDAC - Todos direitos recervados.</p>
</div>
</div>

<script>
const passField = document.getElementById("password");
const toggleBtn = document.getElementById("togglePass");

toggleBtn.addEventListener("click", () => {
    if(passField.type === "password") { 
        passField.type = "text"; 
        toggleBtn.innerHTML = '<i class="bi bi-eye-slash"></i>';
    } else { 
        passField.type = "password"; 
        toggleBtn.innerHTML = '<i class="bi bi-eye"></i>';
    }
});

document.getElementById("showPassword").addEventListener("change", function() {
    passField.type = this.checked ? "text" : "password";
});

window.onload = () => {
    document.getElementById("username").value = '';
    document.getElementById("password").value = '';
};
</script>

</body>
</html>
