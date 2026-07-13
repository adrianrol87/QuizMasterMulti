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
        <title>Delete Account | <?= ucwords($_SESSION['company_name']) ?> Admin Panel</title>
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
                                    <h2>Delete Account <small>Update account deletion content here</small></h2>
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

                                    $deleteAccountEs = getSettingMessage($db, 'delete_account_es', 'delete_account');
                                    $deleteAccountEn = getSettingMessage($db, 'delete_account_en');
                                    ?>
                                    <div class="col-md-offset-1 col-md-6">
                                        <h4>Delete Account <small>Bilingual content for App Users</small></h4>
                                    </div>
                                    <div class="col-md-4">
                                        <a href='play-store-delete-account.php?lang=en' target='_blank' class='btn btn-primary btn-sm'>Delete Account Page for Play Store</a>
                                    </div>
                                    <div class="col-md-12"><hr style="margin-top: 5px;"></div>
                                    <form id="delete_account_form" method="POST" action="db_operations.php" data-parsley-validate class="form-horizontal form-label-left">
                                        <input type="hidden" id="update_delete_account" name="update_delete_account" required value="1"/>
                                        <div class="form-group">
                                            <label class="control-label col-md-2" for="message_es">Delete Account ES</label>
                                            <div class="col-md-9">
                                                <textarea name="message_es" id="delete_account_es" class="form-control"><?= $deleteAccountEs; ?></textarea>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label col-md-2" for="message_en">Delete Account EN</label>
                                            <div class="col-md-9">
                                                <textarea name="message_en" id="delete_account_en" class="form-control"><?= $deleteAccountEn; ?></textarea>
                                            </div>
                                        </div>
                                        <div class="ln_solid"></div>
                                        <div class="form-group">
                                            <div class="col-md-6 col-sm-6 col-xs-12 col-md-offset-3">
                                                <button type="submit" id="submit_delete_account_btn" class="btn btn-success">Update Delete Account</button>
                                            </div>
                                        </div>
                                    </form>
                                    <div class="row">
                                        <div class="col-md-offset-3 col-md-4" style="display:none;" id="delete_account_result"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php include 'footer.php'; ?>
            </div>
        </div>
        <script>
            tinymce.init({
                selector: '#delete_account_es, #delete_account_en',
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
                        editor.save();
                        $(editor.getElement()).trigger('change');
                    });
                }
            });
        </script>
        <script>
            $('#delete_account_form').on('submit', function (e) {
                e.preventDefault();
                var formData = new FormData(this);
                if ($("#delete_account_form").validate().form()) {
                    if (confirm('Are you sure? Want to change the delete account content? This will reflect to all app users')) {
                        $.ajax({
                            type: 'POST',
                            url: $(this).attr('action'),
                            data: formData,
                            beforeSend: function () {
                                $('#submit_delete_account_btn').html('Please updating..');
                            },
                            cache: false,
                            contentType: false,
                            processData: false,
                            success: function (result) {
                                $('#delete_account_result').html(result);
                                $('#delete_account_result').show().delay(3000).fadeOut();
                                $('#submit_delete_account_btn').html('Update Delete Account');
                            }
                        });
                    }
                }
            });
        </script>
    </body>
</html>
