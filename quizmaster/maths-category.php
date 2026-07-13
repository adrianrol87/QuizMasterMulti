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
    <title>Create and Manage Main Category | <?= ucwords($_SESSION['company_name']) ?> - Admin Panel </title>
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
                                <h2>Create Main Category</h2>
                                <div class="clearfix"></div>
                            </div>
                            <div class="x_content">
                                <div class='row'>
                                    <div class='col-md-12 col-sm-12'>
                                        <form id="category_form" method="POST" action="db_operations.php" class="form-horizontal form-label-left" enctype="multipart/form-data">
                                            <input type="hidden" id="add_category" name="add_category" required="" value="1" aria-required="true">
                                            <input type="hidden" name="type" value="<?= $type ?>" required>
                                            <?php if ($fn->is_language_mode_enabled()) { ?>
                                                <div class="form-group row">
                                                    <div class="col-md-12 col-sm-12">
                                                        <?php
                                                        $db->sql("SET NAMES 'utf8'");
                                                        $sql = "SELECT * FROM `languages` ORDER BY id DESC";
                                                        $db->sql($sql);
                                                        $languages = $db->getResult();
                                                        ?>
                                                        <label for="language">Language</label>
                                                        <select id="language_id" name="language_id" required class="form-control">
                                                            <option value="">Select language</option>
                                                            <?php foreach ($languages as $language) { ?>
                                                                <option value='<?= $language['id'] ?>'><?= $language['language'] ?></option>
                                                            <?php } ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            <?php } ?>
                                            <div class="form-group row">
                                                <div class="col-md-6 col-sm-12">
                                                    <label for="name">Category Name</label>
                                                    <input type="text" id="name" name="name" required class="form-control">
                                                </div>
                                                <div class="col-md-6 col-sm-12">
                                                    <label for="image">Image</label>
                                                    <input type='file' name="image" id="image" class="form-control">
                                                </div>

                                            </div>

                                            <div class="ln_solid"></div>
                                            <div id="result"></div>
                                            <div class="form-group">
                                                <div class="col-md-6 col-sm-6 col-xs-12">
                                                    <button type="submit" id="submit_btn" class="btn btn-warning">Add New</button>
                                                </div>
                                            </div>
                                        </form>
                                        <div class="col-md-12">
                                            <hr>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class='col-sm-12'>
                                        <h2>Categories <small>View / Update / Delete</small></h2>
                                        <?php if ($fn->is_language_mode_enabled()) { ?>
                                            <div class='col-md-4'>
                                                <select id='filter_language' class='form-control' required>
                                                    <option value="">Select language</option>
                                                    <?php foreach ($languages as $language) { ?>
                                                        <option value='<?= $language['id'] ?>'><?= $language['language'] ?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                            <div class='col-md-4'>
                                                <button class='btn btn-primary btn-block' id='filter_btn'>Filter Category</button>
                                            </div>
                                        <?php } ?>
                                        <div class='col-md-12'>
                                            <hr>
                                        </div>
                                    </div>
                                    <div class='col-md-12 col-sm-12'>
                                        <?php
                                        $sql = "SELECT * FROM `languages` ORDER BY id DESC";
                                        $db->sql($sql);
                                        $languages = $db->getResult();
                                        ?>

                                        <div class='row'>
                                            <div id="toolbar">
                                                <button class="btn btn-danger btn-sm" id="delete_multiple_categories" title="Delete Selected Categories"><em class='fa fa-trash'></em></button>
                                            </div>

                                            <table aria-describedby="mydesc" class='table-striped' id='category_list' data-toggle="table" data-url="get-list.php?table=category" data-click-to-select="true" data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false" data-sort-name="row_order" data-sort-order="asc" data-toolbar="#toolbar" data-mobile-responsive="true" data-maintain-selected="true" data-show-export="false" data-export-types='["txt","excel"]' data-export-options='{
                                                       "fileName": "category-list-<?= date('d-m-y') ?>",
                                                       "ignoreColumn": ["state"]	
                                                       }' data-query-params="queryParams">
                                                <thead>
                                                    <tr>
                                                        <th scope="col" data-field="state" data-checkbox="true"></th>
                                                        <th scope="col" data-field="id" data-sortable="true">ID</th>
                                                        <th scope="col" data-field="status" data-sortable="false">Status</th>

                                                        <?php if ($fn->is_language_mode_enabled()) { ?>
                                                            <th scope="col" data-field="language_id" data-sortable="true" data-visible="false">Language ID</th>
                                                            <th scope="col" data-field="language" data-sortable="true">Language</th>
                                                        <?php } ?>
                                                        <th scope="col" data-field="row_order" data-visible='false' data-sortable="true">Order</th>
                                                        <th scope="col" data-field="category_name" data-sortable="true">Category Name</th>
                                                        <th scope="col" data-field="image" data-sortable="false">Image</th>
                                                        <th scope="col" data-field="no_of_que" data-sortable="false">Total Question</th>
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
                </div>
            </div>
        </div>
        <!-- /page content -->
        <div class="modal fade" id='editCategoryModal' tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel">
            <div class="modal-dialog modal-md" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title" id="myModalLabel">Edit Main Category</h4>
                    </div>
                    <div class="modal-body">
                        <form id="update_form" method="POST" action="db_operations.php" data-parsley-validate class="form-horizontal form-label-left">
                            <input type='hidden' name="update_category" id="update_category" value='1' />
                            <input type="hidden" name="type" value="<?= $type ?>" required>
                            <input type='hidden' name="category_id" id="category_id" value='' />
                            <input type='hidden' name="image_url" id="image_url" value='' />
                            <?php if ($fn->is_language_mode_enabled()) { ?>
                                <div class="form-group">
                                    <label class="" for="name">Language</label>
                                    <select id="update_language_id" name="language_id" required class="form-control">
                                        <option value="">Select language</option>
                                        <?php foreach ($languages as $language) { ?>
                                            <option value='<?= $language['id'] ?>'><?= $language['language'] ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            <?php } ?>
                            <div class="form-group">
                                <label for="name">Category Name</label>
                                <input type="text" name="name" id="update_name" placeholder="Category Name" class='form-control' required>
                            </div>
                            <div class="form-group">
                                <label for="image">Image <small>( Leave it blank for no change )</small></label>
                                <input type="file" name="image" id="update_image" class="form-control" aria-required="true">
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
                        <h4 class="modal-title" id="myModalLabel">Edit Category</h4>
                    </div>
                    <div class="modal-body">
                        <form id="update_status_form" method="POST" action="db_operations.php" data-parsley-validate class="form-horizontal form-label-left">
                            <input type='hidden' name="category_status_id" id="category_status_id" value='' />
                            <input type='hidden' name="update_category_status" id="update_category_status" value='1' />

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
        $('#filter_btn').on('click', function(e) {
            $('#category_list').bootstrapTable('refresh');
        });
        $('#delete_multiple_categories').on('click', function(e) {
            sec = 'category';
            is_image = 1;
            table = $('#category_list');
            delete_button = $('#delete_multiple_categories');
            selected = table.bootstrapTable('getAllSelections');

            ids = "";
            $.each(selected, function(i, e) {
                ids += e.id + ",";
            });
            ids = ids.slice(0, -1); // removes last comma character

            if (ids == "") {
                alert("Please select some categories to delete!");
            } else {
                if (confirm("Are you sure you want to delete all selected categories?")) {
                    $.ajax({
                        type: 'GET',
                        url: "db_operations.php",
                        data: 'delete_multiple=1&ids=' + ids + '&sec=' + sec + '&is_image=' + is_image,
                        beforeSend: function() {
                            delete_button.html('<i class="fa fa-spinner fa-pulse"></i>');
                        },
                        success: function(result) {
                            if (result == 1) {
                                alert("Categories deleted successfully");
                            } else {

                                alert("Could not delete Categories. Try again!");
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
        var $table = $('#category_list');
        $('#toolbar').find('select').change(function() {
            $table.bootstrapTable('refreshOptions', {
                exportDataType: $(this).val()
            });
        });
    </script>

    <script>
        window.actionEvents = {
            'click .edit-category': function(e, value, row, index) {
                // alert('You click remove icon, row: ' + JSON.stringify(row));
                var regex = /<img.*?src="(.*?)"/;
                var src = regex.exec(row.image)[1];
                <?php if ($fn->is_language_mode_enabled()) { ?>
                    $('#update_language_id').val(row.language_id);
                <?php } ?>
                $('#category_id').val(row.id);
                $('#update_name').val(row.category_name);
                $('#image_url').val(src);
            },
            'click .edit-status': function(e, value, row, index) {
                console.log(row.status);
                $('#category_status_id').val(row.id);
                $("input[name=status][value=1]").prop('checked', true);
                if ($(row.status).text() == 'Deactive')
                    $("input[name=status][value=0]").prop('checked', true);
            }
        };
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
                        $('#update_image').val('');
                        // $('#update_form')[0].reset();
                        $('#category_list').bootstrapTable('refresh');
                        setTimeout(function() {
                            $('#editCategoryModal').modal('hide');
                        }, 4000);
                    }
                });
            }
        });
    </script>
    <script>
        function queryParams(p) {
            return {
                "language": $('#filter_language').val(),
                type: <?= $type ?>,
                limit: p.limit,
                sort: p.sort,
                order: p.order,
                offset: p.offset,
                search: p.search
            };
        }
    </script>
    <script>
        $('#category_form').validate({
            rules: {
                name: "required"
            }
        });
    </script>
    <script>
        $('#category_form').on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            if ($("#category_form").validate().form()) {
                if (confirm('Are you sure? Want to create Category')) {
                    $.ajax({
                        type: 'POST',
                        url: $(this).attr('action'),
                        data: formData,
                        beforeSend: function() {
                            $('#submit_btn').html('Please wait..');
                        },
                        cache: false,
                        contentType: false,
                        processData: false,
                        success: function(result) {
                            $('#result').html(result);
                            $('#result').show().delay(4000).fadeOut();
                            $('#submit_btn').html('Submit');
                            $('#category_form')[0].reset();
                            $('#category_list').bootstrapTable('refresh');
                        }
                    });
                }
            }
        });
    </script>
    <script>
        $(document).on('click', '.delete-category', function() {
            if (confirm('Are you sure? Want to delete category? All related questions and sub categories will also be deleted')) {
                id = $(this).data("id");
                image = $(this).data("image");
                $.ajax({
                    url: 'db_operations.php',
                    type: "get",
                    data: 'id=' + id + '&image=' + image + '&delete_category=1',
                    success: function(result) {
                        if (result == 1) {
                            $('#category_list').bootstrapTable('refresh');
                        } else
                            alert('Error! Category could not be deleted');
                    }
                });
            }
        });
    </script>
</body>

</html>