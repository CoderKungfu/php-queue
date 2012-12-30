#!/usr/bin/php
<?php
// Usage:
// php cli.php <queuename> add --data '{"boo":"bar","foo":"car"}'
// php cli.php <queuename> work
require_once __DIR__ . '/config.php';

$queue_name = $argv[1];
$action = $argv[2];
$options = array('queue'=>$queue_name);
$c = new PHPQueue\Cli($options);

switch ($action) {
    case 'add':
        $payload_json = $argv[4];
        $payload = json_decode($payload_json, true);
        $c->add($payload);
        break;
    case 'work':
        $c->work();
        break;
    case 'get':
        break;
    default:
        echo "Error: No action declared...\n";
        break;
}
