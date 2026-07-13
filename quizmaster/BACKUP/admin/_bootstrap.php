<?php

declare(strict_types=1);

session_start();

$config = require dirname(__DIR__) . '/config/app.php';

require dirname(__DIR__) . '/src/Database.php';

$db = new Database($config['db']);

function admin_db(): Database
{
    global $db;
    return $db;
}

function admin_config(): array
{
    global $config;
    return $config;
}

function admin_escape(string $value): string
{
    return admin_db()->escape(trim($value));
}

function admin_url(string $path): string
{
    return $path;
}

function admin_redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function admin_set_flash(string $message, string $type = 'success'): void
{
    $_SESSION['admin_flash'] = [
        'message' => $message,
        'type' => $type,
    ];
}

function admin_get_flash(): ?array
{
    $flash = $_SESSION['admin_flash'] ?? null;
    unset($_SESSION['admin_flash']);
    return $flash;
}

function admin_is_logged_in(): bool
{
    return isset($_SESSION['admin_user']);
}

function admin_require_login(): void
{
    if (!admin_is_logged_in()) {
        admin_set_flash('Inicia sesion para continuar.', 'error');
        admin_redirect('login.php');
    }
}

function admin_logout(): void
{
    unset($_SESSION['admin_user']);
}

function admin_attempt_login(string $username, string $password): bool
{
    $username = admin_escape($username);
    $passwordHash = md5($password);

    $user = admin_db()->fetchOne(
        "SELECT auth_username, role, status
         FROM authenticate
         WHERE auth_username='{$username}'
           AND auth_pass='{$passwordHash}'
         LIMIT 1"
    );

    if ($user === null || (int) ($user['status'] ?? 0) !== 1) {
        return false;
    }

    $_SESSION['admin_user'] = [
        'username' => $user['auth_username'],
        'role' => $user['role'] ?? 'admin',
    ];

    return true;
}

function admin_user(): array
{
    return $_SESSION['admin_user'] ?? [];
}

function admin_h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function admin_render_header(string $title, string $active = 'dashboard'): void
{
    $flash = admin_get_flash();
    $user = admin_user();
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= admin_h($title) ?> | QuizMaster Admin</title>
    <style>
        :root {
            --bg: #f4f7fb;
            --surface: #ffffff;
            --text: #183153;
            --muted: #64748b;
            --line: #d8e1ee;
            --primary: #1d74d8;
            --primary-dark: #1557a2;
            --danger: #d9485f;
            --success: #17946b;
            --warning: #f59e0b;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, sans-serif;
            background: var(--bg);
            color: var(--text);
        }
        a { color: inherit; text-decoration: none; }
        .shell {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 240px 1fr;
        }
        .sidebar {
            background: linear-gradient(180deg, #0f4ea1, #1d74d8);
            color: #fff;
            padding: 24px 18px;
        }
        .brand {
            font-size: 24px;
            font-weight: 800;
            margin-bottom: 28px;
        }
        .nav a {
            display: block;
            padding: 12px 14px;
            border-radius: 12px;
            margin-bottom: 8px;
            background: rgba(255, 255, 255, 0.06);
        }
        .nav a.active {
            background: rgba(255, 255, 255, 0.18);
            font-weight: 700;
        }
        .content {
            padding: 24px;
        }
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .topbar h1 {
            margin: 0;
            font-size: 28px;
        }
        .muted { color: var(--muted); }
        .card {
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 18px;
            padding: 18px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.05);
        }
        .grid {
            display: grid;
            gap: 16px;
        }
        .grid.stats {
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            margin-bottom: 20px;
        }
        .stat-value {
            font-size: 32px;
            font-weight: 800;
            margin-top: 10px;
        }
        .flash {
            padding: 14px 16px;
            border-radius: 14px;
            margin-bottom: 16px;
            font-weight: 600;
        }
        .flash.success { background: #e8f8f2; color: #136d4f; }
        .flash.error { background: #fff0f3; color: #b4233d; }
        .actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .btn, button {
            border: 0;
            border-radius: 12px;
            padding: 10px 14px;
            background: var(--primary);
            color: #fff;
            cursor: pointer;
            font-weight: 700;
        }
        .btn.secondary {
            background: #e8eef8;
            color: var(--text);
        }
        .btn.danger {
            background: var(--danger);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
        }
        th, td {
            text-align: left;
            padding: 12px 10px;
            border-bottom: 1px solid var(--line);
            vertical-align: top;
        }
        th {
            color: var(--muted);
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        form.grid-form {
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        }
        .field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .field.full {
            grid-column: 1 / -1;
        }
        input, select, textarea {
            width: 100%;
            padding: 11px 12px;
            border-radius: 12px;
            border: 1px solid var(--line);
            background: #fff;
            font: inherit;
        }
        textarea {
            min-height: 110px;
            resize: vertical;
        }
        .split {
            display: grid;
            gap: 18px;
            grid-template-columns: minmax(320px, 420px) 1fr;
        }
        .badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            background: #e8eef8;
        }
        @media (max-width: 960px) {
            .shell { grid-template-columns: 1fr; }
            .sidebar { padding-bottom: 12px; }
            .split { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<?php if (admin_is_logged_in()): ?>
    <div class="shell">
        <aside class="sidebar">
            <div class="brand">QuizMaster</div>
            <nav class="nav">
                <a class="<?= $active === 'dashboard' ? 'active' : '' ?>" href="index.php">Dashboard</a>
                <a class="<?= $active === 'categories' ? 'active' : '' ?>" href="categories.php">Categorias</a>
                <a class="<?= $active === 'subcategories' ? 'active' : '' ?>" href="subcategories.php">Subcategorias</a>
                <a class="<?= $active === 'questions' ? 'active' : '' ?>" href="questions.php">Preguntas</a>
                <a href="logout.php">Cerrar sesion</a>
            </nav>
        </aside>
        <main class="content">
            <div class="topbar">
                <div>
                    <h1><?= admin_h($title) ?></h1>
                    <div class="muted">Admin conectado como <?= admin_h($user['username'] ?? 'admin') ?></div>
                </div>
            </div>
            <?php if ($flash !== null): ?>
                <div class="flash <?= admin_h($flash['type']) ?>"><?= admin_h($flash['message']) ?></div>
            <?php endif; ?>
<?php else: ?>
    <main style="min-height:100vh;display:grid;place-items:center;padding:24px;background:linear-gradient(180deg,#1d74d8,#7fc4ff);">
        <?php if ($flash !== null): ?>
            <div class="flash <?= admin_h($flash['type']) ?>" style="position:fixed;top:20px;right:20px;max-width:340px;"><?= admin_h($flash['message']) ?></div>
        <?php endif; ?>
<?php endif; ?>
    <?php
}

function admin_render_footer(): void
{
    ?>
<?php if (admin_is_logged_in()): ?>
        </main>
    </div>
<?php else: ?>
    </main>
<?php endif; ?>
</body>
</html>
    <?php
}

function admin_count(string $table): int
{
    $row = admin_db()->fetchOne("SELECT COUNT(*) AS total FROM {$table}");
    return (int) ($row['total'] ?? 0);
}

function admin_categories(): array
{
    return admin_db()->fetchAll(
        "SELECT id, category_name, type, language_id, row_order, plan, amount, status
         FROM category
         ORDER BY CAST(row_order AS UNSIGNED) ASC, id DESC"
    );
}

function admin_subcategories(): array
{
    return admin_db()->fetchAll(
        "SELECT s.id, s.maincat_id, s.language_id, s.subcategory_name, s.row_order, s.status,
                c.category_name
         FROM subcategory s
         LEFT JOIN category c ON c.id = s.maincat_id
         ORDER BY CAST(s.row_order AS UNSIGNED) ASC, s.id DESC"
    );
}

