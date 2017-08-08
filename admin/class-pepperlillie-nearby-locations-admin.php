<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://www.pepperlillie.com/
 * @since      1.0.0
 *
 * @package    Pepperlillie_Nearby_Locations
 * @subpackage Pepperlillie_Nearby_Locations/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Pepperlillie_Nearby_Locations
 * @subpackage Pepperlillie_Nearby_Locations/admin
 * @author     Aaron Frey <aaron.frey@gmail.com>
 */
class Pepperlillie_Nearby_Locations_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style('jquery-ui', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css', array(), $this->version, 'all');

		wp_enqueue_style('shared', plugin_dir_url(dirname(__FILE__)) . 'shared/css/pepperlillie-nearby-locations-shared.css', array(), $this->version, 'all');

		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/pepperlillie-nearby-locations-admin.css', array(), $this->version, 'all');
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		// get Google Maps API key
		$api_key = get_option('plnl-google-api-key');
		if ($api_key) {
			wp_enqueue_script('google-maps', "https://maps.googleapis.com/maps/api/js?key=$api_key", array(), $this->version, false);
		}

		wp_enqueue_script('jquery-ui', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js', array('jquery'), $this->version, false);
		wp_enqueue_script('jquery-validate', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.17.0/jquery.validate.min.js', array('jquery'), $this->version, false);
		wp_enqueue_script('shared', plugin_dir_url(dirname(__FILE__)) . 'shared/js/pepperlillie-nearby-locations-shared.js', array('jquery'), $this->version, false);

		wp_localize_script('shared', 'myVars', array(
			'ajaxUrl' => admin_url('admin-ajax.php'),
			'pluginsUrl' => plugins_url(),
		));

		wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/pepperlillie-nearby-locations-admin.js', array('jquery'), $this->version, false);
	}

	public function pepperlillie_nearby_locations_page() {
    add_menu_page(
      'Nearby Locations',
      'Locations',
      'manage_options',
      plugin_dir_path(__FILE__) . 'partials/pepperlillie-nearby-locations-admin-display.php',
      null,
      'dashicons-location'
    );

		add_submenu_page(
			plugin_dir_path(__FILE__) . 'partials/pepperlillie-nearby-locations-admin-display.php',
			'Location Types',
			'Location Types',
			'manage_options',
			plugin_dir_path(__FILE__) . 'partials/pepperlillie-nearby-locations-admin-types-display.php'
		);

		add_submenu_page(
			plugin_dir_path(__FILE__) . 'partials/pepperlillie-nearby-locations-admin-display.php',
			'Settings',
			'Settings',
			'manage_options',
			plugin_dir_path(__FILE__) . 'partials/pepperlillie-nearby-locations-admin-settings-display.php'
		);

	}	

	public function pepperlillie_nearby_locations_process_ajax() {

		$callback = sanitize_text_field($_POST['callback']);

		if (isset($callback)) {
			if($callback === 'add_new_location') {
				$this->add_new_location();
			} elseif($callback === 'read_locations') {
				$this->read_locations();
			} elseif($callback === 'add_new_type') {
				$this->add_new_type();
			} elseif($callback === 'update_settings') {
				$this->update_settings();
			} elseif ($callback === 'remove_center_location') {
				$this->remove_center_location();
			}
		}
		die();
	}

	private function add_new_type() {

		$section_id = absint($_POST['section_id']);
		$section_name = sanitize_text_field(stripslashes($_POST['name']));
		$section_order = absint($_POST['order']);

    global $wpdb;
		$table_name = $wpdb->prefix . "plnl_sections";

		if (!empty($section_id)) {
	    $wpdb->update(
	    	$table_name,
	    	array(
	      	'name' => $section_name,
	      	'order' => $section_order,
	    	),
	    	array('id' => $section_id)
	    );
		} else {
	    $wpdb->insert($table_name, array(
	      'name' => $section_name,
	      'order' => $section_order,
	    ));
		}
	}

  private function add_new_location() {

  	$location_name = sanitize_text_field($_POST['location_name']);
  	$formatted_name = sanitize_text_field($_POST['formatted_name']);
  	$lat = floatval($_POST['lat']);
  	$lng = floatval($_POST['lng']);
  	$section_id = absint($_POST['section_id']);

    global $wpdb;
		$table_name = $wpdb->prefix . "plnl_locations"; 
    $result = $wpdb->insert($table_name, array(
    	'section_id' => $section_id,
      'name' => stripslashes($location_name),
      'formatted' => $formatted_name,
      'lat' => $lat,
      'lng' => $lng,
      'post_date' => date('Y-m-d H:i:s'),
    ));
    echo json_encode($result);
  }

  private function read_locations() {

  	$response = [
  		'locations' => '',
  		'center' => ''
  	];

		global $wpdb;
		$table_name = $wpdb->prefix . "plnl_sections"; 
		$location_types = $wpdb->get_results("SELECT * FROM $table_name ORDER BY `order` ASC", OBJECT);

		$join_table_name = $wpdb->prefix . "plnl_locations"; 
		$response['locations'] = $wpdb->get_results("
		  SELECT `locations`.*, `sections`.name `section_name`
		  FROM $table_name `sections`, $join_table_name `locations`
		  WHERE `locations`.`section_id` = `sections`.`id`
		  ORDER BY `sections`.`order` ASC, `locations`.name
		", OBJECT);

		$center = get_option('plnl-center-address');
		if ($center) {
			$response['center'] = $center;
		}

		echo json_encode($response);
  }

  private function update_settings() {
  	
  	$api_key = sanitize_text_field($_POST['api-key']);
  	update_option('plnl-google-api-key', $api_key);

  	$center_address = $_POST['center-address'];

  	if ($center_address) {
	   	$center_address = [
	  		'coords' => [
	  			'lat' => floatval($center_address['coords']['lat']),
	  			'lng' => floatval($center_address['coords']['lng']),
	  		],
	  		'address' => sanitize_text_field($center_address['address']),
	  	];
  		update_option('plnl-center-address', $center_address);
  	}
  }

  private function remove_center_location() {
  	delete_option('plnl-center-address');
  }

}