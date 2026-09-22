<?php
/**
 * painel/scripts/salvar_assinatura_perfil.php
 * Salva a assinatura do usuário LOGADO (sempre a própria, nunca recebe um "id" — vem de
 * $_SESSION['id']). Chamada pela modal "Fazer Assinatura", independente do resto do
 * formulário de Perfil.
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

$id_usuario = (int) $_SESSION['id'];

$corpo = json_decode(file_get_contents('php://input'), true);
$assinatura = (string) ($corpo['assinatura'] ?? '');

if ($assinatura === '' || !str_starts_with($assinatura, 'data:image/png;base64,')) {
    resp(false, 'Assinatura inválida — desenhe no quadro antes de salvar.');
}

$dadosPng = base64_decode(substr($assinatura, strlen('data:image/png;base64,')), true);
if ($dadosPng === false) {
    resp(false, 'Não foi possível processar a imagem da assinatura.');
}

$uploadsDir = __DIR__ . '/../../uploads/assinaturas';
if (!is_dir($uploadsDir)) {
    mkdir($uploadsDir, 0755, true);
}
$uploadsDir = realpath($uploadsDir);

$novoNome = 'assinatura_' . $id_usuario . '_' . bin2hex(random_bytes(4)) . '.png';
$destino  = $uploadsDir . DIRECTORY_SEPARATOR . $novoNome;

if (file_put_contents($destino, $dadosPng) === false) {
    resp(false, 'Não foi possível salvar o arquivo da assinatura no servidor.');
}

// Apaga a assinatura antiga, se houver, pra não acumular arquivo órfão.
$stmt = $pdo->prepare("SELECT assinatura FROM usuarios WHERE id = ?");
$stmt->execute([$id_usuario]);
$assinaturaAntiga = $stmt->fetchColumn();
if ($assinaturaAntiga) {
    $caminhoAntigo = $uploadsDir . DIRECTORY_SEPARATOR . basename($assinaturaAntiga);
    if (is_file($caminhoAntigo)) {
        @unlink($caminhoAntigo);
    }
}

$pdo->prepare("UPDATE usuarios SET assinatura = ? WHERE id = ?")->execute([$novoNome, $id_usuario]);

resp(true, 'Assinatura salva com sucesso!', ['assinatura_url' => '../uploads/assinaturas/' . $novoNome]);
