<?php
require_once __DIR__ . '/vendor/autoload.php';
$client = new GuzzleHttp\Client(['base_uri' => 'http://fio.greymass.com']);
include "objects.php";
$Faucet = new Faucet($client);

if (php_sapi_name() != "cli") {
    die("Access Denied");
}

$clio_path = "/home/fio/ubuntu_18/";
//$clio_path = "/Users/lukestokes/Documents/workspace/FIO/chain_files/fio.ready-master/";
$clio = "clio --url https://fio.greymass.com ";

print "Welcome to the Wisdom Nuggets Faucet Admin\n\n";

$show = null;
if (isset($argv[1])) {
    if ($argv[1] == "users") {
        $show = "users";
    }
    if ($argv[1] == "payments") {
        $show = "payments";
    }
    if ($argv[1] == "rejected") {
        $show = "rejected";
    }
}
$id = null;
if (isset($argv[2])) {
    if (is_numeric($argv[2])) {
        $id = $argv[2];
    }
}


function printUsers($id = null) {
    $users = array();
    $User = new User("");
    if ($id) {
        $User->_id = $id;
        $User->read();
        $users[] = $User;
    } else {
        $users = $User->getUsers();
    }
    foreach ($users as $user) {
        $user->print();
        $user->showSessions();
    }
}

function showAll($Faucet) {
    print "All Payments:" . br();
    $FaucetPayments = $Faucet->getPayments(["status","!=","Rejected"]);
    foreach ($FaucetPayments as $key => $FaucetPayment) {
        print $FaucetPayment->_id . ": ";
        $FaucetPayment->print();
    }
}

function showRejected($Faucet) {
    print "Rejected Payments:" . br();
    $FaucetPayments = $Faucet->getPayments(["status","=","Rejected"]);
    foreach ($FaucetPayments as $key => $FaucetPayment) {
        print $FaucetPayment->_id . ": ";
        $FaucetPayment->print();
    }
}


function showPending($Faucet) {
    print "Pending Payments:" . br();
    $FaucetPayments = $Faucet->getPayments(["status","=","Pending"]);
    $oldest = -1;
    foreach ($FaucetPayments as $key => $FaucetPayment) {
        if ($oldest == -1) {
            $oldest = $FaucetPayment->_id;
        }
        $oldest = min($oldest,$FaucetPayment->_id);
        print $FaucetPayment->_id . ": ";
        $FaucetPayment->print();
    }
    return $oldest;
}

function rejectAllPending($Faucet, $time) {
    $time = $time - 10000;
    print "Rejecting all pending payments with the following note (press enter to skip): ";
    $input = rtrim(fgets(STDIN));
    if ($input != "") {
        $FaucetPayments = $Faucet->getPayments(["status","=","Pending"]);
        foreach ($FaucetPayments as $key => $FaucetPayment) {
            if ($FaucetPayment->time < $time) {
                $FaucetPayment->status = "Rejected";
                $FaucetPayment->note = $input;
                $FaucetPayment->save();
                print $FaucetPayment->_id . ": ";
                $FaucetPayment->print();
            }
        }
    }
}

function selectPayment($Faucet, $oldest) {
    print "Which Payment Would you like to Process? (enter for oldest, type 'cancel' to cancel all): ";
    $input = rtrim(fgets(STDIN));
    if ($input == "cancel") {
        rejectAllPending($Faucet, time());
        return;
    }
    $fetch_id = $oldest;
    if (is_numeric($input)) {
        $fetch_id = $input;
    }
    $FaucetPayment = new FaucetPayment();
    $FaucetPayment->_id = $fetch_id;
    $found = $FaucetPayment->read();
    if ($found) {
        return $FaucetPayment;
    }
    $oldest = showPending($Faucet);
    if ($oldest != -1) {
        selectPayment($Faucet, $oldest);
    }
}

function my_exec($cmd, $input='') {
    $proc=proc_open($cmd, array(0=>array('pipe', 'r'), 1=>array('pipe', 'w'), 2=>array('pipe', 'w')), $pipes);
    fwrite($pipes[0], $input);fclose($pipes[0]);
    $stdout=stream_get_contents($pipes[1]);fclose($pipes[1]);
    $stderr=stream_get_contents($pipes[2]);fclose($pipes[2]);
    $rtn=proc_close($proc);
    return array('stdout'=>$stdout,
               'stderr'=>$stderr,
               'return'=>$rtn
              );
}

function makePayment($Faucet, $selected) {
    global $clio_path;
    global $clio;
    print br();
    $FaucetPayment = selectPayment($Faucet, $selected);
    if (is_null($FaucetPayment)) {
        return;
    }
    $user = new User("");
    $user->_id = $FaucetPayment->user_id;
    $user->read();
    print br();
    print "--------------" . br();
    $user->print();
    $user->showSessions();
    print br();
    $FaucetPayment->print();
    $cmd = $FaucetPayment->cmd;
    print $clio_path . $clio . $cmd . br();
    print "Process payment (y/n)? ";
    $input = rtrim(fgets(STDIN));
    if ($input == "y") {
        $results = my_exec($clio_path . $clio . $cmd);
        if ($results["return"] == 1) {
            $str = strtok($results["stderr"], "\n");
            $parts = preg_split('/\s+/', $str);
            if (isset($parts[2]) && $parts[2] == "Locked") {
                print "Please unlock your wallet:" . br();
                print "./wallet.sh" . br();
                die();
            } else {
                print "=================== ERROR ===================" . br();
                print $results["stderr"] . br();
            }
        } else {
            $str = strtok($results["stderr"], "\n");
            $parts = preg_split('/\s+/', $str);
            if ($parts[0] == "executed") {
                $FaucetPayment->transaction_id = $parts[2];
                $FaucetPayment->status = "Paid";
                $FaucetPayment->save();
                print "Success! https://fio.bloks.io/transaction/" . $parts[2] . br();

                $stats = $Faucet->dataStore->findById(1);
                $stats["total_distributed"] += $FaucetPayment->amount;
                $Faucet->total_distributed = $stats["total_distributed"];
                $Faucet->dataStore->update($stats);

            } else {
                var_dump($parts);
            }
        }
    }
    if ($input == "n") {
        print "To reject this payment, enter a note (press enter to skip): ";
        $input = rtrim(fgets(STDIN));
        if ($input != "") {
            $FaucetPayment->status = "Rejected";
            $FaucetPayment->note = $input;
            $FaucetPayment->save();
        }
    }
}


if (is_null($show)) {
    $selected = showPending($Faucet);
    while($selected != -1) {
        makePayment($Faucet, $selected);
        $selected = showPending($Faucet);
    }
} else {
    if ($show == "users") {
        printUsers($id);
    }
    if ($show == "payments") {
        showAll($Faucet);
    }
    if ($show == "rejected") {
        showRejected($Faucet);
    }
}



?>