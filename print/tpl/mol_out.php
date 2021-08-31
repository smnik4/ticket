<?php
$file_name = 'Выгрузка сведений по МОЛ от '. date("Y.m.d");
if(isset($_GET['q'])){
	//styles
	include($base_dir."/PHPExcel/styles.php");
	
	$input_operation = filter_input(INPUT_GET,"operation");
	if(empty($input_operation)){
		$input_operation = "AND";
	}
	$input_operation = " ".$input_operation." ";
	$q = trim($_GET['q']);
	$q = str_replace("*","%",$q);
	if(strlen($q)==0){
		exit('<p class="error">Пустой запрос!</p>');
	}
	if(strlen($q)<3){
		exit('<p class="error">Минимальная длинна запроса 3 символа!</p>');
	}
	if($q=="%"){
		exit('<p class="error">Неверный запрос!</p>');
	}
	$where_mol = '';
	if(preg_match("/\|/",$q)){
		$qq = explode("|",$q);
		$sub_where_mol = $sub_where_hosts = $sub_ticket_mes = $sub_ticket = array();
		foreach($qq as $q){
			$q = trim($q);
			if(empty($q) OR strlen($q)<3){
				exit('<p class="error">Неверный запрос!</p>');
			}
			if(substr_count($q,"%") == 0){
				$q = "%".$q."%";
			}
			$sub_where_mol[] = sprintf("(
				`name` LIKE '%s' OR 
				`number` LIKE '%s' OR 
				`description` LIKE '%s' OR 
				`mol` LIKE '%s')",
				$q,$q,$q,$q);
		}
		if(count($sub_where_mol) == 0){
			exit('<p class="error">Пустой запрос по базе МОЛ!</p>');
		}else{
			$where_mol = implode($input_operation,$sub_where_mol);
		}
	}else{
		if(substr_count($q,"%") == 0){
			$q = "%".$q."%";
		}
		$where_mol = sprintf("
				`name` LIKE '%s' OR 
				`number` LIKE '%s' OR 
				`description` LIKE '%s' OR 
				`mol` LIKE '%s'",
				$q,$q,$q,$q);
	}
	$sql = sprintf("SELECT * FROM `hosts_inventory` 
			WHERE 
				%s
				ORDER BY 'mol',`name`,`number`",$where_mol); 
	$sel = $DB_PDO -> prepare($sql);
	$sel -> execute();
	$search = array("#=#",'""','" ',' "',"'",);
	$replace = array("",'"','"','"','"',);
	if($sel -> rowCount() > 0){
		$aSheet = $objExcel->setActiveSheetIndex(0);
		$aSheet->getPageSetup()
				->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_DEFAULT)
				->SetPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);
		
		$aSheet->setCellValue('A1', '#')
				->setCellValue('B1', 'Инвентарный')
				->setCellValue('C1', 'HOSTs')
				->setCellValue('D1', 'Наименование')
				->setCellValue('E1', 'Комплектация')
				->setCellValue('F1', 'МОЛ')
				->setCellValue('G1', 'Подразделение')
				->setCellValue('H1', 'Кол-во')
				->setCellValue('I1', 'Дата принятия к учету')
				->setCellValue('J1', 'Год принятия к учету');
		$aSheet->getColumnDimension('A')->setWidth(5);
		$aSheet->getColumnDimension('B')->setWidth(25);
		$aSheet->getColumnDimension('C')->setWidth(25);
		$aSheet->getColumnDimension('D')->setWidth(40);
		$aSheet->getColumnDimension('E')->setWidth(60);
		$aSheet->getColumnDimension('F')->setWidth(40);
		$aSheet->getColumnDimension('G')->setWidth(40);
		$aSheet->getColumnDimension('H')->setWidth(10);
		$aSheet->getColumnDimension('I')->setWidth(15);
		$aSheet->getColumnDimension('J')->setWidth(15);
		$aSheet->getStyle('A1:I1')->applyFromArray($style_head);
		$aSheet->getStyle('A1:I1')->getAlignment()->setWrapText(true);
		$nn = 0;
		$t_row = 1;
		while ($ref = $sel -> fetch()) {
			$nn++;
			$t_row++;
			$ref['number'] = trim($ref['number']);
			$ref['mol'] = trim($ref['mol']);
			$ref['div'] = trim($ref['div']);
			$ref['date_enter_to'] = trim($ref['date_enter_to']);
			$ref['date_enter'] = trim($ref['date_enter']);
			$ref['description'] = trim(str_replace($search,$replace,$ref['description']));
			$ref['description'] = trim(str_replace($ref['number'],"",$ref['description']));
			$ref['name'] = trim(str_replace($search,$replace,$ref['name']));
			$ref['name'] = trim(str_replace($ref['number'],"",$ref['name']));
			$ref['number'] = (strlen($ref['number']) == 0)?"Нет номера":$ref['number'];
			$ref['name'] = trim($ref['name']);
			$ref['description'] = trim($ref['description']);
			$hosts = array();
			$hsql = sprintf("SELECT * FROM `hosts` WHERE `in_number` LIKE '%%%s' OR `descr` LIKE '%%%s%%'",mb_substr($ref['number'],2),mb_substr($ref['number'],2));
			$to_hosts = $DB_PDO -> prepare($hsql);
			$to_hosts -> execute();
			if($to_hosts-> rowCount() > 0){
				while($h = $to_hosts -> fetch()){
					$hosts[] = $h['name'];
				}
			}
			$hosts = implode(", ",$hosts);
			$aSheet->setCellValue('A'.$t_row, $nn);
			$aSheet->setCellValue('B'.$t_row, $ref['number']);
			$aSheet->setCellValue('C'.$t_row, $hosts);
			$aSheet->setCellValue('D'.$t_row, $ref['name']);
			$aSheet->setCellValue('E'.$t_row, $ref['description']);
			$aSheet->setCellValue('F'.$t_row, $ref['mol']);
			$aSheet->setCellValue('G'.$t_row, $ref['div']);
			$aSheet->setCellValue('H'.$t_row, $ref['count']);
			$aSheet->setCellValue('I'.$t_row, $ref['date_enter']);
			list($d,$m,$y) = explode(".",$ref['date_enter']);
			$aSheet->setCellValue('J'.$t_row, $y);
			$aSheet->getStyle(sprintf('A%s:I%s',$t_row,$t_row))->applyFromArray($style_all);
			$aSheet->getStyle(sprintf('B%s:G%s',$t_row,$t_row))->getAlignment()->setWrapText(true);
		}
	}else{
		exit('<p class="error">Запрос не выдал результата, нечего выводитьы!</p>');
	}
}