// js/settings_validation.js
document.getElementById('settings-form').addEventListener('submit', function(e) {
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

    const usernameInput = document.getElementById('username');
    const usernameErr   = document.getElementById('username-error');
    const username      = usernameInput.value.trim();

    // Validate updated username parameters (permits internal space groups)
    if (username === "") {
        invalidateField(usernameInput, usernameErr, "Username cannot be left blank.");
    } else if (username.length < 3) {
        invalidateField(usernameInput, usernameErr, "Username must be at least 3 characters.");
    } else if (!/^[a-zA-Z0-9_ ]+$/.test(username)) {
        invalidateField(usernameInput, usernameErr, "Alphanumeric, spaces, and underscores only.");
    } else {
        validateField(usernameInput, usernameErr);
    }

    if (!isValid) {
        e.preventDefault();
    }
});