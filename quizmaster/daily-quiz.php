<?php
session_start();
if (!isset($_SESSION['id']) && !isset($_SESSION['username']))
    header("location:index.php");
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <!-- Meta, title, CSS, favicons, etc. -->
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Daily Quiz | <?= ucwords($_SESSION['company_name']) ?> - Admin Panel </title>
        <?php include 'include-css.php'; ?>
        <style>
            #sortable-row li { margin-bottom:4px; padding:10px; background-color:#ededed;cursor:move;} 
            #sortable-row li.ui-state-highlight { height: 1.0em; background-color:#F0F0F0;border:#ccc 2px dotted;}
        </style>
    </head>
    <body class="nav-md">
        <div class="container body">
            <div class="main_container">
                <?php include 'sidebar.php'; ?>
                <!-- page content -->
                <div class="right_col" role="main">
                    <!-- top tiles -->
                    <br>
                    <div class="row">
                        <div class="col-md-12 col-sm-12 col-xs-12">
                            <div class="x_panel">
                                <div class="x_title">
                                    <h2>Daily Quiz <small>Create New Quiz</small></h2>
                                    <div class="clearfix"></div>
                                </div>
                                <div class="x_content">                                   

                                    <div class='row'>
                                        <?php $db->sql("SET NAMES 'utf8'"); ?>
                                        <?php
                                        if ($fn->is_language_mode_enabled()) {
                                            $sql = "SELECT * FROM `languages` ORDER BY id DESC";
                                            $db->sql($sql);
                                            $languages = $db->getResult();
                                            ?>
                                            <div class='col-md-3'>
                                                <select id='filter_language_id' class='form-control' required>
                                                    <option value=''>Select Language</option>
                                                    <?php foreach ($languages as $language) { ?>
                                                        <option value='<?= $language['id'] ?>'><?= $language['language'] ?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        <?php } ?>
                                        <div class='col-md-3'>
                                            <?php
                                            $sql = "SELECT * FROM category ORDER BY id + 0 ASC";
                                            $db->sql($sql);
                                            $res = $db->getResult();
                                            ?>
                                            <select id='filter_category' class='form-control' required>
                                                <option value=''>Select Main Category</option>
                                                <?php foreach ($res as $row) { ?>
                                                    <option value='<?= $row['id'] ?>'><?= $row['category_name'] ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                        <div class='col-md-3'>
                                            <select id='filter_subcategory' class='form-control' required>
                                                <option value=''>Select Sub Category</option>
                                            </select>
                                        </div>
                                        <div class='col-md-3'>
                                            <button class='btn btn-primary btn-block' id='filter_btn'>Filter Questions</button>
                                        </div>
                                    </div>
                                    <div class='col-md-12'>
                                        <hr>
                                    </div>
                                    <h2>Create New Quiz</h2>
                                    <div class='col-md-12'>
                                        <hr>
                                    </div>
                                    <div class='row'>
                                        <div class='col-md-6'>
                                            <h4><strong>Select Questions for Daily Quiz</strong></h4>
                                            <table aria-describedby="mydesc" class='table-striped' id='questions'
                                                   data-toggle="table"
                                                   data-url="get-list.php?table=question"
                                                   data-click-to-select="true"
                                                   data-side-pagination="server"
                                                   data-pagination="true"
                                                   data-page-list="[5, 10, 20, 50, 100, 200]"
                                                   data-search="true" data-show-columns="true"
                                                   data-show-refresh="true" data-trim-on-search="false"
                                                   data-sort-name="id" data-sort-order="desc"
                                                   data-mobile-responsive="true"
                                                   data-toolbar="#toolbar" data-show-export="false"
                                                   data-maintain-selected="true"
                                                   data-export-types='["txt","excel"]'
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
                                                        <th scope="col" data-field="question" data-sortable="true">Question</th>
                                                    </tr>
                                                </thead>
                                            </table>
                                        </div>
                                        <div class="col-md-1">
                                            <label class="control-label" for="add_question">Add</label>
                                            <a href="#" id="add_question" class="btn btn-success form-control"><i class="fa fa-chevron-circle-right"></i></a>
                                        </div>
                                        <div class='col-md-5'>
                                            <h4><strong>Selected Questions</strong></h4>
                                            <?php $db->sql("SET NAMES 'utf8'"); ?>

                                            <form id="daily_quiz_form" method="POST" action="db_operations.php" data-parsley-validate class="form-horizontal form-label-left">
                                                <input type="hidden" id="update_daily_quiz_order" name="update_daily_quiz_order" required value='1'/>

                                                <div class="form-group">
                                                    <?php
                                                    if ($fn->is_language_mode_enabled()) {
                                                        $sql = "SELECT * FROM `languages` ORDER BY id DESC";
                                                        $db->sql($sql);
                                                        $languages = $db->getResult();
                                                        ?>
                                                        <label class="control-label " for="language">Language</label><br>
                                                        <div class='row'>
                                                            <div class="col-md-12">
                                                                <select id="language_id" name="language_id" required class="form-control">
                                                                    <?php foreach ($languages as $language) { ?>
                                                                        <option value='<?= $language['id'] ?>'><?= $language['language'] ?></option>
                                                                    <?php } ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    <?php } else { ?>
                                                        <input type = "hidden" name="language_id" id="language_id" value="0" required/> 
                                                    <?php } ?>
                                                    <label class="control-label" for="add_question">Title</label>

                                                    <input type="date" id="daily_quiz_date" name="daily_quiz_date" value="<?= date('Y-m-d') ?>" class='form-control'/>

                                                    <div id='questions_block' class="form-group" style="overflow-y:scroll;height:500px;">
                                                        <input type = "hidden" name="question_ids" id="question_ids" required readonly/> 
                                                        <ol id="sortable-row">
                                                        </ol>
                                                    </div>
                                                    <div class="ln_solid"></div>
                                                    <div class="form-group">
                                                        <div class="col-md-12">
                                                            <button type="submit" id="submit_btn" class="btn btn-success">Save</button>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div style ="display:none;" id="result"></div>
                                                    </div>
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
        </div>
        <!-- jQuery -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.blockUI/2.70/jquery.blockUI.min.js"></script>
        <script src="https://code.jquery.com/ui/1.11.2/jquery-ui.js"></script>
        <script>
            $(document).ready(function () {
                var language_id = $('#language_id').val();
                load_sortable_ui('<?= date('Y-m-d') ?>', language_id);
            });

            $('#daily_quiz_date, #language_id').on('change', function (e) {
                e.preventDefault();
                date = $('#daily_quiz_date').val();
                var language_id = $('#language_id').val();
                load_sortable_ui(date, language_id);
            });

            function load_sortable_ui(date, language_id) {
                var selected_date = date;

                $.ajax({
                    type: 'POST',
                    url: "db_operations.php",
                    data: 'get_selected_date=1&selected_date=' + selected_date + '&language_id=' + language_id,
                    beforeSend: function () {
                        $('#questions_block').block({message: '<img src="images/loading.gif"/><h4>Please wait  Loading.. .</h4>'});
                    },
                    success: function (response) {
                        var obj = JSON.parse(response);

                        $('#sortable-row').html(obj.questions_list);
                        if (obj.language_id != '') {
                            $('#language_id').val(obj.language_id);
                        }
                        $('#questions_block').unblock();
                    }
                });
            }
        </script>
        <script>
            $('#daily_quiz_form').on('submit', function (e) {

                e.preventDefault();
                var selectedLanguage = new Array();
                $('ol#sortable-row li').each(function () {
                    selectedLanguage.push($(this).attr("id"));
                });
                $("#question_ids").val(selectedLanguage);
                var formData = new FormData(this);

                if ($("#question_ids").val() == '') {
                    alert('Please Select some questions and proceed ');
                    return false;
                }
                if ($("#daily_quiz_form").validate().form()) {
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
                            $('#result').html(result);
                            $('#result').show().delay(5000).fadeOut();
                            $('#submit_btn').html('Save');
                        }
                    });
                }
            });
        </script>
        <script>
            $(function () {
                $("#sortable-row").sortable({
                    placeholder: "ui-state-highlight"
                });
            });
            var $table = $('#questions');
            $('#add_question').on('click', function (e) {
                e.preventDefault();
                var questions = $table.bootstrapTable('getSelections');
                li = '';
                $.each(questions, function (i, v) {
                    li = $("<li id='" + questions[i].id + "'' class='ui-state-default'/>").text(questions[i].id + ". " + questions[i].question).append("<a class='btn btn-danger btn-xs remove-row pull-right'>x</a>");
                    var pasteItem = checkList("sortable-row", li);
                    if (pasteItem) {
                        $("#sortable-row").append(li);
                        $("#sortable-row").sortable('refresh');
                    }
                });
                $('#no_questions').remove();
            });
            function checkList(listName, newItem) {
                var dupl = false;
                $("#" + listName + " > li").each(function () {
                    if ($(this)[0] !== newItem[0]) {
                        if ($(this).html() == newItem.html()) {
                            dupl = true;
                        }
                    }
                });
                return !dupl;
            }
            $(document).on('click', '.remove-row', function (e) {
                e.preventDefault();
                $(this).closest('li').remove();
                $("#sortable-row").sortable('refresh');
            });
        </script>
        <script>
            $('#filter_btn').on('click', function (e) {
                $('#questions').bootstrapTable('refresh');
            });
            var $table = $('#questions');
            $('#toolbar').find('select').change(function () {
                $table.bootstrapTable('refreshOptions', {
                    exportDataType: $(this).val()
                });
            });
            function queryParams_1(p) {
                return {
                    "language": $('#filter_language_id').val(),
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
        <?php if ($fn->is_language_mode_enabled()) { ?>
            <script>
                $('#filter_language_id').on('change', function (e) {
                    var language_id = $('#filter_language_id').val();
                    $.ajax({
                        type: 'POST',
                        url: "db_operations.php",
                        data: 'get_categories_of_language=1&language_id=' + language_id,
                        beforeSend: function () {
                            $('#filter_category').html('<option>Please wait..</option>');
                        },
                        success: function (result) {
                            $('#filter_category').html(result);
                            $('#filter_subcategory').html('<option>Select Sub Category</option>');
                        }
                    });
                });
            </script>
        <?php } ?>

    </body>
</html>
