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
        <title>Question Reports by users | <?= ucwords($_SESSION['company_name']) ?> - Admin Panel </title>
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
                                    <h2>Questions Reported by Users</h2>
                                    <div class="clearfix"></div>
                                </div>
                                <div class="x_content">
                                    <div class='row'>
                                        <div class='col-md-12'>
                                            <div class="row" id="toolbar">
                                                <div class="col-md-3">
                                                    <button class="btn btn-danger btn-sm" id="delete_multiple_question_reports" title="Delete Selected Question Reports"><em class='fa fa-trash'></em></button>
                                                </div>
                                            </div>
                                            <table aria-describedby="mydesc" class='table-striped' id='report_list'
                                                   data-toggle="table"
                                                   data-url="get-list.php?table=question_reports"
                                                   data-click-to-select="true"
                                                   data-side-pagination="server"
                                                   data-pagination="true"
                                                   data-page-list="[5, 10, 20, 50, 100, 200]"
                                                   data-search="true" data-show-columns="true"
                                                   data-show-refresh="true" data-trim-on-search="false"
                                                   data-sort-name="id" data-sort-order="desc"
                                                   data-mobile-responsive="true"
                                                   data-toolbar="#toolbar" 
                                                   data-maintain-selected="true"
                                                   data-show-export="false" data-export-types='["txt","excel"]'
                                                   data-export-options='{
                                                   "fileName": "users-list-<?= date('d-m-y') ?>",
                                                   "ignoreColumn": ["state"]	
                                                   }'
                                                   data-query-params="queryParams_1"
                                                   >
                                                <thead>
                                                    <tr>
                                                        <th scope="col" data-field="state" data-checkbox="true"></th>
                                                        <th scope="col" data-field="id" data-sortable="true">ID</th>
                                                        <th scope="col" data-field="user_id" data-sortable="true" data-visible='false'>User ID</th>
                                                        <th scope="col" data-field="name" data-sortable="true">Name</th>
                                                        <th scope="col" data-field="question_id" data-sortable="true" data-visible='false'>Question ID</th>
                                                        <th scope="col" data-field="question" data-sortable="true">Question</th>
                                                        <th scope="col" data-field="message" data-sortable="true">Message</th>
                                                        <th scope="col" data-field="date" data-sortable="true">Date</th>
                                                        <th scope="col" data-field="operate">Operate</th>
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
            <!-- footer content -->
            <?php include 'footer.php'; ?>
            <!-- /footer content -->
        </div>

        <script>
            function queryParams_1(p) {
                return {
                    limit: p.limit,
                    sort: p.sort,
                    order: p.order,
                    offset: p.offset,
                    search: p.search
                };
            }
            $('#delete_multiple_question_reports').on('click', function (e) {
                sec = 'question_reports';
                is_image = 0;
                table = $('#report_list');
                delete_button = $('#delete_multiple_question_reports');
                selected = table.bootstrapTable('getAllSelections');
                // alert(selected[0].id);
                ids = "";
                $.each(selected, function (i, e) {
                    ids += e.id + ",";
                });
                ids = ids.slice(0, -1); // removes last comma character
                if (ids == "") {
                    alert("Please select some question reports to delete!");
                } else {
                    if (confirm("Are you sure you want to delete all selected question reports?")) {
                        $.ajax({
                            type: 'GET',
                            url: "db_operations.php",
                            data: 'delete_multiple=1&ids=' + ids + '&sec=' + sec + '&is_image=' + is_image,
                            beforeSend: function () {
                                delete_button.html('<i class="fa fa-spinner fa-pulse"></i>');
                            },
                            success: function (result) {
                                if (result == 1) {
                                    alert("Question reports deleted successfully");
                                } else {
                                    alert("Could not delete question reports. Try again!");
                                }
                                delete_button.html('<i class="fa fa-trash"></i>');
                                table.bootstrapTable('refresh');
                            }
                        });
                    }
                }
            });
        </script>
        <!-- jQuery -->
        <script>
            $(document).on('click', '.delete-report', function () {
                if (confirm('Are you sure? Want to delete report')) {
                    id = $(this).data("id");
                    $.ajax({
                        url: 'db_operations.php',
                        type: "get",
                        data: 'id=' + id + '&delete_question_report=1',
                        success: function (result) {
                            if (result == 1) {
                                $('#report_list').bootstrapTable('refresh');
                            } else
                                alert('Error! Report could not be deleted');
                        }
                    });
                }
            });
        </script>
    </body>
</html>