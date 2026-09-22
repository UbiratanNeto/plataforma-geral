<?php
/**
 * painel/scripts/clientes/importar.php
 * Importa clientes de um arquivo .xlsx (mesmo formato de painel/scripts/clientes/exportar.php).
 * Linha com "ID" preenchido e existente vira UPDATE; sem ID (ou ID que não existe) vira INSERT.
 * Não dispara WhatsApp/e-mail de boas-vindas aqui — isso é só pra criação manual (modal), pra
 * não sair mandando mensagem em massa numa importação de planilha.
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

require_once __DIR__ . '/../../../conexao.php';
require_once __DIR__ . '/../../includes/permissoes.php';
require_once __DIR__ . '/../../../painel/funcoes/logs.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

if (!podeExecutarAcao($pdo, $_SESSION['id'] ?? null, $_SESSION['cargo_id'] ?? null, 'criar')) {
    resp(false, 'Você não tem permissão para importar clientes.');
}

if (empty($_FILES['arquivo']['name'])) {
    resp(false, 'Nenhum arquivo enviado.');
}

$arquivo = $_FILES['arquivo'];
if ($arquivo['error'] !== UPLOAD_ERR_OK) {
    resp(false, 'Falha ao enviar o arquivo.');
}
if (strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION)) !== 'xlsx') {
    resp(false, 'Envie um arquivo .xlsx (mesmo modelo gerado pelo botão Exportar).');
}

use PhpOffice\PhpSpreadsheet\IOFactory;

try {
    $spreadsheet = IOFactory::load($arquivo['tmp_name']);
    $sheet = $spreadsheet->getActiveSheet();
    $linhas = $sheet->toArray(null, true, true, false);
} catch (Throwable $e) {
    resp(false, 'Não foi possível ler o arquivo: ' . $e->getMessage());
}

// Primeira linha é o cabeçalho (ID, Nome, E-mail, Telefone, DDI, CPF/CNPJ, Tipo, Ativo,
// Notificar Cadastro, CEP, Endereço, Número, Complemento, Bairro, Cidade, Estado, Observações)
array_shift($linhas);

$inseridos = 0;
$atualizados = 0;
$ignorados = 0;
$erros = [];

foreach ($linhas as $i => $linha) {
    $numeroLinha = $i + 2; // +2: array 0-based + já tirou o cabeçalho

    [
        $id, $nome, $email, $telefone, $ddi, $cpfCnpj, $tipo, $ativo, $notificarCadastro,
        $cep, $endereco, $numero, $complemento, $bairro, $cidade, $estado, $observacoes,
    ] = array_pad($linha, 17, null);

    $nome     = trim((string) $nome);
    $telefone = trim((string) $telefone);

    // Mesmas obrigatoriedades do cadastro manual (painel/scripts/clientes/salvar.php).
    if ($nome === '' || $telefone === '') {
        $ignorados++;
        $erros[] = "Linha {$numeroLinha}: nome e telefone são obrigatórios — ignorada.";
        continue;
    }

    $id = is_numeric($id) ? (int) $id : null;
    $ddi = trim((string) $ddi) ?: '55';
    $ativo = in_array(trim((string) $ativo), ['Sim', 'Não'], true) ? trim((string) $ativo) : 'Sim';
    $notificarCadastro = in_array(trim((string) $notificarCadastro), ['Sim', 'Não'], true) ? trim((string) $notificarCadastro) : 'Sim';
    $estado = strtoupper(trim((string) $estado)) ?: null;

    $params = [
        ':nome' => $nome,
        ':email' => trim((string) $email) ?: null,
        ':telefone' => $telefone,
        ':ddi' => $ddi,
        ':cpf_cnpj' => trim((string) $cpfCnpj) ?: null,
        ':tipo' => trim((string) $tipo) ?: null,
        ':ativo' => $ativo,
        ':notificar_cadastro' => $notificarCadastro,
        ':cep' => trim((string) $cep) ?: null,
        ':endereco' => trim((string) $endereco) ?: null,
        ':numero' => trim((string) $numero) ?: null,
        ':complemento' => trim((string) $complemento) ?: null,
        ':bairro' => trim((string) $bairro) ?: null,
        ':cidade' => trim((string) $cidade) ?: null,
        ':estado' => $estado,
        ':observacoes' => trim((string) $observacoes) ?: null,
    ];

    try {
        // Só faz UPDATE se o ID da planilha realmente existir na base — senão trata como novo.
        $clienteExiste = false;
        if ($id) {
            $stmtChecagem = $pdo->prepare("SELECT id FROM clientes WHERE id = ?");
            $stmtChecagem->execute([$id]);
            $clienteExiste = (bool) $stmtChecagem->fetchColumn();
        }

        if ($clienteExiste) {
            $params[':id'] = $id;
            $pdo->prepare("
                UPDATE clientes SET nome = :nome, email = :email, telefone = :telefone, ddi = :ddi,
                    cpf_cnpj = :cpf_cnpj, tipo = :tipo, ativo = :ativo, notificar_cadastro = :notificar_cadastro,
                    cep = :cep, endereco = :endereco, numero = :numero, complemento = :complemento,
                    bairro = :bairro, cidade = :cidade, estado = :estado, observacoes = :observacoes
                WHERE id = :id
            ")->execute($params);

            registrarLog($pdo, 'editar', 'clientes', $id, "Cliente \"{$nome}\" atualizado via importação de planilha");
            $atualizados++;
        } else {
            $params[':data_cadastro'] = date('Y-m-d H:i:s');
            $pdo->prepare("
                INSERT INTO clientes (nome, email, telefone, ddi, cpf_cnpj, tipo, ativo, notificar_cadastro,
                    cep, endereco, numero, complemento, bairro, cidade, estado, observacoes, foto, data_cadastro)
                VALUES (:nome, :email, :telefone, :ddi, :cpf_cnpj, :tipo, :ativo, :notificar_cadastro,
                    :cep, :endereco, :numero, :complemento, :bairro, :cidade, :estado, :observacoes, NULL, :data_cadastro)
            ")->execute($params);

            $novoId = (int) $pdo->lastInsertId();
            registrarLog($pdo, 'inserir', 'clientes', $novoId, "Cliente \"{$nome}\" criado via importação de planilha");
            $inseridos++;
        }
    } catch (PDOException $e) {
        $ignorados++;
        $erros[] = "Linha {$numeroLinha}: erro no banco de dados — {$e->getMessage()}";
    }
}

resp(true, "Importação concluída: {$inseridos} criado(s), {$atualizados} atualizado(s), {$ignorados} ignorada(s).", [
    'inseridos'   => $inseridos,
    'atualizados' => $atualizados,
    'ignorados'   => $ignorados,
    'erros'       => $erros,
]);
