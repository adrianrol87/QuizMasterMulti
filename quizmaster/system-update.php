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
        <title>System Update | <?= ucwords($_SESSION['company_name']) ?> Admin Panel  </title>
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
                                <?php
                                $db->sql("SET NAMES 'utf8'");
                                $sql = "select * from `settings` where type='quiz_version'";
                                $db->sql($sql);
                                $res = $db->getResult();
                                $data = array();
                                if (!empty($res)) {
                                    $data = $res[0];
                                }
                                ?>
                                <div class="x_title">
                                    <h2>System Update <small><b>Current Version <?= ($data) ? $data['message'] : '' ?></b></small></h2>
                                    <div class="clearfix"></div>
                                </div>
                                <div class="x_content">
                                    <br />

                                    <form id="frm_update"  method="POST" action ="db_operations.php" data-parsley-validate class="form-horizontal form-label-left">
                                        <input type="hidden" id="update_system" name="update_system" required value='1'/>
                                        <?php
                                        $quiz_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . str_replace(basename($_SERVER['SCRIPT_NAME']), "", $_SERVER['SCRIPT_NAME']);
                                        ?>
                                        <input type="hidden" name="quiz_url" value="<?= $quiz_url; ?>" required/>
                                        <div class="form-group row">
                                            <div class="col-md-6 col-sm-6 col-xs-12">
                                                <label>Purchase Code</label>
                                                <input type="text" name="purchase_code" required placeholder="Enter Purchase Code" class="form-control"/>
                                            </div>
                                        </div>
                                        <div class="form-group row">                                            
                                            <div class="col-md-6 col-sm-6 col-xs-12">
                                                <label>Update Zip <small class="text-danger">Only zip file allow</small></label>
                                                <input name="file" type="file" accept=".zip,.rar,.7zip" required class="form-control">
                                                <small class="text-danger"> Your Current Version is <?= ($data) ? $data['message'] : '' ?>, Please update nearest version here if available</small>
                                            </div>
                                        </div>
                                        <div class="ln_solid"></div>
                                        <div class="form-group">
                                            <div class="col-md-6 col-sm-6 col-xs-12">
                                                <button type="submit" id="submit_btn" class="btn btn-success">Submit</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                <div class="row">
                                    <div class="col-md-offset-2 col-md-6" style ="display:none;" id="result">
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
        <script type="text/javascript">
            $('#frm_update').validate({});
        </script>
        <script type="text/javascript">
            $('#frm_update').on('submit', function (e) {
                e.preventDefault();
                var formData = new FormData(this);
                if ($("#frm_update").validate().form()) {
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
                            setTimeout(function () {
                                location.reload();
                            }, 4000);
                        }
                    });
                }
            });
        </script>
    </body>
</html>