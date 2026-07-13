<?php

/*
  API v5.6
  Quiz Online - WRTeam.in
  WRTeam Developers
 */
require_once 'crud.php';

/*
  Functions
  -------------
  1. get_configurations()
  2. is_language_mode_enabled()
  3. is_option_e_mode_enabled()
  4. get_user_by_id()
  5. get_count()
  6. get_sum()
  7. get_fields()
  8. upload_file()
  9. no_of_days_bw_dates()
  10. get_user_IP()
  11. is_refer_code_set($user_id)
  12. credit_coins_to_friends_code($friends_code)
  13. check_friends_code_is_used_by_user($user_id)
  14. valid_friends_refer_code($friends_code)
 */

class Functions {

    private $db;

    function __construct() {
        $this->db = new Database();
        $this->db->connect();
    }

    public function get_configurations() {
        $this->db->sql("SET NAMES 'utf8'");
        $sql = "SELECT * FROM settings WHERE type='system_configurations' LIMIT 1";
        $this->db->sql($sql);
        $res = $this->db->getResult();
        if (!empty($res)) {
            return json_decode($res[0]['message'], true);
        } else {
            return false;
        }
    }

    public function is_language_mode_enabled() {
        $configs = $this->get_configurations();
        if (!empty($configs)) {
            if (isset($configs['language_mode']) && $configs['language_mode'] == 1)
                return true;
            else
                return false;
        }else {
            return false;
        }
    }

    public function is_option_e_mode_enabled() {
        $configs = $this->get_configurations();
        if (!empty($configs)) {
            if (isset($configs['option_e_mode']) && $configs['option_e_mode'] == 1)
                return true;
            else
                return false;
        }else {
            return false;
        }
    }

    public function get_user_by_id($id) {
        $sql = "Select * from `users` where `id` = '$id'";
        $this->db->sql($sql);
        $res = $this->db->getResult();
        if (!empty($res)) {
            return $res[0];
        } else {
            return false;
        }
    }

    public function get_count($field, $table, $where = '') {
        if (!empty($where))
            $where = "where " . $where;

        $sql = "SELECT COUNT(" . $field . ") as total FROM " . $table . " " . $where;
        $this->db->sql($sql);
        $res = $this->db->getResult();
        if (!empty($res)) {
            return $res[0]['total'];
        } else {
            return 0;
        }
    }

    public function get_sum($field, $table, $where = '') {
        if (!empty($where))
            $where = "where " . $where;

        $sql = "SELECT SUM(" . $field . ") as total FROM " . $table . " " . $where;
        $this->db->sql($sql);
        $res = $this->db->getResult();
        if (!empty($res)) {
            return $res[0]['total'];
        } else {
            return 0;
        }
    }

    public function get_fields($fields = '*', $table, $where = '', $order = '', $limit = '') {
        $this->db->select($table, $fields, '', $where, $order, $limit);
        $res = $this->db->getResult();
        if (!empty($res)) {
            if (count($res) == 1) {
                return $res[0];
            } else {
                return $res;
            }
        } else {
            return false;
        }
    }

    public function upload_file($file, $target_path, $allowed_extensions) {
        $extension = end(explode(".", $file["name"]));
        if (!(in_array($extension, $allowed_extensions))) {
            $response['error'] = true;
            $response['message'] = "Invalid image format. only jpeg, jpg, png or gif format images are allowed";
            return $response;
            exit();
        }
        if (!is_dir($target_path)) {
            mkdir($target_path, 0777, true);
        }
        $filename = microtime(true) . '.' . strtolower($extension);
        $full_path = $target_path . "" . $filename;
        if (!move_uploaded_file($file["tmp_name"], $full_path)) {
            $response['error'] = true;
            $response['message'] = "File could not be uploaded.";
            return $response;
            exit();
        } else {
            $response['error'] = false;
            $response['message'] = "File uploaded successfully";
            $response['filename'] = $filename;
            $response['target_path'] = $target_path;
            $response['full_path'] = $full_path;
            return $response;
            exit();
        }
    }

    public function no_of_days_bw_dates($from, $to) {
        $from = strtotime($from);
        $to = strtotime($to);
        $datediff = $to - $from;

        return round($datediff / (60 * 60 * 24));
    }

    function get_user_IP() {
        // Get real visitor IP behind CloudFlare network
        if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
            $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
            $_SERVER['HTTP_CLIENT_IP'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
        }
        $client = @$_SERVER['HTTP_CLIENT_IP'];
        $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
        $remote = $_SERVER['REMOTE_ADDR'];

        if (filter_var($client, FILTER_VALIDATE_IP)) {
            $ip = $client;
        } elseif (filter_var($forward, FILTER_VALIDATE_IP)) {
            $ip = $forward;
        } else {
            $ip = $remote;
        }
        return $ip;
    }

    public function is_refer_code_set($user_id) {
        $sql = "SELECT `refer_code` FROM users WHERE id='" . $user_id . "'";
        $this->db->sql($sql);
        $res = $this->db->getResult();

        if (!empty($res[0]['refer_code'])) {
            return true;
        } else {
            return false;
        }
    }

    public function credit_coins_to_friends_code($friends_code) {
        $configs = $this->get_configurations();

        $sql = "UPDATE `users` SET `coins` = `coins` + " . $configs['earn_coin'] . " WHERE `refer_code`='" . $friends_code . "'";
        $this->db->sql($sql);
        $res = $this->db->getResult();
        // return $res;
        $response['credited'] = true;
        return $response;
    }

    public function check_friends_code_is_used_by_user($user_id) {
        $sql = "SELECT friends_code FROM users WHERE id='" . $user_id . "'";
        $this->db->sql($sql);
        $res = $this->db->getResult();
        // return $res;

        if (!empty($res[0]['friends_code'])) {
            $response['is_used'] = true;
        } else {
            $response['is_used'] = false;
        }
        return $response;
    }

    public function valid_friends_refer_code($friends_code) {
        $sql = "SELECT id,name,email FROM users WHERE refer_code='" . $friends_code . "'";
        $this->db->sql($sql);
        $res = $this->db->getResult();
        // return $res;

        if (!empty($res)) {
            $response['is_valid'] = true;
            $response['user_id'] = $res[0]['id'];
            $response['name'] = $res[0]['name'];
            $response['email'] = $res[0]['email'];
        } else {
            $response['is_valid'] = false;
        }
        return $response;
    }

    public function get_battle_settings() {
        $this->db->sql("SET NAMES 'utf8'");
        $sql = "SELECT * FROM settings WHERE type='battle_settings' LIMIT 1";
        $this->db->sql($sql);
        $res = $this->db->getResult();
        if (!empty($res)) {
            return json_decode($res[0]['message'], true);
        } else {
            return false;
        }
    }

    public function is_battle_category_mode_enabled() {
        $configs = $this->get_configurations();
        if (!empty($configs)) {
            if (isset($configs['battle_random_category_mode']) && $configs['battle_random_category_mode'] == 1)
                return true;
            else
                return false;
        }else {
            return false;
        }
    }

    public function is_room_category_mode_enabled() {
        $configs = $this->get_configurations();
        if (!empty($configs)) {
            if (isset($configs['battle_group_category_mode']) && $configs['battle_group_category_mode'] == 1)
                return true;
            else
                return false;
        }else {
            return false;
        }
    }

}
