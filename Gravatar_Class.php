<?PHP
/**
 *	Gravatar Class | PHP 5
 *
 *	Copyright (c) 2013
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
 *  INFO: http://gravatar.com/site/implement/images/php/
 *  
 *  EXAMPLE:
 *   
 *  $gravatar = new Gravatar("info@localhost.com");
 *  $gravatar->size = 80;
 *  $gravatar->rating = "G";
 *  $gravatar->border = "FF0000";
 *  $gravatar->setProfile();
 *  var_dump($gravatar->profile);
 *  echo $gravatar; // Or echo $gravatar->toHTML();
 *  
**/

class Gravatar
{
	// Gravatar's url
	const GRAVATAR_URL = "http://www.gravatar.com";

	/* Ratings disponibili [ g | pg | r | x ]
		G	= Suitable for display on all websites with any audience type.
		PG	= May contain rude gestures, provocatively dressed individuals, the lesser swear words, or mild violence.
		R	= May contain such things as harsh profanity, intense violence, nudity, or hard drug use.
		X	= May contain hardcore sexual imagery or extremely disturbing violence.*/
	private $GRAVATAR_RATING = array("G", "PG", "R", "X");
	
	private $GRAVATAR_DEFAULT = array("default", "404", "mm", "identicon", "monsterid", "wavatar");
	
	// Query string. key/value
	protected $properties = array(
		"gravatar_id"	=> NULL,
		"default"		=> "mm",
		"size"			=> 80,		// 80px [ 1 - 2048 ]		
		"rating"		=> "G",
		"border"		=> NULL,
		"profile"		=> false,
		"email"			=> "",		// E-mail, this will convert into md5($email)
		"extra"			=> ""		// Other attributes for the tag IMG, type: ALT, CLASS, STYLE...
	);

	public function __construct($email = NULL, $default = NULL){
		$this->setEmail($email);
		$this->setDefault($default);
	}

	public function setEmail($email){
		if($this->isValidEmail($email)){
		    $this->properties['email'] = $email;
		    $this->properties['gravatar_id'] = md5(strtolower($this->properties['email']));
		    return true;
		}
		return false;
	}

	public function setDefault($default){
		if(in_array($default, $this->GRAVATAR_DEFAULT)){
			$this->properties['default'] = $default;
			return true;
		}
		return false;
	}

	public function setRating($rating){
		if(in_array($rating, $this->GRAVATAR_RATING)){
		    $this->properties['rating'] = $rating;
		    return true;
		}
		return false;
	}

	public function setSize($size){
		$size = (int)$size;
		if($size > 0)
			$this->properties['size'] = $size;
	}

	public function setExtra($extra){
		$this->properties['extra'] = $extra;
	}

	public function isValidEmail($email){
		return preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/i", $email);
	}

	public function __get($var) { return @$this->properties[$var]; }

	// Object property overloading
	public function __set($var, $value){
		switch($var){
		    case "email":		return $this->setEmail($value);
		    case "rating":		return $this->setRating($value);
		    case "default":		return $this->setDefault($value);
		    case "size":		return $this->setSize($value);
		    case "extra":		return $this->setExtra($value);
		    case "profile":		return $this->setProfile();
		    // Not setted
		    case "gravatar_id":	return;
			case "email":		return;
		}
		return @$this->properties[$var] = $value;
	}

	// Object property overloading
	public function __isset($var){ return isset($this->properties[$var]); }

	// Object property overloading
	public function __unset($var){ return @$this->properties[$var] == NULL; }

	// Get source
	public function getSrc(){
		$url = self::GRAVATAR_URL."/avatar/?";
		$first = true;
		foreach($this->properties as $key => $value){
		    if(isset($value) && $key != "profile" && $key != "email" && $key != "extra"){
		        if(!$first){
		            $url .= "&";
				}
		        $url .= $key."=".urlencode($value);
		        $first = false;
		    }
		}
		return $url;    
	}

	// toHTML
	public function toHTML(){
		return "<img src='".$this->getSrc()."'".(!isset($this->properties['size']) ? "" : " width='".$this->properties['size']."' height='".$this->properties['size']."' ").$this->properties['extra']."/>";  
	}

	// toString
	public function __toString(){ return $this->toHTML(); }
	
	// Retrive the user profile from gravatar.com
	public function setProfile(){		
		$str = @file_get_contents(self::GRAVATAR_URL."/".md5($this->properties['email']).".php");
		$profile = unserialize($str);
		if(is_array($profile) && isset($profile['entry'])){
			$this->properties['profile'] = $profile['entry'][0];
		}
	}
}

?> 