<?PHP
/**
 *	Logs Class | PHP 5
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
 * Logging class:
 * - contains lfile, lwrite and lclose public methods
 * - Logs sets path and name of log file on inizialization of new instance
 * - lwrite writes message to the log file (and implicitly opens log file)
 * - lclose closes log file
 * - first call of lwrite method will open log file implicitly
 * - message is written with the following format: [d/M/Y H:i:s] (script name) message
 * 
**/

class Logs {
    // declare log file and file pointer as private properties
    private $log_file, $log_size, $nl, $fp;
    // set log file (path and name)
    public function Logs($path = "", $size = 1) {
        $this->log_file = $path;
        $this->log_size = $size * (1024*1024); // Megs to bytes
    }
    // write message to the log file
    public function lwrite($category = "SYSTEM", $message) {
        // if file pointer doesn't exist, then open log file
        if (!is_resource($this->fp)) {
            $this->lopen();
        }
		$category = strtoupper($category);
        // define script name
		$option = PATHINFO_DIRNAME + PATHINFO_BASENAME;
        $script_name = pathinfo($_SERVER['PHP_SELF'], $option);
        // define current time and suppress E_WARNING if using the system TZ settings
        // (don't forget to set the INI setting date.timezone)
        $date= @date("[d/M/Y H:i:s P T U]");
        // write current time, script name and message to the log file
        fwrite($this->fp, "$date ".$_SERVER['REMOTE_ADDR']." ".gethostbyaddr($_SERVER['REMOTE_ADDR'])." - [".$category."] (".$script_name.") ".$message.PHP_EOL);
    }
    // close log file (it's always a good idea to close a file when you're done with it)
    public function lclose() {
        fclose($this->fp);
    }
    // open log file (private method)
    private function lopen() {
        // in case of Windows set default log file
        $log_file_default = $_SERVER['DOCUMENT_ROOT']."/logs/Site.log";
        // define log file from lfile method or use previously set default
		$lfile = !empty($this->log_file) ? $this->log_file : $log_file_default;
		if (file_exists($lfile)) {
			
			if (filesize($lfile) > $this->log_size) {
				$this->fp = fopen($lfile, "w") or die("Not Open FO");
				fclose($this->fp);
				unlink($lfile);
			  }
		}
		// open log file for writing only and place file pointer at the end of the file
		// (if the file does not exist, try to create it)
		chmod($lfile, 0777);
		$this->fp = fopen($lfile, "a") or die("Not open FA");
    }
}

?>