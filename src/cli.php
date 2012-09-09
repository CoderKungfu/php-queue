<?php
// Usage:
// php cli.php <queuename> add --data '{"boo":"bar","foo":"car"}'
// php cli.php <queuename> work
require __DIR__ . '/vendor/autoload.php';

$queue_name = $argv[1];
$action = $argv[2];
$options = array('queue'=>$queue_name);
$c = new PHPQueue\Cli($options);

if ($action == 'add')
{
	$payload_json = $argv[4];
	$payload = json_decode($payload_json, true);
	$c->add($payload);
}
else
{
	$c->work();
}
?>
Done.