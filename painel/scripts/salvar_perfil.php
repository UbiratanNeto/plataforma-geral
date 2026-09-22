<?php
@session_start();
header('Content-Type: application/json; charset=utf-8');

// Valida se o usuário está logado
if (empty($_SESSION['id'])) {
    echo json_encode(['ok' => false, 'msg' => 'Sessão expirada.']);
    exit();
}

// Caminho relativo para a conexão
require_once __DIR__ . '/../../conexao.php';

function resp($ok, $msg, $extra = []) {
    echo json_encode(array_merge(['ok' => $ok, 'msg' => $msg], $extra));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    resp(false, 'Método inválido.');
}

$id_usuario = (int) $_SESSION['id'];

// Resgata os dados enviados pelo modal de perfil
$nome = trim($_POST['nome'] ?? '');
$email = trim($_POST['email'] ?? '');
$cpf = trim($_POST['cpf'] ?? '');
$telefone = trim($_POST['telefone'] ?? '');
$cep = trim($_POST['cep'] ?? '');
$estado = trim($_POST['estado'] ?? '');
$cidade = trim($_POST['cidade'] ?? '');
$bairro = trim($_POST['bairro'] ?? '');
$endereco = trim($_POST['endereco'] ?? '');
$numero = trim($_POST['numero'] ?? '');
$complemento = trim($_POST['complemento'] ?? '');
$nova_senha = $_POST['nova_senha'] ?? '';

// Validações básicas
if ($nome === '') {
    resp(false, 'Informe o nome completo.');
}
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    resp(false, 'Informe um e-mail válido.');
}
if ($nova_senha !== '' && strlen($nova_senha) < 3) {
    resp(false, 'A nova senha precisa ter pelo menos 3 caracteres.');
}

try {
    // A tabela não tem restrição de e-mail único no banco, então checamos aqui
    $stmtDup = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ? LIMIT 1");
    $stmtDup->execute([$email, $id_usuario]);
    if ($stmtDup->fetch()) {
        resp(false, 'Este e-mail já está sendo usado por outro usuário.');
    }

    // ===== Upload de foto (opcional) =====
    $novoNomeFoto = null;

    if (!empty($_FILES['foto']['name'])) {
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

        $uploadsDir = realpath(__DIR__ . '/../../uploads/perfil');
        if ($uploadsDir === false) {
            resp(false, 'Pasta de uploads não encontrada.');
        }

        $novoNomeFoto = 'perfil_' . $id_usuario . '_' . bin2hex(random_bytes(4)) . '.' . $extensao;
        $destino = $uploadsDir . DIRECTORY_SEPARATOR . $novoNomeFoto;

        if (!move_uploaded_file($arquivo['tmp_name'], $destino)) {
            resp(false, 'Não foi possível salvar a foto no servidor.');
        }

        // Apaga a foto antiga (exceto o fallback padrão do sistema) pra não acumular lixo
        $stmtFotoAtual = $pdo->prepare("SELECT foto FROM usuarios WHERE id = ? LIMIT 1");
        $stmtFotoAtual->execute([$id_usuario]);
        $fotoAntiga = $stmtFotoAtual->fetchColumn();
        if ($fotoAntiga && $fotoAntiga !== 'sem_foto.png') {
            $caminhoAntigo = $uploadsDir . DIRECTORY_SEPARATOR . basename($fotoAntiga);
            if (is_file($caminhoAntigo)) {
                @unlink($caminhoAntigo);
            }
        }
    }

    $sql = "UPDATE usuarios SET
            nome = ?,
            email = ?,
            cpf = ?,
            telefone = ?,
            cep = ?,
            estado = ?,
            cidade = ?,
            bairro = ?,
            endereco = ?,
            numero = ?,
            complemento = ?";
    $params = [
        $nome, $email, $cpf, $telefone,
        $cep, $estado, $cidade, $bairro,
        $endereco, $numero, $complemento,
    ];

    if ($novoNomeFoto !== null) {
        $sql .= ", foto = ?";
        $params[] = $novoNomeFoto;
    }

    if ($nova_senha !== '') {
        $sql .= ", senha = ?";
        $params[] = password_hash($nova_senha, PASSWORD_DEFAULT);
    }

    $sql .= " WHERE id = ?";
    $params[] = $id_usuario;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // Atualiza as variáveis de sessão para refletir imediatamente na interface
    $_SESSION['nome'] = $nome;
    $_SESSION['email'] = $email;
    if ($novoNomeFoto !== null) {
        $_SESSION['foto'] = $novoNomeFoto;
    }

    resp(true, 'Perfil atualizado com sucesso!');

} catch (PDOException $e) {
    resp(false, 'Erro ao atualizar o perfil: ' . $e->getMessage());
}