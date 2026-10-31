document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', event => {
      const required = form.querySelectorAll('[required]');
      for (const field of required) {
        if (!String(field.value || '').trim()) {
          field.focus(); event.preventDefault(); alert('Please complete all required fields.'); return;
        }
      }
    });
  });
});
