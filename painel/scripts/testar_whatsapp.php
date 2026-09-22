<?php
/**
 * painel/scripts/testar_whatsapp.php
 * Botão "Testar WhatsApp" do modal de Configurações — dispara uma mensagem de teste real
 * pro provedor configurado, usando o telefone cadastrado em "Dados do Sistema" como destino.
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
require_once __DIR__ . '/../apis/whatsapp.php';

if (empty($telefone_sistema)) {
    resp(false, 'Cadastre um telefone em "Dados do Sistema" antes de testar — é pra ele que a mensagem de teste é enviada.');
}

$numero = preg_replace('/\D/', '', $telefone_sistema);
$ddi    = $ddi_sistema ?: '55';
if ($numero !== '' && substr($numero, 0, strlen($ddi)) !== $ddi) {
    $numero = $ddi . $numero;
}

$mensagem = 'Teste de envio WhatsApp - API';

$resultado = match ($api_whatsapp) {
    'menuia'    => enviarWhatsapp($numero, $mensagem),
    'meta'      => enviarWhatsappCloud($numero, $mensagem),
    'evolution' => enviarWhatsappEvolution($numero, $mensagem),
    default     => ['sucesso' => false, 'erro' => 'Nenhuma API de WhatsApp configurada.'],
};

$apiLabel = strtoupper($api_whatsapp ?: 'nenhuma');

if ($resultado['sucesso']) {
    resp(true, "WhatsApp ({$apiLabel}): enviado com sucesso.", [
        'api'  => $apiLabel,
        'http' => $resultado['http'] ?? null,
        'raw'  => json_encode($resultado['resposta'] ?? $resultado, JSON_UNESCAPED_UNICODE),
    ]);
} else {
    resp(false, "WhatsApp ({$apiLabel}): " . ($resultado['erro'] ?? 'falha desconhecida'), [
        'api'  => $apiLabel,
        'http' => $resultado['http'] ?? null,
        'raw'  => json_encode($resultado['resposta'] ?? $resultado, JSON_UNESCAPED_UNICODE),
    ]);
}
