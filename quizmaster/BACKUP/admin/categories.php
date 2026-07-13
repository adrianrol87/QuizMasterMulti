<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
admin_require_login();

if (isset($_GET['delete'])) {
    $deleteId = (int) $_GET['delete'];
    admin_db()->execute("DELETE FROM category WHERE id={$deleteId} LIMIT 1");
    admin_set_flash('Categoria eliminada.');
    admin_redirect('categories.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $categoryName = admin_escape((string) ($_POST['category_name'] ?? ''));
    $languageId = (int) ($_POST['language_id'] ?? 0);
    $type = (int) ($_POST['type'] ?? 1);
    $image = admin_escape((string) ($_POST['image'] ?? ''));
    $plan = admin_escape((string) ($_POST['plan'] ?? 'Free'));
    $amount = (int) ($_POST['amount'] ?? 0);
    $rowOrder = admin_escape((string) ($_POST['row_order'] ?? '1'));
    $status = isset($_POST['status']) ? (int) $_POST['status'] : 1;

    if ($categoryName === '') {
        admin_set_flash('Escribe el nombre de la categoria.', 'error');
        admin_redirect('categories.php');
    }

    if ($id > 0) {
        admin_db()->execute(
            "UPDATE category
             SET language_id={$languageId},
                 category_name='{$categoryName}',
                 type={$type},
                 image='{$image}',
                 plan='{$plan}',
                 amount={$amount},
                 row_order='{$rowOrder}',
                 status={$status}
             WHERE id={$id}"
        );
        admin_set_flash('Categoria actualizada.');
    } else {
        admin_db()->execute(
            "INSERT INTO category
             (language_id, category_name, type, image, row_order, plan, amount, status)
             VALUES
             ({$languageId}, '{$categoryName}', {$type}, '{$image}', '{$rowOrder}', '{$plan}', {$amount}, {$status})"
        );
        admin_set_flash('Categoria creada.');
    }

    admin_redirect('categories.php');
}

$editId = (int) ($_GET['edit'] ?? 0);
$editing = $editId > 0
    ? admin_db()->fetchOne("SELECT * FROM category WHERE id={$editId} LIMIT 1")
    : null;

$categories = admin_categories();

admin_render_header('Categorias', 'categories');
?>
<div class="split">
    <section class="card">
        <h3 style="margin-top:0;"><?= $editing ? 'Editar categoria' : 'Nueva categoria' ?></h3>
        <form method="post" class="grid-form">
            <input type="hidden" name="id" value="<?= admin_h((string) ($editing['id'] ?? '0')) ?>">
            <div class="field full">
                <label>Nombre</label>
                <input name="category_name" required value="<?= admin_h($editing['category_name'] ?? '') ?>">
            </div>
            <div class="field">
                <label>Tipo</label>
                <select name="type">
                    <?php $selectedType = (string) ($editing['type'] ?? '1'); ?>
                    <option value="1" <?= $selectedType === '1' ? 'selected' : '' ?>>Quiz</option>
                    <option value="2" <?= $selectedType === '2' ? 'selected' : '' ?>>Learning</option>
                    <option value="3" <?= $selectedType === '3' ? 'selected' : '' ?>>Maths</option>
                </select>
            </div>
            <div class="field">
                <label>Language ID</label>
                <input name="language_id" type="number" min="0" value="<?= admin_h((string) ($editing['language_id'] ?? '0')) ?>">
            </div>
            <div class="field">
                <label>Orden</label>
                <input name="row_order" value="<?= admin_h((string) ($editing['row_order'] ?? '1')) ?>">
            </div>
            <div class="field">
                <label>Status</label>
                <?php $selectedStatus = (string) ($editing['status'] ?? '1'); ?>
                <select name="status">
                    <option value="1" <?= $selectedStatus === '1' ? 'selected' : '' ?>>Activo</option>
                    <option value="0" <?= $selectedStatus === '0' ? 'selected' : '' ?>>Inactivo</option>
                </select>
            </div>
            <div class="field">
                <label>Plan</label>
                <input name="plan" value="<?= admin_h((string) ($editing['plan'] ?? 'Free')) ?>">
            </div>
            <div class="field">
                <label>Monto</label>
                <input name="amount" type="number" min="0" value="<?= admin_h((string) ($editing['amount'] ?? '0')) ?>">
            </div>
            <div class="field full">
                <label>Imagen</label>
                <input name="image" placeholder="nombre.png" value="<?= admin_h($editing['image'] ?? '') ?>">
            </div>
            <div class="actions">
                <button type="submit"><?= $editing ? 'Guardar cambios' : 'Crear categoria' ?></button>
                <?php if ($editing): ?>
                    <a class="btn secondary" href="categories.php">Cancelar</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <section class="card">
        <h3 style="margin-top:0;">Listado</h3>
        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>Categoria</th>
                <th>Tipo</th>
                <th>Orden</th>
                <th>Status</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($categories as $category): ?>
                <tr>
                    <td><?= admin_h((string) $category['id']) ?></td>
                    <td><?= admin_h($category['category_name']) ?></td>
                    <td><span class="badge"><?= admin_h((string) $category['type']) ?></span></td>
                    <td><?= admin_h((string) $category['row_order']) ?></td>
                    <td><?= admin_h((string) $category['status']) ?></td>
                    <td class="actions">
                        <a class="btn secondary" href="categories.php?edit=<?= admin_h((string) $category['id']) ?>">Editar</a>
                        <a class="btn danger" href="categories.php?delete=<?= admin_h((string) $category['id']) ?>" onclick="return confirm('Eliminar categoria?')">Eliminar</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($categories === []): ?>
                <tr><td colspan="6" class="muted">Aun no hay categorias.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </section>
</div>
<?php admin_render_footer(); ?>
