<?php
session_start();
if (!isset($_SESSION['id']) && !isset($_SESSION['username'])) {
    header("location:index.php");
    return false;
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <!-- Meta, title, CSS, favicons, etc. -->
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Questions for Quiz | <?= ucwords($_SESSION['company_name']) ?> - Admin Panel </title>
        <?php include 'include-css.php'; ?>
    </head>
    <body class="nav-md">
        <div class="container body">
            <div class="main_container">
                <?php include 'sidebar.php'; ?>
                <!-- page content -->
                <div class="right_col" role="main">
                    <!-- top tiles -->
                    <br />
                    <div class="row">
                        <div class="col-md-12 col-sm-12 col-xs-12">
                            <div class="x_panel">
                                <div class="x_title">
                                    <h2>Questions for Quiz <small>Update Question</small></h2>
                                    <div class="clearfix"></div>
                                </div>
                                <div class="x_content">
                                    <div class="row">
                                        <form id="register_form" method="POST" action="db_operations.php" data-parsley-validate="" class="form-horizontal form-label-left" novalidate="novalidate">
                                            <h4 class="col-md-offset-1"><strong>Update a Question</strong></h4>

                                            <?php
                                            $res = "";
                                            $db->sql("SET NAMES 'utf8'");
                                            $sql = "SELECT * FROM question WHERE id='" . $_GET['id'] . "' LIMIT 1";
                                            $db->sql($sql);
                                            $res = $db->getResult();
                                            $res = (!empty($res)) ? $res[0] : "";
                                            ?>

                                            <input type='hidden' name="question_id" id="question_id" value='<?= (!empty($res)) ? $res['id'] : '' ?>'/>
                                            <input type='hidden' name="update_question" id="update_question" value='1'/>
                                            <input type='hidden' name="image_url" id="image_url" value='<?= (!empty($res)) ? 'images/questions/' . $res['image'] : '' ?>'/>
                                            <?php
                                            if ($fn->is_language_mode_enabled()) {
                                                ?>
                                                <div class="form-group">
                                                    <label class="control-label col-md-1 col-sm-3 col-xs-12" for="category">Language</label>
                                                    <div class="col-md-10 col-sm-6 col-xs-12">
                                                        <?php
                                                        $sql = "SELECT * FROM `languages` ORDER BY id DESC";
                                                        $db->sql($sql);
                                                        $languages = $db->getResult();
                                                        ?>
                                                        <select id="language_id" name="language_id" required class="form-control col-md-7 col-xs-12">
                                                            <option value="">Select language</option>
                                                            <?php foreach ($languages as $language) { ?>
                                                                <?php if ($language['id'] == $res['language_id']) { ?>
                                                                    <option value='<?= $language['id'] ?>' selected><?= $language['language'] ?></option>
                                                                <?php } else { ?>
                                                                    <option value='<?= $language['id'] ?>'><?= $language['language'] ?></option>
                                                                <?php } ?>                                                                
                                                            <?php } ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            <?php } ?>
                                            <div class="form-group">
                                                <label class="control-label col-md-1 col-sm-3 col-xs-12" for="category">Category</label>
                                                <div class="col-md-4 col-sm-6 col-xs-12">
                                                    <?php
                                                    if ($fn->is_language_mode_enabled()) {
                                                        $sql = "select id,`category_name` from `category` WHERE language_id='" . $res['language_id'] . "' order by id desc ";
                                                    } else {
                                                        $sql = "select id,`category_name` from `category` order by id desc";
                                                    }
                                                    $db->sql($sql);
                                                    $categories = $db->getResult();
                                                    ?>
                                                    <select name='category' id='category' class='form-control' required>
                                                        <option value=''>Select Main Category</option>
                                                        <?php foreach ($categories as $row) { ?>
                                                            <?php if ($row['id'] == $res['category']) { ?>
                                                                <option value='<?= $row['id'] ?>' selected><?= $row['category_name'] ?></option>
                                                            <?php } else { ?>
                                                                <option value='<?= $row['id'] ?>'><?= $row['category_name'] ?></option>
                                                            <?php } ?>                                                            
                                                        <?php } ?>
                                                    </select>
                                                </div>
                                                <label class="control-label col-md-2 col-sm-3 col-xs-12" for="subcategory">Sub Category</label>
                                                <div class="col-md-4 col-sm-6 col-xs-12">
                                                    <?php
                                                    $sql1 = "select id,`subcategory_name` from `subcategory` WHERE maincat_id='" . $res['category'] . "' order by id desc ";
                                                    $db->sql($sql1);
                                                    $subcategories = $db->getResult();
                                                    ?>
                                                    <select name='subcategory' id='subcategory' class='form-control' >
                                                        <option value=''>Select Sub Category</option>
                                                        <?php foreach ($subcategories as $srow) { ?>
                                                            <?php if ($srow['id'] == $res['subcategory']) { ?>
                                                                <option value='<?= $srow['id'] ?>' selected><?= $srow['subcategory_name'] ?></option>
                                                            <?php } else { ?>
                                                                <option value='<?= $srow['id'] ?>'><?= $srow['subcategory_name'] ?></option>
                                                            <?php } ?>                                                            
                                                        <?php } ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label col-md-1 col-sm-3 col-xs-12" for="question">Question</label>
                                                <div class="col-md-10 col-sm-6 col-xs-12">
                                                    <textarea id="question" name="question" class="form-control col-md-7 col-xs-12" required><?= (!empty($res)) ? $res['question'] : '' ?></textarea>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label col-md-1 col-sm-3 col-xs-12" for="image">Image for Question <small>( if any )</small></label>
                                                <div class="col-md-10 col-sm-6 col-xs-12">
                                                    <input type="file" id="image" name="image" class="form-control col-md-7 col-xs-12" aria-required="true">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label col-md-1 col-sm-3 col-xs-12" for="answer type">Question Type</label>
                                                <div class="col-md-8 col-sm-6 col-xs-12">                                                     
                                                    <div id="status" class="btn-group">
                                                        <label class="btn btn-default" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default">
                                                            <input type="radio" name="edit_question_type" value="1" <?= (!empty($res) && $res['question_type'] == '1') ? 'checked' : '' ?>> Options 
                                                        </label>
                                                        <label class="btn btn-default" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default">
                                                            <input type="radio" name="edit_question_type" value="2" <?= (!empty($res) && $res['question_type'] == '2') ? 'checked' : '' ?>> True / False
                                                        </label>                                                        
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label col-md-1 col-sm-3 col-xs-12" for="a">Options</label>
                                                <div class="col-md-8 col-sm-6 col-xs-12"></div>
                                            </div>
                                            <div class="form-group">
                                                <label for="a" class="control-label col-md-1 col-sm-3 col-xs-12">A</label>
                                                <div class="col-md-4 col-sm-6 col-xs-12">
                                                    <input id="edit_a" class="form-control col-md-7 col-xs-12" type="text" name="a" value="<?= (!empty($res)) ? $res['optiona'] : '' ?>">
                                                </div>
                                                <label for="b" class="control-label col-md-1 col-sm-3 col-xs-12">B</label>
                                                <div class="col-md-5 col-sm-6 col-xs-12">
                                                    <input id="edit_b" class="form-control col-md-7 col-xs-12" type="text" name="b" value="<?= (!empty($res)) ? $res['optionb'] : '' ?>">
                                                </div>
                                            </div>
                                            <div id="edit_tf">
                                                <div class="form-group" >
                                                    <label for="c" class="control-label col-md-1 col-sm-3 col-xs-12">C</label>
                                                    <div class="col-md-4 col-sm-6 col-xs-12">
                                                        <input id="edit_c" class="form-control col-md-7 col-xs-12" type="text" name="c" value="<?= (!empty($res)) ? $res['optionc'] : '' ?>">
                                                    </div>
                                                    <label for="d" class="control-label col-md-1 col-sm-3 col-xs-12">D</label>
                                                    <div class="col-md-5 col-sm-6 col-xs-12">
                                                        <input id="edit_d" class="form-control col-md-7 col-xs-12" type="text" name="d" value="<?= (!empty($res)) ? $res['optiond'] : '' ?>">
                                                    </div>
                                                </div>
                                                <?php if ($fn->is_option_e_mode_enabled()) { ?>
                                                    <div class="form-group">
                                                        <label for="e" class="control-label col-md-1 col-sm-3 col-xs-12">E</label>
                                                        <div class="col-md-4 col-sm-6 col-xs-12">
                                                            <input id="edit_e" class="form-control col-md-7 col-xs-12" type="text" name="e" value="<?= (!empty($res)) ? $res['optione'] : '' ?>">
                                                        </div>
                                                        <label class="control-label col-md-1 col-sm-3 col-xs-12"></label>
                                                        <div class="col-md-5 col-sm-6 col-xs-12"></div>
                                                    </div>
                                                <?php } ?>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label col-md-1 col-sm-3 col-xs-12" for="level">Level</label>
                                                <div class="col-md-10 col-sm-6 col-xs-12">
                                                    <input type='text' name='level' id='level' class='form-control' value="<?= (!empty($res)) ? $res['level'] : '' ?>" required>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label col-md-1 col-sm-3 col-xs-12" for="answer">Answer</label>
                                                <div class="col-md-10 col-sm-6 col-xs-12">
                                                    <select name="answer" id="edit_answer" class="form-control" required>
                                                        <option value="">Select Right Answer</option>
                                                        <option value="a" <?= (!empty($res) && ($res['answer'] == 'a' || $res['answer'] == 'A')) ? 'selected' : '' ?>>A</option>
                                                        <option value="b" <?= (!empty($res) && ($res['answer'] == 'b' || $res['answer'] == 'B')) ? 'selected' : '' ?>>B</option>
                                                        <option class='edit_ntf' value="c" <?= (!empty($res) && ($res['answer'] == 'c' || $res['answer'] == 'C')) ? 'selected' : '' ?>>C</option>
                                                        <option class='edit_ntf' value="d" <?= (!empty($res) && ($res['answer'] == 'd' || $res['answer'] == 'D')) ? 'selected' : '' ?>>D</option>
                                                        <?php if ($fn->is_option_e_mode_enabled()) { ?>
                                                            <option class='edit_ntf' value='e' <?= (!empty($res) && ($res['answer'] == 'e' || $res['answer'] == 'E')) ? 'selected' : '' ?>>E</option>
                                                        <?php } ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label col-md-1 col-sm-3 col-xs-12" for="note">Note</label>
                                                <div class="col-md-10 col-sm-6 col-xs-12">
                                                    <textarea name='note' id='note' class='form-control'><?= (!empty($res)) ? $res['note'] : '' ?></textarea>
                                                </div>
                                            </div>
                                            <div class="ln_solid"></div>
                                            <div class="form-group">
                                                <div class="col-md-6 col-sm-6 col-xs-12 col-md-offset-1">
                                                    <button type="submit" id="update_btn" class="btn btn-success">Update Question</button>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-offset-3 col-md-4" style ="display:none;" id="update_result"></div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /page content -->

            <!-- footer content -->
            <?php include 'footer.php'; ?>
            <!-- /footer content -->
        </div>

        <!-- jQuery -->
        <script>
            $(document).ready(function () {

                var edit_question_type = $('input[name="edit_question_type"]:checked').val();

                if (edit_question_type == "2") {
                    $('#edit_tf').hide('fast');
                    $('#edit_a').val("<?php echo $config['true_value'] ?>");
                    $('#edit_b').val("<?php echo $config['false_value'] ?>");
                    $('.edit_ntf').hide('fast');

                } else {
                    $('#edit_tf').show('fast');
                    $('.edit_ntf').show('fast');
                }
            });
            $('input[name="edit_question_type"]').on("click", function (e) {
                var edit_question_type = $(this).val();

                if (edit_question_type == "2") {
                    $('#edit_tf').hide('fast');
                    $('#edit_a').val("<?php echo $config['true_value'] ?>");
                    $('#edit_b').val("<?php echo $config['false_value'] ?>");
                    $('.edit_ntf').hide('fast');
                    $('#edit_answer').val('');
                } else {
                    $('#edit_tf').show('fast');
                    $('.edit_ntf').show('fast');
                }
            });
        </script>
        <script>
<?php if ($fn->is_language_mode_enabled()) { ?>
                $('#language_id').on('change', function (e) {
                    var language_id = $('#language_id').val();
                    $.ajax({
                        type: 'POST',
                        url: "db_operations.php",
                        data: 'get_categories_of_language=1&language_id=' + language_id,
                        beforeSend: function () {
                            $('#category').html('Please wait..');
                        },
                        success: function (result) {
                            $('#category').html(result);
                        }
                    });
                });

                category_options = '';
    <?php
    $category_options = "<option value=''>Select Options</option>";
    foreach ($categories as $category) {
        $category_options .= "<option value='" . $category['id'] . "'>" . $category['category_name'] . "</option>";
    }
    ?>
                category_options = "<?= $category_options; ?>";

<?php } ?>
        </script>

        <script>
            $('#category').on('change', function (e) {
                var category_id = $('#category').val();
                $.ajax({
                    type: 'POST',
                    url: "db_operations.php",
                    data: 'get_subcategories_of_category=1&category_id=' + category_id,
                    beforeSend: function () {
                        $('#subcategory').html('Please wait..');
                    },
                    success: function (result) {
                        $('#subcategory').html(result);
                    }
                });
            });
        </script>


        <script>
            $('#register_form').validate({
                rules: {
                    question: "required",
                    category: "required",
                    a: "required",
                    b: "required",
                    c: "required",
                    d: "required",
                    level: "required",
                    answer: "required"
                }
            });
        </script>
        <script>
            $('#register_form').on('submit', function (e) {
                e.preventDefault();
                var formData = new FormData(this);
                if ($("#register_form").validate().form()) {
<?php if ($fn->is_language_mode_enabled()) { ?>
                        var language = $('#language_id').val();
<?php } ?>
                    var category = $('#category').val();
                    var subcategory = $('#subcategory').val();
                    $.ajax({
                        type: 'POST',
                        url: $(this).attr('action'),
                        data: formData,
                        beforeSend: function () {
                            $('#submit_btn').html('Please wait..');
                            $('#submit_btn').prop('disabled', true);
                        },
                        cache: false,
                        contentType: false,
                        processData: false,
                        success: function (result) {
                            $('#update_result').html(result);
                            $('#update_result').show().delay(4000).fadeOut();
                            $('#update_btn').html('Update Question');
                            $('#category').val(category);
                            $('#subcategory').val(subcategory);
<?php if ($fn->is_language_mode_enabled()) { ?>
                                $('#language_id').val(language);
<?php } ?>
                            $('#tf').show('fast');
                            $('.ntf').show('fast');
                            $('#submit_btn').prop('disabled', false);
                            setTimeout(function () {
                                window.location = 'question-reports.php';
                            }, 4000);
                        }
                    });
                }
            });
        </script>

    </body>
</html>