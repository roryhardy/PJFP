<?php
/**
 * @package Picasa JSON Feed Parser
 * @category Libraries
 * @author GneatGeek <oregonstate.edu/~croninhr>
 * @copyright (c) 2012, Rory Hardy [GneatGeek]
 * @license http://www.opensource.org/licenses/BSD-3-Clause BSD-3 Clause.  The license is included in the repo.
 * @link http://github.com/gneatgeek/PJFP
 * @version 1.4.5
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
	 * The name and/or path of the config file to use. This shouldn't need to be changed.
	 * If the file is in the same dir as PJFP the path may be omitted!
	 * If it needs to be changed, do it here!
	 * @var string
	 */
	private $config_file = "PJFP_config.php";

	/**
	 * Class constant for requesting an associative array as opposed to numeric.
	 * @access public
	 */
	const NUMERIC = 0x01;

	/**
	 * Class constant for requesting an associative array as opposed to numeric.
	 * @access public
	 */
	const ASSOC = 0x02;

	/**
	 * Class constant for requesting an associative array merged with a numeric array.
	 * NUMERIC = 0x01, ASSOC = 0x02 -> ASSOC|NUMERIC = 0x03
	 * @access public
	 */
	const BOTH = 0x03;

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
	 * Sets a config variable
	 * @param string $key - The key of the config array to set
	 * @param mixed $value - The config value to use
	 */
	final public function set_conf($key, $value) {
		$this -> config[$key] = $value;
	}

	/**
	 * Build the internal config array based off of the config file and user defined parameters
	 * @param array $conf - The passed in array from the user to the constructor.
	 * @throws Exception - Config File is MIA
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
	 * Retrieve the 2D array of data created by json()
	 * Format is array(URL, width, height, caption)
	 *   Default is numeric indicies.
	 * Calls json() if data array is not yet built.
	 * @param int $type - What type of array to request. PJFP::ASSOC for associative, PJFP::BOTH for mixed
	 * @return array
	 */
	public function get_data($type = self::NUMERIC) {
		if (empty($this -> data))
			$this -> json();

		# Needs to be before the numeric case and after the json call.
		$tmp_array = array_fill(0, count($this -> data), array());

		if (($type & 0x01) != 0) # Numeric case
			$tmp_array = $this -> data;

		if (($type & 0x02) != 0) { # Associative case
			static $keys = array("URL", "width", "height", "caption");

			foreach ($this->data as $key => &$node)
				$tmp_array[$key] = array_merge($tmp_array[$key], array_combine($keys, $node));
		}

		return $tmp_array;
	}

	/**
	 * Basic curl method to get data from Picasa
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
			$out = sprintf("GET /data/feed/base/user/%s/albumid/%s?alt=json&fields=entry(media:group)&imgmax=%d%s HTTP/1.1\r\n",
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
	 * Adjusts the vertical image's width x height parameters so it fits the bounds of the gallery dynamically.
	 * If the image is horizontal (width > height) then Picasa will spit it out at the correct size already
	 *   so there is no reason to resize those images.
	 * @param int $width - Variable reference to width
	 * @param int $height - Variable reference to height
	 */
	private function image_resize(&$width, &$height) {
		if ($height > $this -> config['max_height']) {
			if ($height == $width)
				$width  = $height = $this -> config['max_height'];
			else {
				$width  = round($width * ($this -> config['max_height'] / $height));
				$height = $this -> config['max_height'];
			}
		}
	}

	/**
	 * Getter method to access the config array.
	 * @param string $index - The array index to return.
	 * @return mixed
	 */
	public function get_config($index) {
			
		return ($this -> config[$index]);
	}
}

#EOF pjfp.php