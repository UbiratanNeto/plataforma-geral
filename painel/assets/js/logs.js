/**
 * painel/assets/js/logs.js
 * Logs do Sistema: lista via DataTables (Bootstrap 5 + jQuery) — só leitura, sem
 * criar/editar/excluir (o próprio log é um registro imutável).
 */

const BADGES_ACAO = {
    login: 'bg-primary',
    logout: 'bg-secondary',
    inserir: 'bg-success',
    editar: 'bg-warning text-dark',
    excluir: 'bg-danger',
};

function formatarDataHora(valor) {
    // valor vem como "YYYY-MM-DD HH:MM:SS" (formato DATETIME do MySQL)
    const partes = valor.split(' ');
    if (partes.length !== 2) return valor;
    const [data, hora] = partes;
    const [ano, mes, dia] = data.split('-');
    return `${dia}/${mes}/${ano} ${hora}`;
}

/** YYYY-MM-DD no fuso local (evita o typeof toISOString() que usa UTC e pode "voltar" um dia). */
function dataLocalISO(data) {
    const ano = data.getFullYear();
    const mes = String(data.getMonth() + 1).padStart(2, '0');
    const dia = String(data.getDate()).padStart(2, '0');
    return `${ano}-${mes}-${dia}`;
}

$(function () {
    const $tabela = $('#tabelaLogs');
    if ($tabela.length === 0) return; // Este script só faz sentido na página de Logs

    const $dataInicial = $('#filtro_data_inicial');
    const $dataFinal = $('#filtro_data_final');
    const $usuario = $('#filtro_usuario');
    const $acao = $('#filtro_acao');

    const tabela = $tabela.DataTable({
        processing: true, // mostra "Processando..." (já traduzido, via language abaixo) durante o ajax
        ajax: {
            url: 'scripts/logs/listar.php',
            data: function (parametros) {
                parametros.data_inicial = $dataInicial.val();
                parametros.data_final = $dataFinal.val();
                parametros.usuario = $usuario.val();
                parametros.acao = $acao.val();
            },
            dataSrc: function (json) {
                if (!json.ok) {
                    Mensagens.erro('Erro', json.msg || 'Não foi possível carregar os logs.');
                    return [];
                }
                return json.data;
            }
        },
        columns: [
            {
                data: 'criado_em',
                render: function (valor, tipo) {
                    // Ordena/filtra pela string ISO original (ordem cronológica certa);
                    // só exibe formatado (dd/mm/aaaa hh:mm:ss) na tela.
                    return (tipo === 'display') ? formatarDataHora(valor) : valor;
                }
            },
            { data: 'usuario_nome' },
            {
                data: 'acao', render: function (acao) {
                    const classe = BADGES_ACAO[acao] || 'bg-secondary';
                    return '<span class="badge ' + classe + '">' + acao + '</span>';
                }
            },
            { data: 'entidade' },
            { data: 'descricao', render: function (v) { return v || '-'; } },
            { data: 'ip', render: function (v) { return v || '-'; } },
        ],
        order: [[0, 'desc']],
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json'
        },
        // Layout customizado: sem caixa de busca nativa (já temos o filtro "Usuário" acima)
        // e sem o texto "Mostrando de X até Y" — só "Exibir X por página" + paginação.
        dom: "t<'row mt-3 align-items-center'<'col-sm-6'l><'col-sm-6 d-flex justify-content-end'p>>",
    });

    // Muda a data (inicial ou final) e a tabela já recarrega filtrada — sem precisar
    // de um botão "Filtrar" separado.
    $dataInicial.on('change', function () { tabela.ajax.reload(); });
    $dataFinal.on('change', function () { tabela.ajax.reload(); });

    // Busca por usuário — debounce de 400ms, pra não recarregar a cada letra digitada.
    let debounceUsuario = null;
    $usuario.on('input', function () {
        clearTimeout(debounceUsuario);
        debounceUsuario = setTimeout(function () { tabela.ajax.reload(); }, 400);
    });

    // Select de Ação — filtra assim que troca a opção, sem precisar de botão.
    $acao.on('change', function () { tabela.ajax.reload(); });

    // "Limpar" — esvazia data, usuário e ação, recarrega sem filtro nenhum.
    $('#btnLimparFiltro').on('click', function () {
        $dataInicial.val('');
        $dataFinal.val('');
        $usuario.val('');
        $acao.val('');
        tabela.ajax.reload();
    });

    // Atalhos — preenchem Data inicial/final e já disparam o filtro.
    $('[data-atalho]').on('click', function () {
        const hoje = new Date();
        let inicio = new Date(hoje);
        let fim = new Date(hoje);

        switch ($(this).data('atalho')) {
            case 'hoje':
                break; // inicio = fim = hoje
            case 'ontem':
                inicio.setDate(inicio.getDate() - 1);
                fim.setDate(fim.getDate() - 1);
                break;
            case 'mes':
                inicio = new Date(hoje.getFullYear(), hoje.getMonth(), 1);
                break;
            case '7dias':
                inicio.setDate(inicio.getDate() - 6); // hoje + 6 dias atrás = 7 dias
                break;
        }

        $dataInicial.val(dataLocalISO(inicio));
        $dataFinal.val(dataLocalISO(fim));
        tabela.ajax.reload();
    });

    // "Relatório" — abre o PDF numa aba nova, com os mesmos filtros já aplicados na tela.
    $('#btnRelatorioLogs').on('click', function () {
        const botao = this;
        const textoOriginal = botao.innerHTML;

        // Abre a aba já no clique (síncrono), senão o navegador bloqueia como pop-up
        // depois que o fetch() responder — só troca o conteúdo dela quando o PDF terminar.
        const novaAba = window.open('', '_blank');
        if (novaAba) {
            novaAba.document.write('<p style="font-family:sans-serif;padding:2rem;color:#64748b;">Gerando relatório...</p>');
        }

        botao.disabled = true;
        botao.innerHTML = '<span class="spinner-border spinner-border-sm" style="margin-right:0.4rem;"></span>Gerando...';

        const parametros = new URLSearchParams({
            data_inicial: $dataInicial.val(),
            data_final: $dataFinal.val(),
            usuario: $usuario.val(),
            acao: $acao.val(),
        });

        fetch('scripts/logs/relatorio.php?' + parametros.toString())
            .then(function (resposta) {
                if (!resposta.ok) throw new Error('Falha ao gerar o PDF.');
                return resposta.blob();
            })
            .then(function (blob) {
                const url = URL.createObjectURL(blob);
                if (novaAba) {
                    novaAba.location.href = url;
                } else {
                    window.open(url, '_blank');
                }
            })
            .catch(function () {
                if (novaAba) novaAba.close();
                Mensagens.erro('Erro', 'Não foi possível gerar o relatório agora.');
            })
            .finally(function () {
                botao.disabled = false;
                botao.innerHTML = textoOriginal;
            });
    });
});
