<?php
/**
 * painel/scripts/cargos/buscar.php
 * Devolve os dados de um cargo (nome + acesso_total), pra pré-preencher o modal de edição.
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

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    resp(false, 'ID inválido.');
}

$stmt = $pdo->prepare("SELECT id, nome, acesso_total FROM cargos WHERE id = ?");
$stmt->execute([$id]);
$cargo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cargo) {
    resp(false, 'Cargo não encontrado.');
}

$cargo['acesso_total'] = (int) $cargo['acesso_total'];

resp(true, '', ['data' => $cargo]);
