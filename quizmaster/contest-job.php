<?php

if (isset($_GET['selected_contest']) && !empty($_GET['selected_contest'])) {
    include 'library/crud.php';
    $db = new Database();
    $db->connect();

    include 'library/functions.php';
    $fn = new Functions();
    $config = $fn->get_configurations();

    if (isset($config['system_timezone']) && !empty($config['system_timezone'])) {
        date_default_timezone_set($config['system_timezone']);
    } else {
        date_default_timezone_set('Asia/Kolkata');
    }

    $prev_date = date('Y-m-d', strtotime(date('Y-m-d') . ' -1 day'));
    $selected_contest_id = $db->escapeString($_GET['selected_contest']);
    $sql = "SELECT * FROM `contest` WHERE `end_date` <= '$prev_date' AND `id`=" . $selected_contest_id . " LIMIT 1";
    $db->sql($sql);
    $res = $db->getResult();
    if (!empty($res)) {
        foreach ($res as $contest) {
            $prize_status = $contest['prize_status'];
            $contest_name = $contest['name'];
            if ($prize_status == 0) {
                $contest_id = $contest['id'];
                $type = "Contest Winner - $contest_name ";

                $sql1 = "SELECT * FROM contest_prize WHERE contest_id=" . $contest_id . " ORDER BY top_winner ASC";
                $db->sql($sql1);
                $res1 = $db->getResult();

                if (!empty($res1)) {
                    for ($j = 0; $j < count($res1); $j++) {
                        $u_rank = $res1[$j]['top_winner'];
                        $winner_points = $res1[$j]['points'];

                        $sql3 = "SELECT r.*, u.firebase_id FROM (SELECT s.*, @user_rank := @user_rank + 1 user_rank FROM ( SELECT user_id, score FROM contest_leaderboard c join users u on u.id = c.user_id WHERE contest_id='" . $contest_id . "' ) s, (SELECT @user_rank := 0) init ORDER BY score DESC ) r INNER join users u on u.id = r.user_id WHERE r.user_rank='" . $u_rank . "' ORDER BY r.user_rank ASC";
                        $db->sql($sql3);
                        $res3 = $db->getResult();

                        for ($i = 0; $i < count($res3); $i++) {
                            $sql4 = "INSERT INTO `tbl_tracker`(`user_id`,`uid`,`points`,`type`,`date`) VALUES ('" . $res3[$i]['user_id'] . "','" . $res3[$i]['firebase_id'] . "','" . $winner_points . "','" . $type . "','" . date("Y-m-d") . "')";
                            $db->sql($sql4);
                            $sql5 = "UPDATE `users` SET `coins` = `coins` + '" . $winner_points . "' WHERE `id` ='" . $res3[$i]['user_id'] . "'";
                            $db->sql($sql5);
                        }
                    }

                    $sql2 = "UPDATE `contest` SET `prize_status`= '1' WHERE `id` = " . $contest_id;
                    $db->sql($sql2);

                    echo "Successfully prizes distributed for - $contest_name";
                } else {
                    echo "Prizes can not distributed for - $contest_name";
                }
            } else {
                echo "Prizes are already distributed for - $contest_name";
            }
        }
    } else {
        echo "Prize distribution is currently not available. check contest end date.";
    }
} else {
    exit("<script type='text/javascript'>window.close();</script>");
    return false;
}
?>