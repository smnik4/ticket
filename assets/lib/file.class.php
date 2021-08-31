<?php

class File {

    private $id = 0;
    private $user_id = 0;
    private $ticket_id = 0;
    private $message_id = 0;
    private $form_id = NULL;
    private $name = FALSE;
    private $file_path = FALSE;
    private $full_path = FALSE;
    private $size = 0;
    private $ext = FALSE;
    private $date = FALSE;
    private $tmp_name = FALSE;
    private $error = 0;
    private $cache = NULL;
    private $ALLOW_FILE_TYPE = array(
        "zip", "rar", "7z", "cab", "tar", "gz", "gzip",
        "iso",
        "pdf", "tif", "tiff", "xps",
        "doc", "docx", "rtf", "txt",
        "xls", "xlsx",
        "exe", "bat", "cmd", "ps1", "vbe",
        "jpg", "jpeg", "bmp", "png",
        "reg", "crt", "cer", "req",
        "vsd",
        "pl",
    );

    public function __construct($ticket_id = 0, $message_id = 0, $file_id = 0) {
        global $USER, $DB, $CONFIG;
        $this->ticket_id = $ticket_id;
        if ($message_id > 0) {
            $this->message_id = $message_id;
        }
        if ($file_id > 0) {
            $this->load($file_id);
        }
    }

    public function load($file_id) {
        global $DB, $CONFIG;
        if ($file_id > 0) {
            $sel = $DB->prepare("SELECT * FROM `tickets_attachment` WHERE `id`=:id");
            $sel->execute(array('id' => $file_id));
            if ($sel->rowCount() > 0) {
                $this->id = $file_id;
                $data = $sel->fetch();
                $this->user_id = $data['user_id'];
                $this->ticket_id = $data['ticket_id'];
                $this->message_id = $data['message_id'];
                $this->size = $data['size'];
                $this->name = $data['name'];
                $this->ext = $data['ext'];
                $this->date = strtotime($data['upload']);
                $this->file_path = $data['path'];
                if (isset($CONFIG['PATH']['www']['paths'])) {
                    if (!empty($CONFIG['PATH']['www']['paths'])) {
                        $this->file_path = $CONFIG['PATH']['www']['paths'] . $this->file_path;
                    }
                }
                if (isset($CONFIG['PATH']['CFG']['doc_root'])) {
                    $this->full_path = $CONFIG['PATH']['CFG']['doc_root'];
                } else {
                    $this->full_path = $_SERVER['DOCUMENT_ROOT'];
                }
                $this->full_path .= $this->file_path;
                if (!file_exists($this->full_path)) {
                    set_error('Файл отсутствует в файловой системе!');
                    return FALSE;
                }
                return TRUE;
            } else {
                set_error('Файл не найден или удален!');
            }
        }
        return FALSE;
    }

    public function download() {
        $this->name = preg_replace("/\.+/", ".", $this->name);
        $this->name = preg_replace("/\s+/", " ", $this->name);
        $mime = mime_content_type($this->full_path);
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Type: ' . $mime);
        if (in_array($this->ext, array("pdf", "jpg", "jpeg", "png", "mp4"))) {
            header("Content-Disposition: inline; filename*=UTF-8''" . rawurlencode($this->name));
        } else {
            header("Content-Disposition: attachment; filename*=UTF-8''" . rawurlencode($this->name));
        }
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($this->full_path));
        if ($fd = fopen($this->full_path, 'rb')) {
            while (!feof($fd)) {
                print fread($fd, 1024);
            }
            fclose($fd);
        }
        exit;
    }

    static public function get_link($file) {
        global $CONFIG;
        $pre = '';
        if (isset($CONFIG['PATH']['www']['paths'])) {
            if (!empty($CONFIG['PATH']['www']['paths'])) {
                $pre = $CONFIG['PATH']['www']['paths'];
            }
        }
        return sprintf('<a href="%s/files/?file=%s" target="_blank">%s</a>', $pre, $file['id'], $file['name']);
    }

    public function upload_file($name, $tmp_name, $error, $size) {
        global $DB, $USER;
        $this->form_id = filter_input(INPUT_POST, "form_id");
        $this->ext = $this->get_ext($name);
        if (!in_array($error, array(0, 4))) {
            set_error($this->get_error($error));
            return FALSE;
        } elseif ($error == 0) {
            if (!in_array($this->ext, $this->ALLOW_FILE_TYPE)) {
                set_error(sprintf('Не допустимый тип файла: %s', $name));
                return FALSE;
            }
            $this->tmp_name = $tmp_name;
            $this->cache = md5_file($tmp_name);
            $this->name = $name;
            $this->error = $error;
            $this->size = $size;
            $this->user_id = $USER->id;
            if (!$this->check_uploaded()) {
                $ins = $DB->prepare("INSERT INTO `tickets_attachment`(`ticket_id`, `message_id`, `user_id`, `form_id`, `name`, `size`, `ext`, `cache`) "
                        . "VALUES (:ticket_id, :message_id, :user_id, :form_id, :name, :size, :ext, :cache)");
                $d = array(
                    'ticket_id' => $this->ticket_id,
                    'message_id' => $this->message_id,
                    'user_id' => $this->user_id,
                    'form_id' => $this->form_id,
                    'name' => $this->name,
                    'size' => $this->size,
                    'ext' => $this->ext,
                    'cache' => $this->cache,
                );
                $ins->execute($d);
                if ($ins->rowCount() > 0) {
                    $this->id = $DB->lastInsertId();
                    if ($this->create_file_path()) {
                        if (move_uploaded_file($tmp_name, $this->full_path)) {
                            $upd = $DB->prepare("UPDATE `tickets_attachment` SET `path`=:path WHERE `id`=:id");
                            $upd->execute(array('path' => $this->file_path, 'id' => $this->id));
                            if ($upd->rowCount() > 0) {
                                /* $TE = Ticket::set_ticket_event(
                                  $this->ticket_id,
                                  $USER->id,
                                  sprintf('Загрузил файл: %s [%s]',$this->name, self::transform_size($this->size)),
                                  1); */
                                return $this->id;
                            } else {
                                set_error('Ошибка сервера: Файл не загружен. #03');
                                $this->delete();
                                return FALSE;
                            }
                        } else {
                            set_error('Ошибка сервера: Файл не загружен. #02');
                            $this->delete();
                            return FALSE;
                        }
                    } else {
                        $this->delete();
                    }
                } else {
                    set_error('Ошибка сервера: Файл не загружен. #01');
                    return FALSE;
                }
            } else {
                set_info(sprintf('Файл уже загружен и был пропущен: %s', $this->name));
            }
        }
        return FALSE;
    }

    static public function remove_file($id) {
        global $CONFIG, $DB, $USER;
        $file = new File();
        $file->load($id);
        if ($file->id > 0) {
            $remove = TRUE;

            if (file_exists($file->full_path)) {
                if (!unlink($file->full_path)) {
                    $remove = FALSE;
                }
            }
            $files_path = dirname($file->full_path);
            $path_files = scandir($files_path);
            if (count($path_files) <= 2) {
                rmdir($files_path);
            }
            if (!$remove) {
                set_error('Файл не удален с файловой системы');
            } else {
                if ($file->delete()) {
                    //return $file->ticket_id;
                    Ticket::set_ticket_event($file->ticket_id, $USER->id, sprintf('Удалил файл: %s', $file->name), 1);
                    return TRUE;
                }
            }
        }
        return FALSE;
    }

    private function delete() {
        global $DB;
        $d = $DB->prepare("DELETE FROM `tickets_attachment` WHERE `id`=:id");
        $d->execute(array('id' => $this->id));
        if ($d->rowCount() > 0) {
            return TRUE;
        } else {
            set_error('Ошибка сервера: Файл не удален. ' . $this->id);
            return FALSE;
        }
    }

    static public function delete_link($id, $ovner = 0, $area = 0) {
        global $USER;
        if ($USER->id == $ovner OR in_array($area, $USER->group_manager) OR isset($USER->groups['Admins']) OR $ovner === 0) {
            return sprintf('<span class="ticket_page_button delete_file" onclick="confirm_exec(this,\'delete_file\',{\'id\':%s});" title="Удалить файл"></span>', $id);
        }
        return '';
    }

    private function get_ext($name) {
        return pathinfo($name, PATHINFO_EXTENSION);
    }

    private function check_uploaded() {
        global $DB;
        $sel = $DB->prepare("SELECT * FROM `tickets_attachment` WHERE `ticket_id`=:ticket_id AND "
                . "`message_id`=:message_id AND "
                . "`user_id`=:user_id AND "
                . "`form_id`=:form_id AND "
                . "`name`=:name");
        $sel->execute(array(
            'ticket_id' => $this->ticket_id,
            'message_id' => $this->message_id,
            'user_id' => $this->user_id,
            'form_id' => $this->form_id,
            'name' => $this->name,
        ));
        if ($sel->rowCount() > 0) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    private function create_file_path() {
        global $CONFIG;
        if (isset($CONFIG['PATH']['CFG']['doc_root'])) {
            $this->full_path = $CONFIG['PATH']['CFG']['doc_root'];
        } else {
            $this->full_path = $_SERVER['DOCUMENT_ROOT'];
        }
        if (isset($CONFIG['PATH']['www']['paths'])) {
            if (!empty($CONFIG['PATH']['www']['paths'])) {
                $this->full_path .= $CONFIG['PATH']['www']['paths'];
                //$this->file_path .= $CONFIG['PATH']['www']['paths'];
            }
        }
        if (isset($CONFIG['PATH']['CFG']['filedir'])) {
            $this->full_path .= $CONFIG['PATH']['CFG']['filedir'];
            $this->file_path .= $CONFIG['PATH']['CFG']['filedir'];
        } else {
            set_error('Ошибка конфигурации: Не задана директория загрузки.');
            return FALSE;
        }
        $pp = "/" . strtolower(substr(md5($this->ticket_id), 1, 2));
        $check_dir = $pp . "/" . $this->ticket_id;
        $this->full_path .= $check_dir;
        $this->file_path .= $check_dir;
        if (!file_exists($this->full_path)) {
            if (!mkdir($this->full_path, 0754, TRUE)) {
                set_error('Ошибка конфигурации: Не удалось создать директорию загрузки.');
                return FALSE;
            }
        }
        $this->full_path .= "/" . $this->id;
        $this->file_path .= "/" . $this->id;
        return TRUE;
    }

    private function get_error($error) {
        switch ($error) {
            case 1:
            case 2:
                $text = 'Размер принятого файла превысил максимально допустимый размер.';
                break;
            case 3:
                $text = 'Загружаемый файл был получен только частично.';
                break;
            case 4:
                $text = 'Файл не был загружен.';
                break;
            case 6:
                $text = 'Ошибка сервера: Отсутствует временная папка.';
                break;
            case 7:
                $text = 'Ошибка сервера: Не удалось записать файл на диск.';
                break;
            default :
                $text = 'Ошибок не возникло, файл был успешно загружен на сервер.';
        }
        return text;
    }

    static public function form_max_size($text = '') {
        $ms = ini_get("post_max_size");
        $mu = ini_get("upload_max_filesize");
        if ($mu < $ms) {
            $ms = $mu;
        }
        if (preg_match("/^(\d*)(\w*)/", $ms, $f)) {
            $ms = $f[1];
            switch ($f[2]) {
                case "T": $ms = $ms * 1024;
                case "G": $ms = $ms * 1024;
                case "M": $ms = $ms * 1024;
                case "K": $ms = $ms * 1024;
            }
        }
        return sprintf('<input type="hidden" name="MAX_FILE_SIZE" value="%s" />'
                . $text
                . '<br/><sup>Максисмум %s</sup>', $ms, self::transform_size($ms));
    }

    static public function transform_size($val = 0) {
        $name = array(
            'б',
            'Кб',
            'Мб',
            'Гб',
            'Тб',
        );
        $i = 0;
        while ($val > 1024) {
            $i++;
            $val = $val / 1024;
        }
        return number_format($val, 2, ",", "") . $name[$i];
    }

    static public function transform_name($name) {
        $name = explode(".", $name);
        $i = count($name) - 1;
        if (isset($name[$i])) {
            unset($name[$i]);
        }

        return implode(".", $name);
    }

}
