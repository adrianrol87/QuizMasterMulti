<?php

/*
  API v5.6
  Quiz Online - WRTeam.in
  WRTeam Developers
 */
session_start();
if (!isset($_SESSION['id']) && !isset($_SESSION['username'])) {
    header("location:index.php");
    return false;
    exit();
}
header("Content-Type: application/json");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

include('library/crud.php');
include('library/functions.php');
$db = new Database();
$db->connect();

$fn = new Functions();
$config = $fn->get_configurations();

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

$db->sql("SET NAMES 'utf8'");

/*
  1. category
  2. subcategory
  3. users
  4. global_leaderboard
  5. monthly_leaderboard
  6. daily_leaderboard
  7. admin
  8. question
  9. question_reports
  10. notifications
  11. languages
  12. battle_statistics
  13. contest
  14. contest_prize
  15. contest_questions
  16. contest_leaderboard
  17. learning_zone
  18. learnning_question
  19. maths_question
 */

// 1. category
if (isset($_GET['table']) && $_GET['table'] == 'category') {
    $offset = 0;
    $limit = 10;
    $sort = '`row_order` + 0 ';
    $order = 'ASC';
    $where = '';
    $table = $_GET['table'];

    if (isset($_GET['sort'])) {
        $sort = $_GET['sort'];
        if ($sort == 'row_order')
            $sort = '`row_order` + 0 ';
    }
    if (isset($_GET['offset']))
        $offset = $_GET['offset'];
    if (isset($_GET['limit']))
        $limit = $_GET['limit'];
    if (isset($_GET['order']))
        $order = $_GET['order'];

    if (isset($_GET['type']) && !empty($_GET['type'])) {
        $type = $_GET['type'];
        $where = ' WHERE `type` = ' . $type;
        if ($type == 1 || $type == '1') {
            $total_question = ", (SELECT count(id) FROM question WHERE question.category = c.id ) as no_of_que";
        }
        if ($type == 2 || $type == '2') {
            $total_question = ", (SELECT count(id) FROM tbl_learning WHERE tbl_learning.category = c.id ) as no_of_que";
        }
        if ($type == 3 || $type == '3') {
            $total_question = ", (SELECT count(id) FROM tbl_maths_question WHERE tbl_maths_question.category = c.id ) as no_of_que";
        }
    }

    if (isset($_GET['language']) && !empty($_GET['language'])) {
        $where .= ' AND `language_id` = ' . $_GET['language'];
    }

    if (isset($_GET['search'])) {
        $search = $_GET['search'];
        $where .= " AND ( c.`id` like '%" . $search . "%' OR c.`category_name` like '%" . $search . "%' OR l.`language` like '%" . $search . "%' )";
    }

    $left_join = " LEFT JOIN languages l on l.id = c.language_id ";

    $sql = "SELECT COUNT(c.id) as total FROM `category` c " . $left_join . " " . $where;
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row) {
        $total = $row['total'];
    }

    $sql = "SELECT c.*, l.language as language " . $total_question . " FROM `category` c " . $left_join . " " . $where . " ORDER BY " . $sort . " " . $order . " LIMIT " . $offset . ", " . $limit;
    $db->sql($sql);
    $res = $db->getResult();

    $bulkData = array();
    $bulkData['total'] = $total;
    $rows = array();
    $tempRow = array();

    foreach ($res as $row) {
        $image = (!empty($row['image'])) ? 'images/category/' . $row['image'] : '';
        $operate = "<a class='btn btn-xs btn-primary edit-category' data-id='" . $row['id'] . "' data-toggle='modal' data-target='#editCategoryModal' title='Edit'><i class='fas fa-edit'></i></a>";
        $operate .= "<a class='btn btn-xs btn-danger delete-category' data-id='" . $row['id'] . "' data-image='" . $image . "' title='Delete'><i class='fas fa-trash'></i></a>";
        $operate .= "<a class='btn btn-xs btn-success edit-status' data-id='" . $row['id'] . "' data-toggle='modal' data-target='#editStatusModal' title='Edit Status'><i class='fas fa-edit'></i></a>";

        $tempRow['id'] = $row['id'];
        $tempRow['language'] = $row['language'];
        $tempRow['language_id'] = $row['language_id'];
        $tempRow['category_name'] = $row['category_name'];
        $tempRow['plan'] = $row['plan'];
        $tempRow['amount'] = $row['amount'];
        $tempRow['row_order'] = $row['row_order'];
        $tempRow['image'] = (!empty($row['image'])) ? '<a href="' . $image . '" data-lightbox="Category Images"><img src="' . $image . '" height=30 ></a>' : '<img src="images/logo-half.png" height=30>';
        $tempRow['no_of_que'] = $row['no_of_que'];
        $tempRow['operate'] = $operate;
        $tempRow['status'] = ($row['status']) ? "<label class='label label-success'>Active</label>" : "<label class='label label-danger'>Deactive</label>";



        $rows[] = $tempRow;
    }

    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}

// 2. subcategory
if (isset($_GET['table']) && $_GET['table'] == 'subcategory') {
    $offset = 0;
    $limit = 10;
    $sort = 'row_order';
    $order = 'ASC';
    $where = '';
    $table = $_GET['table'];

    if (isset($_GET['sort'])) {
        $sort = $_GET['sort'];
        if ($sort == 'row_order')
            $sort = 's.`row_order` + 0 ';
    }
    if (isset($_GET['offset']))
        $offset = $_GET['offset'];
    if (isset($_GET['limit']))
        $limit = $_GET['limit'];
    if (isset($_GET['order']))
        $order = $_GET['order'];

    if (isset($_GET['type']) && !empty($_GET['type'])) {
        $type = $_GET['type'];
        $where = ' WHERE c.type = ' . $type;
        if ($type == 1 || $type == '1') {
            $total_question = ", (SELECT count(id) FROM question WHERE question.subcategory=s.id ) as no_of_que";
        }
        if ($type == 3 || $type == '3') {
            $total_question = ", (SELECT count(id) FROM tbl_maths_question WHERE tbl_maths_question.subcategory = s.id ) as no_of_que";
        }
    }

    if (isset($_GET['language']) && !empty($_GET['language'])) {
        $where .= ' AND s.`language_id` = ' . $_GET['language'];
        if (isset($_GET['category']) && !empty($_GET['category'])) {
            $where .= ' AND `maincat_id`=' . $_GET['category'];
        }
    } elseif (isset($_GET['category']) && !empty($_GET['category'])) {
        $where .= ' AND `maincat_id`=' . $_GET['category'];
    }

    if (isset($_GET['search'])) {
        $search = $_GET['search'];
        $where .= " AND (s.`id` like '%" . $search . "%' OR s.`maincat_id` like '%" . $search . "%' OR s.`subcategory_name` like '%" . $search . "%' OR l.`language` like '%" . $search . "%' OR c.`category_name` like '%" . $search . "%' )";
    }

    $left_join = " LEFT JOIN languages l on l.id = s.language_id ";
    $left_join .= " LEFT JOIN category c ON c.id = s.maincat_id ";

    $sql = "SELECT COUNT(s.id) as total FROM `subcategory` s " . $left_join . " " . $where;
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row) {
        $total = $row['total'];
    }

    $sql = "SELECT s.*, l.language, c.`category_name` " . $total_question . " FROM `subcategory` s " . $left_join . " " . $where . " ORDER BY " . $sort . " " . $order . " LIMIT " . $offset . ", " . $limit;

    $db->sql($sql);
    $res = $db->getResult();

    $bulkData = array();
    $bulkData['total'] = $total;
    $rows = array();
    $tempRow = array();

    foreach ($res as $row) {
        $image = (!empty($row['image'])) ? 'images/subcategory/' . $row['image'] : '';
        $operate = "<a class='btn btn-xs btn-primary edit-subcategory' data-id='" . $row['id'] . "' data-toggle='modal' data-target='#editCategoryModal' title='Edit'><i class='fas fa-edit'></i></a>";
        $operate .= "<a class='btn btn-xs btn-danger delete-subcategory' data-id='" . $row['id'] . "' data-image='" . $image . "' title='Delete'><i class='fas fa-trash'></i></a>";

        $tempRow['id'] = $row['id'];
        $tempRow['language_id'] = $row['language_id'];
        $tempRow['language'] = $row['language'];
        $tempRow['maincat_id'] = $row['maincat_id'];
        $tempRow['category_name'] = $row['category_name'];
        $tempRow['subcategory_name'] = $row['subcategory_name'];
        $tempRow['row_order'] = $row['row_order'];
        $tempRow['image'] = (!empty($row['image'])) ? '<a href="' . $image . '" data-lightbox="Sub Category Images"><img src="' . $image . '" height=30 ></a>' : '<img src="images/logo-half.png" height=30>';
        $tempRow['status'] = ($row['status']) ? '<label class="label label-success">Active</label>' : '<label class="label label-danger">Deactive</label>';
        $tempRow['no_of_que'] = $row['no_of_que'];
        $tempRow['operate'] = $operate;
        $rows[] = $tempRow;
    }

    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}

// word_search_category
if (isset($_GET['table']) && $_GET['table'] == 'word_search_category') {
    $offset = 0;
    $limit = 10;
    $sort = '`row_order` + 0 ';
    $order = 'ASC';
    $where = ' WHERE 1=1 ';

    if (isset($_GET['sort'])) {
        $sort = $_GET['sort'];
        if ($sort == 'row_order') {
            $sort = '`row_order` + 0 ';
        }
    }
    if (isset($_GET['offset'])) {
        $offset = $_GET['offset'];
    }
    if (isset($_GET['limit'])) {
        $limit = $_GET['limit'];
    }
    if (isset($_GET['order'])) {
        $order = $_GET['order'];
    }
    if (isset($_GET['language']) && $_GET['language'] !== '') {
        $where .= ' AND c.`language_id` = ' . (int) $_GET['language'];
    }
    if (isset($_GET['search']) && $_GET['search'] !== '') {
        $search = $_GET['search'];
        $where .= " AND ( c.`id` like '%" . $search . "%' OR c.`title` like '%" . $search . "%' OR l.`language` like '%" . $search . "%' )";
    }

    $left_join = " LEFT JOIN languages l on l.id = c.language_id ";

    $sql = "SELECT COUNT(c.id) as total FROM `word_search_category` c " . $left_join . $where;
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row) {
        $total = $row['total'];
    }

    $sql = "SELECT c.*, l.language as language,
            (SELECT COUNT(id) FROM word_search_level WHERE category_id = c.id) as no_of_levels
            FROM `word_search_category` c " . $left_join . $where . " ORDER BY " . $sort . " " . $order . " LIMIT " . $offset . ", " . $limit;
    $db->sql($sql);
    $res = $db->getResult();

    $bulkData = array();
    $bulkData['total'] = $total;
    $rows = array();
    $tempRow = array();

    foreach ($res as $row) {
        $image = (!empty($row['image'])) ? 'images/category/' . $row['image'] : '';
        $operate = "<a class='btn btn-xs btn-primary edit-word-search-category' data-id='" . $row['id'] . "' data-toggle='modal' data-target='#editWordSearchCategoryModal' title='Edit'><i class='fas fa-edit'></i></a>";
        $operate .= "<a class='btn btn-xs btn-danger delete-word-search-category' data-id='" . $row['id'] . "' data-image='" . $image . "' title='Delete'><i class='fas fa-trash'></i></a>";
        $operate .= "<a class='btn btn-xs btn-success edit-word-search-status' data-id='" . $row['id'] . "' data-toggle='modal' data-target='#editWordSearchStatusModal' title='Edit Status'><i class='fas fa-edit'></i></a>";

        $tempRow['id'] = $row['id'];
        $tempRow['language'] = $row['language'];
        $tempRow['language_id'] = $row['language_id'];
        $tempRow['row_order'] = $row['row_order'];
        $tempRow['title'] = $row['title'];
        $tempRow['plan'] = $row['plan'];
        $tempRow['amount'] = $row['amount'];
        $tempRow['image'] = (!empty($row['image'])) ? '<a href="' . $image . '" data-lightbox="Word Search Category Images"><img src="' . $image . '" height=30 ></a>' : '<img src="images/logo-half.png" height=30>';
        $tempRow['no_of_levels'] = $row['no_of_levels'];
        $tempRow['operate'] = $operate;
        $tempRow['status'] = ($row['status']) ? "<label class='label label-success'>Active</label>" : "<label class='label label-danger'>Deactive</label>";
        $rows[] = $tempRow;
    }

    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}

// word_search_level
if (isset($_GET['table']) && $_GET['table'] == 'word_search_level') {
    $offset = 0;
    $limit = 10;
    $sort = 'c.title';
    $order = 'ASC';
    $where = ' WHERE 1=1 ';

    if (isset($_GET['sort']) && $_GET['sort'] !== '') {
        $sort = $_GET['sort'];
    }
    if (isset($_GET['offset'])) {
        $offset = (int) $_GET['offset'];
    }
    if (isset($_GET['limit'])) {
        $limit = (int) $_GET['limit'];
    }
    if (isset($_GET['order']) && $_GET['order'] !== '') {
        $order = $_GET['order'];
    }
    if (isset($_GET['search']) && $_GET['search'] !== '') {
        $search = $_GET['search'];
        $where .= " AND (l.`id` like '%" . $search . "%' OR c.`title` like '%" . $search . "%' OR l.`level_number` like '%" . $search . "%' OR l.`time_limit` like '%" . $search . "%' OR l.`reward_coins` like '%" . $search . "%' OR lang.`language` like '%" . $search . "%')";
    }

    $left_join = " INNER JOIN word_search_category c ON c.id = l.category_id ";
    $left_join .= $fn->is_language_mode_enabled() ? " LEFT JOIN languages lang ON lang.id = l.language_id " : "";

    $sql = "SELECT COUNT(l.id) as total FROM `word_search_level` l " . $left_join . $where;
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row) {
        $total = $row['total'];
    }

    $sql = "SELECT l.*, c.title AS category_name" .
        ($fn->is_language_mode_enabled() ? ", lang.language AS language_name" : "") .
        " FROM `word_search_level` l " . $left_join . $where .
        " ORDER BY " . $sort . " " . $order . " LIMIT " . $offset . ", " . $limit;
    $db->sql($sql);
    $res = $db->getResult();

    $bulkData = array();
    $bulkData['total'] = $total;
    $rows = array();

    foreach ($res as $row) {
        $operate = "<a class='btn btn-xs btn-danger delete-word-search-level' data-id='" . $row['id'] . "' href='javascript:void(0)' title='Delete'><i class='fas fa-trash'></i></a>";
        $decodedWords = json_decode($row['words_json'], true);

        $tempRow = array();
        $tempRow['id'] = $row['id'];
        $tempRow['category_name'] = $row['category_name'];
        $tempRow['language'] = isset($row['language_name']) ? $row['language_name'] : $row['language_id'];
        $tempRow['level_number'] = $row['level_number'];
        $tempRow['board'] = $row['board_rows'] . ' x ' . $row['board_cols'];
        $tempRow['time_limit'] = $row['time_limit'] . 's';
        $tempRow['reward_coins'] = $row['reward_coins'];
        $tempRow['words_count'] = is_array($decodedWords) ? count($decodedWords) : 0;
        $tempRow['status'] = ((int) $row['status'] === 1) ? "<label class='label label-success'>Active</label>" : "<label class='label label-danger'>Disabled</label>";
        $tempRow['operate'] = $operate;
        $rows[] = $tempRow;
    }

    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}

// game_2048_challenge_level
if (isset($_GET['table']) && $_GET['table'] == 'game_2048_challenge_level') {
    $offset = 0;
    $limit = 10;
    $sort = 'level_number';
    $order = 'ASC';
    $where = ' WHERE 1=1 ';

    if (isset($_GET['sort']) && $_GET['sort'] !== '') {
        $sort = $_GET['sort'];
    }
    if (isset($_GET['offset'])) {
        $offset = (int) $_GET['offset'];
    }
    if (isset($_GET['limit'])) {
        $limit = (int) $_GET['limit'];
    }
    if (isset($_GET['order']) && $_GET['order'] !== '') {
        $order = $_GET['order'];
    }
    if (isset($_GET['search']) && $_GET['search'] !== '') {
        $search = $_GET['search'];
        $where .= " AND (`id` like '%" . $search . "%' OR `level_number` like '%" . $search . "%' OR `move_limit` like '%" . $search . "%' OR `difficulty` like '%" . $search . "%' OR `note_balance` like '%" . $search . "%')";
    }

    $sql = "SELECT COUNT(id) as total FROM `game_2048_challenge_level` " . $where;
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row) {
        $total = $row['total'];
    }

    $sql = "SELECT * FROM `game_2048_challenge_level` " . $where .
        " ORDER BY " . $sort . " " . $order . " LIMIT " . $offset . ", " . $limit;
    $db->sql($sql);
    $res = $db->getResult();

    $bulkData = array();
    $bulkData['total'] = $total;
    $rows = array();

    foreach ($res as $row) {
        $operate = "<a class='btn btn-xs btn-danger delete-game-2048-challenge-level' data-id='" . $row['id'] . "' href='javascript:void(0)' title='Delete'><i class='fas fa-trash'></i></a>";

        $tempRow = array();
        $tempRow['id'] = $row['id'];
        $tempRow['level_number'] = $row['level_number'];
        $tempRow['move_limit'] = $row['move_limit'];
        $tempRow['goal_1'] = ((int) $row['goal_1_value'] > 0 && (int) $row['goal_1_count'] > 0) ? ($row['goal_1_value'] . ' x ' . $row['goal_1_count']) : '-';
        $tempRow['goal_2'] = ((int) $row['goal_2_value'] > 0 && (int) $row['goal_2_count'] > 0) ? ($row['goal_2_value'] . ' x ' . $row['goal_2_count']) : '-';
        $tempRow['goal_3'] = ((int) $row['goal_3_value'] > 0 && (int) $row['goal_3_count'] > 0) ? ($row['goal_3_value'] . ' x ' . $row['goal_3_count']) : '-';
        $tempRow['difficulty'] = $row['difficulty'];
        $tempRow['note_balance'] = $row['note_balance'];
        $tempRow['status'] = ((int) $row['status'] === 1) ? "<label class='label label-success'>Active</label>" : "<label class='label label-danger'>Disabled</label>";
        $tempRow['operate'] = $operate;
        $rows[] = $tempRow;
    }

    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}

// 3. users
if (isset($_GET['table']) && $_GET['table'] == 'users') {
    $offset = 0;
    $limit = 10;
    $sort = 'id';
    $order = 'DESC';
    $where = '';
    $table = $_GET['table'];

    if (isset($_GET['sort']))
        $sort = $_GET['sort'];
    if (isset($_GET['offset']))
        $offset = $_GET['offset'];
    if (isset($_GET['limit']))
        $limit = $_GET['limit'];

    if (isset($_GET['sort']))
        $sort = $_GET['sort'];
    if (isset($_GET['order']))
        $order = $_GET['order'];

    if (isset($_GET['status'])) {
        $status = $_GET['status'];
        if ($_GET['status'] != '')
            $where = " WHERE (`status` = " . $status . ")";
    }

    if (isset($_GET['search'])) {
        $search = $_GET['search'];
        if (isset($_GET['status']) && $_GET['status'] != '')
            $where .= " AND (`id` like '%" . $search . "%' OR `name` like '%" . $search . "%' OR `mobile` like '%" . $search . "%' OR `email` like '%" . $search . "%' OR `date_registered` like '%" . $search . "%' )";
        else
            $where = " WHERE (`id` like '%" . $search . "%' OR `name` like '%" . $search . "%' OR `mobile` like '%" . $search . "%' OR `email` like '%" . $search . "%' OR `date_registered` like '%" . $search . "%' )";
    }

    $sql = "SELECT COUNT(*) as total FROM `users` " . $where;
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row) {
        $total = $row['total'];
    }

    $sql = "SELECT * FROM `users` " . $where . " ORDER BY " . $sort . " " . $order . " LIMIT " . $offset . ", " . $limit;
    $db->sql($sql);
    $res = $db->getResult();

    $bulkData = array();
    $bulkData['total'] = $total;
    $rows = array();
    $tempRow = array();
    $icon = array(
        'email' => 'far fa-envelope-open',
        'gmail' => 'fab fa-google-plus-square text-danger',
        'fb' => 'fab fa-facebook-square text-primary',
        'mobile' => 'fa fa-phone-square',
        'apple' => 'fab fa-apple'
    );

    foreach ($res as $row) {
        $operate = "<a class='btn btn-xs btn-primary edit-users' data-id='" . $row['id'] . "' data-toggle='modal' data-target='#editUserModal' title='Edit'><i class='far fa-edit'></i></a>";
        $operate .= "<a class='btn btn-xs btn-success' href='monthly-leaderboard-user.php?user_id=" . $row['id'] . "' target='_blank' title='Monthly Leaderboard'><i class='fas fa-th'></i></a>";

        $operate .= "<a class='btn btn-xs btn-warning' href='battle-statistics.php?user_id=" . $row['id'] . "' target='_blank' title='User Statistics'><i class='far fa-chart-bar'></i></a>";
        $operate .= "<a class='btn btn-xs btn-default add-coin' data-id='" . $row['id'] . "' data-toggle='modal' data-target='#editUserModal1' title='Coin'><i class='fas fa-coins' style='color: red'></i></a>";

        if (filter_var($row['profile'], FILTER_VALIDATE_URL) === FALSE) {
            // Not a valid URL. Its a image only or empty
            $tempRow['profile'] = (!empty($row['profile'])) ? DOMAIN_URL . 'uploads/profile/' . $row['profile'] : '';
        } else {
            /* if it is a ur than just pass url as it is */
            $tempRow['profile'] = $row['profile'];
        }

        $tempRow['id'] = $row['id'];
        $tempRow['profile'] = (!empty($tempRow['profile'])) ? "<a data-lightbox='Profile Picture' href='" . $tempRow['profile'] . "'><img src='" . $tempRow['profile'] . "' width='80'/></a>" : "No Image";
        $tempRow['name'] = $row['name'];
        $tempRow['email'] = $row['email'];
        $tempRow['mobile'] = $row['mobile'];
        $tempRow['type'] = (isset($row['type']) && $row['type'] != '') ? '<i class="' . $icon[trim($row['type'])] . ' fa-2x"></i>' : '<i class="' . $icon['email'] . ' fa-2x"></i>';
        $tempRow['fcm_id'] = $row['fcm_id'];
        $tempRow['coins'] = $row['coins'];
        $tempRow['refer_code'] = $row['refer_code'];
        $tempRow['friends_code'] = $row['friends_code'];
        $tempRow['ip_address'] = $row['ip_address'];
        $tempRow['date_registered'] = date('d-M-Y h:i A', strtotime($row['date_registered']));
        $tempRow['status'] = ($row['status']) ? "<label class='label label-success'>Active</label>" : "<label class='label label-danger'>Deactive</label>";
        $tempRow['operate'] = $operate;
        $rows[] = $tempRow;
    }

    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}

// 4. global_leaderboard
if (isset($_GET['table']) && $_GET['table'] == 'global_leaderboard') {
    $offset = 0;
    $limit = 10;
    $sort = 'r.user_rank';
    $order = 'ASC';
    $where = $where_sub = '';
    $table = $_GET['table'];

    if (isset($_POST['id']))
        $id = $_POST['id'];
    if (isset($_GET['offset']))
        $offset = $_GET['offset'];
    if (isset($_GET['limit']))
        $limit = $_GET['limit'];
    if (isset($_GET['sort']))
        $sort = $_GET['sort'];
    if (isset($_GET['order']))
        $order = $_GET['order'];

    if (isset($_GET['search'])) {
        $search = $_GET['search'];
        $where = " WHERE (r.id like '%" . $search . "%' OR u.name like '%" . $search . "%' OR u.email like '%" . $search . "%' )";
    }

    $sql1 = "SELECT count(r.user_id) as total FROM ( SELECT s.*, @user_rank := @user_rank + 1 user_rank FROM ( SELECT MIN(m.id) AS id, user_id, SUM(score) AS score FROM monthly_leaderboard m join users u on u.id = m.user_id GROUP BY user_id) s, (SELECT @user_rank := 0) init ORDER BY score DESC) r INNER join users u on u.id = r.user_id " . $where . "";
    $db->sql($sql1);
    $res = $db->getResult();
    foreach ($res as $row) {
        $total = $row['total'];
    }
    //  
    $sql = "SELECT r.*, u.email,u.name FROM ( SELECT s.*, @user_rank := @user_rank + 1 user_rank FROM ( SELECT MIN(m.id) AS id, user_id, SUM(score) AS score FROM monthly_leaderboard m join users u on u.id = m.user_id GROUP BY user_id ORDER BY score DESC ) s, (SELECT @user_rank := 0) init ORDER BY score DESC) r INNER join users u on u.id = r.user_id " . $where . " ORDER BY " . $sort . " " . $order . " LIMIT " . $offset . ", " . $limit;
    $db->sql($sql);
    $res = $db->getResult();

    $bulkData = array();
    $bulkData['total'] = $total;
    $rows = array();
    $tempRow = array();

    $count = 1;
    foreach ($res as $row) {
        $tempRow['id'] = $row['id'];
        $tempRow['name'] = $row['name'];
        $tempRow['email'] = $row['email'];
        $tempRow['user_id'] = $row['user_id'];
        $tempRow['score'] = $row['score'];
        $tempRow['user_rank'] = $row['user_rank'];
        $rows[] = $tempRow;
        $count++;
    }

    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}

// 5. monthly_leaderboard
if (isset($_GET['table']) && $_GET['table'] == 'monthly_leaderboard') {
    $offset = 0;
    $limit = 10;
    $sort = 'r.user_rank';
    $order = 'ASC';
    $where = $where1 = $where_sub = '';
    $table = $_GET['table'];

    if (isset($_POST['id']))
        $id = $_POST['id'];
    if (isset($_GET['offset']))
        $offset = $_GET['offset'];
    if (isset($_GET['limit']))
        $limit = $_GET['limit'];
    if (isset($_GET['sort']))
        $sort = $_GET['sort'];
    if (isset($_GET['order']))
        $order = $_GET['order'];
    if (isset($_GET['user_id'])) {
        $user_id = $_GET['user_id'];
        if ($_GET['user_id'] != '')
            $where1 = " WHERE user_id=" . $user_id;
        $where = " WHERE user_id=" . $user_id;
    }

    if (isset($_GET['year']) && $_GET['year'] != '') {
        $year = $_GET['year'];
        $where1 = " WHERE (YEAR(m.date_created) = '" . $year . "') ";
        $where_sub = " WHERE (YEAR(m.date_created) = '" . $year . "') ";
        if (isset($_GET['month']) && $_GET['month'] != '') {
            $month = $_GET['month'];
            $where1 .= " AND (MONTH(m.date_created) = '" . $month . "') ";
            $where_sub .= " AND (MONTH(m.date_created) = '" . $month . "') ";
        }
    } else if (isset($_GET['month']) && $_GET['month'] != '') {
        $month = $_GET['month'];
        $where1 = " WHERE ( MONTH(m.date_created) = '" . $month . "') ";
        $where_sub = " WHERE ( MONTH(m.date_created) = '" . $month . "') ";
    }

    if (isset($_GET['search'])) {
        $search = $_GET['search'];
        $where1 = " WHERE (u.name like '%" . $search . "%' OR u.email like '%" . $search . "%' )";
        $where = " WHERE (u.name like '%" . $search . "%' OR u.email like '%" . $search . "%' )";
    }

    $sql = "SELECT COUNT(*) AS total FROM monthly_leaderboard m INNER JOIN users u ON m.user_id=u.id " . $where1;

    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row) {
        $total = $row['total'];
    }

    $sql = "SELECT u.email,u.name,u.profile,r.* FROM (
        SELECT s.*, @user_rank := @user_rank + 1 user_rank FROM 
        ( SELECT MIN(m.id) AS id, user_id, SUM(score) AS score, MAX(last_updated) AS last_updated, MIN(date_created) AS date_created FROM monthly_leaderboard m join users u on u.id = m.user_id $where_sub GROUP BY user_id ORDER BY score DESC) s,
        (SELECT @user_rank := 0) init ORDER BY score DESC ) r 
    INNER join users u on u.id = r.user_id $where ORDER BY " . $sort . " " . $order . " LIMIT $offset,$limit";
    $db->sql($sql);
    $res = $db->getResult();

    $bulkData = array();
    $bulkData['total'] = $total;
    $rows = array();
    $tempRow = array();

    foreach ($res as $row) {
        //$operate = "<a class='btn btn-xs btn-primary edit-users' data-id='".$row['id']."' data-toggle='modal' data-target='#editUserModal' title='Edit'><i class='far fa-edit'></i></a>";

        $tempRow['id'] = $row['id'];
        $tempRow['name'] = $row['name'];
        $tempRow['email'] = $row['email'];
        $tempRow['user_id'] = $row['user_id'];
        $tempRow['score'] = $row['score'];
        $tempRow['user_rank'] = $row['user_rank'];
        $tempRow['last_updated'] = date("d-m-Y H:m:s", strtotime($row['last_updated']));
        $tempRow['date_created'] = date("d-m-Y H:m:s", strtotime($row['date_created']));
        $rows[] = $tempRow;
    }

    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}

// 6. daily_leaderboard
if (isset($_GET['table']) && $_GET['table'] == 'daily_leaderboard') {
    $offset = 0;
    $limit = 10;
    $sort = 'r.user_rank';
    $order = 'ASC';
    $where = $where_sub = '';
    $table = $_GET['table'];

    if (isset($_POST['id']))
        $id = $_POST['id'];
    if (isset($_GET['offset']))
        $offset = $_GET['offset'];
    if (isset($_GET['limit']))
        $limit = $_GET['limit'];
    if (isset($_GET['sort']))
        $sort = $_GET['sort'];
    if (isset($_GET['order']))
        $order = $_GET['order'];

    $date = date('Y-m-d H:i:s');

    //$where = " WHERE ( DAY(daily_leaderboard.date_created) = DAY('" . $date . "') ) ";
    $where_sub = " WHERE ( DAY(daily_leaderboard.date_created) = DAY('" . $date . "') ) ";

    if (isset($_GET['search'])) {
        $search = $_GET['search'];
        $where = " WHERE (r.id like '%" . $search . "%' OR u.name like '%" . $search . "%' OR u.email like '%" . $search . "%' )";
    }

    $sql = "SELECT COUNT(r.id) AS total FROM (SELECT s.*, @user_rank := @user_rank + 1 user_rank FROM (SELECT id,user_id, score, last_updated, date_created  FROM daily_leaderboard d WHERE ((DATE(d.date_created) BETWEEN DATE('" . date('Y-m-d') . "') and DATE('" . date('Y-m-d') . "')))) s, (SELECT @user_rank := 0) init ORDER BY score DESC) r INNER join users u on u.id = r.user_id " . $where . "";
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row) {
        $total = $row['total'];
    }

    $sql = "SELECT r.*,u.email,u.name,u.profile FROM (SELECT s.*, @user_rank := @user_rank + 1 user_rank FROM (SELECT MIN(id) AS id, user_id, SUM(score) AS score, MAX(last_updated) AS last_updated, MIN(date_created) AS date_created FROM daily_leaderboard d WHERE ((DATE(d.date_created) BETWEEN DATE('" . date('Y-m-d') . "') and DATE('" . date('Y-m-d') . "'))) GROUP BY user_id ORDER BY score DESC) s, (SELECT @user_rank := 0) init ORDER BY score DESC) r INNER join users u on u.id = r.user_id " . $where . " ORDER BY " . $sort . " " . $order . " LIMIT $offset,$limit";
    $db->sql($sql);
    $res = $db->getResult();

    $bulkData = array();
    $bulkData['total'] = $total;
    $rows = array();
    $tempRow = array();

    foreach ($res as $row) {
        $tempRow['id'] = $row['id'];
        $tempRow['name'] = $row['name'];
        $tempRow['email'] = $row['email'];
        $tempRow['user_id'] = $row['user_id'];
        $tempRow['score'] = $row['score'];
        $tempRow['user_rank'] = $row['user_rank'];
        $tempRow['last_updated'] = date("d-m-Y H:m:s", strtotime($row['last_updated']));
        $tempRow['date_created'] = date("d-m-Y H:m:s", strtotime($row['date_created']));
        $rows[] = $tempRow;
    }

    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}

// 7. admin
if (isset($_GET['table']) && $_GET['table'] == 'admin') {
    $username = $_SESSION['username'];
    $offset = 0;
    $limit = 10;
    $sort = 'auth_username';
    $order = 'DESC';
    $where = " WHERE status = '0'";
    $table = $_GET['table'];

    if (isset($_GET['offset']))
        $offset = $_GET['offset'];
    if (isset($_GET['limit']))
        $limit = $_GET['limit'];

    if (isset($_GET['sort']))
        $sort = $_GET['sort'];
    if (isset($_GET['order']))
        $order = $_GET['order'];

    if (isset($_GET['search'])) {
        $search = $_GET['search'];
        $where .= " AND (`auth_username` like '%" . $search . "%' OR `role` like '%" . $search . "%')";
    }

    $sql = "SELECT COUNT(*) as total FROM `authenticate` " . $where;
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row) {
        $total = $row['total'];
    }

    $sql = "SELECT * FROM `authenticate` " . $where . " ORDER BY " . $sort . " " . $order . " LIMIT " . $offset . ", " . $limit;
    $db->sql($sql);
    $res = $db->getResult();

    $bulkData = array();
    $bulkData['total'] = $total;
    $rows = array();
    $tempRow = array();

    foreach ($res as $row) {
        $operate = "<a class='btn btn-xs btn-primary edit-admin' data-id='" . $row['auth_username'] . "'- data-toggle='modal' data-target='#editAdminModal' title='Edit'><i class='fas fa-edit'></i></a>";
        $operate .= "<a class='btn btn-xs btn-danger delete-admin' data-id=" . $row['auth_username'] . "  title='Delete'><i class='fas fa-trash'></i></a>";

        $tempRow['auth_username'] = $row['auth_username'];
        $tempRow['role'] = $row['role'];
        $tempRow['created'] = $row['created'];
        $tempRow['operate'] = $operate;
        $rows[] = $tempRow;
    }

    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}

// 8. question
if (isset($_GET['table']) && $_GET['table'] == 'question') {
    $offset = 0;
    $limit = 10;
    $sort = 'q.id';
    $order = 'DESC';
    $where = '';
    $table = $_GET['table'];

    if (isset($_POST['id']))
        $id = $_POST['id'];
    if (isset($_GET['offset']))
        $offset = $_GET['offset'];
    if (isset($_GET['limit']))
        $limit = $_GET['limit'];

    if (isset($_GET['sort'])) {
        $sort = ($_GET['sort'] == 'id') ? "q." . $_GET['sort'] : $_GET['sort'];
    }

    if (isset($_GET['order']))
        $order = $_GET['order'];

    if (isset($_GET['language']) && !empty($_GET['language'])) {
        $where = 'where `language_id` = ' . $_GET['language'];
        if (isset($_GET['category']) && !empty($_GET['category'])) {
            $where .= ' and `category`=' . $_GET['category'];
            if (isset($_GET['subcategory']) && !empty($_GET['subcategory'])) {
                $where .= ' and `subcategory`=' . $_GET['subcategory'];
            }
        }
    } elseif (isset($_GET['category']) && !empty($_GET['category'])) {
        $where = 'where `category` = ' . $_GET['category'];
        if (isset($_GET['subcategory']) && !empty($_GET['subcategory'])) {
            $where .= ' and `subcategory`=' . $_GET['subcategory'];
        }
    }

    if (isset($_GET['search'])) {
        $search = $_GET['search'];
        $where = " where (q.`id` like '%" . $search . "%' OR `question` like '%" . $search . "%' OR `optiona` like '%" . $search . "%' OR `optionb` like '%" . $search . "%' OR `optionc` like '%" . $search . "%' OR `optiond` like '%" . $search . "%' OR `answer` like '%" . $search . "%' )";
        if (isset($_GET['language']) && !empty($_GET['language'])) {
            $where .= ' and `language_id` = ' . $_GET['language'];
            if (isset($_GET['category']) && !empty($_GET['category'])) {
                $where .= ' and `category`=' . $_GET['category'];
                if (isset($_GET['subcategory']) && !empty($_GET['subcategory'])) {
                    $where .= ' and `subcategory`=' . $_GET['subcategory'];
                }
            }
        } elseif (isset($_GET['category']) && !empty($_GET['category'])) {
            $where .= ' and `category` = ' . $_GET['category'];
            if (isset($_GET['subcategory']) && !empty($_GET['subcategory'])) {
                $where .= ' and `subcategory`=' . $_GET['subcategory'];
            }
        }
    }

    $left_join = " LEFT JOIN languages l on l.id = q.language_id ";

    $sql = "SELECT COUNT(q.id) as total FROM `question` q " . $left_join . " " . $where;
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row) {
        $total = $row['total'];
    }

    $sql = "SELECT q.*, l.language FROM `question` q " . $left_join . " " . $where . " ORDER BY " . $sort . " " . $order . " LIMIT " . $offset . ", " . $limit;

    $db->sql($sql);
    $res = $db->getResult();

    $bulkData = array();
    $bulkData['total'] = $total;
    $rows = array();
    $tempRow = array();

    foreach ($res as $row) {
        $image = (!empty($row['image'])) ? 'images/questions/' . $row['image'] : '';
        $operate = "<a class='btn btn-xs btn-primary edit-question' data-id='" . $row['id'] . "' data-toggle='modal' data-target='#editQuestionModal' title='Edit'><i class='fas fa-edit'></i></a>";
        $operate .= "<a class='btn btn-xs btn-danger delete-question' data-id='" . $row['id'] . "' data-image='" . $image . "' title='Delete'><i class='fas fa-trash'></i></a>";

        $tempRow['id'] = $row['id'];
        $tempRow['category'] = $row['category'];
        $tempRow['subcategory'] = $row['subcategory'];
        $tempRow['language_id'] = $row['language_id'];
        $tempRow['language'] = $row['language'];
        $tempRow['image'] = (!empty($row['image'])) ? '<a data-lightbox="Question-Image" href="' . $image . '" data-caption="' . $row['question'] . '"><img src="' . $image . '" height=30 ></a>' : 'No image';
        $tempRow['question'] = $row['question'];
        $tempRow['question_type'] = $row['question_type'];
        $tempRow['optiona'] = $row['optiona'];
        $tempRow['optionb'] = $row['optionb'];
        $tempRow['optionc'] = $row['optionc'];
        $tempRow['optiond'] = $row['optiond'];
        $tempRow['optione'] = $row['optione'];
        $tempRow['answer'] = $row['answer'];
        $tempRow['level'] = $row['level'];
        $tempRow['note'] = $row['note'];
        $tempRow['operate'] = $operate;
        $rows[] = $tempRow;
    }

    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}

// 9. question_reports
if (isset($_GET['table']) && $_GET['table'] == 'question_reports') {
    $offset = 0;
    $limit = 10;
    $sort = 'id';
    $order = 'DESC';
    $where = '';
    $table = $_GET['table'];

    if (isset($_GET['offset']))
        $offset = $_GET['offset'];
    if (isset($_GET['limit']))
        $limit = $_GET['limit'];

    if (isset($_GET['sort']))
        $sort = $_GET['sort'];
    if (isset($_GET['order']))
        $order = $_GET['order'];

    if (isset($_GET['search'])) {
        $search = $_GET['search'];
        $where = " where (id like '%" . $search . "%' OR message like '%" . $search . "%' OR u.name like '%" . $search . "%')";
    }

    $join = " JOIN users u ON u.id = qr.user_id";
    $join .= " JOIN question q ON q.id = qr.question_id";

    $sql = "SELECT COUNT(*) as total FROM question_reports qr " . $join . " " . $where;
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row) {
        $total = $row['total'];
    }

    $sql = "SELECT qr.*, u.name, q.category, q.subcategory, q.language_id, q.image, q.question, q.question_type, q.optiona, q.optionb, q.optionc, q.optiond, q.optione, q.answer, q.level, q.note FROM question_reports qr " . $join . " " . $where . " ORDER BY " . $sort . " " . $order . " LIMIT " . $offset . ", " . $limit;
    $db->sql($sql);
    $res = $db->getResult();

    $bulkData = array();
    $bulkData['total'] = $total;
    $rows = array();
    $tempRow = array();

    foreach ($res as $row) {
        $image = (!empty($row['image'])) ? 'images/questions/' . $row['image'] : '';
        $operate = "<a class='btn btn-xs btn-primary' href='question.php?id=" . $row['question_id'] . "' title='Edit'><i class='far fa-edit'></i></a>";
        $operate .= "<a class='btn btn-xs btn-danger delete-report' data-id='" . $row['id'] . "' title='Delete'><i class='fas fa-trash'></i></a>";

        $tempRow['id'] = $row['id'];
        $tempRow['question_id'] = $row['question_id'];
        $tempRow['question'] = $row['question'];
        $tempRow['user_id'] = $row['user_id'];
        $tempRow['name'] = $row['name'];
        $tempRow['message'] = $row['message'];
        $tempRow['date'] = $row['date'];

        $tempRow['image'] = (!empty($row['image'])) ? '<a data-lightbox="Question-Image" href="' . $image . '" data-caption="' . $row['question'] . '"><img src="' . $image . '" height=30 ></a>' : 'No image';
        $tempRow['category'] = $row['category'];
        $tempRow['subcategory'] = $row['subcategory'];
        $tempRow['language_id'] = $row['language_id'];
        $tempRow['question_type'] = $row['question_type'];
        $tempRow['optiona'] = $row['optiona'];
        $tempRow['optionb'] = $row['optionb'];
        $tempRow['optionc'] = $row['optionc'];
        $tempRow['optiond'] = $row['optiond'];
        $tempRow['optione'] = $row['optione'];
        $tempRow['answer'] = $row['answer'];
        $tempRow['level'] = $row['level'];
        $tempRow['note'] = $row['note'];

        $tempRow['operate'] = $operate;
        $rows[] = $tempRow;
    }

    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}

// 10. notifications
if (isset($_GET['table']) && $_GET['table'] == 'notifications') {
    $offset = 0;
    $limit = 10;
    $sort = 'id';
    $order = 'DESC';
    $where = '';
    $table = $_GET['table'];

    if (isset($_POST['id']))
        $id = $_POST['id'];
    if (isset($_GET['offset']))
        $offset = $_GET['offset'];
    if (isset($_GET['limit']))
        $limit = $_GET['limit'];

    if (isset($_GET['sort']))
        $sort = $_GET['sort'];
    if (isset($_GET['order']))
        $order = $_GET['order'];

    if (isset($_GET['search'])) {
        $search = $_GET['search'];
        $where = " WHERE (`id` like '%" . $search . "%' OR `type` like '%" . $search . "%' OR `title` like '%" . $search . "%' OR `message` like '%" . $search . "%' OR `users` like '%" . $search . "%')";
    }

    $sql = "SELECT COUNT(`id`) as total FROM `notifications` " . $where;
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row) {
        $total = $row['total'];
    }

    $sql = "SELECT * FROM `notifications` " . $where . " ORDER BY " . $sort . " " . $order . " LIMIT " . $offset . ", " . $limit;
    $db->sql($sql);
    $res = $db->getResult();

    $bulkData = array();
    $bulkData['total'] = $total;
    $rows = array();
    $tempRow = array();

    foreach ($res as $row) {
        $operate = "<a class='btn btn-xs btn-danger delete-notification' data-id='" . $row['id'] . "' data-image='" . $row['image'] . "' title='Delete Notification'><i class='fas fa-trash'></i></a>";

        $tempRow['id'] = $row['id'];
        $tempRow['title'] = $row['title'];
        $tempRow['message'] = $row['message'];
        $tempRow['image'] = ($row['image'] != '') ? "<a data-lightbox='notification' href='images/notifications/" . $row['image'] . "' data-caption='" . $row['title'] . "'><img src='images/notifications/" . $row['image'] . "' title='" . $row['title'] . "' width='80'/></a>" : 'no image';
        $tempRow['users'] = ucwords($row['users']);
        $tempRow['type'] = ucwords($row['type']);
        $tempRow['type_id'] = ucwords($row['type_id']);
        $tempRow['date_sent'] = $row['date_sent'];
        $tempRow['operate'] = $operate;
        $rows[] = $tempRow;
    }

    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}

// 11. languages
if (isset($_GET['table']) && $_GET['table'] == 'languages') {
    $offset = 0;
    $limit = 10;
    $sort = 'id';
    $order = 'ASC';
    $where = '';
    $table = $_GET['table'];

    if (isset($_POST['id']))
        $id = $_POST['id'];
    if (isset($_GET['offset']))
        $offset = $_GET['offset'];
    if (isset($_GET['limit']))
        $limit = $_GET['limit'];
    if (isset($_GET['order']))
        $order = $_GET['order'];
    if (isset($_GET['search'])) {
        $search = $_GET['search'];
        $where = " where (`id` like '%" . $search . "%' OR `language` like '%" . $search . "%' )";
    }

    $sql = "SELECT COUNT(*) as total FROM `languages` " . $where;
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row) {
        $total = $row['total'];
    }

    $sql = "SELECT * FROM `languages` " . $where . " ORDER BY " . $sort . " " . $order . " LIMIT " . $offset . ", " . $limit;
    $db->sql($sql);
    $res = $db->getResult();

    $bulkData = array();
    $bulkData['total'] = $total;
    $rows = array();
    $tempRow = array();

    foreach ($res as $row) {
        $operate = "<a class='btn btn-xs btn-primary edit-language' data-id='" . $row['id'] . "' data-toggle='modal' data-target='#editlanguageModal' title='Edit'><i class='fas fa-edit'></i></a>";
        $operate .= "<a class='btn btn-xs btn-danger delete-language' data-id='" . $row['id'] . "' title='Delete'><i class='fas fa-trash'></i></a>";

        $tempRow['id'] = $row['id'];
        $tempRow['language'] = $row['language'];
        $tempRow['status'] = ($row['status'] == 1) ? "<label class='label label-success'>Enabled</label>" : "<label class='label label-warning'>Disabled</label>";
        $tempRow['operate'] = $operate;
        $rows[] = $tempRow;
    }

    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}

// 12. battle_statistics
if (isset($_GET['table']) && $_GET['table'] == 'battle_statistics') {
    $offset = 0;
    $limit = 10;
    $sort = 'id';
    $order = 'DESC';
    $where = '';
    $table = $_GET['table'];

    if (isset($_GET['offset']))
        $offset = $_GET['offset'];
    if (isset($_GET['limit']))
        $limit = $_GET['limit'];

    if (isset($_GET['sort']))
        $sort = $_GET['sort'];
    if (isset($_GET['order']))
        $order = $_GET['order'];

    if (isset($_GET['user_id'])) {
        $user_id = $db->escapeString($_GET['user_id']);
        $where = " WHERE user_id1 = $user_id or user_id2 = $user_id";
    }

    if (isset($_GET['search'])) {
        $search = $_GET['search'];
        $where .= " AND (`id` like '%" . $search . "%' OR `user_1` like '%" . $search . "%' OR `user_2` like '%" . $search . "%' OR `name` like '%" . $search . "%')";
    }

    $sql = "SELECT COUNT(`id`) as total FROM `battle_statistics` " . $where;
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row) {
        $total = $row['total'];
    }

    $bulkData = array();
    $bulkData['total'] = $total;
    $rows = array();
    $tempRow = array();

    $sql = "SELECT *,(select `name` from users u WHERE u.id = m.user_id1 ) as user_1,(select `name` from users u WHERE u.id = m.user_id2 ) as user_2 FROM `battle_statistics` m " . $where . " ORDER BY " . $sort . " " . $order . " LIMIT " . $offset . ", " . $limit;

    $db->sql($sql);
    $result = $db->getResult();

    if (!empty($result)) {
        foreach ($result as $row) {
            $tempRow['id'] = $row['id'];
            $tempRow['opponent_id'] = ($row['user_id1'] == $user_id) ? $row['user_id2'] : $row['user_id1'];
            $tempRow['opponent_name'] = ($row['user_id1'] == $user_id) ? $row['user_2'] : $row['user_1'];

            if ($row['is_drawn'] == 1) {
                $tempRow['mystatus'] = "Draw";
            } else {
                $tempRow['mystatus'] = ($row['winner_id'] == $user_id) ? "Won" : "Lost";
            }
            $rows[] = $tempRow;
        }
    }
    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}

// 13. contest
if (isset($_GET['table']) && $_GET['table'] == 'contest') {
    $offset = 0;
    $limit = 10;
    $sort = 'id';
    $order = 'DESC';
    $where = '';
    $table = $_GET['table'];

    if (isset($_GET['offset']))
        $offset = $_GET['offset'];
    if (isset($_GET['limit']))
        $limit = $_GET['limit'];

    if (isset($_GET['sort']))
        $sort = $_GET['sort'];
    if (isset($_GET['order']))
        $order = $_GET['order'];

    if (isset($_GET['search'])) {
        $search = $_GET['search'];
        $where = " where (`id` like '%" . $search . "%' OR `name` like '%" . $search . "%' OR `description` like '%" . $search . "%' )";
    }

    $sql = "SELECT COUNT(*) as total FROM `contest` " . $where;
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row) {
        $total = $row['total'];
    }

    $sql = "SELECT *, (select count(contest_id) FROM contest_prize WHERE contest_prize.contest_id=contest.id) as top_users,(SELECT COUNT('id') from contest_leaderboard where contest_leaderboard.contest_id = contest.id ) as `participants`,(SELECT COUNT('id') from contest_questions where contest.id=contest_questions.contest_id) as `total_question` FROM `contest` " . $where . " ORDER BY " . $sort . " " . $order . " LIMIT " . $offset . ", " . $limit;
    $db->sql($sql);
    $res = $db->getResult();

    $bulkData = array();
    $bulkData['total'] = $total;
    $rows = array();
    $tempRow = array();

    foreach ($res as $row) {
        $image = (!empty($row['image'])) ? 'images/contest/' . $row['image'] : '';
        $operate = "<a class='btn btn-xs btn-primary edit-quiz' data-id='" . $row['id'] . "' data-image='" . $image . "' data-toggle='modal' data-target='#editCategoryModal' title='Edit'><i class='fas fa-edit'></i></a>";
        $operate .= "<a class='btn btn-xs btn-success edit-data' data-id='" . $row['id'] . "' data-toggle='modal' data-target='#editStatusModal' title='Edit Status'><i class='fas fa-edit'></i></a>";

        $operate .= "<a class='btn btn-xs btn-danger delete-quiz' data-id='" . $row['id'] . "' data-image='" . $image . "' title='Delete'><i class='fas fa-trash'></i></a>";
        $operate .= "<a class='btn btn-xs btn-warning' href='contest-leaderboard.php?contest_id=" . $row['id'] . "' target='_blank' title='View Top Users'><i class='fas fa-list'></i></a>";
        $prev_date = date('Y-m-d', strtotime(date('Y-m-d') . ' -1 day'));
        if ($row['prize_status'] == 0 && $row['end_date'] <= $prev_date) {
            $operate .= "<a class='btn btn-xs btn-success' href='contest-job.php?selected_contest=" . $row['id'] . "' target='_blank' title='Ready to Distribute Prize'><i class='fas fa-bullhorn'></i></a>";
        }
        $tempRow['id'] = $row['id'];
        $tempRow['name'] = $row['name'];
        $tempRow['start_date'] = $row['start_date'];
        $tempRow['end_date'] = $row['end_date'];
        $tempRow['image'] = ($row['image'] != '') ? "<a data-fancybox='Contest Gallery' href='" . $image . "' data-lightbox='" . $row['name'] . "'><img src='" . $image . "' title='" . $row['name'] . "' width='80'/></a>" : 'no image';
        $tempRow['description'] = $row['description'];
        $tempRow['entry'] = $row['entry'];
        $tempRow['top_users'] = '<a class="btn btn-xs btn-warning" href="contest-prize.php?contest_id=' . $row['id'] . '" target="_blank" title="View Prize">' . $row['top_users'] . '</a>';
        $tempRow['participants'] = $row['participants'];
        $tempRow['total_question'] = $row['total_question'];
        $tempRow['status'] = ($row['status']) ? "<label class='label label-success'>Active</label>" : "<label class='label label-danger'>Deactive</label>";
        $tempRow['prize_status'] = ($row['prize_status'] == 0) ? '<label class="label label-warning">Not Distributed</label>' : '<label class="label label-success">Distributed</label>';
        $tempRow['operate'] = $operate;
        $rows[] = $tempRow;
    }

    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}

// 14. contest_prize
if (isset($_GET['table']) && $_GET['table'] == 'contest_prize') {
    $offset = 0;
    $limit = 10;
    $sort = 'top_winner';
    $order = 'ASC';
    $where = '';
    $table = $_GET['table'];

    if (isset($_GET['offset']))
        $offset = $_GET['offset'];
    if (isset($_GET['limit']))
        $limit = $_GET['limit'];

    if (isset($_GET['sort']))
        $sort = $_GET['sort'];
    if (isset($_GET['order']))
        $order = $_GET['order'];

    if (isset($_GET['contest_id'])) {
        $contest_id = $_GET['contest_id'];
        $where = " WHERE p.contest_id=" . $contest_id;
    }

    if (isset($_GET['search'])) {
        $search = $_GET['search'];
        $where .= " AND (`id` like '%" . $search . "%' OR `points` like '%" . $search . "%')";
    }

    $join = " JOIN contest c ON c.id = p.contest_id";

    $sql = "SELECT COUNT(*) as total FROM contest_prize p " . $join . " " . $where;
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row) {
        $total = $row['total'];
    }

    $sql = "SELECT p.*, c.name FROM contest_prize p " . $join . " " . $where . " ORDER BY " . $sort . " " . $order . " LIMIT " . $offset . ", " . $limit;
    $db->sql($sql);
    $res = $db->getResult();

    $bulkData = array();
    $bulkData['total'] = $total;
    $rows = array();
    $tempRow = array();

    foreach ($res as $row) {
        $operate = "<a class='btn btn-xs btn-primary edit-data' data-id='" . $row['id'] . "' data-toggle='modal' data-target='#editDataModel' title='Edit'><i class='fas fa-edit'></i></a>";
        $operate .= "<a class='btn btn-xs btn-danger delete-data' data-id='" . $row['id'] . "' title='Delete'><i class='fas fa-trash'></i></a>";

        $tempRow['id'] = $row['id'];
        $tempRow['name'] = $row['name'];
        $tempRow['top_winner'] = $row['top_winner'];
        $tempRow['points'] = $row['points'];
        $tempRow['operate'] = $operate;
        $rows[] = $tempRow;
    }

    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}

// 15. contest_questions
if (isset($_GET['table']) && $_GET['table'] == 'contest_questions') {
    $offset = 0;
    $limit = 10;
    $sort = 'id';
    $order = 'DESC';
    $where = '';
    $table = $_GET['table'];

    if (isset($_GET['offset']))
        $offset = $_GET['offset'];
    if (isset($_GET['limit']))
        $limit = $_GET['limit'];

    if (isset($_GET['sort']))
        $sort = $_GET['sort'];
    if (isset($_GET['order']))
        $order = $_GET['order'];

    if (isset($_GET['contest_filter'])) {
        if (!empty($_GET['contest_filter'])) {
            $contest_filter = $_GET['contest_filter'];
            $where = " WHERE q.contest_id=" . $contest_filter;
        }
    }

    if (isset($_GET['search'])) {
        $search = $_GET['search'];
        if (!empty($_GET['contest_filter'])) {
            $contest_filter = $_GET['contest_filter'];
            $where .= " AND (`id` like '%" . $search . "%' OR q.question like '%" . $search . "%' OR q.optiona like '%" . $search . "%' OR `b` like '%" . $search . "%' OR `c` like '%" . $search . "%' OR `d` like '%" . $search . "%' OR `answer` like '%" . $search . "%' ) AND `contest_id`=" . $contest_filter;
        } else {
            $where = " WHERE (`id` like '%" . $search . "%' OR q.question like '%" . $search . "%' OR q.optiona like '%" . $search . "%' OR `b` like '%" . $search . "%' OR `c` like '%" . $search . "%' OR `d` like '%" . $search . "%' OR `answer` like '%" . $search . "%' ) ";
        }
    }

    $join = " JOIN contest c ON c.id = q.contest_id";

    $sql = "SELECT COUNT(*) as total FROM contest_questions q " . $join . " " . $where . " ";
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row) {
        $total = $row['total'];
    }

    $sql = "SELECT q.*, c.name FROM contest_questions q " . $join . " " . $where . " ORDER BY " . $sort . " " . $order . " LIMIT " . $offset . ", " . $limit;
    $db->sql($sql);
    $res = $db->getResult();

    $bulkData = array();
    $bulkData['total'] = $total;
    $rows = array();
    $tempRow = array();

    foreach ($res as $row) {
        $image = (!empty($row['image'])) ? 'images/contest-question/' . $row['image'] : '';
        $operate = "<a class='btn btn-xs btn-primary edit-question' data-id='" . $row['id'] . "' data-image='" . $image . "' data-toggle='modal' data-target='#editQuestionModal' title='Edit'><i class='fas fa-edit'></i></a>";
        $operate .= "<a class='btn btn-xs btn-danger delete-question' data-id='" . $row['id'] . "' data-image='" . $image . "' title='Delete'><i class='fas fa-trash'></i></a>";

        $tempRow['id'] = $row['id'];
        $tempRow['contest_id'] = $row['contest_id'];
        $tempRow['contest_name'] = $row['name'];
        $tempRow['image'] = ($row['image'] != '') ? "<a data-fancybox='Contest Gallery' href='" . $image . "' data-lightbox='image'><img src='" . $image . "' title='image' width='80'/></a>" : 'no image';
        $tempRow['question'] = $row['question'];
        $tempRow['question_type'] = $row['question_type'];
        $tempRow['optiona'] = $row['optiona'];
        $tempRow['optionb'] = $row['optionb'];
        $tempRow['optionc'] = $row['optionc'];
        $tempRow['optiond'] = $row['optiond'];
        $tempRow['optione'] = $row['optione'];
        $tempRow['answer'] = $row['answer'];
        $tempRow['note'] = $row['note'];
        $tempRow['operate'] = $operate;
        $rows[] = $tempRow;
    }

    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}

// 16. contest_leaderboard
if (isset($_GET['table']) && $_GET['table'] == 'contest_leaderboard') {
    $offset = 0;
    $limit = 10;
    $sort = 'user_rank';
    $order = 'ASC';
    $where = $where_sub = '';
    $table = $_GET['table'];

    if (isset($_GET['offset']))
        $offset = $_GET['offset'];
    if (isset($_GET['limit']))
        $limit = $_GET['limit'];

    if (isset($_GET['sort']))
        $sort = $_GET['sort'];
    if (isset($_GET['order']))
        $order = $_GET['order'];

    if (isset($_GET['contest_id'])) {
        $contest_id = $_GET['contest_id'];
        if ($_GET['contest_id'] != '')
            $where_sub = " WHERE contest_id = '" . $contest_id . "'";
    }

    if (isset($_GET['search'])) {
        $search = $_GET['search'];
        if ($_GET['contest_id'] != '')
            $where .= " AND (`id` like '%" . $search . "%' OR `user_id` like '%" . $search . "%' OR `score` like '%" . $search . "%' OR `last_modified` like '%" . $search . "%' )";
        else
            $where = " WHERE (`id` like '%" . $search . "%' OR `user_id` like '%" . $search . "%' OR `score` like '%" . $search . "%' OR `last_modified` like '%" . $search . "%' )";
    }

    $sql = "SELECT COUNT(`id`) as total FROM `contest_leaderboard` $where_sub " . $where;
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row) {
        $total = $row['total'];
    }

    $sql = "SELECT r.*,u.name,u.profile FROM (SELECT s.*, @user_rank := @user_rank + 1 user_rank FROM ( SELECT c.* FROM contest_leaderboard c join users u on u.id = c.user_id  $where_sub ) s, (SELECT @user_rank := 0) init ORDER BY score DESC ) r INNER join users u on u.id = r.user_id " . $where . " ORDER BY " . $sort . " " . $order . " LIMIT " . $offset . ", " . $limit;
    $db->sql($sql);
    $res = $db->getResult();

    $bulkData = array();
    $bulkData['total'] = $total;
    $rows = array();
    $tempRow = array();

    foreach ($res as $row) {
        $tempRow['id'] = $row['id'];
        $tempRow['name'] = $row['name'];
        $tempRow['user_id'] = $row['user_id'];
        $tempRow['contest_id'] = $row['contest_id'];
        $tempRow['questions_attended'] = $row['questions_attended'];
        $tempRow['correct_answers'] = $row['correct_answers'];
        $tempRow['score'] = $row['score'];
        $tempRow['user_rank'] = $row['user_rank'];
        $tempRow['last_modified'] = $row['last_modified'];
        $tempRow['date_created'] = $row['date_created'];
        $rows[] = $tempRow;
    }

    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}

// 17. learning_zone
if (isset($_GET['table']) && $_GET['table'] == 'learning_zone') {
    $offset = 0;
    $limit = 10;
    $sort = 'q.id';
    $order = 'DESC';
    $where = '';
    $table = $_GET['table'];

    if (isset($_POST['id']))
        $id = $_POST['id'];
    if (isset($_GET['offset']))
        $offset = $_GET['offset'];
    if (isset($_GET['limit']))
        $limit = $_GET['limit'];

    if (isset($_GET['sort'])) {
        $sort = ($_GET['sort'] == 'id') ? "q." . $_GET['sort'] : $_GET['sort'];
    }

    if (isset($_GET['order']))
        $order = $_GET['order'];

    if (isset($_GET['language']) && !empty($_GET['language'])) {
        $where = 'where `language_id` = ' . $_GET['language'];
        if (isset($_GET['category']) && !empty($_GET['category'])) {
            $where .= ' and `category`=' . $_GET['category'];
        }
    } elseif (isset($_GET['category']) && !empty($_GET['category'])) {
        $where = 'where `category` = ' . $_GET['category'];
    }

    if (isset($_GET['search'])) {
        $search = $_GET['search'];
        $where = " WHERE (q.id like '%" . $search . "%' OR q.title like '%" . $search . "%' OR l.language like '%" . $search . "%' )";
        if (isset($_GET['language']) && !empty($_GET['language'])) {
            $where .= ' and `language_id` = ' . $_GET['language'];
            if (isset($_GET['category']) && !empty($_GET['category'])) {
                $where .= ' and `category`=' . $_GET['category'];
            }
        } elseif (isset($_GET['category']) && !empty($_GET['category'])) {
            $where .= ' and `category` = ' . $_GET['category'];
        }
    }

    $left_join = " LEFT JOIN languages l on l.id = q.language_id ";

    $sql = "SELECT COUNT(q.id) as total FROM `tbl_learning` q " . $left_join . " " . $where;
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row) {
        $total = $row['total'];
    }

    $sql = "SELECT q.*, l.language FROM `tbl_learning` q " . $left_join . " " . $where . " ORDER BY " . $sort . " " . $order . " LIMIT " . $offset . ", " . $limit;

    $db->sql($sql);
    $res = $db->getResult();

    $bulkData = array();
    $bulkData['total'] = $total;
    $rows = array();
    $tempRow = array();

    foreach ($res as $row) {
        $pdf_file = (!empty($row['pdf_file'])) ? 'pdf_files/' . $row['pdf_file'] : '';

        $operate = "<a class='btn btn-xs btn-warning' href='learning-questions.php?id=" . $row['id'] . "' title='Add question'><i class='fas fa-plus'></i></a>";
        $operate .= "<a class='btn btn-xs btn-primary edit-data' data-id='" . $row['id'] . "' data-toggle='modal' data-target='#editDataModal' title='Edit'><i class='fas fa-edit'></i></a>";
        $operate .= "<a class='btn btn-xs btn-success edit-status' data-id='" . $row['id'] . "' data-toggle='modal' data-target='#editStatusModal' title='Edit Status'><i class='fas fa-edit'></i></a>";
        $operate .= "<a class='btn btn-xs btn-danger delete-data' data-id='" . $row['id'] . "'   data-pdf='" . $pdf_file . "' title='Delete'><i class='fas fa-trash'></i></a>";

        $tempRow['id'] = $row['id'];
        $tempRow['category'] = $row['category'];
        $tempRow['language_id'] = $row['language_id'];
        $tempRow['language'] = $row['language'];
        $tempRow['title'] = $row['title'];
        $tempRow['video_id'] = $row['video_id'];
        $tempRow['detail'] = $row['detail'];
        $tempRow['pdf_file'] = (!empty($row['pdf_file'])) ? '<a href="' . $pdf_file . '" data-name="' . $pdf_file . '" id="get_pdf">View File</a>' : '';
        $tempRow['pdf_name'] = $pdf_file;
        $tempRow['status'] = ($row['status']) ? "<label class='label label-success'>Active</label>" : "<label class='label label-danger'>Deactive</label>";
        $tempRow['operate'] = $operate;
        $rows[] = $tempRow;
    }

    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}

// 18. learnning_question
if (isset($_GET['table']) && $_GET['table'] == 'learnning_question') {
    $offset = 0;
    $limit = 10;
    $sort = 'q.id';
    $order = 'DESC';
    $where = '';
    $table = $_GET['table'];

    if (isset($_POST['id']))
        $id = $_POST['id'];
    if (isset($_GET['offset']))
        $offset = $_GET['offset'];
    if (isset($_GET['limit']))
        $limit = $_GET['limit'];

    if (isset($_GET['sort'])) {
        $sort = ($_GET['sort'] == 'id') ? "q." . $_GET['sort'] : $_GET['sort'];
    }

    if (isset($_GET['order']))
        $order = $_GET['order'];


    if (isset($_GET['learning_id'])) {
        $learning_id = $_GET['learning_id'];
        $where = " WHERE learning_id=" . $learning_id;
    }

    if (isset($_GET['search'])) {
        $search = $_GET['search'];
        $where .= " AND (q.`id` like '%" . $search . "%' OR `question` like '%" . $search . "%' OR `optiona` like '%" . $search . "%' OR `optionb` like '%" . $search . "%' OR `optionc` like '%" . $search . "%' OR `optiond` like '%" . $search . "%' OR `answer` like '%" . $search . "%' )";
    }

    $sql = "SELECT COUNT(q.id) as total FROM `tbl_learning_question` q " . $where;
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row) {
        $total = $row['total'];
    }

    $sql = "SELECT q.* FROM `tbl_learning_question` q " . $where . " ORDER BY " . $sort . " " . $order . " LIMIT " . $offset . ", " . $limit;

    $db->sql($sql);
    $res = $db->getResult();

    $bulkData = array();
    $bulkData['total'] = $total;
    $rows = array();
    $tempRow = array();

    foreach ($res as $row) {
        $operate = "<a class='btn btn-xs btn-primary edit-question' data-id='" . $row['id'] . "' data-toggle='modal' data-target='#editQuestionModal' title='Edit'><i class='fas fa-edit'></i></a>";
        $operate .= "<a class='btn btn-xs btn-danger delete-question' data-id='" . $row['id'] . "' title='Delete'><i class='fas fa-trash'></i></a>";

        $tempRow['id'] = $row['id'];
        $tempRow['question'] = $row['question'];
        $tempRow['question_type'] = $row['question_type'];
        $tempRow['optiona'] = $row['optiona'];
        $tempRow['optionb'] = $row['optionb'];
        $tempRow['optionc'] = $row['optionc'];
        $tempRow['optiond'] = $row['optiond'];
        $tempRow['optione'] = $row['optione'];
        $tempRow['answer'] = $row['answer'];
        $tempRow['operate'] = $operate;
        $rows[] = $tempRow;
    }

    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}

// 19. maths_question
if (isset($_GET['table']) && $_GET['table'] == 'maths_question') {
    $offset = 0;
    $limit = 10;
    $sort = 'q.id';
    $order = 'DESC';
    $where = '';
    $table = $_GET['table'];

    if (isset($_POST['id']))
        $id = $_POST['id'];
    if (isset($_GET['offset']))
        $offset = $_GET['offset'];
    if (isset($_GET['limit']))
        $limit = $_GET['limit'];

    if (isset($_GET['sort'])) {
        $sort = ($_GET['sort'] == 'id') ? "q." . $_GET['sort'] : $_GET['sort'];
    }

    if (isset($_GET['order']))
        $order = $_GET['order'];

    if (isset($_GET['language']) && !empty($_GET['language'])) {
        $where = 'where `language_id` = ' . $_GET['language'];
        if (isset($_GET['category']) && !empty($_GET['category'])) {
            $where .= ' and `category`=' . $_GET['category'];
            if (isset($_GET['subcategory']) && !empty($_GET['subcategory'])) {
                $where .= ' and `subcategory`=' . $_GET['subcategory'];
            }
        }
    } elseif (isset($_GET['category']) && !empty($_GET['category'])) {
        $where = 'where `category` = ' . $_GET['category'];
        if (isset($_GET['subcategory']) && !empty($_GET['subcategory'])) {
            $where .= ' and `subcategory`=' . $_GET['subcategory'];
        }
    }

    if (isset($_GET['search'])) {
        $search = $_GET['search'];
        $where = " where (q.`id` like '%" . $search . "%' OR `question` like '%" . $search . "%' OR `optiona` like '%" . $search . "%' OR `optionb` like '%" . $search . "%' OR `optionc` like '%" . $search . "%' OR `optiond` like '%" . $search . "%' OR `answer` like '%" . $search . "%' )";
        if (isset($_GET['language']) && !empty($_GET['language'])) {
            $where .= ' and `language_id` = ' . $_GET['language'];
            if (isset($_GET['category']) && !empty($_GET['category'])) {
                $where .= ' and `category`=' . $_GET['category'];
                if (isset($_GET['subcategory']) && !empty($_GET['subcategory'])) {
                    $where .= ' and `subcategory`=' . $_GET['subcategory'];
                }
            }
        } elseif (isset($_GET['category']) && !empty($_GET['category'])) {
            $where .= ' and `category` = ' . $_GET['category'];
            if (isset($_GET['subcategory']) && !empty($_GET['subcategory'])) {
                $where .= ' and `subcategory`=' . $_GET['subcategory'];
            }
        }
    }

    $left_join = " LEFT JOIN languages l on l.id = q.language_id ";

    $sql = "SELECT COUNT(q.id) as total FROM `tbl_maths_question` q " . $left_join . " " . $where;
    $db->sql($sql);
    $res = $db->getResult();
    foreach ($res as $row) {
        $total = $row['total'];
    }

    $sql = "SELECT q.*, l.language FROM `tbl_maths_question` q " . $left_join . " " . $where . " ORDER BY " . $sort . " " . $order . " LIMIT " . $offset . ", " . $limit;

    $db->sql($sql);
    $res = $db->getResult();

    $bulkData = array();
    $bulkData['total'] = $total;
    $rows = array();
    $tempRow = array();

    foreach ($res as $row) {
        $image = (!empty($row['image'])) ? 'images/maths-question/' . $row['image'] : '';
        $operate = "<a class='btn btn-xs btn-primary edit-question' href='maths-questions.php?id=" . $row['id'] . "' data-id='" . $row['id'] . "' title='Edit'><i class='fas fa-edit'></i></a>";
        $operate .= "<a class='btn btn-xs btn-danger delete-question' data-id='" . $row['id'] . "' data-image='" . $image . "' title='Delete'><i class='fas fa-trash'></i></a>";

        $tempRow['id'] = $row['id'];
        $tempRow['category'] = $row['category'];
        $tempRow['subcategory'] = $row['subcategory'];
        $tempRow['language_id'] = $row['language_id'];
        $tempRow['language'] = $row['language'];
        $tempRow['image'] = (!empty($row['image'])) ? '<a data-lightbox="Question-Image" href="' . $image . '" data-caption="maths-image"><img src="' . $image . '" height=30 ></a>' : 'No image';
        $tempRow['answer'] = $row['answer'];

        $tempRow['question'] = "<textarea id='q" . $row['id'] . "' class='form-control'>" . $row['question'] . "</textarea> 
        <script type='text/javascript'>CKEDITOR.replace('q" . $row['id'] . "', { extraPlugins: 'mathjax', mathJaxLib: 'https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.4/MathJax.js?config=TeX-AMS_HTML', readOnly:true, });</script>";
        $tempRow['question_type'] = $row['question_type'];

        $tempRow['optiona'] = "<textarea id='optiona" . $row['id'] . "' class='form-control'>" . $row['optiona'] . "</textarea> 
        <script type='text/javascript'>CKEDITOR.replace('optiona" . $row['id'] . "', { extraPlugins: 'mathjax', mathJaxLib: 'https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.4/MathJax.js?config=TeX-AMS_HTML', readOnly:true, });</script>";

        $tempRow['optionb'] = "<textarea id='optionb" . $row['id'] . "' class='form-control'>" . $row['optionb'] . "</textarea> 
        <script type='text/javascript'>CKEDITOR.replace('optionb" . $row['id'] . "', { extraPlugins: 'mathjax', mathJaxLib: 'https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.4/MathJax.js?config=TeX-AMS_HTML', readOnly:true, });</script>";

        $tempRow['optionc'] = "<textarea id='optionc" . $row['id'] . "' class='form-control'>" . $row['optionc'] . "</textarea> 
        <script type='text/javascript'>CKEDITOR.replace('optionc" . $row['id'] . "', { extraPlugins: 'mathjax', mathJaxLib: 'https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.4/MathJax.js?config=TeX-AMS_HTML', readOnly:true, });</script>";

        $tempRow['optiond'] = "<textarea id='optiond" . $row['id'] . "' class='form-control'>" . $row['optiond'] . "</textarea> 
        <script type='text/javascript'>CKEDITOR.replace('optiond" . $row['id'] . "', { extraPlugins: 'mathjax', mathJaxLib: 'https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.4/MathJax.js?config=TeX-AMS_HTML', readOnly:true, });</script>";

        $tempRow['optione'] = "<textarea id='optione" . $row['id'] . "' class='form-control'>" . $row['optione'] . "</textarea> 
        <script type='text/javascript'>CKEDITOR.replace('optione" . $row['id'] . "', { extraPlugins: 'mathjax', mathJaxLib: 'https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.4/MathJax.js?config=TeX-AMS_HTML', readOnly:true, });</script>";

        $tempRow['note'] = "<textarea id='note" . $row['id'] . "' class='form-control'>" . $row['note'] . "</textarea> 
        <script type='text/javascript'>CKEDITOR.replace('note" . $row['id'] . "', { extraPlugins: 'mathjax', mathJaxLib: 'https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.4/MathJax.js?config=TeX-AMS_HTML', readOnly:true, });</script>";

        $tempRow['operate'] = $operate;
        $rows[] = $tempRow;
    }

    $bulkData['rows'] = $rows;
    print_r(json_encode($bulkData));
}
