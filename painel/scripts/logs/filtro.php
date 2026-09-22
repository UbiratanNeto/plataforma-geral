<?php
/**
 * painel/scripts/logs/filtro.php
 * Monta o WHERE de data usado tanto na listagem (listar.php) quanto no PDF (relatorio.php) —
 * evita duplicar a mesma validação/lógica nos dois lugares.
 */

/**
 * @param array $get Normalmente $_GET, com 'data_inicial'/'data_final' no formato YYYY-MM-DD
 *                    (é o que o <input type="date"> manda) e 'usuario' (texto livre, nome do
 *                    funcionário). Assume que a query principal já faz LEFT JOIN usuarios u.
 * @return array{0: string, 1: array} [SQL do WHERE (ou string vazia), parâmetros pra bindar]
 */
function montarFiltroLogs(array $get): array
{
    $dataInicial = trim($get['data_inicial'] ?? '');
    $dataFinal   = trim($get['data_final'] ?? '');
    $usuario     = trim($get['usuario'] ?? '');
    $acao        = trim($get['acao'] ?? '');

    // Valor fixo (ENUM da coluna) — só aceita um dos 5 conhecidos, ignora qualquer outra coisa.
    $acoesValidas = ['login', 'logout', 'inserir', 'editar', 'excluir'];
    if (!in_array($acao, $acoesValidas, true)) {
        $acao = '';
    }

    $dataValidaRegex = '/^\d{4}-\d{2}-\d{2}$/';
    if (!preg_match($dataValidaRegex, $dataInicial)) {
        $dataInicial = '';
    }
    if (!preg_match($dataValidaRegex, $dataFinal)) {
        $dataFinal = '';
    }

    $condicoes = [];
    $params    = [];

    if ($dataInicial !== '') {
        $condicoes[] = 'l.criado_em >= :data_inicial';
        $params[':data_inicial'] = $dataInicial . ' 00:00:00';
    }
    if ($dataFinal !== '') {
        $condicoes[] = 'l.criado_em <= :data_final';
        $params[':data_final'] = $dataFinal . ' 23:59:59';
    }
    if ($usuario !== '') {
        $condicoes[] = 'u.nome LIKE :usuario';
        $params[':usuario'] = '%' . $usuario . '%';
    }
    if ($acao !== '') {
        $condicoes[] = 'l.acao = :acao';
        $params[':acao'] = $acao;
    }

    $whereSql = $condicoes ? ('WHERE ' . implode(' AND ', $condicoes)) : '';

    return [$whereSql, $params];
}
