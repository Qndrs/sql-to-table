# SQL to Table

SQL to Table is a WordPress plugin that allows you to execute stored SQL select queries and display the results as a table in posts or pages via a simple shortcode. It comes with an admin interface for managing your stored queries.

## Features
- Save, update, and delete your SQL select queries from the admin interface.
- Display the results of your SQL queries within your posts/pages via shortcodes.
- Tables are sortable thanks to the Sorttable.js library.

## Installation
1. Download the plugin and unzip it.
2. Upload the `sql-to-table` folder to your `/wp-content/plugins/` directory.
3. Activate the plugin through the 'Plugins' menu in WordPress.
4. Go to the Tools -> SQL to Table page in your WordPress admin panel to start using the plugin.

## Usage
1. Go to the Tools -> SQL to Table page in your WordPress admin panel.
2. Enter your SQL select queries in the provided textarea and save, or update/delete existing queries.
3. Use the provided shortcode in your posts/pages to display the result of a query as a table. The format for the shortcode is `[sql_to_table id="YOUR_QUERY_ID"]`, replacing `YOUR_QUERY_ID` with the ID of the query you wish to display.

## Frequently Asked Questions

**Q: What types of SQL queries are supported?**

A: The plugin currently supports SQL select queries only.

**Q: How do I update or delete a query?**

A: You can manage your queries from the Tools -> SQL to Table page in your WordPress admin panel. Each query has an "Update" and "Delete" button.

## Changelog

**1.8**
- Users can now add, update, and delete queries through the admin interface.
- Introduced syntax highlighting for SQL queries using CodeMirror library.
- Implemented security measures to sanitize SQL queries.

**1.0**
- Initial release.

## License
This plugin is licensed under GPLv3.

## Author
This plugin is developed by Qndrs. For more information, visit [qndrs.nl](https://qndrs.nl).
