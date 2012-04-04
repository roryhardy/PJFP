<?php
if ( ! defined('EXT')){
	exit('No Direct Script Access!');
}
/**
 * @package Picasa JSON Feed Parser
 * @category Libraries
 * @author Rory Cronin-Hardy (GneatGeek)
 * @link oregonstate.edu/~croninhr/
 * @version 1.2
 * @todo Add a caching mechanism
 * Description : Google Picasa Parser.
 * Please note that when an exception is thrown it will likely be in the gallery system... you may have to view the page source to see it
 */

// ------------------------------------------------------------------------


/**
 * Picasa JSON Feed Parser (PJFP) Class
 * This class ... FILL IN
 */
class PJPF {
	/**
	 * The name and path of the config file to use. This shouldn't need to be changed.
	 * If it does, do it here!
	 * @var string
	 */
	private $config_file = "PJFP_config.php";
	/**
	 * Array of all loaded config values. (See config.inc)
	 * @var array
	 */
	private $config = array();
	
	/**
	 * Array of all parsed data.
	 * @var array
	 */
	private $data = array();
	
	/**
	 * Album ID of album to parse.
	 * @var int
	 */
	private $album_id;

	/**
	 * Constructor
	 * @access public
	 * @param string $albumID - Picasa RSS Album ID.
	 * @param array $conf - Associative array used to override default settings in config.inc.  See config.inc for parameters
	 * @throws Exception - Variable types are incorrect.
	 */
	public function __construct($albumId, $conf = NULL) {
		$this -> albumId = $albumId;
		$this -> _build_conf($conf);
	}

	/**
	 * Set a config variable
	 * @access public
	 * @param string $var - The key of the config array to set
	 * @param mixed $value - The config value to use
	 */
	final public function set_conf($key, $value) {
		$this -> config[$key] = $value;
	}

	/**
	 * Build the internal config array based off of the config file and user defined parameters
	 * @access private
	 * @param array $conf - The passed in array from the user to the constructor.
	 */
	final private function _build_conf(&$conf){
		require_once ($this->config_file); # Include the config file.  Throw an error if not found!
		if(is_array($conf))
			$pjfp_conf = array_merge($pjfp_conf, $conf);
		foreach($pjfp_conf as $key => $val)
			$this->set_conf($key, $val);
	}
	
	public function get_data(){
		if(empty($this->data))
			$this -> _json();
		return $this->data;
	}

	/**
	 * Basic curl method to get data from Picasa
	 * @access private
	 * @throws Exception - curl failed.
	 * @return string Requested Data From Picasa.
	 */
	private function _curl() {
		$url = sprintf("http://picasaweb.google.com/data/feed/base/user/%s/albumid/%s%s",
			$this->config['user'],
			$this -> albumId,
			"?alt=json&fields=entry(media:group)&imgmax=577" // Down here to shorten Completed URL visually
		);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_FAILONERROR, TRUE);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		if (!$ret = curl_exec($ch))
			throw new Exception("An error occured in method _curl()! - " . $this -> cURL . curl_error($ch));
		return ($ret);
	}

	/**
	 * This is simply an alternative to curl since some systems may not have it available.
	 * Both methods ultimately do the same thing.
	 * @access private
	 * @throws Exception - socket failed
	 * @return string Requested JSON string from Picasa
	 */
	private function socket() {
		$ret = NULL;
		$url = "picasaweb.google.com";
		$fp = fsockopen($url, 80, $errno, $errstr, 10);
		if (!$fp)
			throw new Exception("An error occured in method socket()! - $errstr ($errno)");
		else {
			$out = sprintf("GET /data/feed/base/user/%s/albumid/%s?imgmax=%d%s HTTP/1.1\r\n",
				$this->config['user'],
				$this -> albumId,
				$this->config['max_width'],
				"&alt=json&fields=entry(media:group)" // Down here to shorten Completed URL visually
			);
			$out .= "Host: $url\r\n";
			$out .= "Connection: Close\r\n\r\n";
			fwrite($fp, $out);
			$headers = TRUE;
			while (!feof($fp)) {
				if (!$headers)
					$ret .= fgets($fp, 128);
				elseif (fgets($fp, 128) == "\r\n")
					$headers = FALSE;
			}
			fclose($fp);
			return ($ret);
		}
	}

	/**
	 * JSON Method
	 * @access private
	 * Sets:  $this->data to decoded JSON as an array (NOT AN OBJECT)
	 */
	private function _json() {
		$json = ($this -> config['use_curl'] ? $this -> _curl() : $this -> socket());
		if (!$arr = json_decode($json, TRUE))
			throw new Exception("Could not Decode supplied JSON in method _json()!");
		foreach ($arr['feed']['entry'] as $v) {
			$url = $v['media$group']['media$content'][0]['url'];
			if ($this -> config['use_HTTPS']) {
				$url = str_replace("http", "https", $url);
			}
			$this -> _image_resize($v['media$group']['media$content'][0]['width'], $v['media$group']['media$content'][0]['height']);
			$this -> data[] = array($url, $v['media$group']['media$content'][0]['width'], $v['media$group']['media$content'][0]['height'], $v['media$group']['media$description']['$t']);
		}
	}

	/**
	 * Image Resize Method
	 * Adjusts the vertical image's width x height parameters so it fits the bounds of the gallery dynamically.
	 * If the image is horizontal (width > height) then Picasa will spit it out at the correct size already
	 *   so there is no reason to resize those images.
	 * @access private
	 * @param $width - Variable reference to width
	 * @param $height - Variable reference to height
	 */
	private function _image_resize(&$width, &$height) {
		if ($height > $this->config['max_height']) {
			if ($height == $width) {
				$width = $this->config['max_height'];
				$height = $this->config['max_height'];
			} else {
				$width = round($width * ($this->config['max_height'] / $height));
				$height = $this->config['max_height'];
			}
		}
	}
}

#EOF pjfp.inc