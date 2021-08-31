<?php

class User {

    public $id = 0;
    public $username = '';
    public $div_id = 0;
    public $fio = NULL;
    public $fios = NULL;
    public $info = NULL;
    public $email = NULL;
    public $enable = 1;
    public $ticket_groups = array();
    public $ticket_groups_orig = '';
    public $group_manager = array();
    public $groups = array();
    //var $sess = '';

    public function __construct($id = FALSE) {
        global $DB;
        if(!$id){
            if (!isset($_SESSION['id'])) {
                return FALSE;
            }else{
                $id = $_SESSION['id'];
            }
        }
        if (!$id) {
            $sel = $DB->prepare("select * from `users` where `user`=:user");
            $sel->execute(array('user' => $_SESSION['id']));
        } else {
            $sel = $DB->prepare("select * from `users` where `id`=:id");
            $sel->execute(array('id' => $id));
        }
        if ($sel->rowCount() > 0) {
            $ref = $sel->fetch();
            $this->id = (INT) $ref['id'];
            $this->username = $ref['user'];
            /*if (isset($_COOKIE['PHPSESSID'])) {
                $this->sess = $_COOKIE['PHPSESSID'];
            }*/
            $this->div_id = $ref['div_id'];
            $this->fio = $ref['FIO'];
            $this->info = $ref['info'];
            $fios = preg_split('/\s+/', $ref['FIO']);
            $this->fios = self::short_fio($ref['FIO']);
            $this->email = $ref['email'];
            $this->enable = $ref['enable'];
            if ($ref['groups'] == "*") {
                //groups manager all
                $sel_mg = $DB->prepare("SELECT `value` FROM `roles`");
                $sel_mg->execute();
                $roles_all = array();
                while ($row = $sel_mg->fetch()) {
                    $roles_all[] = $row['id'];
                }
                $this->groups = $roles_all;
            } else {
                $this->groups = (!empty($ref['groups'])) ? preg_split('/,/', $ref['groups']) : array();
            }
            if(in_array('root', $this->groups)){
                $sel_mg = $DB->prepare("SELECT id FROM ticket_groups ");
                $sel_mg->execute();
            }else{
                $sel_mg = $DB->prepare("SELECT id FROM ticket_groups WHERE `div_id`=:div_id");
                $sel_mg->execute(array('div_id'=> $this->div_id));
            }
            $groups_all = array();
            while ($row = $sel_mg->fetch()) {
                $groups_all[] = $row['id'];
            }
            $this->ticket_groups_orig = $ref['ticket_groups'];
            if ($ref['ticket_groups'] == "*") {
                //groups all
                $this->ticket_groups = $groups_all;
            } else {
                $this->ticket_groups = ($ref['ticket_groups']) ? preg_split('/,/', $ref['ticket_groups']) : array();
            }
            if ($ref['group_manager'] == "*") {
                //groups manager all
                $this->group_manager = $groups_all;
            } else {
                $this->group_manager = ($ref['group_manager']) ? preg_split('/,/', $ref['group_manager']) : array();
            }
        } else {
            $this->username = 'not identified';
        }
    }
    
    public function params($name) {
        return ifisset($_SESSION,$name,[]);
    }
    
    static public function get_user_id($who) {
        global $DB;
        $sel = $DB -> prepare("SELECT * FROM `users` WHERE `user`=:login OR `id`=:login");
        $sel -> execute(array('login'=>$who));
        if($sel -> rowCount() > 0){
            $d = $sel -> fetch();
            return intval($d['id']);
        }else{
            return NULL;
        }
    }


    static public function short_fio($fio) {
        $fios = preg_split('/\s+/', $fio);
        $res = $fios[0];
        if(isset($fios[1])){
            $res .= ' ' .mb_substr($fios[1], 0, 1, 'UTF-8').'.';
        }
        if (isset($fios[2])) {
            $res .= '' . mb_substr($fios[2], 0, 1, 'UTF-8') . '.';
        }
        return $res;
    }

    public static function auth_user() {
        global $DB;
        $login = filter_input(INPUT_POST, 'login');
        $password = filter_input(INPUT_POST, 'password');
        $sel = $DB->prepare("SELECT * FROM `users` WHERE `user`=:login AND `pass`=MD5(:password)");
        $sel->execute(array('login' => $login, 'password' => $password));
        if ($sel->rowCount() > 0) {
            $d = $sel->fetch();
            if ($d['enable'] > 0) {
                $_SESSION['id'] = $d['id'];
                return TRUE;
            }
        }
        return FALSE;
    }
    
     public static function deauth_user() {
        global $DB;
        if(isset($_SESSION['id'])){
            unset($_SESSION['id']);
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
            session_destroy();
            return TRUE;
        }
        return FALSE;
    }

    public function is($group) {
        if (isset($this->groups[$group]) OR in_array($group, $this->groups)) {
            return TRUE;
        }
        return FALSE;
    }
    
    static public function edit_form($id) {
        global $USER,$DIV;
        $form = array();
        $form_id = 'edit_user';
        $data = array();
        if($id > 0){
            $data = new User($id);
        }
        $form[] = html::hidden('action', 'save_user');
        $form[] = html::hidden('user_id', $id);
        $form[] = html::form_item('ФИО', html::input('text', 'user_fio', ifisset($data, 'fio'),array('placeholder'=>'Иванов Иван Иванович')),1);
        $form[] = html::form_item('Инфо', html::input('text', 'user_info', ifisset($data, 'info'),array('placeholder'=>'Примечание')),2);
        $form[] = html::form_item('Логин', html::input('text', 'user_login', ifisset($data, 'username')),1);
        $form[] = html::table(FALSE, array(array(
            html::form_item('Пароль', html::input('password', 'user_pass', ''),0),
            html::form_item('Пароль (еще раз)', html::input('password', 'user_pass_too', ''),0)
        )));
        $form[] = html::form_item('Email', html::input('text', 'user_email', ifisset($data, 'email'),array('placeholder'=>'mymail@mysite.org')),1);
        $form[] = html::form_item('Организация', html::select('user_div', $DIV->get_divs(), ifisset($data, 'div_id')),1);
        $form[] = html::form_item('Активен', html::radios('user_enable', array(0=>'Нет',1=>'Да'), ifisset($data, 'enable',1), array(), TRUE),1);
        if(in_array('root', $USER->groups)){
            $roles = get_sql_array('roles','*');
            $values = array();
            foreach($roles as $i){
                $values[$i['value']] = $i['name'];
            }
            $form[] = html::form_item('Роли', html::checkboxes('user_roles', $values, ifisset($data, 'groups',array())),2);
        }
        $form[] = html::submit('Сохранить', $form_id);
        return html::form(implode("",$form), $form_id);
    }
    
    static public function save() {
        global $DB;
        $valid = self::valid_form();
        if($valid !== FALSE){
            $sq = $DB -> prepare($valid['sql']);
            $sq -> execute($valid['data']);
            if($sq -> rowCount() > 0){
                return TRUE;
            }else{
                debug($DB -> errorInfo());
                set_error('Ошибка запроса');
            }
        }
        return FALSE;
    }
    
    static public function valid_form() {
        global $USER;
        $fields = array(
            'FIO' => array('value'=>'user_fio','name'=>'ФИО'),
            'info' => array('value'=>'user_info','name'=>'Инфо'),
            'user' => array('value'=>'user_login','name'=>'Логин'),
            'email' => array('value'=>'user_email','name'=>'Email'),
            'div_id' => array('value'=>'user_div','name'=>t('Организация')),
            'enable' => array('value'=>'user_enable','name'=>'Активен'),
        );
        $f = $v = $data = array();
        $pass = TRUE;
        $id = filter_input(INPUT_POST, 'user_id',FILTER_VALIDATE_INT);
        $user = array();
        if($id > 0){
            $user = new User($id);
        }else{
            $fields['pass'] = array('value'=>'user_pass','name'=>'Пароль');
        }
        foreach($fields as $key=>$field){
            switch($key){
                case 'user':
                    $value = filter_input(INPUT_POST, $field['value']);
                    $value = trim($value);
                    $check_login = FALSE;
                    if(!empty($value)){
                        if($id > 0){
                            if($value !== ifisset($user, 'username')){
                                $check_login = TRUE;
                            }
                        }else{
                            $check_login = TRUE;
                        }
                        if($check_login){
                            if(!self::check_field('user',$value)){
                                set_error_field($field['value']);
                                set_error(sprintf('Ошибка в поле "%s" - значение занято',$field['name']));
                                $pass = FALSE;
                            }
                        }
                    }
                    break;
                case 'email':
                    $value = filter_input(INPUT_POST, $field['value'],FILTER_VALIDATE_EMAIL);
                    $value = trim($value);
                    $check_mail = FALSE;
                    if(!empty($value)){
                        if($id > 0){
                            if($value !== ifisset($user, 'email')){
                                $check_mail = TRUE;
                            }
                        }else{
                            $check_mail = TRUE;
                        }
                        if($check_mail){
                            if(!self::check_field('email',$value)){
                                set_error_field($field['value']);
                                set_error(sprintf('Ошибка в поле "%s" - значение занято',$field['name']));
                                $pass = FALSE;
                            }
                        }
                    }
                    break;
                case 'div_id':
                case 'enable':
                    $value = filter_input(INPUT_POST, $field['value'],FILTER_VALIDATE_INT);
                    break;
                default :
                    $value = filter_input(INPUT_POST, $field['value']);
            }
            $value = trim($value);
            if(mb_strlen($value) == 0 OR is_null($value)){
                set_error_field($field['value']);
                set_error(sprintf('Ошибка в поле "%s"',$field['name']));
                $pass = FALSE;
            }elseif($key !== 'pass'){
                $data[$key] = $value;
                if($id > 0){
                    $f[] = sprintf('`%s`=:%s',$key,$key);
                }else{
                    $f[] = sprintf('`%s`',$key);
                    $v[] = sprintf(':%s',$key);
                }
            }
        }
        $user_pass = filter_input(INPUT_POST, 'user_pass');
        $user_pass_too = filter_input(INPUT_POST, 'user_pass_too');
        if(!empty($user_pass) OR !empty($user_pass_too)){
            if($user_pass !== $user_pass_too){
                set_error_field('user_pass');
                set_error_field('user_pass_too');
                set_error('Введенные пароли не совпадают.');
                $pass = FALSE;
            }else{
                if(mb_strlen($user_pass)<8){
                    set_error_field('user_pass');
                    set_error('Пароль менее 8 символов.');
                    $pass = FALSE;
                }else{
                    if(!preg_match("/\w/u", $user_pass) OR !preg_match("/\d/", $user_pass)){
                        set_error_field('user_pass');
                        set_error_field('user_pass_too');
                        set_error('Введенный пароль не безопасен.');
                        $pass = FALSE;
                    }else{
                        $data['pass'] = md5($user_pass);
                        if($id > 0){
                            $f[] = '`pass`=:pass';
                        }else{
                            $f[] = '`pass`';
                            $v[] = ':pass';
                        }
                    }
                }
            }
        }
        $user_roles = ifisset($_POST, 'user_roles', array());
        if(in_array('root', $USER->groups)){
            if($id > 0){
                $f[] = '`groups`=:groups';
            }else{
                $f[] = '`groups`';
                $v[] = ':groups';
            }
            $data['groups'] = implode(",",$user_roles);
            if(in_array('admin', $user_roles)){
                if($id > 0){
                    $f[] = "`ticket_groups`='*'";
                    $f[] = "`group_manager`='*'";
                }else{
                    $f[] = "`ticket_groups`";
                    $f[] = "`group_manager`";
                    $v[] = "'*'";
                    $v[] = "'*'";
                }
            }
        }else{
            if($id == 0){
                $data['groups'] = '';
            }
        }
        if($pass){
            if($id > 0){
                $data['id'] = $id;
                $sql = sprintf("UPDATE `users` SET %s WHERE `id`=:id",implode(", ",$f));
            }else{
                $sql = sprintf("INSERT INTO `users`(%s) VALUES (%s)",implode(", ",$f),implode(", ",$v));
            }
            return array('data'=>$data,'sql'=>$sql);
        }
        return FALSE;
    }
    
    static public function check_field($field,$value) {
        global $DB;
        $sel = $DB -> prepare("SELECT * FROM `users` WHERE `".$field."`=:field");
        $sel -> execute(array('field'=>$value));
        if($sel -> rowCount() > 0){
            return FALSE;
        }
        return TRUE;
    }
}