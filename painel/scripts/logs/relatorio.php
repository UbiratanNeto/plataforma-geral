<?php
/**
 * painel/scripts/logs/relatorio.php
 * Gera o relatório de Logs em PDF (DomPDF), respeitando o mesmo filtro de data da tela
 * (painel/scripts/logs/filtro.php, compartilhado com listar.php). Cabeçalho, CSS base e
 * configuração do PDF vêm de painel/funcoes/relatorio_pdf.php, compartilhado com os
 * outros relatórios (Clientes, ...).
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['id'])) {
    http_response_code(403);
    exit('Sessão expirada.');
}

require_once __DIR__ . '/../../../conexao.php';
require_once __DIR__ . '/filtro.php';
require_once __DIR__ . '/../../funcoes/relatorio_pdf.php';

[$whereSql, $params] = montarFiltroLogs($_GET);

$stmt = $pdo->prepare("
    SELECT l.acao, l.entidade, l.registro_id, l.descricao, l.ip, l.criado_em, u.nome AS usuario_nome
    FROM logs l
    LEFT JOIN usuarios u ON u.id = l.usuario_id
    {$whereSql}
    ORDER BY l.criado_em DESC
");
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$dataInicial  = $_GET['data_inicial'] ?? '';
$dataFinal    = $_GET['data_final'] ?? '';
$usuarioBusca = trim($_GET['usuario'] ?? '');

$periodo = ($dataInicial !== '' || $dataFinal !== '')
    ? sprintf(
        'Período: %s até %s',
        $dataInicial !== '' ? date('d/m/Y', strtotime($dataInicial)) : 'início',
        $dataFinal !== '' ? date('d/m/Y', strtotime($dataFinal)) : 'hoje'
    )
    : 'Período: Todos os registros';

if ($usuarioBusca !== '') {
    $periodo .= ' — Usuário: "' . $usuarioBusca . '"';
}

$acaoLabel = ['login' => 'Login', 'logout' => 'Logout', 'inserir' => 'Inserir', 'editar' => 'Editar', 'excluir' => 'Excluir'];

$acaoFiltro = $_GET['acao'] ?? '';
if ($acaoFiltro !== '' && isset($acaoLabel[$acaoFiltro])) {
    $periodo .= ' — Ação: ' . $acaoLabel[$acaoFiltro];
}

$cabecalho = cabecalhoRelatorio(
    'Relatório de Logs do Sistema',
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
    .b-login { background: #0d6efd; }
    .b-logout { background: #6c757d; }
    .b-inserir { background: #198754; }
    .b-editar { background: #e0a800; color: #000; }
    .b-excluir { background: #dc3545; }
</style>
</head>
<body>
    <?php echo $cabecalho; ?>

    <table>
        <thead>
            <tr>
                <th>Data/Hora</th>
                <th>Usuário</th>
                <th>Ação</th>
                <th>Entidade</th>
                <th>Descrição</th>
                <th>IP</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($logs)): ?>
            <tr><td colspan="6" style="text-align:center; color:#94a3b8;">Nenhum registro encontrado nesse período.</td></tr>
            <?php else: foreach ($logs as $log): ?>
            <tr>
                <td><?php echo date('d/m/Y H:i:s', strtotime($log['criado_em'])); ?></td>
                <td><?php echo htmlspecialchars($log['usuario_nome'] ?: 'Sistema'); ?></td>
                <td><span class="badge b-<?php echo htmlspecialchars($log['acao']); ?>"><?php echo htmlspecialchars($acaoLabel[$log['acao']] ?? $log['acao']); ?></span></td>
                <td><?php echo htmlspecialchars($log['entidade']); ?></td>
                <td><?php echo htmlspecialchars($log['descricao'] ?? '-'); ?></td>
                <td><?php echo htmlspecialchars($log['ip'] ?? '-'); ?></td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>

    <p class="rodape">Total de registros: <?php echo count($logs); ?></p>

    <?php echo blocoAssinaturaRelatorio($pdo); ?>
</body>
</html>
<?php
$html = ob_get_clean();

$dompdf = criarDompdfRelatorio($html, 'landscape');

// Attachment=false -> abre no visualizador de PDF do próprio navegador (aba nova),
// em vez de forçar um download direto.
$dompdf->stream('relatorio-logs-' . date('Y-m-d_His') . '.pdf', ['Attachment' => false]);
