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
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Word Search Categories | <?= ucwords($_SESSION['company_name']) ?> - Admin Panel</title>
    <?php include 'include-css.php'; ?>
</head>
<body class="nav-md">
    <div class="container body">
        <div class="main_container">
            <?php include 'sidebar.php'; ?>
            <div class="right_col" role="main">
                <br />
                <div class="row">
                    <div class="col-md-12 col-sm-12 col-xs-12">
                        <div class="x_panel">
                            <div class="x_title">
                                <h2>Word Search Categories</h2>
                                <div class="clearfix"></div>
                            </div>
                            <div class="x_content">
                                <div class='row'>
                                    <div class='col-md-12 col-sm-12'>
                                        <form id="word_search_category_form" method="POST" action="db_operations.php" class="form-horizontal form-label-left" enctype="multipart/form-data">
                                            <input type="hidden" name="add_word_search_category" value="1">
                                            <?php if ($fn->is_language_mode_enabled()) { ?>
                                                <?php
                                                $db->sql("SET NAMES 'utf8'");
                                                $db->sql("SELECT * FROM `languages` ORDER BY id ASC");
                                                $languages = $db->getResult();
                                                ?>
                                                <div class="form-group row">
                                                    <div class="col-md-12 col-sm-12">
                                                        <label for="language_id">Language</label>
                                                        <select id="language_id" name="language_id" required class="form-control">
                                                            <option value="">Select language</option>
                                                            <?php foreach ($languages as $language) { ?>
                                                                <option value="<?= $language['id'] ?>"><?= $language['language'] ?></option>
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
                                                    <input type="file" name="image" id="image" class="form-control">
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <div class="col-md-4 col-sm-12">
                                                    <label for="word_search_category_plan">Plan</label>
                                                    <div id="status" class="btn-group">
                                                        <label class="btn btn-default" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default">
                                                            <input type="radio" name="word_search_cat_plan" value="1"> Paid
                                                        </label>
                                                        <label class="btn btn-default" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default">
                                                            <input type="radio" name="word_search_cat_plan" value="2" checked> Free
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-md-2 col-sm-12 word-search-paid-cat" style="display:none;">
                                                    <label for="word_search_category_amount">Amount</label>
                                                    <input type="number" name="word_search_category_amount" id="word_search_category_amount" class="form-control">
                                                    <input type="hidden" name="word_search_category_plan" id="word_search_category_plan" value="">
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
                                        <div class="col-md-12"><hr></div>
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
                                                        <option value="<?= $language['id'] ?>"><?= $language['language'] ?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                            <div class='col-md-4'>
                                                <button class='btn btn-primary btn-block' id='filter_btn'>Filter Category</button>
                                            </div>
                                        <?php } ?>
                                        <div class='col-md-12'><hr></div>
                                    </div>
                                    <div class='col-md-12 col-sm-12'>
                                        <div class='row'>
                                            <div id="toolbar">
                                                <button class="btn btn-danger btn-sm" id="delete_multiple_word_search_categories" title="Delete Selected Categories"><em class='fa fa-trash'></em></button>
                                            </div>

                                            <table aria-describedby="mydesc" class='table-striped' id='word_search_category_list'
                                                data-toggle="table"
                                                data-url="get-list.php?table=word_search_category"
                                                data-click-to-select="true"
                                                data-side-pagination="server"
                                                data-pagination="true"
                                                data-page-list="[5, 10, 20, 50, 100, 200]"
                                                data-search="true"
                                                data-show-columns="true"
                                                data-show-refresh="true"
                                                data-trim-on-search="false"
                                                data-sort-name="row_order"
                                                data-sort-order="asc"
                                                data-toolbar="#toolbar"
                                                data-mobile-responsive="true"
                                                data-maintain-selected="true"
                                                data-query-params="queryParams">
                                                <thead>
                                                    <tr>
                                                        <th scope="col" data-field="state" data-checkbox="true"></th>
                                                        <th scope="col" data-field="id" data-sortable="true">ID</th>
                                                        <?php if ($fn->is_language_mode_enabled()) { ?>
                                                            <th scope="col" data-field="language_id" data-sortable="true" data-visible="false">Language ID</th>
                                                            <th scope="col" data-field="language" data-sortable="true">Language</th>
                                                        <?php } ?>
                                                        <th scope="col" data-field="row_order" data-visible='false' data-sortable="true">Order</th>
                                                        <th scope="col" data-field="title" data-sortable="true">Category Name</th>
                                                        <th scope="col" data-field="image">Image</th>
                                                        <th scope="col" data-field="no_of_levels" data-sortable="false">Total Levels</th>
                                                        <th scope="col" data-field="plan" data-sortable="false">Plan</th>
                                                        <th scope="col" data-field="amount" data-sortable="false">Amount</th>
                                                        <th scope="col" data-field="status">Status</th>
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

            <div class="modal fade" id='editWordSearchCategoryModal' tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel">
                <div class="modal-dialog modal-md" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            <h4 class="modal-title">Edit Word Search Category</h4>
                        </div>
                        <div class="modal-body">
                            <form id="update_form" method="POST" action="db_operations.php" class="form-horizontal form-label-left" enctype="multipart/form-data">
                                <input type='hidden' name="update_word_search_category" value='1' />
                                <input type='hidden' name="word_search_category_id" id="word_search_category_id" value='' />
                                <input type='hidden' name="image_url" id="image_url" value='' />
                                <?php if ($fn->is_language_mode_enabled()) { ?>
                                    <div class="form-group">
                                        <label for="update_language_id">Language</label>
                                        <select id="update_language_id" name="language_id" required class="form-control">
                                            <option value="">Select language</option>
                                            <?php foreach ($languages as $language) { ?>
                                                <option value="<?= $language['id'] ?>"><?= $language['language'] ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                <?php } ?>
                                <div class="form-group">
                                    <label for="update_name">Category Name</label>
                                    <input type="text" name="name" id="update_name" class='form-control' required>
                                </div>
                                <div class="form-group">
                                    <label for="update_image">Image <small>(Leave it blank for no change)</small></label>
                                    <input type="file" name="image" id="update_image" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label for="update_word_search_category_plan">Plan</label>
                                    <div id="status" class="btn-group">
                                        <label class="btn btn-default" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default">
                                            <input type="radio" name="update_word_search_cat_plan" value="1"> Paid
                                        </label>
                                        <label class="btn btn-default" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default">
                                            <input type="radio" name="update_word_search_cat_plan" value="2"> Free
                                        </label>
                                    </div>
                                </div>
                                <div class="form-group word-search-update-amount" style="display:none;">
                                    <label for="update_word_search_category_amount">Amount</label>
                                    <input type="number" name="update_word_search_category_amount" id="update_word_search_category_amount" class="form-control">
                                    <input type="hidden" name="update_word_search_category_plan" id="update_word_search_category_plan" value="">
                                </div>
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

            <div class="modal fade" id='editWordSearchStatusModal' tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            <h4 class="modal-title">Edit Category Status</h4>
                        </div>
                        <div class="modal-body">
                            <form id="update_word_search_status_form" method="POST" action="db_operations.php" class="form-horizontal form-label-left">
                                <input type='hidden' name="word_search_category_status_id" id="word_search_category_status_id" value='' />
                                <input type='hidden' name="update_word_search_category_status" value='1' />
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

            <?php include 'footer.php'; ?>
        </div>
    </div>

    <script>
        $('#filter_btn').on('click', function() {
            $('#word_search_category_list').bootstrapTable('refresh');
        });

        $('#delete_multiple_word_search_categories').on('click', function() {
            var sec = 'word_search_category';
            var is_image = 1;
            var table = $('#word_search_category_list');
            var delete_button = $('#delete_multiple_word_search_categories');
            var selected = table.bootstrapTable('getAllSelections');
            var ids = "";

            $.each(selected, function(i, e) {
                ids += e.id + ",";
            });
            ids = ids.slice(0, -1);

            if (ids == "") {
                alert("Please select some categories to delete!");
            } else if (confirm("Are you sure you want to delete all selected categories?")) {
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
                            alert("Could not delete categories. Try again!");
                        }
                        delete_button.html('<i class="fa fa-trash"></i>');
                        table.bootstrapTable('refresh');
                    }
                });
            }
        });

        window.actionEvents = {
            'click .edit-word-search-category': function(e, value, row) {
                var regex = /<img.*?src="(.*?)"/;
                var src = regex.exec(row.image)[1];
                <?php if ($fn->is_language_mode_enabled()) { ?>
                    $('#update_language_id').val(row.language_id);
                <?php } ?>
                $('#word_search_category_id').val(row.id);
                $('#update_name').val(row.title);
                $('#update_word_search_category_amount').val(row.amount);
                $('#image_url').val(src);
                $("input[name=update_word_search_cat_plan][value='2']").prop("checked", row.plan == "Free" ? true : false);
                $("input[name=update_word_search_cat_plan][value='1']").prop("checked", row.plan == "Paid" ? true : false);
                $('#update_word_search_category_plan').val(row.plan);
                syncUpdateWordSearchCategoryPlan();
            },
            'click .edit-word-search-status': function(e, value, row) {
                $('#word_search_category_status_id').val(row.id);
                $("input[name=status][value=1]").prop('checked', true);
                if ($(row.status).text() == 'Deactive') {
                    $("input[name=status][value=0]").prop('checked', true);
                }
            }
        };

        function queryParams(p) {
            return {
                language: $('#filter_language').val(),
                limit: p.limit,
                sort: p.sort,
                order: p.order,
                offset: p.offset,
                search: p.search
            };
        }

        function syncCreateWordSearchCategoryPlan() {
            var plan = $("input:radio[name=word_search_cat_plan]:checked").val();
            if (plan == 2) {
                $('.word-search-paid-cat').hide();
                $('#word_search_category_plan').val("Free");
                $('#word_search_category_amount').val(0);
                $('#word_search_category_amount').prop('required', false);
            } else {
                $('.word-search-paid-cat').show();
                $('#word_search_category_plan').val("Paid");
                $('#word_search_category_amount').prop('required', true);
            }
        }

        function syncUpdateWordSearchCategoryPlan() {
            var plan = $("input:radio[name=update_word_search_cat_plan]:checked").val();
            if (plan == 2) {
                $('.word-search-update-amount').hide();
                $('#update_word_search_category_plan').val("Free");
                $('#update_word_search_category_amount').val(0);
                $('#update_word_search_category_amount').prop('required', false);
            } else {
                $('.word-search-update-amount').show();
                $('#update_word_search_category_plan').val("Paid");
                $('#update_word_search_category_amount').prop('required', true);
            }
        }

        $(document).ready(function() {
            syncCreateWordSearchCategoryPlan();
            syncUpdateWordSearchCategoryPlan();
        });

        $("input:radio[name=word_search_cat_plan]").on('change', syncCreateWordSearchCategoryPlan);
        $("input:radio[name=update_word_search_cat_plan]").on('change', syncUpdateWordSearchCategoryPlan);

        $('#word_search_category_form').on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);
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
                    $('#submit_btn').html('Add New');
                    $('#word_search_category_form')[0].reset();
                    $('#word_search_category_list').bootstrapTable('refresh');
                }
            });
        });

        $('#update_form').on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);
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
                    $('#word_search_category_list').bootstrapTable('refresh');
                    setTimeout(function() {
                        $('#editWordSearchCategoryModal').modal('hide');
                    }, 1500);
                }
            });
        });

        $('#update_word_search_status_form').on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            $.ajax({
                type: 'POST',
                url: $(this).attr('action'),
                data: formData,
                beforeSend: function() {
                    $('#update_btn1').html('Please wait..');
                },
                cache: false,
                contentType: false,
                processData: false,
                success: function(result) {
                    $('#result1').html(result);
                    $('#result1').show().delay(3000).fadeOut();
                    $('#update_btn1').html('Update');
                    $('#word_search_category_list').bootstrapTable('refresh');
                    setTimeout(function() {
                        $('#editWordSearchStatusModal').modal('hide');
                    }, 1500);
                }
            });
        });

        $(document).on('click', '.delete-word-search-category', function() {
            if (confirm('Are you sure? Want to delete category? All related levels and progress will also be deleted')) {
                var id = $(this).data("id");
                var image = $(this).data("image");
                $.ajax({
                    url: 'db_operations.php',
                    type: "get",
                    data: 'id=' + id + '&image=' + image + '&delete_word_search_category=1',
                    success: function(result) {
                        if (result == 1) {
                            $('#word_search_category_list').bootstrapTable('refresh');
                        } else {
                            alert('Error! Category could not be deleted');
                        }
                    }
                });
            }
        });

        $('#editWordSearchCategoryModal').on('shown.bs.modal', function() {
            syncUpdateWordSearchCategoryPlan();
        });

        $(document).ready(function() {
            $('.word-search-paid-cat').hide();
            $('.word-search-update-amount').hide();
            $('#word_search_category_amount').val(0);
            $('#word_search_category_plan').val("Free");
            syncCreateWordSearchCategoryPlan();
        });

        $("input:radio[name=word_search_cat_plan],input:radio[name=update_word_search_cat_plan]").on('change', function() {
            syncCreateWordSearchCategoryPlan();
            syncUpdateWordSearchCategoryPlan();
        });
    </script>
</body>
</html>
