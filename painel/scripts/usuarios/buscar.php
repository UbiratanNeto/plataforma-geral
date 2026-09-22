<?php
/**
 * painel/scripts/usuarios/buscar.php
 * Devolve os dados de um usuário específico, pra pré-preencher o modal de edição.
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

$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    resp(false, 'Usuário não encontrado.');
}

unset($usuario['senha']); // Nunca envia o hash de senha para a tela

$fotoExiste = !empty($usuario['foto']) && file_exists(__DIR__ . '/../../../uploads/perfil/' . $usuario['foto']);
$usuario['foto_url'] = $fotoExiste ? '../uploads/perfil/' . $usuario['foto'] : '../uploads/perfil/sem_foto.png';

// Permissões individuais (tela de Permissões) e se o cargo dele já dá acesso total
$stmtPerm = $pdo->prepare("SELECT menu_id FROM usuario_permissoes WHERE usuario_id = ?");
$stmtPerm->execute([$id]);
$usuario['permissoes'] = $stmtPerm->fetchAll(PDO::FETCH_COLUMN);

$usuario['acesso_total'] = 0;
if (!empty($usuario['cargo_id'])) {
    $stmtCargo = $pdo->prepare("SELECT acesso_total FROM cargos WHERE id = ?");
    $stmtCargo->execute([$usuario['cargo_id']]);
    $usuario['acesso_total'] = (int) $stmtCargo->fetchColumn();
}

resp(true, '', ['data' => $usuario]);
