(function () {
  const eyeShow = 'assets/img/eye_show.svg';
  const eyeHide = 'assets/img/eye_hide.svg';

  document.querySelectorAll('.password_field').forEach((field) => {
    const passwordInput = field.querySelector('input');
    const toggleBtn = field.querySelector('.eye_icon');
    const eyeIcon = toggleBtn && toggleBtn.querySelector('img');
    if (!passwordInput || !toggleBtn || !eyeIcon) return;

    toggleBtn.addEventListener('click', () => {
      const isPassword = passwordInput.type === 'password';
      passwordInput.type = isPassword ? 'text' : 'password';
      eyeIcon.src = isPassword ? eyeShow : eyeHide;
      toggleBtn.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
    });
  });
})();
