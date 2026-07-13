<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
admin_require_login();

if (isset($_GET['delete'])) {
    $deleteId = (int) $_GET['delete'];
    admin_db()->execute("DELETE FROM subcategory WHERE id={$deleteId} LIMIT 1");
    admin_set_flash('Subcategoria eliminada.');
    admin_redirect('subcategories.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $maincatId = (int) ($_POST['maincat_id'] ?? 0);
    $languageId = (int) ($_POST['language_id'] ?? 0);
    $name = admin_escape((string) ($_POST['subcategory_name'] ?? ''));
    $image = admin_escape((string) ($_POST['image'] ?? ''));
    $rowOrder = admin_escape((string) ($_POST['row_order'] ?? '1'));
    $status = isset($_POST['status']) ? (int) $_POST['status'] : 1;

    if ($maincatId <= 0 || $name === '') {
        admin_set_flash('Selecciona categoria y nombre.', 'error');
        admin_redirect('subcategories.php');
    }

    if ($id > 0) {
        admin_db()->execute(
            "UPDATE subcategory
             SET maincat_id={$maincatId},
                 language_id={$languageId},
                 subcategory_name='{$name}',
                 image='{$image}',
                 row_order='{$rowOrder}',
                 status={$status}
             WHERE id={$id}"
        );
        admin_set_flash('Subcategoria actualizada.');
    } else {
        admin_db()->execute(
            "INSERT INTO subcategory
             (maincat_id, language_id, subcategory_name, image, status, row_order)
             VALUES
             ({$maincatId}, {$languageId}, '{$name}', '{$image}', {$status}, '{$rowOrder}')"
        );
        admin_set_flash('Subcategoria creada.');
    }

    admin_redirect('subcategories.php');
}

$editId = (int) ($_GET['edit'] ?? 0);
$editing = $editId > 0
    ? admin_db()->fetchOne("SELECT * FROM subcategory WHERE id={$editId} LIMIT 1")
    : null;

$categories = admin_categories();
$subcategories = admin_subcategories();

admin_render_header('Subcategorias', 'subcategories');
?>
<div class="split">
    <section class="card">
        <h3 style="margin-top:0;"><?= $editing ? 'Editar subcategoria' : 'Nueva subcategoria' ?></h3>
        <form method="post" class="grid-form">
            <input type="hidden" name="id" value="<?= admin_h((string) ($editing['id'] ?? '0')) ?>">
            <div class="field full">
                <label>Categoria principal</label>
                <select name="maincat_id" required>
                    <option value="">Selecciona una categoria</option>
                    <?php $selectedMain = (string) ($editing['maincat_id'] ?? ''); ?>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= admin_h((string) $category['id']) ?>" <?= $selectedMain === (string) $category['id'] ? 'selected' : '' ?>>
                            <?= admin_h($category['category_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field full">
                <label>Nombre</label>
                <input name="subcategory_name" required value="<?= admin_h($editing['subcategory_name'] ?? '') ?>">
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
            <div class="field full">
                <label>Imagen</label>
                <input name="image" placeholder="nombre.png" value="<?= admin_h($editing['image'] ?? '') ?>">
            </div>
            <div class="actions">
                <button type="submit"><?= $editing ? 'Guardar cambios' : 'Crear subcategoria' ?></button>
                <?php if ($editing): ?>
                    <a class="btn secondary" href="subcategories.php">Cancelar</a>
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
                <th>Subcategoria</th>
                <th>Categoria</th>
                <th>Orden</th>
                <th>Status</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($subcategories as $subcategory): ?>
                <tr>
                    <td><?= admin_h((string) $subcategory['id']) ?></td>
                    <td><?= admin_h($subcategory['subcategory_name']) ?></td>
                    <td><?= admin_h($subcategory['category_name'] ?? 'Sin categoria') ?></td>
                    <td><?= admin_h((string) $subcategory['row_order']) ?></td>
                    <td><?= admin_h((string) $subcategory['status']) ?></td>
                    <td class="actions">
                        <a class="btn secondary" href="subcategories.php?edit=<?= admin_h((string) $subcategory['id']) ?>">Editar</a>
                        <a class="btn danger" href="subcategories.php?delete=<?= admin_h((string) $subcategory['id']) ?>" onclick="return confirm('Eliminar subcategoria?')">Eliminar</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($subcategories === []): ?>
                <tr><td colspan="6" class="muted">Aun no hay subcategorias.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </section>
</div>
<?php admin_render_footer(); ?>
