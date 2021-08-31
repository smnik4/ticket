<?php

date_default_timezone_set('Asia/Omsk');

class tlgbot {

    public $name = 'Ticket BOT';
    public $apiurl = 'https://api.telegram.org/bot';
    public $token = 0;
    public $chat_id = 0;
    public $user_name = '';
    public $input_text = '';
    public $user_id = 0;
    public $auth = FALSE;
    public $calback = FALSE;
    public $calback_action = FALSE;
    public $calback_id = 0;
    public $last_action = NULL;
    public $last_action_id = 0;
    public $def_area = 1;
    public $def_user_id = 0;
    public $upload = __DIR__ .'/files';
    public $div = FALSE;

    public function __construct($div_id,$user_id = 0) {
        global $CONFIG;
        $sel = db("SELECT * FROM `divs` WHERE `id`=:id OR `botname`=:id", ['id'=>$div_id]);
        if($sel->rowCount() > 0){
            $d = $sel -> fetch();
            $this->div = new div($d['id']);
            $bot = $this->div->get_bot();
            $this->name = $bot['botname'];
            $this->token = $bot['bottoken'];
            $this->def_area = $bot['botdefgroup'];
            $this->apiurl .= $this->token;
        }else{
            header('HTTP/1.0 404 Not Found', true, 404);
            return FALSE;
        }
        $this->upload = $CONFIG['PATH']['CFG']['doc_root'];
        $RM = (isset($_SERVER['REQUEST_METHOD']))?$_SERVER['REQUEST_METHOD']:'CLI';
        if ($RM == 'POST') {
            $data = file_get_contents('php://input');
            $data = json_decode($data, true);
            //file_put_contents("dump/" . time() . '.text', print_r($data, true));
            if (isset($data['callback_query'])) {
                $data = $data['callback_query'];
                $this->calback = json_decode($data['data'], TRUE);
                $this->calback_id = intval($data['id']);
                $this->calback_action = (isset($this->calback['action'])) ? $this->calback['action'] : FALSE;
            }
            if (isset($data['message']['from']['id'])) {
                $this->chat_id = $data['message']['chat']['id'];
            }
            if (isset($data['message']['from']['username'])) {
                $this->user_name = $data['message']['chat']['username'];
            }
            if (isset($data['message']['text'])) {
                $this->input_text = $data['message']['text'];
            }
            if ($this->find_local() AND $this->user_id > 0) {
                if ($this->calback !== FALSE) {
                    $answer = 'Эмм, тут что-то должно быть(';
                    switch ($this->calback_action) {
                        case 'menu':
                            $answer = 'Меню';
                            $this->start();
                            $this->last_action_id = 0;
                            $this->lastactionset(NULL);
                            break;
                        case 'new':
                            $answer = 'Создаем заявку';
                            if($this->def_area > 0){
                                $this->sendMessage('Впишите заголовок заявки');
                                $this->last_action_id = 0;
                                $this->lastactionset($this->calback_action);
                            }else{
                                $this->last_action_id = 0;
                            $this->lastactionset(NULL);
                                $this->sendMessage('Укажите в настройках '.t('организации').' группу по умолчанию');
                            }
                            break;
                        case 'my':
                            $this->tikets($this->calback_action);
                            $answer = 'Мои заявки';
                            break;
                        case 'free':
                            $this->tikets($this->calback_action);
                            $answer = 'Свободные заявки';
                            $this->last_action_id = 0;
                            $this->lastactionset(NULL);
                            break;
                        case 'select':
                            $id = (isset($this->calback['id'])) ? $this->calback['id'] : 0;
                            $this->ticket_view($id);
                            $this->last_action_id = $id;
                            $this->lastactionset('select');
                            $answer = 'Заявка #' . $id;
                            break;
                        case 'givemy':
                            $id = (isset($this->calback['id'])) ? $this->calback['id'] : 0;
                            $this->ticket_tomy($id);
                            $this->ticket_view($id);
                            $this->last_action_id = 0;
                            $this->lastactionset(NULL);
                            $answer = 'Приняли заявку в работу';
                            break;
                        case 'close':
                            $id = (isset($this->calback['id'])) ? $this->calback['id'] : 0;
                            $this->ticket_close($id);
                            $this->ticket_view($id);
                            $answer = 'Закрыли заявку';
                            break;
                        case 'info':
                            $answer = 'Info';
                            $this->last_action_id = 0;
                            $this->lastactionset(NULL);
                            $this->info();
                            break;
                        default :
                            $answer = 'Неизвестная команда';
                            $this->last_action_id = 0;
                            $this->lastactionset(NULL);
                    }
                    $this->request("answerCallbackQuery", array(
                        'callback_query_id' => $this->calback_id,
                        'text' => $answer,
                    ));
                } else {
                    if(isset($data['message']['photo'])){
                        $caption = ifisset($data['message'], 'caption');
                        if($this->last_action_id == 0){
                            $this->sendMessage('Прием фото не в заявку');
                        }else{
                            $this->sendMessage('Прием фото в заявку #'.$this->last_action_id);
                        }
                        if(!$this->upload_file($data['message']['photo'],'photo',$caption)){
                            //return TRUE;
                        }
                    }
                    if(isset($data['message']['document'])){
                        $caption = ifisset($data['message'], 'caption');
                        if($this->last_action_id == 0){
                            $this->sendMessage('Прием файла не в заявку');
                        }else{
                            $this->sendMessage('Прием файла в заявку #'.$this->last_action_id);
                        }
                        if(!$this->upload_file($data['message']['document'],'document',$caption)){
                            //return TRUE;
                        }
                    }
                    if(isset($data['message']['video'])){
                        $caption = ifisset($data['message'], 'caption');
                        if($this->last_action_id == 0){
                            $this->sendMessage('Прием видео не в заявку');
                        }else{
                            $this->sendMessage('Прием видео в заявку #'.$this->last_action_id);
                        }
                        if(!$this->upload_file($data['message']['video'],'video',$caption)){
                            //return TRUE;
                        }
                    }
                    if (in_array($this->input_text, array('menu', 'Menu', 'Меню', 'меню'))) {
                        $this->last_action_id = 0;
                        $this->last_action = NULL;
                        $this->lastactionset(NULL);
                        $this->input_text = '';
                    }
                    if (!empty($this->last_action)) {
                        switch ($this->last_action) {
                            case 'new':
                                if (!empty($this->input_text)) {
                                    $this->last_action_id = db("INSERT INTO `tickets`(`area`, `head`, `korpus`, `kab`, `state`,`user_id`) "
                                            . "VALUES (:area, :head, 0, '', 0,:user_id)",
                                            ['area' => $this->def_area, 'head' => $this->input_text, 'user_id' => $this->def_user_id]);
                                    $this->lastactionset('new_kab');
                                    db("INSERT INTO `tickets_event`(`ticket_id`, `dt`, `user_id`, `mes`, `status`) "
                                            . "VALUES (:ticket_id, :dt, :user_id, 'Нет ответственного', 1)",
                                            ['ticket_id' => $this->last_action_id, 'dt' => time(), 'user_id' => $this->user_id]);
                                    $this->sendMessage('Впишите '.t('корпус').' и кабинет: N NNN');
                                    $allow_korp = $this->div->get_korpus(TRUE);
                                    $this->sendMessage(sprintf("<b>%s</b>\n%s",t('Доступные корпуса'), implode("\n", $allow_korp)),['parse_mode' => 'HTML']);
                                } else {
                                    $this->sendMessage('Впишите заголовок заявки');
                                }
                                break;
                            case 'new_kab':
                                if (!empty($this->input_text)) {
                                    if (preg_match("/^(\d{1,4})\s([\d\s\w]{1,100})$/ui", $this->input_text, $f)) {
                                        $allow_korp = $this->div->get_korpus();
                                        if (isset($allow_korp[$f[1]])) {
                                            db("UPDATE `tickets` SET `korpus`=:korpus,`kab`=:kab WHERE `id`=:id",
                                                    ['korpus' => $f[1], 'kab' => $f[2], 'id' => $this->last_action_id]);
                                            $this->lastactionset('new_desc');
                                            $this->sendMessage('Впишите описание заявки');
                                        } else {
                                            $this->sendMessage(t('Неизвестный корпус'));
                                            $allow_korp = $this->div->get_korpus(TRUE);
                                            $this->sendMessage(sprintf("<b>%s</b>\n%s",t('Доступные корпуса'), implode("\n", $allow_korp)),['parse_mode' => 'HTML']);
                                        }
                                    } else {
                                        $this->sendMessage('Не понял ответ: N NNN');
                                    }
                                } else {
                                    $this->sendMessage('Впишите '.t('корпус').' и кабинет');
                                }
                                break;
                            case 'new_desc':
                                if (!empty($this->input_text)) {
                                    db("INSERT INTO `tickets_event`(`ticket_id`, `dt`, `user_id`, `mes`, `status`) "
                                            . "VALUES (:ticket_id, :dt, :user_id, :mes, 0)",
                                            ['ticket_id' => $this->last_action_id, 'dt' => time(), 'user_id' => $this->user_id, 'mes' => $this->input_text]);
                                    $this->sendMessage('Заявка создана: #' . $this->last_action_id);
                                    $this->last_action_id = 0;
                                    $this->lastactionset(NULL);
                                    $this->start();
                                } else {
                                    $this->sendMessage('Впишите описание заявки');
                                }
                                break;
                            case 'select':
                                if (!empty($this->input_text) AND !  preg_match("/^\//ui", $this->input_text)) {
                                    $this->ticket_event($this->last_action_id, $this->input_text);
                                }
                                $this->ticket_view($this->last_action_id);
                                break;

                            default :
                        }
                    } else {
                        $id = 0;
                        /*if(preg_match("/^(M|m|М|м)\d*$|^0001\d*$/ui", $this->input_text)){
                            $id = $this->input_text;
                            $this->input_text = 'show_in';
                        }else*/
                        if(preg_match("/^\d\d*$/ui", $this->input_text)){
                            $id = $this->input_text;
                            $this->input_text = 'show_tiket';
                        }
                        /*elseif(preg_match("/^([acdegxzw][\w\d\-]{1,50}|[abcdef0123456789]{2}\:[abcdef0123456789]{2})$/ui", $this->input_text)){
                            $id = $this->input_text;
                            $this->input_text = 'host_view';
                        }*/
                        $this->last_action_id = 0;
                        $this->lastactionset(NULL);
                        switch ($this->input_text) {
                            case 'host_view':
                                $this->host_view($id);
                                break;
                            case 'show_tiket':
                                $this->ticket_view($id);
                                break;
                            case 'show_in':
                                $this->inventory_view($id);
                                break;
                            case '/':
                            case '/start':
                                $this->start();
                                break;
                            /*case 'лошара':
                                $this->sendMessage('написать @Zardonic ??');
                                break;*/
                            default :
                                if (!empty($this->input_text)) {
                                    $func = $this->input_text;
                                    $funcarg = '';
                                    if(preg_match("/^([\w\d\_]{1,200})\s(.*)$/ui", $func,$ff)){
                                        $func = $ff[1];
                                        $funcarg = $ff[2];
                                    }
                                    $funcres = FALSE;
                                    if(function_exists($func)){
                                        if(!empty($funcarg)){
                                            $funcres = $func($funcarg);
                                        }else{
                                            $funcres = $func();
                                        }
                                        $funcres = preg_replace(['<br/>','<br>'], ["\n","\n"], $funcres);
                                        $funcres = strip_tags($funcres, "<b><strong><i><em><u><ins><s><strike><del><code><a><pre>");
                                    }
                                    //file_put_contents("dump/" . time() . '_func.text', print_r([$func,$funcarg,$funcres], true));
                                    if(is_string($funcres)){
                                        $this->sendMessage($funcres);
                                    }else{
                                        file_put_contents($this->upload.'/bot/dump/unknown.commands', sprintf("%s %s %s\n", $this->user_id, $this->user_name,$this->input_text),FILE_APPEND);
                                        $this->sendMessage('Команда не найдена');
                                    }
                                    
                                }
                                $this->start();
                        }
                    }
                }
            }
        }elseif($user_id > 0){
            $this->user_id = $user_id;
            $sel = db("SELECT * FROM `users` WHERE `id`=:id",['id'=>$user_id]);
            $u = $sel -> fetch();
            if(!empty($u['tlg_name'])){
                $this->user_name = $u['tlg_name'];
                $this->chat_id = $u['tlg_chat_id'];
            }
        }
        //file_put_contents("dump/" . time() . '_class.text', print_r($this, true));
    }
    
    private function upload_file($file_input,$type,$caption = '') {
        global $CONFIG;
        $file = NULL;
        $doc_name = 'файл';
        if($type == 'photo'){
            $file = array_pop($file_input);
            $doc_name = 'фото';
        }
        if($type == 'document'){
            $file = $file_input;
        }
        if($type == 'video'){
            $file = $file_input;
            $doc_name = 'видео';
        }
        if(!empty($file)){
            $data = $this->request('getFile?file_id='.$file['file_id'], [], 'GET');
            if(isset($data['result']['file_path'])){
                $url = sprintf('https://api.telegram.org/file/bot%s/%s',
                        $this->token,
                        $data['result']['file_path']);
                $name= $data['result']['file_path'];
                $size= $data['result']['file_size'];
                $ext= 'dat';
                if(preg_match("/[\d\w\-\_]{1,200}\.(\w*)$/ui", $data['result']['file_path'],$f)){
                    $ext= mb_strtolower($f[1]);
                    $name= $f[0];
                }
                $file_data = file_get_contents($url);
                $cache = md5($file_data);
                $find = db("SELECT * FROM `tickets_attachment` WHERE `ticket_id`=:ticket_id AND `user_id`=:user_id AND `size`=:size AND `ext`=:ext AND `cache`=:cache", [
                                'ticket_id'=>$this->last_action_id,
                                'user_id'=>$this->user_id,
                                //'name'=>$name,
                                'size'=>$size,
                                'ext'=>$ext,
                                'cache'=>$cache,
                            ]);
                if($find ->rowCount() > 0){
                    $this->sendMessage('Файл загружен ранее.');
                    return FALSE;
                }
                if(!empty($file_data)){
                    $ins_file = db("INSERT INTO `tickets_attachment`(`ticket_id`, `message_id`, `user_id`, `name`, `size`, `ext`, `path`,`comment`,`cache`)"
                            . " VALUES (:ticket_id, 0, :user_id, :name, :size, :ext, '',:comment,:cache)",[
                                'ticket_id'=>$this->last_action_id,
                                'user_id'=>$this->user_id,
                                'name'=>$name,
                                'size'=>$size,
                                'ext'=>$ext,
                                'comment'=>$caption,
                                'cache'=>$cache,
                            ]);
                    if($ins_file > 0){
                        $path = sprintf('/files/tlg/%s/%s', $this->user_id,$this->last_action_id);
                        $full_path = $this->upload . $path;
                        if(!file_exists($full_path)){
                            if(!mkdir($full_path,0754,TRUE)){
                                $this->sendMessage('Ошибка получения файла. Не удалось создать директорию загрузки.');
                                db("DELETE FROM `tickets_attachment` WHERE `id`=:id",['id'=>$ins_file]);
                                return FALSE;
                            }
                        }
                        $path .= '/'.$ins_file;
                        $full_path = $this->upload . $path;
                        if(file_put_contents($full_path, $file_data)){
                            db("UPDATE `tickets_attachment` SET `path`=:path WHERE `id`=:id",['path'=>$path,'id'=>$ins_file]);
                            if($this->last_action_id > 0){
                                $mess = sprintf('Загрузил %s: %s http://%s/files/?file=%s',
                                        $doc_name,$name,$CONFIG['server'],$ins_file);
                                if(!empty($caption)){
                                    $mess .= "\n".$caption;
                                }
                                $this->ticket_event($this->last_action_id, $mess);
                            }
                            $this->sendMessage('Файл успешно сохранен.');
                            return TRUE;
                        }else{
                            $this->sendMessage('Ошибка получения файла. Не удалось записать файл.');
                            db("DELETE FROM `tickets_attachment` WHERE `id`=:id",['id'=>$ins_file]);
                        }
                    }else{
                        $this->sendMessage('Ошибка получения файла. Не смог вставить файл.');
                    }
                }else{
                    $this->sendMessage('Ошибка получения файла. Пустые даннеы файла.');
                }
                
                
            }else{
                $this->sendMessage('Ошибка получения файла. Телеграм не отдал инфо о файле');
            }
            //file_put_contents("dump/" . time() . '_file.text', print_r($data, true));
        }else{
            $this->sendMessage('Ошибка получения файла. Тип не опознан.');
        }
        return FALSE;
    }
    
    private function info() {
        $c = "<b>".$this->name."</b>\n";
        $c .= "<b>Что делаем:</b>\n";
        $c .= " - Добавляем заявки\n";
        $c .= " - Показываем открытые заявки\n";
        $c .= " - Принимаем заявки на в работу\n";
        $c .= " - Закрываем свои заявки\n";
        $c .= " - Пишем сообщения в заяки\n";
        $c .= " - Отправляем фото/файлы/видео в заявки\n";
        $c .= " - Ищем заявки (номер)\n";
        //$c .= " - Ищем хосты по маку (FF:FF) или имени (an...)\n";
        //$c .= " - Ищем инвентарные (М00.. или 0001..)\n";
        $keys = [];
        $keys[] = ['text' => 'Меню', 'callback_data' => json_encode(['action' => 'menu'])];
        $attr = ['parse_mode' => 'HTML'];
        if (count($keys) > 0) {
            $attr['disable_web_page_preview'] = false;
            $attr['reply_markup'] = json_encode(array('inline_keyboard' => [$keys]));
        }
        $this->sendMessage($c, $attr);
    }

    public function lastactionset($name) {
        db("UPDATE `users` SET `tlg_la`=:tlg_la,`tlg_laid`=:tlg_laid WHERE `id`=:id",
                ['tlg_la' => $name, 'tlg_laid' => $this->last_action_id, 'id' => $this->user_id]);
    }

    private function find_local() {
        $sel = db("SELECT * FROM `users` WHERE `tlg_chat_id`=:tlg_chat_id AND `enable`=1",
                ['tlg_chat_id' => $this->chat_id]);
        if ($sel->rowCount() == 0 AND !empty($this->input_text)) {
            $sel = db("SELECT * FROM `users` WHERE `user` LIKE :inp OR `FIO` LIKE :inp AND `enable`=1",
                    ['inp' => $this->input_text]);
            $this->input_text = '';
        }
        if ($sel->rowCount() > 0) {
            $d = $sel->fetch();
            $this->user_id = intval($d['id']);
            /*if ($this->user_id == 30 AND random_int(1, 100) == 50) {
                $this->sendMessage('ВОЛОДЯ, ИДИ НАХЕР;)');
                return FALSE;
            }*/
            $this->auth = boolval($d['tlg_auth']);
            $this->last_action = $d['tlg_la'];
            $this->last_action_id = intval($d['tlg_laid']);
            if ($d['tlg_chat_id'] != $this->chat_id) {
                $upd = db("UPDATE `users` SET `tlg_chat_id`=:tlg_chat_id WHERE `id`=:id",
                    ['tlg_chat_id' => $this->chat_id, 'id' => $this->user_id]);
            }
            if ($d['tlg_name'] != $this->user_name) {
                $upd = db("UPDATE `users` SET `tlg_name`=:tlg_name WHERE `id`=:id",
                    ['tlg_name' => $this->user_name, 'id' => $this->user_id]);
            }
            if ($this->auth) {
                return TRUE;
            } else {
                if (!empty($this->input_text)) {
                    if ($d['pass'] === md5($this->input_text)) {
                        $upd = db("UPDATE `users` SET `tlg_auth`=1 WHERE `id`=:id", ['id' => $this->user_id]);
                        $this->sendMessage('Приветствую: ' . $d['FIO'] . ' - Вы авторизованы.');
                        $this->input_text = '';
                        return TRUE;
                    } else {
                        $this->sendMessage('Введенный пароль не верен.');
                    }
                    $this->input_text = '';
                }
                $this->sendMessage('Напишите Ваш пароль от системы заявок.');
            }
        } else {
            if (!empty($this->input_text)) {
                $this->sendMessage('Пользователь не найден.');
            }
            $this->sendMessage('Пожалуйста представьтесь. Напишите Ваш логин в системе заявок или ФИО.');
        }
        return FALSE;
    }

    private function start() {
        $keyboard = array(
            array(
                array('text' => 'Добавить', 'callback_data' => '{"action":"new"}'),
                array('text' => 'Мои', 'callback_data' => '{"action":"my"}'),
                array('text' => 'Свободные', 'callback_data' => '{"action":"free"}'),
                array('text' => 'Инфо', 'callback_data' => '{"action":"info"}'),
                //array('text' => 'Статистика', 'callback_data' => '{"action":"stat"}'),
            )
        );
        $this->sendMessage('Выберите действие', [
            'disable_web_page_preview' => false,
            'reply_markup' => json_encode(array('inline_keyboard' => $keyboard))
        ]);
    }
    
    private function inventory_view($id) {
        $id= preg_replace("/M|m|м/ui", "М", $id);
        $sel = db("SELECT * FROM `hosts_inventory` WHERE `number` LIKE :id", ['id' => $id]);
        $conts = array();
        while($t = $sel->fetch()){
            $t['description'] = str_replace("/", "|", $t['description']);
            $cont = sprintf("<b>Инвентарный: %s</b>\n", $t['number']);
            $cont .= sprintf("<b>Наименование: %s</b>\n", $t['name']);
            $cont .= sprintf("<b>МОЛ:</b> %s\n", $t['mol']);
            $cont .= sprintf("<b>Подразделение:</b> %s\n", $t['div']);
            $cont .= sprintf("<b>Описание:</b> %s\n", htmlentities($t['description']));
            $conts[] = $cont;
        }
        if($sel ->rowCount() == 0){
            $conts[] = 'Инвентарный не найден';
        }
        
        $keys = [];
        $keys[] = ['text' => 'Меню', 'callback_data' => json_encode(['action' => 'menu'])];
        $attr = ['parse_mode' => 'HTML'];
        if (count($keys) > 0) {
            $attr['disable_web_page_preview'] = false;
            $attr['reply_markup'] = json_encode(array('inline_keyboard' => [$keys]));
        }
        $this->sendMessage(implode("\n",$conts), $attr);
    }
    
    private function host_view($id){
        $sel = db("SELECT * FROM `hosts` WHERE `name` LIKE :name OR `mac` LIKE :mac ORDER BY `name`",
                ['name' => $id.'%','mac' => "%".$id]);
        $conts = array();
        while($t = $sel->fetch()){
            $t['devinfo'] = trim($t['devinfo']);
            $cont = sprintf("<b>%s</b>\n", $t['name']);
            $cont .= sprintf("<b>IP VLAN: %s %s</b>\n", $t['ip'], $t['vlan']);
            $cont .= sprintf("<b>MAC: %s</b>\n", $t['mac']);
            $cont .= sprintf("<b>Инвентарный:</b> %s\n", $t['in_number']);
            $cont .= sprintf("<b>Инфо:</b> %s\n", $t['devinfo']);
            $cont .= sprintf("<b>Виден:</b> %s\n", date("H:i d.m.Y",$t['lastseen']));
            $cont .= sprintf("<b>Примечание:</b> %s\n", $t['descr']);
            /*$cont .= sprintf("<b>Описание:</b> %s\n", htmlentities($t['description']));*/
            $conts[] = $cont;
        }
        if($sel ->rowCount() == 0){
            $conts[] = 'Хост не найден';
        }
        $keys = [];
        $keys[] = ['text' => 'Меню', 'callback_data' => json_encode(['action' => 'menu'])];
        $attr = ['parse_mode' => 'HTML'];
        if (count($keys) > 0) {
            $attr['disable_web_page_preview'] = false;
            $attr['reply_markup'] = json_encode(array('inline_keyboard' => [$keys]));
        }
        $this->sendMessage(implode("\n",$conts), $attr);
    }

    private function tikets($type) {
        $keys = [];
        $text = '??';
        $sql = 'SELECT * FROM `tickets` WHERE TRUE ';
        switch ($type) {
            case 'my':
                $sql .= " AND `status`=1 AND `user_id`=" . $this->user_id;
                $text = 'Мои заявки';
                break;
            case 'free':
                $sql .= " AND `status` IN (0,1) AND `user_id` IN (0)";
                $text = 'Свободные заявки';
                break;
        }
        $sel_a = db("SELECT `ticket_groups` FROM `users` WHERE `id`=:id", ['id' => $this->user_id]);
        $sel_a = $sel_a->fetch();
        if($sel_a['ticket_groups'] !== "*"){
            $sql .= sprintf(" AND `area` IN (%s)", $sel_a['ticket_groups']);
        }
        $sql .= " ORDER BY `dt` DESC LIMIT 10";
        $sel = db($sql);
        $kshort = $this->div->get_korpus_short();
        while ($t = $sel->fetch()) {
            $kk = '';
            if (!empty($t['korpus'])) {
                $kk = $kshort[$t['korpus']];
            }
            if (!empty($t['kab'])) {
                if (!empty($kk)) {
                    $kk .= ' ';
                }
                $kk .= $t['kab'];
            }
            if (mb_strlen($t['head']) > 40) {
                $t['head'] = mb_substr($t['head'], 0, 40) . '...';
            }
            $keys[] = [['text' => sprintf('#%s %s %s', $t['id'], $kk, $t['head']), 'callback_data' => json_encode(['action' => 'select', 'id' => $t['id']])]];
        }
        $keys[] = [['text' => 'Меню', 'callback_data' => json_encode(['action' => 'menu'])]];
        if (count($keys) > 1) {
            $this->sendMessage($text, [
                'disable_web_page_preview' => false,
                'reply_markup' => json_encode(array('inline_keyboard' => $keys))
            ]);
        } else {
            $this->sendMessage('Нет заявок');
        }
    }

    private function ticket_view($id) {
        $sel = db("SELECT * FROM `tickets` WHERE `id`=:id", ['id' => $id]);
        if($sel ->rowCount() == 0){
            $this->sendMessage('Заявка не найдена');
            $this->start();
            exit();
        }
        $t = $sel->fetch();
        $cont = sprintf("<b>Заявка #%s</b>\n = <b>%s</b> =", $t['id'], $t['head']);
        $kab = '';
        if(!empty($t['korpus'])){
            $kshort = $this->div->get_korpus_short();
            $kab = $kshort[$t['korpus']];
            if(!empty($t['kab'])){
                $kab .= ' '.$t['kab'];
            }
        }
        if(!empty($kab)){
            $cont .= sprintf("\n<b>Где:</b> %s", $kab);
        }
        $sel = db("SELECT E.*, U.tlg_name,U.FIO FROM `tickets_event` E,`users` U "
                . "WHERE E.user_id=U.id AND E.ticket_id = :id AND E.status=0 ORDER BY E.dt DESC", ['id' => $id]);
        if ($sel->rowCount() > 0) {
            $cont .= "\n<b>Сообщения:</b>";
            while ($m = $sel->fetch()) {
                if (!empty($m['tlg_name'])) {
                    $m['mes'] = sprintf('[@%s] %s', $m['tlg_name'], $m['mes']);
                }else{
                    $m['FIO'] = mb_substr($m['FIO'], 0, mb_strpos($m['FIO'], " ")+2).'.';
                    $m['mes'] = sprintf('[%s] %s', $m['FIO'], $m['mes']);
                }
                $cont .= sprintf("\n - %s", $m['mes']);
            }
        } else {
            $cont .= "\nСообщений нет";
        }
        $keys = [];
        if (($t['status'] == 1 OR $t['status'] == 0) AND in_array($t['user_id'], array(0))) {
            $keys[] = ['text' => 'Принять', 'callback_data' => json_encode(['action' => 'givemy', 'id' => $id])];
        }
        if ($t['status'] == 1 AND $t['user_id'] == $this->user_id) {
            $keys[] = ['text' => 'Выполнена', 'callback_data' => json_encode(['action' => 'close', 'id' => $id])];
        }
        $keys[] = ['text' => 'Меню', 'callback_data' => json_encode(['action' => 'menu'])];
        $attr = ['parse_mode' => 'HTML'];
        if (count($keys) > 0) {
            $attr['disable_web_page_preview'] = false;
            $attr['reply_markup'] = json_encode(array('inline_keyboard' => [$keys]));
        }
        $this->sendMessage($cont, $attr);
    }

    public function ticket_newmes($id, $mes) {
        $sel = db("SELECT * FROM `tickets` WHERE `id`=:id", ['id' => $id]);
        $t = $sel->fetch();
        $cont = sprintf("<b>Новое сообщение по заявке #%s</b>\n = <b>%s</b> =", $t['id'], $t['head']);
        $cont .= "\n<b>Сообщениe: </b>" . $mes;
        $keys = [];
        if (in_array($t['user_id'], array(0))) {
            $keys[] = ['text' => 'Принять', 'callback_data' => json_encode(['action' => 'givemy', 'id' => $id])];
        }
        $keys[] = ['text' => 'Открыть', 'callback_data' => json_encode(['action' => 'select', 'id' => $id])];
        $keys[] = ['text' => 'Меню', 'callback_data' => json_encode(['action' => 'menu'])];
        $attr = ['parse_mode' => 'HTML'];
        if (count($keys) > 0) {
            $attr['disable_web_page_preview'] = false;
            $attr['reply_markup'] = json_encode(array('inline_keyboard' => [$keys]));
        }
        $this->sendMessage($cont, $attr);
    }

    private function ticket_tomy($id) {
        $sel = db("SELECT * FROM `tickets` WHERE `id`=:id", ['id' => $id]);
        $t = $sel->fetch();
        if (in_array($t['user_id'], array(0))) {
            db("UPDATE `tickets` SET `user_id`=:user_id, `status`=1 WHERE `id`=:id", ['user_id' => $this->user_id, 'id' => $id]);
            db("INSERT INTO `tickets_event`(`ticket_id`, `dt`, `user_id`, `mes`, `status`) "
                    . "VALUES (:ticket_id, :dt, :user_id, 'Принял заявку', 1)",
                    ['ticket_id' => $id, 'dt' => time(), 'user_id' => $this->user_id]);
        } else {
            $this->sendMessage('Заявку #' . $id . ' уже приняли');
        }
    }

    private function ticket_close($id) {
        $sel = db("SELECT * FROM `tickets` WHERE `id`=:id", ['id' => $id]);
        $t = $sel->fetch();
        if ($t['status'] == 1 AND $t['user_id'] == $this->user_id) {
            db("UPDATE `tickets` SET `status`=2 WHERE `id`=:id", ['id' => $id]);
            db("INSERT INTO `tickets_event`(`ticket_id`, `dt`, `user_id`, `mes`, `status`) "
                    . "VALUES (:ticket_id, :dt, :user_id, 2, 1)",
                    ['ticket_id' => $id, 'dt' => time(), 'user_id' => $this->user_id]);
        } else {
            $this->sendMessage('Заявку #' . $id . ' нельзя закрыть');
        }
    }

    private function ticket_event($id, $mes) {
        db("INSERT INTO `tickets_event`(`ticket_id`, `dt`, `user_id`, `mes`, `status`) "
                . "VALUES (:ticket_id, :dt, :user_id, :mes, 0)",
                ['ticket_id' => $id, 'dt' => time(), 'user_id' => $this->user_id, 'mes' => $mes]);
    }

    private function sendMessage($message, $params = array()) {
        $response = array(
            'chat_id' => $this->chat_id,
            'text' => $message
        );
        if (count($params) > 0) {
            foreach ($params as $k => $i) {
                $response[$k] = $i;
            }
        }
        $this->request('sendMessage', $response);
    }

    private function request($method, $params = array(),$type='POST') {
        $ch = curl_init('https://api.telegram.org/bot' . $this->token . '/' . $method);
        if($type === 'POST'){
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $res = curl_exec($ch);
        $info = curl_getinfo($ch);
        //file_put_contents("dump/" . time() . '_data.text', print_r($params, true));
        //file_put_contents("dump/" . time() . '_info.text', print_r($info, true));
        curl_close($ch);
        return json_decode($res, TRUE);
    }

}
