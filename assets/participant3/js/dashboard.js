document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', event => {
      if (!window.confirm(el.getAttribute('data-confirm') || 'Continue?')) event.preventDefault();
    });
  });
});
