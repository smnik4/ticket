<?php
	require "../cartridges/lib.php";
	
	$file_name = 'Статистика по кабинетам от '. date("Y.m.d");
	
	//styles
	include($base_dir."/PHPExcel/styles.php");
	
	$aSheet = $objExcel->setActiveSheetIndex(0);
	$aSheet->getPageSetup()
			->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_DEFAULT)
			->SetPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);
	
	$aSheet->setCellValue('A1', t('Корпус'))
			->setCellValue('B1', 'Кабинет')
			->setCellValue('C1', 'Картридж')
			->setCellValue('D1', 'Брал');
	$aSheet->getColumnDimension('A')->setWidth(15);
	$aSheet->getColumnDimension('B')->setWidth(25);
	$aSheet->getColumnDimension('C')->setWidth(30);
	$aSheet->getColumnDimension('D')->setWidth(40);
	$aSheet->getStyle('A1:D1')->applyFromArray($style_head);
	
	$cartr = get_cartr();
	$sel = $DB_PDO -> prepare("SELECT * FROM `exit_cartridges` WHERE `kab` IS NOT NULL GROUP BY `korp_id`,`kab`,`cartridge_id` ORDER BY `korp_id`,`kab` ");
	$sel -> execute();
	$res = $comments = array();
	while($row = $sel -> fetch()){
		if(!isset($res[$row['korp_id']])){
			$res[$row['korp_id']] = array();
		}
		if(!isset($res[$row['korp_id']][$row['kab']])){
			$res[$row['korp_id']][$row['kab']] = array();
		}
		if(!in_array($row['cartridge_id'],$res[$row['korp_id']][$row['kab']])){
			$res[$row['korp_id']][$row['kab']][$row['id']] = $row['cartridge_id'];
			if(!isset($comments[$row['id']])){
				$comments[$row['id']] = $row['fio'];
			}
		}
	}
	$t_row = 1;
	foreach($res as $korp=>$kabinets){
		foreach($kabinets as $kabinet=>$kartriges){
			$n = 0;
			foreach($kartriges as $id=>$kid){
				$t_row++;
				if($n == 0){
					$aSheet->setCellValue('A'.$t_row, $korp);
					$aSheet->setCellValue('B'.$t_row, $kabinet);
					$m = $t_row+count($kartriges)-1;
					$cellA = sprintf('A%s:A%s',$t_row,$m);
					$cellB = sprintf('B%s:B%s',$t_row,$m);
					$aSheet->mergeCells($cellA);
					$aSheet->mergeCells($cellB);
				}
				$aSheet->setCellValue('C'.$t_row, $cartr[$kid]);
				$aSheet->setCellValue('D'.$t_row, $comments[$id]);
				$aSheet->getStyle(sprintf('A%s:D%s',$t_row,$t_row))->applyFromArray($style_all);
				$n++;
			}
		}
	}