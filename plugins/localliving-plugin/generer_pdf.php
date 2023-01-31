<?php
/**
 * LocalLiving generate pdf page
 */

use LocalLiving_Plugin\iTravelAPI\iTravelGeneralSettings;
use LocalLiving_Plugin\iTravelAPI;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;
use Mpdf\MpdfException;;

require_once(__DIR__ . '/vendor/autoload.php');
require_once(__DIR__ . '/google-map-static-api/package.php');

/** WordPress Administration Bootstrap */
require_once(ABSPATH . 'wp-load.php');
require_once(ABSPATH . 'wp-admin/admin.php');
require_once(ABSPATH . 'wp-admin/admin-header.php');

//javascript
add_action('admin_footer', function () {
    echo
    '
    <script>
    jQuery(document).ready(function ($) {
        $(document).on("click", ".remove-from-cart", function(e) {
            e.preventDefault();
           
            var objectId = $(this).data("object-id");
            var unitId   = $(this).data("unit-id");
            var dateRange = $(this).closest("section").data("date-range");
            var dateFrom = dateRange.split("-")[0];
            var dateTo   = dateRange.split("-")[1];
            
            var unitIDList = [];
            
            if(!objectId) objectId = "";
            
            if(!unitId) unitId = "";
           
            //is add to cart button of Accommocation
            if(unitId === "") {
                unitIDList = $(".remove-from-cart[data-object-id=" + objectId + "]").map(function() {
                    return $(this).data("unit-id");
                }).get();
               
                unitId = unitIDList;
            }
           
            $.ajax({
                type: "POST",
                dataType: "json",
                url: "' . admin_url("admin-ajax.php") . '",
                data: {
                   action: "remove_from_cart",
                   objectId: objectId,
                   unitId: unitId,
                   dateFrom: dateFrom,
                   dateTo: dateTo
                },
                context: this,
                success: function(response) {
                    $(".cart-item-counter").text(response.total);
                    
                    if($(this).parents("section").find(".article-wrapper").length === 1) {
                        if($.isArray(unitId)) {
                            $(this).parents("section").remove();
                        } else {
                            if($(this).parents(".article-wrapper").find("article").length === 2) {
                                $(this).parents("section").remove();
                            } else {
                                $(this).parents("article").remove();
                            }
                        }
                    } else {
                        if($.isArray(unitId)) {
                        $(this).parents("article").parent().remove();
                        } else {
                            if($(this).parents(".article-wrapper").find("article").length === 2) {
                                $(this).parents(".article-wrapper").remove();
                            } else {
                                $(this).parents("article").remove();
                            }
                        }
                    }
                    
                    if($("article").length === 0) {
                        prependNoResultHtml();
                    }
                }
            });
        });
        
        //prepend no result html
        function prependNoResultHtml() {
            var noResultHtml = "<div><h2 class=fw-normal>Ingen valgte boliger</h2><p class=text-error>Vælg venligst én eller flere ferieboliger</p></div>";
            
            var divNoResult      = $("div.no-result");
            var divSearchResults = $("div.search-results");
            
            if(divNoResult.length === 0) {
                divSearchResults.append(noResultHtml);
            }
        }
        
        //character counter
        var maxLength = 1400;
        $(document).on("keyup", "#pdf-description-input", function() {
          var textlen = maxLength - $(this).val().length;
          $("#rchars").text(textlen);
        });
        var textlen = maxLength - $("#pdf-description-input").val().length;
        $("#rchars").text(textlen);
        
        //media button
        if ($(".set_custom_images").length > 0) {
            if ( typeof wp !== "undefined" && wp.media && wp.media.editor) {
                $(".set_custom_images").on("click", function(e) {
                    e.preventDefault();
                    var button = $(this);
                    var id = button.prev();
                    wp.media.editor.send.attachment = function(props, attachment) {
                        id.val(attachment.id);
                        $("#img-preview").attr("src",attachment.url);
                    };
                    wp.media.editor.open(button);
                    return false;
                });
            }
        }
    });
</script>
    ';
});

function getListAccommodationInCartSearchResult($accommodationIdList, $dateRange = '', $persons = 1)
{
    $result = array();
    
    if (isset($_SESSION['localliving_cart']) && count($_SESSION['localliving_cart']) > 0) {
        $accommodationSearchResults = new iTravelAPI\GetSearchResults();
    
        $object_description = array(
            'ResponseDetail' => 'ObjectDescription',
            'NumberOfResults' => '1'
        );
     
        $accommodationSearchResults->objectIDList = $accommodationIdList;
    
        if ($dateRange != '') {
            $explodedDateRange = explode('-', $dateRange);
        
            $dateFrom  = date_create_from_format(
                'd/m/Y',
                $explodedDateRange[0]
            );
            $dateTo    = date_create_from_format(
                'd/m/Y',
                $explodedDateRange[1]
            );
    
            $accommodationSearchResults->from = $dateFrom;
            $accommodationSearchResults->to   = $dateTo;
        }
	
	    $personFilter = array(
		    'AttributeID' => 120,
		    'AttributeValue' => $persons,
		    'ComparisonType' => 'GreaterOrEqualThan'
	    );
	
	    $accommodationSearchResults->unitFilters[0] = $personFilter;
        
        $accommodationSearchResults->outParameterList[] = $object_description;
        $accommodationSearchResults->languageID = lemax_two_letter_iso_language_name();
        //$accommodationSearchResults->pageSize = 20;
        //$accommodationSearchResults->currencyID = 208;
        $accommodationSearchResults->currencyID = iTravelGeneralSettings::GetCurrencyID(lemax_two_letter_iso_language_name());
        $accommodationSearchResults->thumbnailWidth = 140;
        $accommodationSearchResults->thumbnailHeight = 100;
    
        $result = $accommodationSearchResults->GetAPIResponse();
        
        if (!is_null($result)) {
            $result = $result->GetSearchResultsResult;
        }
    }
    
    return $result;
}

function getListAccommodationInCartDetailDescription($accommodationObjectUrl, $dateRange = '', $persons = 1)
{
    if ($accommodationObjectUrl == '' || empty($accommodationObjectUrl)) {
        return false;
    }
    
    $result = array();
    
    if (isset($_SESSION['localliving_cart']) && count($_SESSION['localliving_cart']) > 0) {
        $accommodationDetailedDescription = new iTravelAPI\GetDetailedDescription();
    
        $accommodationDetailedDescription->objectURL = $accommodationObjectUrl;
        
        if ($dateRange != '') {
            $explodedDateRange = explode('-', $dateRange);
            
            $dateFrom  = date_create_from_format(
                'd/m/Y',
                $explodedDateRange[0]
            );
            $dateTo    = date_create_from_format(
                'd/m/Y',
                $explodedDateRange[1]
            );
    
            $accommodationDetailedDescription->from = $dateFrom;
            $accommodationDetailedDescription->to   = $dateTo;
        }
	
	    $accommodationDetailedDescription->persons = $persons;
        
        $result = $accommodationDetailedDescription->GetAPIResponse();
        
        if (!is_null($result)) {
            $result = $result->GetDetailedDescriptionResult->AccommodationObject;
        }
    }
    
    return $result;
}

function redirect($url)
{
	echo
	'
    <script>
        window.location = "'.$url.'";
    </script>
    ';
}
	
function compressImage($originalUrl, $quality = 30) {
	
    $destination = '';
    
    try{
	    $originalUrl = str_replace(' ', '%20', $originalUrl);
     
	    $im = new Imagick($originalUrl);
        
        $destination = COMPRESS_IMAGES_LOGS . uniqid('compress_img_') . '.jpeg';
        
        if(!file_exists($destination)) {
            touch($destination);
	        $im->setImageCompression(Imagick::COMPRESSION_JPEG);
	        $im->setImageCompressionQuality($quality);
	        $im->writeImage($destination);
	        $im->clear();
	        $im->destroy();
        }
    } catch (\Exception $e) {
        $pluginClass = $GLOBALS['localliving_plugin'];
        $pluginClass->write_log($e->getMessage(), 'localliving-plg_generer-pdf.log');
    }
    
    return $destination;
}

function generateStaticMapImage($markersArray) {
    $map = new Static_Map("AIzaSyBTXW7m7qnmxGdIvDHjyu9QBcsV1ljLYzQ");
    
    foreach ($markersArray as $index => $value) {
	    $marker = new Marker($value['lat'], $value['long']);
	    $marker->set_size('S');
	    $marker->set_color('0x5D7329');
	    $marker->set_label((string)($index+1));
        $marker->set_scale(2);
	    $map->add_marker($marker);
    }
	
	$map->set_size('687x369');
    $map->set_scale(2);
 
	if(count($markersArray) == 1) {
		$map->set_zoom(10);
    }
    
	return $map->__toString();
}

function pdfGeneration(
    $pdfOpening,
    $pdfDescription,
    $generatePdfArr,
    $newOfferId
) {
    ini_set('error_reporting', E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE);
    ini_set('display_errors', 0);
    ini_set('log_errors', 0);
    
    $defaultConfig = (new ConfigVariables())->getDefaults();
    $fontDirs = $defaultConfig['fontDir'];
    
    $defaultFontConfig = (new FontVariables())->getDefaults();
    $fontData = $defaultFontConfig['fontdata'];
    
    $accommodationIdList = array();
    $accommodationIdListWithDateRangeKey = array();
    
    foreach ($generatePdfArr as $item) {
        $accommodationIdListWithDateRangeKey[$item['dateRange']][] = $item['accommodationId'];
	    $accommodationIdListWithDateRangeKey[$item['dateRange']]['selectedPersons'] = $item['selectedPersons'];
        $accommodationIdListWithDateRangeKey[$item['dateRange']] =
            array_unique($accommodationIdListWithDateRangeKey[$item['dateRange']]);
    }
    
    $accommodationListByDateRange            = array();
    $accommodationRegionListByDateRange      = array();
    $accommodationDestinationListByDateRange = array();
    
    $accommodationList = array();
    $regionList        = array();
    $destinationList   = array();
    
    foreach ($accommodationIdListWithDateRangeKey as $dateRange => $values) {
	    $selectedPersons = $values['selectedPersons'];
        unset($values['selectedPersons']);
        
        $listAccommodationInCartSearchResult = getListAccommodationInCartSearchResult(
            $values,
            $dateRange,
            $selectedPersons
        );
        
        $accommodationListByDateRange[$dateRange] =
            $listAccommodationInCartSearchResult->AccommodationObjectList->AccommodationObject;
        $accommodationRegionListByDateRange[$dateRange] =
            $listAccommodationInCartSearchResult->RegionList->Region;
        $accommodationDestinationListByDateRange[$dateRange] =
            $listAccommodationInCartSearchResult->DestinationList->Destination;
    }
    
    foreach ($accommodationListByDateRange as $accommodationAsArray) {
        foreach ($accommodationAsArray as $accommodation) {
            $accommodationList[] = $accommodation;
        }
    }
    
    foreach ($accommodationRegionListByDateRange as $regionListAsArray) {
        foreach ($regionListAsArray as $region) {
            $regionList[] = $region;
        }
    }
    
    foreach ($accommodationDestinationListByDateRange as $destinationListAsArray) {
        foreach ($destinationListAsArray as $destination) {
            $destinationList[] = $destination;
        }
    }
    
    foreach ($accommodationList as $accommodation) {
        if (isset($accommodation->UnitList->AccommodationUnit)) {
            $unitList = $accommodation->UnitList->AccommodationUnit;
            $isVilla  = $accommodation->ObjectType->ObjectTypeID == 70;
            
            foreach ($unitList as $unitListIndex => $unit) {
                $unitID = $unit->UnitID;
                
                if($unitID <= 0) {
                    continue;
                }
	
	            if ($isVilla) {
		            $unitID -= 1;
	            }
    
                foreach ($generatePdfArr as $generatePdfItem) {
                    $unitInCart = $unitID == $generatePdfItem['unitID'];
        
                    if ($unitInCart) {
                        $accommodation->UnitList->AccommodationUnit[$generatePdfItem['dateRange']][] = $unit;
//	                    $unit->DateRange = $generatePdfItem['dateRange'];
                    }
        
                    unset($accommodation->UnitList->AccommodationUnit[$unitListIndex]);
                }
            }
        }
    }
    
    $unitListWithDateRangeKey = array();
    
    foreach ($accommodationList as $accommodation) {
        $objectId   = $accommodation->ObjectID;
	    $unitList   = $accommodation->UnitList->AccommodationUnit;
        $dateRanges = array_keys($unitList);
        
        foreach ($dateRanges as $dateRange) {
	        $unitListWithDateRangeKey[$objectId][$dateRange] = $unitList[$dateRange];
        }
    }
	
	foreach ($accommodationList as $accommodation) {
		$objectId = $accommodation->ObjectID;
		
		unset($accommodation->UnitList->AccommodationUnit);
		
		$accommodation->UnitList->AccommodationUnit = $unitListWithDateRangeKey[$objectId];
    }
    
    //remove duplicate ObjectID if any
    $accommodationList = array_values(array_column($accommodationList, null, 'ObjectID'));

    try {
        $mpdf = new Mpdf([
            'fontDir' => array_merge($fontDirs, [
                __DIR__ . '/assets/font',
            ]),
            'fontdata' => $fontData + [
                    'lemontuesday' => [
                        'R' => 'LemonTuesday.ttf',
                    ],
                    'opensans' => [
                        'R' => 'OpenSans-Regular.ttf',
                        'B' => 'OpenSans-Bold.ttf',
                        'I' => 'OpenSans-Italic.ttf',
                    ],
                    'opensanslight' => [
                        'R' => 'OpenSans-Light.ttf',
                    ],
                    'merriweather' => [
                        'R' => 'Merriweather-Regular.ttf',
                        'B' => 'Merriweather-Bold.ttf',
                    ],
                ],
            'default_font' => 'opensans',
	        'mode'   => 'utf-8',
	        'format' => [210, 310]
        ]);
    
        $mpdf->useSubstitutions = false;
        $mpdf->simpleTables = true;
    
        $htmlPageHeader =
        '
        <htmlpageheader name="MyHeader">
            <div class="header">
                <img width="225" src="'.WP_PLUGIN_DIR.'/localliving-plugin/assets/images/pdf/logo.png"/>
            </div>
        </htmlpageheader>
        ';
    
        $htmlPageFooter =
        '
        <htmlpagefooter name="MyFooter">
            <div class="footer-left">Medlem af Rejsegarantifonden reg. nr. 2071 · e-mærket · grundlagt i 2005</div>
            <div class="footer-right">S. {PAGENO} af {nbpg}</div>
        </htmlpagefooter>
        ';
        
        $markersArray = array();
	
	    foreach ($accommodationList as $i => $accommodation) {
            $accommodationObjUrl = $accommodation->ObjectURL;
            $accommodationDetailedDescription = new iTravelAPI\GetDetailedDescription();
		    $explodedDateRange = explode('-', array_key_first($accommodation->UnitList->AccommodationUnit));
		    $dateFrom  = date_create_from_format(
			    'd/m/Y',
			    $explodedDateRange[0]
		    );
		    $dateTo    = date_create_from_format(
			    'd/m/Y',
			    $explodedDateRange[1]
		    );
		    $accommodationDetailedDescription->from = $dateFrom;
		    $accommodationDetailedDescription->to   = $dateTo;
            $accommodationDetailedDescription->objectURL = $accommodationObjUrl;
		    $accommodationDetailedDescription = $accommodationDetailedDescription->GetAPIResponse();
            
            if(isset($accommodationDetailedDescription
                    ->GetDetailedDescriptionResult
                    ->AccommodationObject->AttributeGroupList->AttributeGroup)) {
	            $accommodationAttributeGroupList = $accommodationDetailedDescription
			            ->GetDetailedDescriptionResult
			            ->AccommodationObject->AttributeGroupList->AttributeGroup;
                
                foreach ($accommodationAttributeGroupList as $accommodationAttributeGroup) {
                    if(isset($accommodationAttributeGroup->AttributeList->Attribute)) {
	                    $accommodationAttributeList = $accommodationAttributeGroup->AttributeList->Attribute;
                        
                        foreach ($accommodationAttributeList as $accommodationAttribute) {
                            if($accommodationAttribute->AttributeID == 290) {
	                            $markersArray[$i]['lat']
                                    = $accommodationAttribute->AttributeValue;
                            }
	                        if($accommodationAttribute->AttributeID == 291) {
		                        $markersArray[$i]['long']
                                    = $accommodationAttribute->AttributeValue;
	                        }
                        }
                    }
                }
            }
        }
        
        $htmlPageGreetingFooter =
        '
        <htmlpagefooter name="MyFooterGreeting">
            <h2 class="text-primary">JERES NØJE UDVALGTE FERIEBOLIGER</h2>
            <div class="location">
            <div class="location-map">
                <img class="location-map-img" src="'.generateStaticMapImage($markersArray).'"/>
            </div>
            <div class="location-list">
            <ol>
        ';
        $accommodationCount = 1;
        foreach ($accommodationList as $accommodation) {
            $accommodationUrl = '#';
            if (isset($accommodation->UnitList->AccommodationUnit)) {
                $explodedDateRange = explode('-', array_key_first($accommodation->UnitList->AccommodationUnit));
	
	            $dateFrom  = date_create_from_format(
		            'd/m/Y',
		            $explodedDateRange[0]
	            );
	            $dateTo    = date_create_from_format(
		            'd/m/Y',
		            $explodedDateRange[1]
	            );
                
                if($dateFrom && $dateTo) {
	                $accommodationUrl =
		                get_home_url().
		                $accommodation->ObjectURL.
		                '?dateFrom=' . $dateFrom->format('Y-m-d') .
		                '&dateTo=' . $dateTo->format('Y-m-d');
                }
            }
            $htmlPageGreetingFooter .=
                '<li><a href="'.$accommodationUrl.'" target="_blank">'.$accommodationCount.'. '.$accommodation->Name.'</a></li>';
            $accommodationCount++;
        }
        $htmlPageGreetingFooter .=
        '
            </ol>
                <div class="location-list-arrow">
                    <img width="28px" src="'.WP_PLUGIN_DIR.'/localliving-plugin/assets/images/pdf/arrow.png"/>
                </div>
                <div class="location-list-desc heading-custom">
                    Klik for at se<br/>på hjemmesiden
                </div>
            </div>
        </div>
        <div class="footer-left">Medlem af Rejsegarantifonden reg. nr. 2071 · e-mærket · grundlagt i 2005</div>
        <div class="footer-right">S. {PAGENO} af {nbpg}</div>
        </htmlpagefooter>
        ';
    
        $htmlFrontPage =
        '
        <div class="logo">
            <img width="340" src="'.WP_PLUGIN_DIR.'/localliving-plugin/assets/images/pdf/logo-with-text.png"/>
        </div>
        <div class="footer">
            <h1 class="footer-heading">Autentiske ferieoplevelser i Italien</h1>
            <p class="footer-desc">Local Living A/S · <a style="color: #fff;text-decoration: none" href="https://localliving.dk/">www.localliving.dk</a> · Tel: <a href="tel:+45.28157241" style="color:white;text-decoration: none">+45 28 15 72 41</a> · <a style="color: #fff;text-decoration: none" href="mailto:info@localliving.dk">info@localliving.dk</a></p>
        </div>
        ';
    
        $htmlGreetingPage =
        '
        <div class="user-info">
            <div class="user-info-desc">
                <h1 class="text-primary">' . stripslashes(nl2br(mb_strtoupper($pdfOpening,"UTF-8"))) . '</h1>
                <p>' . stripslashes(nl2br($pdfDescription)) . '</p>
            </div>
            <div class="user-info-contact">
                <img width="150" src="'.WP_PLUGIN_DIR.'/localliving-plugin/assets/images/pdf/avatar.png"/>
                <h2 class="text-primary">HAR DU SPØRGSMÅL?</h2>
                <p>Vi står klar til at hjælpe!</p>
                <div><strong>Inge Gustafsson</strong></div>
                <div>Email: <a style="color: #000;text-decoration: none" href="mailto:inge@localliving.dk">inge@localliving.dk</a></div>
                <div>Telefon: <a href="tel:+45.28157241" style="color:#000;text-decoration: none">+45 28 15 72 41</a></div>
                <div>(mellem kl. 9.00 – 17.00)</div>
            </div>
        </div>
        ';
    
        $aboutUsPdfPage = get_posts(['name' => 'about-us-pdf', 'post_type' => 'page']);
        $content        = explode('-----', $aboutUsPdfPage[0]->post_content);

        $htmlAboutPage =
        '
        <div class="about-page">
            <h1 class="text-primary">'. $content[0]->post_title .'</h1>
            <div class="about-desc-left">
                '. $content[0] .'
            </div>
            <div class="about-desc-right">
                '. $content[1] .'
            </div>
        </div>
        ';
    
        $htmlPageAboutFooter =
        '
        <htmlpagefooter name="MyFooterAbout">
            <div class="about-footer-wrapper">
                <div class="about-footer">
                    <div class="about-user">
                        <div class="about-user-avatar">
                            <img width="155" src="'.WP_PLUGIN_DIR.'/localliving-plugin/assets/images/pdf/avatar.png"/>
                        </div>
                        <div class="about-user-contact">
                            <h2 class="about-user-contact-h2 text-primary">HAR DU SPØRGSMÅL?</h2>
                            <p class="about-user-contact-p">Vi står klar til at hjælpe!</p>
                            <div><strong>Inge Gustafsson</strong></div>
                            <div>Email: <a class="link-style" href="mailto:inge@localliving.dk">inge@localliving.dk</a></div>
                            <div>Telefon: <a class="link-style" href="tel:45.28157241">+45 28 15 72 41</a> (mellem kl. 9.00 – 17.00)</div>
                            <div class="about-user-contact-link">
                                <div>Lad os endelig holde kontakten: </div>
                                <div>Følg med på <a class="link-style" href="https://www.facebook.com/LocalLivingDK">Facebook</a> og <a class="link-style" href="https://www.instagram.com/locallivingdk/">Instagram</a> eller tilmeld dig til vores <a class="link-style" href="https://localliving.us18.list-manage.com/subscribe/post?u=9469fca9e0cc85c18faf26796&id=2e5afd0417">nyhedsbrev</a></div>
                            </div>
                        </div>
                    </div>
                    <div class="footer-about" width="95%">
                        <div class="footer-left footer-about-item">Medlem af Rejsegarantifonden reg. nr. 2071 · e-mærket · grundlagt i 2005</div>
                        <div class="footer-right footer-about-item">S. {PAGENO} af {nbpg}</div>
                    </div>
                </div>
            </div>
        </htmlpagefooter>
        ';
        $pdfStyle = '';
        include_once WP_PLUGIN_DIR . '/localliving-plugin/assets/style/pdf_style.php';
        $mpdf->WriteHTML($pdfStyle, \Mpdf\HTMLParserMode::HEADER_CSS);
    
        $mpdf->WriteHTML($htmlPageHeader);
        $mpdf->SetHTMLHeaderByName('MyHeader');
        $mpdf->AddPage('', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', 'frontpage');
        $mpdf->WriteHTML($htmlFrontPage);
        $mpdf->AddPage('', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', 'greetingpage');
        $mpdf->WriteHTML($htmlGreetingPage);
        $mpdf->WriteHTML($htmlPageGreetingFooter);
        $mpdf->SetHTMLFooterByName('MyFooterGreeting');
	    
        if (count($accommodationList) > 0) {
            foreach ($accommodationList as $accommodation) {
                $accommodationName             = $accommodation->Name;
                $accommodationDestinationID    = $accommodation->DestinationID;
                $accommodationObjectType       = $accommodation->ObjectType->ObjectTypeName;
                $accommodationObjectTypeID     = $accommodation->ObjectType->ObjectTypeID;
                $accommodationAttributeList    = $accommodation->AttributeGroupList->AttributeGroup[0]->AttributeList->Attribute;
                $accommodationNumberOfStars    = 0;
                $isVilla                       = $accommodationObjectTypeID == 70;
            
                foreach ($accommodationAttributeList as $accommodationAttribute) {
                    $attributeID = $accommodationAttribute->AttributeID;
                
                    if ($attributeID == 970) {
                        $accommodationNumberOfStars = $accommodationAttribute->AttributeValue;
                    }
                }
                
                $accommodationObjUrl              = $accommodation->ObjectURL;
                $dateRange                        = array_key_first($accommodation->UnitList->AccommodationUnit);
                $selectedPersons                  =
                    $accommodation->UnitList->AccommodationUnit[$dateRange][0]->CalculatedPriceInfo->NumberOfPersons;
            
                $accommodationRegionID   = 0;
                $accommodationRegionName = '';
            
                foreach ($destinationList as $v) {
                    if ($v->DestinationID === $accommodationDestinationID) {
                        $accommodationRegionID = $v->RegionID;
                    }
                }
            
                foreach ($regionList as $v) {
                    if ($v->RegionID === $accommodationRegionID) {
                        $accommodationRegionName = $v->RegionName;
                    }
                }
            
                if ($accommodationRegionID == 87) {
                    $accommodationRegionLineClass = 'search-results-row-north-italy';
                } elseif ($accommodationRegionID == 49) {
                    $accommodationRegionLineClass = 'search-results-row-toscana';
                } elseif ($accommodationRegionID == 66) {
                    $accommodationRegionLineClass = 'search-results-row-ligurien';
                } elseif ($accommodationRegionID == 50) {
                    $accommodationRegionLineClass = 'search-results-row-umbrien';
                } elseif ($accommodationRegionID == 69) {
                    $accommodationRegionLineClass = 'search-results-row-sicilien';
                } else {
                    $accommodationRegionLineClass = 'search-results-row-default';
                }
            
                $facilitiesList = array();
                $extraCleaning = false;
                $cleaningIncluded = false;
                if (isset($accommodation->AttributeGroupList->AttributeGroup[3])) {
                    foreach ($accommodation->AttributeGroupList->AttributeGroup[3]->AttributeList->Attribute as $facilitiesAttr) {
                        $facilitiesList[] = $facilitiesAttr->AttributeName;
                        if ($facilitiesAttr->AttributeID == 1010) {
                            $extraCleaning = true;
                        }
                        if ($facilitiesAttr->AttributeID == 1031) {
                            $cleaningIncluded = true;
                        }
                    }
                }
    
                $accommodationUrl = '#';
                if (isset($accommodation->UnitList->AccommodationUnit)) {
                    $explodedDateRange = explode('-', array_key_first($accommodation->UnitList->AccommodationUnit));
    
                    $dateFrom  = date_create_from_format(
                        'd/m/Y',
                        $explodedDateRange[0]
                    );
                    $dateTo    = date_create_from_format(
                        'd/m/Y',
                        $explodedDateRange[1]
                    );
                    if($dateFrom && $dateTo) {
	                    $accommodationUrl =
		                    get_home_url().
		                    $accommodationObjUrl .
		                    '?dateFrom=' . $dateFrom->format('Y-m-d') .
		                    '&dateTo=' . $dateTo->format('Y-m-d');
                    }
                }
                
                $htmlAccommodationPage = '<div class="stars">';
                $starsDouble = doubleval($accommodationNumberOfStars);
                for ($i = 1; $i <= round($starsDouble, 0, PHP_ROUND_HALF_DOWN); $i++) {
                    $htmlAccommodationPage .= '<span class="star"><img class="star-img" width="18px" src="'.WP_PLUGIN_DIR.'/localliving-plugin/assets/images/cat-medium2.png"/></span>';
                }
                if (floor($starsDouble) != $starsDouble) {
                    $htmlAccommodationPage .= '<span class="plus">+</span>';
                }
                $htmlAccommodationPage .= '</div>';
                $htmlAccommodationPage .= '<div class="accomodation-border '.$accommodationRegionLineClass.'"></div>';
                $htmlAccommodationPage .= '<h1 class="accomodation-title text-primary">';
                $htmlAccommodationPage .= mb_strtoupper($accommodationName, "UTF-8");
                $htmlAccommodationPage .= '</h1>';
                $htmlAccommodationPage .= '<div class="region">'.$accommodationObjectType .' - '. $accommodationRegionName.'</div>';
                $htmlAccommodationPage .= '<div class="gallery">';
                if(isset($accommodation->PhotoList->Photo[0]->PhotoUrl)) {
                    $compressedImgUrl =
                        compressImage($accommodation->PhotoList->Photo[0]->PhotoUrl, 40);
	                $htmlAccommodationPage .= '<div class="gallery-main">';
	                $htmlAccommodationPage .= '<img width="470px" height="312px" src="'.$compressedImgUrl.'"/>';
	                $htmlAccommodationPage .= '</div>';
                }
                $htmlAccommodationPage .= '<div class="gallery-sub">';
                if(isset($accommodation->PhotoList->Photo[1]->PhotoUrl)) {
	                $compressedImgUrl =
		                compressImage($accommodation->PhotoList->Photo[1]->PhotoUrl);
	                $htmlAccommodationPage .= '<div class="gallery-sub-image">';
	                $htmlAccommodationPage .= '<img width="223px" height="148px" src="'.$compressedImgUrl.'"/>';
	                $htmlAccommodationPage .= '</div>';
                }
                if(isset($accommodation->PhotoList->Photo[2]->PhotoUrl)) {
	                $compressedImgUrl =
		                compressImage($accommodation->PhotoList->Photo[2]->PhotoUrl);
	                $htmlAccommodationPage .= '<div class="gallery-sub-image">';
	                $htmlAccommodationPage .= '<img width="223px" height="148px" src="'.$compressedImgUrl.'"/>';
	                $htmlAccommodationPage .= '</div>';
                }
                $htmlAccommodationPage .= '</div>';
                $htmlAccommodationPage .= '</div>';
                $htmlAccommodationPage .= '<div class="accomodation-description">';
                $htmlAccommodationPage .= $accommodation->ShortDescription;
                $htmlAccommodationPage .= 
                    '<div class="accomodation-description-hjemmesiden">
                        <a href="'.$accommodationUrl.'" class="heading-custom" target="_blank">Læs mere på hjemmesiden</a>
                    </div>';
                $htmlAccommodationPage .= '</div>';
                $htmlAccommodationPage .= 
                    '<div class="accomodation-more-photo">
                        <a href="'.$accommodationUrl.'" class="heading-custom accomodation-more-photo-text-link" target="_blank">Se flere billeder her</a>
                        <div class="accomodation-more-photo-bg">
                            <img width="30px" src="'.WP_PLUGIN_DIR.'/localliving-plugin//assets/images/pdf/billeder-her-img.png"/>
                        </div>
                    </div>';
                $htmlAccommodationPage .= '<div class="facilities-wrapper">';
                $htmlAccommodationPage .= '<h2 class="text-primary">FACILITETER</h2>';
                $htmlAccommodationPage .= '<div class="facilities">';
	            $htmlAccommodationPage .= '<div class="facilities-list">';
                $facilitiesColumns = array_chunk($facilitiesList, 3);
                for ($i = 0; $i <= count($facilitiesColumns); $i++) {
                    $htmlAccommodationPage .= '<ul>';
                    if (isset($facilitiesColumns[$i])) {
                        foreach ($facilitiesColumns[$i] as $index => $txt) {
                            $widthLi = $index == 2 ? 'style="width:30%"' : 'style="width:35%"';
                            $htmlAccommodationPage .= '<li '.$widthLi.'>'.$txt.'</li>';
                        }
                    }
                    $htmlAccommodationPage .= '</ul>';
                }
	            $htmlAccommodationPage .= '</div>';
                $htmlAccommodationPage .= '</div>';
                $htmlAccommodationPage .= '</div>';


                $mpdf->AddPage('', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', 'accomodation');

                $mpdf->WriteHTML($htmlAccommodationPage);
                $mpdf->WriteHTML($htmlPageFooter);
                $mpdf->SetHTMLFooterByName('MyFooter');
                
                
                if (isset($accommodation->UnitList->AccommodationUnit)) {
                    $accommodationUnitList = $accommodation->UnitList->AccommodationUnit;
                    
                    if (count($accommodationUnitList) > 0) {
                        $autoIncrementIndex = 0;
                        
                        foreach ($accommodationUnitList as $dateRange => $accommodationUnitArr) {
	                        $autoIncrementIndex++;
	
	                        $accommodationDetailedDescription =
		                        getListAccommodationInCartDetailDescription($accommodationObjUrl, $dateRange, $selectedPersons);
                            
                            $explodedDateRange = explode('-', $dateRange);
                            
                            foreach ($accommodationUnitArr as $accommodationUnit) {
                                $accommodationUnitID = $accommodationUnit->UnitID;
                                
                                $accommodationUnitName = '';
                                
                                if (isset($accommodationUnit->AttributeGroupList->AttributeGroup[0]->AttributeList->Attribute)) {
                                    $accommodationUnitAttributesList = $accommodationUnit->AttributeGroupList->AttributeGroup[0]->AttributeList->Attribute;
                                    foreach ($accommodationUnitAttributesList as $accommodationUnitAttribute) {
                                        if ($accommodationUnitAttribute->AttributeID == 133) {
                                            $accommodationUnitName = $accommodationUnitAttribute->AttributeValue;
                                        }
                                    }
                                }
                            
                                $cleaningFee = 'Inkl.';
                            
                                if ($extraCleaning || !$cleaningIncluded) {
                                    foreach ($accommodationDetailedDescription->UnitList->AccommodationUnit as $detailedAccommodationUnit) {
                                        if ($accommodationUnitID == $detailedAccommodationUnit->UnitID) {
                                            $accommodationUnitServiceList = $detailedAccommodationUnit->ServiceList->Service;
                                        
                                            foreach ($accommodationUnitServiceList as $accommodationUnitService) {
                                                $serviceID   = $accommodationUnitService->ServiceID;
                                            
                                                if ($serviceID == 176 || $serviceID == 303 || $serviceID == 307 ||
                                                    $serviceID == 313 || $serviceID == 314 || $serviceID == 315 ||
                                                    $serviceID == 321) {
                                                    $cleaningFee = $accommodationUnitService->PriceRowList->PriceRow[0]
                                                        ->PriceItemList->PriceItem[0]
                                                        ->ListPriceOnDayOfWeek->PriceOnDayOfWeek[0]
                                                        ->PriceOnDay;
                                                    $cleaningFee = number_format(
                                                        $cleaningFee,
                                                        2,
                                                        ',',
                                                        ' '
                                                    );
                                                }
                                            }
                                        }
                                    }
                                }
                            
                                $oldPrice                     = 0;
                                $newPrice                     = 0;
                                $minimumPrice                 = 0;
                                $discountDescription          = '';
                                $accommodationUnitDescription = '';
                                $accommodationBookingAddress  = '#';
                            
                                foreach ($accommodationDetailedDescription->UnitList->AccommodationUnit as $detailedAccommodationUnit) {
                                    if ($accommodationUnitID == $detailedAccommodationUnit->UnitID) {
                                        $oldPrice    = $detailedAccommodationUnit->CalculatedPriceInfo->BasicCalculatedPriceFormated;
                                        $newPrice    = $detailedAccommodationUnit->CalculatedPriceInfo->CalculatedPriceFormated;
                                        $minimumPrice = $detailedAccommodationUnit->UnitMinimumPriceInfo->PriceFormatted;
                                        $serviceList = $detailedAccommodationUnit->CalculatedPriceInfo->ServiceList->Service;
                                        $specialOfferServiceID = 0;
                                        foreach ($serviceList as $service) {
                                            if ($service->ServiceType == 'SpecialOffer') {
                                                $specialOfferServiceID = $service->ServiceID;
                                            }
                                        }
                                        $specialOfferList = $detailedAccommodationUnit->SpecialOfferList->SpecialOffer;
                                        foreach ($specialOfferList as $specialOffer) {
                                            if ($specialOffer->ServiceID == $specialOfferServiceID) {
                                                $discountDescription = $specialOffer->ServiceName;
                                            }
                                        }
    
                                        $accommodationUnitDescription
                                            = mb_convert_encoding($detailedAccommodationUnit->Description, 'UTF-8');
                                        $accommodationBookingAddress  = $detailedAccommodationUnit->BookingAddress;
                                    }
                                }
                                
                                $extraCostsDescription = '';
    
                                if (isset($accommodationDetailedDescription->NoteList->Note)) {
                                    $noteList = $accommodationDetailedDescription->NoteList->Note;
    
                                    foreach ($noteList as $note) {
                                        if ($isVilla) {
                                            if ($note->NoteID == 2400) {
                                                $accommodationUnitDescription = mb_convert_encoding($note->NoteText, 'UTF-8');
                                            }
                                        }
        
                                        if (strpos($note->NoteTitle, 'Extra cost') !== false) {
                                            $extraCostsDescription = mb_convert_encoding($note->NoteText, 'UTF-8');;
                                        }
                                    }
                                }
    
                                $dateFrom  = date_create_from_format(
                                    'd/m/Y',
                                    $explodedDateRange[0]
                                );
                                $dateTo    = date_create_from_format(
                                    'd/m/Y',
                                    $explodedDateRange[1]
                                );
                                $accommodationUrl =
                                    get_home_url().
                                    $accommodationObjUrl .
                                    '?dateFrom=' . $dateFrom->format('Y-m-d') .
                                    '&dateTo=' . $dateTo->format('Y-m-d') ;
    
                                $shortedAccommodationUnitDescription = subStringLength($accommodationUnitDescription, 250);
                            
                                $accommodationUnitPhotoList = $accommodationUnit->PhotoList->Photo;
                                
                                if ($isVilla) {
                                    $accommodationUnitPhotoList = $accommodationDetailedDescription->PhotoList->Photo;
                                }
                            
                                $htmlAccommodationUnit = '<div class="accomodation-border '.$accommodationRegionLineClass.'"></div>';
                                $htmlAccommodationUnit .= '<h1 class="accomodation-unit-title text-primary">';
                                $htmlAccommodationUnit .= mb_strtoupper($accommodationName, "UTF-8");
                                $htmlAccommodationUnit .= '</h1>';
                                $htmlAccommodationUnit .= '<h2 class="accomodation-unit-sub-title text-primary">';
                                $htmlAccommodationUnit .= mb_strtoupper($accommodationUnitName, "UTF-8");
                                $htmlAccommodationUnit .= '</h2>';
                                if($autoIncrementIndex === 1) {
	                                $htmlAccommodationUnit .= '<div class="unit-description">'.$shortedAccommodationUnitDescription.'</div>';
	                                if(count($accommodationUnitPhotoList) > 0) {
		                                $htmlAccommodationUnit .= '<div class="gallery">';
		                                $photoIndex = 0;
		                                if($isVilla) {
			                                $photoIndex = 3;
		                                }
		                                if(isset($accommodationUnitPhotoList[$photoIndex]->PhotoUrl)) {
			                                $compressedImgUrl =
				                                compressImage($accommodationUnitPhotoList[$photoIndex]->PhotoUrl, 40);
			                                $htmlAccommodationUnit .= '<div class="gallery-main">';
			                                $htmlAccommodationUnit .= '<img style="width:470px; height:312px" src="';
			                                $htmlAccommodationUnit .= $compressedImgUrl;
			                                $htmlAccommodationUnit .= '"/>';
			                                $htmlAccommodationUnit .= '</div>';
		                                }
		                                $htmlAccommodationUnit .= '<div class="gallery-sub">';
		                                if(isset($accommodationUnitPhotoList[$photoIndex+1]->PhotoUrl)) {
			                                $compressedImgUrl =
				                                compressImage($accommodationUnitPhotoList[$photoIndex+1]->PhotoUrl);
			                                $htmlAccommodationUnit .= '<div class="gallery-sub-image">';
			                                $htmlAccommodationUnit .= '<img style="width:227px; height:151px" src="';
			                                $htmlAccommodationUnit .= $compressedImgUrl;
			                                $htmlAccommodationUnit .= '"/>';
			                                $htmlAccommodationUnit .= '</div>';
		                                }
		                                if(isset($accommodationUnitPhotoList[$photoIndex+2]->PhotoUrl)) {
			                                $compressedImgUrl =
				                                compressImage($accommodationUnitPhotoList[$photoIndex+2]->PhotoUrl);
			                                $htmlAccommodationUnit .= '<div class="gallery-sub-image">';
			                                $htmlAccommodationUnit .= '<img style="width:227px; height:151px" src="';
			                                $htmlAccommodationUnit .= $compressedImgUrl;
			                                $htmlAccommodationUnit .= '"/>';
			                                $htmlAccommodationUnit .= '</div>';
		                                }
		
		                                $htmlAccommodationUnit .= '</div>';
		                                $htmlAccommodationUnit .= '<div class="gallery-sub-2">';
		                                if(isset($accommodationUnitPhotoList[$photoIndex+3]->PhotoUrl)) {
			                                $compressedImgUrl =
				                                compressImage($accommodationUnitPhotoList[$photoIndex+3]->PhotoUrl);
			                                $htmlAccommodationUnit .= '<div class="gallery-sub-2-image">';
			                                $htmlAccommodationUnit .= '<img style="width:227px; height:151px" src="';
			                                $htmlAccommodationUnit .= $compressedImgUrl;
			                                $htmlAccommodationUnit .= '"/>';
			                                $htmlAccommodationUnit .= '</div>';
		                                }
		                                if(isset($accommodationUnitPhotoList[$photoIndex+4]->PhotoUrl)) {
			                                $compressedImgUrl =
				                                compressImage($accommodationUnitPhotoList[$photoIndex+4]->PhotoUrl);
			                                $htmlAccommodationUnit .= '<div class="gallery-sub-2-image">';
			                                $htmlAccommodationUnit .= '<img style="width:227px; height:151px" src="';
			                                $htmlAccommodationUnit .= $compressedImgUrl;
			                                $htmlAccommodationUnit .= '"/>';
			                                $htmlAccommodationUnit .= '</div>';
		                                }
		                                if(isset($accommodationUnitPhotoList[$photoIndex+5]->PhotoUrl)) {
			                                $compressedImgUrl =
				                                compressImage($accommodationUnitPhotoList[$photoIndex+5]->PhotoUrl);
			                                $htmlAccommodationUnit .= '<div class="gallery-sub-2-image last">';
			                                $htmlAccommodationUnit .= '<img style="width:227px; height:151px" src="';
			                                $htmlAccommodationUnit .= $compressedImgUrl;
			                                $htmlAccommodationUnit .= '"/>';
			                                $htmlAccommodationUnit .= '</div>';
		                                }
		                                $htmlAccommodationUnit .= '</div>';
		                                $htmlAccommodationUnit .= '</div>';
		                                $htmlAccommodationUnit .= '</div>';
		                                $htmlAccommodationUnit .=
			                                '<div class="accomodation-unit-more-photo">
                                                <a href="'.$accommodationUrl.'" class="heading-custom accomodation-more-photo-text-link" target="_blank">Se flere billeder her</a>
                                                <div class="accomodation-more-photo-bg">
                                                    <img width="30px" src="'.WP_PLUGIN_DIR.'/localliving-plugin//assets/images/pdf/billeder-her-img.png"/>
                                                </div>
                                            </div>';
	                                }
                                }

                                if ($isVilla) {
                                    $accommodationObjectType = 'VILLAEN';
                                }

                                $htmlAccommodationUnit .= '<h2 class="text-primary">PRISER I KR. FOR HELE ';
                                $htmlAccommodationUnit .= mb_strtoupper($accommodationObjectType, "UTF-8");
                                $htmlAccommodationUnit .= '</h2>';
                                $htmlAccommodationUnit .= 
                                    '<div class="accomodation-arrow">
                                        <img src="'.WP_PLUGIN_DIR.'/localliving-plugin/assets/images/pdf/arrow-down.png" width="15px"/>
                                    </div>';
                                $htmlAccommodationUnit .= '<table class="accomodation-prices" border="0" cellSpacing="0">';
                                $htmlAccommodationUnit .=
                                    '<tr>
                                        <th class="column-1" width="30%"  align="left"><strong>PRIS I KR. PR. UGE</strong></th>
                                        <th class="column-2" width="20%" align="center"><strong>AFREJSE - RENGØRING</strong></th>
                                        <th class="column-3" width="20%" align="left">
                                            <strong>'.DateTime::createFromFormat('d/m/Y', $explodedDateRange[0])->format('d.m.Y').' - <br/>'.DateTime::createFromFormat('d/m/Y', $explodedDateRange[1])->format('d.m.Y').'</strong>
                                        </th>
                                        <th class="column-4" colspan="2" width="30%" align="right"><div class="heading-custom">Book online her</div></th>
                                    </tr>
                                    ';
                                $htmlAccommodationUnit .=
                                '
                                <tr>
                                    <td>'.mb_strtoupper($accommodationUnitName, "UTF-8").'</td>
                                    <td align="center">'.$cleaningFee.'</td>';
                                if ($oldPrice != $newPrice) {
                                    $htmlAccommodationUnit .=
                                            '
                                    <td colspan="2">
                                        <div><span class="old-price">'.$oldPrice.'</span> '.$newPrice.'</div>
                                        '.mb_strtoupper($discountDescription, "UTF-8").'
                                    </td>
                                ';
                                } else {
                                    if ($newPrice == 0) {
                                        $newPrice = $minimumPrice;
                                    }
                                    
                                    $htmlAccommodationUnit .=
                                        '
                                <td colspan="2">
                                    <div>'.$newPrice.'</div>
                                </td>
                                ';
                                }
                                
                                $extraCostsDescriptionLine1
                                    = preg_split('#\r?\n#', ltrim(strip_tags($extraCostsDescription)), 0)[0];
                                $extraCostsDescriptionLine2
                                    = preg_split('#\r?\n#', ltrim(strip_tags($extraCostsDescription)), 0)[1];
                                
                                $htmlAccommodationUnit .= '<td align="right"><a class="booking" href="'.$accommodationBookingAddress.'" target="_blank">BOOK NU</a></td>
                            </tr>
                            ';
                                $htmlAccommodationUnit .= '</table>';
                                $htmlAccommodationUnit .= '<div class="extra-costs-description">';
                                if(strlen($extraCostsDescriptionLine1) >= 100) {
	                                $extraCostsDescriptionLine1 = subStringLength($extraCostsDescriptionLine1, 100);
                                }
	                            if(strlen($extraCostsDescriptionLine2) >= 100) {
		                            $extraCostsDescriptionLine2 = subStringLength($extraCostsDescriptionLine2, 100);
	                            }
	                            $htmlAccommodationUnit .= '<p>'.$extraCostsDescriptionLine1.'</p>';
	                            $htmlAccommodationUnit .= '<p>'.$extraCostsDescriptionLine2.'</p>';
	                            $htmlAccommodationUnit .= '<a href="'.$accommodationUrl.'">Læs mere på hjemmesiden</a>';
                                $htmlAccommodationUnit .= '</div>';
                            
                                $mpdf->AddPage('', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', 'accomodation-unit');
                            
                                $mpdf->WriteHTML($htmlAccommodationUnit);
                                $mpdf->WriteHTML($htmlPageFooter);
                                $mpdf->SetHTMLFooterByName('MyFooter');
                            }
                        }
                    }
                }
            }
        }
    
        $mpdf->AddPage('', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', 'about');
        $mpdf->writeHTML($htmlAboutPage);
        $mpdf->writeHTML($htmlPageAboutFooter);
        $mpdf->SetHTMLFooterByName('MyFooterAbout');
        $mpdf->WriteHTML($htmlPageFooter);
        $mpdf->SetHTMLFooterByName('MyFooter');
    
        $folder      = date('Y');
        $receiverName = $_POST['EmailReceiverName'] ?? '';
	    $receiverName = str_replace(' ', '_', $receiverName);
        $offerName    = $receiverName
            . '_' .
            date('Y')
            . '_' .
            'T' . str_pad($newOfferId, 4, '0', STR_PAD_LEFT);
        $fileName    = $offerName . '.pdf';
    
        if (!file_exists(PDF_LOGS . $folder)) {
            mkdir(PDF_LOGS . $folder, 0777, true);
        }
    
        $mpdf->Output(
            PDF_LOGS . $folder . '/' . $fileName,
            \Mpdf\Output\Destination::FILE
        );
    } catch (\Exception $e) {
	    $pluginClass = $GLOBALS['localliving_plugin'];
     
	    $pluginClass->write_log(json_encode($generatePdfArr), 'localliving-plg_generer-pdf.log');
	    $pluginClass->write_log($e->getMessage(), 'localliving-plg_generer-pdf.log');
    }
    
    //clear cart
    $_SESSION['localliving_cart'] = array();
    
    return array(
        'file_url'         => LL_PLUGIN_URL . 'pdf_logs/' . $folder . '/' . $fileName,
        'file_name'        => $offerName
    );
}

function openSendMailPopup($receiverMail)
{
    echo
        '<script>
            window.open("mailto:'.$receiverMail.'", "_blank");
        </script>';
}

function insertOffer($offerReceiverName = '', $offerReceiverEmail = '', $offerName = '', $offerUrl = '')
{
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'localliving_plg_offer_list';
    
    $wpdb->insert(
        $table_name,
        array(
            'offer_receiver'           => $offerReceiverName,
            'offer_receiver_email'     => $offerReceiverEmail,
            'offer_name'               => $offerName,
            'offer_url'                => $offerUrl,
            'offer_generate_timestamp' => time(),
            'offer_status'             => 'green',
        )
    );
    
    return $wpdb->insert_id;
}

function subStringLength($str, $length, $minword = 7)
{
    $sub = '';
    $len = 0;
    foreach (explode(' ', $str) as $word)
    {
        $part = (($sub != '') ? ' ' : '') . $word;
        $sub .= $part;
        $len += strlen($part);
        if (strlen($word) > $minword && strlen($sub) >= $length)
        {
            break;
        }
    }
    return $sub . (($len < strlen($str)) ? '...' : '');
}

function updateOffer($offerId, $offerReceiverName = '', $offerReceiverEmail = '', $offerName = '', $offerUrl = '')
{
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'localliving_plg_offer_list';
    
    $data = array(
        'offer_receiver'       => $offerReceiverName,
        'offer_receiver_email' => $offerReceiverEmail,
        'offer_name'           => $offerName,
        'offer_url'            => $offerUrl,
    );
    
    $where = array(
        'offer_id' => $offerId
    );
    
    $wpdb->update($table_name, $data, $where);
}

function updateFrontpageBgImageOption($backgroundImgUrl) {
    global $wpdb;
	
	$table_name_options = $wpdb->prefix . 'localliving_plg_options';
    
    $result = false;
 
    if($backgroundImgUrl != '') {
        $query = "SELECT *
            FROM $table_name_options
            WHERE $table_name_options.option_name = 'pdf_frontpage_background_img'";
        
        $queryResult = $wpdb->get_row($query);
        
        if($queryResult->option_value != $backgroundImgUrl) {
            $data = array(
	            'option_value' => $backgroundImgUrl
            );
            
            $where = array(
                'option_name' => 'pdf_frontpage_background_img'
            );
            
            $wpdb->update($table_name_options, $data, $where);
            
            $result = true;
        }
    }
    
    return $result;
}

function getFrontPageBgImageOption() {
	global $wpdb;
	
	$table_name_options = $wpdb->prefix . 'localliving_plg_options';
	
	$result = '';
    
    $query = "SELECT *
            FROM $table_name_options
            WHERE $table_name_options.option_name = 'pdf_frontpage_background_img'";
    
    $queryResult = $wpdb->get_row($query);
    
    if(isset($queryResult->option_name)) {
	    $result = $queryResult->option_value;
    }
    
    return $result;
}

if (isset($_POST['GeneratePdf'])) {
    $action = $_POST['GeneratePdf'];
    
    if ($action === 'GeneratePdf') {
        if(isset($_SESSION['localliving_cart'])) {
	        $cart = $_SESSION['localliving_cart'];
	
	        $pdfOpening               = $_POST['PdfOpening'] ?? '';
	        $pdfDescription           = $_POST['PdfDescription'] ?? '';
            $pdfFrontPageAttachmentID = $_POST['FrontpageBgAttachmentID'] ?? '';
	        $receiverName             = $_POST['EmailReceiverName'] ?? '';
	        $receiverMail             = $_POST['EmailReceiverMail'] ?? '';
	
	        $stringCounter = mb_strlen(str_replace("\r\n","\n",$pdfDescription), 'UTF-8');
	
	        if ($pdfOpening != ''
                && $pdfDescription != ''
                && $receiverName != ''
                && $receiverMail != ''
                && $stringCounter <= 1400) {
                
		        if($pdfFrontPageAttachmentID != '') {
			        $pdfFrontPageUrl = wp_get_attachment_url($pdfFrontPageAttachmentID);
			
			        updateFrontpageBgImageOption($pdfFrontPageUrl);
		        }
          
		        $generatePdfArr = array();
		
		        foreach ($cart as $dateRange => $selectedAccommodation) {
                    $selectedPersons = $selectedAccommodation['selectedPersons'];
                    unset($selectedAccommodation['selectedPersons']);
			        foreach ($selectedAccommodation as $selectedAccommodationId => $selectedUnitIdList) {
            
				        foreach ($selectedUnitIdList as $index => $selectedUnitId) {
					
					        $generatePdfArr[] = array(
						        'unitID'          => $selectedUnitId,
						        'accommodationId' => $selectedAccommodationId,
						        'dateRange'       => $dateRange,
						        'selectedPersons' => $selectedPersons[$selectedAccommodationId] ?? 1
					        );
				        }
			        }
		        }
		
		        if (count($generatePdfArr) > 0) {
			        $subject      = '';
			
			        $newOfferId = insertOffer($receiverName, $receiverMail);
			
			        if (!empty($newOfferId)) {
				        $generatedPdf = pdfGeneration($pdfOpening, $pdfDescription, $generatePdfArr, $newOfferId);
				
				        updateOffer(
					        $newOfferId,
					        $receiverName,
					        $receiverMail,
					        $generatedPdf['file_name'],
					        $generatedPdf['file_url']
				        );
                        
                        unset($_POST['PdfOpening']);
                        unset($_POST['PdfDescription']);
                        
                        echo '
                        <iframe style="display: none;" src="/wp-admin/admin.php?page=download_pdf&pdf_name='
	                        .$generatedPdf['file_name'].
                            '"></iframe>
                        ';
			        }
		        }
	        }
        }
    }
}

if (isset($_POST['SendMail'])) {
    $action = $_POST['SendMail'];

    if($action === 'SendMail') {
	    $receiverName             = $_POST['EmailReceiverName'] ?? '';
	    $receiverMail             = $_POST['EmailReceiverMail'] ?? '';

	    if($receiverName != '' && $receiverMail != '') {
		    openSendMailPopup($receiverMail);

		    redirect('/wp-admin/admin.php?page=tilbud');
        }
    }
}
?>
<div class="loading-first-wrapper">
    <div class="loading-first">
        <div class="loader"></div>
    </div>
</div>
<div class="localliving-generer-pdf">
<div class="header sticky top-menu">
    <div class="page-title top-menu-left">
        <h1>Tilbudsgenerator</h1>
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
    <div class="search-results-wrapper">
    <div id="localliving-cart">
        <form name="generate-pdf-form" method="POST">
            <div class="generate-pdf-wrapper">
                <div class="email-receiver-name">
                    <?php $classError = isset($_POST['EmailReceiverName']) && $_POST['EmailReceiverName'] == '' ? 'validate-error' : ""; ?>
                    <input class="<?php echo $classError ?>" id="email-receiver-name-input"
                           name="EmailReceiverName"
                           placeholder="Navn på modtager" value="<?php echo $_POST['EmailReceiverName'] ?? '' ?>"/>
                </div>
                <div class="email-receiver-mail">
                    <?php $classError = isset($_POST['EmailReceiverMail']) && $_POST['EmailReceiverMail'] == '' ? 'validate-error' : ""; ?>
                    <input class="<?php echo $classError ?>" id="email-receiver-mail-input"
                           name="EmailReceiverMail"
                           placeholder="Modtager email" value="<?php echo $_POST['EmailReceiverMail'] ?? '' ?>"/>
                </div>
                <div class="generate-pdf">
                    <button id="send-mail-btn" class="btn btn-primary" name="SendMail" value="SendMail">
                        Send Mail
                    </button>
                </div>
            </div>
            <div class="border bg-white">
                <div class="search-results">
                    <div class="description-wrapper">
                        <h2>Beskrivelse til kunden</h2>
                        <div class="pdf-wrapper">
                            <div class="pdf-opening">
                                <label for="pdf-opening-input">
                                    Overskrift
                                </label>
                                <input
                                        id="pdf-opening-input"
                                        name="PdfOpening"
                                        value="<?php echo $_POST['PdfOpening'] ?? '' ?>"/>
                            </div>
			                <?php
                                if (isset($_POST['GeneratePdf']) && $_POST['GeneratePdf'] == 'GeneratePdf') {
	                                if (isset($_POST['PdfOpening']) && $_POST['PdfOpening'] == '') {
		                                echo '<p class="validate-error" style="color: red">Dette felt er påkrævet!</p>';
	                                }
                                }
			                ?>
                            <div class="pdf-description">
                                <label for="pdf-description-input">
                                    Beskrivelse
                                </label>
                                <textarea
                                        rows="20"
                                        cols="60"
                                        id="pdf-description-input"
                                        name="PdfDescription"><?php echo $_POST['PdfDescription'] ?? '' ?></textarea>
                            </div>
                            <p><span id="rchars">1400</span> tegn tilbage</p>
			                <?php
				                if (isset($_POST['GeneratePdf']) && $_POST['GeneratePdf'] == 'GeneratePdf') {
					                if (isset($_POST['PdfDescription']) && $_POST['PdfDescription'] == '') {
						                echo '<p class="validate-error" style="color: red">Dette felt er påkrævet!</p>';
					                }
					
					                if (isset($_POST['PdfDescription'])) {
						                $pdfDescription = $_POST['PdfDescription'];
						                $stringCounter  = mb_strlen(
							                str_replace("\r\n","\n", $pdfDescription), 'UTF-8');
						
						                if($stringCounter > 1400) {
							                echo '<p style="color: red">Dette felt har en grænse på 1400 tegn!</p>';
						                }
					                }
                                }
			                ?>
                            <div class="generate-pdf">
                                <button id="generate-pdf-btn" class="btn btn-primary" name="GeneratePdf"
                                        value="GeneratePdf">Generer PDF
                                </button>
                            </div>
                        </div>
                        <div class="frontpage-background-chooser">
                            <label for="process_custom_images">
                                Vælg baggrund på forsiden
                            </label>
                            <img id="img-preview"
                                 src="<?php
                                     $pdfFrontPageUrl = getFrontPageBgImageOption();
                                     if($pdfFrontPageUrl == '') {
	                                     $pdfFrontPageUrl = '/wp-content/plugins/localliving-plugin/assets/images/pdf/bg.png';
                                     }
                                     echo $pdfFrontPageUrl;
                                 ?>"/>
                            <p class="m-1">Foreslået dimension: 1191 x 1684 pixels</p>
                            <input type="hidden"
                                   value=""
                                   name="FrontpageBgAttachmentID"
                                   class="regular-text process_custom_images"
                                   id="process_custom_images">
                            <button class="set_custom_images button">Vælg billede</button>
                        </div>
                    </div>
                    <?php
                        $noResultHtml = '<div class="no-result"><h2 class="fw-normal">Ingen valgte boliger</h2>
                                <p>Vælg venligst én eller flere ferieboliger</p></div>';
                        
                        if (isset($_SESSION['localliving_cart'])) {
                            $cart = $_SESSION['localliving_cart'];
                            $totalResult = 0;
                            
                            foreach ($cart as $dateRange => $accommodationObj) {
                                $selectedPersonsArr = $accommodationObj['selectedPersons'];
	                            unset($accommodationObj['selectedPersons']);
                             
	                            $totalResult += count($accommodationObj);
                                if (count($accommodationObj) > 0) {
                                    echo '<section data-date-range="'.$dateRange.'">';
                                    $dateRangeArr =  explode("-", $dateRange);
                                    $fmt = new IntlDateFormatter(
                                        'da_DK',
                                        IntlDateFormatter::FULL,
                                        IntlDateFormatter::NONE,
                                        'CET',
                                        IntlDateFormatter::GREGORIAN,
                                        "EEE d MMM Y"
                                    );
                                    $dateFrom = $fmt->format(DateTime::createFromFormat('d/m/Y', $dateRangeArr[0])) . PHP_EOL;
                                    $dateTo = $fmt->format(DateTime::createFromFormat('d/m/Y', $dateRangeArr[1])) . PHP_EOL;
                                    if (class_exists('GetSearchResults')) {
                                        echo
                                            '
                                    <div class="search-results-title">
                                        <h2>Valgte boliger</h2>
                                        <h2>'.ucfirst($dateFrom)." – ".ucfirst($dateTo).'</h2>
                                    </div>
                                    ';
	                                    foreach ($accommodationObj as $accommodationID => $unitID) {
		                                    $selectedPersons = $selectedPersonsArr[$accommodationID] ?? 1;
		
		                                    $accommodationSearchResults = new iTravelAPI\GetSearchResults();
		                                    $object_description = array(
			                                    'ResponseDetail' => 'ObjectDescription',
			                                    'NumberOfResults' => '1'
		                                    );
		
//		                                    $objectIDList    = array();
//		                                    $selectedPersons = $selectedPersonsArr[$accommodationID] ?? 1;
		
//		                                    foreach ($accommodationObj as $accommodationID => $unitID) {
//			                                    $objectIDList[] = $accommodationID;
//			                                    $selectedPersons = $selectedPersonsArr[$accommodationID];
//		                                    }
		
		                                    $personFilter = array(
			                                    'AttributeID' => 120,
			                                    'AttributeValue' => (int) $selectedPersons,
			                                    'ComparisonType' => 'GreaterOrEqualThan'
		                                    );
		
		                                    $accommodationSearchResults->unitFilters[0] = $personFilter;
		
		                                    $accommodationSearchResults->from = date_create_from_format(
			                                    'd/m/Y',
			                                    $dateRangeArr[0]
		                                    )->setTime(0, 0);
		                                    $accommodationSearchResults->to = date_create_from_format(
			                                    'd/m/Y',
			                                    $dateRangeArr[1]
		                                    )->setTime(0, 0);
		                                    $accommodationSearchResults->objectIDList = array($accommodationID);
		                                    $accommodationSearchResults->outParameterList[] = $object_description;
		                                    $accommodationSearchResults->languageID = lemax_two_letter_iso_language_name();
		                                    //$accommodationSearchResults->pageSize = 20;
		                                    //$accommodationSearchResults->currencyID = 208;
		                                    $accommodationSearchResults->currencyID = iTravelGeneralSettings::GetCurrencyID(lemax_two_letter_iso_language_name());
		                                    $accommodationSearchResults->thumbnailWidth = 140;
		                                    $accommodationSearchResults->thumbnailHeight = 100;
		                                    $accommodationSearchResults->xsltPath =
			                                    iTravelGeneralSettings::$iTravelXSLTAccommodationCartPath;
		                                    $accommodationSearchResults->pageSize = 1;
		                                    $accommodationSearchResults->EchoSearchResults(array(), $dateRange);
	                                    }
                                    }
                                    echo '</section>';
                                }
                            }
                            
                            if($totalResult === 0) {
	                            echo $noResultHtml;
                            }
                        } else {
                            echo $noResultHtml;
                        }
                    ?>
                </div>
            </div>
        </form>
    </div>
</div>
</div>
