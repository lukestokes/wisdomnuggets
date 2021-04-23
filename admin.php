<?php
require_once __DIR__ . '/vendor/autoload.php';
$client = new GuzzleHttp\Client(['base_uri' => 'http://fio.greymass.com']);
include "objects.php";
$Faucet = new Faucet($client);

/*
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
*/

if (php_sapi_name() != "cli") {
    die("Access Denied");
}

$override_fee = 0;

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
    if ($argv[1] == "fees") {
        $override_fee = 1000000000;
    }
}
$id = null;
if (isset($argv[2])) {
    if (is_numeric($argv[2])) {
        $id = $argv[2];
    }
}


function printUsers($id = null) {
    global $Faucet;
    $user_count = 0;
    $total_time_spent_in_minutes = 0;
    $total_rewards = 0;
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
        $user_count++;
        $total_time_spent_in_minutes += $user->getTimeSpentInMinutes();
        $total_rewards += $user->total_rewards;
        $user->print();
        if ($id) {
            $user->showSessions();
        } else {
            $ips = array();
            foreach ($user->sessions as $key => $Session) {
                if (!in_array($Session->login_ip, $ips)) {
                    $ips[] = $Session->login_ip;
                }
            }
            print "     ";
            foreach ($ips as $ip) {
                print $ip . " ";
            }
            print "\n";
        }
    }
    print "\nUsers: " . $user_count . "\n";
    print "Total time spent: " . number_format($total_time_spent_in_minutes/60,2) . " hours.\n";
    print "Total rewards: " . $total_rewards . " FIO.\n";
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
    $total_pending = 0;
    print "Pending Payments:" . br();
    $FaucetPayments = $Faucet->getPayments(["status","=","Pending"]);
    $oldest = -1;
    foreach ($FaucetPayments as $key => $FaucetPayment) {
        if ($oldest == -1) {
            $oldest = $FaucetPayment->_id;
        }
        $oldest = min($oldest,$FaucetPayment->_id);
        $total_pending += $FaucetPayment->amount;
        print $FaucetPayment->_id . ": ";
        $FaucetPayment->print();
    }
    print br() . "Total Pending: " . $total_pending . " FIO" . br() . br();
    return $oldest;
}

function rejectAllPending($Faucet, $time) {
    $time = $time - 60;
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

function rejectAllPendingLowCaptcha($Faucet, $time) {
    $time = $time - 60;
    print "Rejecting all pending payments with a captcha of 0.3 and lower with the following note (press enter to skip): ";
    $input = rtrim(fgets(STDIN));
    if ($input != "") {
        $FaucetPayments = $Faucet->getPayments([["status","=","Pending"],['captcha_score',"<=",0.3]]);
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

function payAllPending($Faucet, $time) {
    $time = $time - 60;
    print "Are you sure you want to pay all pending payments (y/n)? ";
    $input = rtrim(fgets(STDIN));
    if ($input == "y") {
        $FaucetPayments = $Faucet->getPayments(["status","=","Pending"]);
        foreach ($FaucetPayments as $key => $FaucetPayment) {
            if ($FaucetPayment->time < $time) {
                makePayment($Faucet, $FaucetPayment, false);
            }
        }
    }
}


function selectPayment($Faucet, $oldest) {
    print "Which Payment Would you like to Process?\n";
    print " Type enter for oldest\n Type 'cancel' to cancel all\n Type 'cancel_low_captcha' to cancel all low captcha\n Type 'approve' to pay all): ";
    $input = rtrim(fgets(STDIN));
    if ($input == "cancel_low_captcha") {
        rejectAllPendingLowCaptcha($Faucet, time());
        return;
    }
    if ($input == "cancel") {
        rejectAllPending($Faucet, time());
        return;
    }
    if ($input == "approve") {
        payAllPending($Faucet, time());
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

function makePayment($Faucet, $FaucetPayment, $prompt = true) {
    global $clio_path;
    global $clio;
    global $override_fee;
    print br();
    if (is_null($FaucetPayment)) {
        return;
    }
    $user = new User("");
    $user->_id = $FaucetPayment->user_id;
    $user->read();
    if ($prompt) {
        print br();
        print "--------------" . br();
        $user->print();
        $user->showSessions();
        print br();
    }
    $FaucetPayment->print();
    $cmd = $FaucetPayment->cmd;
    if ($override_fee) {
        $cmd = preg_replace('/"max_fee": ([0-9])*/','"max_fee": 10000000000',$cmd);
    }
    print $clio_path . $clio . $cmd . br();
    if ($prompt) {
        print "Process payment (y/n)? ";
        $input = rtrim(fgets(STDIN));
    } else {
        $input = "y";
    }
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

                $user->total_rewards += $FaucetPayment->amount;
                $user->save();

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
        $FaucetPayment = selectPayment($Faucet, $selected);
        makePayment($Faucet, $FaucetPayment);
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