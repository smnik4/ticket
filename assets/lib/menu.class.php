<?php

    # class вывода меню из таблицы БД
    class Menu{
        private $idNAV = 'menu-wrap';
        private $idMainUL = 'menu';
        private $groups = array();
        private $active = 0;
		public $name = '';
		public $url = '';
		public $check_url = '';
		public $page_auth = TRUE;
        public $menu_html = '';
        
        
        private function getArrayMenu(){
			global $DB;
			$sel = $DB -> prepare("SELECT * FROM `menu` WHERE `visible`=1 ORDER BY `num`,`name`");
			$sel -> execute();
			$arr = array();
            while ($tmp = $sel -> fetch()){
                $tmp['group'] = (!empty($tmp['group']))?explode(",",$tmp['group']):array();
                if(count(array_intersect($tmp['group'],$this->groups)) > 0 OR empty($tmp['group'])){
					$arr[] = $tmp;
				}
            }
            return $arr;
        }
        
        private function getSubMenu($array,$parent){
            $this->menu_html .= '<ul>';
            foreach ($array as $value) {
                if($value['parent_id']==$parent){
                    $url = $value['href'];
                    $this->menu_html .= sprintf("<li><a href='%s'>%s</a>",
						$url,
						t($value['name']));                   
                    $this->getSubMenu($array, $value['id']);
                    $this->menu_html .= '</li>';   
                }
            }
           $this->menu_html .= '</ul>';
        }

        public function __construct($groups = FALSE,$vlans = FALSE){
            global $USER;
            $this->check_url = $this->url = filter_input(INPUT_SERVER,"REQUEST_URI");
			if($groups){
				$this->groups=$groups;
			}
			if($vlans){
				$this->vlans = $vlans;
			}
            $array = $this->getArrayMenu();
            $this->menu_html .= sprintf('<ul id="%s">',$this->idMainUL);
            if($USER->id > 0){
                $this->menu_html .= sprintf('<li class="logout" onclick="%s"><a>Выход</a><li>', AJAX::exec_remote('logout'));
            }
            foreach ($array as $value) {
                if($value['parent_id']==0 && $value['visible']==1){
                    $url = $value['href'];
                    $this->menu_html .= sprintf("<li><a href='%s'>%s</a>",$url,t($value['name']));
                    $this->getSubMenu($array, $value['id']);
                    $this->menu_html .= '</li>';
                }
            }
            
            $this->menu_html .= '</ul>';
			$this->check_rights();
			$this->menu_html .= $this->sub_menu($array);
            if($USER->id == 0){
                $this->menu_html = '';
            }
        }
		
		private function sub_menu($array){
			$parent = 0;
            $html = '';
            //debug($array);
			foreach($array as $i){
				//$reg_url = sprintf("/%s.*/",str_replace("/","\/",$this->url));
				//$reg_url = str_replace("?","\?",$reg_url);
				//$reg_url = str_replace("&","\&",$reg_url);
				//$reg_href = sprintf("/%s.*/",str_replace("/","\/",$i['href']));
				//$reg_href = str_replace("?","\?",$reg_href);
				//$reg_href = str_replace("&","\&",$reg_href);
				//if(preg_match($reg_url,$i['href']) OR preg_match($reg_href,$this->url)){
                if($i['href'] === $this->url){
					$this->active = $i['id'];
					if($this->is_children($array,$i['id'])){
						$parent = $i['id'];
					}else{
						$parent = $i['parent_id'];
					}
				}
			}
			if((INT)$parent === 0){
				return '';
			}
			$sub = array();
			foreach($array as $i){
				if($i['parent_id'] === $parent AND $i['href'] !== "#"){
                    $link = $i['href'];
					$sub[] = sprintf('<a class="sub_menu%s" href="%s">%s</a>',
						((INT)$this->active === (INT)$i['id'])?' active':'',
						$link,
						t($i['name']));
				}
			}
			$action = filter_input(INPUT_GET,"action");
			$page = filter_input(INPUT_GET,"page");
            if(!$action && in_array($page, ['admin','util'])){
                $action = 'start';
            }
            if($action === "start"){
                $html = sprintf('<div id="header">%s</div>',$this->name);
            }
			$html .= '<table border="0" width="100%" cellspacing="0"><tr class="sub_menu_block">';
			if(count($sub) > 0){
				$html .= '<td>';
				$html .= implode("",$sub);
				$html .= '</td>';
			}
			$html .= '</tr></table>';
			if(!empty($action)){
				if($action === "start"){
					$html = str_replace('class="sub_menu"','class="btn_big"',$html);
					$html = str_replace('class="sub_menu_block"','class="center"',$html);
				}
			}
			return $html;
		}
		private function is_children($array,$id){
			foreach($array as $v){
				if($v['parent_id'] === $id){
					return TRUE;
				}
			}
			return FALSE;
		}
		
		public function check_rights(){
			global $USER,$DB;
			$sql = sprintf("SELECT * FROM `menu` WHERE `href` LIKE '%s' ORDER BY `id` LIMIT 1",
				$this->check_url);
			$sel = $DB -> prepare($sql);
			$sel -> execute();
			if($sel -> rowCount() > 0){
				$data = $sel -> fetch();
                $this->name = t($data['name']);
                $data['group'] = (!empty($data['group']))?explode(",",$data['group']):array();
				if(count($data['group']) > 0){
                    if(count(array_intersect($data['group'],$USER->groups)) == 0){
						$this->page_auth = FALSE;
					}
				}
			}else{
                $regs = array(
                    "/\/\?action=view&id=\d*/ui",
                    "/\/\?action=edit&id=\d*/ui",
                    "/\/\?action=stat_work.*/ui",
                    "/\/\?action=stat_kab.*/ui",
                    "/\/\?page\=util\&action\=ip.*/ui",
                );
                $f = false;
                foreach($regs as $reg){
                    if(preg_match($reg, $this->check_url)){
                        $f = TRUE;
                        break;
                    }
                }
                if(!$f){
                    $this->page_auth = FALSE;
                }
            }
		}
    }
?>
