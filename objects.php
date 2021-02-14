<?php

class Session {
    public $login_time;
    public $login_ip;
    public $fio_address;
    public $session_start_time;
    public $completed;
    
    function __construct($User,$session_start_time,$completed) {
        $this->login_time = $User->last_login;
        $this->login_ip = $User->last_login_ip;
        $this->fio_address = $User->fio_address;
        $this->session_start_time = $session_start_time;
        $this->completed = $completed;
    }

    function print($last_login) {
        if ($last_login != $this->login_time) {            
            print "<b>" . $this->fio_address . " " . date("Y-m-d H:m:s",$this->login_time) . " from " . $this->login_ip . "</b><br />";
        }
        print date("Y-m-d H:m:s",$this->session_start_time) . ": Completed " . $this->completed . "<br />";
    }
}

class User {
    public $actor;
    public $fio_address = "";
    public $fio_public_key;
    public $last_login;
    public $last_login_ip;
    public $auto_play;
    public $sessions = array();
    public $fio_addresses = array();
    public $types = array();

    function __construct($actor) {
        $this->actor = $actor;
    }

    function saveSession($session_start_time, $completed, $auto_play, $types) {
        $this->auto_play = $auto_play;
        $this->types = $types;
        $this->sessions[] = new Session($this, $session_start_time, $completed);
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
            'actor' => $this->actor,
            'fio_address' => $this->fio_address,
            'fio_public_key' => $this->fio_public_key,
            'last_login' => $this->last_login,
            'last_login_ip' => $this->last_login_ip,
            'auto_play' => $this->auto_play,
            'sessions' => $this->sessions,
            'fio_addresses' => $this->fio_addresses,
            'types' => $this->types,
        );
        return $data;
    }
    function loadData($data) {
        $this->actor = $data['actor'];
        $this->fio_address = $data['fio_address'];
        $this->fio_public_key = $data['fio_public_key'];
        $this->last_login = $data['last_login'];
        $this->last_login_ip = $data['last_login_ip'];
        $this->auto_play = $data['auto_play'];
        $this->sessions = $data['sessions'];
        $this->fio_addresses = $data['fio_addresses'];
        $this->types = $data['types'];
    }
    function getUserFilename() {
        $file_name = "./user_data/" . $this->actor . ".txt"; // TODO: change this to a non-web accessible folder
        return $file_name;
    }
    function save() {
        file_put_contents($this->getUserFilename(),serialize($this->getData()));
    }
    function read() {
        $seralized_data = @file_get_contents($this->getUserFilename());
        if ($seralized_data) {
            $this->loadData(unserialize($seralized_data));
        }
        return ($seralized_data);
    }
    function getFIOPublicKey($client) {
        if ($this->fio_public_key != "") {
            return $this->fio_public_key;
        }
        $params = array(
            "account_name" => $this->actor
        );
        try {
          $response = $client->chain()->getAccount($params);
          //var_dump($response);
          foreach ($response->permissions as $key => $permission) {
              //var_dump($permission);
              if ($permission->perm_name == "active") {
                  if (isset($permission->required_auth->keys[0])) {
                      $this->fio_public_key = $permission->required_auth->keys[0]->key;
                  }
              }
          }
        } catch(\Exception $e) {
            //print $e->getMessage() . "\n";
        }
        return $this->fio_public_key;
    }

    function getFIOAddresses($client) {
        if (count($this->fio_addresses)) {
            return $this->fio_addresses;
        }
        $fio_public_key = $this->getFIOPublicKey($client);
        $params = array(
            "fio_public_key" => $fio_public_key,
            "limit" => 100,
            "offeset" => 0
        );
        try {
            $result = $client->chain()->getFioAddresses($params);
            if (isset($result->fio_addresses[0])) {
                foreach ($result->fio_addresses as $key => $fio_address_object) {
                    $this->fio_addresses[] = $fio_address_object->fio_address;
                }
            }
        } catch(\Exception $e) {
            //print "getFIOAddress error: " . $e->getMessage();
        }
        return $this->fio_addresses;
    }

    function isOwnedFIOAddress($client, $fio_address) {
        return in_array($fio_address, $this->getFIOAddresses($client));
    }

    function getFIOAddressSelectionForm() {
        $form_string = "";
        $form_string .= '
            <form method="GET" id="fio_address_selection_form">
            <input type="hidden" id="next_action" name="next_action" value="use_fio_address">
        ';
        foreach ($this->fio_addresses as $key => $fio_address) {
            $form_string .= '
            <div class="form-check">
            <input class="form-check-input" name="user_fio_address" id="user_fio_address_' . $key . '" type="radio" value="' . $fio_address . '">
            <label class="form-check-label" for="user_fio_address_' . $key . '">' . $fio_address . '</label>
            </div>';
        }
        $form_string .= '
            <button type="submit" class="btn btn-primary mb-3">Use This Address</button>
            </form>
        ';
        return $form_string;
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

    function setActiveChunks($types) {
        foreach ($this->chunks as $key => $chunk) {
            $this->chunks[$key]->included = in_array($chunk->type, $types);
        }
    }

    function getTypesFromGet() {
        $types = array();
        foreach ($this->chunks as $chunk) {
            if (isset($_GET["include_chunk_" . $chunk->type]) && $_GET["include_chunk_" . $chunk->type] == "on") {
                $types[] = $chunk->type;
            }
        }
        return $types;
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