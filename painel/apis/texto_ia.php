<?php
/**
 * painel/apis/texto_ia.php
 * Página de teste manual da API de IA — dispara pelo provedor escolhido em
 * Configurações do Sistema (ChatGPT, Gemini ou Claude), sem precisar saber qual está por trás.
 */
require_once __DIR__ . '/ia.php'; // já inclui conexao.php e as funções de pergunta

$mensagem = 'Diga viva em uma palavra.';

$resultado = perguntarIA($mensagem);

echo $resultado['sucesso'] ? htmlspecialchars($resultado['resposta']) : ('Falha ao perguntar: ' . htmlspecialchars($resultado['erro'] ?? 'erro desconhecido'));
echo '<pre>' . htmlspecialchars(print_r($resultado, true)) . '</pre>';
?>
