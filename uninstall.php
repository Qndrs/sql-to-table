<?php
// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}
global $wpdb;

// Remove custom table.
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}sql_to_table_queries");

