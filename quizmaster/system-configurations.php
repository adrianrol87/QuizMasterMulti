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
    <title>System Settings | <?= ucwords($_SESSION['company_name']) ?> - Admin Panel </title>
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
                                <h2>System Settings for App <small>Note that this will directly reflect the changes in App</small></h2>
                                <div class="clearfix"></div>
                            </div>
                            <?php
                            $db->sql("SET NAMES 'utf8'");
                            $sql = "SELECT * FROM settings WHERE type='system_configurations' LIMIT 1";
                            $db->sql($sql);
                            $res = $db->getResult();
                            if (!empty($res)) {
                                foreach ($res as $row) {
                                    $id = $row['id'];
                                    $data = json_decode($row['message'], true);
                                }
                            }
                            ?>
                            <div class="x_content">
                                <form id="system_configurations_form" method="POST" data-parsley-validate="" class="form-horizontal form-label-left">
                                    <input type="hidden" id="system_configurations" name="system_configurations" required value="1" aria-required="true">
                                    <input type="hidden" id="system_configurations_id" name="system_configurations_id" value="<?php echo (!empty($id)) ? $id : ''; ?>" aria-required="true">
                                    <input type="hidden" id="system_timezone_gmt" name="system_timezone_gmt" value="<?php echo (!empty($data['system_timezone_gmt'])) ? $data['system_timezone_gmt'] : '-11:00'; ?>" aria-required="true">

                                    <div class="row">
                                        <div class="col-md-12 col-xs-12">
                                            <div class="form-group">
                                                <label for="system_timezone">System Timezone</label>
                                                <?php $options = getTimezoneOptions(); ?>
                                                <select id="system_timezone" name="system_timezone" required class="form-control">
                                                    <?php foreach ($options as $option) { ?>
                                                        <option value="<?= $option[2] ?>" data-gmt="<?= $option['1']; ?>" <?= (isset($data['system_timezone']) && $data['system_timezone'] == $option[2]) ? 'selected' : ''; ?>><?= $option[2] ?> - GMT <?= $option[1] ?> - <?= $option[0] ?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 col-xs-12">
                                            <div class="form-group">
                                                <label for="app_link">App Link</label>
                                                <input type="url" id="app_link" name="app_link" required class="form-control" value="<?php echo (!empty($data['app_link'])) ? $data['app_link'] : "" ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-xs-12">
                                            <div class="form-group">
                                                <label for="more_apps">More Apps Link ( Your Google / iOS Market place URL )</label>
                                                <input type="url" id="more_apps" name="more_apps" required class="form-control" value="<?php echo (!empty($data['more_apps'])) ? $data['more_apps'] : "" ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 col-xs-12">
                                            <div class="form-group">
                                                <label for="ios_app_link">iOS App Link</label>
                                                <input type="url" id="ios_app_link" name="ios_app_link" class="form-control" value="<?php echo (!empty($data['ios_app_link'])) ? $data['ios_app_link'] : "" ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-xs-12">
                                            <div class="form-group">
                                                <label for="ios_more_apps">More Apps Link ( Your iOS Market place URL )</label>
                                                <input type="url" id="ios_more_apps" name="ios_more_apps" class="form-control" value="<?php echo (!empty($data['ios_more_apps'])) ? $data['ios_more_apps'] : "" ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-3 col-xs-12">
                                            <div class="form-group">
                                                <label for="refer_coin">Refer Coin</label>
                                                <input type="number" id="refer_coin" min="0" name="refer_coin" required class="form-control" value="<?php echo ($data['refer_coin'] != "") ? $data['refer_coin'] : "" ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-xs-12">
                                            <div class="form-group">
                                                <label class="" for="question">Welcome User Coin</label>
                                                <input type="number" min="1" id="welcome_coin" name="welcome_coin" required class="form-control" value="<?php echo ($data['welcome_coin']) ? $data['welcome_coin'] : '0' ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <input type="hidden" id="earn_coin" name="earn_coin" value="<?php echo ($data['earn_coin'] != '') ? $data['earn_coin'] : '0' ?>">
                                    <input type="hidden" id="reward_coin" name="reward_coin" value="<?php echo ($data['reward_coin'] != '') ? $data['reward_coin'] : '0'; ?>">

                                    <div class="row">
                                        <div class="col-md-2 col-xs-12">
                                            <div class="form-group">
                                                <label for="app_version">App Version</label>
                                                <input type="text" id="app_version" name="app_version" required class="form-control" value="<?php echo (!empty($data['app_version'])) ? $data['app_version'] : "" ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-2 col-xs-12">
                                            <div class="form-group">
                                                <label for="t/f_value">True Value</label>
                                                <input type="text" id="true_value" name="true_value" required class="form-control" value="<?php echo ($data['true_value']) ? $data['true_value'] : "" ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-2 col-xs-12">
                                            <div class="form-group">
                                                <label for="t/f_value">False Value</label>
                                                <input type="text" id="false_value" name="false_value" required class="form-control" value="<?php echo ($data['false_value']) ? $data['false_value'] : "" ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-2 col-xs-12 hidden-advanced-config">
                                            <div class="form-group">
                                                <label for="answer_display">Answer Display</label>
                                            </div>
                                            <div class="form-group">
                                                <input type="checkbox" id="answer_mode_btn" class="js-switch" <?php
                                                                                                                if (!empty($data['answer_mode']) && $data['answer_mode'] == '1') {
                                                                                                                    echo 'checked';
                                                                                                                }
                                                                                                                ?>>
                                                <input type="hidden" id="answer_mode" name="answer_mode" value="<?= (!empty($data['answer_mode'])) ? $data['answer_mode'] : 0; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-2 col-xs-12">
                                            <div class="form-group">
                                                <label for="language_mode">Language Mode</label>
                                            </div>
                                            <div class="form-group">
                                                <input type="checkbox" id="language_mode_btn" class="js-switch" <?php
                                                                                                                if (!empty($data['language_mode']) && $data['language_mode'] == '1') {
                                                                                                                    echo 'checked';
                                                                                                                }
                                                                                                                ?>>
                                                <input type="hidden" id="language_mode" name="language_mode" value="<?= (!empty($data['language_mode'])) ? $data['language_mode'] : 0; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-2 col-xs-12">
                                            <div class="form-group">
                                                <label for="option_e_mode">Option E Mode</label>
                                            </div>
                                            <div class="form-group">
                                                <input type="checkbox" id="option_e_mode_btn" class="js-switch" <?php
                                                                                                                if (!empty($data['option_e_mode']) && $data['option_e_mode'] == '1') {
                                                                                                                    echo 'checked';
                                                                                                                }
                                                                                                                ?>>
                                                <input type="hidden" id="option_e_mode" name="option_e_mode" value="<?= (!empty($data['option_e_mode'])) ? $data['option_e_mode'] : 0; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-2 col-xs-12">
                                            <div class="form-group">
                                                <label for="force_update">Force Update App</label>
                                            </div>
                                            <div class="form-group">
                                                <input type="checkbox" id="force_update_btn" class="js-switch" <?php
                                                                                                                if (!empty($data['force_update']) && $data['force_update'] == '1') {
                                                                                                                    echo 'checked';
                                                                                                                }
                                                                                                                ?>>
                                                <input type="hidden" id="force_update" name="force_update" value="<?= (!empty($data['force_update'])) ? $data['force_update'] : 0; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-2 col-xs-12">
                                            <div class="form-group">
                                                <label for="daily_quiz_mode">Daily Quiz Mode</label>
                                            </div>
                                            <div class="form-group">
                                                <input type="checkbox" id="daily_quiz_mode_btn" class="js-switch" <?php
                                                                                                                    if (!empty($data['daily_quiz_mode']) && $data['daily_quiz_mode'] == '1') {
                                                                                                                        echo 'checked';
                                                                                                                    }
                                                                                                                    ?>>
                                                <input type="hidden" id="daily_quiz_mode" name="daily_quiz_mode" value="<?= (!empty($data['daily_quiz_mode'])) ? $data['daily_quiz_mode'] : 0; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-2 col-xs-12">
                                            <div class="form-group">
                                                <label for="contest_mode">Contest Mode</label>
                                            </div>
                                            <div class="form-group">
                                                <input type="checkbox" id="contest_mode_btn" class="js-switch" <?php
                                                                                                                if (!empty($data['contest_mode']) && $data['contest_mode'] == '1') {
                                                                                                                    echo 'checked';
                                                                                                                }
                                                                                                                ?>>
                                                <input type="hidden" id="contest_mode" name="contest_mode" value="<?= (!empty($data['contest_mode'])) ? $data['contest_mode'] : 0; ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-2 col-xs-12">
                                            <div class="form-group">
                                                <label for="battle_category_mode">Battle Random Category Mode</label>
                                            </div>
                                            <div class="form-group">
                                                <input type="checkbox" id="battle_random_category_mode_btn" class="js-switch" <?php
                                                                                                                                if (!empty($data['battle_random_category_mode']) && $data['battle_random_category_mode'] == '1') {
                                                                                                                                    echo 'checked';
                                                                                                                                }
                                                                                                                                ?>>
                                                <input type="hidden" id="battle_random_category_mode" name="battle_random_category_mode" value="<?= (!empty($data['battle_random_category_mode'])) ? $data['battle_random_category_mode'] : 0; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-2 col-xs-12">
                                            <div class="form-group">
                                                <label for="battle_group_category_mode">Battle Group Category Mode</label>
                                            </div>
                                            <div class="form-group">
                                                <input type="checkbox" id="battle_group_category_mode_btn" class="js-switch" <?php
                                                                                                                                if (!empty($data['battle_group_category_mode']) && $data['battle_group_category_mode'] == '1') {
                                                                                                                                    echo 'checked';
                                                                                                                                }
                                                                                                                                ?>>
                                                <input type="hidden" id="battle_group_category_mode" name="battle_group_category_mode" value="<?= (!empty($data['battle_group_category_mode'])) ? $data['battle_group_category_mode'] : 0; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-2 col-xs-12 hidden-advanced-config">
                                            <div class="form-group">
                                                <label for="in_app_purchase_mode">In App Purchase</label>
                                            </div>
                                            <div class="form-group">
                                                <input type="checkbox" id="in_app_purchase_mode_btn" class="js-switch" <?php
                                                                                                                        if (!empty($data['in_app_purchase_mode']) && $data['in_app_purchase_mode'] == '1') {
                                                                                                                            echo 'checked';
                                                                                                                        }
                                                                                                                        ?>>
                                                <input type="hidden" id="in_app_purchase_mode" name="in_app_purchase_mode" value="<?= (!empty($data['in_app_purchase_mode'])) ? $data['in_app_purchase_mode'] : 0; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-2 col-xs-12">
                                            <div class="form-group">
                                                <label for="learning_zone_mode">Learning Zone Mode</label>
                                            </div>
                                            <div class="form-group">
                                                <input type="checkbox" id="learning_zone_mode_btn" class="js-switch" <?php
                                                                                                                        if (!empty($data['learning_zone_mode']) && $data['learning_zone_mode'] == '1') {
                                                                                                                            echo 'checked';
                                                                                                                        }
                                                                                                                        ?>>
                                                <input type="hidden" id="learning_zone_mode" name="learning_zone_mode" value="<?= (!empty($data['learning_zone_mode'])) ? $data['learning_zone_mode'] : 0; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-2 col-xs-12">
                                            <div class="form-group">
                                                <label for="maths_quiz_mode">Maths Quiz Mode</label>
                                            </div>
                                            <div class="form-group">
                                                <input type="checkbox" id="maths_quiz_mode_btn" class="js-switch" <?php
                                                                                                                    if (!empty($data['maths_quiz_mode']) && $data['maths_quiz_mode'] == '1') {
                                                                                                                        echo 'checked';
                                                                                                                    }
                                                                                                                    ?>>
                                                <input type="hidden" id="maths_quiz_mode" name="maths_quiz_mode" value="<?= (!empty($data['maths_quiz_mode'])) ? $data['maths_quiz_mode'] : 0; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-2 col-xs-12">
                                            <div class="form-group">
                                                <label for="true_false_mode">True/False Mode</label>
                                            </div>
                                            <div class="form-group">
                                                <input type="checkbox" id="true_false_mode_btn" class="js-switch" <?php
                                                                                                                    if (!empty($data['true_false_mode']) && $data['true_false_mode'] == '1') {
                                                                                                                        echo 'checked';
                                                                                                                    }
                                                                                                                    ?>>
                                                <input type="hidden" id="true_false_mode" name="true_false_mode" value="<?= (!empty($data['true_false_mode'])) ? $data['true_false_mode'] : 0; ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-2 col-xs-12">
                                            <div class="form-group">
                                                <label for="fix_question">Spin Mode</label>
                                            </div>
                                            <div class="form-group">
                                                <input type="checkbox" id="spin_mode_btn" class="js-switch" <?php
                                                                                                            if (!empty($data['spin_mode']) && $data['spin_mode'] == '1') {
                                                                                                                echo 'checked';
                                                                                                            }
                                                                                                            ?>>
                                                <input type="hidden" id="spin_mode" name="spin_mode" value="<?= (!empty($data['spin_mode'])) ? $data['spin_mode'] : 0; ?>">
                                            </div>
                                        </div>

                                        <div class="col-md-2 col-xs-12 hidden-advanced-config">
                                            <div class="form-group">
                                                <label for="fix_question">Fix Question in Level</label>
                                            </div>
                                            <div class="form-group">
                                                <input type="checkbox" id="fix_question_btn" class="js-switch" <?php
                                                                                                                if (!empty($data['fix_question']) && $data['fix_question'] == '1') {
                                                                                                                    echo 'checked';
                                                                                                                }
                                                                                                                ?>>
                                                <input type="hidden" id="fix_question" name="fix_question" value="<?= (!empty($data['fix_question'])) ? $data['fix_question'] : 0; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-2 col-xs-12 hidden-advanced-config" id="fix_que">
                                            <div class="form-group">
                                                <label class="" for="question">Total Question per Level</label>
                                            </div>
                                            <div class="form-group">
                                                <input type="number" min="0" id="total_question" name="total_question" required class="form-control" value="<?php echo ($data['total_question']) ? $data['total_question'] : '0' ?>">
                                            </div>
                                        </div>

                                    </div>
                                    <div class="row">
                                        <div class="col-md-2 col-xs-12">
                                            <div class="form-group">
                                                <label for="app_maintenance">App Maintenance</label>
                                            </div>
                                            <div class="form-group">
                                                <input type="checkbox" id="app_maintenance_btn" class="js-switch" <?php
                                                                                                                    if (!empty($data['app_maintenance']) && $data['app_maintenance'] == '1') {
                                                                                                                        echo 'checked';
                                                                                                                    }
                                                                                                                    ?>>
                                                <input type="hidden" id="app_maintenance" name="app_maintenance" value="<?= (!empty($data['app_maintenance'])) ? $data['app_maintenance'] : 0; ?>">
                                            </div>
                                        </div>
                                        <!-- <div class="col-md-2 col-xs-12" id="fix_que">
                                            <div class="form-group">
                                                <label class="" for="question">Total Question per Level</label>
                                                <input type="number" min="1" id="total_question" name="total_question" required class="form-control" value="<?php echo ($data['total_question']) ? $data['total_question'] : '0' ?>">
                                            </div>
                                        </div> -->
                                        <div class="col-md-4 col-xs-12 maintenance">
                                            <div class="form-group">
                                                <label>App Maintenance Message</label>
                                                <textarea id="app_maintenance_message" name="app_maintenance_message" class="form-control"><?= (!empty($data['app_maintenance_message'])) ? $data['app_maintenance_message'] : ''; ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 col-xs-12">
                                            <div class="form-group">
                                                <label class="" for="shareapp_text">Shareapp Text</label>
                                                <textarea id="shareapp_text" name="shareapp_text" required class="form-control"><?php
                                                                                                                                if (!empty($data['shareapp_text'])) {
                                                                                                                                    echo $data['shareapp_text'];
                                                                                                                                }
                                                                                                                                ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <hr />
                                    <div class="mt-20">
                                        <h2>System Settings for Ads.</h2>
                                        <div class="clearfix"></div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-2 col-xs-12">
                                            <div class="form-group">
                                                <label for="in_app_ads_mode">In App Ads.</label>
                                            </div>
                                            <div class="form-group">
                                                <input type="checkbox" id="in_app_ads_mode_btn" class="js-switch" <?php
                                                                                                                    if (!empty($data['in_app_ads_mode']) && $data['in_app_ads_mode'] == '1') {
                                                                                                                        echo 'checked';
                                                                                                                    }
                                                                                                                    ?>>
                                                <input type="hidden" id="in_app_ads_mode" name="in_app_ads_mode" value="<?= (!empty($data['in_app_ads_mode'])) ? $data['in_app_ads_mode'] : 0; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-4 col-xs-12 adsHide">
                                            <div class="form-group">
                                                <label>&nbsp;</label>
                                            </div>
                                            <div id="status" class="btn-group">
                                                <label class="btn btn-default" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default">
                                                    <input type="radio" name="ads_type" value="1" <?php
                                                                                                    if (!empty($data['ads_type']) && $data['ads_type'] == '1') {
                                                                                                        echo 'checked';
                                                                                                    }
                                                                                                    ?>> Google AdMob
                                                </label>
                                                <label class="btn btn-default" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default">
                                                    <input type="radio" name="ads_type" value="2" <?php
                                                                                                    if (!empty($data['ads_type']) && $data['ads_type'] == '2') {
                                                                                                        echo 'checked';
                                                                                                    }
                                                                                                    ?>> Facebook Ads.
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row adsgoogle">
                                        <div class="col-md-4 col-xs-12">
                                            <div class="form-group">
                                                <label for="adAppId">Ads. App Id</label>
                                                <input type="text" id="adAppId" name="adAppId" class="form-control googleAtt" value="<?php echo (!empty($data['adAppId'])) ? $data['adAppId'] : "" ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-4 col-xs-12">
                                            <div class="form-group">
                                                <label for="admob_Rewarded_Video_Ads">Rewarded Video Ads</label>
                                                <input type="text" id="admob_Rewarded_Video_Ads" name="admob_Rewarded_Video_Ads" class="form-control googleAtt" value="<?php echo (!empty($data['admob_Rewarded_Video_Ads'])) ? $data['admob_Rewarded_Video_Ads'] : "" ?>">
                                            </div>
                                        </div>

                                        <div class="col-md-4 col-xs-12">
                                            <div class="form-group">
                                                <label for="admob_interstitial_id">Interstitial Id</label>
                                                <input type="text" id="admob_interstitial_id" name="admob_interstitial_id" class="form-control googleAtt" value="<?php echo (!empty($data['admob_interstitial_id'])) ? $data['admob_interstitial_id'] : "" ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-4 col-xs-12">
                                            <div class="form-group">
                                                <label for="admob_banner_id">Banner Id</label>
                                                <input type="text" id="admob_banner_id" name="admob_banner_id" class="form-control googleAtt" value="<?php echo (!empty($data['admob_banner_id'])) ? $data['admob_banner_id'] : "" ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-4 col-xs-12">
                                            <div class="form-group">
                                                <label for="native_unit_id">Native Unit Id</label>
                                                <input type="text" id="native_unit_id" name="native_unit_id" class="form-control googleAtt" value="<?php echo (!empty($data['native_unit_id'])) ? $data['native_unit_id'] : "" ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-4 col-xs-12">
                                            <div class="form-group">
                                                <label for="admob_openads_id">Open Ads_id</label>
                                                <input type="text" id="admob_openads_id" name="admob_openads_id" class="form-control googleAtt" value="<?php echo (!empty($data['admob_openads_id'])) ? $data['admob_openads_id'] : "" ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row adsfacebook">
                                        <div class="col-md-6 col-xs-12">
                                            <div class="form-group">
                                                <label class="" for="app_link">Facebook Interstitial id</label>
                                                <input type="text" id="fb_interstitial_id" name="fb_interstitial_id" class="form-control facebookAtt" value="<?= (!empty($data['fb_interstitial_id'])) ? $data['fb_interstitial_id'] : ''; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-xs-12">
                                            <div class="form-group">
                                                <label class="" for="app_link">Facebook Banner id</label>
                                                <input type="text" id="fb_banner_id" name="fb_banner_id" class="form-control facebookAtt" value="<?= (!empty($data['fb_banner_id'])) ? $data['fb_banner_id'] : ''; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-xs-12">
                                            <div class="form-group">
                                                <label class="" for="app_link">Facebook Rewarded Video Ads</label>
                                                <input type="text" id="fb_rewarded_video_ads" name="fb_rewarded_video_ads" class="form-control facebookAtt" value="<?= (!empty($data['fb_rewarded_video_ads'])) ? $data['fb_rewarded_video_ads'] : ''; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-xs-12">
                                            <div class="form-group">
                                                <label class="" for="app_link">Facebook Native Unit Id</label>
                                                <input type="text" id="fb_native_unit_id" name="fb_native_unit_id" class="form-control facebookAtt" value="<?= (!empty($data['fb_native_unit_id'])) ? $data['fb_native_unit_id'] : ''; ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <hr />
                                    <div class="mt-20">
                                        <h2>System Settings for iOS Ads.</h2>
                                        <div class="clearfix"></div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-2 col-xs-12">
                                            <div class="form-group">
                                                <label for="ios_in_app_ads_mode">In App Ads.</label>
                                            </div>
                                            <div class="form-group">
                                                <input type="checkbox" id="ios_in_app_ads_mode_btn" class="js-switch" <?php
                                                                                                                        if (!empty($data['ios_in_app_ads_mode']) && $data['ios_in_app_ads_mode'] == '1') {
                                                                                                                            echo 'checked';
                                                                                                                        }
                                                                                                                        ?>>
                                                <input type="hidden" id="ios_in_app_ads_mode" name="ios_in_app_ads_mode" value="<?= (!empty($data['ios_in_app_ads_mode'])) ? $data['ios_in_app_ads_mode'] : 0; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-4 col-xs-12 iOSadsHide">
                                            <div class="form-group">
                                                <label>&nbsp;</label>
                                            </div>
                                            <div id="status" class="btn-group">
                                                <label class="btn btn-default" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default">
                                                    <input type="radio" name="ios_ads_type" value="1" <?php
                                                                                                        if (!empty($data['ios_ads_type']) && $data['ios_ads_type'] == '1') {
                                                                                                            echo 'checked';
                                                                                                        }
                                                                                                        ?>> Google AdMob
                                                </label>
                                                <label class="btn btn-default" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default">
                                                    <input type="radio" name="ios_ads_type" value="2" <?php
                                                                                                        if (!empty($data['ios_ads_type']) && $data['ios_ads_type'] == '2') {
                                                                                                            echo 'checked';
                                                                                                        }
                                                                                                        ?>> Facebook Ads.
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row iOSadsgoogle">
                                        <div class="col-md-6 col-xs-12">
                                            <div class="form-group">
                                                <label for="ios_admob_Rewarded_Video_Ads">Rewarded Video Ads</label>
                                                <input type="text" id="ios_admob_Rewarded_Video_Ads" name="ios_admob_Rewarded_Video_Ads" class="form-control iOSgoogleAtt" value="<?php echo (!empty($data['ios_admob_Rewarded_Video_Ads'])) ? $data['ios_admob_Rewarded_Video_Ads'] : "" ?>">
                                            </div>
                                        </div>

                                        <div class="col-md-6 col-xs-12">
                                            <div class="form-group">
                                                <label for="ios_admob_interstitial_id">Interstitial Id</label>
                                                <input type="text" id="ios_admob_interstitial_id" name="ios_admob_interstitial_id" class="form-control iOSgoogleAtt" value="<?php echo (!empty($data['ios_admob_interstitial_id'])) ? $data['ios_admob_interstitial_id'] : "" ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-xs-12">
                                            <div class="form-group">
                                                <label for="ios_admob_banner_id">Banner Id</label>
                                                <input type="text" id="ios_admob_banner_id" name="ios_admob_banner_id" class="form-control iOSgoogleAtt" value="<?php echo (!empty($data['ios_admob_banner_id'])) ? $data['ios_admob_banner_id'] : "" ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-xs-12">
                                            <div class="form-group">
                                                <label for="ios_admob_openads_id">Open Ads_id</label>
                                                <input type="text" id="ios_admob_openads_id" name="ios_admob_openads_id" class="form-control iOSgoogleAtt" value="<?php echo (!empty($data['ios_admob_openads_id'])) ? $data['ios_admob_openads_id'] : "" ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row iOSadsfacebook">
                                        <div class="col-md-4 col-xs-12">
                                            <div class="form-group">
                                                <label class="" for="ios_fb_interstitial_id">Facebook Interstitial id</label>
                                                <input type="text" id="ios_fb_interstitial_id" name="ios_fb_interstitial_id" class="form-control iOSfacebookAtt" value="<?= (!empty($data['ios_fb_interstitial_id'])) ? $data['ios_fb_interstitial_id'] : ''; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-4 col-xs-12">
                                            <div class="form-group">
                                                <label class="" for="ios_fb_banner_id">Facebook Banner id</label>
                                                <input type="text" id="ios_fb_banner_id" name="ios_fb_banner_id" class="form-control iOSfacebookAtt" value="<?= (!empty($data['ios_fb_banner_id'])) ? $data['ios_fb_banner_id'] : ''; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-4 col-xs-12">
                                            <div class="form-group">
                                                <label class="" for="ios_fb_rewarded_video_ads">Facebook Rewarded Video Ads</label>
                                                <input type="text" id="ios_fb_rewarded_video_ads" name="ios_fb_rewarded_video_ads" class="form-control iOSfacebookAtt" value="<?= (!empty($data['ios_fb_rewarded_video_ads'])) ? $data['ios_fb_rewarded_video_ads'] : ''; ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <hr />
                                    <div class="mt-20">
                                        <h2>Social Media Links</h2>
                                        <div class="clearfix"></div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 col-xs-12">
                                            <div class="form-group">
                                                <label for="instagram_link">Instagram Link</label>
                                                <input type="url" id="instagram_link" name="instagram_link" class="form-control" value="<?php echo (!empty($data['instagram_link'])) ? $data['instagram_link'] : "" ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-xs-12">
                                            <div class="form-group">
                                                <label for="facebook_link">Facebook Link</label>
                                                <input type="url" id="facebook_link" name="facebook_link" class="form-control" value="<?php echo (!empty($data['facebook_link'])) ? $data['facebook_link'] : "" ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 col-xs-12">
                                            <div class="form-group">
                                                <label for="youtube_link">YouTube Link</label>
                                                <input type="url" id="youtube_link" name="youtube_link" class="form-control" value="<?php echo (!empty($data['youtube_link'])) ? $data['youtube_link'] : "" ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-12 col-xs-12">
                                            <div class="ln_solid"></div>
                                            <div id="result"></div>
                                            <div class="form-group">
                                                <div class="col-md-6 col-sm-6 col-xs-12">
                                                    <button type="submit" id="submit_btn" class="btn btn-warning">Save Settings</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
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

    <?php

    function getTimezoneOptions()
    {
        $list = DateTimeZone::listAbbreviations();
        $idents = DateTimeZone::listIdentifiers();

        $data = $offset = $added = array();
        foreach ($list as $abbr => $info) {
            foreach ($info as $zone) {
                if (
                    !empty($zone['timezone_id'])
                    and !in_array($zone['timezone_id'], $added)
                    and
                    in_array($zone['timezone_id'], $idents)
                ) {
                    $z = new DateTimeZone($zone['timezone_id']);
                    $c = new DateTime(null, $z);
                    $zone['time'] = $c->format('H:i a');
                    $offset[] = $zone['offset'] = $z->getOffset($c);
                    $data[] = $zone;
                    $added[] = $zone['timezone_id'];
                }
            }
        }

        array_multisort($offset, SORT_ASC, $data);
        /* $options = array();
              foreach ($data as $key => $row) {
              $options[$row['timezone_id']] = $row['time'] . ' - '
              . formatOffset($row['offset']). ' ' . $row['timezone_id'];
              } */
        $i = 0;
        $temp = array();
        foreach ($data as $key => $row) {
            $temp[0] = $row['time'];
            $temp[1] = formatOffset($row['offset']);
            $temp[2] = $row['timezone_id'];
            $options[$i++] = $temp;
        }

        // echo "<pre>";
        // print_r($options);
        return $options;
    }

    function formatOffset($offset)
    {
        $hours = $offset / 3600;
        $remainder = $offset % 3600;
        $sign = $hours > 0 ? '+' : '-';
        $hour = (int) abs($hours);
        $minutes = (int) abs($remainder / 60);

        if ($hour == 0 and $minutes == 0) {
            $sign = ' ';
        }
        return $sign . str_pad($hour, 2, '0', STR_PAD_LEFT) . ':' . str_pad($minutes, 2, '0');
    }
    ?>

    <!-- jQuery -->
    <style>
        .hidden-advanced-config {
            display: none !important;
        }
    </style>
    <script>
        $(document).ready(function() {
            var que = $('#fix_question').val();
            if (que == '1') {
                $('#fix_que').show();
            } else {
                $('#fix_que').hide();
            }
            var spin = $('#spin_mode').val();

            if (spin == '1') {
                $('#coins').show();
            } else {
                $('#coins').hide();
            }

            $('.adsHide').hide();
            $('.adsgoogle').hide();
            $('.adsfacebook').hide();
            var ads = $('#in_app_ads_mode').val();
            if (ads === '1' || ads === 1) {
                $('.adsHide').show();
                var ads_type = $("input:radio[name=ads_type]:checked").val();
                if (ads_type == undefined) {
                    $("input[name=ads_type][value=1]").prop('checked', true);
                }
                var ads_type = $("input:radio[name=ads_type]:checked").val();
                ads_type_manage(ads_type);
            } else {
                $('.adsHide').hide();
                $('.adsgoogle').hide();
                $('.googleAtt').removeAttr('required');
                $('.adsfacebook').hide();
                $('.facebookAtt').removeAttr('required');
            }

            $('.iOSadsHide').hide();
            $('.iOSadsgoogle').hide();
            $('.iOSadsfacebook').hide();
            var ads = $('#ios_in_app_ads_mode').val();
            if (ads === '1' || ads === 1) {
                $('.iOSadsHide').show();
                var ios_ads_type = $("input:radio[name=ios_ads_type]:checked").val();
                if (ios_ads_type == undefined) {
                    $("input[name=ios_ads_type][value=1]").prop('checked', true);
                }
                var ios_ads_type = $("input:radio[name=ios_ads_type]:checked").val();
                ios_ads_type_manage(ios_ads_type);
            } else {
                $('.iOSadsHide').hide();
                $('.iOSadsgoogle').hide();
                $('.iOSgoogleAtt').removeAttr('required');
                $('.iOSadsfacebook').hide();
                $('.iOSfacebookAtt').removeAttr('required');
            }

            var app_maintenance = $('#app_maintenance').val();
            if (app_maintenance == 1 || app_maintenance == '1') {
                $('.maintenance').show();
                $('#app_maintenance_message').attr('required', 'required');
            } else {
                $('.maintenance').hide();
                $('#app_maintenance_message').removeAttr('required');
            }

        });

        function ads_type_manage(ads_type) {
            if (ads_type === '1' || ads_type === 1) {
                $('.adsgoogle').show();
                $('.googleAtt').attr('required', 'required');
                $('.adsfacebook').hide();
                $('.facebookAtt').removeAttr('required');
            } else if (ads_type === '2' || ads_type === 2) {
                $('.adsgoogle').hide();
                $('.googleAtt').removeAttr('required');
                $('.adsfacebook').show();
                $('.facebookAtt').attr('required', 'required');
            } else {
                $('.adsHide').hide();
                $('.adsfacebook').hide();
                $('.facebookAtt').removeAttr('required');
                $('.adsgoogle').hide();
                $('.googleAtt').removeAttr('required');
            }
        }

        $(document).on('click', 'input[name="ads_type"]', function() {
            var ads_type = $(this).val();
            ads_type_manage(ads_type);
        });

        function ios_ads_type_manage(ios_ads_type) {
            if (ios_ads_type === '1' || ios_ads_type === 1) {
                $('.iOSadsgoogle').show();
                $('.iOSgoogleAtt').attr('required', 'required');
                $('.iOSadsfacebook').hide();
                $('.iOSfacebookAtt').removeAttr('required');
            } else if (ios_ads_type === '2' || ios_ads_type === 2) {
                $('.iOSadsgoogle').hide();
                $('.iOSgoogleAtt').removeAttr('required');
                $('.iOSadsfacebook').show();
                $('.iOSfacebookAtt').attr('required', 'required');
            } else {
                $('.iOSadsHide').hide();
                $('.iOSadsfacebook').hide();
                $('.iOSfacebookAtt').removeAttr('required');
                $('.iOSadsgoogle').hide();
                $('.iOSgoogleAtt').removeAttr('required');
            }
        }

        $(document).on('click', 'input[name="ios_ads_type"]', function() {
            var ios_ads_type = $(this).val();
            ios_ads_type_manage(ios_ads_type);
        });


        /* on change of language mode btn - switchery js */
        var changeCheckbox = document.querySelector('#language_mode_btn');
        changeCheckbox.onchange = function() {
            if (changeCheckbox.checked)
                $('#language_mode').val(1);
            else
                $('#language_mode').val(0);
        };
        /* on change of option e mode btn - switchery js */
        var changeCheckbox1 = document.querySelector('#option_e_mode_btn');
        changeCheckbox1.onchange = function() {
            if (changeCheckbox1.checked)
                $('#option_e_mode').val(1);
            else
                $('#option_e_mode').val(0);
        };
        /* on change of answer mode btn - switchery js */
        var changeCheckbox2 = document.querySelector('#answer_mode_btn');
        changeCheckbox2.onchange = function() {
            if (changeCheckbox2.checked)
                $('#answer_mode').val(1);
            else
                $('#answer_mode').val(0);
        };
        /* on change of fix question btn - switchery js */
        var changeCheckbox3 = document.querySelector('#fix_question_btn');
        changeCheckbox3.onchange = function() {
            console.log(changeCheckbox3.checked);
            if (changeCheckbox3.checked) {
                $('#fix_question').val(1);
                $('#fix_que').show();
            } else {
                $('#fix_question').val(0);
                $('#fix_que').hide();
            }
        };
        // /* on change of spin mode  btn - switchery js */
        var changeCheckbox3 = document.querySelector('#spin_mode_btn');
        changeCheckbox3.onchange = function() {
            if (changeCheckbox3.checked) {
                $('#spin_mode').val(1);
                $('#coins').show();
            } else {
                $('#spin_mode').val(0);
                $('#coins').hide();
            }
        };
        /* on change of force update btn - switchery js */
        var changeCheckbox4 = document.querySelector('#force_update_btn');
        changeCheckbox4.onchange = function() {
            if (changeCheckbox4.checked)
                $('#force_update').val(1);
            else
                $('#force_update').val(0);
        };
        /* on change of daily quiz mode btn - switchery js */
        var changeCheckbox5 = document.querySelector('#daily_quiz_mode_btn');
        changeCheckbox5.onchange = function() {
            if (changeCheckbox5.checked)
                $('#daily_quiz_mode').val(1);
            else
                $('#daily_quiz_mode').val(0);
        };
        /* on change of contest mode btn - switchery js */
        var changeCheckbox6 = document.querySelector('#contest_mode_btn');
        changeCheckbox6.onchange = function() {
            if (changeCheckbox6.checked)
                $('#contest_mode').val(1);
            else
                $('#contest_mode').val(0);
        };

        /* on change of battle category mode btn - switchery js */
        var changeCheckbox7 = document.querySelector('#battle_random_category_mode_btn');
        changeCheckbox7.onchange = function() {
            if (changeCheckbox7.checked)
                $('#battle_random_category_mode').val(1);
            else
                $('#battle_random_category_mode').val(0);
        };

        /* on change of room category mode btn - switchery js */
        var changeCheckbox8 = document.querySelector('#battle_group_category_mode_btn');
        changeCheckbox8.onchange = function() {
            if (changeCheckbox8.checked)
                $('#battle_group_category_mode').val(1);
            else
                $('#battle_group_category_mode').val(0);
        };

        /* on change of in app purchase mode btn - switchery js */
        var changeCheckbox9 = document.querySelector('#in_app_purchase_mode_btn');
        changeCheckbox9.onchange = function() {
            if (changeCheckbox9.checked)
                $('#in_app_purchase_mode').val(1);
            else
                $('#in_app_purchase_mode').val(0);
        };

        /* on change of in app ads mode btn - switchery js */
        var changeCheckbox10 = document.querySelector('#in_app_ads_mode_btn');
        changeCheckbox10.onchange = function() {
            if (changeCheckbox10.checked) {
                $('#in_app_ads_mode').val(1);
                $('.adsHide').show();
                $("input[name=ads_type][value=1]").prop('checked', true);
                var ads_type = $("input:radio[name=ads_type]:checked").val();
                ads_type_manage(ads_type);
            } else {
                $('#in_app_ads_mode').val(0);
                $('.adsHide').hide();
                ads_type_manage(0);
            }
        };

        /* on change of in learning zone mode btn - switchery js */
        var changeCheckbox11 = document.querySelector('#learning_zone_mode_btn');
        changeCheckbox11.onchange = function() {
            if (changeCheckbox11.checked)
                $('#learning_zone_mode').val(1);
            else
                $('#learning_zone_mode').val(0);
        };

        /* on change of in maths quiz mode btn - switchery js */
        var changeCheckbox12 = document.querySelector('#maths_quiz_mode_btn');
        changeCheckbox12.onchange = function() {
            if (changeCheckbox12.checked)
                $('#maths_quiz_mode').val(1);
            else
                $('#maths_quiz_mode').val(0);
        };

        /* on change of ios in app ads mode btn - switchery js */
        var changeCheckbox13 = document.querySelector('#ios_in_app_ads_mode_btn');
        changeCheckbox13.onchange = function() {
            if (changeCheckbox13.checked) {
                $('#ios_in_app_ads_mode').val(1);
                $('.iOSadsHide').show();
                $("input[name=ios_ads_type][value=1]").prop('checked', true);
                var ios_ads_type = $("input:radio[name=ios_ads_type]:checked").val();
                ios_ads_type_manage(ios_ads_type);
            } else {
                $('#ios_in_app_ads_mode').val(0);
                $('.iOSadsHide').hide();
                ios_ads_type_manage(0);
            }
        };

        /* on change of in maths quiz mode btn - switchery js */
        var changeCheckbox14 = document.querySelector('#true_false_mode_btn');
        changeCheckbox14.onchange = function() {
            if (changeCheckbox14.checked)
                $('#true_false_mode').val(1);
            else
                $('#true_false_mode').val(0);
        };

        /* on change of in maths quiz mode btn - switchery js */
        var changeCheckbox15 = document.querySelector('#app_maintenance_btn');
        changeCheckbox15.onchange = function() {
            if (changeCheckbox15.checked) {
                $('#app_maintenance').val(1);
                $('.maintenance').show();
                $('#app_maintenance_message').attr('required', 'required');
            } else {
                $('#app_maintenance').val(0);
                $('.maintenance').hide();
                $('#app_maintenance_message').removeAttr('required');
            }
        };

        $('#system_timezone').on('change', function(e) {
            gmt = $(this).find(':selected').data('gmt');
            $('#system_timezone_gmt').val(gmt);

        });

        $('#system_configurations_form').on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            if ($("#system_configurations_form").validate().form()) {
                swal({
                    title: "Are you sure?",
                    text: "Changing Option E Mode / Language Mode On / Off Will affect the App. After disabling option E Mode some of your answer may go into locked status, So please verify all answers and than update.",
                    icon: "warning",
                    // buttons: true,
                    buttons: ["Cancel! Let me check", "Its okay! Update now"],
                    dangerMode: true,
                }).then((willUpdate) => {
                    if (willUpdate) {
                        $.ajax({
                            type: 'POST',
                            url: 'db_operations.php',
                            data: formData,
                            beforeSend: function() {
                                $('#submit_btn').html('Please wait..');
                            },
                            cache: false,
                            contentType: false,
                            processData: false,
                            success: function(result) {
                                $('#result').html(result);
                                $('#result').show().delay(5000).fadeOut();
                                $('#submit_btn').html('Save Settings');
                            }
                        });
                    }
                });
            }
        });
    </script>
</body>

</html>
