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
        <title>Firebase Settings | <?= ucwords($_SESSION['company_name']) ?> - Admin Panel </title>
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
                                    <h2>Firebase Settings for Web <small>Note that this will directly reflect the changes in Web</small></h2>
                                    <div class="clearfix"></div>
                                </div>
                                <?php
                                $db->sql("SET NAMES 'utf8'");
                                $setting = [
                                    'apiKey', 'authDomain', 'databaseURL', 'projectId', 'storageBucket', 'messagingSenderId', 'appId', 'client_id_google', 'app_id_fb'
                                ];
                                $data = array();
                                foreach ($setting as $row) {
                                    $sql = "SELECT * FROM settings WHERE type='" . $row . "' LIMIT 1";
                                    $db->sql($sql);
                                    $res = $db->getResult();
                                    $data[$row] = (!empty($res)) ? $res[0]['message'] : '';
                                }
                                //print_r($data);
                                ?>
                                <div class="x_content">
                                    <form id="system_form" method="POST" data-parsley-validate class="form-horizontal form-label-left">
                                        <input type="hidden" id="web_firebase_settings" name="web_firebase_settings" required value="1" aria-required="true">

                                        <div class="form-group row">
                                            <div class="col-md-6 col-xs-12">
                                                <label>apiKey</label>
                                                <input type="text" name="apiKey" value="<?= (!empty($data)) ? $data['apiKey'] : '' ?>" placeholder="X0X0X0X0X0X0" class="form-control" required/>
                                            </div>
                                            <div class="col-md-6 col-xs-12">
                                                <label>authDomain</label>
                                                <input type="text" name="authDomain" value="<?= (!empty($data)) ? $data['authDomain'] : '' ?>" placeholder="X0X0X0X0X0X0.firebaseapp.com" class="form-control" required/>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <div class="col-md-6 col-xs-12">
                                                <label>databaseURL</label>
                                                <input type="text" name="databaseURL" value="<?= (!empty($data)) ? $data['databaseURL'] : '' ?>" placeholder="https://X0X0X0X0X0X0.firebaseio.com" class="form-control" required/>
                                            </div>
                                            <div class="col-md-6 col-xs-12">
                                                <label>projectId</label>
                                                <input type="text" name="projectId" value="<?= (!empty($data)) ? $data['projectId'] : '' ?>" placeholder="X0X0X0X0X0X0" class="form-control" required/>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <div class="col-md-6 col-xs-12">
                                                <label>storageBucket</label>
                                                <input type="text" name="storageBucket" value="<?= (!empty($data)) ? $data['storageBucket'] : '' ?>" placeholder="X0X0X0X0X0X0.appspot.com" class="form-control" required/>
                                            </div>
                                            <div class="col-md-6 col-xs-12">
                                                <label>messagingSenderId</label>
                                                <input type="text" name="messagingSenderId" value="<?= (!empty($data)) ? $data['messagingSenderId'] : '' ?>" placeholder="123456789012" class="form-control" required/>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <div class="col-md-6 col-xs-12">
                                                <label>appId</label>
                                                <input type="text" name="appId" value="<?= (!empty($data)) ? $data['appId'] : '' ?>" placeholder="1:123456789012:web:9ddde123456c78c20d2301" class="form-control" required/>
                                            </div>
                                            <div class="col-md-6 col-xs-12">
                                                <label>Facebook App Id</label>
                                                <input type="text" name="app_id_fb" value="<?= (!empty($data)) ? $data['app_id_fb'] : '' ?>" placeholder="X0X0X0X0X0X0X0" class="form-control"/>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <div class="col-md-12 col-xs-12">
                                                <label>Google Client Id</label>
                                                <input type="text" name="client_id_google" value="<?= (!empty($data)) ? $data['client_id_google'] : '' ?>" placeholder="X0X0X0X0X0X0.apps.googleusercontent.com" class="form-control" required/>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12 col-xs-12">
                                                <div class="ln_solid"></div>
                                                <div id="result"></div>
                                                <div class="form-group">
                                                    <div class="col-md-6 col-sm-6 col-xs-12">
                                                        <button type="submit" id="submit_btn" class="btn btn-warning">Save Settings</button>
                                                    </div>
                                                </div>
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
        <!-- footer content -->
        <?php include 'footer.php'; ?>
        <!-- /footer content -->

        <script>
            $('#system_form').validate({});
        </script>
        <!-- jQuery -->
        <script>


            $('#system_form').on('submit', function (e) {
                e.preventDefault();
                var formData = new FormData(this);
                if ($("#system_form").validate().form()) {
                    swal({
                        title: "Are you sure?",
                        text: "Please verify all data and than update.",
                        icon: "warning",
                        // buttons: true,
                        buttons: ["Cancel! Let me check", "Its okay! Update now"],
                        dangerMode: true,
                    }).then((willUpdate) => {
                        if (willUpdate) {
                            $.ajax({
                                type: 'POST',
                                url: 'db_operations.php',
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
                                    $('#submit_btn').html('Save Settings');
                                }
                            });
                        }
                    });
                }
            });
        </script>
    </body>
</html>