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
        <title>User Accounts and Rights | <?= ucwords($_SESSION['company_name']) ?> - Admin Panel </title>
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
                    <!-- Content -->
                    <div class="row">
                        <div class="col-md-12 col-sm-12 col-xs-12">
                            <div class="x_panel">	
                                <div class="x_title">
                                    <h2> User Accounts and Rights</h2>
                                    <div class="clearfix"></div>
                                </div>

                                <form id="add_admin_form" method="post" class="form-horizontal form-label-left input_mask">
                                    <input type="hidden" name="add_admin" id="add_admin" value="1" required>
                                    <div class="col-md-3 col-sm-6 col-xs-12 form-group has-feedback">
                                        <p><strong>Username</strong></p>
                                        <input type="text" name="username" id="username" class="form-control">
                                    </div>

                                    <div class="col-md-3 col-sm-6 col-xs-12 form-group has-feedback">
                                        <p><strong>Password</strong></p>
                                        <input type="password" name="password" id="password" class="form-control">
                                    </div>

                                    <div class="col-md-3 col-sm-6 col-xs-12 form-group has-feedback">
                                        <p><strong>Role</strong></p>
                                        <select name="role" id="role" class="form-control">
                                            <option value="">Select Role</option>
                                            <option value="editor">Editor</option>
                                            <option value="admin">Admin</option>
                                        </select>
                                    </div>

                                    <div class="col-md-3 col-sm-6 col-xs-12 form-group has-feedback">
                                        <p><strong>Create</strong></p>
                                        <input type="submit" name="submit_btn" id="submit_btn" class="btn btn-warning form-control" value="Add Now">
                                    </div>
                                    <div class="col-md-12 col-sm-12 col-xs-12 text-center has-feedback">
                                        <div id="result"></div>
                                    </div>

                                </form>
                                <hr>
                                <div class="x_content">

                                    <table  aria-describedby="mydesc" class='table-striped' id='admin_list'
                                            data-toggle="table"
                                            data-url="get-list.php?table=admin"
                                            data-click-to-select="true"
                                            data-side-pagination="server"
                                            data-pagination="true"
                                            data-page-list="[5, 10, 20, 50, 100, 200]"
                                            data-search="true" data-show-columns="true"
                                            data-show-refresh="true" data-trim-on-search="false"
                                            data-sort-name="auth_username" data-sort-order="desc"
                                            data-mobile-responsive="true"
                                            data-toolbar="#toolbar" data-show-export="false"
                                            data-maintain-selected="true"
                                            data-export-types='["txt","excel"]'
                                            data-export-options='{
                                            "fileName": "admin-list-<?= date('d-m-y') ?>",
                                            "ignoreColumn": ["state"]   
                                            }'
                                            data-query-params="queryParams"
                                            >
                                        <thead>
                                            <tr>
                                                <th scope="col" data-field="state" data-checkbox="true"></th>
                                                <th scope="col" data-field="auth_username" data-sortable="true">Username</th>
                                                <th scope="col" data-field="role" data-sortable="true">Role</th>
                                                <th scope="col" data-field="created" data-sortable="true">Created</th>
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
        <div class="modal fade" id='editAdminModal' tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel">
            <div class="modal-dialog modal-md" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title" id="myModalLabel">Edit User Accounts and Rights</h4>
                    </div>
                    <form id="update_admin_form" method="POST" data-parsley-validate class="form-horizontal form-label-left">
                        <div class="modal-body">
                            <input type='hidden' name="update_admin" id="update_admin" value='1' required />
                            <input type='hidden' name="update_admin_id" id="update_admin_id" required />
                            <div class="form-group has-feedback" >
                                <p><strong>Username</strong></p>
                                <input type="text" name="update_username" id="update_username" class='form-control' required>
                            </div>
                            <div class="form-group has-feedback">
                                <p><strong>Role</strong></p>
                                <select name="update_role" id="update_role" class="form-control" required>
                                    <option value="">Select Role</option>
                                    <option value="editor">Editor</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                            <div class="ln_solid"></div>
                            <div class="form-group">
                                <div class="col-md-6 col-sm-6 col-xs-12">
                                    <input type="submit" id="update_admin_btn" class="btn btn-warning" value="Save">
                                </div>
                            </div>
                            <div id="update_admin_result"></div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php include 'footer.php'; ?>
        <script type="text/javascript">
            $('#add_admin_form').validate({
                rules: {
                    username: "required",
                    password: "required",
                    role: "required"
                }
            });
            $('#update_admin_form').validate({
                rules: {
                    update_username: "required",
                    update_role: "required"
                }
            });
            window.actionEvents = {
                'click .edit-admin': function (e, value, row, index) {
                    $('#update_username').val(row.auth_username);
                    $('#update_role').val(row.role);
                    $('#update_admin_id').val(row.auth_username);
                },
                'click .delete-admin': function (e, value, row, index) {
                    if (confirm('Are you sure? Want to Delete ?')) {
                        var id = row.auth_username;
                        $.ajax({
                            url: 'db_operations.php',
                            type: "post",
                            data: 'id=' + id + '&delete_admin=1',
                            success: function (result) {
                                if (result == 1) {
                                    $('#admin_list').bootstrapTable('refresh');
                                } else {
                                    alert('Error! Admin could not be deleted');
                                }
                            }
                        });
                    }
                }
            };
            function queryParams(p) {
                return {
                    limit: p.limit,
                    sort: p.sort,
                    order: p.order,
                    offset: p.offset,
                    search: p.search
                };
            }
            $('#add_admin_form').on('submit', function (e) {
                if ($("#add_admin_form").validate().form()) {
                    e.preventDefault();
                    var formData = new FormData(this);
                    if (confirm('Are you sure? Want to create ?')) {
                        $.ajax({
                            type: 'POST',
                            url: 'db_operations.php',
                            data: formData,
                            beforeSend: function () {
                                $('#submit_btn').prop('value', 'Please wait..');
                            },
                            cache: false,
                            contentType: false,
                            processData: false,
                            success: function (result) {
                                $('#result').html(result);
                                $('#result').show().delay(5000).fadeOut();
                                $('#submit_btn').prop('value', 'Add Now');
                                $('#add_admin_form')[0].reset();
                                $('#admin_list').bootstrapTable('refresh');
                            }
                        });
                    }
                }
            });
            $('#update_admin_form').on('submit', function (e) {
                e.preventDefault();
                var formData = new FormData(this);
                if ($("#update_admin_form").validate().form()) {
                    $.ajax({
                        type: 'POST',
                        url: 'db_operations.php',
                        data: formData,
                        beforeSend: function () {
                            $('#update_admin_btn').prop('value', 'Please wait..');
                        },
                        cache: false,
                        contentType: false,
                        processData: false,
                        success: function (result) {
                            $('#update_admin_result').html(result);
                            $('#update_admin_result').show().delay(5000).fadeOut();
                            $('#update_admin_btn').prop('value', 'Save');
                            $('#admin_list').bootstrapTable('refresh');
                        }
                    });
                }
            });
        </script>
    </body>
</html>	