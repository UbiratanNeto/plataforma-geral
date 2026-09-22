<?php
/**
 * painel/scripts/cargos/listar.php
 * Devolve a lista de cargos em JSON puro pro DataTables (assets/js/cargos.js).
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

try {
    $stmt = $pdo->query("SELECT id, nome FROM cargos ORDER BY nome ASC");
    $cargos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = [];
    foreach ($cargos as $cargo) {
        $data[] = [
            'id'   => (int) $cargo['id'],
            'nome' => $cargo['nome'],
        ];
    }

    resp(true, '', ['data' => $data]);
} catch (PDOException $e) {
    resp(false, 'Erro ao carregar cargos: ' . $e->getMessage());
}
