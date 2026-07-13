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
        <title>Send Notifications  | <?= ucwords($_SESSION['company_name']) ?> - Admin Panel </title>
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
                                    <h2>Send Notifications to Users<small>To all or selected</small></h2>
                                    <div class="clearfix"></div>
                                </div>
                                <div class="x_content">
                                    <div class='row'>
                                        <div class='col-md-6 col-sm-12'>
                                            <form id="notification_form" method="POST" action="db_operations.php" data-parsley-validate="" class="form-horizontal form-label-left" enctype="multipart/form-data">
                                                <input type="hidden" id="send_notifications" name="send_notifications" required="" value="1" aria-required="true">
                                                <textarea id="selected_list" name="selected_list" style='display:none'></textarea>
                                                <div class="form-group">
                                                    <label for="users">Select Users</label>
                                                    <select name='users' id='users' class='form-control' >
                                                        <option value='all'>All</option>
                                                        <option value='selected'>Selected only</option>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label for="type">Type</label>
                                                    <select name="type" id="type" class="form-control" required>
                                                        <option value="default">Default</option>
                                                        <option value="category">Main Category</option>
                                                    </select>
                                                </div>
                                                <div class="form-group" id="categories" style="display:none;">
                                                    <?php
                                                    $db->sql("SET NAMES 'utf8'");
                                                    $sql = "select * from category order by id DESC";
                                                    $db->sql($sql);
                                                    $categories = $db->getResult();
                                                    ?>
                                                    <label class="" for="maincat_id">Main Category</label>
                                                    <select id="maincat_id" name="maincat_id" required class="form-control">
                                                        <option value=''>Select Options</option>
                                                        <?php foreach ($categories as $category) { ?>
                                                            <option value='<?= $category['id'] ?>'><?= $category['category_name'] ?></option>
                                                        <?php } ?>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label class="" for="title">Title</label>
                                                    <input type="text" id="title" name="title" required="required" class="form-control col-md-7 col-xs-12">
                                                </div>
                                                <div class="form-group">
                                                    <label class="" for="message">Message</label>
                                                    <textarea id="message" name="message" required="required" class="form-control col-md-7 col-xs-12" ></textarea>
                                                </div>
                                                <div class="form-group">
                                                    <input name="include_image" id="include_image"  type="checkbox" > Include image
                                                </div>
                                                <div class="form-group">
                                                    <input type='file' name="image" id="image" style='display:none;'> 
                                                </div>
                                                <div class="ln_solid"></div>
                                                <div id="result"></div>
                                                <div class="form-group">
                                                    <div class="col-md-6 col-sm-6 col-xs-12">
                                                        <button type="submit" id="submit_btn" class="btn btn-warning">Send Notification</button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                        <div class='col-md-6 col-sm-12'>
                                            <button type='button' id='get_selections' class='btn btn-primary'>Get Selected Users</button>
                                            <div class="row" id="toolbar">
                                                <form id="report_form" method="post">
                                                    <div class="col-md-12">
                                                        <div class="form-group">
                                                            <select name="filter_status" id="filter_status" class="form-control">
                                                                <option value="">All</option>
                                                                <option value="1">Active</option>
                                                                <option value="0">De-active</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                            <table  aria-describedby="mydesc" class='table-striped' id='users_list'
                                                    data-toggle="table"
                                                    data-url="get-list.php?table=users"
                                                    data-click-to-select="true"
                                                    data-side-pagination="server"
                                                    data-pagination="true"
                                                    data-page-list="[5, 10, 20, 50, 100, 200]"
                                                    data-search="true" data-show-columns="true"
                                                    data-show-refresh="true" data-trim-on-search="false"
                                                    data-sort-name="id" data-sort-order="desc"
                                                    data-mobile-responsive="true"
                                                    data-toolbar="#toolbar" 
                                                    data-maintain-selected="true"
                                                    data-show-export="false" data-export-types='["txt","excel"]'
                                                    data-export-options='{
                                                    "fileName": "users-list-<?= date('d-m-y') ?>",
                                                    "ignoreColumn": ["state"]	
                                                    }'
                                                    data-query-params="queryParams_1"
                                                    >
                                                <thead>
                                                    <tr>
                                                        <th scope="col" data-field="state" data-checkbox="true"></th>
                                                        <th scope="col" data-field="id" data-sortable="true">ID</th>
                                                        <th scope="col" data-field="name" data-sortable="true">Name</th>
                                                        <th scope="col" data-field="email" data-sortable="true">Email</th>
                                                        <th scope="col" data-field="status" data-sortable="false">Status</th>
                                                    </tr>
                                                </thead>
                                            </table>
                                        </div>
                                        <div class="col-md-12">

                                            <div class="row" id="toolbar-1">
                                                <div class="col-md-3">
                                                    <button class="btn btn-danger btn-sm" id="delete_multiple_notifications" title="Delete Selected Notifications"><em class='fa fa-trash'></em></button>
                                                </div>
                                            </div>
                                            <table  aria-describedby="mydesc" class='table-striped' id='notification_list'
                                                    data-toggle="table"
                                                    data-url="get-list.php?table=notifications"
                                                    data-click-to-select="true"
                                                    data-side-pagination="server"
                                                    data-pagination="true"
                                                    data-page-list="[5, 10, 20, 50, 100, 200]"
                                                    data-search="true" data-show-columns="true"
                                                    data-show-refresh="true" data-trim-on-search="false"
                                                    data-sort-name="id" data-sort-order="desc"
                                                    data-mobile-responsive="true"
                                                    data-toolbar="#toolbar-1" 
                                                    data-maintain-selected="true"
                                                    data-show-export="false" data-export-types='["txt","excel"]'
                                                    data-export-options='{
                                                    "fileName": "notifications-list-<?= date('d-m-y') ?>",
                                                    "ignoreColumn": ["state"]	
                                                    }'
                                                    data-query-params="queryParams"
                                                    >
                                                <thead>
                                                    <tr>
                                                        <th scope="col" data-field="state" data-checkbox="true"></th>
                                                        <th scope="col" data-field="id" data-sortable="true">ID</th>
                                                        <th scope="col" data-field="title" data-sortable="true">Title</th>
                                                        <th scope="col" data-field="message" data-sortable="true">Message</th>
                                                        <th scope="col" data-field="image" data-sortable="false">Image</th>
                                                        <th scope="col" data-field="users" data-sortable="true" data-visible="false" >Users</th>
                                                        <th scope="col" data-field="type" data-sortable="true">Type</th>
                                                        <th scope="col" data-field="type_id" data-sortable="true">Main Category ID</th>
                                                        <th scope="col" data-field="date_sent" data-sortable="true">Date Sent</th>
                                                        <th scope="col" data-field="operate">Operate</th>
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
            <!-- footer content -->
            <?php include 'footer.php'; ?>
            <!-- /footer content -->
        </div>

        <!-- jQuery -->
        <script type="text/javascript">
            $("#type").change(function () {
                type = $("#type").val();
                if (type == "default") {
                    $("#categories").hide();
                    $("#maincat_id").prop("disabled", true);
                }
                if (type == "category") {
                    $("#categories").show();
                    $("#maincat_id").prop("disabled", false);
                }
            });
            $(document).ready(function () {
                type = $("#type").val();
                if (type == "default") {
                    $("#categories").hide();
                    $("#maincat_id").prop("disabled", true);
                }
                if (type == "category") {
                    $("#categories").show();
                    $("#maincat_id").prop("disabled", false);
                }
            });
        </script>
        <script type="text/javascript">
            $(document).on('click', '.delete-notification', function () {
                if (confirm('Are you sure? Want to delete notification?')) {
                    id = $(this).data("id");
                    image = $(this).data("image");
                    $.ajax({
                        url: 'db_operations.php',
                        type: "post",
                        data: 'id=' + id + '&image=' + image + '&delete_notification=1',
                        success: function (result) {
                            if (result == 1) {
                                $('#notification_list').bootstrapTable('refresh');
                            } else
                                alert('Error! Category could not be deleted');
                        }
                    });
                }
            });
        </script>
        <script>
            $('#notification_form').validate({
                rules: {
                    type: "required",
                    title: "required",
                    message: "required"
                }
            });
            var $table = $('#users_list');
            $('#toolbar').find('select').change(function () {
                $table.bootstrapTable('refreshOptions', {
                    exportDataType: $(this).val()
                });
            });
        </script>
        <script>
            function queryParams_1(p) {
                return {
                    "status": $('#filter_status').val(),
                    limit: p.limit,
                    sort: p.sort,
                    order: p.order,
                    offset: p.offset,
                    search: p.search
                };
            }
            function queryParams(p) {
                return {
                    limit: p.limit,
                    sort: p.sort,
                    order: p.order,
                    offset: p.offset,
                    search: p.search
                };
            }
            $('#delete_multiple_notifications').on('click', function (e) {
                sec = 'notifications';
                is_image = 1;
                table = $('#notification_list');
                delete_button = $('#delete_multiple_notifications');
                selected = table.bootstrapTable('getAllSelections');
                // alert(selected[0].id);
                ids = "";
                $.each(selected, function (i, e) {
                    ids += e.id + ",";
                });
                ids = ids.slice(0, -1); // removes last comma character
                if (ids == "") {
                    alert("Please select some notifications to delete!");
                } else {
                    if (confirm("Are you sure you want to delete all selected notifications?")) {
                        $.ajax({
                            type: 'GET',
                            url: "db_operations.php",
                            data: 'delete_multiple=1&ids=' + ids + '&sec=' + sec + '&is_image=' + is_image,
                            beforeSend: function () {
                                delete_button.html('<i class="fa fa-spinner fa-pulse"></i>');
                            },
                            success: function (result) {
                                if (result == 1) {
                                    alert("Notifications deleted successfully");
                                } else {
                                    alert("Could not delete notifications. Try again!");
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
            $('#report_form').on('submit', function (e) {
                e.preventDefault();
                $('#users_list').bootstrapTable('refresh');
            });
        </script>
        <script>
            $('#notification_form').on('submit', function (e) {
                e.preventDefault();
                var formData = new FormData(this);
                if ($("#notification_form").validate().form()) {
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
                            $('#result').show().delay(6000).fadeOut();
                            $('#submit_btn').html('Submit');
                            $('#notification_form')[0].reset();
                            $('#notification_list').bootstrapTable('refresh');
                        }
                    });
                }
            });
        </script>
        <script>
            $("#include_image").change(function () {
                if (this.checked) {
                    $('#image').show('fast');
                } else {
                    $('#image').val('');
                    $('#image').hide('fast');
                }
            });
        </script>
        <script>
            $table = $('#users_list');
            $(function () {
                $('#get_selections').click(function () {
                    selected = $table.bootstrapTable('getSelections');
                    var arr = Object.values(selected);
                    var i;
                    var final_selection = [];
                    for (i = 0; i < arr.length; ++i) {
                        final_selection.push(arr[i]['fcm_id']);
                    }
                    $('textarea#selected_list').val(final_selection);

                });
            });
        </script>
    </body>
</html>