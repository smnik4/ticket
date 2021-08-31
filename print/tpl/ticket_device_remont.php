<?php

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);
$go = FALSE;
$html = '';
if($id > 0){
    $ticket = new Ticket($id);
    if($ticket->id > 0){
        $go = TRUE;
    }else{
        exit("Заявка не найдена!");
    }
}else{
    exit("Не определен номер заявки!");
}
if($go){
    $file = file_get_contents(__DIR__ ."/doc/device_remont.tpl.html");
    if(preg_match_all("/\[[\w_]+\]/", $file,$search)){
        $search = $search[0];
        $replace = array();
        foreach($search as $field){
            $field = str_replace(array("[","]"), array("",""), $field);
            switch($field){
                case "ORG_NAME":
                    $replace[] = $CONFIG['name'];
                    break;
                 case "TIKET_NUMBER":
                    $replace[] = $id;
                    break;
                case "HEAD":
                    $replace[] = $ticket->head;
                    break;
                case "INVENTORY":
                    $replace[] = $ticket->inventory;
                    break;
                case "KOMPLECTION":
                    $replace[] = $ticket->repair_komplekt;
                    break;
                case "POSITION":
                    $replace[] = $ticket->position;
                    break;
                case "DESCRIPTION":
                    $mes = '';
                    if(isset($ticket->repair_data['problem'])){
                        $mes = $ticket->repair_data['problem'];
                    }
                    $replace[] = $mes;
                    break;
                case "GIVE_FIO":
                    $user_give = $USER->fios;
                    if(count($ticket->events) > 0){
                        $e1 = array_shift($ticket->events);
                        if($e1['user_id'] != $USER->id){
                            $user_give = $DIV->get_user_fio($e1['user_id']);
                        }
                    }
                    $replace[] = $user_give;
                    break;
                case "SENDER_FIO":
                    $mes = '';
                    if(isset($ticket->repair_data['fio'])){
                        $mes = $ticket->repair_data['fio'];
                    }
                    $replace[] = $mes;
                    break;
                default :
                    debug($field);
                    $replace[] = "";
            }
        }
        //debug($search);
    }
    $html = str_replace($search, $replace, $file);
}else{
    exit("Не определен инвентарный номер!");
}

