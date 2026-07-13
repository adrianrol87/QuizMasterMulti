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
        <title>Home | <?= ucwords($_SESSION['company_name']) ?> </title>
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
                        <div role="main">
                            <!-- top tiles -->
                            <h1 style="color:black;font-size:29px;text-align:center;">Welcome to <?= ucwords($_SESSION['company_name']) ?> - Admin Panel</h1>
                            <hr>
                            <div class="animated flipInY col-lg-3 col-md-3 col-sm-6 col-xs-12">
                                <div class="tile-stats">
                                    <div class="icon"><em class="fas fa-user-friends"></em>
                                    </div>
                                    <div class="count"><?= get_count('id', 'category', ''); ?></div>
                                    <h3>Total Categories</h3>
                                </div>
                            </div>
                            <div class="animated flipInY col-lg-3 col-md-3 col-sm-6 col-xs-12">
                                <div class="tile-stats">
                                    <div class="icon"><em class="fas fa-users"></em>
                                    </div>
                                    <div class="count"><?= get_count('id', 'subcategory'); ?></div>
                                    <h3>Total Sub Categories</h3>
                                </div>
                            </div>
                            <div class="animated flipInY col-lg-3 col-md-3 col-sm-6 col-xs-12">
                                <div class="tile-stats">
                                    <div class="icon"><em class="far fa-question-circle"></em>
                                    </div>
                                    <div class="count"><?= get_count('id', 'question', ''); ?></div>
                                    <h3>Total Questions</h3>
                                </div>
                            </div>
                            <div class="animated flipInY col-lg-3 col-md-3 col-sm-6 col-xs-12">
                                <div class="tile-stats">
                                    <div class="icon"><em class="fas fa-recycle"></em>
                                    </div>
                                    <div class="count"><?= get_count('id', 'users', ''); ?></div>
                                    <h3>Registered Devices</h3>
                                </div>
                            </div>
                            <!-- /top tiles -->
                        </div>
                    </div>
                    <div class="row">
                        <div role="main">
                            <?php if (checkadmin($auth_username)) { ?>
                                <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                                    <?php
                                    $year = date("Y");
                                    $sql = "SELECT COUNT(id) AS month_wise_count,MONTHNAME(date_created) AS month_name FROM monthly_leaderboard WHERE YEAR(date_created) = '$year' GROUP BY MONTH(date_created)";
                                    $db->sql($sql);
                                    $result = $db->getResult();
                                    ?>
                                    <div class="tile-stats" style="padding:10px 5px 10px 10px;">
                                        <div id="columnchart_material" style="width:100%;height:350px;"></div>
                                    </div>
                                </div>
                                <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                                    <?php
                                    $year = date("Y");
                                    $sql = "SELECT COUNT(id) AS month_wise_user_count, MONTHNAME(date_registered) AS month_name_wise_user_count FROM users WHERE YEAR(date_registered) = '$year' GROUP BY MONTH(date_registered)";
                                    $db->sql($sql);
                                    $users = $db->getResult();
                                    ?>
                                    <div class="tile-stats" style="padding:10px 5px 10px 10px;">
                                        <div id="columnchart_material_2" style="width:100%;height:350px;"></div>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php include 'footer.php'; ?>
        </div>
        <!-- jQuery -->
        <?php if (checkadmin($auth_username)) { ?>
            <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
            <script type="text/javascript">
                google.charts.load('current', {'packages': ['bar']});
                google.charts.setOnLoadCallback(drawChart);
                function drawChart() {
                    var data = google.visualization.arrayToDataTable([
                        ['Month', 'Participated'],
                    <?php
                    foreach ($result as $row) {
                        echo "['" . $row['month_name'] . "'," . $row['month_wise_count'] . "],";
                    }
                    ?>
                    ]);
                    var options = {
                        chart: {
                            title: 'Monthly Leaderboard',
                            subtitle: 'Total Users Participated in Quiz Month Wise (Year: <?php echo date("Y"); ?>)',
                        }
                    };
                    var chart = new google.charts.Bar(document.getElementById('columnchart_material'));
                    chart.draw(data, google.charts.Bar.convertOptions(options));
                }

                google.charts.load('current', {'packages': ['bar']});
                google.charts.setOnLoadCallback(drawChart1);
                function drawChart1() {
                    var data = google.visualization.arrayToDataTable([
                        ['Month', 'Registered'],
                    <?php
                    foreach ($users as $row) {
                        echo "['" . $row['month_name_wise_user_count'] . "'," . $row['month_wise_user_count'] . "],";
                    }
                    ?>
                    ]);
                    var options = {
                        chart: {
                            title: 'Monthly Registered Users',
                            subtitle: 'Total Users Registered Month Wise (Year: <?php echo date("Y"); ?>)',
                        }
                    };
                    var chart = new google.charts.Bar(document.getElementById('columnchart_material_2'));
                    chart.draw(data, google.charts.Bar.convertOptions(options));
                }
            </script>
        <?php } ?>
    </body>
</html>