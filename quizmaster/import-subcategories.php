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
        <title>Import Sub Categories | <?= ucwords($_SESSION['company_name']) ?> Admin Panel</title>
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
                                    <h2>Import Sub Categories <small>upload using CSV file</small></h2>
                                    <div class="clearfix"></div>
                                </div>
                                <div class="x_content">
                                    <br />
                                    <form id="register_form" method="POST" action="db_operations.php" data-parsley-validate class="form-horizontal form-label-left">
                                        <input type="hidden" id="import_subcategories" name="import_subcategories" required value="1"/>
                                        <div class="form-group">
                                            <label class="control-label col-md-3 col-sm-3 col-xs-12" for="subcategories_file">CSV Sub Categories file</label>
                                            <div class="col-md-6 col-sm-6 col-xs-12">
                                                <input type="file" name="subcategories_file" id="subcategories_file" required class="form-control col-md-7 col-xs-12" accept=".csv" />
                                            </div>
                                        </div>
                                        <div class="ln_solid"></div>
                                        <div class="form-group">
                                            <div class="col-md-3 col-sm-6 col-xs-12 col-md-offset-3">
                                                <button type="submit" id="submit_btn" class="btn btn-success">Upload CSV file</button>
                                            </div>
                                            <div class="col-md-4 col-sm-6 col-xs-12">
                                                <a class='btn btn-warning' href='library/subcategory-import-format.csv' target="_blank"><em class='fas fa-download'></em> Download Sample File Here</a>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                <div class="row">
                                    <div class="col-md-offset-3 col-md-4" style="display:none;" id="result"></div>
                                </div>
                            </div>
                            <div class="x_panel">
                                <div class="x_title">
                                    <h2>CSV Format Guide</h2>
                                    <div class="clearfix"></div>
                                </div>
                                <p>Column 1: language_id (use 0 when language mode is disabled)</p>
                                <p>Column 2: main_category_name</p>
                                <p>Column 3: subcategory_name</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include 'footer.php'; ?>
        </div>
        <script>
            $('#register_form').on('submit', function (e) {
                e.preventDefault();
                var formData = new FormData(this);
                $.ajax({
                    type: 'POST',
                    url: $(this).attr('action'),
                    data: formData,
                    beforeSend: function () {
                        $('#submit_btn').html('Uploading sub categories..');
                    },
                    cache: false,
                    contentType: false,
                    processData: false,
                    success: function (result) {
                        $('#result').html(result);
                        $('#result').show().delay(6000).fadeOut();
                        $('#submit_btn').html('Upload CSV file');
                        $('#subcategories_file').val('');
                    }
                });
            });
        </script>
    </body>
</html>
