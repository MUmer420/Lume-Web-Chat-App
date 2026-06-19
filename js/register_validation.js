// js/register_validation.js
console.log("Registration validation engine active.");

document.getElementById('register-form').addEventListener('submit', function(e) {
    let isValid = true;

    // Helper functions for unified visual feedback
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
    const emailInput    = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const languageInput = document.getElementById('language');

    const usernameErr = document.getElementById('username-error');
    const emailErr    = document.getElementById('email-error');
    const passwordErr = document.getElementById('password-error');
    const languageErr = document.getElementById('language-error');

// 1. Validate Username
const username = usernameInput.value.trim();
if (username === "") {
    invalidateField(usernameInput, usernameErr, "Username field cannot be left blank.");
} else if (username.length < 3) {
    invalidateField(usernameInput, usernameErr, "Username must be at least 3 characters long.");
} else if (!/^[a-zA-Z0-9_ ]+$/.test(username)) { // <-- ADDED A SPACE HERE right after the underscore
    invalidateField(usernameInput, usernameErr, "Username can only contain alphanumeric characters, underscores, and spaces.");
} else {
    validateField(usernameInput, usernameErr);
}
    // 2. Validate Email
    const email = emailInput.value.trim();
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (email === "") {
        invalidateField(emailInput, emailErr, "Email field cannot be left blank.");
    } else if (!emailRegex.test(email)) {
        invalidateField(emailInput, emailErr, "Please provide a valid formatting style email address.");
    } else {
        validateField(emailInput, emailErr);
    }

    // 3. Validate Password
    const password = passwordInput.value;
    const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/;
    if (password === "") {
        invalidateField(passwordInput, passwordErr, "Password field cannot be left blank.");
    } else if (password.length < 6) {
        invalidateField(passwordInput, passwordErr, "Password security requires a minimum length of 6 characters.");
    } else if (!passwordRegex.test(password)) {
        invalidateField(passwordInput, passwordErr, "Password must contain at least one uppercase letter, one lowercase letter, and one number.");
    } else {
        validateField(passwordInput, passwordErr);
    }

    // 4. Validate Language
    if (languageInput.value === "") {
        invalidateField(languageInput, languageErr, "Please select a preferred native translation language.");
    } else {
        validateField(languageInput, languageErr);
    }

    if (!isValid) {
        e.preventDefault();
        console.log("Form submission rejected: Client-side validation failed.");
    }
});