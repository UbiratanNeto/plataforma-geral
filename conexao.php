<?php
/**
 * Arquivo de configuração e conexão do sistema Helpdesk
 * Adaptado para ambiente local XAMPP (Padrão do Curso)
 */

// ===== CARREGA O .env (segredos que não vão pro git) =====
// safeLoad() não quebra o site se o .env estiver ausente (ex.: variáveis
// já definidas direto no servidor de produção).
require_once __DIR__ . '/vendor/autoload.php';
Dotenv\Dotenv::createImmutable(__DIR__)->safeLoad();

// Disponibiliza a chave de criptografia da senha SMTP como constante,
// no mesmo formato que painel/funcoes/crypto.php espera (64 chars hex).
if (!defined('SMTP_SECRET_KEY')) {
    define('SMTP_SECRET_KEY', $_ENV['SMTP_SECRET_KEY'] ?? getenv('SMTP_SECRET_KEY') ?? '');
}
require_once __DIR__ . '/painel/funcoes/crypto.php';

$modo_teste = 'Não';

// ===== FUSO HORÁRIO (LISBOA) =====
date_default_timezone_set('Europe/Lisbon'); // Ajuste para o fuso horário de Lisboa, Portugal

// ===== CONFIGURAÇÃO DO BANCO (XAMPP) =====
// No XAMPP, o servidor padrão sempre será localhost ou 127.0.0.1
$db_servidor = 'localhost';     // AJUSTADO: Voltando para localhost (XAMPP)
$db_usuario  = 'root';          // Padrão do XAMPP é root
$db_senha    = '';              // AJUSTADO: No XAMPP a senha por padrão vem VAZIA
$db_banco    = 'helpdesk';      // AJUSTADO: O nome do banco que você criou manualmente
$db_charset  = 'utf8';

try {
    $dsn = "mysql:host={$db_servidor};dbname={$db_banco};charset={$db_charset}";
    $pdo = new PDO($dsn, $db_usuario, $db_senha, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    // Interrompe o código e mostra o erro se não conseguir conectar
    die('Erro ao conectar ao banco de dados no XAMPP: ' . $e->getMessage());
}

// ===== SENHA PADRÃO (usada ao criar novos usuários no sistema) =====
$senha_padrao = '123';

// ===== ACESSO DE TESTE (preenchimento automático no login quando modo_teste = 'Sim') =====
$usuario_teste = 'contato@hugocursos.com.br';
$senha_teste = '123';

// ===== INFORMAÇÕES DO SISTEMA =====
$nome_sistema = 'Sistema Helpdesk';
$telefone_sistema = '(31)97527-5084';
$email_sistema = 'contato@hugocursos.com.br';
$id_empresa = 0;

// ===== LOGO, ÍCONE E ENDEREÇO (fallback caso ainda não tenham sido enviados/preenchidos) =====
$logo     = 'sem_foto.png'; // Mesmo arquivo padrão já usado como fallback em uploads/
$icone    = 'sem_foto.png';
$endereco = '';

// ==========================================================================
// 🎨 CORES DO TEMA (Fallback padrão caso não exista configuração salva)
// ==========================================================================
$cor_primaria   = '#4f46e5'; // Roxo Indigo do curso do Hugo
$cor_secundaria = '#818cf8'; // Roxo Claro do curso do Hugo
$cor_fundo      = '#f8fafc'; // Cor sólida limpa (evita quebra de CSS ao injetar variável de cor no background)

// ===== CARREGAR E SINCRONIZAR CONFIGURAÇÃO (com cache em sessão) =====
// conexao.php é incluído em TODA página do sistema (login, painel, recuperação de senha).
// Sem cache, isso significa consultar a tabela 'config' a cada requisição, mesmo que ela
// quase nunca mude. Guardamos o resultado em $_SESSION['config'] e só voltamos ao banco
// quando ainda não existe cache para esta sessão (ex.: logo após o login).
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['config']) || ($_SESSION['config_empresa'] ?? null) !== $id_empresa) {
    try {
        // Busca a configuração existente para a empresa 0
        $stmt = $pdo->query("SELECT * FROM config WHERE empresa = 0 LIMIT 1");
        $config = $stmt->fetch();

        if (!$config) {
            // Se não existir, cria o primeiro registro com as cores definidas acima
            $stmt = $pdo->prepare("
                INSERT INTO config (nome_sistema, telefone_sistema, email_sistema, logo, icone, endereco, cor_primaria, cor_secundaria, empresa)
                VALUES (:nome_sistema, :telefone_sistema, :email_sistema, :logo, :icone, :endereco, :cor_primaria, :cor_secundaria, :empresa)
            ");
            $stmt->execute([
                ':nome_sistema'     => $nome_sistema,
                ':telefone_sistema' => $telefone_sistema,
                ':email_sistema'    => $email_sistema,
                ':logo'             => $logo,
                ':icone'            => $icone,
                ':endereco'         => $endereco,
                ':cor_primaria'     => $cor_primaria,
                ':cor_secundaria'   => $cor_secundaria,
                ':empresa'          => $id_empresa,
            ]);

            // Recarrega para guardar no cache o registro recém-criado (com id, timestamps, etc.)
            $stmt = $pdo->query("SELECT * FROM config WHERE empresa = 0 LIMIT 1");
            $config = $stmt->fetch();
        } elseif ($config['cor_primaria'] !== $cor_primaria || $config['cor_secundaria'] !== $cor_secundaria) {
            // Se já existir, mas as cores estáticas no PHP forem diferentes das do Banco, nós atualizamos o banco!
            $stmt = $pdo->prepare("
                UPDATE config
                SET cor_primaria = :cor_primaria, cor_secundaria = :cor_secundaria
                WHERE empresa = 0
            ");
            $stmt->execute([
                ':cor_primaria'   => $cor_primaria,
                ':cor_secundaria' => $cor_secundaria
            ]);
            $config['cor_primaria']   = $cor_primaria;
            $config['cor_secundaria'] = $cor_secundaria;
        }

        $_SESSION['config']         = $config ?: [];
        $_SESSION['config_empresa'] = $id_empresa;
    } catch (PDOException $e) {
        // Se a tabela 'config' não existir ainda, ignora e usa as variáveis padrão do PHP
        $_SESSION['config']         = [];
        $_SESSION['config_empresa'] = $id_empresa;
    }
}

// Usa o config já cacheado na sessão (evita repetir a consulta nas próximas páginas)
$config = $_SESSION['config'];

if ($config) {
    // AJUSTE CRÍTICO: Se já existem cores no banco, garante que as variáveis PHP do tema usem as cores do Banco de Dados!
    $cor_primaria     = $config['cor_primaria']     ?? $cor_primaria;
    $cor_secundaria   = $config['cor_secundaria']   ?? $cor_secundaria;

    // Carrega as demais informações salvas no banco
    $nome_sistema     = $config['nome_sistema']     ?? $nome_sistema;
    $telefone_sistema = $config['telefone_sistema'] ?? $telefone_sistema;
    $ddi_sistema      = $config['ddi_sistema']      ?? '55';
    $email_sistema    = $config['email_sistema']    ?? $email_sistema;
    $id_empresa       = (int) ($config['empresa']   ?? $id_empresa);

    // Logo, ícone e endereço: "?? $logo" só entra em ação se a coluna estiver NULL no banco
    // (ainda não foi enviado nenhum arquivo/preenchido nenhum endereço)
    $logo     = $config['logo']     ?? $logo;
    $icone    = $config['icone']    ?? $icone;
    $endereco = $config['endereco'] ?? $endereco;

    // URL fixa de acesso ao sistema (usada, por ex., na mensagem de boas-vindas por WhatsApp).
    // Fixa e configurável em vez de calculada por requisição — evita depender de qual página
    // disparou a chamada.
    $url_sistema = $config['url_sistema'] ?? '';

    // Dados de SMTP: host/porta/segurança podem ir pro formulário de configurações normalmente.
    // $smtp_senha NUNCA deve ser impresso em HTML — existe aqui só pra uso futuro no envio de e-mails.
    $smtp_host      = $config['smtp_host']      ?? '';
    $smtp_porta     = $config['smtp_porta']     ?? '';
    $smtp_seguranca = $config['smtp_seguranca'] ?? '';

    // Api de WhatsApp (ex.: Menuia) — token único e perpétuo da conta (equivale ao
    // antigo par authkey/appkey), fornecido no painel da própria API.
    // device_whatsapp: ID da conexão/dispositivo específico (canal WhatsApp V2) — necessário
    // pra API saber por qual número conectado rotear o envio (Menuia → Canais).
    $api_whatsapp    = $config['api_whatsapp']    ?? '';
    $device_whatsapp = $config['device_whatsapp'] ?? '';

    // O token é salvo criptografado (AES-256-GCM, mesmo esquema da senha SMTP); aqui já
    // devolvemos o valor descriptografado pra uso nas chamadas da API.
    $token_whatsapp_criptografado = $config['token_whatsapp'] ?? '';
    $token_whatsapp = '';
    if ($token_whatsapp_criptografado !== '') {
        try {
            $token_whatsapp = smtp_decrypt($token_whatsapp_criptografado);
        } catch (Throwable $e) {
            error_log('Aviso: token_whatsapp no banco não está no formato criptografado esperado (dado antigo?). ' . $e->getMessage());
            $token_whatsapp = $token_whatsapp_criptografado;
        }
        // smtp_decrypt() devolve '' silenciosamente (sem lançar exceção) quando o valor não está
        // em base64 válido — cobre o caso de um token salvo em texto puro ANTES dessa criptografia
        // existir (ex.: contém "-"/"_", típicos de JWT, que não são base64 padrão).
        if ($token_whatsapp === '') {
            $token_whatsapp = $token_whatsapp_criptografado;
        }
        // Remove espaço/quebra de linha que pode ter vindo junto ao colar o token no campo.
        $token_whatsapp = preg_replace('/\s+/', '', $token_whatsapp);
    }

    // WhatsApp Cloud (Meta) — canal adicional, além do WhatsApp V2 acima. phoneNumberId não é
    // segredo (é só um identificador); accessToken é credencial de verdade, salvo criptografado
    // no mesmo esquema do token_whatsapp.
    $whatsapp_cloud_phone_id = $config['whatsapp_cloud_phone_id'] ?? '';

    $whatsapp_cloud_token_criptografado = $config['whatsapp_cloud_token'] ?? '';
    $whatsapp_cloud_token = '';
    if ($whatsapp_cloud_token_criptografado !== '') {
        try {
            $whatsapp_cloud_token = smtp_decrypt($whatsapp_cloud_token_criptografado);
        } catch (Throwable $e) {
            error_log('Aviso: whatsapp_cloud_token no banco não está no formato criptografado esperado (dado antigo?). ' . $e->getMessage());
            $whatsapp_cloud_token = $whatsapp_cloud_token_criptografado;
        }
        if ($whatsapp_cloud_token === '') {
            $whatsapp_cloud_token = $whatsapp_cloud_token_criptografado;
        }
        $whatsapp_cloud_token = preg_replace('/\s+/', '', $whatsapp_cloud_token);
    }

    // Evolution API — terceiro canal, self-hosted (URL própria do servidor de cada usuário).
    // url/instance não são segredo; apikey é credencial de verdade, salva criptografada.
    $evolution_url      = $config['evolution_url']      ?? '';
    $evolution_instance = $config['evolution_instance'] ?? '';

    $evolution_apikey_criptografado = $config['evolution_apikey'] ?? '';
    $evolution_apikey = '';
    if ($evolution_apikey_criptografado !== '') {
        try {
            $evolution_apikey = smtp_decrypt($evolution_apikey_criptografado);
        } catch (Throwable $e) {
            error_log('Aviso: evolution_apikey no banco não está no formato criptografado esperado (dado antigo?). ' . $e->getMessage());
            $evolution_apikey = $evolution_apikey_criptografado;
        }
        if ($evolution_apikey === '') {
            $evolution_apikey = $evolution_apikey_criptografado;
        }
        $evolution_apikey = preg_replace('/\s+/', '', $evolution_apikey);
    }

    // Api de IA (ex.: ChatGPT, Gemini, Claude) — token de acesso, salvo criptografado.
    $api_ia = $config['api_ia'] ?? '';

    $token_ia_criptografado = $config['token_ia'] ?? '';
    $token_ia = '';
    if ($token_ia_criptografado !== '') {
        try {
            $token_ia = smtp_decrypt($token_ia_criptografado);
        } catch (Throwable $e) {
            error_log('Aviso: token_ia no banco não está no formato criptografado esperado (dado antigo?). ' . $e->getMessage());
            $token_ia = $token_ia_criptografado;
        }
        if ($token_ia === '') {
            $token_ia = $token_ia_criptografado;
        }
        $token_ia = preg_replace('/\s+/', '', $token_ia);
    }

    // A senha é salva criptografada (AES-256-GCM); aqui já devolvemos o valor descriptografado
    // pra uso futuro (ex.: enviar e-mail). Um valor salvo ANTES dessa criptografia existir
    // (texto puro) não decripta — nesse caso caímos de volta pro texto original, com aviso no log.
    $smtp_senha_criptografada = $config['smtp_senha'] ?? '';
    $smtp_senha = '';
    if ($smtp_senha_criptografada !== '') {
        try {
            $smtp_senha = smtp_decrypt($smtp_senha_criptografada);
        } catch (Throwable $e) {
            error_log('Aviso: smtp_senha no banco não está no formato criptografado esperado (dado antigo?). ' . $e->getMessage());
            $smtp_senha = $smtp_senha_criptografada;
        }
    }
}
?>