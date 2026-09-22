<?php
/**
 * painel/scripts/testar_ia.php
 * Botão "Testar IA" do modal de Configurações — pergunta algo real pro provedor de IA
 * configurado, só pra confirmar que o token e o modelo estão funcionando.
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

require_once __DIR__ . '/../../conexao.php';
require_once __DIR__ . '/../apis/ia.php';

$resultado = perguntarIA('Dê uma dica rápida de produtividade em até 2 frases.');

$apiLabel = strtoupper($api_ia ?: 'nenhuma');

if ($resultado['sucesso']) {
    resp(true, "IA ({$apiLabel}): respondeu com sucesso.", [
        'api'      => $apiLabel,
        'http'     => $resultado['http'] ?? null,
        'resposta' => $resultado['resposta'],
    ]);
} else {
    resp(false, "IA ({$apiLabel}): " . ($resultado['erro'] ?? 'falha desconhecida'), [
        'api'  => $apiLabel,
        'http' => $resultado['http'] ?? null,
    ]);
}
