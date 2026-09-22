<?php
/**
 * painel/includes/permissoes.php
 * Checagem de acesso por usuário (não por cargo). A única coisa que o cargo ainda decide
 * é o bypass total: um cargo com acesso_total = 1 (ex.: Administrador) faz o usuário ver
 * tudo, sem precisar de nenhuma linha em usuario_permissoes.
 */

/**
 * Devolve as chaves (slugs) de painel/includes/menus.php que o usuário pode acessar.
 * Usuário cujo cargo tem acesso_total = 1 recebe todas as páginas do catálogo automaticamente.
 *
 * @return string[] lista de slugs permitidos (ex.: ['dashboard', 'chamados'])
 */
function menusPermitidos(PDO $pdo, ?int $usuarioId, ?int $cargoId): array {
    $catalogo = require __DIR__ . '/menus.php';
    $todasPaginas = array_keys($catalogo['paginas']);

    if ($cargoId) {
        $stmt = $pdo->prepare("SELECT acesso_total FROM cargos WHERE id = ?");
        $stmt->execute([$cargoId]);
        if ($stmt->fetchColumn()) {
            return $todasPaginas;
        }
    }

    if (!$usuarioId) {
        return []; // Sem usuário logado = sem acesso a nada
    }

    $stmt = $pdo->prepare("SELECT menu_id FROM usuario_permissoes WHERE usuario_id = ?");
    $stmt->execute([$usuarioId]);
    $permitidos = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Filtra contra o catálogo real: uma permissão salva pra uma página que não existe
    // mais no array não deve "vazar" acesso a nada.
    return array_values(array_intersect($permitidos, $todasPaginas));
}

/**
 * Confere se o usuário pode acessar uma página específica.
 */
function podeAcessarPagina(PDO $pdo, ?int $usuarioId, ?int $cargoId, string $pagina): bool {
    return in_array($pagina, menusPermitidos($pdo, $usuarioId, $cargoId), true);
}

/**
 * Permissão de ação (criar/editar/excluir) — diferente de menusPermitidos(), essa é
 * GLOBAL, não por página: vale pra qualquer tela (Usuários, Cargos, Clientes, e as que
 * vierem depois), porque todas usam o mesmo padrão de botões novo()/editar()/excluir().
 * Cargo com acesso_total continua irrestrito, igual ao acesso a menus.
 *
 * @param string $acao 'criar' | 'editar' | 'excluir'
 */
function podeExecutarAcao(PDO $pdo, ?int $usuarioId, ?int $cargoId, string $acao): bool {
    $colunas = [
        'criar'   => 'permissao_criar',
        'editar'  => 'permissao_editar',
        'excluir' => 'permissao_excluir',
    ];
    if (!isset($colunas[$acao])) {
        return false; // ação desconhecida — nega por padrão, não libera por engano
    }

    if ($cargoId) {
        $stmt = $pdo->prepare("SELECT acesso_total FROM cargos WHERE id = ?");
        $stmt->execute([$cargoId]);
        if ($stmt->fetchColumn()) {
            return true;
        }
    }

    if (!$usuarioId) {
        return false;
    }

    $coluna = $colunas[$acao];
    $stmt = $pdo->prepare("SELECT {$coluna} FROM usuarios WHERE id = ?");
    $stmt->execute([$usuarioId]);
    return (bool) $stmt->fetchColumn();
}

/**
 * Devolve as 3 permissões de ação do usuário logado de uma vez, prontas pra virar
 * JSON e alimentar window.PERMISSOES_ACAO no front-end.
 *
 * @return array{criar: bool, editar: bool, excluir: bool}
 */
function permissoesAcaoUsuarioLogado(PDO $pdo, ?int $usuarioId, ?int $cargoId): array {
    return [
        'criar'   => podeExecutarAcao($pdo, $usuarioId, $cargoId, 'criar'),
        'editar'  => podeExecutarAcao($pdo, $usuarioId, $cargoId, 'editar'),
        'excluir' => podeExecutarAcao($pdo, $usuarioId, $cargoId, 'excluir'),
    ];
}
