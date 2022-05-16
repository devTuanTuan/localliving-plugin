<?php

require_once(__DIR__ . '/vendor/autoload.php');

use League\Csv\Reader;
use League\Csv\Writer;

include_once ('ics.php');

const WEEK_START_SATURDAY = -1;
const WEEK_END_SATURDAY   = 6;

add_action('admin_footer', function () {
    echo '
    <script>
    jQuery(document).ready(function ($) {
        $(document).on("click", "#cancel-edit-ical-link", function(e) {
            e.preventDefault();
            
            window.location = "?page=ferieboliger";
        });
        
        //add row
        $(document).on("click", ".add-unit-type", function() {
            var unitId = $(this).data("unit-id");
            
			var html = `
				<tr>
					<td><input class="w-100" type="text" name="NewUnitTypeName[`+unitId+`][]"/></td>
					<td align="center">
						<span class="icon remove-new-unit-type">−</span>
					</td>
					<td class="ical-link-column"><input class="w-100" type="text" name="NewUnitTypeIcalLink[`+unitId+`][]"/></td>
				</tr>
			`;
            
            $(this).closest("tr").after(html);
        });
        
        $(document).on("click", ".remove-new-unit-type", function () {
            
            $(this).closest("tr").remove();
        });
        
        $(document).on("click", ".remove-unit-type", function (event) {
            var unitTypeId   = $(this).val();
            var unitTypeName = $("input[name=\'UnitTypeName["+unitTypeId+"]\'").val();
            var message   = "Are you sure to delete this Unit Type? (" + unitTypeName + ")\n" +
             "This action is cannot be undone";
            
            if(!confirm(message)) {
                event.preventDefault();
            }
        });
        
        $("#generate-csv-mode").parent().on("click", function() {
            var selector = $(this).find("#generate-csv-mode")[0];
            var toggleStatus = $(selector).prop("checked") ? "off" : "on";
            var editAccommodationId = $(selector).data("accommodation-id");
            
            $.ajax({
                type: "POST",
                dataType: "json",
                url: "' . admin_url("admin-ajax.php") . '",
                data: {
                   action: "toggle_generate_csv_mode",
                   editAccommodationId: editAccommodationId,
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

function getAccommodationDataById($accommodationId)
	{
		global $wpdb;
		
		$result = array();
		
		if($accommodationId !== '') {
			
			$table_name_suppliers      = $wpdb->prefix  . 'localliving_plg_suppliers';
			$table_name_accommodations = $wpdb->prefix . 'localliving_plg_accommodations';
			$table_name_units          = $wpdb->prefix . 'localliving_plg_units';
			
			$query = "SELECT *
			FROM $table_name_accommodations
			JOIN $table_name_suppliers ON $table_name_accommodations.accommodation_supplier_id = $table_name_suppliers.supplier_id
			JOIN $table_name_units ON $table_name_accommodations.accommodation_id = $table_name_units.unit_accommodation_id
			WHERE $table_name_accommodations.accommodation_id = $accommodationId";
			
			$returnedDataList = $wpdb->get_results($query);
			
			foreach ($returnedDataList as $returnedData) {
				$result[] = array(
					"accommodation_id"                => $returnedData->accommodation_id,
					"accommodation_name"              => $returnedData->accommodation_name,
                    "accommodation_csv_generate_mode" => $returnedData->accommodation_csv_generate_mode,
					"supplier_name"                   => $returnedData->supplier_name,
					"unit"               => array(
						"unit_id"   => $returnedData->unit_id,
						"unit_name" => $returnedData->unit_name
					)
				);
			}
		}
		
		return $result;
	}
 
function getUnitTypesListByUnitId($unitId) {
 
	global $wpdb;
	
	$result = array();
	
	if($unitId !== '') {
		$table_name_unit_types_ical_reminders = $wpdb->prefix  . 'localliving_plg_unit_types_ical_reminders';
		
		$query = "SELECT *
		FROM $table_name_unit_types_ical_reminders
		WHERE $table_name_unit_types_ical_reminders.unit_type_unit_id = $unitId";
		
		$result = $wpdb->get_results($query);
    }
    
    return $result;
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
 
function getUnitIcalLinkById($unitId) {
    global $wpdb;
	
	$table_name_units_ical_reminders = $wpdb->prefix . 'localliving_plg_units_ical_reminders';
    
    $result = '';
 
    $query = "SELECT $table_name_units_ical_reminders.reminder_ical_link
            FROM $table_name_units_ical_reminders
            WHERE $table_name_units_ical_reminders.reminder_unit_id = $unitId";
    
    $obj = $wpdb->get_row($query);
    
    if(!is_null($obj)) {
        $result = $obj->reminder_ical_link;
    }
    
    return $result;
}

function getUnitUniqueRefById($unitId) {
	global $wpdb;
	
	$table_name_units = $wpdb->prefix . 'localliving_plg_units';
	
	$result = '';
	
	$query = "SELECT $table_name_units.unit_unique_ref
            FROM $table_name_units
            WHERE $table_name_units.unit_id = $unitId";
	
	$obj = $wpdb->get_row($query);
	
	if(!is_null($obj)) {
		$result = $obj->unit_unique_ref;
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

function getWeekNumber($startTime, $endTime) {
	$weeks = array();
	
	while ($startTime < $endTime) {
		$day     = date('N', $startTime);
        $weekNo  = date('W', $startTime);
        
        if($day == 6) {
	        $weekNo += 1;
        }
		$weeks[] = $weekNo;
		$startTime += strtotime('+1 week', 0);
        
//        if($startTime > $endTime && $startTime < (strtotime('+1 week', $endTime))) {
//	        $weeks[] = date('W', $endTime);
//        }
	}
    
    return $weeks;
}

function generateCsvByYear($year, $generateCsvMode = '0')
{
	$csvHeader = ['PROPERTY', 'NAME', 'SLEEPS', 'ZONE', 'TIPOLOGY', '', '', 'REF', 'Unique ref', '', '', '', '', ''];
	
	$firstDayOfYear = new DateTime();
	$firstDayOfYear->setISODate($year, 1, WEEK_START_SATURDAY);
    
    $lastDayOfYear = new DateTime();
	$lastDayOfYear->setISODate($year, 52, WEEK_END_SATURDAY);
    
	$firstDayOfYearTimestamp = $firstDayOfYear->getTimestamp();
	$lastDayOfYearTimestamp  = $lastDayOfYear->getTimestamp();
    $oneMoreWeek    = $firstDayOfYearTimestamp;
    $weekNo         = 0 ;
    
    $tmpArr = array();
	
	while ($oneMoreWeek < $lastDayOfYearTimestamp) {
		$weekNo++;
        $weekNoTwoDigits = str_pad($weekNo, 2, '0', STR_PAD_LEFT);
		$tmpArr[$weekNoTwoDigits]['start'] = date('d-m', $oneMoreWeek);
		$oneMoreWeek = strtotime('+1 week', $oneMoreWeek);
		$tmpArr[$weekNoTwoDigits]['end']   = date('d-m', $oneMoreWeek);
	}
    
    foreach ($tmpArr as $weekNoTwoDigits => $tmp) {
        $headerString = $weekNoTwoDigits.'-'.$year.' '.$tmp['start'].' '.$tmp['end'];
	    $csvHeader[]  = $headerString;
    }
	
	try {
		ob_clean();
		
		$accommodationData = array();
		
		if(isset($_POST['EditIcalLinkAccommodationId']) || $_SESSION['EditIcalLinkAccommodationId']) {
			$editIcalLinkAccommodationId = $_POST['EditIcalLinkAccommodationId'] ?? '';
			
			if($editIcalLinkAccommodationId == '') {
				$editIcalLinkAccommodationId = $_SESSION['EditIcalLinkAccommodationId'];
			}
			
			if($editIcalLinkAccommodationId != '') {
				$accommodationData = getAccommodationDataById($editIcalLinkAccommodationId);
			}
		}
        
        $fileName = $year.'_'.str_replace(' ','_',$accommodationData[0]['accommodation_name']).'.csv';
  
		$filePath = CSV_LOGS . $fileName;
        
        if(!is_file($filePath)) {
	        touch($filePath);
        }
  
		$csvWriter = Writer::createFromPath($filePath);
		$csvWriter->setOutputBOM(Reader::BOM_UTF8);
		$csvWriter->setDelimiter(';');
		$csvWriter->insertOne($csvHeader);
        
        foreach ($accommodationData as $accommodation) {
            $accommodationName = $accommodation['accommodation_name'];
            $unitName          = $accommodation['unit']['unit_name'];
            $unitId            = $accommodation['unit']['unit_id'];
            
            $dataRow = [
                $accommodationName,
                $unitName,
                '',
                '',
                '',
                '',
                '',
                '', //REF
                getUnitUniqueRefById($unitId), //UNIQUE REF
                '',
                '',
                '',
                '',
                ''
            ];
            
            $icalLink      = getUnitIcalLinkById($unitId);
	        $unitTypesList = getUnitTypesListByUnitId($unitId);
            $bookedWeeks   = array();
            $haveUnitType  = false;
            
            if($icalLink != '') {
	            $obj = new ics();
	            $icsEvents = $obj->getIcsEventsAsArray($icalLink);
                
                foreach ($icsEvents as $icsEvent) {
                    
                    if ((isset($icsEvent['DTSTART;VALUE=DATE']) && isset($icsEvent['DTEND;VALUE=DATE']))
                    || (isset($icsEvent['DTSTART']) && isset($icsEvent['DTEND']))) {
	                    $startDateString = $icsEvent['DTSTART;VALUE=DATE'] ?? $icsEvent['DTSTART'];
	                    $endDateString   = $icsEvent['DTEND;VALUE=DATE'] ?? $icsEvent['DTEND'];
	
	                    $startDateString = substr($startDateString, 0, 8);
	                    $endDateString   = substr($endDateString, 0, 8);
                        
                        if($startDateString != '' && $endDateString != '') {
	                        $startDate = DateTime::createFromFormat('Ymd', trim($startDateString))->setTime(0,0);
	                        $endDate   = DateTime::createFromFormat('Ymd', trim($endDateString))->setTime(0,0);;
                            $startTime = $startDate->getTimestamp();
                            $endTime   = $endDate->getTimestamp();
                            
                            $dateRangeWeekNo = getWeekNumber($startTime, $endTime);
	                        $bookedWeeks     = array_merge($bookedWeeks,$dateRangeWeekNo);
                        }
                    }
                }
                
	            $bookedWeeks = array_unique($bookedWeeks);
	            asort($bookedWeeks);
            } else {
                if(count($unitTypesList) > 0) {
	                $haveUnitType = true;
                    foreach ($unitTypesList as $unitType) {
                        $unitTypeIcalLink = $unitType->unit_type_ical_link;
	                    $unitTypeId       = $unitType->unit_type_id;
	
	                    $obj = new ics();
	                    $icsEvents = $obj->getIcsEventsAsArray($unitTypeIcalLink);
	
	                    $bookedWeeks[$unitTypeId] = array();
	
	                    foreach ($icsEvents as $icsEvent) {
		
		                    if ((isset($icsEvent['DTSTART;VALUE=DATE']) && isset($icsEvent['DTEND;VALUE=DATE']))
			                    || (isset($icsEvent['DTSTART']) && isset($icsEvent['DTEND']))) {
			                    $startDateString = $icsEvent['DTSTART;VALUE=DATE'] ?? $icsEvent['DTSTART'];
			                    $endDateString   = $icsEvent['DTEND;VALUE=DATE'] ?? $icsEvent['DTEND'];
			
			                    $startDateString = substr($startDateString, 0, 8);
			                    $endDateString   = substr($endDateString, 0, 8);
			
			                    if($startDateString != '' && $endDateString != '') {
				                    $startDate = DateTime::createFromFormat('Ymd', trim($startDateString))->setTime(0,0);
				                    $endDate   = DateTime::createFromFormat('Ymd', trim($endDateString))->setTime(0,0);;
				                    $startTime = $startDate->getTimestamp();
				                    $endTime   = $endDate->getTimestamp();
				
				                    $dateRangeWeekNo          = getWeekNumber($startTime, $endTime);
				                    $bookedWeeks[$unitTypeId] = array_merge($bookedWeeks[$unitTypeId], $dateRangeWeekNo);
			                    }
		                    }
	                    }
	
	                    $bookedWeeks[$unitTypeId] = array_unique($bookedWeeks[$unitTypeId]);
	                    asort($bookedWeeks[$unitTypeId]);
                    }
                }
            }
            
            for($i = 1; $i <= $weekNo; $i++) {
                $thisWeekIsBooked = false;
                
                if(!$haveUnitType) {
	                foreach ($bookedWeeks as $bookedWeek) {
		                if((int) $bookedWeek == $i) {
			                $thisWeekIsBooked = true;
			                break;
		                }
                    }
	
	                if($thisWeekIsBooked) {
		                $dataRow[] = 'b';
	                } else {
		                $dataRow[] = '';
	                }
                }
                else {
	                $allUnitTypeIsBookedByThisWeek    = false;
                    $allUnitTypeIsAvailableByThisWeek = false;
	
	                foreach ($bookedWeeks as $unitTypeBookedWeeks) {
		                $allUnitTypeIsBookedByThisWeek = in_array($i, $unitTypeBookedWeeks);
                        if(!$allUnitTypeIsBookedByThisWeek) {
                            break;
                        }
	                }
	
	                foreach ($bookedWeeks as $unitTypeBookedWeeks) {
		                $allUnitTypeIsAvailableByThisWeek = !in_array($i, $unitTypeBookedWeeks);
		                if(!$allUnitTypeIsAvailableByThisWeek) {
			                break;
		                }
	                }
                 
	                if($generateCsvMode == '1') {
		               if($allUnitTypeIsBookedByThisWeek) {
			               $dataRow[] = 'b';
                       } else {
			               $dataRow[] = '';
                       }
	                } else {
		                if($allUnitTypeIsAvailableByThisWeek) {
			                $dataRow[] = '';
                        } else {
			                $dataRow[] = 'b';
                        }
	                }
                }
            }
            
	        $csvWriter->insertOne($dataRow);
	
	        //------ THIS IS FOR TESTING ISSUE------
	        if(count($unitTypesList) > 0) {
		        foreach ($unitTypesList as $unitType) {
			        $unitTypeIcalLink = $unitType->unit_type_ical_link;
			        //$unitTypeId       = $unitType->unit_type_id;
			        $unitTypeName     = $unitType->unit_type_name;
			
			        $dataRow = [
				        $unitName,
				        $unitTypeName,
				        '',
				        '',
				        '',
				        '',
				        '',
				        '', //REF
				        '', //UNIQUE REF
				        '',
				        '',
				        '',
				        '',
				        ''
			        ];
			
			        $bookedWeeks = array();
			
			        if($unitTypeIcalLink != '') {
				        /* Getting events from isc file */
				        $obj = new ics();
				        $unitTypeIcsEvents = $obj->getIcsEventsAsArray( $unitTypeIcalLink );
				
				        foreach ($unitTypeIcsEvents as $unitTypeIcsEvent) {
					
					        if (isset($unitTypeIcsEvent['DTSTART;VALUE=DATE']) && isset($unitTypeIcsEvent['DTEND;VALUE=DATE'])) {
						        $startDateString = $unitTypeIcsEvent['DTSTART;VALUE=DATE'];
						        $endDateString   = $unitTypeIcsEvent['DTEND;VALUE=DATE'];
						
						        if($startDateString != '' && $endDateString != '') {
							        $startDate = DateTime::createFromFormat('Ymd', trim($startDateString))->setTime(0,0);
							        $endDate   = DateTime::createFromFormat('Ymd', trim($endDateString))->setTime(0,0);;
							        $startTime = $startDate->getTimestamp();
							        $endTime   = $endDate->getTimestamp();
							
							        $dateRangeWeekNo = getWeekNumber($startTime, $endTime);
							        $bookedWeeks     = array_merge($bookedWeeks,$dateRangeWeekNo);
						        }
					        }
				        }
				
				        $bookedWeeks = array_unique($bookedWeeks);
				        asort($bookedWeeks);
			        }
			
			        for($i = 1; $i <= $weekNo; $i++) {
				        $thisWeekIsBooked = false;
				
				        foreach ($bookedWeeks as $bookedWeek) {
					        if((int) $bookedWeek == $i) {
						        $thisWeekIsBooked = true;
						        break;
					        }
				        }
				
				        if($thisWeekIsBooked) {
					        $dataRow[] = 'b';
				        } else {
					        $dataRow[] = '';
				        }
			        }
			
			        $csvWriter->insertOne($dataRow);
		        }
	        }
	        //------ THIS IS FOR TESTING ISSUE------
        }
        
        //remove enclosure
        $csvContent = $csvWriter->__toString();
		$csvContent = str_replace('"', '', $csvContent);
		
		if(is_file($filePath)) {
            file_put_contents($filePath, $csvContent);
			header('Content-Type: text/csv; charset=UTF-8');
			header('Content-Description: File Transfer');
			header('Content-Disposition: attachment; filename="'.$fileName.'"');
            readfile($filePath);
		}
  
		exit;
	} catch (\Exception $e) {
        echo "<pre>";
        print_r($e);
        echo "</pre>";
        exit;
    }
}
	
$accommodationData           = array();
$editIcalLinkAccommodationId = '';

if(isset($_POST['EditIcalLinkAccommodationId']) || $_SESSION['EditIcalLinkAccommodationId']) {
    $editIcalLinkAccommodationId = $_POST['EditIcalLinkAccommodationId'] ?? '';
    
    if($editIcalLinkAccommodationId == '') {
	    $editIcalLinkAccommodationId = $_SESSION['EditIcalLinkAccommodationId'];
    }
    
    $_SESSION['EditIcalLinkAccommodationId'] = $editIcalLinkAccommodationId;
    $accommodationData = getAccommodationDataById($editIcalLinkAccommodationId);
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
                    $oldIcalLink = getUnitIcalLinkById($unitId);
                    
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
                    }
                }
            }
        }
        
        if(isset($_POST['NewUnitTypeName']) && isset($_POST['NewUnitTypeIcalLink'])) {
            $newUnitTypeNameOfUnit = $_POST['NewUnitTypeName'];
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
                        
                        if($unitIcalIsExisted) {
	                        $data = array(
		                        "reminder_ical_link"         => '',
		                        "reminder_status"            => "green",
		                        "reminder_updated_timestamp" => time()
	                        );
	
	                        $where = array(
		                        "reminder_unit_id" => $unitId
	                        );
	
	                        $wpdb->update($table_name_units_ical_reminders, $data, $where);
                        } else {
	                        $data = array(
		                        "reminder_unit_id"           => $unitId,
		                        "reminder_ical_link"         => '',
		                        "reminder_status"            => "green",
		                        "reminder_created_timestamp" => time(),
		                        "reminder_updated_timestamp" => time(),
	                        );
	
	                        $wpdb->insert($table_name_units_ical_reminders, $data);
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

		            if($updateUnitTypeIcalLink !== $thisUnitTypeIcalLink) {
			            $data = array(
				            'unit_type_ical_link' => $updateUnitTypeIcalLink
			            );

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

if(isset($_POST['GenerateCsvByYear'])) {
    $yearGenerateCsv = $_POST['GenerateCsvByYear'];
    $generateCsvMode = $accommodationData[0]['accommodation_csv_generate_mode'];
    
    if($yearGenerateCsv != '') {
        generateCsvByYear($yearGenerateCsv, $generateCsvMode);
    }
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
                        <?php
                            $thisYear = date('Y');
                            for ( $i=0 ; $i<=2 ; $i++ ) {
                                echo '<button class="btn btn-light" name="GenerateCsvByYear" value="'.((int) $thisYear + $i).'">';
                                echo (int) $thisYear + $i;
                                echo '</button>';
                            }
                        ?>
					</div>
				</div>
				<div class="set-available">
					<div class="w-100 edit-ical-label">Set available if 1 unit apartment is free</div>
					<div class="d-flex">
                        <label>
                            <input id="generate-csv-mode"
                                   data-accommodation-id="<?php echo $editIcalLinkAccommodationId; ?>"
                                   name="SetAvailableMode"
                                   <?php
                                       if($accommodationData[0]['accommodation_csv_generate_mode'] == '1') {
                                           echo 'checked="checked"';
                                       }
                                   ?>
                                   class="toggle-input" type="checkbox">
                        </label>
                    </div>
				</div>
			</div>
			
			<div class="ical-wrapper">
				<table class="ical-table" width="100%">
					<tr>
						<th width="20%">Unit</th>
						<th width="60%">iCal link</th>
                        <th width="20%">Unique ref</th>
					</tr>
					<tr>
						<td colspan="3">
							<table class="ical-table-inside table" border="1" width="100%">
								<?php foreach ($accommodationData as $data) : ?>
									<?php
										$unitId         = $data["unit"]["unit_id"];
										$icalLink       = getUnitIcalLinkById($unitId) ?? '';
                                        $unitUniqueRef  = getUnitUniqueRefById($unitId) ?? '';
                                        $unitIcalStatus = getUnitIcalStatusById($unitId);
										$unitTypesList  = getUnitTypesListByUnitId($unitId);
                                        
                                        if($unitIcalStatus == '') {
	                                        $unitIcalStatus = 'grey';
                                        }
									?>
									<tr>
										<td class="ical-table-unit">
											<table width="100%">
												<tr>
													<th width="17%" class="text-uppercase fw-normal" valign="middle">
                                                        <div class="d-flex align-items-center">
															<div class="accommodation-ical-status-<?php echo $unitIcalStatus; ?> m-0 me-2"></div>
															<?php echo $data["unit"]["unit_name"] ?>
														</div>
                                                    </th>
													<th width="2%" align="center">
														<span class="icon add-unit-type" data-unit-id="<?php echo $unitId; ?>">+</span>
													</th>
													<th width="61%" class="ical-link-column">
                                                        <?php if(count($unitTypesList) <= 0) :?>
														<input class="w-100" type="text" name="IcalLink[<?php echo $unitId; ?>]" value="<?php echo $icalLink; ?>"/>
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
														<tr>
															<td>
																<input class="w-100" type="text" name="UnitTypeName[<?php echo $unitTypeId ?>]" value="<?php echo $unitTypeName ?>"/>
                                                            </td>
                                                            <td align="center">
                                                                <button type="submit" name="RemoveUnitType" value="<?php echo $unitTypeId ?>" class="remove-unit-type icon">−</button>
                                                            </td>
															<td class="ical-link-column">
                                                            	<input class="w-100" type="text" name="UnitTypeIcalLink[<?php echo $unitTypeId ?>]" value="<?php echo $unitTypeIcalLink ?>"/>
                                                            </td>
														</tr>
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