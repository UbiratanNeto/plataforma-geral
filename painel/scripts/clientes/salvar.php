<?php
/**
 * painel/scripts/clientes/salvar.php
 * Cria ou atualiza um cliente (Gestão de Clientes).
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    resp(false, 'Método inválido.');
}

require_once __DIR__ . '/../../../conexao.php';
require_once __DIR__ . '/../../includes/permissoes.php';
require_once __DIR__ . '/../../../painel/funcoes/logs.php';

/**
 * Valida e salva o upload da foto em uploads/clientes/.
 * Devolve o novo nome de arquivo, ou null se nenhum arquivo novo foi enviado.
 */
function processarUploadFotoCliente(string $arquivoAntigo): ?string {
    if (empty($_FILES['foto']['name'])) {
        return null;
    }
    $arquivo = $_FILES['foto'];

    if ($arquivo['error'] !== UPLOAD_ERR_OK) {
        resp(false, 'Falha ao enviar a foto.');
    }
    if ($arquivo['size'] > 2 * 1024 * 1024) {
        resp(false, 'A foto deve ter no máximo 2MB.');
    }

    $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
    $permitidas = ['png', 'jpg', 'jpeg', 'webp'];
    if (!in_array($extensao, $permitidas, true)) {
        resp(false, 'Formato de imagem inválido. Use PNG, JPG ou WEBP.');
    }

    // Confere o conteúdo de verdade do arquivo, não só a extensão do nome
    $tipoReal = @exif_imagetype($arquivo['tmp_name']);
    $tiposValidos = [IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_WEBP];
    if ($tipoReal === false || !in_array($tipoReal, $tiposValidos, true)) {
        resp(false, 'O arquivo enviado não é uma imagem válida.');
    }

    $uploadsDir = __DIR__ . '/../../../uploads/clientes';
    if (!is_dir($uploadsDir)) {
        mkdir($uploadsDir, 0755, true);
    }
    $uploadsDir = realpath($uploadsDir);

    $novoNome = 'cliente_' . bin2hex(random_bytes(4)) . '.' . $extensao;
    $destino = $uploadsDir . DIRECTORY_SEPARATOR . $novoNome;

    if (!move_uploaded_file($arquivo['tmp_name'], $destino)) {
        resp(false, 'Não foi possível salvar a foto no servidor.');
    }

    // Apaga a foto antiga (exceto o fallback padrão) pra não acumular lixo
    if ($arquivoAntigo && $arquivoAntigo !== 'sem_foto.png') {
        $caminhoAntigo = $uploadsDir . DIRECTORY_SEPARATOR . basename($arquivoAntigo);
        if (is_file($caminhoAntigo)) {
            @unlink($caminhoAntigo);
        }
    }

    return $novoNome;
}

// ===== Resgata os dados enviados pelo formulário =====
$id          = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$nome        = trim($_POST['nome'] ?? '');
$email       = trim($_POST['email'] ?? '');
$telefone    = trim($_POST['telefone'] ?? '');
$ddi         = preg_replace('/\D/', '', trim($_POST['ddi'] ?? '')) ?: '55';
$notificarCadastro = trim($_POST['notificar_cadastro'] ?? 'Sim') === 'Sim' ? 'Sim' : 'Não';
$cpf_cnpj    = trim($_POST['cpf_cnpj'] ?? '');
$tipo        = trim($_POST['tipo'] ?? '');
$ativo       = trim($_POST['ativo'] ?? 'Sim');
$cep         = trim($_POST['cep'] ?? '');
$endereco    = trim($_POST['endereco'] ?? '');
$numero      = trim($_POST['numero'] ?? '');
$complemento = trim($_POST['complemento'] ?? '');
$bairro      = trim($_POST['bairro'] ?? '');
$cidade      = trim($_POST['cidade'] ?? '');
$estado      = strtoupper(trim($_POST['estado'] ?? ''));
$observacoes = trim($_POST['observacoes'] ?? '');

// Permissão de AÇÃO (global, não por página) — criar ou editar dependendo se veio um $id
$acao = $id ? 'editar' : 'criar';
if (!podeExecutarAcao($pdo, $_SESSION['id'] ?? null, $_SESSION['cargo_id'] ?? null, $acao)) {
    resp(false, 'Você não tem permissão para ' . ($id ? 'editar' : 'criar') . ' clientes.');
}

// ===== Validações =====
if ($nome === '') {
    resp(false, 'Informe o nome do cliente.');
}
if ($telefone === '') {
    resp(false, 'Informe o telefone do cliente.');
}
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    resp(false, 'Informe um e-mail válido.');
}

try {
    // Foto atual (pra saber o que apagar, se uma nova for enviada)
    $fotoAtual = '';
    if ($id) {
        $stmtFoto = $pdo->prepare("SELECT foto FROM clientes WHERE id = ?");
        $stmtFoto->execute([$id]);
        $fotoAtual = (string) $stmtFoto->fetchColumn();
    }
    $novaFoto = processarUploadFotoCliente($fotoAtual);

    $params = [
        ':nome' => $nome, ':email' => $email ?: null, ':telefone' => $telefone ?: null, ':ddi' => $ddi,
        ':cpf_cnpj' => $cpf_cnpj ?: null, ':tipo' => $tipo ?: null, ':ativo' => $ativo,
        ':notificar_cadastro' => $notificarCadastro,
        ':cep' => $cep ?: null, ':endereco' => $endereco ?: null, ':numero' => $numero ?: null,
        ':complemento' => $complemento ?: null, ':bairro' => $bairro ?: null, ':cidade' => $cidade ?: null,
        ':estado' => $estado ?: null, ':observacoes' => $observacoes ?: null,
    ];

    if ($id) {
        // ===== Atualização (UPDATE) =====
        $set = [
            'nome = :nome', 'email = :email', 'telefone = :telefone', 'ddi = :ddi', 'cpf_cnpj = :cpf_cnpj',
            'tipo = :tipo', 'ativo = :ativo', 'notificar_cadastro = :notificar_cadastro',
            'cep = :cep', 'endereco = :endereco', 'numero = :numero',
            'complemento = :complemento', 'bairro = :bairro', 'cidade = :cidade', 'estado = :estado',
            'observacoes = :observacoes',
        ];

        if ($novaFoto !== null) {
            $set[] = 'foto = :foto';
            $params[':foto'] = $novaFoto;
        }

        $params[':id'] = $id;
        $sql = 'UPDATE clientes SET ' . implode(', ', $set) . ' WHERE id = :id';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        registrarLog($pdo, 'editar', 'clientes', $id, "Cliente \"{$nome}\" atualizado");

        resp(true, 'Cliente atualizado com sucesso!');
    } else {
        // ===== Inserção (INSERT) =====
        $params[':foto'] = $novaFoto; // pode ser null; a coluna aceita NULL
        $params[':data_cadastro'] = date('Y-m-d H:i:s');

        $stmt = $pdo->prepare("
            INSERT INTO clientes (nome, email, telefone, ddi, cpf_cnpj, tipo, ativo, notificar_cadastro, cep, endereco, numero, complemento, bairro, cidade, estado, observacoes, foto, data_cadastro)
            VALUES (:nome, :email, :telefone, :ddi, :cpf_cnpj, :tipo, :ativo, :notificar_cadastro, :cep, :endereco, :numero, :complemento, :bairro, :cidade, :estado, :observacoes, :foto, :data_cadastro)
        ");
        $stmt->execute($params);
        $novoId = (int) $pdo->lastInsertId();

        registrarLog($pdo, 'inserir', 'clientes', $novoId, "Cliente \"{$nome}\" criado");

        // Envia mensagem de boas-vindas por WhatsApp, com texto gerado por IA (se houver
        // telefone informado e uma API de WhatsApp configurada). Não bloqueia o cadastro se falhar.
        $whatsappEnviado = null;
        $whatsappErro    = null;
        if ($telefone !== '' && $notificarCadastro === 'Sim') {
            require_once __DIR__ . '/../../apis/ia.php';
            require_once __DIR__ . '/../../apis/whatsapp.php';

            $numeroWhatsapp = preg_replace('/\D/', '', $telefone);
            if ($numeroWhatsapp !== '' && substr($numeroWhatsapp, 0, strlen($ddi)) !== $ddi) {
                $numeroWhatsapp = $ddi . $numeroWhatsapp;
            }

            // Texto padrão, usado se a IA não estiver configurada ou a chamada falhar.
            $mensagemBoasVindas = "Olá, {$nome}! Seu cadastro no {$nome_sistema} foi criado com sucesso. Qualquer dúvida, estamos à disposição!";

            $prompt = "Crie uma mensagem curta de WhatsApp, em PT-BR, pra dar boas-vindas ao cliente "
                . "\"{$nome}\" que acabou de se cadastrar no sistema \"{$nome_sistema}\". "
                . "Seja cordial, breve (no máximo 3 frases) e sem emojis em excesso.";

            $resultadoIA = perguntarIA($prompt);
            if ($resultadoIA['sucesso'] && trim($resultadoIA['resposta']) !== '') {
                $mensagemBoasVindas = trim($resultadoIA['resposta']);
            }

            $resultadoWhatsapp = match ($api_whatsapp) {
                'menuia'    => enviarWhatsapp($numeroWhatsapp, $mensagemBoasVindas),
                'meta'      => enviarWhatsappCloud($numeroWhatsapp, $mensagemBoasVindas),
                'evolution' => enviarWhatsappEvolution($numeroWhatsapp, $mensagemBoasVindas),
                default     => ['sucesso' => false, 'erro' => 'Nenhuma API de WhatsApp configurada.'],
            };
            $whatsappEnviado = $resultadoWhatsapp['sucesso'];
            $whatsappErro    = $resultadoWhatsapp['erro'] ?? null;
        }

        // Envia a mesma mensagem de boas-vindas por e-mail (se houver e-mail informado e
        // "Notificar cadastro?" estiver em Sim). E-mail é opcional pra cliente, diferente
        // de usuário, por isso o gate em $email !== ''.
        $emailEnviado = null;
        if ($email !== '' && $notificarCadastro === 'Sim') {
            require_once __DIR__ . '/../../funcoes/email.php';

            // Reaproveita o texto já definido acima (gerado por IA ou o padrão) — se o
            // telefone não tiver sido informado, monta aqui o texto padrão do zero.
            $textoBoasVindas = $mensagemBoasVindas ?? "Olá, {$nome}! Seu cadastro no {$nome_sistema} foi criado com sucesso. Qualquer dúvida, estamos à disposição!";

            $assuntoEmail = "Bem-vindo(a) ao {$nome_sistema}!";
            $mensagemEmail = '<p>' . nl2br(htmlspecialchars($textoBoasVindas, ENT_QUOTES, 'UTF-8')) . '</p>';

            $emailEnviado = enviarEmailGlobal($email, $assuntoEmail, $mensagemEmail);
        }

        resp(true, 'Cliente cadastrado com sucesso!', [
            'whatsapp_enviado' => $whatsappEnviado,
            'whatsapp_erro'    => $whatsappErro,
            'email_enviado'    => $emailEnviado,
        ]);
    }
} catch (PDOException $e) {
    resp(false, 'Erro no banco de dados: ' . $e->getMessage());
}
