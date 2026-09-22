<?php
/**
 * painel/scripts/clientes/relatorio.php
 * Gera o relatório de Clientes em PDF (DomPDF). Mesmos campos exibidos em
 * painel/scripts/clientes/listar.php (nome, e-mail, telefone, tipo, status).
 * Cabeçalho/CSS base/config do PDF vêm de painel/funcoes/relatorio_pdf.php,
 * compartilhado com o relatório de Logs.
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
require_once __DIR__ . '/../../funcoes/relatorio_pdf.php';

if (!podeAcessarPagina($pdo, $_SESSION['id'] ?? null, $_SESSION['cargo_id'] ?? null, 'clientes')) {
    http_response_code(403);
    exit('Você não tem permissão para acessar Clientes.');
}

$stmt = $pdo->query("SELECT id, nome, email, telefone, ddi, tipo, ativo FROM clientes ORDER BY nome ASC");
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$periodo = sprintf('Total de %d cliente(s) cadastrado(s)', count($clientes));

$cabecalho = cabecalhoRelatorio(
    'Relatório de Clientes',
    $nome_sistema ?? null,
    $logo ?? null,
    $telefone_sistema ?? null,
    $email_sistema ?? null,
    $endereco ?? null,
    $periodo
);

ob_start();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    <?php echo estiloBaseRelatorio(); ?>
    .b-ativo { background: #198754; }
    .b-inativo { background: #dc3545; }
</style>
</head>
<body>
    <?php echo $cabecalho; ?>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>E-mail</th>
                <th>Telefone</th>
                <th>Tipo</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($clientes)): ?>
            <tr><td colspan="6" style="text-align:center; color:#94a3b8;">Nenhum cliente cadastrado.</td></tr>
            <?php else: foreach ($clientes as $cliente): ?>
            <tr>
                <td><?php echo (int) $cliente['id']; ?></td>
                <td><?php echo htmlspecialchars($cliente['nome']); ?></td>
                <td><?php echo htmlspecialchars($cliente['email'] ?: '-'); ?></td>
                <td><?php echo htmlspecialchars($cliente['telefone'] ? '+' . $cliente['ddi'] . ' ' . $cliente['telefone'] : '-'); ?></td>
                <td><?php echo htmlspecialchars($cliente['tipo'] ?: '-'); ?></td>
                <td>
                    <?php $ativo = $cliente['ativo'] === 'Sim'; ?>
                    <span class="badge <?php echo $ativo ? 'b-ativo' : 'b-inativo'; ?>"><?php echo $ativo ? 'Ativo' : 'Inativo'; ?></span>
                </td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>

    <p class="rodape">Total de registros: <?php echo count($clientes); ?></p>

    <?php echo blocoAssinaturaRelatorio($pdo); ?>
</body>
</html>
<?php
$html = ob_get_clean();

$dompdf = criarDompdfRelatorio($html, 'landscape');

$dompdf->stream('relatorio-clientes-' . date('Y-m-d_His') . '.pdf', ['Attachment' => false]);
