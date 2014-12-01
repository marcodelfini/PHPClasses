<?PHP
/**
 *	SMTPClient Class | PHP 5
 *
 *	Copyright (c) 2012
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
 *  $SMTPMail = new SMTPClient("smtp.localhost.it", 25, true, "localhost.it", $user, $pass, $da, $a, $oggetto, $body, 3, false);
 *  
 *  if($SMTPMail->SendMail()){ ... }else{ ... }
 *  
**/

class SMTPClient
{

	function SMTPClient($SmtpServer, $SmtpPort, $SmtpUseEHLO, $SmtpHeloOrDomain, $SmtpUser, $SmtpPass, $from, $to, $subject, $body, $priority, $isHTML){

		$this->SmtpServer = $SmtpServer;
		$this->useEHLO = $SmtpUseEHLO;
		$this->SmtpHelo = $SmtpHeloOrDomain;
		$this->SmtpUser = $SmtpUser;
		$this->SmtpPass = $SmtpPass;
		$this->from = $from;
		$this->to = $to;
		$this->priority = $priority; // 1 2 (urgent), 3 (normal), 4 5 (non-urgent)
		$this->isHTML = $isHTML;
		$this->subject = $subject;
		$this->body = $body;

		if ($SmtpPort == ""){
			$this->PortSMTP = 25;
		}else{
			$this->PortSMTP = $SmtpPort;
		}
	}


	function SendMail(){

		if($SMTPIN = fsockopen($this->SmtpServer, $this->PortSMTP)){

			$talk["begin"] = fgets($SMTPIN, 1024);
			
			if($this->useEHLO == true){
				fputs($SMTPIN, "EHLO ".$this->SmtpHelo."\r\n");
				while($i >= 0){
					$read = fgets($SMTPIN, 1024);
					$talk["helo"] .= $read;
					if(substr($read, 0, 4) == "250 "){
						$i = -1;
					}else{
						$i++;
					}
					unset($read);
				}
			}else{
				fputs($SMTPIN, "HELO ".$this->SmtpHelo."\r\n");
				$talk["helo"] = fgets($SMTPIN, 1024);
			}

			fputs($SMTPIN, "AUTH LOGIN\r\n");
			$talk["res"] = fgets($SMTPIN, 1024);
			fputs($SMTPIN, base64_encode($this->SmtpUser)."\r\n");
			$talk["user"] = fgets($SMTPIN, 1024);
			fputs($SMTPIN, base64_encode($this->SmtpPass)."\r\n");
			$talk["pass"] = fgets($SMTPIN, 256);

			fputs($SMTPIN, "MAIL FROM: <".$this->from.">\r\n");
			$talk["From"] = fgets($SMTPIN, 1024);
			fputs($SMTPIN, "RCPT TO: <".$this->to.">\r\n");
			$talk["To"] = fgets($SMTPIN, 1024);

			fputs($SMTPIN, "DATA\r\n");
			$talk["data"] = fgets($SMTPIN, 1024);
			
			if (ereg("(.*)<(.*)>", $this->from, $regs)) {
				// There is a name for the expeditor !
				$from = "=?UTF-8?B?".base64_encode($regs[1])."?= <".$regs[2].">";
			}else{
				// Nothing to do, the from is directly the email !
				$from = "<".$this->from.">";
			}
			
			$headers = "X-Mailer: Dowlee Srls Site\r\n".
			"X-Priority: ".$this->priority."\r\n". // 1 2 (urgent), 3 (normal), 4 5 (non-urgent)
			"MIME-Version: 1.0\r\n".
			"Content-type: text/".($this->isHTML == true ? "html" : "plain")."; charset=\"UTF-8\"\r\n".
			"Content-Transfer-Encoding: 8bit\r\n".
			"To: <".$this->to.">\r\n".
			"From: ".$from."\r\n".
			"Subject: =?UTF-8?B?".base64_encode($this->subject)."?="."\r\n\r\n\r\n";
			unset($from);
			
			fputs($SMTPIN, $headers.$this->body."\r\n.\r\n");
			$talk["send"] = fgets($SMTPIN, 256);

			// Close connection and exit
			fputs($SMTPIN, "QUIT\r\n");
			fclose($SMTPIN);
		}

		return $talk;
	}

}

?>