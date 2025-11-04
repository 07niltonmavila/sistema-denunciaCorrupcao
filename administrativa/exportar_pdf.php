<?php
require 'vendor/autoload.php';
use Dompdf\Dompdf;

// Conexão BD
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sdac";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) { die("Erro de conexão: " . $conn->connect_error); }

// Recebe filtros
$data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : "";
$data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : "";
$estado = isset($_GET['estado']) ? $_GET['estado'] : "";

// Query
$sql = "SELECT d.protocolo, d.data_acontecimento, d.tipo_denunciante, d.anonimato, 
               d.nome, d.assunto, d.descricao, c.nome_categoria, e.nome_estado
        FROM denuncias d
        LEFT JOIN categorias c ON d.id_categoria = c.id_categoria
        LEFT JOIN estados e ON d.id_estado = e.id_estado
        WHERE 1=1";

if (!empty($data_inicio) && !empty($data_fim)) {
    $sql .= " AND d.data_acontecimento BETWEEN '$data_inicio' AND '$data_fim'";
}
if (!empty($estado)) {
    $sql .= " AND e.id_estado = " . intval($estado);
}

$sql .= " ORDER BY d.data_envio DESC";
$result = $conn->query($sql);

// Monta tabela HTML
$html = "<h2 style='text-align:center;'>Relatório de Denúncias</h2>";
$html .= "<table border='1' cellspacing='0' cellpadding='5' width='100%'>
            <thead>
              <tr>
                <th>Protocolo</th>
                <th>Data</th>
                <th>Tipo</th>
                <th>Anonimato</th>
                <th>Categoria</th>
                <th>Estado</th>
                <th>Assunto</th>
              </tr>
            </thead>
            <tbody>";

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $html .= "<tr>
                    <td>{$row['protocolo']}</td>
                    <td>".date("d/m/Y", strtotime($row['data_acontecimento']))."</td>
                    <td>{$row['tipo_denunciante']}</td>
                    <td>{$row['anonimato']}</td>
                    <td>{$row['nome_categoria']}</td>
                    <td>{$row['nome_estado']}</td>
                    <td>{$row['assunto']}</td>
                  </tr>";
    }
} else {
    $html .= "<tr><td colspan='7'>Nenhuma denúncia encontrada</td></tr>";
}
$html .= "</tbody></table>";

$conn->close();

// Gerar PDF
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$dompdf->stream("relatorio_denuncias.pdf", ["Attachment" => true]);
?>
