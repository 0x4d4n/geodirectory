<?php
/**
 * GeoDirectory General Settings
 *
 * @author      AyeCode
 * @category    Admin
 * @package     GeoDirectory/Admin
 * @version     2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'GeoDir_Settings_General', false ) ) :

/**
 * GeoDir_Settings_General.
 */
class GeoDir_Settings_General extends GeoDir_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {



		$this->id    = 'general';
		$this->label = __( 'General', 'geodirectory' );

		add_filter( 'geodir_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
		add_action( 'geodir_settings_' . $this->id, array( $this, 'output' ) );
		add_action( 'geodir_sections_' . $this->id, array( $this, 'output_toggle_advanced' ) );
		add_action( 'geodir_settings_save_' . $this->id, array( $this, 'save' ) );
		add_action( 'geodir_sections_' . $this->id, array( $this, 'output_sections' ) );


	}

	/**
	 * Get sections.
	 *
	 * @return array
	 */
	public function get_sections() {

		$sections = array(
			''          	=> __( 'General', 'geodirectory' ),
			'location'       => __( 'Default location', 'geodirectory' ),
			'pages' 	=> __( 'Pages', 'geodirectory' ),
			'search' 	=> __( 'Search', 'geodirectory' ),
			'dummy_data' 	=> __( 'Dummy Data', 'geodirectory' ),
			'developer' 	=> __( 'Developer', 'geodirectory' ),
			'uninstall' 	=> __( 'Uninstall', 'geodirectory' ),
		);

		return apply_filters( 'geodir_get_sections_' . $this->id, $sections );
	}

	/**
	 * Output the settings.
	 */
	public function output() {
		global $current_section, $hide_save_button;

		$settings = $this->get_settings( $current_section );

		GeoDir_Admin_Settings::output_fields( $settings );

		// hide save button on dummy data page
		if ( 'dummy_data' == $current_section ) {
			$hide_save_button = true;
		}


	}


	/**
	 * Save settings.
	 */
	public function save() {
		global $current_section;

		$settings = $this->get_settings( $current_section );
		GeoDir_Admin_Settings::save_fields( $settings );
	}

	/**
	 * Get settings array.
	 *
	 * @return array
	 */
	public function get_settings( $current_section = '' ) {

		if ( 'developer' == $current_section ) {

			/**
			 * Filter GD general settings array.
			 *
			 * @since 1.0.0
			 * @package GeoDirectory
			 */
			$settings = apply_filters( 'geodir_developer_options', array(
				array(
					'title' => __( 'Uninstall Settings', 'geodirectory' ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'developer_options',
					//'desc_tip' => true,
				),

				array(
					'name'     => __( 'Advanced settings', 'geodirectory' ),
					'desc'     => __( 'Disable advanced toggle, show advanced settings at all times (not recommended).', 'geodirectory' ),
					'id'       => 'admin_disable_advanced',
					'type'     => 'checkbox',
					//'desc_tip' => true,
					//'advanced' => true
				),




				array( 'type' => 'sectionend', 'id' => 'developer_options' ),
			));
		}elseif ( 'uninstall' == $current_section ) {

			/**
			 * Filter GD general settings array.
			 *
			 * @since 1.0.0
			 * @package GeoDirectory
			 */
			$settings = apply_filters( 'geodir_uninstall_options', array(
				array(
					'title' => __( 'Uninstall Settings', 'geodirectory' ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'uninstall_options',
					//'desc_tip' => true,
				),

				array(
					'name'     => __( 'Remove Data on Uninstall?', 'geodirectory' ),
					'desc'     => __( 'Check this box if you would like GeoDirectory to completely remove all of its data when the plugin is deleted.', 'geodirectory' ),
					'id'       => 'admin_uninstall',
					'type'     => 'checkbox',
					//'desc_tip' => true,
					//'advanced' => true
				),




				array( 'type' => 'sectionend', 'id' => 'uninstall_options' ),
			));
		}
		elseif ( 'dummy_data' == $current_section ) {

			/**
			 * Filter GD general settings array.
			 *
			 * @since 1.0.0
			 * @package GeoDirectory
			 */
			$settings = apply_filters( 'geodir_dummy_data', array(
				array(
					'title' => __( 'Dummy data installer', 'geodirectory' ),
					'type'  => 'title',
					'desc'  => '*Hint*: Installing our Advanced Search addon FIRST will add extra search fields to non-default data types.',
					'id'    => 'dummy_data',
					//'desc_tip' => true,
				),

				array(
					'name' => '',
					'desc' => '',
					'id' => 'geodir_dummy_data_installer',
					'type' => 'dummy_installer',
					'css' => 'min-width:300px;',
					'std' => '40'
				),



				array( 'type' => 'sectionend', 'id' => 'dummy_data' ),
			));
		}
		else if ( 'pages' == $current_section ) {
			/**
			 * Filter GD general settings array.
			 *
			 * @since 1.0.0
			 * @package GeoDirectory
			 */
			$settings = apply_filters( 'geodir_page_options', array(
				array(
					'title' => __( 'Page Settings', 'geodirectory' ),
					'type'  => 'title',
					//'desc'  => 'Drag the map or the marker to set the city/town you wish to use as the default location, then click save changes.',
					'id'    => 'page_options',
					//'desc_tip' => true,
				),
				array(
					'name'     => __( 'Location page', 'geodirectory' ),
					'desc'     => __( 'Select the page to use for locations', 'geodirectory' ),
					'id'       => 'page_location',
					'type'     => 'single_select_page',
					'class'      => 'geodir-select',
					'desc_tip' => true,
				),
				array(
					'name'     => __( 'Add listing page', 'geodirectory' ),
					'desc'     => __( 'Select the page to use for adding listings', 'geodirectory' ),
					'id'       => 'page_add',
					'type'     => 'single_select_page',
					'class'      => 'geodir-select',
					'desc_tip' => true,
				),
				array(
					'name'     => __( 'Search Page', 'geodirectory' ),
					'desc'     => __( 'Select the page to use as the GD search page', 'geodirectory' ),
					'id'       => 'page_search',
					'type'     => 'single_select_page',
					'class'      => 'geodir-select',
					'desc_tip' => true,
				),
				array(
					'name'     => __( 'Terms and Conditions page', 'geodirectory' ),
					'desc'     => __( 'Select the page to use for your terms and conditions.', 'geodirectory' ),
					'id'       => 'page_terms_conditions',
					'type'     => 'single_select_page',
					'class'      => 'geodir-select',
					'desc_tip' => true,
				),
				array( 'type' => 'sectionend', 'id' => 'page_options' ),

				array(
					'title' => __( 'Template Page Settings', 'geodirectory' ),
					'type'  => 'title',
					'desc'  => 'Template pages are used to design the respective pages and should never be linked to directly.',
					'id'    => 'page_template_options',
					'desc_tip' => true,
				),

				array(
					'name'     => __( 'Details Page', 'geodirectory' ),
					'desc'     => __( 'Select the page to use as the GD details page template', 'geodirectory' ),
					'id'       => 'page_details',
					'type'     => 'single_select_page',
					'class'      => 'geodir-select',
					'desc_tip' => true,
				),
				array(
					'name'     => __( 'Archive page', 'geodirectory' ),
					'desc'     => __( 'Select the page to use for GD archives such as taxonomy and CPT pages', 'geodirectory' ),
					'id'       => 'page_archive',
					'type'     => 'single_select_page',
					'class'      => 'geodir-select',
					'desc_tip' => true,
				),
				array(
					'name'     => __( 'Archive page item', 'geodirectory' ),
					'desc'     => __( 'Select the page to use for GD archive items, this is the item template used on taxonomy and CPT pages', 'geodirectory' ),
					'id'       => 'page_archive_item',
					'type'     => 'single_select_page',
					'class'      => 'geodir-select',
					'desc_tip' => true,
				),





				array( 'type' => 'sectionend', 'id' => 'page_template_options' ),
			));
		}

		else if ( 'search' == $current_section ) {
			/**
			 * Filter GD general settings array.
			 *
			 * @since 1.0.0
			 * @package GeoDirectory
			 */
			$settings = apply_filters( 'geodir_search_options', array(
				array(
					'title' => __( 'Search bar settings', 'geodirectory' ),
					'type'  => 'title',
					'id'    => 'search_options',
				),

				array(
					'name' => __('Search field placeholder text', 'geodirectory'),
					'desc' => __('Show the search text box `placeholder` value on search form.', 'geodirectory'),
					'id' => 'search_default_text',
					'type' => 'text',
					'placeholder' => get_search_default_text(),
					'desc_tip' => true,
					'default'  => '',
					'advanced' => false
				),
				array(
					'name' => __('Near field placeholder text', 'geodirectory'),
					'desc' => __('Show the near text box \'placeholder\' value on search form.', 'geodirectory'),
					'id' => 'search_default_near_text',
					'type' => 'text',
					'placeholder' => get_search_default_near_text(),
					'desc_tip' => true,
					'default'  => '',
					'advanced' => false
				),
				array(
					'name' => __('Search button label', 'geodirectory'),
					'desc' => __('Show the search button label on search form. You can use font awesome unicode here (minus the &) from:', 'geodirectory')." <a href='http://fontawesome.io/cheatsheet/' target='_blank'>http://fontawesome.io/cheatsheet/</a>",
					'id' => 'search_default_button_text',
					'type' => 'text',
					'placeholder' => get_search_default_button_text(),
					'desc_tip' => true,
					'default'  => '',
					'advanced' => true
				),
				array(
					'name' => __('Advanced pagination', 'geodirectory'),
					'desc' => __('This will add extra pagination info like "Showing listings x-y of z" after/before pagination.', 'geodirectory'),
					'id' => 'search_advanced_pagination',
					'type' => 'select',
					'class' => 'geodir-select',
					'options' => array(
						'' => __('Never Display', 'geodirectory'),
						'after' => __('After Pagination', 'geodirectory'),
						'before' => __('Before Pagination', 'geodirectory')
					),
					'desc_tip' => true,
					'default'  => '',
					'advanced' => true
				),


				array( 'type' => 'sectionend', 'id' => 'search_options' ),

				array(
					'title' => __( 'Search results settings', 'geodirectory' ),
					'type'  => 'title',
					'id'    => 'search_results_options',
				),

				array(
					'name' => __('Search near radius', 'geodirectory'),
					'desc' => __('Limits the search radius to X miles/km (lower numbers help with speed) ', 'geodirectory'),
					'id' => 'search_radius',
					'type' => 'number',
					'default'  => '5',
					'desc_tip' => true,
					'advanced' => false
				),
				array(
					'name' => __('Search distances', 'geodirectory'),
					'desc' => __('Show search distances in miles or km', 'geodirectory'),
					'id' => 'search_distance_long',
					'type' => 'select',
					'class' => 'geodir-select',
					'options' => array(
						'miles' => __('Miles', 'geodirectory'),
						'km' => __('Kilometers', 'geodirectory')
					),
					'desc_tip' => true,
					'default'  => 'miles',
					'advanced' => false
				),
				array(
					'name' => __('Search distances short', 'geodirectory'),
					'desc' => __('If distance is less than 0.01 show distance in meters or feet', 'geodirectory'),
					'id' => 'search_distance_short',
					'type' => 'select',
					'class' => 'geodir-select',
					'options' => array(
						'feet' => __('Feet', 'geodirectory'),
						'meters' => __('Meters', 'geodirectory')
					),
					'default'  => 'feet',
					'desc_tip' => true,
					'advanced' => false
				),
				array(
					'name' => __('Search near additional', 'geodirectory'),
					'desc' => __('This is useful if your directory is limited to one location such as: New York or Australia (this setting should be blank if using default country, regions etc with multilocation addon as it will automatically add them)', 'geodirectory'),
					'id' => 'search_near_addition',
					'type' => 'text',
					'placeholder' => __('New York','goedirectory'),
					'default'  => '',
					'desc_tip' => true,
					'advanced' => true
				),
				array(
					'name' => __('Individual word search limit', 'geodirectory'),
					'desc' => __('With this option you can limit individual words being searched for, for example searching for `Jo Brown` would return results with words like `Jones`, you can exclude these types of small character words if you wish.', 'geodirectory'),
					'id' => 'search_word_limit',
					'type' => 'select',
					'class' => 'geodir-select',
					'options' => array_unique(array(
						'0' => __('Disabled', 'geodirectory'),
						'1' => __('1 Character words excluded', 'geodirectory'),
						'2' => __('2 Character words and less excluded', 'geodirectory'),
						'3' => __('3 Character words and less excluded', 'geodirectory'),
					)),
					'default'  => '0',
					'desc_tip' => true,
					'advanced' => true
				),

				array( 'type' => 'sectionend', 'id' => 'search_results_options' ),


			));
		}
		else if ( 'location' == $current_section ) {
			/**
			 * Filter GD general settings array.
			 *
			 * @since 1.0.0
			 * @package GeoDirectory
			 */
			$settings = apply_filters( 'geodir_default_location', array(
				array(
					'title' => __( 'Set default location', 'geodirectory' ),
					'type'  => 'title',
					'desc'  => 'Drag the map or the marker to set the city/town you wish to use as the default location, then click save changes.',
					'id'    => 'default_location',
					//'desc_tip' => true,
				),

				array(
					'name'     => __( 'City', 'geodirectory' ),
					'desc'     => __( 'The default location city name.', 'geodirectory' ),
					'id'       => 'default_location_city',
					'type'     => 'text',
					'css'      => 'min-width:300px;',
					'desc_tip' => true,
					'default'  => 'Philadelphia',
					'advanced' => true
				),
				array(
					'name'     => __( 'Region', 'geodirectory' ),
					'desc'     => __( 'The default location region name.', 'geodirectory' ),
					'id'       => 'default_location_region',
					'type'     => 'text',
					'css'      => 'min-width:300px;',
					'desc_tip' => true,
					'default'  => 'Pennsylvania',
					'advanced' => true
				),
				array(
					'name'     => __( 'Country', 'geodirectory' ),
					'desc'     => __( 'The default location country name.', 'geodirectory' ),
					'id'       => 'default_location_country',
					'css'      => 'min-width:300px;',
					'desc_tip' => true,
					'advanced' => true,
					'type'       => 'single_select_country',
					'class'      => 'geodir-select',
					'default'  => 'United States',
					'options'    => geodir_get_countries()

				),

				array(
					'name'     => __( 'City Latitude', 'geodirectory' ),
					'desc'     => __( 'The latitude of the default location.', 'geodirectory' ),
					'id'       => 'default_location_latitude',
					'type' => 'number',
					'custom_attributes' => array(
						'min'           => '-90',
						'max'           => '90',
						'step'          => 'any',
					),
					'desc_tip' => true,
					'default'  => '39.9523894183957',
					'advanced' => true
				),

				array(
					'name'     => __( 'City Longitude', 'geodirectory' ),
					'desc'     => __( 'The longitude of the default location.', 'geodirectory' ),
					'id'       => 'default_location_longitude',
					'type' => 'number',
					'custom_attributes' => array(
						'min'           => '-180',
						'max'           => '180',
						'step'          => 'any',
					),
					'desc_tip' => true,
					'default'  => '-75.16359824536897',
					'advanced' => true
				),

				array(
					'id'       => 'default_location_map',
					'type'     => 'default_location_map',
				),

				array( 'type' => 'sectionend', 'id' => 'default_location' ),
			));
		}else{
			/**
			 * Filter GD general settings array.
			 *
			 * @since 1.0.0
			 * @package GeoDirectory
			 */
			$settings = apply_filters( 'geodir_general_options', array(
				array(
					'title' => __( 'Site Settings', 'geodirectory' ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'general_options'
				),

				array(
					'name'       => __( 'Restrict wp-admin', 'geodirectory' ),
					'desc'       => __( 'The user roles that should be restricted from the wp-admin area.', 'geodirectory' ),
					'id'         => 'admin_blocked_roles',
					'default'    => array('subscriber'),
					'type'       => 'multiselect',
					'class'      => 'geodir-select',
					'options'    => geodir_user_roles(array('administrator')),
					'desc_tip'   => true,
					'docs'       => "https://wpgeodirectory.com/docs/",
					//'advanced' => true
				),


				array( 'type' => 'sectionend', 'id' => 'general_options' ),

				array(
					'title' => __( 'Listing Settings', 'geodirectory' ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'general_options_add'
				),


				array(
					'name' => __( 'User deleted posts', 'geodirectory' ),
					'desc' => __( 'If checked a user deleted post will go to trash, otherwise it will be permanently deleted', 'geodirectory' ),
					'id'   => 'user_trash_posts',
					'type' => 'checkbox',
					'default'  => '1',
					'docs'       => "https://wpgeodirectory.com/docs/",

				),
				array(
					'name'       => __( 'New listing default status', 'geodirectory' ),
					'desc'       => __( 'This is the post status a new listing will get when submitted from the frontend.', 'geodirectory' ),
					'id'         => 'default_status',
					'default'    => 'pending',
					'type'       => 'select',
					'class'      => 'geodir-select',
					'options' => array_unique(array(
						'pending' => __('Pending Review', 'geodirectory'),
						'publish' => __('Publish', 'geodirectory'),

					)),
					'desc_tip' => true,
					//'advanced' => true
				),
				array(
					'name' => __( 'Allow posting without logging in?', 'geodirectory' ),
					'desc' => __( 'If checked non logged in users will be able to post listings from the frontend.', 'geodirectory' ),
					'id'   => 'post_logged_out',
					'type' => 'checkbox',
					'default'  => '0',
					'advanced' => true
				),

				array(
					'name' => __( 'Show preview button?', 'geodirectory' ),
					'desc' => __( 'If checked a preview button will be shown on the add listing page so uses can preview their post.', 'geodirectory' ),
					'id'   => 'post_preview',
					'type' => 'checkbox',
					'default'  => '1',
					'advanced' => true
				),

				array(
					'name' => __( 'Max upload file size(in mb)', 'geodirectory' ),
					'desc' => __( '(Maximum upload file size in MB, 1 MB = 1024 KB. Must be greater then 0(ZERO), for ex: 2. This setting will overwrite the max upload file size limit in image/file upload & import listings for entire GeoDirectory core + GeoDirectory plugins.)', 'geodirectory' ).wp_max_upload_size(),
					'id'   => 'upload_max_filesize',
					'type' => 'number',
					'css'  => 'min-width:300px;',
					'default'  => '2',
					'desc_tip' => true,
					'advanced' => true
				),

				array( 'type' => 'sectionend', 'id' => 'general_options_add' ),

				array(
					'title' => __( 'Map Settings', 'geodirectory' ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'general_options_map'
				),

				self::get_google_maps_api_key_setting(),

				self::get_maps_api_setting(),

				self::get_map_language_setting(),

				array(
					'name'     => __( 'Default marker icon', 'geodirectory' ),
					'desc'     => __( 'This is the marker icon used if the category does not have a marker icon set.', 'geodirectory' ),
					'id'       => 'map_default_marker_icon',
					'type'     => 'image',
					'default'  => '',
					'desc_tip' => true,
					'advanced' => true
				),

				array(
					'name' => __('Enable map cache', 'geodirectory'), // @todo we ned to port this over from GDv1
					'desc' => __('This will cache the map JSON for 24 hours or until a GD listing is saved.', 'geodirectory'),
					'id' => 'map_cache',
					'type' => 'checkbox',
					'default'  => '0',
					'desc_tip' => false,
					'advanced' => true
				),

				array( 'type' => 'sectionend', 'id' => 'general_options_map' ),

				array(
					'title' => __( 'Tracking Settings', 'geodirectory' ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'general_options_tracking',
					'advanced' => true
				),

				array(
					'name' => __( 'Allow Usage Tracking?', 'geodirectory' ),
					'desc' => sprintf( __( 'Want to help make GeoDirectory even more awesome? Allow GeoDirectory to collect non-sensitive diagnostic data and usage information. %1$sFind out more%2$s.', 'geodirectory' ), '<a href="https://wpgeodirectory.com/usage-tracking/" target="_blank">', '</a>' ),
					'id'   => 'usage_tracking',
					'type' => 'checkbox',
					'default'  => '1', // @todo remove this after beta release
					'advanced' => true
				),

				array( 'type' => 'sectionend', 'id' => 'general_options_map' ),

			) );/* General Options End*/
		}


		return apply_filters( 'geodir_get_settings_' . $this->id, $settings );
	}

	public static function get_map_language_setting(){
		return array(
			'name'       => __( 'Default map language', 'geodirectory' ),
			'desc'       => __( 'URLs will only be in one language, this will determine the language location slugs get. You should avoid changing this after listings have been added.', 'geodirectory' ),
			'id'         => 'map_language',
			'default'    => 'en',
			'type'       => 'select',
			'class'      => 'geodir-select',
			'options'    => self::supported_map_languages(),
			'desc_tip' => true,
			'advanced' => true
		);
	}

	public static function get_maps_api_setting(){
		return array(
			'name'       => __( 'Maps API', 'geodirectory' ),
			'desc'       => __( "- Google Maps API will force to load Google JS library only.
- OpenStreetMap API will force to load OpenStreetMap JS library only.
- Load Automatic will load Google JS library first, but if Google maps JS library not loaded it then loads the OpenStreetMap JS library to load the maps (recommended for regions where Google maps banned).
- Disable Maps will disable and hides maps for entire site.", 'geodirectory' ),
			'id'         => 'maps_api',
			'default'    => 'auto',
			'type'       => 'select',
			'class'      => 'geodir-select',
			'options'    => self::supported_maps_apis(),
			'desc_tip' => true,
			'advanced' => true
		);
	}
	
	
	public static function get_google_maps_api_key_setting(){
		return array(
			'name' => __( 'Google Maps API KEY', 'geodirectory' ),
			'desc' => __( 'Google Maps <b>requires</b> an API key. Use the button to create a properly configured key using your Google account. </br>Advanced: Open Street Map (OSM) API options.', 'geodirectory' ),
			'id'   => 'google_maps_api_key',
			'type' => 'map_key',
			'default'  => '',
			'desc_tip' => true,
			//'advanced' => true
		);
	}

	/**
	 * Output a color picker input box.
	 *
	 * @param mixed $name
	 * @param string $id
	 * @param mixed $value
	 * @param string $desc (default: '')
	 */
	public function color_picker( $name, $id, $value, $desc = '' ) {
		echo '<div class="color_box">' . geodir_help_tip( $desc ) . '
			<input name="' . esc_attr( $id ) . '" id="' . esc_attr( $id ) . '" type="text" value="' . esc_attr( $value ) . '" class="colorpick" /> <div id="colorPickerDiv_' . esc_attr( $id ) . '" class="colorpickdiv"></div>
		</div>';
	}

	/**
	 * The list of supported maps api's.
	 * @return array
	 */
	public static function supported_maps_apis(){
		return array(
			'auto' => __('Automatic (recommended)', 'geodirectory'),
			'google' => __('Google Maps API', 'geodirectory'),
			'osm' => __('OpenStreetMap API', 'geodirectory'),
			'none' => __('Disable Maps', 'geodirectory'),
		);
	}

	/**
	 * The list of supported Google maps api languages.
	 *
	 * @return array
	 */
	public static function supported_map_languages(){
		return array(
			'ar' => __('ARABIC', 'geodirectory'),
			'eu' => __('BASQUE', 'geodirectory'),
			'bg' => __('BULGARIAN', 'geodirectory'),
			'bn' => __('BENGALI', 'geodirectory'),
			'ca' => __('CATALAN', 'geodirectory'),
			'cs' => __('CZECH', 'geodirectory'),
			'da' => __('DANISH', 'geodirectory'),
			'de' => __('GERMAN', 'geodirectory'),
			'el' => __('GREEK', 'geodirectory'),
			'en' => __('ENGLISH', 'geodirectory'),
			'en-AU' => __('ENGLISH (AUSTRALIAN)', 'geodirectory'),
			'en-GB' => __('ENGLISH (GREAT BRITAIN)', 'geodirectory'),
			'es' => __('SPANISH', 'geodirectory'),
			'fa' => __('FARSI', 'geodirectory'),
			'fi' => __('FINNISH', 'geodirectory'),
			'fil' => __('FILIPINO', 'geodirectory'),
			'fr' => __('FRENCH', 'geodirectory'),
			'gl' => __('GALICIAN', 'geodirectory'),
			'gu' => __('GUJARATI', 'geodirectory'),
			'hi' => __('HINDI', 'geodirectory'),
			'hr' => __('CROATIAN', 'geodirectory'),
			'hu' => __('HUNGARIAN', 'geodirectory'),
			'id' => __('INDONESIAN', 'geodirectory'),
			'it' => __('ITALIAN', 'geodirectory'),
			'iw' => __('HEBREW', 'geodirectory'),
			'ja' => __('JAPANESE', 'geodirectory'),
			'kn' => __('KANNADA', 'geodirectory'),
			'ko' => __('KOREAN', 'geodirectory'),
			'lt' => __('LITHUANIAN', 'geodirectory'),
			'lv' => __('LATVIAN', 'geodirectory'),
			'ml' => __('MALAYALAM', 'geodirectory'),
			'mr' => __('MARATHI', 'geodirectory'),
			'nl' => __('DUTCH', 'geodirectory'),
			'no' => __('NORWEGIAN', 'geodirectory'),
			'pl' => __('POLISH', 'geodirectory'),
			'pt' => __('PORTUGUESE', 'geodirectory'),
			'pt-BR' => __('PORTUGUESE (BRAZIL)', 'geodirectory'),
			'pt-PT' => __('PORTUGUESE (PORTUGAL)', 'geodirectory'),
			'ro' => __('ROMANIAN', 'geodirectory'),
			'ru' => __('RUSSIAN', 'geodirectory'),
			'sk' => __('SLOVAK', 'geodirectory'),
			'sl' => __('SLOVENIAN', 'geodirectory'),
			'sr' => __('SERBIAN', 'geodirectory'),
			'sv' => __('	SWEDISH', 'geodirectory'),
			'tl' => __('TAGALOG', 'geodirectory'),
			'ta' => __('TAMIL', 'geodirectory'),
			'te' => __('TELUGU', 'geodirectory'),
			'th' => __('THAI', 'geodirectory'),
			'tr' => __('TURKISH', 'geodirectory'),
			'uk' => __('UKRAINIAN', 'geodirectory'),
			'vi' => __('VIETNAMESE', 'geodirectory'),
			'zh-CN' => __('CHINESE (SIMPLIFIED)', 'geodirectory'),
			'zh-TW' => __('CHINESE (TRADITIONAL)', 'geodirectory'),
		);
	}


}

endif;

return new GeoDir_Settings_General();
