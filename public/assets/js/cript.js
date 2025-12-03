/**
 * Central UI Script
 * Small UI behaviors: search interactions and login/signup modals
 */
document.addEventListener('DOMContentLoaded', function() {
  // Search behavior: Enter key and icon click navigate to dashboard with query
  function goToSearch(q) {
    if (!q) return;
    // Navigate to the public browse page with query
    const base = 'browse.html';
    window.location.href = `${base}?search=${encodeURIComponent(q)}`;
  }

  document.querySelectorAll('.nav__search input').forEach(input => {
    input.addEventListener('keydown', e => {
      if (e.key === 'Enter') {
        const q = input.value.trim();
        if (!q) return;
        goToSearch(q);
      }
    });
  });

  document.querySelectorAll('.nav__search i, .nav__search .fa-search').forEach(icon => {
    icon.addEventListener('click', e => {
      const wrapper = icon.closest('.nav__search');
      if (!wrapper) return;
      const input = wrapper.querySelector('input');
      if (!input) return;
      const q = input.value.trim();
      if (!q) return;
      goToSearch(q);
    });
  });

  // ----- Login / Signup interactivity (merged provided script) -----
  const formContainer = document.getElementById('formContainer');
  const toLogin = document.getElementById('toLogin');
  const toSignup = document.getElementById('toSignup');

  if (formContainer) setTimeout(() => formContainer.classList.add('loaded'), 100);

  toLogin?.addEventListener('click', e => {
    e.preventDefault();
    formContainer?.classList.add('flip-to-login');
  });

  toSignup?.addEventListener('click', e => {
    e.preventDefault();
    formContainer?.classList.remove('flip-to-login');
  });

  // Toggle Password Visibility
  document.querySelectorAll('.toggle-password').forEach(icon => {
    icon.addEventListener('click', () => {
      const target = document.getElementById(icon.dataset.target);
      if (!target) return;
      if (target.type === 'password') {
        target.type = 'text';
        icon.textContent = '🙈';
      } else {
        target.type = 'password';
        icon.textContent = '👁️';
      }
    });
  });

  // Fake submit animation (can replace with real API)
  document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', e => {
      e.preventDefault();
      const btn = form.querySelector('.btn-primary');
      if (!btn) return;
      const originalText = btn.textContent;
      btn.textContent = 'Processing...';
      btn.disabled = true;
      setTimeout(() => {
        btn.textContent = 'Success!';
        btn.style.background = 'linear-gradient(135deg, #00ff88, #00cc66)';
        setTimeout(() => {
          btn.textContent = originalText || 'Continue';
          btn.disabled = false;
          form.reset();
        }, 1500);
      }, 1200);
    });
  });

});
