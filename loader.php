<?php

/*
  Plugin Name: BP Group Documents
  PLugin URI: http://lenasterg.wordpress.com/
  Description: This BuddyPress component creates a document storage area within each group. Original plugin is <a href="http://wordpress.org/extend/plugins/buddypress-group-documents/">no longer supported</a> so revised by Lena Stergatu (<a href="mailto://stergatu@cti.gr">stergatu@cti.gr</a>) for WP 3.3, with additional bug fixes and improvements by <a href="http://keeblesmith.com">Keeble Smith</a>.
  Version: 1.0
  Revision Date: August 28, 2013
  Requires at least: WP 3.5.1, BuddyPress 1.7
  Tested up to: WP 3.6, BuddyPress 1.8
  License:  GNU General Public License 3.0 or newer (GPL) http://www.gnu.org/licenses/gpl.html
  Author:Lena Stergatu (http://lenasterg.wordpress.com) since v. 0.4.3.3. with additional bug fixing and improvements by <a href="http://keeblesmith.com">Keeble Smith</a>. Original plugin author Peter Anselmo, Studio66.
  Author URI:
  Network Only: true
 * @todo minor, make a deregister function, 26/4/2013 stergatu
 */


/* Only load code that needs BuddyPress to run once BP is loaded and initialized. */

//some constants that can be checked when extending this plugin
define('BP_GROUP_DOCUMENTS_IS_INSTALLED', 1);
define('BP_GROUP_DOCUMENTS_VERSION', '0.5');
define('BP_GROUP_DOCUMENTS_DB_VERSION', '5');
define('BP_GROUP_DOCUMENTS_VALID_FILE_FORMATS', 'odt,rtf,txt,doc,docx,xls,xlsx,ppt,pps,pptx,pdf,jpg,jpeg,gif,png,zip,tar,gz');
define('BP_GROUP_DOCUMENTS_ITEMS_PER_PAGE', 20);
define('BP_GROUP_DOCUMENTS_UPLOAD_PERMISSION', 'mods_decide');
define('BP_GROUP_DOCUMENTS_DISPLAY_ICONS', 1);
define('BP_GROUP_DOCUMENTS_USE_CATEGORIES', 1);
define('BP_GROUP_DOCUMENTS_ENABLE_ALL_GROUPS', true);
define('BP_GROUP_DOCUMENTS_PROGRESS_BAR', 1);
define('BP_GROUP_DOCUMENTS_FORUM_ATTACHMENTS', 0);


//allow override of URL slug
if (!defined('BP_GROUP_DOCUMENTS_SLUG')) {
    define('BP_GROUP_DOCUMENTS_SLUG', 'documents');
}

/**
 * Nifty function to get the name of the directory bp_group_documents plugin is installed in.
 * @author  Stergatu Eleni 
 * @version 1, 6/3/2013
 * @since 0.5
 */
function bp_group_documents_dir() {
    if (stristr(__FILE__, '/'))
        $bp_gr_dir = explode('/plugins/', dirname(__FILE__));
    else
        $bp_gr_dir = explode('\\plugins\\', dirname(__FILE__));
    return str_replace('\\', '/', end($bp_gr_dir)); //takes care of MS slashes
}

$bp_gr_dir = bp_group_documents_dir();

define('BP_GROUP_DOCUMENTS_DIR', $bp_gr_dir); //the name of the directory that bp_group_documents  files are located.

/**
 * @author Stergatu Eleni 
 * @global type $wpdb
 * @return type
 * @since 0.5
 * @version 1 30/4/2013
 */
function bp_group_documents_init() {
    global $wpdb;
    if (is_multisite() && BP_ROOT_BLOG != $wpdb->blogid)
        return;
    if (!bp_is_active('groups'))
        return;
    // Because our loader file uses BP_Component, it requires BP 1.5 or greater.
    if (version_compare(BP_VERSION, '1.6.5', '>')) {
        bp_group_documents_set_constants();
        require( dirname(__FILE__) . '/buddypress-group-documents.php' );
        bp_group_documents_include_files();
    }
}

add_action('bp_include', 'bp_group_documents_init');

/**
 * bp_group_documents_is_installed()
 * Checks to see if the DB tables exist or if we are running an old version
 * of the component. If the value has increased, it will run the installation function.
  @since 0.5
 * @version 1, 5/3/2013
 */
function bp_group_documents_is_installed() {
    bp_group_documents_set_constants();
    if (get_site_option('bp-group-documents-db-version') < BP_GROUP_DOCUMENTS_DB_VERSION) {
        bp_group_documents_install_upgrade();
    }
}

register_activation_hook(__FILE__, 'bp_group_documents_is_installed');

/**
 * bp_group_documents_install_upgrade()
 *
 * Installs and/or upgrades the database tables
 * This will only run if the database version constant is         
 * greater than the stored database version value or no database version found
 * @author Stergatu Eleni <stergatu@cti.gr>
 * @version 1.0, 7/3/2013 now uses add_site_option instead of add_option 
 * @since 0.5

 */
function bp_group_documents_install_upgrade() {
    global $wpdb, $bp;
      
    $charset_collate = '';
    if (!empty($wpdb->charset)) {
        $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
    }

    //If there is a previous version installed then move the variables to the sitemeta table
    if ((get_site_option('bp-group-documents-db-version')) && (get_site_option('bp-group-documents-db-version') < 5)) {
        $sql[] = bp_group_documents_tableCreate($charset_collate);
    }
    if (!get_site_option('bp-group-documents-db-version')) {
        $sql[] = bp_group_documents_tableCreate($charset_collate);
        add_option('bp_group_documents_nav_page_name', __('Documents', 'bp-group-documents'));
        add_option('bp_group_documents_valid_file_formats', BP_GROUP_DOCUMENTS_VALID_FILE_FORMATS);
        add_option('bp_group_documents_items_per_page', BP_GROUP_DOCUMENTS_ITEMS_PER_PAGE);
        add_option('bp_group_documents_upload_permission', BP_GROUP_DOCUMENTS_UPLOAD_PERMISSION);
        add_option('bp_group_documents_display_icons', BP_GROUP_DOCUMENTS_DISPLAY_ICONS);
        add_option('bp_group_documents_use_categories', BP_GROUP_DOCUMENTS_USE_CATEGORIES);
        add_option('bp_group_documents_enable_all_groups', BP_GROUP_DOCUMENTS_ENABLE_ALL_GROUPS);
        add_option('bp_group_documents_progress_bar', BP_GROUP_DOCUMENTS_PROGRESS_BAR);
        add_option('bp_group_documents_forum_attachments', BP_GROUP_DOCUMENTS_FORUM_ATTACHMENTS);
        add_option('bp-group-documents-db-version', BP_GROUP_DOCUMENTS_DB_VERSION);
    }
    update_site_option('bp-group-documents-db-version', BP_GROUP_DOCUMENTS_DB_VERSION);

    require_once( ABSPATH . "wp-admin/includes/upgrade.php" );


    dbDelta($sql);
}

/**
 * SQL create command for BP_GROUP_DOCUMENTS_TABLE 
 * @since version 0.5
 * @author stergatu
 * @version 1.0, 25/4/2013
 * @param type $charset_collate
 * @return string
 */
function bp_group_documents_tableCreate($charset_collate) {
    $toSql = $sql[] = "CREATE TABLE " . BP_GROUP_DOCUMENTS_TABLE . " (
		  		id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
		  		user_id bigint(20) NOT NULL,
		  		group_id bigint(20) NOT NULL,
		  		created_ts int NOT NULL,
				modified_ts int NOT NULL,
				file VARCHAR(255) NOT NULL,
				name VARCHAR(255) NULL,
				description TEXT NULL,
				featured BOOL DEFAULT FALSE,
				download_count bigint(20) NOT NULL DEFAULT 0,
                                KEY user_id (user_id),
                                KEY group_id (group_id),
                                KEY created_ts (created_ts),
				KEY modified_ts (modified_ts),
				KEY download_count (download_count)
		 	   ) {$charset_collate};";
    return $toSql;
}

/**
 * @author Stergatu Eleni  
 * @since 0.5
 * @version 2, 24/4/2013
 */
function bp_group_documents_set_constants() {
    global $wpdb, $bp;
    if (!defined('BP_GROUP_DOCUMENTS_TABLE'))
        define('BP_GROUP_DOCUMENTS_TABLE', $wpdb->base_prefix . 'bp_group_documents');


    //This is where to look for admin bulk uploads
    if (!defined('BP_GROUP_DOCUMENTS_ADMIN_UPLOAD_PATH'))
        define('BP_GROUP_DOCUMENTS_ADMIN_UPLOAD_PATH', WP_PLUGIN_DIR . '/buddypress-group-documents/uploads/');

    //Widgets can be set to only show documents in certain (site-admin specified) groups
    if (!defined('BP_GROUP_DOCUMENTS_WIDGET_GROUP_FILTER'))
        define('BP_GROUP_DOCUMENTS_WIDGET_GROUP_FILTER', true);

    //if enabled, documents can be flagged as "featured"
    //widget will have an option to only show featured docs
    if (!defined('BP_GROUP_DOCUMENTS_FEATURED'))
        define('BP_GROUP_DOCUMENTS_FEATURED', true);

    //longer text descriptions to go with the documents can be toggled on or off.
    //this will toggle both the textarea input, and the display;
    if (!defined('BP_GROUP_DOCUMENTS_SHOW_DESCRIPTIONS'))
        define('BP_GROUP_DOCUMENTS_SHOW_DESCRIPTIONS', true);

    //if enabled, and wp_editor exists, it will be used for the document comment editor
    if (!defined('BP_GROUP_DOCUMENTS_ALLOW_WP_EDITOR'))
        define('BP_GROUP_DOCUMENTS_ALLOW_WP_EDITOR', false);

    switch (substr(BP_VERSION, 0, 3)) {
        case '1.1':
            define('BP_GROUP_DOCUMENTS_THEME_VERSION', '1.1');
            break;
        case '1.2':
        default:
            if ('BuddyPress Classic' == wp_get_theme()) {
                define('BP_GROUP_DOCUMENTS_THEME_VERSION', '1.1');
            } else {
                define('BP_GROUP_DOCUMENTS_THEME_VERSION', '1.2');
            }
            break;
    }
    do_action('bp_group_documents_globals_loaded');
}

?>