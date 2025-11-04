<?php


// Funções AES para descriptografar a descrição

function decifrar ($data) {
    $key = "MinhaChaveSuperSecreta2025"; // mesma chave usada no formulário
    $iv = substr(hash('sha256', $key), 0, 16);
    return openssl_decrypt(base64_decode($data), "AES-256-CBC", $key, 0, $iv);
}

// Conexão ao banco
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sdac";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}

// Receber ID da denúncia
$id_denuncia = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id_denuncia <= 0) {
    die("Denúncia inválida.");
}

// Buscar dados da denúncia
$sql_denuncia = "
SELECT d.*, c.nome_categoria, e.nome_estado, u.nome AS denunciante
FROM denuncias d
LEFT JOIN categorias c ON d.id_categoria = c.id_categoria
LEFT JOIN estados e ON d.id_estado = e.id_estado
LEFT JOIN usuarios u ON d.id_denunciante = u.id_usuario
WHERE d.id = $id_denuncia
";
$result_denuncia = $conn->query($sql_denuncia);
if ($result_denuncia->num_rows == 0) {
    die("Denúncia não encontrada.");
}
$denuncia = $result_denuncia->fetch_assoc();


// Histórico de alterações

$sql_historico = "
SELECT h.*, 
       e1.nome_estado AS estado_anterior_nome, 
       e2.nome_estado AS estado_novo_nome, 
       u.nome AS alterado_por_nome
FROM historico_denuncia h
LEFT JOIN estados e1 ON h.estado_anterior = e1.id_estado
LEFT JOIN estados e2 ON h.estado_novo = e2.id_estado
LEFT JOIN usuarios u ON h.alterado_por = u.id_usuario
WHERE h.id_denuncia = $id_denuncia
ORDER BY h.data_alteracao ASC
";
$result_historico = $conn->query($sql_historico);

// Órgãos encaminhados

$sql_encaminhamentos = "
SELECT o.nome_orgao, enc.data_encaminhamento
FROM encaminhamentos enc
LEFT JOIN orgaos o ON enc.id_orgao = o.id_orgao
WHERE enc.id_denuncia = $id_denuncia
ORDER BY enc.data_encaminhamento ASC
";
$result_encaminhamentos = $conn->query($sql_encaminhamentos);

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Detalhes da Denúncia - <?= htmlspecialchars($denuncia['protocolo']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { font-family: 'Segoe UI', sans-serif; background-color: #f5f5f5; padding: 20px; }
h2 { background-color: #004080; color: #fff; padding: 15px; border-radius: 8px; text-align: center; margin-bottom: 30px; }
.card { border-radius: 10px; margin-bottom: 20px; }
.card-header { border-radius: 10px 10px 0 0; }
table th { background-color: #f5e6d0; }
</style>
</head>
<body>

<h2>📝 Detalhes da Denúncia - <?= htmlspecialchars($denuncia['protocolo']) ?></h2>




<div class="d-flex justify-content-between mt-4">
    <a href="relatorios.php" class="btn btn-secondary">⬅ Voltar aos Relatórios</a>
    <button onclick="window.print()" class="btn btn-danger">📄 Exportar PDF</button>
</div>

<dic class="-4"></div>
<h2></h2>
<!-- <p><strong>Descrição:</strong> <!= nl2br(htmlspecialchars(decifrar($denuncia['descricao'])) )?>-->
<!-- 🔹 Dados da denúncia -->
<div class="card">
    <div class="card-header bg-primary text-white">Informações Gerais</div>
    <div class="card-body">
        <p><strong>Protocolo:</strong> <?= htmlspecialchars($denuncia['protocolo']) ?></p>
        <p><strong>Data do Acontecimento:</strong> <?= date("d/m/Y", strtotime($denuncia['data_acontecimento'])) ?></p>
        <p><strong>Hora:</strong> <?= $denuncia['hora'] ?? 'Não informada' ?></p>
        <p><strong>Tipo Denunciante:</strong> <?= htmlspecialchars($denuncia['tipo_denunciante'] ?? 'Anónimo') ?></p>
        <p><strong>Anonimato:</strong> <?= htmlspecialchars($denuncia['anonimato']) ?></p>
        <p><strong>Nome do Denunciante:</strong> <?= htmlspecialchars($denuncia['nome'] ?? '-') ?></p>
        <p><strong>Telefone:</strong> <?= htmlspecialchars($denuncia['telefone'] ?? '-') ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($denuncia['email'] ?? '-') ?></p>
        <p><strong>Categoria:</strong> <?= htmlspecialchars($denuncia['nome_categoria'] ?? '-') ?></p>
        <p><strong>Estado Atual:</strong> <?= htmlspecialchars($denuncia['nome_estado']) ?></p>
        <p><strong>Assunto:</strong> <?= htmlspecialchars($denuncia['assunto']) ?></p>
        <p><strong>Descrição:</strong> <?= nl2br(htmlspecialchars($denuncia['descricao'])) ?></p>
        <?php if (!empty($denuncia['anexo'])): ?>
            
<p><strong>Anexo:</strong> <a href="uploads/<?= htmlspecialchars($denuncia['anexo']) ?>" target="_blank"><?= htmlspecialchars($denuncia['anexo']) ?></a></p>
        <?php endif; ?>

  


    </div>
</div>

<!-- 🔹 Histórico de alterações -->
<div class="card">
    <div class="card-header bg-info text-white">Histórico de Alterações</div>
    <div class="card-body table-responsive">
        <table class="table table-bordered table-hover text-center align-middle">
            <thead>
                <tr>
                    <th>Data/Hora</th>
                    <th>Alterado Por</th>
                    <th>Assunto Anterior</th>
                    <th>Assunto Novo</th>
                    <th>Estado Anterior</th>
                    <th>Estado Novo</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($result_historico->num_rows > 0): ?>
                <?php while ($h = $result_historico->fetch_assoc()): ?>
                <tr>
                    <td><?= date("d/m/Y H:i:s", strtotime($h['data_alteracao'])) ?></td>
                    <td><?= htmlspecialchars($h['alterado_por_nome']) ?></td>
                    <td><?= htmlspecialchars($h['assunto_anterior'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($h['assunto_novo'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($h['estado_anterior_nome'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($h['estado_novo_nome'] ?? '-') ?></td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6">Nenhuma alteração registrada</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- 🔹 Órgãos encaminhados -->
<div class="card">
    <div class="card-header bg-success text-white">Órgãos Encaminhados</div>
    <div class="card-body table-responsive">
        <table class="table table-bordered table-hover text-center align-middle">
            <thead>
                <tr>
                    <th>Órgão</th>
                    <th>Data de Encaminhamento</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($result_encaminhamentos->num_rows > 0): ?>
                <?php while ($o = $result_encaminhamentos->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($o['nome_orgao']) ?></td>
                    <td><?= date("d/m/Y H:i:s", strtotime($o['data_encaminhamento'])) ?></td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="2">Nenhum encaminhamento registrado</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div style="display: flex; justify-content:center; align-items: center; height:100px;">
<p >  © 2025 SDAC - Todos os direitos reservados. </p> </div>

</body>
</html>

