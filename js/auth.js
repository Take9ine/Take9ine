document.addEventListener('DOMContentLoaded', () => {
    const loginForm    = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');
    const forgotPasswordForm = document.getElementById('forgot-password-form');
    const resetPasswordForm = document.getElementById('reset-password-form');
    const resetCodeInput = document.getElementById('reset-code');
    const resetEmailInput = document.getElementById('reset-email');
    const resetChallengeInput = document.getElementById('reset-challenge-id');
    const resetPasswordInput = document.getElementById('reset-password');
    const resetPasswordConfirmInput = document.getElementById('reset-password-confirm');
    const alertBox     = document.getElementById('alert-box');
    const subtitle     = document.getElementById('auth-subtitle');
    const loginTransition = document.getElementById('login-transition');

    function showAlert(message, type) {
        const messageElement = alertBox.querySelector('.alert-message');
        messageElement.textContent = message;
        alertBox.className = `alert ${type}`;
    }

    function clearAlert() {
        alertBox.className = 'alert hidden';
    }

    function clearResetForm() {
        resetCodeInput.value = '';
        resetPasswordInput.value = '';
        resetPasswordConfirmInput.value = '';
        resetChallengeInput.value = '';
        resetEmailInput.value = '';
    }

    function switchView(view) {
        clearAlert();
        loginForm.classList.add('hidden');
        registerForm.classList.add('hidden');
        forgotPasswordForm.classList.add('hidden');
        resetPasswordForm.classList.add('hidden');

        if (view === 'login') {
            loginForm.classList.remove('hidden');
            subtitle.textContent = 'Sign in to manage your reservations';
        } else if (view === 'register') {
            registerForm.classList.remove('hidden');
            subtitle.textContent = 'Create a guest account';
        } else if (view === 'forgot') {
            forgotPasswordForm.classList.remove('hidden');
            subtitle.textContent = 'Request a password reset code';
        } else if (view === 'reset') {
            resetPasswordForm.classList.remove('hidden');
            subtitle.textContent = 'Enter your email code and choose a new password';
        }
    }

    // Navigation toggles
    document.getElementById('show-forgot-password').addEventListener('click', (e) => {
        e.preventDefault();
        switchView('forgot');
    });

    document.getElementById('forgot-back-to-login').addEventListener('click', (e) => {
        e.preventDefault();
        switchView('login');
    });

    document.getElementById('reset-back-to-login').addEventListener('click', (e) => {
        e.preventDefault();
        clearResetForm();
        switchView('login');
    });

    document.getElementById('reset-request-another').addEventListener('click', (e) => {
        e.preventDefault();
        document.getElementById('forgot-email').value = resetEmailInput.value;
        clearResetForm();
        switchView('forgot');
    });

    document.getElementById('show-register').addEventListener('click', (e) => {
        e.preventDefault();
        switchView('register');
    });

    document.getElementById('show-login').addEventListener('click', (e) => {
        e.preventDefault();
        switchView('login');
    });

    // Helper for sending form data to PHP
    async function postAuthData(formData) {
        const response = await fetch('backend.php', {
            method: 'POST',
            headers: { 'Accept': 'application/json' },
            body: formData
        });
        const result = await response.json();
        if (!result || typeof result.status !== 'string') {
            throw new Error('Invalid authentication response.');
        }
        return result;
    }

    resetCodeInput.addEventListener('input', () => {
        resetCodeInput.value = resetCodeInput.value.replace(/\D/g, '').slice(0, 6);
    });

    // 1. Handle Registration
    registerForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const submitButton = registerForm.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        const formData = new FormData(registerForm);
        formData.append('action', 'register');

        try {
            const res = await postAuthData(formData);
            if (res.status === 'success') {
                registerForm.reset();
                switchView('login');
                showAlert(res.message, 'success');
            } else {
                showAlert(res.message, 'error');
            }
        } catch {
            showAlert('Network error. Please try again.', 'error');
        } finally {
            submitButton.disabled = false;
        }
    });

    // 2. Request a password reset code
    forgotPasswordForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const submitButton = forgotPasswordForm.querySelector('button[type="submit"]');
        const buttonLabel = submitButton.textContent;
        submitButton.disabled = true;
        submitButton.textContent = 'Sending...';
        const formData = new FormData(forgotPasswordForm);
        formData.append('action', 'request_password_reset');

        try {
            const res = await postAuthData(formData);
            if (
                res.status === 'accepted' &&
                typeof res.challenge_id === 'string' &&
                /^[a-f0-9]{64}$/.test(res.challenge_id)
            ) {
                resetEmailInput.value = String(formData.get('email') || '');
                resetChallengeInput.value = res.challenge_id;
                switchView('reset');
                showAlert(res.message, 'success');
            } else {
                showAlert(res.message || 'Password reset request failed.', 'error');
            }
        } catch {
            showAlert('Network error. Please try again.', 'error');
        } finally {
            submitButton.disabled = false;
            submitButton.textContent = buttonLabel;
        }
    });

    // 3. Complete a password reset
    resetPasswordForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const password = resetPasswordInput.value;
        const passwordConfirmation = resetPasswordConfirmInput.value;
        if (password !== passwordConfirmation) {
            showAlert('The passwords do not match.', 'error');
            return;
        }

        const passwordBytes = new Blob([password]).size;
        if (passwordBytes < 6 || passwordBytes > 72) {
            showAlert('Use a password between 6 and 72 UTF-8 bytes.', 'error');
            return;
        }

        const submitButton = resetPasswordForm.querySelector('button[type="submit"]');
        const buttonLabel = submitButton.textContent;
        submitButton.disabled = true;
        submitButton.textContent = 'Updating...';
        const formData = new FormData(resetPasswordForm);
        formData.set('reset_code', resetCodeInput.value.trim());
        formData.append('action', 'reset_password');

        try {
            const res = await postAuthData(formData);
            if (res.status === 'success') {
                resetPasswordForm.reset();
                switchView('login');
                showAlert(res.message, 'success');
            } else {
                showAlert(res.message || 'The password could not be reset.', 'error');
            }
        } catch {
            showAlert('Network error. Please try again.', 'error');
        } finally {
            submitButton.disabled = false;
            submitButton.textContent = buttonLabel;
        }
    });

    // 4. Handle Login
    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const submitButton = loginForm.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        let loginSucceeded = false;
        const formData = new FormData(loginForm);
        formData.append('action', 'login');

        try {
            const res = await postAuthData(formData);
            if (res.status === 'success') {
                loginSucceeded = true;
                loginTransition.classList.add('is-active');
                loginTransition.setAttribute('aria-hidden', 'false');
                setTimeout(() => {
                    window.location.assign(res.redirect);
                }, 350);
            } else {
                showAlert(res.message, 'error');
            }
        } catch {
            showAlert('Network error. Please try again.', 'error');
        } finally {
            if (!loginSucceeded) {
                submitButton.disabled = false;
            }
        }
    });
});