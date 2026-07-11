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
Version: 2.0
*/

defined( 'ABSPATH' ) || exit;

define( 'SQL_TO_TABLE_VERSION', '2.0' );

function sql_to_table_get_table_name() {
	global $wpdb;

	return $wpdb->prefix . 'sql_to_table_queries';
}

// Adding admin menu
function sql_to_table_add_admin_menu() {
	global $sql_to_table_page_hook;
	$sql_to_table_page_hook = add_management_page( 'SQL to Table', 'SQL to Table', 'manage_options', 'sql_to_table', 'sql_to_table_options_page' );
}
add_action( 'admin_menu', 'sql_to_table_add_admin_menu' );

// This function runs when the plugin is activated
function sql_to_table_install() {
	global $wpdb;

	$table_name = sql_to_table_get_table_name();

	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        query text NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

	sql_to_table_migrate_schema();
	update_option( 'sql_to_table_version', SQL_TO_TABLE_VERSION );
}

register_activation_hook( __FILE__, 'sql_to_table_install' );

function sql_to_table_migrate_schema() {
	global $wpdb;

	$table_name       = sql_to_table_get_table_name();
	$table_name_sql   = '`' . str_replace( '`', '``', $table_name ) . '`';
	$table_exists     = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );

	if ( ! $table_exists ) {
		return;
	}

	$shortcode_column = $wpdb->get_var( "SHOW COLUMNS FROM $table_name_sql LIKE 'shortcode'" );

	if ( $shortcode_column ) {
		$wpdb->query( "ALTER TABLE $table_name_sql DROP COLUMN shortcode" );
	}
}

function sql_to_table_maybe_migrate_schema() {
	if ( SQL_TO_TABLE_VERSION === get_option( 'sql_to_table_version' ) ) {
		return;
	}

	sql_to_table_migrate_schema();
	update_option( 'sql_to_table_version', SQL_TO_TABLE_VERSION );
}
add_action( 'plugins_loaded', 'sql_to_table_maybe_migrate_schema' );

// Render the options page
function sql_to_table_options_page() {
	global $wpdb;
	$table_name = sql_to_table_get_table_name();
	$message    = '';
	$error      = '';

	// Check if the form is submitted
	if ( isset( $_POST['update'] ) || isset( $_POST['delete'] ) || isset( $_POST['add'] ) ) {
		// Verify the nonce
		check_admin_referer( 'sql_to_table_update_queries' );
		$query_id         = isset( $_POST['id'] ) ? absint( wp_unslash( $_POST['id'] ) ) : 0;
		$posted_query     = isset( $_POST['query'] ) ? wp_unslash( $_POST['query'] ) : '';
		$normalized_query = sql_to_table_normalize_select_query( $posted_query );

		if ( isset( $_POST['update'] ) && $query_id && $normalized_query ) {
			// Update an existing query
			$wpdb->update(
				$table_name,
				array( 'query' => $normalized_query ), // data
				array( 'id' => $query_id ), // where
				array( '%s' ),
				array( '%d' )
			);
			$message = 'Query updated.';
		} elseif ( isset( $_POST['delete'] ) && $query_id ) {
			// Delete a query
			$wpdb->delete(
				$table_name,
				array( 'id' => $query_id ), // where
				array( '%d' )
			);
			$message = 'Query deleted.';
		} elseif ( isset( $_POST['add'] ) && $normalized_query ) {
			// Add a new query
			$wpdb->insert(
				$table_name,
				array( 'query' => $normalized_query ), // data
				array( '%s' )
			);
			$message = 'Query added.';
		} else {
			$error = 'Only a single read-only SELECT query can be saved.';
		}
	}

	$queries = $wpdb->get_results( "SELECT id, query FROM $table_name ORDER BY id ASC" );

	?>
    <div class="wrap">
        <h2>SQL to Table</h2>
		<?php if ( $message ) : ?>
            <div class="notice notice-success is-dismissible"><p><?php echo esc_html( $message ); ?></p></div>
		<?php endif; ?>
		<?php if ( $error ) : ?>
            <div class="notice notice-error is-dismissible"><p><?php echo esc_html( $error ); ?></p></div>
		<?php endif; ?>
		<?php
		foreach ( $queries as $query ) {
			?>
            <form method="post">
	            <?php wp_nonce_field( 'sql_to_table_update_queries' ); ?>
                <input type="hidden" name="id" value="<?php echo esc_attr($query->id); ?>">
                <label for="query">Query:</label><br>
                <textarea name="query" id="query" cols="40" rows="5" class="sql-query"><?php echo stripslashes(esc_textarea($query->query)); ?></textarea><br>
                <label for="shortcode">Shortcode:</label><br>
                <input type="text" id="shortcode" value="[sql_to_table id='<?php echo esc_attr($query->id); ?>']" readonly><br>
                <input type="submit" name="update" value="Update">
                <input type="submit" name="delete" value="Delete">
            </form>
			<?php
		}
		?>
        <h2>Add New Query</h2>
        <form method="post">
	        <?php wp_nonce_field( 'sql_to_table_update_queries' ); ?>
            <label for="query">Query:</label><br>
            <textarea name="query" id="query" cols="40" rows="5" class="sql-query"></textarea><br>
            <input type="submit" name="add" value="Add">
        </form>
    </div>
	<?php
}

// Add the shortcode handler
add_shortcode( 'sql_to_table', 'sql_to_table_shortcode_handler' );

function sql_to_table_shortcode_handler( $atts ) {
	// Shortcodes attributes
	$atts = shortcode_atts( array(
		'id' => null,
	), $atts );
	$query_id = absint( $atts['id'] );

	if ( ! $query_id ) {
		return 'No query found with this id.';
	}

	// Get the query associated with the id from the database
	global $wpdb;
	$table_name = sql_to_table_get_table_name();
	$query_row = $wpdb->get_row( $wpdb->prepare(
		"SELECT id, query FROM $table_name WHERE id = %d",
		$query_id
	) );

	// If there is no query with this id, return an error message
	if ( $query_row === null ) {
		return 'No query found with this id.';
	}

	$query = sql_to_table_normalize_select_query( stripslashes( $query_row->query ) );

	if ( ! $query ) {
		return 'This query is not allowed.';
	}

	wp_enqueue_script( 'sql-to-table-export', plugin_dir_url( __FILE__ ) . 'sql-to-table.js', array(), '2.0', true );

	$results = $wpdb->get_results( $query, ARRAY_A );

	if ( $wpdb->last_error ) {
		return 'Error running query.';
	}

	$export_json = wp_json_encode(
		$results,
		JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
	);

	// Start the output
	// Add the Export to JSON button
	$output = '<button type="button" class="sql-to-table-export" data-sql-to-table-export="' . esc_attr( $export_json ) . '">Export to JSON</button>';
	$output .= '<table class="sortable" >';

	// Header row
	if ( ! empty( $results ) ) {
		$output .= '<thead><tr>';
		foreach ( $results[0] as $key => $value ) {
			$output .= '<th>' . esc_html( $key ) . '</th>';
		}
		$output .= '</tr></thead>';
	}

	// Data rows
	$output .= '<tbody>';
	foreach ( $results as $row ) {
		$output .= '<tr>';
		foreach ( $row as $value ) {
			$output .= '<td>' . esc_html( $value ) . '</td>';
		}
		$output .= '</tr>';
	}
	$output .= '</tbody>';

	// End the output
	$output .= '</table>';
	return $output;
}
function enqueue_sorttable_script() {
	// Define the path to sorttable.js (Modify it according to your actual path)
	$sorttable_js_path = plugin_dir_url(__FILE__) . 'sorttable.js';

	// Get global post object
	global $post;

	// Check if the post content contains the 'sql_to_table' shortcode
	if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'sql_to_table' ) ) {
		// Enqueue sorttable.js
		wp_enqueue_script( 'sorttable', $sorttable_js_path, array(), '1.0', true );
	}
}
add_action( 'wp_enqueue_scripts', 'enqueue_sorttable_script' );

function my_sql_to_table_enqueue($hook) {
//	if ($sql_to_table_page_hook != $hook) {
//		return;
//	}
	wp_enqueue_style('codemirror_css', 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.62.3/codemirror.min.css');
	wp_enqueue_script('codemirror_js', 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.62.3/codemirror.min.js');
	wp_enqueue_script('codemirror_mode_sql', 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.62.3/mode/sql/sql.min.js');
	wp_enqueue_script('my_custom_script', plugins_url('/sql-script.js', __FILE__));
}
add_action('admin_enqueue_scripts', 'my_sql_to_table_enqueue');

function sql_to_table_normalize_select_query( $query ) {
	if ( ! is_string( $query ) ) {
		return false;
	}

	$query = trim( $query );

	if ( '' === $query ) {
		return false;
	}

	if ( ';' === substr( $query, -1 ) ) {
		$query = trim( substr( $query, 0, -1 ) );
	}

	if ( false !== strpos( $query, ';' ) ) {
		return false;
	}

	if ( ! preg_match( '/^SELECT\s+/i', $query ) ) {
		return false;
	}

	$blocked_patterns = array(
		'/--|#|\/\*/',
		'/\b(?:INSERT|UPDATE|DELETE|REPLACE|ALTER|DROP|TRUNCATE|CREATE|GRANT|REVOKE|CALL|DO|LOAD|HANDLER|LOCK|UNLOCK|SET|SHOW|DESCRIBE|EXPLAIN|USE)\b/i',
		'/\bINTO\b/i',
		'/\bFOR\s+UPDATE\b/i',
		'/\bLOCK\s+IN\s+SHARE\s+MODE\b/i',
	);

	foreach ( $blocked_patterns as $pattern ) {
		if ( preg_match( $pattern, $query ) ) {
			return false;
		}
	}

	return $query;
}
