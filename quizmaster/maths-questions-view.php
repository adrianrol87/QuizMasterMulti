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
       
        <style>
            .test .cke_top,
            .test .cke_bottom{
                display: none;
            }
            .test .cke_chrome{
                border: none;
            }
        </style>
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
                                    <h2>Questions for Quiz <small>View / Update / Delete</small></h2>
                                    <div class="clearfix"></div>
                                </div>
                                <div class="x_content">                                   
                                    <div class='row'>
                                        <div class='col-md-12'>                                           
                                            <?php if ($fn->is_language_mode_enabled()) { ?>
                                                <div class='col-md-3'>
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
                                                <div class='col-md-3'>
                                                    <select id='filter_category' class='form-control' required>
                                                        <option value=''>Select Main Category</option>
                                                    </select>
                                                </div>
                                            <?php } else { ?>
                                                <?php
                                                $sql = "SELECT id, category_name FROM category WHERE type=" . $type . " ORDER BY id DESC";
                                                $db->sql($sql);
                                                $categories = $db->getResult();
                                                ?>
                                                <div class='col-md-3'>
                                                    <select id='filter_category' class='form-control' required>
                                                        <option value=''>Select Main Category</option>
                                                        <?php foreach ($categories as $row) { ?>
                                                            <option value='<?= $row['id'] ?>'><?= $row['category_name'] ?></option>
                                                        <?php } ?>
                                                    </select>
                                                </div>
                                            <?php } ?>
                                            <div class='col-md-3'>
                                                <select id='filter_subcategory' class='form-control' required>
                                                    <option value=''>Select Sub Category</option>
                                                </select>
                                            </div>

                                            <div class='col-md-3'>
                                                <button class='btn btn-primary btn-block' id='filter_btn'>Filter Questions</button>
                                            </div>
                                        </div>
                                        <div class='col-md-12'><hr></div>
                                    </div>
                                    <div id="toolbar">
                                        <div class="col-md-3">
                                            <button class="btn btn-danger btn-sm" id="delete_multiple_questions" title="Delete Selected Questions"><em class='fa fa-trash'></em></button>
                                        </div>                                        
                                    </div>
                                    <table aria-describedby="mydesc" class='table-striped' id='questions'
                                           data-toggle="table" data-url="get-list.php?table=maths_question"
                                           data-sort-name="id" data-sort-order="desc"
                                           data-click-to-select="true" data-side-pagination="server"                                           
                                           data-search="true" data-show-columns="true"
                                           data-show-refresh="true" data-trim-on-search="false"                                                    
                                           data-toolbar="#toolbar" data-mobile-responsive="true" data-maintain-selected="true"  
                                           data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]"  
                                           data-show-export="false" data-export-types='["txt","excel"]'
                                           data-export-options='{
                                           "fileName": "questions-list-<?= date('d-m-y') ?>",
                                           "ignoreColumn": ["state"]	
                                           }'
                                           data-query-params="queryParams_1"
                                           >
                                        <thead>
                                            <tr>
                                                <th scope="col" data-field="state" data-checkbox="true"></th>
                                                <th scope="col" data-field="id" data-sortable="true">ID</th>
                                                <th scope="col" data-field="category" data-sortable="true" data-visible='false'>Category</th>
                                                <th scope="col" data-field="subcategory" data-sortable="true" data-visible='false'>Sub Category</th>
                                                <?php if ($fn->is_language_mode_enabled()) { ?>
                                                    <th scope="col" data-field="language_id" data-sortable="true" data-visible='false'>Language ID</th>
                                                    <th scope="col" data-field="language" data-sortable="true" data-visible='true'>Language</th>
                                                <?php } ?>
                                                <th scope="col" data-field="image" data-sortable="false">Image</th>
                                                <th scope="col" data-class="test" data-field="question" data-sortable="false">Question</th>
                                                <th scope="col" data-field="question_type" data-sortable="true" data-visible='false'>Question Type</th>
                                                <th scope="col" data-class="test" data-field="optiona" data-sortable="false">Option A</th>
                                                <th scope="col" data-class="test" data-field="optionb" data-sortable="false">Option B</th>
                                                <th scope="col" data-class="test" data-field="optionc" data-sortable="false">Option C</th>
                                                <th scope="col" data-class="test" data-field="optiond" data-sortable="false">Option D</th>
                                                <?php if ($fn->is_option_e_mode_enabled()) { ?>
                                                    <th scope="col" data-class="test" data-field="optione" data-sortable="false">Option E</th>
                                                <?php } ?>
                                                <th scope="col" data-field="answer" data-sortable="true" data-visible='false'>Answer</th>
                                                <th scope="col" data-class="test" data-field="note" data-sortable="false" data-visible='false'>Note</th>
                                                <th scope="col" data-field="operate" data-events="actionEvents">Operate</th>
                                            </tr>
                                        </thead>
                                    </table>
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
            var type =<?= $type ?>;
<?php if ($fn->is_language_mode_enabled()) { ?>
                $('#filter_language').on('change', function (e) {
                    var language_id = $('#filter_language').val();
                    $.ajax({
                        type: 'POST',
                        url: "db_operations.php",
                        data: 'get_categories_of_language=1&language_id=' + language_id + '&type=' + type,
                        beforeSend: function () {
                            $('#filter_category').html('<option>Please wait..</option>');
                        },
                        success: function (result) {
                            $('#filter_category').html(result);
                            $('#filter_subcategory').html('<option>Select Sub Category</option>');
                        }
                    });
                });
<?php } ?>
        </script>

        <script>
            $('#filter_category').on('change', function (e) {
                var category_id = $('#filter_category').val();
                $.ajax({
                    type: 'POST',
                    url: "db_operations.php",
                    data: 'get_subcategories_of_category=1&category_id=' + category_id,
                    beforeSend: function () {
                        $('#filter_subcategory').html('<option>Please wait..</option>');
                    },
                    success: function (result) {
                        $('#filter_subcategory').html(result);
                    }
                });
            });
        </script>

        <script>
            function queryParams_1(p) {
                return {
                    "language": $('#filter_language').val(),
                    "category": $('#filter_category').val(),
                    "subcategory": $('#filter_subcategory').val(),
                    limit: p.limit,
                    sort: p.sort,
                    order: p.order,
                    offset: p.offset,
                    search: p.search
                };
            }
        </script>

        <script>
            var $table = $('#questions');
            $('#toolbar').find('select').change(function () {
                $table.bootstrapTable('refreshOptions', {
                    exportDataType: $(this).val()
                });
            });
        </script> 
        <script>
            $(document).on('click', '.delete-question', function () {
                if (confirm('Are you sure? Want to delete question')) {
                    id = $(this).data("id");
                    image = $(this).data("image");
                    $.ajax({
                        url: 'db_operations.php',
                        type: "get",
                        data: 'id=' + id + '&image=' + image + '&delete_maths_question=1',
                        success: function (result) {
                            if (result == 1) {
                                $('#questions').bootstrapTable('refresh');
                            } else
                                alert('Error! Question could not be deleted');
                        }
                    });
                }
            });
        </script>  
        <script>
            $('#filter_btn').on('click', function (e) {
                $('#questions').bootstrapTable('refresh');
            });
            $('#delete_multiple_questions').on('click', function (e) {
                sec = 'tbl_maths_question';
                is_image = 1;
                table = $('#questions');
                delete_button = $('#delete_multiple_questions');
                selected = table.bootstrapTable('getAllSelections');
                ids = "";
                $.each(selected, function (i, e) {
                    ids += e.id + ",";
                });
                ids = ids.slice(0, -1); // removes last comma character
                if (ids == "") {
                    alert("Please select some questions to delete!");
                } else {
                    if (confirm("Are you sure you want to delete all selected questions?")) {
                        $.ajax({
                            type: 'GET',
                            url: "db_operations.php",
                            data: 'delete_multiple=1&ids=' + ids + '&sec=' + sec + '&is_image=' + is_image,
                            beforeSend: function () {
                                delete_button.html('<i class="fa fa-spinner fa-pulse"></i>');
                            },
                            success: function (result) {
                                if (result == 1) {
                                    alert("Questions deleted successfully");
                                } else {
                                    ("Could not delete questions. Try again!");
                                }
                                delete_button.html('<i class="fa fa-trash"></i>');
                                table.bootstrapTable('refresh');
                            }
                        });
                    }
                }
            });
        </script>       

    </body>
</html>