<?php

// Configure these parameters to your needs
const ADDRESS = 'localhost';
const PORT = 27020;

// WARNING: Setting a default user and password can be VERY DANGEROUS!
// Be sure to only use this with a non-existing username who has only restricted privileges!
const DEFAULT_USER = null;
const DEFAULT_PASSWORD = null;

function getArg($name) {
	return isset($_REQUEST[$name]) ? $_REQUEST[$name] : null;
}

function error($message) {
	echo json_encode(array(
		'success' => false,
		'message' => $message
	));
	exit;
}

include "FERemote.php";

header("Content-type: application/json");

$id = getArg('id');
$rid = getArg('rid');
$data = getArg('data');
$username = getArg('username');
$password = getArg('password');

if (!$username)
	$username = DEFAULT_USER;

if (!$password)
	$password = DEFAULT_PASSWORD;

if (!$id)
	return error('Missing request ID');

if ($username && !$password)
	return error('Missing password');

$remote = new FERemote(ADDRESS, PORT, $username, $password);

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

echo $response;

exit;