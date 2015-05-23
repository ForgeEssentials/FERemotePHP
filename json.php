<?php

const ADDRESS = 'localhost';

const PORT = 27020;

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

if (!$user)
	$user = DEFAULT_USER;

if (!$password)
	$user = DEFAULT_PASSWORD;

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

if ($data)
	$data = json_decode($data);

$remote->sendRequest($id, $data);

$response = $remote->read();

if ($response === false) {
	return error('Connection timed out');
}

echo $response;

exit;