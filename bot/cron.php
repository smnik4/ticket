#!/usr/bin/php -q
<?php
require dirname(__DIR__). "/assets/config.php";
$sel_users = db("SELECT U.*,D.botname FROM `users` U, `divs` D WHERE U.div_id=D.id AND U.enable=1 AND U.tlg_chat_id > 0 AND (D.botname IS NOT NULL AND D.botname != '')");
while($user = $sel_users -> fetch()){
    //новые сообщения
    $sign = [];
    $sel = db("SELECT T.* FROM `tickets` T, `tickets_user` U "
            . "WHERE T.id=U.ticket_id AND T.status = 1 AND U.sign = 1 AND U.user_id=:user_id AND T.user_id!=:user_id",
            ['user_id'=>$user['id']]);
    while($t = $sel -> fetch()){
        $sign[] = $t['id'];
    }
    $add = '';
    if(count($sign) > 0){
        $add = sprintf('(T.user_id=:user_id OR T.id IN (%s))',implode(",",$sign));
    }else{
        $add = 'T.user_id=:user_id';
    }
    $sql = "SELECT E.*,U.tlg_name,U.FIO FROM `tickets` T, `tickets_event` E, `users` U "
            . "WHERE T.status = 1 AND ".$add." AND E.ticket_id=T.id AND E.user_id!=:user_id "
            . "AND E.status=0 AND E.send=0 AND E.user_id=U.id";
    $sel = db($sql,['user_id'=>$user['id']]);
    if($sel->rowCount() > 0){
        //printf("Found %s for %s\n",$sel->rowCount(),$user['id']);
        $bot = new tlgbot($user['botname'],$user['id']);
        $bot->last_action_id = 0;
        $bot->lastactionset(NULL);
        while($i = $sel->fetch()){
            if(!empty($i['tlg_name'])){
                $i['mes'] = sprintf('[@%s] %s',$i['tlg_name'],$i['mes']);
            }else{
                $i['FIO'] = mb_substr($i['FIO'], 0, mb_strpos($i['FIO'], " ")+2).'.';
                $i['mes'] = sprintf('[%s] %s', $i['FIO'], $i['mes']);
            }
            //printf("Send for %s: %s\n",$user['id'],$i['mes']);
            $bot->ticket_newmes($i['ticket_id'], ' - '.$i['mes']);
            db("UPDATE `tickets_event` SET `send`=1 WHERE `ID`=:id",['id'=>$i['ID']]);
        }
    }
}

