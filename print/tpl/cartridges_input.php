<?php
	require "../cartridges/lib.php";
	
	$objExcel->getDefaultStyle()->getFont()->setSize(9);
	$aSheet = $objExcel->getActiveSheet();
	$aSheet->getPageMargins()->setTop(0.4);
	$aSheet->getPageMargins()->setRight(0.4);
	$aSheet->getPageMargins()->setLeft(0.4);
	$aSheet->getPageMargins()->setBottom(0.4);
	$style_title = array(
		//рамки
		'alignment' => array(
			'horizontal' => PHPExcel_STYLE_ALIGNMENT::HORIZONTAL_CENTER,
			'vertical' => PHPExcel_STYLE_ALIGNMENT::VERTICAL_CENTER,
		),
		'font'=>array(
			'bold' => true,
			'size' => "11pt",
		),
	);
	
	$style_head = array(
		//рамки
		'borders'=>array(
			// внешняя рамка
			'outline' => array(
				'style'=>PHPExcel_Style_Border::BORDER_THIN,
				'color' => array(
					'rgb'=>'000000'
				)
			),
			//внутренняя
			'allborders'=>array(
				'style'=>PHPExcel_Style_Border::BORDER_THIN,
				'color' => array(
					'rgb'=>'000000'
				)
			)
		),
		'alignment' => array(
			'horizontal' => PHPExcel_STYLE_ALIGNMENT::HORIZONTAL_CENTER,
			'vertical' => PHPExcel_STYLE_ALIGNMENT::VERTICAL_CENTER,
		),
		'font'=>array(
			'bold' => true,
		),
	);
	
	
	$style_all = array(
		//рамки
		'borders'=>array(
			// внешняя рамка
			'outline' => array(
				'style'=>PHPExcel_Style_Border::BORDER_THIN,
				'color' => array(
					'rgb'=>'000000'
				)
			),
			//внутренняя
			'allborders'=>array(
				'style'=>PHPExcel_Style_Border::BORDER_THIN,
				'color' => array(
					'rgb'=>'000000'
				)
			)
		),
		'alignment' => array(
			'vertical' => PHPExcel_STYLE_ALIGNMENT::VERTICAL_TOP,
			'horizontal' => PHPExcel_STYLE_ALIGNMENT::HORIZONTAL_LEFT,
		),
	);
	$filter = filter_input(INPUT_GET,"filter");
	$start_date = filter_input(INPUT_GET,"stdt");
	$end_date = filter_input(INPUT_GET,"endt");
	if(!$filter){
		$filter = 1;
	}
	switch((INT)$filter){
		case 1:
			$sql_filter = 'AND `dt_in` = 0';
			break;
		case 2:
			$sql_filter = 'AND `dt_in` > 0';
			break;
	}
	$sql = sprintf("SELECT * FROM `cartridges_input` WHERE `dt_out`>=%u AND `dt_out`<=%u %s ORDER BY `dt_out` DESC",
		$start_date,
		$end_date,
		$sql_filter);
	$sel = $DB_PDO -> prepare($sql);
	$sel -> execute();
	$all_cartr = get_cartr();
	if($filter == 1){
		/*PAGE 1*/
		$aSheet->setTitle("Передача");
		$aSheet = $objExcel->setActiveSheetIndex(0);
		$aSheet->getPageSetup()
			->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_DEFAULT)
			->SetPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);
		$file_name = 'Акт передачи картриджей от '. date("Y.m.d");
		$aSheet->setCellValue('A2', 'Акт передачи картриджей/оборудования на заправку/ремонт')
			->setCellValue('A1', 'ФГБОУ ВО ОмГПУ')
			->setCellValue('E1', date("___.___.Y г."))
			->setCellValue('A4', '№')
			->setCellValue('B4', 'Наим. карт./обор.')
			->setCellValue('C4', 'Сер./инв. ном. ')
			->setCellValue('D4', 'Причина отправки')
			->setCellValue('E4', 'Прим.');
			$aSheet->getStyle('B4:E4')->getAlignment()->setWrapText(true);
			$aSheet->mergeCells('A2:E2');
			$aSheet->mergeCells('A1:B1');
			$aSheet->getColumnDimension('A')->setWidth(4);
			$aSheet->getColumnDimension('B')->setWidth(25);
			$aSheet->getColumnDimension('C')->setWidth(10);
			$aSheet->getColumnDimension('D')->setWidth(55);
			$aSheet->getColumnDimension('E')->setWidth(20);
			$aSheet->getStyle('A1:E2')->applyFromArray($style_title);
			$aSheet->getStyle('A4:E4')->applyFromArray($style_head);
		/*PAGE 2*/
		$aSheet = $objExcel->createSheet(1);
		$aSheet->setTitle("Приемка");
		$aSheet->getPageSetup()
			->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_DEFAULT)
			->SetPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);
		$aSheet->setCellValue('A2', 'Акт выполненных работ.')
			->setCellValue('A1', 'ИП Булатов Андрей Александрович')
			->setCellValue('F1', date("___.___.Y г."))
			->setCellValue('A4', '№')
			->setCellValue('B4', 'Наим. карт./обор.')
			->setCellValue('C4', 'Сер./инв. ном. ')
			->setCellValue('D4', 'Выявленные неисправности')
			->setCellValue('E4', 'Выполненные работы')
			->setCellValue('F4', 'Стоим. руб.')
			->setCellValue('G4', 'Прим. о приемке');
			$aSheet->getStyle('B4:G4')->getAlignment()->setWrapText(true);
			$aSheet->mergeCells('A1:C1');
			$aSheet->mergeCells('F1:G1');
			$aSheet->mergeCells('A2:G2');
			$aSheet->getColumnDimension('A')->setWidth(4);
			$aSheet->getColumnDimension('B')->setWidth(20);
			$aSheet->getColumnDimension('C')->setWidth(10);
			$aSheet->getColumnDimension('D')->setWidth(35);
			$aSheet->getColumnDimension('E')->setWidth(18);
			$aSheet->getColumnDimension('F')->setWidth(10);
			$aSheet->getColumnDimension('G')->setWidth(10);
			//$aSheet->getColumnDimension('H')->setWidth(10);
			//$aSheet->getColumnDimension('I')->setWidth(14);
			$aSheet->getStyle('A1:G2')->applyFromArray($style_title);
			$aSheet->getStyle('A4:G4')->applyFromArray($style_head);
	}else{
		$aSheet->getPageSetup()
			->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE)
			->SetPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);
		$file_name = 'Акт выполненных работ от '. date("Y.m.d");
		$aSheet->setCellValue('A1', 'Акт выполненных работ')
			->setCellValue('H2', date("___.___.Y г."))
			->setCellValue('A4', '№')
			->setCellValue('B4', 'Наим. карт./обор.')
			->setCellValue('C4', 'Сер./инв. ном. ')
			->setCellValue('D4', 'Выявленные неисправности')
			->setCellValue('E4', 'Выполненные работы')
			->setCellValue('G4', 'Стоим. руб.')
			->setCellValue('H4', 'Прим.');
			$aSheet->mergeCells('A1:H1');
			$aSheet->getStyle('A1:H1')->applyFromArray($style_title);
			$aSheet->mergeCells('E4:F4');
			$aSheet->getColumnDimension('A')->setWidth(4);
			$aSheet->getColumnDimension('B')->setWidth(22);
			$aSheet->getColumnDimension('C')->setWidth(10);
			$aSheet->getColumnDimension('D')->setWidth(35);
			$aSheet->getColumnDimension('E')->setWidth(25);
			$aSheet->getColumnDimension('F')->setWidth(10);
			$aSheet->getColumnDimension('G')->setWidth(10);
			$aSheet->getColumnDimension('H')->setWidth(15);
			$aSheet->getStyle('B4:H4')->getAlignment()->setWrapText(true);
			$aSheet->getStyle('A4:H4')->applyFromArray($style_head);
	}
	$t_row = 4;
	$nn = 0;
	while($row = $sel -> fetch()){
		$t_row++;
		$nn++;
		if($row['dt_out'] > 0){
			$row['dt_out'] = date("d.m.Y",$row['dt_out']);
		}
		if($row['dt_in'] > 0){
			$row['dt_in'] = date("d.m.Y",$row['dt_in']);
		}
		$C_NAME = $all_cartr[$row['cartr_id']];
		if($row['newc']){
			$C_NAME = "Новый ".$C_NAME;
		}
		if($filter == 1){
			/*PAGE 1*/
			$objExcel->setActiveSheetIndex(0);
			$aSheet = $objExcel->getActiveSheet();
			$aSheet->setCellValue('A'.$t_row, $nn);
			$aSheet->setCellValue('B'.$t_row, $C_NAME);
			$aSheet->setCellValue('C'.$t_row, $row['sn']);
			$aSheet->setCellValue('D'.$t_row, $row['comment']);
			//$aSheet->mergeCells(sprintf('D%s:G%s',$t_row,$t_row));
			//$aSheet->mergeCells(sprintf('E%s:F%s',$t_row,$t_row));
			$aSheet->getStyle(sprintf('A%s:E%s',$t_row,$t_row))->applyFromArray($style_all);
			$aSheet->getStyle(sprintf('B%s:E%s',$t_row,$t_row))->getAlignment()->setWrapText(true);
			/*PAGE 2*/
			$objExcel->setActiveSheetIndex(1);
			$aSheet = $objExcel->getActiveSheet();
			$aSheet->setCellValue('A'.$t_row, $nn);
			$aSheet->setCellValue('B'.$t_row, $C_NAME);
			$aSheet->setCellValue('C'.$t_row, $row['sn']);
			$aSheet->setCellValue('D'.$t_row, $row['comment']);
			//$aSheet->mergeCells(sprintf('E%s:F%s',$t_row,$t_row));
			//$aSheet->mergeCells(sprintf('H%s:I%s',$t_row,$t_row));
			$aSheet->getStyle(sprintf('A%s:G%s',$t_row,$t_row))->applyFromArray($style_all);
			$aSheet->getStyle(sprintf('B%s:G%s',$t_row,$t_row))->getAlignment()->setWrapText(true);
		}else{
			$aSheet->setCellValue('A'.$t_row, $nn);
			$aSheet->setCellValue('B'.$t_row, $C_NAME);
			$aSheet->setCellValue('C'.$t_row, $row['sn']);
			$aSheet->setCellValue('D'.$t_row, $row['work_neisp']);
			$aSheet->setCellValue('E'.$t_row, $row['work_vip']);
			$aSheet->mergeCells(sprintf('E%s:F%s',$t_row,$t_row));
			$aSheet->setCellValue('G'.$t_row, ($row['summ'] > 0)?$row['summ']:'');
			$aSheet->getStyle(sprintf('A%s:H%s',$t_row,$t_row))->applyFromArray($style_all);
			$aSheet->getStyle(sprintf('B%s:G%s',$t_row,$t_row))->getAlignment()->setWrapText(true);
		}
	}
	$t_row = $t_row+2;
	if($filter == 1){
		/*PAGE 1*/
		$objExcel->setActiveSheetIndex(0);
		$aSheet = $objExcel->getActiveSheet();
		$aSheet->setCellValue('A'.$t_row, "Сдал");
		$aSheet->setCellValue('C'.$t_row, "________________ Романов Д.Р.");
		$objExcel->setActiveSheetIndex(1);
		$aSheet = $objExcel->getActiveSheet();
		$aSheet->setCellValue('A'.$t_row, "Сдал");
		$aSheet->setCellValue('D'.$t_row, "________________ Булатов А. А.");
		$t_row = $t_row+2;
		$objExcel->setActiveSheetIndex(0);
		$aSheet = $objExcel->getActiveSheet();
		$aSheet->setCellValue('A'.$t_row, "Принял");
		$aSheet->setCellValue('C'.$t_row, "________________ Булатов А. А.");
		$objExcel->setActiveSheetIndex(1);
		$aSheet = $objExcel->getActiveSheet();
		$aSheet->setCellValue('A'.$t_row, "Принял для проверки качества");
		$aSheet->setCellValue('D'.$t_row, "________________ Романов Д.Р.");
		$t_row = $t_row+2;
		$objExcel->setActiveSheetIndex(0);
		$aSheet = $objExcel->getActiveSheet();
		$aSheet->setCellValue('A'.$t_row, "Согласовано");
		$aSheet->setCellValue('C'.$t_row, "________________ Юдин Ю.Ю.");
		$objExcel->setActiveSheetIndex(1);
		$aSheet = $objExcel->getActiveSheet();
		$aSheet->setCellValue('A'.$t_row, "Согласовано");
		$aSheet->setCellValue('D'.$t_row, "________________ Юдин Ю.Ю.");
		$objExcel->setActiveSheetIndex(0);
		$t_row = $t_row+2;
		$objExcel->getActiveSheet()
			->getPageSetup()
			->setPrintArea('A1:E'.$t_row);
		$objExcel->setActiveSheetIndex(1);
		$objExcel->getActiveSheet()
			->getPageSetup()
			->setPrintArea('A1:G'.$t_row);
	}else{
		$aSheet->setCellValue('A'.$t_row, "Сдал");
		$aSheet->setCellValue('E'.$t_row, "________________ Булатов А. А.");
		$t_row = $t_row+2;
		$aSheet->setCellValue('A'.$t_row, "Принял для проверки качества");
		$aSheet->setCellValue('E'.$t_row, "________________ Романов Д.Р.");
		$t_row = $t_row+2;
		$aSheet->setCellValue('A'.$t_row, "Согласовано");
		$aSheet->setCellValue('E'.$t_row, "________________ Юдин Ю.Ю.");
	}
	