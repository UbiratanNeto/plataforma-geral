/**
 * painel/assets/js/usuarios.js
 * Gestão de Usuários: lista via DataTables (Bootstrap 5 + jQuery), cria/edita/exclui via fetch.
 *
 * Funções globais (novo/editar/excluir/salvar/visualizar), sem namespace: só carregamos este
 * arquivo na página de Usuários, então não há risco de colidir com o mesmo padrão em outras
 * páginas (chamados.js, clientes.js etc. terão suas próprias versões dessas funções).
 */

let tabela;
let idParaExcluir = null;

$(function () {
    const $tabela = $('#tabelaUsuarios');
    if ($tabela.length === 0) return; // Este script só faz sentido na página de Usuários

    // Select2 no lugar do <select> nativo de Nível de Acesso — dropdownParent aponta pro
    // modal porque, dentro de um modal do Bootstrap, o dropdown do Select2 renderiza atrás
    // do backdrop se não for "ancorado" nele.
    $('#usuario_nivel').select2({
        theme: 'bootstrap-5',
        width: '100%',
        dropdownParent: $('#modalUsuario'),
        placeholder: 'Selecione o nível de acesso',
    });

    // Popula o select de Nível de Acesso a partir de Cadastros > Cargos — função genérica
    // (functions.js, carregada em toda página), reaproveitável por qualquer outra tela.
    carregarOpcoesEmSelect('scripts/cargos/listar.php', '#usuario_nivel', 'Nenhum cargo cadastrado');

    tabela = $tabela.DataTable({
        ajax: {
            url: 'scripts/usuarios/listar.php',
            dataSrc: function (json) {
                if (!json.ok) {
                    Mensagens.erro('Erro', json.msg || 'Não foi possível carregar os usuários.');
                    return [];
                }
                return json.data;
            }
        },
        columns: [
            {
                data: 'foto_url', orderable: false, render: function (url) {
                    return '<img src="' + url + '" class="rounded-circle" style="width:2.25rem;height:2.25rem;object-fit:cover;" alt="">';
                }
            },
            { data: 'nome' },
            { data: 'email' },
            { data: 'telefone', render: function (v) { return v || '-'; } },
            { data: 'nivel' },
            {
                data: 'ativo', render: function (ativo) {
                    return ativo == 1
                        ? '<span class="badge bg-success">Ativo</span>'
                        : '<span class="badge bg-danger">Inativo</span>';
                }
            },
            {
                data: 'id', orderable: false, className: 'text-end', render: function (id, tipo, linha) {
                    // Usuário cujo cargo já tem acesso_total não precisa (nem pode) de
                    // permissão individual — botão fica desabilitado, com dica do motivo.
                    const permissoesDesabilitado = linha.acesso_total == 1;
                    const tituloPermissoes = permissoesDesabilitado
                        ? 'Este usuário já tem acesso total pelo cargo'
                        : 'Permissões';

                    // Permissões de AÇÃO (criar/editar/excluir) do usuário LOGADO — globais,
                    // definidas em painel/includes/head.php. Editar/Excluir somem se não tiver.
                    const permissoesAcao = window.PERMISSOES_ACAO || { editar: true, excluir: true };

                    let html = '<div class="btn-group btn-group-sm">';
                    html += '<button type="button" class="btn btn-outline-primary btn-visualizar" data-id="' + id + '" title="Visualizar"><i class="fa-solid fa-eye"></i></button>';
                    if (permissoesAcao.editar) {
                        html += '<button type="button" class="btn btn-outline-secondary btn-editar" data-id="' + id + '" title="Editar"><i class="fa-solid fa-pen"></i></button>';
                    }
                    html += '<button type="button" class="btn btn-outline-secondary btn-permissoes" data-id="' + id + '" title="' + tituloPermissoes + '"' + (permissoesDesabilitado ? ' disabled' : '') + '><i class="fa-solid fa-key"></i></button>';
                    if (permissoesAcao.excluir) {
                        html += '<button type="button" class="btn btn-outline-danger btn-excluir" data-id="' + id + '" title="Excluir"><i class="fa-solid fa-trash"></i></button>';
                    }
                    html += '</div>';
                    return html;
                }
            },
        ],
        order: [[1, 'asc']],
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json'
        },
    });

    // Delegação de eventos: os botões de ação são recriados a cada "draw" do DataTables
    $tabela.on('click', '.btn-visualizar', function () {
        visualizar($(this).data('id'));
    });
    $tabela.on('click', '.btn-editar', function () {
        editar($(this).data('id'));
    });
    $tabela.on('click', '.btn-permissoes', function () {
        permissoes($(this).data('id'));
    });
    $tabela.on('click', '.btn-excluir', function () {
        excluir($(this).data('id'));
    });

    $('#formUsuario').on('submit', function (e) {
        e.preventDefault();
        salvar(this);
    });

    $('#formPermissoes').on('submit', function (e) {
        e.preventDefault();
        salvarPermissoes(this);
    });

    $('#btnMarcarTodasPermissoes').on('click', function () {
        alternarTodasPermissoes(true);
    });
    $('#btnDesmarcarTodasPermissoes').on('click', function () {
        alternarTodasPermissoes(false);
    });

    $('#usuario_foto').on('change', function () {
        if (!this.files || !this.files[0]) return;
        const leitor = new FileReader();
        leitor.onload = function (e) {
            $('#previewFotoUsuario').attr('src', e.target.result);
        };
        leitor.readAsDataURL(this.files[0]);
    });

    $('#btnConfirmarExclusao').on('click', function () {
        confirmarExclusao();
    });
});

function novo() {
    const form = document.getElementById('formUsuario');
    form.reset();
    $('#usuario_id').val('');
    $('#usuario_nivel').trigger('change');
    $('#modalUsuarioLabel').text('Cadastrar Usuário');
    $('#usuarioSenhaHint').text('(mínimo 6 caracteres)');
    $('#usuario_senha').prop('required', true);
    $('#previewFotoUsuario').attr('src', '../uploads/perfil/sem_foto.png');
    bootstrap.Modal.getOrCreateInstance('#modalUsuario').show();
}

function visualizar(id) {
    fetch('scripts/usuarios/buscar.php?id=' + encodeURIComponent(id))
        .then(function (resposta) { return resposta.json(); })
        .then(function (dados) {
            if (!dados.ok) {
                Mensagens.erro('Erro', dados.msg);
                return;
            }

            const u = dados.data;
            const ativo = u.ativo == 1;

            $('#verFotoUsuario').attr('src', u.foto_url);
            $('#verNome').text(u.nome || '-');
            $('#verStatus').text(ativo ? 'Ativo' : 'Inativo')
                .removeClass('bg-success bg-danger')
                .addClass(ativo ? 'bg-success' : 'bg-danger');
            $('#verEmail').text(u.email || '-');
            $('#verTelefone').text(u.telefone || '-');
            $('#verCpf').text(u.cpf || '-');
            $('#verNivel').text(u.nivel || '-');
            $('#verCep').text(u.cep || '-');
            $('#verEstado').text(u.estado || '-');
            $('#verCidade').text(u.cidade || '-');
            $('#verEndereco').text(u.endereco || '-');
            $('#verNumero').text(u.numero || '-');
            $('#verBairro').text(u.bairro || '-');
            $('#verComplemento').text(u.complemento || '-');

            bootstrap.Modal.getOrCreateInstance('#modalVisualizarUsuario').show();
        })
        .catch(function () {
            Mensagens.erro('Erro', 'Não foi possível carregar os dados do usuário.');
        });
}

function editar(id) {
    fetch('scripts/usuarios/buscar.php?id=' + encodeURIComponent(id))
        .then(function (resposta) { return resposta.json(); })
        .then(function (dados) {
            if (!dados.ok) {
                Mensagens.erro('Erro', dados.msg);
                return;
            }

            const u = dados.data;

            $('#usuario_id').val(u.id);
            $('#usuario_nome').val(u.nome || '');
            $('#usuario_email').val(u.email || '');
            $('#usuario_ddi').val(u.ddi || '55');
            $('#usuario_telefone').val(u.telefone || '');
            $('#usuario_cpf').val(u.cpf || '');
            $('#usuario_cep').val(u.cep || '');
            $('#usuario_estado').val(u.estado || '');
            $('#usuario_cidade').val(u.cidade || '');
            $('#usuario_bairro').val(u.bairro || '');
            $('#usuario_endereco').val(u.endereco || '');
            $('#usuario_numero').val(u.numero || '');
            $('#usuario_complemento').val(u.complemento || '');
            // cargo_id é a FK de verdade agora — se o usuário for um registro antigo sem
            // cargo vinculado (NULL), o select simplesmente abre vazio, exigindo escolher
            // um cargo real da lista antes de salvar (o campo é required).
            $('#usuario_nivel').val(u.cargo_id || '').trigger('change');
            $('#usuario_ativo').val(String(u.ativo));

            $('#modalUsuarioLabel').text('Editar Usuário');
            $('#usuarioSenhaHint').text('(deixe em branco para não alterar)');
            $('#usuario_senha').prop('required', false).val('');

            $('#previewFotoUsuario').attr('src', u.foto_url);

            bootstrap.Modal.getOrCreateInstance('#modalUsuario').show();
        })
        .catch(function () {
            Mensagens.erro('Erro', 'Não foi possível carregar os dados do usuário.');
        });
}

/**
 * Marca ou desmarca todos os switches do modal de Permissões de uma vez — inclui os
 * que forem gerados dinamicamente em "Outros" (mesma classe .permissao-checkbox).
 */
function alternarTodasPermissoes(marcar) {
    document.querySelectorAll('#formPermissoes .permissao-checkbox').forEach(function (checkbox) {
        checkbox.checked = marcar;
    });
}

function permissoes(id) {
    fetch('scripts/usuarios/buscar.php?id=' + encodeURIComponent(id))
        .then(function (resposta) { return resposta.json(); })
        .then(function (dados) {
            if (!dados.ok) {
                Mensagens.erro('Erro', dados.msg);
                return;
            }

            const u = dados.data;

            if (u.acesso_total) {
                Mensagens.erro('Acesso total', 'Este usuário já tem acesso total pelo cargo — não há o que configurar aqui.');
                return;
            }

            $('#permissoes_usuario_id').val(u.id);
            $('#permissoesNomeUsuario').text(u.nome || '');

            // Permissões de AÇÃO (globais, colunas em usuarios — não fazem parte do
            // checklist de menus, por isso não usam a classe .permissao-checkbox)
            $('#permissao_criar').prop('checked', !!Number(u.permissao_criar));
            $('#permissao_editar').prop('checked', !!Number(u.permissao_editar));
            $('#permissao_excluir').prop('checked', !!Number(u.permissao_excluir));

            const checkboxesConhecidos = document.querySelectorAll('#formPermissoes .permissao-checkbox');
            const valoresConhecidos = Array.from(checkboxesConhecidos).map(function (c) { return c.value; });
            checkboxesConhecidos.forEach(function (checkbox) {
                checkbox.checked = u.permissoes.indexOf(checkbox.value) !== -1;
            });

            // "Outros": permissões que o usuário já tem gravadas mas que não batem com
            // nenhuma página do catálogo atual (ex.: página removida depois de liberada).
            // Mostramos em vez de esconder, pra dar a chance de desativar/limpar.
            const orfas = u.permissoes.filter(function (menu) { return valoresConhecidos.indexOf(menu) === -1; });
            const listaOutros = document.getElementById('permissoesOutrosLista');
            listaOutros.innerHTML = '';
            orfas.forEach(function (menu) {
                const linha = document.createElement('div');
                linha.className = 'hd-permissao-item';

                const label = document.createElement('span');
                label.className = 'hd-permissao-item__label';
                label.textContent = menu; // não tem label no catálogo — mostra o valor bruto mesmo

                const wrapSwitch = document.createElement('div');
                wrapSwitch.className = 'form-check form-switch mb-0';
                const input = document.createElement('input');
                input.type = 'checkbox';
                input.role = 'switch';
                input.className = 'form-check-input permissao-checkbox';
                input.name = 'permissoes[]';
                input.value = menu;
                input.checked = true; // ele já tem essa permissão — o admin decide se mantém ou desliga
                wrapSwitch.appendChild(input);

                linha.append(label, wrapSwitch);
                listaOutros.appendChild(linha);
            });
            document.getElementById('permissoesOutrosWrapper').style.display = orfas.length > 0 ? '' : 'none';

            bootstrap.Modal.getOrCreateInstance('#modalPermissoes').show();
        })
        .catch(function () {
            Mensagens.erro('Erro', 'Não foi possível carregar as permissões do usuário.');
        });
}

function salvarPermissoes(form) {
    const botao = document.getElementById('btnSalvarPermissoes');
    const textoOriginal = botao.textContent;
    botao.disabled = true;
    botao.textContent = 'Salvando...';

    fetch('scripts/usuarios/salvar_permissoes.php', {
        method: 'POST',
        body: new FormData(form)
    })
        .then(function (resposta) { return resposta.json(); })
        .then(function (dados) {
            if (dados.ok) {
                bootstrap.Modal.getOrCreateInstance('#modalPermissoes').hide();
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

    fetch('scripts/usuarios/excluir.php', {
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
    const telefone = $('#usuario_telefone').val().trim();
    const ddi = $('#usuario_ddi').val();
    if (telefone !== '' && ddi === '') {
        Mensagens.aviso('Selecione o DDI', 'Você preencheu um telefone, mas não escolheu o DDI (código do país) — sem isso a mensagem de boas-vindas por WhatsApp não sai pro número certo.');
        $('#usuario_ddi').focus();
        return;
    }

    const botao = document.getElementById('btnSalvarUsuario');
    const textoOriginal = botao.textContent;
    botao.disabled = true;
    botao.textContent = 'Salvando...';

    fetch('scripts/usuarios/salvar.php', {
        method: 'POST',
        body: new FormData(form)
    })
        .then(function (resposta) { return resposta.json(); })
        .then(function (dados) {
            if (dados.ok) {
                bootstrap.Modal.getOrCreateInstance('#modalUsuario').hide();
                tabela.ajax.reload(null, false);

                let detalhes = '';
                let falhouAlgo = false;

                if (dados.whatsapp_enviado === true) {
                    detalhes += ' Mensagem de boas-vindas enviada por WhatsApp.';
                } else if (dados.whatsapp_enviado === false) {
                    detalhes += ' Não foi possível enviar o WhatsApp: ' + (dados.whatsapp_erro || 'motivo desconhecido') + '.';
                    falhouAlgo = true;
                }

                if (dados.email_enviado === true) {
                    detalhes += ' E-mail de boas-vindas enviado.';
                } else if (dados.email_enviado === false) {
                    detalhes += ' Não foi possível enviar o e-mail de boas-vindas.';
                    falhouAlgo = true;
                }

                if (falhouAlgo) {
                    Mensagens.aviso('Usuário salvo, mas...', dados.msg + detalhes);
                } else {
                    Mensagens.sucesso('Sucesso!', dados.msg + detalhes);
                }
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
