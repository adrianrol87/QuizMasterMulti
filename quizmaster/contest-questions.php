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
        <title>Questions for Contest | <?= ucwords($_SESSION['company_name']) ?> - Admin Panel </title>
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
                                    <h2>Questions for Contest <small>View / Update / Delete</small></h2>
                                    <div class="clearfix"></div>
                                </div>
                                <div class="x_content">
                                    <div class="row">
                                        <form id="question_form" method="POST" action="db_operations.php" data-parsley-validate="" class="form-horizontal form-label-left" novalidate="novalidate">
                                            <h4 class="col-md-offset-1"><b>Create a Question</b></h4>
                                            <input type="hidden" id="add_contest_question" name="add_contest_question" required="" value="1" aria-required="true">
                                            <div class="form-group">
                                                <label class="control-label col-md-1 col-sm-3 col-xs-12" for="contest_id">Contest</label>
                                                <div class="col-md-10 col-sm-6 col-xs-12">
                                                    <?php
                                                    $db->sql("SET NAMES 'utf8'");
                                                    $sql = "SELECT id, name FROM contest WHERE prize_status=0 order by id desc";
                                                    $db->sql($sql);
                                                    $res = $db->getResult();
                                                    ?>
                                                    <select name='contest_id' id='contest_id' class='form-control'>
                                                        <option value=''>Select any contest</option>
                                                        <?php foreach ($res as $row) { ?>
                                                            <option value='<?= $row['id'] ?>'><?= $row['name'] ?></option>
                                                        <?php } ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label col-md-1 col-sm-3 col-xs-12" for="question">Question</label>
                                                <div class="col-md-10 col-sm-6 col-xs-12">
                                                    <textarea id="question" name="question" class="form-control" required></textarea>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label col-md-1 col-sm-3 col-xs-12" for="image">Image <small>(Optional)</small></label>
                                                <div class="col-md-10 col-sm-6 col-xs-12">
                                                    <input type='file' class="form-control" name="image" id="image">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label col-md-1 col-sm-3 col-xs-12" for="answer type">Question Type</label>
                                                <div class="col-md-8 col-sm-6 col-xs-12">                                                     
                                                    <div id="status" class="btn-group">
                                                        <label class="btn btn-default" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default">
                                                            <input type="radio" name="question_type" value="1" checked=""> Options 
                                                        </label>
                                                        <label class="btn btn-default" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default">
                                                            <input type="radio" name="question_type" value="2"> True / False
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
                                                    <input id="a" class="form-control" type="text" name="a">
                                                </div>
                                                <label for="b" class="control-label col-md-1 col-sm-3 col-xs-12">B</label>
                                                <div class="col-md-5 col-sm-6 col-xs-12">
                                                    <input id="b" class="form-control" type="text" name="b">
                                                </div>
                                            </div>
                                            <div id="tf">
                                                <div class="form-group" >
                                                    <label for="c" class="control-label col-md-1 col-sm-3 col-xs-12">C</label>
                                                    <div class="col-md-4 col-sm-6 col-xs-12">
                                                        <input id="c" class="form-control" type="text" name="c">
                                                    </div>
                                                    <label for="d" class="control-label col-md-1 col-sm-3 col-xs-12">D</label>
                                                    <div class="col-md-5 col-sm-6 col-xs-12">
                                                        <input id="d" class="form-control" type="text" name="d">
                                                    </div>
                                                </div>
                                                <?php if ($fn->is_option_e_mode_enabled()) { ?>
                                                    <div class="form-group">
                                                        <label for="e" class="control-label col-md-1 col-sm-3 col-xs-12">E </label>
                                                        <div class="col-md-4 col-sm-6 col-xs-12">
                                                            <input id="e" class="form-control" type="text" name="e">
                                                        </div>
                                                        <label for="d" class="control-label col-md-1 col-sm-3 col-xs-12"></label>
                                                        <div class="col-md-5 col-sm-6 col-xs-12">
                                                        </div>
                                                    </div>
                                                <?php } ?>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label col-md-1 col-sm-3 col-xs-12" for="answer">Answer</label>
                                                <div class="col-md-10 col-sm-6 col-xs-12">
                                                    <select name='answer' id='answer' class='form-control'>
                                                        <option value=''>Select Right Answer</option>
                                                        <option value='a'>A</option>
                                                        <option value='b'>B</option>
                                                        <option class='ntf' value='c'>C</option>
                                                        <option class='ntf' value='d'>D</option>
                                                        <?php if ($fn->is_option_e_mode_enabled()) { ?>
                                                            <option class='ntf' value='e'>E</option>
                                                        <?php } ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label col-md-1 col-sm-3 col-xs-12" for="description">Note</label>
                                                <div class="col-md-10 col-sm-6 col-xs-12">
                                                    <textarea id="note" name="note" class="form-control"></textarea>
                                                </div>
                                            </div>

                                            <div class="ln_solid"></div>
                                            <div class="form-group">
                                                <div class="col-md-6 col-sm-6 col-xs-12">
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
                                    <div class="row">
                                        <div class='col-md-12'>
                                            <h2>Questions of Contest <small>View / Update / Delete</small></h2>
                                        </div>
                                        <?php
                                        $db->sql("SELECT id,name FROM contest GROUP BY id,name");
                                        $res = $db->getResult();
                                        ?>
                                        <div class="col-md-3 col-sm-6 col-xs-12">
                                            <select name="contest_filter" id="contest_filter" class="form-control">
                                                <option value="0">Select Contest</option>
                                                <?php
                                                foreach ($res as $route) {
                                                    echo "<option value=" . $route['id'] . ">" . $route['name'] . "</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3 col-sm-6 col-xs-12">
                                            <button class='btn btn-primary btn-block' id='filter_btn'>Filter Questions</button>
                                        </div>
                                    </div>
                                    <div class="ln_solid"></div>
                                    <div id="toolbar">
                                        <div class="col-md-3">
                                            <button class="btn btn-danger btn-sm" id="delete_multiple_questions" title="Delete Selected Questions"><em class='fa fa-trash'></em></button>
                                        </div>                                                
                                    </div>
                                    <table class='table-striped' id='contest_questions'
                                           data-toggle="table"
                                           data-url="get-list.php?table=contest_questions"
                                           data-click-to-select="true"
                                           data-side-pagination="server"
                                           data-pagination="true"
                                           data-page-list="[5, 10, 20, 50, 100, 200]"
                                           data-search="true" data-show-columns="true"
                                           data-show-refresh="true" data-trim-on-search="false"
                                           data-sort-name="id" data-sort-order="desc"
                                           data-mobile-responsive="true"
                                           data-toolbar="#toolbar" data-show-export="true"
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
                                                <th scope="col" data-field="contest_id" data-sortable="true" data-visible='false'>Contest ID</th>
                                                <th scope="col" data-field="contest_name" data-sortable="true">Contest Name</th>
                                                <th scope="col" data-field="image" data-sortable="false">Image</th>
                                                <th scope="col" data-field="question" data-sortable="true">Question</th>
                                                <th scope="col" data-field="question_type" data-sortable="true" data-visible='false'>Question Type</th>
                                                <th scope="col" data-field="optiona" data-sortable="true">Option A</th>
                                                <th scope="col" data-field="optionb" data-sortable="true">Option B</th>
                                                <th scope="col" data-field="optionc" data-sortable="true">Option C</th>
                                                <th scope="col" data-field="optiond" data-sortable="true">Option D</th>
                                                <?php if ($fn->is_option_e_mode_enabled()) { ?>
                                                    <th scope="col" data-field="optione" data-sortable="true">Option E</th>
                                                <?php } ?>
                                                <th scope="col" data-field="answer" data-sortable="true">Answer</th>
                                                <th scope="col" data-field="note" data-sortable="false" data-visible='false'>Note</th>
                                                <th scope="col" data-field="operate" data-sortable="false" data-events="actionEvents">Operate</th>
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
            <div class="modal fade" id='editQuestionModal' tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            <h4 class="modal-title" id="myModalLabel">Edit Question Details</h4>
                        </div>
                        <div class="modal-body">
                            <form id="update_form"  method="POST" action ="db_operations.php" data-parsley-validate class="form-horizontal form-label-left">
                                <input type='hidden' name="question_id" id="question_id" value=''/>
                                <input type='hidden' name="update_contest_question" id="update_contest_question" value='1'/>
                                <input type='hidden' name="image_url" id="image_url" value=''/>

                                <div class="form-group">
                                    <label class="control-label col-md-1 col-sm-3 col-xs-12" for="contest_id">Contest</label>
                                    <div class="col-md-10 col-sm-6 col-xs-12">
                                        <select name="contest_id" id="update_contest_id" class="form-control">
                                            <?php foreach ($res as $row) { ?>
                                                <option value="<?= $row['id'] ?>"><?= $row['name'] ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="control-label col-md-1 col-sm-3 col-xs-12" for="question">Question</label>
                                    <div class="col-md-10 col-sm-6 col-xs-12">
                                        <textarea type="text" id="edit_question" name="question" required class="form-control" aria-required="true"></textarea>
                                    </div>
                                </div> 
                                <div class="form-group">
                                    <label class="col-md-5 col-sm-3 col-xs-12" for="image">Image for Question <small>( Leave it blank for no change )</small></label>
                                    <div class="col-md-10 col-md-offset-1 col-sm-6 col-xs-12">
                                        <input type="file" id="edit_image" name="image" class="form-control" aria-required="true">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="control-label col-md-1 col-sm-3 col-xs-12" for="answer type">Question Type</label>
                                    <div class="col-md-8 col-sm-6 col-xs-12">                                                     
                                        <div id="status" class="btn-group">
                                            <label class="btn btn-default" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default">
                                                <input type="radio" name="edit_question_type" value="1" checked=""> Options 
                                            </label>
                                            <label class="btn btn-default" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default">
                                                <input type="radio" name="edit_question_type" value="2"> True / False
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
                                        <input id="edit_a" class="form-control col-md-7 col-xs-12" type="text" name="a">
                                    </div>
                                    <label for="b" class="control-label col-md-1 col-sm-3 col-xs-12">B</label>
                                    <div class="col-md-5 col-sm-6 col-xs-12">
                                        <input id="edit_b" class="form-control col-md-7 col-xs-12" type="text" name="b">
                                    </div>
                                </div>
                                <div id="edit_tf">
                                    <div class="form-group" >
                                        <label for="c" class="control-label col-md-1 col-sm-3 col-xs-12">C</label>
                                        <div class="col-md-4 col-sm-6 col-xs-12">
                                            <input id="edit_c" class="form-control" type="text" name="c">
                                        </div>
                                        <label for="d" class="control-label col-md-1 col-sm-3 col-xs-12">D</label>
                                        <div class="col-md-5 col-sm-6 col-xs-12">
                                            <input id="edit_d" class="form-control" type="text" name="d">
                                        </div>
                                    </div>
                                    <?php if ($fn->is_option_e_mode_enabled()) { ?>
                                        <div class="form-group">
                                            <label for="e" class="control-label col-md-1 col-sm-3 col-xs-12">E</label>
                                            <div class="col-md-4 col-sm-6 col-xs-12">
                                                <input id="edit_e" class="form-control" type="text" name="e">
                                            </div>
                                            <label class="control-label col-md-1 col-sm-3 col-xs-12"></label>
                                            <div class="col-md-5 col-sm-6 col-xs-12"></div>
                                        </div>
                                    <?php } ?>
                                </div>
                                <div class="form-group">
                                    <label class="control-label col-md-1 col-sm-3 col-xs-12" for="answer">Answer</label>
                                    <div class="col-md-10 col-sm-6 col-xs-12">
                                        <select name="answer" id="edit_answer" class="form-control" required>
                                            <option value="">Select Right Answer</option>
                                            <option value="a">A</option>
                                            <option value="b">B</option>
                                            <option class='edit_ntf' value="c">C</option>
                                            <option class='edit_ntf' value="d">D</option>
                                            <?php if ($fn->is_option_e_mode_enabled()) { ?>
                                                <option class='edit_ntf' value='e'>E</option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="control-label col-md-1 col-sm-3 col-xs-12" for="description">Note</label>
                                    <div class="col-md-10 col-sm-6 col-xs-12">
                                        <textarea id="edit_note" name="edit_note" class="form-control"></textarea>
                                    </div>
                                </div>

                                <div class="ln_solid"></div>
                                <div class="form-group">
                                    <div class="col-md-6 col-sm-6 col-xs-12 col-md-offset-3">
                                        <button type="submit" id="update_btn" class="btn btn-success">Update Question</button>
                                    </div>
                                </div>
                            </form>
                            <div class="row"><div  class="col-md-offset-3 col-md-8" style ="display:none;" id="update_result"></div></div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- footer content -->
            <?php include 'footer.php'; ?>
            <!-- /footer content -->
        </div>
    </div>
    <!-- jQuery -->
    <script>
        $('input[name="question_type"]').on("click", function (e) {
            var question_type = $(this).val();
            if (question_type == "2") {
                $('#tf').hide('fast');
                $('#a').val("<?php echo $config['true_value'] ?>");
                $('#b').val("<?php echo $config['false_value'] ?>");
                $('.ntf').hide('fast');
            } else {
                $('#a').val('');
                $('#b').val('');
                $('#tf').show('fast');
                $('.ntf').show('fast');
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
        $('#delete_multiple_questions').on('click', function (e) {
            sec = 'contest_questions';
            is_image = 1;
            table = $('#contest_questions');
            delete_button = $('#delete_multiple_questions');
            selected = table.bootstrapTable('getAllSelections');
            // alert(selected[0].id);
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
    <script>
        $('#question_form').validate({
            rules: {
                question: "required",
                contest_id: "required",
                a: "required",
                b: "required",
                c: "required",
                d: "required",
                answer: "required",
            }
        });
    </script>
    <script>
        $('#question_form').on('submit', function (e) {
            e.preventDefault();
            var formData = new FormData(this);
            if ($("#question_form").validate().form()) {
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
                        $('#submit_btn').html('Create Now');
                        $('#result').html(result);
                        $('#result').show().delay(8000).fadeOut();
                        $('#question_form')[0].reset();
                        $('#submit_btn').prop('disabled', false);
                        $('#contest_questions').bootstrapTable('refresh');
                    }
                });
            }
        });
    </script>

    <script>
        function queryParams_1(p) {
            return {
                "contest_filter": $('#contest_filter').val(),
                limit: p.limit,
                sort: p.sort,
                order: p.order,
                offset: p.offset,
                search: p.search
            };
        }
        $('#filter_btn').on('click', function (e) {
            $('#contest_questions').bootstrapTable('refresh');
        });

    </script>
    <script>
        window.actionEvents = {
            'click .edit-question': function (e, value, row, index) {
                //alert(JSON.stringify(row));                
                $('#question_id').val(row.id);
                $('#image_url').val($(this).data('image'));
                $('#edit_question').val(row.question);
                $('#update_contest_id').val(row.contest_id);
                var question_type = row.question_type;
                if (question_type == "2") {
                    $("input[name=edit_question_type][value=2]").prop('checked', true);
                    $('#edit_tf').hide('fast');
                    $('#edit_a').val(row.optiona);
                    $('#edit_b').val(row.optionb);
                    $('.edit_ntf').hide('fast');
                } else {
                    $("input[name=edit_question_type][value=1]").prop('checked', true);
                    $('#edit_a').val(row.optiona);
                    $('#edit_b').val(row.optionb);
                    $('#edit_c').val(row.optionc);
                    $('#edit_d').val(row.optiond);
<?php if ($fn->is_option_e_mode_enabled()) { ?>
                        $('#edit_e').val(row.optione);
<?php } ?>
                    $('#edit_tf').show('fast');
                    $('.edit_ntf').show('fast');
                }
                $('#edit_a').val(row.optiona);
                $('#edit_b').val(row.optionb);
                $('#edit_c').val(row.optionc);
                $('#edit_d').val(row.optiond);
<?php if ($fn->is_option_e_mode_enabled()) { ?>
                    $('#edit_e').val(row.optione);
<?php } ?>
                $('#edit_answer').val(row.answer.toLowerCase());
                $('#edit_note').val(row.note);
            }
        };
    </script>
    <script>
        $('#update_form').validate({
            rules: {
                question: "required",
                contest_id: "required",
                a: "required",
                b: "required",
                c: "required",
                d: "required",
                answer: "required",
            }
        });
    </script>
    <script>
        $('#update_form').on('submit', function (e) {
            e.preventDefault();
            var formData = new FormData(this);
            if ($("#update_form").validate().form()) {
                $.ajax({
                    type: 'POST',
                    url: $(this).attr('action'),
                    data: formData,
                    beforeSend: function () {
                        $('#update_btn').html('Please wait..');
                    },
                    cache: false,
                    contentType: false,
                    processData: false,
                    success: function (result) {
                        $('#update_result').html(result);
                        $('#update_result').show().delay(3000).fadeOut();
                        $('#update_btn').html('Update Question');
                        $('#contest_questions').bootstrapTable('refresh');
                        setTimeout(function () {
                            $('#editQuestionModal').modal('hide');
                        }, 4000);
                    }
                });
            }
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
                    data: 'id=' + id + '&image=' + image + '&delete_contest_question=1',
                    success: function (result) {
                        if (result == 1) {
                            $('#contest_questions').bootstrapTable('refresh');
                        } else
                            alert('Error! Question could not be deleted');
                    }
                });
            }
        });
    </script>
</body>

</html>
