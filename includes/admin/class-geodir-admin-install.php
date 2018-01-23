<?php
/**
 * Installation related functions and actions.
 *
 * @author   AyeCode
 * @category Admin
 * @package  GeoDirectory/Classes
 * @version  2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDir_Admin_Install Class.
 */
class GeoDir_Admin_Install {

	/** @var array DB updates and callbacks that need to be run per version */
	private static $db_updates = array(
		'2.0.0' => array(
			'wc_update_200_file_paths',
			'wc_update_200_permalinks',
			'wc_update_200_subcat_display',
			'wc_update_200_taxrates',
			'wc_update_200_line_items',
			'wc_update_200_images',
			'wc_update_200_db_version',
		),
		'2.0.9' => array(
			'wc_update_209_brazillian_state',
			'wc_update_209_db_version',
		),
		'2.1.0' => array(
			'wc_update_210_remove_pages',
			'wc_update_210_file_paths',
			'wc_update_210_db_version',
		),
		'2.2.0' => array(
			'wc_update_220_shipping',
			'wc_update_220_order_status',
			'wc_update_220_variations',
			'wc_update_220_attributes',
			'wc_update_220_db_version',
		),
		'2.3.0' => array(
			'wc_update_230_options',
			'wc_update_230_db_version',
		),
		'2.4.0' => array(
			'wc_update_240_options',
			'wc_update_240_shipping_methods',
			'wc_update_240_api_keys',
			'wc_update_240_webhooks',
			'wc_update_240_refunds',
			'wc_update_240_db_version',
		),
		'2.4.1' => array(
			'wc_update_241_variations',
			'wc_update_241_db_version',
		),
		'2.5.0' => array(
			'wc_update_250_currency',
			'wc_update_250_db_version',
		),
		'2.6.0' => array(
			'wc_update_260_options',
			'wc_update_260_termmeta',
			'wc_update_260_zones',
			'wc_update_260_zone_methods',
			'wc_update_260_refunds',
			'wc_update_260_db_version',
		),
		'3.0.0' => array(
			'wc_update_300_webhooks',
			'wc_update_300_grouped_products',
			'wc_update_300_settings',
			'wc_update_300_product_visibility',
			'wc_update_300_db_version',
		),
	);

	/** @var object Background update class */
	private static $background_updater;

	/**
	 * Hook in tabs.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'check_version' ), 5 );
		//add_action( 'init', array( __CLASS__, 'init_background_updater' ), 5 );
		add_action( 'admin_init', array( __CLASS__, 'install_actions' ) );
		//add_action( 'in_plugin_update_message-woocommerce/woocommerce.php', array( __CLASS__, 'in_plugin_update_message' ) );
		//add_filter( 'plugin_action_links_' . WC_PLUGIN_BASENAME, array( __CLASS__, 'plugin_action_links' ) );
		add_filter( 'plugin_row_meta', array( __CLASS__, 'plugin_row_meta' ), 10, 2 );
		add_filter( 'wpmu_drop_tables', array( __CLASS__, 'wpmu_drop_tables' ) );
		add_filter( 'cron_schedules', array( __CLASS__, 'cron_schedules' ) );
		//add_action( 'woocommerce_plugin_background_installer', array( __CLASS__, 'background_installer' ), 10, 2 );
	}

	/**
	 * Init background updates
	 */
	public static function init_background_updater() {
		include_once( dirname( __FILE__ ) . '/class-wc-background-updater.php' );
		self::$background_updater = new WC_Background_Updater();
	}

	/**
	 * Check GeoDirectory version and run the updater as required.
	 *
	 * This check is done on all requests and runs if the versions do not match.
	 */
	public static function check_version() { //self::install(); // @todo remove after testing
		if ( ! defined( 'IFRAME_REQUEST' ) && get_option( 'geodirectory_version' ) !== GeoDir()->version ) {
			self::install();
			do_action( 'geodirectory_updated' );
		}
	}

	/**
	 * Install actions when a update button is clicked within the admin area.
	 *
	 * This function is hooked into admin_init to affect admin only.
	 */
	public static function install_actions() {
		if ( ! empty( $_GET['do_update_woocommerce'] ) ) {
			self::update();
			WC_Admin_Notices::add_notice( 'update' );
		}
		if ( ! empty( $_GET['force_update_woocommerce'] ) ) {
			do_action( 'wp_wc_updater_cron' );
			wp_safe_redirect( admin_url( 'admin.php?page=wc-settings' ) );
			exit;
		}
	}

	/**
	 * Install WC.
	 */
	public static function install() {
		global $wpdb;

		if ( ! is_blog_installed() ) {
			return;
		}

		if ( ! defined( 'GD_INSTALLING' ) ) {
			define( 'GD_INSTALLING', true );
		}

		// Ensure needed classes are loaded
		//include_once( dirname( __FILE__ ) . '/class-geodir-admin-notices.php' );


		self::create_tables();
		self::insert_countries();
		self::create_options();
		self::insert_default_fields();
		self::create_pages();


		// Register post types
		GeoDir_Post_types::register_post_types();
		GeoDir_Post_types::register_taxonomies();


		// Also register endpoints - this needs to be done prior to rewrite rule flush
		//WC()->query->init_query_vars();
		//WC()->query->add_endpoints();
		//WC_API::add_endpoint();
		//WC_Auth::add_endpoint();

		///return; // @todo remove after testing

		self::create_cron_jobs();

		// Queue upgrades/setup wizard
		$current_gd_version    = get_option( 'geodirectory_version', null );
		$current_db_version    = get_option( 'geodirectory_db_version', null );

		GeoDir_Admin_Notices::remove_all_notices();

		// No versions? This is a new install :)
		if ( is_null( $current_gd_version ) && is_null( $current_db_version ) && apply_filters( 'geodirectory_enable_setup_wizard', true ) ) {
			GeoDir_Admin_Notices::add_notice( 'install' );
			set_transient( '_gd_activation_redirect', 1, 30 );

		// No archive page template? Let user run wizard again..
		} elseif ( ! geodir_get_option( 'page_archive' ) ) {
			GeoDir_Admin_Notices::add_notice( 'install' );
		}

		if ( ! is_null( $current_db_version ) && version_compare( $current_db_version, max( array_keys( self::$db_updates ) ), '<' ) ) {
			GeoDir_Admin_Notices::add_notice( 'update' );
		} else {
			self::update_db_version();
		}

		self::update_gd_version();

		// Flush rules after install
		do_action( 'geodirectory_flush_rewrite_rules' );

		// Trigger action
		do_action( 'geodirectory_installed' );
	}

	/*
	 * Insert the default field for the CPTs
	 */
	public static function insert_default_fields(){
		$fields = geodir_default_custom_fields('gd_place');

		/**
		 * Filter the array of default custom fields DB table data.
		 *
		 * @since 1.0.0
		 * @param string $fields The default custom fields as an array.
		 */
		$fields = apply_filters('geodir_before_default_custom_fields_saved', $fields);
		foreach ($fields as $field_index => $field) {
			geodir_custom_field_save($field);

		}
	}
	/**
	 * Insert the default countries if needed.
	 */
	public static function insert_countries(){
		global $wpdb;
		$country_table_empty = $wpdb->get_var("SELECT COUNT(CountryId) FROM " . GEODIR_COUNTRIES_TABLE );

		if ($country_table_empty == 0) {
			$countries_insert = '';

			// include the default country data
			include_once( dirname( __FILE__ ) . '/settings/data_countries.php' );

			/**
			 * Filter the SQL query that inserts the country DB table data.
			 *
			 * @since 1.0.0
			 * @param string $sql The SQL insert query string.
			 */
			$countries_insert = apply_filters('geodir_before_country_data_insert', $countries_insert);
			$wpdb->query($countries_insert);
		}

	}

	/**
	 * Update WC version to current.
	 */
	private static function update_gd_version() {
		delete_option( 'geodirectory_version' );
		add_option( 'geodirectory_version', GEODIRECTORY_VERSION );
	}

	/**
	 * Get list of DB update callbacks.
	 *
	 * @since  3.0.0
	 * @return array
	 */
	public static function get_db_update_callbacks() {
		return self::$db_updates;
	}

	/**
	 * Push all needed DB updates to the queue for processing.
	 */
	private static function update() {
		$current_db_version = get_option( 'geodirectory_db_version' );
		$logger             = wc_get_logger();
		$update_queued      = false;

		foreach ( self::get_db_update_callbacks() as $version => $update_callbacks ) {
			if ( version_compare( $current_db_version, $version, '<' ) ) {
				foreach ( $update_callbacks as $update_callback ) {
					$logger->info(
						sprintf( 'Queuing %s - %s', $version, $update_callback ),
						array( 'source' => 'wc_db_updates' )
					);
					self::$background_updater->push_to_queue( $update_callback );
					$update_queued = true;
				}
			}
		}

		if ( $update_queued ) {
			self::$background_updater->save()->dispatch();
		}
	}

	/**
	 * Update DB version to current.
	 * @param string $version
	 */
	public static function update_db_version( $version = null ) {
		delete_option( 'geodirectory_db_version' );
		add_option( 'geodirectory_db_version', is_null( $version ) ? GEODIRECTORY_VERSION : $version );
	}

	/**
	 * Add more cron schedules.
	 * @param  array $schedules
	 * @return array
	 */
	public static function cron_schedules( $schedules ) {
		$schedules['monthly'] = array(
			'interval' => 2635200,
			'display'  => __( 'Monthly', 'woocommerce' ),
		);
		return $schedules;
	}

	/**
	 * Create cron jobs (clear them first).
	 */
	private static function create_cron_jobs() {
		//@todo add crons here
	}


	/**
	 * Create pages that the plugin relies on, storing page IDs in variables.
	 */
	public static function create_pages() {

		$pages = apply_filters( 'geodirectory_create_pages', array(
			'page_home' => array(
				'name'    => _x( 'gd-home', 'Page slug', 'geodirectory'),
				'title'   => _x( 'GD Home page', 'Page title', 'geodirectory'),
				'content' => "[gd_popular_post_category category_limit=10]\n[gd_homepage_map width=100% height=300 scrollwheel=false]\n[gd_advanced_search]\n[gd_popular_post_view]",
			),
			'page_add' => array(
				'name'    => _x( 'add-listing', 'Page slug', 'geodirectory'),
				'title'   => _x( 'Add Listing', 'Page title', 'geodirectory'),
				'content' => '[gd_add_listing]',
			),
			'page_search' => array(
				'name'    => _x( 'search', 'Page slug', 'geodirectory'),
				'title'   => _x( 'Search page', 'Page title', 'geodirectory'),
				'content' => "[gd_advanced_search]\n[gd_loop_actions]\n[gd_loop]\n[gd_loop_paging]",
			),
			'page_terms_conditions' => array(
				'name'    => _x( 'terms-and-conditions', 'Page slug', 'geodirectory'),
				'title'   => _x( 'Terms and Conditions', 'Page title', 'geodirectory'),
				'content' => __('ENTER YOUR SITE TERMS AND CONDITIONS HERE','geodirectory'),
			),
			'page_location' => array(
				'name'    => _x( 'location', 'Page slug', 'geodirectory'),
				'title'   => _x( 'Location', 'Page title', 'geodirectory'),
				'content' => "[gd_popular_post_category category_limit=10]\n[gd_homepage_map width=100% height=300 scrollwheel=false]\n[gd_advanced_search]\n[gd_popular_post_view]",
			),
			'page_archive' => array(
				'name'    => _x( 'gd-archive', 'Page slug', 'geodirectory'),
				'title'   => _x( 'GD Archive', 'Page title', 'geodirectory'),
				'content' => "[gd_advanced_search]\n[gd_loop_actions]\n[gd_loop]\n[gd_loop_paging]",
			),
			'page_details' => array(
				'name'    => _x( 'gd-details', 'Page slug', 'geodirectory'),
				'title'   => _x( 'GD Details', 'Page title', 'geodirectory'),
				'content' => "[gd_single_slider]\n[gd_single_taxonomies]\n[gd_single_tabs]\n[gd_single_next_prev]",
			),
			
		) );

		foreach ( $pages as $key => $page ) {
			geodir_create_page( esc_sql( $page['name'] ), $key , $page['title'], $page['content']);
		}

		delete_transient( 'woocommerce_cache_excluded_uris' );
	}

	/**
	 * Default options.
	 *
	 * Sets up the default options used on the settings page.
	 */
	private static function create_options() {
		// Include settings so that we can run through defaults
		include_once( dirname( __FILE__ ) . '/class-geodir-admin-settings.php' );
		
		$current_settings = geodir_get_settings();

		$settings = GeoDir_Admin_Settings::get_settings_pages();

		foreach ( $settings as $section ) {
			if ( ! method_exists( $section, 'get_settings' ) ) {
				continue;
			}
			$subsections = array_unique( array_merge( array( '' ), array_keys( $section->get_sections() ) ) );

			foreach ( $subsections as $subsection ) {
				foreach ( $section->get_settings( $subsection ) as $value ) {
					if ( !isset($current_settings[$value['id']]) && isset( $value['default'] ) && isset( $value['id'] ) ) {
						geodir_update_option($value['id'], $value['default']);
					}
				}
			}
		}

	}

	/**
	 * Set up the database tables which the plugin needs to function.
	 *
	 * Tables:
	 *		woocommerce_attribute_taxonomies - Table for storing attribute taxonomies - these are user defined
	 *		woocommerce_termmeta - Term meta table - sadly WordPress does not have termmeta so we need our own
	 *		woocommerce_downloadable_product_permissions - Table for storing user and guest download permissions.
	 *			KEY(order_id, product_id, download_id) used for organizing downloads on the My Account page
	 *		woocommerce_order_items - Order line items are stored in a table to make them easily queryable for reports
	 *		woocommerce_order_itemmeta - Order line item meta is stored in a table for storing extra data.
	 *		woocommerce_tax_rates - Tax Rates are stored inside 2 tables making tax queries simple and efficient.
	 *		woocommerce_tax_rate_locations - Each rate can be applied to more than one postcode/city hence the second table.
	 */
	private static function create_tables() {
		global $wpdb;

		$wpdb->hide_errors();

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		dbDelta( self::get_schema() );

	}

	/**
	 * Get Table schema.
	 *
	 * https://github.com/woocommerce/woocommerce/wiki/Database-Description/
	 *
	 * A note on indexes; Indexes have a maximum size of 767 bytes. Historically, we haven't need to be concerned about that.
	 * As of WordPress 4.2, however, we moved to utf8mb4, which uses 4 bytes per character. This means that an index which
	 * used to have room for floor(767/3) = 255 characters, now only has room for floor(767/4) = 191 characters.
	 *
	 * Changing indexes may cause duplicate index notices in logs due to https://core.trac.wordpress.org/ticket/34870 but dropping
	 * indexes first causes too much load on some servers/larger DB.
	 *
	 * @return string
	 */
	private static function get_schema() {
		global $wpdb, $plugin_prefix;

		/*
         * Indexes have a maximum size of 767 bytes. Historically, we haven't need to be concerned about that.
         * As of 4.2, however, we moved to utf8mb4, which uses 4 bytes per character. This means that an index which
         * used to have room for floor(767/3) = 255 characters, now only has room for floor(767/4) = 191 characters.
         */
		$max_index_length = 191;

		$collate = '';

		if ( $wpdb->has_cap( 'collation' ) ) {
			$collate = $wpdb->get_charset_collate();
		}
		
		// Countries table
		$tables = "CREATE TABLE " . GEODIR_COUNTRIES_TABLE . " (
						CountryId smallint AUTO_INCREMENT NOT NULL ,
						Country varchar (50) NOT NULL ,
						FIPS104 varchar (2) NOT NULL ,
						ISO2 varchar (2) NOT NULL ,
						ISO3 varchar (3) NOT NULL ,
						ISON varchar (4) NOT NULL ,
						Internet varchar (2) NOT NULL ,
						Capital varchar (25) NULL ,
						MapReference varchar (50) NULL ,
						NationalitySingular varchar (35) NULL ,
						NationalityPlural varchar (35) NULL ,
						Currency varchar (30) NULL ,
						CurrencyCode varchar (3) NULL ,
						Population bigint NULL ,
						Title varchar (50) NULL ,
						Comment varchar (255) NULL ,
						PRIMARY KEY  (CountryId)) $collate; ";

		// Table for storing post custom fields - these are user defined
		$tables .= " CREATE TABLE " . GEODIR_CUSTOM_FIELDS_TABLE . " (
							  id int(11) NOT NULL AUTO_INCREMENT,
							  post_type varchar(100) NULL,
							  data_type varchar(100) NULL DEFAULT NULL,
							  field_type varchar(255) NOT NULL COMMENT 'text,checkbox,radio,select,textarea',
							  field_type_key varchar(255) NOT NULL,
							  admin_title varchar(255) NULL DEFAULT NULL,
							  frontend_desc text NULL DEFAULT NULL,
							  frontend_title varchar(255) NULL DEFAULT NULL,
							  htmlvar_name varchar(255) NULL DEFAULT NULL,
							  default_value text NULL DEFAULT NULL,
							  sort_order int(11) NOT NULL,
							  option_values text NULL DEFAULT NULL,
							  clabels text NULL DEFAULT NULL,
							  is_active tinyint(1) NOT NULL DEFAULT '1',
							  is_default tinyint(1) NOT NULL DEFAULT '0',
							  is_required tinyint(1) NOT NULL DEFAULT '0',
							  required_msg varchar(255) NULL DEFAULT NULL,
							  show_in text NULL DEFAULT NULL,
							  for_admin_use tinyint(1) NOT NULL DEFAULT '0',
							  packages text NULL DEFAULT NULL,
							  cat_sort text NULL DEFAULT NULL,
							  cat_filter text NULL DEFAULT NULL,
							  extra_fields text NULL DEFAULT NULL,
							  field_icon varchar(255) NULL DEFAULT NULL,
							  css_class varchar(255) NULL DEFAULT NULL,
							  decimal_point varchar( 10 ) NOT NULL,
							  validation_pattern varchar( 255 ) NOT NULL,
							  validation_msg text NULL DEFAULT NULL,
							  PRIMARY KEY  (id)
							  ) $collate; ";


		// Table for storing place attribute - these are user defined
		$tables .= " CREATE TABLE " . $plugin_prefix . "gd_place_detail (
						".implode (",",geodir_db_cpt_default_columns(false)).",
						".implode (",",geodir_db_cpt_default_keys(false))." 
						) $collate; ";

		// Table for storing place images - these are user defined
		$tables .= " CREATE TABLE " . GEODIR_ATTACHMENT_TABLE . " (
						ID int(11) NOT NULL AUTO_INCREMENT,
						post_id int(11) NOT NULL,
						user_id int(11) DEFAULT NULL,
						title varchar(254) NULL DEFAULT NULL,
						caption varchar(254) NULL DEFAULT NULL,
						file varchar(254) NOT NULL, 
						mime_type varchar(150) NOT NULL,
						menu_order int(11) NOT NULL DEFAULT '0',
						is_featured tinyint(1) NULL DEFAULT '0',
						is_approved tinyint(1) NULL DEFAULT '1',
						metadata text NULL DEFAULT NULL,
					    type varchar(254) NULL DEFAULT 'post_image',
						PRIMARY KEY  (ID)
						) $collate ; " ;

		// Table for storing custom sort fields
		$tables .= " CREATE TABLE " . GEODIR_CUSTOM_SORT_FIELDS_TABLE . " (
			id int(11) NOT NULL AUTO_INCREMENT,
			post_type varchar(255) NOT NULL,
			data_type varchar(255) NOT NULL,
			field_type varchar(255) NOT NULL,
			frontend_title varchar(255) NOT NULL,
			htmlvar_name varchar(255) NOT NULL,
			sort_order int(11) NOT NULL,
			is_active int(11) NOT NULL,
			is_default int(11) NOT NULL,
			default_order varchar(255) NOT NULL,
			sort_asc int(11) NOT NULL,
			sort_desc int(11) NOT NULL,
			asc_title varchar(255) NOT NULL,
			desc_title varchar(255) NOT NULL,
			PRIMARY KEY  (id)
			) $collate; " ;

		// Table for storing review info
		$tables .= " CREATE TABLE " . GEODIR_REVIEW_TABLE . " (
			id int(11) NOT NULL AUTO_INCREMENT,
			post_id int(11) DEFAULT NULL,
			post_title varchar( 255 ) NULL DEFAULT NULL,
			post_type varchar( 255 ) NULL DEFAULT NULL,
			user_id int(11) DEFAULT NULL,
			comment_id int(11) DEFAULT NULL,
			rating_ip varchar( 50 ) NULL DEFAULT NULL,
			ratings text NULL DEFAULT NULL,
			overall_rating float(11) DEFAULT NULL,
			comment_images text NULL DEFAULT NULL,
			wasthis_review int(11) NOT NULL,
			status varchar( 100 ) NOT NULL,
			post_status int(11) DEFAULT NULL,
			post_date datetime NOT NULL,
			post_city varchar(50) NULL DEFAULT NULL,
			post_region varchar(50) NULL DEFAULT NULL,
			post_country varchar(50) NULL DEFAULT NULL,
			post_latitude varchar(20) NULL DEFAULT NULL,
			post_longitude varchar(20) NULL DEFAULT NULL,
			comment_content text NULL DEFAULT NULL,
			PRIMARY KEY  (id)
			) $collate; " ;

		return $tables;
	}


	/**
	 * Show plugin changes. Code adapted from W3 Total Cache.
	 */
	public static function in_plugin_update_message( $args ) {
		$transient_name = 'wc_upgrade_notice_' . $args['Version'];

		if ( false === ( $upgrade_notice = get_transient( $transient_name ) ) ) {
			$response = wp_safe_remote_get( 'https://plugins.svn.wordpress.org/woocommerce/trunk/readme.txt' );

			if ( ! is_wp_error( $response ) && ! empty( $response['body'] ) ) {
				$upgrade_notice = self::parse_update_notice( $response['body'], $args['new_version'] );
				set_transient( $transient_name, $upgrade_notice, DAY_IN_SECONDS );
			}
		}

		echo wp_kses_post( $upgrade_notice );
	}

	/**
	 * Parse update notice from readme file.
	 *
	 * @param  string $content
	 * @param  string $new_version
	 * @return string
	 */
	private static function parse_update_notice( $content, $new_version ) {
		// Output Upgrade Notice.
		$matches        = null;
		$regexp         = '~==\s*Upgrade Notice\s*==\s*=\s*(.*)\s*=(.*)(=\s*' . preg_quote( WC_VERSION ) . '\s*=|$)~Uis';
		$upgrade_notice = '';

		if ( preg_match( $regexp, $content, $matches ) ) {
			$notices = (array) preg_split( '~[\r\n]+~', trim( $matches[2] ) );

			// Convert the full version strings to minor versions.
			$notice_version_parts  = explode( '.', trim( $matches[1] ) );
			$current_version_parts = explode( '.', WC_VERSION );

			if ( 3 !== sizeof( $notice_version_parts ) ) {
				return;
			}

			$notice_version  = $notice_version_parts[0] . '.' . $notice_version_parts[1];
			$current_version = $current_version_parts[0] . '.' . $current_version_parts[1];

			// Check the latest stable version and ignore trunk.
			if ( version_compare( $current_version, $notice_version, '<' ) ) {

				$upgrade_notice .= '</p><p class="wc_plugin_upgrade_notice">';

				foreach ( $notices as $index => $line ) {
					$upgrade_notice .= preg_replace( '~\[([^\]]*)\]\(([^\)]*)\)~', '<a href="${2}">${1}</a>', $line );
				}
			}
		}

		return wp_kses_post( $upgrade_notice );
	}

	/**
	 * Show action links on the plugin screen.
	 *
	 * @param	mixed $links Plugin Action links
	 * @return	array
	 */
	public static function plugin_action_links( $links ) {
		$action_links = array(
			'settings' => '<a href="' . admin_url( 'admin.php?page=wc-settings' ) . '" aria-label="' . esc_attr__( 'View WooCommerce settings', 'woocommerce' ) . '">' . esc_html__( 'Settings', 'woocommerce' ) . '</a>',
		);

		return array_merge( $action_links, $links );
	}

	/**
	 * Show row meta on the plugin screen.
	 *
	 * @param	mixed $links Plugin Row Meta
	 * @param	mixed $file  Plugin Base file
	 * @return	array
	 */
	public static function plugin_row_meta( $links, $file ) {
		if ( GEODIRECTORY_PLUGIN_BASENAME == $file ) {
			$row_meta = array(
				'docs'    => '<a href="' . esc_url( apply_filters( 'geodirectory_docs_url', 'https://wpgeodirectory.com/docs/' ) ) . '" aria-label="' . esc_attr__( 'View GeoDirectory documentation', 'geodirectory' ) . '">' . esc_html__( 'Docs', 'geodirectory' ) . '</a>',
				'support' => '<a href="' . esc_url( apply_filters( 'geodirectory_support_url', 'https://wpgeodirectory.com/support/' ) ) . '" aria-label="' . esc_attr__( 'Visit GeoDirectory support', 'geodirectory' ) . '">' . esc_html__( 'Support', 'geodirectory' ) . '</a>',
				'translation' => '<a href="' . esc_url( apply_filters( 'geodirectory_translation_url', 'https://wpgeodirectory.com/translate/projects' ) ) . '" aria-label="' . esc_attr__( 'View translations', 'geodirectory' ) . '">' . esc_html__( 'Translations', 'geodirectory' ) . '</a>',
			);

			return array_merge( $links, $row_meta );
		}

		return (array) $links;
	}

	/**
	 * Uninstall tables when MU blog is deleted.
	 * @param  array $tables
	 * @return string[]
	 */
	public static function wpmu_drop_tables( $tables ) {
		global $wpdb;

		$tables[] = $wpdb->prefix . 'woocommerce_sessions';
		$tables[] = $wpdb->prefix . 'woocommerce_api_keys';
		$tables[] = $wpdb->prefix . 'woocommerce_attribute_taxonomies';
		$tables[] = $wpdb->prefix . 'woocommerce_downloadable_product_permissions';
		$tables[] = $wpdb->prefix . 'woocommerce_termmeta';
		$tables[] = $wpdb->prefix . 'woocommerce_tax_rates';
		$tables[] = $wpdb->prefix . 'woocommerce_tax_rate_locations';
		$tables[] = $wpdb->prefix . 'woocommerce_order_items';
		$tables[] = $wpdb->prefix . 'woocommerce_order_itemmeta';
		$tables[] = $wpdb->prefix . 'woocommerce_payment_tokens';
		$tables[] = $wpdb->prefix . 'woocommerce_shipping_zones';
		$tables[] = $wpdb->prefix . 'woocommerce_shipping_zone_locations';
		$tables[] = $wpdb->prefix . 'woocommerce_shipping_zone_methods';

		return $tables;
	}

	/**
	 * Get slug from path
	 * @param  string $key
	 * @return string
	 */
	private static function format_plugin_slug( $key ) {
		$slug = explode( '/', $key );
		$slug = explode( '.', end( $slug ) );
		return $slug[0];
	}

	/**
	 * Install a plugin from .org in the background via a cron job (used by
	 * installer - opt in).
	 * @param string $plugin_to_install_id
	 * @param array $plugin_to_install
	 * @since 2.6.0
	 */
	public static function background_installer( $plugin_to_install_id, $plugin_to_install ) {
		// Explicitly clear the event.
		wp_clear_scheduled_hook( 'woocommerce_plugin_background_installer', func_get_args() );

		if ( ! empty( $plugin_to_install['repo-slug'] ) ) {
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
			require_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
			require_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

			WP_Filesystem();

			$skin              = new Automatic_Upgrader_Skin;
			$upgrader          = new WP_Upgrader( $skin );
			$installed_plugins = array_map( array( __CLASS__, 'format_plugin_slug' ), array_keys( get_plugins() ) );
			$plugin_slug       = $plugin_to_install['repo-slug'];
			$plugin            = $plugin_slug . '/' . $plugin_slug . '.php';
			$installed         = false;
			$activate          = false;

			// See if the plugin is installed already
			if ( in_array( $plugin_to_install['repo-slug'], $installed_plugins ) ) {
				$installed = true;
				$activate  = ! is_plugin_active( $plugin );
			}

			// Install this thing!
			if ( ! $installed ) {
				// Suppress feedback
				ob_start();

				try {
					$plugin_information = plugins_api( 'plugin_information', array(
						'slug'   => $plugin_to_install['repo-slug'],
						'fields' => array(
							'short_description' => false,
							'sections'          => false,
							'requires'          => false,
							'rating'            => false,
							'ratings'           => false,
							'downloaded'        => false,
							'last_updated'      => false,
							'added'             => false,
							'tags'              => false,
							'homepage'          => false,
							'donate_link'       => false,
							'author_profile'    => false,
							'author'            => false,
						),
					) );

					if ( is_wp_error( $plugin_information ) ) {
						throw new Exception( $plugin_information->get_error_message() );
					}

					$package  = $plugin_information->download_link;
					$download = $upgrader->download_package( $package );

					if ( is_wp_error( $download ) ) {
						throw new Exception( $download->get_error_message() );
					}

					$working_dir = $upgrader->unpack_package( $download, true );

					if ( is_wp_error( $working_dir ) ) {
						throw new Exception( $working_dir->get_error_message() );
					}

					$result = $upgrader->install_package( array(
						'source'                      => $working_dir,
						'destination'                 => WP_PLUGIN_DIR,
						'clear_destination'           => false,
						'abort_if_destination_exists' => false,
						'clear_working'               => true,
						'hook_extra'                  => array(
							'type'   => 'plugin',
							'action' => 'install',
						),
					) );

					if ( is_wp_error( $result ) ) {
						throw new Exception( $result->get_error_message() );
					}

					$activate = true;

				} catch ( Exception $e ) {
					WC_Admin_Notices::add_custom_notice(
						$plugin_to_install_id . '_install_error',
						sprintf(
							__( '%1$s could not be installed (%2$s). <a href="%3$s">Please install it manually by clicking here.</a>', 'woocommerce' ),
							$plugin_to_install['name'],
							$e->getMessage(),
							esc_url( admin_url( 'index.php?wc-install-plugin-redirect=' . $plugin_to_install['repo-slug'] ) )
						)
					);
				}

				// Discard feedback
				ob_end_clean();
			}

			wp_clean_plugins_cache();

			// Activate this thing
			if ( $activate ) {
				try {
					$result = activate_plugin( $plugin );

					if ( is_wp_error( $result ) ) {
						throw new Exception( $result->get_error_message() );
					}
				} catch ( Exception $e ) {
					WC_Admin_Notices::add_custom_notice(
						$plugin_to_install_id . '_install_error',
						sprintf(
							__( '%1$s was installed but could not be activated. <a href="%2$s">Please activate it manually by clicking here.</a>', 'woocommerce' ),
							$plugin_to_install['name'],
							admin_url( 'plugins.php' )
						)
					);
				}
			}
		}
	}
}

