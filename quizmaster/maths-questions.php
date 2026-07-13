<?php
session_start();
if (!isset($_SESSION['id']) && !isset($_SESSION['username'])) {
    header("location:index.php");
    return false;
    exit();
}
$type = '3';
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
        
        <script src="https://cdn.ckeditor.com/4.16.2/standard-all/ckeditor.js"></script>
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
                                    <h2>Questions for Quiz <small>Create New Question</small></h2>
                                    <div class="clearfix"></div>
                                </div>
                                <div class="x_content">
                                    <div class="row">
                                        <form id="register_form" method="POST" action="db_operations.php" data-parsley-validate="" enctype="multipart/form-data" class="form-horizontal form-label-left" novalidate="novalidate">
                                            <h4 class="col-md-offset-1"><strong>Create a Question</strong></h4>
                                            <input type="hidden" id="add_maths_question" name="add_maths_question" required="" value="1" aria-required="true">
                                            <?php if (isset($_GET['id'])) { ?>
                                                <input type="hidden" id="question_id" name="question_id" required value="<?= $_GET['id']; ?>" aria-required="true">
                                            <?php } ?>                                           

                                            <?php
                                            $db->sql("SET NAMES 'utf8'");
                                            $res = array();
                                            $sess_language_id = '0';
                                            $sess_category = '0';
                                            $sess_subcategory = '0';
                                            $question_type = '1';
                                            if (isset($_GET['id'])) {
                                                $id = $_GET['id'];
                                                $sql = "SELECT * FROM `tbl_maths_question` WHERE id=" . $id;
                                                $db->sql($sql);
                                                $res = $db->getResult();
                                                if (!empty($res)) {
                                                    $sess_language_id = $res[0]['language_id'];
                                                    $sess_category = $res[0]['category'];
                                                    $sess_subcategory = $res[0]['subcategory'];
                                                    $question_type = $res[0]['question_type'];
                                                    ?>
                                                    <input type="hidden" id="image_url" name="image_url" required value="<?= ($res[0]['image']) ? 'images/maths-question/' . $res[0]['image'] : ''; ?>" aria-required="true">
                                                    <?php
                                                }
                                            }
                                            if ($fn->is_language_mode_enabled()) {
                                                ?>
                                                <div class="form-group">
                                                    <label class="control-label col-md-1 col-sm-3 col-xs-12" for="category">Language</label>
                                                    <div class="col-md-11 col-sm-7 col-xs-12">
                                                        <?php
                                                        $sql = "SELECT * FROM `languages` ORDER BY id DESC";
                                                        $db->sql($sql);
                                                        $languages = $db->getResult();
                                                        ?>
                                                        <select id="language_id" name="language_id" required class="form-control">
                                                            <option value="">Select language</option>
                                                            <?php foreach ($languages as $language) { ?>
                                                                <option value='<?= $language['id'] ?>'><?= $language['language'] ?></option>
                                                            <?php } ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            <?php } ?>
                                            <div class="form-group">
                                                <label class="control-label col-md-1 col-sm-3 col-xs-12" for="category">Category</label>
                                                <div class="col-md-5 col-sm-7 col-xs-12">
                                                    <?php
                                                    $sql = "SELECT id, category_name FROM category WHERE type=" . $type . " ORDER BY id DESC";
                                                    $db->sql($sql);
                                                    $categories = $db->getResult();
                                                    ?>
                                                    <select name='category' id='category' class='form-control' required>
                                                        <option value=''>Select Main Category</option>
                                                        <?php foreach ($categories as $row) { ?>
                                                            <option value='<?= $row['id'] ?>'><?= $row['category_name'] ?></option>
                                                        <?php } ?>
                                                    </select>
                                                </div>
                                                <label class="control-label col-md-1 col-sm-3 col-xs-12" for="subcategory">Sub Category</label>
                                                <div class="col-md-5 col-sm-7 col-xs-12">
                                                    <select name='subcategory' id='subcategory' class='form-control' >
                                                        <option value=''>Select Sub Category</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label col-md-1 col-sm-3 col-xs-12" for="question">Question</label>
                                                <div class="col-md-11 col-sm-7 col-xs-12">
                                                    <textarea id="question" name="question" class="form-control" required><?= (!empty($res)) ? $res[0]['question'] : '' ?></textarea>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label col-md-1 col-sm-3 col-xs-12" for="answer type">Question Type</label>
                                                <div class="col-md-5 col-sm-7 col-xs-12">                                                     
                                                    <div id="status" class="btn-group">
                                                        <label class="btn btn-default" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default">
                                                            <input type="radio" name="question_type" value="1" <?= (!isset($_GET['id'])) ? 'checked' : '' ?> <?= (!empty($res)) ? (($res[0]['question_type'] == '1') ? 'checked' : '') : '' ?> /> Options 
                                                        </label>
                                                        <label class="btn btn-default" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default">
                                                            <input type="radio" name="question_type" value="2" <?= (!empty($res)) ? (($res[0]['question_type'] == '2') ? 'checked' : '') : '' ?>> True / False
                                                        </label>                                                        
                                                    </div>
                                                </div>
                                                <label class="control-label col-md-1 col-sm-3 col-xs-12" for="image">Image</small></label>
                                                <div class="col-md-5 col-sm-7 col-xs-12">
                                                    <input type="file" id="image" name="image" class="form-control" aria-required="true">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label col-md-1 col-sm-3 col-xs-12" for="a">Options</label>
                                                <div class="col-md-11 col-sm-7 col-xs-12"></div>
                                            </div>
                                            <div class="form-group">
                                                <label for="a" class="control-label col-md-1 col-sm-3 col-xs-12">A</label>
                                                <div class="col-md-5 col-sm-7 col-xs-12">
                                                    <textarea id="a" name="a" class="form-control"><?= (!empty($res)) ? $res[0]['optiona'] : '' ?></textarea>
                                                </div>
                                                <label for="b" class="control-label col-md-1 col-sm-3 col-xs-12">B</label>
                                                <div class="col-md-5 col-sm-7 col-xs-12">
                                                    <textarea id="b" name="b" class="form-control"><?= (!empty($res)) ? $res[0]['optionb'] : '' ?></textarea>
                                                </div>
                                            </div>
                                            <div id="tf">
                                                <div class="form-group" >
                                                    <label for="c" class="control-label col-md-1 col-sm-3 col-xs-12">C</label>
                                                    <div class="col-md-5 col-sm-7 col-xs-12">
                                                        <textarea id="c" name="c" class="form-control"><?= (!empty($res)) ? $res[0]['optionc'] : '' ?></textarea>
                                                    </div>
                                                    <label for="d" class="control-label col-md-1 col-sm-3 col-xs-12">D</label>
                                                    <div class="col-md-5 col-sm-7 col-xs-12">
                                                        <textarea id="d" name="d" class="form-control"><?= (!empty($res)) ? $res[0]['optiond'] : '' ?></textarea>
                                                    </div>
                                                </div>
                                                <?php if ($fn->is_option_e_mode_enabled()) { ?>
                                                    <div class="form-group">
                                                        <label for="e" class="control-label col-md-1 col-sm-3 col-xs-12">E </label>
                                                        <div class="col-md-5 col-sm-7 col-xs-12">
                                                            <textarea id="e" name="e" class="form-control"><?= (!empty($res)) ? $res[0]['optione'] : '' ?></textarea>
                                                        </div>
                                                        <label for="d" class="control-label col-md-1 col-sm-3 col-xs-12"></label>
                                                        <div class="col-md-5 col-sm-7 col-xs-12">
                                                        </div>
                                                    </div>
                                                <?php } ?>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label col-md-1 col-sm-3 col-xs-12" for="answer">Answer</label>
                                                <div class="col-md-11 col-sm-7 col-xs-12">
                                                    <select name='answer' id='answer' class='form-control'>
                                                        <option value=''>Select Right Answer</option>
                                                        <option value='a' <?= (!empty($res)) ? (($res[0]['answer'] == 'a') ? 'selected' : '') : '' ?>>A</option>
                                                        <option value='b' <?= (!empty($res)) ? (($res[0]['answer'] == 'b') ? 'selected' : '') : '' ?>>B</option>
                                                        <option class='ntf' value='c' <?= (!empty($res)) ? (($res[0]['answer'] == 'c') ? 'selected' : '') : '' ?>>C</option>
                                                        <option class='ntf' value='d' <?= (!empty($res)) ? (($res[0]['answer'] == 'd') ? 'selected' : '') : '' ?>>D</option>
                                                        <?php if ($fn->is_option_e_mode_enabled()) { ?>
                                                            <option class='ntf' value='e' <?= (!empty($res)) ? (($res[0]['answer'] == 'e') ? 'selected' : '') : '' ?>>E</option>
                                                        <?php } ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label col-md-1 col-sm-3 col-xs-12" for="note">Note</label>
                                                <div class="col-md-11 col-sm-7 col-xs-12">
                                                    <textarea name='note' id='note' class='form-control'><?= (!empty($res)) ? $res[0]['note'] : '' ?></textarea>
                                                </div>
                                            </div>
                                            <div class="ln_solid"></div>
                                            <div class="form-group">
                                                <div class="col-md-6 col-sm-6 col-xs-12 col-md-offset-1">
                                                    <button type="submit" id="submit_btn" class="btn btn-success">Create Now</button>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div  class="col-md-offset-3 col-md-4" style ="display:none;" id="result">
                                                </div>
                                            </div>
                                        </form>
                                        <div class="col-md-12"><hr></div>
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

        <script type="text/javascript">
            MathJax = {
                tex: {
                    inlineMath: [['$', '$'], ['\\(', '\\)']]
                }
            };
        </script>
        <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-chtml.js"></script>
        <script type="text/javascript">
            CKEDITOR.replace('question', {
                extraPlugins: 'mathjax',
                mathJaxLib: 'https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.4/MathJax.js?config=TeX-AMS_HTML',
                height: 100,
                removeButtons: 'PasteFromWord,Image,Anchor'
            });
            CKEDITOR.replace('a', {
                extraPlugins: 'mathjax',
                mathJaxLib: 'https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.4/MathJax.js?config=TeX-AMS_HTML',
                height: 100,
                removeButtons: 'PasteFromWord,Image,Anchor'
            });
            CKEDITOR.replace('b', {
                extraPlugins: 'mathjax',
                mathJaxLib: 'https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.4/MathJax.js?config=TeX-AMS_HTML',
                height: 100,
                removeButtons: 'PasteFromWord,Image,Anchor'
            });
            CKEDITOR.replace('c', {
                extraPlugins: 'mathjax',
                mathJaxLib: 'https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.4/MathJax.js?config=TeX-AMS_HTML',
                height: 100,
                removeButtons: 'PasteFromWord,Image,Anchor'
            });
            CKEDITOR.replace('d', {
                extraPlugins: 'mathjax',
                mathJaxLib: 'https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.4/MathJax.js?config=TeX-AMS_HTML',
                height: 100,
                removeButtons: 'PasteFromWord,Image,Anchor'
            });
            CKEDITOR.replace('e', {
                extraPlugins: 'mathjax',
                mathJaxLib: 'https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.4/MathJax.js?config=TeX-AMS_HTML',
                height: 100,
                removeButtons: 'PasteFromWord,Image,Anchor'
            });
            CKEDITOR.replace('note', {
                extraPlugins: 'mathjax',
                mathJaxLib: 'https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.4/MathJax.js?config=TeX-AMS_HTML',
                height: 100,
                removeButtons: 'PasteFromWord,Image,Anchor'
            });

            if (CKEDITOR.env.ie && CKEDITOR.env.version == 8) {
                document.getElementById('ie8-warning').className = 'tip alert';
            }
        </script>
        <script>
            var type =<?= $type ?>;
            var sess_language_id = '<?= $sess_language_id ?>';
            var sess_category = '<?= $sess_category ?>';
            var sess_subcategory = '<?= $sess_subcategory ?>';
            $(document).ready(function () {
                if (sess_language_id != '0' || sess_category != '0') {
<?php if ($fn->is_language_mode_enabled()) { ?>
                        $('#language_id').val(sess_language_id).trigger("change", [sess_language_id, sess_category, sess_subcategory]);
<?php } else { ?>
                        $('#category').val(sess_category).trigger("change", [sess_category, sess_subcategory]);
<?php } ?>
                }
                var question_type = '<?= $question_type ?>';
                if (question_type == "2") {
                    $('#tf').hide('fast');
                    $('.ntf').hide('fast');
                } else {
                    $('#tf').show('fast');
                    $('.ntf').show('fast');
                }
            });
<?php if ($fn->is_language_mode_enabled()) { ?>
                $('#language_id').on('change', function (e, row_language_id, row_category, row_subcategory) {
                    var language_id = $('#language_id').val();
                    $.ajax({
                        type: 'POST',
                        url: "db_operations.php",
                        data: 'get_categories_of_language=1&language_id=' + language_id + '&type=' + type,
                        beforeSend: function () {
                            $('#category').html('Please wait..');
                        },
                        success: function (result) {
                            $('#category').html(result).trigger("change");
                            if (language_id == row_language_id && row_category != 0)
                                $('#category').val(row_category).trigger("change", [row_category, row_subcategory]);
                        }
                    });
                });
<?php } ?>
        </script>
        <script>
            $('#category').on('change', function (e, row_category, row_subcategroy) {
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
                        if (category_id == row_category && row_subcategroy != 0)
                            $('#subcategory').val(row_subcategroy);
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
            $('input[name="question_type"]').on("click", function (e) {
                var question_type = $(this).val();
                if (question_type == "2") {
                    $('#tf').hide('fast');
                    $('.ntf').hide('fast');
                } else {
                    $('#a').val('');
                    $('#b').val('');
                    $('#tf').show('fast');
                    $('.ntf').show('fast');
                }
            });
        </script>

    </body>
</html>
