<?php
	add_action('admin_footer', function () {
    echo '
        <script>
        jQuery(document).ready(function ($) {
           $(document).on("change", "#ferieboliger-status-select", function() {
                $("#search-action-submit").click();
           });

           $(document).on("click", "#select-all-checkbox", function() {
               $("input:checkbox[class=accommodation-checkbox]").not(this).prop("checked", this.checked);
           });
           
           $(document).on("click", "#select-all-supplier-checkbox", function() {
               $(this).closest(".supplier-wrapper").find("input:checkbox[class=accommodation-checkbox]").prop("checked", this.checked);
           });
           
           $(".exception-toggle-all").parent().on("click", function() {
               var selector = $(this).find(".exception-toggle-all")[0];
               var toggleStatus = $(selector).prop("checked") ? "off" : "on";
               var supplierId = $(selector).val();
               
               $(this).parents(".supplier-wrapper").find("input.exception-toggle-accommodation").bootstrapToggle(toggleStatus);
               
               $.ajax({
                    type: "POST",
                    dataType: "json",
                    url: "' . admin_url("admin-ajax.php") . '",
                    data: {
                       action: "toggle_exception",
                       supplierId: supplierId,
                       accommodationId: "",
                       mode: toggleStatus
                    },
                    context: this,
                    success: function(response) {
                        console.log(response);
                    }
               });
           });
           
           $(".exception-toggle-accommodation").parent().on("click", function() {
               var selector = $(this).find(".exception-toggle-accommodation")[0];
               var toggleStatus = $(selector).prop("checked") ? "off" : "on";
               var accommodationId = $(selector).val();
               
               $.ajax({
                    type: "POST",
                    dataType: "json",
                    url: "' . admin_url("admin-ajax.php") . '",
                    data: {
                       action: "toggle_exception",
                       supplierId: "",
                       accommodationId: accommodationId,
                       mode: toggleStatus
                    },
                    context: this,
                    success: function(response) {
                        console.log(response);
                    }
               });
           });
           
           $(document).on("click", "#select-all-supplier-checkbox, #select-all-checkbox, .accommodation-checkbox", function() {
               if($(".accommodation-checkbox:checked").length > 0) {
                   $("#set-supplier-btn").removeAttr("disabled");
               } else {
                   $("#set-supplier-btn").prop("disabled","disabled");
               }
           });

           $("#supplier-select").select2({
               width: "100%",
               dropdownParent: $(".supplier-selector"),
           })

           $("#set-supplier-btn").on("click", function(event) {
               event.preventDefault();

               var selectedAccommodationsListForSetSupplier = [];
               $("input[class=accommodation-checkbox]:checked").each(function() {
                  selectedAccommodationsListForSetSupplier.push($(this).val());
               });

               $("#selected-accommodation-for-set-supplier-counter").text(selectedAccommodationsListForSetSupplier.length);
               $("#selected-accommodation-for-set-supplier-hidden").val(selectedAccommodationsListForSetSupplier.join(","));
           });
           
           //view options select
           $(document).on("change", ".ferie-view-options-input", function () {
            var checked = $(this).attr("checked");
            var checkname = $(this).attr("name");
        
            if (checked === undefined) {
              this.checked = true;
              $("input.ferie-view-options-hidden-input[type=hidden][name=" + checkname + "]").val("1");
            } else {
              this.checked = false;
        
              $("input.ferie-view-options-hidden-input[type=hidden][name=" + checkname + "]").val("0");
            }
        
            $("#search-action-submit").click();
           });
           
            //reset button
            $(document).on("click", "#btn-reset", function (e) {
                e.preventDefault();
            
                window.location = "?page=ferieboliger";
            });
        });
        </script>
    ';
    });
    
    function getAccommodationsList(
            $returnCount = false,
            $needsPagination = false,
            $limit = -1,
            $offset = 0,
            $supplierId = '',
            $searchCombine = '',
            $searchStatus = '') {
        global $wpdb;
	
	    $table_name_accommodations       = $wpdb->prefix . 'localliving_plg_accommodations';
	    $table_name_suppliers            = $wpdb->prefix . 'localliving_plg_suppliers';
        $table_name_units                = $wpdb->prefix . 'localliving_plg_units';
        $table_name_units_ical_reminders = $wpdb->prefix . 'localliving_plg_units_ical_reminders';
        
        $icalStatusOrder = ['green', 'orange', 'yellow', 'red'];
        
        $query = "
            SELECT accommodation_id, $table_name_suppliers.supplier_id as accommodation_supplier_id,
                   $table_name_suppliers.supplier_name as accommodation_supplier_name, accommodation_name,
                   accommodation_exception_status, CASE";
        
        foreach ($icalStatusOrder as $icalStatus) {
	        $query .= " WHEN(
                    SELECT COUNT($table_name_units_ical_reminders.reminder_unit_id)
                    FROM $table_name_units
                    LEFT JOIN $table_name_units_ical_reminders
                    ON $table_name_units_ical_reminders.reminder_unit_id = $table_name_units.unit_id
                    WHERE $table_name_units.unit_accommodation_id = $table_name_accommodations.accommodation_id
                      AND $table_name_units_ical_reminders.reminder_status = '$icalStatus'
               ) > 0 THEN '$icalStatus'";
        }
	
	    $query .= " END AS ical_status_color";
	
	    $viewExceptionOnly = '0';
	
	    if(isset($_POST['ViewExceptionOnly'])) {
		    $viewExceptionOnly = $_POST['ViewExceptionOnly'];
	    }
        
        $query .= "
            FROM $table_name_accommodations
            LEFT JOIN $table_name_suppliers
            ON $table_name_accommodations.accommodation_supplier_id = $table_name_suppliers.supplier_id
        ";
        
        if($viewExceptionOnly == '1') {
	        $query .= " WHERE $table_name_accommodations.accommodation_exception_status = 1";
        } else {
	        $query .= " WHERE $table_name_accommodations.accommodation_exception_status = 0";
        }
	
	    if($supplierId != '') {
		    $query .= " AND $table_name_suppliers.supplier_id = $supplierId";
	    }
	
        if($searchCombine != '') {
	        $query .= " AND ($table_name_accommodations.accommodation_name LIKE '%$searchCombine%'
	        OR $table_name_suppliers.supplier_name LIKE '%$searchCombine%')";
        }
	
	    $query .= " GROUP BY $table_name_accommodations.accommodation_id";
	
	    if($searchStatus != '' && $searchStatus != 'black') {
      
//		    if($searchStatus == 'green') {
//			    $query .= " HAVING (ical_status_color = '$searchStatus'
//	            OR $table_name_accommodations.accommodation_exception_status = 1)";
//		    } else
            
            if ($searchStatus == 'oyr') {
			    $query .= " HAVING (ical_status_color = 'orange'
	            OR ical_status_color = 'yellow' OR ical_status_color = 'red')";
            } else {
			    $query .= " HAVING ical_status_color = '$searchStatus'";
		    }
	    }
     
	    $query .= " ORDER BY $table_name_suppliers.supplier_id";
        
        if($returnCount) {
	        return $wpdb->get_var("SELECT COUNT(1) FROM ($query) AS combined_table");
        }
        
        if($needsPagination) {
	        $query .= " LIMIT $limit OFFSET $offset";
        }
        
        return $wpdb->get_results($query);
    }
	
	function getSuppliersList(
		$returnCount = false,
		$needsPagination = false,
		$groupBySupplierId = false,
		$limit = -1,
		$offset = 0,
		$searchCombine = '',
		$searchStatus = '') {
		global $wpdb;
		
		$table_name_suppliers            = $wpdb->prefix . 'localliving_plg_suppliers';
		$table_name_accommodations       = $wpdb->prefix . 'localliving_plg_accommodations';
		$table_name_units                = $wpdb->prefix . 'localliving_plg_units';
		$table_name_units_ical_reminders = $wpdb->prefix . 'localliving_plg_units_ical_reminders';
		
		$viewExceptionOnly = '0';
		
		if(isset($_POST['ViewExceptionOnly'])) {
			$viewExceptionOnly = $_POST['ViewExceptionOnly'];
		}
		
		$icalStatusOrder = ['green', 'orange', 'yellow', 'red'];
		
		$query = "
            SELECT *, CASE
        ";
		
		foreach ($icalStatusOrder as $icalStatus) {
			$query .= " WHEN(
                    SELECT COUNT($table_name_units_ical_reminders.reminder_unit_id)
                    FROM $table_name_units
                    LEFT JOIN $table_name_units_ical_reminders
                    ON $table_name_units_ical_reminders.reminder_unit_id = $table_name_units.unit_id
                    WHERE $table_name_units.unit_accommodation_id = $table_name_accommodations.accommodation_id
                      AND $table_name_units_ical_reminders.reminder_status = '$icalStatus'
               ) > 0 THEN '$icalStatus'";
		}

		$query .= " END AS ical_status_color";
		
		$query .= "
            FROM $table_name_suppliers
            LEFT JOIN $table_name_accommodations
            ON $table_name_accommodations.accommodation_supplier_id = $table_name_suppliers.supplier_id
            LEFT JOIN $table_name_units
            ON $table_name_units.unit_accommodation_id = $table_name_accommodations.accommodation_id
            LEFT JOIN $table_name_units_ical_reminders
            ON $table_name_units_ical_reminders.reminder_unit_id = $table_name_units.unit_id
        ";
		
		if($viewExceptionOnly == '1') {
			$query .= " WHERE $table_name_accommodations.accommodation_exception_status = 1";
		} else {
			$query .= " WHERE $table_name_accommodations.accommodation_exception_status = 0";
		}
		
		$query .= " AND $table_name_suppliers.supplier_id IN(
            SELECT $table_name_accommodations.accommodation_supplier_id
            FROM $table_name_accommodations
        )";
		
//		if($searchCombine != '') {
//
//		}
		
		$query .= " HAVING ($table_name_accommodations.accommodation_name LIKE '%$searchCombine%'
	        OR $table_name_suppliers.supplier_name LIKE '%$searchCombine%')";
	
        if($searchStatus != '' && $searchStatus != 'black') {
	        if ($searchStatus == 'oyr') {
		        $query .= " AND (ical_status_color = 'orange'
		            OR ical_status_color = 'yellow'
		            OR ical_status_color = 'red'
		        )";
            } else {
		        $query .= " AND ical_status_color = '$searchStatus'";
            }
	        
        }
		
		if($groupBySupplierId) {
            $query = " SELECT q.* FROM ($query) q GROUP BY q.supplier_id";
//			$query .= " GROUP BY $table_name_suppliers.supplier_id";
		}
		
//		if($searchStatus != '' && $searchStatus != 'black') {

//		    if($searchStatus == 'green') {
//			    $query .= " HAVING (ical_status_color = '$searchStatus'
//	            OR $table_name_accommodations.accommodation_exception_status = 1)";
//		    } else
			
//			if ($searchStatus == 'oyr') {
//				$query .= " HAVING (ical_status_color = 'orange'
//	            OR ical_status_color = 'yellow' OR ical_status_color = 'red')";
//			} else {
//				$query .= " HAVING ical_status_color = '$searchStatus'";
//			}
//		}

//		if($searchCombine != '') {
//			$query .= " AND ($table_name_accommodations.accommodation_name LIKE '%$searchCombine%'
//	        OR $table_name_suppliers.supplier_name LIKE '%$searchCombine%')";
//		}
		
		if ($returnCount) {
			return $wpdb->get_var("SELECT COUNT(1) FROM ($query) AS combined_table");
		}
		
		if($needsPagination) {
			$query .= " LIMIT $limit OFFSET $offset";
		}
		
		return $wpdb->get_results($query);
	}
    
    function getAccommodationIcalStatusById($accommodationId) {
        global $wpdb;
	
	    $table_name_accommodations      = $wpdb->prefix . 'localliving_plg_accommodations';
        $table_name_units               = $wpdb->prefix . 'localliving_plg_units';
        $table_name_unit_ical_reminders = $wpdb->prefix . 'localliving_plg_units_ical_reminders';
	
	    $statusOrder = ['green','yellow','orange','red'];
        
        $query = "SELECT *
        FROM $table_name_accommodations
        LEFT JOIN $table_name_units ON $table_name_units.unit_accommodation_id = $table_name_accommodations.accommodation_id
        LEFT JOIN $table_name_unit_ical_reminders ON $table_name_unit_ical_reminders.reminder_unit_id = $table_name_units.unit_id
        WHERE $table_name_accommodations.accommodation_id = $accommodationId
        ";
        
        $rows = $wpdb->get_results($query);
        
        $tmp = array();
        
        $accommodationIcalStatus = "";
        
        foreach($rows as $row) {
            $accommodationUnitIcalStatus = $row->reminder_status;
            
            if(!is_null($accommodationUnitIcalStatus)) {
	            $tmp[$accommodationUnitIcalStatus] = array_search($accommodationUnitIcalStatus, $statusOrder);
	
	            $accommodationIcalStatus = array_keys($tmp, min($tmp))[0];
            }
        }
        
        return $accommodationIcalStatus;
    }
    
    function countAccommodationIcalStatus($icalStatuses) {
	    $accommodationList = getAccommodationsList();
	
	    $result = 0;
	
	    foreach ($accommodationList as $accommodation) {
		    if(isset($accommodation->accommodation_id)) {
			    $accommodationId = $accommodation->accommodation_id;
			    $accommodationIcalStatus = getAccommodationIcalStatusById($accommodationId);
			    if($accommodationIcalStatus === $icalStatuses) {
				    $result++;
			    }
		    }
	    }
        
        return $result;
    }
    
    function countExceptionReminderAccommodation($supplierId = '') {
        global $wpdb;
	
	    $table_name_suppliers      = $wpdb->prefix . 'localliving_plg_suppliers';
        $table_name_accommodations = $wpdb->prefix . 'localliving_plg_accommodations';
        
        $query = "SELECT $table_name_accommodations.accommodation_id
                FROM $table_name_accommodations";
                
        
        if($supplierId != '') {
            $query .= " LEFT JOIN $table_name_suppliers
            ON $table_name_suppliers.supplier_id = $table_name_accommodations.accommodation_supplier_id";
        }
        
        $query .= " WHERE $table_name_accommodations.accommodation_exception_status = '1'";
	
	    if($supplierId != '') {
		    $query .= " AND $table_name_suppliers.supplier_id = $supplierId";
	    }
        
        return $wpdb->get_var("SELECT COUNT(1) FROM ($query) AS combined_table");
    }
    
    function getAccommodationLatestUpdateById($accommodationId) {
	    global $wpdb;
	
	    $table_name_accommodations      = $wpdb->prefix . 'localliving_plg_accommodations';
	    $table_name_units               = $wpdb->prefix . 'localliving_plg_units';
	    $table_name_unit_ical_reminders = $wpdb->prefix . 'localliving_plg_units_ical_reminders';
        
        $query = "SELECT $table_name_unit_ical_reminders.reminder_updated_timestamp
            FROM $table_name_unit_ical_reminders
            LEFT JOIN $table_name_units ON $table_name_unit_ical_reminders.reminder_unit_id = $table_name_units.unit_id
            LEFT JOIN $table_name_accommodations ON $table_name_units.unit_accommodation_id = $table_name_accommodations.accommodation_id
            WHERE $table_name_accommodations.accommodation_id = $accommodationId";
        
        $result = $wpdb->get_row($query);
        
        if($result) {
            $format              = 'd.m.Y \k\l\. H:i';
            $timestamp           = $result->reminder_updated_timestamp;
            $timestampToDateTime = DateTime::createFromFormat('U', $timestamp);
	        return $timestampToDateTime->format($format);
        }
        
        return false;
    }
    
    function getSupplierDetailById($supplierId) {
        global $wpdb;
	
	    $table_name_suppliers = $wpdb->prefix . 'localliving_plg_suppliers';
        
        $query = "SELECT *
            FROM $table_name_suppliers
            WHERE $table_name_suppliers.supplier_id = $supplierId";
        
        return $wpdb->get_row($query);
    }
    
    function getYellowStatusRemindersBySupplierId($supplierId) {
	    global $wpdb;
	
	    $table_name_units_ical_reminders = $wpdb->prefix . 'localliving_plg_units_ical_reminders';
	    $table_name_units                = $wpdb->prefix . 'localliving_plg_units';
	    $table_name_accommodations       = $wpdb->prefix . 'localliving_plg_accommodations';
        $table_name_suppliers            = $wpdb->prefix . 'localliving_plg_suppliers';
	
	    $query = "SELECT *
	            FROM $table_name_units_ical_reminders
	            LEFT JOIN $table_name_units ON $table_name_units_ical_reminders.reminder_unit_id = $table_name_units.unit_id
	            LEFT JOIN $table_name_accommodations ON $table_name_units.unit_accommodation_id = $table_name_accommodations.accommodation_id
                LEFT JOIN $table_name_suppliers ON $table_name_accommodations.accommodation_supplier_id = $table_name_suppliers.supplier_id
                WHERE $table_name_suppliers.supplier_id = $supplierId AND $table_name_units_ical_reminders.reminder_status = 'yellow'";
        
        return $wpdb->get_results($query);
    }
    
    function isSupplierException($supplierId) {
	    global $wpdb;
        
        $result = true;
	
        $table_name_accommodation = $wpdb->prefix . 'localliving_plg_accommodations';
	    $table_name_suppliers     = $wpdb->prefix . 'localliving_plg_suppliers';
        
        $query = "SELECT $table_name_accommodation.accommodation_exception_status
                FROM $table_name_accommodation
                LEFT JOIN $table_name_suppliers ON $table_name_suppliers.supplier_id = $table_name_accommodation.accommodation_supplier_id
                WHERE $table_name_suppliers.supplier_id = $supplierId";
        
        $queryResult = $wpdb->get_results($query);
        
        foreach ($queryResult as $obj) {
            if($obj->accommodation_exception_status == '0') {
	            $result = false;
                break;
            }
        }
        
        return $result;
    }
    
    function getExceptionStatusByAccommodationId($accommodationId) {
	    global $wpdb;
	
	    $result = '';
	
	    $table_name_accommodations = $wpdb->prefix . 'localliving_plg_accommodations';
        
        $query = "SELECT $table_name_accommodations.accommodation_exception_status
                FROM $table_name_accommodations
                WHERE $table_name_accommodations.accommodation_id = $accommodationId";
        
        $queryResult = $wpdb->get_row($query);
        
        if($queryResult) {
	        $result = $queryResult->accommodation_exception_status;
        }
        
        return $result;
    }
	
	function renderPagination($dataTotal, $perPage, $currentPage)
	{
		$numberOfPages = ceil($dataTotal / $perPage);
		
		echo '<div class="pagination-sort-holder">';
		echo '<ul class="pagination-list">';
		
		if ($currentPage <= 1) {
			echo '<li><button disabled="disabled">«</button></li>';
			echo '<li><button disabled="disabled">‹</button></li>';
		} else {
			echo '<li><button type="submit" name="cpage" value="1" form="ferieboliger-search-form">«</button></li>';
			echo '<li><button type="submit" name="cpage" value="' . ($currentPage - 1) . '" form="ferieboliger-search-form">‹</button></li>';
		}
		
		echo '<div class="page-counter">';
		echo $currentPage . ' of ' . $numberOfPages;
		echo '</div>';
		
		if ($currentPage >= $numberOfPages) {
			echo '<li><button disabled="disabled">›</button></li>';
			echo '<li><button disabled="disabled">»</button></li>';
		} else {
			echo '<li><button type="submit" name="cpage" value="' . ($currentPage + 1) . '" form="ferieboliger-search-form">›</button></li>';
			echo '<li><button type="submit" name="cpage" value="' . $numberOfPages . '" form="ferieboliger-search-form">»</button></li>';
		}
		
		echo '</ul>';
		echo '</div>';
	}
    
    function groupAccommodationWithSuppliers() {
        
        $result = array();
	
	    $itemsPerPage = 5;
	    $currentPage = isset($_POST['cpage']) ? abs((int)$_POST['cpage']) : 1;
	    $offset = ($currentPage * $itemsPerPage) - $itemsPerPage;
	
	    $accommodationSearchCombine = '';
        $accommodationSearchStatus  = '';
	
	    if(isset($_POST['FerieboligerSearchCombine'])) {
		    $accommodationSearchCombine = $_POST['FerieboligerSearchCombine'];
	    }
        
        if(isset($_POST['FerieboligerStatus'])) {
	        $accommodationSearchStatus = $_POST['FerieboligerStatus'];
        }
        
        $suppliersList = getSuppliersList(
                false,
                true,
                true,
                5,
                $offset,
	            $accommodationSearchCombine,
                $accommodationSearchStatus
        );
        
        foreach ($suppliersList as $supplier) {
	        $result[$supplier->supplier_name] =
                getAccommodationsList(
                    false,
                    false,
                    -1,
                    0,
                    $supplier->supplier_id,
                    $accommodationSearchCombine,
                    $accommodationSearchStatus);
        }
        
        return $result;
    }
    
    function renderSuppliersOptions() {
        $suppliersList = getSuppliersList(false, false, true);
        
        $options = array();
        foreach ($suppliersList as $supplier) {
            $options[] = array(
                'value' => $supplier->supplier_id,
                'label' => $supplier->supplier_id . ' - ' . $supplier->supplier_name
            );
        }
        
        foreach ($options as $option) {
            echo '<option value="' . $option["value"] . '">'. $option["label"] .'</option>';
        }
    }
    
    function renderViewOptionsCheckboxes() {
        $viewOptions = array(
	        'ViewGroupAfterSupplier' => 'Grupper efter supplier',
            'ViewExceptionOnly'      => 'Vis undtagede boliger'
        );
        
        $isDefaultView = !isset($_POST['ViewGroupAfterSupplier'])
            && !isset($_POST['ViewExceptionOnly']);
        
        echo '<div class="view-options-wrapper">';
        foreach ($viewOptions as $optionName => $optionLabel) {
            $optionValue = '0';
	
	        echo '<div class="filter">';
	        echo '<label data-type="checkbox">';
	        echo '<input class="ferie-view-options-input" type="checkbox" name="' . $optionName . '"';
	        if (isset($_POST[$optionName])) {
		        $optionValue = $_POST[$optionName];
		
		        if($optionValue === '1') {
			        echo 'checked="checked"';
		        }
	        }
	        if($isDefaultView && $optionName === "ViewGroupAfterSupplier") {
		        echo 'checked="checked"';
	        }
	        echo '/>';
	        echo '<input class="ferie-view-options-hidden-input" type="hidden" name="' . $optionName . '" value="';
	        if(($isDefaultView && $optionName === "ViewGroupAfterSupplier") || $optionValue === '1') {
		        echo '1';
	        } else {
		        echo '0';
	        }
	        echo '"/>';
	        echo $optionLabel;
            echo '</label>';
            echo '</div>';
        }
        echo '</div>';
    }
    
	function renderFerieboligerStatusOptions()
	{
		$ferieboligerStatusList = array(
			'black'   => 'Vis alle',
			'oyr'     => 'mangler opdateringer',
			'green'   => 'opdaterede kalendre',
			'yellow'  => 'klar til første påmindelse (> 5 dage)',
			'orange'  => 'har modtaget første påmindelse',
			'red'     => 'klar til anden påmindelse (> 3 dage)'
		);
		
		$selectedFerieboligerStatus = $_POST['FerieboligerStatus'] ?? '';
		
		$accommodationSearchCombine = '';
		
		if(isset($_POST['FerieboligerSearchCombine'])) {
			$accommodationSearchCombine = $_POST['FerieboligerSearchCombine'];
		}
		
		foreach ($ferieboligerStatusList as $ferieboligerStatusValue => $ferieboligerStatusLabel) {
			$selected = $selectedFerieboligerStatus == $ferieboligerStatusValue;
			
            if($ferieboligerStatusValue != 'black') {
	            $ferieboligerStatusLabel =
		            getAccommodationsList(
			            true,
			            false,
			            -1,
			            0,
			            '',
			            $accommodationSearchCombine,
			            $ferieboligerStatusValue
		            )
		            .' '.
		            $ferieboligerStatusLabel;
            }
			
			if ($selected) {
				echo '<option value="'.$ferieboligerStatusValue.'" selected="selected">'
					.$ferieboligerStatusLabel
					.'</option>';
			} else {
				echo '<option value="'.$ferieboligerStatusValue.'">'
					.$ferieboligerStatusLabel
					.'</option>';
			}
		}
	}
    
    function renderResultByGroupSupplier() {
	    $accommodationsGroupBySupplierList = groupAccommodationWithSuppliers();
	
	    foreach ($accommodationsGroupBySupplierList as $supplierName => $accommodationsList) {
		
		    $supplierId = 0;
		
		    foreach ($accommodationsList as $accommodation) {
			    if($accommodation->accommodation_supplier_name === $supplierName) {
				    $supplierId = $accommodation->accommodation_supplier_id;
			    }
		    }
		    echo '<form method="POST" action="'.admin_url( 'admin.php' ).'?page=ferieboliger'.'">';
		    echo '<tr class="supplier-wrapper">';
		    echo '<td colspan="4" class="no-padding">';
		    echo '<table class="table-inside">';
		    echo '<thead>';
		    echo '<tr>';
		    echo '<th class="name-column" width="40%">';
		    echo '<input id="select-all-supplier-checkbox" type="checkbox"/>';
		    echo $supplierName;
		    echo '</th>';
		    echo '<th width="40%"></th>';
		    echo '<th width="15%" align="right">
                    <button class="btn btn-primary" name="SendReminder" value="'.$supplierId.'">
                        Send påmindelse
                    </button>
                </th>';
		    echo '<th width="5%"><input class="toggle-input exception-toggle-all" name="ExceptionBySupplier" value="'.$supplierId.'" type="checkbox" ';
		
		    $viewGroupAfterSupplier = '0';
		    if(isset($_POST['ViewGroupAfterSupplier'])) {
			    $viewGroupAfterSupplier = $_POST['ViewGroupAfterSupplier'];
		    }
		    if($viewGroupAfterSupplier == '0') {
			    echo ' disabled="disabled"';
		    }
		
		    if(isSupplierException($supplierId)) {
			    echo ' checked="checked"';
		    }
		
		    echo '/></th>';
		    echo '</tr>';
		    echo '</thead>';
		    echo '<tbody>';
		    echo '<tr>';
		    echo '</form>';
		    echo '<td colspan="4">';
		    echo '<form method="POST" action="'.admin_url( 'admin.php' ) . '?page=rediger_feriebolig'.'">';
		    echo '<table class="table table-child" border="1">';
		    echo '<tbody>';
		    foreach ($accommodationsList as $accommodation) {
			    $accommodationIcalStatus = getAccommodationIcalStatusById($accommodation->accommodation_id);
			
			    $exceptionStatus = getExceptionStatusByAccommodationId($accommodation->accommodation_id);
			
			    echo '<tr>';
			    echo '<td class="checkbox-wrapper checkbox-column" valign="middle">';
			    echo '<input class="accommodation-checkbox" value="'
				    .$accommodation->accommodation_id.'" type="checkbox">';
			    echo '</td>';
			    echo '<td class="status-column" width="20px" valign="middle">';
			    if($accommodationIcalStatus == '') {
				    $accommodationIcalStatus = 'grey';
			    }
			    if($accommodation->accommodation_exception_status == '1') {
				    $accommodationIcalStatus = 'green';
			    }
			    echo '<div class="accommodation-ical-status-' . $accommodationIcalStatus . '"></div>';
			    echo '</td>';
			    echo '<td class="name-column" width="38%" valign="middle">';
			    echo '<button class="name" type="submit" name="EditIcalLinkAccommodationId" value="'.$accommodation->accommodation_id.'">'.$accommodation->accommodation_name.'</button>';
			    echo '</td>';
			    echo '<td valign="middle">'.getAccommodationLatestUpdateById($accommodation->accommodation_id).'</td>';
			    echo '<td valign="middle" align="right"></td>';
			    echo '<td valign="middle" align="right">
                        <input class="toggle-input exception-toggle-accommodation"
                        name="ExceptionByAccommodation" value="'.$accommodation->accommodation_id.'"';
			
			    $viewGroupAfterSupplier = '0';
			    if(isset($_POST['ViewGroupAfterSupplier'])) {
				    $viewGroupAfterSupplier = $_POST['ViewGroupAfterSupplier'];
			    }
			    if($viewGroupAfterSupplier == '0') {
				    echo ' disabled="disabled"';
			    }
			
			    if($exceptionStatus == '1') {
				    echo ' checked="checked"';
			    }
			
			    echo 'type="checkbox" data-toggle="toggle"></td>';
			    echo '</tr>';
		    }
		    echo '</tbody>';
		    echo '</table>';
		    echo '</td>';
		    echo '</tr>';
		    echo '</tbody>';
		    echo '</table>';
		    echo '</form>';
	    }
    }
    
    function renderResultByList() {
	    echo '<tr>';
	    echo '<td class="table-child-wrapper" colspan="5">';
	    echo '<form method="POST" action="'.admin_url( 'admin.php' ) . '?page=rediger_feriebolig'.'">';
	    echo '<table class="table table-child">';
	
	    $itemsPerPage = 10;
	    $currentPage = isset($_POST['cpage']) ? abs((int)$_POST['cpage']) : 1;
	    $offset = ($currentPage * $itemsPerPage) - $itemsPerPage;
	
	    $accommodationSearchCombine = '';
	    $accommodationSearchStatus  = '';
	
	    if(isset($_POST['FerieboligerSearchCombine'])) {
		    $accommodationSearchCombine = $_POST['FerieboligerSearchCombine'];
	    }
	
	    if(isset($_POST['FerieboligerStatus'])) {
		    $accommodationSearchStatus = $_POST['FerieboligerStatus'];
	    }
     
	    $accommodationsList = getAccommodationsList(
                false,
                true,
		        $itemsPerPage,
                $offset,
                '',
		        $accommodationSearchCombine,
		        $accommodationSearchStatus
        );
     
	    foreach ($accommodationsList as $accommodation) {
		    $accommodationIcalStatus = getAccommodationIcalStatusById($accommodation->accommodation_id);
		
		    echo '<tr>';
		    echo '<td class="checkbox-wrapper checkbox-column" valign="middle">';
		    echo '<input class="accommodation-checkbox" value="'
			    .$accommodation->accommodation_id.'" type="checkbox">';
		    echo '</td>';
		    echo '<td class="status-column" width="20px" valign="middle">';
		    if($accommodationIcalStatus == '') {
			    $accommodationIcalStatus = 'grey';
		    }
		    if($accommodation->accommodation_exception_status == '1') {
			    $accommodationIcalStatus = 'green';
		    }
		    echo '<div class="accommodation-ical-status-' . $accommodationIcalStatus . '"></div>';
		    echo '</td>';
		    echo '<td class="name-column" width="25%" valign="middle">';
		    echo '<button class="name" type="submit" name="EditIcalLinkAccommodationId" value="'.$accommodation->accommodation_id.'">'.$accommodation->accommodation_name.'</button>';
		    echo '</td>';
		    echo '<td width="25%" valign="middle" class="supplier-column">'.$accommodation->accommodation_supplier_name.'</td>';
		    echo '<td valign="middle">'.getAccommodationLatestUpdateById($accommodation->accommodation_id).'</td>';
		    echo '<td width="17%" align="right" valign="middle"><input class="toggle-input"';
		    $viewGroupAfterSupplier = '0';
		    if(isset($_POST['ViewGroupAfterSupplier'])) {
			    $viewGroupAfterSupplier = $_POST['ViewGroupAfterSupplier'];
		    }
		    if($viewGroupAfterSupplier == '0') {
			    echo 'disabled="disabled"';
		    }
		    if($accommodation->accommodation_exception_status == '1') {
			    echo ' checked="checked"';
		    }
		    echo 'type="checkbox"></td>';
		    echo '</tr>';
	    }
	    echo '</table>';
        echo '</form>';
	    echo '</td>';
	    echo '</tr>';
    }
	
	$isDefaultView = !isset($_POST['ViewGroupAfterSupplier'])
		&& !isset($_POST['ViewExceptionOnly']);
	
	if($isDefaultView) {
		$_POST['ViewGroupAfterSupplier'] = '1';
	}
    
    if(isset($_POST['SetSupplier'])) {
        $action = $_POST['SetSupplier'];
        
        if($action == 'SetSupplier') {
            if(isset($_POST['SelectedAccommodationForSetSupplier']) && isset($_POST['SetSupplierId'])) {
                
                global $wpdb;
	
	            $tableNameAccommodation = $wpdb->prefix . 'localliving_plg_accommodations';
                
                $selectedAccommodationForSetSupplierList = $_POST['SelectedAccommodationForSetSupplier'];
                $setSupplierId                           = $_POST['SetSupplierId'];
                
                $explodedArr = explode(',' , $selectedAccommodationForSetSupplierList);
                
                foreach ($explodedArr as $selectedAccommodationId) {
                    $data = array (
                        'accommodation_supplier_id' => $setSupplierId
                    );
                    
                    $where = array (
                        'accommodation_id' => $selectedAccommodationId
                    );
                    
                    $wpdb->update($tableNameAccommodation, $data, $where);
                }
            }
        }
    }
    
    if(isset($_POST['SendReminder'])) {
        $sendReminderToSupplierId = $_POST['SendReminder'];
        
        $supplierDetail = getSupplierDetailById($sendReminderToSupplierId);
        
        if(isset($supplierDetail->supplier_email) && isset($supplierDetail->supplier_id)) {
            $supplierId    = $supplierDetail->supplier_id;
	        $supplierEmail = $supplierDetail->supplier_email;
            
            $yellowRemindersListOfThisSupplier = getYellowStatusRemindersBySupplierId($supplierId);
            
            if(count($yellowRemindersListOfThisSupplier) > 0) {
                foreach ($yellowRemindersListOfThisSupplier as $yellowReminder) {
	                $yellowReminderId = 0;
                    
                    if(isset($yellowReminder->reminder_id)) {
	                    $yellowReminderId = $yellowReminder->reminder_id;
                    }
                    
                    if($yellowReminderId == 0) {
                        continue;
                    }
                    
                    global $wpdb;
                    
                    $table_name_units_ical_reminders = $wpdb->prefix . 'localliving_plg_units_ical_reminders';
                    
                    $data = array(
                        'reminder_status' => 'orange',
                        'reminder_sent_timestamp' => time()
                    );
                    
                    $where = array(
                        'reminder_id' => $yellowReminderId
                    );
	
	                $wpdb->update($table_name_units_ical_reminders, $data, $where);
                }
            }
            
	        echo
		        '<script>
                    window.open("mailto:'.$supplierEmail.'", "_blank");
                </script>';
        }
    }
?>
<div class="loading-first-wrapper">
    <div class="loading-first">
        <div class="loader"></div>
    </div>
</div>
<div class="localliving-ferieboliger">
    <div class="header sticky top-menu">
        <div class="page-title top-menu-left">
            <h1>Ferieboliger</h1>
        </div>
        <div class="logo top-menu-center">
            <img src="<?php echo plugin_dir_url(__FILE__) . '/assets/images/localliving-logo.png' ?>"
                 title="Local Living Logo"
                 alt="localliving-logo"/>
        </div>
        <div class="top-menu-right">
            <div class="home-icon">
            <span class="cart-item-counter">
                <?php
	                $total = 0;
	
	                if (isset($_SESSION['localliving_cart'])) {
		                $cart = $_SESSION['localliving_cart'];
		
		                foreach ($cart as $selectedAccommodation) {
			                $total += count($selectedAccommodation);
		                }
	                }
	
	                echo $total;
                ?>
            </span>
                <a href="?page=generer_pdf">
                    <img width="40px" src="<?php echo plugin_dir_url(__FILE__) . '/assets/images/home-outline.svg' ?>"
                         title="Local Living Home"
                         alt="localliving-home"/>
                </a>
            </div>
        </div>
    </div>
    <div class="ferieboliger-wrapper">
        <div id="ferieboliger">
            <form id="ferieboliger-search-form" name="FerieboligerSearchForm" method="POST">
                <div class="filter-wrapper">
                    <div class="ferieboliger-status filter-status" data-color="<?php echo $_POST['FerieboligerStatus'] ?? 'black' ?>">
                        <select id="ferieboliger-status-select" name="FerieboligerStatus">
                            <?php renderFerieboligerStatusOptions(); ?>
                        </select>
                    </div>
                    <div class="ferieboliger-combine filter-search">
                        <input id="ferieboliger-combine-input"
                               name="FerieboligerSearchCombine"
                               placeholder="Søg på supplier eller bolignavn"
                               value="<?php echo $_POST['FerieboligerSearchCombine'] ?? '' ?>"
                        />
                    </div>
                    <div class="search-ferieboliger">
                        <button id="search-action-submit" class="btn btn-primary" name="SearchFerieboliger" value="SearchFerieboliger">
                            Søg
                        </button>
                    </div>
                    <div class="reset">
                        <button id="btn-reset"></button>
                    </div>
                </div>
                <div class="above-result-list-wrapper">
                    <div class="set-suppler">
                        <button id="set-supplier-btn" type="button" class="btn btn-primary" 
                            data-bs-toggle="modal"
                            data-bs-target="#set-supplier-modal"
                            disabled="disabled"
                            >
                            Set supplier
                        </button>
                    </div>
                    <?php renderViewOptionsCheckboxes(); ?>
                </div>
            </form>
            <div class="ferieboliger-result-list">
                <table border="0">
                    <thead>
                        <tr>
                            <?php
	                            if (isset($_POST['ViewGroupAfterSupplier'])) {
		                            $viewGroupAfterSupplier = $_POST['ViewGroupAfterSupplier'];
		
		                            if($viewGroupAfterSupplier == '1') {
			                            echo '
                                            <th width="40%" class="name-column">Accommodation</th>
                                            <th width="26px" class="status-column"></th>
                                            <th>Senest opdateret</th>
                                            <th align="right">Påmind / Undtagelse</th>
                                        ';
		                            } else {
                                        echo '
                                            <th width="2%" class="checkbox-wrapper"><input id="select-all-checkbox" type="checkbox"/></th>
                                            <th width="26%">Accommodation</th>
                                            <th width="25%">Supplier</th>
                                            <th width="30%">Senest opdateret</th>
                                            <th width="17%" align="right">Påmind / Undtagelse</th>
			                            ';
		                            }
	                            } else {
		                            echo '
                                        <th width="40%" class="name-column">Accommodation</th>
                                        <th width="26px" class="status-column"></th>
                                        <th>Senest opdateret</th>
                                        <th align="right">Påmind / Undtagelse</th>
                                    ';
	                            }
                            ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            if (isset($_POST['ViewGroupAfterSupplier'])) {
                                $viewGroupAfterSupplier = $_POST['ViewGroupAfterSupplier'];
    
                                if($viewGroupAfterSupplier == '1') {
                                    renderResultByGroupSupplier();
                                } else {
                                    renderResultByList();
                                }
                            } else {
                                renderResultByGroupSupplier();
                            }
                        ?>
                    </tbody>
                </table>
            </div>
	        <?php
		        $totalResult   = 0;
		        $itemsPerPage = 0;
		        $currentPage = isset($_POST['cpage']) ? abs((int)$_POST['cpage']) : 1;
		        $offset = ($currentPage * $itemsPerPage) - $itemsPerPage;
		        $searchCombine = '';
		        $searchStatus  = '';
		
		
		        $viewGroupAfterSupplier = '0';
		
		        if(isset($_POST['ViewGroupAfterSupplier'])) {
			        $viewGroupAfterSupplier = $_POST['ViewGroupAfterSupplier'];
		        }
		        if(isset($_POST['FerieboligerSearchCombine'])) {
			        $searchCombine = $_POST['FerieboligerSearchCombine'];
		        }
		        if(isset($_POST['FerieboligerStatus'])) {
			        $searchStatus = $_POST['FerieboligerStatus'];
		        }
		
		        if($viewGroupAfterSupplier == '1') {
			        $totalResult = getSuppliersList(
				        true,
				        false,
				        true,
				        -1,
				        0,
				        $searchCombine,
				        $searchStatus
			        );
			        $itemsPerPage = 5;
		        }
		
		        if($viewGroupAfterSupplier == '0') {
			        $totalResult = getAccommodationsList(
				        true,
				        false,
				        -1,
				        0,
				        '',
				        $searchCombine,
				        $searchStatus);
			        $itemsPerPage = 10;
		        }
		
		        if(isset($_POST['cpage'])) {
			        $currentPage = $_POST['cpage'];
		        }
		
		        renderPagination($totalResult, $itemsPerPage, $currentPage);
	        ?>
            <div id="set-supplier-modal" class="modal fade" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-body">
                            <form name="set-supplier-form" method="POST">
                                <div class="w-75 mx-auto">
                                    <h2 class="set-supplier-title">Set supplier</h2>
                                    <div class="accomodation-selected fw-bold">
                                        <span id="selected-accommodation-for-set-supplier-counter"></span> accommodation(s) selected
                                    </div>
                                    <div class="choose-supplier">Choose supplier</div>
                                    <div class="supplier-selector">
                                        <select id="supplier-select" name="SetSupplierId">
                                            <?php renderSuppliersOptions(); ?>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-primary py-3" name="SetSupplier" value="SetSupplier">Set supplier</button>
                                    <input id="selected-accommodation-for-set-supplier-hidden" type="hidden" name="SelectedAccommodationForSetSupplier"/>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</div>
