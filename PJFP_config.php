<?php
/**
 * @package Picasa JSON Feed Parser
 * @category Configuration
 * @author Rory Hardy (GneatGeek)
 * @link http://github.com/gneatgeek/PJFP
 * @version 1.3
 */

// ------------------------------------------------------------------------

/*
 * Associative array containing all the configuration aspects.
 * Put all of the defaults in the config.inc file and override the values you need by passing
 * an associative array to the constructor.
 * 
 * EXPLANATION OF VARIABLES
 * ['user']      The username for the given Picasa Album.
 * ['maxWidth']  The maximum image width in pixels.
 * ['maxHeight'] The maximum image height in pixels.
 * ['useHTTPS']  TRUE/FALSE - Whether to make image URLS use HTTPS or HTTP
 *   OR use (empty($_SERVER['HTTPS']) ? FALSE : TRUE); to auto determine [UNTESTED]
 * ['useCurl']   TRUE/FALSE - Whether to use curl or sockets.
 */

$pjfp_conf['user']       = "gneatgeek";
$pjfp_conf['max_width']  = 550;
$pjfp_conf['max_height'] = 367;
$pjfp_conf['use_HTTPS']  = FALSE;
$pjfp_conf['use_curl']   = TRUE;

# EOF pjfp_config.php