<?php

class host {
    public $id = 0;
    private $div_id = 0;
    private $ethertype = [];
    private $etherth = [];
    

    public function __construct($div_id,$id = 0) {
        $this->div_id = $div_id;
        $this->ethertype = self::get_ethertype();
        if($id > 0){
            $this->id = $id;
            $this->load();
        }
    }
    
    public function find_host($mac) {
        $sel = db("SELECT H.id FROM `hosts` H, `hosts_eth` E WHERE H.id=E.host_id AND H.div_id=:div_id AND E.mac=:mac",
                ['div_id'=>  $this->div_id,'mac'=>  self::mac_clear($mac)]);
        if($sel ->rowCount() > 0){
            $d = $sel -> fetch();
            $this->id = $d['id'];
            $this->load();
        }
    }
    
    private function load() {
        ##
    }


    public static function mac_clear($mac){
        return str_replace("-", ":", $mac);
    }

    public static function get_ethertype() {
        return [
            'eth'=>'Ethernet',
            'wifi'=>'WiFi',
            'ipmi'=>'IPMI',
        ];
    }
    
    public static function parse_input_script($file_path,$div_id) {
        if (file_exists($file_path)) {
            $file_data = file_get_contents($file_path);
            $file_data = str_replace("\r\n", "\n", $file_data);
            $file_data = strip_tags($file_data);
            $file_data = str_replace("\n\n","\n",$file_data);
            $file_data = str_replace("\n\n","\n",$file_data);
            $file_data = str_replace("\nHost is up", " Host is up", $file_data);
            $file_data = str_replace("\nMAC Address", " MAC Address", $file_data);
            $file_data = explode("\n", $file_data);
            $hosts = [];
            foreach ($file_data as $line) {
                $line = trim($line);
                if (preg_match("/^#|Starting Nmap|Nmap done/ui", $line) OR empty($line)) {
                    continue;
                }
                if (preg_match("/Nmap scan report for ([\w\d\.\-]{2,200}) \(([\d\.]{7,15})\) Host is up\s?(\(([\-\d\.s]{1,10}) latency\))?.( MAC Address: ([\:\-A-Fa-f0-9]{17}) \(([\w\d\.\-\s\'\(\)]{2,300})\))?/ui", $line, $ff)) {
                    $hosts[] = [
                        'mac' => ifisset($ff, 6),
                        'ip' => ifisset($ff, 2),
                        'name' => ifisset($ff, 1),
                        'platfonm' => ifisset($ff, 7),
                        'latency' => ifisset($ff, 4),
                    ];
                } elseif (preg_match("/Nmap scan report for ([\d\.]{7,15}) Host is up \(([\-\d\.s]{1,10}) latency\). MAC Address: ([\:\-A-Fa-f0-9]{17}) \(([\w\d\.\-\s\'\(\)]{2,300})\)/ui", $line, $ff)) {
                    $hosts[] = [
                        'mac' => ifisset($ff, 3),
                        'ip' => ifisset($ff, 1),
                        'name' => '-',
                        'platfonm' => ifisset($ff, 4),
                        'latency' => ifisset($ff, 2),
                    ];
                } elseif (preg_match("/Nmap scan report for ([\d\.]{7,15}) Host is up.( MAC Address: ([\:\-A-Fa-f0-9]{17}) \(([\w\d\.\-\s\'\(\)]{2,300})\))?/ui", $line, $ff)) {
                    $hosts[] = [
                        'mac' => ifisset($ff, 3),
                        'ip' => ifisset($ff, 1),
                        'name' => '-',
                        'platfonm' => ifisset($ff, 4),
                        'latency' => '-',
                    ];
                } else {
                    echo "Error host. Input: ".$line."\n";
                }
            }
            $vlan_list = vlan::vlan_list($div_id);
            foreach($hosts as $host){
                $mac = trim($host['mac']);
                if(empty($mac)){
                    printf("Host %s not found MAC address\n",$host['ip']);
                    continue;
                }
                $vlan = vlan::find_vlan($vlan_list, $host['ip']);
                if($vlan['vid'] == 0){
                    printf("Host %s %s not in allowed VLANs\n",$mac,$host['ip']);
                }else{
                    printf("Host %s %s in %s VLAN\n",$mac,$host['ip'],$vlan['num']);
                    
                }
            }
        } else {
            echo "Input file error. File not found!\n";
        }
    }
}
