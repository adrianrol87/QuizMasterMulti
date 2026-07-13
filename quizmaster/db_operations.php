<?php

/*
  API v7.0.7
  Quiz Online - WRTeam.in
  WRTeam Developers
 */
session_start();
if (!isset($_SESSION['id']) && !isset($_SESSION['username'])) {
    header("location:index.php");
    return false;
    exit();
}
include('library/crud.php');
include('library/functions.php');
include('library/fcm-v1.php');

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
$auth_username = $db->escapeString($_SESSION["username"]);

$toDate = date('Y-m-d');
$toDateTime = date('Y-m-d H:i:s');
$allowedExts = array("gif", "jpeg", "jpg", "png", "JPEG", "JPG", "PNG");
$allowedType = array("pdf");

define('ALLOW_MODIFICATION', 1);
/*
  1. add_category
  2. update_category
  3. delete_category
  4. add_subcategory
  5. update_subcategory
  6. delete_subcategory
  7. get_subcategories_of_category
  8. add_question
  9. update_question
  10. delete_question
  11. send_notifications
  12. delete_notification
  13. update_fcm_server_key
  13b. upload_fcm_service_account
  14. delete_question_report
  15. import_questions
  15b. import_categories
  15c. import_subcategories
  16. update_category_order
  17. update_subcategory_order
  18. update_policy
  19. update_terms
  20. update_user
  21. add_admin_form
  22. update_admin
  23. delete_admin
  24. system_configurations
  25. delete_multiple
  26. add_language
  27. update_language
  28. delete_language
  29. get_categories_of_language
  30. update_about_us
  31. update_instructions
  31b. update_delete_account
  32. update_daily_quiz_order
  33. get_selected_date - Date options
  34. add_contest
  35. delete_contest
  36. update_contest
  37. update_contest_status
  38. add_contest_prize
  39. update_contest_prize
  40. delete_contest_prize
  41. add_contest_question
  42. update_contest_question
  43. delete_contest_question
  44. import_contest_questions
  45. battle_settings()
  46. add_learning
  47. update_question
  48. update_learning_status
  49. delete_question
  50.update_category_status
  51. import_word_search_levels
  52. add_word_search_category
  53. update_word_search_category
  54. delete_word_search_category
  55. update_word_search_category_status


  functions
  ----------------
  1. checkadmin($auth_username)
 */

function checkadmin($auth_username)
{
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

function upsert_setting_message($db, $type, $message)
{
    $safeType = $db->escapeString($type);
    $safeMessage = $db->escapeString($message);
    $sql = "select * from `settings` where `type`='" . $safeType . "'";
    $db->sql($sql);
    $res = $db->getResult();
    if (!empty($res)) {
        $sql = "Update `settings` set `message`='" . $safeMessage . "' where `type`='" . $safeType . "'";
    } else {
        $sql = "INSERT INTO `settings`(`type`, `message`, `status`) VALUES ('" . $safeType . "','" . $safeMessage . "',1)";
    }
    $db->sql($sql);
    return $db->getResult();
}

//7. get_subcategories_of_category - ajax dropdown menu options 
if (isset($_POST['get_subcategories_of_category']) && $_POST['get_subcategories_of_category'] != '') {
    $id = $_POST['category_id'];
    if (empty($id)) {
        echo '<option value="">Select Sub Category</option>';
        return false;
    }
    $sql = 'SELECT * FROM `subcategory` WHERE `maincat_id`=' . $id . ' ORDER BY row_order + 0 ASC';

    $db->sql($sql);
    $res = $db->getResult();

    if (isset($_POST['sortable']) && $_POST['sortable'] == 'sortable') {
        $options = '';
        foreach ($res as $category) {
            if (!empty($category["image"])) {
                $options .= "<li id='" . $category["id"] . "'><big>" . $category["row_order"] . ".</big> &nbsp;<img src='images/subcategory/$category[image]' height=30 > " . $category["subcategory_name"] . "</li>";
            } else {
                $options .= "<li id='" . $category["id"] . "'><big>" . $category["row_order"] . ".</big> &nbsp;<img src='images/logo-half.png' height=30 > " . $category["subcategory_name"] . "</li>";
            }
        }
    } else {
        $options = '<option value="">Select Sub Category</option>';
        foreach ($res as $option) {
            $options .= "<option value='" . $option['id'] . "'>" . $option['subcategory_name'] . "</option>";
        }
    }
    echo $options;
}

// 29. get_categories_of_language - ajax dropdown menu options 
if (isset($_POST['get_categories_of_language']) && $_POST['get_categories_of_language'] != '') {
    $id = $_POST['language_id'];
    $type = (isset($_POST['type'])) ? $_POST['type'] : 1;
    if (empty($id)) {
        echo '<option value="">Select Category</option>';
        return false;
    }
    $sql = 'SELECT * FROM `category` WHERE `language_id`=' . $id . ' AND `type`=' . $type . ' ORDER BY row_order + 0 ASC';
    $db->sql($sql);
    $res = $db->getResult();

    if (isset($_POST['sortable']) && $_POST['sortable'] == 'sortable') {
        $options = '';
        foreach ($res as $category) {

            if (!empty($category["image"])) {
                $options .= "<li id='" . $category["id"] . "'><big>" . $category["row_order"] . ".</big> &nbsp;<img src='images/category/$category[image]' height=30 > " . $category["category_name"] . "</li>";
            } else {
                $options .= "<li id='" . $category["id"] . "'><big>" . $category["row_order"] . ".</big> &nbsp;<img src='images/logo-half.png' height=30 > " . $category["category_name"] . "</li>";
            }
        }
    } else {
        $options = '<option value="">Select Category</option>';
        foreach ($res as $option) {
            $options .= "<option value='" . $option['id'] . "'>" . $option['category_name'] . "</option>";
        }
    }
    echo $options;
}

// 33. get_selected_date - Date options 
if (isset($_POST['get_selected_date']) && !empty($_POST['get_selected_date']) && $_POST['language_id'] != "") {
    $selected_date = $db->escapeString($_POST['selected_date']);
    $language_id = $db->escapeString($_POST['language_id']);

    $sql = "SELECT * from daily_quiz WHERE date_published='$selected_date' AND language_id= '$language_id'";
    $db->sql($sql);
    $res = $db->getResult();
    $html = "";

    if (!empty($res)) {
        foreach ($res as $row) {
            $language_id = $row['language_id'];
        }
        $questions = $response = array();
        $questions = $res[0]['questions_id'];
        $sql = "SELECT `id`, `question` FROM `question` WHERE `id` IN (" . $questions . ") ORDER BY FIELD(id," . $questions . ")";
        $db->sql($sql);
        $res = $db->getResult();
        foreach ($res as $question) {
            $html .= "<li id=" . $question['id'] . " class='ui-state-default ui-sortable-handle'>" . $question['id'] . ". " . $question['question'] . "<a class='btn btn-danger btn-xs remove-row pull-right'>x</a></li>";
        }
        $response['error'] = false;
        $response['language_id'] = $language_id;
        $response['questions_list'] = $html;
    } else {
        //        $html .= "<li id='' class='ui-state-default ui-sortable-handle'>There are no questions added today<a class='btn btn-danger btn-xs remove-row pull-right'>x</a></li>";
        $response['error'] = false;
        $response['questions_list'] = $html;
        $response['language_id'] = '';
    }
    print_r(json_encode($response));
}

if (ALLOW_MODIFICATION == 0 && !defined(ALLOW_MODIFICATION)) {
    echo '<label class="alert alert-danger">This operation is not allowed in demo panel!.</label>';
    return false;
}

if (isset($_POST['name']) && isset($_POST['add_category'])) {
    $type = $db->escapeString($_POST['type']);
    $name = $db->escapeString($_POST['name']);
    $plan = isset($_POST['category_plan']) ? $db->escapeString($_POST['category_plan']) : NULL;
    $amount = isset($_POST['category_amount']) ? $db->escapeString($_POST['category_amount']) : 0;
    if ($plan === "Paid" && $amount <= 0) {
        echo '<label class="alert alert-danger">Amount must be greater than 0</label>';
        return false;
    }

    $language_id = ($fn->is_language_mode_enabled()) ? $db->escapeString($_POST['language_id']) : 0;
    $filename = '';
    // common image file extensions
    if ($_FILES['image']['error'] == 0 && $_FILES['image']['size'] > 0) {
        if (!is_dir('images/category')) {
            mkdir('images/category', 0777, true);
        }

        $extension = pathinfo($_FILES["image"]["name"])['extension'];
        if (!(in_array($extension, $allowedExts))) {
            $response['error'] = true;
            $response['message'] = 'Image type is invalid';
            echo json_encode($response);
            return false;
        }
        $target_path = 'images/category/';
        $filename = microtime(true) . '.' . strtolower($extension);
        $full_path = $target_path . "" . $filename;
        if (!move_uploaded_file($_FILES["image"]["tmp_name"], $full_path)) {
            $response['error'] = true;
            $response['message'] = 'Image type is invalid';
            echo json_encode($response);
            return false;
        }
    }
    $sql = "INSERT INTO `category` (`language_id`, `category_name`, `type`, `image`,`plan`,`amount`, `row_order`) VALUES ('" . $language_id . "','" . $name . "','" . $type . "','" . $filename . "','" . $plan . "','" . $amount . "','1')";
    $db->sql($sql);

    echo '<label class="alert alert-success">Category created successfully!</label>';
}

//2. update_category
if (isset($_POST['category_id']) && isset($_POST['update_category'])) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    $id = $_POST['category_id'];
    $name = $db->escapeString($_POST['name']);
    $amount = isset($_POST['update_category_amount']) ? $db->escapeString($_POST['update_category_amount']) : 0;
    $plan = isset($_POST['update_category_plan']) ? $db->escapeString($_POST['update_category_plan']) : '';

    if ($plan == '') {
        $selected_plan = isset($_POST['update_cat_plan']) ? $db->escapeString($_POST['update_cat_plan']) : '2';
        $plan = ($selected_plan == '1') ? 'Paid' : 'Free';
    }

    if ($plan === "Free") {
        $amount = 0;
    }

    if ($plan === "Paid" && $amount <= 0) {
        echo '<label class="alert alert-danger">Amount must be greater than 0</label>';
        return false;
    }

    $language_id = ($fn->is_language_mode_enabled()) ? $db->escapeString($_POST['language_id']) : 0;
    if ($_FILES['image']['size'] != 0 && $_FILES['image']['error'] == 0) {
        if (!is_dir('images/category')) {
            mkdir('images/category', 0777, true);
        }
        //image isn't empty and update the image
        $image_url = $db->escapeString($_POST['image_url']);
        // common image file extensions
        $extension = pathinfo($_FILES["image"]["name"])['extension'];
        if (!(in_array($extension, $allowedExts))) {
            echo '<p class="alert alert-danger">Image type is invalid</p>';
            return false;
        }
        $target_path = 'images/category/';
        $filename = microtime(true) . '.' . strtolower($extension);
        $full_path = $target_path . "" . $filename;
        if (!move_uploaded_file($_FILES["image"]["tmp_name"], $full_path)) {
            echo '<p class="alert alert-danger">Image type is invalid</p>';
            return false;
        }

        if ($image_url != "images/logo-half.png" && file_exists($image_url)) {
            unlink($image_url);
        }
        $sql = "UPDATE category SET `image`='" . $filename . "' WHERE `id`=" . $id;
        $db->sql($sql);
    }

    $sql = "UPDATE `category` SET `category_name`='" . $name . "' , `plan`='" . $plan . "',`amount`='" . $amount . "'";
    $sql .= ($fn->is_language_mode_enabled()) ? ", `language_id` = " . $language_id . " " : "";
    $sql .= " WHERE `id`=" . $id;
    $db->sql($sql);

    if ($fn->is_language_mode_enabled()) {
        $sql1 = "UPDATE subcategory SET `language_id`='" . $language_id . "' WHERE `maincat_id`=" . $id;
        $db->sql($sql1);

        $sql2 = "UPDATE question SET `language_id`='" . $language_id . "' WHERE `category`=" . $id;
        $db->sql($sql2);

        $sql3 = "UPDATE tbl_learning SET `language_id`='" . $language_id . "' WHERE `category`=" . $id;
        $db->sql($sql3);

        $sql4 = "UPDATE tbl_maths_question SET `language_id`='" . $language_id . "' WHERE `category`=" . $id;
        $db->sql($sql4);
    }

    echo "<p class='alert alert-success'>Category updated successfully!</p>";
}

//3. delete_category
if (isset($_GET['delete_category']) && $_GET['delete_category'] != '') {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    $id = $_GET['id'];
    $image = $_GET['image'];
    $sql = 'DELETE FROM `category` WHERE `id`=' . $id;
    if ($db->sql($sql)) {
        if (!empty($image) && file_exists($image)) {
            unlink($image);
        }

        // select sub category images & delete it
        $sql = 'SELECT `image` FROM `subcategory` WHERE `maincat_id`=' . $id;
        $db->sql($sql);
        $sub_category_images = $db->getResult();
        if (!empty($sub_category_images)) {
            foreach ($sub_category_images as $image) {
                if (!empty($image['image']) && file_exists('images/subcategory/' . $image['image'])) {
                    unlink('images/subcategory/' . $image['image']);
                }
            }
        }
        $sql = 'DELETE FROM `subcategory` WHERE `maincat_id`=' . $id;
        $db->sql($sql);

        $sql = 'SELECT `image` FROM `question` WHERE `category`=' . $id;
        $db->sql($sql);
        $question_images = $db->getResult();
        if (!empty($question_images)) {
            foreach ($question_images as $image) {
                if (!empty($image['image']) && file_exists('images/questions/' . $image['image'])) {
                    unlink('images/questions/' . $image['image']);
                }
            }
        }
        $sql = 'DELETE FROM `question` WHERE `category`=' . $id;
        $db->sql($sql);

        $sql2 = 'SELECT `id` FROM `tbl_learning` WHERE `category`=' . $id;
        $db->sql($sql2);
        $question_images2 = $db->getResult();
        if (!empty($question_images2)) {
            $learning_id = $question_images2[0]['id'];
            $sql = 'DELETE FROM `tbl_learning_question` WHERE `learning_id`=' . $learning_id;
            $db->sql($sql);
        }
        $sql2 = 'DELETE FROM `tbl_learning` WHERE `category`=' . $id;
        $db->sql($sql2);

        $sql3 = 'SELECT `image` FROM `tbl_maths_question` WHERE `category`=' . $id;
        $db->sql($sql3);
        $question_images3 = $db->getResult();
        if (!empty($question_images3)) {
            foreach ($question_images3 as $image3) {
                if (!empty($image3['image']) && file_exists('images/maths-question/' . $image3['image'])) {
                    unlink('images/maths-question/' . $image3['image']);
                }
            }
        }
        $sql3 = 'DELETE FROM `tbl_maths_question` WHERE `category`=' . $id;
        $db->sql($sql3);

        echo 1;
    } else {
        echo 0;
    }
}

//4. add_subcategory
if (isset($_POST['name']) && isset($_POST['add_subcategory'])) {
    $name = $db->escapeString($_POST['name']);
    $maincat_id = $db->escapeString($_POST['maincat_id']);
    $language_id = ($fn->is_language_mode_enabled()) ? $db->escapeString($_POST['language_id']) : 0;

    $filename = '';
    // common image file extensions
    if ($_FILES['image']['error'] == 0 && $_FILES['image']['size'] > 0) {
        if (!is_dir('images/subcategory')) {
            mkdir('images/subcategory', 0777, true);
        }
        $extension = pathinfo($_FILES["image"]["name"])['extension'];
        if (!(in_array($extension, $allowedExts))) {
            $response['error'] = true;
            $response['message'] = 'Image type is invalid';
            echo json_encode($response);
            return false;
        }
        $target_path = 'images/subcategory/';
        $filename = microtime(true) . '.' . strtolower($extension);
        $full_path = $target_path . "" . $filename;
        if (!move_uploaded_file($_FILES["image"]["tmp_name"], $full_path)) {
            $response['error'] = true;
            $response['message'] = 'Image type is invalid';
            echo json_encode($response);
            return false;
        }
    }

    $sql = "INSERT INTO `subcategory` (`language_id`,`maincat_id`,`subcategory_name`, `image`,`row_order`) VALUES ('" . $language_id . "','" . $maincat_id . "','" . $name . "','" . $filename . "','0')";
    $db->sql($sql);

    echo '<label class="alert alert-success">Sub Category created successfully!</label>';
}

//5. update_subcategory
if (isset($_POST['subcategory_id']) && isset($_POST['update_subcategory'])) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    $id = $_POST['subcategory_id'];
    $name = $db->escapeString($_POST['name']);
    $maincat_id = $db->escapeString($_POST['maincat_id']);
    $language_id = ($fn->is_language_mode_enabled()) ? $db->escapeString($_POST['language_id']) : 0;

    $status = $db->escapeString($_POST['status']);
    if ($_FILES['image']['size'] != 0 && $_FILES['image']['error'] == 0) {
        if (!is_dir('images/subcategory')) {
            mkdir('images/subcategory', 0777, true);
        }
        //image isn't empty and update the image
        $image_url = $db->escapeString($_POST['image_url']);

        // common image file extensions
        $extension = pathinfo($_FILES["image"]["name"])['extension'];
        if (!(in_array($extension, $allowedExts))) {
            echo '<p class="alert alert-danger">Image type is invalid</p>';
            return false;
        }
        $target_path = 'images/subcategory/';
        $filename = microtime(true) . '.' . strtolower($extension);
        $full_path = $target_path . "" . $filename;
        if (!move_uploaded_file($_FILES["image"]["tmp_name"], $full_path)) {
            echo '<p class="alert alert-danger">Image type is invalid</p>';
            return false;
        }
        if ($image_url != "images/logo-half.png" && file_exists($image_url)) {
            // if its not half logo image
            unlink($image_url);
        }
        $sql = "UPDATE subcategory SET `image`='" . $filename . "' WHERE `id`=" . $id;
        $db->sql($sql);
    }

    $sql = "UPDATE subcategory SET `maincat_id`='" . $maincat_id . "', `subcategory_name`='" . $name . "', `status`='" . $status . "' ";
    $sql .= ($fn->is_language_mode_enabled()) ? ", `language_id` = " . $language_id . " " : "";
    $sql .= " WHERE `id`=" . $id;
    $db->sql($sql);

    $sql1 = "UPDATE question SET `category`='" . $maincat_id . "' ";
    $sql1 .= ($fn->is_language_mode_enabled()) ? ", `language_id` = " . $language_id . " " : "";
    $sql1 .= " WHERE `subcategory` =" . $id;
    $db->sql($sql1);

    // $sql2 = "UPDATE tbl_learning SET `category`='" . $maincat_id . "' ";
    // $sql2 .= ($fn->is_language_mode_enabled()) ? ", `language_id` = " . $language_id . " " : "";
    // $sql2 .= " WHERE `subcategory` =" . $id;
    // $db->sql($sql2);

    $sql3 = "UPDATE tbl_maths_question SET `category`='" . $maincat_id . "' ";
    $sql3 .= ($fn->is_language_mode_enabled()) ? ", `language_id` = " . $language_id . " " : "";
    $sql3 .= " WHERE `subcategory` =" . $id;
    $db->sql($sql3);

    echo "<p class='alert alert-success'>Sub category updated successfully!</p>";
}

//6. delete_subcategory
if (isset($_GET['delete_subcategory']) && $_GET['delete_subcategory'] != '') {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    $id = $_GET['id'];
    $image = $_GET['image'];

    $sql = 'DELETE FROM `subcategory` WHERE `id`=' . $id;
    if ($db->sql($sql)) {
        if (!empty($image) && file_exists($image)) {
            unlink($image);
        }

        $sql = 'SELECT `image` FROM `question` WHERE `subcategory`=' . $id;
        $db->sql($sql);
        $question_images = $db->getResult();
        if (!empty($question_images)) {
            foreach ($question_images as $image) {
                if (!empty($image['image']) && file_exists('images/questions/' . $image['image'])) {
                    unlink('images/questions/' . $image['image']);
                }
            }
        }
        $sql = 'DELETE FROM `question` WHERE `subcategory`=' . $id;
        $db->sql($sql);

        $sql2 = 'SELECT `image` FROM `tbl_maths_question` WHERE `subcategory`=' . $id;
        $db->sql($sql2);
        $question_images2 = $db->getResult();
        if (!empty($question_images2)) {
            foreach ($question_images2 as $image2) {
                if (!empty($image2['image']) && file_exists('images/maths-question/' . $image2['image'])) {
                    unlink('images/maths-question/' . $image2['image']);
                }
            }
        }
        $sql2 = 'DELETE FROM `tbl_maths_question` WHERE `subcategory`=' . $id;
        $db->sql($sql2);

        echo 1;
    } else {
        echo 0;
    }
}

//8. add_question
if (isset($_POST['question']) && isset($_POST['add_question'])) {
    $question = $db->escapeString($_POST['question']);
    $category = $db->escapeString($_POST['category']);
    $subcategory = (empty($_POST['subcategory'])) ? 0 : $db->escapeString($_POST['subcategory']);
    $language_id = ($fn->is_language_mode_enabled()) ? $db->escapeString($_POST['language_id']) : 0;
    $question_type = $db->escapeString($_POST['question_type']);
    $a = $db->escapeString($_POST['a']);
    $b = $db->escapeString($_POST['b']);
    $c = ($question_type == 1) ? $db->escapeString($_POST['c']) : "";
    $d = ($question_type == 1) ? $db->escapeString($_POST['d']) : "";
    $e = ($fn->is_option_e_mode_enabled()) ? (($question_type == 1) ? $db->escapeString($_POST['e']) : "") : "";
    $level = $db->escapeString($_POST['level']);
    $answer = $db->escapeString($_POST['answer']);
    $note = $db->escapeString($_POST['note']);

    $filename = $full_path = '';
    // common image file extensions
    if ($_FILES['image']['error'] == 0 && $_FILES['image']['size'] > 0) {
        if (!is_dir('images/questions')) {
            mkdir('images/questions', 0777, true);
        }

        $extension = pathinfo($_FILES["image"]["name"])['extension'];
        if (!(in_array($extension, $allowedExts))) {
            $response['error'] = true;
            $response['message'] = 'Image type is invalid';
            echo json_encode($response);
            return false;
        }
        $target_path = 'images/questions/';
        $filename = microtime(true) . '.' . strtolower($extension);
        $full_path = $target_path . "" . $filename;
        if (!move_uploaded_file($_FILES["image"]["tmp_name"], $full_path)) {
            $response['error'] = true;
            $response['message'] = 'Image type is invalid';
            echo json_encode($response);
            return false;
        }
    }

    $sql = "INSERT INTO `question`(`category`, `subcategory`, `language_id`, `image`, `question`, `question_type`, `optiona`, `optionb`, `optionc`, `optiond`, `optione`, `level`, `answer`, `note`) VALUES 
	('" . $category . "','" . $subcategory . "','" . $language_id . "','" . $filename . "','" . $question . "','" . $question_type . "','" . $a . "','" . $b . "','" . $c . "','" . $d . "','" . $e . "','" . $level . "','" . $answer . "','" . $note . "')";

    $db->sql($sql);
    $res = $db->getResult();
    echo '<label class="alert alert-success">Question created successfully!</label>';
}

//9. update_question
if (isset($_POST['question_id']) && isset($_POST['update_question'])) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    $id = $_POST['question_id'];

    if ($_FILES['image']['size'] != 0 && $_FILES['image']['error'] == 0) {
        //image isn't empty and update the image
        $image_url = $db->escapeString($_POST['image_url']);

        // common image file extensions
        $extension = pathinfo($_FILES["image"]["name"])['extension'];
        if (!(in_array($extension, $allowedExts))) {
            echo '<p class="alert alert-danger">Image type is invalid</p>';
            return false;
        }
        if (!is_dir('images/questions')) {
            mkdir('images/questions', 0777, true);
        }
        $target_path = 'images/questions/';
        $filename = microtime(true) . '.' . strtolower($extension);
        $full_path = $target_path . "" . $filename;
        if (!move_uploaded_file($_FILES["image"]["tmp_name"], $full_path)) {
            echo '<p class="alert alert-danger">Image type is invalid</p>';
            return false;
        }
        if (!empty($image_url) && file_exists($image_url)) {
            unlink($image_url);
        }
        $sql = "UPDATE `question` SET `image`='" . $filename . "' where `id`=" . $id;
        $db->sql($sql);
    }

    $question = $db->escapeString($_POST['question']);
    $category = $db->escapeString($_POST['category']);
    $subcategory = (empty($_POST['subcategory'])) ? 0 : $db->escapeString($_POST['subcategory']);
    $language_id = ($fn->is_language_mode_enabled()) ? $db->escapeString($_POST['language_id']) : 0;
    $question_type = $db->escapeString($_POST['edit_question_type']);
    $a = $db->escapeString($_POST['a']);
    $b = $db->escapeString($_POST['b']);
    $c = ($question_type == 1) ? $db->escapeString($_POST['c']) : "";
    $d = ($question_type == 1) ? $db->escapeString($_POST['d']) : "";
    if ($fn->is_option_e_mode_enabled()) {
        $e = ($question_type == 1) ? $db->escapeString($_POST['e']) : "";
    }
    $level = $db->escapeString($_POST['level']);
    $answer = $db->escapeString($_POST['answer']);
    $note = $db->escapeString($_POST['note']);

    $sql = "Update `question` set `question`='" . $question . "', `category`='" . $category . "', `subcategory`='" . $subcategory . "',`question_type`='" . $question_type . "',`optiona`='" . $a . "',`optionb`='" . $b . "' ,`optionc`='" . $c . "' ,`optiond`='" . $d . "', `answer`='" . $answer . "' ,`level`='" . $level . "',`note`='" . $note . "'";
    $sql .= ($fn->is_option_e_mode_enabled()) ? ",`optione`='" . $e . "'" : "";
    $sql .= ($fn->is_language_mode_enabled()) ? ", `language_id`=" . $language_id : "";
    $sql .= " where `id`=" . $id;
    $db->sql($sql);

    echo "<p class='alert alert-success'>Question updated successfully!</p>";
}

//10. delete_question
if (isset($_GET['delete_question']) && $_GET['delete_question'] != '') {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    $id = $_GET['id'];
    $image = $_GET['image'];

    $sql = 'DELETE FROM `question` WHERE `id`=' . $id;
    if ($db->sql($sql)) {
        if (!empty($image) && file_exists($image)) {
            unlink($image);
        }
        echo 1;
    } else {
        echo 0;
    }
}

//11. send_notifications - send notifications to users
if (isset($_POST['title']) && isset($_POST['send_notifications'])) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    //creating a new push
    $title = $db->escapeString($_POST['title']);
    $message = $db->escapeString($_POST['message']);
    $users = $db->escapeString($_POST['users']);
    $type = $db->escapeString($_POST['type']);

    $maxlevel = $no_of = "0";
    $maincat_id = "0";
    $language_id = "0";
    $category_type = "0";
    if ($type == 'category') {
        $maincat_id = $db->escapeString($_POST['maincat_id']);

        $sql = "select 	type as category_type, language_id FROM category WHERE id = " . $maincat_id;
        $db->sql($sql);
        $res = $db->getResult();
        $language_id = $res[0]['language_id'];
        $category_type = $res[0]['category_type'];

        $sql1 = "select max(`level`) as `maxlevel` FROM question WHERE category = " . $maincat_id;
        $db->sql($sql1);
        $res1 = $db->getResult();
        $maxlevel = $res1[0]['maxlevel'];

        $sql2 = "SELECT count(`id`) as no_of from subcategory s WHERE s.maincat_id = " . $maincat_id . " and s.status = 1 ";
        $db->sql($sql2);
        $res2 = $db->getResult();
        $no_of = $res2[0]['no_of'];
    }

    if ($users == 'all') {
        $sql = "select `fcm_id` from `users` ";
        $db->sql($sql);
        $res = $db->getResult();
        $fcm_ids = array();
        foreach ($res as $fcm_id) {
            if (!empty($fcm_id['fcm_id'])) {
                $fcm_ids[] = $fcm_id['fcm_id'];
            }
        }
    } elseif ($users == 'selected') {
        $selected_list = $_POST['selected_list'];
        if (empty($selected_list)) {
            $response['error'] = true;
            $response['message'] = 'Please Select the users from the table';
            echo json_encode($response);
            return false;
        }
        $fcm_ids = array();
        $fcm_ids = explode(",", $selected_list);
    }

    $registrationIDs = array_values(array_filter($fcm_ids));

    if (empty($registrationIDs)) {
        echo '<p class="alert alert-danger">No users have a valid FCM token yet.</p>';
        return false;
    }

    $include_image = (isset($_POST['include_image']) && $_POST['include_image'] == 'on') ? TRUE : FALSE;
    if ($include_image) {
        if (!is_dir('images/notifications')) {
            mkdir('images/notifications', 0777, true);
        }
        // common image file extensions
        $extension = pathinfo($_FILES["image"]["name"])['extension'];
        if (!(in_array($extension, $allowedExts))) {
            $response['error'] = true;
            $response['message'] = 'Image type is invalid';
            echo json_encode($response);
            return false;
        }
        $target_path = 'images/notifications/';
        $filename = microtime(true) . '.' . strtolower($extension);
        $full_path = $target_path . "" . $filename;
        if (!move_uploaded_file($_FILES["image"]["tmp_name"], $full_path)) {
            $response['error'] = true;
            $response['message'] = 'Image type is invalid';
            echo json_encode($response);
            return false;
        }
        $sql = "INSERT INTO `notifications`(`title`,`message`,`users`,`type`,`type_id`,`image`) VALUES 
			('" . $title . "','" . $message . "','" . $users . "','" . $type . "'," . $maincat_id . ",'" . $filename . "')";
    } else {
        $sql = "INSERT INTO `notifications`(`title`,`message`,`users`,`type`,`type_id`,`image`) VALUES 
			('" . $title . "','" . $message . "','" . $users . "','" . $type . "'," . $maincat_id . ",'')";
    }

    $db->sql($sql);
    $newMsg = array();
    $fcmMsg = array();

    //first check if the push has an image with it
    if ($include_image) {
        $fcmMsg = array(
            'title' => $title,
            'body' => $message,
            'image' => DOMAIN_URL . $full_path,
            'type' => $type,
            'type_id' => $maincat_id,
            'language_id' => $language_id,
            'maxlevel' => $maxlevel,
            'no_of' => $no_of,
            'category_type' => $category_type
        );
        // $newMsg['data'] = $fcmMsg;
    } else {
        //if the push don't have an image give null in place of image
        $fcmMsg = array(
            'title' => $title,
            'body' => $message,
            'image' => "no_image",
            'type' => $type,
            'type_id' => $maincat_id,
            'language_id' => $language_id,
            'maxlevel' => $maxlevel,
            'no_of' => $no_of,
            'category_type' => $category_type
        );
        // $newMsg['data'] = $fcmMsg;
    }
    // $notification_msg = array(
    //     'title' => $title,
    //     'body' => $message,
    // );
    $success = $failure = 0;
    $failureMessages = array();

    foreach ($registrationIDs as $registrationID) {
        try {
            fcm_send_v1_message($registrationID, $fcmMsg);
            $success++;
        } catch (Exception $e) {
            $failure++;
            $failureMessages[] = $e->getMessage();
        }
    }

    if ($success > 0) {
        $message = 'Notification sent. Success: ' . $success . ' | Failed: ' . $failure;
        if (!empty($failureMessages)) {
            $message .= '<br><small>' . htmlspecialchars(implode(' | ', array_slice(array_unique($failureMessages), 0, 2))) . '</small>';
        }
        echo '<p class="alert alert-success">' . $message . '</p>';
    } else {
        $message = 'No notification could be sent.';
        if (!empty($failureMessages)) {
            $message .= '<br><small>' . htmlspecialchars(implode(' | ', array_slice(array_unique($failureMessages), 0, 2))) . '</small>';
        } else {
            $message .= ' Verify your Firebase service account JSON.';
        }
        echo '<p class="alert alert-danger">' . $message . '</p>';
    }
}

// 12. delete_notification
if (isset($_POST['id']) && isset($_POST['delete_notification'])) {
    $id = $_POST['id'];
    $sql = "DELETE FROM `notifications` WHERE `id`=" . $id;
    if ($db->sql($sql)) {
        if (isset($_POST['image']) && $_POST['image'] != '') {
            $image = 'images/notifications/' . $_POST['image'];
            unlink($image);
        }
        echo 1;
    } else
        echo 0;
}

// 13. update_fcm_server_key()
if (isset($_POST['update_fcm_server_key'])) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    $fcm_key = $db->escapeString($_POST['fcm_key']);
    $update_fcm_server_key_id = $db->escapeString($_POST['update_fcm_server_key_id']);
    if (empty($_POST['update_fcm_server_key_id'])) {
        $sql = "INSERT INTO tbl_fcm_key (fcm_key) VALUES ('" . $fcm_key . "')";
        $db->sql($sql);
        $res = $db->getResult();
        echo "<p class='alert alert-success'>FCM Key Inserted Successfully!</p><br>";
    } else {
        $sql = "Update `tbl_fcm_key` set `fcm_key`='" . $fcm_key . "' where `id`=" . $update_fcm_server_key_id;
        $db->sql($sql);
        $res = $db->getResult();
        echo "<p class='alert alert-success'>FCM Key Updated Successfully!</p><br>";
    }
}

if (isset($_POST['upload_fcm_service_account'])) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }

    if (empty($_FILES['service_account_json']['tmp_name'])) {
        echo "<label class='alert alert-danger'>Please select the Firebase service account JSON file.</label>";
        return false;
    }

    $extension = strtolower(pathinfo($_FILES['service_account_json']['name'], PATHINFO_EXTENSION));
    if ($extension !== 'json') {
        echo "<label class='alert alert-danger'>Only JSON files are allowed.</label>";
        return false;
    }

    $raw = file_get_contents($_FILES['service_account_json']['tmp_name']);
    $json = json_decode($raw, true);

    if (!is_array($json) || empty($json['project_id']) || empty($json['client_email']) || empty($json['private_key'])) {
        echo "<label class='alert alert-danger'>The uploaded JSON is not a valid Firebase service account file.</label>";
        return false;
    }

    $directory = __DIR__ . '/service-accounts';
    if (!is_dir($directory)) {
        mkdir($directory, 0777, true);
    }

    $target = $directory . '/firebase-adminsdk.json';
    if (!move_uploaded_file($_FILES['service_account_json']['tmp_name'], $target)) {
        echo "<label class='alert alert-danger'>The Firebase JSON file could not be saved.</label>";
        return false;
    }

    echo "<label class='alert alert-success'>Firebase service account uploaded successfully.</label>";
}

// 14. delete_question_report
if (isset($_GET['delete_question_report']) && $_GET['delete_question_report'] != '') {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    $id = $_GET['id'];

    $sql = 'DELETE FROM `question_reports` WHERE `id`=' . $id;
    if ($db->sql($sql)) {
        echo 1;
    } else {
        echo 0;
    }
}

// 15. import_questions - import questions to database from a CSV file
if (isset($_POST['import_questions']) && $_POST['import_questions'] == 1) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    $count = $count1 = 0;
    $filename = $_FILES["questions_file"]["tmp_name"];
    $file_extension = pathinfo($_FILES["questions_file"]["name"], PATHINFO_EXTENSION);
    if ($_FILES["questions_file"]["size"] > 0 && $file_extension == "csv") {
        $file = fopen($filename, "r");

        while (($emapData = fgetcsv($file, 10000, ",")) !== FALSE) {
            if (count($emapData) > 2) {
                $emapData[0] = $db->escapeString($emapData[0]); //category
                $emapData[1] = (empty($db->escapeString($emapData[1]))) ? "0" : $db->escapeString($db->escapeString($emapData[1])); //subcategory
                $emapData[2] = ($fn->is_language_mode_enabled()) ? $db->escapeString($emapData[2]) : "0";   //language_id
                $emapData[3] = $db->escapeString(trim($emapData[3]));   //question_type
                $emapData[4] = $db->escapeString($emapData[4]);     //question
                $emapData[5] = $db->escapeString($emapData[5]);    // optiona
                $emapData[6] = $db->escapeString($emapData[6]);    // optionb
                $emapData[7] = $db->escapeString($emapData[7]);    // optionc
                $emapData[8] = $db->escapeString($emapData[8]);    // optiond
                $emapData[9] = (empty($db->escapeString($emapData[9]))) ? "" : $db->escapeString($emapData[9]);  // optione
                $emapData[10] = $db->escapeString(trim($emapData[10]));  //answer
                $emapData[11] = $db->escapeString($emapData[11]);       //level
                $emapData[12] = $db->escapeString($emapData[12]);      // note
                $count++;
                if ($count > 1) {
                    if ($emapData[3] == '1') {
                        if ($emapData[0] != '' && $emapData[1] != '' && $emapData[2] != '' && !empty($emapData[3]) && $emapData[4] != '' && $emapData[5] != '' && $emapData[6] != '' && $emapData[7] != '' && $emapData[8] != '' && !empty($emapData[10]) && $emapData[11] != '') {
                            $empty_value_found = true;
                        } else {
                            $empty_value_found = false;
                            echo '<p class="text-danger">Please Check ' . $count . ' row</p>';
                            break;
                        }
                    } else if ($emapData[3] == '2') {
                        if ($emapData[0] != '' && $emapData[1] != '' && $emapData[2] != '' && !empty($emapData[3]) && $emapData[4] != '' && $emapData[5] != '' && $emapData[6] != '' && !empty($emapData[10]) && $emapData[11] != '') {
                            $empty_value_found = true;
                        } else {
                            $empty_value_found = false;
                            echo '<p class="text-danger">Please Check ' . $count . ' row</p>';
                            break;
                        }
                    } else {
                        $empty_value_found = false;
                        break;
                    }
                }
            }
        }
        fclose($file);
        if ($empty_value_found == TRUE) {
            $file = fopen($filename, "r");
            while (($emapData1 = fgetcsv($file, 10000, ",")) !== FALSE) {
                if (count($emapData1) > 2) {
                    $emapData1[0] = $db->escapeString($emapData1[0]);
                    $emapData1[1] = (empty($db->escapeString($emapData1[1]))) ? "0" : $db->escapeString($db->escapeString($emapData1[1]));
                    $emapData1[2] = ($fn->is_language_mode_enabled()) ? $db->escapeString($emapData1[2]) : "0";
                    $emapData1[3] = $db->escapeString($emapData1[3]);
                    $emapData1[4] = $db->escapeString($emapData1[4]);
                    $emapData1[5] = $db->escapeString($emapData1[5]);
                    $emapData1[6] = $db->escapeString($emapData1[6]);
                    $emapData1[7] = $db->escapeString($emapData1[7]);
                    $emapData1[8] = $db->escapeString($emapData1[8]);
                    $emapData1[9] = (empty($db->escapeString($emapData1[9]))) ? "" : $db->escapeString($emapData1[9]);
                    $emapData1[10] = $db->escapeString(trim($emapData1[10]));
                    $emapData1[11] = $db->escapeString($emapData1[11]);
                    $emapData1[12] = $db->escapeString($emapData1[12]);
                    $count1++;
                    if ($count1 > 1) {
                        if (count($emapData1) > 2) {
                            $sql = "INSERT INTO `question`(`category`, `subcategory`, `language_id`, `image`, `question_type`, `question`,`optiona`, `optionb`, `optionc`, `optiond`,  `optione`, `answer`, `level`, `note`) VALUES 
						('$emapData1[0]','$emapData1[1]','$emapData1[2]','','$emapData1[3]','$emapData1[4]','$emapData1[5]','$emapData1[6]','$emapData1[7]','$emapData1[8]','$emapData1[9]','$emapData1[10]','$emapData1[11]','$emapData1[12]')";
                            $db->sql($sql);
                        }
                    }
                }
            }
            fclose($file);
            echo "<p class='alert alert-success'>CSV file is successfully imported!</p>";
        } else {
            echo "<p class='alert alert-danger'>Please fill all the data in CSV file!</p>";
        }
    } else {
        echo "<p class='alert alert-danger'>Invalid file format! Please upload data in CSV file!</p>";
    }
}

// 51. import_word_search_levels - import word search levels from a CSV file
if (isset($_POST['import_word_search_levels']) && $_POST['import_word_search_levels'] == 1) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }

    $db->sql("SHOW TABLES LIKE 'word_search_level'");
    $module_exists = !empty($db->getResult());
    if (!$module_exists) {
        echo "<p class='alert alert-danger'>Word Search module tables were not found. Import word-search-module.sql in phpMyAdmin first.</p>";
        return false;
    }

    $filename = $_FILES["levels_file"]["tmp_name"];
    $file_extension = pathinfo($_FILES["levels_file"]["name"], PATHINFO_EXTENSION);
    $inserted = 0;
    $updated = 0;
    $skipped = 0;

    if ($_FILES["levels_file"]["size"] > 0 && strtolower($file_extension) == "csv") {
        $file = fopen($filename, "r");
        $row_number = 0;

        while (($row = fgetcsv($file, 10000, ",")) !== FALSE) {
            $row_number++;
            if ($row_number == 1) {
                continue;
            }

            if (count($row) < 8) {
                $skipped++;
                continue;
            }

            $category_name = trim($db->escapeString($row[0]));
            $language_id = $fn->is_language_mode_enabled() ? (int) $db->escapeString($row[1]) : 0;
            $level_number = (int) $db->escapeString($row[2]);
            $board_rows = (int) $db->escapeString($row[3]);
            $board_cols = (int) $db->escapeString($row[4]);
            $time_limit = (int) $db->escapeString($row[5]);
            $reward_coins = (int) $db->escapeString($row[6]);
            $words_raw = trim($row[7]);

            if ($category_name === '' || $level_number <= 0 || $board_rows <= 0 || $board_cols <= 0 || $time_limit <= 0 || $words_raw === '') {
                $skipped++;
                continue;
            }

            $words = array();
            foreach (explode('|', $words_raw) as $word) {
                $clean_word = trim($word);
                if ($clean_word !== '') {
                    $words[] = $clean_word;
                }
            }

            if (empty($words)) {
                $skipped++;
                continue;
            }

            $words_json = $db->escapeString(json_encode(array_values($words), JSON_UNESCAPED_UNICODE));

            $db->sql("SELECT id FROM word_search_category WHERE language_id='$language_id' AND title='$category_name' LIMIT 1");
            $category_res = $db->getResult();

            if (empty($category_res)) {
                $db->sql("SELECT COALESCE(MAX(row_order), 0) + 1 AS next_order FROM word_search_category");
                $order_res = $db->getResult();
                $next_order = !empty($order_res) ? (int) $order_res[0]['next_order'] : 1;

                $sql = "INSERT INTO word_search_category(language_id, title, row_order, status) VALUES ('$language_id', '$category_name', '$next_order', 1)";
                $db->sql($sql);
                $db->getResult();
                $category_id = $db->insert_id();
            } else {
                $category_id = (int) $category_res[0]['id'];
            }

            $db->sql("SELECT id FROM word_search_level WHERE category_id='$category_id' AND language_id='$language_id' AND level_number='$level_number' LIMIT 1");
            $level_res = $db->getResult();

            if (empty($level_res)) {
                $sql = "INSERT INTO word_search_level(category_id, language_id, level_number, board_rows, board_cols, time_limit, reward_coins, words_json, status) VALUES ('$category_id', '$language_id', '$level_number', '$board_rows', '$board_cols', '$time_limit', '$reward_coins', '$words_json', 1)";
                $db->sql($sql);
                $db->getResult();
                $inserted++;
            } else {
                $level_id = (int) $level_res[0]['id'];
                $sql = "UPDATE word_search_level SET board_rows='$board_rows', board_cols='$board_cols', time_limit='$time_limit', reward_coins='$reward_coins', words_json='$words_json', status='1' WHERE id='$level_id'";
                $db->sql($sql);
                $db->getResult();
                $updated++;
            }
        }

        fclose($file);
        echo "<p class='alert alert-success'>Word Search import completed. Inserted: <strong>$inserted</strong>, Updated: <strong>$updated</strong>, Skipped: <strong>$skipped</strong>.</p>";
    } else {
        echo "<p class='alert alert-danger'>Invalid file format! Please upload data in CSV file!</p>";
    }
}

// 51A. import_game_2048_challenge_levels - import 2048 challenge levels from a CSV file
if (isset($_POST['import_game_2048_challenge_levels']) && $_POST['import_game_2048_challenge_levels'] == 1) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }

    $db->sql("SHOW TABLES LIKE 'game_2048_challenge_level'");
    $module_exists = !empty($db->getResult());
    if (!$module_exists) {
        echo "<p class='alert alert-danger'>2048 Retos module tables were not found. Import game-2048-challenge-module.sql in phpMyAdmin first.</p>";
        return false;
    }

    $filename = $_FILES["levels_file"]["tmp_name"];
    $file_extension = pathinfo($_FILES["levels_file"]["name"], PATHINFO_EXTENSION);
    $inserted = 0;
    $updated = 0;
    $skipped = 0;

    if ($_FILES["levels_file"]["size"] > 0 && strtolower($file_extension) == "csv") {
        $file = fopen($filename, "r");
        $row_number = 0;

        while (($row = fgetcsv($file, 10000, ",")) !== FALSE) {
            $row_number++;
            if ($row_number == 1) {
                continue;
            }

            if (count($row) < 10) {
                $skipped++;
                continue;
            }

            $level_number = (int) $db->escapeString($row[0]);
            $move_limit = (int) $db->escapeString($row[1]);
            $goal_1_value = (int) $db->escapeString($row[2]);
            $goal_1_count = (int) $db->escapeString($row[3]);
            $goal_2_value = (int) $db->escapeString($row[4]);
            $goal_2_count = (int) $db->escapeString($row[5]);
            $goal_3_value = (int) $db->escapeString($row[6]);
            $goal_3_count = (int) $db->escapeString($row[7]);
            $difficulty = trim($db->escapeString($row[8]));
            $note_balance = trim($db->escapeString($row[9]));
            $status = isset($row[10]) ? (int) $db->escapeString($row[10]) : 1;
            $status = ($status === 0) ? 0 : 1;

            if ($level_number <= 0 || $move_limit <= 0 || $goal_1_value <= 0 || $goal_1_count <= 0) {
                $skipped++;
                continue;
            }

            $db->sql("SELECT id FROM game_2048_challenge_level WHERE level_number='$level_number' LIMIT 1");
            $level_res = $db->getResult();

            if (empty($level_res)) {
                $sql = "INSERT INTO game_2048_challenge_level(level_number, move_limit, goal_1_value, goal_1_count, goal_2_value, goal_2_count, goal_3_value, goal_3_count, difficulty, note_balance, status) VALUES ('$level_number', '$move_limit', '$goal_1_value', '$goal_1_count', '$goal_2_value', '$goal_2_count', '$goal_3_value', '$goal_3_count', '$difficulty', '$note_balance', '$status')";
                $db->sql($sql);
                $db->getResult();
                $inserted++;
            } else {
                $level_id = (int) $level_res[0]['id'];
                $sql = "UPDATE game_2048_challenge_level SET move_limit='$move_limit', goal_1_value='$goal_1_value', goal_1_count='$goal_1_count', goal_2_value='$goal_2_value', goal_2_count='$goal_2_count', goal_3_value='$goal_3_value', goal_3_count='$goal_3_count', difficulty='$difficulty', note_balance='$note_balance', status='$status' WHERE id='$level_id'";
                $db->sql($sql);
                $db->getResult();
                $updated++;
            }
        }

        fclose($file);
        echo "<p class='alert alert-success'>2048 Retos import completed. Inserted: <strong>$inserted</strong>, Updated: <strong>$updated</strong>, Skipped: <strong>$skipped</strong>.</p>";
    } else {
        echo "<p class='alert alert-danger'>Invalid file format! Please upload data in CSV file!</p>";
    }
}

// 52. add_word_search_category
if (isset($_POST['name']) && isset($_POST['add_word_search_category'])) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }

    $name = $db->escapeString($_POST['name']);
    $language_id = ($fn->is_language_mode_enabled()) ? $db->escapeString($_POST['language_id']) : 0;
    $plan = isset($_POST['word_search_category_plan']) ? $db->escapeString($_POST['word_search_category_plan']) : 'Free';
    $amount = isset($_POST['word_search_category_amount']) ? (int) $db->escapeString($_POST['word_search_category_amount']) : 0;
    if ($plan === 'Free') {
        $amount = 0;
    }
    $filename = '';

    if ($_FILES['image']['error'] == 0 && $_FILES['image']['size'] > 0) {
        if (!is_dir('images/category')) {
            mkdir('images/category', 0777, true);
        }

        $extension = pathinfo($_FILES["image"]["name"])['extension'];
        if (!(in_array($extension, $allowedExts))) {
            echo '<p class="alert alert-danger">Image type is invalid</p>';
            return false;
        }
        $target_path = 'images/category/';
        $filename = microtime(true) . '.' . strtolower($extension);
        $full_path = $target_path . $filename;
        if (!move_uploaded_file($_FILES["image"]["tmp_name"], $full_path)) {
            echo '<p class="alert alert-danger">Image upload failed</p>';
            return false;
        }
    }

    $db->sql("SELECT COALESCE(MAX(row_order), 0) + 1 AS next_order FROM word_search_category");
    $order_res = $db->getResult();
    $next_order = !empty($order_res) ? (int) $order_res[0]['next_order'] : 1;

    $sql = "INSERT INTO `word_search_category` (`language_id`, `title`, `image`, `plan`, `amount`, `row_order`, `status`) VALUES ('" . $language_id . "', '" . $name . "', '" . $filename . "', '" . $plan . "', '" . $amount . "', '" . $next_order . "', '1')";
    $db->sql($sql);
    echo '<label class="alert alert-success">Word Search category created successfully!</label>';
}

// 52A. delete_word_search_level
if (isset($_GET['delete_word_search_level']) && $_GET['delete_word_search_level'] != '') {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }

    $id = (int) $_GET['id'];
    $db->sql("SELECT category_id, level_number FROM `word_search_level` WHERE `id`=" . $id . " LIMIT 1");
    $level = $db->getResult();

    if (empty($level)) {
        echo 0;
        return false;
    }

    $category_id = (int) $level[0]['category_id'];
    $level_number = (int) $level[0]['level_number'];

    $db->sql("DELETE FROM `word_search_user_progress` WHERE `category_id`=" . $category_id . " AND `level_number`=" . $level_number);
    $sql = "DELETE FROM `word_search_level` WHERE `id`=" . $id;
    if ($db->sql($sql)) {
        echo 1;
    } else {
        echo 0;
    }
}

// 52B. delete_game_2048_challenge_level
if (isset($_GET['delete_game_2048_challenge_level']) && $_GET['delete_game_2048_challenge_level'] != '') {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }

    $id = (int) $_GET['id'];
    $db->sql("SELECT level_number FROM `game_2048_challenge_level` WHERE `id`=" . $id . " LIMIT 1");
    $level = $db->getResult();

    if (empty($level)) {
        echo 0;
        return false;
    }

    $level_number = (int) $level[0]['level_number'];
    $db->sql("DELETE FROM `game_2048_challenge_user_progress` WHERE `level_number`=" . $level_number);

    $sql = "DELETE FROM `game_2048_challenge_level` WHERE `id`=" . $id;
    if ($db->sql($sql)) {
        echo 1;
    } else {
        echo 0;
    }
}

// 53. update_word_search_category
if (isset($_POST['word_search_category_id']) && isset($_POST['update_word_search_category'])) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }

    $id = (int) $_POST['word_search_category_id'];
    $name = $db->escapeString($_POST['name']);
    $language_id = ($fn->is_language_mode_enabled()) ? $db->escapeString($_POST['language_id']) : 0;
    $amount = isset($_POST['update_word_search_category_amount']) ? (int) $db->escapeString($_POST['update_word_search_category_amount']) : 0;
    $plan = isset($_POST['update_word_search_category_plan']) ? $db->escapeString($_POST['update_word_search_category_plan']) : '';
    if ($plan == '') {
        $selected_plan = isset($_POST['update_word_search_cat_plan']) ? $db->escapeString($_POST['update_word_search_cat_plan']) : '2';
        $plan = ($selected_plan == '1') ? 'Paid' : 'Free';
    }
    if ($plan === 'Free') {
        $amount = 0;
    }

    if ($_FILES['image']['size'] != 0 && $_FILES['image']['error'] == 0) {
        if (!is_dir('images/category')) {
            mkdir('images/category', 0777, true);
        }
        $image_url = $db->escapeString($_POST['image_url']);
        $extension = pathinfo($_FILES["image"]["name"])['extension'];
        if (!(in_array($extension, $allowedExts))) {
            echo '<p class="alert alert-danger">Image type is invalid</p>';
            return false;
        }
        $target_path = 'images/category/';
        $filename = microtime(true) . '.' . strtolower($extension);
        $full_path = $target_path . $filename;
        if (!move_uploaded_file($_FILES["image"]["tmp_name"], $full_path)) {
            echo '<p class="alert alert-danger">Image upload failed</p>';
            return false;
        }
        if ($image_url != "images/logo-half.png" && file_exists($image_url)) {
            unlink($image_url);
        }
        $db->sql("UPDATE word_search_category SET `image`='" . $filename . "' WHERE `id`=" . $id);
    }

    $sql = "UPDATE `word_search_category` SET `title`='" . $name . "', `plan`='" . $plan . "', `amount`='" . $amount . "'";
    $sql .= ($fn->is_language_mode_enabled()) ? ", `language_id`='" . $language_id . "'" : "";
    $sql .= " WHERE `id`=" . $id;
    $db->sql($sql);
    echo "<p class='alert alert-success'>Word Search category updated successfully!</p>";
}

// 54. delete_word_search_category
if (isset($_GET['delete_word_search_category']) && $_GET['delete_word_search_category'] != '') {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    $id = (int) $_GET['id'];
    $image = $_GET['image'];

    $sql = 'DELETE FROM `word_search_category` WHERE `id`=' . $id;
    if ($db->sql($sql)) {
        if (!empty($image) && file_exists($image)) {
            unlink($image);
        }
        $db->sql('DELETE FROM `word_search_level` WHERE `category_id`=' . $id);
        $db->sql('DELETE FROM `word_search_user_progress` WHERE `category_id`=' . $id);
        $db->sql('DELETE FROM `word_search_user_purchased_category` WHERE `category_id`=' . $id);
        echo 1;
    } else {
        echo 0;
    }
}

// 55. update_word_search_category_status
if (isset($_POST['word_search_category_status_id']) && isset($_POST['update_word_search_category_status'])) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    $id = (int) $_POST['word_search_category_status_id'];
    $status = $db->escapeString($_POST['status']);
    $db->sql("UPDATE `word_search_category` SET `status`='" . $status . "' WHERE `id`=" . $id);
    echo "<p class='alert alert-success'>Word Search category status updated successfully!</p>";
}

if (isset($_POST['import_categories']) && $_POST['import_categories'] == 1) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }

    $filename = $_FILES["categories_file"]["tmp_name"];
    $file_extension = pathinfo($_FILES["categories_file"]["name"], PATHINFO_EXTENSION);
    if ($_FILES["categories_file"]["size"] > 0 && $file_extension == "csv") {
        $file = fopen($filename, "r");
        $inserted = 0;

        while (($emapData = fgetcsv($file, 10000, ",")) !== FALSE) {
            if (count($emapData) >= 5) {
                $language_id = $fn->is_language_mode_enabled() ? $db->escapeString(trim($emapData[0])) : 0;
                $category_name = $db->escapeString(trim($emapData[1]));
                $type = $db->escapeString(trim($emapData[2]));
                $plan = ucfirst(strtolower($db->escapeString(trim($emapData[3]))));
                $amount = $db->escapeString(trim($emapData[4]));

                if ($category_name == '' || $type == '' || $plan == '') {
                    continue;
                }

                if ($plan !== 'Paid') {
                    $plan = 'Free';
                    $amount = 0;
                }

                $sql = "SELECT id FROM category WHERE category_name='" . $category_name . "' AND type='" . $type . "'";
                $sql .= $fn->is_language_mode_enabled() ? " AND language_id='" . $language_id . "'" : "";
                $sql .= " LIMIT 1";
                $db->sql($sql);
                $res = $db->getResult();

                if (!empty($res)) {
                    $sql = "UPDATE category SET plan='" . $plan . "', amount='" . $amount . "' WHERE id=" . $res[0]['id'];
                } else {
                    $sql = "INSERT INTO category (`language_id`, `category_name`, `type`, `image`, `plan`, `amount`, `row_order`) VALUES ('" . $language_id . "','" . $category_name . "','" . $type . "','','" . $plan . "','" . $amount . "','0')";
                }
                $db->sql($sql);
                $inserted++;
            }
        }
        fclose($file);
        echo "<p class='alert alert-success'>Main categories imported successfully! Rows processed: " . $inserted . "</p>";
    } else {
        echo "<p class='alert alert-danger'>Invalid file format! Please upload data in CSV file!</p>";
    }
}

if (isset($_POST['import_subcategories']) && $_POST['import_subcategories'] == 1) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }

    $filename = $_FILES["subcategories_file"]["tmp_name"];
    $file_extension = pathinfo($_FILES["subcategories_file"]["name"], PATHINFO_EXTENSION);
    if ($_FILES["subcategories_file"]["size"] > 0 && $file_extension == "csv") {
        $file = fopen($filename, "r");
        $inserted = 0;

        while (($emapData = fgetcsv($file, 10000, ",")) !== FALSE) {
            if (count($emapData) >= 3) {
                $language_id = $fn->is_language_mode_enabled() ? $db->escapeString(trim($emapData[0])) : 0;
                $main_category_name = $db->escapeString(trim($emapData[1]));
                $subcategory_name = $db->escapeString(trim($emapData[2]));

                if ($main_category_name == '' || $subcategory_name == '') {
                    continue;
                }

                $sql = "SELECT id FROM category WHERE category_name='" . $main_category_name . "'";
                $sql .= $fn->is_language_mode_enabled() ? " AND language_id='" . $language_id . "'" : "";
                $sql .= " LIMIT 1";
                $db->sql($sql);
                $category = $db->getResult();

                if (empty($category)) {
                    continue;
                }

                $maincat_id = $category[0]['id'];

                $sql = "SELECT id FROM subcategory WHERE maincat_id='" . $maincat_id . "' AND subcategory_name='" . $subcategory_name . "'";
                $sql .= $fn->is_language_mode_enabled() ? " AND language_id='" . $language_id . "'" : "";
                $sql .= " LIMIT 1";
                $db->sql($sql);
                $res = $db->getResult();

                if (empty($res)) {
                    $sql = "INSERT INTO subcategory (`language_id`, `maincat_id`, `subcategory_name`, `image`, `row_order`) VALUES ('" . $language_id . "','" . $maincat_id . "','" . $subcategory_name . "','','0')";
                    $db->sql($sql);
                }
                $inserted++;
            }
        }
        fclose($file);
        echo "<p class='alert alert-success'>Sub categories imported successfully! Rows processed: " . $inserted . "</p>";
    } else {
        echo "<p class='alert alert-danger'>Invalid file format! Please upload data in CSV file!</p>";
    }
}

// 16. update_category_order
if (isset($_POST['update_category_order']) && $_POST['update_category_order'] == 1) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    $id_ary = explode(",", $_POST["row_order"]);
    for ($i = 0; $i < count($id_ary); $i++) {
        $sql = "UPDATE category SET row_order='" . $i . "' WHERE id=" . $id_ary[$i];
        $db->sql($sql);
        $res = $db->getResult();
    }
    echo "<p class='alert alert-success'>Category order updated!</p>";
}

// 17. update_subcategory_order
if (isset($_POST['update_subcategory_order']) && $_POST['update_subcategory_order'] == 1) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    $id_ary = explode(",", $_POST["row_order_2"]);
    for ($i = 0; $i < count($id_ary); $i++) {
        $sql = "UPDATE subcategory SET row_order='" . $i . "' WHERE id=" . $id_ary[$i];
        $db->sql($sql);
        $res = $db->getResult();
    }
    echo "<p class='alert alert-success'>Subcategory order updated!</p>";
}

// 18. update_policy()
if (isset($_POST['update_policy'])) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    $messageEs = $_POST['message_es'] ?? $_POST['message'] ?? '';
    $messageEn = $_POST['message_en'] ?? '';
    upsert_setting_message($db, 'privacy_policy_es', $messageEs);
    upsert_setting_message($db, 'privacy_policy_en', $messageEn);
    upsert_setting_message($db, 'privacy_policy', $messageEs);
    echo "<p class='alert alert-success'>Privacy policy updated Successfully!</p><br>";
}

// 19. update_terms()
if (isset($_POST['update_terms'])) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    $messageEs = $_POST['message_es'] ?? $_POST['message'] ?? '';
    $messageEn = $_POST['message_en'] ?? '';
    upsert_setting_message($db, 'update_terms_es', $messageEs);
    upsert_setting_message($db, 'update_terms_en', $messageEn);
    upsert_setting_message($db, 'update_terms', $messageEs);
    echo "<p class='alert alert-success'>Terms and conditions updated Successfully!</p><br>";
}

// 20. update_user()
if (isset($_POST['user_id']) && isset($_POST['update_user'])) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    $id = $_POST['user_id'];
    $status = $db->escapeString($_POST['status']);
    $sql = "Update users set `status`='" . $status . "' where `id`=" . $id;
    $db->sql($sql);
    $res = $db->getResult();
    echo "<p class='alert alert-success'>User Status updated!</p>";
}

// 21. add_admin_form
if (isset($_POST["add_admin"]) && !empty($_POST["add_admin"]) && $_POST['add_admin'] == 1) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    $username = $db->escapeString($_POST['username']);
    $role = $db->escapeString($_POST['role']);
    $password = $db->escapeString($_POST['password']);
    $password = md5($password);
    $sql = "SELECT auth_username FROM authenticate WHERE auth_username='" . $username . "'";
    $db->sql($sql);
    $res = $db->getResult();
    if ($res) {
        echo "<p class='alert alert-warning'>$username is already exists.</p>";
    } else {
        $data = array('auth_username' => $username, 'auth_pass' => $password, 'role' => $role, 'app_passcode' => '0', 'android_key' => '0', 'status' => '0');
        $db->insert('authenticate', $data);
        $res = $db->getResult();
        if ($res) {
            echo "<p class='alert alert-success'>" . $username . " added as " . $role . "!</p>";
        } else {
            echo "<p class='alert alert-danger'>Admin registration is failed. try again.</p>";
        }
    }
}

// 22. update_admin
if (isset($_POST['update_admin']) && !empty($_POST['update_admin']) && $_POST['update_admin'] == 1 && !empty($_POST['update_admin_id'])) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }

    $update_admin_id = $db->escapeString($_POST['update_admin_id']);
    $update_username = $db->escapeString($_POST['update_username']);
    $update_role = $db->escapeString($_POST['update_role']);
    $sql = "UPDATE authenticate SET auth_username='" . $update_username . "',role='" . $update_role . "' WHERE auth_username='" . $update_admin_id . "'";
    $db->sql($sql);
    $res = $db->getResult();
    if ($res) {
        echo "<p class='alert alert-danger'>$update_username is not updated.</p>";
    } else {
        echo "<p class='alert alert-success'>$update_username is successfully updated.</p>";
    }
}

// 23. delete_admin
if (isset($_POST['delete_admin']) && !empty($_POST['id']) && $_POST['delete_admin'] == 1) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    $id = $db->escapeString($_POST['id']);
    $sql = "DELETE FROM `authenticate` WHERE `auth_username`='" . $id . "'";
    if ($db->sql($sql)) {
        echo 1;
    } else {
        echo 0;
    }
}

// 24. system_configurations
if (isset($_POST['app_link']) && isset($_POST['system_configurations'])) {

    $date = $db->escapeString(date('Y-m-d'));
    if (!empty($_POST['system_configurations_id'])) {
        $_POST['system_timezone_gmt'] = preg_replace('/\s+/', '', $_POST['system_timezone_gmt']);
        $_POST['system_timezone_gmt'] = ($_POST['system_timezone_gmt'] == '00:00') ? "+" . $_POST['system_timezone_gmt'] : $_POST['system_timezone_gmt'];
        $sql = "UPDATE settings SET message='" . json_encode($_POST, JSON_UNESCAPED_UNICODE) . "' WHERE type='system_configurations'";
    } else {
        $sql = "INSERT INTO settings (type,message,status) VALUES ('system_configurations','" . json_encode($_POST, JSON_UNESCAPED_UNICODE) . "','1')";
    }
    $db->sql($sql);
    $res = $db->getResult();
    echo "<p class='alert alert-success'>Settings Saved!</p>";
}

// 25. delete_multiple
if (isset($_GET['delete_multiple']) && $_GET['delete_multiple'] != '') {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    $ids = $db->escapeString($_GET['ids']);
    $table = $db->escapeString($_GET['sec']);
    $is_image = $_GET['is_image'];

    if ($is_image) {
        $path = array(
            'category' => 'images/category/',
            'word_search_category' => 'images/category/',
            'subcategory' => 'images/subcategory/',
            'question' => 'images/questions/',
            'notifications' => 'images/notifications/',
            'contest' => 'images/contest/',
            'contest_questions' => 'images/contest-question/',
            'tbl_maths_question' => 'images/maths-question/',
        );

        $sql = "select `image` from " . $table . " where id in ( " . $ids . " )";
        $db->sql($sql);
        $res = $db->getResult();
        foreach ($res as $image) {
            if (!empty($image['image']) && file_exists($path[$table] . $image['image'])) {
                unlink($path[$table] . $image['image']);
            }
        }
    }

    if ($table === 'word_search_category') {
        $db->sql("DELETE FROM `word_search_level` WHERE `category_id` in ( " . $ids . " )");
        $db->sql("DELETE FROM `word_search_user_progress` WHERE `category_id` in ( " . $ids . " )");
        $db->sql("DELETE FROM `word_search_user_purchased_category` WHERE `category_id` in ( " . $ids . " )");
    }
    if ($table === 'word_search_level') {
        $db->sql("SELECT `category_id`, `level_number` FROM `word_search_level` WHERE `id` in ( " . $ids . " )");
        $levels = $db->getResult();
        foreach ($levels as $level) {
            $category_id = (int) $level['category_id'];
            $level_number = (int) $level['level_number'];
            $db->sql("DELETE FROM `word_search_user_progress` WHERE `category_id`=" . $category_id . " AND `level_number`=" . $level_number);
        }
    }
    if ($table === 'game_2048_challenge_level') {
        $db->sql("SELECT `level_number` FROM `game_2048_challenge_level` WHERE `id` in ( " . $ids . " )");
        $levels = $db->getResult();
        foreach ($levels as $level) {
            $level_number = (int) $level['level_number'];
            $db->sql("DELETE FROM `game_2048_challenge_user_progress` WHERE `level_number`=" . $level_number);
        }
    }

    $sql = "DELETE FROM `" . $table . "` WHERE `id` in ( " . $ids . " ) ";
    if ($db->sql($sql)) {
        echo 1;
    } else {
        echo 0;
    }
}

// 26. add_language
if (isset($_POST['name']) && isset($_POST['add_language'])) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    $name = $db->escapeString($_POST['name']);
    $sql = "SELECT `language` FROM `languages` WHERE `language`='" . $name . "'";
    $db->sql($sql);
    $language = $db->getResult();
    if (empty($language)) {
        $sql = "INSERT INTO `languages` (`language`,`status`) VALUES ('" . $name . "','1')";
        $db->sql($sql);
        echo '<label class="alert alert-success">Language created successfully!</label>';
    } else {
        echo '<label class="alert alert-danger">Language is already created</label>';
    }
}

// 27. update_language
if (isset($_POST['language_id']) && isset($_POST['update_language'])) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    $language_id = $db->escapeString($_POST['language_id']);
    $name = $db->escapeString($_POST['name']);
    $status = $db->escapeString($_POST['status']);
    $sql = "UPDATE `languages` SET `language`='" . $name . "',`status`='" . $status . "' WHERE `id` = " . $language_id;
    if ($db->sql($sql)) {
        echo "<p class='alert alert-success'>Language updated successfully!</p>";
    } else {
        echo "<p class='alert alert-danger'>Language not updated!</p>";
    }
}

// 28. delete_language
if (isset($_GET['delete_language']) && $_GET['delete_language'] == '1') {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    $id = $db->escapeString($_GET['id']);
    $sql = 'DELETE FROM `languages` WHERE `id`=' . $id;
    if ($db->sql($sql)) {
        echo 1;
    } else {
        echo 0;
    }
}

// 30. update_about_us()
if (isset($_POST['update_about_us'])) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }

    $messageEs = $_POST['message_es'] ?? $_POST['message'] ?? '';
    $messageEn = $_POST['message_en'] ?? '';
    upsert_setting_message($db, 'about_us_es', $messageEs);
    upsert_setting_message($db, 'about_us_en', $messageEn);
    upsert_setting_message($db, 'about_us', $messageEs);
    echo "<p class='alert alert-success'>About us updated successfully!</p><br>";
}

// 31. update_instructions()
if (isset($_POST['update_instructions'])) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }

    $messageEs = $_POST['message_es'] ?? $_POST['message'] ?? '';
    $messageEn = $_POST['message_en'] ?? '';
    upsert_setting_message($db, 'instructions_es', $messageEs);
    upsert_setting_message($db, 'instructions_en', $messageEn);
    upsert_setting_message($db, 'instructions', $messageEs);

    echo "<p class='alert alert-success'>Instructions updated successfully!</p><br>";
}

// 31b. update_delete_account()
if (isset($_POST['update_delete_account'])) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }

    $messageEs = $_POST['message_es'] ?? $_POST['message'] ?? '';
    $messageEn = $_POST['message_en'] ?? '';
    upsert_setting_message($db, 'delete_account_es', $messageEs);
    upsert_setting_message($db, 'delete_account_en', $messageEn);
    upsert_setting_message($db, 'delete_account', $messageEn !== '' ? $messageEn : $messageEs);

    echo "<p class='alert alert-success'>Delete account content updated successfully!</p><br>";
}

// 32. update_daily_quiz_order
if (isset($_POST['question_ids']) && isset($_POST['update_daily_quiz_order']) && isset($_POST['language_id'])) {
    $language_id = $db->escapeString($_POST['language_id']);
    $question_ids = $db->escapeString($_POST['question_ids']);
    $date_published = $db->escapeString($_POST['daily_quiz_date']);

    $sql = "SELECT * FROM daily_quiz WHERE date_published = '$date_published' AND language_id='$language_id'";
    $db->sql($sql);
    $res = $db->getResult();

    if (!empty($res)) {
        $sql1 = "UPDATE daily_quiz SET `questions_id`='$question_ids',`language_id`='$language_id' WHERE `id`=" . $res[0]['id'];
    } else {
        $sql1 = "INSERT INTO `daily_quiz` (`language_id`,`questions_id`,`date_published`) VALUES ('$language_id','$question_ids',STR_TO_DATE('$date_published', '%Y-%m-%d'))";
    }
    $db->sql($sql1);
    echo "<p class='alert alert-success'> Saved </p>";
}

// 34. add_contest()
if (isset($_POST['name']) && isset($_POST['add_contest'])) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    $name = $db->escapeString($_POST['name']);
    $start_date = $db->escapeString($_POST['start_date']);
    $end_date = $db->escapeString($_POST['end_date']);
    $description = $db->escapeString($_POST['description']);
    $entry = $db->escapeString($_POST['entry']);
    $status = 0;

    $file = explode(".", strtolower($_FILES["image"]["name"]));
    $extension = end($file);
    if (!(in_array($extension, $allowedExts))) {
        echo "<p class='alert alert-danger'>Image type is invalid!</p>";
        return false;
    }
    $target_path = 'images/contest/';
    if (!is_dir($target_path)) {
        mkdir($target_path, 0777, true);
    }

    $filename = microtime(true) . '.' . strtolower($extension);
    $full_path = $target_path . "" . $filename;
    if (!move_uploaded_file($_FILES["image"]["tmp_name"], $full_path)) {
        echo "<p class='alert alert-danger'>Image type is invalid!</p>";
        return false;
    }

    $sql = "INSERT INTO `contest` (`name`, `start_date`, `end_date`, `description`, `image`, `entry`,`prize_status`, `date_created`,`status`) VALUES
	('" . $name . "','" . $start_date . "','" . $end_date . "','" . $description . "','" . $filename . "','" . $entry . "','0','" . $toDateTime . "','" . $status . "')";

    $db->sql($sql);
    $insert_id = $db->insert_id();
    $points = implode(',', array_filter($_POST['points']));
    $points1 = explode(',', $points);
    $winner = $_POST['winner'];
    $count = count($points1);
    for ($i = 0; $i < $count; $i++) {
        $sql1 = "INSERT INTO `contest_prize` (`contest_id`, `top_winner`, `points`) VALUES
	('" . $insert_id . "','" . $winner[$i] . "','" . $points1[$i] . "')";

        $db->sql($sql1);
    }
    echo '<label class="alert alert-success">Contest created successfully!</label>';
}

// 35. delete_contest()
if (isset($_GET['delete_contest']) && $_GET['delete_contest'] != '') {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    $id = $_GET['id'];
    $image = $_GET['image'];

    $sql = 'DELETE FROM `contest` WHERE `id`=' . $id;
    if ($db->sql($sql)) {
        /* delete questions */
        $sql = 'SELECT FROM `contest_questions` WHERE `contest_id`=' . $id;
        $db->sql($sql);
        $questions_images = $db->getResult();

        if (!empty($questions_images)) {
            foreach ($questions_images as $img) {
                if (!empty($img['image']) && file_exists('images/contest-question/' . $img['image'])) {
                    unlink('images/contest-question/' . $img['image']);
                }
            }
        }

        /* delete leaderboard */
        $sql = 'DELETE FROM `contest_leaderboard` WHERE `contest_id`=' . $id;
        $db->sql($sql);
        if (!empty($image) && file_exists($image)) {
            unlink($image);
        }
        echo 1;
    } else {
        echo 0;
    }
}

// 36. update_contest()
if (isset($_POST['contest_id']) && isset($_POST['update_contest'])) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    $id = $_POST['contest_id'];
    $name = $db->escapeString($_POST['name']);
    $description = $db->escapeString($_POST['description']);
    $start_date = $db->escapeString($_POST['start_date']);
    $end_date = $db->escapeString($_POST['end_date']);
    $entry = $db->escapeString($_POST['entry']);

    if ($_FILES['image']['size'] != 0 && $_FILES['image']['error'] == 0) {
        //image isn't empty and update the image
        $image_url = $db->escapeString($_POST['image_url']);

        $file = explode(".", strtolower($_FILES["image"]["name"]));
        $extension = end($file);
        if (!(in_array($extension, $allowedExts))) {
            echo '<p class="alert alert-danger">Image type is invalid</p>';
            return false;
        }
        $target_path = 'images/contest/';
        if (!is_dir($target_path)) {
            mkdir($target_path, 0777, true);
        }
        $filename = microtime(true) . '.' . strtolower($extension);
        $full_path = $target_path . "" . $filename;
        if (!move_uploaded_file($_FILES["image"]["tmp_name"], $full_path)) {
            echo '<p class="alert alert-danger">Image type is invalid</p>';
            return false;
        }
        if (!empty($image_url) && file_exists($image_url)) {
            unlink($image_url);
        }

        $sql = "Update `contest` set `image`='" . $filename . "' where `id`=" . $id;
        $db->sql($sql);
    }

    $sql = "Update contest set `name`='" . $name . "', `description`='" . $description . "', `start_date`='" . $start_date . "', `end_date`='" . $end_date . "', `entry`='" . $entry . "' where `id`=" . $id;

    $db->sql($sql);
    echo "<p class='alert alert-success'>Contest updated successfully!</p>";
}

// 37. update_contest_status()
if (isset($_POST['update_id']) && isset($_POST['update_contest_status'])) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    $id = $_POST['update_id'];
    $status = $db->escapeString($_POST['status']);

    $sql = 'SELECT *  FROM `contest_questions` WHERE `contest_id`=' . $id;
    $db->sql($sql);
    $res = $db->getResult();

    if (!empty($res)) {
        $sql = "UPDATE `contest` SET `status`='" . $status . "' WHERE `id`=" . $id;
        $db->sql($sql);
        echo "<p class='alert alert-success'>Status updated successfully!</p>";
    } else {
        echo "<p class='alert alert-danger'>No enought question for active</p>";
    }
}

// 38. add_contest_prize()
if (isset($_POST['contest_id']) && isset($_POST['add_contest_prize'])) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }

    $contest_id = $db->escapeString($_POST['contest_id']);
    $points = $db->escapeString($_POST['points']);
    $winner = $db->escapeString($_POST['winner']);

    $sql = "INSERT INTO `contest_prize` (`contest_id`, `top_winner`, `points`) VALUES ('" . $contest_id . "','" . $winner . "','" . $points . "')";
    $db->sql($sql);

    echo '<label class="alert alert-success">Prize created successfully!</label>';
}

// 39. update_contest_prize()
if (isset($_POST['prize_id']) && isset($_POST['update_contest_prize'])) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    $id = $_POST['prize_id'];
    $points = $db->escapeString($_POST['points']);
    $winner = $db->escapeString($_POST['winner']);

    $sql = "Update `contest_prize` set `points`='" . $points . "' where `id`=" . $id;
    $db->sql($sql);

    echo "<p class='alert alert-success'>Prize updated successfully!</p>";
}

// 40. delete_contest_prize()
if (isset($_GET['delete_contest_prize']) && $_GET['delete_contest_prize'] != '') {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    $id = $_GET['id'];

    $sql = 'DELETE FROM `contest_prize` WHERE `id`=' . $id;
    if ($db->sql($sql)) {
        echo 1;
    } else {
        echo 0;
    }
}

// 41. add_contest_question()
if (isset($_POST['question']) && isset($_POST['add_contest_question'])) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    $question = $db->escapeString($_POST['question']);
    $contest_id = $db->escapeString($_POST['contest_id']);
    $question_type = $db->escapeString($_POST['question_type']);

    $a = $db->escapeString($_POST['a']);
    $b = $db->escapeString($_POST['b']);
    $c = ($question_type == 1) ? $db->escapeString($_POST['c']) : "";
    $d = ($question_type == 1) ? $db->escapeString($_POST['d']) : "";
    $e = ($fn->is_option_e_mode_enabled()) ? (($question_type == 1) ? $db->escapeString($_POST['e']) : "") : "";
    $answer = $db->escapeString($_POST['answer']);
    $note = $db->escapeString($_POST['note']);

    $filename = '';
    if ($_FILES['image']['error'] == 0 && $_FILES['image']['size'] > 0) {
        $target_path = 'images/contest-question/';
        if (!is_dir($target_path)) {
            mkdir($target_path, 0777, true);
        }

        $extension = pathinfo($_FILES["image"]["name"])['extension'];
        if (!(in_array($extension, $allowedExts))) {
            $response['error'] = true;
            $response['message'] = 'Image type is invalid';
            echo json_encode($response);
            return false;
        }
        $filename = microtime(true) . '.' . strtolower($extension);
        $full_path = $target_path . "" . $filename;
        if (!move_uploaded_file($_FILES["image"]["tmp_name"], $full_path)) {
            $response['error'] = true;
            $response['message'] = 'Image type is invalid';
            echo json_encode($response);
            return false;
        }
    }
    $sql = "INSERT INTO `contest_questions`(`contest_id`,`image` , `question`, `question_type`, `optiona`, `optionb`, `optionc`, `optiond`, `optione`, `answer`,`note`) VALUES ('" . $contest_id . "','" . $filename . "','" . $question . "','" . $question_type . "','" . $a . "','" . $b . "','" . $c . "','" . $d . "','" . $e . "','" . $answer . "','" . $note . "')";

    $db->sql($sql);
    $res = $db->getResult();
    echo '<label class="alert alert-success">Question created successfully!</label>';
}

// 42. update_contest_question()
if (isset($_POST['question_id']) && isset($_POST['update_contest_question'])) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    $id = $_POST['question_id'];
    $question = $db->escapeString($_POST['question']);
    $quiz_id = $db->escapeString($_POST['contest_id']);
    $question_type = $db->escapeString($_POST['edit_question_type']);

    $a = $db->escapeString($_POST['a']);
    $b = $db->escapeString($_POST['b']);
    $c = ($question_type == 1) ? $db->escapeString($_POST['c']) : "";
    $d = ($question_type == 1) ? $db->escapeString($_POST['d']) : "";
    if ($fn->is_option_e_mode_enabled()) {
        $e = ($question_type == 1) ? $db->escapeString($_POST['e']) : "";
    }
    $answer = $db->escapeString($_POST['answer']);
    $update_note = $db->escapeString($_POST['edit_note']);

    if ($_FILES['image']['size'] != 0 && $_FILES['image']['error'] == 0) {
        $target_path = 'images/contest-question/';
        if (!is_dir($target_path)) {
            mkdir($target_path, 0777);
        }
        //image isn't empty and update the image
        $image_url = $db->escapeString($_POST['image_url']);

        $extension = pathinfo($_FILES["image"]["name"])['extension'];
        if (!(in_array($extension, $allowedExts))) {
            echo '<p class="alert alert-danger">Image type is invalid</p>';
            return false;
        }
        $filename = microtime(true) . '.' . strtolower($extension);
        $full_path = $target_path . "" . $filename;
        if (!move_uploaded_file($_FILES["image"]["tmp_name"], $full_path)) {
            echo '<p class="alert alert-danger">Image type is invalid</p>';
            return false;
        }
        if (!empty($image_url) && file_exists($image_url)) {
            unlink($image_url);
        }

        $sql = "Update category set `image`='" . $filename . "' where `id`=" . $id;
        $db->sql($sql);
    }
    $sql = "Update `contest_questions` set `question`='" . $question . "', `contest_id`='" . $quiz_id . "',`question_type`='" . $question_type . "',`optiona`='" . $a . "',`optionb`='" . $b . "' ,`optionc`='" . $c . "' ,`optiond`='" . $d . "',`answer`='" . $answer . "',`note`='" . $update_note . "'";
    $sql .= ($fn->is_option_e_mode_enabled()) ? ",`optione`='" . $e . "'" : "";
    $sql .= " where `id`=" . $id;
    $db->sql($sql);
    echo "<p class='alert alert-success'>Question updated successfully!</p>";
}

// 43. delete_contest_question()
if (isset($_GET['delete_contest_question']) && $_GET['delete_contest_question'] != '') {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    $id = $_GET['id'];
    $image = $_GET['image'];

    $sql = 'DELETE FROM `contest_questions` WHERE `id`=' . $id;
    if ($db->sql($sql)) {
        if (!empty($image) && file_exists($image)) {
            unlink($image);
        }
        echo 1;
    } else {
        echo 0;
    }
}

// 44. import_contest_questions() - import questions to database from a CSV file
if (isset($_POST['import_contest_questions']) && $_POST['import_contest_questions'] == 1) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    $count = $count1 = 0;
    $filename = $_FILES["questions_file"]["tmp_name"];
    $file_extension = pathinfo($_FILES["questions_file"]["name"], PATHINFO_EXTENSION);
    if ($_FILES["questions_file"]["size"] > 0 && $file_extension == "csv") {
        $file = fopen($filename, "r");

        while (($emapData = fgetcsv($file, 10000, ",")) !== FALSE) {
            if (count($emapData) > 2) {
                $emapData[0] = $db->escapeString($emapData[0]); //contest_id
                $emapData[1] = $db->escapeString(trim($emapData[1]));   //question_type
                $emapData[2] = $db->escapeString($emapData[2]);     //question
                $emapData[3] = $db->escapeString($emapData[3]);    // optiona
                $emapData[4] = $db->escapeString($emapData[4]);    // optionb
                $emapData[5] = $db->escapeString($emapData[5]);    // optionc
                $emapData[6] = $db->escapeString($emapData[6]);    // optiond
                $emapData[7] = (empty($db->escapeString($emapData[7]))) ? "" : $db->escapeString($emapData[7]);  // optione
                $emapData[8] = $db->escapeString(trim($emapData[8]));  //answer
                $emapData[9] = $db->escapeString($emapData[9]);       //note
                $count++;
                if ($count > 1) {
                    if ($emapData[1] == '1') {
                        if (!empty($emapData[0]) && !empty($emapData[1]) && !empty($emapData[2]) && $emapData[3] != '' && $emapData[4] != '' && $emapData[5] != '' && $emapData[6] != '' && $emapData[8] != '') {
                            $empty_value_found = true;
                        } else {
                            $empty_value_found = false;
                            echo '<p class="text-danger">Please Check ' . $count . ' row</p>';
                            break;
                        }
                    } else if ($emapData[1] == '2') {
                        if (!empty($emapData[0]) && !empty($emapData[1]) && !empty($emapData[2]) && $emapData[3] != '' && $emapData[4] != '' && $emapData[8] != '') {
                            $empty_value_found = true;
                        } else {
                            $empty_value_found = false;
                            echo '<p class="text-danger">Please Check ' . $count . ' row</p>';
                            break;
                        }
                    } else {
                        $empty_value_found = false;
                        break;
                    }
                }
            }
        }
        fclose($file);
        if ($empty_value_found == TRUE) {
            $file = fopen($filename, "r");
            while (($emapData1 = fgetcsv($file, 10000, ",")) !== FALSE) {
                if (count($emapData1) > 2) {
                    $emapData1[0] = $db->escapeString($emapData1[0]); //contest_id
                    $emapData1[1] = $db->escapeString(trim($emapData1[1]));   //question_type
                    $emapData1[2] = $db->escapeString($emapData1[2]);     //question
                    $emapData1[3] = $db->escapeString($emapData1[3]);    // optiona
                    $emapData1[4] = $db->escapeString($emapData1[4]);    // optionb
                    $emapData1[5] = $db->escapeString($emapData1[5]);    // optionc
                    $emapData1[6] = $db->escapeString($emapData1[6]);    // optiond
                    $emapData1[7] = (empty($db->escapeString($emapData1[7]))) ? "" : $db->escapeString($emapData1[7]);  // optione
                    $emapData1[8] = $db->escapeString(trim($emapData1[8]));  //answer
                    $emapData1[9] = $db->escapeString($emapData1[9]);       //note
                    $count1++;
                    if ($count1 > 1) {
                        if (count($emapData1) > 2) {
                            $sql = "INSERT INTO `contest_questions`(`contest_id`, `image`, `question_type`, `question`,`optiona`, `optionb`, `optionc`, `optiond`,  `optione`, `answer`, `note`) VALUES 
						('$emapData1[0]','','$emapData1[1]','$emapData1[2]','$emapData1[3]','$emapData1[4]','$emapData1[5]','$emapData1[6]','$emapData1[7]','$emapData1[8]','$emapData1[9]')";
                            $db->sql($sql);
                        }
                    }
                }
            }
            fclose($file);
            echo "<p class='alert alert-success'>CSV file is successfully imported!</p>";
        } else {
            echo "<p class='alert alert-danger'>Please fill all the data in CSV file!</p>";
        }
    } else {
        echo "<p class='alert alert-danger'>Invalid file format! Please upload data in CSV file!</p>";
    }
}

// 45. battle_settings()
if (isset($_POST['web_firebase_settings']) && isset($_POST['databaseURL'])) {

    $setting = [
        'apiKey',
        'authDomain',
        'databaseURL',
        'projectId',
        'storageBucket',
        'messagingSenderId',
        'appId',
        'client_id_google',
        'app_id_fb'
    ];
    foreach ($setting as $row) {
        $sql = "SELECT * FROM settings WHERE type='" . $row . "' LIMIT 1";
        $db->sql($sql);
        $res = $db->getResult();
        if (!empty($res)) {
            $sql1 = "UPDATE settings SET message='" . $_POST[$row] . "' WHERE type='" . $row . "' ";
        } else {
            $sql1 = "INSERT INTO settings (type,message,status) VALUES ('" . $row . "','" . $_POST[$row] . "','1')";
        }
        $db->sql($sql1);
    }

    echo "<p class='alert alert-success'>Settings Saved!</p>";
}

// 46. add_learning
if (isset($_POST['title']) && isset($_POST['add_learning'])) {
    $language_id = ($fn->is_language_mode_enabled()) ? $db->escapeString($_POST['language_id']) : 0;
    $category = $db->escapeString($_POST['category']);
    $title = $db->escapeString($_POST['title']);
    $video_id = ($db->escapeString($_POST['video_id'])) ? $db->escapeString($_POST['video_id']) : '';
    $detail = $db->escapeString($_POST['detail']);
    $filename = "";
    if ($_FILES['pdf_file']['error'] == 0 && $_FILES['pdf_file']['size'] > 0) {
        // Define allowed file types
        $allowedTypes = ['pdf'];

        // Get the file extension
        $extension = pathinfo($_FILES["pdf_file"]["name"], PATHINFO_EXTENSION);

        // Check if the file extension is not in the allowed types
        if (!in_array(strtolower($extension), $allowedTypes)) {

            echo "<label class='alert alert-danger'>Invalid file type. Only PDF files are allowed.</label>";
            return false;
        }

        // Rest of your code for file handling
        if (!is_dir('pdf_files')) {
            mkdir('pdf_files', 0777, true);
        }
        $target_path = 'pdf_files/';
        $filename = microtime(true) . '.' . strtolower($extension);
        $full_path = $target_path . $filename;
        if (!move_uploaded_file($_FILES["pdf_file"]["tmp_name"], $full_path)) {
            $response['error'] = true;
            $response['message'] = 'Failed to upload the file.';
            echo json_encode($response);
            return false;
        }


    }

    $sql = "INSERT INTO `tbl_learning` ( `category`, `language_id`, `title`, `video_id`, `detail`,pdf_file,`status`) VALUES ('" . $category . "','" . $language_id . "','" . $title . "','" . $video_id . "','" . $detail . "','" . $filename . "','0')";
    $db->sql($sql);
    $res = $db->getResult();
    echo '<label class="alert alert-success">Learning created successfully!</label>';
}

// 47. update_question
if (isset($_POST['learning_id']) && isset($_POST['update_learning'])) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    $id = $_POST['learning_id'];

    $language_id = ($fn->is_language_mode_enabled()) ? $db->escapeString($_POST['language_id']) : 0;
    $category = $db->escapeString($_POST['category']);
    $title = $db->escapeString($_POST['title']);
    $video_id = ($db->escapeString($_POST['video_id'])) ? $db->escapeString($_POST['video_id']) : '';
    $detail = $db->escapeString($_POST['detail']);

    $filename = "";





    if ($_FILES['edit_pdf_file']['error'] == 0 && $_FILES['edit_pdf_file']['size'] > 0) {

        if (!is_dir('pdf_files')) {
            mkdir('pdf_files', 0777, true);
        }
        $extension = pathinfo($_FILES["edit_pdf_file"]["name"])['extension'];




        $allowedTypes = ['pdf'];

        // Get the file extension
        $extension = pathinfo($_FILES["edit_pdf_file"]["name"], PATHINFO_EXTENSION);

        // Check if the file extension is not in the allowed types
        if (!in_array(strtolower($extension), $allowedTypes)) {

            echo "<label class='alert alert-danger'>Invalid file type. Only PDF files are allowed.</label>";
            return false;
        }

        if (!(in_array($extension, $allowedType))) {
            $response['error'] = true;
            $response['message'] = 'type is invalid';
            echo json_encode($response);
            return false;
        }
        $target_path = 'pdf_files/';
        $filename = microtime(true) . '.' . strtolower($extension);
        $full_path = $target_path . "" . $filename;
        if (!move_uploaded_file($_FILES["edit_pdf_file"]["tmp_name"], $full_path)) {
            $response['error'] = true;
            $response['message'] = 'Image type is invalid';
            echo json_encode($response);
            return false;
        }
        $image_url = $db->escapeString($_POST['edit_pdf']);


        if (file_exists($image_url)) {

            unlink($image_url);
        }
    }
    $sql = "Update `tbl_learning` set `category`='" . $category . "', `title`='" . $title . "', `video_id`='" . $video_id . "', `detail`='" . $detail . "',`pdf_file`='" . $filename . "'";
    $sql .= ($fn->is_language_mode_enabled()) ? ", `language_id`=" . $language_id : "";
    $sql .= " where `id`=" . $id;
    $db->sql($sql);

    echo "<p class='alert alert-success'>Learning updated successfully!</p>";
}

// 48. update_learning_status
if (isset($_POST['learning_status_id']) && isset($_POST['update_learning_status'])) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    $id = $_POST['learning_status_id'];
    $status = $db->escapeString($_POST['status']);
    if ($status == 1 || $status == '1') {
        $sql = 'SELECT id FROM `tbl_learning_question` WHERE `learning_id`=' . $id;
        $db->sql($sql);
        $res = $db->getResult();
        if (empty($res)) {
            echo "<p class='alert alert-danger'>No enought question for active Learning!</p>";
        } else {
            $sql = "Update `tbl_learning` set `status`='" . $status . "' where `id`=" . $id;
            $db->sql($sql);
            echo "<p class='alert alert-success'>Learning status updated successfully!</p>";
        }
    } else {
        $sql = "Update `tbl_learning` set `status`='" . $status . "' where `id`=" . $id;
        $db->sql($sql);
        echo "<p class='alert alert-success'>Learning status updated successfully!</p>";
    }
}

// 49. delete_question
if (isset($_GET['delete_learning']) && $_GET['delete_learning'] != '') {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    $id = $_GET['id'];
    $pdf = $_GET['pdf'];


    $sql = 'DELETE FROM `tbl_learning` WHERE `id`=' . $id;
    if ($db->sql($sql)) {
        $sql = 'DELETE FROM `tbl_learning_question` WHERE `learning_id`=' . $id;
        $db->sql($sql);
        if (file_exists($pdf)) {

            unlink($pdf);
        }

        echo 1;
    } else {
        echo 0;
    }
}

// 50. add_learning_question
if (isset($_POST['question']) && isset($_POST['add_learning_question'])) {
    $question = $db->escapeString($_POST['question']);
    $learning_id = $db->escapeString($_POST['learning_id']);

    $question_type = $db->escapeString($_POST['question_type']);

    $a = $db->escapeString($_POST['a']);
    $b = $db->escapeString($_POST['b']);
    $c = ($question_type == 1) ? $db->escapeString($_POST['c']) : "";
    $d = ($question_type == 1) ? $db->escapeString($_POST['d']) : "";
    $e = ($fn->is_option_e_mode_enabled()) ? (($question_type == 1) ? $db->escapeString($_POST['e']) : "") : "";
    $answer = $db->escapeString($_POST['answer']);

    $sql = "INSERT INTO `tbl_learning_question`(`learning_id`, `question`, `question_type`, `optiona`, `optionb`, `optionc`, `optiond`, `optione`, `answer`) VALUES 
	('" . $learning_id . "','" . $question . "','" . $question_type . "','" . $a . "','" . $b . "','" . $c . "','" . $d . "','" . $e . "','" . $answer . "')";

    $db->sql($sql);
    $res = $db->getResult();
    echo '<label class="alert alert-success">Question created successfully!</label>';
}

// 51. update_learning_question
if (isset($_POST['question_id']) && isset($_POST['update_learning_question'])) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    $id = $_POST['question_id'];
    $question = $db->escapeString($_POST['question']);
    $question_type = $db->escapeString($_POST['edit_question_type']);

    $a = $db->escapeString($_POST['a']);
    $b = $db->escapeString($_POST['b']);
    $c = ($question_type == 1) ? $db->escapeString($_POST['c']) : "";
    $d = ($question_type == 1) ? $db->escapeString($_POST['d']) : "";
    if ($fn->is_option_e_mode_enabled()) {
        $e = ($question_type == 1) ? $db->escapeString($_POST['e']) : "";
    }
    $answer = $db->escapeString($_POST['answer']);
    $sql = "UPDATE `tbl_learning_question` set `question`='" . $question . "',`question_type`='" . $question_type . "',`optiona`='" . $a . "',`optionb`='" . $b . "' ,`optionc`='" . $c . "' ,`optiond`='" . $d . "', `answer`='" . $answer . "'";
    $sql .= ($fn->is_option_e_mode_enabled()) ? ",`optione`='" . $e . "'" : "";
    $sql .= " WHERE `id`=" . $id;
    $db->sql($sql);

    echo "<p class='alert alert-success'>Question updated successfully!</p>";
}

// 52. delete_learning_question
if (isset($_GET['delete_learning_question']) && $_GET['delete_learning_question'] != '') {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    $id = $_GET['id'];

    $sql = 'DELETE FROM `tbl_learning_question` WHERE `id`=' . $id;
    if ($db->sql($sql)) {
        echo 1;
    } else {
        echo 0;
    }
}

// 53. add_maths_question()
if (isset($_POST['question']) && isset($_POST['add_maths_question'])) {
    $question = $db->escapeString($_POST['question']);

    $language_id = ($fn->is_language_mode_enabled()) ? $db->escapeString($_POST['language_id']) : 0;
    $category = $db->escapeString($_POST['category']);
    $subcategory = (empty($_POST['subcategory'])) ? 0 : $db->escapeString($_POST['subcategory']);

    $question_type = $db->escapeString($_POST['question_type']);

    $a = $db->escapeString($_POST['a']);
    $b = $db->escapeString($_POST['b']);
    $c = ($question_type == 1) ? $db->escapeString($_POST['c']) : "";
    $d = ($question_type == 1) ? $db->escapeString($_POST['d']) : "";
    $e = ($fn->is_option_e_mode_enabled()) ? (($question_type == 1) ? $db->escapeString($_POST['e']) : "") : "";
    $answer = $db->escapeString($_POST['answer']);
    $note = $db->escapeString($_POST['note']);

    $filename = $full_path = '';

    if (isset($_POST['question_id'])) {
        $id = $_POST['question_id'];

        if ($_FILES['image']['size'] != 0 && $_FILES['image']['error'] == 0) {
            $target_path = 'images/maths-question/';
            if (!is_dir($target_path)) {
                mkdir($target_path, 0777, true);
            }

            //image isn't empty and update the image
            $image_url = $db->escapeString($_POST['image_url']);

            // common image file extensions
            $extension = pathinfo($_FILES["image"]["name"])['extension'];
            if (!(in_array($extension, $allowedExts))) {
                echo '<p class="alert alert-danger">Image type is invalid</p>';
                return false;
            }
            $filename = microtime(true) . '.' . strtolower($extension);
            $full_path = $target_path . "" . $filename;
            if (!move_uploaded_file($_FILES["image"]["tmp_name"], $full_path)) {
                echo '<p class="alert alert-danger">Image type is invalid</p>';
                return false;
            }
            if (!empty($image_url) && file_exists($image_url)) {
                unlink($image_url);
            }
            $sql = "UPDATE `tbl_maths_question` SET `image`='" . $filename . "' WHERE `id`=" . $id;
            $db->sql($sql);
        }
        $sql = "UPDATE `tbl_maths_question` SET `question`='" . $question . "', `category`='" . $category . "', `subcategory`='" . $subcategory . "',`question_type`='" . $question_type . "',`optiona`='" . $a . "',`optionb`='" . $b . "' ,`optionc`='" . $c . "' ,`optiond`='" . $d . "', `answer`='" . $answer . "', `note`='" . $note . "'";
        $sql .= ($fn->is_option_e_mode_enabled()) ? ",`optione`='" . $e . "'" : "";
        $sql .= ($fn->is_language_mode_enabled()) ? ", `language_id`=" . $language_id : "";
        $sql .= " WHERE `id`=" . $id;
        $db->sql($sql);
        header("location:maths-questions-view.php");
    } else {
        // common image file extensions
        if ($_FILES['image']['error'] == 0 && $_FILES['image']['size'] > 0) {
            $target_path = 'images/maths-question/';
            if (!is_dir($target_path)) {
                mkdir($target_path, 0777, true);
            }

            $extension = pathinfo($_FILES["image"]["name"])['extension'];
            if (!(in_array($extension, $allowedExts))) {
                $response['error'] = true;
                $response['message'] = 'Image type is invalid';
                echo json_encode($response);
                return false;
            }

            $filename = microtime(true) . '.' . strtolower($extension);
            $full_path = $target_path . "" . $filename;
            if (!move_uploaded_file($_FILES["image"]["tmp_name"], $full_path)) {
                $response['error'] = true;
                $response['message'] = 'Image type is invalid';
                echo json_encode($response);
                return false;
            }
        }

        $sql = "INSERT INTO `tbl_maths_question` (`category`, `subcategory`, `language_id`, `image`, `question`, `question_type`, `optiona`, `optionb`, `optionc`, `optiond`, `optione`, `answer`, `note`) VALUES 
        ('" . $category . "','" . $subcategory . "','" . $language_id . "','" . $filename . "','" . $question . "','" . $question_type . "','" . $a . "','" . $b . "','" . $c . "','" . $d . "','" . $e . "','" . $answer . "','" . $note . "')";

        $db->sql($sql);
        $res = $db->getResult();
        header("location:maths-questions.php");
    }
    // echo $sql;
    // echo '<label class="alert alert-success">Question created successfully!</label>';
}

// 54. delete_maths_question
if (isset($_GET['delete_maths_question']) && $_GET['delete_maths_question'] != '') {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    $id = $_GET['id'];
    $image = $_GET['image'];

    $sql = 'DELETE FROM `tbl_maths_question` WHERE `id`=' . $id;
    if ($db->sql($sql)) {
        if (!empty($image) && file_exists($image)) {
            unlink($image);
        }
        echo 1;
    } else {
        echo 0;
    }
}

// 53. update_system()
if (isset($_POST['update_system'])) {

    if (isset($_POST['purchase_code']) && isset($_POST['quiz_url'])) {
        if (!empty($_POST['purchase_code']) && !empty($_POST['quiz_url'])) {
            $purchase_code = $db->escapeString($_POST['purchase_code']);
            $quiz_url = $_SERVER['HTTP_HOST'] . str_replace(basename($_SERVER['SCRIPT_NAME']), "", $_SERVER['SCRIPT_NAME']);
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://validator.wrteam.in/quiz_online_validator?purchase_code=' . $purchase_code . '&domain_url=' . $quiz_url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
            ));
            $response = curl_exec($curl);
            $response = json_decode($response, 1);
            curl_close($curl);
            if ($response["error"] == false) {
                if ($_FILES['file']['error'] == 0 && $_FILES['file']['size'] > 0) {
                    $target_path = getcwd() . DIRECTORY_SEPARATOR;
                    if (!is_dir('tmp')) {
                        mkdir('tmp', 0777, true);
                    }
                    $allowedExts = array("zip", "ZIP", "rar", "RAR", "7zip", "7ZIP");
                    $extension = pathinfo($_FILES["file"]["name"])['extension'];
                    if ((in_array($extension, $allowedExts))) {
                        $target_path1 = $target_path . '/tmp';
                        $filePath = $target_path . '/' . $_FILES["file"]["name"];
                        $filePath1 = $target_path1 . $_FILES["file"]["name"];
                        if (move_uploaded_file($_FILES["file"]["tmp_name"], $filePath1)) {
                            $zip = new ZipArchive();
                            $zipFile = $zip->open($filePath1);
                            if ($zipFile === true) {
                                $zip->extractTo($target_path1);
                                $zip->close();

                                unlink($filePath1);

                                $ver_file1 = $target_path1 . '/version_info.php';
                                $source_path1 = $target_path1 . '/source_code.zip';
                                $sql_file1 = $target_path1 . '/database.sql';
                                if (file_exists($ver_file1) && file_exists($source_path1) && file_exists($sql_file1)) {
                                    $ver_file = $target_path . '/version_info.php';
                                    $source_path = $target_path . '/source_code.zip';
                                    $sql_file = $target_path . '/database.sql';
                                    if (rename($ver_file1, $ver_file) && rename($source_path1, $source_path) && rename($sql_file1, $sql_file)) {
                                        DeleteDir($target_path1);

                                        $version_file = require_once($ver_file);
                                        $db->sql("select * from `settings` where type='quiz_version'");
                                        $res = $db->getResult();
                                        $current_version = (!empty($res)) ? $res[0]['message'] : '';

                                        if ($current_version == $version_file['current_version']) {
                                            $zip1 = new ZipArchive();
                                            $zipFile1 = $zip1->open($source_path);
                                            if ($zipFile1 === true) {
                                                $zip1->extractTo($target_path);
                                                $zip1->close();
                                                if (file_exists($sql_file)) {
                                                    $lines = file($sql_file);
                                                    for ($i = 0; $i < count($lines); $i++) {
                                                        if (!empty($lines[$i])) {
                                                            $db->sql($lines[$i]);
                                                        }
                                                    }
                                                }
                                                unlink($source_path);
                                                unlink($ver_file);
                                                unlink($sql_file);
                                                $db->sql("UPDATE settings SET message='" . $version_file['update_version'] . "' WHERE type='quiz_version'");
                                                $result = '<label class="alert alert-success">System update successfully.!</label>';
                                            } else {
                                                unlink($source_path);
                                                unlink($ver_file);
                                                unlink($sql_file);
                                                DeleteDir($target_path1);
                                                $result = "<label class='alert alert-danger'>Something wrong, please try again.!<lable>";
                                            }
                                        } else if ($current_version == $version_file['update_version']) {
                                            unlink($source_path);
                                            unlink($ver_file);
                                            unlink($sql_file);
                                            DeleteDir($target_path1);
                                            $result = "<label class='alert alert-danger'>System is already updated.!<lable>";
                                        } else {
                                            unlink($source_path);
                                            unlink($ver_file);
                                            unlink($sql_file);
                                            DeleteDir($target_path1);
                                            $result = "<label class='alert alert-danger'>Your version is $current_version, Please update nearest version first.<lable>";
                                        } //                                
                                    } else {
                                        DeleteDir($target_path1);
                                        $result = "<label class='alert alert-danger'>Invalid file, please try again.!<lable>";
                                    }
                                } else {
                                    DeleteDir($target_path1);
                                    $result = "<label class='alert alert-danger'>Invalid file, please try again.!<lable>";
                                }
                            } else {
                                DeleteDir($target_path1);
                                $result = "<label class='alert alert-danger'>Something wrong, please try again.!<lable>";
                            }
                        } else {
                            $result = "<label class='alert alert-danger'>file type is invalid, Only zip allow.!<lable>";
                        }
                    } else {
                        $result = "<label class='alert alert-danger'>file type is invalid, Only zip allow.!<lable>";
                    }
                } else {
                    $result = "<label class='alert alert-danger'>Only zip allow, please try again.!<lable>";
                }
            } else {
                $result = "<label class='alert alert-danger'>" . $response["message"] . "</lable>";
            }
        } else {
            $result = "<label class='alert alert-danger'>Purchase code required </lable>";
        }
    } else {
        $result = "<label class='alert alert-danger'>Purchase code required </lable>";
    }
    echo $result;
}
//54.add_coin
if (isset($_POST['user']) && isset($_POST['add_coin'])) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }

    $id = $_POST['user'];
    $get_coin = $_POST['coin'];
    $coin = $db->escapeString($_POST['add_coin']);


    $total_coin = $coin + $get_coin;

    $sql = "Update users set `coins`='" . $total_coin . "' where `id`=" . $id;
    $db->sql($sql);
    $res = $db->getResult();


    echo "<p class='alert alert-success'>Coins given to user!</p>";
}
function DeleteDir($dir)
{
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (filetype($dir . "/" . $object) == "dir") {
                    $dir_sec = $dir . "/" . $object;
                    if (is_dir($dir_sec)) {
                        rmdir($dir_sec);
                    }
                } else {
                    unlink($dir . "/" . $object);
                }
            }
        }
        rmdir($dir);
    }
}












// 50. update_category_status
if (isset($_POST['category_status_id']) && isset($_POST['update_category_status'])) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    $id = $_POST['category_status_id'];
    $status = $db->escapeString($_POST['status']);

    $sql = "Update `category` set `status`='" . $status . "' where `id`=" . $id;
    $db->sql($sql);
    echo "<p class='alert alert-success'>Category status updated successfully!</p>";
}
