<?PHP
/*
DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions` (
  `id` varchar(200) NOT NULL,
  `host` varchar(50) NOT NULL,
  `time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `expiration` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `last_ip` varchar(15) NOT NULL,
  `hash` longtext NOT NULL,
  `data` longtext NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

$sessions = new Session($server, $port, $user, $pass, $db, $charsetdb, $sub_domain, $second_life, $salt);
*/
class Session
{
	var $SecondOfLife = 2592000;	// In secondi [30 giorni (60 * 60 * 24 * 30)]
	var $resorsa;
	var $BrowserHash;
	var $SubDomain;
	var $Salt;
	
	function Session($server, $port, $user, $pass, $db, $charsetdb = "utf8", $sub_domain = NULL, $second_life = NULL, $salt = NULL){
		if($second_life != NULL && is_integer($second_life)){
			$this->SecondOfLife = $second_life;
		}
		if($salt == NULL){
			$this->Salt = "";
		}else{
			$this->Salt = md5($salt);
		}
		if($sub_domain == NULL){
			$this->SubDomain = "";
		}else{
			$this->SubDomain = $sub_domain;
		}
		$this->HashCalculation();
		
		$res = mysql_connect($server.":".$port, $user, $pass) or die(mysql_error());
		mysql_select_db($db, $res) or die(mysql_error());
		$this->resource = $res;
		
		mysql_set_charset($charsetdb, $this->resource) or die(mysql_error());
		mysql_query("SET NAMES ".$charsetdb, $this->resource) or die(mysql_error());
		mysql_query("SET CHARACTER SET ".$charsetdb, $this->resource) or die(mysql_error());
		
		//@ini_set("session.use_cookies", 1);	//attivo i cookies di sessione
		//@ini_set("session.use_only_cookies", 1);	//informa di utilizzare solo gli ID dei cookies in modo tale da rifiutare quelli che vengono propagati da url
		@ini_set("session.save_handler", "user");	//dice al php che dovrà usare i gestori definiti da me
		@ini_set("session.gc_divisor", 100);	// The probability is calculated by using gc_probability/gc_divisor, e.g. 1/100 means there is a 1% chance that the GC process starts on each request. session.gc_divisor defaults to 100
		@ini_set("session.gc_probability", 50);	// setta la probabilità che venga eseguito il garbage collector(spazzino) a 30%
		@ini_set("session.gc_maxlifetime", $this->SecondOfLife);	//le sessioni scederanno dopo 60 secondi dalla creazione

		register_shutdown_function("session_write_close");
		session_set_cookie_params($this->SecondOfLife, "/", $this->SubDomain);
		session_set_save_handler(array(&$this,"Open"), array(&$this,"Close"), array(&$this,"Read"), array(&$this,"Write"), array(&$this,"Destroy"), array(&$this,"GC"));
		session_start();
	}
	
	function Open($save_path, $session_name){
		global $res;
		$res = $this->resource;
		return true;
	}
	
	function Close(){
		global $res;
		mysql_close($this->resource);
		unset($res);
		return true;
	}
	
	function Read($session_id){
		global $res;
		$sql = "SELECT * FROM sessions WHERE id = '".$this->valid_session($session_id)."'";
		if($result = mysql_query($sql, $res) or die (mysql_error())){
			if(mysql_num_rows($result) == 1){
				$sess_read = mysql_fetch_assoc($result);
				if(strtotime($sess_read['expiration']) >= time() && strcasecmp($this->BrowserHash, $sess_read['hash']) == 0){
					return $sess_read['data'];
				}else{
					return "";
				}
			}else{
				return "";
			}
		}else{
			return "";
		}
	}
	
	function Write($session_id, $session_data){
		global $res;
		if(!$session_data){
			return false;
		}else{
			if(!is_numeric($session_data)){
				$session_data = mysql_real_escape_string($session_data, $res);
			}
			$host = ($this->SubDomain != "" ? $this->SubDomain : $_SERVER['HTTP_HOST']);
			$sql = "INSERT INTO sessions (id, host, time, expiration, last_ip, hash, data) VALUES ('".$this->valid_session($session_id)."', '".$host."', NOW(), (NOW() + INTERVAL ".$this->SecondOfLife." SECOND), '".$_SERVER['REMOTE_ADDR']."', '".$this->BrowserHash."', '".$session_data."') ON DUPLICATE KEY UPDATE host = '".$host."', data = '".$session_data."', time = NOW(), expiration = (NOW() + INTERVAL ".$this->SecondOfLife." SECOND), hash = '".$this->BrowserHash."'";
			if(mysql_query($sql, $res) or die (mysql_error())){
				return true;
			}else{
				return false;
			}
		}
	}
	
	function Destroy($session_id){
		global $res;
		$sql = "DELETE FROM sessions WHERE id = '".$this->valid_session($session_id)."'";
		if(mysql_query($sql, $res) or die (mysql_error())){
			$this->GC();
			return true;
		}else{
			return false;
		}
	}
	
	function GC(){
		global $res;
		$sql = "DELETE FROM sessions WHERE expiration < '".date("Y-m-d H:i:s", time())."'";
		if(mysql_query($sql, $res) or die (mysql_error())){
			return true;
		}else{
			return false;
		}
	}
	
	function Stop(){
		global $res;
		$this->RegenerateID();
		session_unset();
		session_destroy();
	}
	
	function RegenerateID(){
		global $res;
		$oldSessionID = session_id();
		session_regenerate_id();
		$this->Destroy($oldSessionID);
	}
	
	private function valid_session($id){
		if(preg_match("/^[0-9a-z]+$/", $id)){
			return $id;
		}else{
			return "";
		}
	}

	private function HashCalculation(){
		$ip = (isset($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : "Unknown");
		$ip .= (isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : "Unknown");
		$ip .= (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : "Unknown");
		$agent = (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : "NoUserAgent");
		$browser_data = $this->Salt.$ip.$agent;
		$this->BrowserHash = md5($browser_data);
	} 
}
?>