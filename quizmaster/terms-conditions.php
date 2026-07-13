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
        <title>Terms Conditions | <?= ucwords($_SESSION['company_name']) ?> Admin Panel  </title>
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
                                    <h2>Terms Conditions <small>Update terms conditions here</small></h2>
                                    <div class="clearfix"></div>
                                </div>
                                <div class="x_content">
                                    <br />
                                    <?php
                                    $db->sql("SET NAMES 'utf8'");
                                    function getSettingMessage($db, $type, $fallback = '')
                                    {
                                        $sql = "select * from `settings` where type='" . $db->escapeString($type) . "' limit 1";
                                        $db->sql($sql);
                                        $res = $db->getResult();
                                        if (!empty($res) && isset($res[0]['message'])) {
                                            return $res[0]['message'];
                                        }

                                        if ($fallback !== '') {
                                            $sql = "select * from `settings` where type='" . $db->escapeString($fallback) . "' limit 1";
                                            $db->sql($sql);
                                            $res = $db->getResult();
                                            if (!empty($res) && isset($res[0]['message'])) {
                                                return $res[0]['message'];
                                            }
                                        }

                                        return '';
                                    }

                                    $termsMessageEs = getSettingMessage($db, 'update_terms_es', 'update_terms');
                                    $termsMessageEn = getSettingMessage($db, 'update_terms_en');
                                    ?>
                                    <div class="col-md-offset-1 col-md-6">
                                        <h4>Terms Conditions <small>Bilingual terms for App Usage</small></h4>
                                    </div>
                                    <div class="col-md-4">
                                        <a href='play-store-terms-conditions.php' target='_blank' class='btn btn-primary btn-sm'>Terms & Conditions Page for Play Store</a>
                                    </div>
                                    <div class="col-md-12"><hr style="margin-top: 5px;"></div>
                                    <form id="terms_form"  method="POST" action ="db_operations.php"data-parsley-validate class="form-horizontal form-label-left">
                                        <input type="hidden" id="update_terms" name="update_terms" required value='1'/>
                                        <div class="form-group">
                                            <label class="control-label col-md-2" for="message_es">Terms ES</label>
                                            <div class="col-md-9">
                                                <textarea name='message_es' id='terms_es' class='form-control'><?= $termsMessageEs; ?></textarea>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label col-md-2" for="message_en">Terms EN</label>
                                            <div class="col-md-9">
                                                <textarea name='message_en' id='terms_en' class='form-control'><?= $termsMessageEn; ?></textarea>
                                            </div>
                                        </div>
                                        <div class="ln_solid"></div>
                                        <div class="form-group">
                                            <div class="col-md-6 col-sm-6 col-xs-12 col-md-offset-3">
                                                <button type="submit" id="submit_privacy_btn" class="btn btn-success">Update terms</button>
                                            </div>
                                        </div>
                                    </form>

                                    <div class="row">
                                        <div  class="col-md-offset-3 col-md-4" style ="display:none;" id="privacy_result">
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
        </div>
        <!-- jQuery -->
        <script>
            tinymce.init({
                selector: '#terms_es, #terms_en',
                height: 400,
                menubar: true,
                plugins: [
                    'advlist autolink lists link charmap print preview anchor textcolor',
                    'searchreplace visualblocks code fullscreen',
                    'insertdatetime table contextmenu paste code help wordcount'
                ],
                toolbar: 'code | insert | undo redo | formatselect | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | help',
                setup: function (editor) {
                    editor.on("change keyup", function (e) {
                        //tinyMCE.triggerSave(); // updates all instances
                        editor.save(); // updates this instance's textarea
                        $(editor.getElement()).trigger('change'); // for garlic to detect change
                    });
                }
            });
        </script>
        <script>
            $('#terms_form').on('submit', function (e) {
                e.preventDefault();
                var formData = new FormData(this);
                if ($("#terms_form").validate().form()) {
                    if (confirm('Are you sure? Want to change the terms conditions? This will reflect to all app users')) {
                        $.ajax({
                            type: 'POST',
                            url: $(this).attr('action'),
                            data: formData,
                            beforeSend: function () {
                                $('#submit_privacy_btn').html('Please updating..');
                            },
                            cache: false,
                            contentType: false,
                            processData: false,
                            success: function (result) {
                                $('#privacy_result').html(result);
                                $('#privacy_result').show().delay(3000).fadeOut();
                                $('#submit_privacy_btn').html('Update terms');
                                // $('#register_form')[0].reset();
                            }
                        });
                    }
                }
            });
        </script>
    </body>
</html>
