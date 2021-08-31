<?php

function iputil($param, $address) {
    $res = '';
    if (!empty($address)) {
        set_param('util', 'inpaddress', $address);
        set_param('util', 'param', $param);
        switch ($param) {
            case 'ping':
                $res = shell_exec(sprintf('ping -c 5 %s', $address));
                if (empty($res)) {
                    set_error("ping ничего не ответил");
                }
                break;
            case 'nslookup':
            case 'traceroute':
                $res = shell_exec(sprintf('%s %s', $param, $address));
                if (empty($res)) {
                    set_error($param . " ничего не ответил");
                }
                break;
            default:
                set_error('Неизвестная команда');
                break;
        }
    } else {
        set_error('Не указан узел');
        set_error_field('address');
    }
    return nl2br($res);
}

class ipcalc {

    public $input = '';
    public $dq_host = '';
    public $dq_nmask = '';
    public $dq_wmask = '';
    public $dq_bcast = '';
    public $dq_first = '';
    public $dq_last = '';
    public $cdr_nmask = '';
    public $bin_host = '';
    public $bin_nmask = '';
    public $bin_wmask = '';
    public $bin_net = '';
    public $dotbin_net = '';
    public $class = '';
    public $bin_bcast = '';
    public $bin_first = '';
    public $bin_last = '';
    public $host_total = '';
    public $special = '';
    public $err = FALSE;
    public $find = NULL;
    public $find_res = FALSE;

    public function __construct($input, $find = '') {
        if (!preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}(( ([0-9]{1,3}\.){3}[0-9]{1,3})|(\/[0-9]{1,2}))$/ui', $input)) {
            $this->err = TRUE;
            set_error('Не корректный ввод');
            set_error_field('my_net_info');
            return FALSE;
        } else {
            $this->input = $input;
        }
        if (ereg("/", $this->input)) {  //if cidr type mask
            $this->dq_host = strtok($this->input, "/");
            $this->cdr_nmask = strtok("/");
            if (!($this->cdr_nmask >= 0 && $this->cdr_nmask <= 32)) {
                $this->err = TRUE;
                set_error('Invalid CIDR value. Try an integer 0 - 32.');
                set_error_field('my_net_info');
                return FALSE;
            }
            $this->bin_nmask = $this->cdrtobin($this->cdr_nmask);
            $this->bin_wmask = $this->binnmtowm($this->bin_nmask);
        } else { //Dotted quad mask?
            $dqs = explode(" ", $this->input);
            $this->dq_host = $dqs[0];
            $this->bin_nmask = $this->dqtobin(ifisset($dqs, 1));
            $this->bin_wmask = $this->binnmtowm($this->bin_nmask);
            if (ereg("0", rtrim($this->bin_nmask, "0"))) {  //Wildcard mask then? hmm?
                $this->bin_wmask = $this->dqtobin(ifisset($dqs, 1));
                $this->bin_nmask = $this->binwmtonm($this->bin_wmask);
                if (ereg("0", rtrim($this->bin_nmask, "0"))) { //If it's not wcard, whussup?
                    $this->err = TRUE;
                    set_error('Invalid Netmask.');
                    set_error_field('my_net_info');
                    return FALSE;
                }
            }
            $this->cdr_nmask = $this->bintocdr($this->bin_nmask);
        }
        //Check for valid $this->dq_host
        if (!ereg('^0.', $this->dq_host)) {
            foreach (explode(".", $this->dq_host) as $octet) {
                if ($octet > 255) {
                    $this->err = TRUE;
                    set_error('Invalid IP Address.');
                    set_error_field('my_net_info');
                    return FALSE;
                }
            }
        }
        if (!$this->err) {
            $this->bin_host = $this->dqtobin($this->dq_host);
            $this->bin_bcast = (str_pad(substr($this->bin_host, 0, $this->cdr_nmask), 32, 1));
            $this->bin_net = (str_pad(substr($this->bin_host, 0, $this->cdr_nmask), 32, 0));
            $this->bin_first = (str_pad(substr($this->bin_net, 0, 31), 32, 1));
            $this->bin_last = (str_pad(substr($this->bin_bcast, 0, 31), 32, 0));
            $this->host_total = (bindec(str_pad("", (32 - $this->cdr_nmask), 1)) - 1);
            if ($this->host_total <= 0) {  //Takes care of 31 and 32 bit masks.
                $this->bin_first = "N/A";
                $this->bin_last = "N/A";
                $this->host_total = "N/A";
                if ($this->bin_net === $this->bin_bcast) {
                    $this->bin_bcast = "N/A";
                }
            }
            $this->getclass();
            $this->dq_nmask = $this->bintodq($this->bin_nmask);
            $this->dq_wmask = $this->bintodq($this->bin_wmask);
            $this->dq_bcast = $this->bintodq($this->bin_bcast);
            $this->dq_first = $this->bintodq($this->bin_first);
            $this->dq_last = $this->bintodq($this->bin_last);
            if (!empty($find)) {
                if (preg_match("/^([0-9]{1,3}\.){3}[0-9]{1,3}$/", $find)) {
                    $find_err = FALSE;
                    $find_l = explode(".", $find);
                    foreach ($find_l as $ff) {
                        if (intval($ff) > 255) {
                            $find_err = TRUE;
                            break;
                        }
                    }
                    if ($find_err) {
                        set_error('Invalid IP Address.');
                        set_error_field('my_net_ip');
                        return FALSE;
                    } else {
                        $this->find = $find;
                        $this->find_ip();
                    }
                } else {
                    set_error('Invalid IP Address');
                    set_error_field('my_net_ip');
                }
            }
        }
    }

    public function in_lan($find) {
        if (!empty($find)) {
            if (preg_match("/^([0-9]{1,3}\.){3}[0-9]{1,3}$/", $find)) {
                $find_err = FALSE;
                $find_l = explode(".", $find);
                foreach ($find_l as $ff) {
                    if (intval($ff) > 255) {
                        $find_err = TRUE;
                        break;
                    }
                }
                if ($find_err) {
                    set_error('Invalid IP Address.');
                    return FALSE;
                } else {
                    $this->find = $find;
                    return $this->find_ip();
                }
            } else {
                set_error('Invalid IP Address');
            }
        }
        return FALSE;
    }

    private function getclass() {
        //Determine Class
        if (ereg('^0', $this->bin_net)) {
            $this->class = "A";
            $this->dotbin_net = "<font color=\"Green\">0</font>" . substr($this->dotbin($this->bin_net, $this->cdr_nmask), 1);
        } elseif (ereg('^10', $this->bin_net)) {
            $this->class = "B";
            $this->dotbin_net = "<font color=\"Green\">10</font>" . substr($this->dotbin($this->bin_net, $this->cdr_nmask), 2);
        } elseif (ereg('^110', $this->bin_net)) {
            $this->class = "C";
            $this->dotbin_net = "<font color=\"Green\">110</font>" . substr($this->dotbin($this->bin_net, $this->cdr_nmask), 3);
        } elseif (ereg('^1110', $this->bin_net)) {
            $this->class = "D";
            $this->dotbin_net = "<font color=\"Green\">1110</font>" . substr($this->dotbin($this->bin_net, $this->cdr_nmask), 4);
            $this->special = "<font color=\"Green\">Class D = Multicast Address Space.</font>";
        } else {
            $this->class = "E";
            $this->dotbin_net = "<font color=\"Green\">1111</font>" . substr($this->dotbin($this->bin_net, $this->cdr_nmask), 4);
            $this->special = "<font color=\"Green\">Class E = Experimental Address Space.</font>";
        }
    }

    static public function form() {
        global $USER;
        $form = [];
        $my_net_info = trim(filter_input(INPUT_POST, 'my_net_info'));
        $my_net_ip = trim(filter_input(INPUT_POST, 'my_net_ip'));
        $param = $USER->params('util');
        if (empty($my_net_info)) {
            $my_net_info = ifisset($param, 'net_info');
        }
        if (empty($my_net_ip)) {
            $my_net_ip = ifisset($param, 'net_ip');
        }
        $form[] = html::hidden('action', 'ipsubnet');
        $form[] = html::form_item('IP/Mask', html::input('text', 'my_net_info', $my_net_info), 1, 'IP & CIDR Netmask: 10.0.0.1/22<br />'
                        . 'IP & Netmask: 10.0.0.1 255.255.252.0<br />'
                        . 'IP & Wildcard Mask: 10.0.0.1 0.0.3.255');
        $form[] = html::form_item('IP in lan', html::input('text', 'my_net_ip', $my_net_ip), 1, 'IP: 10.0.0.1');
        $form_id = 'ipsubnet';
        $form[] = html::submit(t('Рассчитать'), $form_id);
        echo html::form($form, $form_id);
    }

    public function view() {
        if ($this->err) {
            return '';
        }
        $rows = [];
        ///['class'=>'blue']
        $rows[] = [t('Address:'),
            html::td($this->dq_host, ['class' => 'blue']),
            html::td($this->dotbin($this->bin_host, $this->cdr_nmask), ['class' => 'brown show_full']),
        ];
        $rows[] = [t('Netmask:'),
            html::td($this->dq_nmask, ['class' => 'blue']),
            html::td($this->dotbin($this->bin_nmask, $this->cdr_nmask), ['class' => 'brown show_full']),
        ];
        $rows[] = [t('Wildcard:'),
            html::td($this->bintodq($this->bin_wmask), ['class' => 'blue']),
            html::td($this->dotbin($this->bin_wmask, $this->cdr_nmask), ['class' => 'brown show_full']),
        ];
        $rows[] = [t('Network:'),
            html::td($this->bintodq($this->bin_net), ['class' => 'blue']),
            html::td($this->dotbin_net, ['class' => 'brown show_full']),
        ];
        $rows[] = [t('Broadcast:'),
            html::td($this->dq_bcast, ['class' => 'blue']),
            html::td($this->dotbin_net, ['class' => 'brown show_full']),
        ];
        $rows[] = [t('HostMin:'),
            html::td($this->dq_first, ['class' => 'blue']),
            html::td($this->dotbin($this->bin_first, $this->cdr_nmask), ['class' => 'brown show_full']),
        ];
        $rows[] = [t('HostMax:'),
            html::td($this->dq_last, ['class' => 'blue']),
            html::td($this->dotbin($this->bin_last, $this->cdr_nmask), ['class' => 'brown show_full']),
        ];
        $rows[] = [t('Hosts/Net:'), html::td($this->host_total, ['class' => 'blue'])];
        $rows[] = [t('Class:'), html::td($this->class, ['class' => 'green'])];
        if (!empty($this->find)) {
            $attr = ['class' => 'green'];
            $text = '??';
            if ($this->find_res) {
                $text = 'Принадлежит';
            } else {
                $text = 'Не принадлежит';
                $attr = ['class' => 'red'];
            }
            $rows[] = [t('IP in lan:'), html::td($text, $attr)];
        }
        return html::table(FALSE, $rows, 1, ['class' => 'monospace', 'style' => 'width: auto;']);
    }

    private function binnmtowm($binin) {
        $binin = rtrim($binin, "0");
        if (!ereg("0", $binin)) {
            return str_pad(str_replace("1", "0", $binin), 32, "1");
        } else
            return "1010101010101010101010101010101010101010";
    }

    private function bintocdr($binin) {
        return strlen(rtrim($binin, "0"));
    }

    private function bintodq($binin) {
        if ($binin == "N/A")
            return $binin;
        $binin = explode(".", chunk_split($binin, 8, "."));
        for ($i = 0; $i < 4; $i++) {
            $dq[$i] = bindec($binin[$i]);
        }
        return implode(".", $dq);
    }

    private function bintoint($binin) {
        return bindec($binin);
    }

    private function binwmtonm($binin) {
        $binin = rtrim($binin, "1");
        if (!ereg("1", $binin)) {
            return str_pad(str_replace("0", "1", $binin), 32, "0");
        } else
            return "1010101010101010101010101010101010101010";
    }

    private function cdrtobin($cdrin) {
        return str_pad(str_pad("", $cdrin, "1"), 32, "0");
    }

    private function dotbin($binin, $cdr_nmask) {
        // splits 32 bit bin into dotted bin octets
        if ($binin == "N/A")
            return $binin;
        $oct = rtrim(chunk_split($binin, 8, "."), ".");
        if ($this->cdr_nmask > 0) {
            $offset = sprintf("%u", $this->cdr_nmask / 8) + $this->cdr_nmask;
            return substr($oct, 0, $offset) . "&nbsp;&nbsp;&nbsp;" . substr($oct, $offset);
        } else {
            return $oct;
        }
    }

    private function dqtobin($dqin) {
        $dq = explode(".", $dqin);
        for ($i = 0; $i < 4; $i++) {
            $bin[$i] = str_pad(decbin($dq[$i]), 8, "0", STR_PAD_LEFT);
        }
        return implode("", $bin);
    }

    /* private function inttobin($intin) {
      return str_pad(decbin($intin), 32, "0", STR_PAD_LEFT);
      } */

    private function find_ip() {
        list($a, $b, $c, $d) = explode(".", $this->dq_first);
        list($ae, $be, $ce, $de) = explode(".", $this->dq_last);
        list($af, $bf, $cf, $df) = explode(".", $this->find);
        $find = [];
        for ($ai = $a; $ai <= $ae; $ai++) {
            if (intval($ai) === intval($af)) {
                $find[] = 1;
                break;
            }
        }
        for ($bi = $b; $bi <= $be; $bi++) {
            if (intval($bi) === intval($bf)) {
                $find[] = 1;
                break;
            }
        }
        for ($ci = $c; $ci <= $ce; $ci++) {
            if (intval($ci) === intval($cf)) {
                $find[] = 1;
                break;
            }
        }
        for ($di = $d; $di <= $de; $di++) {
            if (intval($di) === intval($df)) {
                $find[] = 1;
                break;
            }
        }
        if (array_sum($find) == 4) {
            $this->find_res = TRUE;
            return TRUE;
        }
        return FALSE;
    }

}
