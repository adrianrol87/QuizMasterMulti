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
        <title>Notification Settings | Firebase Service Account | <?= ucwords($_SESSION['company_name']) ?> Admin Panel  </title>
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
                                    <h2>Notification Settings <small>Upload Firebase service account JSON for FCM v1</small></h2>
                                    <div class="clearfix"></div>
                                </div>
                                <div class="x_content">
                                    <br />
                                    <?php
                                    $serviceAccountPath = __DIR__ . '/service-accounts/firebase-adminsdk.json';
                                    $serviceAccountExists = file_exists($serviceAccountPath);
                                    $serviceAccountData = array();
                                    if ($serviceAccountExists) {
                                        $serviceAccountData = json_decode(file_get_contents($serviceAccountPath), true);
                                    }
                                    ?>
                                    <div class="form-group">
                                        <div class="col-md-9 col-md-offset-3">
                                            <?php if ($serviceAccountExists) { ?>
                                                <div class="alert alert-success">
                                                    Firebase JSON loaded for project:
                                                    <strong><?= htmlspecialchars($serviceAccountData['project_id']) ?></strong>
                                                </div>
                                            <?php } else { ?>
                                                <div class="alert alert-warning">
                                                    No Firebase service account JSON has been uploaded yet.
                                                </div>
                                            <?php } ?>
                                        </div>
                                    </div>
                                    <form id="register_form"  method="POST" action ="db_operations.php" data-parsley-validate class="form-horizontal form-label-left" enctype="multipart/form-data">
                                        <input type="hidden" id="upload_fcm_service_account" name="upload_fcm_service_account" required value='1'/>
                                        <div class="form-group">
                                            <label class="control-label col-md-3 col-sm-3 col-xs-12" for="service_account_json">Firebase JSON</label>
                                            <div class="col-md-6 col-sm-6 col-xs-12">
                                                <input type="file" id="service_account_json" name="service_account_json" accept=".json" required class="form-control col-md-7 col-xs-12" />
                                                <p style="margin-top:8px;color:#73879C;">
                                                    Upload the Firebase Admin SDK JSON file downloaded from Service Accounts.
                                                </p>
                                            </div>
                                        </div>
                                        <div class="ln_solid"></div>
                                        <div class="form-group">
                                            <div class="col-md-6 col-sm-6 col-xs-12 col-md-offset-3">
                                                <button type="submit" id="submit_btn" class="btn btn-success">Upload Firebase JSON</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                <div class="row">
                                    <div  class="col-md-offset-3 col-md-4" style ="display:none;" id="result">
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

        <!-- jQuery -->
        <script>
            $('#register_form').validate({
                rules: {
                    service_account_json: "required",
                }
            });
        </script>
        <script>
            $('#register_form').on('submit', function (e) {
                e.preventDefault();
                var formData = new FormData(this);
                if ($("#register_form").validate().form()) {
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
                            $('#result').show();
                            $('#submit_btn').html('Submit');
                            location.reload();
                        }
                    });
                }
            });
        </script>
    </body>
</html>
