        <?php
        include 'library/crud.php';
        include 'library/functions.php';
        $fn = new Functions();
        $config = $fn->get_configurations();
        $db = new Database();
        $db->connect();

        if (isset($config['system_timezone']) && !empty($config['system_timezone'])) {
            date_default_timezone_set($config['system_timezone']);
        } else {
            date_default_timezone_set('Asia/Kolkata');
        }

        if (isset($config['system_timezone_gmt']) && !empty($config['system_timezone_gmt'])) {
            $db->sql("SET `time_zone` = '" . $config['system_timezone_gmt'] . "'");
        } else {
            $db->sql("SET `time_zone` = '+05:30'");
        }

        function get_count($field, $table, $where = '') {
            if (!empty($where))
                $where = "where " . $where;

            $sql = "SELECT COUNT(" . $field . ") as total FROM " . $table . " " . $where;
            global $db;
            $db->sql($sql);
            $res = $db->getResult();
            foreach ($res as $row)
                return $row['total'];
        }

        $auth_username = $db->escapeString($_SESSION["username"]);

        function checkadmin($auth_username) {
            $db = new Database();
            $db->connect();
            $db->sql("SELECT `auth_username`,`role` FROM `authenticate` WHERE `auth_username`='$auth_username' LIMIT 1");
            $res = $db->getResult();
            if (!empty($res)) {
                if ($res[0]["role"] == "admin") {
                    return true;
                } else {
                    return false;
                }
            }
        }

        if (!checkadmin($auth_username)) {
            $pages = array('languages.php', 'users.php', 'monthly-leaderboard.php', 'send-notifications.php', 'user-accounts-rights.php', 'notification-settings.php', 'privacy-policy.php', 'delete-account.php');
            foreach ($pages as $page) {
                if (basename($_SERVER['PHP_SELF']) == $page) {
                    exit("<center><h2 style='color:#fff;'><br><br><br><br><em style='color:#f7d701;' class='fas fa-exclamation-triangle fa-4x'></em><br><br>Access denied - You are not authorized to access this.</h2></center>");
                }
            }
        }
        if (basename($_SERVER['PHP_SELF']) == 'languages.php' && !$fn->is_language_mode_enabled()) {
            exit("<center><h2 style='color:#fff;'><br><br><br><br><em style='color:#f7d701;' class='fas fa-exclamation-triangle fa-4x'></em><br><br>Language mode is disabled - You are not allowed to access this page.</h2></center>");
        }
        ?>
        <div class="col-md-3 left_col">
            <div class="left_col scroll-view">
                <div class="navbar nav_title text-center" style="border: 0;">
                    <img src="images/logo-460x114.png" alt="logo" width="230" class="md">
                    <img src="images/logo-half.png" alt="logo" width="56" class="sm">
                </div>
                <div class="clearfix"></div>
                <!-- menu profile quick info -->
                <div class="profile clearfix text-center">                   
                    <div class="profile_info">
                        <h2> <?= ucwords($_SESSION['company_name']) ?> Admin Panel</h2>
                    </div>
                </div>
                <!-- /menu profile quick info -->
                <!-- sidebar menu -->
                <div id="sidebar-menu" class="main_menu_side hidden-print main_menu">
                    <div class="menu_section">

                        <ul class="nav side-menu">
                            <li><a href ="home.php"><em class="fas fa-home"></em> Home</a></li>
                            <?php if (checkadmin($auth_username)) { ?>
                                <li><a href ="users.php"><em class="fas fa-users"></em> Users</a></li>
                                <li>
                                    <a><em class="fas fa-th"></em> Leaderboard<span class="fas fa-caret-down"></span></a>
                                    <ul class="nav child_menu">
                                        <li><a href="global-leaderboard.php">All</a></li>
                                        <li><a href="monthly-leaderboard.php">Monthly</a></li>
                                        <li><a href="daily-leaderboard.php">Daily</a></li>                                       
                                    </ul>
                                </li>                                   
                                <?php if ($fn->is_language_mode_enabled()) { ?>
                                    <li><a href="languages.php"><em class="fas fa-language"></em> Languages</a></li>
                                    <?php
                                }
                            }
                            ?>
                            <li>
                                <a><em class="fas fa-book"></em> Quiz Zone<span class="fas fa-caret-down"></span></a>
                                <ul class="nav child_menu">
                                    <li><a href="main-category.php">Main Category</a></li>
                                    <li><a href="sub-category.php">Sub Category</a></li>
                                    <li><a href="category-order.php">Category Order</a></li>
                                    <li><a href="questions.php">Questions</a></li>
                                </ul>
                            </li>
                            <li><a href="daily-quiz.php"><em class="fas fa-question"></em> Daily Quiz</a></li>                          
                            <li>
                                <a><em class="fas fa-th"></em> Word Search<span class="fas fa-caret-down"></span></a>
                                <ul class="nav child_menu">
                                    <li><a href="word-search-category.php">Categories</a></li>
                                    <li><a href="word-search-import.php">Import Levels</a></li>
                                </ul>
                            </li>
                            <li>
                                <a><em class="fas fa-th-large"></em> 2048 Retos<span class="fas fa-caret-down"></span></a>
                                <ul class="nav child_menu">
                                    <li><a href="game-2048-challenges-import.php">Import Levels</a></li>
                                </ul>
                            </li>
                            <li>
                                <a><em class="fas fa-book"></em> Learning Zone<span class="fas fa-caret-down"></span></a>
                                <ul class="nav child_menu">
                                    <li><a href="learning-category.php">Category</a></li>
                                    <li><a href="learning-category-order.php">Category Order</a></li>
                                    <li><a href="learning.php"> Manage Learning</a></li>
                                </ul>
                            </li>
                            <li>
                                <a><em class="fas fa-book-open"></em> Maths Quiz<span class="fas fa-caret-down"></span></a>
                                <ul class="nav child_menu">
                                    <li><a href="maths-category.php">Category</a></li>
                                    <li><a href="maths-subcategory.php">Sub Category</a></li>
                                    <li><a href="maths-category-order.php">Category Order</a></li>
                                    <li><a href="maths-questions.php">Create Questions</a></li>
                                    <li><a href="maths-questions-view.php">View Questions</a></li>
                                </ul>
                            </li>
                            <li>
                                <a><i class="fas fa-gift"></i> Contests<span class="fas fa-caret-down"></span></a>
                                <ul class="nav child_menu">
                                    <li><a href="contest.php"><i class="fas fa-gift"></i> Manage Contest</a></li>
                                    <li><a href="contest-questions.php"><i class="fas fa-question-circle"></i> Manage Questions</a></li>
                                    <li><a href="contest-questions-import.php"><i class="fas fa-upload"></i> Import Questions</a></li>
                                </ul>
                            </li>
                            <li><a href="question-reports.php"><em class="far fa-question-circle"></em> Question Reports</a></li>
                            <?php if (checkadmin($auth_username)) { ?>
                                <li><a href="send-notifications.php"><em class="fas fa-bullhorn"></em> Send Notifications</a></li>
                            <?php } ?>
                            <li>
                                <a><em class="fas fa-upload"></em> Imports<span class="fas fa-caret-down"></span></a>
                                <ul class="nav child_menu">
                                    <li><a href="import-questions.php">Import Questions</a></li>
                                    <li><a href="import-categories.php">Import Main Categories</a></li>
                                    <li><a href="import-subcategories.php">Import Sub Categories</a></li>
                                </ul>
                            </li>
                            <?php if (checkadmin($auth_username)) { ?>
                                <li><a href="user-accounts-rights.php"><em class="fas fa-user"></em> User Accounts and Rights</a></li>
                                <li>
                                    <a><em class="fas fa-cog"></em> Settings<span class="fas fa-caret-down"></span></a>
                                    <ul class="nav child_menu">
                                        <li><a href="system-configurations.php">System Configurations</a></li>
                                        <li><a href="notification-settings.php">Notification Settings</a></li>
                                        <li><a href="about-us.php">About Us</a></li>
                                        <li><a href="instructions.php">Instructions</a></li>
                                        <li><a href="privacy-policy.php">Privacy Policy</a></li>
                                        <li><a href="delete-account.php">Delete Account</a></li>
                                        <li><a href="terms-conditions.php">Terms Conditions</a></li>    
                                        <li><a href="web-firebase-settings.php">Web Firebase Settings</a></li>                                       
                                    </ul>
                                </li>
                                <li><a href="system-update.php"><em class="fas fa-cloud-download-alt"></em> System Update</a></li>
                            <?php } ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <!-- top navigation -->
        <div class="top_nav">
            <div class="nav_menu">
                <nav>
                    <div class="nav toggle">
                        <a id="menu_toggle"><em class="fa fa-bars"></em></a>
                    </div>
                    <ul class="nav navbar-nav navbar-right">
                        <li class="">
                            <a href="javascript:;" class="user-profile dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                                <?= ucwords($_SESSION['username']) ?>
                                <span class=" fa fa-angle-down"></span>
                            </a>
                            <ul class="dropdown-menu dropdown-usermenu pull-right">
                                <li><a href="password.php"><em class="fa fa-key pull-right"></em> Change Password</a></li>
                                <li><a href="logout.php"><em class="fas fa-sign-out-alt pull-right"></em> Log Out</a></li>
                            </ul>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
        <!-- /top navigation -->
