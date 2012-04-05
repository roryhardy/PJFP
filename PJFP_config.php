<?php
/**
 * @package Picasa JSON Feed Parser
 * @category Configuration
 * @author Rory Cronin-Hardy (GneatGeek)
 * @link oregonstate.edu/~croninhr/
 * @version 1.2
 */

// ------------------------------------------------------------------------

/*
 * Associative array containing all the configuration aspects.
 * Put all of the defaults in the config.inc file and override the values you need by passing
 * an associative array to the constructor.
 * 
 * EXPLANATION OF VARIABLES
 * ['user']      The username for the given Picasa Album.
 * ['max_width']  The maximum image width in pixels.
 * ['max_height'] The maximum image height in pixels.
 * ['use_HTTPS']  TRUE/FALSE - Whether to make image URLS use HTTPS or HTTP
 *   OR use (empty($_SERVER['HTTPS']) ? FALSE : TRUE); to auto determine [UNTESTED]
 * ['use_curl']   TRUE/FALSE - Whether to use curl or sockets.
 */

$pjfp_conf['user']       = "";
$pjfp_conf['max_width']  = 550;
$pjfp_conf['max_height'] = 367;
$pjfp_conf['use_HTTPS']  = FALSE;
$pjfp_conf['use_curl']   = TRUE;

# EOF pjfp_config.inc