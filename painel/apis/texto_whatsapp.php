<?php
/**
 * painel/apis/texto_whatsapp.php
 * Página de teste manual de envio de WhatsApp — dispara pelo provedor escolhido em
 * Configurações do Sistema (Menuia WhatsApp V2 ou WhatsApp Cloud/Meta), sem precisar
 * saber qual API está por trás.
 */
require_once __DIR__ . '/whatsapp.php'; // já inclui conexao.php e as funções de envio

$numero   = '351927228925'; // Lisboa, PT — formato internacional, sem "+"
$mensagem = 'Olá, esta é uma mensagem de teste! Parabéns, está tudo funcionando.';

$resultado = match ($api_whatsapp) {
    'menuia'    => enviarWhatsapp($numero, $mensagem),
    'meta'      => enviarWhatsappCloud($numero, $mensagem),
    'evolution' => enviarWhatsappEvolution($numero, $mensagem),
    default     => ['sucesso' => false, 'erro' => 'Nenhuma API de WhatsApp configurada em Configurações do Sistema.'],
};

echo $resultado['sucesso'] ? 'Enviado com sucesso!' : ('Falha ao enviar: ' . htmlspecialchars($resultado['erro'] ?? 'erro desconhecido'));
echo '<pre>' . htmlspecialchars(print_r($resultado, true)) . '</pre>';
?>
