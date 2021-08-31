<?php
$theme->title(t("Подписка заявок"));
$alldivs = get_sql_array('divs');
$divs = array();
if(in_array('root', $USER->groups)){
    $divs = $alldivs;
}elseif(isset($alldivs[$USER->div_id])){
    $divs[$USER->div_id] = $alldivs[$USER->div_id];
}
foreach ($divs as $div_id=>$div_name){
    echo html::h3($div_name);
    $items = $head = array();
    $head[] = 'ФИО';
    $head[] = 'Все';
    $sel = $DB -> prepare("SELECT * FROM `users` WHERE `div_id`=:div_id");
    $sel -> execute(array('div_id'=>$div_id));
    $colspan = 9;
    $allgroups = get_sql_array('ticket_groups', 'name_group', sprintf("`div_id`='%s'",$div_id), FALSE, 'name_group');
    if($allgroups == FALSE){
        $items[] = array(html::td('Нет Групп', array('colspan'=>2)));
    }elseif($sel -> rowCount() > 0){
        foreach($allgroups as $g){
            $head[] = $g;
        }
        while($i = $sel -> fetch()){
            $gr = array();
            if($i['ticket_groups'] == '*'){
                $gr = array_keys($allgroups);
            }elseif(!empty($i['ticket_groups'])){
                $gr = explode(",",$i['ticket_groups']);
            }
            $item = array(User::short_fio($i['FIO']));
            $attr = array();
            if($i['ticket_groups'] == '*'){
                $attr['checked'] = 'checked';
            }
            $attr['onchange'] = AJAX::on_event("this", "user_set_group", $i['id'], 'false', 0);
            $item[] = html::td(html::input('checkbox', sprintf('set_%s_0',$i['id']), 1, $attr), array('align'=>'center'));
            foreach($allgroups as $gk=>$gn){
                $attr = array();
                if(in_array($gk, $gr)){
                    $attr['checked'] = 'checked';
                }
                $attr['onchange'] = AJAX::on_event("this", "user_set_group", $i['id'], 'false', $gk);
                $item[] = html::td(html::input('checkbox', sprintf('set_%s_%s',$i['id'],$gk), 1, $attr), array('align'=>'center'));
            }
            $items[] = $item;
                    /*array(
                        ,
                        
                       /* $i['name_group'],
                        ifisset($repair, $i['repair_type'], ''),
                        html::action('edit', 'edit_group', array('id'=>$i['id'],'div_id'=>$div_id), 'Редактировать')
                    );*/
        }
    }else{
        $items[] = array(html::td('Нет записей', array('colspan'=>count($allgroups) + 1)));
    }
    echo html::table($head, $items, 1, array('class'=>'out_c'));
}
