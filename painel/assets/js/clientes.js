/**
 * painel/assets/js/clientes.js
 * Gestão de Clientes: lista via DataTables (Bootstrap 5 + jQuery), cria/edita/exclui via fetch.
 *
 * Mesmas funções globais genéricas de usuarios.js/cargos.js (novo/editar/excluir/salvar/visualizar) —
 * só carregamos este arquivo na página de Clientes, então não há colisão com as outras páginas.
 * Diferença importante: "ativo" aqui é texto ("Sim"/"Não"), não 1/0 como em usuarios/cargos —
 * é assim que a tabela clientes foi definida.
 */

let tabela;
let idParaExcluir = null;

$(function () {
    const $tabela = $('#tabelaClientes');
    if ($tabela.length === 0) return; // Este script só faz sentido na página de Clientes

    // "Exportar" — o próprio navegador já baixa (Content-Disposition: attachment no PHP),
    // não precisa de fetch/blob feito à mão como no Relatório/Importar.
    $('#btnExportarClientes').on('click', function () {
        window.open('scripts/clientes/exportar.php', '_blank');
    });

    // "Importar" — clique no botão só abre o seletor de arquivo escondido.
    $('#btnImportarClientes').on('click', function () {
        $('#inputImportarClientes').trigger('click');
    });

    $('#inputImportarClientes').on('change', function () {
        const $input = $(this);
        const arquivo = this.files[0];
        if (!arquivo) return;

        const botao = document.getElementById('btnImportarClientes');
        const textoOriginal = botao.innerHTML;
        botao.disabled = true;
        botao.innerHTML = '<span class="spinner-border spinner-border-sm" style="margin-right:0.4rem;"></span>Importando...';

        const formData = new FormData();
        formData.append('arquivo', arquivo);

        fetch('scripts/clientes/importar.php', { method: 'POST', body: formData })
            .then(function (resposta) { return resposta.json(); })
            .then(function (dados) {
                if (!dados.ok) {
                    Mensagens.erro('Erro', dados.msg);
                    return;
                }
                tabela.ajax.reload(null, false);
                if (dados.ignorados > 0) {
                    Mensagens.aviso('Importação concluída', dados.msg + (dados.erros.length ? '\n\n' + dados.erros.join('\n') : ''));
                } else {
                    Mensagens.sucesso('Sucesso!', dados.msg);
                }
            })
            .catch(function () {
                Mensagens.erro('Erro de conexão', 'Não foi possível importar agora. Tente novamente.');
            })
            .finally(function () {
                botao.disabled = false;
                botao.innerHTML = textoOriginal;
                $input.val(''); // permite reimportar o mesmo arquivo depois, se precisar
            });
    });

    // "Relatório" — mesmo padrão do logs.js: abre a aba já no clique (senão o navegador
    // bloqueia como pop-up depois do fetch responder) e só troca o conteúdo quando o PDF terminar.
    $('#btnRelatorioClientes').on('click', function () {
        const botao = this;
        const textoOriginal = botao.innerHTML;

        const novaAba = window.open('', '_blank');
        if (novaAba) {
            novaAba.document.write('<p style="font-family:sans-serif;padding:2rem;color:#64748b;">Gerando relatório...</p>');
        }

        botao.disabled = true;
        botao.innerHTML = '<span class="spinner-border spinner-border-sm" style="margin-right:0.4rem;"></span>Gerando...';

        fetch('scripts/clientes/relatorio.php')
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

    tabela = $tabela.DataTable({
        ajax: {
            url: 'scripts/clientes/listar.php',
            dataSrc: function (json) {
                if (!json.ok) {
                    Mensagens.erro('Erro', json.msg || 'Não foi possível carregar os clientes.');
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
            { data: 'email', render: function (v) { return v || '-'; } },
            { data: 'telefone', render: function (v) { return v || '-'; } },
            { data: 'tipo', render: function (v) { return v || '-'; } },
            {
                data: 'ativo', render: function (ativo) {
                    return ativo === 'Sim'
                        ? '<span class="badge bg-success">Sim</span>'
                        : '<span class="badge bg-danger">Não</span>';
                }
            },
            {
                data: 'id', orderable: false, className: 'text-end', render: function (id) {
                    // Permissões de AÇÃO do usuário LOGADO (globais — painel/includes/head.php)
                    const permissoesAcao = window.PERMISSOES_ACAO || { editar: true, excluir: true };

                    let html = '<div class="btn-group btn-group-sm">';
                    html += '<button type="button" class="btn btn-outline-primary btn-visualizar" data-id="' + id + '" title="Visualizar"><i class="fa-solid fa-eye"></i></button>';
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
    $tabela.on('click', '.btn-excluir', function () {
        excluir($(this).data('id'));
    });

    $('#formCliente').on('submit', function (e) {
        e.preventDefault();
        salvar(this);
    });

    $('#cliente_foto').on('change', function () {
        if (!this.files || !this.files[0]) return;
        const leitor = new FileReader();
        leitor.onload = function (e) {
            $('#previewFotoCliente').attr('src', e.target.result);
        };
        leitor.readAsDataURL(this.files[0]);
    });

    $('#btnConfirmarExclusao').on('click', function () {
        confirmarExclusao();
    });
});

function novo() {
    const form = document.getElementById('formCliente');
    form.reset();
    $('#cliente_id').val('');
    $('#modalClienteLabel').text('Cadastrar Cliente');
    $('#previewFotoCliente').attr('src', '../uploads/clientes/sem_foto.png');
    bootstrap.Modal.getOrCreateInstance('#modalCliente').show();
}

function visualizar(id) {
    fetch('scripts/clientes/buscar.php?id=' + encodeURIComponent(id))
        .then(function (resposta) { return resposta.json(); })
        .then(function (dados) {
            if (!dados.ok) {
                Mensagens.erro('Erro', dados.msg);
                return;
            }

            const c = dados.data;
            const ativo = c.ativo === 'Sim';

            $('#verFotoCliente').attr('src', c.foto_url);
            $('#verNome').text(c.nome || '-');
            $('#verStatus').text(ativo ? 'Ativo' : 'Inativo')
                .removeClass('bg-success bg-danger')
                .addClass(ativo ? 'bg-success' : 'bg-danger');
            $('#verEmail').text(c.email || '-');
            $('#verTelefone').text(c.telefone || '-');
            $('#verCpfCnpj').text(c.cpf_cnpj || '-');
            $('#verTipo').text(c.tipo || '-');
            $('#verCep').text(c.cep || '-');
            $('#verEstado').text(c.estado || '-');
            $('#verCidade').text(c.cidade || '-');
            $('#verEndereco').text(c.endereco || '-');
            $('#verNumero').text(c.numero || '-');
            $('#verBairro').text(c.bairro || '-');
            $('#verComplemento').text(c.complemento || '-');
            $('#verObservacoes').text(c.observacoes || '-');

            bootstrap.Modal.getOrCreateInstance('#modalVisualizarCliente').show();
        })
        .catch(function () {
            Mensagens.erro('Erro', 'Não foi possível carregar os dados do cliente.');
        });
}

function editar(id) {
    fetch('scripts/clientes/buscar.php?id=' + encodeURIComponent(id))
        .then(function (resposta) { return resposta.json(); })
        .then(function (dados) {
            if (!dados.ok) {
                Mensagens.erro('Erro', dados.msg);
                return;
            }

            const c = dados.data;

            $('#cliente_id').val(c.id);
            $('#cliente_nome').val(c.nome || '');
            $('#cliente_ddi').val(c.ddi || '55');
            $('#cliente_telefone').val(c.telefone || '');
            $('#cliente_cpf_cnpj').val(c.cpf_cnpj || '');
            $('#cliente_email').val(c.email || '');
            $('#cliente_tipo').val(c.tipo || 'Pessoa Física');
            $('#cliente_ativo').val(c.ativo || 'Sim');
            $('#cliente_notificar').val(c.notificar_cadastro || 'Sim');
            $('#cliente_cep').val(c.cep || '');
            $('#cliente_estado').val(c.estado || '');
            $('#cliente_cidade').val(c.cidade || '');
            $('#cliente_endereco').val(c.endereco || '');
            $('#cliente_numero').val(c.numero || '');
            $('#cliente_bairro').val(c.bairro || '');
            $('#cliente_complemento').val(c.complemento || '');
            $('#cliente_observacoes').val(c.observacoes || '');

            $('#modalClienteLabel').text('Editar Cliente');
            $('#previewFotoCliente').attr('src', c.foto_url);

            bootstrap.Modal.getOrCreateInstance('#modalCliente').show();
        })
        .catch(function () {
            Mensagens.erro('Erro', 'Não foi possível carregar os dados do cliente.');
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

    fetch('scripts/clientes/excluir.php', {
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
    const telefone = $('#cliente_telefone').val().trim();
    const ddi = $('#cliente_ddi').val();
    const notificar = $('#cliente_notificar').val();
    if (telefone !== '' && ddi === '' && notificar === 'Sim') {
        Mensagens.aviso('Selecione o DDI', 'Você preencheu um telefone, mas não escolheu o DDI (código do país) — sem isso a mensagem de boas-vindas por WhatsApp não sai pro número certo.');
        $('#cliente_ddi').focus();
        return;
    }

    const botao = document.getElementById('btnSalvarCliente');
    const textoOriginal = botao.textContent;
    botao.disabled = true;
    botao.textContent = 'Salvando...';

    fetch('scripts/clientes/salvar.php', {
        method: 'POST',
        body: new FormData(form)
    })
        .then(function (resposta) { return resposta.json(); })
        .then(function (dados) {
            if (dados.ok) {
                bootstrap.Modal.getOrCreateInstance('#modalCliente').hide();
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
                    Mensagens.aviso('Cliente salvo, mas...', dados.msg + detalhes);
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
