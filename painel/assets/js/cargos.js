/**
 * painel/assets/js/cargos.js
 * Gestão de Cargos: lista via DataTables (Bootstrap 5 + jQuery), cria/edita/exclui via fetch.
 * "acesso_total" ainda mora no cargo (bypassa toda checagem de permissão pra quem tiver
 * esse cargo) — as permissões por menu, essas, agora ficam por usuário (tela de Usuários).
 *
 * Mesmas funções globais genéricas de usuarios.js (novo/editar/excluir/salvar) — só carregamos
 * este arquivo na página de Cargos, então não colide com o mesmo padrão em outras páginas.
 */

let tabela;
let idParaExcluir = null;

$(function () {
    const $tabela = $('#tabelaCargos');
    if ($tabela.length === 0) return; // Este script só faz sentido na página de Cargos

    tabela = $tabela.DataTable({
        ajax: {
            url: 'scripts/cargos/listar.php',
            dataSrc: function (json) {
                if (!json.ok) {
                    Mensagens.erro('Erro', json.msg || 'Não foi possível carregar os cargos.');
                    return [];
                }
                return json.data;
            }
        },
        columns: [
            { data: 'nome' },
            {
                data: 'id', orderable: false, className: 'text-end', render: function (id) {
                    // Permissões de AÇÃO do usuário LOGADO (globais — painel/includes/head.php)
                    const permissoesAcao = window.PERMISSOES_ACAO || { editar: true, excluir: true };

                    let html = '<div class="btn-group btn-group-sm">';
                    if (permissoesAcao.editar) {
                        html += '<button type="button" class="btn btn-outline-secondary btn-editar" data-id="' + id + '" title="Editar"><i class="fa-solid fa-pen"></i></button>';
                    }
                    if (permissoesAcao.excluir) {
                        html += '<button type="button" class="btn btn-outline-danger btn-excluir" data-id="' + id + '" title="Excluir"><i class="fa-solid fa-trash"></i></button>';
                    }
                    html += '</div>';
                    return html;
                }
            },
        ],
        order: [[0, 'asc']],
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json'
        },
    });

    // Delegação de eventos: os botões de ação são recriados a cada "draw" do DataTables
    $tabela.on('click', '.btn-editar', function () {
        editar($(this).data('id'));
    });
    $tabela.on('click', '.btn-excluir', function () {
        excluir($(this).data('id'));
    });

    $('#formCargo').on('submit', function (e) {
        e.preventDefault();
        salvar(this);
    });

    $('#btnConfirmarExclusao').on('click', function () {
        confirmarExclusao();
    });
});

function novo() {
    const form = document.getElementById('formCargo');
    form.reset();
    $('#cargo_id').val('');
    $('#modalCargoLabel').text('Cadastrar Cargo');
    bootstrap.Modal.getOrCreateInstance('#modalCargo').show();
}

function editar(id) {
    fetch('scripts/cargos/buscar.php?id=' + encodeURIComponent(id))
        .then(function (resposta) { return resposta.json(); })
        .then(function (dados) {
            if (!dados.ok) {
                Mensagens.erro('Erro', dados.msg);
                return;
            }

            const c = dados.data;

            $('#cargo_id').val(c.id);
            $('#cargo_nome').val(c.nome || '');
            $('#modalCargoLabel').text('Editar Cargo');
            document.getElementById('cargo_acesso_total').checked = !!c.acesso_total;

            bootstrap.Modal.getOrCreateInstance('#modalCargo').show();
        })
        .catch(function () {
            Mensagens.erro('Erro', 'Não foi possível carregar os dados do cargo.');
        });
}

function excluir(id) {
    idParaExcluir = id;
    bootstrap.Modal.getOrCreateInstance('#modalConfirmarExclusao').show();
}

function confirmarExclusao() {
    if (!idParaExcluir) return;

    const botao = document.getElementById('btnConfirmarExclusao');
    const textoOriginal = botao.textContent;
    botao.disabled = true;
    botao.textContent = 'Excluindo...';

    fetch('scripts/cargos/excluir.php', {
        method: 'POST',
        body: new URLSearchParams({ id: idParaExcluir })
    })
        .then(function (resposta) { return resposta.json(); })
        .then(function (dados) {
            bootstrap.Modal.getOrCreateInstance('#modalConfirmarExclusao').hide();
            if (dados.ok) {
                tabela.ajax.reload(null, false);
                Mensagens.sucesso('Sucesso!', dados.msg);
            } else {
                Mensagens.erro('Erro', dados.msg);
            }
        })
        .catch(function () {
            bootstrap.Modal.getOrCreateInstance('#modalConfirmarExclusao').hide();
            Mensagens.erro('Erro de conexão', 'Não foi possível excluir agora. Tente novamente.');
        })
        .finally(function () {
            idParaExcluir = null;
            botao.disabled = false;
            botao.textContent = textoOriginal;
        });
}

function salvar(form) {
    const botao = document.getElementById('btnSalvarCargo');
    const textoOriginal = botao.textContent;
    botao.disabled = true;
    botao.textContent = 'Salvando...';

    fetch('scripts/cargos/salvar.php', {
        method: 'POST',
        body: new FormData(form)
    })
        .then(function (resposta) { return resposta.json(); })
        .then(function (dados) {
            if (dados.ok) {
                bootstrap.Modal.getOrCreateInstance('#modalCargo').hide();
                tabela.ajax.reload(null, false);
                Mensagens.sucesso('Sucesso!', dados.msg);
            } else {
                Mensagens.erro('Atenção', dados.msg);
            }
        })
        .catch(function () {
            Mensagens.erro('Erro de conexão', 'Não foi possível salvar agora. Tente novamente.');
        })
        .finally(function () {
            botao.disabled = false;
            botao.textContent = textoOriginal;
        });
}
