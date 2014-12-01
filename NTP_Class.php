<?PHP
/**
 *	NTP Class | PHP 5
 *
 *	Copyright (c) 2014
 *	Marco Delfini <info@marcodelfini.com>
 *	http://marcodelfini.com
 *
 *	This library is free software; you can redistribute it and/or
 *	modify it under the terms of the GNU Lesser General Public
 *	License as published by the Free Software Foundation; either
 *	version 2.1 of the License, or (at your option) any later version.
 *
 *	This library is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 *	Lesser General Public License for more details.
 *
 *	You should have received a copy of the GNU Lesser General Public
 *	License along with this library; if not, write to the Free Software
 *	Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 * 
 *  http://www.gnu.org/copyleft/lesser.html
 *
 *  
 *  EXAMPLE:
 *  
 *  $time = new NTP();
 *  $time->setTimeZone(1);
 *  
 *  if ($time->query()) {
 *  	echo "<div style=font-family:Arial;font-size:12pt>";
 *  	echo "Time Server Host: <span style=color:#999>" . $time->getHost() . "</span><br />";
 *  	echo "Time Server Port: <span style=color:#999>" . $time->getPort() . "</span><br />";
 *  	echo "Result in epoch seconds: <span style=color:red>" . $time->getResult() . "</span><br />";
 *  	echo "Today's date (CET): <span style=color:#999>" . date("M d Y, H:i:s", $time->getResult()) . "</span>";
 *  	echo "</div>";
 *  }
**/

class NTP {
	// timezone adjustment in seconds
	private $timezone = 0;

	// continent
	private $continent = 0;

	// time server response
	private $response = false;

	// time server result in seconds
	private $result = 0;
	
	// time server id host being queried
	private $selected_id;

	// time server port
	private $port = 37;
	
	// time servers
	private $host = array(
		// Europe
		0 => array(
			0 => array( // Italy - Torino
				"server_address"	=>	"ntp1.inrim.it",
				"server_port"		=>	37,
				"server_timezone"	=>	1
			),
			1 => array( // Italy - Torino
				"server_address"	=>	"ntp2.inrim.it",
				"server_port"		=>	37,
				"server_timezone"	=>	1
			)
		)
		// America (North)
		// America (South)
		// Africa
		// Asia
		// Antarctica
		// Australia
	);

	// constructor
	public function __construct() {
		$this->setTimeZone(0);
		$this->setContinent(0);
		return true;
	}
	
	// set timezone
	public function setTimeZone( $adjust = 0 ) {
		$this->timezone = $adjust;
	}
	
	// get timezone
	public function getTimeZone() {
		return $this->timezone;
	}
	
	// set continent
	public function setContinent( $continent = 0 ) {
		$this->continent = $continent;
	}
	
	// get continent
	public function getContinent() {
		return $this->continent;
	}

	// get port
	public function getPort() {
		return $this->port;
	}

	// get selected host
	public function getHost() {
		return $this->host[$this->continent][$this->selected_id]['server_address'];
	}

	// return number of available time servers
	public function getServerCount() {
		$count = sizeof($this->host[$this->continent]);
		return $count;
	}
	
	// query selected time server
	public function query() {
		$data = NULL;
		$this->response = false;
		for ($i = 0; $i < $this->getServerCount(); $i++) {
			$this->selected_id = $i;
			$this->port = $this->host[$this->continent][$this->selected_id]['server_port'];
			$fp = @fsockopen($this->getHost(), $this->port, $errno, $errstr, 30);
			if (!$fp) {} else {
				$data = NULL;
				while (!feof($fp)) $data .= fgets($fp, 128);
				fclose($fp);
				if (strlen($data) != 4) { // invalid response; try next host until list is depleted
				} else {
					$this->response = true;
					break;
				}
			}
		}
		return $this->processResponse( $data );
	}
	
	// process response
	private function processResponse( $data ) {
		if ($this->response && !is_null($data)) {
			// process time server response
			$ntp_time = (ord($data{0})*pow(256,3))+(ord($data{1})*pow(256,2))+(ord($data{2})*pow(256,1))+(ord($data{3})*pow(256,0));
			// convert seconds to the present seconds
			$time_filter = $ntp_time - 2840140800; // 2840140800 = Thu, 1 Jan 2060 00:00:00 UTC
			$time_now = $time_filter + 631152000; // 631152000  = Mon, 1 Jan 1990 00:00:00 UTC
			// port time to utc from time of the server
			$time_now = $time_now - ($this->host[$this->continent][$this->selected_id]['server_timezone'] * 3600);
			// add timezone in seconds
			$time_now = $time_now + ($this->timezone * 3600);
			// result in seconds
			$this->setResult( $time_now );
			return true;
		} else {
			return false; // no time servers available
		}
	}

	// set result
	private function setResult( $result ) {
		$this->result = $result;
	}

	// get result
	public function getResult() {
		return $this->result;
	}
	
	// destructor
	function __destruct() {
		// reserved for codes to run when this object is destructed
		if (isset($this->timezone)) unset($this->timezone);
		if (isset($this->response)) unset($this->response);
		if (isset($this->result)) unset($this->result);
		if (isset($this->continent)) unset($this->continent);
		if (isset($this->selected_id)) unset($this->selected_id);
		if (isset($this->port)) unset($this->port);
		if (isset($this->host)) unset($this->host);
	}
}

?>