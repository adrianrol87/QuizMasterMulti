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
        <title>Category Order | <?= ucwords($_SESSION['company_name']) ?> Admin Panel</title>
        <?php include 'include-css.php'; ?>
        <style>
            #sortable-row li {
                margin-bottom: 4px;
                padding: 10px;
                background-color: #ededed;
                cursor: move;
            }

            #sortable-row li.ui-state-highlight {
                height: 1.0em;
                background-color: #F0F0F0;
                border: #ccc 2px dotted;
            }
        </style>
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
                        <div class="x_panel">
                            <div class="x_title">
                                <h2>Category Order Settings <small>Update Category Order here</small></h2>

                                <div class="clearfix"></div>
                            </div>
                            <div class="x_content">
                                <?php
                                $db->sql("SET NAMES 'utf8'");
                                $sql = "SELECT * FROM category WHERE type=" . $type . " ORDER BY CAST(row_order as unsigned) ASC";
                                $db->sql($sql);
                                $cat = $db->getResult();
                                ?>
                                <div class="col-md-6 col-sm-12 col-xs-12">
                                    <?php if ($fn->is_language_mode_enabled()) { ?>
                                        <div class="row">
                                            <div class='col-md-12'>
                                                <?php
                                                $sql = "SELECT * FROM `languages` ORDER BY id DESC";
                                                $db->sql($sql);
                                                $languages = $db->getResult();
                                                ?>
                                                <select id='filter_language' class='form-control' required>
                                                    <option value="">Select language</option>
                                                    <?php foreach ($languages as $language) { ?>
                                                        <option value='<?= $language['id'] ?>'><?= $language['language'] ?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        </div>
                                    <?php } ?>
                                    <h2>Main Category</h2>
                                    <hr>
                                    <form id="category_form" method="POST" action="db_operations.php" data-parsley-validate class="form-horizontal form-label-left">
                                        <input type="hidden" id="update_category_order" name="update_category_order" required value='1' />
                                        <div class="form-group" style="overflow-y:scroll;height:400px;">
                                            <input type="hidden" name="row_order" id="row_order" required readonly />
                                            <ol id="sortable-row">
                                                <?php foreach ($cat as $category) { ?>
                                                    <li id=<?php echo $category["id"]; ?>>
                                                        <?php
                                                        if (!empty($category["image"])) {
                                                            echo "<big>" . $category["row_order"] . ".</big> &nbsp;<img src='images/category/$category[image]' height=30 > " . $category["category_name"];
                                                        } else {
                                                            echo "<big>" . $category["row_order"] . ".</big> &nbsp;<img src='images/logo-half.png' height=30 > " . $category["category_name"];
                                                        }
                                                        ?>
                                                    </li>
                                                <?php } ?>
                                            </ol>
                                        </div>
                                        <div class="ln_solid"></div>
                                        <div class="form-group">
                                            <div class="col-md-6 col-sm-6 col-xs-12">
                                                <button type="submit" id="submit_btn" class="btn btn-success">Save Order</button>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div style="display:none;" id="result"></div>
                                        </div>
                                    </form>
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
        <script src="//code.jquery.com/ui/1.11.2/jquery-ui.js"></script>
        <script>
            $(function () {
                $("#sortable-row").sortable({
                    placeholder: "ui-state-highlight"
                });
            });
            $('#category_form').on('submit', function (e) {
                e.preventDefault();
                var selectedLanguage = new Array();
                $('ol#sortable-row li').each(function () {
                    selectedLanguage.push($(this).attr("id"));
                });
                $("#row_order").val(selectedLanguage);
                var formData = new FormData(this);
                if ($("#category_form").validate().form()) {
                    $.ajax({
                        type: 'POST',
                        url: $(this).attr('action'),
                        data: formData,
                        beforeSend: function () {
                            $('#submit_btn').html('Please wait..');
                        },
                        cache: false,
                        contentType: false,
                        processData: false,
                        success: function (result) {
                            alert("Category order updated!");
                            //    						$('#result').html(result);
                            //    						$('#result').show().delay(5000).fadeOut();
                            $('#submit_btn').html('Save Order');
                            window.location = "";
                        }
                    });
                }
            });

        </script>
        <script>
            var type =<?= $type ?>;
            $('#filter_language').on('change', function (e) {
                var lang_id = $('#filter_language').val();
                $.ajax({
                    type: 'POST',
                    url: "db_operations.php",
                    data: 'get_categories_of_language=1&sortable=sortable&language_id=' + lang_id + '&type=' + type,
                    success: function (result) {
                        $('#sortable-row').html(result);
                    }
                });
            });
        </script>
    </body>

</html>