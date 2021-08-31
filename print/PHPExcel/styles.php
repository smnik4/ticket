<?php
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