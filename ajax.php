<?php

/*
 * AJAX lgobal requests 
 *  */

require "assets/config.php";
$action = filter_input(INPUT_POST, "action");
$param = filter_input(INPUT_POST, "param");
$subparam = filter_input(INPUT_POST, "subparam");
$value = filter_input(INPUT_POST, "value");
$id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
$execute = filter_input(INPUT_POST, "execute");
$ajax = new AJAX();
if ($USER->id == 0 AND!in_array($action, array("login", 'reg_my_org')) AND!in_array($execute, array('give_my_org'))) {
    $ajax->alert('Вы не авторизованы');
    $ajax->redirect("/");
    $ajax->execute();
    exit();
}
if (!empty($action)) {
    switch ($action) {
        case "execute": break; //only run $force_action
        case 'ipsubnet':
            $input =  trim(filter_input(INPUT_POST, 'my_net_info'));
            $input_ip =  trim(filter_input(INPUT_POST, 'my_net_ip'));
            set_param('util', 'net_info', $input);
            set_param('util', 'net_ip', $input_ip);
            $c = new ipcalc($input,$input_ip);
            $ajax->replace('#calcres', $c->view());
            break;
        case 'iputils':
            $address = trim(filter_input(INPUT_POST, 'inpaddress'));
            $ajax->replace("#ipres", iputil($param,$address));
            break;
        case 'reg_my_org':
            $div_fio = trim(filter_input(INPUT_POST, 'div_fio'));
            $div_mail = filter_input(INPUT_POST, 'div_mail', FILTER_VALIDATE_EMAIL);
            $user_login = trim(filter_input(INPUT_POST, 'user_login'));
            $user_pass = filter_input(INPUT_POST, 'user_pass');
            $user_passc = filter_input(INPUT_POST, 'user_passc');
            $user_q = filter_input(INPUT_POST, 'user_q',FILTER_VALIDATE_INT);
            $q = ifisset($_SESSION, 'q','');
            if (!empty($div_fio) AND !empty($div_mail) AND !empty($user_login) AND !empty($user_pass) AND !empty($user_passc) AND !empty($user_q)) {
                $err = FALSE;
                if($user_q !== $q){
                    $err = TRUE;
                    set_error_field('user_q');
                    set_error('Не верный ответ на вопрос');
                }
                if (!preg_match("/^[a-z\d\_\-]{3,100}$/", $user_login)) {
                    set_error_field('user_login');
                    set_error('Не корректный логин');
                    $err = TRUE;
                }
                if (!preg_match("/^\w{3,20}\s\w{3,20}\s\w{3,20}$/u", $div_fio)) {
                    set_error_field('div_fio');
                    set_error('Не корректное ФИО');
                    $err = TRUE;
                }
                if ($user_pass !== $user_passc) {
                    set_error_field('user_pass');
                    set_error_field('user_passc');
                    set_error('Введенные пароль не совпадают');
                    $err = TRUE;
                }
                $sel = $DB->prepare("SELECT * FROM `divs` WHERE `reg_mail`=:reg_mail OR `reg_fio` LIKE :reg_fio");
                $sel->execute(array('reg_mail' => $div_mail, 'reg_fio' => $div_fio));
                $sel_u = $DB->prepare("SELECT * FROM `users` WHERE `email`=:reg_mail AND `FIO` LIKE :reg_fio");
                $sel_u->execute(array('reg_mail' => $div_mail, 'reg_fio' => $div_fio));
                if ($sel->rowCount() > 0 OR $sel_u->rowCount() > 0) {
                    set_error_field('div_mail');
                    set_error('Уже регистрировались');
                }
                $sel = $DB->prepare("SELECT * FROM `users` WHERE `user`=:user");
                $sel->execute(array('user' => $user_login));
                if ($sel->rowCount() > 0) {
                    set_error_field('user_login');
                    set_error('Имя пользователя заянто, выберите другое');
                }
                if (!$err) {
                    $sel = $DB->prepare("SELECT * FROM `divs`");
                    $sel->execute();
                    $c = $sel->rowCount() + 1;
                    $ins = db("INSERT INTO `divs`(`name`, `reg_mail`, `reg_fio`) VALUES (:name, :reg_mail, :reg_fio)",
                            ['name' => 'My ORG #' . $c, 'reg_mail' => $div_mail, 'reg_fio' => $div_fio]);
                    if ($ins > 0) {
                        db("UPDATE `divs` SET `token`=MD5(`id`) WHERE `token` IS NULL;");
                        $ins_user = db("INSERT INTO `users`(`user`, `pass`, `groups`, `div_id`, `FIO`, `email`, `enable`) "
                                . "VALUES (:user, :pass, 'admin', :div_id, :FIO, :email, 1)", 
                                [
                                    'user'=>$user_login,
                                    'pass'=> md5($user_pass),
                                    'div_id'=>$ins,
                                    'FIO'=>$div_fio,
                                    'email'=>$div_mail,
                                ]);
                        if($ins_user > 0){
                            db("INSERT INTO `tickets_event`(`ticket_id`, `dt`, `user_id`, `mes`, `status`) "
                                    . "VALUES (1, :dt, :user_id, :mes, 0)", [
                                        'dt'=>time(),
                                        'user_id'=>$ins_user,
                                        'mes'=> sprintf('Зарегистрирована '.t('организация').' #%s:
Пользователь: %s
E-mail: %s',
                                                $c,$div_fio,$div_mail),
                                    ]);
                            set_message('Вы успешно зарегистрировались.');
                            //set_message('Заявка успешно отправлена. Дождитесь подтверждения на указанный адрес.');
                            $ajax->close_awindow();
                        }else{
                            db("DELETE FROM `divs` WHERE `id`=:id", ['id'=>$ins]);
                            set_error('Ошибка');
                        }
                    } else {
                        set_error('Ошибка');
                    }
                }
            } else {
                if (empty($div_fio)) {
                    set_error_field('div_fio');
                }
                if (empty($div_mail)) {
                    set_error_field('div_mail');
                }
                if (empty($user_login)) {
                    set_error_field('user_login');
                }
                if (empty($user_pass)) {
                    set_error_field('user_pass');
                }
                if (empty($user_passc)) {
                    set_error_field('user_passc');
                }
                if (empty($user_q)) {
                    set_error_field('user_q');
                }
                set_error('Заполните все поля');
            }
            break;
        case "login":
            if (User::auth_user()) {
                $ajax->redirect("/");
            } else {
                set_error('Не верный логин или пароль');
            }
            break;
        case 'save_area':
            if (area::save()) {
                $ajax->close_awindow();
                $ajax->reload();
            }
            break;
        case 'save_group':
            if (group::save()) {
                $ajax->close_awindow();
                $ajax->reload();
            }
            break;
        case 'save_user':
            if (User::save()) {
                $ajax->close_awindow();
                $ajax->reload();
            }
            break;
        case 'save_org':
            if (div::save()) {
                $ajax->alert('Успешно');
                $ajax->reload();
            }
            break;
        case 'save_vlan':
            if (vlan::save()) {
                set_message('Успешно');
                $ajax->close_awindow();
                $ajax->reload();
            }
            break;
        case "save_ticket":
            $ticket_id = Ticket::save();
            if (count($errors) == 0) {
                if ($ticket_id) {
                    $url = '/?action=view&id=' . $ticket_id;
                    if (isset($CONFIG['PATH']['www']['paths'])) {
                        if (!empty($CONFIG['PATH']['www']['paths'])) {
                            $url = $CONFIG['PATH']['www']['paths'] . $url;
                        }
                    }
                    $ajax->redirect($url);
                } else {
                    set_error('Не корректный ID заявки');
                }
            }
            break;
        case "set_value":
            if (!empty($param)) {
                switch ($param) {
                    case 'user_set_group':
                        $value = ($value === 'true') ? TRUE : FALSE;
                        $u = new User($subparam);
                        $new_gr = implode(",", $u->ticket_groups);
                        if ($id == 0) {
                            if ($value) {
                                $new_gr = '*';
                            } else {
                                $new_gr = '';
                            }
                            $allgroups = get_sql_array('ticket_groups', 'name_group', sprintf("`div_id`='%s'", $u->div_id), FALSE, 'name_group');
                            foreach ($allgroups as $gk => $gn) {
                                $el = 'input[name=set_' . $subparam . '_' . $gk . ']';
                                if ($value) {
                                    $ajax->prop($el, 'checked', true);
                                } else {
                                    $ajax->prop($el, 'checked', false);
                                    $ajax->removeAttr($el, 'checked');
                                }
                            }
                        } else {
                            $el = 'input[name=set_' . $subparam . '_0]';
                            $ajax->prop($el, 'checked', false);
                            $gkt = array_flip($u->ticket_groups);
                            if ($value) {
                                if (!isset($gkt[$id])) {
                                    $gkt[$id] = count($gkt);
                                }
                            } else {
                                if (isset($gkt[$id])) {
                                    unset($gkt[$id]);
                                }
                            }
                            $new_gr = implode(",", array_flip($gkt));
                        }
                        $upd = $DB->prepare("UPDATE `users` SET `ticket_groups`=:ticket_groups WHERE `id`=:id");
                        $upd->execute(array('ticket_groups' => $new_gr, 'id' => $subparam));
                        if ($upd->rowCount() > 0) {
                            set_message("Успешно");
                        }
                        break;
                    case "autocomplete":
                        if (!empty($subparam)) {
                            $html = '';
                            switch ($subparam) {
                                case 'inventory';
                                    if (mb_strlen($value) >= 3) {
                                        
                                    }
                                    //$ajax->replace(".autocomplete#inventory",$html);
                                    break;
                                case 'fio';
                                    if (mb_strlen($value) >= 3) {
                                        
                                    }
                                    //$ajax->replace(".autocomplete#fio",$html);
                                    break;
                                case 'firm';
                                    if (mb_strlen($value) >= 3) {
                                        
                                    }
                                    //$ajax->replace(".autocomplete#firm",$html);
                                    break;
                                default :
                                    set_error("Неизвестный параметр: " . $param . " - " . $subparam);
                            }
                        } else {
                            set_error("Пустой субпараметр: " . $param);
                        }
                        break;
                    case "ticket":
                        if (!empty($subparam)) {
                            set_param('ticket', $subparam, $value);
                        } else {
                            set_error("Неизвестный параметр: " . $param . " - " . $subparam);
                        }
                        break;
                    case "ticket_edit_var":
                        if (!empty($subparam)) {
                            set_param('ticket_edit', $subparam, $value);
                        } else {
                            set_error("Неизвестный параметр: " . $param . " - " . $subparam);
                        }
                        break;
                    case "ticket_event":
                        if (!empty($subparam)) {
                            if (!$id) {
                                set_error("Не определен идентификатор заявки");
                            } else {
                                switch ($subparam) {
                                    case "sign_my":
                                        Ticket::subscribe_to_ticket($id);
                                        break;
                                    case "sign_user":
                                        if ($value > 0) {
                                            Ticket::subscribe_to_ticket($id, $value);
                                        }
                                        break;
                                    case "unsign_my":
                                        Ticket::unsubscribe_to_ticket($id);
                                        break;
                                    case "unsign_user":
                                        if ($value > 0) {
                                            Ticket::unsubscribe_to_ticket($id, $value);
                                        }
                                        break;
                                    case "give_my":
                                        Ticket::set_ticket_give($id);
                                        break;
                                    case "calcel_my":
                                        $ticket = Ticket::get_ticket_data($id);
                                        if (in_array($ticket['status'], array(1))) {
                                            Ticket::set_ticket_value($id, 'status', 0);
                                            Ticket::set_ticket_value($id, 'user_id', 0);
                                            Ticket::set_ticket_event($id, $USER->id, "Снял ответственность", 1);
                                        } else {
                                            set_error('Ошибка: Статус заявки сменился ранее');
                                        }
                                        break;
                                    case "pre_confirm":
                                        $ticket = Ticket::get_ticket_data($id);
                                        if (in_array($ticket['status'], array(1))) {
                                            Ticket::set_ticket_value($id, 'status', 2);
                                            Ticket::set_ticket_event($id, $USER->id, 2, 1);
                                        } else {
                                            set_error('Ошибка: Статус заявки сменился ранее');
                                        }
                                        break;
                                    case "pre_confirm_other":
                                        $ticket = Ticket::get_ticket_data($id);
                                        if (in_array($ticket['status'], array(1))) {
                                            Ticket::set_ticket_value($id, 'status', 2);
                                            $mes = sprintf("%s выполнил заявку", $DIV->get_user_fio($ticket['user_id']));
                                            Ticket::set_ticket_event($id, $USER->id, $mes, 1);
                                        } else {
                                            set_error('Ошибка: Статус заявки сменился ранее');
                                        }
                                        break;
                                    case "set_close":
                                        Ticket::set_ticket_status($id, 3);
                                        break;
                                    case "confirm_give":
                                        $gd = Ticket::get_give($id);
                                        if ($gd !== FALSE) {
                                            Ticket::confirm_ticket_give($id);
                                            $id = $gd['ticket_id'];
                                        }
                                        break;
                                    case "cancel_give":
                                        $gd = Ticket::get_give($id);
                                        if ($gd !== FALSE) {
                                            Ticket::cancel_ticket_give($id);
                                            $id = $gd['ticket_id'];
                                        }
                                        break;
                                    case "set_give":
                                        $ticket = Ticket::get_ticket_data($id);
                                        $give = Ticket::get_give(0, $id);
                                        if ($give === FALSE) {
                                            if ($ticket['status'] == 1) {
                                                if ($ticket['user_id'] !== $value) {
                                                    Ticket::preset_ticket_give($id, $value);
                                                } else {
                                                    set_error('Ошибка: выбранный пользователь уже назначен ответственным');
                                                }
                                            } else {
                                                set_error('Ошибка: Статус заявки сменился ранее');
                                            }
                                        } else {
                                            set_error('Ошибка: Заявка уже передается');
                                        }
                                        break;
                                    case "set_user_id":
                                        $ticket = Ticket::get_ticket_data($id);
                                        if (in_array($ticket['status'], array(0, 1))) {
                                            if ($ticket['user_id'] !== $value) {
                                                Ticket::set_ticket_value($id, 'user_id', $value);
                                                if ((INT) $value === 0) {
                                                    Ticket::set_ticket_value($id, 'status', 0);
                                                    $mes = 5;
                                                } else {
                                                    Ticket::set_ticket_value($id, 'status', 1);
                                                    $mes = sprintf('Ответственным назначен %s', $DIV->get_user_fio($value));
                                                }
                                                Ticket::set_ticket_event($id, $USER->id, $mes, 1);
                                            } else {
                                                set_error('Ошибка: выбранный пользователь уже назначен ответственным');
                                            }
                                        } else {
                                            set_error('Ошибка: Статус заявки сменился ранее');
                                        }
                                        break;
                                    case "cancel_confirm":
                                        $ticket = Ticket::get_ticket_data($id);
                                        if (in_array($ticket['status'], array(2))) {
                                            Ticket::set_ticket_value($id, 'status', 1);
                                            Ticket::set_ticket_event($id, $USER->id, "Выполнение отклонено", 1);
                                        } else {
                                            set_error('Ошибка: Статус заявки сменился ранее');
                                        }
                                        break;
                                    case "confirm_close":
                                        $ticket = Ticket::get_ticket_data($id);
                                        if (in_array($ticket['status'], array(2))) {
                                            Ticket::set_ticket_value($id, 'status', 3);
                                            Ticket::set_ticket_event($id, $USER->id, 3, 1);
                                        } else {
                                            set_error('Ошибка: Статус заявки сменился ранее');
                                        }
                                        break;
                                    case "confirm_close_p":
                                        $ticket = Ticket::get_ticket_data($id);
                                        if ($ticket['status'] != 3) {
                                            Ticket::set_ticket_value($id, 'status', 3);
                                            Ticket::set_ticket_event($id, $USER->id, 3, 1);
                                        } else {
                                            set_error('Ошибка: Статус заявки сменился ранее');
                                        }
                                        break;
                                    case "renew_ticket":
                                        $ticket = Ticket::get_ticket_data($id);
                                        if (in_array($ticket['status'], array(3))) {
                                            Ticket::set_ticket_value($id, 'status', 1);
                                            Ticket::set_ticket_event($id, $USER->id, 'Заявка возобновлена', 1);
                                        } else {
                                            set_error('Ошибка: Статус заявки сменился ранее');
                                        }
                                        break;
                                    case "renew_edit_ticket":
                                        $ticket = Ticket::get_ticket_data($id);
                                        if (in_array($ticket['status'], array(3))) {
                                            Ticket::set_ticket_value($id, 'status', 1);
                                            $mes = 'Заявка возобновлена.';
                                            if ($ticket['user_id'] != $value) {
                                                Ticket::set_ticket_value($id, 'user_id', $value);
                                                if ((INT) $value === 0) {
                                                    Ticket::set_ticket_value($id, 'status', 0);
                                                    $mes .= ' Нет отвественного';
                                                } else {
                                                    Ticket::set_ticket_value($id, 'status', 1);
                                                    $mes = sprintf('%s Ответственным назначен %s', $mes, $DIV->get_user_fio($value));
                                                }
                                            } else {
                                                Ticket::set_ticket_value($id, 'status', 1);
                                            }
                                            Ticket::set_ticket_event($id, $USER->id, $mes, 1);
                                        } else {
                                            set_error('Ошибка: Статус заявки сменился ранее');
                                        }
                                        break;
                                    case "change_ticket_state":
                                        $ticket = Ticket::get_ticket_data($id);
                                        if ($ticket !== FALSE) {
                                            $value = (INT) $value;
                                            Ticket::set_ticket_value($id, 'state', $value);
                                            Ticket::set_ticket_event($id, $USER->id, sprintf('Сменил статус: %s', $STATE[$value]), 1);
                                        }
                                        break;
                                    case "full_remove_message":
                                        Ticket::remove_event($id, TRUE);
                                        break;
                                    case "remove_message":
                                        Ticket::remove_event($id);
                                        break;
                                    case "set_subticket":
                                        Ticket::subticket($id,1);
                                        break;
                                    case "unset_subticket":
                                        Ticket::subticket($id,0);
                                        break;
                                    case "subticket_open":
                                        Ticket::subticket_open($id,0);
                                        break;
                                    case "subticket_close":
                                        Ticket::subticket_open($id,1);
                                        break;
                                    case "edit_message":
                                        $ajax->replace("#edit_message_block", Ticket::view_message_form($value, $id));
                                        break;
                                    default :
                                        set_error("Неизвестный параметр: " . $param . " - " . $subparam);
                                }
                            }
                        } else {
                            set_error("Пустой суб параметр: " . $param);
                        }
                        break;
                    case "ticket_show":
                        if (!$id) {
                            set_error("Не определен идентификатор заявки");
                            break;
                        }
                        if (!empty($subparam)) {
                            $link = '';
                            if (isset($CONFIG['PATH']['www']['paths'])) {
                                $link .= $CONFIG['PATH']['www']['paths'];
                            }
                            $link .= "/?action=view&id=" . $id;
                            Ticket::update_ticket_user($USER->id, $id);
                            switch ($subparam) {
                                case "swindow":
                                    $ajax->swindow($link);
                                    break;
                                case "window":
                                    $ajax->window($link);
                                    break;
                                default :
                                    set_error("Неизвестный параметр: " . $param . " - " . $subparam);
                            }
                        } else {
                            set_error("Пустой суб параметр: " . $param);
                        }
                        break;
                    default :
                        set_error("Неизвестный параметр: " . $param);
                }
            } else {
                set_error("Пустой параметр запроса");
            }
            break;
        case "save_ticket_message":
            $ticket_id = trim(filter_input(INPUT_POST, "ticket_id"));
            $message_id = trim(filter_input(INPUT_POST, "message_id"));
            $message = trim(filter_input(INPUT_POST, "message_text"));
            $reedit = filter_input(INPUT_POST, "reedit", FILTER_VALIDATE_INT);
            $add_mes = $add_files = FALSE;
            if (!empty($message)) {
                if ($message_id == 0) {
                    $message_id = Ticket::set_ticket_event($ticket_id, $USER->id, $message, 0);
                    if ($message_id !== FALSE) {
                        $add_mes = TRUE;
                    }
                } else {
                    $add_mes = Ticket::edit_ticket_event($message_id, $message);
                }
                if ($message_id > 0) {
                    $add_files = Ticket::save_attach($ticket_id, $message_id, 'attach');
                }
            } else {
                $add_files = Ticket::save_attach($ticket_id, $message_id, 'attach');
            }
            if (!$add_mes AND!$add_files AND!$reedit) {
                //set_error('Ничего не изменилось, напишите сообщение и (или) прикрепите файлы');
            } else {
                if ($reedit AND $message_id > 0) {
                    $ajax->replace("#edit_message_block", Ticket::view_message_form($ticket_id, $message_id));
                } elseif ($message_id > 0) {
                    //$ajax->value('input[name=message_id]', $message_id);
                    $ajax->replace("#edit_message_block", Ticket::view_message_form($ticket_id, 0));
                }
                $id = $value = $ticket_id;
                if ($add_files) {
                    $execute = "update_messages_attach";
                } else {
                    $execute = "update_messages";
                }
            }
            if (!$reedit) {
                //$ajax->value('input[name=message_id]', 0);
                $ajax->replace("#edit_message_block", Ticket::view_message_form($ticket_id, 0));
            }
            break;
        default :
            set_error("Неизвестная команда: " . $action);
    }
} else {
    $ajax->alert("Пустая команда запроса");
}

if (!empty($execute)) {
    $arguments = array();
    if (isset($_POST['arguments'])) {
        if (is_array($_POST['arguments'])) {
            $arguments = $_POST['arguments'];
        }
    }
    switch ($execute) {
        case 'false':break; //ничего не делать
        case 'show_tickets':
            //показать передаваемые заявки
            Ticket::get_give_to_my();
            $ajax->replace("#body", Ticket::load_ticket_list());
            $ajax->replace("#action", Ticket::get_action());
            break;
        case 'update_tickets_list':
            $lub = filter_input(INPUT_POST, "last_update", FILTER_VALIDATE_INT);
            $lut = Ticket::last_users_action();
            if ($lut > $lub AND $lub > 0) {
                $ajax->replace("#body", Ticket::load_ticket_list());
            } elseif ($lub == 0) {
                $ajax->replace("#body", Ticket::load_ticket_list());
            }
            $ajax->set_var("last_update", $lut);
            //показать передаваемые заявки
            Ticket::get_give_to_my();
            break;
        case 'update_edit_form':
            $id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
            if (isset($arguments['id']) AND!$id) {
                $id = $arguments['id'];
            }
            if (isset($_SESSION['ticket_edit']['area'])) {
                $group = new group($_SESSION['ticket_edit']['area']);
                if ($group->repair_type > 0) {
                    $ajax->attribute("#inventory_field_label", "style", "display: inline-block;");
                } else {
                    $ajax->attribute("#inventory_field_label", "style", "display: none;");
                }
            }
            $html = Ticket::get_repair_block($id);
            $ajax->replace(".ticket_priem_line td", $html);
            break;
        case "show_inventory":
            if (isset($arguments['number_id'])) {
                $in_data = get_in($arguments['number_id']);
                if ($in_data !== FALSE) {
                    $ajax->awindow(
                            (isset($in_data['number'])) ? $in_data['number'] : 'Ошибка',
                            show_in($in_data)
                    );
                } else {
                    set_error('Не найден инвентарный');
                }
            } else {
                set_error('Не определен запрашиваемый инвентарный');
            }
            break;
        case "show_host_info":
            if (isset($arguments['host_id'])) {
                $host_id = $arguments['host_id'];
                $data = Hosts::get_host_data($host_id);
                if ($data !== FALSE) {
                    $ajax->awindow(
                            $data['name'],
                            Hosts::view_short_data($data)
                    );
                } else {
                    set_error('Не найден такой хост');
                }
            } else {
                set_error('Не определен запрашиваемый инвентарный');
            }
            break;
        case "delete_file":
            if (isset($arguments['id'])) {
                if (File::remove_file($arguments['id'])) {
                    $ajax->remove("*[file=file_" . $arguments['id'] . "]");
                }
            }
            break;
        case "update_ticket_view":
            $ticket = new Ticket($id);
            $ajax->replace("#ticket_head", Ticket::view_ticket_head($ticket));
            $ajax->replace("#action", Ticket::get_action_ticket($ticket));
            $ajax->replace("#ticket_messages", Ticket::view_ticket_message($ticket));
            $ajax->replace("#ticket_attachment", Ticket::view_attachment_ticket($ticket));
            break;
        case "update_messages":
            $ticket = new Ticket($value);
            $ajax->replace("#ticket_messages", Ticket::view_ticket_message($ticket));
            break;
        case "update_messages_attach":
            $ticket = new Ticket($value);
            $ajax->replace("#ticket_attachment", Ticket::view_attachment_ticket($ticket));
            $ajax->replace("#ticket_messages", Ticket::view_ticket_message($ticket));
            break;
        case "show_remont_info":
            if (isset($arguments['ticket_id'])) {
                $ticket = new Ticket($arguments['ticket_id']);
                $ajax->awindow(
                        "Информация",
                        $ticket->repair
                );
            } else {
                set_error('Не нашел номер заявки');
            }
            break;
        case "print_remont":
            $pre = '';
            $id = 0;
            if (isset($CONFIG['PATH']['www']['paths'])) {
                if (!empty($CONFIG['PATH']['www']['paths'])) {
                    $pre = $CONFIG['PATH']['www']['paths'];
                }
            }

            if (isset($arguments['ticket_id'])) {
                $id = $arguments['ticket_id'];
                $ticket = new Ticket($id);
                if ($ticket->id > 0) {
                    $res = 'found';
                    $html = '';
                    $ajax->window(sprintf("/print/?type=html&tamplate=ticket_device_remont&id=%s", $id));
                } else {
                    set_error('Не найдена заявка');
                }
            } else {
                set_error("Не указан номер заявки");
            }
            break;
        case "new_ticket":
            $pre = '';
            if (isset($CONFIG['PATH']['www']['paths'])) {
                if (!empty($CONFIG['PATH']['www']['paths'])) {
                    $pre = $CONFIG['PATH']['www']['paths'];
                }
            }
            $ajax->window($pre . "/?action=edit&id=0");
            break;
        case 'edit_user':
            $id = ifisset($arguments, 'id', 0);
            if ($id > 0) {
                $ajax->awindow('Редактирвоание пользователя #' . $id, User::edit_form($id));
            } else {
                $ajax->awindow('Добавить пользователя', User::edit_form(0));
            }
            break;
        case 'edit_div':
            $id = ifisset($arguments, 'id', 0);
            if ($id > 0) {
                $ajax->awindow(t('Редактирвоание организации #') . $id, div::edit_form($id));
            } else {
                $ajax->awindow(t('Добавить организацию'), div::edit_form(0));
            }
            break;
        case 'edit_area':
            $id = ifisset($arguments, 'id', 0);
            $div_id = ifisset($arguments, 'id', 0);
            if ($id > 0) {
                $ajax->awindow('Редактирвоание #' . $id, area::edit_form($id, $div_id));
            } else {
                $ajax->awindow('Добавить', area::edit_form(0, $div_id));
            }
            break;
        case 'edit_group':
            $id = ifisset($arguments, 'id', 0);
            $div_id = ifisset($arguments, 'id', 0);
            if ($id > 0) {
                $ajax->awindow('Редактирвоание #' . $id, group::edit_form($id, $div_id));
            } else {
                $ajax->awindow('Добавить', group::edit_form(0, $div_id));
            }
            break;
        case "logout":
            if (User::deauth_user()) {
                $ajax->redirect("/");
            }
            break;
        case 'give_my_org':
            $form = array();
            $form[] = html::hidden('action', 'reg_my_org');
            $form[] = html::form_item("Ваше ФИО", html::input('text', 'div_fio', ''), 1);
            $form[] = html::form_item("Ваш email", html::input('text', 'div_mail', ''), 1);
            $form[] = html::form_item("Логин", html::input('text', 'user_login', ''), 1);
            $form[] = html::form_item("Пароль", html::input('password', 'user_pass', ''), 1);
            $form[] = html::form_item("Пароль еще раз", html::input('password', 'user_passc', ''), 1);
            $a = random_int(1, 1000);
            $b = random_int(1, 1000);
            $_SESSION['q'] = $a + $b;
            $form[] = html::form_item("Сколько будет $a + $b", html::input('number', 'user_q', '',['stet'=>1]), 1);
            $form[] = html::submit('Отправить', 'give_my_org');
            $ajax->awindow('Регистрация', html::form(implode("", $form), 'give_my_org'));
            break;
        case 'edit_vlan':
            $id = ifisset($arguments, 'id', 0);
            $vlan = new vlan($id);
            if ($id > 0) {
                $ajax->awindow('Редактирвоание #' . $id, $vlan->edit_form());
            } else {
                $ajax->awindow('Добавить', $vlan->edit_form());
            }
            break;
        default :
            set_error("Команда для выполнения не найдена: " . $execute);
    }
    //$ajax->swindow("gbgf yjdjt jryj");
}
$deb = ob_get_clean();
if (!empty($deb)) {
    debug($deb);
}
$ajax->execute();
