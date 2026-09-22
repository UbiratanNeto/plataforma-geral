<?php
/**
 * painel/funcoes/relatorio_pdf.php
 * Peças reutilizáveis pra gerar relatórios em PDF (DomPDF) — cabeçalho, CSS base e
 * configuração/numeração de página, comuns a qualquer relatório (Logs, Clientes, ...).
 * Cada relatório continua livre pra montar sua própria tabela e estilos específicos
 * (ex.: cores de badge) por cima disso.
 */
require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * Monta a logo do sistema como data URI base64, pronta pra usar num <img src="">.
 * Sem isso, o DomPDF precisaria de acesso a arquivo/rede habilitado (isRemoteEnabled).
 *
 * @param string|null $logo Nome do arquivo salvo em Configurações (ex.: $logo de conexao.php)
 */
function logoRelatorioDataUri(?string $logo): ?string
{
    if (empty($logo)) {
        return null;
    }

    $logoPath = __DIR__ . '/../../uploads/' . basename($logo);
    if (!is_file($logoPath)) {
        return null;
    }

    $mime = mime_content_type($logoPath) ?: 'image/png';
    return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
}

/**
 * CSS base comum a todos os relatórios. Cada relatório concatena o próprio CSS específico
 * (cores de badge, larguras de coluna, etc.) depois deste.
 */
function estiloBaseRelatorio(): string
{
    return '
        body { font-family: Arial, sans-serif; font-size: 11px; color: #1e293b; }
        h1 { font-size: 16px; margin-bottom: 2px; }
        .subtitulo { color: #64748b; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #cbd5e1; padding: 5px 7px; text-align: left; }
        th { background: #f1f5f9; }
        .badge { padding: 2px 6px; border-radius: 4px; color: #fff; font-size: 10px; }
        .rodape { margin-top: 16px; font-size: 9px; color: #94a3b8; }
        .cabecalho td { border: 1px solid #cbd5e1; padding: 8px 12px; vertical-align: middle; }
    ';
}

/**
 * Cabeçalho padrão de qualquer relatório — 3 colunas com borda (igual ao modelo do curso):
 * logo | nome do sistema + telefone/e-mail + endereço | título do relatório + data de geração.
 * Seguido do texto de período/filtros aplicados, logo abaixo do cabeçalho.
 *
 * @param string      $titulo          Título do relatório (ex.: "Relatório de Logs do Sistema")
 * @param string|null $nomeSistema     $nome_sistema, de conexao.php
 * @param string|null $logo            $logo, de conexao.php (nome do arquivo, não o caminho)
 * @param string|null $telefoneSistema $telefone_sistema, de conexao.php
 * @param string|null $emailSistema    $email_sistema, de conexao.php
 * @param string|null $endereco        $endereco, de conexao.php
 * @param string      $periodo         Texto já pronto com período/filtros (ex.: "Período: ... — Usuário: ...")
 */
function cabecalhoRelatorio(
    string $titulo,
    ?string $nomeSistema,
    ?string $logo,
    ?string $telefoneSistema,
    ?string $emailSistema,
    ?string $endereco,
    string $periodo
): string {
    $logoDataUri       = logoRelatorioDataUri($logo);
    $nomeSistemaSeguro = htmlspecialchars($nomeSistema ?: 'Helpdesk', ENT_QUOTES, 'UTF-8');
    $tituloSeguro      = htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8');
    $periodoSeguro     = htmlspecialchars($periodo, ENT_QUOTES, 'UTF-8');
    $geradoEm          = date('d/m/Y H:i');

    $logoHtml = $logoDataUri
        ? '<img src="' . $logoDataUri . '" style="max-width: 70px; max-height: 50px;">'
        : '';

    // Linha "telefone | e-mail" — só entra o que realmente existir preenchido.
    $contato = array_filter([$telefoneSistema, $emailSistema], fn($v) => $v !== null && $v !== '');
    $infoSistema = '<div style="font-size: 13px; font-weight: bold;">' . $nomeSistemaSeguro . '</div>';
    if ($contato) {
        $infoSistema .= '<div style="font-size: 10px; color: #64748b;">' . htmlspecialchars(implode(' | ', $contato), ENT_QUOTES, 'UTF-8') . '</div>';
    }
    if (!empty($endereco)) {
        $infoSistema .= '<div style="font-size: 10px; color: #64748b;">' . htmlspecialchars($endereco, ENT_QUOTES, 'UTF-8') . '</div>';
    }

    return <<<HTML
        <table class="cabecalho">
            <tr>
                <td style="width: 90px; text-align: center;">
                    {$logoHtml}
                </td>
                <td>
                    {$infoSistema}
                </td>
                <td style="text-align: right; white-space: nowrap;">
                    <div style="font-size: 13px; font-weight: bold;">{$tituloSeguro}</div>
                    <div style="font-size: 10px; color: #64748b;">Gerado em: {$geradoEm}</div>
                </td>
            </tr>
        </table>
        <p class="subtitulo" style="margin-top: 10px;">{$periodoSeguro}</p>
        HTML;
}

/**
 * Bloco de assinatura no fim do relatório: imagem + linha + nome de quem está logado
 * (quem gerou o relatório), centralizado na página. A assinatura vem de usuarios.assinatura
 * (Editar Perfil -> Fazer assinatura), embutida em base64 como a logo. Se o usuário ainda não
 * assinou, sai só uma linha em branco com o nome, pra dar pra assinar à mão depois de impresso.
 */
function blocoAssinaturaRelatorio(PDO $pdo): string
{
    $stmt = $pdo->prepare("SELECT nome, assinatura FROM usuarios WHERE id = ?");
    $stmt->execute([(int) ($_SESSION['id'] ?? 0)]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$usuario) {
        return '';
    }

    $nomeSeguro = htmlspecialchars($usuario['nome'], ENT_QUOTES, 'UTF-8');

    $imagemHtml = '<div style="height: 80px;"></div>';
    if (!empty($usuario['assinatura'])) {
        $caminho = __DIR__ . '/../../uploads/assinaturas/' . basename($usuario['assinatura']);
        if (is_file($caminho)) {
            $mime = mime_content_type($caminho) ?: 'image/png';
            $dataUri = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($caminho));
            $imagemHtml = '<img src="' . $dataUri . '" style="display: block; width: 220px; height: 80px;">';
        }
    }

    return <<<HTML
        <div style="margin-top: 36px; text-align: center; page-break-inside: avoid;">
            <div style="display: inline-block; width: 220px; text-align: center;">
                {$imagemHtml}
                <div style="font-size: 11px; font-weight: bold; padding-top: 4px; border-top: 1px solid #000;">{$nomeSeguro}</div>
            </div>
        </div>
        HTML;
}

/**
 * Instancia o Dompdf já configurado do jeito padrão do projeto (sem acesso remoto,
 * fonte Arial) e adiciona a numeração de página ("Página X de Y") depois de renderizar.
 * A posição do rodapé se ajusta ao tamanho real da página, então funciona tanto em
 * paisagem quanto em retrato.
 *
 * @param string $orientacao 'landscape' ou 'portrait'
 */
function criarDompdfRelatorio(string $html, string $orientacao = 'landscape'): \Dompdf\Dompdf
{
    $options = new \Dompdf\Options();
    $options->set('isRemoteEnabled', false);
    $options->set('defaultFont', 'Arial');

    $dompdf = new \Dompdf\Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', $orientacao);
    $dompdf->render();

    $canvas = $dompdf->getCanvas();
    $canvas->page_text(
        $canvas->get_width() - 80,
        $canvas->get_height() - 25,
        'Página {PAGE_NUM} de {PAGE_COUNT}',
        null,
        8,
        [0.58, 0.64, 0.72]
    );

    return $dompdf;
}
