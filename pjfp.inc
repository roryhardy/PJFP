<?php
/**
 * @package Picasa JSON Feed Parser
 * @category Libraries
 * @author Rory Cronin-Hardy (GneatGeek)
 * @link people.oregonstate.edu/~croninhr/
 * @version 1.0
 * @todo Finish Caching System
 * @todo Add RSS parser to compliment JSON parser
 * Description : Google Picasa Parser.
 * Please note that when an exception is thrown it will likely be in the gallery system... you may have to view the page source to see it
 */

require_once('config.inc');


class Gallery{
	private $data, $albumId, $useCurl, $enableCaptions, $useHTTPS;

	/* Constructor Method
	 * Takes:  Picasa RSS album id $albumId
	 *         BOOL to determine whether or not to use HTTPS $useHTTPS.  Default is FALSE
	 *         BOOL to show captions or not $enableCaptions.  Default is TRUE
	 *         BOOL to determine whether to use curl or sockets $useCurl.  Default is TRUE
	 * Throws: An exception if $enableCaptions is not boolean.
	 */
	public function __construct($albumId, $useHTTPS=FALSE, $enableCaptions=TRUE, $useCurl=TRUE){
		if(!is_bool($useHTTPS))
			throw new Exception("VAR useHTTPS must be a boolean (TRUE/FALSE)!");
		if(!is_bool($enableCaptions))
			throw new Exception("VAR enableCaptions must be a boolean (TRUE/FALSE)!");
		if(!is_bool($useCurl))
			throw new Exception("VAR useCurl must be a boolean (TRUE/FALSE)!");
		$this->albumId        = $albumId;
		$this->enableCaptions = $enableCaptions;
		$this->useCurl        = $useCurl;
		$this->useHTTPS       = $useHTTPS;
		$this->cacheMe();
	}

	/* To String Method
	 * Enables user to print directly from class declaration
	 * Usage: print(new Gallery(PARAMS);
	 */
	public function __toString(){
		return $this->data;
	}

	/* Curl Method
	 * Returns: Requested Data From Picasa
	 * Throws:  Exception if curl fails.
	 */
	private function curl(){
		$url = sprintf("http://picasaweb.google.com/data/feed/base/user/%s/albumid/%s%s",
			USER,
			$this->albumId,
			"?alt=json&fields=entry(media:group)&imgmax=577" // Down here to shorten Completed URL visually
		);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,            $url);
		curl_setopt($ch, CURLOPT_FAILONERROR,    TRUE);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_TIMEOUT,        10);
		if(!$ret = curl_exec($ch))
			throw new Exception("An error occured in method curl()! - " . $this->cURL . curl_error($ch));
		return($ret);
	}
	
	/* Socket Method
	 * Returns: Requested Data From Picasa
	 * Throws an exception if the socket fails
	 * This is simply an alternative to curl since some systems may not have it available.
	 * Both methods ultimately do the same thing.
	 */
	private function socket(){
		$ret = NULL;
		$url = "picasaweb.google.com";
		$fp = fsockopen($url, 80, $errno, $errstr, 10);
		if(!$fp)
			throw new Exception("An error occured in method socket()! - $errstr ($errno)");
		else {
			$out = sprintf("GET /data/feed/base/user/%s/albumid/%s?imgmax=%d%s HTTP/1.1\r\n",
				USER,
				$this->albumId,
				MAX_WIDTH,
				"&alt=json&fields=entry(media:group)" // Down here to shorten Completed URL visually
			);
			$out .= "Host: $url\r\n";
			$out .= "Connection: Close\r\n\r\n";
			fwrite($fp, $out);
			$headers = TRUE;
			while (!feof($fp)){
				if(!$headers)
					$ret .= fgets($fp, 128);
				elseif(fgets($fp, 128) == "\r\n")
					$headers = FALSE;
			}
			fclose($fp);
			return($ret);
		}
	}

	/* JSON Method
	 * Takes: BOOL $useCurl  to determine whether to use curl or sockets
	 *        BOOL $caption  to determine whether or not to display captions
	 *        BOOL $useHTTPS to determine whether or not the image urls should be HTTPS instead of HTTP
	 * Sets:  $this->data to decoded JSON as an associated array (NOT AN OBJECT)
	 */
	private function JSON(){
		$json = ($this->useCurl ? $this->curl() : $this->socket());
		if(!$arr = json_decode($json, TRUE))
			throw new Exception("Could not Decode supplied JSON in method JSON()!");
		foreach($arr['feed']['entry'] as $v){
			$url = $v['media$group']['media$content'][0]['url'];
			if($this->useHTTPS){
				$url = str_replace("http", "https", $url);
			}
			$this->imgResize($v['media$group']['media$content'][0]['width'], $v['media$group']['media$content'][0]['height']);
			$this->data .= sprintf("<div><img src=\"%s\" width=\"%s\" height=\"%s\" alt=\"%s\">%s</div>\n",
				$url,
				$v['media$group']['media$content'][0]['width'],
				$v['media$group']['media$content'][0]['height'],
				ALT_MSG,
				($this->enableCaptions ? "<br><p>" . $v['media$group']['media$description']['$t'] . "</p>" : "")
			);
		}
	}

	// UNDER DEVELOPMENT
	private function cacheMe(){
			$file = CACHE_DIR . $this->albumId . ".inc";
			if(CACHE){
				if(file_exists($file)){
					if(!$this->data = file_get_contents($file))
						throw new Exception("Could not get cache file contents from $file in method cacheMe()!");
				}else{
					$this->JSON();
					if(!file_exists(CACHE_DIR)){
						throw new Exception("File " . CACHE_DIR . "does not exist in method cacheMe()!");
					}
				}
			}else
				$this->JSON();
	}

	/* Image Resize Method
	 * Takes:  Variable reference to width and height
	 * Adjusts the vertical image's width x height parameters so it fits the bounds of the gallery dynamically.
	 * If the image is horizontal (width > height) then Picasa will spit it out at the correct size already
	 *   so there is no reason to resize those images.
	 */
	private function imgResize(&$width, &$height){
		if($height > MAX_HEIGHT){
			if($height == $width){
				$width  = MAX_HEIGHT;
				$height = MAX_HEIGHT;	
			}else{
				$width  = round($width * (MAX_HEIGHT / $height));
				$height = MAX_HEIGHT;	
			}
		}
	}
}
?>