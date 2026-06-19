
document.getElementById('login-form').addEventListener('submit', function(e) {
    let isValid = true;

    function invalidateField(inputEl, errorEl, msg) {
        inputEl.classList.remove('valid-state');
        inputEl.classList.add('invalid-state');
        errorEl.textContent = msg;
        errorEl.classList.add('visible');
        isValid = false;
    }

    function validateField(inputEl, errorEl) {
        inputEl.classList.remove('invalid-state');
        inputEl.classList.add('valid-state');
        errorEl.textContent = "";
        errorEl.classList.remove('visible');
    }

    const emailInput = document.getElementById('login-email');
    const passwordInput = document.getElementById('login-password');
    const emailErr = document.getElementById('login-email-error');
    const passwordErr = document.getElementById('login-password-error');

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (!emailRegex.test(emailInput.value.trim())) {
        invalidateField(emailInput, emailErr, "Please enter a valid email address.");
    } else {
        validateField(emailInput, emailErr);
    }

    if (passwordInput.value.length === 0) {
        invalidateField(passwordInput, passwordErr, "Password cannot be left blank.");
    } else {
        validateField(passwordInput, passwordErr);
    }

    if (!isValid) {
        e.preventDefault();
    }
});