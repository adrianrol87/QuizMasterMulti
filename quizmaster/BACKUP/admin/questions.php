<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
admin_require_login();

if (isset($_GET['delete'])) {
    $deleteId = (int) $_GET['delete'];
    admin_db()->execute("DELETE FROM question WHERE id={$deleteId} LIMIT 1");
    admin_set_flash('Pregunta eliminada.');
    admin_redirect('questions.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $category = (int) ($_POST['category'] ?? 0);
    $subcategory = (int) ($_POST['subcategory'] ?? 0);
    $languageId = (int) ($_POST['language_id'] ?? 0);
    $image = admin_escape((string) ($_POST['image'] ?? ''));
    $question = admin_escape((string) ($_POST['question'] ?? ''));
    $questionType = (int) ($_POST['question_type'] ?? 1);
    $optionA = admin_escape((string) ($_POST['optiona'] ?? ''));
    $optionB = admin_escape((string) ($_POST['optionb'] ?? ''));
    $optionC = admin_escape((string) ($_POST['optionc'] ?? ''));
    $optionD = admin_escape((string) ($_POST['optiond'] ?? ''));
    $optionE = admin_escape((string) ($_POST['optione'] ?? ''));
    $answer = admin_escape((string) ($_POST['answer'] ?? ''));
    $level = (int) ($_POST['level'] ?? 1);
    $note = admin_escape((string) ($_POST['note'] ?? ''));

    if ($category <= 0 || $question === '' || $answer === '') {
        admin_set_flash('Completa categoria, pregunta y respuesta.', 'error');
        admin_redirect('questions.php');
    }

    $optionESql = $optionE === '' ? 'NULL' : "'{$optionE}'";

    if ($id > 0) {
        admin_db()->execute(
            "UPDATE question
             SET category={$category},
                 subcategory={$subcategory},
                 language_id={$languageId},
                 image='{$image}',
                 question='{$question}',
                 question_type={$questionType},
                 optiona='{$optionA}',
                 optionb='{$optionB}',
                 optionc='{$optionC}',
                 optiond='{$optionD}',
                 optione={$optionESql},
                 answer='{$answer}',
                 level={$level},
                 note='{$note}'
             WHERE id={$id}"
        );
        admin_set_flash('Pregunta actualizada.');
    } else {
        admin_db()->execute(
            "INSERT INTO question
             (category, subcategory, language_id, image, question, question_type, optiona, optionb, optionc, optiond, optione, answer, level, note)
             VALUES
             ({$category}, {$subcategory}, {$languageId}, '{$image}', '{$question}', {$questionType}, '{$optionA}', '{$optionB}', '{$optionC}', '{$optionD}', {$optionESql}, '{$answer}', {$level}, '{$note}')"
        );
        admin_set_flash('Pregunta creada.');
    }

    admin_redirect('questions.php');
}

$editId = (int) ($_GET['edit'] ?? 0);
$editing = $editId > 0
    ? admin_db()->fetchOne("SELECT * FROM question WHERE id={$editId} LIMIT 1")
    : null;

$categories = admin_categories();
$subcategories = admin_subcategories();
$questions = admin_db()->fetchAll(
    "SELECT q.id, q.question, q.answer, q.level, q.question_type,
            c.category_name, s.subcategory_name
     FROM question q
     LEFT JOIN category c ON c.id = q.category
     LEFT JOIN subcategory s ON s.id = q.subcategory
     ORDER BY q.id DESC
     LIMIT 100"
);

admin_render_header('Preguntas', 'questions');
?>
<div class="split">
    <section class="card">
        <h3 style="margin-top:0;"><?= $editing ? 'Editar pregunta' : 'Nueva pregunta' ?></h3>
        <form method="post" class="grid-form">
            <input type="hidden" name="id" value="<?= admin_h((string) ($editing['id'] ?? '0')) ?>">
            <div class="field">
                <label>Categoria</label>
                <select name="category" required>
                    <option value="">Selecciona categoria</option>
                    <?php $selectedCategory = (string) ($editing['category'] ?? ''); ?>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= admin_h((string) $category['id']) ?>" <?= $selectedCategory === (string) $category['id'] ? 'selected' : '' ?>>
                            <?= admin_h($category['category_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label>Subcategoria</label>
                <select name="subcategory">
                    <option value="0">Sin subcategoria</option>
                    <?php $selectedSubcategory = (string) ($editing['subcategory'] ?? '0'); ?>
                    <?php foreach ($subcategories as $subcategory): ?>
                        <option value="<?= admin_h((string) $subcategory['id']) ?>" <?= $selectedSubcategory === (string) $subcategory['id'] ? 'selected' : '' ?>>
                            <?= admin_h(($subcategory['category_name'] ?? 'General') . ' / ' . $subcategory['subcategory_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label>Tipo</label>
                <?php $selectedType = (string) ($editing['question_type'] ?? '1'); ?>
                <select name="question_type">
                    <option value="1" <?= $selectedType === '1' ? 'selected' : '' ?>>Multiple choice</option>
                    <option value="2" <?= $selectedType === '2' ? 'selected' : '' ?>>True / False</option>
                </select>
            </div>
            <div class="field">
                <label>Nivel</label>
                <input name="level" type="number" min="1" value="<?= admin_h((string) ($editing['level'] ?? '1')) ?>">
            </div>
            <div class="field">
                <label>Language ID</label>
                <input name="language_id" type="number" min="0" value="<?= admin_h((string) ($editing['language_id'] ?? '0')) ?>">
            </div>
            <div class="field full">
                <label>Pregunta</label>
                <textarea name="question" required><?= admin_h($editing['question'] ?? '') ?></textarea>
            </div>
            <div class="field">
                <label>Opcion A</label>
                <input name="optiona" value="<?= admin_h($editing['optiona'] ?? '') ?>">
            </div>
            <div class="field">
                <label>Opcion B</label>
                <input name="optionb" value="<?= admin_h($editing['optionb'] ?? '') ?>">
            </div>
            <div class="field">
                <label>Opcion C</label>
                <input name="optionc" value="<?= admin_h($editing['optionc'] ?? '') ?>">
            </div>
            <div class="field">
                <label>Opcion D</label>
                <input name="optiond" value="<?= admin_h($editing['optiond'] ?? '') ?>">
            </div>
            <div class="field">
                <label>Opcion E</label>
                <input name="optione" value="<?= admin_h($editing['optione'] ?? '') ?>">
            </div>
            <div class="field">
                <label>Respuesta</label>
                <input name="answer" placeholder="optiona / True / False / texto" required value="<?= admin_h($editing['answer'] ?? '') ?>">
            </div>
            <div class="field full">
                <label>Imagen</label>
                <input name="image" placeholder="pregunta.png" value="<?= admin_h($editing['image'] ?? '') ?>">
            </div>
            <div class="field full">
                <label>Nota / explicacion</label>
                <textarea name="note"><?= admin_h($editing['note'] ?? '') ?></textarea>
            </div>
            <div class="actions">
                <button type="submit"><?= $editing ? 'Guardar cambios' : 'Crear pregunta' ?></button>
                <?php if ($editing): ?>
                    <a class="btn secondary" href="questions.php">Cancelar</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <section class="card">
        <h3 style="margin-top:0;">Ultimas preguntas</h3>
        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>Pregunta</th>
                <th>Categoria</th>
                <th>Resp.</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($questions as $question): ?>
                <tr>
                    <td><?= admin_h((string) $question['id']) ?></td>
                    <td><?= admin_h($question['question']) ?></td>
                    <td><?= admin_h(($question['category_name'] ?? 'Sin categoria') . (($question['subcategory_name'] ?? '') !== '' ? ' / ' . $question['subcategory_name'] : '')) ?></td>
                    <td><?= admin_h($question['answer']) ?></td>
                    <td class="actions">
                        <a class="btn secondary" href="questions.php?edit=<?= admin_h((string) $question['id']) ?>">Editar</a>
                        <a class="btn danger" href="questions.php?delete=<?= admin_h((string) $question['id']) ?>" onclick="return confirm('Eliminar pregunta?')">Eliminar</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($questions === []): ?>
                <tr><td colspan="5" class="muted">Aun no hay preguntas.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </section>
</div>
<?php admin_render_footer(); ?>
