<?php
/**
 * painel/scripts/usuarios/salvar.php
 * Cria ou atualiza um usuário (Gestão de Usuários).
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
 * Valida e salva o upload da foto em uploads/perfil/ (mesma pasta usada pelo modal de Perfil).
 * Devolve o novo nome de arquivo, ou null se nenhum arquivo novo foi enviado.
 */
function processarUploadFotoUsuario(string $arquivoAntigo): ?string {
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

    $uploadsDir = __DIR__ . '/../../../uploads/perfil';
    if (!is_dir($uploadsDir)) {
        mkdir($uploadsDir, 0755, true);
    }
    $uploadsDir = realpath($uploadsDir);

    $novoNome = 'usuario_' . bin2hex(random_bytes(4)) . '.' . $extensao;
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
$cpf         = trim($_POST['cpf'] ?? '');
$endereco    = trim($_POST['endereco'] ?? '');
$numero      = trim($_POST['numero'] ?? '');
$complemento = trim($_POST['complemento'] ?? '');
$bairro      = trim($_POST['bairro'] ?? '');
$cidade      = trim($_POST['cidade'] ?? '');
$estado      = strtoupper(trim($_POST['estado'] ?? ''));
$cep         = trim($_POST['cep'] ?? '');
$cargo_id    = filter_input(INPUT_POST, 'cargo_id', FILTER_VALIDATE_INT);
$ativo       = isset($_POST['ativo']) ? (int) $_POST['ativo'] : 1;
$senha       = $_POST['senha'] ?? '';

// Permissão de AÇÃO (global, não por página) — criar ou editar dependendo se veio um $id
$acao = $id ? 'editar' : 'criar';
if (!podeExecutarAcao($pdo, $_SESSION['id'] ?? null, $_SESSION['cargo_id'] ?? null, $acao)) {
    resp(false, 'Você não tem permissão para ' . ($id ? 'editar' : 'criar') . ' usuários.');
}

// ===== Validações =====
if ($nome === '') {
    resp(false, 'Informe o nome completo.');
}
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    resp(false, 'Informe um e-mail válido.');
}
if ($senha !== '' && strlen($senha) < 6) {
    resp(false, 'A senha precisa ter pelo menos 6 caracteres.');
}
if (!$id && $senha === '') {
    resp(false, 'Informe a senha para o novo usuário.');
}
if (!$cargo_id) {
    resp(false, 'Selecione um cargo válido.');
}

try {
    // Cargo precisa existir de verdade (é FK agora) — busca o nome aqui e reaproveita pra
    // manter a coluna "nivel" sincronizada, já que sidebar/verificar.php ainda leem esse texto.
    $stmtCargo = $pdo->prepare("SELECT nome FROM cargos WHERE id = ?");
    $stmtCargo->execute([$cargo_id]);
    $nivel = $stmtCargo->fetchColumn();
    if ($nivel === false) {
        resp(false, 'O cargo selecionado não existe mais. Atualize a página e tente novamente.');
    }

    // E-mail duplicado (considerando outros usuários, não o próprio ao editar)
    $stmtDup = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
    $stmtDup->execute([$email, $id ?? 0]);
    if ($stmtDup->fetch()) {
        resp(false, 'Este e-mail já está cadastrado por outro usuário.');
    }

    // Telefone duplicado (considerando outros usuários, não o próprio ao editar) — comparado
    // só pelos dígitos + DDI, pra não deixar passar duplicidade por formatação diferente
    // (ex.: "(92) 7228-925" vs "92 7228925").
    if ($telefone !== '') {
        $telefoneDigitos = preg_replace('/\D/', '', $telefone);
        $stmtTel = $pdo->prepare("SELECT id, ddi, telefone FROM usuarios WHERE telefone != '' AND telefone IS NOT NULL AND id != ?");
        $stmtTel->execute([$id ?? 0]);
        foreach ($stmtTel->fetchAll() as $outro) {
            $outroDigitos = preg_replace('/\D/', '', $outro['telefone']);
            if ($outroDigitos === $telefoneDigitos && $outro['ddi'] === $ddi) {
                resp(false, 'Este telefone já está cadastrado por outro usuário.');
            }
        }
    }

    // Foto atual (pra saber o que apagar, se uma nova for enviada)
    $fotoAtual = '';
    if ($id) {
        $stmtFoto = $pdo->prepare("SELECT foto FROM usuarios WHERE id = ?");
        $stmtFoto->execute([$id]);
        $fotoAtual = (string) $stmtFoto->fetchColumn();
    }
    $novaFoto = processarUploadFotoUsuario($fotoAtual);

    $params = [
        ':nome' => $nome, ':email' => $email, ':telefone' => $telefone, ':ddi' => $ddi, ':cpf' => $cpf,
        ':endereco' => $endereco, ':numero' => $numero, ':complemento' => $complemento,
        ':bairro' => $bairro, ':cidade' => $cidade, ':estado' => $estado, ':cep' => $cep,
        ':nivel' => $nivel, ':cargo_id' => $cargo_id, ':ativo' => $ativo,
    ];

    if ($id) {
        // ===== Atualização (UPDATE) =====
        $set = [
            'nome = :nome', 'email = :email', 'telefone = :telefone', 'ddi = :ddi', 'cpf = :cpf',
            'endereco = :endereco', 'numero = :numero', 'complemento = :complemento',
            'bairro = :bairro', 'cidade = :cidade', 'estado = :estado', 'cep = :cep',
            'nivel = :nivel', 'cargo_id = :cargo_id', 'ativo = :ativo', 'data_atualizacao = NOW()',
        ];

        if ($senha !== '') {
            $set[] = 'senha = :senha';
            $params[':senha'] = password_hash($senha, PASSWORD_DEFAULT);
        }
        if ($novaFoto !== null) {
            $set[] = 'foto = :foto';
            $params[':foto'] = $novaFoto;
        }

        $params[':id'] = $id;
        $sql = 'UPDATE usuarios SET ' . implode(', ', $set) . ' WHERE id = :id';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        registrarLog($pdo, 'editar', 'usuarios', $id, "Usuário \"{$nome}\" atualizado");

        resp(true, 'Usuário atualizado com sucesso!');
    } else {
        // ===== Inserção (INSERT) — senha e foto sempre presentes aqui =====
        $params[':senha'] = password_hash($senha, PASSWORD_DEFAULT);
        $params[':foto']  = $novaFoto; // pode ser null; a coluna aceita NULL

        $stmt = $pdo->prepare("
            INSERT INTO usuarios (nome, email, telefone, ddi, cpf, endereco, numero, complemento, bairro, cidade, estado, cep, nivel, cargo_id, ativo, senha, foto)
            VALUES (:nome, :email, :telefone, :ddi, :cpf, :endereco, :numero, :complemento, :bairro, :cidade, :estado, :cep, :nivel, :cargo_id, :ativo, :senha, :foto)
        ");
        $stmt->execute($params);
        $novoId = (int) $pdo->lastInsertId();

        registrarLog($pdo, 'inserir', 'usuarios', $novoId, "Usuário \"{$nome}\" criado");

        // URL fixa, definida em Configurações do Sistema — não depende de qual página
        // disparou essa requisição. Usada tanto na mensagem de WhatsApp quanto no e-mail.
        $urlAcesso = ($url_sistema !== '' ? $url_sistema : '') . '/index.php';

        // Envia as credenciais de acesso por WhatsApp (se houver telefone informado e uma API
        // configurada em Configurações). Não bloqueia o cadastro caso o envio falhe.
        $whatsappEnviado = null;
        $whatsappErro    = null;
        if ($telefone !== '') {
            require_once __DIR__ . '/../../apis/whatsapp.php';

            $numeroWhatsapp = preg_replace('/\D/', '', $telefone);
            if ($numeroWhatsapp !== '' && substr($numeroWhatsapp, 0, strlen($ddi)) !== $ddi) {
                $numeroWhatsapp = $ddi . $numeroWhatsapp;
            }

            $mensagemBoasVindas = "Olá, {$nome}! Sua conta no {$nome_sistema} foi criada.\n\n"
                . "Acesso: {$urlAcesso}\n"
                . "E-mail: {$email}\n"
                . "Senha: {$senha}\n\n"
                . "Recomendamos alterar sua senha no primeiro acesso.";

            // Dispara pelo provedor escolhido em Configurações — os dois usam a mesma assinatura
            // (número, mensagem) então o restante do fluxo não precisa saber qual é.
            $resultadoWhatsapp = match ($api_whatsapp) {
                'menuia'    => enviarWhatsapp($numeroWhatsapp, $mensagemBoasVindas),
                'meta'      => enviarWhatsappCloud($numeroWhatsapp, $mensagemBoasVindas),
                'evolution' => enviarWhatsappEvolution($numeroWhatsapp, $mensagemBoasVindas),
                default     => ['sucesso' => false, 'erro' => 'Nenhuma API de WhatsApp configurada.'],
            };
            $whatsappEnviado = $resultadoWhatsapp['sucesso'];
            $whatsappErro    = $resultadoWhatsapp['erro'] ?? null;
        }

        // Envia as mesmas credenciais também por e-mail — sempre roda, já que e-mail é
        // obrigatório pra todo usuário (diferente do telefone, que é opcional).
        require_once __DIR__ . '/../../funcoes/email.php';

        $nomeSeguro = htmlspecialchars($nome, ENT_QUOTES, 'UTF-8');
        $assuntoEmail = "Bem-vindo(a) ao {$nome_sistema}!";
        $mensagemEmail = "<p>Olá, <strong>{$nomeSeguro}</strong>!</p>"
            . "<p>Sua conta no <strong>" . htmlspecialchars($nome_sistema, ENT_QUOTES, 'UTF-8') . "</strong> foi criada com sucesso.</p>"
            . "<p><strong>Acesso:</strong> <a href=\"{$urlAcesso}\">{$urlAcesso}</a><br>"
            . "<strong>E-mail:</strong> " . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . "<br>"
            . "<strong>Senha:</strong> " . htmlspecialchars($senha, ENT_QUOTES, 'UTF-8') . "</p>"
            . "<p>Recomendamos alterar sua senha no primeiro acesso.</p>";

        $emailEnviado = enviarEmailGlobal($email, $assuntoEmail, $mensagemEmail);

        resp(true, 'Usuário cadastrado com sucesso!', [
            'whatsapp_enviado' => $whatsappEnviado,
            'whatsapp_erro'    => $whatsappErro,
            'email_enviado'    => $emailEnviado,
        ]);
    }
} catch (PDOException $e) {
    resp(false, 'Erro no banco de dados: ' . $e->getMessage());
}
