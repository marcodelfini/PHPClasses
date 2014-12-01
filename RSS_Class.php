<?PHP
/**
 *	Rss Class | PHP 5
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
 
SQL:

DROP TABLE IF EXISTS `rss_channels`;
CREATE TABLE `rss_channels` (
  `channel_id` int(11) NOT NULL,
  `channel_short` varchar(10) NOT NULL,
  `title` longtext CHARACTER SET utf8 NOT NULL,
  `description` longtext CHARACTER SET utf8 NOT NULL,
  `link` longtext,
  `language` longtext CHARACTER SET utf8,
  `image_title` text CHARACTER SET utf8,
  `image_url` text CHARACTER SET utf8,
  `image_link` text CHARACTER SET utf8,
  `image_width` text CHARACTER SET utf8,
  `image_height` text CHARACTER SET utf8,
  `image_description` longtext,
  `disable` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`channel_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `rss_items`;
CREATE TABLE `rss_items` (
  `channel_id` int(11) NOT NULL,
  `pubDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `pubDateDiffGMT` varchar(6) NOT NULL DEFAULT '+00:00',
  `title` longtext CHARACTER SET utf8 NOT NULL,
  `description` longtext CHARACTER SET utf8 NOT NULL,
  `link` longtext CHARACTER SET utf8,
  `disable` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`channel_id`,`pubDate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


**/

class Rss
{	
	var $id_chn = 1;
	var $condb;
	var $channel_short;
	
	public function Rss($rss){
		$this->channel_short = $rss;
	}
	
	public function GetFeed()
	{
		return $this->getDetailsBegin().$this->getItems().$this->getDetailsEnd();
	}

	private function dbConnect()
	{
		global $dbinfo;
		$this->condb = mysql_connect($dbinfo['dow']['host'].":".$dbinfo['dow']['port'], $dbinfo['dow']['user'], $dbinfo['dow']['pass']);
		mysql_select_db($dbinfo['dow']['name'], $this->condb);
	}
	
	private function getChnID()
	{
		if(isset($this->channel_short)){
			$this->dbConnect();
			$query = "SELECT channel_id FROM `rss_channels` WHERE `channel_short` = '".$this->channel_short."' AND language = '".$_SESSION['dowlee_lang']."' AND disable = 0";
			$result = mysql_query($query, $this->condb);
			while($row = mysql_fetch_array($result))
			{
				$this->id_chn = $row['channel_id'];
			}
		}
	}

	private function getDetailsBegin()
	{
		global $system;
		$this->dbConnect();
		$this->getChnID();
		$query = "SELECT *, (SELECT CONVERT_TZ(pubDate, pubDateDiffGMT,'+00:00') FROM rss_items WHERE channel_id = ".$this->id_chn." AND disable = 0 ORDER BY pubDate DESC LIMIT 0, 1) AS pubDate FROM rss_channels WHERE channel_id = ".$this->id_chn;
		$result = mysql_query($query, $this->condb);
		$details = "";
		while($row = mysql_fetch_array($result))
		{
			$details .= "<?xml version=\"1.0\" encoding=\"utf-8\"?>
<rss version=\"2.0\">
<channel>
<title>".$row['title']."</title>
<link>".$system['address']['WEB_SITE']."/".$_SESSION['dowlee_lang']."/rss/".$row['channel_short']."/</link>
<description>".$row['description']."</description>
<language>".$row['language']."</language>
<copyright>".$system['rss']['COPYRIGHT']."</copyright>
<webMaster>".$system['rss']['WEBMASTER']."</webMaster>
<pubDate>".date("D, d M Y H:i:s", strtotime($row['pubDate']))." GMT</pubDate>
<lastBuildDate>".date("D, d M Y H:i:s", strtotime($row['pubDate']))." GMT</lastBuildDate>
<category>".$system['rss']['CATEGORY']."</category>
<generator>".$system['rss']['CATEGORY']."</generator>
<docs>".$system['rss']['DOCS']."</docs>".(!isset($row['image_url']) ? "\n\n" : "\n");

if(isset($row['image_url'])){
$details .= "<image>
	<title>".$row['image_title']."</title>
	<url>".$row['image_url']."</url>
	<link>".$row['image_link']."</link>
	<width>".$row['image_width']."</width>
	<height>".$row['image_height']."</height>
	<description>".$row['image_description']."</description>
</image>\n\n";
}
		}
		return $details;
	}

	private function getItems()
	{
		$this->dbConnect();
		$this->getChnID();
		$query = "SELECT *, CONVERT_TZ(pubDate, pubDateDiffGMT,'+00:00') AS pubDateCONV FROM rss_items WHERE channel_id = ".$this->id_chn." AND disable = 0 ORDER BY pubDate DESC";
		$result = mysql_query($query, $this->condb);
		$items = "";
		while($row = mysql_fetch_array($result))
		{
$items .= "<item>
	<title><![CDATA[".$row['title']."]]></title>
	<link>".$row['link']."</link>
	<description><![CDATA[".str_replace("\r\n", "<br>", $row['description'])."]]></description>
	<pubDate>".date("D, d M Y H:i:s", strtotime($row['pubDateCONV']))." GMT</pubDate>
</item>\n";
		// gmdate(DATE_RSS, strtotime($row['pubDate']))   spunto per il futuro
		}
		return $items;
	}

	private function getDetailsEnd()
	{
		return "\n</channel>\n</rss>";
	}
}

?>