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
        <title>Change Password | <?= ucwords($_SESSION['company_name']) ?> </title>

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
                                    <h2>Change Password</h2>
                                    <div class="clearfix"></div>
                                </div>
                                <div class="x_content">
                                    <br />
                                    <form id="password_form"  method="POST" action ="change_password.php" class="form-horizontal form-label-left">
                                        <div class="form-group">
                                            <label class="control-label col-md-3 col-sm-3 col-xs-12" for="old_password">Old Password<span class="required">*</span>
                                            </label>
                                            <div class="col-md-6 col-sm-6 col-xs-12">
                                                <input type="password" id="old_password" name="old_password" required="required" class="form-control col-md-7 col-xs-12">
                                            </div>
                                            <label id="old_status"></label>
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label col-md-3 col-sm-3 col-xs-12" for="new_password">New Password<span class="required">*</span>
                                            </label>
                                            <div class="col-md-6 col-sm-6 col-xs-12">
                                                <input type="password" id="new_password" name="new_password" required class="form-control col-md-7 col-xs-12">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label col-md-3 col-sm-3 col-xs-12" for="confirm_password">Confirm Password<span class="required">*</span>
                                            </label>
                                            <div class="col-md-6 col-sm-6 col-xs-12">
                                                <input type="password" id="confirm_password" name="confirm_password" required class="form-control col-md-7 col-xs-12">
                                            </div>
                                        </div>
                                        <div class="ln_solid"></div>
                                        <div class="row">
                                            <div class="col-md-offset-3 col-md-4 alert alert-success" style="display: none;" id="result">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <div class="col-md-6 col-sm-6 col-xs-12 col-md-offset-3">
                                                <button type="submit" id="submit_btn" class="btn btn-success">Submit</button>
                                            </div>
                                        </div>
                                    </form>

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
        <script>
            $('#password_form').validate({
                rules: {
                    old_password: "required",
                    new_password: {minlength: 6},
                    confirm_password: {
                        minlength: 6,
                        equalTo: "#new_password"
                    }
                },
                messages: {
                    confirm_password: {
                        equalTo: "password and confirm password must be a same"
                    }
                }
            });
        </script>
        <script>
            $('#old_password').on('blur input', function () {
                var old_password = $(this).val();
                if (old_password.length > 4) {
                    $.ajax({
                        type: 'POST',
                        url: 'change_password.php',
                        data: 'old_password=' + old_password,
                        beforeSend: function () {
                            $('#old_status').html('checking..');
                        },
                        success: function (result) {
                            if (result == 'True') {
                                $('#old_status').html("<i class='fa fa-check-circle fa-2x text-success'></i>");
                                allowsubmit = true;
                            } else {
                                $('#old_status').html("<i class='fa fa-times-circle fa-2x text-danger'></i>");
                                $('#old_password').focus();
                                allowsubmit = false;
                            }
                        }
                    });
                }
            });
        </script>
        <script>
            $('#password_form').on('submit', function (e) {
                e.preventDefault();
                if ($("#password_form").validate().form()) {
                    if (allowsubmit) {
                        var formData = 'new_password=' + $('#new_password').val() + '&old_password=' + $('#old_password').val();
                        $.ajax({
                            type: 'POST',
                            url: $(this).attr('action'),
                            data: formData,
                            beforeSend: function () {
                                $('#submit_btn').html('Please wait..');
                            },
                            success: function (result) {
                                $('#old_status').html('');
                                $('#result').html(result);
                                $('#result').show().delay(4000).fadeOut();
                                $('#submit_btn').html('Submit');
                                $('#password_form')[0].reset();
                            }
                        });
                    } else {
                        return false;
                    }
                }
            });
        </script>
    </body>
</html>