<?php

class div{
    public $id = 0;
    public $name = NULL;
    public $token = NULL;
    
    private $smtp_server = NULL;
    private $smtp_secure = NULL;
    private $smtp_port = NULL;
    private $smtp_user = NULL;
    private $smtp_password = NULL;
    
    private $users = array();
    private $korpus = array();
    
    private $botname = NULL;
    private $botlogin = NULL;
    private $bottoken = NULL;
    private $botdefgroup = 0;
    
    public $timezone = 0;
    
    public function __construct($id) {
        global $DB,$CONFIG,$USER;
        if($id > 0){
            $this->id = $id;
            $sel = $DB->prepare("SELECT * FROM `divs` WHERE `id`=:div_id");
            $sel->execute(array('div_id'=>$id));
            if($sel -> rowCount() > 0){
                $sel = $sel->fetch();
                $CONFIG['name'] = $sel['name'];
                $this->name = $sel['name'];
                $this->token = $sel['token'];
                $this->smtp_server = $sel['smtp_server'];
                $this->smtp_secure = $sel['smtp_secure'];
                $this->smtp_port = $sel['smtp_port'];
                $this->smtp_user = $sel['smtp_user'];
                $this->smtp_password = $sel['smtp_password'];
                $this->botname = $sel['botname'];
                $this->botlogin = $sel['botlogin'];
                $this->bottoken = $sel['bottoken'];
                $this->botdefgroup = $sel['botdefgroup'];
                $this->timezone = $sel['timezone'];
            }
            $sel_k = $DB->prepare("SELECT * FROM `div_korpus` WHERE `div_id`=:div_id ORDER BY `name`");
            $sel_k->execute(array('div_id'=>$id));
            $this->korpus = $sel_k -> fetchAll();
            if(in_array('root', $USER->groups)){
                $sel_users = $DB->prepare("SELECT * from `users` ORDER BY `FIO`");
                $sel_users->execute();
            }else{
                $sel_users = $DB->prepare("SELECT U.* from `users` U, `divs` D WHERE U.div_id=D.id AND D.id = :div_id ORDER BY U.FIO");
                $sel_users->execute(array('div_id'=>$id));
            }
            while ($u = $sel_users->fetch()) {
                $this->users[$u['id']] = (array) new User($u['id']);
            }
        }
        $this->user_system();
    }
    
    public function get_smtp() {
        return array(
            'server'=> $this->smtp_server,
            'secure'=> $this->smtp_secure,
            'port'=> $this->smtp_port,
            'user'=> $this->smtp_user,
            'password'=> $this->smtp_password,
        );
    }
    
    public function get_bot() {
        return array(
            'botname'=> $this->botname,
            'botlogin'=> $this->botlogin,
            'bottoken'=> $this->bottoken,
            'botdefgroup'=> $this->botdefgroup,
        );
    }
    
    public function get_users() {
        return $this->users;
    }
    
    public function get_korpus($with_id = FALSE) {
        $res = array();
        foreach($this->korpus as $k){
            if($with_id){
                $k['name'] = $k['id'].' - '.$k['name'];
            }
            $res[$k['id']] = $k['name'];
        }
        return $res;
    }
    
    public function get_areas($div_id) {
        global $DB;
        $res = array();
        $sel = $DB -> prepare("SELECT * FROM `ticket_groups` WHERE `div_id`=:div_id");
        $sel -> execute(array('div_id'=>$div_id));
        while($i = $sel -> fetch()){
            $res[$i['id']] = $i['name_group'];
        }
        return $res;
    }
    
    public function get_korpus_short() {
        $res = array();
        foreach($this->korpus as $k){
            $res[$k['id']] = $k['short_name'];
        }
        return $res;
    }
    
    public function get_divs() {
        global $USER;
        $alldivs = get_sql_array('divs');
        $divs = array();
        if(in_array('root', $USER->groups)){
            $divs = $alldivs;
        }elseif(isset($alldivs[$USER->div_id])){
            $divs[$USER->div_id] = $alldivs[$USER->div_id];
        }
        return $divs;
    }
    
    public function get_groups() {
        global $USER;
        if(in_array('root', $USER->groups)){
            $res = get_sql_array('ticket_groups', 'name_group', FALSE, FALSE, 'name_group');
        }else{
            $res = get_sql_array('ticket_groups', 'name_group', 'div_id='.$USER->div_id, FALSE, 'name_group');
        }
        if(!$res){
            $res = array();
        }
        return $res;
    }
    
    private function user_system(){
        //virtual users
        $this->users[0] = array(
            'username' => 'unknown',
            'id' => 0,
            'level' => 0,
            'enable' => 1,
            'sess' => NULL,
            'fio' => 'Нет отвественного',
            'fios' => 'Нет отвественного',
            'email' => NULL,
            'ticket_groups' => array(),
            'group_manager' => array(),
            'groups' => array(),
        );
    }
    
    public function get_user_fio($i) {
        $user_who = 'UNKNOWN';
        $id = $this->get_user_id($i);
        if ($id !== FALSE) {
            $user_who = $this->users[$id]['fios'];
        }
        return $user_who;
    }

    public function get_user_id($i) {
        $user_who = FALSE;
        if (isset($this->users[$i])) {
            $user_who = $i;
        } else {
            foreach($this->users as $u){
                if($i == $u['username']){
                    $user_who = $u['id'];
                break;
                }
            }
        }
        return $user_who;
    }
    
    static public function edit_form($id) {
        global $USER,$CONFIG;
        $div = new div($id);
        $form = $form_smtp = $form_bot = array();
        $form[] = html::hidden('action', 'save_org');
        $form[] = html::hidden('div_id', $id);
        $form[] = html::form_item('Наименование', html::input('text', 'name', ifisset($div, 'name')),1);
        
        $tm = timezone_identifiers_list(DateTimeZone::PER_COUNTRY, 'RU');
        $zons = [];
        foreach($tm as $i){
            $zons[$i] = t($i);
        }
        $form[] = html::form_item('Часовой пояс', html::select('timezone', $zons, ifisset($div, 'timezone'),[],'Выберите'),1);
        
        $smtp = $div->get_smtp();
        $form_smtp[] = html::form_item('SMTP server', html::input('text', 'smtp_server', ifisset($smtp, 'server')),2);
        $secure = array(
            'none' => 'Нет',
            'ssl' => 'SSL',
            'tls' => 'TLS',
        );
        $form_smtp[] = html::form_item('SMTP secure', html::radios('smtp_secure', $secure, ifisset($smtp, 'secure','ssl'),array(),1),2);
        $form_smtp[] = html::form_item('SMTP port', html::input('number', 'smtp_port', ifisset($smtp, 'port',465)),2);
        $form_smtp[] = html::form_item('SMTP user', html::input('text', 'smtp_user', ifisset($smtp, 'user')),2);
        $form_smtp[] = html::form_item('SMTP password', html::input('text', 'smtp_password', ifisset($smtp, 'password')),2);
        $form[] = html::fielset('SMTP', implode("",$form_smtp));
        
        $bot = $div->get_bot();
        $form_bot[] = html::form_item('Имя', html::input('text', 'botname', ifisset($bot, 'botname')),2);
        $form_bot[] = html::form_item('Логин', html::input('text', 'botlogin', ifisset($bot, 'botlogin')),2);
        $form_bot[] = html::form_item('Токен', html::input('text', 'bottoken', ifisset($bot, 'bottoken')),2);
        $form_bot[] = html::form_item('Группа для создания заявки', html::radios('botdefgroup', $div->get_areas($id) , ifisset($bot, 'botdefgroup')));
        $form_bot[] = html::form_item('Регистрация WebHook', html::input("text", 'reg',
                sprintf('https://api.telegram.org/bot%s/setWebhook?url=https://%s/bot/?%s',
                        (!empty($bot['bottoken']))?$bot['bottoken']:'ЗпполнитеТокен',
                        $CONFIG['server'],
                        (!empty($bot['botname']))?$bot['botname']:'ЗаполнитеИмя'), ['readonly'=>'readonly']));
        $form[] = html::fielset('Telegram BOT', implode("",$form_bot));
        $form[] = html::fielset('Внешние сервисы', html::form_item('Токен', html::input('text', 'accesstoken', ifisset($div, 'token'), ['readonly'=>'readonly'])));
        $form_id = 'edit_org';
        $form[] = html::submit('Сохранить', $form_id);
        return html::form(implode("",$form), $form_id);
    }
    
    static public function save() {
        global $DB,$err_fiedls;
        $valid = self::valid_form();
        if($valid !== FALSE){
            $sq = $DB -> prepare($valid['sql']);
            $sq -> execute($valid['data']);
            if($sq -> rowCount() > 0){
                db("UPDATE `divs` SET `token`=MD5(`id`) WHERE `token` IS NULL;");
                return TRUE;
            }else{
                set_error('Ошибка запроса. Возможно Вы ничего не поменяли.');
            }
        }
        return FALSE;
    }
    
    static public function valid_form() {
        global $USER;
        $fields = array(
            'name' => array('value'=>'name','name'=>'Наименование'),
            'timezone' => array('value'=>'timezone','name'=>'Часовой пояс'),
            'smtp_server' => array('value'=>'smtp_server','name'=>'SMTP server'),
            'smtp_secure' => array('value'=>'smtp_secure','name'=>'SMTP secure'),
            'smtp_port' => array('value'=>'smtp_port','name'=>'SMTP port'),
            'smtp_user' => array('value'=>'smtp_user','name'=>'SMTP user'),
            'smtp_password' => array('value'=>'smtp_password','name'=>'SMTP password'),
            'botname' => array('value'=>'botname','name'=>'Имя бота'),
            'botlogin' => array('value'=>'botlogin','name'=>'Логин бота'),
            'bottoken' => array('value'=>'bottoken','name'=>'Токен бота'),
            'botdefgroup' => array('value'=>'botdefgroup','name'=>'Группа для создания заявки'),
        );
        $f = $v = $data = array();
        $pass = TRUE;
        $id = filter_input(INPUT_POST, 'div_id',FILTER_VALIDATE_INT);
        $data_t = array();
        if($id > 0){
            $data_t = new div($id);
        }
        foreach($fields as $key=>$field){
            switch($key){
                case 'name':
                    $value = filter_input(INPUT_POST, $field['value']);
                    $value = trim($value);
                    $value = htmlspecialchars($value);
                    $check = FALSE;
                    if(!empty($value)){
                        if($id > 0){
                            if($value !== ifisset($data_t, 'name')){
                                $check = TRUE;
                            }
                        }else{
                            $check = TRUE;
                        }
                        if($check){
                            if(!self::check_field('name',$value)){
                                set_error_field($field['value']);
                                set_error(sprintf('Ошибка в поле "%s" - значение занято',$field['name']));
                                $pass = FALSE;
                            }
                        }
                    }
                    break;
                default :
                    $value = filter_input(INPUT_POST, $field['value']);
                    $value = trim($value);
                    $value = htmlspecialchars($value);
            }
            if($key == 'botdefgroup' AND mb_strlen($value) == 0){
                $value = 0;
            }
            if($key == 'name'){
                if(mb_strlen($value) == 0 OR is_null($value)){
                    set_error_field($field['value']);
                    set_error(sprintf('Ошибка в поле "%s"',$field['name']));
                    $pass = FALSE;
                }
            }
            if($pass){
                $data[$key] = $value;
                if($id > 0){
                    $f[] = sprintf('`%s`=:%s',$key,$key);
                }else{
                    $f[] = sprintf('`%s`',$key);
                    $v[] = sprintf(':%s',$key);
                }
            }
        }
        if($pass){
            if($id > 0){
                $data['id'] = $id;
                $sql = sprintf("UPDATE `divs` SET %s WHERE `id`=:id",implode(", ",$f));
            }else{
                $sql = sprintf("INSERT INTO `divs`(%s) VALUES (%s)",implode(", ",$f),implode(", ",$v));
            }
            return array('data'=>$data,'sql'=>$sql);
        }
        return FALSE;
    }
    
    static public function check_field($field,$value) {
        global $DB;
        $sel = $DB -> prepare("SELECT * FROM `divs` WHERE `".$field."`=:field");
        $sel -> execute(array('field'=>$value));
        if($sel -> rowCount() > 0){
            return FALSE;
        }
        return TRUE;
    }
}