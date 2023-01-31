<?php

require_once(__DIR__ . '/vendor/autoload.php');
require_once(WP_PLUGIN_DIR . '/localliving-plugin/Rediger_FerieboligHelper.php');

$accommodationData           = array();
$editIcalLinkAccommodationId = '';

if(isset($_POST['EditIcalLinkAccommodationId']) || $_SESSION['EditIcalLinkAccommodationId']) {
    $editIcalLinkAccommodationId = $_POST['EditIcalLinkAccommodationId'] ?? '';
    
    if($editIcalLinkAccommodationId == '') {
        $editIcalLinkAccommodationId = $_SESSION['EditIcalLinkAccommodationId'] ?? '';
    }
    
    $_SESSION['EditIcalLinkAccommodationId'] = $editIcalLinkAccommodationId;
    $accommodationData = Rediger_FerieboligHelper::getAccommodationData($editIcalLinkAccommodationId);
}

add_action('admin_footer', function () {
	$editIcalLinkAccommodationId = '';
 
	if(isset($_POST['EditIcalLinkAccommodationId']) || $_SESSION['EditIcalLinkAccommodationId']) {
		$editIcalLinkAccommodationId = $_POST['EditIcalLinkAccommodationId'] ?? '';
		
		if($editIcalLinkAccommodationId == '') {
			$editIcalLinkAccommodationId = $_SESSION['EditIcalLinkAccommodationId'];
		}
		
		$_SESSION['EditIcalLinkAccommodationId'] = $editIcalLinkAccommodationId;
	}
    
    echo '
    <script>
    jQuery(document).ready(function ($) {
        var unitsListJson = `'.getUnitsListByAccommodationId($editIcalLinkAccommodationId, true).'`;
        var unitsList = JSON.parse(unitsListJson);
        
        console.log(unitsList);
        
        $(document).on("click", "#cancel-edit-ical-link", function(e) {
            e.preventDefault();
            
            window.location = "?page=ferieboliger";
        });
        
        //add row
        $(document).on("click", ".add-unit-type", function() {
            var unitId = $(this).data("unit-id");
            var mergeType = $(this).parents(".accommodation-unit-row").find(".select-merge-type").val();
            var html = ``;
            
            if(mergeType === "'.UNIT_MERGE_TYPE_UNIT_TYPES.'") {
                html += `
				<tr class="new-unit-type-row">
					<td><input class="w-100" type="text" name="NewUnitTypeName[`+unitId+`][]"/></td>
					<td></td>
					<td></td>
					<td align="center">
						<span class="icon remove-new-unit-type" data-unit-id="`+unitId+`">−</span>
					</td>
					<td class="ical-link-column"><input class="w-100" type="text" name="NewUnitTypeIcalLink[`+unitId+`][]"/></td>
				</tr>
			`;
            } else if (mergeType === "'.UNIT_MERGE_TYPE_MERGE_UNITS.'") {
                html = `
                <tr class="new-unit-type-row">
                    <td>
                        <select class="w-100 merged-unit-type-select" type="text" name="NewUnitTypeName[`+unitId+`][]">
                    `;
                $.map(unitsList, function( value ){
                    var unit_id = value.unit_id !== null ? value.unit_id : "";
                    var reminder_ical_link = value.reminder_ical_link !== null ? value.reminder_ical_link : "";
                    var unit_name = value.unit_name !== null ? value.unit_name : "";
                    var is_merge_unit = value.is_merge_unit !== null ? value.is_merge_unit : "0";
                    
                    if(is_merge_unit === "1") {
                        html += `<option
                            value="`+unit_id+`"
                            data-ical-link="`+reminder_ical_link+`"
                            disabled
                            >` + unit_name + `</option>`;
                    } else {
                        html += `<option
                            value="`+unit_id+`"
                            data-ical-link="`+reminder_ical_link+`"
                            >`+unit_name+`</option>`;
                    }
                });
                var first_reminder_ical_link_index = unitsList.findIndex(element => element.is_merge_unit === "0");
                var first_reminder_ical_link =
                    unitsList[first_reminder_ical_link_index].reminder_ical_link !== null
                    ? unitsList[first_reminder_ical_link_index].reminder_ical_link
                    : "";
                html += `
                        </select>
                    </td>
                        <td></td>
                        <td></td>
                        <td align="center">
                            <span class="icon remove-new-unit-type" data-unit-id="`+unitId+`">−</span>
                        </td>
                        <td class="ical-link-column">
                            <input class="w-100" type="text" name="NewUnitTypeIcalLink[`+unitId+`][]" value="`+first_reminder_ical_link+`" readonly/>
                        </td>
                    </tr>
                `;
            }
            
            $(this).closest("tr").after(html);
            $(`.select-merge-type[data-unit-id="`+unitId+`"]`).attr("disabled", true);
        });
        
        $(document).on("click", ".remove-new-unit-type", function () {
            var unitId = $(this).data("unit-id");
            
            var counter = $(this).parents(".ical-table-unit").find("tr.new-unit-type-row").length
            + $(this).parents(".ical-table-unit").find("tr.unit-type-row").length;
            
            console.log(counter);
            
            if(counter <= 1) {
                $(`.select-merge-type[data-unit-id="`+unitId+`"]`).attr("disabled", false);
            }
            
            $(this).closest("tr").remove();
        });
        
        $(document).on("click", ".remove-unit-type", function (event) {
            var unitTypeId   = $(this).val();
            var unitTypeName = $("input[name=\'UnitTypeName["+unitTypeId+"]\'").val();
            if(unitTypeName === undefined) {
                unitTypeName = $(this).parents("tr.unit-type-row").find(":selected").text();
            }
            var message   = "Are you sure to delete this Unit Type? (" + $.trim(unitTypeName) + ")\n" +
             "This action is cannot be undone";
            
            if(!confirm(message)) {
                event.preventDefault();
            }
        });
        
        $(document).on("change", ".ajax-merge-type-update", function() {
            var unitId = $(this).data("unit-id");
            var mergeTypeValue = $(this).val();
            
            $(`.add-unit-type[data-unit-id="`+unitId+`"]`).addClass("d-none");
            
            $.ajax({
                type: "POST",
                url: "'. admin_url("admin-ajax.php") .'",
                data: {
                   action: "unit_merge_type_update",
                   editUnitId: unitId,
                   mergeTypeValue: mergeTypeValue
                },
                context: this,
                success: function() {
                var addUnitTypeSpan = $(`.add-unit-type[data-unit-id="`+unitId+`"]`);
                    if( mergeTypeValue !== "'.UNIT_MERGE_TYPE_DEFAULT.'" ) {
                        addUnitTypeSpan.removeClass("d-none");
                    }
                }
            });
        });
        
        $(document).on("change", ".merged-unit-type-select", function() {
            var icalLink = $(this).find(":selected").data("ical-link");
            
            $(this).parents("tr.new-unit-type-row").find("input").val(icalLink);
            $(this).parents("tr.unit-type-row").find("input").val(icalLink);
        });
        
        $(".generate-csv-mode").parent().on("click", function() {
            var selector = $(this).find(".generate-csv-mode")[0];
            var toggleStatus = $(selector).prop("checked") ? "off" : "on";
            var editUnitId = $(selector).data("unit-id");
            
            $.ajax({
                type: "POST",
                dataType: "json",
                url: "' . admin_url("admin-ajax.php") . '",
                data: {
                   action: "toggle_unit_generate_csv_mode",
                   editUnitId: editUnitId,
                   mode: toggleStatus
                },
                context: this,
                success: function(response) {
                    console.log(response);
                }
           });
        });
    });
    </script>
    ';
});

function getUnitsListByAccommodationId($accommodationId, $returnJson = false)
{
	global $wpdb;
	
	$table_name_units                     = $wpdb->prefix . 'localliving_plg_units';
    $table_name_units_ical_reminders      = $wpdb->prefix . 'localliving_plg_units_ical_reminders';
	$table_name_unit_types_ical_reminders = $wpdb->prefix . 'localliving_plg_unit_types_ical_reminders';
	
	$query = "SELECT *, IF($table_name_unit_types_ical_reminders.unit_type_id IS NOT NULL, 1, 0) AS is_merge_unit
        FROM $table_name_units
        LEFT JOIN $table_name_units_ical_reminders
            ON $table_name_units_ical_reminders.reminder_unit_id = $table_name_units.unit_id
        LEFT JOIN $table_name_unit_types_ical_reminders
            ON $table_name_units.unit_id = $table_name_unit_types_ical_reminders.unit_type_name
        WHERE $table_name_units.unit_accommodation_id = $accommodationId";
    
    $queryResult = $wpdb->get_results($query);
    
    if($returnJson) {
        return json_encode($queryResult);
    }
	
	return $queryResult;
}

function getUnitTypeById($unitTypeId) {
	global $wpdb;
	
	$result = false;
	
	if($unitTypeId !== '') {
		$table_name_unit_types_ical_reminders = $wpdb->prefix  . 'localliving_plg_unit_types_ical_reminders';
		
		$query = "SELECT *
		FROM $table_name_unit_types_ical_reminders
		WHERE $table_name_unit_types_ical_reminders.unit_type_id = $unitTypeId";
        
        $result = $wpdb->get_row($query);
    }
    
    return $result;
}

function getUnitIcalStatusById($unitId) {
	global $wpdb;
	
	$table_name_units_ical_reminders = $wpdb->prefix . 'localliving_plg_units_ical_reminders';
    
    $result = '';
	
	$query = "SELECT $table_name_units_ical_reminders.reminder_status
            FROM $table_name_units_ical_reminders
            WHERE $table_name_units_ical_reminders.reminder_unit_id = $unitId";
	
	$obj = $wpdb->get_row($query);
	
	if(!is_null($obj)) {
		$result = $obj->reminder_status;
	}
	
	return $result;
}

function checkIfUnitTypeNameIsExisted($unitTypeName, $unitId) {
	global $wpdb;
    
    $result = false;
	
    if($unitTypeName !== '') {
	    $table_name_unit_types_ical_reminders = $wpdb->prefix  . 'localliving_plg_unit_types_ical_reminders';
     
        $query = "SELECT *
                FROM $table_name_unit_types_ical_reminders
                WHERE $table_name_unit_types_ical_reminders.unit_type_name = '$unitTypeName'
                AND $table_name_unit_types_ical_reminders.unit_type_unit_id = $unitId";
        
        $count = $wpdb->get_var("SELECT COUNT(1) FROM ($query) AS combined_table");
        
        if($count >= 1) {
            $result = true;
        }
    }
    
    return $result;
}

function renderUnitMergeTypeOptions($selectedValue) {
    $unitMergeTypeOptions = array(
	    UNIT_MERGE_TYPE_DEFAULT     => "Default",
        UNIT_MERGE_TYPE_UNIT_TYPES  => "Unit Types",
        UNIT_MERGE_TYPE_MERGE_UNITS => "Flet"
    );
	
	foreach ($unitMergeTypeOptions as $unitMergeTypeValue => $unitMergeTypeLabel) {
        if($selectedValue == $unitMergeTypeValue) {
	        echo '<option value="' . $unitMergeTypeValue . '" selected="selected">'
		        . $unitMergeTypeLabel
		        . '</option>';
        } else {
	        echo '<option value="' . $unitMergeTypeValue . '">'
		        . $unitMergeTypeLabel
		        . '</option>';
        }
    }
}

if(isset($_POST['SaveEditIcalLink'])) {
    $saveEditIcalLink = $_POST['SaveEditIcalLink'];
    
    if($saveEditIcalLink === 'SaveEditIcalLink') {
        global $wpdb;
	
        $table_name_units                     = $wpdb->prefix . 'localliving_plg_units';
	    $table_name_units_ical_reminders      = $wpdb->prefix . 'localliving_plg_units_ical_reminders';
	    $table_name_unit_types_ical_reminders = $wpdb->prefix . 'localliving_plg_unit_types_ical_reminders';
	    $icalLinkList                    = array();
        $query                           = "";
        
        if(isset($_POST['IcalLink'])) {
            $icalLinkList = $_POST['IcalLink'];
            
            foreach ($icalLinkList as $unitId => $icalLink) {
                //ignore empty input
                if ($icalLink === '') {
                    continue;
                }
                
                $icalLink = trim($icalLink);

                $query = "SELECT $table_name_units_ical_reminders.reminder_unit_id
                        FROM $table_name_units_ical_reminders
                        WHERE $table_name_units_ical_reminders.reminder_unit_id = $unitId";
                
                $isExist = !is_null($wpdb->get_var($query));
                
                if(!$isExist) {
                    $data = array(
                        "reminder_unit_id"           => $unitId,
                        "reminder_ical_link"         => $icalLink,
                        "reminder_status"            => "green",
                        "reminder_created_timestamp" => time(),
	                    "reminder_updated_timestamp" => time(),
                    );
                    
                    $wpdb->insert($table_name_units_ical_reminders, $data);
                } else {
                    $oldIcalLink = Rediger_FerieboligHelper::getUnitIcalLinkById($unitId);
                    
                    if($oldIcalLink !== $icalLink) {
	                    $data = array(
		                    "reminder_ical_link"         => $icalLink,
		                    "reminder_status"            => "green",
		                    "reminder_updated_timestamp" => time()
	                    );
	
	                    $where = array(
		                    "reminder_unit_id" => $unitId
	                    );
	
	                    $wpdb->update($table_name_units_ical_reminders, $data, $where);
                        
                        //update as unit type when this unit is merged unit
	                    $data = array(
                            "unit_type_ical_link" => $icalLink
                        );
                        
                        $where = array(
                            "unit_type_name" => $unitId
                        );
                        
                        $wpdb->update($table_name_unit_types_ical_reminders, $data, $where);
                    }
                }
            }
        }
	
	    if(isset($_POST['NewUnitTypeName']) && isset($_POST['NewUnitTypeIcalLink'])) {
		    $newUnitTypeNameOfUnit     = $_POST['NewUnitTypeName'];
		    $newUnitTypeIcalLinkOfUnit = $_POST['NewUnitTypeIcalLink'];
		
		    global $wpdb;
		
		    $table_name_unit_types_ical_reminders = $wpdb->prefix . 'localliving_plg_unit_types_ical_reminders';
		
		    foreach ($newUnitTypeNameOfUnit as $unitId => $newUnitTypeNamesList) {
			    foreach ($newUnitTypeNamesList as $index => $newUnitTypeName) {
				    $unitTypeNameExisted = checkIfUnitTypeNameIsExisted($newUnitTypeName, $unitId);
				
				    //prevent re-insert when reload
				    if(!$unitTypeNameExisted) {
					    $data = array(
						    'unit_type_unit_id'   => $unitId,
						    'unit_type_name'      => $newUnitTypeName,
						    'unit_type_ical_link' => $newUnitTypeIcalLinkOfUnit[$unitId][$index]
					    );
					
					    $wpdb->insert($table_name_unit_types_ical_reminders, $data);
					
					    $query = "SELECT $table_name_units_ical_reminders.reminder_unit_id
                        FROM $table_name_units_ical_reminders
                        WHERE $table_name_units_ical_reminders.reminder_unit_id = $unitId";
					
					    $unitIcalIsExisted = !is_null($wpdb->get_var($query));
					
					    if($unitIcalIsExisted)
					    {
						    $data = array(
							    "reminder_ical_link"         => '',
							    "reminder_status"            => "green",
							    "reminder_updated_timestamp" => time()
						    );
						
						    $where = array(
							    "reminder_unit_id" => $unitId
						    );
						
						    $wpdb->update($table_name_units_ical_reminders, $data, $where);
					    } else
					    {
						    $data = array(
							    "reminder_unit_id"           => $unitId,
							    "reminder_ical_link"         => '',
							    "reminder_status"            => "green",
							    "reminder_created_timestamp" => time(),
							    "reminder_updated_timestamp" => time(),
						    );
						
						    $wpdb->insert($table_name_units_ical_reminders, $data);
					    }
					
					    $unitTypeIsMergedUnit  = !is_null(Rediger_FerieboligHelper::getUnitInfoById($newUnitTypeName));
					
					    if($unitTypeIsMergedUnit) {
						    $data = array(
							    'unit_type_ical_link' => Rediger_FerieboligHelper::getUnitIcalLinkById($newUnitTypeName)
						    );
						
						    $where = array(
							    "unit_type_name" => $newUnitTypeName
						    );
						
						    $wpdb->update($table_name_unit_types_ical_reminders, $data, $where);
					    }
				    }
			    }
		    }
	    }
	
	    if(isset($_POST['UnitTypeName']) && isset($_POST['UnitTypeIcalLink'])) {
		    $updateUnitTypeNameArr     = $_POST['UnitTypeName'];
		    $updateUnitTypeIcalLinkArr = $_POST['UnitTypeIcalLink'];
		
		    foreach ($updateUnitTypeNameArr as $updateUnitTypeId => $updateUnitTypeName) {
			    $thisUnitTypeObj = getUnitTypeById($updateUnitTypeId);
			
			    if($thisUnitTypeObj !== false) {
				    $thisUnitTypeName   = $thisUnitTypeObj->unit_type_name;
				    $thisUnitTypeUnitId = $thisUnitTypeObj->unit_type_unit_id;
				    $unitTypeNameExisted = checkIfUnitTypeNameIsExisted($updateUnitTypeName, $thisUnitTypeUnitId);
				
				    if(($updateUnitTypeName !== $thisUnitTypeName) && !$unitTypeNameExisted) {
					    $data = array(
						    'unit_type_name' => $updateUnitTypeName
					    );
					
					    $where = array(
						    'unit_type_id' => $updateUnitTypeId
					    );
					
					    $wpdb->update($table_name_unit_types_ical_reminders, $data, $where);
					
					    $query = "SELECT $table_name_units_ical_reminders.reminder_unit_id
                        FROM $table_name_units_ical_reminders
                        WHERE $table_name_units_ical_reminders.reminder_unit_id = $thisUnitTypeUnitId";
					
					    $isExist = !is_null($wpdb->get_var($query));
					
					    if(!$isExist) {
						    $data = array(
							    "reminder_unit_id" => $thisUnitTypeUnitId,
							    "reminder_ical_link" => "",
							    "reminder_status" => "green",
							    "reminder_created_timestamp" => time(),
							    "reminder_updated_timestamp" => time(),
						    );
						
						    $wpdb->insert($table_name_units_ical_reminders, $data);
					    } else {
						    $data = array(
							    "reminder_ical_link"         => "",
							    "reminder_status"            => "green",
							    "reminder_updated_timestamp" => time()
						    );
						
						    $where = array(
							    "reminder_unit_id" => $thisUnitTypeUnitId
						    );
						
						    $wpdb->update($table_name_units_ical_reminders, $data, $where);
					    }
				    }
			    }
		    }
		
		    foreach ($updateUnitTypeIcalLinkArr as $updateUnitTypeId => $updateUnitTypeIcalLink) {
			    $thisUnitTypeObj = getUnitTypeById($updateUnitTypeId);
			
			    if($thisUnitTypeObj !== false) {
				    $thisUnitTypeIcalLink  = $thisUnitTypeObj->unit_type_ical_link;
				    $thisUnitTypeUnitId    = $thisUnitTypeObj->unit_type_unit_id;
				    $thisUnitTypeName      = $thisUnitTypeObj->unit_type_name;
				
				    $unitTypeIsMergedUnit  = !is_null(Rediger_FerieboligHelper::getUnitInfoById($thisUnitTypeName));
				
				    if($updateUnitTypeIcalLink !== $thisUnitTypeIcalLink) {
					    if(!$unitTypeIsMergedUnit) {
						    $data = array(
							    'unit_type_ical_link' => $updateUnitTypeIcalLink
						    );
					    } else {
						    $data = array(
							    'unit_type_ical_link' => Rediger_FerieboligHelper::getUnitIcalLinkById($thisUnitTypeName)
						    );
					    }
					
					    $where = array(
						    'unit_type_id' => $updateUnitTypeId
					    );
					
					    $wpdb->update($table_name_unit_types_ical_reminders, $data, $where);
					
					    $data = array(
						    "reminder_ical_link"         => "",
						    "reminder_status"            => "green",
						    "reminder_updated_timestamp" => time()
					    );
					
					    $where = array(
						    "reminder_unit_id" => $thisUnitTypeUnitId
					    );
					
					    $wpdb->update($table_name_units_ical_reminders, $data, $where);
				    }
			    }
		    }
	    }
        
        if(isset($_POST['UnitUniqueRef'])) {
            $unitUniqueRefsList = $_POST['UnitUniqueRef'];
	
	        foreach ($unitUniqueRefsList as $unitId => $unitUniqueRef) {
		        //ignore empty input
		        if ($unitUniqueRef === '') {
			        continue;
		        }
		
		        $data = array(
			        "unit_unique_ref" => $unitUniqueRef
		        );
		
		        $where = array(
			        "unit_id" => $unitId
		        );
		
		        $wpdb->update($table_name_units, $data, $where);
            }
        }
    }
}

if(isset($_POST['RemoveUnitType'])) {
    $removeUnitTypeId = $_POST['RemoveUnitType'];
    
    if($removeUnitTypeId != '') {
        global $wpdb;
        
        $table_name_unit_types_ical_reminders = $wpdb->prefix . 'localliving_plg_unit_types_ical_reminders';
	
	    $wpdb->delete($table_name_unit_types_ical_reminders, array( 'unit_type_id' => $removeUnitTypeId ));
    }
}

$generateCsvByYearAction = $_POST['GenerateCsvByYear'] ?? '';
$yearGenerateCsv         = $_POST['YearGenerate']      ?? date("Y");

if($generateCsvByYearAction == 'GenerateCsvByYear') {
    Rediger_FerieboligHelper::generateCsvByYear($yearGenerateCsv, $editIcalLinkAccommodationId);
}
?>
<div class="loading-first-wrapper">
	<div class="loading-first">
		<div class="loader"></div>
	</div>
</div>
<div class="localliving-rediger-feriebolig">
	<div class="header sticky top-menu">
		<div class="page-title top-menu-left">
			<h1>Rediger feriebolig</h1>
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
		
		                foreach ($cart as $selectedAccommodations) {
			                foreach ($selectedAccommodations as $key => $selectedAccommodation) {
				                if(is_numeric($key)) {
					                $total += 1;
				                }
			                }
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
	<?php if($editIcalLinkAccommodationId !== '' && isset($accommodationData[0])) : ?>
	<div class="edit-ical-link">
		<form name="edit-ical-link-form" method="POST">
			<h2 class="edit-ical-link-title">Edit iCal link</h2>
			<div class="d-flex flex-wrap edit-ical-section">
				<div class="w-100 edit-ical-label">Supplier</div>
				<input id="supplier-name-input" type="text" value="<?php echo $accommodationData[0]['supplier_name'] ?>" disabled/>
			</div>
			<div class="d-flex">
				<div class="d-flex flex-wrap edit-ical-section">
					<div class="w-100 edit-ical-label">Property</div>
					<input id="accommodation-name-input" type="text"
                           value="<?php
                               echo $accommodationData[0]['accommodation_id']
                                   . ' - '
                                   .$accommodationData[0]['accommodation_name'] ?>"
                           disabled/>
				</div>
				<div class="download-csv">
					<div class="w-100 edit-ical-label">Download CSV file</div>
					<div class="d-flex">
                        <input type="text" id="generate-csv-year-input" name="YearGenerate" value="<?php echo date("Y"); ?>"/>
                        <button type="submit" name="GenerateCsvByYear" value="GenerateCsvByYear">Download CSV</button>
					</div>
				</div>
			</div>
			
			<div class="ical-wrapper">
				<table class="ical-table" width="100%">
					<tr>
                        <th width="20%">Unit</th>
                        <th width="5%"></th>
                        <th width="10%"></th>
                        <th width="5%"></th>
						<th width="40%" class="ical-link-column">iCal link</th>
                        <th width="20%">Unique ref</th>
					</tr>
					<tr>
						<td colspan="7">
							<table class="ical-table-inside table" border="1" width="100%">
								<?php foreach ($accommodationData as $data) : ?>
									<?php
										$unitId         = $data["unit"]["unit_id"];
										$icalLink       = Rediger_FerieboligHelper::getUnitIcalLinkById($unitId) ?? '';
									    $unitTypesList  = Rediger_FerieboligHelper::getUnitTypesListByUnitId($unitId);
                                        $isTerretrusche = $data['supplier_name'] === 'Terretrusche';
									
									    $unitIcalStatus = getUnitIcalStatusById($unitId);
                                        if($unitIcalStatus == '') {
	                                        $unitIcalStatus = 'grey';
                                        }
									
									    $unitInfo            = Rediger_FerieboligHelper::getUnitInfoById($unitId);
									    $unitUniqueRef       = $unitInfo->unit_unique_ref        ?? '';
                                        $unitMergeType       = $unitInfo->unit_merge_type        ?? UNIT_MERGE_TYPE_UNIT_TYPES;
                                        $unitGenerateCsvMode = $unitInfo->unit_generate_csv_mode ?? 0;
									?>
									<tr class="accommodation-unit-row">
										<td class="ical-table-unit">
											<table width="100%">
												<tr>
													<th width="15%" class="text-uppercase fw-normal" valign="middle">
                                                        <div class="d-flex align-items-center">
															<div class="accommodation-ical-status-<?php echo $unitIcalStatus; ?> m-0 me-2"></div>
															<?php echo $data["unit"]["unit_name"] ?>
														</div>
                                                    </th>
                                                    <th width="5%">
                                                        <div class="set-available">
                                                            <div class="toggle-wrapper d-flex">
                                                                <label>
                                                                    <input name="SetAvailableMode"
                                                                           class="toggle-input generate-csv-mode"
                                                                           type="checkbox"
                                                                           data-unit-id="<?php echo $unitId; ?>"
                                                                           <?php echo $unitGenerateCsvMode == '1' ? 'checked="checked"' : '' ?>
                                                                    >
                                                                    <span class="toggle-tooltip">Set available if 1 unit type is free</span>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </th>
                                                    <th width="10%">
                                                        <select class="ajax-merge-type-update select-merge-type w-100"
                                                                data-unit-id="<?php echo $unitId; ?>"
                                                                <?php echo count($unitTypesList) > 0 ? "disabled" : "" ?>
                                                                <?php echo $isTerretrusche ? "disabled" : "" ?>
                                                        >
                                                            <?php renderUnitMergeTypeOptions($unitMergeType); ?>
                                                        </select>
                                                    </th>
                                                    <th width="5%" align="center">
                                                        <?php if($unitMergeType == UNIT_MERGE_TYPE_DEFAULT) : ?>
                                                            <span class="icon add-unit-type d-none" data-unit-id="<?php echo $unitId; ?>">+</span>
                                                        <?php else : ?>
                                                            <span class="icon add-unit-type" data-unit-id="<?php echo $unitId; ?>">+</span>
                                                        <?php endif; ?>
                                                    </th>
													<th width="40%" class="ical-link-column">
                                                        <?php if(count($unitTypesList) <= 0) :?>
														<input class="w-100" type="text" name="IcalLink[<?php echo $unitId; ?>]" value="<?php echo $icalLink; ?>" <?php echo $isTerretrusche ? 'disabled' : ''; ?>/>
                                                        <?php endif; ?>
                                                    </th>
                                                    <th width="20%">
                                                    	<input class="w-100" type="text" name="UnitUniqueRef[<?php echo $unitId; ?>]" value="<?php echo $unitUniqueRef; ?>"/>
                                                    </th>
												</tr>
												<?php if(count($unitTypesList) > 0): ?>
													    <?php foreach ($unitTypesList as $unitType): ?>
														<?php
															$unitTypeIcalLink = $unitType->unit_type_ical_link;
															$unitTypeId       = $unitType->unit_type_id;
															$unitTypeName     = $unitType->unit_type_name;
														?>
												        <?php if($unitMergeType == UNIT_MERGE_TYPE_UNIT_TYPES) : ?>
														<tr class="unit-type-row">
															<td>
																<input class="w-100" type="text" name="UnitTypeName[<?php echo $unitTypeId ?>]" value="<?php echo $unitTypeName ?>"/>
                                                            </td>
                                                            <td></td>
                                                            <td></td>
                                                            <td align="center">
                                                                <button type="submit" name="RemoveUnitType" value="<?php echo $unitTypeId ?>" class="remove-unit-type icon">−</button>
                                                            </td>
															<td class="ical-link-column">
                                                            	<input class="w-100" type="text" name="UnitTypeIcalLink[<?php echo $unitTypeId ?>]" value="<?php echo $unitTypeIcalLink ?>"/>
                                                            </td>
														</tr>
														<?php elseif ($unitMergeType == UNIT_MERGE_TYPE_MERGE_UNITS) : ?>
                                                            <tr class="unit-type-row">
                                                                <td>
                                                                    <select class="w-100 merged-unit-type-select" name="UnitTypeName[<?php echo $unitTypeId ?>]">
	                                                                    <?php
		                                                                    $unitsList = getUnitsListByAccommodationId($editIcalLinkAccommodationId);
                                                                            foreach ($unitsList as $unit) :
                                                                            $selected    = $unitTypeName == $unit->unit_id;
                                                                            $isMergeUnit = $unit->is_merge_unit == 1;
                                                                            if($selected) {
	                                                                            $unitTypeIcalLink
                                                                                    = !is_null($unit->reminder_ical_link) ? $unit->reminder_ical_link : '';
                                                                            }
	                                                                    ?>
                                                                            <option value="<?php echo $unit->unit_id; ?>"
                                                                                data-ical-link="<?php echo $unit->reminder_ical_link; ?>"
                                                                                <?php echo $selected ? 'selected' : '' ?>
	                                                                            <?php echo $isMergeUnit ? 'disabled' : '' ?>
                                                                            >
                                                                                <?php echo $unit->unit_name; ?>
                                                                            </option>
                                                                        <?php endforeach; ?>
                                                                    </select>
                                                                </td>
                                                                <td></td>
                                                                <td></td>
                                                                <td align="center">
                                                                    <button type="submit" name="RemoveUnitType" value="<?php echo $unitTypeId ?>" class="remove-unit-type icon">−</button>
                                                                </td>
                                                                <td class="ical-link-column">
                                                                    <input class="w-100" type="text" name="UnitTypeIcalLink[<?php echo $unitTypeId ?>]" value="<?php echo $unitTypeIcalLink ?>" readonly/>
                                                                </td>
                                                            </tr>
														<?php endif; ?>
													<?php endforeach; ?>
												<?php endif; ?>
											</table>
										</td>
									</tr>
								<?php endforeach; ?>
							</table>
						</td>
					</tr>
				</table>
			</div>
			<div class="d-flex footer-btn-wrapper">
				<button class="footer-btn-save btn btn-primary w-auto" type="submit" name="SaveEditIcalLink" value="SaveEditIcalLink">Gem</button>
				<button class="footer-btn-cancel btn btn-light w-auto" id="cancel-edit-ical-link">Annuller</button>
			</div>
			<input type="hidden" name="EditIcalLinkAccommodationId" value="<?php echo $_POST['EditIcalLinkAccommodationId'] ?? '' ?>"/>
		</form>
	</div>
	<?php else : ?>
		Invalid Accommodation! Please re-select an Accommodation and make sure it set Supplier already.
	<?php endif; ?>
</div>