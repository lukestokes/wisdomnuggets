<?php

class Session {
    public $login_time;
    public $login_ip;
    public $session_start_time;
    public $completed;
    
    function __construct($User,$session_start_time,$completed) {
        $this->login_time = $User->last_login;
        $this->login_ip = $User->last_login_ip;
        $this->session_start_time = $session_start_time;
        $this->completed = $completed;
    }

    function print($last_login) {
        if ($last_login != $this->login_time) {            
            print "<b>Login " . date("Y-m-d H:m:s",$this->login_time) . " from " . $this->login_ip . "</b><br />";
        }
        print date("Y-m-d H:m:s",$this->session_start_time) . ": Completed " . $this->completed . "<br />";
    }
}

class User {
    public $fio_address;
    public $password_hash;
    public $last_login;
    public $last_login_ip;
    public $auto_play;
    public $sessions = array();

    function __construct($fio_address) {
        $this->fio_address = $fio_address;
    }

    function saveSession($session_start_time, $completed, $auto_play) {
        $this->auto_play = $auto_play;
        $this->sessions[] = new Session($this, $session_start_time,$completed);
        $this->save();
    }
    function showSessions() {
        $last_login = "";
        foreach ($this->sessions as $key => $Session) {
            $Session->print($last_login);
            $last_login = $Session->login_time;
        }
    }
    function getData() {
        $data = array(
            'fio_address' => $this->fio_address,
            'password_hash' => $this->password_hash,
            'last_login' => $this->last_login,
            'last_login_ip' => $this->last_login_ip,
            'auto_play' => $this->auto_play,
            'sessions' => $this->sessions,
        );
        return $data;
    }
    function loadData($data) {
        $this->fio_address = $data['fio_address'];
        $this->password_hash = $data['password_hash'];
        $this->last_login = $data['last_login'];
        $this->last_login_ip = $data['last_login_ip'];
        $this->auto_play = $data['auto_play'];
        $this->sessions = $data['sessions'];
    }
    function getUserFilename() {
        $file_name = "./user_data/" . md5($this->fio_address) . ".txt"; // change this to a non-web accessible folder
        return $file_name;
    }
    function login($plaintext) {
        $this->read();
        if ($this->checkPassword($plaintext)) {
            $this->last_login = time();
            $this->last_login_ip = $_SERVER['REMOTE_ADDR'];
            $this->save();
            return true;
        } else {
            return false;
        }
    }

    function checkPassword($plaintext) {
        return password_verify($plaintext, $this->password_hash);
    }

    function updatePassword($plaintext) {
        $this->password_hash = password_hash($plaintext, PASSWORD_DEFAULT);
    }

    function save() {
        file_put_contents($this->getUserFilename(),serialize($this->getData()));
    }
    function read() {
        $seralized_data = @file_get_contents($this->getUserFilename());
        if ($seralized_data) {
            $this->loadData(unserialize($seralized_data));
        }
    }
    function userExists() {
        $seralized_data = @file_get_contents($this->getUserFilename());
        return ($seralized_data);
    }

}


class Nugget {
    public $type;
    public $category;
    public $description;
    public $title;

    function __construct($type, $category, $description, $title = "") {
        $this->type = $type;
        $this->category = $category;
        $this->description = $description;
        $this->title = $title;
    }

    function createWordGroup() {
        $smallest_group = 3;
        $largest_group = 5;
        $words = str_word_count($this->description, 1);
        if (count($words) <= 8) {
            $smallest_group = 2;
            $largest_group = 3;
        }
        if (count($words) > 40) {
            $smallest_group = 5;
            $largest_group = 8;
        }
        $grouped_words = array();
        $group_size = rand($smallest_group,$largest_group);
        $group = "";
        foreach ($words as $key => $value) {
            $group .= $value . " ";
            if (str_word_count($group,0) == $group_size) {
                $grouped_words[] = $group;
                $group_size = rand($smallest_group,$largest_group);
                $group = "";
            }
        }
        if ($group != "") {
            $grouped_words[] = $group;
        }
        return $grouped_words;
    }
}

class Chunk {
    public $type;
    public $description;
    public $url;
    public $nuggets = array();
    public $included = true;

    function __construct($type, $description, $url) {
        $this->type = $type;
        $this->description = $description;
        $this->url = $url;
    }

    function addNugget($category, $description, $title = "") {
        $this->nuggets[] = new Nugget($this->type, $category, $description, $title);
    }
}

class Wisdom {
    public $chunks = array();

    function addChunk($chunk) {
        $this->chunks[] = $chunk;
    }

    function getChunk($type) {
        foreach ($this->chunks as $chunk) {
            if ($chunk->type == $type) {
                return $chunk;
            }
        }
    }

    function getTypesAndCategories() {
        $types_and_categories = array();
        foreach ($this->chunks as $chunk) {
            if ($chunk->included) {
                foreach ($chunk->nuggets as $key => $nugget) {
                    $type_and_category = $chunk->type . "|" . $nugget->category;
                    if (!in_array($type_and_category, $types_and_categories)) {
                        $types_and_categories[] = $type_and_category;
                    }
                }
            }
        }
        return $types_and_categories;
    }

    function printAutoplayOptions($auto_play) {
        $auto_play_options = array(
          0 => '(no autoplay)',
          1 => '1 second',
          3 => '3 seconds',
          5 => '5 seconds',
          10 => '10 seconds',
        );
        foreach ($auto_play_options as $key => $value) {
            $selected = ($key == $auto_play) ? " selected" : "";
            print "<option" . $selected . " value=\"" . $key . "\">" . $value . "</option>\n";
        }
    }

    function printTypeCategoryOptions($selected_type_category) {
        $selected = ($selected_type_category == "") ? " selected" : "";
        print "<option" . $selected . " value=\"\">(no filter)</option>\n";
        foreach ($this->getTypesAndCategories() as $key => $type_category) {
            $selected = ($selected_type_category == $type_category) ? " selected" : "";
            $type_and_category = explode("|", $type_category);
            print "<option" . $selected . " value=\"" . $type_category . "\">" . ucwords($type_and_category[0]) . ": " . $type_and_category[1] . "</option>\n";
        }
    }

    function getChunkTypes() {
        $chunk_types = array();
        foreach ($this->chunks as $chunk) {
            $chunk_types[] = $chunk->type;
        }
        return $chunk_types;
    }

    function printChunkCheckboxes() {
        foreach ($this->chunks as $chunk) {
            $checked = $chunk->included ? " checked" : "";
            print "<div class=\"form-check\">";
            print "<input" . $checked . " class=\"form-check-input\" name=\"include_chunk_" . $chunk->type . "\" id=\"include_chunk_" . $chunk->type . "\" type=\"checkbox\">\n";
            print "<label class=\"form-check-label\" for=\"include_chunk_" . $chunk->type . "\">" . count($chunk->nuggets) . " " . $chunk->description . " <a href=\"" . $chunk->url . "\">(source)</a></label>\n";
            print "</div>";
        }
    }

    function getEntries($entry_type = "", $entry_category = "") {
        $entries = array();
        if ($entry_type != "") {
            $chunk = $this->getChunk($entry_type);
            if ($chunk->included) {
                if ($entry_category != "") {
                    foreach ($chunk->nuggets as $nugget) {
                        if ($nugget->category == $entry_category) {
                            $entries[] = $nugget;
                        }
                    }
                } else {
                    $entries = $chunk->nuggets;
                }
            }
            return $entries;
        } else {
            foreach ($this->chunks as $chunk) {
                if ($chunk->included) {
                    foreach ($chunk->nuggets as $nugget) {
                        $entries[] = $nugget;
                    }
                }
            }
            return $entries;
        }
    }

    function getRandom($entry_type = "", $entry_category = "") {
        $all_entries = $this->getEntries($entry_type, $entry_category);
        if (count($all_entries) == 0) {
            return new Nugget("empty","nothing","You've chosen nothing, and you shall have it.");
        }
        $key = array_rand($all_entries);
        return $all_entries[$key];
    }
}