# TODO

## High Priority

- [x] Fix potential XSS in `sql-to-table.php` by removing inline `onclick` JavaScript for JSON export.
- [x] Encode export data with `wp_json_encode()` and safe JSON hex flags before exposing it to the page.
- [x] Move export handling into an enqueued JavaScript file and bind events with unique selectors/data attributes.
- [x] Fix the activation schema mismatch: `shortcode text NOT NULL` is created but never inserted.
- [x] Remove the unused `shortcode` database column, or make it nullable/defaulted if it must remain.
- [x] Rework arbitrary SQL execution; `sanitize_sql_query()` is not a reliable SQL safety boundary.
- [x] Prefer a constrained report/query builder or an allowlist of trusted stored reports.

## Medium Priority

- [x] Sanitize and normalize request data from `$_POST` using `wp_unslash()`.
- [x] Cast query IDs with `absint()` before passing them to database operations.
- [x] Use `$wpdb` format arrays for inserts, updates, and deletes.
- Restrict CodeMirror admin assets to the plugin settings page.
- Prefer WordPress-bundled editor assets instead of loading CodeMirror from a CDN.
- [x] Handle `$wpdb->get_results()` errors after query execution and return a safe admin/frontend message.
- [x] Remove unreachable or ineffective error handling around shortcode query execution.

## Low Priority

- [x] Add `defined( 'ABSPATH' ) || exit;` to PHP entry files.
- Prefix all plugin functions consistently to reduce global namespace collision risk.
- Avoid duplicate HTML IDs in admin forms and frontend shortcode output.
- Clean up commented-out hook-check code in the admin enqueue function.
- Document the security model clearly: only trusted administrators should manage stored SQL queries.
