<?php
/*
Plugin Name: SQL to Table
Plugin URI: https://futurelearning.nl
Description: This plugin executes stored SQL queries and displays the result as a table in posts/pages via a shortcode.
Requires at least: WP 6
Author: Qndrs
Author URI: qndrs.nl
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Version: 1.0
*/
// TODO: Add code here for creating the admin panel
// Adding admin menu
function sql_to_table_add_admin_menu() {
	add_menu_page( 'SQL to Table', 'SQL to Table', 'manage_options', 'sql_to_table', 'sql_to_table_options_page' );
}
add_action( 'admin_menu', 'sql_to_table_add_admin_menu' );

// This function runs when the plugin is activated
function sql_to_table_install() {
	global $wpdb;

	$table_name = $wpdb->prefix . 'sql_to_table_queries';

	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        query text NOT NULL,
        shortcode text NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
}

register_activation_hook( __FILE__, 'sql_to_table_install' );

// Render the options page
function sql_to_table_options_page() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'sql_to_table_queries';
	$queries = $wpdb->get_results("SELECT * FROM $table_name");

	?>
    <div class="wrap">
        <h2>SQL to Table</h2>
		<?php
		foreach ( $queries as $query ) {
			?>
            <form method="post">
                <input type="hidden" name="id" value="<?php echo esc_attr($query->id); ?>">
                <label for="query">Query:</label><br>
                <textarea name="query" id="query" cols="40" rows="5"><?php echo esc_textarea($query->query); ?></textarea><br>
                <label for="shortcode">Shortcode:</label><br>
                <input type="text" id="shortcode" value="<?php echo esc_attr($query->shortcode); ?>" readonly><br>
                <input type="submit" name="update" value="Update">
                <input type="submit" name="delete" value="Delete">
            </form>
			<?php
		}
		?>
        <h2>Add New Query</h2>
        <form method="post">
            <label for="query">Query:</label><br>
            <textarea name="query" id="query" cols="40" rows="5"></textarea><br>
            <input type="submit" name="add" value="Add">
        </form>
    </div>
	<?php
}


// TODO: Add code here for handling the shortcode

// TODO: Add code here for executing SQL queries and generating the table

// TODO: Add code here for enqueueing sorttable.js on the pages where the shortcode is used
