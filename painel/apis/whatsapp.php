<?php
/**
 * painel/apis/whatsapp.php
 * Função reutilizável de envio de mensagem de WhatsApp via API do Menuia.
 * Depende de $api_whatsapp e $token_whatsapp, carregados em conexao.php.
 *
 * Fluxo real da API (2 passos — confirmado testando em produção, ver painel/apis/texto_whatsapp.php):
 *   1) POST /inbox/start-conversation -> abre/reaproveita uma conversa, devolve um ticket
 *   2) POST /inbox/ticket/{id}/message -> envia o texto de fato pro ticket criado
 */
require_once __DIR__ . '/../../conexao.php';

/**
 * Envia uma mensagem de WhatsApp usando o provedor configurado no sistema.
 *
 * @param string $numero   Número no formato internacional, só dígitos (ex.: 5531999999999)
 * @param string $mensagem Texto da mensagem
 * @return array{sucesso: bool, erro?: string, ticketId?: string}
 */
function enviarWhatsapp(string $numero, string $mensagem): array
{
    global $api_whatsapp, $token_whatsapp, $device_whatsapp;

    if (empty($token_whatsapp) || $api_whatsapp !== 'menuia') {
        return ['sucesso' => false, 'erro' => 'Nenhuma API de WhatsApp configurada.'];
    }
    if (empty($device_whatsapp)) {
        return ['sucesso' => false, 'erro' => 'Nenhuma conexão (deviceId) de WhatsApp configurada.'];
    }

    // ===== PASSO 1: iniciar (ou reaproveitar) a conversa no Inbox =====
    // channel/deviceId identificam a conexão WhatsApp V2 específica (ver Menuia -> Canais).
    // Sem isso a API cria o ticket normalmente, mas a entrega falha com "Configuração não encontrada".
    $chConversa = curl_init("https://api-ia.menuia.com/api/v1/inbox/start-conversation");
    curl_setopt($chConversa, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($chConversa, CURLOPT_POST, true);
    curl_setopt($chConversa, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer " . $token_whatsapp,
    ]);
    curl_setopt($chConversa, CURLOPT_POSTFIELDS, json_encode([
        "phone"    => $numero,
        "name"     => "Cliente",
        "channel"  => "whatsapp_v2",
        "deviceId" => $device_whatsapp,
    ]));

    $respostaConversa = curl_exec($chConversa);
    $erroConversa     = curl_error($chConversa);
    $httpConversa     = curl_getinfo($chConversa, CURLINFO_HTTP_CODE);
    curl_close($chConversa);

    if ($erroConversa) {
        return ['sucesso' => false, 'erro' => $erroConversa];
    }

    $conversa = json_decode($respostaConversa, true);
    $ticketId = $conversa['id'] ?? null;

    if (!$ticketId) {
        return ['sucesso' => false, 'erro' => $conversa['message'] ?? 'Não foi possível abrir a conversa.', 'http' => $httpConversa];
    }

    // ===== PASSO 2: enviar a mensagem de fato pro ticket criado/reaproveitado =====
    $chMensagem = curl_init("https://api-ia.menuia.com/api/v1/inbox/ticket/{$ticketId}/message");
    curl_setopt($chMensagem, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($chMensagem, CURLOPT_POST, true);
    curl_setopt($chMensagem, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer " . $token_whatsapp,
    ]);
    curl_setopt($chMensagem, CURLOPT_POSTFIELDS, json_encode([
        "content" => $mensagem,
    ]));

    $respostaMensagem = curl_exec($chMensagem);
    $erroMensagem     = curl_error($chMensagem);
    $httpMensagem     = curl_getinfo($chMensagem, CURLINFO_HTTP_CODE);
    curl_close($chMensagem);

    if ($erroMensagem) {
        return ['sucesso' => false, 'erro' => $erroMensagem, 'ticketId' => $ticketId];
    }

    $dadosMensagem = json_decode($respostaMensagem, true);

    // A API pode responder HTTP 201 (mensagem registrada no ticket) mesmo quando a
    // ENTREGA de verdade pro WhatsApp falha — isso vem nos campos deliveryStatus/deliveryError,
    // não em statusCode. Sem checar isso, um "Configuração não encontrada" passaria como sucesso.
    if (isset($dadosMensagem['statusCode']) && (int) $dadosMensagem['statusCode'] >= 400) {
        return [
            'sucesso'  => false,
            'erro'     => $dadosMensagem['message'] ?? 'Falha ao enviar a mensagem.',
            'ticketId' => $ticketId,
            'http'     => $httpMensagem,
        ];
    }
    if (($dadosMensagem['deliveryStatus'] ?? null) === 'failed') {
        return [
            'sucesso'  => false,
            'erro'     => $dadosMensagem['deliveryError'] ?? 'Falha na entrega da mensagem.',
            'ticketId' => $ticketId,
            'http'     => $httpMensagem,
        ];
    }

    return ['sucesso' => true, 'ticketId' => $ticketId, 'http' => $httpMensagem, 'resposta' => $dadosMensagem];
}

/**
 * Envia uma mensagem de TEXTO LIVRE via WhatsApp Cloud (Meta), canal adicional ao WhatsApp V2
 * acima. Só funciona dentro da janela de 24h de conversa aberta pelo cliente — fora dela, use
 * enviarWhatsappCloudTemplate().
 * Depende de $token_whatsapp (conta Menuia) + $whatsapp_cloud_phone_id/$whatsapp_cloud_token
 * (canal Meta, configurados em Menuia -> Canais -> WhatsApp Cloud), carregados em conexao.php.
 *
 * @param string $numero   Número no formato internacional, só dígitos (ex.: 5531999999999)
 * @param string $mensagem Texto da mensagem
 * @return array{sucesso: bool, erro?: string, resposta?: array}
 */
function enviarWhatsappCloud(string $numero, string $mensagem): array
{
    global $token_whatsapp, $whatsapp_cloud_phone_id, $whatsapp_cloud_token;

    if (empty($token_whatsapp)) {
        return ['sucesso' => false, 'erro' => 'Nenhuma API de WhatsApp configurada.'];
    }
    if (empty($whatsapp_cloud_phone_id) || empty($whatsapp_cloud_token)) {
        return ['sucesso' => false, 'erro' => 'WhatsApp Cloud (Meta) não configurado — falta Phone Number ID e/ou Access Token.'];
    }

    $ch = curl_init("https://api-ia.menuia.com/api/v1/webhooks/whatsapp-cloud/send-test");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer " . $token_whatsapp, // token da conta Menuia, não o access token do Meta
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        "phoneNumberId" => $whatsapp_cloud_phone_id,
        "to"            => $numero,
        "message"       => $mensagem,
        "accessToken"   => $whatsapp_cloud_token,
    ]));

    $respostaBruta = curl_exec($ch);
    $erroCurl       = curl_error($ch);
    $httpCode       = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($erroCurl) {
        return ['sucesso' => false, 'erro' => $erroCurl];
    }

    $dados = json_decode($respostaBruta, true);

    if ($httpCode < 200 || $httpCode >= 300) {
        return ['sucesso' => false, 'erro' => $dados['message'] ?? "HTTP {$httpCode}", 'resposta' => $dados, 'http' => $httpCode];
    }
    if (($dados['deliveryStatus'] ?? null) === 'failed') {
        return ['sucesso' => false, 'erro' => $dados['deliveryError'] ?? 'Falha na entrega da mensagem.', 'resposta' => $dados, 'http' => $httpCode];
    }
    // A Menuia pode responder HTTP 2xx só porque o PROXY funcionou, mesmo quando a Meta recusa a
    // mensagem de verdade — nesse caso vem um "error" dentro do corpo (ex.: número de teste fora
    // da lista de destinatários permitidos), não refletido no status HTTP nem no deliveryStatus.
    if (!empty($dados['error']) || ($dados['success'] ?? true) === false) {
        $erroMeta = $dados['data']['error']['error_data']['details']
            ?? $dados['data']['error']['message']
            ?? $dados['error']
            ?? 'Falha ao enviar a mensagem.';
        return ['sucesso' => false, 'erro' => $erroMeta, 'resposta' => $dados, 'http' => $httpCode];
    }

    return ['sucesso' => true, 'resposta' => $dados, 'http' => $httpCode];
}

/**
 * Envia uma mensagem por TEMPLATE aprovado via WhatsApp Cloud (Meta) — funciona mesmo fora da
 * janela de 24h (é o único jeito de iniciar contato sem o cliente ter mandado mensagem antes).
 * O accessToken do Meta é resolvido automaticamente pela Menuia aqui, não precisa passar.
 *
 * @param string   $numero           Número no formato internacional, só dígitos
 * @param string   $templateName     Nome do template já aprovado no Meta Business
 * @param string   $templateLanguage Código do idioma do template (ex.: "pt_BR")
 * @param string[] $bodyParams       Valores pras variáveis {{1}}, {{2}}... do template, em ordem
 * @param bool     $registerInInbox  Se true, registra a conversa no Inbox da Menuia também
 * @return array{sucesso: bool, erro?: string, resposta?: array}
 */
function enviarWhatsappCloudTemplate(string $numero, string $templateName, string $templateLanguage, array $bodyParams = [], bool $registerInInbox = true): array
{
    global $token_whatsapp, $whatsapp_cloud_phone_id;

    if (empty($token_whatsapp)) {
        return ['sucesso' => false, 'erro' => 'Nenhuma API de WhatsApp configurada.'];
    }
    if (empty($whatsapp_cloud_phone_id)) {
        return ['sucesso' => false, 'erro' => 'WhatsApp Cloud (Meta) não configurado — falta Phone Number ID.'];
    }

    $ch = curl_init("https://api-ia.menuia.com/api/v1/webhooks/whatsapp-cloud/send-template");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer " . $token_whatsapp,
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        "phoneNumberId"    => $whatsapp_cloud_phone_id,
        "to"               => $numero,
        "templateName"     => $templateName,
        "templateLanguage" => $templateLanguage,
        "bodyParams"       => $bodyParams,
        "registerInInbox"  => $registerInInbox,
    ]));

    $respostaBruta = curl_exec($ch);
    $erroCurl       = curl_error($ch);
    $httpCode       = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($erroCurl) {
        return ['sucesso' => false, 'erro' => $erroCurl];
    }

    $dados = json_decode($respostaBruta, true);

    if ($httpCode < 200 || $httpCode >= 300) {
        return ['sucesso' => false, 'erro' => $dados['message'] ?? "HTTP {$httpCode}", 'resposta' => $dados];
    }
    if (($dados['deliveryStatus'] ?? null) === 'failed') {
        return ['sucesso' => false, 'erro' => $dados['deliveryError'] ?? 'Falha na entrega da mensagem.', 'resposta' => $dados];
    }
    if (!empty($dados['error']) || ($dados['success'] ?? true) === false) {
        $erroMeta = $dados['data']['error']['error_data']['details']
            ?? $dados['data']['error']['message']
            ?? $dados['error']
            ?? 'Falha ao enviar a mensagem.';
        return ['sucesso' => false, 'erro' => $erroMeta, 'resposta' => $dados];
    }

    return ['sucesso' => true, 'resposta' => $dados];
}

/**
 * Envia uma mensagem de texto via Evolution API — terceiro canal, self-hosted (URL própria de
 * cada servidor). Formato do corpo baseado na Evolution API v2 (documentação oficial em
 * doc.evolution-api.com); AINDA NÃO TESTADO com um servidor real — o payload/resposta exatos
 * podem precisar de ajuste na primeira chamada de verdade, como já aconteceu com os outros 2 canais.
 * Depende de $evolution_url/$evolution_instance/$evolution_apikey, carregados em conexao.php.
 *
 * @param string $numero   Número no formato internacional, só dígitos (ex.: 5531999999999)
 * @param string $mensagem Texto da mensagem
 * @return array{sucesso: bool, erro?: string, resposta?: array}
 */
function enviarWhatsappEvolution(string $numero, string $mensagem): array
{
    global $evolution_url, $evolution_instance, $evolution_apikey;

    if (empty($evolution_url) || empty($evolution_instance) || empty($evolution_apikey)) {
        return ['sucesso' => false, 'erro' => 'Evolution API não configurada — falta URL, instância e/ou API Key.'];
    }

    $url = rtrim($evolution_url, '/') . '/message/sendText/' . rawurlencode($evolution_instance);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "apikey: " . $evolution_apikey,
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        "number" => $numero,
        "text"   => $mensagem,
    ]));

    $respostaBruta = curl_exec($ch);
    $erroCurl       = curl_error($ch);
    $httpCode       = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($erroCurl) {
        return ['sucesso' => false, 'erro' => $erroCurl];
    }

    $dados = json_decode($respostaBruta, true);

    if ($httpCode < 200 || $httpCode >= 300) {
        return ['sucesso' => false, 'erro' => $dados['message'] ?? $dados['error'] ?? "HTTP {$httpCode}", 'resposta' => $dados, 'http' => $httpCode];
    }

    return ['sucesso' => true, 'resposta' => $dados, 'http' => $httpCode];
}
