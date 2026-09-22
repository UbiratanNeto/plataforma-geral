/**
 * Login: salvar acesso (localStorage), preenchimento automático em modo teste
 * e disparo assíncrono para recuperação de senha.
 */
(function () {
  const STORAGE_KEY = 'helpdesk_login_salvo';

  function lerSalvo() {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      if (!raw) return null;
      const dados = JSON.parse(raw);
      if (!dados || !dados.email) return null;
      return dados;
    } catch (e) {
      return null;
    }
  }

  function salvarAcesso(email, senha) {
    localStorage.setItem(STORAGE_KEY, JSON.stringify({ email, senha }));
  }

  function limparSalvo() {
    localStorage.removeItem(STORAGE_KEY);
  }

  // Centraliza todas as manipulações do DOM em um único evento de carregamento
  document.addEventListener('DOMContentLoaded', function () {

    // === ELEMENTOS DO LOGIN ===
    const formLogin = document.querySelector('.login-form');
    const usernameEl = document.getElementById('username');
    const passwordEl = document.getElementById('password');
    const rememberEl = document.getElementById('remember');

    // === ELEMENTOS DA RECUPERAÇÃO ===
    const modalRecuperar = document.getElementById('modalRecuperarSenha');
    const formRecuperar = document.getElementById('formRecuperarSenha');
    const inputEmail = document.getElementById('emailRecuperar');
    const btnEnviar = document.getElementById('btnEnviarRecuperacao');

    // Lógica de Autopreenchimento / Modo Teste
    if (formLogin && usernameEl && passwordEl && rememberEl) {
      const salvo = lerSalvo();

      if (salvo) {
        usernameEl.value = salvo.email;
        passwordEl.value = salvo.senha || '';
        rememberEl.checked = true;
      } else if (window.LOGIN_MODO_TESTE === 'Sim') {
        usernameEl.value = window.LOGIN_USUARIO_TESTE || '';
        passwordEl.value = window.LOGIN_SENHA_TESTE || '';
      }

      formLogin.addEventListener('submit', function () {
        if (rememberEl.checked) {
          salvarAcesso(usernameEl.value.trim(), passwordEl.value);
        } else {
          limparSalvo();
        }
      });
    }

    // Comportamentos visuais da Modal Bootstrap
    if (modalRecuperar && inputEmail) {
      modalRecuperar.addEventListener('shown.bs.modal', function () {
        inputEmail.focus();
      });

      modalRecuperar.addEventListener('hidden.bs.modal', function () {
        if (formRecuperar) formRecuperar.reset();
      });
    }

    // Envio do formulário de recuperação via AJAX (Fetch API)
    if (btnEnviar && formRecuperar && inputEmail) {
      btnEnviar.addEventListener('click', function (e) {
        e.preventDefault();

        const emailValue = inputEmail.value.trim();

        if (emailValue === '') {
          Swal.fire({
            icon: 'warning',
            title: 'Atenção',
            text: 'Por favor, preencha o campo de e-mail.',
            confirmButtonColor: 'var(--helpdesk-primary)'
          });
          return;
        }

        // Previne múltiplos cliques desativando o botão temporariamente
        btnEnviar.disabled = true;
        const textoOriginal = btnEnviar.innerHTML;
        btnEnviar.innerHTML = 'Enviando...';

        const formData = new FormData();
        formData.append('email', emailValue);

        // Rota corrigida apontando para a pasta scripts/
        fetch('scripts/recuperar-senha.php', {
          method: 'POST',
          body: formData
        })
        .then(response => {
          if (!response.ok) {
            throw new Error('Erro na resposta do servidor');
          }
          return response.json();
        })
        .then(data => {
          if (data.success) {
            Swal.fire({
              icon: 'success',
              title: 'Sucesso!',
              text: data.message,
              confirmButtonColor: 'var(--helpdesk-primary)'
            }).then(() => {
              formRecuperar.reset();
              if (modalRecuperar) {
                const modalInstance = bootstrap.Modal.getInstance(modalRecuperar);
                if (modalInstance) {
                  modalInstance.hide();
                }
              }
            });
          } else {
            Swal.fire({
              icon: 'error',
              title: 'Erro',
              text: data.message,
              confirmButtonColor: 'var(--helpdesk-primary)'
            });
          }
        })
        .catch(error => {
          console.error('Erro:', error);
          Swal.fire({
            icon: 'error',
            title: 'Erro no Sistema',
            text: 'Não foi possível processar a requisição no momento.',
            confirmButtonColor: 'var(--helpdesk-primary)'
          });
        })
        .finally(() => {
          // Devolve o estado original do botão
          btnEnviar.disabled = false;
          btnEnviar.innerHTML = textoOriginal;
        });
      });
    }
  });
})();
