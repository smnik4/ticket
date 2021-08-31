<?php
	$theme->title("Статистика выполненных работ");
	if(isset($_GET['start'])){
		$start = $_GET['start'];
	}else{
		$start = date("Y-m-01");
	}
	if(isset($_GET['end'])){
		$end = $_GET['end'];
	}else{
		$end = date("Y-m-d");
	}
	$start = strtotime($start);
	$end = strtotime($end." 23:59:59");
	$sel = $DB -> prepare("SELECT * FROM `users` WHERE `enable` = 1 AND `div_id`=:div_id ORDER BY `FIO`");
	$sel -> execute(['div_id'=>$USER->div_id]);
	$results = array();
	while($user = $sel -> fetch()){
		$results[$user['id']] = array(
				'fio' => $user['FIO'],
				1=>0,
				2=>0,
				3=>0
			);
		$sql = sprintf("SELECT T.id,T.status,max(E.dt) as dt 
				FROM `tickets` T, `tickets_event` E 
				where T.id=E.ticket_id AND T.user_id='%s' group by T.id",$user['id']);
		$tickets = $DB -> prepare($sql);
		$tickets -> execute();
		while($ticket = $tickets -> fetch()){
			if($start <= $ticket['dt'] AND $ticket['dt'] <= $end){
				$results[$user['id']][$ticket['status']]++;
			}
		}
	}
	echo '<style>
		.ticket1{
			height:20px;
			border: 1px solid black;
			border-left: none;
			background: #d3d198; /* Old browsers */
			background: -moz-linear-gradient(top,  #d3d198 0%, #d5d030 60%, #938c22 100%);
			background: -webkit-linear-gradient(top,  #d3d198 0%,#d5d030 60%,#938c22 100%);
			background: linear-gradient(to bottom,  #d3d198 0%,#d5d030 60%,#938c22 100%);
			filter: progid:DXImageTransform.Microsoft.gradient( startColorstr=\'#d3d198\', endColorstr=\'#938c22\',GradientType=0 );
			text-align:center;
			display: inline-block;
			
		}
		.ticket2{
			height:20px;
			border: 1px solid black;
			border-left: none;
			background: #b4ddb4; /* Old browsers */
			background: -moz-linear-gradient(top,  #b4ddb4 0%, #87ca6d 60%, #5d8748 100%);
			background: -webkit-linear-gradient(top,  #b4ddb4 0%,#87ca6d 60%,#5d8748 100%);
			background: linear-gradient(to bottom,  #b4ddb4 0%,#87ca6d 60%,#5d8748 100%);
			filter: progid:DXImageTransform.Microsoft.gradient( startColorstr=\'#b4ddb4\', endColorstr=\'#5d8748\',GradientType=0 );
			text-align:center;
			display: inline-block;
			
		}
		.ticket3{
			height:20px;
			border: 1px solid black;
background: #b4ddb4;
background: -moz-linear-gradient(top,  #b4ddb4 0%, #008a00 60%, #002400 100%);
background: -webkit-linear-gradient(top,  #b4ddb4 0%,#008a00 60%,#002400 100%);
background: linear-gradient(to bottom,  #b4ddb4 0%,#008a00 60%,#002400 100%);
filter: progid:DXImageTransform.Microsoft.gradient( startColorstr=\'#b4ddb4\', endColorstr=\'#002400\',GradientType=0 );

			text-align:center;
			display: inline-block;
			padding:0px 2px;
		}
		.fio{
			display: inline-block;
			width: 300px;
		}
	</style>
	<div class="ticket3">Выполнено</div>
	<div class="ticket2">Ожидает подтверждения</div>
	<div class="ticket1">Не выполнено</div><br><br>
	';
	printf('<form method="GET">
		<input type="hidden" name="action" value="stat_work" />
		<p>
			С <input type="date" name="start" value="%s"> 
			по <input type="date" name="end" value="%s">
			<input type="submit" value="Просмотреть">
		</p>
		</form>',
			date("Y-m-d",$start),
			date("Y-m-d",$end)
		);
	
	foreach($results as $res){
		printf('<div style="border-bottom: 1px solid black;padding:2px;"><span class="fio">%s</span>%s%s%s %s</div>',
			$res['fio'],
			show_col(3,$res[3]),
			show_col(2,$res[2]),
			show_col(1,$res[1]),
			$res[3]+$res[2]+$res[1]
			);
	}
	
	//require 'footer.php';
	function show_col($type,$col){
		global $results;
		if($col > 0){
			$width = set_width_of_sum($results);
			if($width < 1 AND $col < 3){
				$width = 1;
			}
			return sprintf('<div class="ticket%s" style="width:%spx;" title="%s"></div>',
				$type,$col*$width,$col);
		}else{
			return "";
		}
	}
	
	function set_width_of_sum($array){
		$max = 0;
		foreach($array as $res){
			$sum = $res[3]+$res[2]+$res[1];
			if($sum >= $max){
				$max = $sum;
			}
		}
		$width = 600/$max;
		return $width;
	}
?>