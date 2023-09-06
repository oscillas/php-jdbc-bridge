<?php

class PJBridge {

	private $sock;
        private $jdbc_enc;
        private $app_enc;

        public $last_search_length = 0;

	function __construct($host="localhost", $port="4444", $jdbc_enc="ascii", $app_enc="ascii") {

		$this->sock = fsockopen($host, $port);
                $this->jdbc_enc = $jdbc_enc;
                $this->app_enc = $app_enc;
 	}

	function __destruct() {
	
		fclose($this->sock);
	}

	private function parse_reply() {

		$il = explode(' ', fgets($this->sock));
		$ol = array();

		foreach ($il as $value)
			$ol[] = iconv($this->jdbc_enc, $this->app_enc, base64_decode($value));

		return $ol;
	}

	private function exchange($cmd_a) {

		$cmd_s = '';

		foreach ($cmd_a as $tok)
			$cmd_s .= base64_encode(iconv($this->app_enc, $this->jdbc_enc, $tok)).' ';
		
		$cmd_s = substr($cmd_s, 0, -1)."\n";

		fwrite($this->sock, $cmd_s);
		
		return $this->parse_reply();
	}

	public function connect($url, $user, $pass) {

		$reply = $this->exchange(array('connect', $url, $user, $pass));

		switch ($reply[0]) {

		case 'ok':
			return true;

		default:
			return false;
		}
	}

	public function exec($query) {

		$cmd_a = array('exec', $query);

		if (func_num_args() > 1) {
		
			$args = func_get_args();

			for ($i = 1; $i < func_num_args(); $i ++)
				$cmd_a[] = $args[$i];
		}

		$reply = $this->exchange($cmd_a);

		switch ($reply[0]) {

		case 'ok':
			return $reply[1];

		default:
			return false;
		}
	}

	public function fetch_array($res) {

		$reply = $this->exchange(array('fetch_array', $res));

		switch ($reply[0]) {

		case 'ok':
			$row = array();

			for ($i = 0; $i < $reply[1]; $i ++) {

				$col = $this->parse_reply($this->sock);
				$row[$col[0]] = $col[1];
			}

			return $row;

		default:
			return false;
		}
	}

	public function free_result($res) {

		$reply = $this->exchange(array('free_result', $res));

		switch ($reply[0]) {

		case 'ok':
			return true;
		default:
			return false;
		}
	}
}

?>
