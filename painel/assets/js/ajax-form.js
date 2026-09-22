/**
 * painel/assets/js/ajax-forms.js
 * Intercepta os formulários das modais, valida campos obrigatórios e envia via AJAX (Fetch API)
 */
document.addEventListener('DOMContentLoaded', () => {

    function configurarFormularioAjax(formSelector, modalSelector, callbackValidacao) {
        const form = document.querySelector(formSelector);
        if (!form) return;

        form.addEventListener('submit', function(e) {
            e.preventDefault(); // Impede o envio padrão

            // Executa validação personalizada antes de enviar, se houver
            if (callbackValidacao && !callbackValidacao(this)) {
                return; // Interrompe se a validação falhar
            }

            const formData = new FormData(this);
            const actionUrl = this.getAttribute('action');

            fetch(actionUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.ok) {
                    // Sucesso com fechamento automático em 1 segundo
                    Mensagens.sucesso('Sucesso!', data.msg, true).then(() => {
                        const modal = document.querySelector(modalSelector);
                        if (modal) modal.style.display = 'none';
                        location.reload();
                    });
                } else {
                    Mensagens.erro('Atenção', data.msg);
                }
            })
            .catch(error => {
                console.error('Erro na requisição:', error);
                Mensagens.erro('Erro', 'Ocorreu um erro ao tentar salvar. Verifique o console.');
            });
        });
    }

    // 1. Configurações: Valida se o "Nome do Sistema" é obrigatório
    configurarFormularioAjax(
        'form[action="scripts/salvar_config.php"]', 
        '#modalConfig', 
        (form) => {
            // Ajuste o seletor abaixo ('[name="nome_sistema"]') caso o atributo name do seu input seja diferente
            const inputNomeSistema = form.querySelector('[name="nome_sistema"]');
            
            if (inputNomeSistema && inputNomeSistema.value.trim() === '') {
                Mensagens.aviso('Campo obrigatório', 'O nome do sistema não pode ficar em branco.');
                inputNomeSistema.focus();
                return false; // Retorna falso para cancelar o envio
            }
            return true;
        }
    );

    // 2. Perfil: Valida se o "Nome Completo" é obrigatório
    configurarFormularioAjax(
        'form[action="scripts/salvar_perfil.php"]', 
        '#modalPerfil', 
        (form) => {
            // Ajuste o seletor abaixo ('[name="perfil_nome"]') caso o atributo name do seu input seja diferente
            const inputNomeCompleto = form.querySelector('[name="perfil_nome"]');
            
            if (inputNomeCompleto && inputNomeCompleto.value.trim() === '') {
                Mensagens.aviso('Campo obrigatório', 'O nome completo não pode ficar em branco.');
                inputNomeCompleto.focus();
                return false; // Retorna falso para cancelar o envio
            }
            return true;
        }
    );
});