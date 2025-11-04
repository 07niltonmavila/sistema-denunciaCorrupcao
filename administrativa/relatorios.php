
<?php
// Conexão ao banco
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sdac";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}

// ======================
// Função AES para descriptografar a descrição
// ======================
function decryptAES($data) {
    $key = "MinhaChaveSuperSecreta2025"; // mesma chave usada no formulário
    $iv = substr(hash('sha256', $key), 0, 16);
    return openssl_decrypt(base64_decode($data), "AES-256-CBC", $key, 0, $iv);
}

// Receber filtros
$data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : "";
$data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : "";
$estado = isset($_GET['estado']) ? $_GET['estado'] : "";

// Query base
$sql = "SELECT d.id, d.protocolo, d.data_acontecimento, d.tipo_denunciante, d.anonimato, 
               d.nome, d.assunto, d.descricao, c.nome_categoria, e.nome_estado
        FROM denuncias d
        LEFT JOIN categorias c ON d.id_categoria = c.id_categoria
        LEFT JOIN estados e ON d.id_estado = e.id_estado
        WHERE 1=1";

// Aplicar filtros dinamicamente
if (!empty($data_inicio) && !empty($data_fim)) {
    $sql .= " AND d.data_acontecimento BETWEEN '$data_inicio' AND '$data_fim'";
}
if (!empty($estado)) {
    $sql .= " AND e.id_estado = " . intval($estado);
}

$sql .= " ORDER BY d.data_envio DESC";
$result = $conn->query($sql);

// Carregar lista de estados para o filtro
$estados_query = $conn->query("SELECT * FROM estados");

// Contadores para gráficos
$pendentes = $encaminhadas = $resolvidas = $arquivadas = 0;
$anonimos = $identificados = $cidadaos = $funcionarios = 0;

$denuncias = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $row['descricao'] = decryptAES($row['descricao']); // Descriptografar descrição
        $denuncias[] = $row;

        // Contagem por estado
        switch (strtolower($row['nome_estado'])) {
            case "pendente": $pendentes++; break;
            case "encaminhada": $encaminhadas++; break;
            case "resolvida": $resolvidas++; break;
            case "arquivada": $arquivadas++; break;
        }

        // Contagem por tipo
        if ($row['anonimato'] == 'sim') {
            $anonimos++;
        } else {
            if ($row['tipo_denunciante'] == 'cidadao') $cidadaos++;
            if ($row['tipo_denunciante'] == 'funcionario') $funcionarios++;
            $identificados++;
        }
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <title>Relatórios de Denúncias</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
  <style>
    body { font-family: 'Segoe UI', sans-serif; background-color: #f5f5f5; color: #333; padding: 20px; }
    h2 { background-color: #004080; color: #fff; padding: 15px; border-radius: 8px; text-align: center; margin-bottom: 30px; }
    .card { border-radius: 10px; }
    .card-header { border-radius: 10px 10px 0 0; }
    table th { background-color: #f5e6d0; }
  </style>
</head>
<body>

<h2>📊 Relatórios de Denúncias</h2>

<a href="painelGestor.php" class="btn btn-secondary">⬅ Voltar ao Painel</a>

<div class="mb-3"></div>

<!-- 🔹 Filtros -->
<div class="card mb-4">
  <div class="card-header bg-info text-white">Filtros</div>
  <div class="card-body">
    <form method="GET" class="row g-3">
      <div class="col-md-3">
        <label for="data_inicio" class="form-label">Data Início</label>
        <input type="date" name="data_inicio" id="data_inicio" class="form-control" value="<?= htmlspecialchars($data_inicio) ?>">
      </div>
      <div class="col-md-3">
        <label for="data_fim" class="form-label">Data Fim</label>
        <input type="date" name="data_fim" id="data_fim" class="form-control" value="<?= htmlspecialchars($data_fim) ?>">
      </div>
      <div class="col-md-3">
        <label for="estado" class="form-label">Estado</label>
        <select name="estado" id="estado" class="form-select">
          <option value="">-- Todos --</option>
          <?php while ($e = $estados_query->fetch_assoc()): ?>
            <option value="<?= $e['id_estado'] ?>" <?= ($estado == $e['id_estado'] ? "selected" : "") ?>>
              <?= htmlspecialchars($e['nome_estado']) ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="col-md-3 d-flex align-items-end">
        <button type="submit" class="btn btn-primary w-100">Filtrar</button>
      </div>
    </form>
  </div>
</div>

<!-- 🔹 Ações -->
<div class="d-flex justify-content-between mb-3">
  <a href="listar_uploads.php" class="btn btn-secondary"> Abrir todos anexos → </a>
  <button onclick="window.print()" class="btn btn-danger">📄 Exportar PDF</button>
</div>

<!-- 🔹 Tabela -->
<div class="card mb-4">
  <div class="card-header bg-primary text-white">Tabela de Resumo</div>
  <div class="card-body table-responsive">
    <table class="table table-bordered table-hover text-center align-middle">
      <thead>
        <tr>
          <th>Protocolo</th>
          <th>Data</th>
          <th>Tipo Denunciante</th>
          <th>Anonimato</th>
          <th>Categoria</th>
          <th>Estado</th>
          <th>Assunto</th>
          <th>Descrição</th>
          <th>Acções</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($denuncias)): ?>
          <?php foreach ($denuncias as $d): ?>
            <tr>
              <td><?= htmlspecialchars($d['protocolo']) ?></td>
              <td><?= date("d/m/Y", strtotime($d['data_acontecimento'])) ?></td>
              <td><?= htmlspecialchars($d['tipo_denunciante']) ?></td>
              <td><?= htmlspecialchars($d['anonimato']) ?></td>
              <td><?= htmlspecialchars($d['nome_categoria']) ?></td>
              <td><?= htmlspecialchars($d['nome_estado']) ?></td>
              <td><?= htmlspecialchars($d['assunto']) ?></td>
              <td><?= htmlspecialchars($d['descricao']) ?></td>
              <td>
                <a href="detalhes_relatorio.php?id=<?= $d['id'] ?>" class="btn btn-sm btn-info">
                  Ver Historico
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="9">Nenhuma denúncia encontrada</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<!-- 🔹 Gráficos -->
<div class="row">
  <div class="col-md-6">
    <div class="card">
      <div class="card-header bg-primary text-white">Estados das Denúncias</div>
      <div class="card-body">
        <canvas id="statusChart"></canvas>
      </div>
    </div>
  </div>

  <div class="col-md-6">
    <div class="card">
      <div class="card-header bg-success text-white">Tipos de Denunciante</div>
      <div class="card-body">
        <canvas id="tipoChart"></canvas>
      </div>
    </div>
  </div>
</div>

<script>
new Chart(document.getElementById('statusChart').getContext('2d'), {
  type: 'doughnut',
  data: {
    labels: ['Pendentes', 'Encaminhadas', 'Resolvidas', 'Arquivadas'],
    datasets: [{
      data: [<?= $pendentes ?>, <?= $encaminhadas ?>, <?= $resolvidas ?>, <?= $arquivadas ?>],
      backgroundColor: ['#ffc107', '#0d6efd', '#28a745', '#6c757d']
    }]
  },
  options: {
    responsive: true,
    plugins: {
      legend: { position: 'bottom' },
      datalabels: { color: '#fff', font: { weight: 'bold', size: 14 }, formatter: value => value }
    }
  },
  plugins: [ChartDataLabels]
});

new Chart(document.getElementById('tipoChart').getContext('2d'), {
  type: 'bar',
  data: {
    labels: ['Anónimos', 'Identificados', 'Cidadãos', 'Funcionários'],
    datasets: [{
      label: 'Qtd',
      data: [<?= $anonimos ?>, <?= $identificados ?>, <?= $cidadaos ?>, <?= $funcionarios ?>],
      backgroundColor: ['#198754', '#0d6efd', '#fd7e14', '#6f42c1']
    }]
  },
  options: {
    responsive: true,
    plugins: {
      datalabels: { anchor: 'end', align: 'top', color: '#000', font: { weight: 'bold' } },
      legend: { display: false }
    },
    scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
  },
  plugins: [ChartDataLabels]
});
</script>

</body>
</html>

