<?php
@session_start();
header('Content-Type: application/json; charset=utf-8');

// Valida se o usuário está logado
if (empty($_SESSION['id'])) {
    echo json_encode(['ok' => false, 'msg' => 'Sessão expirada.']);
    exit();
}

// Caminho relativo para a conexão dependendo de onde a pasta scripts está
require_once __DIR__ . '/../../conexao.php';

function resp($ok, $msg, $extra = []) {
    echo json_encode(array_merge(['ok' => $ok, 'msg' => $msg], $extra));
    exit();
}

/**
 * Valida e salva o upload de uma imagem (logo/ícone) em uploads/.
 * Devolve o novo nome de arquivo, ou null se nada foi enviado nesse campo.
 * Em caso de erro, já responde com resp(false, ...) e encerra — não precisa tratar retorno de erro.
 */
function processarUploadImagemSistema(string $campoArquivo, string $rotulo, string $arquivoAntigo): ?string {
    if (empty($_FILES[$campoArquivo]['name'])) {
        return null;
    }
    $arquivo = $_FILES[$campoArquivo];

    if ($arquivo['error'] !== UPLOAD_ERR_OK) {
        resp(false, "Falha ao enviar {$rotulo}.");
    }
    if ($arquivo['size'] > 2 * 1024 * 1024) {
        resp(false, "{$rotulo} deve ter no máximo 2MB.");
    }

    $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
    $permitidas = ['png', 'jpg', 'jpeg', 'webp'];
    if (!in_array($extensao, $permitidas, true)) {
        resp(false, "Formato inválido para {$rotulo}. Use PNG, JPG ou WEBP.");
    }

    // Confere o conteúdo de verdade do arquivo, não só a extensão do nome
    $tipoReal = @exif_imagetype($arquivo['tmp_name']);
    $tiposValidos = [IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_WEBP];
    if ($tipoReal === false || !in_array($tipoReal, $tiposValidos, true)) {
        resp(false, "O arquivo enviado para {$rotulo} não é uma imagem válida.");
    }

    $uploadsDir = realpath(__DIR__ . '/../../uploads');
    if ($uploadsDir === false) {
        resp(false, 'Pasta de uploads não encontrada.');
    }

    $novoNome = preg_replace('/[^a-z0-9]/', '', strtolower($campoArquivo)) . '_' . bin2hex(random_bytes(4)) . '.' . $extensao;
    $destino = $uploadsDir . DIRECTORY_SEPARATOR . $novoNome;

    if (!move_uploaded_file($arquivo['tmp_name'], $destino)) {
        resp(false, "Não foi possível salvar {$rotulo} no servidor.");
    }

    // Apaga o arquivo antigo (exceto o fallback padrão do sistema) pra não acumular lixo
    if ($arquivoAntigo && $arquivoAntigo !== 'sem_foto.png') {
        $caminhoAntigo = $uploadsDir . DIRECTORY_SEPARATOR . basename($arquivoAntigo);
        if (is_file($caminhoAntigo)) {
            @unlink($caminhoAntigo);
        }
    }

    return $novoNome;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    resp(false, 'Método inválido.');
}

// Resgata os dados enviados pelo formulário
$nome_sistema     = trim($_POST['nome_sistema'] ?? '');
$telefone_sistema = trim($_POST['telefone_sistema'] ?? '');
$ddi_sistema      = preg_replace('/\D/', '', trim($_POST['ddi_sistema'] ?? '')) ?: '55';
$email_sistema    = trim($_POST['email_sistema'] ?? '');
$endereco_novo    = trim($_POST['endereco'] ?? '');
$url_sistema      = rtrim(trim($_POST['url_sistema'] ?? ''), '/');
$cor_primaria     = trim($_POST['cor_primaria'] ?? '');
$cor_secundaria   = trim($_POST['cor_secundaria'] ?? '');
$smtp_host        = trim($_POST['smtp_host'] ?? '');
$smtp_porta       = trim($_POST['smtp_porta'] ?? '');
$smtp_seguranca   = trim($_POST['smtp_seguranca'] ?? '');
$smtp_senha_bruta = $_POST['smtp_senha'] ?? '';
$api_whatsapp    = trim($_POST['api_whatsapp'] ?? '');
$token_whatsapp  = trim($_POST['token_whatsapp'] ?? '');
$device_whatsapp = trim($_POST['device_whatsapp'] ?? '');
$whatsapp_cloud_phone_id = trim($_POST['whatsapp_cloud_phone_id'] ?? '');
$whatsapp_cloud_token    = trim($_POST['whatsapp_cloud_token'] ?? '');
$evolution_url = rtrim(trim($_POST['evolution_url'] ?? ''), '/');
$evolution_instance = trim($_POST['evolution_instance'] ?? '');
$evolution_apikey   = trim($_POST['evolution_apikey'] ?? '');
$api_ia   = trim($_POST['api_ia'] ?? '');
$token_ia = trim($_POST['token_ia'] ?? '');

try {
    // $logo/$icone aqui são os valores ATUAIS (carregados pelo conexao.php no topo deste
    // arquivo) — usados só pra saber qual arquivo antigo apagar, caso um novo seja enviado.
    $novoLogo  = processarUploadImagemSistema('logo', 'Logo', $logo ?? '');
    $novoIcone = processarUploadImagemSistema('icone', 'Ícone', $icone ?? '');

    $set = [
        'nome_sistema = :nome_sistema',
        'telefone_sistema = :telefone_sistema',
        'ddi_sistema = :ddi_sistema',
        'email_sistema = :email_sistema',
        'endereco = :endereco',
        'url_sistema = :url_sistema',
        'cor_primaria = :cor_primaria',
        'cor_secundaria = :cor_secundaria',
        'smtp_host = :smtp_host',
        'smtp_porta = :smtp_porta',
        'smtp_seguranca = :smtp_seguranca',
        'api_whatsapp = :api_whatsapp',
        'token_whatsapp = :token_whatsapp',
        'device_whatsapp = :device_whatsapp',
        'whatsapp_cloud_phone_id = :whatsapp_cloud_phone_id',
        'whatsapp_cloud_token = :whatsapp_cloud_token',
        'evolution_url = :evolution_url',
        'evolution_instance = :evolution_instance',
        'evolution_apikey = :evolution_apikey',
        'api_ia = :api_ia',
        'token_ia = :token_ia',
    ];
    $params = [
        ':nome_sistema'     => $nome_sistema,
        ':telefone_sistema' => $telefone_sistema,
        ':ddi_sistema'      => $ddi_sistema,
        ':email_sistema'    => $email_sistema,
        ':endereco'         => $endereco_novo,
        ':url_sistema'      => $url_sistema,
        ':cor_primaria'     => $cor_primaria,
        ':cor_secundaria'   => $cor_secundaria,
        ':smtp_host'        => $smtp_host,
        ':smtp_porta'       => $smtp_porta,
        ':smtp_seguranca'   => $smtp_seguranca,
        ':api_whatsapp'     => $api_whatsapp,
        // 🔒 CRIPTOGRAFA O TOKEN ANTES DE SALVAR NO BANCO (mesmo esquema da senha SMTP, AES-256-GCM)
        ':token_whatsapp'   => smtp_encrypt($token_whatsapp),
        ':device_whatsapp'  => $device_whatsapp,
        ':whatsapp_cloud_phone_id' => $whatsapp_cloud_phone_id,
        // 🔒 CRIPTOGRAFA O ACCESS TOKEN DO WHATSAPP CLOUD (mesmo esquema do token_whatsapp)
        ':whatsapp_cloud_token'    => smtp_encrypt($whatsapp_cloud_token),
        ':evolution_url'      => $evolution_url,
        ':evolution_instance' => $evolution_instance,
        // 🔒 CRIPTOGRAFA A API KEY DA EVOLUTION (mesmo esquema dos outros tokens)
        ':evolution_apikey'   => smtp_encrypt($evolution_apikey),
        ':api_ia'   => $api_ia,
        // 🔒 CRIPTOGRAFA O TOKEN DA API DE IA (mesmo esquema dos outros tokens)
        ':token_ia' => smtp_encrypt($token_ia),
    ];

    // Senha SMTP: só entra no UPDATE se veio preenchida (em branco = mantém a que já existe)
    if (!empty($smtp_senha_bruta)) {
        // 🔒 CRIPTOGRAFA A SENHA ANTES DE SALVAR NO BANCO (AES-256-GCM, painel/funcoes/crypto.php)
        $set[] = 'smtp_senha = :smtp_senha';
        $params[':smtp_senha'] = smtp_encrypt($smtp_senha_bruta);
    }

    // Logo/Ícone: só entram no UPDATE se um arquivo novo foi realmente enviado
    if ($novoLogo !== null) {
        $set[] = 'logo = :logo';
        $params[':logo'] = $novoLogo;
    }
    if ($novoIcone !== null) {
        $set[] = 'icone = :icone';
        $params[':icone'] = $novoIcone;
    }

    $sql = 'UPDATE config SET ' . implode(', ', $set) . ' WHERE empresa = 0 LIMIT 1';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // Atualiza o cache de config na sessão
    $stmtRecarrega = $pdo->query("SELECT * FROM config WHERE empresa = 0 LIMIT 1");
    $_SESSION['config'] = $stmtRecarrega->fetch() ?: [];
    $_SESSION['config_empresa'] = 0;

    resp(true, 'Configurações salvas com sucesso!');

} catch (PDOException $e) {
    resp(false, 'Erro ao salvar no banco de dados: ' . $e->getMessage());
} catch (Throwable $e) {
    // Cobre falhas de smtp_encrypt() (ex.: SMTP_SECRET_KEY ausente/inválida no .env)
    resp(false, 'Erro ao processar a solicitação: ' . $e->getMessage());
}