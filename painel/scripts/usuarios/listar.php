<?php
/**
 * painel/scripts/usuarios/listar.php
 * Devolve a lista de usuários em JSON puro (sem HTML embutido) — o front-end
 * (painel/assets/js/usuarios.js) monta as linhas da tabela com DOM/textContent,
 * o que evita qualquer risco de HTML malicioso vindo de um nome/e-mail salvo no banco.
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
    // JOIN com cargos só pra saber se é um cargo de acesso_total (ex.: Administrador) —
    // usado no front pra desabilitar o botão de Permissões nesses usuários.
    $stmt = $pdo->query("
        SELECT u.id, u.nome, u.email, u.telefone, u.foto, u.nivel, u.ativo,
               COALESCE(c.acesso_total, 0) AS acesso_total
        FROM usuarios u
        LEFT JOIN cargos c ON c.id = u.cargo_id
        ORDER BY u.id DESC
    ");
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = [];
    foreach ($usuarios as $user) {
        $fotoExiste = !empty($user['foto']) && file_exists(__DIR__ . '/../../../uploads/perfil/' . $user['foto']);

        $data[] = [
            'id'           => (int) $user['id'],
            'nome'         => $user['nome'],
            'email'        => $user['email'],
            'telefone'     => $user['telefone'],
            'nivel'        => $user['nivel'],
            'ativo'        => (int) $user['ativo'],
            'acesso_total' => (int) $user['acesso_total'],
            'foto_url'     => $fotoExiste ? '../uploads/perfil/' . $user['foto'] : '../uploads/perfil/sem_foto.png',
        ];
    }

    resp(true, '', ['data' => $data]);
} catch (PDOException $e) {
    resp(false, 'Erro ao carregar usuários: ' . $e->getMessage());
}
