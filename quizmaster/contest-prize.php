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
        <title>Contest Prize | <?= ucwords($_SESSION['company_name']) ?> - Admin Panel </title>
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
                                    <h2>Create Contest Prize</h2>
                                    <div class="clearfix"></div>
                                </div>
                                <div class="x_content">
                                    <div class='row'>
                                        <div class='col-md-12 col-sm-12'>
                                            <form id="contest_form" method="POST" action="db_operations.php" class="form-horizontal form-label-left" enctype="multipart/form-data">
                                                <input type="hidden" id="add_contest_prize" name="add_contest_prize" required="" value="1" aria-required="true">
                                                <input type="hidden" id="contest_id" name="contest_id" value="<?= $_GET['contest_id'] ?>"/>
                                                <div class="form-group row">
                                                    <div class="col-md-4 col-sm-12">
                                                        <?php
                                                        $db->sql("SET NAMES 'utf8'");
                                                        $sql = "SELECT top_winner as total FROM contest_prize WHERE contest_id=" . $_GET['contest_id'] . " ORDER BY top_winner DESC LIMIT 1";
                                                        $db->sql($sql);
                                                        $res = $db->getResult();
                                                        $max = (!empty($res)) ? $res[0]['total'] + 1 : 1;
                                                        ?>
                                                        <label for="winner">Top Winner</label>
                                                        <input type="number" name="winner" value="<?= $max ?>" required class="form-control" readonly>
                                                    </div>
                                                    <div class="col-md-4 col-xs-12">
                                                        <label for="points">Prize</label>
                                                        <input type="number" name="points" min="0" required class="form-control">
                                                    </div>
                                                    <div class="col-md-2 col-xs-12">
                                                        <label>&nbsp;</label><br>
                                                        <button type="submit" id="submit_btn" class="btn btn-warning">Add New</button>
                                                    </div>
                                                </div>
                                                <div id="result"></div>
                                                <div class="ln_solid"></div>

                                            </form>
                                        </div>                                       
                                    </div>
                                    <div class="row">
                                        <div class='col-md-12 col-sm-12'>
                                            <h2>Contest Prize <small>View / Update / Delete</small></h2>                                           
                                        </div>
                                        <div class='col-md-12 col-sm-12'>                                            
                                            <div class='row'>                                                
                                                <table aria-describedby="mydesc" class='table-striped' id='contest_list'
                                                       data-toggle="table"
                                                       data-url="get-list.php?table=contest_prize"
                                                       data-click-to-select="true"
                                                       data-side-pagination="server"
                                                       data-pagination="true"
                                                       data-page-list="[5, 10, 20, 50, 100, 200]"
                                                       data-search="true" data-show-columns="true"
                                                       data-show-refresh="true" data-trim-on-search="false"
                                                       data-sort-name="top_winner" data-sort-order="asc"
                                                       data-mobile-responsive="true"
                                                       data-toolbar="#toolbar" data-show-export="false"
                                                       data-maintain-selected="true"
                                                       data-export-types='["txt","excel"]'
                                                       data-export-options='{
                                                       "fileName": "category-list-<?= date('d-m-y') ?>",
                                                       "ignoreColumn": ["state"]	
                                                       }'
                                                       data-query-params="queryParams">
                                                    <thead>
                                                        <tr>
                                                            <th scope="col" data-field="state" data-checkbox="true"></th>
                                                            <th scope="col" data-field="id" data-sortable="true">ID</th>                                                           
                                                            <th scope="col" data-field="name" data-sortable="false">Name</th>
                                                            <th scope="col" data-field="top_winner" data-sortable="true">Top Winner</th>
                                                            <th scope="col" data-field="points" data-sortable="true">Coins</th>
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
                </div>
            </div>
            <!-- /page content -->
            <div class="modal fade" id='editDataModel' tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel">
                <div class="modal-dialog modal-md" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            <h4 class="modal-title" id="myModalLabel">Edit Prize</h4>
                        </div>
                        <div class="modal-body">
                            <form id="update_form"  method="POST" action ="db_operations.php" data-parsley-validate class="form-horizontal form-label-left">
                                <input type='hidden' name="update_contest_prize" id="update_contest_prize" value='1'/>
                                <input type='hidden' name="prize_id" id="prize_id" value=''/>

                                <div class="form-group">
                                    <label for="winner">Top Winner</label>
                                    <input type="number" name="winner" id="update_winner" readonly class='form-control' required>
                                </div>
                                <div class="form-group">
                                    <label for="points">Prize</label>
                                    <input type="number" name="points" id="update_points" class='form-control' min="0" required>
                                </div>
                                <div class="ln_solid"></div>
                                <div class="form-group">
                                    <div class="col-md-6 col-sm-6 col-xs-12 col-md-offset-3">
                                        <button type="submit" id="update_btn" class="btn btn-success">Update</button>
                                    </div>
                                </div>
                            </form>
                            <div class="row"><div  class="col-md-offset-3 col-md-8" style ="display:none;" id="update_result"></div></div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- footer content -->
            <?php include 'footer.php'; ?>
            <!-- /footer content -->
        </div>

        <!-- jQuery -->

        <script>
            var $table = $('#contest_list');
            $('#toolbar').find('select').change(function () {
                $table.bootstrapTable('refreshOptions', {
                    exportDataType: $(this).val()
                });
            });
        </script>

        <script>
            window.actionEvents = {
                'click .edit-data': function (e, value, row, index) {
                    // alert('You click remove icon, row: ' + JSON.stringify(row));
                    $('#prize_id').val(row.id);
                    $('#update_winner').val(row.top_winner);
                    $('#update_points').val(row.points);
                }
            };
        </script>
        <script>
            $('#update_form').on('submit', function (e) {
                e.preventDefault();
                var formData = new FormData(this);
                if ($("#update_form").validate().form()) {
                    $.ajax({
                        type: 'POST',
                        url: $(this).attr('action'),
                        data: formData,
                        beforeSend: function () {
                            $('#update_btn').html('Please wait..');
                        },
                        cache: false,
                        contentType: false,
                        processData: false,
                        success: function (result) {
                            $('#update_result').html(result);
                            $('#update_result').show().delay(3000).fadeOut();
                            $('#update_btn').html('Update');
                            $('#update_image').val('');
                            $('#contest_list').bootstrapTable('refresh');
                            setTimeout(function () {
                                $('#editDataModel').modal('hide');
                            }, 4000);
                        }
                    });
                }
            });
        </script>
        <script>
            function queryParams(p) {
                return {
                    "contest_id": $('#contest_id').val(),
                    limit: p.limit,
                    sort: p.sort,
                    order: p.order,
                    offset: p.offset,
                    search: p.search
                };
            }
        </script>
        <script>
            $('#contest_form').validate({
                rules: {
                    name: "required"
                }
            });
        </script>
        <script>
            $('#contest_form').on('submit', function (e) {
                e.preventDefault();
                var formData = new FormData(this);
                if ($("#contest_form").validate().form()) {
                    if (confirm('Are you sure? Want to create prize')) {
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
                                $('#contest_form')[0].reset();
                                $('#result').show().delay(4000).fadeOut();
                                $('#submit_btn').html('Submit');
                                $('#contest_list').bootstrapTable('refresh');
                                setTimeout(function () {

                                    window.location.reload();
                                }, 3000);
                            }
                        });
                    }
                }
            });
        </script>
        <script>
            $(document).on('click', '.delete-data', function () {
                if (confirm('Are you sure? Want to delete prize?')) {
                    id = $(this).data("id");
                    $.ajax({
                        url: 'db_operations.php',
                        type: "get",
                        data: 'id=' + id + '&delete_contest_prize=1',
                        success: function (result) {
                            if (result == 1) {
                                $('#contest_form')[0].reset();
                                window.location.reload();
                                $('#contest_list').bootstrapTable('refresh');
                            } else
                                alert('Error! prize could not be deleted');
                        }
                    });
                }
            });
        </script>

    </body>
</html>