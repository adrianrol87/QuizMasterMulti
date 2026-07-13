<?php
session_start();
if (!isset($_SESSION['id']) && !isset($_SESSION['username'])) {
    header("location:index.php");
    return false;
    exit();
}
$type = '1';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <!-- Meta, title, CSS, favicons, etc. -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create and Manage Sub Category | <?= ucwords($_SESSION['company_name']) ?> - Admin Panel </title>
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
                                <h2>Create Sub Category</h2>
                                <div class="clearfix"></div>
                            </div>
                            <div class="x_content">
                                <div class='row'>
                                    <div class='col-md-12'>
                                        <form id="category_form" method="POST" action="db_operations.php" class="form-horizontal form-label-left" enctype="multipart/form-data">
                                            <input type="hidden" id="add_subcategory" name="add_subcategory" required="" value="1" aria-required="true">
                                            <?php
                                            $db->sql("SET NAMES 'utf8'");
                                            $sql = "SELECT * FROM category WHERE type=" . $type . " AND status=" . 1 . " ORDER BY id DESC";
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
                                                        <label for="language_id">Language</label>
                                                        <select id="language_id" name="language_id" required class="form-control">
                                                            <option value="">Select language</option>
                                                            <?php foreach ($languages as $language) { ?>
                                                                <option value='<?= $language['id'] ?>'><?= $language['language'] ?></option>
                                                            <?php } ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6 col-sm-12">
                                                        <label for="maincat_id">Main Category</label>
                                                        <select id="maincat_id" name="maincat_id" required class="form-control">
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
                                                        <label for="maincat_id">Main Category</label>
                                                        <select id="maincat_id" name="maincat_id" required class="form-control">
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
                                                    <label for="name">Sub Category Name</label>
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

                                    <div class='row'>
                                        <div class='col-md-12'>
                                            <h2>Subcategories of Category <small>View / Update / Delete</small></h2>
                                        </div>
                                        <?php if ($fn->is_language_mode_enabled()) { ?>
                                            <div class='col-md-4'>
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
                                            <div class='col-md-4'>
                                                <select id='filter_category' class='form-control' required>
                                                    <option value=''>Select Main Category</option>
                                                </select>
                                            </div>
                                        <?php } else { ?>
                                            <div class='col-md-4'>
                                                <?php
                                                $sql = "SELECT id,`category_name` FROM `category` WHERE type=" . $type . " AND status=1 ORDER BY id desc";
                                                $db->sql($sql);
                                                $categories = $db->getResult();
                                                ?>
                                                <select id='filter_category' class='form-control' required>
                                                    <option value=''>Select Main Category</option>
                                                    <?php foreach ($categories as $row) { ?>
                                                        <option value='<?= $row['id'] ?>'><?= $row['category_name'] ?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        <?php } ?>
                                        <div class='col-md-3'>
                                            <button class='btn btn-primary btn-block' id='filter_btn'>Filter Subcategory</button>
                                        </div>
                                        <div class='col-md-12'>
                                            <hr>
                                        </div>
                                    </div>
                                    <div class='col-md-12'>
                                        <div id="toolbar">
                                            <div class="col-md-3">
                                                <button class="btn btn-danger btn-sm" id="delete_multiple_subcategories" title="Delete Selected Subcategories"><em class='fa fa-trash'></em></button>
                                            </div>
                                        </div>
                                        <table aria-describedby="mydesc" class='table-striped' id='category_list' data-toggle="table" data-url="get-list.php?table=subcategory" data-click-to-select="true" data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false" data-sort-name="row_order" data-sort-order="asc" data-toolbar="#toolbar" data-mobile-responsive="true" data-maintain-selected="true" data-show-export="false" data-export-types='["txt","excel"]' data-export-options='{
                                                    "fileName": "subcategory-list-<?= date('d-m-y') ?>",
                                                    "ignoreColumn": ["state"]	
                                                    }' data-query-params="queryParams">
                                            <thead>
                                                <tr>
                                                    <th scope="col" data-field="state" data-checkbox="true"></th>
                                                    <th scope="col" data-field="id" data-sortable="true">ID</th>
                                                    <th scope="col" data-field="row_order" data-visible='false' data-sortable="true">Order</th>
                                                    <?php if ($fn->is_language_mode_enabled()) { ?>
                                                        <th scope="col" data-field="language_id" data-sortable="true" data-visible='false'>Language ID</th>
                                                        <th scope="col" data-field="language" data-sortable="true" data-sort-name="l.language">Language</th>
                                                    <?php } ?>
                                                    <th scope="col" data-field="maincat_id" data-sortable="true" data-visible='false'>Main Category ID</th>
                                                    <th scope="col" data-field="category_name" data-sortable="true">Main Category</th>
                                                    <th scope="col" data-field="subcategory_name" data-sortable="true">Sub Category Name</th>
                                                    <th scope="col" data-field="image" data-sortable="false">Image</th>
                                                    <th scope="col" data-field="status" data-sortable="false">Status</th>
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
        <!-- /page content -->
        <div class="modal fade" id='editCategoryModal' tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel">
            <div class="modal-dialog modal-md" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title" id="myModalLabel">Edit Sub Category</h4>
                    </div>
                    <div class="modal-body">
                        <form id="update_form" method="POST" action="db_operations.php" data-parsley-validate class="form-horizontal form-label-left">
                            <input type='hidden' name="update_subcategory" id="update_subcategory" value='1' />
                            <input type='hidden' name="subcategory_id" id="subcategory_id" value='' />
                            <input type='hidden' name="image_url" id="image_url" value='' />
                            <?php if ($fn->is_language_mode_enabled()) { ?>
                                <div class="form-group">
                                    <label class="" for="update_language_id">Language</label>
                                    <select id="update_language_id" name="language_id" required class="form-control">
                                        <option value="">Select language</option>
                                        <?php foreach ($languages as $language) { ?>
                                            <option value='<?= $language['id'] ?>'><?= $language['language'] ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            <?php } ?>
                            <label class="" for="update_maincat_id">Main Category</label>
                            <select id="update_maincat_id" name="maincat_id" required class="form-control">
                                <option value=''>Select Options</option>
                                <?php foreach ($categories as $category) { ?>
                                    <option value='<?= $category['id'] ?>'><?= $category['category_name'] ?></option>
                                <?php } ?>
                            </select>
                            <div class="form-group">
                                <label>Sub Category Name</label>
                                <input type="text" name="name" id="update_name" placeholder="Category Name" class='form-control' required>
                            </div>
                            <div class="form-group">
                                <label class="" for="image">Image <small>( Leave it blank for no change )</small></label>
                                <input type="file" name="image" id="update_image" class="form-control" aria-required="true">
                            </div>
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
                            <input type="hidden" id="id" name="id">
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
        <!-- footer content -->
        <?php include 'footer.php'; ?>
        <!-- /footer content -->
    </div>

    <!-- jQuery -->
    <script>
        window.actionEvents = {
            'click .edit-subcategory': function(e, value, row, index) {
                // alert('You click remove icon, row: ' + JSON.stringify(row));
                var regex = /<img.*?src="(.*?)"/;
                var src = regex.exec(row.image)[1];
                $("input[name=status][value=1]").prop('checked', true);
                if ($(row.status).text() == 'Deactive')
                    $("input[name=status][value=0]").prop('checked', true);

                $('#subcategory_id').val(row.id);
                <?php if ($fn->is_language_mode_enabled()) { ?>
                    if (row.language_id == 0) {
                        $('#update_language_id').val(row.language_id);
                        $('#update_maincat_id').html(category_options);
                        $('#update_maincat_id').val(row.maincat_id);
                    } else {
                        $('#update_language_id').val(row.language_id).trigger("change", [row.language_id, row.maincat_id]);
                    }
                <?php } else { ?>
                    $('#update_maincat_id').val(row.maincat_id);
                <?php } ?>
                $('#update_name').val(row.subcategory_name);
                $('#image_url').val(src);
            }
        };
    </script>


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
                        $('#maincat_id').html('Please wait..');
                    },
                    success: function(result) {
                        // alert(result);
                        $('#maincat_id').html(result);
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
                        $('#update_maincat_id').html('Please wait..');
                    },
                    success: function(result) {
                        $('#update_maincat_id').html(result).trigger("change");
                        //alert(row_language_id);
                        if (language_id == row_language_id && row_category != 0)
                            $('#update_maincat_id').val(row_category).trigger("change", [row_category]);
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
        $(document).on('click', '.delete-subcategory', function() {
            if (confirm('Are you sure? Want to delete sub category? All related questions will also be deleted')) {
                id = $(this).data("id");
                image = $(this).data("image");
                $.ajax({
                    url: 'db_operations.php',
                    type: "get",
                    data: 'id=' + id + '&image=' + image + '&delete_subcategory=1',
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
    <script>
        var $table = $('#category_list');
        $('#toolbar').find('select').change(function() {
            $table.bootstrapTable('refreshOptions', {
                exportDataType: $(this).val()
            });
        });
    </script>
    <script>
        function queryParams(p) {
            return {
                "language": $('#filter_language').val(),
                "category": $('#filter_category').val(),
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
        $('#filter_btn').on('click', function(e) {
            $('#category_list').bootstrapTable('refresh');
        });
    </script>
    <script>
        $('#category_form').validate({
            rules: {
                name: "required",
                maincat_id: "required"
            }
        });
    </script>
    <script>
        $('#category_form').on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            if ($("#category_form").validate().form()) {
                if (confirm('Are you sure? Want to create Sub-Category')) {
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
        $('#delete_multiple_subcategories').on('click', function(e) {
            sec = 'subcategory';
            is_image = 1;
            table = $('#category_list');
            delete_button = $('#delete_multiple_subcategories');
            selected = table.bootstrapTable('getAllSelections');
            // alert(selected[0].id);
            ids = "";
            $.each(selected, function(i, e) {
                ids += e.id + ",";
            });
            ids = ids.slice(0, -1); // removes last comma character
            if (ids == "") {
                alert("Please select some subcategories to delete!");
            } else {
                if (confirm("Are you sure you want to delete all selected subcategories?")) {
                    $.ajax({
                        type: 'GET',
                        url: "db_operations.php",
                        data: 'delete_multiple=1&ids=' + ids + '&sec=' + sec + '&is_image=' + is_image,
                        beforeSend: function() {
                            delete_button.html('<i class="fa fa-spinner fa-pulse"></i>');
                        },
                        success: function(result) {

                            if (result == 1) {
                                alert("Subcategories deleted successfully");
                            } else {

                                alert("Could not delete subcategories. Try again!");
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