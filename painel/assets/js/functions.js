/**
 * painel/assets/js/functions.js
 * Funções utilitárias e integração com ViaCEP
 */
document.addEventListener('DOMContentLoaded', () => {
    // Cada grupo é um formulário diferente que tem os mesmos 6 campos de endereço.
    // Pra adicionar autofill de CEP em um formulário novo, basta acrescentar um grupo aqui.
    const grupos = [
        { cep: 'perfil_cep', endereco: 'perfil_endereco', bairro: 'perfil_bairro', cidade: 'perfil_cidade', estado: 'perfil_estado', numero: 'perfil_numero' },
        { cep: 'usuario_cep', endereco: 'usuario_endereco', bairro: 'usuario_bairro', cidade: 'usuario_cidade', estado: 'usuario_estado', numero: 'usuario_numero' },
        { cep: 'cliente_cep', endereco: 'cliente_endereco', bairro: 'cliente_bairro', cidade: 'cliente_cidade', estado: 'cliente_estado', numero: 'cliente_numero' },
    ];

    grupos.forEach(function (campos) {
        const inputCep = document.getElementById(campos.cep);
        if (!inputCep) return;

        inputCep.addEventListener('blur', function() {
            let cep = this.value.replace(/\D/g, '');
            if (cep.length !== 8) return;

            fetch(`https://viacep.com.br/ws/${cep}/json/`)
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    if (!data.erro) {
                        atribuirValor(campos.endereco, data.logradouro);
                        atribuirValor(campos.bairro, data.bairro);
                        atribuirValor(campos.cidade, data.localidade);
                        atribuirValor(campos.estado, data.uf);

                        let inputNumero = document.getElementById(campos.numero);
                        if (inputNumero) inputNumero.focus();
                    } else {
                        alert('CEP não encontrado.');
                    }
                })
                .catch(function (error) { console.error('Erro ao buscar o CEP:', error); });
        });
    });
});

function atribuirValor(id, valor) {
    const campo = document.getElementById(id);
    if (campo) {
        campo.value = valor;
    }
}

/**
 * Preenche um <select> com opções vindas de um endpoint no mesmo formato usado por toda
 * listagem do painel: { ok: true, data: [{ id, nome }, ...] }. Genérica de propósito — não
 * fala de nenhuma entidade específica, então qualquer página pode chamar isso pra popular um
 * select a partir de uma tabela de apoio (Cargos, Setores, Categorias, etc.), sem duplicar
 * essa lógica em cada arquivo *.js de página.
 *
 * @param {string} url           endpoint que devolve {ok, data: [{id, nome}]}
 * @param {string} seletorSelect seletor CSS do <select> a preencher (ex.: '#usuario_nivel')
 * @param {string} [mensagemVazia] texto mostrado quando não há registros
 * @returns {Promise} resolve quando o select já está preenchido
 */
function carregarOpcoesEmSelect(url, seletorSelect, mensagemVazia) {
    mensagemVazia = mensagemVazia || 'Nenhum registro cadastrado';

    return fetch(url)
        .then(function (resposta) { return resposta.json(); })
        .then(function (dados) {
            const select = document.querySelector(seletorSelect);
            if (!select) return;

            // Option vazia sempre na frente: necessária pro placeholder do Select2 (quando
            // usado) e evita que o navegador pré-selecione a primeira opção sem querer.
            select.innerHTML = '';
            select.appendChild(new Option('', ''));

            if (dados.ok && dados.data.length > 0) {
                dados.data.forEach(function (item) {
                    // new Option(texto, valor) — seguro contra HTML no dado (não usa innerHTML)
                    select.appendChild(new Option(item.nome, item.id));
                });
            } else {
                const optionVazia = new Option(mensagemVazia, '');
                optionVazia.disabled = true;
                select.appendChild(optionVazia);
            }

            // Se o Select2 estiver ativo nesse elemento, avisa ele que as opções mudaram
            // (o plugin não percebe sozinho mudanças feitas via DOM puro).
            if (typeof jQuery !== 'undefined') {
                jQuery(select).trigger('change');
            }
        })
        .catch(function (erro) {
            console.error('Erro ao carregar opções (' + seletorSelect + '):', erro);
            if (typeof Mensagens !== 'undefined') {
                Mensagens.erro('Erro', 'Não foi possível carregar as opções deste campo.');
            }
        });
}