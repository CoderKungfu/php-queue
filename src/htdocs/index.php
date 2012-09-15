<?php
require __DIR__ . '/../config.php';

$r3 = new Respect\Rest\Router;

$r3->any('/', function(){
	PHPQueue\Helpers::output(null, 200, "Hello, World!");
});
?>