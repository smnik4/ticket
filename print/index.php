<?php
	//ini_set('display_errors',1);
	//error_reporting(E_ALL);

    require "../assets/config.php";
    
	$base_dir = __DIR__;
	ob_start();
	$type = filter_input(INPUT_GET,"type");
	if(!$type){
		$type = 'pdf';
	}
	$html = '';
	switch($type){
		case 'pdf':
			require 'mpdf/mpdf.php';
			$styles = '<style>hr{padding:0px; margin:5px 0px;}</style>';
			$mpdf=new mPDF('P', 'A4',10,"",10,10,10,10);
			//$mpdf->WriteHTML($styles);
			break;
		case 'xls':
			require 'PHPExcel/PHPExcel.php';
			$objExcel = new PHPExcel();
			$objExcel->setActiveSheetIndex(0);
			$file_name = 'Заголовок';
			break;
		case 'html':
			break;
		default:
			exit("<center><h1>Неизвестный тип файла !!!</h1></center>");
	}
	
	$tamplate = filter_input(INPUT_GET,"tamplate");
	if(!$tamplate){
		echo "<center><h1>Не указан шаблон!!</h1></center>";  exit;
	}
	
	$file = __DIR__ . "/tpl/".$tamplate.".php";
	if(!file_exists($file)){
		echo "<center><h1>Не верный шаблон!!</h1></center>";  exit;
	}
	include($file);
	if($type !== "html"){
		ob_clean();
	}
	switch($type){
		case 'pdf':
			$mpdf->WriteHTML($html);
			$mpdf->Output();
			break;
		case 'xls':
			header('Content-Type: application/vnd.ms-excel');
			header('Content-Disposition: attachment;filename="'.$file_name.'.xls"');
			header('Cache-Control: max-age=0');
			header('Cache-Control: max-age=1');
			header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
			header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
			header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
			header ('Pragma: public'); // HTTP/1.0
			$objWriter = PHPExcel_IOFactory::createWriter($objExcel, 'Excel5');
			$objWriter->save('php://output');
			break;
        case "html":
            echo '<html><head><title>--</title><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head><body>';
            echo $html;
            echo '</body></html>';
            break;
	}