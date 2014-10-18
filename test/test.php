#!/usr/bin/env php
<?php

require '../src/SupervisorClient/SupervisorClient.php';
use SupervisorClient\SupervisorClient;

function test($configFile, SupervisorClient $supervisorClient) {
    print "> Testing $configFile\n";
    exec('killall supervisord >/dev/null 2>&1');

    $command = "supervisord -c $configFile";
    exec($command, $output, $return_val);
    if ($return_val !== 0) {
        print "✘ Could not execute `$command`\n";
        return;
    }

    $success = true;
    for ($i=0; $i<2; $i++) {
        $success &= $supervisorClient->getAllProcessInfo()[0]['name'] === 'cat';
    }
    print ($success ? '✔ Test passed' : '✘ Test failed') . "\n";
}

$supervisor = new SupervisorClient('unix:///tmp/supervisor.sock');
test('unix-http-server-without-password.conf', $supervisor);

$supervisor = new SupervisorClient('unix:///tmp/supervisor.sock');
$supervisor->setAuth('user', 'password');
test('unix-http-server-with-password.conf', $supervisor);

$supervisor = new SupervisorClient('localhost', 9001);
test('inet-http-server-without-password.conf', $supervisor);

$supervisor = new SupervisorClient('localhost', 9001);
$supervisor->setAuth('user', 'password');
test('inet-http-server-with-password.conf', $supervisor);
