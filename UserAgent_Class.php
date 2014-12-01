<?PHP
/**
 *	UserAgent_Parser Class | PHP 5
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
 *  $UserAgent = new UserAgent_Parser();
 *  
 *  $UserAgent->is_idevice();
 *  
**/

class UserAgent_Parser{
	const VERSION_FORMAT = '(?:[0-9A-Za-z]+)(?:[\.\-\_][0-9A-Za-z]+)*\+?';
	// add more windows version here
	private static $WINDOWS_VERSIONS = array(
		'ce' => 'CE',
		'95' => '95',
		'98' => '98',
		'4.0' => 'NT 4.0',
		'5.0' => '2000',
		'5.1' => 'XP',
		'5.2' => '2003',
		'6.0' => 'VISTA',
		'6.1' => '7',
		'6.2' => '8',
	);
	// add more samsung galaxy versio here
	private static $SAMSUNG_GALAXY_VERSIONS = array(
		'i8150' => 'W',
		'i8160' => 'Ace 2',
		'i8190n' => 'S3 Mini',
		'i8530' => 'Beam',
		'i8730' => 'Express',
		'i9001' => 'S+',
		'i9003' => 'S',
		'i9070' => 'S Advance',
		'i9100' => 'S2',
		'i9103' => 'R',
		'i9105p' => 'S2+',
		'i9150' => 'Mega 5.8',
		'i9190' => 'S4 Mini',
		'i9192' => 'S4 Mini Duo',
		'i9195' => 'S4 Mini LTE',
		'i9200' => 'Mega 6.3',
		'i9210' => 'S2 LTE',
		'i9250' => 'Nexus',
		'i9295' => 'S4 Active',
		'i9300' => 'S3',
		'i9305' => 'S3 LTE',
		'i9500' => 'S4',
		'i9505' => 'S4 LTE',
		'n5120' => 'Note 8.0 LTE',
		'n7000' => 'Note',
		'n7100' => 'Note 2',
		'n7105' => 'Note 2 LTE',
		'n8000' => 'Note 10.1',
		'p3110' => 'Tab 2 7.0',
		'p5100' => 'Tab 2 10.1',
		'p6200' => 'Tab 7.0+',
		'p6800' => 'Tab 7.7',
		'p7320' => 'Tab 8.9 LTE',
		'p7500' => 'Tab 10.1',
		's5301' => 'Pocket',
		's5360' => 'Y',
		's5380d' => 'Wave Y',
		's5660' => 'Gio',
		's5830' => 'Ace',
		's6102' => 'Y Duos',
		's6500d' => 'Mini 2',
		's6802' => 'Ace Duos',
		's7500' => 'Ace+',
		's7562' => 'S Duos',
		'nexus' => 'Nexus',
	);
	// add more mobile os here
	private static $MOBILE_OS_NAMES = array(
		'windows phone os(?: (%s))?' => 'Windows Phone OS',
		'blackberry(?: (%s))?' => 'BlackBerry',
		'meego(?: (%s))?' => 'Nokia MeeGo',
		'ipod.*?cpu.*?os(?: (%s))?' => 'iPod',
		'iphone.*?cpu.*?os(?: (%s))?' => 'iPhone',
		'ipad.*?cpu.*?os(?: (%s))?' => 'iPad',
		'sonyericsson(%s)?' => 'Sony Xperia',
		'htc sensation(?: (%s))?' => 'HTC Sensation',
		'(?:(?:galaxy)|(?:gt))(?:[ \-](%s))?' => 'Samsung Galaxy',
		'nexus one(?: (%s))?' => 'Nexus One',
		'mi (%s)?' => 'MI',
		'android(?: (%s))?' => 'Android',
	);
	// add more os here
	private static $OS_NAMES = array(
		'(?:(?:winnt)|(?:windows(?: nt)?))(?: (%s))?' => 'Windows',
		'mac os x(?: (%s))?' => 'Mac OS X',
		'mac(?:(?:intosh)|(?:_powerpc))(?: (%s))?' => 'Macintosh',
		'linux mint(?:\/(%s))?' => 'Linux Mint',
		'ubuntu(?:\/(%s))?' => 'Ubuntu',
		'debian(?:\/(%s))?' => 'Debian',
		'(?:mandriva(?: linux)?)(?: (%s))?' => 'Mandriva',
		'fedora(?: (%s))?' => 'Fedora',
		'oracle(?: (%s))?' => 'Oracle',
		'gentoo(?: (%s))?' => 'Gentoo',
		'freebsd(?: (%s))?' => 'FreeBSD',
		'openbsd(?: (%s))?' => 'OpenBSD',
		's(?:(?:olaris)|(?:unos))(?: (%s))?' => 'Solaris',
		'(?:(?:linux)|(?:x11))(?: (%s))?' => 'GNU/Linux',
	);
	// add more mobile browser here
	private static $MOBILE_BROWSER_NAMES = array(
		'miuibrowser(?:\/(%s))?' => 'MIUI Browser',
		'opera.*mini.*version(?:\/(%s))?' => 'Opera Mini',
		'opera.*mobi.*version(?:\/(%s))?' => 'Opera Mobile',
		'dolphin(?:\/(%s))?' => 'Dolphin Browser',
		'chrome(?:\/(%s))? mobile' => 'Mobile Chrome',
		'nokiabrowser(?:\/(%s))?' => 'Nokia Browser',
		'iemobile(?:\/(%s))?' => 'Internet Explorer Mobile',
		'mobile.*firefox(?:\/(%s))?' => 'Mobile Firefox',
		'fennec(?:\/(%s))?' => 'Fennec',
		'android.*version(?:\/(%s))?' => 'Android Browser',
		'crios(?:\/(%s))?' => 'CriOS',
		'(?:ip.*cpu.*os.*version(?:\/(%s))?)' => 'Mobile Safari',
		'(?:ip.*cpu.*os.*mobile(?:\/(%s))?)' => 'Mobile Safari',
		'dalvik(?:\/(%s))?' => 'Dalvik',
	);
	// add more browser here
	private static $BROWSER_NAMES = array(
		'opera.*version(?:\/(%s))?' => 'Opera',
		'maxthon(?:\ (%s))?' => 'Maxthon',
		'msie(?:\ (%s))?' => 'Internet Explorer',
		'iceweasel(?:\/(%s))?' => 'Iceweasel',
		'(?:n(?:etscape)|(?:avigator))(?:\/(%s))?' => 'Netscape Navigator',
		'thunderbird(?:\/(%s))?' => 'Thunderbird',
		'thunderbrowse(?:\/(%s))?' => 'ThunderBrowse',
		'firefox(?:\/(%s))?' => 'Firefox',
		'instapaper(?:\/(%s))?' => 'Instapaper',
		'phantomjs(?:\/(%s))?' => 'PhantomJS',
		'google earth(?:\/(%s))?' => 'Google Earth',
		'midori(?:\/(%s))?' => 'Midori',
		'iron(?:\/(%s))?' => 'Iron',
		'chromium(?:\/(%s))?' => 'Chromium',
		'chrome(?:\/(%s))?' => 'Chrome',
		'epiphany(?:\/(%s))?' => 'Epiphany',
		'konqueror(?:\/(%s))?' => 'Konqueror',
		'version(?:\/(%s))?\ safari' => 'Safari',
		'netsurf(?:\/(%s))?' => 'NetSurf',
		'wget(?:\/(%s))?' => 'Wget',
		'dillo(?:\/(%s))?' => 'Dillo',
	);
	// add more robot here
	private static $ROBOT_NAMES = array(
		'baiduspider(?:\/(%s))?' => 'Baidu Spider',
		'bingbot(?:\/(%s))?' => 'Bing Bot',
		'googlebot(?:\/(%s))?' => 'Google Bot',
		'ia_archiver(?:\/(%s))?' => 'Internet Archive',
		'magpierss(?:\/(%s))?' => 'Magpie RSS',
		'feedbucket(?:\/(%s))?' => 'Feed ucket',
		'docomo(?:\/(%s))?' => 'DoCoMo',
		'lwp\-trivial(?:\/(%s))?' => 'Lwp-Trivial',
		'w3c_validator(?:\/(%s))?' => 'W3C Validator',
		'yandexsomething(?:\/(%s))?' => 'Yandex Something',
		'libwww\-perl(?:\/(%s))?' => 'libwww-Perl',
	);
	// add more layout engine here
	private static $LAYOUT_NAMES = array(
		'trident(?:\/(%s))?' => 'Trident',
		'(?:apple)?webkit(?:\/(%s))?' => 'WebKit',
		'khtml(?:\/(%s))?' => 'KHTML',
		'gecko(?:\/(%s))?' => 'Gecko',
		'presto(?:\/(%s))?' => 'Presto',
	);
	private $user_agent = '';
	private $colored_user_agent = '';
	private $default_language = '';
	private $is_mobile = false;
	private $is_robot = false;
	private $is_idevice = false;
	private $is_mac = false;
	private $os_name = 'Unknown OS';
	private $os_version = '';
	private $browser_name = 'Unknown Browser';
	private $browser_version = '';
	private $layout_name = 'Unknown Layout Engine';
	private $layout_version = '';
	/*
	the parent must under child in order
	*/
	public static final function MOBILE_OS_NAMES(){
		return self::$MOBILE_OS_NAMES;
	}
	public static final function OS_NAMES(){
		return self::$OS_NAMES;
	}
	public static final function MOBILE_BROWSER_NAMES(){
		return self::$MOBILE_BROWSER_NAMES;
	}
	public static final function BROWSER_NAMES(){
		return self::$BROWSER_NAMES;
	}
	public static final function ROBOT_NAMES(){
		return self::$ROBOT_NAMES;
	}
	public static final function LAYOUT_NAMES(){
		return self::$LAYOUT_NAMES;
	}
	public function get_user_agent(){
		return $this->user_agent;
	}
	public function get_colored_user_agent(){
		return $this->colored_user_agent;
	}
	public function get_default_language(){
		return $this->default_language;
	}
	public function is_robot(){
		return $this->is_robot;
	}
	public function is_mobile(){
		return $this->is_mobile;
	}
	public function is_idevice(){
		return $this->is_idevice;
	}
	public function is_mac(){
		return $this->is_mac;
	}
	public function get_os_name(){
		return $this->os_name;
	}
	public function get_os_version(){
		return $this->os_version;
	}
	public function get_browser_name(){
		return $this->browser_name;
	}
	public function get_browser_version(){
		return $this->browser_version;
	}
	public function get_layout_name(){
		return $this->layout_name;
	}
	public function get_layout_version(){
		return $this->layout_version;
	}
	private function set_user_agent($user_agent){
		if (strlen($user_agent) === 0){
			$user_agent = $_SERVER['HTTP_USER_AGENT'];
		}
		$this->colored_user_agent = $this->user_agent = $user_agent;
	}
	private function set_default_language(){
		$matches = array();
		if (preg_match('/^(..(?:\-..))[\,\;]?/i', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $matches) > 0){
			$this->default_language = preg_replace('/[\-]/', '_', strtolower($matches[1]));
		}
	}
	private function set_name_version($array, &$name, &$version, $color, $check_is_robot = false, $check_is_mobile = false){
		$matches = array();
		foreach ($array as $k => $v){
			$regex = sprintf('/(?:%s)/i', sprintf($k, self::VERSION_FORMAT));
			if (preg_match($regex, $this->user_agent, $matches) > 0){
				$name = $v;
				$this->colored_user_agent = str_replace($matches[0], sprintf('<span style="color:%s;">%s</span>', $color, $matches[0]), $this->colored_user_agent);
				if (count($matches) > 1){
					$version = preg_replace('/[_\-]/i', '.', $matches[1]);
					//$version = strtolower($version);
					switch ($v){
						case 'Windows': {
							$version = self::$WINDOWS_VERSIONS[strtolower($version)];
						} break;
						case 'Samsung Galaxy': {
							$version = self::$SAMSUNG_GALAXY_VERSIONS[strtolower($version)];
						} break;
					}
				}
				$this->is_robot = $check_is_robot;
				$this->is_mobile = $check_is_mobile;
				if($this->is_mobile == true && in_array($this->os_name, array("iPod", "iPad", "iPhone"))){
					$this->is_idevice = true;
				}else{
					$this->is_idevice = false;
				}
				if($this->is_mobile == false && in_array($this->os_name, array("Mac OS X", "Macintosh"))){
					$this->is_mac = true;
				}else{
					$this->is_mac = false;
				}
				break;
			}
		}
	}
	public function __construct($user_agent = ''){
		if((is_null($user_agent) || $user_agent == '') && isset($_SERVER['HTTP_USER_AGENT'])){
			$user_agent = $_SERVER['HTTP_USER_AGENT'];
		}
		$this->set_default_language();
		$this->set_user_agent(''.$user_agent);
		$this->set_name_version(self::ROBOT_NAMES(), $this->browser_name, $this->browser_version, 'blue', true, $this->is_mobile());
		if (!$this->is_robot()){
			$this->set_name_version(self::MOBILE_BROWSER_NAMES(), $this->browser_name, $this->browser_version, 'blue', $this->is_robot(), true);
			if (!$this->is_mobile()){
				$this->set_name_version(self::BROWSER_NAMES(), $this->browser_name, $this->browser_version, 'blue', $this->is_robot(), $this->is_mobile());
			}
		}
		$this->set_name_version(self::LAYOUT_NAMES(), $this->layout_name, $this->layout_version, 'green', $this->is_robot(), $this->is_mobile());
		if (!$this->is_robot()){
			if (!$this->is_mobile()){
				$this->set_name_version(self::OS_NAMES(), $this->os_name, $this->os_version, 'red', $this->is_robot(), $this->is_mobile());
			} else {
				$this->set_name_version(self::MOBILE_OS_NAMES(), $this->os_name, $this->os_version, 'red', $this->is_robot(), $this->is_mobile());
			}
		}
	}
}

?>