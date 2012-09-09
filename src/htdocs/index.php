<?php
require __DIR__ . '/../vendor/autoload.php';

$r3 = new Respect\Rest\Router;

$r3->any('/', function(){
	PHPQueue\Helpers::output(null, 200, "Hello, World!");
});
?>