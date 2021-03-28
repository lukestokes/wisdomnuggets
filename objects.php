<?php

date_default_timezone_set('America/Puerto_Rico');

function br() { return (PHP_SAPI === 'cli' ? "\n" : "<br />"); }

class FaucetPayment {
    public $_id;
    public $time;
    public $status;
    public $note;
    public $user_id;
    public $actor;
    public $fio_address;
    public $payee_public_key;
    public $amount;
    public $cmd;
    public $transaction_id;

    public $dataDir;
    public $dataStore;

    function __construct() {
        $this->dataDir = __DIR__ . "/user_data";
        $this->dataStore = new \SleekDB\Store("faucet_payment", $this->dataDir);
    }

    function save() {
        $faucetPaymentData = get_object_vars($this);
        unset($faucetPaymentData["dataDir"]);
        unset($faucetPaymentData["dataStore"]);
        if ($faucetPaymentData["_id"]) {
            $this->dataStore->update($faucetPaymentData);
        } else {
            unset($faucetPaymentData["_id"]);
            $faucet_payment = $this->dataStore->insert($faucetPaymentData);
            $this->_id = $faucet_payment["_id"];
        }
    }

    function loadData($data) {
        foreach (get_object_vars($this) as $key => $value) {
            if (array_key_exists($key, $data)) {
                $this->$key = $data[$key];
            }
        }
    }

    function read() {
        $user = $this->dataStore->findById($this->_id);
        if ($user) {
            $this->loadData($user);
        }
        return ($user);
    }

    function print() {
        print date("Y-m-d H:i:s",$this->time);
        print ": " . $this->fio_address . " got " . $this->amount . " FIO";
        print " (";
        if ($this->status == "Paid") {
            if (PHP_SAPI !== 'cli') {
                print '<a href="https://fio.bloks.io/transaction/' . $this->transaction_id . '" target="_blank">' . $this->status . '</a>';
            } else {
                print $this->status;
            }
        } else {
            print $this->status;
        }
        if ($this->note) {
            print ": " . $this->note;
        }
        print ")" . br();
    }
}

class Faucet {
    public $max_to_give = 5;
    public $min_to_give = 0.5;
    public $client;
    public $fio_address = "faucet@stokes";
    public $actor = "vuuchahodjvm";
    public $fio_public_key = "FIO64rU7M6jtc6QcgXFdqERrB99iv2sXK9WRR7Dg2JwezvfXStQMH";

    public $total_distributed = 0;

    public $dataDir;
    public $dataStore;

    function __construct($client) {
        $this->client = $client;
        $this->dataDir = __DIR__ . "/user_data";
        $this->dataStore = new \SleekDB\Store("faucet_stats", $this->dataDir);
    }

    function getTransferFee() {
        $fee = 0;
        try {
            $params = array(
                "end_point" => "transfer_tokens_pub_key",
                "fio_address" => "faucet@stokes"
            );
            $response = $this->client->post('/v1/chain/get_fee', [
                GuzzleHttp\RequestOptions::JSON => $params
            ]);
            $result = json_decode($response->getBody());
            $fee = $result->fee;
        } catch(\Exception $e) { }
        return $fee + 100000000; // add extra in case something changes between now and when it is executed.
    }

    function isWinner($completed, $user = null) {
        $upper_limit = 2000;
        $threshold = 1900; // 1 out of 20 by default
        if ($user) {
            $adjustment = floor($user->total_rewards / 10) * 100;
            $upper_limit += $adjustment;
            $threshold += $adjustment;
        }
        $threshold -= $completed;
        $pick = random_int(1, $upper_limit);
        $winner = ($pick > $threshold);
        $result = array(
            "winner" => $winner,
            "pick" => $pick,
            "threshold" => $threshold,
        );
        return $result;
    }

    function getRewardAmount($user = null) {
        $max_to_give = $this->max_to_give;
        if (!is_null($user)) {
            if (($user->total_rewards / 100) > 1) {
                $max_to_give -= ($max_to_give * 0.20); // if you win a lot, lower how much you can win by 20%
            }
        }
        $amount = random_int($this->min_to_give * 100,$max_to_give * 100);
        $amount /= 100;
        return $amount;
    }

    function distribute($user) {
        $amount = $this->getRewardAmount($user);
        $amount_in_SUF = $amount * 1000000000;
        $fee = $this->getTransferFee();
        $data = '{
          "payee_public_key": "' . $user->fio_public_key . '",
          "amount": "' . $amount_in_SUF . '",
          "max_fee": ' . $fee . ',
          "tpid": "' . $this->fio_address . '",
          "actor": "' . $this->actor . '"
        }';
        $cmd = "push action fio.token trnsfiopubky '" . $data . "' -p " . $this->actor . "@active";
        $FaucetPayment = new FaucetPayment();
        $FaucetPayment->user_id = $user->_id;
        $FaucetPayment->actor = $user->actor;
        $FaucetPayment->time = time();
        $FaucetPayment->fio_address = $user->fio_address;
        $FaucetPayment->amount = $amount;
        $FaucetPayment->payee_public_key = $user->fio_public_key;
        $FaucetPayment->cmd = $cmd;
        $FaucetPayment->status = "Pending";
        $FaucetPayment->save();
        return $FaucetPayment;
    }

    function getPayments($criteria = null, $limit = null) {
        $FaucetPayment = new FaucetPayment();
        if (is_null($criteria)) {
            $criteria = ["fio_address", "!=", ""];
        }
        $faucet_payments = $FaucetPayment->dataStore->findBy($criteria, ["time" => "desc"], $limit);
        $FaucetPayments = array();
        foreach ($faucet_payments as $key => $faucet_payment) {
            $Payment = new FaucetPayment();
            $Payment->loadData($faucet_payment);
            $FaucetPayments[] = $Payment;
        }
        return $FaucetPayments;
    }

    function printPayments($FaucetPayments) {
        foreach ($FaucetPayments as $key => $FaucetPayment) {
            $FaucetPayment->print();
        }
    }

    function totalDistributed() {
        if ($this->total_distributed) {
            return $this->total_distributed;
        }
        try {
            $stats = $this->dataStore->findById(1);
            $this->total_distributed = $stats["total_distributed"];
            if ($this->total_distributed) {
                return $this->total_distributed;
            }
        } catch (Exception $e) {
            // no stats yet...
        }
        $FaucetPayments = $this->getPayments(["status","=","Paid"]);
        foreach ($FaucetPayments as $FaucetPayment) {
            $this->total_distributed += $FaucetPayment->amount;
        }
        $stats = array("total_distributed" => $this->total_distributed);
        $this->dataStore->insert($stats);
        return $this->total_distributed;
    }
}

class Session {
    public $login_time;
    public $login_ip;
    public $fio_address;
    public $session_start_time;
    public $session_end_time;
    public $completed;
    
    function __construct($login_time,$login_ip,$fio_address,$session_start_time,$session_end_time,$completed) {
        $this->login_time = $login_time;
        $this->login_ip = $login_ip;
        $this->fio_address = $fio_address;
        $this->session_start_time = $session_start_time;
        $this->session_end_time = $session_end_time;
        $this->completed = $completed;
    }

    function getTimeInMinutes() {
        $time_in_seconds = ($this->session_end_time - $this->session_start_time);
        $time_in_minutes = $time_in_seconds / 60;
        return $time_in_minutes;
    }

    function print($last_login) {
        if ($last_login != $this->login_time) {
            if (PHP_SAPI !== 'cli') {
                print "<b>";
            }
            print $this->fio_address . " " . date("Y-m-d H:i:s",$this->login_time) . " from " . $this->login_ip;
            if (PHP_SAPI !== 'cli') {
                print "</b>";
            }
            print br();
        }
        print date("Y-m-d H:i:s",$this->session_start_time) . " to " . date("Y-m-d H:i:s",$this->session_end_time) . " (" . number_format($this->getTimeInMinutes(),2) . "m) : Completed " . $this->completed . br();
    }
}

class User {
    public $_id;
    public $actor;
    public $fio_address = "";
    public $fio_public_key;
    public $last_login;
    public $last_login_ip;
    public $auto_play;
    public $sessions = array();
    public $fio_addresses = array();
    public $types = array();
    public $total_rewards = 0;
    public $tweeted;

    // exclude from object properties
    public $dataDir;
    public $dataStore;

    function __construct($actor) {
        $this->actor = $actor;
        $this->dataDir = __DIR__ . "/user_data";
        $this->dataStore = new \SleekDB\Store("users", $this->dataDir);
    }

    function print() {
        print $this->_id . ": " . $this->actor . " " . $this->fio_address . " (" . $this->total_rewards . " FIO over " . number_format($this->getTimeSpentInMinutes(),2) . " minutes) Tweeted:" . $this->tweeted . br();
    }

    function saveSession($session_start_time, $completed, $auto_play, $types) {
        $this->auto_play = $auto_play;
        $this->types = $types;
        $this->sessions[] = new Session(
            $this->last_login,
            $this->last_login_ip,
            $this->fio_address,
            $session_start_time,
            time(),
            $completed
        );
        $this->save();
    }
    function showSessions() {
        $last_login = "";
        foreach ($this->sessions as $key => $Session) {
            $Session->print($last_login);
            $last_login = $Session->login_time;
        }
    }

    function getTimeSpentInMinutes() {
        $time_in_minutes = 0;
        foreach ($this->sessions as $key => $Session) {
            $time_in_minutes += $Session->getTimeInMinutes();
        }
        return $time_in_minutes;
    }

    function loadData($data) {
        foreach (get_object_vars($this) as $key => $value) {
            if ($key == "sessions") {
                $this->sessions = array();
                foreach ($data["sessions"] as $i => $session) {
                    if (!isset($session["session_end_time"])) {
                        $session["session_end_time"] = time();
                    }
                    $this->sessions[] = new Session(
                        $session["login_time"],
                        $session["login_ip"],
                        $session["fio_address"],
                        $session["session_start_time"],
                        $session["session_end_time"],
                        $session["completed"]
                    );
                }
            } else {
                if (array_key_exists($key, $data)) {
                    $this->$key = $data[$key];
                }
            }
        }
    }
    function save() {
        $userData = get_object_vars($this);
        unset($userData["dataDir"]);
        unset($userData["dataStore"]);
        if ($userData["_id"]) {
            $this->dataStore->update($userData);
        } else {
            unset($userData["_id"]);
            $user = $this->dataStore->insert($userData);
            $this->_id = $user["_id"];
        }
    }
    function read() {
        if ($this->_id) {
            $user = $this->dataStore->findById($this->_id);
        } else {
            $user = $this->dataStore->findOneBy(["actor", "=", $this->actor]);
        }
        if ($user) {
            $this->loadData($user);
        }
        return ($user);
    }
    function getUsers($criteria = null, $limit = null) {
        if (is_null($criteria)) {
            $criteria = ["actor", "!=", ""];
        }
        $users = $this->dataStore->findBy($criteria, ["last_login" => "desc"], $limit);
        $Users = array();
        foreach ($users as $key => $user) {
            $User = new User($user["actor"]);
            $User->loadData($user);
            $Users[] = $User;
        }
        return $Users;
    }
    function getFIOPublicKey($client) {
        if ($this->fio_public_key != "") {
            return $this->fio_public_key;
        }
        $params = array(
            "account_name" => $this->actor
        );
        try {
            $get_account_response = $client->post('/v1/chain/get_account', [
                GuzzleHttp\RequestOptions::JSON => $params
            ]);
            $response = json_decode($get_account_response->getBody());
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
            $get_fio_addresses_response = $client->post('/v1/chain/get_fio_addresses', [
                GuzzleHttp\RequestOptions::JSON => $params
            ]);
            $result = json_decode($get_fio_addresses_response->getBody());
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
    public $id;
    public $type;
    public $category;
    public $description;
    public $title;

    function __construct($id, $type, $category, $description, $title = "") {
        $this->id = $id;
        $this->type = $type;
        $this->category = $category;
        $this->description = $description;
        $this->title = $title;
    }

    function createWordGroup() {
        $smallest_group = 3;
        $largest_group = 5;
        $words = explode(" ", $this->description);
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
        $this->nuggets[] = new Nugget(count($this->nuggets), $this->type, $category, $description, $title);
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

    function getEntry($entry_type, $entry_id) {
        $chunk = $this->getChunk($entry_type);
        foreach ($chunk->nuggets as $nugget) {
            if ($nugget->id == $entry_id) {
                return $nugget;
            }
        }
        return null;
    }

    function getRandom($entry_type = "", $entry_category = "") {
        $all_entries = $this->getEntries($entry_type, $entry_category);
        if (count($all_entries) == 0) {
            return new Nugget(-1, "empty","nothing","You've chosen nothing, and you shall have it.");
        }
        $key = array_rand($all_entries);
        return $all_entries[$key];
    }
}