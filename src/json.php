<?php

include "Client.php";

use ForgeEssentials\Remote\Client;
use ForgeEssentials\Remote\SocketException;

// Configure these parameters to your needs
const ADDRESS = 'localhost';
const PORT = 27031;

// WARNING: Setting a default user and password can be VERY DANGEROUS!
// Be sure to only use this with a non-existing username who has only restricted privileges!
const DEFAULT_USER = null;
const DEFAULT_PASSKEY = null;

function getArg($name) {
	return isset($_REQUEST[$name]) ? $_REQUEST[$name] : null;
}

function error($message) {
	header("Content-type: application/json");
	echo json_encode(array(
		'success' => false,
		'message' => $message
	));
	exit;
}


$id = getArg('id');
$rid = getArg('rid');
$data = getArg('data');
$username = getArg('username');
$passkey = getArg('passkey');

if (!$username)
	$username = DEFAULT_USER;

if (!$passkey)
	$passkey = DEFAULT_PASSKEY;

if (!$id)
	return error('Missing request ID');

if ($username && !$passkey)
	return error('Missing passkey');

$remote = new Client(ADDRESS, PORT, $username, $passkey);

try {
	$remote->connect();
} catch (SocketException $e) {
	return error('Error connecting to server: ' . $e->getMessage());
}

if ($data) {
	$decodedData = @json_decode($data);
	if ($decodedData)
		$data = $decodedData;
}

$remote->sendRequest($id, $data);

$response = $remote->read();

if ($response === false) {
	return error('Connection timed out');
}

header("Content-type: application/json");
echo $response;

exit;