<?php
/**
 * painel/scripts/logs/listar.php
 * Devolve o histórico de logs em JSON puro (sem HTML embutido) — o front-end
 * (painel/assets/js/logs.js) monta as linhas da tabela com DOM/textContent.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

function resp($ok, $msg, $extra = []) {
    echo json_encode(array_merge(['ok' => $ok, 'msg' => $msg], $extra), JSON_UNESCAPED_UNICODE);
    exit();
}

if (empty($_SESSION['id'])) {
    resp(false, 'Sessão expirada.');
}

require_once __DIR__ . '/../../../conexao.php';
require_once __DIR__ . '/filtro.php';

try {
    [$whereSql, $params] = montarFiltroLogs($_GET);

    // JOIN com usuarios só pra mostrar o nome de quem fez a ação — usuario_id = 0
    // (ação do sistema, sem sessão) ou usuário já excluído caem no fallback "Sistema".
    $stmt = $pdo->prepare("
        SELECT l.id, l.usuario_id, l.acao, l.entidade, l.registro_id, l.descricao,
               l.rota, l.ip, l.criado_em, u.nome AS usuario_nome
        FROM logs l
        LEFT JOIN usuarios u ON u.id = l.usuario_id
        {$whereSql}
        ORDER BY l.criado_em DESC
        LIMIT 1000
    ");
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = [];
    foreach ($logs as $log) {
        $data[] = [
            'id'           => (int) $log['id'],
            'usuario_nome' => $log['usuario_nome'] ?: 'Sistema',
            'acao'         => $log['acao'],
            'entidade'     => $log['entidade'],
            'registro_id'  => $log['registro_id'] !== null ? (int) $log['registro_id'] : null,
            'descricao'    => $log['descricao'],
            'ip'           => $log['ip'],
            'criado_em'    => $log['criado_em'],
        ];
    }

    resp(true, '', ['data' => $data]);
} catch (PDOException $e) {
    resp(false, 'Erro ao carregar logs: ' . $e->getMessage());
}
