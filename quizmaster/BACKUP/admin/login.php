<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

if (admin_is_logged_in()) {
    admin_redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        admin_set_flash('Escribe usuario y contrasena.', 'error');
        admin_redirect('login.php');
    }

    if (!admin_attempt_login($username, $password)) {
        admin_set_flash('Credenciales invalidas.', 'error');
        admin_redirect('login.php');
    }

    admin_set_flash('Bienvenido al panel.');
    admin_redirect('index.php');
}

admin_render_header('Iniciar sesion');
?>
<div class="card" style="width:min(420px,100%);padding:28px;">
    <div style="font-size:32px;font-weight:800;margin-bottom:8px;color:#0f4ea1;">QuizMaster Admin</div>
    <div class="muted" style="margin-bottom:18px;">Usa el usuario admin de la tabla <code>authenticate</code>.</div>
    <form method="post" class="grid" style="gap:14px;">
        <div class="field">
            <label for="username">Usuario</label>
            <input id="username" name="username" type="text" placeholder="admin" required>
        </div>
        <div class="field">
            <label for="password">Contrasena</label>
            <input id="password" name="password" type="password" placeholder="Tu contrasena" required>
        </div>
        <button type="submit">Entrar</button>
    </form>
</div>
<?php admin_render_footer(); ?>
