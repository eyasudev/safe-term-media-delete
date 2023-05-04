<?php
/*
Plugin Name: Safe Term Media Delete
Plugin URI: #
Description: A brief description of the Plugin.
Version: 1.0.0
Author: Eyasu
Author URI: #
License: GPL2
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

define("STMD_PATH", plugin_dir_path(__FILE__));
define("STMD_URL", plugin_dir_url(__FILE__));
define("STMD_VERSION", "1.0.0");

require STMD_PATH."vendor/autoload.php";

new \SafeTermMediaDelete\Wp_Core();
