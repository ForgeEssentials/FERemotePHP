<?php

namespace ForgeEssentials\Remote;

class SocketException extends \Exception {
}

class Client {

	private $adress = 'localhost';

	private $port = 27020;

	private $timeout = 15;

	private $username;

	private $password;

	private $socket;

	private $connected;

	private $rid;

	private $rcvBuff;
	
	/**************************************************************/

    public function __construct($address, $port = null, $username = null, $password = null) {
		$this->address = gethostbyname($address);
		if ($port)
			$this->port = $port;
		$this->username = $username;
		$this->password = $password;
    }
	
	/**************************************************************/
	
	public function connect() {
		if ($this->connected)
			return true;

		// Create socket if not created yet
		if (!$this->socket) {
			//$domain = (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN' ? AF_INET : AF_UNIX);
			$this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
			if ($this->socket === false) {
				$this->lastError = 0;
				$this->lastErrorMessage = "Could not create socket";
				throw new SocketException($this->lastErrorMessage, $this->lastError);
			}
		}
		
		// Try to connect to server
		$result = @socket_connect($this->socket, $this->address, $this->port);
		if ($result === false) {
			$this->lastError = socket_last_error($this->socket);
			$this->lastErrorMessage = socket_strerror($this->lastError);
			throw new SocketException($this->lastErrorMessage, $this->lastError);
		}
		socket_set_block($this->socket);
		$this->connected = true;
		
		$this->setTimeout($this->timeout);
		return true;
	}
	
	public function disconnect() {
		$this->rid = 0;
		$this->connected = false;
		socket_close($this->socket);
		$this->socket = null;
	}
	
	public function isConnected() {
		return $this->connected && $this->socket !== null;
	}
	
	public function setTimeout($timeout) {
		$this->timeout = $timeout;
		if ($this->connected) {
			if ($timeout)
				socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => $timeout, 'usec' => 0));
			else
				socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => 0, 'usec' => 0));
		}
	}
	
	/**************************************************************/
	
	private function encode($id, $data = null) {
		$message = array(
			'id' => $id,
			'rid' => ++$this->rid,
		);
		if ($this->username) {
			$message['auth'] = array(
				'username' => $this->username,
				'password' => $this->password,
			);
		}
		if ($data) {
			$message['data'] = $data;
		}
		return json_encode($message) . "\n\n\n";
	}
	
	private function decode($message, $assoc = false) {
		return json_decode($message, $assoc);
	}
	
	/**************************************************************/
	
	private function send($message) {
		if (!$this->isConnected())
			throw new SocketException("Connection closed");
		socket_write($this->socket, $message, strlen($message));
		return true;
	}
	
	public function sendRequest($id, $data = null) {
		return $this->send($this->encode($id, $data));
	}
	
	/**************************************************************/
	
	private function filterMessage() {
		$n = strpos($this->rcvBuff, "\n\n\n");
		if ($n !== false) {
			$split = str_split($this->rcvBuff, $n);
			$this->rcvBuff = ltrim($split[1], "\n ");
			return $split[0];
		}
		return false;
	}
	
	public function read() {
		while (true) {
			$result = $this->filterMessage();
			if (false !== $result)
				return $result;
			
			$result = @socket_recv($this->socket, $rcv, 1024 * 8, 0);
			if ($result === false) {
				return false;
			} else if ($result === 0) {
				$this->disconnect();
				throw new SocketException(socket_strerror(SOCKET_ECONNRESET), SOCKET_ECONNRESET);
			}
			$this->rcvBuff .= $rcv;
			
			/*
			$rcv = @socket_read($this->socket, 1024 * 8);
			if ($rcv === false) {
				switch (socket_last_error($this->socket)) {
					case SOCKET_ENETRESET:
					case SOCKET_ECONNRESET:
					case SOCKET_ECONNABORTED:
						// TODO: Throw exception
						disconnect();
						return false;
					case SOCKET_ETIMEDOUT:
						return false;
					default:
						return false;
				}
			}
			$this->rcvBuff .= $rcv;
			*/
		}
	}
	
	public function waitForResponse($id, $assoc = false, $exceptionOnTimeout = true) {
		while (true) {
			$message = $this->read();
			if ($message === false)
				if ($exceptionOnTimeout)
					throw new SocketException(socket_strerror(SOCKET_ETIMEDOUT), SOCKET_ETIMEDOUT);
				else
					return false;
			$message = $this->decode($message, $assoc);
			$messageObj = (object) $message;
			if ($messageObj->id == $id)
				break;
		}
		return $message;
	}
	
	public function query($id, $data = null, $assoc = false, $exceptionOnTimeout = true) {
		$this->sendRequest($id, $data);
		return $this->waitForResponse($id, $assoc, $exceptionOnTimeout);
	}
	
	/**************************************************************/

}



