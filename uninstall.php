<?php
/**
 * Uninstall AITSEO Connect
 *
 * Removes all plugin data when the plugin is deleted via WordPress admin.
 *
 * @package AitseoConnect
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Remove plugin options
delete_option('aitseo_connection_key');
delete_option('aitseo_enabled');

// Remove IndexNow key file if it exists
$key = substr(md5(get_option('aitseo_connection_key', '')), 0, 32);
$key_file = ABSPATH . $key . '.txt';
if (file_exists($key_file)) {
    wp_delete_file($key_file);
}
