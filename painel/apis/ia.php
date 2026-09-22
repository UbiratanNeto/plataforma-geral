<?php
/**
 * painel/apis/ia.php
 * Funções reutilizáveis pra "perguntar" a uma IA (ChatGPT, Gemini ou Claude), usando o
 * provedor configurado em Configurações do Sistema ($api_ia / $token_ia).
 * Cada provedor tem endpoint/formato de request e resposta próprios (APIs oficiais,
 * sem intermediário).
 */
require_once __DIR__ . '/../../conexao.php';

/**
 * Envia uma pergunta/prompt pra IA configurada no sistema e devolve o texto de resposta.
 *
 * @param string $mensagem Pergunta/prompt em texto livre
 * @return array{sucesso: bool, resposta?: string, erro?: string}
 */
function perguntarIA(string $mensagem): array
{
    global $api_ia;

    return match ($api_ia) {
        'chatgpt' => perguntarChatGPT($mensagem),
        'gemini'  => perguntarGemini($mensagem),
        'claude'  => perguntarClaude($mensagem),
        default   => ['sucesso' => false, 'erro' => 'Nenhuma API de IA configurada em Configurações do Sistema.'],
    };
}

/**
 * ChatGPT (OpenAI) — https://platform.openai.com/docs/api-reference/chat
 */
function perguntarChatGPT(string $mensagem): array
{
    global $token_ia;

    if (empty($token_ia)) {
        return ['sucesso' => false, 'erro' => 'Token do ChatGPT não configurado.'];
    }

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer " . $token_ia,
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        "model"    => "gpt-4o-mini", // ajuste aqui se quiser outro modelo
        "messages" => [
            ["role" => "user", "content" => $mensagem],
        ],
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
        return ['sucesso' => false, 'erro' => $dados['error']['message'] ?? "HTTP {$httpCode}", 'resposta_bruta' => $dados, 'http' => $httpCode];
    }

    $texto = $dados['choices'][0]['message']['content'] ?? null;
    if ($texto === null) {
        return ['sucesso' => false, 'erro' => 'Resposta em formato inesperado.', 'resposta_bruta' => $dados];
    }

    return ['sucesso' => true, 'resposta' => $texto, 'http' => $httpCode];
}

/**
 * Gemini (Google) — https://ai.google.dev/api/generate-content
 */
function perguntarGemini(string $mensagem): array
{
    global $token_ia;

    if (empty($token_ia)) {
        return ['sucesso' => false, 'erro' => 'Token do Gemini não configurado.'];
    }

    $modelo = 'gemini-3.6-flash'; // ajuste aqui se quiser outro modelo
    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$modelo}:generateContent?key=" . urlencode($token_ia);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        "contents" => [
            ["parts" => [["text" => $mensagem]]],
        ],
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
        return ['sucesso' => false, 'erro' => $dados['error']['message'] ?? "HTTP {$httpCode}", 'resposta_bruta' => $dados, 'http' => $httpCode];
    }

    $texto = $dados['candidates'][0]['content']['parts'][0]['text'] ?? null;
    if ($texto === null) {
        return ['sucesso' => false, 'erro' => 'Resposta em formato inesperado.', 'resposta_bruta' => $dados];
    }

    return ['sucesso' => true, 'resposta' => $texto, 'http' => $httpCode];
}

/**
 * Claude (Anthropic) — https://docs.anthropic.com/en/api/messages
 */
function perguntarClaude(string $mensagem): array
{
    global $token_ia;

    if (empty($token_ia)) {
        return ['sucesso' => false, 'erro' => 'Token do Claude não configurado.'];
    }

    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "x-api-key: " . $token_ia,
        "anthropic-version: 2023-06-01",
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        "model"      => "claude-sonnet-4-5", // ajuste aqui se quiser outro modelo
        "max_tokens" => 1024,
        "messages"   => [
            ["role" => "user", "content" => $mensagem],
        ],
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
        return ['sucesso' => false, 'erro' => $dados['error']['message'] ?? "HTTP {$httpCode}", 'resposta_bruta' => $dados, 'http' => $httpCode];
    }

    $texto = $dados['content'][0]['text'] ?? null;
    if ($texto === null) {
        return ['sucesso' => false, 'erro' => 'Resposta em formato inesperado.', 'resposta_bruta' => $dados];
    }

    return ['sucesso' => true, 'resposta' => $texto, 'http' => $httpCode];
}
