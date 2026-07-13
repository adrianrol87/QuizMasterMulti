<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
admin_require_login();

$stats = [
    'Categorias' => admin_count('category'),
    'Subcategorias' => admin_count('subcategory'),
    'Preguntas' => admin_count('question'),
    'Usuarios' => admin_count('users'),
];

$settings = admin_db()->fetchOne(
    "SELECT message FROM settings WHERE type='system_configurations' LIMIT 1"
);

$configData = [];
if ($settings !== null) {
    $configData = json_decode((string) $settings['message'], true) ?: [];
}

admin_render_header('Dashboard', 'dashboard');
?>
<div class="grid stats">
    <?php foreach ($stats as $label => $value): ?>
        <section class="card">
            <div class="muted"><?= admin_h($label) ?></div>
            <div class="stat-value"><?= admin_h((string) $value) ?></div>
        </section>
    <?php endforeach; ?>
</div>

<div class="grid" style="grid-template-columns:1.2fr .8fr;">
    <section class="card">
        <h3 style="margin-top:0;">Siguiente paso recomendado</h3>
        <p class="muted">Tu API ya responde. Lo que faltaba era el panel para cargar contenido. Desde aqui ya puedes crear categorias, subcategorias y preguntas para que la app Flutter empiece a mostrar informacion real.</p>
        <div class="actions">
            <a class="btn" href="categories.php">Crear categorias</a>
            <a class="btn secondary" href="subcategories.php">Crear subcategorias</a>
            <a class="btn secondary" href="questions.php">Crear preguntas</a>
        </div>
    </section>
    <section class="card">
        <h3 style="margin-top:0;">Configuracion actual</h3>
        <div class="muted">Idioma multiple: <strong><?= admin_h((string) ($configData['language_mode'] ?? '0')) ?></strong></div>
        <div class="muted">Version app: <strong><?= admin_h((string) ($configData['app_version'] ?? '-')) ?></strong></div>
        <div class="muted">Modo mantenimiento: <strong><?= admin_h((string) ($configData['app_maintenance'] ?? '0')) ?></strong></div>
    </section>
</div>
<?php admin_render_footer(); ?>
