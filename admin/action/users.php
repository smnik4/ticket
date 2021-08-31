<?php

$theme->title(t('Пользователи'));
$items = array();
$alldivs = get_sql_array('divs');
$divs = array();
if(in_array('root', $USER->groups)){
    $divs = $alldivs;
}elseif(isset($alldivs[$USER->div_id])){
    $divs[$USER->div_id] = $alldivs[$USER->div_id];
}
$roles = get_sql_array('roles','*');
foreach ($divs as $div_id=>$div_name){
    $groups = get_sql_array('ticket_groups', 'name_group', 'div_id='.$div_id);
    if(!$groups){
        $groups = array();
    }
    $users = get_sql_array('users', '*', 'div_id='.$div_id);
    $items[] = array(html::td(sprintf('<b>%s</b>',$div_name), array('colspan'=>'9')));
    if(is_array($users)){
        foreach($users as $k=>$u){
            $u = new User($u['id']);
            $tgr = $tgrm = $r = array();
            foreach($groups as $gk=>$gn){
                if(in_array($gk, $u->ticket_groups)){
                    $tgr[] = $gn;
                }
                if(in_array($gk, $u->group_manager)){
                    $tgrm[] = $gn;
                }
            }
            foreach($roles as $rk=>$rn){
                if(in_array($rn['value'], $u->groups)){
                    $r[] = $rn['name'];
                }
            }
            $items[] = array(
                sprintf('<img src="%s" />',($u->enable > 0)?'circle_green.png':'circle_red.png'),
                $u->fio,
                $u->info,
                $u->username,
                $u->email,
                implode(", ",$tgr),
                implode(", ",$tgrm),
                implode(", ",$r),
                html::action('edit', 'edit_user', array('id'=>$u->id), 'Редактировать')
            );
        }
    }else{
        $items[] = array(html::td(t('Пользователи не найдены'), array('colspan'=>'9')));
    }
}
echo html::p(html::button(t('Добавить пользователя'), '', 'edit_user', array('id'=>0)));
echo html::table(array('','ФИО','Инфо','Логин','Email','Группы','Управление','Роли',''), $items, 1, array('class'=>'out_c'));
