<?php
/**
 * painel/scripts/cargos/salvar.php
 * Cria ou atualiza um cargo (nome + acesso_total).
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    resp(false, 'Método inválido.');
}

require_once __DIR__ . '/../../../conexao.php';
require_once __DIR__ . '/../../includes/permissoes.php';

$id           = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$nome         = trim($_POST['nome'] ?? '');
$acesso_total = isset($_POST['acesso_total']) ? 1 : 0;

// Permissão de AÇÃO (global, não por página) — criar ou editar dependendo se veio um $id
$acao = $id ? 'editar' : 'criar';
if (!podeExecutarAcao($pdo, $_SESSION['id'] ?? null, $_SESSION['cargo_id'] ?? null, $acao)) {
    resp(false, 'Você não tem permissão para ' . ($id ? 'editar' : 'criar') . ' cargos.');
}

if ($nome === '') {
    resp(false, 'Informe o nome do cargo.');
}

try {
    // Nome duplicado (considerando outros cargos, não o próprio ao editar)
    $stmtDup = $pdo->prepare("SELECT id FROM cargos WHERE nome = ? AND id != ?");
    $stmtDup->execute([$nome, $id ?? 0]);
    if ($stmtDup->fetch()) {
        resp(false, 'Já existe um cargo com esse nome.');
    }

    if ($id) {
        $stmt = $pdo->prepare("UPDATE cargos SET nome = :nome, acesso_total = :acesso_total WHERE id = :id");
        $stmt->execute([':nome' => $nome, ':acesso_total' => $acesso_total, ':id' => $id]);
        resp(true, 'Cargo atualizado com sucesso!');
    } else {
        $stmt = $pdo->prepare("INSERT INTO cargos (nome, acesso_total, empresa) VALUES (:nome, :acesso_total, :empresa)");
        $stmt->execute([':nome' => $nome, ':acesso_total' => $acesso_total, ':empresa' => $_SESSION['id_empresa'] ?? 0]);
        resp(true, 'Cargo cadastrado com sucesso!');
    }
} catch (PDOException $e) {
    resp(false, 'Erro no banco de dados: ' . $e->getMessage());
}
