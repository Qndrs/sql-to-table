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
Version: 1.6
*/
// Adding admin menu
function sql_to_table_add_admin_menu() {
	global $sql_to_table_page_hook;
	$sql_to_table_page_hook = add_menu_page( 'SQL to Table', 'SQL to Table', 'manage_options', 'sql_to_table', 'sql_to_table_options_page' );
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

	// Check if the form is submitted
	if ( isset( $_POST['update'] ) || isset( $_POST['delete'] ) || isset( $_POST['add'] ) ) {
		// Verify the nonce
		check_admin_referer( 'sql_to_table_update_queries' );

		if ( isset( $_POST['update'] ) ) {
			// Update an existing query
			$wpdb->update(
				$table_name,
				array( 'query' => $_POST['query'] ), // data
				array( 'id' => $_POST['id'] ) // where
			);
		} elseif ( isset( $_POST['delete'] ) ) {
			// Delete a query
			$wpdb->delete(
				$table_name,
				array( 'id' => $_POST['id'] ) // where
			);
		} elseif ( isset( $_POST['add'] ) ) {
			// Add a new query
			$wpdb->insert(
				$table_name,
				array( 'query' => $_POST['query'] ) // data
			);
		}
	}

	$queries = $wpdb->get_results("SELECT * FROM $table_name");

	?>
    <div class="wrap">
        <h2>SQL to Table</h2>
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

	// Get the query associated with the id from the database
	global $wpdb;
	$table_name = $wpdb->prefix . 'sql_to_table_queries';
	$query_row = $wpdb->get_row( $wpdb->prepare(
		"SELECT * FROM $table_name WHERE id = %d",
		$atts['id']
	) );

	// If there is no query with this id, return an error message
	if ( $query_row === null ) {
		return 'No query found with this id.';
	}

	if($query_row) {
		// Unescape the query before executing it
		$query = stripslashes($query_row->query);
		$results = $wpdb->get_results($query, ARRAY_A);

	} else {
		return 'Error running query: ' . $wpdb->last_error;
	}

	// Start the output
	$output = '<table class="sortable">';

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

function sanitize_sql_query($query) {
	// Remove trailing and leading white spaces
	$query = trim($query);

	// Check if there's more than one statement
	$semicolon_position = strpos($query, ';');
	if ($semicolon_position !== false && $semicolon_position !== strlen($query) - 1) {
		throw new Exception("Only one SQL statement is allowed.");
	}

	// Check that the query begins with "SELECT"
	if (strtoupper(substr($query, 0, 6)) !== "SELECT") {
		throw new Exception("Only SELECT statements are allowed.");
	}

	// For extra security, use a regular expression to allow only alphanumeric characters, spaces, underscores, dots, commas, parentheses, equals signs and single quotes
	if (!preg_match("/^[a-zA-Z0-9\s_,\.\(\)='*]+$/", $query)) {
		throw new Exception("Invalid characters in SQL statement.");
	}

	// If all checks pass, return the query
	return $query;
}
