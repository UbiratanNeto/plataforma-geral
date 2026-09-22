<?php
/**
 * painel/scripts/clientes/exportar.php
 * Exporta todos os clientes pra um arquivo .xlsx (PhpSpreadsheet). A coluna "ID" vem
 * vazia pra linhas novas e preenchida pras existentes — é o que painel/scripts/clientes/
 * importar.php usa depois pra decidir entre INSERT e UPDATE num reimport.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['id'])) {
    http_response_code(403);
    exit('Sessão expirada.');
}

require_once __DIR__ . '/../../../conexao.php';
require_once __DIR__ . '/../../includes/permissoes.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

if (!podeAcessarPagina($pdo, $_SESSION['id'] ?? null, $_SESSION['cargo_id'] ?? null, 'clientes')) {
    http_response_code(403);
    exit('Você não tem permissão para acessar Clientes.');
}

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$stmt = $pdo->query("
    SELECT id, nome, email, telefone, ddi, cpf_cnpj, tipo, ativo, notificar_cadastro,
           cep, endereco, numero, complemento, bairro, cidade, estado, observacoes
    FROM clientes
    ORDER BY nome ASC
");
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$colunas = [
    'ID', 'Nome', 'E-mail', 'Telefone', 'DDI', 'CPF/CNPJ', 'Tipo', 'Ativo', 'Notificar Cadastro',
    'CEP', 'Endereço', 'Número', 'Complemento', 'Bairro', 'Cidade', 'Estado', 'Observações',
];

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Clientes');
$sheet->fromArray($colunas, null, 'A1');
$sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->getFont()->setBold(true);

$linha = 2;
foreach ($clientes as $cliente) {
    $sheet->fromArray([
        $cliente['id'],
        $cliente['nome'],
        $cliente['email'],
        $cliente['telefone'],
        $cliente['ddi'],
        $cliente['cpf_cnpj'],
        $cliente['tipo'],
        $cliente['ativo'],
        $cliente['notificar_cadastro'],
        $cliente['cep'],
        $cliente['endereco'],
        $cliente['numero'],
        $cliente['complemento'],
        $cliente['bairro'],
        $cliente['cidade'],
        $cliente['estado'],
        $cliente['observacoes'],
    ], null, "A{$linha}");
    $linha++;
}

foreach (range('A', $sheet->getHighestColumn()) as $coluna) {
    $sheet->getColumnDimension($coluna)->setAutoSize(true);
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="clientes-' . date('Y-m-d_His') . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
