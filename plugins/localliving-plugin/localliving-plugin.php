<?php
/*
 * Plugin Name: LocalLiving Plugin
 * Description: Plugin for Administrators of LocalLiving.
 * Author: Tuan Nguyen redWEB
 * Version: 1.0
*/

include_once("iTravelAPI/iTravelAPI.php");

global $localliving_plg_db_version;
$localliving_plg_db_version = '1.0';

/*
|--------------------------------------------------------------------------
| CONSTANTS
|--------------------------------------------------------------------------
*/
if (!defined('LL_PLUGIN_URL')) {
    define('LL_PLUGIN_URL', plugin_dir_url(__FILE__));
    define('THEME_URL', get_template_directory_uri());
    define('PDF_LOGS', WP_PLUGIN_DIR . '/localliving-plugin/pdf_logs/');
	define('CSV_LOGS', WP_PLUGIN_DIR . '/localliving-plugin/csv_logs/');
	define('COMPRESS_IMAGES_LOGS', WP_PLUGIN_DIR . '/localliving-plugin/compress_images_logs/');
}

/*
|--------------------------------------------------------------------------
| MAIN CLASS
|--------------------------------------------------------------------------
*/
class localliving_plugin
{
    
    /*--------------------------------------------*
     * Constructor
     *--------------------------------------------*/
    public function __construct()
    {
        add_action('init', array( &$this, 'init_session'));
        add_action('init', function () {
            ob_start();
        });
        add_action('wp_logout', array( &$this, 'destroy_session' ));
        add_action('admin_menu', array( &$this,'localliving_register_menu'));
        add_action('admin_enqueue_scripts', array( &$this,'enqueue_script_style'));
	    add_action ( 'admin_enqueue_scripts', function () {
		    if (is_admin ())
			    wp_enqueue_media ();
	    } );
        add_action('wp_ajax_add_to_cart', array( &$this,'add_to_cart'));
        add_action('wp_ajax_remove_from_cart', array( &$this,'remove_from_cart'));
		add_action('wp_ajax_toggle_exception',array( &$this, 'toggle_exception' ));
		add_action('wp_ajax_toggle_generate_csv_mode', array( &$this, 'toggle_generate_csv_mode' ));
		add_action('wp_ajax_manual_edit_offer_status', array( &$this, 'manual_edit_offer_status' ));
        add_action('localliving_plg_daily_cron', array( &$this,'ll_daily_cron'));
        register_activation_hook(__FILE__, array( &$this,'localliving_plg_db_install'));
    }
    
    public function localliving_register_menu()
    {
        add_menu_page(
            'Local Living Dashboard',
            'Local Living',
            'manage_options',
            'localliving',
            array( &$this,'localliving_create_dashboard'),
            LL_PLUGIN_URL . '/assets/images/icon.png',
            2
        );
        add_submenu_page(
            "localliving",
            "Tilbudsgenerator",
            "Tilbudsgenerator",
            'manage_options',
            "localliving"
        );
        add_submenu_page(
            "localliving",
            "Tilbud",
            "Tilbud",
            'manage_options',
            "tilbud",
            function () {
                include_once('tilbud.php');
            }
        );
        add_submenu_page(
            "localliving",
            "Ferieboliger",
            "Ferieboliger",
            'manage_options',
            "ferieboliger",
            function () {
                include_once('ferieboliger.php');
            }
        );
        add_submenu_page(
            null,
            'Generer PDF',
            'Generer PDF',
            'manage_options',
            'generer_pdf',
            function () {
                include_once('generer_pdf.php');
            }
        );
        add_submenu_page(
            null,
            'Generer PDF',
            'Generer PDF',
            'manage_options',
            'pdf_files_01',
            function () {
                include_once('pdf_files_01.php');
            }
        );
	    add_submenu_page(
		    null,
		    'Rediger Feriebolig',
		    'Rediger Feriebolig',
		    'manage_options',
		    'rediger_feriebolig',
		    function () {
			    include_once('rediger_feriebolig.php');
		    }
	    );
	    add_submenu_page(
		    null,
		    'Download PDF',
		    'Download PDF',
		    'manage_options',
		    'download_pdf',
		    function () {
			    include_once('download_pdf.php');
		    }
	    );
    }
    
    public function localliving_create_dashboard()
    {
        include_once('localliving-dashboard.php');
    }
    
    public function init_session()
    {
        if (!session_id()) {
            session_start();
        }
    }
    
    public function destroy_session()
    {
        unset($_SESSION['localliving_cart']);
    }
    
    public function add_to_cart()
    {
        $dateFrom = $_POST['dateFrom'] == '' ?
            date('d/m/Y') : $_POST['dateFrom'];
        $dateTo   = $_POST['dateTo'] == '' ?
            date('d/m/Y', strtotime('+7 days')) : $_POST['dateTo'];
        $dateRange = implode('-', array($dateFrom, $dateTo));
        
        if (isset($_POST['objectId']) && isset($_POST['unitId'])) {
            $objectId = $_POST['objectId'];
            $unitId   = $_POST['unitId'];
            
            if (is_array($unitId)) {
                $_SESSION['localliving_cart'][$dateRange][$objectId][] = $objectId;
                
                foreach ($unitId as $id) {
                    $_SESSION['localliving_cart'][$dateRange][$objectId][] = $id;
                }
                $_SESSION['localliving_cart'][$dateRange][$objectId]
                    = array_unique($_SESSION['localliving_cart'][$dateRange][$objectId]);
            } else {
                $_SESSION['localliving_cart'][$dateRange][$objectId][] = $unitId;
            }
        } else {
            $objectId = $_POST['objectId'];
    
            $_SESSION['localliving_cart'][$dateRange][$objectId][] = $objectId;
        }
        
        $total = 0;

        if (isset($_SESSION['localliving_cart'])) {
            $cart = $_SESSION['localliving_cart'];
            
            foreach ($cart as $cartItem) {
                $total += count($cartItem);
            }
        }

        $result = array(
            'total' => $total,
            'cart'  => $_SESSION['localliving_cart']
        );

        echo json_encode($result);
        die;
    }
    
    public function remove_from_cart()
    {
        $dateFrom = $_POST['dateFrom'] == '' ?
            date('d/m/Y') : $_POST['dateFrom'];
        $dateTo   = $_POST['dateTo'] == '' ?
            date('d/m/Y', strtotime('+7 days')) : $_POST['dateTo'];
        $dateRange = implode('-', array($dateFrom, $dateTo));
        
        if (isset($_POST['objectId']) && isset($_POST['unitId'])) {
            $objectId = $_POST['objectId'];
            $unitId   = $_POST['unitId'];
    
            if (is_array($unitId)) {
                unset($_SESSION['localliving_cart'][$dateRange][$objectId]);
            } else {
                $removeKey = array_search($unitId, $_SESSION['localliving_cart'][$dateRange][$objectId]);
                unset($_SESSION['localliving_cart'][$dateRange][$objectId][$removeKey]);
                
                $objectIdIsOnlyInCart = count($_SESSION['localliving_cart'][$dateRange][$objectId]) === 1
                    && in_array($objectId, $_SESSION['localliving_cart'][$dateRange][$objectId]);
                
                if (count($_SESSION['localliving_cart'][$dateRange][$objectId]) === 0
                || $objectIdIsOnlyInCart) {
                    unset($_SESSION['localliving_cart'][$dateRange][$objectId]);
                }
            }
        } else {
            unset($_SESSION['localliving_cart'][$dateRange]);
        }
    
        $total = 0;
    
        if (isset($_SESSION['localliving_cart'])) {
            $cart = $_SESSION['localliving_cart'];
        
            foreach ($cart as $cartItem) {
                $total += count($cartItem);
            }
        }
    
        $result = array(
            'total' => $total,
            'cart'  => $_SESSION['localliving_cart']
        );
    
        echo json_encode($result);
        die;
    }
	
	public function toggle_exception() {
		$supplierId      = '';
		$accommodationId = '';
		$toggleMode      = '';
		
		if(isset($_POST['supplierId'])) {
			$supplierId = $_POST['supplierId'];
		}
		
		if(isset($_POST['accommodationId'])) {
			$accommodationId = $_POST['accommodationId'];
		}
		
		if(isset($_POST['mode'])) {
			$toggleMode = $_POST['mode'];
		}
		
		global $wpdb;
		
		$table_name_accommodations = $wpdb->prefix . 'localliving_plg_accommodations';
		
		if($toggleMode == 'on') {
			if($supplierId != '') {
				
				$query = "SELECT $table_name_accommodations.accommodation_id, $table_name_accommodations.accommodation_exception_status
					FROM $table_name_accommodations
					WHERE $table_name_accommodations.accommodation_supplier_id = $supplierId";
				
				$accommodationObjList = $wpdb->get_results($query);
				
				foreach ($accommodationObjList as $accommodationObj) {
					if($accommodationObj->accommodation_exception_status == '0'
						|| $accommodationObj->accommodation_exception_status == ''
					) {
						$data = array(
							'accommodation_exception_status' => '1',
							'accommodation_set_exception_time' => time()
						);
						
						$where = array(
							'accommodation_id' => $accommodationObj->accommodation_id
						);
						
						$wpdb->update($table_name_accommodations, $data, $where);
					}
				}
			}
			
			if($accommodationId != '') {
				
				$query = "SELECT $table_name_accommodations.accommodation_exception_status
					FROM $table_name_accommodations
					WHERE $table_name_accommodations.accommodation_id = $accommodationId";
				
				$obj = $wpdb->get_row($query);
				
				if($obj->accommodation_exception_status == '0'
					|| $obj->accommodation_exception_status == ''
				) {
					$data = array(
						'accommodation_exception_status' => '1',
						'accommodation_set_exception_time' => time()
					);
					
					$where = array(
						'accommodation_id' => $accommodationId
					);
					
					$wpdb->update($table_name_accommodations, $data, $where);
				}
			}
		} else {
			if($supplierId != '') {
				
				$query = "SELECT $table_name_accommodations.accommodation_id
					FROM $table_name_accommodations
					WHERE $table_name_accommodations.accommodation_supplier_id = $supplierId";
				
				$accommodationObjList = $wpdb->get_results($query);
				
				foreach ($accommodationObjList as $accommodationObj) {
					$data = array(
						'accommodation_exception_status' => '0',
						'accommodation_set_exception_time' => ''
					);
					
					$where = array(
						'accommodation_id' => $accommodationObj->accommodation_id
					);
					
					$wpdb->update($table_name_accommodations, $data, $where);
				}
			}
			
			if($accommodationId != '') {
				
				$data = array(
					'accommodation_exception_status' => '0',
					'accommodation_set_exception_time' => ''
				);
				
				$where = array(
					'accommodation_id' => $accommodationId
				);
				
				$wpdb->update($table_name_accommodations, $data, $where);
			}
		}
		
		die;
	}
	
	public function toggle_generate_csv_mode() {
		$editAccommodationId      = '';
		$toggleMode               = '';
		
		if(isset($_POST['editAccommodationId'])) {
			$editAccommodationId = $_POST['editAccommodationId'];
		}
		
		if(isset($_POST['mode'])) {
			$toggleMode = $_POST['mode'];
		}
		
		if($editAccommodationId != '') {
			global $wpdb;
			$table_name_accommodations = $wpdb->prefix . 'localliving_plg_accommodations';
			
			if($toggleMode == "on") {
				$data = array(
					"accommodation_csv_generate_mode" => '1'
				);
				
			} else {
				$data = array(
					"accommodation_csv_generate_mode" => '0'
				);
				
			}
			
			$where = array(
				"accommodation_id" => $editAccommodationId
			);
			
			$wpdb->update($table_name_accommodations, $data, $where);
		}
		
		die;
	}
	
	public function manual_edit_offer_status() {
		$offerId        = '';
		$offerNewStatus = '';
		
		if(isset($_POST['offer_id'])) {
			$offerId = $_POST['offer_id'];
		}
		
		if(isset($_POST['offer_new_status'])) {
			$offerNewStatus = $_POST['offer_new_status'];
		}
		
		global $wpdb;
		
		$table_name_offer_list = $wpdb->prefix . 'localliving_plg_offer_list';
		
		if($offerId != '' && $offerNewStatus != '') {
			$data = array(
				"offer_status" => $offerNewStatus
			);
			
			$where = array(
				"offer_id" => $offerId
			);
			
			$wpdb->update($table_name_offer_list, $data, $where);
		}
		
		die;
	}
    
    public function enqueue_script_style($hook_suffix)
    {
        if (is_admin()) {
            if($hook_suffix == "toplevel_page_localliving" || 
                $hook_suffix == "local-living_page_tilbud" || 
                $hook_suffix == "local-living_page_ferieboliger" ||
                $hook_suffix == "admin_page_generer_pdf" ||
                $hook_suffix == "admin_page_rediger_feriebolig") {
                //styles
                wp_register_style(
                    'bootstrap5',
                    LL_PLUGIN_URL . '/assets/bootstrap/dist/css/bootstrap.min.css'
                );
                wp_register_style(
                    'bootstrap-datepicker_stylesheet',
                    LL_PLUGIN_URL . '/assets/bootstrap-datepicker/dist/css/bootstrap-datepicker3.min.css'
                );
                wp_register_style(
                    'bootstrap-toggle',
                    LL_PLUGIN_URL . '/assets/bootstrap-toggle/css/bootstrap-toggle.min.css'
                );
                wp_register_style(
                    'select2_stylesheet',
                    LL_PLUGIN_URL . '/assets/select2/select2.min.css'
                );

                wp_enqueue_style('bootstrap5');
                wp_enqueue_style('select2_stylesheet');
                wp_enqueue_style('bootstrap-datepicker_stylesheet');
                wp_enqueue_style('bootstrap-toggle');
	            wp_enqueue_style('jquery_modal_stylesheet');

                //scripts
                wp_deregister_script('jquery');
                wp_register_script(
                    'jquery',
                    'https://cdn.jsdelivr.net/jquery/latest/jquery.min.js'
                );
                wp_register_script(
                    'localliving_script_dist',
                    LL_PLUGIN_URL . '/dist/main.js',
                    array('jquery'),
                    '1.0',
                    true
                );
                wp_register_script(
                    'moment_script',
                    LL_PLUGIN_URL . '/assets/moment/moment.js',
                    array('jquery'),
                    false,
                    true
                );
	            wp_register_script(
		            'moment_script_language',
		            LL_PLUGIN_URL . '/assets/moment/locale/da.js',
		            array('jquery'),
		            false,
		            true
	            );
                wp_register_script(
                    'lazyload_script',
                    THEME_URL . '/js/lazyload.min.js',
                    array('jquery'),
                    '20160804'
                );
                wp_register_script(
                    'select2_script',
                    LL_PLUGIN_URL . '/assets/select2/select2.min.js',
                    array('jquery'),
                    false,
                    true
                );
                wp_register_script(
                    'bootstrap_datepicker',
                    LL_PLUGIN_URL . '/assets/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js',
                    array('jquery'),
                    false,
                    true
                );
                wp_register_script(
                    'bootstrap_datepicker_locales',
                    LL_PLUGIN_URL . '/assets/bootstrap-datepicker/dist/locales/bootstrap-datepicker.da.min.js',
                    array('jquery'),
                    false,
                    true
                );
                wp_register_script(
                    'bootstrap5',
                    LL_PLUGIN_URL . '/assets/bootstrap/dist/js/bootstrap.min.js',
                    array('jquery'),
                    false,
                    true
                );
                wp_register_script(
                    'bootstrap-toggle',
                    LL_PLUGIN_URL . '/assets/bootstrap-toggle/js/bootstrap-toggle.min.js',
                    array('jquery'),
                    false,
                    true
                );

                wp_enqueue_script('jquery');
                wp_enqueue_script('localliving_script_dist');
                wp_enqueue_script('moment_script');
				wp_enqueue_script('moment_script_language');
                wp_enqueue_script('lazyload_script');
                wp_enqueue_script('select2_script');
                wp_enqueue_script('bootstrap_datepicker');
                wp_enqueue_script('bootstrap_datepicker_locales');
				wp_enqueue_script('bootstrap5');
				wp_enqueue_script('bootstrap-toggle');
            }
        }
    }
    
    public function localliving_plg_db_install()
    {
        global $wpdb;
        global $localliving_plg_db_version;
        
		//------- OFFER LIST TABLE -------
        $table_name_offer_list = $wpdb->prefix . 'localliving_plg_offer_list';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql[] = "CREATE TABLE $table_name_offer_list (
				offer_id int(9) NOT NULL AUTO_INCREMENT,
				offer_receiver text,
				offer_receiver_email text,
				offer_name text,
				offer_url text,
				offer_generate_timestamp text,
				offer_status text,
				PRIMARY KEY  (offer_id)
		) $charset_collate;";
	
	    //------- SUPPLIERS TABlE -------
	    $table_name_suppliers = $wpdb->prefix . 'localliving_plg_suppliers';
	
	    $sql[] = "CREATE TABLE $table_name_suppliers (
				supplier_id int(9) NOT NULL,
				supplier_name text NOT NULL,
				supplier_email text NOT NULL,
				PRIMARY KEY  (supplier_id)
		) $charset_collate;";
	
	    //------- ACCOMMODATIONS TABlE -------
	    $table_name_accommodations = $wpdb->prefix . 'localliving_plg_accommodations';
		
		$sql[] = "CREATE TABLE $table_name_accommodations (
    			accommodation_id int(9) NOT NULL,
    			accommodation_supplier_id int(9),
                accommodation_name text NOT NULL,
                accommodation_csv_generate_mode int(1),
                accommodation_exception_status int(1),
                accommodation_set_exception_time text,
                PRIMARY KEY  (accommodation_id),
                FOREIGN KEY  (accommodation_supplier_id) REFERENCES $table_name_suppliers(supplier_id)
		) $charset_collate;";
	
	    //------- UNITS TABlE -------
	    $table_name_units = $wpdb->prefix . 'localliving_plg_units';
		
		$sql[] = "CREATE TABLE $table_name_units (
    			unit_id int(9) NOT NULL,
    			unit_accommodation_id int(9),
    			unit_name text NOT NULL,
    			unit_unique_ref text,
    			PRIMARY KEY  (unit_id),
    			FOREIGN KEY  (unit_accommodation_id) REFERENCES $table_name_accommodations(accommodation_id)
		) $charset_collate;";
	
	    //------- UNITS ICAL REMINDER TABlE -------
	    $table_name_units_ical_reminders = $wpdb->prefix . 'localliving_plg_units_ical_reminders';
		
		$sql[] = "CREATE TABLE $table_name_units_ical_reminders (
    			reminder_id int(9) NOT NULL AUTO_INCREMENT,
    			reminder_unit_id int(9) NOT NULL,
    			reminder_ical_link text NOT NULL,
    			reminder_status text NOT NULL,
    			reminder_sent_timestamp text,
    			reminder_created_timestamp text NOT NULL,
    			reminder_updated_timestamp text NOT NULL,
    			PRIMARY KEY (reminder_id),
    			FOREIGN KEY (reminder_unit_id) REFERENCES $table_name_units(unit_id)
		) $charset_collate;";
	
	    //------- UNIT TYPES ICAL REMINDER TABlE -------
	    $table_name_unit_types_ical_reminders = $wpdb->prefix . 'localliving_plg_unit_types_ical_reminders';
	
	    $sql[] = "CREATE TABLE $table_name_unit_types_ical_reminders (
    			unit_type_id int(9) NOT NULL AUTO_INCREMENT,
    			unit_type_unit_id int(9) NOT NULL,
    			unit_type_name text,
    			unit_type_ical_link text,
    			PRIMARY KEY (unit_type_id),
                FOREIGN KEY (unit_type_unit_id) REFERENCES $table_name_units(unit_id)
		) $charset_collate;";
	
	    //------- OPTIONS TABLE -------
	    $table_name_options= $wpdb->prefix . 'localliving_plg_options';
		
	    $sql[] = "CREATE TABLE $table_name_options (
    		option_id int(9) NOT NULL AUTO_INCREMENT,
    		option_name text NOT NULL,
    		option_value text,
    		PRIMARY KEY (option_id)
		) $charset_collate;";
	
	    if ( !empty($sql) ) {
		    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		
		    dbDelta($sql);
		    add_option("localliving_plg_db_version", $localliving_plg_db_version);
		
		    //pulling Suppliers data from iTravel API
		    $getAllCustomersRequest = new \LocalLiving_Plugin\iTravelAPI\GetCustomers();
		    $suppliersList = $getAllCustomersRequest->getAllSuppliers();
		
		    foreach ($suppliersList as $supplier) {
				$supplierId    = $supplier->CustomerID ?? "";
				$supplierName  = $supplier->CompanyName ?? "";
				$supplierEmail = $supplier->Email ?? "";
				
				$supplierExisted = $this->supplierExisted($supplierId);
				
				if($supplierExisted) {
					$data = array(
						"supplier_name" => $supplierName,
						"supplier_email" => $supplierEmail
					);
					
					$where = array(
						"supplier_id" => $supplierId
					);
					
					$wpdb->update($table_name_suppliers, $data, $where);
					
					$log = "Supplier [".$supplierId."] ". $supplierName . " updated";
				} else {
					$data = array(
						"supplier_id" => $supplierId,
						"supplier_name" => $supplierName,
						"supplier_email" => $supplierEmail
					);
					
					$wpdb->insert($table_name_suppliers, $data);
					
					$log = "Supplier [".$supplierId."] ". $supplierName . " inserted";
				}
			    $this->wh_log($log);
		    }
		
		    $getSearchResultsRequest = new \LocalLiving_Plugin\iTravelAPI\GetSearchResults();
		    $getSearchResultsRequest->ignorePriceAndAvailability = true;
		    $getSearchResultsRequest->pageSize = 99999; //get all data
		    $getSearchResultsResponse = $getSearchResultsRequest->GetAPIResponse();
		    
		    //pulling Accommodations & Units data from iTravel API
		    if($this->countDataByTable($table_name_accommodations) <= 0
			    && $this->countDataByTable($table_name_units) <= 0) {

			    if (isset($getSearchResultsResponse->GetSearchResultsResult->AccommodationObjectList->AccommodationObject)) {
				    $accommodationsList =
					    $getSearchResultsResponse->GetSearchResultsResult->AccommodationObjectList->AccommodationObject;

				    foreach ($accommodationsList as $accommodation) {
						$accommodationId   = $accommodation->ObjectID ?? '';
						$accommodationName = $accommodation->Name     ?? '';
						
					    $data = array(
						    "accommodation_id"                 => $accommodationId,
						    "accommodation_name"               => $accommodationName,
						    "accommodation_csv_generate_mode"  => '0',
						    "accommodation_exception_status"   => '0',
						    "accommodation_set_exception_time" => ''
					    );

					    $wpdb->insert($table_name_accommodations, $data);
						
						$log = "Accommodation [".$accommodationId."] ". $accommodationName . " inserted";
						$this->wh_log($log);
						
					    if (isset($accommodation->UnitList->AccommodationUnit)) {
						    $unitList = $accommodation->UnitList->AccommodationUnit;
						
						    foreach ($unitList as $unit) {
							    $unitName = '';
							
							    if (isset($unit->AttributeGroupList->AttributeGroup[0]->AttributeList->Attribute)) {
								    $unitAttributesList = $unit->AttributeGroupList->AttributeGroup[0]->AttributeList->Attribute;
								
								    foreach ($unitAttributesList as $unitAttribute) {
									    if ($unitAttribute->AttributeID == 133) {
										    $unitName = $unitAttribute->AttributeValue;
									    }
								    }
							    }
								
								$unitId = $unit->UnitID ?? "";
								$unitAccommodationId = $accommodation->ObjectID ?? "";
							
							    $data = array(
								    "unit_id" => $unitId,
								    "unit_accommodation_id" => $unitAccommodationId,
								    "unit_name" => $unitName
							    );
							
							    $wpdb->insert($table_name_units, $data);
							
							    $log = "Unit [".$unitId."] ". $unitName . " inserted";
							    $this->wh_log($log);
						    }
					    }
				    }
			    }
		    }
			else {
				if (isset($getSearchResultsResponse->GetSearchResultsResult->AccommodationObjectList->AccommodationObject)) {
					$accommodationsList =
						$getSearchResultsResponse->GetSearchResultsResult->AccommodationObjectList->AccommodationObject;
					
					foreach ($accommodationsList as $accommodation) {
						$accommodationId   = $accommodation->ObjectID ?? "";
						$accommodationName = $accommodation->Name     ?? "";
						
						$accommodationExisted = $this->accommodationExisted($accommodationId);
						
						if($accommodationExisted) {
							$data = array(
								"accommodation_name" => $accommodationName
							);
							
							$where = array(
								"accommodation_id" => $accommodationId
							);
							
							$wpdb->update($table_name_accommodations, $data, $where);
							
							$log = "Accommodation [".$accommodationId."] ". $accommodationName . " updated";
						}
						else {
							$data = array(
								"accommodation_id"                 => $accommodationId,
								"accommodation_name"               => $accommodationName,
								"accommodation_csv_generate_mode"  => '0',
								"accommodation_exception_status"   => '0',
								"accommodation_set_exception_time" => ''
							);
							
							$wpdb->insert($table_name_accommodations, $data);
							
							$log = "Accommodation [".$accommodationId."] ". $accommodationName . " inserted";
						}
						
						$this->wh_log($log);
						
						if (isset($accommodation->UnitList->AccommodationUnit)) {
							$unitList = $accommodation->UnitList->AccommodationUnit;
							
							foreach ($unitList as $unit) {
								$unitName = '';
								
								if (isset($unit->AttributeGroupList->AttributeGroup[0]->AttributeList->Attribute)) {
									$unitAttributesList = $unit->AttributeGroupList->AttributeGroup[0]->AttributeList->Attribute;
									
									foreach ($unitAttributesList as $unitAttribute) {
										if ($unitAttribute->AttributeID == 133) {
											$unitName = $unitAttribute->AttributeValue;
										}
									}
								}
								
								$unitId = $unit->UnitID ?? "";
								
								$unitExisted = $this->unitExisted($unitId);
								
								if($unitExisted) {
									$data = array(
										"unit_name" => $unitName,
										"unit_accommodation_id" => $accommodationId
									);
									
									$where = array(
										"unit_id" => $unitId
									);
									
									$wpdb->update($table_name_units, $data, $where);
									
									$log = "Unit [".$unitId."] ". $unitName . " updated";
								} else {
									$data = array(
										"unit_id" => $unitId,
										"unit_accommodation_id" => $accommodationId,
										"unit_name" => $unitName
									);
									
									$wpdb->insert($table_name_units, $data);
									
									$log = "Unit [".$unitId."] ". $unitName . " inserted";
								}
								$this->wh_log($log);
							}
						}
					}
				}
		    }
			
			if($this->countDataByTable($table_name_options) <= 0) {
				$data = array(
					'option_name'  => 'pdf_frontpage_background_img',
					'option_value' => ''
				);
				
				$wpdb->insert($table_name_options, $data);
			}
	    }
    }
	
	private function accommodationExisted($accommodationId) {
		global $wpdb;
		
		$table_name_accommodations = $wpdb->prefix . 'localliving_plg_accommodations';
		
		$query = "SELECT $table_name_accommodations.accommodation_id
		FROM $table_name_accommodations
		WHERE $table_name_accommodations.accommodation_id = $accommodationId";
		
		$count = $wpdb->get_var("SELECT COUNT(1) FROM ($query) AS combined_table");
		
		if($count >= 1) {
			return true;
		} else {
			return false;
		}
	}
	
	private function unitExisted($unitId) {
		global $wpdb;
		
		$table_name_units = $wpdb->prefix . 'localliving_plg_units';
		
		$query = "SELECT $table_name_units.unit_id
		FROM $table_name_units
		WHERE $table_name_units.unit_id = $unitId";
		
		$count = $wpdb->get_var("SELECT COUNT(1) FROM ($query) AS combined_table");
		
		if($count >= 1) {
			return true;
		} else {
			return false;
		}
	}
	
	private function supplierExisted($supplierId) {
		global $wpdb;
		
		$table_name_suppliers = $wpdb->prefix . 'localliving_plg_suppliers';
		
		$query = "SELECT $table_name_suppliers.supplier_id
		FROM $table_name_suppliers
		WHERE $table_name_suppliers.supplier_id = $supplierId";
		
		$count = $wpdb->get_var("SELECT COUNT(1) FROM ($query) AS combined_table");
		
		if($count >= 1) {
			return true;
		} else {
			return false;
		}
	}
	
	private function countDataByTable($table_name) {
		global $wpdb;
		
		return (int) $wpdb->get_var("SELECT COUNT(*) from $table_name");
	}
	
	public function wh_log($log_msg)
	{
		$log_folder_name = $_SERVER['DOCUMENT_ROOT'] . "/log";
		if (!file_exists($log_folder_name))
		{
			// create directory/folder uploads.
			mkdir($log_folder_name, 0777, true);
		}
		$log_file_data = $log_folder_name.'/log_' . date('d-m-Y') . '.log';
		file_put_contents($log_file_data, $log_msg . "\n", FILE_APPEND);
	}
	
    public function ll_daily_cron()
    {
        global $wpdb;
    
		$table_name_accommodations       = $wpdb->prefix . 'localliving_plg_accommodations';
        $table_name_offer_list           = $wpdb->prefix . 'localliving_plg_offer_list';
	    $table_name_units_ical_reminders = $wpdb->prefix . 'localliving_plg_units_ical_reminders';
	
	    $nowTimestamp = time();
    
        $availableOffersList = $wpdb->get_results("
	        SELECT * FROM $table_name_offer_list
	        WHERE offer_status = 'green' OR offer_status = 'yellow'
    	");
        
        foreach ($availableOffersList as $availableOffer) {
            if (isset($availableOffer->offer_generate_timestamp)) {
                $offerGenerateTimestamp = $availableOffer->offer_generate_timestamp;
                
                //the offer expire after 5 days since it was generated
                $isExpired = $nowTimestamp >= strtotime('+5 day', $offerGenerateTimestamp);
                
                if ($isExpired) {
                    $data = array(
                        'offer_status' => 'red'
                    );
    
                    $where = array(
                        'offer_id' => $availableOffer->offer_id
                    );
    
                    $wpdb->update($table_name_offer_list, $data, $where);
                }
            }
        }
		
		$greenUnitIcalReminders = $wpdb->get_results("
			SELECT * FROM $table_name_units_ical_reminders
			WHERE $table_name_units_ical_reminders.reminder_status = 'green'
		");
	
	    foreach ($greenUnitIcalReminders as $greenUnitIcalReminder) {
			if(isset($greenUnitIcalReminder->reminder_updated_timestamp)) {
				$greenUnitIcalReminderUpdateTimestamp = $greenUnitIcalReminder->reminder_updated_timestamp;
				
				$needsRemind = $nowTimestamp >= strtotime('+5 day', $greenUnitIcalReminderUpdateTimestamp);
				
				if($needsRemind) {
					$data = array(
						'reminder_status' => 'yellow'
					);
					
					$where = array(
						'reminder_unit_id' => $greenUnitIcalReminder->reminder_unit_id
					);
					
					$wpdb->update($table_name_units_ical_reminders, $data, $where);
				}
			}
	    }
		
		$orangeUnitIcalReminders = $wpdb->get_results("
			SELECT * FROM $table_name_units_ical_reminders
			WHERE $table_name_units_ical_reminders.reminder_status = 'orange'
		");
	
	    foreach ($orangeUnitIcalReminders as $orangeUnitIcalReminder) {
		    if(isset($orangeUnitIcalReminder->reminder_sent_timestamp)) {
			    $orangeUnitIcalReminderSentTimestamp = $orangeUnitIcalReminder->reminder_sent_timestamp;
				
			    $needsRemind =
				    $nowTimestamp >= strtotime('+3 day', $orangeUnitIcalReminderSentTimestamp);
			
			    if($needsRemind) {
				    $data = array(
					    'reminder_status' => 'red'
				    );
				
				    $where = array(
					    'reminder_unit_id' => $orangeUnitIcalReminder->reminder_unit_id
				    );
				
				    $wpdb->update($table_name_units_ical_reminders, $data, $where);
			    }
		    }
	    }
		
		$exceptionAccommodations = $wpdb->get_results("
			SELECT * FROM $table_name_accommodations
			WHERE $table_name_accommodations.accommodation_exception_status = '1'
		");
		
		foreach ($exceptionAccommodations as $exceptionAccommodation) {
			if(isset($exceptionAccommodation->accommodation_set_exception_time)) {
				$setExceptionTime = $exceptionAccommodation->accommodation_set_exception_time;
				
				if($setExceptionTime != '') {
					$needsUnexception = $nowTimestamp >= strtotime('+30 day', $setExceptionTime);
					
					if($needsUnexception) {
						$data = array(
							'accommodation_exception_status' => '0',
							'accommodation_set_exception_time' => ''
						);
						
						$where = array(
							'accommodation_id' => $exceptionAccommodation->accommodation_id
						);
						
						$wpdb->update($table_name_accommodations, $data, $where);
					}
				}
			}
		}
		
		//clear compress images logs folder
	    $files = glob(COMPRESS_IMAGES_LOGS . '/*');
		
		foreach ($files as $file) {
			if(is_file($file)) {
				unlink($file);
			}
		}
    }
}


// instantiate plugin's class
$GLOBALS['localliving_plugin'] = new localliving_plugin();
