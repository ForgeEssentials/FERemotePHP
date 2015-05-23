# PHPRemoteLib

This small wrapper allows you to use functionality of FE remote with PHP.

## Example

````php
<?php

header("Content-type: text");

include "FERemote.php";

$feRemote = new FERemote('localhost', null, 'ForgeDevName', '123456');

try {
	$feRemote->connect();
	var_dump($feRemote->query('query_remote_capabilities'));
	var_dump($feRemote->query('query_player', array('flags' => array('location', 'detail'))));
} catch (SocketException $e) {
	$error = "Error: " . $e->getMessage();
}

````