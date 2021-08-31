<?php

class Ticket {

    public $id = 0;
    public $window_head = "Добавить заявку";
    public $head_view = "";
    public $head = NULL;
    public $info = NULL;
    public $area = NULL;
    public $area_name = NULL;
    public $state = 0;
    public $status = NULL;
    public $korpus = NULL;
    public $kab = NULL;
    public $position = NULL;
    public $inventory = NULL;
    public $user_id = 0;
    public $user = 'Нет ответственного';
    public $give_id = 0;
    public $new_user_id = 0;
    public $new_user_init = 0;
    public $is_editor = FALSE;
    public $user_sign = array();
    public $events = array();
    public $attachments = array();
    public $error = array();
    public $repair = array();
    public $repair_data = array();
    public $repair_komplekt = '';
    public $event_dt = NULL;
    public $event_time = NULL;

    public function __construct($id = 0) {
        global $DB_PDO, $DB, $USER, $USERS,$DIV;
        $this->preset_get();
        if ($id > 0) {
            $data = self::get_ticket_data($id);
            if ($data) {
                //$data = self::get_ticket_short($data);
                $this->id = (INT) $id;
                $this->area = $data['area'];
                $areas = self::get_area();
                $this->area_name = $areas[$this->area];
                $this->state = $data['state'];
                $this->status = $data['status'];
                $this->korpus = $data['korpus'];
                $this->kab = $data['kab'];
                $this->position = self::get_position($data['korpus'], $data['kab']);
                $this->head = $data['head'];
                $this->head_view = self::transform_head($data['head']);
                $this->window_head = t("Редактировать заявку");
                $this->inventory = $data['inventory'];
                $this->event_dt = $data['event_dt'];
                $this->event_time = $data['event_time'];
                $this->user_id = (INT) $data['user_id'];
                $this->user = $DIV->get_user_fio($data['user_id']);;
                $this->get_ticket_give();
                $this->is_editor = self::is_editor($data['user_id']);
                $this->user_sign = $this->get_ticket_sign($id);
                $this->repair = $this->get_ticket_repair($data['repair'], $data['priem']);
                $this->attachments = self::get_attachment($this->id, 0);
                $this->get_ticket_events();
                self::update_ticket_user($USER->id, $id);
            } else {
                set_error("Заявка с таким номером не найдена");
            }
        } else {
            $this->window_head = t("Создать заявку");
            $this->is_editor = TRUE;
        }
        $this->user = $this->user;
        $this->preset_edit();
    }

    private function preset_edit() {
        global $USER;
        if (($this->area == NULL OR empty($this->area)) AND in_array(1, $USER->ticket_groups)) {
            set_param('ticket_edit', 'area', 1);
            $this->area = 1;
        } else {
            set_param('ticket_edit', 'area', $this->area);
        }
        set_param('ticket_edit', 'korpus', $this->korpus);
        set_param('ticket_edit', 'kab', $this->kab);
        set_param('ticket_edit', 'inventory', $this->inventory);
        set_param('ticket_edit', 'event_dt', $this->event_dt);
        set_param('ticket_edit', 'event_time', $this->event_time);
    }

    static public function get_preset() {
        $res = array(
            'area' => 0,
            'korpus' => 0,
            'kab' => '',
            'inventory' => '',
        );
        foreach ($res as $key => $val) {
            if (isset($_SESSION['ticket_edit'][$key])) {
                $res[$key] = $_SESSION['ticket_edit'][$key];
            }
        }
        return $res;
    }

    private function preset_get() {
        global $DB;
        $this->head = filter_input(INPUT_GET, "head");
        $this->area = filter_input(INPUT_GET, "area", FILTER_VALIDATE_INT);
        $this->korpus = filter_input(INPUT_GET, "korpus");
        $this->kab = filter_input(INPUT_GET, "kab");
        $this->info = filter_input(INPUT_GET, "info");
        $this->event_dt = filter_input(INPUT_GET, "event_dt");
        $this->event_time = filter_input(INPUT_GET, "event_time");
    }

    static private function is_editor($user_id) {
        global $USER;
        if (in_array($user_id, array($USER->id, 0)) OR count($USER->group_manager) > 0) {
            return TRUE;
        }
        return FALSE;
    }

    static public function get_ticket_data($id) {
        global $DB;
        $sel = $DB->prepare("SELECT * FROM `tickets` WHERE `id`=:id");
        $sel->execute(array('id' => $id));
        if ($sel->rowCount() > 0) {
            return $sel->fetch();
        }
        set_error(t("Заявка")." " . $id." " . t("не найдена"));
        return FALSE;
    }

    private function set_error($text = NULL) {
        $text = trim($text);
        if (!empty($text)) {
            $this->error[] = $text;
        }
    }

    private function get_ticket_give() {
        global $DB;
        $sel = $DB->prepare("SELECT * FROM `tickets_give` WHERE `ticket_id`=:ticket_id");
        $sel->execute(array('ticket_id' => $this->id));
        if ($sel->rowCount() > 0) {
            $sel = $sel->fetch();
            $this->give_id = intval($sel['id']);
            $this->new_user_id = intval($sel['user_id']);
            $this->new_user_init = user::get_user_id($sel['who']);
        }
    }

    static public function get_ticket_user($id) {
        global $DB;
        $sel = $DB->prepare("SELECT `user_id` FROM `tickets` WHERE `id`=:ticket_id");
        $sel->execute(array('ticket_id' => $id));
        if ($sel->rowCount() > 0) {
            return $sel->fetch()['user_id'];
        }
        return 0;
    }

    static public function get_give($id, $ticket_id = 0) {
        global $DB;
        if ($id > 0) {
            $sel = $DB->prepare("SELECT * FROM `tickets_give` WHERE `id`=:id");
            $sel->execute(array('id' => $id));
        } elseif ($id == 0 AND $ticket_id > 0) {
            $sel = $DB->prepare("SELECT * FROM `tickets_give` WHERE `ticket_id`=:ticket_id");
            $sel->execute(array('ticket_id' => $ticket_id));
        } else {
            return FALSE;
        }
        if ($sel->rowCount() > 0) {
            return $sel->fetch();
        } else {
            //set_error('Не найдена информация о передаче заявки. Информация устарела, обновите страницу.');
        }
        return FALSE;
    }

    static public function get_give_to_my() {
        global $DB, $USER, $DIV;
        $sel = $DB->prepare("SELECT * FROM `tickets_give` WHERE `user_id`=:user_id");
        $sel->execute(array('user_id' => $USER->id));
        if ($sel->rowCount() > 0) {
            while ($row = $sel->fetch()) {
                $mes = '';
                if (!empty($row['mes'])) {
                    $mes = sprintf('<br/><b>Сообщение</b>: %s', htmlspecialchars($row['mes']));
                }
                $mes = self::link_to_ticket($row['ticket_id'], sprintf('<span>Пользователь <b>%s</b> хочет передать Вам заявку <b>%s</b>%s</span>', $DIV->get_user_fio($row['who']), $row['ticket_id'], $mes
                ));
                $TU = Ticket::get_ticket_user($row['ticket_id']);
                if (user::get_user_id($row['who']) !== intval($TU)) {
                    $mes .= '<span class="red bold">Ошибка передачи заявки: владелец уже сменился</span>';
                }
                set_info($mes, false, "give_ticket_" . $row['ticket_id']);
            }
        }
        return 0;
    }

    static private function get_ticket_sign($id = 0) {
        global $DB,$DIV;
        $users = array();
        if (!$id) {
            return array();
        }
        $sel = $DB->prepare("SELECT `user_id` FROM `tickets_user` WHERE `ticket_id`=:ticket_id AND `sign`=1 GROUP BY `user_id`");
        $sel->execute(array('ticket_id' => $id));
        if ($sel->rowCount() > 0) {
            while ($row = $sel->fetch()) {
                if (!in_array($row['user_id'], $users)) {
                    $ff = $DIV->get_user_fio($row['user_id']);
                    $ff .= sprintf('<span class="small_page_button for_head unsign_user" title="Отписать %s" value_set="%s" onclick="set_var(this,\'ticket_event\',\'unsign_user\',\'update_ticket_view\',%s);"></span>', $ff, $row['user_id'], $id);
                    $users[$row['user_id']] = $ff;
                }
            }
        }
        return $users;
    }

    private function get_ticket_repair($data, $old_data = '') {
        //$res = $this->repair;
        $res = array();
        $komplekt = $this->array_repair();
        if ($data === NULL) {
            $old_data = explode("\n", $old_data);
            $values = array();
            if (isset($old_data[3])) {
                $old_data[3] = preg_replace("/(\s*)?,(\s*)?/", ",", $old_data[3]);
                $old_data[3] = explode(",", $old_data[3]);
                $nn = 0;
                foreach ($old_data[3] as $va) {
                    $nn++;
                    $values[$nn] = $va;
                }
            }
            $values = implode(", ", $values);
            $data = array(
                'firm' => (isset($old_data[0])) ? $old_data[0] : t('Не указана'),
                'problem' => (isset($old_data[1])) ? $old_data[1] : t('Не указана'),
                'fio' => (isset($old_data[4])) ? $old_data[4] : t('Не указано'),
                'phone' => (isset($old_data[5])) ? $old_data[5] : t('Не указана'),
                'komplekt' => array(
                    'other' => array(
                        'count' => 1,
                        'value' => array('other' => array(1 => $values))
                    )
                ),
            );
        } else {
            $data = json_decode($data, TRUE);
        }
        $this->repair_data = $data;
        if (isset($this->repair_data['komplekt'])) {
            unset($this->repair_data['komplekt']);
        }
        foreach ($komplekt as $key => $val) {
            $value = (isset($data[$key])) ? $data[$key] : '';
            if ($key !== "komplekt") {
                $res[] = array(
                    $val['name'],
                    $value
                );
            } else {
                $value = array();
                $komplekt_type = $this->array_repair_komplekt_type();
                foreach ($komplekt_type as $tkey => $tval) {
                    if (isset($data[$key][$tkey])) {
                        $data_val = $data[$key][$tkey];
                        if ($data_val['count'] > 0) {
                            for ($i = 0; $i < $data_val['count']; $i++) {
                                $t_kompl = array();
                                foreach ($tval['default'] as $sub_key => $sub_val) {
                                    if ($tkey === $sub_key) {
                                        $sub_val['name'] = $tval['name'];
                                    }
                                    if ($sub_key !== "other") {
                                        if (isset($data_val['value'][$sub_key])) {
                                            if (in_array(($i + 1), $data_val['value'][$sub_key])) {
                                                $t_kompl[] = $sub_val['name'];
                                            }
                                        }
                                    } else {
                                        if (isset($data_val['value'][$sub_key][($i + 1)])) {
                                            $tov = $data_val['value'][$sub_key][($i + 1)];
                                            if (!empty($tov)) {
                                                $t_kompl[] = $tov;
                                            }
                                        }
                                    }
                                }
                                $value[] = sprintf('<li>%s #%s<br/><sup>%s</sup></li>', $tval['name'], ($i + 1), implode(", ", $t_kompl));
                            }
                        }
                    }
                }
                $this->repair_komplekt = sprintf('<ul>%s</ul>', implode("", $value));
                $res[] = array(
                    $val['name'],
                    sprintf('<ul>%s</ul>', implode("", $value))
                );
            }
        }
        return table($res);
    }

    private function get_ticket_events() {
        global $USER, $DB,$DIV;
        $events = array();
        $sel = $DB->prepare("SELECT *  FROM `tickets_event` WHERE `ticket_id` = :ticket_id ORDER BY `subticket` DESC,`dt`");
        $sel->execute(array('ticket_id' => $this->id));
        if ($sel->rowCount() > 0) {
            $min_id = 0;
            while ($row = $sel->fetch()) {
                $id = $row['ID'];
                $row['dt'] = intval($row['dt']);
                if($min_id == 0 OR $min_id > $row['ID']){
                    $min_id = $row['ID'];
                }
                $row['empty'] = empty($row['mes']);
                $row['mes'] = self::transform_message($row['mes'], $row['status']);
                $row['user'] = $DIV->get_user_fio($row['user_id']);
                $row['time'] = time_show($row['dt']);
                $style = array("ticket_message");
                $row['edit'] = array();
                if ($row['subticket'] > 0 AND !$row['empty']) {
                    $row['mes'] = html::span('! ',['title'=>t('Задача'),'class'=>'bold red'])
                            .$row['mes'];
                    if ($row['subticket_status'] > 0){
                        $style[] = 'subticket_close';
                    }else{
                        $style[] = 'subticket';
                    }
                }
                if($row['status'] == 0 AND !$row['empty'] AND ($this->user_id == 0 OR $row['user_id'] == $USER->id OR in_array($this->area, $USER->group_manager) OR in_array('admin',$USER->groups) OR in_array('root',$USER->groups))){
                    if ($row['subticket'] > 0) {
                        if ($row['subticket_status'] > 0){
                            $row['edit'][] = html::span("", [
                                'title'=>t('Снять выполнение задачи'),
                                'value_set'=>$this->id,
                                'class' => ['small_page_button','subticket_open'],
                                'onclick' => AJAX::on_event('this', 'ticket_event', 'subticket_open', 'update_messages', $id),
                            ]);
                        }else{
                            $row['edit'][] = html::span("", [
                                'title'=>t('Задача выполнена'),
                                'value_set'=>$this->id,
                                'class' => ['small_page_button','subticket_close'],
                                'onclick' => AJAX::on_event('this', 'ticket_event', 'subticket_close', 'update_messages', $id),
                            ]);
                        }
                        $row['edit'][] = html::span("", [
                            'title'=>t('Убрать с задач'),
                            'value_set'=>$this->id,
                            'class' => ['small_page_button','unsetsubticket'],
                            'onclick' => AJAX::on_event('this', 'ticket_event', 'unset_subticket', 'update_messages', $id),
                        ]);
                        
                    } else {
                        $row['edit'][] = html::span("", [
                            'title'=>t('Поставить задачей'),
                            'value_set'=>$this->id,
                            'class' => ['small_page_button','setsubticket'],
                            'onclick' => AJAX::on_event('this', 'ticket_event', 'set_subticket', 'update_messages', $id),
                        ]);
                    }
                }
                if ($row['status'] > 0) {
                    $style[] = 'status';
                } else {
                    if ($row['user_id'] == $USER->id) {
                        $style[] = 'my_message';
                        if ($row['dt'] > (time() - (60 * 60 * 24 * 150)) AND ! $row['empty']) {
                            $row['edit'][] = sprintf('<span value_set="%s" title="'.t("Редактировать сообщение").'" class="small_page_button edit" onclick="set_var(this,\'ticket_event\',\'edit_message\',\'update_messages\',%s);"></span>', $this->id, $id);
                            $row['edit'][] = sprintf('<span value_set="%s" title="'.t("Удалить сообщение").'" class="small_page_button remove" onclick="confirm_set(this,\'ticket_event\',\'remove_message\',\'update_messages\',%s);"></span>', $this->id, $id);
                        }
                    } else {
                        $style[] = 'other_message';
                    }
                }
                if (in_array($this->area, $USER->group_manager) OR in_array('admin',$USER->groups) OR in_array('root',$USER->groups)) {
                    $row['edit'][] = sprintf('<span value_set="%s" title="'.t("Уничтожить сообщение").'" class="small_page_button full_remove" onclick="confirm_set(this,\'ticket_event\',\'full_remove_message\',\'update_messages_attach\',%s);"></span>', $this->id, $id);
                }
                $row['edit'] = implode("", $row['edit']);
               /* if (count($events) == 0) {
                    $row['edit'] = '';
                }*/
                $row['style'] = sprintf('class="%s" title="#%s"', implode(" ", $style),$id);
                $row['attach'] = self::get_attachment($this->id, $id);
                unset($row['ID']);
                $events[$id] = $row;
            }
            foreach($events as $id=>$e){
                if($id == $min_id){
                    $events[$id]['edit'] = '';
                }
            }
        }
        $this->events = $events;
    }

    static public function remove_event($id, $full = FALSE) {
        global $DB;
        $id = (INT) $id;
        if ($full) {
            $attach = self::get_attach($id);
            foreach ($attach as $file) {
                File::remove_file($file['id']);
            }
            $rem = $DB->prepare("DELETE FROM `tickets_event` WHERE `ID`=:id");
            $rem->execute(array('id' => $id));
        } else {
            $rem = $DB->prepare("UPDATE `tickets_event` SET `mes`='' WHERE `ID`=:id");
            $rem->execute(array('id' => $id));
        }
        if ($rem->rowCount() > 0) {
            //set_info('Сообщение удалено');
            return TRUE;
        } else {
            set_error('Ошибка: Сообщение не удалено');
            return FALSE;
        }
    }
    
    static public function subticket($id, $set = FALSE) {
        global $USER;
        $id = intval($id);
        if($id > 0){
            $sel = db("SELECT * FROM `tickets_event` WHERE `ID`=:id", ['id'=>$id]);
            $d = $sel -> fetch();
            $c = boolval($d['subticket']);
            $ticket_id = intval($d['ticket_id']);
            $set = boolval($set);
            if($set AND !$c){
                db("UPDATE `tickets_event` SET `subticket`=1 WHERE `ID`=:id", ['id'=>$id]);
                Ticket::set_ticket_event($ticket_id, $USER->id, t('Поставил задачей #').$id, 1);
            }elseif(!$set AND $c){
                db("UPDATE `tickets_event` SET `subticket`=0 WHERE `ID`=:id", ['id'=>$id]);
                Ticket::set_ticket_event($ticket_id, $USER->id,  t('Убрал с задач #').$id, 1);
            }
        }
    }
    
    static public function subticket_open($id, $set = FALSE) {
        global $USER;
        $id = intval($id);
        if($id > 0){
            $sel = db("SELECT * FROM `tickets_event` WHERE `ID`=:id", ['id'=>$id]);
            $d = $sel -> fetch();
            $c = boolval($d['subticket_status']);
            $ticket_id = intval($d['ticket_id']);
            $set = boolval($set);
            if($set AND !$c){
                db("UPDATE `tickets_event` SET `subticket_status`=1, `dt`=:dt WHERE `ID`=:id", ['dt'=>time(),'id'=>$id]);
                Ticket::set_ticket_event($ticket_id, $USER->id,  t('Отметил задачу выполненной #').$id, 1);
            }elseif(!$set AND $c){
                db("UPDATE `tickets_event` SET `subticket_status`=0, `dt`=:dt WHERE `ID`=:id", ['dt'=>time(),'id'=>$id]);
                Ticket::set_ticket_event($ticket_id, $USER->id,  t('Отменил выполнение задачи #').$id, 1);
            }
        }
    }

    private function get_attachment($ticket_id, $message_id = 0) {
        global $DB, $USER;
        $res = array();
        $sel = $DB->prepare("SELECT * FROM `tickets_attachment` WHERE `ticket_id`=:ticket_id AND `message_id`=:message_id");
        $sel->execute(array(
            'ticket_id' => $ticket_id,
            'message_id' => $message_id,
        ));
        while ($row = $sel->fetch()) {
            $row['edit'] = self::is_editor($row['user_id']);
            $res[$row['id']] = $row;
            $this->attachments[$row['id']] = $row;
        }
        return $res;
    }

    static private function get_attach($message_id) {
        global $DB, $USER;
        $res = array();
        $sel = $DB->prepare("SELECT * FROM `tickets_attachment` WHERE `message_id`=:message_id");
        $sel->execute(array(
            'message_id' => $message_id,
        ));
        while ($row = $sel->fetch()) {
            $res[$row['id']] = $row;
        }
        return $res;
    }

    static private function translate_event_status($status_id) {
        global $STATUS;
        $text = '';
        if (isset($STATUS[$status_id])) {
            $text = $STATUS[$status_id];
        } else {
            $text = t('Неизвестный статус заявки');
        }
        return $text;
    }

    static public function get_users($groups = array()) {
        global $DB, $USER, $USERS;
        $users = array();
        //0 => 'Нет ответственного'
        if (count($groups) == 0 AND count($USER->ticket_groups) > 0) {
            $groups = $USER->ticket_groups;
        }
        foreach($USERS as $u){
            if(count(array_intersect($groups, $u['ticket_groups'])) > 0){
                $users[$u['id']] = $u['fios'];
            }
        }
        return $users;
    }

    static public function get_area() {
        global $DB, $USER;
        $areas = $res = array();
        $ticket_groups = (isset($USER->ticket_groups)) ? $USER->ticket_groups : array();
        $sel = $DB->prepare("SELECT * FROM `ticket_groups`");
        $sel->execute();
        while ($row = $sel->fetch()) {
            $areas[$row['id']] = $row['name_group'];
        }
        foreach ($ticket_groups as $group) {
            if (isset($areas[$group]) AND ! isset($res[$group])) {
                $res[$group] = $areas[$group];
            }
        }
        return $res;
    }

    static public function get_user_from_area($area) {
        global $USERS;
        $res = array();
        foreach ($USERS as $user) {
            if ((in_array($area, $user['ticket_groups']) OR $user['id'] == 0) AND $user['enable'] == 1) {
                $res[$user['id']] = $user['fios'];
            }
        }
        return $res;
    }

    static private function get_read_user($id) {
        global $DB, $USER;
        $res = array();
        $sel = $DB->prepare("SELECT MAX(`dt`)as dt,`user_id` FROM `tickets_user` WHERE `ticket_id`=:ticket_id GROUP BY `user_id`");
        $sel->execute(array('ticket_id' => $id));
        while ($row = $sel->fetch()) {
            $res[$row['user_id']] = $row['dt'];
        }
        if (!isset($res[$USER->id])) {
            $res[$USER->id] = 0;
        }
        return $res;
    }

    static private function get_time_create($id) {
        global $DB, $USER;
        $last_event = 0;
        $sel = $DB->prepare("SELECT * FROM `tickets_event` WHERE `ticket_id`=:ticket_id ORDER BY `dt` ASC LIMIT 1");
        $sel->execute(array('ticket_id' => $id));
        if ($sel->rowCount() > 0) {
            $de = $sel->fetch();
            $last_event = (INT) $de['dt'];
        }
        return $last_event;
    }

    static private function get_time_last_event($id) {
        global $DB;
        $last_event = (INT) time();
        $sel = $DB->prepare("SELECT * FROM `tickets_event` WHERE NOT (`mes`=1 AND `status`=1) AND  `ticket_id`=:ticket_id ORDER BY `dt` DESC LIMIT 1");
        $sel->execute(array('ticket_id' => $id));
        if ($sel->rowCount() > 0) {
            $de = $sel->fetch();
            $last_event = (INT) $de['dt'];
        }
        return $last_event;
    }

    static public function update_ticket_user($user_id, $ticket_id) {
        global $DB;
        $sel = $DB->prepare("SELECT * FROM `tickets_user` WHERE `user_id`=:user_id AND `ticket_id`=:ticket_id AND `sign`=0");
        $sel->execute(array(
            'user_id' => $user_id,
            'ticket_id' => $ticket_id,
        ));
        if ($sel->rowCount() > 0) {
            $upd = $DB->prepare("UPDATE `tickets_user` SET `dt`=:dt WHERE `user_id`=:user_id AND `ticket_id`=:ticket_id AND `sign`=0");
            $upd->execute(array(
                'dt' => time(),
                'user_id' => $user_id,
                'ticket_id' => $ticket_id,
            ));
        } else {
            $ins = $DB->prepare("INSERT INTO `tickets_user`(`user_id`, `ticket_id`, `dt`, `sign`) "
                    . "VALUES (:user_id, :ticket_id, :dt, 0)");
            $ins->execute(array(
                'user_id' => $user_id,
                'ticket_id' => $ticket_id,
                'dt' => time(),
            ));
        }
    }

    static public function action() {
        global $USER;
        $action = array(
            'closed' => 0,
            'complete' => 0,
            'subscribe' => 0,
            'warning' => 0,
            'user_id' => 0,
            'area' => 0,
            'korpus' => 0,
            'kab' => NULL,
            'head' => NULL,
            'full_search' => 0,
            'set_all_status_3' => 0,
        );
        if (count($USER->group_manager) > 0) {
            $action['complete'] = 1;
        }
        if (isset($_SESSION['ticket'])) {
            $reset = FALSE;
            if (isset($_SESSION['ticket']['reset'])) {
                if ($_SESSION['ticket']['reset']) {
                    $reset = TRUE;
                    $_SESSION['ticket']['reset'] = 0;
                }
            }
            foreach ($action as $key => $value) {
                if (isset($_SESSION['ticket'][$key])) {
                    if (!$reset) {
                        if (!in_array($key, array("kab", "head"))) {
                            $_SESSION['ticket'][$key] = $_SESSION['ticket'][$key];
                        }
                        $action[$key] = $_SESSION['ticket'][$key];
                    } else {
                        $_SESSION['ticket'][$key] = $action[$key];
                    }
                }
            }
        }
        return $action;
    }

    static public function get_action() {
        global $USER, $KORPUS;
        $text = '';
        $USERS = self::get_users($USER->ticket_groups);
        $action = self::action();
        $AREAS = self::get_area();
        $text .= '<div class="button nonactive add" title="'.t('Добавить заявку').'" onclick="open_win(\'/?action=edit&id=0\');"></div>';
        $text .= sprintf('<div class="button%s closed" title="'.t("Закрытые заявки").'" onclick="set_var(this,\'ticket\',\'closed\',\'show_tickets\');"></div>', ($action['closed']) ? " active" : " nonactive");
        $complete_class = "nonactive";
        if ($action['complete'] == 1) {
            $complete_class = "active";
        } elseif ($action['complete'] == 2) {
            $complete_class = "selected";
        }
        $text .= sprintf('<div class="button %s complete" title="'.t("Выполненные заявки").'" onclick="set_var(this,\'ticket\',\'complete\',\'show_tickets\');"></div>', $complete_class);
        if ($action['complete'] == 2 AND ( count($USER->group_manager) > 0 OR isset($USER->groups['admin']))) {
            $text .= sprintf('<div class="button%s set_all_status_3" title="'.t("Закрыть все показанные заявки").'" onclick="confirm_set(this,\'ticket\',\'set_all_status_3\',\'show_tickets\',100,false);"></div>', ($action['subscribe']) ? " active" : " nonactive");
        }
        $text .= sprintf('<div class="button%s subscribe" title="'.t("Подписан").'" onclick="set_var(this,\'ticket\',\'subscribe\',\'show_tickets\');"></div>', ($action['subscribe']) ? " active" : " nonactive");
        $text .= sprintf('<div class="button%s warning" title="'.t("Не принятые").'" onclick="set_var(this,\'ticket\',\'warning\',\'show_tickets\');"></div>', ($action['warning']) ? " active" : " nonactive");
        $text .= sprintf('<div class="field">%s</div>', get_select($USERS, "user", $action['user_id'], ' onchange="set_var(this,\'ticket\',\'user_id\',\'show_tickets\');"', 'fios', t("Пользователь")));
        $text .= sprintf('<div class="field">%s</div>', get_select($AREAS, "area", $action['area'], ' onchange="set_var(this,\'ticket\',\'area\',\'show_tickets\');"', FALSE, t("Направление")));
        $text .= sprintf('<div class="field">%s</div>', get_select($KORPUS, "korpus", $action['korpus'], ' onchange="set_var(this,\'ticket\',\'korpus\',\'show_tickets\');"', NULL, t("Корпус")));
        $text .= sprintf('<div class="field"><input type="text" name="kab" size="3" placeholder="'.t("каб").'" value="%s" onkeypress="set_var_enter(event,this,\'ticket\',\'kab\',\'show_tickets\');"/></div>', $action['kab']);
        $text .= sprintf('<div class="field"><input type="text" name="head" size="8" placeholder="'.t("поиск").'" value="%s" onkeyup="set_var_enter(event,this,\'ticket\',\'head\',\'show_tickets\');"/></div>', $action['head']);
        $text .= sprintf('<div class="button%s full_search" title="'.t("Поиск по сообщениям").'" onclick="set_var(this,\'ticket\',\'full_search\',\'show_tickets\');"></div>', ($action['full_search']) ? " active" : " nonactive");
        $text .= '<div class="button nonactive reset" title="'.t("Сбросить").'" onclick="set_var(this,\'ticket\',\'reset\',\'show_tickets\');"></div>';
        return $text;
    }

    static private function create_load_sql() {
        global $USER, $USERS, $DB, $STATUS;
        $action = self::action();
        $sub_sql = '';
        if (!$action['closed']) {
            if (isset($STATUS[3])) {
                unset($STATUS[3]);
            }
        } else {
            $STATUS = array(3 => t("Закрыта"));
        }
        switch ($action['complete']) {
            case 2:
                $STATUS = array(2 => t("Выполнена"));
                break;
            case 1:
                if (!isset($STATUS[2])) {
                    $STATUS[2] = t("Выполнена");
                }
                /* if(count($USER->group_manager) > 0){
                  $STATUS = array(2=>"Выполнена");
                  } */
                break;
            default:
                if (isset($STATUS[2])) {
                    unset($STATUS[2]);
                }
                break;
        }
        if ($action['warning'] > 0) {
            $STATUS = array(0 => t("Поставлена"), 5 => t("Нет ответственного"));
        }
        $sub_sql .= sprintf(' AND `status` IN (%s)', implode(",", array_keys($STATUS)));
        if ($action['user_id'] > 0) {
            $sub_sql .= sprintf(' AND `user_id` = %u', $action['user_id']);
        }
        if ($action['area'] > 0) {
            $sub_sql .= sprintf(' AND `area` = %u', $action['area']);
        } else {
            $ticket_groups = (isset($USER->ticket_groups)) ? $USER->ticket_groups : array();
            $sub_sql .= sprintf(' AND `area` IN (%s)', implode(",", $ticket_groups));
        }
        if ($action['korpus'] > 0) {
            $sub_sql .= sprintf(' AND `korpus` = %u', $action['korpus']);
        }
        if (!empty($action['kab'])) {
            $action['kab'] = str_replace("*", "%", $action['kab']);
            $sub_sql .= sprintf(" AND `kab` LIKE '%%%s%%'", $action['kab']);
        }
        if (!empty($action['head'])) {
            $action['head'] = str_replace("*", "%", $action['head']);
            $head_no_m = str_replace(array("M", "m", "М", "м"), array("", "", "", ""), $action['head']);
            if (substr_count($action['head'], "%") == 0) {
                $action['head'] = "%" . $action['head'] . "%";
                $head_no_m = "%" . $head_no_m . "%";
            }
            $sub_sql_head = '';
            if ($action['full_search']) {
                $tickets_mes = array();
                $sql_tm = sprintf("SELECT * FROM `tickets_event` WHERE `status`=0 AND (`mes` LIKE '%s' OR `mes` LIKE '%s') GROUP BY `ticket_id`", $action['head'], $head_no_m);
                $sel_tm = $DB->prepare($sql_tm);
                $sel_tm->execute();
                while ($tm = $sel_tm->fetch()) {
                    $tickets_mes[] = $tm['ticket_id'];
                }
                if (count($tickets_mes) > 0 AND count($tickets_mes) < 101) {
                    $sub_sql_head = sprintf(" OR `id` IN (%s)", implode(", ", $tickets_mes));
                } elseif (count($tickets_mes) > 100) {
                    set_error("Большое количество совпадений при поиске по сообщениям. Результаты поиска по сообщениям не включены. Уточните запрос.", FALSE, "many_count");
                }
            }
            $sub_sql .= sprintf(" AND (`id` = '%s' OR `head` LIKE '%s' OR `inventory` LIKE '%s' OR `descr` LIKE '%s' OR `input_mail` LIKE '%s'%s)", str_replace("%", "", $action['head']), $action['head'], $head_no_m, $action['head'], $action['head'], $sub_sql_head);
        }
        if ($action['subscribe']) {
            $tickets_subscribe = array();
            $sql_ss = sprintf("SELECT * FROM `tickets_user` WHERE `user_id`=26 AND `sign`=1 GROUP BY `ticket_id` ORDER BY `ticket_id` DESC LIMIT 100", $USER->id);
            $sel_ss = $DB->prepare($sql_ss);
            $sel_ss->execute();
            while ($ss = $sel_ss->fetch()) {
                $tickets_subscribe[] = $ss['ticket_id'];
            }
            if (count($tickets_subscribe) > 0) {
                $sub_sql .= sprintf(" AND `id` IN (%s)", implode(", ", $tickets_subscribe));
            }
        }
        if ($action['closed']) {
            $sub_sql .= " ORDER BY `id` DESC LIMIT 400 ";
        }
        $sql = "SELECT * FROM `tickets` WHERE TRUE " . $sub_sql;
        return $sql;
    }

    static public function close_opened_ticket_select() {
        global $DB, $STATUS, $STATUS_ORIG;
        $action = self::action();
        $sql = self::create_load_sql();
        $sel = $DB->prepare($sql);
        $sel->execute();
        while ($ticket = $sel->fetch()) {
            if ((INT) $ticket['status'] === 2) {
                self::set_ticket_status($ticket['id'], 3, FALSE);
            }
        }
        set_param('ticket', 'set_all_status_3', 0);
        set_param('ticket', 'complete', "1");
        $STATUS = $STATUS_ORIG;
        return TRUE;
    }

    static public function load_ticket_list() {
        global $USER, $USERS, $DB, $STATUS;
        //self::close_opened_ticket_select();
        $action = self::action();
        if ($action['set_all_status_3'] > 0) {
            self::close_opened_ticket_select();
        }
        $sql = self::create_load_sql();
        $sel = $DB->prepare($sql);
        $sel->execute();
        $html = '';
        if ($sel->rowCount() > 0) {
            $tickets = array();
            while ($ticket = $sel->fetch()) {
                //debug($ticket);
                $ticket = self::get_ticket_short($ticket);
                $tid_sort = $ticket['last_event'] . "" . str_pad($ticket['id'], 5, "0", STR_PAD_LEFT);
                $tickets[$tid_sort] = $ticket;
            }
            //sort_m($tickets,'last_event',"DESC");
            krsort($tickets);
            $html = '<table class="list_tickets" cellpadding="5" cellspacing="0" border="0">';
            $t = time();
            foreach ($tickets as $ticket) {
                if ($t > $ticket['last_event']) {
                    $t = $ticket['last_event'];
                }
                $html .= sprintf('<tr tid="%s" class="%s%s">'
                        . '<td class="icon show_full">%s</td>'
                        . '<td class="icon ticket_status">%s</td>'
                        . '<td class="tid">%s</td>'
                        . '<td class="ticket_name">%s</td>'
                        . '<td>%s</td>'
                        . '<td class="show_full">%s -> %s'
                        . '<div class="ticket_length year%s" style=" background: linear-gradient(to right, #c73c3c 0%%, #c73c3c %s%%, #3fc73c %s%%, #3fc73c 100%%);"></div></td>'
                        . '</tr>', $ticket['id'], ($ticket['ready']) ? "ready" : "nonread", $ticket['subclass'], $ticket['subscrib'], $ticket['status_text'], $ticket['id_link'], $ticket['head_link'], $ticket['position'], $ticket['user'], $ticket['last_event_text'], $ticket['ticket_length_year'], $ticket['ticket_length_month'], $ticket['ticket_length_month']);
            }
            $html .= '</table>';
        } else {
            $html = t('Заявки по запросу не найдены.');
        }
        return $html;
    }

    static private function link_to_ticket($id, $text) {
        global $CONFIG;
        $link = '';
        if (isset($CONFIG['PATH']['www']['paths'])) {
            $link .= $CONFIG['PATH']['www']['paths'];
        }
        $link .= "/?action=view&id=" . $id;
        return sprintf('<span class="show_full link" onclick="set_var(this,\'ticket_show\',\'swindow\',\'update_tickets_list\',%s);">%s</span>'
                . '<span class="show_small"><a href="%s">%s</a></span>', $id, $text, $link, $text);
    }

    static private function get_ticket_short($data) {
        global $DB, $USER, $USERS, $STATE;
        // onclick="set_var(this,\'ticket_event\',\'give_my\',\'show_tickets\',%s);"
        $data['event_exec'] = 0;
        if (!empty($data['event_dt'])) {
            $date_head = date("d.m", strtotime($data['event_dt'] . " 00:00:00"));
            if (empty($data['event_time'])) {
                $data['event_time'] = '00:00:00';
            } else {
                $date_head .= " После " . date("H:i", strtotime($data['event_dt'] . " " . $data['event_time']));
            }
            $data['event_exec'] = strtotime($data['event_dt'] . " " . $data['event_time']);
            $data['head'] = $date_head . ". " . $data['head'];
        }
        $data['id_link'] = sprintf('<span class="link" onclick="open_win(\'/?action=view&id=%s\');">%s</span>', $data['id'], $data['id']);
        $data['head_link'] = self::link_to_ticket($data['id'], $data['head']);
        $data['create'] = self::get_time_create($data['id']);
        $data['last_event'] = self::get_time_last_event($data['id']);
        $data['last_event_text'] = time_show($data['last_event']);
        $read_users = self::get_read_user($data['id']);
        $data['ready'] = TRUE;
        if ((INT) $data['last_event'] > (INT) $read_users[$USER->id]) {
            $data['ready'] = FALSE;
        }
        $subscribe_users = self::get_ticket_sign($data['id']);

        $data['subscribed'] = FALSE;
        if (isset($USERS[$data['user_id']])) {
            $data['user'] = $USERS[$data['user_id']]["fios"];
            if (isset($subscribe_users[$USER->id])) {
                $data['subscribed'] = TRUE;
            }
        } else {
            $data['user'] = t('U/C Пользователь');
        }
        if ($data['subscribed']) {
            $data['subscrib'] = sprintf('<div class="button ticket scribed" onclick="set_var(this,\'ticket_event\',\'unsign_my\',\'show_tickets\',%s);"></div>', $data['id']);
        } else {
            $data['subscrib'] = sprintf('<div class="button ticket unscribed" onclick="set_var(this,\'ticket_event\',\'sign_my\',\'show_tickets\',%s);"></div>', $data['id']);
        }
        $status_image = '';
        $data['subclass'] = array();
        $event_start = ($data['event_exec'] > 0 AND ( ($data['event_exec'] - (60 * 60 * 2)) < time()));
        switch ($data['status']) {
            case '0':
            case '5':
                switch ($data['state']) {
                    case 1: $substate = 'free_st1';
                        break;
                    case 3: $substate = 'free_st3';
                        break;
                    default: $substate = 'free';
                }
                $status_image = sprintf('<div class="button ticket %s" onclick="set_var(this,\'ticket_event\',\'give_my\',\'show_tickets\',%s);"></div>', $substate, $data['id']);
                if (($data['create'] < (INT) (time() - (60 * 60 * 24)) AND $data['event_exec'] == 0)
                        OR $event_start) {
                    //поднимаем вверх не принятые заявки больше суток
                    $data['last_event'] = (INT) time() + 10;
                    $old = time() - $data['create'];
                    $old_text = '';
                    $day = 60 * 60 * 24;
                    $week = $day * 7;
                    $month = $week * 4;
                    if ($old > $day) {
                        $old_text = t('больше суток');
                    }

                    if ($old > $day AND $old < $week) {
                        $days = round($old / $day);
                        switch ($days) {
                            case 1:$old_text = $days .' '. t('день');
                                break;
                            case 2:
                            case 3:
                            case 4:$old_text = $days .' '. t('дня');
                                break;
                            case 5:
                            case 6:
                            case 7:$old_text = $days .' '. t('дней');
                                break;
                        }
                    }
                    $data['subclass'][] = 'warning';
                    $add_warn = FALSE;
                    if ($old > $week) {
                        $old_text = t('больше недели');
                        $add_warn = 'week';
                    }
                    if ($old > $month) {
                        $old_text = t('больше месяца');
                        $add_warn = 'month';
                    }
                    if ($event_start) {
                        $old_text .= sprintf('<br><b>%s</b>', time_show_event($data['event_exec']));
                    }
                    $data['last_event_text'] = $old_text;
                    if ($add_warn) {
                        $data['subclass'][] = $add_warn;
                    }
                } elseif (in_array($data['area'], array(7, 8))) {
                    $data['last_event'] = time() - 60 * 60 * 24 * 20;
                }
                break;
            case '1':
                switch ($data['state']) {
                    case '1':
                        $status_image = '<div class="button ticket state1"></div>';
                        //поднимаем вверх срочные заявки
                        $data['last_event'] = (INT) time();
                        break;
                    case '2':
                        $status_image = '<div class="button ticket state2"></div>';
                        break;
                    case '3':
                        $status_image = '<div class="button ticket state3"></div>';
                        break;
                }
                //поднимаем вверх заявки по времени выполнения
                if ($event_start) {
                    $data['last_event_text'] .= sprintf('<br/><b>%s</b>', time_show_event($data['event_exec']));
                    $data['last_event'] = (INT) time();
                    $data['subclass'][] = 'warning';
                }
                break;
            case '2':
                if (count($USER->group_manager) > 0) {
                    $status_image = sprintf('<div class="button ticket complete" onclick="set_var(this,\'ticket_event\',\'set_close\',\'show_tickets\',%s);"></div>', $data['id']);
                } else {
                    $status_image = '<div class="button ticket complete"></div>';
                }
                break;
            case '3':
                $status_image = '<div class="button ticket closed"></div>';
                break;
        }
        $data['ticket_length_year'] = 1;
        $data['ticket_length_month'] = 0;
        $create_year = (INT) date("Y", $data['create']);
        if (in_array($data['status'], array(2, 3))) {
            $ms_flow = $data['last_event'] - $data['create'];
        } else {
            $ms_flow = time() - $data['create'];
        }
        if ($ms_flow > (60 * 60 * 24)) {
            $date_folow = $ms_flow / 60 / 60 / 24;
        } else {
            $date_folow = 0;
        }
        if ($date_folow > 30) {
            $date_folow = 30;
        }
        $percent_folow = 100 / 30 * $date_folow;
        $data['ticket_length_month'] = round($percent_folow);
        $data['ticket_length_year'] = (INT) date("Y") - $create_year;
        if ($data['ticket_length_year'] > 5) {
            $data['ticket_length_year'] = 5;
        } elseif ($data['ticket_length_year'] < 1) {
            $data['ticket_length_year'] = 1;
        }
        if (((INT) date("Y") - (INT) date("Y", $data['last_event'])) > 1 AND in_array($data['status'], array(0, 1, 2))) {
            $data['subclass'][] = 'smelly';
        }
        $data['status_text'] = $status_image;
        $data['position'] = self::get_position($data['korpus'], $data['kab']);
        if (count($data['subclass']) > 0) {
            $data['subclass'] = " " . implode(" ", $data['subclass']);
        } else {
            $data['subclass'] = '';
        }
        return $data;
    }

    static private function get_position($korpus, $kab) {
        $position = array();
        if ($korpus) {
            $position[] = self::tramsform_korpus($korpus);
        }
        $kab = explode(",", $kab);
        foreach ($kab as $kab_key => $kab_val) {
            $kab[$kab_key] = trim($kab_val);
        }
        $kab = implode(", ", $kab);
        if (!empty($kab)) {
            $position[] = $kab;
        }
        return implode(", ", $position);
    }

    static private function transform_head($text) {
        global $CONFIG;
        if (preg_match("/\[6(\d+)\]/", $text, $f)) {
            $id = (INT) $f[1];
            $repl = str_replace(array("[", "]"), "", $f[0]);
            $link = '';
            if (isset($CONFIG['PATH']['www']['paths'])) {
                //$link .= $CONFIG['PATH']['www']['paths'];
            }
            //$link .= "/internet/?action=edit&id=".$id;
            $link .= "/internet/edit.php?id=" . $id;
            $text = str_replace($repl, sprintf('<a target="_blank" href="%s" target="_blank">%s</a>', $link, $repl), $text);
        }
        return $text;
    }

    static public function subscribe_to_ticket($id, $user_id = 0) {
        global $USER, $DB, $DIV;
        $scribed = self::get_ticket_sign($id);
        if (!$user_id) {
            $user_id = $USER->id;
        }
        if (!in_array($user_id, $scribed)) {
            $ins = $DB->prepare("INSERT INTO `tickets_user`(`user_id`, `ticket_id`, `dt`, `sign`) "
                    . "VALUES (:user_id, :ticket_id, :dt, 1)");
            $ins->execute(array(
                'user_id' => $user_id,
                'ticket_id' => $id,
                'dt' => time()
            ));
            if ($USER->id == $user_id) {
                $mes = t('Подписался');
            } else {
                $mes = sprintf(t("Подписал").' %s', $DIV->get_user_fio($user_id));
            }
            self::set_ticket_event($id, $USER->id, $mes, "1");
            self::update_ticket_user($user_id, $id);
        } else {
            set_error(t("Пользователь уже подписан к заявке #") . $id);
        }
    }

    static public function unsubscribe_to_ticket($id, $user_id = 0) {
        global $USER, $DB, $DIV;
        $scribed = self::get_ticket_sign($id);
        if ((INT) $user_id <= 0) {
            $user_id = $USER->id;
        }
        if (isset($scribed[$user_id])) {
            $del = $DB->prepare("DELETE FROM `tickets_user` WHERE `ticket_id`=:ticket_id AND `user_id`=:user_id AND `sign`=1");
            $del->execute(array(
                'user_id' => $user_id,
                'ticket_id' => $id,
            ));
            if ($USER->id == $user_id) {
                $mes = t('Отписался');
            } else {
                $mes = sprintf(t('Отписал').' %s', $DIV->get_user_fio($user_id));
            }
            self::set_ticket_event($id, $USER->id, $mes, 1);
            self::update_ticket_user($user_id, $id);
        } else {
            set_error(t("Пользователь уже отписан от заявки #") . $id);
        }
    }

    static public function preset_ticket_give($id, $user_id) {
        global $USER, $DB;
        $user_id = (INT) $user_id;
        if ($user_id === 0) {
            self::set_ticket_value($id, 'status', 0);
            self::set_ticket_value($id, 'user_id', $user_id);
            self::set_ticket_event($id, $USER->id, t("Снял ответственность"), 1);
        } else {
            $ins = $DB->prepare("INSERT INTO `tickets_give`(`who`, `user_id`, `ticket_id`) VALUES (:who, :user_id, :ticket_id)");
            $ins->execute(array(
                'who' => $USER->username,
                'user_id' => $user_id,
                'ticket_id' => $id,
            ));
            if ($ins->rowCount() > 0) {
                return TRUE;
            } else {
                return FALSE;
            }
        }
    }

    static public function set_ticket_give($id, $user_id = 0) {
        global $USER, $USERS, $DB;
        if (!$user_id) {
            $user_id = $USER->id;
        }
        $sel = $DB->prepare("SELECT * FROM `tickets` WHERE `id`=:id");
        $sel->execute(array('id' => $id));
        if ($sel->rowCount() > 0) {
            $data = $sel->fetch();
            $mes = t('Ответственный');
            if ($user_id !== $USER->id) {
                if (isset($USERS[$user_id])) {
                    $mes = 'Назначен ответственным ' . $USERS[$user_id]['fios'];
                } else {
                    set_error("not found user: " . $user_id);
                }
            }
            if (in_array($data['user_id'], array("0")) AND $data['user_id'] !== $user_id) {
                if (self::set_ticket_value($id, "user_id", $user_id)) {
                    self::set_ticket_event($id, $USER->id, $mes, "1");
                    if (in_array($data['status'], array("0", "5"))) {
                        self::set_ticket_value($id, "status", "1");
                    }
                } else {
                    set_error(sprintf("Не удалось назначит ответственного %s в заявке %s", $user_id, $id));
                }
            } elseif (in_array($data['area'], $USER->group_manager) AND $data['user_id'] !== $user_id) {
                if (self::set_ticket_value($id, "user_id", $user_id)) {
                    self::set_ticket_event($id, $USER->id, $mes, "1");
                    if (in_array($data['status'], array("0", "5"))) {
                        self::set_ticket_value($id, "status", "1");
                    }
                } else {
                    set_error(sprintf("Не удалось назначит ответственного %s в заявке %s", $user_id, $id));
                }
            } else {
                if ($data['user_id'] == $USER->id) {
                    set_error("Вы уже ответственный");
                } else {
                    set_error("В заявке " . $id . " уже назначен ответственный");
                }
            }
            self::update_ticket_user($user_id, $id);
        } else {
            set_error("Ошибка: не найдена заявка: " . $id);
        }
    }

    static public function set_ticket_value($id, $name, $value) {
        global $DB;
        $sel = $DB->prepare("SELECT * FROM `tickets` WHERE `id`=:id");
        $sel->execute(array('id' => $id));
        if ($sel->rowCount() > 0) {
            $data = $sel->fetch();
            if (isset($data[$name])) {
                $sql = sprintf("UPDATE `tickets` SET `%s`='%s' WHERE `id`=%u", $name, $value, $id);
                $upd = $DB->prepare($sql);
                $upd->execute();
                if ($upd->rowCount() > 0) {
                    return TRUE;
                }
            }
        }
        return TRUE;
    }

    static public function set_ticket_event($ticket_id, $user_id, $mes, $status) {
        global $DB;
        $subticket = 0;
        $mes = trim($mes);
        if(preg_match("/^\!\s?/ui", $mes)){
            $subticket = 1;
            $mes = preg_replace("/^\!\s?/ui", "", $mes);
        }
        $ins_e = $DB->prepare("INSERT INTO `tickets_event`(`ticket_id`, `dt`, `user_id`, `mes`, `status`, `subticket`) "
                . "VALUES (:ticket_id, :dt, :user_id, :mes, :status, :subticket)");
        $ins_e->execute(array(
            'ticket_id' => $ticket_id,
            'dt' => time(),
            'user_id' => $user_id,
            'mes' => $mes,
            'status' => $status,
            'subticket' => $subticket,
        ));
        if ($ins_e->rowCount() > 0) {
            return $DB->lastInsertId();
        } else {
            set_error("Не удалось добавить сообщение в заявку");
            return FALSE;
        }
    }

    static public function get_event($event_id) {
        global $DB;
        $sel = $DB->prepare("SELECT * FROM `tickets_event` WHERE `ID`=:id");
        $sel->execute(array('id' => $event_id));
        if ($sel->rowCount() > 0) {
            return $sel->fetch();
        } else {
            set_error('Сообщение не найдено, возможно оно удалено');
            return FALSE;
        }
    }

    static public function edit_ticket_event($event_id, $mes) {
        global $DB;
        $event = self::get_event($event_id);
        if ($event !== FALSE) {
            $mes = htmlspecialchars($mes);
            if ($event['mes'] !== $mes) {
                $upd = $DB->prepare("UPDATE `tickets_event` SET `mes`=:mes WHERE `ID`=:id");
                $upd->execute(array('mes' => $mes, 'id' => $event_id));
                if ($upd->rowCount() > 0) {
                    return TRUE;
                } else {
                    set_error('Не удалось отредактировать сообщение');
                }
            } else {
                //set_error('Ничего не изменилось');
            }
        }
        return FALSE;
    }

    static public function set_ticket_status($id, $status_id, $message = TRUE) {
        global $USER, $STATUS_ORIG;
        if (!isset($STATUS_ORIG[$status_id])) {
            set_error("Неизвестный статус");
        } else {
            $data = self::get_ticket_data($id);
            if ($data) {
                if ((INT) $data['status'] !== (INT) $status_id) {
                    if (Ticket::set_ticket_value($id, "status", "3")) {
                        if ($message) {
                            set_message("Статус установлен");
                        }
                        self::set_ticket_event($id, $USER->id, $STATUS_ORIG[$status_id], 1);
                        self::update_ticket_user($USER->id, $id);
                    } else {
                        set_error("Не удалось установить статус");
                    }
                } else {
                    set_error("У заявки уже установлен выбранный статус");
                }
            }
        }
    }

    static private function tramsform_korpus($id) {
        global $KORPUS, $KORPUS_SHORT;
        $full_name = $short_name = $id;
        if (isset($KORPUS[$id])) {
            $full_name = $KORPUS[$id];
        }
        if (isset($KORPUS_SHORT[$id])) {
            $short_name = $KORPUS_SHORT[$id];
        }
        $text = sprintf('<span class="show_full">%s</span><span class="show_small">%s</span>', $full_name, $short_name);
        return $text;
    }

    static private function transform_message($mes, $status = 0) {
        global $STATUS;
        //вывод сообщения
        if ($status) {
            $st_id = (INT) $mes;
            if (isset($STATUS[$st_id]) AND preg_match("/^\d*$/", $mes)) {
                $mes = $STATUS[$st_id];
            }
        }
        $mes = str_replace("\r\n", "\n", $mes);
        $mes = explode("\n", $mes);
        //$mes = nl2br($mes);
        foreach ($mes as $key => $val) {
            $mes[$key] = text_to_link($val);
        }
        $mes = implode("<br/>", $mes);
        if (empty($mes)) {
            $mes = t('Сообщение удалено.');
        }
        //return text_to_link($mes);
        return $mes;
    }

    static public function get_repair_block($id = 0) {
        $preset = self::get_preset();
        $res = '';
        $repair = self::array_repair();
        $repair_komplekt_type = self::array_repair_komplekt_type();
        $group = new group($preset['area']);
        if ($group->repair_type > 0) {
            $ticket_repair = array();
            if ($id > 0) {
                $data = self::get_ticket_data($id);
                if ($data['repair'] !== NULL) {
                    $ticket_repair = json_decode($data['repair'], TRUE);
                } else {
                    $data['priem'] = explode("\n", $data['priem'], 6);
                    $komplekt = array();
                    if (isset($data['priem'][3])) {
                        $data['priem'][3] = preg_replace("/(\s+)?,(\s+)?/", ",", $data['priem'][3]);
                        $data['priem'][3] = explode(",", $data['priem'][3]);
                        $kn = 0;
                        foreach ($data['priem'][3] as $val_dk) {
                            $kn++;
                            $komplekt[$kn] = $val_dk;
                        }
                    }
                    $ticket_repair = array(
                        'firm' => (isset($data['priem'][0])) ? $data['priem'][0] : '',
                        'problem' => (isset($data['priem'][1])) ? $data['priem'][1] : '',
                        'fio' => (isset($data['priem'][4])) ? $data['priem'][4] : '',
                        'phone' => (isset($data['priem'][5])) ? $data['priem'][5] : '',
                        'komplekt' => array(
                            'other' => array(
                                'count' => count($komplekt),
                                'value' => array(
                                    'other' => $komplekt,
                                ),
                            ),
                        ),
                    );
                    if (isset($data['priem'][2])) {
                        $ticket_repair['problem'] .= ", " . $data['priem'][2];
                    }
                }
            }

            $form = array();
            foreach ($repair as $type => $line) {
                if (isset($ticket_repair[$type]) AND is_string($ticket_repair[$type])) {
                    $line['value'] = $ticket_repair[$type];
                }
                $form[$type] = array(
                    'name' => array(
                        'value' => $line['name'] . '<span class="red bold">*</span>',
                        'attr' => array('width' => '150px'),
                    ),
                    'field' => sprintf('<input type="text" name="%s" value="%s"%s/>%s', $type, $line['value'], $line['attr'], $line['addon'])
                );
            }
            $form['komplekt']['field'] = '';
            foreach ($repair_komplekt_type as $key => $type) {
                $def = $val = array();
                if ($group->repair_type == 2 AND in_array($key, array('system_unit', 'monitor', 'laptop', 'projector'))) {
                    continue;
                }
                if (isset($ticket_repair['komplekt'][$key]['count'])) {
                    $type['count'] = $ticket_repair['komplekt'][$key]['count'];
                }
                foreach ($type['default'] as $d_key => $d_val) {
                    $hidden = FALSE;
                    if (isset($d_val['hidden'])) {
                        $hidden = TRUE;
                    }
                    if ($hidden AND !in_array($d_key, array('number','other'))) {
                        $def[] = sprintf('<label><input type="hidden" value="1" name="%s_%s[]"/></label>', $key, $d_key);
                    } elseif (!in_array($d_key, array('number','other'))) {
                        $def[] = sprintf('<label><input type="checkbox" value="1" name="%s_%s[]" %s %s/>%s</label>', $key, $d_key, ($d_val['value'] == 1) ? 'checked' : '', ($hidden) ? 'hidden="hidden"' : '', $d_val['name']);
                    } else {
                        $def[] = sprintf('<input type="text" name="%s_%s[]" placeholder="%s" %s/>', $key, $d_key, $d_val['name'], ($key == 'other') ? 'class="full_width"' : '');
                    }
                }
                for ($value_item = 1; $value_item <= $type['count']; $value_item++) {
                    $tval = array();
                    foreach ($type['default'] as $d_key => $d_val) {
                        $hidden = FALSE;
                        if (isset($d_val['hidden'])) {
                            $hidden = TRUE;
                        }
                        if ($hidden AND !in_array($d_key, array('number','other'))) {
                            $tval[] = sprintf('<label><input type="hidden" value="1" name="%s_%s[%s]"/></label>', $key, $d_key, $value_item);
                        } elseif (!in_array($d_key, array('number','other'))) {
                            if (isset($ticket_repair['komplekt'][$key]['value'][$d_key])) {
                                if (in_array($value_item, $ticket_repair['komplekt'][$key]['value'][$d_key])) {
                                    $d_val['value'] = 1;
                                }
                            }
                            $tval[] = sprintf('<label><input type="checkbox" value="1" name="%s_%s[%s]" %s %s/>%s</label>', $key, $d_key, $value_item, ($d_val['value'] == 1) ? 'checked' : '', ($hidden) ? 'hidden="hidden"' : '', $d_val['name']);
                        } else {
                            if (isset($ticket_repair['komplekt'][$key]['value'][$d_key][$value_item])) {
                                $d_val['value'] = $ticket_repair['komplekt'][$key]['value'][$d_key][$value_item];
                            }
                            $tval[] = sprintf('<input type="text" name="%s_%s[%s]" placeholder="%s" value="%s" %s/>', $key, $d_key, $value_item, $d_val['name'], $d_val['value'], ($key == 'other') ? 'class="full_width"' : '');
                        }
                    }
                    $val[] = sprintf('<div num="%s">%s</div>', $value_item, implode("", $tval));
                }
                $form['komplekt']['field'] .= sprintf('<div class="complect_line">'
                        . '<div class="line_name"><span>%s</span><input type="number" value="%s" step="1" min="0" max="20" class="line_counter" name="%s_value_count"/></div>'
                        . '<div class="line_default">%s</div>'
                        . '<div class="line_values">%s</div>'
                        . '</div>', $type['name'], $type['count'], $key, implode("", $def), implode("", $val));
            }
            $res = table($form, array('width' => '100%'));
        }
        return $res;
    }

    static public function array_repair() {
        return array(
            'firm' => array(
                'name' => 'Фирма',
                'value' => NULL,
                'attr' => ' autocomplete="off" onkeyup="set_var(this,\'autocomplete\',\'firm\',false);"',
                'addon' => '<div class="autocomplete" id="firm"></div>'),
            'problem' => array('name' => 'Проблема', 'value' => NULL, 'attr' => '', 'addon' => ''),
            'fio' => array(
                'name' => 'Сдал технику',
                'value' => NULL,
                'attr' => ' autocomplete="off" onkeyup="set_var(this,\'autocomplete\',\'fio\',false);"',
                'addon' => '<div class="autocomplete" id="fio"></div>'),
            'phone' => array('name' => 'Номер телефона', 'value' => NULL, 'attr' => '', 'addon' => ''),
            'komplekt' => array('name' => 'Комплектация', 'value' => NULL, 'attr' => '', 'addon' => ''),
        );
    }

    static public function array_repair_komplekt_type() {
        return array(
            'system_unit' => array(
                'name' => 'Системный блок',
                'count' => 0,
                'default' => array(
                    'system_unit' => array('name' => '', 'value' => 1, "hidden" => "hidden"),
                    'cable' => array('name' => 'Кабель питания', 'value' => 0),
                    'mouse' => array('name' => 'Мышь', 'value' => 0),
                    'keyboard' => array('name' => 'Клавиатура', 'value' => 0),
                    'other' => array('name' => 'Прочее', 'value' => ''),
                    'number' => array('name' => 'Инвентарный', 'value' => ''),
                ),
                'values' => array(),
            ),
            'monitor' => array(
                'name' => 'Монитор',
                'count' => 0,
                'default' => array(
                    'monitor' => array('name' => '', 'value' => 1, "hidden" => "hidden"),
                    'power' => array('name' => 'Блок питания', 'value' => 0),
                    'vga_cable' => array('name' => 'VGA кабель', 'value' => 0),
                    'dvi_cable' => array('name' => 'DVI кабель', 'value' => 0),
                    'hdmi_cable' => array('name' => 'HDMI кабель', 'value' => 0),
                    'power_cable' => array('name' => 'Кабель питания', 'value' => 0),
                    'other' => array('name' => 'Прочее', 'value' => ''),
                    'number' => array('name' => 'Инвентарный', 'value' => ''),
                ),
                'values' => array(),
            ),
            'laptop' => array(
                'name' => 'Ноутбук',
                'count' => 0,
                'default' => array(
                    'laptop' => array('name' => '', 'value' => 1, "hidden" => "hidden"),
                    'power' => array('name' => 'Блок питания', 'value' => 0),
                    'external_dvd' => array('name' => 'Внешний DVD', 'value' => 0),
                    'external_floppy' => array('name' => 'Внешний FLOPPY', 'value' => 0),
                    'case' => array('name' => 'Сумка', 'value' => 0),
                    'power_filter' => array('name' => 'Сетевой фильтр', 'value' => 0),
                    'mouse' => array('name' => 'Мышь', 'value' => 0),
                    'other' => array('name' => 'Прочее', 'value' => ''),
                    'number' => array('name' => 'Инвентарный', 'value' => ''),
                ),
                'values' => array(),
            ),
            'projector' => array(
                'name' => 'Проектор',
                'count' => 0,
                'default' => array(
                    'projector' => array('name' => '', 'value' => 1, "hidden" => "hidden"),
                    'control_panel' => array('name' => 'Пульт', 'value' => 0),
                    'case' => array('name' => 'Сумка', 'value' => 0),
                    'vga_cable' => array('name' => 'VGA кабель', 'value' => 0),
                    'power_cable' => array('name' => 'Кабель питания', 'value' => 0),
                    'other' => array('name' => 'Прочее', 'value' => ''),
                    'number' => array('name' => 'Инвентарный', 'value' => ''),
                ),
                'values' => array(),
            ),
            'printer' => array(
                'name' => 'Принтер',
                'count' => 0,
                'default' => array(
                    'printer' => array('name' => '', 'value' => 1, "hidden" => "hidden"),
                    'power_cable' => array('name' => 'Кабель питания', 'value' => 0),
                    'usb_cable' => array('name' => 'USB кабель', 'value' => 0),
                    'trays' => array('name' => 'Лотки', 'value' => 0),
                    'other' => array('name' => 'Прочее', 'value' => ''),
                    'number' => array('name' => 'Инвентарный', 'value' => ''),
                ),
                'values' => array(),
            ),
            'mfu' => array(
                'name' => 'МФУ',
                'count' => 0,
                'default' => array(
                    'mfu' => array('name' => '', 'value' => 1, "hidden" => "hidden"),
                    'power_cable' => array('name' => 'Кабель питания', 'value' => 0),
                    'usb_cable' => array('name' => 'USB кабель', 'value' => 0),
                    'trays' => array('name' => 'Лотки', 'value' => 0),
                    'other' => array('name' => 'Прочее', 'value' => ''),
                    'number' => array('name' => 'Инвентарный', 'value' => ''),
                ),
                'values' => array(),
            ),
            'scaner' => array(
                'name' => 'Сканер',
                'count' => 0,
                'default' => array(
                    'scaner' => array('name' => '', 'value' => 1, "hidden" => "hidden"),
                    'power' => array('name' => 'Блок питания', 'value' => 0),
                    'usb_cable' => array('name' => 'USB кабель', 'value' => 0),
                    'other' => array('name' => 'Прочее', 'value' => ''),
                    'number' => array('name' => 'Инвентарный', 'value' => ''),
                ),
                'values' => array(),
            ),
            'other' => array(
                'name' => 'Прочее',
                'count' => 0,
                'default' => array(
                    'other' => array('name' => 'Прочее', 'value' => ''),
                    'number' => array('name' => 'Инвентарный', 'value' => ''),
                ),
                'values' => array(),
            ),
        );
    }

    static public function save() {
        global $err_fiedls, $DB, $USER, $USERS;
        $id = (INT) filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
        $head = trim(filter_input(INPUT_POST, "head"));
        $area = filter_input(INPUT_POST, "area", FILTER_VALIDATE_INT);
        $user_id = filter_input(INPUT_POST, "user_id", FILTER_VALIDATE_INT);
        $state = filter_input(INPUT_POST, "state", FILTER_VALIDATE_INT);
        $korpus = filter_input(INPUT_POST, "korpus", FILTER_VALIDATE_INT);
        $kab = trim(filter_input(INPUT_POST, "kab"));
        $inventory = filter_input(INPUT_POST, "inventory");
        $message = trim(filter_input(INPUT_POST, "message"));
        $event_dt = filter_input(INPUT_POST, "event_dt");
        $event_time = filter_input(INPUT_POST, "event_time");
        if (empty($event_dt)) {
            $event_dt = NULL;
        }
        if (empty($event_time)) {
            $event_time = NULL;
        }
        if(empty($korpus) OR $korpus == 'NULL'){
            $korpus = NULL;
        }
        $repair = self::input_repair($area);
        $complete = FALSE;
        if (empty($head)) {
            set_error_field("head");
        }
        if (preg_match("/\-|\—/", $kab, $f)) {
            set_error_field("kab");
            set_error('Не указывайте диапазон кабинетов, перечислите все кабинеты.');
        }
        $inventory = preg_replace("/\-+/", "-", $inventory);
        if ($inventory === "-") {
            $inventory = "";
            set_error_field("inventory");
            set_error('Ну что еще за &laquo;-&raquo; в инвентарном??? Или пишем, или нет.');
        }
        $group = new group($area);
        if (empty($inventory) AND $group->repair_type > 0) {
            set_error_field("inventory");
            set_error('В разделах &laquo;Ремонт&raquo; инвентаный обязателен.');
        }
        if (count($err_fiedls) > 0) {
            set_error('Заполните обязательные поля.');
        } else {
            if (!empty($kab)) {
                $kab = preg_replace("/[\s\,\-]+/", ", ", $kab);
            }
            $status = 0;
            if ($user_id > 0) {
                if (in_array($user_id, array(0))) {
                    $status = 0;
                } else {
                    $status = 1;
                }
            }
            if ($id == 0) {
                $ins = $DB->prepare("INSERT INTO `tickets`(`area`, `head`, `korpus`, `kab`, `inventory`, "
                        . "`repair`, `descr`, `state`, `status`, `user_id`, `event_dt`, `event_time`) "
                        . "VALUES (:area, :head, :korpus, :kab, :inventory, :repair, :descr, :state, :status, :user_id, :event_dt, :event_time)");
                $ins->execute(array(
                    'area' => $area,
                    'head' => htmlspecialchars($head),
                    'korpus' => $korpus,
                    'kab' => $kab,
                    'inventory' => htmlspecialchars($inventory),
                    'repair' => json_encode($repair),
                    'descr' => htmlspecialchars($message),
                    'state' => $state,
                    'status' => $status,
                    'user_id' => $user_id,
                    'event_dt' => $event_dt,
                    'event_time' => $event_time,
                ));
                if ($ins->rowCount() > 0) {
                    $id = $DB->lastInsertId();
                    $mes = '';
                    if (in_array($user_id, array(0))) {
                        $mes = 'Нет ответственного';
                    } else {
                        $fio_u = "Не определен";
                        if (isset($USERS[$user_id])) {
                            $fio_u = $USERS[$user_id]['fios'];
                        }
                        $mes = sprintf('Ответственным назначен: %s', $fio_u);
                    }
                    self::set_ticket_event($id, $USER->id, $mes, 1);
                    if (!empty($message)) {
                        self::set_ticket_event($id, $USER->id, htmlspecialchars($message), 0);
                    }
                    return $id;
                } else {
                    set_error('Ошибка: заявка не добавлена.');
                    debug($ins);
                }
            } else {
                $data = self::get_ticket_data($id);
                $attach = self::save_attach($id);
                if ($data['area'] != $area
                        OR $data['head'] != htmlspecialchars($head)
                        OR $data['korpus'] != $korpus
                        OR $data['kab'] != $kab
                        OR $data['inventory'] != htmlspecialchars($inventory)
                        OR $data['repair'] != json_encode($repair)
                        OR $data['state'] != $state
                        OR $data['user_id'] != $user_id
                        OR $data['event_dt'] != $event_dt
                        OR $data['event_time'] != $event_time
                ) {
                    $upd = $DB->prepare("UPDATE `tickets` SET `area`=:area,`head`=:head,`korpus`=:korpus,`kab`=:kab,`inventory`=:inventory,"
                            . "`repair`=:repair,`state`=:state, `status`=:status, `user_id`=:user_id, "
                            . "`event_dt`=:event_dt, `event_time`=:event_time WHERE `id`=:id");
                    $upd->execute(array(
                        'area' => $area,
                        'head' => htmlspecialchars($head),
                        'korpus' => $korpus,
                        'kab' => $kab,
                        'inventory' => htmlspecialchars($inventory),
                        'repair' => json_encode($repair),
                        'state' => $state,
                        'status' => $status,
                        'user_id' => $user_id,
                        'event_dt' => $event_dt,
                        'event_time' => $event_time,
                        'id' => $id,
                    ));
                    if ($upd->rowCount() > 0) {
                        $data['user_id'] = (INT) $data['user_id'];
                        if ($data['user_id'] !== $user_id) {
                            $mes = '';
                            if (in_array($user_id, array(0))) {
                                $mes = 'Нет ответственного';
                            } else {
                                $fio_u = "Не определен";
                                if (isset($USERS[$user_id])) {
                                    $fio_u = $USERS[$user_id]['fios'];
                                }
                                $mes = sprintf('Ответственным назначен: %s', $fio_u);
                            }
                            if (!empty($mes)) {
                                self::set_ticket_event($id, $USER->id, $mes, 1);
                            }
                        }
                        return $id;
                    } else {
                        set_error('Ошибка: заявка не обновлена.');
                    }
                } else {
                    /* if(!$attach){
                      set_error('Ошибка: ничего не изменилось.');
                      } */
                    return $id;
                }
            }
        }
        return FALSE;
    }

    static public function save_attach($ticket_id, $message_id = 0, $field_name = 'attach') {
        global $DB, $USER;
        $add = TRUE;
        if (isset($_FILES[$field_name])) {
            if (is_array($_FILES[$field_name]['name'])) {
                $uploaded_files = array();
                foreach ($_FILES[$field_name]['name'] as $key => $val) {
                    //if($_FILES[$field_name]['error'][$key] )
                    $file = new File($ticket_id, $message_id);
                    $ad = $file->upload_file($val, $_FILES[$field_name]['tmp_name'][$key], $_FILES[$field_name]['error'][$key], $_FILES[$field_name]['size'][$key]);
                    if (!$ad) {
                        $add = FALSE;
                    } else {
                        $uploaded_files[] = sprintf('<a href="/files/?file=%s"  target="_blank">%s</a> [%s]', $ad, $val, File::transform_size($_FILES[$field_name]['size'][$key]));
                    }
                }
                if (count($uploaded_files) > 0 AND $message_id == 0) {
                    $TE = self::set_ticket_event(
                                    $ticket_id, $USER->id, sprintf('Загрузил файлы: %s', implode(", ", $uploaded_files)), 1);
                }
            }
        }
        return $add;
    }

    static public function input_repair($area = 0) {
        $res = array();
        $repair = self::array_repair();
        $repair_komplekt_type = self::array_repair_komplekt_type();
        $found1 = $found2 = FALSE;
        $group = new group($area);
        if ($group->repair_type > 0) {
            foreach ($repair as $type => $line) {
                $res[$type] = filter_input(INPUT_POST, $type);
                if (!empty($res[$type])) {
                    $found1 = TRUE;
                } elseif ($type !== 'komplekt') {
                    //set_error(sprintf('Заполните поле <b>%s</b> в блоке «Ремонт».',$line['name']));
                    set_error_field($type);
                }
            }
            $res['komplekt'] = array();
            foreach ($repair_komplekt_type as $key => $type) {
                if ($group->repair_type == 2 AND in_array($key, array('system_unit', 'monitor', 'laptop', 'projector'))) {
                    continue;
                }
                $res['komplekt'][$key] = array(
                    'count' => filter_input(INPUT_POST, $key . "_value_count", FILTER_VALIDATE_INT),
                    'value' => array(),
                );
                foreach ($type['default'] as $d_key => $d_val) {
                    $name = $key . "_" . $d_key;
                    if (isset($_POST[$name])) {
                        if (isset($_POST[$name][0])) {
                            unset($_POST[$name][0]);
                        }
                        if (count($_POST[$name]) > 0) {
                            if ($d_key == "other") {
                                $val = $_POST[$name];
                                foreach ($val as $valval) {
                                    if (!empty($valval) AND ! $found2) {
                                        $found2 = TRUE;
                                    }
                                }
                            } else {
                                $val = array_keys($_POST[$name]);
                                if (!empty($val) AND ! $found2) {
                                    $found2 = TRUE;
                                }
                            }
                            $res['komplekt'][$key]['value'][$d_key] = $val;
                        }
                    }
                }
            }
            if (!$found2) {
                $res['komplekt'] = array();
                set_error_field("komplekt");
                set_error("Не выбрана комплектация");
            }
            if (!$found1) {
                $res = array();
                set_error_field("komplekt");
                set_error("Не заполнены обязательные поля приемки оборудования");
            }
        }
        return $res;
    }

    static public function last_users_action() {
        global $DB, $USER;
        $time = 0;
        if (count($USER->ticket_groups) > 0) {
            $sql = sprintf("SELECT MAX(E.dt) as dt FROM `tickets_event` E, `tickets` T WHERE T.id=E.ticket_id AND T.area IN (%s)", implode(",", $USER->ticket_groups));
            $sel = $DB->prepare($sql);
            $sel->execute();
            if ($sel->rowCount() > 0) {
                $d = $sel->fetch();
                if ($d['dt'] > 0) {
                    $time = (INT) $d['dt'];
                }
            }
        }
        return $time;
    }

    static public function cancel_ticket_give($id) {
        global $DB;
        $del = $DB->prepare("DELETE FROM `tickets_give` WHERE `id`=:id");
        $del->execute(array('id' => $id));
        if ($del->rowCount() > 0) {
            return TRUE;
        } else {
            set_error('Ошибка отмены передачи заявки.');
            return FALSE;
        }
    }

    static public function confirm_ticket_give($id) {
        global $DB,$DIV;
        $sel = $DB->prepare("SELECT * FROM `tickets_give` WHERE `id`=:id");
        $sel->execute(array('id' => $id));
        if ($sel->rowCount() > 0) {
            $gd = $sel->fetch();
            $t = new Ticket($gd['ticket_id']);
            if ($t->user_id !== $t->new_user_id AND $t->user_id == $t->new_user_init) {
                if (self::set_ticket_value($gd['ticket_id'], "user_id", $t->new_user_id)) {
                    self::cancel_ticket_give($id);
                    self::set_ticket_event($gd['ticket_id'], $t->user_id, 'Передал на: ' . $DIV->get_user_fio($t->new_user_id), 1);
                    return TRUE;
                } else {
                    set_error('Не удалось принять заявку');
                    return FALSE;
                }
            } else {
                set_error('Ошибка согласия передачи заявки: обнаружены ошибки, возможно Вы уже ответственный или заявка передана кому то другому.');
            }
        } else {
            set_error('Ошибка согласия передачи заявки.');
            return FALSE;
        }
    }

    static public function view_ticket_head($ticket) {
        global $USER, $USERS, $STATE;
        $html = '';
        $html .= sprintf('<div class="view_ticket_number">%s</div><h3>%s</h3>', $ticket->id, $ticket->head_view);
        $html .= sprintf('<div><b>Ссылки:</b> '
                . '<a target="_blank" href="/?action=view&id=%s">Приватная</a></div>', $ticket->id, md5($ticket->id . substr($ticket->head, 0, 5)), $ticket->id);
        $new_user_info = '';
        if ($ticket->new_user_id > 0) {
            if ($ticket->new_user_id == $ticket->user_id) {
                set_error('Внутренняя ошибка: Заявка передается на ответственного');
            }
            $cancel_sub_text = '';
            if ($ticket->new_user_id === $USER->id AND $ticket->user_id == $ticket->new_user_init) {
                $new_user_info = ' -> ' . show_text('Вам передают заявку: ', '');
                if ($ticket->new_user_id !== $ticket->user_id) {
                    $new_user_info .= sprintf('<span class="ticket_page_button for_head confirm_ticket_give" title="Принять заявку на себя" onclick="confirm_set(this,\'ticket_event\',\'confirm_give\',\'update_ticket_view\',%s);"></span>', $ticket->give_id);
                } elseif ($ticket->new_user_id == $ticket->user_id) {
                    $new_user_info .= '!! Вы не можете принять заявку, она и так уже Ваша. Нажмите "Отменить заявку"';
                    $cancel_sub_text = "\n[Заявка останется Вашей]";
                }
            } elseif ($ticket->new_user_id !== $USER->id AND $ticket->user_id == $ticket->new_user_init) {
                $new_user_fio = 'UNKNOWN';
                if (isset($USERS[$ticket->new_user_id])) {
                    $new_user_fio = $USERS[$ticket->new_user_id]['fios'];
                }
                $new_user_info = " -> " . show_text(
                                sprintf('Передается: %s', $new_user_fio), $new_user_fio);
            } elseif ($ticket->user_id !== $ticket->new_user_init) {
                $new_user_info = '<span class="red bold">Ошибка</span>';
                $cancel_sub_text = "\n[Передача не удастся, есть ошибки]";
            }
            if ($ticket->new_user_id == $ticket->user_id
                    OR $ticket->new_user_id == $USER->id
                    OR in_array($ticket->area, $USER->group_manager)
                    OR isset($USER->groups['admin'])) {
                $new_user_info .= sprintf(' <span class="ticket_page_button for_head cancel_ticket_give" title="%s%s" onclick="confirm_set(this,\'ticket_event\',\'cancel_give\',\'update_ticket_view\',%s);"></span>', ($USER->id == $ticket->user_id) ? 'Отменить передачу' : 'Отказать в передаче заявки', $cancel_sub_text, $ticket->give_id);
            }
        }
        $user_action_switch = $user_action_correct = $user_action_cansel = $user_action_renew = FALSE;
        if ($USER->id === $ticket->user_id AND $ticket->status == 1) {
            $user_action_switch = TRUE;
        }
        $action_button = array();
        if (in_array($ticket->area, $USER->group_manager) OR isset($USER->groups['admin'])) {
            switch ($ticket->status) {
                case 0:
                case 1:
                case 5:
                    $user_action_switch = $user_action_correct = TRUE;
                    break;
                case 2:
                    $user_action_cansel = TRUE;
                    $user_action_switch = FALSE;
                    break;
                case 3:
                    $user_action_switch = $user_action_renew = TRUE;
                    break;
            }
        }
        $user_action_param = array(
            'tvalue' => $ticket->user_id
        );
        if ($ticket->new_user_id > 0) {
            $user_action_switch = $user_action_correct = FALSE;
        }
        if (!$user_action_switch) {
            $user_action_param['disabled'] = 'disabled';
        } elseif ($USER->id === $ticket->user_id AND $user_action_switch AND $ticket->new_user_id == 0 AND $ticket->status == 1) {
            $action_button[] = sprintf('<span class="ticket_page_button for_head switch_user" title="Передать" onclick="confirm_set(\'#user_id\',\'ticket_event\',\'set_give\',\'update_ticket_view\',%s,true,\'Передать\')"></span>', $ticket->id);
        }
        if ($user_action_correct) {
            $action_button[] = sprintf('<span class="ticket_page_button for_head set_user" title="Назначить ответственным" onclick="confirm_set(\'#user_id\',\'ticket_event\',\'set_user_id\',\'update_ticket_view\',%s,true,\'Назначить ответственным\')"></span>', $ticket->id);
        }
        if ($user_action_cansel) {
            $action_button[] = sprintf('<span class="ticket_page_button for_head cancel_confirm" title="Отменить выполнение" onclick="confirm_set(\'#user_id\',\'ticket_event\',\'cancel_confirm\',\'update_ticket_view\',%s,false,\'Отменить выполнение\')"></span>', $ticket->id);
        }
        if ($user_action_renew) {
            $action_button[] = sprintf('<span class="ticket_page_button for_head renew_edit_ticket" title="Возобновить и назначить ответственного" onclick="confirm_set(\'#user_id\',\'ticket_event\',\'renew_edit_ticket\',\'update_ticket_view\',%s,false,\'Возобновить заявку\')"></span>', $ticket->id);
        }
        $area_users = $sign_users = self::get_user_from_area($ticket->area);
        $html .= sprintf('<div><b>Ответственный:</b> %s%s%s</div>', print_select(
                        "user_id", $area_users, $ticket->user_id, $user_action_param), implode(" ", $action_button), $new_user_info);
        $area_buttons = array();
        $ticket->area = (INT) $ticket->area;
        $group = new group($ticket->area);
        if ($group->repair_type > 0) {
            $area_buttons[] = sprintf('<span class="ticket_page_button for_head preview_remont" title="Просмотреть сведения" onclick="exec_remote(\'show_remont_info\',false,{\'ticket_id\':%s});"></span>', $ticket->id);
            $area_buttons[] = sprintf('<span class="ticket_page_button for_head print_remont" title="Распечатать приемку" onclick="exec_remote(\'print_remont\',false,{\'ticket_id\':%s});"></span>', $ticket->id);
        }
        $html .= sprintf('<div><b>Направление:</b> %s %s</div>', $ticket->area_name, implode(" ", $area_buttons));
        $state_icon = '';
        if ($ticket->state != 0) {
            $state_icon = sprintf('<span class="ticket_page_icon state%s"></span>', $ticket->state);
        }
        $html .= sprintf('<div><b>Состояние:</b> %s%s</div>', $state_icon, print_select(
                        "state_id", $STATE, $ticket->state, array(
            'tvalue' => $ticket->state,
            'onchange' => 'set_var(this,\'ticket_event\',\'change_ticket_state\',\'update_ticket_view\',' . $ticket->id . ')'
                        )
                )
        );
        if (!empty($ticket->position)) {
            $html .= sprintf('<div><b>Местонахождение:</b> %s</div>', $ticket->position);
        }
        if (!empty($ticket->event_dt)) {
            $date_e = date("H:i d.m.Y", strtotime($ticket->event_dt . " 00:00:00"));
            if (!empty($ticket->event_time)) {
                $date_e = date("H:i d.m.Y", strtotime($ticket->event_dt . " " . $ticket->event_time));
            }
            $html .= sprintf('<div><b>Дата выполнения:</b> %s</div>', $date_e);
        }
        if (!empty($ticket->inventory)) {
            $html .= sprintf('<div><b>Инвентарный:</b> %s</div>', $ticket->inventory);
        }
        if (isset($sign_users[0])) {
            unset($sign_users[0]);
        }
        if (isset($sign_users[$ticket->user_id])) {
            unset($sign_users[$ticket->user_id]);
        }
        $sign_users[0] = 'Выберите';
        //if(count($ticket->user_sign) > 0){
        foreach ($ticket->user_sign as $uk => $us) {
            if (isset($sign_users[$uk])) {
                unset($sign_users[$uk]);
            }
        }
        $add_sign = '';
        if (count($sign_users) > 1 AND $ticket->status != 3) {
            $add_sign = print_select(
                    "user_sign", $sign_users, 0, array('onchange' => 'set_var(this,\'ticket_event\',\'sign_user\',\'update_ticket_view\',' . $ticket->id . ')',
                'title' => 'Подписать пользователя'));
        }
        if ($ticket->status != 3) {
            $html .= sprintf('<div><b>Подписаны:</b> %s %s</div>', implode(", ", $ticket->user_sign), $add_sign);
        }

        //}
        return $html;
    }

    static public function view_attachment_ticket($ticket) {
        global $DIV;
        $html = '';
        if (count($ticket->attachments) > 0) {
            $all_attach = $ticket_attach = $message_attach = array();
            foreach ($ticket->attachments as $file) {
                $del_link = File::delete_link($file['id'], $file['user_id'], $ticket->area);
                $file_data = array(
                    'attr' => array('file' => 'file_' . $file['id']),
                    File::get_link($file),
                    array(
                        'attr' => array('align' => "right"),
                        'value' => File::transform_size($file['size'])
                    ),
                    array(
                        'attr' => array('class' => "show_full"),
                        'value' => date("Y.m.d H:i", strtotime($file['upload'])),
                    ),
                    array(
                        'attr' => array('class' => "show_full"),
                        'value' => $DIV->get_user_fio($file['user_id']),
                    ),
                    $del_link
                );
                $all_attach[] = $file_data;
                if ($file['message_id'] == 0) {
                    $ticket_attach[] = $file_data;
                } else {
                    $message_attach[] = $file_data;
                }
            }
            $text_attach = array('<span onclick="switch_block(\'.attachment_links\',\'.attachment_links.all_attach\');" class="ticket_page_button all_attach" title="Все прикрепления"></span>');
            if (count($ticket_attach) > 0 AND ( count($ticket_attach) !== count($all_attach))) {
                $text_attach[] = '<span onclick="switch_block(\'.attachment_links\',\'.attachment_links.ticket_attach\');" class="ticket_page_button ticket_attach" title="Прикрепления заявки"></span>';
            }
            if (count($message_attach) > 0 AND ( count($message_attach) !== count($all_attach))) {
                $text_attach[] = '<span onclick="switch_block(\'.attachment_links\',\'.attachment_links.message_attach\');" class="ticket_page_button message_attach" title="Прикрепления сообщений"></span>';
            }
            $close_butt = '<span onclick="hide_block(\'.attachment_links\');" class="ticket_page_button close" title="Закрыть"></span>';
            $html .= sprintf('<div><b>Прикрепления:</b> %s</div>', implode(" ", $text_attach));
            $html .= sprintf('<div class="attachment_links all_attach">%s<p>Все прикрепления:</p>%s</div>', $close_butt, table($all_attach));
            if (count($ticket_attach) > 0 AND ( count($ticket_attach) !== count($all_attach))) {
                $html .= sprintf('<div class="attachment_links ticket_attach">%s<p>Прикрепления заявки:</p>%s</div>', $close_butt, table($ticket_attach));
            }
            if (count($message_attach) > 0 AND ( count($message_attach) !== count($all_attach))) {
                $html .= sprintf('<div class="attachment_links message_attach">%s<p>Прикрепления сообщений:</p>%s</div>', $close_butt, table($message_attach));
            }
        }
        return $html;
    }

    static public function view_ticket_message($ticket) {
        global $USER,$DIV;
        $res = array();
        foreach ($ticket->events as $eventid => $event) {
            if (count($event['attach']) > 0) {
                $attach = array();
                foreach ($event['attach'] as $file) {
                    $del_link = File::delete_link($file['id'], $file['user_id'], $ticket->area);
                    $attach[] = array(
                        'attr' => array('file' => 'file_' . $file['id']),
                        File::get_link($file),
                        array(
                            'attr' => array('align' => "right"),
                            'value' => File::transform_size($file['size'])
                        ),
                        array(
                            'attr' => array('class' => "show_full"),
                            'value' => date("Y.m.d H:i", strtotime($file['upload'])),
                        ),
                        array(
                            'attr' => array('class' => "show_full"),
                            'value' => $DIV->get_user_fio($file['user_id']),
                        ),
                        $del_link
                    );
                }
                $event['mes'] .= sprintf('<div class="message_attach"><label class="for_block">Прикрепления</label>%s</div>', table($attach));
            }
            $view_id = '';
            if($event['status'] == 0){
                $view_id = sprintf('<span class="message_id">#%s</span>',$eventid);
            }
            $res[] = array(
                'attr' => $event['style'],
                array(
                    'attr' => array('class' => 'message_info'),
                    'value' => sprintf($view_id
                            . '<span class="message_user">%s</span>'
                            . '<span class="message_time">%s</span>',$event['user'], $event['time'])
                ),
                sprintf('<span class="message_action">%s</span>%s', $event['edit'], $event['mes']),
            );
        }
        return table($res, array('border' => "1"));
    }

    static public function get_action_ticket($ticket) {
        global $USER, $CONFIG;
        $action_button = array();
        switch ($ticket->status) {
            case 0:
            case 5:
                $action_button[] = sprintf('<span class="ticket_action set_my" title="Взять заявку" onclick="set_var(this,\'ticket_event\',\'give_my\',\'update_ticket_view\',%s,false)"></span>', $ticket->id);
                break;
            case 1:
                if ($USER->id == $ticket->user_id) {
                    $action_button[] = sprintf('<span class="ticket_action calcel_my" title="Снять ответственность" onclick="confirm_set(this,\'ticket_event\',\'calcel_my\',\'update_ticket_view\',%s,false,\'Снять ответственность\')"></span>', $ticket->id);
                    $action_button[] = sprintf('<span class="ticket_action pre_confirm" title="Подтвердить выполнение" onclick="confirm_set(this,\'ticket_event\',\'pre_confirm\',\'update_ticket_view\',%s,false,\'Подтвердить выполнение\')"></span>', $ticket->id);
                } else {
                    $action_button[] = sprintf('<span class="ticket_action pre_confirm_other" title="Подтвердить выполнение заявки ответственным" onclick="confirm_set(this,\'ticket_event\',\'pre_confirm_other\',\'update_ticket_view\',%s,false,\'Подтвердить выполнение заявки ответственным\')"></span>', $ticket->id);
                }
                break;
            case 2:
                if (in_array($ticket->area, $USER->group_manager) OR isset($USER->groups['admin'])) {
                    $action_button[] = sprintf('<span class="ticket_action cancel_confirm" title="Отменить выполнение" onclick="confirm_set(\'#user_id\',\'ticket_event\',\'cancel_confirm\',\'update_ticket_view\',%s,false,\'Отменить выполнение\')"></span>', $ticket->id);
                    $action_button[] = sprintf('<span class="ticket_action confirm_close" title="Подтвердить выполнение и закрыть заявку" onclick="confirm_set(\'#user_id\',\'ticket_event\',\'confirm_close\',\'update_ticket_view\',%s,false,\'Закрыть заявку\')"></span>', $ticket->id);
                }
                break;
            case 3:
                $action_button[] = sprintf('<span class="ticket_action renew_ticket" title="Возобновить заявку" onclick="confirm_set(\'#user_id\',\'ticket_event\',\'renew_ticket\',\'update_ticket_view\',%s,false,\'Возобновить заявку\')"></span>', $ticket->id);
                break;
        }
        if ($ticket->status != 3) {
            $action_button[] = sprintf('<a href="/?action=edit&id=%s" title="Редактировать" class="ticket_action edit"></a>', $ticket->id);
            if ($ticket->status != 2 AND ( in_array($ticket->area, $USER->group_manager) OR isset($USER->groups['admin']))) {
                $action_button[] = sprintf('<span class="ticket_action confirm_close_p" title="Закрыть заявку" onclick="confirm_set(\'#user_id\',\'ticket_event\',\'confirm_close_p\',\'update_ticket_view\',%s,false,\'Закрыть заявку\')"></span>', $ticket->id);
            }
        }
        return implode(" ", $action_button);
    }

    static public function view_message_form($ticket_id, $id = 0) {
        global $DB;
        $mes = '';
        if ($id > 0) {
            $data = self::get_event($id);
            if ($data !== FALSE) {
                $mes = $data['mes'];
            }
        }

        $html = '<form method="POST" id="edit_message" enctype="multipart/form-data">';
        $html .= '<input type="hidden" name="action" value="save_ticket_message" />';
        $html .= sprintf('<input type="hidden" name="ticket_id" value="%s" />', $ticket_id);
        $html .= sprintf('<input type="hidden" name="message_id" value="%s" />', $id);
        $html .= sprintf('<textarea name="message_text" id="message_text" rows="3" placeholder="%s
%s">%s</textarea>', 
                'Поставьте в начале ! '.t('для постановки задачи'),
                '! '.t('текст задачи'),
                $mes);
        $html .= '<span class="auto_save_time"></span>';
        $html .= File::form_max_size('<input type="file" name="attach[]" multiple/>');
        $html .= '<div class="field full center"><input type="button" value="Сохранить" onclick="save_form(\'edit_message\');" /></div>';
        $html .= '</form>';
        return $html;
    }

}

// Не формализовано

function send_mes($ticket_id, $mes = '', $user_id_send = 0) {
    global $USERS, $USER, $KORPUS;
    $emails = array();
    $sql = sprintf("SELECT * FROM tickets WHERE id=%u", $ticket_id);
    $sth = mysql_query($sql);
    $ticket = mysql_fetch_assoc($sth);
    switch ($ticket['status']) {
        case 1:
        case 2:
            $subject = "ОмГПУ: новое сообщение по заявке №".$ticket_id;
            if ($ticket['status'] == 2)
                $mes = 'Заявка закрыта.';
            $sql = sprintf("SELECT user_id FROM tickets_user WHERE sign=1 AND ticket_id='%u'", $ticket_id);
            $sth = mysql_query($sql);
            while ($ref = mysql_fetch_assoc($sth)) {
                $user_sign[$ref['user_id']] = 1;
            }
            foreach ($USERS as $user_id => $user_info) {
                if (!isset($user_info['emails'])) {
                    continue;
                }
                foreach ($user_info['emails'] as $email) {
                    if ($user_id_send == $user_id)
                        continue;
                    if ($ticket['user_id'] == $user_id && $ticket['status'] > 0)
                        $emails[$email] = 1;
                    if (isset($user_sign[$user_id]))
                        $emails[$email] = 1;
                }
            }
            break;
    }
    $input_kab = '';
    if (isset($KORPUS[$ticket['korpus']])) {
        $input_kab = $KORPUS[$ticket['korpus']];
    }
    if (!empty($ticket['kab'])) {
        $input_kab .= ', каб ' . $ticket['kab'];
    }
    $mail_body = $ticket['head'] . ' (' . $input_kab . ')';
    if ($mes)
        $mail_body .= "\nСообщение: $mes";
    foreach ($emails as $recipient => $null) {
        //mail($recipient, $subject, $mail_body, $header);
        //phpmailer_sendmail($recipient, '1234', $subject, $mail_body);
    }
}

function get_areas_newmes($user_id) {
    $areas_newmes = array();
    $sql = sprintf("SELECT dt,ticket_id FROM tickets_user WHERE user_id=%u AND ticket_id IN (SELECT id  FROM `tickets` WHERE `status` != 3)", $user_id);
#    $sql = sprintf("SELECT A.dt,A.ticket_id FROM tickets_user A left outer join tickets B on A.ticket_id=B.id WHERE A.user_id=%u AND B.status != 3",$user_id);
    $sth = mysql_query($sql);
    while ($ref = mysql_fetch_assoc($sth)) {
        $ticket_last_read[$ref['ticket_id']] = $ref['dt'];
    }
    $sth = mysql_query("SELECT id, area FROM tickets WHERE status != 3");
    while ($ref = mysql_fetch_assoc($sth)) {
        if (!isset($ticket_last_read[$ref['id']]) || $ticket_last_read[$ref['id']] < get_last_mes_dt($ref['id'], $user_id))
            $areas_newmes[$ref['area']][$ref['id']] = true;
    }
    return $areas_newmes;
}

function get_areas_count($user_id, $area) {
    $sql = sprintf("SELECT count(*) as `count` FROM `tickets` WHERE user_id=%u AND status!=3 AND `area`=%u", $user_id, $area);
    $sth = mysql_query($sql);
    $tmp = mysql_fetch_array($sth);
    return $tmp['count'];
}

function get_last_mes_dt($id, $user_id) {
    $sql = sprintf("SELECT max(dt) as dt FROM tickets_event WHERE NOT (mes=1 AND status=1) AND user_id!=%u AND ticket_id=%u", $user_id, $id);
    $sth2 = mysql_query($sql);
    return @mysql_result($sth2, 0);
}

function get_users_area($area) {
    global $USERS;
    $TICKET_USER = array();
    foreach ($USERS as $key => $value) {
        if (isset($value['ticket_groups'])) {
            if (in_array($area, $value['ticket_groups']) && $value['enable'] == 1) {
                $TICKET_USER[$key] = $value;
            }
        }
    }
    return $TICKET_USER;
}

function get_count_user_ticket($user_id, $period = 'month', $type = "2,3") {
    $results = 0;
    if ($period == 'month') {
        $start = date("Y-m-01");
        $end = date("Y-m-d");
    } else {
        $start = date("2005-01-01");
        $end = date("Y-m-d");
    }
    $start = strtotime($start);
    $end = strtotime($end . " 23:59:59");
    $sql = sprintf("SELECT T.id,T.status,max(E.dt) as dt FROM `tickets` T, `tickets_event` E 
                where T.id=E.ticket_id AND T.user_id='%s' AND E.dt >='%s' AND E.dt <='%s' AND T.status IN (%s) group by T.id", $user_id, $start, $end, $type);
    $tickets = mysql_query($sql);
    while ($ticket = mysql_fetch_assoc($tickets)) {
        if ($start <= $ticket['dt'] AND $ticket['dt'] <= $end) {
            $results++;
        }
    }
    return $results;
}
