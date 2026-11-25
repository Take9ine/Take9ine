<?php
require_once __DIR__ . '/admin_session.php';

if (!isHttpsRequest()) {
    http_response_code(403);
    exit('HTTPS is required to access the admin sign-in page.');
}

startAdminSession();

if (
    !empty($_SESSION['admin_id']) &&
    ($_SESSION['admin_role'] ?? null) === 'admin'
) {
    header('Location: admin_dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Sign In | T@ke9ine</title>
    <link rel="stylesheet" href="css/styling.css">
</head>
<body>
    <main class="auth-wrapper">
        <section class="auth-card" aria-labelledby="admin-title">
            <header class="auth-header">
                <h2 id="admin-title">T@ke9ine</h2>
                <p>Administrator sign in</p>
            </header>
            <div id="admin-alert" class="alert hidden" role="alert">
                <div class="alert-popup-brand" aria-label="T@ke9ine">
                    <svg viewBox="0 0 64 64" aria-hidden="true" focusable="false">
                        <path d="M12 54V24h40v30M8 54h48M20 24V13h24v11M24 32h5m6 0h5M24 41h5m6 0h5M28 54V45h8v9" />
                    </svg>
                    <span>T@ke9ine</span>
                </div>
                <span class="alert-message"></span>
            </div>
            <form id="admin-login-form" class="auth-form">
                <div class="form-group">
                    <label for="admin-username">Username</label>
                    <input type="text" id="admin-username" name="username" autocomplete="username" minlength="3" maxlength="50" pattern="[A-Za-z0-9_-]+" required>
                </div>
                <div class="form-group">
                    <label for="admin-password">Password</label>
                    <input type="password" id="admin-password" name="password" autocomplete="current-password" required>
                </div>
                <button type="submit" class="btn-primary">Sign In</button>
            </form>
        </section>
    </main>
    <script>
        const form = document.getElementById('admin-login-form');
        const alertBox = document.getElementById('admin-alert');

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const button = form.querySelector('button[type="submit"]');
            button.disabled = true;
            alertBox.className = 'alert hidden';

            try {
                const response = await fetch('admin_auth.php', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' },
                    body: new FormData(form),
                    credentials: 'same-origin'
                });
                const result = await response.json();

                if (!response.ok || result.status !== 'success') {
                    alertBox.querySelector('.alert-message').textContent = result.message || 'Sign-in failed.';
                    alertBox.className = 'alert error';
                    return;
                }

                window.location.assign(result.redirect);
            } catch {
                alertBox.querySelector('.alert-message').textContent = 'Unable to sign in. Please try again.';
                alertBox.className = 'alert error';
            } finally {
                button.disabled = false;
            }
        });
    </script>
</body>
</html>
