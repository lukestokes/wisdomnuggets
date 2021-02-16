<?php
require_once __DIR__ . '/vendor/autoload.php';
$client = new GuzzleHttp\Client(['base_uri' => 'http://fio.greymass.com']);
include "objects.php";

if (php_sapi_name() != "cli") {
    die("Access Denied");
}

$clio_path = "/root/fio.ready/ubuntu_18/";
$clio_path = "/Users/lukestokes/Documents/workspace/FIO/chain_files/fio.ready-master/";
$clio = "clio --url https://fio.greymass.com ";

print "Welcome to the Wisdom Nuggets Faucet Admin\n\n";

$Faucet = new Faucet($client);

function showPending() {
    global $Faucet;
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

function selectPayment($oldest) {
    print "Which Payment Would you like to Process? (enter for oldest): ";
    $input = rtrim(fgets(STDIN));
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
    $oldest = showPending();
    if ($oldest != -1) {
        selectPayment($oldest);
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

$oldest = showPending();
if ($oldest != -1) {
    print br();
    $FaucetPayment = selectPayment($oldest);
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
            if ($parts[2] == "Locked") {
                print "Please unlock your wallet:" . br();
                print $clio_path . $clio . "wallet unlock" . br();
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
            } else {
                var_dump($parts);
            }
        }
    }
    if ($input == "n") {
        print "Would you like to reject this payment with a note (y/n)? ";
        $input = rtrim(fgets(STDIN));
        if ($input == "y") {
            print "Note: ";
            $input = rtrim(fgets(STDIN));
            $FaucetPayment->status = "Rejected";
            $FaucetPayment->note = $input;
            $FaucetPayment->save();
        }
    }
}


?>