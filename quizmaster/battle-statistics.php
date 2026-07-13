<?php
session_start();
if (!isset($_SESSION['id']) && !isset($_SESSION['username'])) {
    header("location:index.php");
    return false;
    exit();
}
if (!isset($_GET['user_id']) || !is_numeric($_GET['user_id'])) {
    header("location:users.php");
    return false;
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
                <?php
                $db->sql("SET NAMES 'utf8'");
                if (isset($_GET['user_id'])) {
                    $user_id = $db->escapeString($_GET['user_id']);
                }
                $sql = "SELECT (SELECT COUNT(`winner_id`) FROM battle_statistics WHERE winner_id= $user_id) AS Victories,(SELECT COUNT(`is_drawn`) FROM battle_statistics WHERE (user_id1= $user_id || user_id2= $user_id)AND is_drawn=1) AS Drawn,(SELECT COUNT(`winner_id`) FROM battle_statistics WHERE (user_id1= $user_id || user_id2= $user_id) AND winner_id != $user_id and is_drawn = 0 )AS Loose,(SELECT name FROM users WHERE id= $user_id) AS name";
                $db->sql($sql);
                $result = $db->getResult();

                $sql = "SELECT us.*,u.name,u.profile,(SELECT category_name FROM category c WHERE c.id=us.strong_category) as strong_category, (SELECT category_name FROM category c WHERE c.id=us.weak_category) as weak_category FROM `users_statistics` us
                        LEFT JOIN users u on u.id = us.user_id WHERE `user_id`=" . $user_id;
                $db->sql($sql);
                $result1 = $db->getResult();
                ?>
                <!-- page content -->
                <div class="right_col" role="main">
                    <!-- top tiles -->
                    <br />
                    <div class="row">
                        <div class="col-md-12 col-sm-12 col-xs-12">
                            <div class="x_panel">
                                <div class="x_title">
                                    <h2>Statistics Details for <?= $result[0]['name'] ? $result[0]['name'] : 0; ?></h2>
                                    <div class="clearfix"></div>
                                </div>

                                <?php if (!empty($result1)) { ?>
                                    <div class="col-md-12 col-sm-12 col-xs-12">
                                        <h4>General Statistics</h4><hr>
                                        <div class="form-group">
                                            <label class="control-label col-md-4">Questions Answered: <?= $result1[0]['questions_answered'] ? $result1[0]['questions_answered'] : 0; ?></label>
                                            <label class="control-label col-md-4">Correct Answers: <?= $result1[0]['correct_answers'] ? $result1[0]['correct_answers'] : 0; ?></label>
                                            <label class="control-label col-md-4">Best Position: <?= $result1[0]['best_position'] ? $result1[0]['best_position'] : 0; ?></label>
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label col-md-4">Strong Category: <?= $result1[0]['strong_category'] ? $result1[0]['strong_category'] : 0; ?></label>
                                            <label class="control-label col-md-4">Weak Category: <?= $result1[0]['weak_category'] ? $result1[0]['weak_category'] : 0; ?></label>
                                        </div>
                                    </div>
                                <?php } ?>
                                <div class="col-md-12 col-sm-12 col-xs-12">
                                    <hr><h4>Battle Statistics</h4><hr>
                                    <div class="form-group">
                                        <label class="control-label col-md-4">Victories: <?= $result[0]['Victories'] ? $result[0]['Victories'] : 0; ?></label>
                                        <label class="control-label col-md-4">Drawn: <?= $result[0]['Drawn'] ? $result[0]['Drawn'] : 0; ?></label>
                                        <label class="control-label col-md-4">Loose: <?= $result[0]['Loose'] ? $result[0]['Loose'] : 0; ?></label>
                                    </div>
                                </div>
                                <div class="x_content">                                   
                                    <table aria-describedby="mydesc" class='table-striped' id='battle_statistics'
                                           data-toggle="table"
                                           data-url="get-list.php?table=battle_statistics"
                                           data-click-to-select="true"
                                           data-side-pagination="server"
                                           data-pagination="true" 
                                           data-page-list="[5, 10, 20, 50, 100, 200]"
                                           data-search="true" data-show-columns="true"
                                           data-show-refresh="true" data-trim-on-search="false"
                                           data-sort-name="id" data-sort-order="asc"
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
                                                <th scope="col" data-field="state" data-checkbox="true"></th>
                                                <th scope="col" data-field="id" data-sortable="true">ID</th>
                                                <th scope="col" data-field="opponent_id" data-sortable="true" data-visible="false">Opponent ID</th>
                                                <th scope="col" data-field="opponent_name">Opponent Name</th>
                                                <th scope="col" data-field="mystatus">My Status</th>
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