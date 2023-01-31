<?php

require_once(__DIR__ . '/vendor/autoload.php');

include_once ('ics.php');

use League\Csv\Reader;
use League\Csv\Writer;

const UNIT_MERGE_TYPE_DEFAULT     = 0;
const UNIT_MERGE_TYPE_UNIT_TYPES  = 1;
const UNIT_MERGE_TYPE_MERGE_UNITS = 2;

const FREE_IF_ONE_UNIT_TYPE_IS_AVAILABLE = 1;
const FREE_IF_ALL_UNIT_TYPE_IS_AVAILABLE = 0;

const WEEK_START_SATURDAY         = -1;
const WEEK_END_SATURDAY           = 6;
	
class Rediger_FerieboligHelper
{
	public static function generateCsvByYear($year, $accommodationId = '', $filePath = '', $download = true)
	{
		$csvHeader = ['PROPERTY', 'NAME', 'SLEEPS', 'ZONE', 'TIPOLOGY', '', '', 'REF', 'Unique ref', '', '', '', '', ''];
		
		$firstDayOfYear = new DateTime();
		$firstDayOfYear->setISODate($year, 1, WEEK_START_SATURDAY);
		
		$lastDayOfYear = new DateTime();
		$lastDayOfYear->setISODate($year, 52, WEEK_END_SATURDAY);
		
		$firstDayOfYearTimestamp = $firstDayOfYear->getTimestamp();
		$lastDayOfYearTimestamp  = $lastDayOfYear->getTimestamp();
		$oneMoreWeek    = $firstDayOfYearTimestamp;
		$weekNo         = 0;
		
		$terretrucheBookingTxtFile        = TERRETRUSCHE_PATH . 'booking.txt';
		$terretrucheBookingTxtFileContent = file_get_contents($terretrucheBookingTxtFile);
		$hashed                           = md5(serialize($terretrucheBookingTxtFileContent));
		
		$csvReader = Reader::createFromPath($terretrucheBookingTxtFile);
		$csvReader->setOutputBOM(Reader::BOM_UTF8);
		$csvReader->setDelimiter(',');
		
		$terretrucheBookingArr = $csvReader->getIterator();
		
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
			
			$accommodationData = self::getAccommodationData($accommodationId);
			
			if($filePath == '') {
				$fileName = $year.'_'.str_replace(' ','_',$accommodationData[0]['accommodation_name']).'.csv';
				$filePath = CSV_LOGS . $fileName;
			} else {
				$fileName = basename($filePath);
			}
			
			if(!file_exists($filePath)) {
				touch($filePath);
			}
			
			$csvWriter = Writer::createFromPath($filePath);
			$csvWriter->setOutputBOM(Reader::BOM_UTF8);
			$csvWriter->setDelimiter(';');
			$csvWriter->insertOne($csvHeader);
			
			foreach ($accommodationData as $accommodation) {
				$accommodationName  = $accommodation['accommodation_name'];
				$unitName           = $accommodation['unit']['unit_name'];
				$unitId             = $accommodation['unit']['unit_id'];
				$isTerretrusche     = $accommodation['supplier_name'] == 'Terretrusche';
				$isAutoUpdatePaused = $accommodation['accommodation_auto_update_pause'] == '1';
				
				if($isAutoUpdatePaused && !$download) {
					continue;
				}
				
				$unitInfo = self::getUnitInfoById($unitId);
				$dataRow = [
					$accommodationName,
					$unitName,
					'',
					'',
					'',
					'',
					'',
					'', //REF
					$unitInfo->unit_unique_ref ?? '', //UNIQUE REF
					'',
					'',
					'',
					'',
					''
				];
				
				$unitGenerateCsvMode = $unitInfo->unit_generate_csv_mode ?? FREE_IF_ALL_UNIT_TYPE_IS_AVAILABLE;
				
				$icalLink      = trim(self::getUnitIcalLinkById($unitId));
				$unitInfo      = self::getUnitInfoById($unitId);
				$unitUniqueRef = $unitInfo->unit_unique_ref ?? '';
				$unitTypesList = self::getUnitTypesListByUnitId($unitId);
				$bookedWeeks   = array();
				$haveUnitType  = false;
				
				if($isTerretrusche) {
					$terretruscheOldId = self::getTerretruscheUnitOldId($unitUniqueRef);
					
					self::terretruscheUnitSelfCheckTextContent($unitId, $hashed);
					
					$bookedWeeks = self::getTerretruscheBookedWeekByOldId($terretrucheBookingArr, $terretruscheOldId, $year);
				} else {
					$fileHeaders = @get_headers($icalLink);
					
					if(!$fileHeaders || $fileHeaders[0] == 'HTTP/1.1 404 Not Found') {
						$log_file_name = ABSPATH . "not_work_ical_link.log";
						
						$log_msg = "Check accommodation [$accommodationName] with ical link [$icalLink]";
						
						file_put_contents($log_file_name, $log_msg . "\n", FILE_APPEND);
						
						if(empty($unitTypesList)) {
							continue;
						}
					} else {
						$log_file_name = ABSPATH . "working_ical_link.log";
						
						$log_msg = "Accommodation [$accommodationName] with ical link [$icalLink] working!";
						
						file_put_contents($log_file_name, $log_msg . "\n", FILE_APPEND);
					}
					
					if($icalLink != '') {
						$obj = new ics();
						$icsEvents = $obj->getIcsEventsAsArray($icalLink);
						
						self::unitSelfCheckIcalContent($unitId);
						
						foreach ($icsEvents as $icsEvent) {
							
							if ((isset($icsEvent['DTSTART;VALUE=DATE']) && isset($icsEvent['DTEND;VALUE=DATE']))
								|| (isset($icsEvent['DTSTART']) && isset($icsEvent['DTEND']))) {
								$startDateString = $icsEvent['DTSTART;VALUE=DATE'] ?? $icsEvent['DTSTART'];
								$endDateString   = $icsEvent['DTEND;VALUE=DATE'] ?? $icsEvent['DTEND'];
								
								$startDateString = substr($startDateString, 0, 8);
								$endDateString   = substr($endDateString, 0, 8);
								
								if($startDateString != '' && $endDateString != '') {
									$startDate = DateTime::createFromFormat('Ymd', trim($startDateString))->setTime(0,0);
									$endDate   = DateTime::createFromFormat('Ymd', trim($endDateString))->setTime(0,0);
									
									//ignore different year
									if($startDate->format("Y") != $year
										&& $endDate->format("Y") != $year) {
										continue;
									} else if ($startDate->format("Y") != $year) {
										$startDate->setDate($year, '01', '01');
									} else if ($endDate->format("Y") != $year) {
										$endDate->setDate($year, '12', '31');
									}
									
									$startTime = $startDate->getTimestamp();
									$endTime   = $endDate->getTimestamp();
									
									$dateRangeWeekNo = self::getWeekNumber($startTime, $endTime);
									
									$bookedWeeks     = array_merge($bookedWeeks,$dateRangeWeekNo);
								}
							}
						}
						
						$bookedWeeks = array_unique($bookedWeeks);
						asort($bookedWeeks);
					}
					else {
						if(count($unitTypesList) > 0) {
							$haveUnitType = true;
							foreach ($unitTypesList as $unitType) {
								$unitTypeIcalLink = $unitType->unit_type_ical_link;
								$unitTypeId       = $unitType->unit_type_id;
								
								$obj = new ics();
								$icsEvents = $obj->getIcsEventsAsArray($unitTypeIcalLink);
								
								self::unitTypeSelfCheckIcalContent($unitTypeId);
								
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
											$endDate   = DateTime::createFromFormat('Ymd', trim($endDateString))->setTime(0,0);
											
											//ignore different year
											if($startDate->format("Y") != $year
												&& $endDate->format("Y") != $year) {
												continue;
											} else if ($startDate->format("Y") != $year) {
												$startDate->setDate($year, '01', '01');
											} else if ($endDate->format("Y") != $year) {
												$endDate->setDate($year, '12', '31');
											}
											
											$startTime = $startDate->getTimestamp();
											$endTime   = $endDate->getTimestamp();
											
											$dateRangeWeekNo          = self::getWeekNumber($startTime, $endTime);
											$bookedWeeks[$unitTypeId] = array_merge($bookedWeeks[$unitTypeId], $dateRangeWeekNo);
										}
									}
								}
								
								$bookedWeeks[$unitTypeId] = array_unique($bookedWeeks[$unitTypeId]);
								asort($bookedWeeks[$unitTypeId]);
							}
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
						
						if($unitGenerateCsvMode == FREE_IF_ONE_UNIT_TYPE_IS_AVAILABLE) {
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
//				if(count($unitTypesList) > 0) {
//					foreach ($unitTypesList as $unitType) {
//						$unitTypeIcalLink = $unitType->unit_type_ical_link;
//						$unitTypeName     = $unitType->unit_type_name;
//
//						$unitMergeTypeInfo = self::getUnitInfoById($unitTypeName);
//						$isUnitMergeType   = !is_null($unitMergeTypeInfo);
//
//						$dataRow = [
//							$unitName,
//							$isUnitMergeType ? $unitMergeTypeInfo->unit_name : $unitTypeName,
//							'',
//							'',
//							'',
//							'',
//							'',
//							'', //REF
//							'', //UNIQUE REF
//							'',
//							'',
//							'',
//							'',
//							''
//						];
//
//						$bookedWeeks = array();
//
//						if($unitTypeIcalLink != '') {
//							/* Getting events from isc file */
//							$obj = new ics();
//							$unitTypeIcsEvents = $obj->getIcsEventsAsArray( $unitTypeIcalLink );
//
//							foreach ($unitTypeIcsEvents as $unitTypeIcsEvent) {
//
//								if (isset($unitTypeIcsEvent['DTSTART;VALUE=DATE']) && isset($unitTypeIcsEvent['DTEND;VALUE=DATE'])) {
//									$startDateString = $unitTypeIcsEvent['DTSTART;VALUE=DATE'];
//									$endDateString   = $unitTypeIcsEvent['DTEND;VALUE=DATE'];
//
//									if($startDateString != '' && $endDateString != '') {
//										$startDate = DateTime::createFromFormat('Ymd', trim($startDateString))->setTime(0,0);
//										$endDate   = DateTime::createFromFormat('Ymd', trim($endDateString))->setTime(0,0);
//
//										//ignore different year
//										if($startDate->format("Y") != $year || $endDate->format("Y") != $year) {
//											continue;
//										}
//
//										$startTime = $startDate->getTimestamp();
//										$endTime   = $endDate->getTimestamp();
//
//										$dateRangeWeekNo = self::getWeekNumber($startTime, $endTime);
//										$bookedWeeks     = array_merge($bookedWeeks,$dateRangeWeekNo);
//									}
//								}
//							}
//
//							$bookedWeeks = array_unique($bookedWeeks);
//							asort($bookedWeeks);
//						}
//
//						for($i = 1; $i <= $weekNo; $i++) {
//							$thisWeekIsBooked = false;
//
//							foreach ($bookedWeeks as $bookedWeek) {
//								if((int) $bookedWeek == $i) {
//									$thisWeekIsBooked = true;
//									break;
//								}
//							}
//
//							if($thisWeekIsBooked) {
//								$dataRow[] = 'b';
//							} else {
//								$dataRow[] = '';
//							}
//						}
//
//						$csvWriter->insertOne($dataRow);
//					}
//				}
				//------ THIS IS FOR TESTING ISSUE------
			}
			
			//remove enclosure
			$csvContent = $csvWriter->__toString();
			$csvContent = str_replace('"', '', $csvContent);
			
			if($download) {
				
				if(is_file($filePath)) {
					file_put_contents($filePath, $csvContent);
					header('Content-Type: text/csv; charset=UTF-8');
					header('Content-Description: File Transfer');
					header('Content-Disposition: attachment; filename="'.$fileName.'"');
					readfile($filePath);
				}
				
				exit;
			} else {
				
				if(is_file($filePath)) {
					file_put_contents($filePath, $csvContent);
				}
			}
			
		} catch (\Exception $e) {
			$log_file_name = ABSPATH . "cron.log";
			
			file_put_contents($log_file_name, $e->getMessage() . "\n", FILE_APPEND);
		}
	}
	
	public static function getAccommodationData($accommodationId = '') {
		global $wpdb;
		
		$result = array();
		
		$table_name_suppliers            = $wpdb->prefix . 'localliving_plg_suppliers';
		$table_name_accommodations       = $wpdb->prefix . 'localliving_plg_accommodations';
		$table_name_units                = $wpdb->prefix . 'localliving_plg_units';
		
		if($accommodationId !== '') {
			
			$query = "SELECT *
        FROM $table_name_accommodations
        JOIN $table_name_suppliers ON $table_name_accommodations.accommodation_supplier_id = $table_name_suppliers.supplier_id
        JOIN $table_name_units ON $table_name_accommodations.accommodation_id = $table_name_units.unit_accommodation_id
        WHERE $table_name_accommodations.accommodation_id = $accommodationId";
		} else {
			
			$query = "SELECT *
        FROM $table_name_accommodations
        JOIN $table_name_suppliers ON $table_name_accommodations.accommodation_supplier_id = $table_name_suppliers.supplier_id
        JOIN $table_name_units ON $table_name_accommodations.accommodation_id = $table_name_units.unit_accommodation_id";
		}
		
		$returnedDataList = $wpdb->get_results($query);
		
		foreach ($returnedDataList as $returnedData) {
			$result[] = array(
				"accommodation_id"                => $returnedData->accommodation_id ?? '',
				"accommodation_name"              => $returnedData->accommodation_name ?? '',
				"accommodation_auto_update_pause" => $returnedData->accommodation_auto_update_pause ?? 0,
				"supplier_name"                   => $returnedData->supplier_name ?? '',
				"unit"                            => array(
					"unit_id"   => $returnedData->unit_id ?? '',
					"unit_name" => $returnedData->unit_name ?? ''
				)
			);
		}
		
		return $result;
	}
	
	public static function getUnitInfoById($unitId) {
		global $wpdb;
		
		$table_name_units = $wpdb->prefix . 'localliving_plg_units';
		
		$query = "SELECT *
            FROM $table_name_units
            WHERE $table_name_units.unit_id = '$unitId'";
		
		return $wpdb->get_row($query);
	}
	
	public static function getUnitIcalLinkById($unitId) {
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
	
	public static function getUnitTypesListByUnitId($unitId) {
		
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
	
	private static function getWeekNumber($startTime, $endTime) {
		$weeks = array();
		
		while ($startTime < $endTime) {
			
			$dayStartTime = date('N', $startTime);
			$dayEndTime   = date('N', $endTime);
			$weekNoStart = date('W', $startTime);
			$weekNoEnd   = date('W', $endTime);
			
			if($dayStartTime >= 6) {
				$weeks[] = ($weekNoStart + 1) < 52 ? ($weekNoStart + 1) : 1;
			} else {
				$weeks[] = $weekNoStart;
			}
			
			if($dayEndTime > 6) {
				$weeks[] = ($weekNoEnd + 1) < 52 ? ($weekNoEnd + 1) : 1;
			} else {
				$weeks[] = $weekNoEnd;
			}
			
			$startTime += strtotime('+1 week', 0);
		}
		
		return $weeks;
	}
	
	private static function getTerretruscheUnitOldId($terretrucheUnitUniqueRef) {
		global $wpdb;
		
		$table_name_accommodations = $wpdb->prefix . 'localliving_plg_terretrusche_property_map';
		
		$query = "SELECT $table_name_accommodations.property_txt_file_id
		FROM $table_name_accommodations
		WHERE $table_name_accommodations.unit_unique_ref = $terretrucheUnitUniqueRef";
		
		return $wpdb->get_var($query);
	}
	
	private static function getTerretruscheBookedWeekByOldId($bookingArr, $terretrucheUnitOldId, $year) {
		$bookedWeeks = array();
		
		foreach ($bookingArr as $arr) {
			
			if($terretrucheUnitOldId == $arr[0]) {
				$startDateString = $arr[1];
				$endDateString   = $arr[2];
				
				$startDate = DateTime::createFromFormat('Ymd', trim($startDateString))->setTime(0,0);
				$endDate   = DateTime::createFromFormat('Ymd', trim($endDateString))->setTime(0,0);
				
				//ignore different year
				if($startDate->format("Y") != $year
					&& $endDate->format("Y") != $year) {
					continue;
				} else if ($startDate->format("Y") != $year) {
					$startDate->setDate($year, '01', '01');
				} else if ($endDate->format("Y") != $year) {
					$endDate->setDate($year, '12', '31');
				}
				
				$startTime = $startDate->getTimestamp();
				$endTime   = $endDate->getTimestamp();
				
				//IMPORTANT NOTE: into terretrusche database the "ToDate" is the last booked night
				$endTime = strtotime('+1 day', $endTime);
				
				$dateRangeWeekNo = self::getWeekNumber($startTime, $endTime);
				
				$bookedWeeks     = array_merge($bookedWeeks,$dateRangeWeekNo);
			}
		}
		
		$bookedWeeks = array_unique($bookedWeeks);
		asort($bookedWeeks);
		
		return $bookedWeeks;
	}
	
	private static function unitSelfCheckIcalContent($unitId) {
		global $wpdb;
		
		$table_name_units_ical_reminders = $wpdb->prefix . 'localliving_plg_units_ical_reminders';
		
		$query = "SELECT *
					FROM $table_name_units_ical_reminders
					WHERE $table_name_units_ical_reminders.reminder_unit_id = $unitId
				";
		
		$queryResults = $wpdb->get_results($query);
		
		foreach ($queryResults as $queryResult) {
			$unitIcalLink              = $queryResult->reminder_ical_link ?? '';
			$lastIcalLinkContentHashed = $queryResult->last_ical_content_hashed ?? '';
			
			$ics = new ics();
			
			$icsEvents       = $ics->getIcsEventsAsArray($unitIcalLink);
			$hashedIcsEvents = md5(serialize($icsEvents));
			
			if($lastIcalLinkContentHashed != $hashedIcsEvents) {
				$updateData = array(
					'last_ical_content_hashed'   => $hashedIcsEvents,
					'reminder_status'            => 'green',
					'reminder_updated_timestamp' => time(),
				);
				
				$where = array (
					'reminder_unit_id' => $unitId
				);
				
				$wpdb->update($table_name_units_ical_reminders, $updateData, $where);
			}
		}
	}
	
	private static function unitTypeSelfCheckIcalContent($unitTypeId) {
		global $wpdb;
		
		$table_name_unit_types_ical_reminders = $wpdb->prefix . 'localliving_plg_unit_types_ical_reminders';
		$table_name_units_ical_reminders      = $wpdb->prefix . 'localliving_plg_units_ical_reminders';
		
		$query = "SELECT *
					FROM $table_name_unit_types_ical_reminders
					WHERE $table_name_unit_types_ical_reminders.unit_type_id = $unitTypeId
				";
		
		$queryResults = $wpdb->get_results($query);
		
		foreach ($queryResults as $queryResult) {
			$unitTypeUnitId            = $queryResult->unit_type_unit_id        ?? '';
			$unitTypeIcalLink          = $queryResult->unit_type_ical_link      ?? '';
			$lastIcalLinkContentHashed = $queryResult->last_ical_content_hashed ?? '';
			
			$ics = new ics();
			
			$icsEvents       = $ics->getIcsEventsAsArray($unitTypeIcalLink);
			$hashedIcsEvents = md5(serialize($icsEvents));
			
			if($lastIcalLinkContentHashed != $hashedIcsEvents) {
				$unitTypeUpdateData = array(
					'last_ical_content_hashed'   => $hashedIcsEvents
				);
				
				$unitTypeWhere = array (
					'unit_type_id' => $unitTypeId
				);
				
				$wpdb->update($table_name_unit_types_ical_reminders, $unitTypeUpdateData, $unitTypeWhere);
				
				$unitUpdateData = array (
					'reminder_status'            => 'green',
					'reminder_updated_timestamp' => time(),
				);
				
				$unitWhere = array(
					'reminder_unit_id' => $unitTypeUnitId
				);
				
				$wpdb->update($table_name_units_ical_reminders, $unitUpdateData, $unitWhere);
			}
		}
	}
	
	private static function terretruscheUnitSelfCheckTextContent($terretruscheUnitId, $hashedTerretruscheBooking) {
		global $wpdb;
		
		$table_name_units_ical_reminders = $wpdb->prefix . 'localliving_plg_units_ical_reminders';
		
		$query = "SELECT reminder_id, last_ical_content_hashed
					FROM $table_name_units_ical_reminders
					WHERE $table_name_units_ical_reminders.reminder_unit_id = $terretruscheUnitId
				";
		
		$queryResult = $wpdb->get_row($query);
		
		if(is_null($queryResult)) {
			$insertDataReminder = array(
				'reminder_unit_id'           => $terretruscheUnitId,
				'last_ical_content_hashed'   => $hashedTerretruscheBooking,
				'reminder_status'            => 'green',
				'reminder_created_timestamp' => time(),
				'reminder_updated_timestamp' => time()
			);
			
			$wpdb->insert($table_name_units_ical_reminders, $insertDataReminder);
		} else {
			$reminderId                = $queryResult->reminder_id;
			$lastIcalLinkContentHashed = $queryResult->last_ical_content_hashed;
			
			if($hashedTerretruscheBooking != $lastIcalLinkContentHashed) {
				$updateDataReminder = array(
					'last_ical_content_hashed'   => $hashedTerretruscheBooking,
					'reminder_status'            => 'green',
					'reminder_updated_timestamp' => time()
				);
				
				$whereReminder = array(
					'reminder_id' => $reminderId
				);
				
				$wpdb->update($table_name_units_ical_reminders, $updateDataReminder, $whereReminder);
			}
		}
	}
	
	public static function terretruscheFtpDownloadBookingFile() {
		$ftpServer   = 'partner.terretrusche.com';
		$ftpUsername = 'localliving';
		$ftpPassword = 'll-parFTP';
		$serverFile  = 'booking.txt';
		$localFile   = TERRETRUSCHE_PATH . 'booking.txt';
		
		$connectId = ftp_connect($ftpServer);
		// login with username and password
		$login = ftp_login($connectId, $ftpUsername, $ftpPassword);
		
		if($login) {
			//enable passive mode
			ftp_pasv($connectId, true);
			
			// try to download $server_file and save to $local_file
			if (ftp_get($connectId, $localFile, $serverFile)) {
				echo "Successfully written to $localFile\n";
			}
			else {
				echo "There was a problem\n";
			}
		}
		
		// close the connection
		ftp_close($connectId);
	}
}