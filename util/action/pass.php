<?php
$theme->title(t("Генератор паролей"));
//длинна слова
if(isset($_POST['length'])){
	$length = $_POST['length'];
}else{
	$length = 8;
}
if(isset($_POST['pass_type'])){
	$type = $_POST['pass_type'];
}else{
	$type = 'other';
}
$result = '';
if(isset($_POST['text']) AND strlen(trim($_POST['text']))>0){
	$result = trim($_POST['text']);
}else{
	if($type == 'other'){
		$sl1 = array('A','B','C','D','E','F','G','H','J','K','L','M','N','P','Q','R','S','T','U','V','W','X','Y','Z');
		$sl2 = array('a','b','c','d','e','f','g','h','i','j','k','m','n','p','q','r','s','t','u','v','w','x','y','z');
		$sl3 = array('2','3','4','5','6','7','8','9');
		$sl4 = array('!','@','#','$','%','^','&','&','*','(',')','-','+');
		$bukv = $noset = $bukvlength = array();
        $bukvlengthmax = intval($length / 3) + 2;
        $max_try = 1000;
        $try = 0;
		while(strlen($result)<$length AND $max_try > $try){
            $try++;
            $sw = rand(1,4);
            if(in_array($sw, $noset)){
                continue;
            }
            if(!isset($bukvlength[$sw])){
                $bukvlength[$sw] = 0;
            }
            if($bukvlengthmax <= $bukvlength[$sw]){
                continue;
            }
            if(count($bukv) == 0 AND $sw == 4){
                continue;
            }
			switch($sw){
                case '1':
                    $tmp = $sl1[rand (0, count($sl1) - 1)];
                    $bukvlength[$sw]++;
                    break;
				case '2':
                    $tmp = $sl2[rand (0, count($sl2) - 1)];
                    $bukvlength[$sw]++;
                    break;
				case '3':
                    $tmp = $sl3[rand (0, count($sl3) - 1)];
                    $noset[] = $sw;
                    $bukvlength[$sw]++;
                    break;
                case '4':
                    $tmp = $sl4[rand (0, count($sl4) - 1)];
                    $noset[] = $sw;
                    $bukvlength[$sw]++;
                    break;
			}
			if(!in_array($tmp,$bukv)){
				$bukv[] = $tmp;
			}
            $result = implode("",$bukv);
		}
        if(count(array_intersect($bukv,$sl4)) == 0){
            $bukv[] = $sl4[rand (0, count($sl4) - 1)];
        }
        $result = implode("",$bukv);
	}else{
		$res = shell_exec(sprintf('apg -q -m%s -x%s -a0',$length,$length));
        if(empty($res)){
            set_error("APG не установлен");
        }else{
            $result = explode("\n",$res);
            unset($result[count($result)-1]);
        }
		//print_r($result);
		//exit();
	}
}
printf('<form method="POST">
		Генератор: <label><input type="radio" name="pass_type" value="APG"%s> APG (произносимый)</label> 
				   <label><input type="radio" name="pass_type" value="other"%s> Не произносимый</label><br/>
		Длинна: <input name="length" style="width:50px;" type="number" value="%s" min="8" max="20" step="1">
		Пароль: <input type="text"name="text" style="width:400px;" placeholder="Если пусто, гененит сам, для русского выводит транслит">
		<input type="submit" value="SET">
	</form>',
		($type=='APG')?" checked":"",
		($type=='other')?" checked":"",
		$length);
if(!is_array($result)){
	$result = array($result);
}
foreach($result as $var){
    if(mb_strlen($var) == 0){
        continue;
    }
	$translit = rus2translit($var);
    $rows = [];
    $rows[] = ['<b>Password:</b>', sprintf('<input type="text" value="%s" readonly="readonly" size="35" />',$var)];
    $rows[] = ['<b>MD5:</b>', sprintf('<input type="text" value="%s" readonly="readonly" size="35" />',md5($var))];
    $rows[] = ['<b>SHA1:</b>', sprintf('<input type="text" value="%s" readonly="readonly" size="35" />',sha1($var))];
    $rows[] = ['<b>Crypt:</b>', sprintf('<input type="text" value="%s" readonly="readonly" size="35" />',crypt($var))];

	if($var !== $translit){
		printf('<hr>
				<b>Translit:</b> <input type="text" value="%s" readonly="readonly" size="35" />
				<br><b>Translit MD5:</b> <input type="text" value="%s" readonly="readonly" size="35" />
				<br><b>Translit SHA1:</b> <input type="text" value="%s" readonly="readonly" size="35" />
				<br><b>Translit Crypt:</b> <input type="text" value="%s" readonly="readonly" size="35" />',
			$translit,
			md5($translit),
			sha1($translit),
			crypt($translit));
	}
    echo html::table(false, $rows,0,['style'=>'width:auto;']);
}



function rus2translit($string) {
    $converter = array(
        'а' => 'a',   'б' => 'b',   'в' => 'v',
        'г' => 'g',   'д' => 'd',   'е' => 'e',
        'ё' => 'e',   'ж' => 'zh',  'з' => 'z',
        'и' => 'i',   'й' => 'y',   'к' => 'k',
        'л' => 'l',   'м' => 'm',   'н' => 'n',
        'о' => 'o',   'п' => 'p',   'р' => 'r',
        'с' => 's',   'т' => 't',   'у' => 'u',
        'ф' => 'f',   'х' => 'h',   'ц' => 'c',
        'ч' => 'ch',  'ш' => 'sh',  'щ' => 'sch',
        'ь' => '\'',  'ы' => 'y',   'ъ' => '\'',
        'э' => 'e',   'ю' => 'yu',  'я' => 'ya',
        
        'А' => 'A',   'Б' => 'B',   'В' => 'V',
        'Г' => 'G',   'Д' => 'D',   'Е' => 'E',
        'Ё' => 'E',   'Ж' => 'Zh',  'З' => 'Z',
        'И' => 'I',   'Й' => 'Y',   'К' => 'K',
        'Л' => 'L',   'М' => 'M',   'Н' => 'N',
        'О' => 'O',   'П' => 'P',   'Р' => 'R',
        'С' => 'S',   'Т' => 'T',   'У' => 'U',
        'Ф' => 'F',   'Х' => 'H',   'Ц' => 'C',
        'Ч' => 'Ch',  'Ш' => 'Sh',  'Щ' => 'Sch',
        'Ь' => '\'',  'Ы' => 'Y',   'Ъ' => '\'',
        'Э' => 'E',   'Ю' => 'Yu',  'Я' => 'Ya',
    );
    return strtr($string, $converter);
}
