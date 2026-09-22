<?php
/**
 * painel/scripts/clientes/buscar.php
 * Devolve os dados de um cliente específico, pra pré-preencher o modal de edição.
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

$stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
$stmt->execute([$id]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cliente) {
    resp(false, 'Cliente não encontrado.');
}

unset($cliente['senha']); // Nunca envia o hash de senha para a tela

$fotoExiste = !empty($cliente['foto']) && file_exists(__DIR__ . '/../../../uploads/clientes/' . $cliente['foto']);
$cliente['foto_url'] = $fotoExiste ? '../uploads/clientes/' . $cliente['foto'] : '../uploads/clientes/sem_foto.png';

resp(true, '', ['data' => $cliente]);
