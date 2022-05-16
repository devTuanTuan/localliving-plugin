<?php
	/**
	 * LocalLiving dashboard page
	 */
	
	use LocalLiving_Plugin\iTravelAPI\iTravelGeneralSettings;
	use LocalLiving_Plugin\iTravelAPI;
	
	/** WordPress Administration Bootstrap */
	require_once(ABSPATH . 'wp-load.php');
	require_once(ABSPATH . 'wp-admin/admin.php');
	require_once(ABSPATH . 'wp-admin/admin-header.php');

//reset button script
	add_action('admin_footer', function () {
		echo
		'
        <script>
            jQuery(document).ready(function($) {
             //reset button
              $(document).on("click", "#btn-reset", function (e) {
                e.preventDefault();
            
                window.location = "?page=localliving";
              });
            });
        </script>
    ';
	});

//add to cart script
	add_action('admin_footer', function () {
		$cartJsonEncoded = '';
		
		if (isset($_SESSION['localliving_cart'])) {
			$cartJsonEncoded = base64_encode(json_encode($_SESSION['localliving_cart']));
		}
		
		echo
			'<script>
        
        jQuery(document).ready(function ($) {
           var decodedCartJson = "";
           
           $(document).on("click", ".add-to-cart", function(e) {
               e.preventDefault();
               
               var objectId = $(this).data("object-id");
               var unitId   = $(this).data("unit-id");
               var dateFrom = $("input[name=dateFrom]").val();
               var dateTo   = $("input[name=dateTo]").val();
               
               var unitIDList = [];
               
               if(!objectId) objectId = "";
               
               if(!unitId) unitId = "";
               
               //is add to cart button of Accommocation
               if(unitId === "") {
                   unitIDList = $(".add-to-cart[data-object-id=" + objectId + "]").map(function() {
                       return $(this).data("unit-id");
                   }).get();
                   
                   unitId = unitIDList;
               }
               
               $.ajax({
                type: "POST",
                dataType: "json",
                url: "' . admin_url("admin-ajax.php") . '",
                data: {
                   action: "add_to_cart",
                   objectId: objectId,
                   unitId: unitId,
                   dateFrom: dateFrom,
                   dateTo: dateTo
                },
                context: this,
                success: function(response) {
                    $(".cart-item-counter").text(response.total);
                    disableAddToCartBtn(response.cart);
                }
               })
           });
           
           if("' . $cartJsonEncoded . '" !== "") {
                decodedCartJson = JSON.parse(atob("' . $cartJsonEncoded . '"));
           }
           
           disableAddToCartBtn(decodedCartJson);
           
           function disableAddToCartBtn(cart) {
                var arrayLv1 = [];
                var arrayLv2 = [];
                
                var selectingDateFrom = $("input[name=dateFrom]").val();
                var selectingDateTo   = $("input[name=dateTo]").val();
            
                if(cart) {
                    for(var k1 in cart) {
                        if(k1 === selectingDateFrom+"-"+selectingDateTo) {
                                arrayLv1.push({
                                    "k1": k1,
                                    "v1": cart[k1]
                                });
                            
                                for(var k2 in cart[k1]) {
                                arrayLv2.push({
                                    "k2": k2,
                                    "v2": cart[k1][k2]
                                });
                            }
                        }
                    }
                 
                    $.map(arrayLv1, function( itemLv1 ) {
              
                        if(itemLv1.v1) {
                            var key1 = itemLv1.k1;
                            
                            var dateFrom = key1.split("-")[0];
                            var dateTo   = key1.split("-")[1];
                            
                            if(selectingDateFrom === "") {
                                selectingDateFrom = getDayAsString();
                            }
                            if(selectingDateTo === "") {
                                selectingDateTo = getDayAsString(true);
                            }
                            
                            $.map(arrayLv2, function( itemLv2 ) {
                                if(itemLv2.v2) {
                                    var key2 = itemLv2.k2;
                                }
                                
                                if(dateFrom === selectingDateFrom && dateTo === selectingDateTo) {
                                    var listOfAddToCartBtnByObj = $(".add-to-cart[data-object-id=" + key2 + "]");
                                    
                                    listOfAddToCartBtnByObj.map(function() {
                                        var unitIdOfBtn = $(this).attr("data-unit-id");
                                        if(typeof unitIdOfBtn === "undefined" || unitIdOfBtn === false) {
                                            $(this).removeClass("add-to-cart").addClass("remove-from-cart");
                                            $(this).text("Valgt");
                                        }
                                    });
                                    
                                    $.map(itemLv2.v2, function( tmp ) {
                                        if(tmp === key2) {
                                            var objectAddToCartBtn = $(".add-to-cart[data-object-id=" + tmp + "]");
                                            objectAddToCartBtn.removeClass("add-to-cart").addClass("remove-from-cart");
                                            objectAddToCartBtn.text("Valgt");
                                        } else {
                                            var unitAddToCartBtn = $(".add-to-cart[data-unit-id=" + tmp + "]");
                                            unitAddToCartBtn.removeClass("add-to-cart").addClass("remove-from-cart");
                                            unitAddToCartBtn.text("Valgt");
                                        }
                                    });
                                }
                            });
                        }
                    });
                }
            }
           
           function getDayAsString(afterAWeek = false) {
               var d = new Date();
               
               if(afterAWeek) {
                   d.setDate(d.getDate() + 7);
               }
               
               var month = d.getMonth() + 1;
      
               var day = d.getDate();
        
               return (("" + day).length < 2 ? "0" : "") + day + "/" +
                   (("" + month).length < 2 ? "0" : "") + month + "/" +
                   d.getFullYear();
           }
        });
    </script>';
	});

//remove from cart script
	add_action('admin_footer', function () {
		echo
			'
    <script>
    jQuery(document).ready(function ($) {
        $(document).on("click", ".remove-from-cart", function(e) {
            e.preventDefault();
           
            var objectId = $(this).data("object-id");
            var unitId   = $(this).data("unit-id");
            var dateFrom = $("input[name=dateFrom]").val();
            var dateTo   = $("input[name=dateTo]").val();
            
            var unitIDList = [];
            
            if(!objectId) objectId = "";
            
            if(!unitId) unitId = "";
           
            //is remove to cart button of Accommocation
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
                    
                    if($.isArray(unitId)) {
                        var objectAddToCartBtn = $(".remove-from-cart[data-object-id=" + objectId + "]");
                        objectAddToCartBtn.removeClass("remove-from-cart").addClass("add-to-cart");
                        objectAddToCartBtn.text("Vælg");
                    } else {
                        var unitAddToCartBtn = $(".remove-from-cart[data-unit-id=" + unitId + "]");
                        unitAddToCartBtn.removeClass("remove-from-cart").addClass("add-to-cart");
                        unitAddToCartBtn.text("Vælg");
                        var listOfObjectAddToCartBtn = $(".remove-from-cart[data-object-id=" + objectId + "]");
                        if(listOfObjectAddToCartBtn.length <= 1) {
                            listOfObjectAddToCartBtn.map(function() {
                                var unitIdOfBtn = $(this).attr("data-unit-id");
                                if(typeof unitIdOfBtn === "undefined" || unitIdOfBtn === false) {
                                    $(this).removeClass("remove-from-cart").addClass("add-to-cart");
                                    $(this).text("Vælg");
                                }
                            })
                        }
                    }
                }
            });
        });
    });
    </script>
    ';
	});

//loading script for viewAClass option
	add_action('admin_footer', function () {
		$viewAClass = '0';
        if(isset($_POST['viewAClass'])) {
            $viewAClass = $_POST['viewAClass'];
        }
		if ($viewAClass === '0') {
			return;
		}
		
		echo
		'
    <script>
        jQuery(document).ready(function ($) {
            $("div.article-wrapper").hide();

            var firstArticle = 0;
            var lastArticle = 5;

            $("div.article-wrapper").slice(firstArticle, lastArticle).show();
            var scroll = 0;
            $(window).scroll(function () {

                if (scroll > 0) {
                    if ($(window).height() + $(window).scrollTop() >= ($(document).height() - 50)) {
                        firstArticle += 5;
                        lastArticle += 5;
                        $("div.article-wrapper").slice(firstArticle, lastArticle).show();
                     }
                }
               scroll++;
            });
        });
    </script>
    ';
	});
	
	function renderRegionIdOptions()
	{
		$regionList = array(
			'66' => 'Ligurien',
			'87' => 'Norditalien',
			'69' => 'Sicilien',
			'49' => 'Toscana',
			'50' => 'Umbrien'
		);
		
		$selectedRegionIdList = $_POST['regionIDList'] ?? array();
		
		foreach ($regionList as $regionId => $regionLabel) {
			$selected = false;
			
			if (!empty($selectedRegionIdList)) {
				if (in_array($regionId, $selectedRegionIdList)) {
					$selected = true;
				}
			}
			
			if ($selected) {
				echo '<option value="' . $regionId . '" selected="selected">'
					. $regionLabel
					. '</option>';
			} else {
				echo '<option value="' . $regionId . '" >'
					. $regionLabel
					. '</option>';
			}
		}
	}
	
	function renderDestinationToIdOptions()
	{
		$destinationToList = array(
			'58' => 'Arezzo',
			'82' => 'Brescia',
			'56' => 'Cortona-området',
			'78' => 'Elba',
			'54' => 'Firenze',
			'68' => 'Gardasøen',
			'52' => 'Grosseto',
			'55' => 'Trasimeno søen',
			'84' => 'Lazio',
			'67' => 'Ligurien',
			'59' => 'Livorno',
			'64' => 'Lucca',
			'65' => 'Maremma',
			'80' => 'Massa Carrara',
			'63' => 'Perugia',
			'90' => 'Piemonte',
			'53' => 'Pisa',
			'60' => 'Pistoia',
			'77' => 'Rom',
			'72' => 'Sicilien',
			'51' => 'Siena',
			'74' => 'Siracusa',
			'57' => 'Sydlige Umbrien',
			'61' => 'Terni',
			'71' => 'Trapani',
			'85' => 'Toscana',
			'62' => 'Umbrien'
		);
		
		$selectedDestinationId = $_POST['destinationID'] ?? array();
		
		foreach ($destinationToList as $destinationToId => $destinationToLabel) {
			$selected = false;
			
			if ($destinationToId == $selectedDestinationId) {
				$selected = true;
			}
			
			if ($selected) {
				echo '<option value="' . $destinationToId . '" selected="selected">'
					. $destinationToLabel
					. '</option>';
			} else {
				echo '<option value="' . $destinationToId . '" >'
					. $destinationToLabel
					. '</option>';
			}
		}
	}
	
	function renderCategoryIdOptions()
	{
		$categoryList = array(
			'13' => 'Ferielejlighed',
			'14' => 'Agriturismo',
			'16' => 'Egen villa'
		);
		
		$selectedCategoryIdList = $_POST['categoryIDList'] ?? array();
		
		foreach ($categoryList as $categoryId => $categoryLabel) {
			$selected = false;
			
			if (!empty($selectedCategoryIdList)) {
				if (in_array($categoryId, $selectedCategoryIdList)) {
					$selected = true;
				}
			}
			
			if ($selected) {
				echo '<option value="' . $categoryId . '" selected="selected">'
					. $categoryLabel
					. '</option>';
			} else {
				echo '<option value="' . $categoryId . '" >'
					. $categoryLabel
					. '</option>';
			}
		}
	}
	
	function renderRoomsOptions()
	{
		$selectedRoomNumber = $_POST['rooms'] ?? 0;
		
		echo '<option value="0" selected="selected">'
			. 'Soveværelser'
			. '</option>';
		
		for ($i = 1; $i <= 14; $i++) {
			if ($selectedRoomNumber == $i) {
				echo '<option value="' . $i . '" selected="selected">'
					. $i . ''
					. '</option>';
			} else {
				echo '<option value="' . $i . '">'
					. $i . ''
					. '</option>';
			}
		}
	}
	
	function renderStarsFilter()
	{
		$starsFilter = array(
			'3',
			'3.5',
			'4',
			'4.5',
			'5'
		);
		
		$selectedStarFilter = $_POST['numberOfStarsCategory'] ?? array();
		
		foreach ($starsFilter as $star) {
			echo '<li>';
			echo '<label data-type="checkbox">';
			echo '<input type="checkbox" id="' . $star . '" name="numberOfStarsCategory[]" value="' . $star . '" ';
			if (in_array($star, $selectedStarFilter)) {
				echo 'checked="checked"';
			}
			echo '/>';
			echo '<span class="mark"></span>';
			echo '<span class="stars">';
			for ($i = 1; $i <= round(doubleval($star), 0, PHP_ROUND_HALF_DOWN); $i++) {
				echo '<span class="star"></span>';
			}
			if (is_numeric(doubleval($star)) && floor(doubleval($star)) != doubleval($star)) {
				echo '+';
			}
			echo '</span>';
			echo '</label>';
			echo '</li>';
		}
	}
	
	function renderFacilitiesFilter()
	{
		$facilitiesFilter = array(
			'ownTerrace' => array(
				'label' => 'Egen terrasse',
				'value' => '841_1_1'
			),
			'ownProductionOfOilCheeseOrWine' => array(
				'label' => 'Egen produktion af olie, ost eller vin',
				'value' => '842_1_1'
			),
			'aircondition' => array(
				'label' => 'Aircondition',
				'value' => '840_1_1'
			),
			'dishwasher' => array(
				'label' => 'Opvaskemaskine',
				'value' => '845_1_1'
			),
			'breakfast' => array(
				'label' => 'Morgenmad',
				'value' => '846_1_1'
			),
			'restaurant' => array(
				'label' => 'Restaurant',
				'value' => '847_1_1'
			),
			'internet' => array(
				'label' => 'Internet',
				'value' => '848_1_1'
			),
			'pool' => array(
				'label' => 'Pool',
				'value' => '849_1_1'
			),
			'pets' => array(
				'label' => 'Kæledyr tilladt',
				'value' => '857_1_1'
			),
			'veryVeryQuiet' => array(
				'label' => 'Meget, meget stille',
				'value' => '900_1_1'
			),
			'barbecue' => array(
				'label' => 'Barbecue',
				'value' => '890_1_1'
			),
			'childrensPool' => array(
				'label' => 'Børnepool',
				'value' => '871_1_1'
			),
			'veryChildFrendly' => array(
				'label' => 'Meget børnevenligt',
				'value' => '899_1_1'
			),
			'swimmingPoolWithSaltWater' => array(
				'label' => 'Swimmingpool med saltvand',
				'value' => '1231_1_1'
			),
		);
		
		$selectedFacilitiesFilter = $_POST['objectAttributeFilters'] ?? array();
		
		foreach ($facilitiesFilter as $facility => $options) {
			echo '<li>';
			echo '<label data-type="checkbox">';
			echo '<input type="checkbox" id="'
				. $facility .
				'" name="objectAttributeFilters[]" value="'
				. $options['value'] .
				'" ';
			if (in_array($options['value'], $selectedFacilitiesFilter)) {
				echo 'checked="checked"';
			}
			echo '/>';
			echo '<span class="mark"></span>';
			_e($options['label'], 'localliving');
			echo '</label>';
			echo '</li>';
		}
	}
	
	function renderActivitiesFilter()
	{
		$activitiesFilter = array(
			'cookingSchool' => array(
				'label' => 'Kokkeskole',
				'value' => '851_1_1'
			),
			'tennisAtTheProperty' => array(
				'label' => 'Tennis',
				'value' => '852_1_1'
			),
			'cycling' => array(
				'label' => 'Cykler',
				'value' => '853_1_1'
			),
			'horseBackRiding' => array(
				'label' => 'Ridning',
				'value' => '1209_1_1'
			),
			'ownersOwnCooking' => array(
				'label' => 'Værtsparret laver mad',
				'value' => '1007_1_1'
			),
			'pingPong' => array(
				'label' => 'Bordtennis',
				'value' => '854_1_1'
			),
			'cook' => array(
				'label' => 'Kok',
				'value' => '1009_1_1'
			),
			'footballField' => array(
				'label' => 'Fodboldbane',
				'value' => '966_1_1'
			),
			'playground' => array(
				'label' => 'Legeplads',
				'value' => '855_1_1'
			),
			'wellness' => array(
				'label' => 'Wellness',
				'value' => '1020_1_1'
			),
			'llsr' => array(
				'label' => 'Local Living Sikker Rejse',
				'value' => '10099_1_1'
			)
		);
		
		$selectedActivitiesFilter = $_POST['objectAttributeFilters'] ?? array();
		
		foreach ($activitiesFilter as $activity => $options) {
			echo '<li>';
			echo '<label data-type="checkbox">';
			echo '<input type="checkbox" id="'
				. $activity .
				'" name="objectAttributeFilters[]" value="'
				. $options['value'] .
				'" ';
			if (in_array($options['value'], $selectedActivitiesFilter)) {
				echo 'checked="checked"';
			}
			echo '/>';
			echo '<span class="mark"></span>';
			_e($options['label'], 'localliving');
			echo '</label>';
			echo '</li>';
		}
	}
	
	function renderThemesFilter()
	{
		$themesFilter = array(
			't21' => array(
				'label' => 'Local Living Luxury',
				'value' => '21'
			),
			't19' => array(
				'label' => 'Local Living Wine',
				'value' => '19'
			),
			't20' => array(
				'label' => 'Local Living Sport',
				'value' => '20'
			),
			't27' => array(
				'label' => 'Local Living Wellness',
				'value' => '27'
			),
			't22' => array(
				'label' => 'Local Living Family',
				'value' => '22'
			),
			't25' => array(
				'label' => 'Local Living Bryllup',
				'value' => '25'
			),
			't26' => array(
				'label' => 'Local Living Teambuilding',
				'value' => '26'
			),
			't23' => array(
				'label' => 'Local Living Bæredygtig',
				'value' => '23'
			),
			't28' => array(
				'label' => 'Local Living Agriturismo',
				'value' => '28'
			),
			't31' => array(
				'label' => 'Local Living weekend i Toscana',
				'value' => '31'
			),
			't38' => array(
				'label' => 'Local Living Sikker rejse',
				'value' => '38'
			),
			't37' => array(
				'label' => 'Local Living Forårsoplevelser',
				'value' => '37'
			),
		);
		
		$selectedThemesFilter = $_POST['categoryIntersectionID'] ?? array();
		
		foreach ($themesFilter as $theme => $options) {
			echo '<li>';
			echo '<label data-type="checkbox">';
			echo '<input type="checkbox" id="'
				. $theme .
				'" name="categoryIntersectionID[]" value="'
				. $options['value'] .
				'" ';
			if (in_array($options['value'], $selectedThemesFilter)) {
				echo 'checked="checked"';
			}
			echo '/>';
			echo '<span class="mark"></span>';
			_e($options['label'], 'localliving');
			echo '</label>';
			echo '</li>';
		}
	}
	
	function renderDistanceToTownLessThan2kmCheckbox()
	{
		$value = '102_3_2000';
		
		$objectAttributeFilters = $_POST['objectAttributeFilters'] ?? array();
		
		if (in_array($value, $objectAttributeFilters)) {
			echo '<label for="distanceToTownLessThan2km">
        <input
            id="distanceToTownLessThan2km"
            name="objectAttributeFilters[]"
            value="' . $value . '"
            type="checkbox"
            checked="checked"
            />
        <span class="mark"></span>
        Max. <strong>2 km</strong> til by
    </label>';
		} else {
			echo '<label for="distanceToTownLessThan2km">
        <input
            id="distanceToTownLessThan2km"
            name="objectAttributeFilters[]"
            value="' . $value . '"
            type="checkbox"
            />
        <span class="mark"></span>
        Max. <strong>2 km</strong> til by
    </label>';
		}
	}
	
	function renderDistanceToTheSeaIsLessThan20kmCheckbox()
	{
		$value = '101_3_20000';
		
		$objectAttributeFilters = $_POST['objectAttributeFilters'] ?? array();
		
		if (in_array($value, $objectAttributeFilters)) {
			echo '<label for="distanceToTheSeaIsLessThan20km">
        <input
            id="distanceToTheSeaIsLessThan20km"
            name="objectAttributeFilters[]"
            value="' . $value . '"
            type="checkbox"
            checked="checked"
            />
        <span class="mark"></span>
        Max. <strong>20 km</strong> til havet
    </label>';
		} else {
			echo '<label for="distanceToTheSeaIsLessThan20km">
        <input
            id="distanceToTheSeaIsLessThan20km"
            name="objectAttributeFilters[]"
            value="' . $value . '"
            type="checkbox"
            />
        <span class="mark"></span>
        Max. <strong>20 km</strong> til havet
    </label>';
		}
	}
	
	function renderSortBy()
	{
		$sortByPriceIsActive = false;
		
		$sortByStarsIsActive = false;
		
		if (isset($_POST['sortByPrice'])) {
			if ($_POST['sortByPrice'] === 'asc') {
				$sortByPriceIsActive = true;
			}
		}
		
		if (isset($_POST['sortByStars'])) {
			if ($_POST['sortByStars'] === 'desc') {
				$sortByStarsIsActive = true;
			}
		}
		
		echo '<div class="search-results-sort">';
		echo 'Sorter efter:';
		$viewFullUnits = "0";
		if (isset($_POST['viewFullUnits'])) {
            $viewFullUnits = $_POST['viewFullUnits'];
			
		}
        if($viewFullUnits === "0") {
	        echo '<span id="sort-by-price" class="sort';
	        if ($sortByPriceIsActive) {
		        echo ' active';
	        }
	        echo '">Pris</span>';
	        echo '|';
        }
		echo '<span id="sort-by-stars" class="sort';
		if ($sortByStarsIsActive) {
			echo ' active';
		}
		echo '">Antal stjerner</span>';
		echo '</div>';
	}
	
	function renderViewOptionCheckboxes()
	{
		$viewOptions = array(
//			'viewFullUnits' => 'Vis alle ferieboliger',
			'viewAClass' => 'Vis A-klasse boliger først',
			'viewSpecialOfferOnly' => 'Vis specialtilbud'
		);
		
		$isDefaultView = !isset($_POST['viewAClass']) &&
			!isset($_POST['viewSpecialOfferOnly']) &&
			!isset($_POST['viewFullUnits']);
		
		echo '<div class="filter-wrapper">';
		foreach ($viewOptions as $optionName => $optionLabel) {
			$optionValue = '0';
   
			echo '<div class="filter">';
			echo '<label data-type="checkbox">';
			echo '<input class="view-options-input" type="checkbox" name="' . $optionName . '"';
   
			if (isset($_POST[$optionName])) {
                $optionValue = $_POST[$optionName];
				
                if($optionValue === '1') {
	                echo 'checked="checked"';
                }
			}
            if($isDefaultView && $optionName === "viewAClass") {
	            echo 'checked="checked"';
            }
			echo '/>';
			echo '<input class="view-options-hidden-input" type="hidden" name="' . $optionName . '" value="';
			if(($isDefaultView && $optionName === "viewAClass") || $optionValue === '1') {
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
	
	function renderTilbudSummarization()
	{
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'localliving_plg_offer_list';
		
		$tilbudStatusArr = array(
			'red' => 'tilbud uden aktion de sidste 5 dage',
			'yellow' => 'tilbud som mangler afgørelse',
			'green' => 'lukkede tilbud'
		);
		
		foreach ($tilbudStatusArr as $status => $label) {
			$query = "SELECT *
            FROM $table_name
            WHERE $table_name.offer_status = '$status'";
			
			$countTilbudByStatus = $wpdb->get_var("SELECT COUNT(1) FROM ($query) AS combined_table");
			
			echo '<div class="summary-offer-status summary-offer-status-' . $status . '">';
			echo '<button type="submit" name="OfferStatus" value="'. $status .'">';
			echo $countTilbudByStatus . ' ' . $label;
			echo '</button>';
			echo '</div>';
		}
	}
    
    function countAccommodationIcalReminderByStatus($searchStatus) {
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
	    $query .= "
            FROM $table_name_accommodations
            LEFT JOIN $table_name_suppliers
            ON $table_name_accommodations.accommodation_supplier_id = $table_name_suppliers.supplier_id
            WHERE $table_name_accommodations.accommodation_exception_status = 0
            GROUP BY $table_name_accommodations.accommodation_id
        ";
	
	    if ($searchStatus == 'oyr') {
		    $query .= " HAVING (ical_status_color = 'orange'
	            OR ical_status_color = 'yellow' OR ical_status_color = 'red')";
	    } else {
		    $query .= " HAVING ical_status_color = '$searchStatus'";
	    }
	
	    return $wpdb->get_var("SELECT COUNT(1) FROM ($query) AS combined_table");
    }
    
    function renderIcalReminderSummarization() {
        $icalRemindersStatusArr = array(
	        'oyr'     => 'mangler opdateringer',
	        'green'   => 'opdaterede kalendre',
	        'yellow'  => 'klar til første påmindelse (> 5 dage)',
	        'orange'  => 'har modtaget første påmindelse',
	        'red'     => 'klar til anden påmindelse (> 3 dage)',
        );
        
        foreach ($icalRemindersStatusArr as $status => $label) {
            $icalStatusCounter = countAccommodationIcalReminderByStatus($status);
	
	        echo '<div class="summary-ical-status summary-ical-status-' . $status . '">';
	        echo '<button type="submit" name="FerieboligerStatus" value="'. $status .'">';
	        echo $icalStatusCounter . ' ' . $label;
            echo '</button>';
            echo '</div>';
        }
    }

?>
    <div class="loading-first-wrapper">
        <div class="loading-first">
            <div class="loader"></div>
        </div>
    </div>
    <div class="localliving-dashboard">
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
		
		                foreach ($cart as $selectedAccommodation) {
			                $total += count($selectedAccommodation);
		                }
	                }
	
	                echo $total;
                ?>
            </span>
                    <a href="?page=generer_pdf">
                        <img width="40px"
                             src="<?php echo plugin_dir_url(__FILE__) . '/assets/images/home-outline.svg' ?>"
                             title="Local Living Home"
                             alt="localliving-home"/>
                    </a>
                </div>
            </div>
        </div>

        <form name="localliving-form" method="POST">
            <div class="filter-wrapper">
                <div class="region-select select-wrapper">
                    <select id="region-id-select" name="regionIDList[]" multiple>
						<?php renderRegionIdOptions(); ?>
                    </select>
                </div>
                <div class="date-range">
                    <input type="text" id="start-date-input"/>
                    <span class="divider"></span>
                    <input type="text" id="end-date-input"/>
                </div>
                <div class="persons-wrapper">
                    <div class="persons">
                        <span class="value"><?php echo $_POST['persons'] ?? '1' ?></span>personer
                    </div>
                    <div class="persons-selector">
                        <div class="wrapper">
                            <label for="persons">Personer</label>
                            <div class="persons-quantity quantity">
                                <div class="quantity-remove">-</div>
                                <input type="number" name="persons" min="1"
                                       value="<?php echo $_POST['persons'] ?? '1' ?>"/>
                                <div class="quantity-add">+</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="rooms-wrapper">
                    <div class="rooms-selector">
                        <select id="rooms-number-select" name="rooms">
							<?php renderRoomsOptions(); ?>
                        </select>
						<?php
							$roomsLabel = "";
							if (isset($_POST['rooms'])) {
								if ($_POST['rooms'] > 0) {
									$roomsLabel = "show";
								}
							}
						?>
                        <div class="rooms-label <?php echo $roomsLabel; ?>">Soveværelser</div>
                    </div>
                </div>
                <div class="category-select select-wrapper">
                    <select id="category-id-select" name="categoryIDList[]" multiple>
						<?php renderCategoryIdOptions(); ?>
                    </select>
                </div>
                <button id="search-action-submit" class="btn btn-primary" name="SubmitAction" type="submit"
                        value="search">Søg
                </button>
                <button id="btn-reset"></button>
            </div>
			<?php if (isset($_POST['Pagination']) || isset($_POST['SubmitAction'])) : ?>
                <div class="search-results-wrapper">
                    <ul class="dropdown-list">
                        <li class="dropdown">
                            <a href="#" data-toggle="dropdown">
                                Antal stjerner
                                <div class="icon">
                                    <img class="chevron-down" width="25px"
                                         src="<?php echo plugin_dir_url(__FILE__) . '/assets/images/chevron-down-outline.svg' ?>">
                                    <img class="close" width="30px"
                                         src="<?php echo plugin_dir_url(__FILE__) . '/assets/images/close-outline.svg' ?>">
                                </div>
                            </a>
                            <ul class="dropdown-menu stars-list" id="category">
								<?php renderStarsFilter(); ?>
                            </ul>
                        </li>
                        <li class="dropdown">
                            <a href="#" data-toggle="dropdown">
                                Faciliteter
                                <div class="icon">
                                    <img class="chevron-down" width="25px"
                                         src="<?php echo plugin_dir_url(__FILE__) . '/assets/images/chevron-down-outline.svg' ?>">
                                    <img class="close" width="30px"
                                         src="<?php echo plugin_dir_url(__FILE__) . '/assets/images/close-outline.svg' ?>">
                                </div>
                            </a>
                            <ul class="dropdown-menu" id="facilities">
								<?php renderFacilitiesFilter(); ?>
                            </ul>
                        </li>
                        <li class="dropdown">
                            <a href="#" data-toggle="dropdown">
                                Aktiviteter
                                <div class="icon">
                                    <img class="chevron-down" width="25px"
                                         src="<?php echo plugin_dir_url(__FILE__) . '/assets/images/chevron-down-outline.svg' ?>">
                                    <img class="close" width="30px"
                                         src="<?php echo plugin_dir_url(__FILE__) . '/assets/images/close-outline.svg' ?>">
                                </div>
                            </a>
                            <ul class="dropdown-menu" id="activities">
								<?php renderActivitiesFilter(); ?>
                            </ul>
                        </li>
                        <li class="dropdown">
                            <a href="#" data-toggle="dropdown">
                                Tema
                                <div class="icon">
                                    <img class="chevron-down" width="25px"
                                         src="<?php echo plugin_dir_url(__FILE__) . '/assets/images/chevron-down-outline.svg' ?>">
                                    <img class="close" width="30px"
                                         src="<?php echo plugin_dir_url(__FILE__) . '/assets/images/close-outline.svg' ?>">
                                </div>
                            </a>
                            <ul class="dropdown-menu" id="tema">
								<?php renderThemesFilter(); ?>
                            </ul>
                        </li>
                        <li class="dropdown">
                            <a href="#" data-toggle="dropdown">
                                Afstand til lokation
                                <div class="icon">
                                    <img class="chevron-down" width="25px"
                                         src="<?php echo plugin_dir_url(__FILE__) . '/assets/images/chevron-down-outline.svg' ?>">
                                    <img class="close" width="30px"
                                         src="<?php echo plugin_dir_url(__FILE__) . '/assets/images/close-outline.svg' ?>">
                                </div>
                            </a>
                            <ul class="dropdown-menu">
                                <li>
                                    <select id="distance-from-region-id-select" name="regionIDList[]" multiple>
										<?php renderRegionIdOptions(); ?>
                                    </select>
                                </li>
                                <li>
                                    <select id="distance-to-destination-id-select" name="destinationID">
                                        <option value="">Større byer</option>
										<?php renderDestinationToIdOptions(); ?>
                                    </select>
                                </li>
                                <li>
									<?php renderDistanceToTownLessThan2kmCheckbox(); ?>
                                </li>
                                <li>
									<?php renderDistanceToTheSeaIsLessThan20kmCheckbox(); ?>
                                </li>
                            </ul>
                        </li>
                        <li class="filter-btn">
                            <button id="btn-reset" class="btn">Nulstil</button>
                            <button id="search-action-submit" class="btn btn-primary" name="SubmitAction" type="submit"
                                    value="search">Gem
                            </button>
                        </li>
                    </ul>

                    <div id="search-results" class="search-results">
						<?php
							if (class_exists('GetSearchResults')) {
								if (isset($_POST['Pagination'])) {
									$fields = explode('&', $_POST['Pagination']);
									$arr = array();
									foreach ($fields as $field) {
										$tmp = explode('=', $field);
										$_POST[$tmp[0]] = $tmp[1];
									}
								}
								
								if (isset($_POST['numberOfStarsCategory'])) {
									$tmp = $_POST['numberOfStarsCategory'];
									if (is_array($tmp)) {
										unset($_POST['numberOfStarsCategory']);
										
										$_POST['numberOfStarsCategory'] = implode(',', $tmp);
									}
								}
								
								if (isset($_POST['objectAttributeFilters'])) {
									$tmp = $_POST['objectAttributeFilters'];
									if (is_array($tmp)) {
										unset($_POST['objectAttributeFilters']);
										
										$_POST['objectAttributeFilters'] = implode(',', $tmp);
									}
								}
								
								if (isset($_POST['categoryIntersectionID'])) {
									$tmp = $_POST['categoryIntersectionID'];
									if (is_array($tmp)) {
										unset($_POST['categoryIntersectionID']);
										
										$_POST['categoryIntersectionID'] = implode(',', $tmp);
									}
								}
								
								if (isset($_POST['sortByPrice']) && isset($_POST['sortByStars'])) {
									if ($_POST['sortByPrice'] === '' && $_POST['sortByStars'] === '') {
										$_POST['sortByPrice'] = 'asc';
									}
									
									if (isset($_POST['viewFullUnits'])) {
                                        $viewFullUnits = $_POST['viewFullUnits'];
                                        
                                        if($viewFullUnits === "1") {
	                                        unset($_POST['sortByPrice']);
	                                        $_POST['sortByStars'] = 'desc';
                                        }
									}
								}
								echo '<div class="search-results-filter">';
								renderViewOptionCheckboxes();
								renderSortBy();
								echo '</div>';
								
								$accommodationSearchResults = new iTravelAPI\GetSearchResults();
								$object_description = array(
									'ResponseDetail' => 'ObjectDescription',
									'NumberOfResults' => '1'
								);
								
								if (isset($_POST['dateFrom']) && isset($_POST['dateTo'])) {
									$dateFrom = $_POST['dateFrom'] == '' ?
										date('d/m/Y') : $_POST['dateFrom'];
									$dateTo = $_POST['dateTo'] == '' ?
										date('d/m/Y', strtotime('+7 days')) : $_POST['dateTo'];
									$dateFrom = date_create_from_format(
										'd/m/Y',
										$dateFrom
									);
									$dateTo = date_create_from_format(
										'd/m/Y',
										$dateTo
									);
									
									$accommodationSearchResults->from = $dateFrom->setTime(0, 0);
									$accommodationSearchResults->to = $dateTo->setTime(0, 0);
								}
								
								if (isset($_POST['regionIDList'])) {
									$accommodationSearchResults->regionID =
										implode(',', $_POST['regionIDList']);
								}
								
								if (isset($_POST['categoryIDList'])) {
									$accommodationSearchResults->categoryID =
										implode(',', $_POST['categoryIDList']);
								}
								
								if (isset($_POST['rooms'])) {
									if ($_POST['rooms'] > 0) {
										$roomsFilter = array(
											'AttributeID' => 1182,
											'AttributeValue' => $_POST['rooms'],
											'ComparisonType' => 'Equals'
										);
										
										$accommodationSearchResults->unitFilters[0] = $roomsFilter;
									}
								}
								
								if (isset($_POST['persons'])) {
									$personFilter = array(
										'AttributeID' => 120,
										'AttributeValue' => $_POST['persons'],
										'ComparisonType' => 'GreaterOrEqualThan'
									);
									
									$accommodationSearchResults->unitFilters[1] = $personFilter;
								}
								
								$accommodationSearchResults->outParameterList[] = $object_description;
								$accommodationSearchResults->languageID = lemax_two_letter_iso_language_name();
								//$accommodationSearchResults->pageSize = 20;
								//$accommodationSearchResults->currencyID = 208;
								$accommodationSearchResults->currencyID = iTravelGeneralSettings::GetCurrencyID(lemax_two_letter_iso_language_name());
								$accommodationSearchResults->thumbnailWidth = 140;
								$accommodationSearchResults->thumbnailHeight = 100;
								$accommodationSearchResults->xsltPath =
									iTravelGeneralSettings::$iTravelXSLTAccommodationSearchResultsPath;
                                
                                $isDefaultView = !isset($_POST['viewAClass']) &&
                                    !isset($_POST['viewSpecialOfferOnly']) &&
	                                !isset($_POST['viewFullUnits']);
                                
                                if($isDefaultView) {
	                                $accommodationSearchResults->pageSize = 10000;
	                                $accommodationSearchResults->xsltPath =
		                                iTravelGeneralSettings::$iTravelXSLTAccommodationSearchResultsABClassPath;
                                }
                                
								if (isset($_POST['viewAClass'])) {
									$viewAClass = $_POST['viewAClass'];
									
									if ($viewAClass === "1") {
										$accommodationSearchResults->pageSize = 10000;
										$accommodationSearchResults->xsltPath =
											iTravelGeneralSettings::$iTravelXSLTAccommodationSearchResultsABClassPath;
									}
								}
        
								if (isset($_POST['viewSpecialOfferOnly'])) {
									$viewSpecialOfferOnly = $_POST['viewSpecialOfferOnly'];
									
									if ($viewSpecialOfferOnly === "1") {
										$accommodationSearchResults->onlyOnSpecialOffer = true;
									}
								}
        
								if (isset($_POST['viewFullUnits'])) {
									$viewFullUnits = $_POST['viewFullUnits'];
									
									if ($viewFullUnits === "1") {
										$accommodationSearchResults->ignorePriceAndAvailability = true;
										$accommodationSearchResults->xsltPath =
											iTravelGeneralSettings::$iTravelXSLTAccommodationSearchResultsFullUnitsPath;
									}
								}
								$accommodationSearchResults->EchoSearchResults();
							}
						?>
                    </div>
                </div>
			<?php endif; ?>
            <input type="hidden" id="dateFrom" name="dateFrom"
                   value="<?php echo $_POST['dateFrom'] ?? date("d/m/Y") ?>"/>
            <input type="hidden" id="dateTo" name="dateTo"
                   value="<?php echo $_POST['dateTo'] ?? date("d/m/Y", strtotime("+7 day")) ?>"/>
            <input type="hidden" id="sortByPrice" name="sortByPrice" value="<?php echo $_POST['sortByPrice'] ?? '' ?>"/>
            <input type="hidden" id="sortByStars" name="sortByStars" value="<?php echo $_POST['sortByStars'] ?? '' ?>"/>
        </form>
        <?php if (!isset($_POST['Pagination']) && !isset($_POST['SubmitAction'])) : ?>
            <div class="dashboard-wrapper">
				<div class="row">
					<div class="col-lg-6">
						<div class="tilbud-summary">
							<form name="" action="<?php echo admin_url( 'admin.php' ) . '?page=tilbud'; ?>" method="POST">
								<h2 class="tilbud-summary-title">Tilbud</h2>
								<?php renderTilbudSummarization(); ?>
							</form>
						</div>
					</div>
                    <div class="col-lg-6">
                        <div class="ical-summary">
                            <form name="" action="<?php echo admin_url( 'admin.php' ) . '?page=ferieboliger'; ?>" method="POST">
                                <h2 class="ical-summary-title">Kalender opdateringer</h2>
								<?php renderIcalReminderSummarization(); ?>
                            </form>
                        </div>
                    </div>
				</div>
			</div>
        <?php endif; ?>
    </div>

<?php include(ABSPATH . 'wp-admin/admin-footer.php');
