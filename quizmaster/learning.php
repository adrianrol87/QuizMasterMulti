<?php
session_start();
if (!isset($_SESSION['id']) && !isset($_SESSION['username'])) {
    header("location:index.php");
    return false;
    exit();
}
$type = '2';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <!-- Meta, title, CSS, favicons, etc. -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Learning Zone | <?= ucwords($_SESSION['company_name']) ?> - Admin Panel </title>
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
                                <h2>Learning Zone</h2>
                                <div class="clearfix"></div>
                            </div>
                            <div class="x_content">
                                <form id="register_form" method="POST" action="db_operations.php" data-parsley-validate="" class="form-horizontal form-label-left" novalidate="novalidate">
                                    <input type="hidden" id="add_learning" name="add_learning" required value="1" aria-required="true">
                                    <input type="hidden" name="type" value="<?= $type ?>" required>
                                    <?php
                                    $db->sql("SET NAMES 'utf8'");
                                    $sql = "SELECT * FROM category WHERE type=" . $type . " ORDER BY id DESC";
                                    $db->sql($sql);
                                    $categories = $db->getResult();
                                    if ($fn->is_language_mode_enabled()) {
                                    ?>
                                        <div class="form-group row">
                                            <?php
                                            $sql = "SELECT * FROM `languages` ORDER BY id DESC";
                                            $db->sql($sql);
                                            $languages = $db->getResult();
                                            ?>
                                            <div class="col-md-6 col-sm-12">
                                                <label>Language</label>
                                                <select id="language_id" name="language_id" required class="form-control">
                                                    <option value="">Select language</option>
                                                    <?php foreach ($languages as $language) { ?>
                                                        <option value='<?= $language['id'] ?>'><?= $language['language'] ?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                            <div class="col-md-6 col-sm-12">
                                                <label>Main Category</label>
                                                <select id="category" name="category" required class="form-control">
                                                    <option value=''>Select Options</option>
                                                    <?php foreach ($categories as $category) { ?>
                                                        <option value='<?= $category['id'] ?>'><?= $category['category_name'] ?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        </div>
                                    <?php } else { ?>
                                        <div class="form-group row">
                                            <div class="col-md-12 col-sm-12">
                                                <label>Main Category</label>
                                                <select id="category" name="category" required class="form-control">
                                                    <option value=''>Select Options</option>
                                                    <?php foreach ($categories as $category) { ?>
                                                        <option value='<?= $category['id'] ?>'><?= $category['category_name'] ?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        </div>
                                    <?php } ?>
                                    <div class="form-group row">
                                        <div class="col-md-6 col-sm-12">
                                            <label>Title</label>
                                            <input name="title" type="text" placeholder="Enter Title" require class="form-control" />
                                        </div>
                                        <div class="col-md-6 col-sm-12">
                                            <label>Youtube Video Id</label>
                                            <input name="video_id" type="text" placeholder="Enter Youtube Video Id" class="form-control" />
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <div class="col-md-6 col-sm-12">
                                            <label>Upload PDF File</label>
                                            <input name="pdf_file" type="file" class="form-control" accept="application/pdf" />
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <div class="col-md-12 col-sm-12">
                                            <label>Detail</label>
                                            <textarea name='detail' id='detail' class='form-control'></textarea>
                                        </div>
                                    </div>
                                    <div class="ln_solid"></div>
                                    <div class="form-group">
                                        <div class="col-md-6 col-sm-6 col-xs-12">
                                            <button type="submit" id="submit_btn" class="btn btn-success">Create Now</button>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-offset-3 col-md-4" style="display:none;" id="result">
                                        </div>
                                    </div>
                                </form>
                                <div class="col-md-12">
                                    <hr>
                                </div>
                            </div>
                            <div class='row'>
                                <div class='col-md-12'>
                                    <h2>Learning Zone <small>View / Update / Delete</small></h2>
                                </div>
                                <div class='col-md-12'>
                                    <?php if ($fn->is_language_mode_enabled()) { ?>
                                        <div class='col-md-3'>
                                            <select id='filter_language' class='form-control' required>
                                                <option value="">Select language</option>
                                                <?php foreach ($languages as $language) { ?>
                                                    <option value='<?= $language['id'] ?>'><?= $language['language'] ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                        <div class='col-md-3'>
                                            <select id='filter_category' class='form-control' required>
                                                <option value=''>Select Category</option>
                                            </select>
                                        </div>
                                    <?php } else { ?>
                                        <div class='col-md-3'>
                                            <select id='filter_category' class='form-control' required>
                                                <option value=''>Select Category</option>
                                                <?php foreach ($categories as $row) { ?>
                                                    <option value='<?= $row['id'] ?>'><?= $row['category_name'] ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    <?php } ?>
                                    <div class='col-md-3'>
                                        <button class='btn btn-primary btn-block' id='filter_btn'>Filter Data</button>
                                    </div>
                                </div>
                                <div class='col-md-12'>
                                    <hr>
                                </div>
                            </div>
                            <div id="toolbar">
                                <div class="col-md-3">
                                    <button class="btn btn-danger btn-sm" id="delete_multiple_learning" title="Delete Selected Questions"><em class='fa fa-trash'></em></button>
                                </div>
                            </div>
                            <table aria-describedby="mydesc" class='table-striped' id='learnings' data-toggle="table" data-url="get-list.php?table=learning_zone" data-sort-name="id" data-sort-order="desc" data-click-to-select="true" data-side-pagination="server" data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false" data-toolbar="#toolbar" data-mobile-responsive="true" data-maintain-selected="true" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-show-export="false" data-export-types='["txt","excel"]' data-export-options='{
                                       "fileName": "learning-list-<?= date('d-m-y') ?>",
                                       "ignoreColumn": ["state"]	
                                       }' data-query-params="queryParams_1">
                                <thead>
                                    <tr>
                                        <th scope="col" data-field="state" data-checkbox="true"></th>
                                        <th scope="col" data-field="id" data-sortable="true">ID</th>
                                        <th scope="col" data-field="status" data-sortable="false">Status</th>
                                        <th scope="col" data-field="category" data-sortable="true" data-visible='false'>Category</th>
                                        <?php if ($fn->is_language_mode_enabled()) { ?>
                                            <th scope="col" data-field="language_id" data-sortable="true" data-visible='false'>Language ID</th>
                                            <th scope="col" data-field="language" data-sortable="true" data-visible='true'>Language</th>
                                        <?php } ?>
                                        <th scope="col" data-field="title" data-sortable="true">Title</th>
                                        <th scope="col" data-field="video_id" data-sortable="true">Youtube Video Id</th>
                                        <th scope="col" data-field="detail" data-sortable="false" data-visible='false'>Detail</th>
                                        <th scope="col" data-field="pdf_file" data-sortable="false" data-visible='false'>PDF File</th>
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
    <div class="modal fade" id='editDataModal' tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="myModalLabel">Edit Learning Zone</h4>
                </div>
                <div class="modal-body">
                    <form id="update_form" method="POST" action="db_operations.php" data-parsley-validate class="form-horizontal form-label-left">
                        <input type='hidden' name="learning_id" id="learning_id" value='' />
                        <input type='hidden' name="update_learning" id="update_learning" value='1' />
                        <?php
                        $db->sql("SET NAMES 'utf8'");
                        if ($fn->is_language_mode_enabled()) {
                        ?>
                            <div class="form-group">

                                <div class="col-md-12 col-sm-12 col-xs-12">
                                    <?php
                                    $sql = "SELECT * FROM `languages` ORDER BY id DESC";
                                    $db->sql($sql);
                                    $languages = $db->getResult();
                                    ?>
                                    <label>Language</label>
                                    <select id="update_language_id" name="language_id" required class="form-control col-md-7 col-xs-12">
                                        <option value="">Select language</option>
                                        <?php foreach ($languages as $language) { ?>
                                            <option value='<?= $language['id'] ?>'><?= $language['language'] ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                        <?php } ?>
                        <div class="form-group">
                            <div class="col-md-12 col-sm-12 col-xs-12">
                                <label>Category</label>
                                <select name='category' id='edit_category' class='form-control' required>
                                    <option value=''>Select Main Category</option>
                                    <?php foreach ($categories as $row) { ?>
                                        <option value='<?= $row['id'] ?>'><?= $row['category_name'] ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-md-12 col-sm-12">
                                <label class="control-label">Title</label>
                                <input id="title" name="title" type="text" class="form-control" placeholder="Enter Title" required>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-md-12 col-sm-12">
                                <label>Youtube Video Id</label>
                                <input id="video_id" name="video_id" type="text" placeholder="Enter Youtube Video Id" class="form-control" />
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-md-12 col-sm-12">
                                <label>Upload PDF File</label>
                                <input name="edit_pdf_file" type="file" class="form-control" accept="application/pdf" />
                                <input type="hidden" name="edit_pdf" id="edit_pdf">
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-md-12 col-sm-12">
                                <label class="control-label">Detail</label>
                                <textarea id="edit_detail" name="detail" class="form-control"></textarea>
                            </div>
                        </div>

                        <div class="ln_solid"></div>
                        <div class="form-group">
                            <div class="col-md-6 col-sm-6 col-xs-12 col-md-offset-3">
                                <button type="submit" id="update_btn" class="btn btn-success">Update</button>
                            </div>
                        </div>
                    </form>
                    <div class="row">
                        <div class="col-md-offset-3 col-md-8" style="display:none;" id="update_result"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id='editStatusModal' tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="myModalLabel">Edit Learning Zone</h4>
                </div>
                <div class="modal-body">
                    <form id="update_status_form" method="POST" action="db_operations.php" data-parsley-validate class="form-horizontal form-label-left">
                        <input type='hidden' name="learning_status_id" id="learning_status_id" value='' />
                        <input type='hidden' name="update_learning_status" id="update_learning_status" value='1' />

                        <div class="form-group">
                            <label class="control-label col-md-3 col-sm-3 col-xs-12">Status</label>
                            <div class="col-md-6 col-sm-6 col-xs-12">
                                <div id="status" class="btn-group">
                                    <label class="btn btn-default" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default">
                                        <input type="radio" name="status" value="0"> Deactive
                                    </label>
                                    <label class="btn btn-primary" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default">
                                        <input type="radio" name="status" value="1"> Active
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="ln_solid"></div>
                        <div class="form-group">
                            <div class="col-md-6 col-sm-6 col-xs-12 col-md-offset-3">
                                <button type="submit" id="update_btn1" class="btn btn-success">Update</button>
                            </div>
                        </div>
                    </form>
                    <div class="row">
                        <div class="col-md-offset-3 col-md-8" style="display:none;" id="result1"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- footer content -->
    <?php include 'footer.php'; ?>
    <!-- /footer content -->
    </div>

    <!-- jQuery -->

    <script>
        var type = <?= $type ?>;
        <?php if ($fn->is_language_mode_enabled()) { ?>
            $('#language_id').on('change', function(e) {
                var language_id = $('#language_id').val();
                $.ajax({
                    type: 'POST',
                    url: "db_operations.php",
                    data: 'get_categories_of_language=1&language_id=' + language_id + '&type=' + type,
                    beforeSend: function() {
                        $('#category').html('Please wait..');
                    },
                    success: function(result) {
                        $('#category').html(result);
                    }
                });
            });
            $('#update_language_id').on('change', function(e, row_language_id, row_category) {
                var language_id = $('#update_language_id').val();
                $.ajax({
                    type: 'POST',
                    url: "db_operations.php",
                    data: 'get_categories_of_language=1&language_id=' + language_id + '&type=' + type,
                    beforeSend: function() {
                        $('#edit_category').html('Please wait..');
                    },
                    success: function(result) {
                        $('#edit_category').html(result).trigger("change");
                        if (language_id == row_language_id && row_category != 0)
                            $('#edit_category').val(row_category);
                    }
                });
            });
            $('#filter_language').on('change', function(e) {
                var language_id = $('#filter_language').val();
                $.ajax({
                    type: 'POST',
                    url: "db_operations.php",
                    data: 'get_categories_of_language=1&language_id=' + language_id + '&type=' + type,
                    beforeSend: function() {
                        $('#filter_category').html('<option>Please wait..</option>');
                    },
                    success: function(result) {
                        $('#filter_category').html(result);
                        $('#filter_subcategory').html('<option>Select Sub Category</option>');
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
        window.actionEvents = {
            'click .edit-data': function(e, value, row, index) {
                console.log(row);

                // var hrefValue = document.getElementById("get_pdf").attr("data-name");
                // console.log(hrefValue);


                $('#learning_id').val(row.id);
                <?php if ($fn->is_language_mode_enabled()) { ?>
                    if (row.language_id == 0) {
                        $('#update_language_id').val(row.language_id);
                        $('#edit_category').html(category_options);
                        $('#edit_category').val(row.category);
                    } else {
                        $('#update_language_id').val(row.language_id).trigger("change", [row.language_id, row.category]);
                    }
                <?php } else { ?> $('#edit_category').val(row.category);
                <?php } ?> $('#title').val(row.title);
                $('#video_id').val(row.video_id);
                var detail = tinyMCE.get('edit_detail').setContent(row.detail);
                $('#edit_detail').val(detail);

                $('#edit_pdf').val(row.pdf_name);
            },
            'click .edit-status': function(e, value, row, index) {
                $('#learning_status_id').val(row.id);
                $("input[name=status][value=1]").prop('checked', true);
                if ($(row.status).text() == 'Deactive')
                    $("input[name=status][value=0]").prop('checked', true);
            }
        };
    </script>
    <script>
        $(document).on('click', '.delete-data', function() {
            if (confirm('Are you sure? Want to delete learning? All related questions will also be deleted')) {
                id = $(this).data("id");
                pdf = $(this).data("pdf");

                $.ajax({
                    url: 'db_operations.php',
                    type: "get",
                    data: 'id=' + id + '&pdf=' + pdf + '&delete_learning=1',
                    success: function(result) {
                        if (result == 1) {
                            $('#learnings').bootstrapTable('refresh');
                        } else
                            alert('Error! Learning could not be deleted');
                    }
                });
            }
        });
    </script>
    <script>
        function queryParams_1(p) {
            return {
                "language": $('#filter_language').val(),
                "category": $('#filter_category').val(),
                limit: p.limit,
                sort: p.sort,
                order: p.order,
                offset: p.offset,
                search: p.search
            };
        }
    </script>
    <script>
        var $table = $('#learnings');
        $('#toolbar').find('select').change(function() {
            $table.bootstrapTable('refreshOptions', {
                exportDataType: $(this).val()
            });
        });
    </script>

    <script>
        $('#register_form').validate({
            rules: {
                category: "required",
            }
        });
    </script>
    <script type="text/javascript">
        $(document).ready(function() {
            tinymce.init({
                selector: '#detail',
                height: 150,
                menubar: true,
                plugins: [
                    'advlist autolink lists charmap print preview anchor textcolor',
                    'searchreplace visualblocks code fullscreen',
                    'insertdatetime table contextmenu paste code help wordcount'
                ],
                toolbar: 'insert | undo redo |  formatselect | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | help',
                setup: function(editor) {
                    editor.on("change keyup", function(e) {
                        editor.save();
                        $(editor.getElement()).trigger('change');
                    });
                }
            });
        });
    </script>

    <script type="text/javascript">
        $(document).on('focusin', function(e) {
            if ($(event.target).closest(".mce-window").length) {
                e.stopImmediatePropagation();
            }
        });
        tinymce.init({
            selector: '#edit_detail',
            height: 150,
            menubar: true,
            plugins: [
                'advlist autolink lists charmap print preview anchor textcolor',
                'searchreplace visualblocks code fullscreen',
                'insertdatetime table contextmenu paste code help wordcount'
            ],
            toolbar: 'insert | undo redo |  formatselect | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | help',
            setup: function(editor) {
                editor.on("change keyup", function(e) {
                    editor.save();
                    $(editor.getElement()).trigger('change');
                });
            }
        });
    </script>
    <script>
        $('#register_form').on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            if ($("#register_form").validate().form()) {
                <?php if ($fn->is_language_mode_enabled()) { ?>
                    var language = $('#language_id').val();
                <?php } ?>
                var category = $('#category').val();
                $.ajax({
                    type: 'POST',
                    url: $(this).attr('action'),
                    data: formData,
                    beforeSend: function() {
                        $('#submit_btn').html('Please wait..');
                        $('#submit_btn').prop('disabled', true);
                    },
                    cache: false,
                    contentType: false,
                    processData: false,
                    success: function(result) {
                        $('#submit_btn').html('Create Now');
                        $('#result').html(result);
                        $('#result').show().delay(4000).fadeOut();
                        $('#register_form')[0].reset();
                        $('#category').val(category);
                        <?php if ($fn->is_language_mode_enabled()) { ?>
                            $('#language_id').val(language);
                        <?php } ?>
                        $('#submit_btn').prop('disabled', false);
                        $('#learnings').bootstrapTable('refresh');
                    }
                });
            }
        });
    </script>

    <script>
        $('#update_form').validate({
            rules: {
                edit_category: "required",
                title: "required",
            }
        });
    </script>
    <script>
        $('#update_form').on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            if ($("#update_form").validate().form()) {
                $.ajax({
                    type: 'POST',
                    url: $(this).attr('action'),
                    data: formData,
                    beforeSend: function() {
                        $('#update_btn').html('Please wait..');
                    },
                    cache: false,
                    contentType: false,
                    processData: false,
                    success: function(result) {
                        $('#update_result').html(result);
                        $('#update_result').show().delay(3000).fadeOut();
                        $('#update_btn').html('Update');
                        $('#learnings').bootstrapTable('refresh');
                        setTimeout(function() {
                            $('#editDataModal').modal('hide');
                        }, 4000);
                    }
                });
            }
        });
    </script>

    <script>
        $('#filter_btn').on('click', function(e) {
            $('#learnings').bootstrapTable('refresh');
        });
        $('#delete_multiple_learning').on('click', function(e) {
            sec = 'tbl_learning';
            is_image = 0;
            table = $('#learnings');
            delete_button = $('#delete_multiple_learning');
            selected = table.bootstrapTable('getAllSelections');
            ids = "";
            $.each(selected, function(i, e) {
                ids += e.id + ",";
            });
            ids = ids.slice(0, -1); // removes last comma character
            if (ids == "") {
                alert("Please select some learning to delete!");
            } else {
                if (confirm("Are you sure you want to delete all selected learning?")) {
                    $.ajax({
                        type: 'GET',
                        url: "db_operations.php",
                        data: 'delete_multiple=1&ids=' + ids + '&sec=' + sec + '&is_image=' + is_image,
                        beforeSend: function() {
                            delete_button.html('<i class="fa fa-spinner fa-pulse"></i>');
                        },
                        success: function(result) {
                            if (result == 1) {
                                alert("Learning deleted successfully");
                            } else {
                                alert("Could not delete learning. Try again!");
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