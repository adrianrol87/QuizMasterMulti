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
        <title>Monthly Leaderboard Details | <?= ucwords($_SESSION['company_name']) ?> - Admin Panel </title>
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
                                    <h2>Monthly Leaderboard Details <small>View month wise leaderboard</small></h2>
                                    <div class="clearfix"></div>
                                </div>
                                <div class="x_content">
                                    <table aria-describedby="mydesc" class='table-striped' id='monthly_leaderboard'
                                           data-toggle="table"
                                           data-url="get-list.php?table=monthly_leaderboard"
                                           data-click-to-select="true"
                                           data-side-pagination="server"
                                           data-pagination="true"
                                           data-page-list="[5, 10, 20, 50, 100, 200]"
                                           data-search="true" data-show-columns="true"
                                           data-show-refresh="true" data-trim-on-search="false"
                                           data-sort-name="score" data-sort-order="desc"
                                           data-mobile-responsive="true"
                                           data-toolbar="#toolbar" data-show-export="false"
                                           data-maintain-selected="true"
                                           data-export-types='["txt","excel"]'
                                           data-export-options='{
                                           "fileName": "monthly-leaderboard-user-list-<?= date('d-m-y') ?>",
                                           "ignoreColumn": ["state"]	
                                           }'
                                           data-query-params="queryParams_1"
                                           >
                                        <thead>
                                            <tr>
                                                <th scope="col" data-field="id" data-sortable="true">ID</th>
                                                <th scope="col" data-field="user_id" data-sortable="true" data-visible="false">User ID</th>
                                                <th scope="col" data-field="name" data-sortable="true">Name</th>
                                                <th scope="col" data-field="email" data-sortable="true">Email</th>
                                                <th scope="col" data-field="score" data-sortable="true">Score</th>
                                                <th scope="col" data-field="user_rank" data-sortable="true">Rank</th>
                                                <th scope="col" data-field="last_updated" data-sortable="true">Last Updated</th>
                                                <th scope="col" data-field="date_created" data-sortable="true">Date Created</th>
                                            </tr>
                                        </thead>
                                    </table>
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

        <!-- jQuery -->
        <script>
            function queryParams_1(p) {
                return {
                    'user_id': '<?= (isset($_GET['user_id'])) ? $_GET['user_id'] : '' ?>',
                    limit: p.limit,
                    sort: p.sort,
                    order: p.order,
                    offset: p.offset,
                    search: p.search
                };
            }
        </script>
    </body>
</html>