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
        <title>Instructions | <?= ucwords($_SESSION['company_name']) ?> Admin Panel  </title>
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
                                    <h2>Instructions <small>Update instructions here</small></h2>
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

                                    $instructionsEs = getSettingMessage($db, 'instructions_es', 'instructions');
                                    $instructionsEn = getSettingMessage($db, 'instructions_en');
                                    ?>
                                    <div class="col-md-offset-1 col-md-6">
                                        <h4>Instructions <small>Bilingual content for App Usage</small></h4>
                                    </div>
                                    <div class="col-md-12"><hr style="margin-top: 5px;"></div>
                                    <form id="terms_form"  method="POST" action ="db_operations.php"data-parsley-validate class="form-horizontal form-label-left">
                                        <input type="hidden" id="update_instructions" name="update_instructions" required value='1'/>
                                        <div class="form-group">
                                            <label class="control-label col-md-2" for="message_es">Instructions ES</label>
                                            <div class="col-md-9">
                                                <textarea name='message_es' id='instructions_es' class='form-control'><?= $instructionsEs; ?></textarea>
                                                <input name="img" type="file" id="upload" class="hidden" onchange="">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label col-md-2" for="message_en">Instructions EN</label>
                                            <div class="col-md-9">
                                                <textarea name='message_en' id='instructions_en' class='form-control'><?= $instructionsEn; ?></textarea>
                                            </div>
                                        </div>
                                        <div class="ln_solid"></div>
                                        <div class="form-group">
                                            <div class="col-md-6 col-sm-6 col-xs-12 col-md-offset-3">
                                                <button type="submit" id="submit_privacy_btn" class="btn btn-success">Update instructions</button>
                                            </div>
                                        </div>
                                    </form>
                                    <div class="row">
                                        <div class="col-md-offset-3 col-md-4" style ="display:none;" id="privacy_result">
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
                selector: '#instructions_es, #instructions_en',
                height: 400,
                menubar: true,
                plugins: [
                    'advlist autolink lists link image charmap print preview anchor textcolor',
                    'searchreplace visualblocks code fullscreen',
                    'insertdatetime table contextmenu paste code help wordcount'
                ],
                toolbar: 'code | insert | undo redo | formatselect | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | image | removeformat | help',
                image_advtab: true,
                //images_upload_url: 'upload.php',
                relative_urls: false,
                remove_script_host: false,
                file_picker_callback: function (callback, value, meta) {
                    if (meta.filetype == "media" || meta.filetype == "image") {

                        // Trigger click on file element
                        jQuery("#upload").trigger("click");
                        $("#upload").unbind('change');
                        // File selection
                        jQuery("#upload").on("change", function () {
                            var file = this.files[0];
                            var reader = new FileReader();

                            // FormData
                            var fd = new FormData();
                            var files = file;
                            fd.append("file", files);
                            fd.append('filetype', meta.filetype);

                            var filename = "";

                            // AJAX
                            jQuery.ajax({
                                url: "upload.php",
                                type: "post",
                                data: fd,
                                contentType: false,
                                processData: false,
                                async: false,
                                success: function (response) {
                                    filename = response;
                                }
                            });

                            reader.onload = function (e) {
                                callback("images/instruction/" + filename);
                            };
                            reader.readAsDataURL(file);
                        });
                    }
                },
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
                    if (confirm('Are you sure? Want to change the instructions? This will reflect to all app users')) {
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
                            }
                        });
                    }
                }
            });
        </script>

    </body>
</html>
