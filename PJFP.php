<?php
/**
 * @package Picasa JSON Feed Parser
 * @category Libraries
 * @author GneatGeek <oregonstate.edu/~croninhr>
 * @copyright (c) 2012, Rory Hardy [GneatGeek]
 * @license http://www.opensource.org/licenses/BSD-3-Clause BSD-3 Clause.  The license is included in the repo.
 * @link http://github.com/gneatgeek/PJFP
 * @version 1.4.1
 */

// --------------------------------------------------------------------------------------

/**
 * Picasa JSON Feed Parser (PJFP) Class
 * This class parses a given Picasa RSS feed in JSON format.
 * It will get image URLs, width, height, and captions for a particular album.
 * @author GneatGeek
 */
class PJFP {
	/**
	 * The name and absolute path of the config file to use. This shouldn't need to be changed.
	 * If the file is in the same dir as PJFP the path may be omitted!
	 * If it does, do it here!
	 * @var string
	 */
	private $config_file = "PJFP_config.php";

	/**
	 * Array of all loaded config values. (See PJFP_config.php)
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
	 * Key that picasa uses for limited/private galleries.
	 * @var string
	 */
	private $authKey;

	/**
	 * Constructor
	 * @access public
	 * @param string $albumID - Picasa RSS album ID.
	 * @param array $conf - Associative array used to override default settings in config.inc.  See PJFP_config.php for parameters
	 * @param string $authKey - Key that picasa uses for limited/private galleries.
	 * @throws Exception - Variable types are incorrect.
	 */
	public function __construct($albumId, $authKey = "", $conf = NULL) {
		$this -> albumId = $albumId;
		$this -> authKey = $authKey;
		$this -> build_conf($conf);
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
	final private function build_conf(&$conf) {
		include ($this -> config_file);

		if (!isset($pjfp_conf))
			throw new Exception("PJFP failed to load the config file [{$this -> config_file}]");

		if (is_array($conf))
			$pjfp_conf = array_merge($pjfp_conf, $conf);

		foreach ($pjfp_conf as $key => $val)
			$this -> set_conf($key, $val);
	}

	/**
	 * Method to retrieve the 2D array of data created by json()
	 * Format is array(URL, width, height, caption)
	 * Calls json() if data array is not yet built.
	 * @param string $type - What type of array to request.  assoc/associative or both. Leave blank for numeric.
	 * @return array
	 */
	public function get_data($type = NULL) {
		if (empty($this -> data))
			$this -> json();
		
		switch(strtolower($type)) {
			case "both" :
				$tmp_array = $this -> data;
				
				foreach ($tmp_array as &$node) {
					$node = array_merge($node, array(
						"URL"     => $node[0],
						"width"   => $node[1],
						"height"  => $node[2], 
						"caption" => $node[3])
					);
				}
				
				return $tmp_array;
			case "assoc" :
			case "associative" :
				$tmp_array = $this -> data;
				
				foreach ($tmp_array as &$node) {
					$node = array(
						"URL"     => $node[0],
						"width"   => $node[1], 
						"height"  => $node[2],
						"caption" => $node[3]
					);
				}
				
				return $tmp_array;
		}

		return $this -> data; # Catch all case
	}

	/**
	 * Basic curl method to get data from Picasa
	 * @access private
	 * @throws Exception - curl failed.
	 * @return string
	 */
	private function curl() {
		$ch  = curl_init();
		$url = sprintf("http://picasaweb.google.com/data/feed/base/user/%s/albumid/%s?alt=json&fields=entry(media:group)&imgmax=%d%s",
			$this -> config['user'],
			$this -> albumId,
			$this -> config['max_width'],
			(!empty($this -> authKey) ? "&authkey={$this -> authKey}" : "")
		);

		curl_setopt($ch, CURLOPT_URL,            $url);
		curl_setopt($ch, CURLOPT_FAILONERROR,    TRUE);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_TIMEOUT,        10);

		if (!$ret = curl_exec($ch))
			throw new Exception("An error occured in method curl()! - " . $this -> cURL . curl_error($ch));

		return ($ret);
	}

	/**
	 * This is simply an alternative to curl since some systems may not have it available.
	 * Both methods ultimately do the same thing.
	 * @access private
	 * @throws Exception - socket failed
	 * @return string
	 */
	private function socket() {
		$headers = TRUE;
		$ret     = NULL;
		$url     = "picasaweb.google.com";
		$fp      = fsockopen($url, 80, $errno, $errstr, 10);

		if (!$fp)
			throw new Exception("An error occured in method socket()! - $errstr ($errno)");
		else {
			$out  = sprintf("GET /data/feed/base/user/%s/albumid/%s?alt=json&fields=entry(media:group)&imgmax=%d%s HTTP/1.1\r\n",
				$this -> config['user'],
				$this -> albumId,
				$this -> config['max_width'],
				(!empty($this -> authKey) ? "&authkey={$this -> authKey}" : "")
			);
			$out .= "Host: $url\r\n";
			$out .= "Connection: Close\r\n\r\n";
			fwrite($fp, $out);

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
	 * Method to parse the JSON data fetched from Picasa
	 * Sets:  $this->data to decoded JSON as an array (NOT AN OBJECT)
	 * @access private
	 */
	private function json() {
		$json = ($this -> config['use_curl'] ? $this -> curl() : $this -> socket());

		if (!$arr = json_decode($json, TRUE))
			throw new Exception("Could not Decode supplied JSON in method json()!");

		foreach ($arr['feed']['entry'] as $v) {
			$url = $v['media$group']['media$content'][0]['url'];

			if ($this -> config['use_HTTPS'])
				$url = str_replace("http", "https", $url);

			$this -> image_resize(
				$v['media$group']['media$content'][0]['width'],
				$v['media$group']['media$content'][0]['height']
			);

			$this -> data[] = array(
				$url,
				$v['media$group']['media$content'][0]['width'],
				$v['media$group']['media$content'][0]['height'],
				$v['media$group']['media$description']['$t']
			);
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
	private function image_resize(&$width, &$height) {
		if ($height > $this -> config['max_height']) {
			if ($height == $width) {
				$width  = $this -> config['max_height'];
				$height = $this -> config['max_height'];
			} else {
				$width  = round($width * ($this -> config['max_height'] / $height));
				$height = $this -> config['max_height'];
			}
		}
	}

	/**
	 * Getter method to access the config array.
	 * @access public
	 * @param $index - The array index to return.
	 * @return mixed
	 */
	public function get_config($index) {
		return ($this -> config[$index]);
	}
}

#EOF pjfp.php
