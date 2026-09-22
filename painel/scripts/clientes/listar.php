<?php
/**
 * painel/scripts/clientes/listar.php
 * Devolve a lista de clientes em JSON puro pro DataTables (assets/js/clientes.js).
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
    $stmt = $pdo->query("SELECT id, nome, email, telefone, tipo, foto, ativo FROM clientes ORDER BY nome ASC");
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = [];
    foreach ($clientes as $cliente) {
        $fotoExiste = !empty($cliente['foto']) && file_exists(__DIR__ . '/../../../uploads/clientes/' . $cliente['foto']);

        $data[] = [
            'id'       => (int) $cliente['id'],
            'nome'     => $cliente['nome'],
            'email'    => $cliente['email'],
            'telefone' => $cliente['telefone'],
            'tipo'     => $cliente['tipo'],
            'ativo'    => $cliente['ativo'],
            'foto_url' => $fotoExiste ? '../uploads/clientes/' . $cliente['foto'] : '../uploads/clientes/sem_foto.png',
        ];
    }

    resp(true, '', ['data' => $data]);
} catch (PDOException $e) {
    resp(false, 'Erro ao carregar clientes: ' . $e->getMessage());
}
