<?php

class vlan{
    public $id = 0;
    public $div_id = NULL;
    public $num = 0;
    public $name = NULL;
    public $mask = NULL;
    
    public static function vlan_list($div_id){
        $sel = db("SELECT * FROM `vlans` WHERE `div_id`=:div_id", ['div_id'=>$div_id]);
        $res = [];
        while($i = $sel -> fetch()){
            $i['lan'] = new ipcalc($i['mask']);
            $res[$i['id']] = $i;
        }
        return $res;
    }
    
    public static function find_vlan($vlan_list,$ip) {
        foreach($vlan_list as $vid=>$vl){
            if($vl['lan']->in_lan($ip)){
                return ['vid'=>$vid,'num'=>$vl['num']];
            }
        }
        return ['vid'=>0,'num'=>0];
    }

    public function __construct($id) {
        global $DB,$USER;
        $this->div_id = $USER->div_id;
        if($id > 0){
            $this->id = $id;
            $sel = $DB->prepare("SELECT * FROM `vlans` WHERE `id`=:id");
            $sel->execute(array('id'=>$id));
            if($sel -> rowCount() > 0){
                $sel = $sel->fetch();
                $this->div_id = $sel['div_id'];
                $this->num = $sel['num'];
                $this->name = $sel['name'];
                $this->mask = $sel['mask'];
            }
        }
    }
    
    public function edit_form() {
        global $USER;
        $form = array();
        $form[] = html::hidden('action', 'save_vlan');
        $form[] = html::hidden('vlan_id', $this->id);
        if($this->id == 0 AND $USER->is('root')){
            $alldivs = get_sql_array('divs');
            $form[] = html::form_item('Организация',html::select('div_id', $alldivs, $this->div_id),1);
        }else{
            $form[] = html::hidden('div_id', $this->div_id);
        }
        $form[] = html::form_item('Номер', html::input('number', 'num', $this->num,['min'=>1,'max'=>4096,'step'=>1]),1,'1-4096');
        $form[] = html::form_item('Наименование', html::input('text', 'name', $this->name,['maxlength'=>100]),1);
        $form[] = html::form_item('IP/Маска', html::input('text', 'mask', $this->mask,['maxlength'=>18]),1,'10.0.0.1/22');
        $form_id = 'edit_vlan';
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
                set_error('Ошибка запроса. Возможно Вы ничего не поменяли.');
            }
        }
        return FALSE;
    }
    
    static public function valid_form() {
        $fields = array(
            'div_id'  => array('value'=>'div_id', 'name'=>t('Организация')),
            'num'  => array('value'=>'num', 'name'=>'Номер'),
            'name' => array('value'=>'name','name'=>'Наименование'),
            'mask' => array('value'=>'mask','name'=>'IP/Маска'),
        );
        $f = $v = $data = array();
        $pass = TRUE;
        $id = filter_input(INPUT_POST, 'vlan_id',FILTER_VALIDATE_INT);
        $data_t = array();
        if($id > 0){
            $data_t = new div($id);
        }
        foreach($fields as $key=>$field){
            $value = filter_input(INPUT_POST, $field['value']);
            $value = trim($value);
            if($key !== 'mask'){
                $value = htmlspecialchars($value);
            }
            if(mb_strlen($value) == 0 OR is_null($value)){
                set_error_field($field['value']);
                set_error(sprintf('Ошибка в поле "%s"',$field['name']));
                $pass = FALSE;
            }elseif($key === 'mask'){
                $check = new ipcalc($value);
                if($check->err){
                    set_error_field($field['value']);
                    set_error(sprintf('Ошибка в поле "%s"',$field['name']));
                    $pass = FALSE;
                }
            }elseif($key === 'num'){
                if($value <= 0 OR $value >4096){
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
                $sql = sprintf("UPDATE `vlans` SET %s WHERE `id`=:id",implode(", ",$f));
            }else{
                $sql = sprintf("INSERT INTO `vlans`(%s) VALUES (%s)",implode(", ",$f),implode(", ",$v));
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