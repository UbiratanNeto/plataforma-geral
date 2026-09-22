/**
 * Configuração e inicialização do Flatpickr no sistema
 * Depende de: Flatpickr (e opcionalmente flatpickr/dist/l10n/pt.js)
 */

document.addEventListener('DOMContentLoaded', function () {
  if (typeof flatpickr === 'undefined') return;

  const defaultConfig = {
    locale: 'pt',
    dateFormat: 'd/m/Y',
    allowInput: true,
    disableMobile: false,
  };

  // Inicializa todos os elementos com classe .flatpickr
  document.querySelectorAll('.flatpickr').forEach(function (el) {
    const opts = { ...defaultConfig };
    if (el.dataset.mode === 'datetime') {
      opts.enableTime = true;
      opts.time_24hr = true;
      opts.dateFormat = 'd/m/Y H:i';
    }
    if (el.dataset.mindate) opts.minDate = el.dataset.mindate;
    if (el.dataset.maxdate) opts.maxDate = el.dataset.maxdate;
    flatpickr(el, opts);
  });

  // Inicializa elementos com data-flatpickr (atributo vazio = opções padrão)
  document.querySelectorAll('[data-flatpickr]').forEach(function (el) {
    if (el._flatpickr) return;
    const opts = { ...defaultConfig };
    try {
      if (el.dataset.flatpickr && el.dataset.flatpickr !== '') {
        Object.assign(opts, JSON.parse(el.dataset.flatpickr));
      }
    } catch (e) {}
    if (el.dataset.mode === 'datetime') {
      opts.enableTime = true;
      opts.time_24hr = true;
      opts.dateFormat = 'd/m/Y H:i';
    }
    if (el.dataset.mindate) opts.minDate = el.dataset.mindate;
    if (el.dataset.maxdate) opts.maxDate = el.dataset.maxdate;
    flatpickr(el, opts);
  });
});
