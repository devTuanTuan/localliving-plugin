<?php
	add_action('admin_footer', function () {
		echo
		'<script>
        jQuery(document).ready(function ($) {
            $(document).on("click", ".delete-offer", function (event) {
                var offerName = $(this).parents("tr").find("td.offer-name").text();
                var message   = "Are you sure to delete this offer? (" + offerName + ")\n" +
                 "This action is cannot be undone";
                
                if(!confirm(message)) {
                    event.preventDefault();
                }
            });
            
            //reset button
            $(document).on("click", "#btn-reset", function (e) {
                e.preventDefault();
            
                window.location = "?page=tilbud";
            });
            
            $(document).on("change", "#offer-status-select", function() {
                $("#search-offer-btn").click();
            });
            
            $(document).on("change", ".edit-offer-status-select", function() {
                var offer_id         = $(this).data("offer_id");
                var offer_new_status = $(this).val();

                $.ajax({
                    type: "POST",
                    dataType: "json",
                    url: "' . admin_url("admin-ajax.php") . '",
                    data: {
                       action: "manual_edit_offer_status",
                       offer_id: offer_id,
                       offer_new_status: offer_new_status
                    },
                    context: this
                });
            });
        });
    </script>';
	});
	
	function renderPagination($dataTotal, $perPage, $currentPage)
	{
		$numberOfPages = ceil($dataTotal / $perPage);
		
		echo '<div class="pagination-sort-holder">';
		echo '<ul class="pagination-list">';
		
		if ($currentPage <= 1) {
			echo '<li><button disabled="disabled">«</button></li>';
			echo '<li><button disabled="disabled">‹</button></li>';
		} else {
			echo '<li><button type="submit" name="cpage" value="1">«</button></li>';
			echo '<li><button type="submit" name="cpage" value="' . ($currentPage - 1) . '">‹</button></li>';
		}
		
		echo '<div class="page-counter">';
		echo $currentPage . ' of ' . $numberOfPages;
		echo '</div>';
		
		if ($currentPage >= $numberOfPages) {
			echo '<li><button disabled="disabled">›</button></li>';
			echo '<li><button disabled="disabled">»</button></li>';
		} else {
			echo '<li><button type="submit" name="cpage" value="' . ($currentPage + 1) . '">›</button></li>';
			echo '<li><button type="submit" name="cpage" value="' . $numberOfPages . '">»</button></li>';
		}
		
		echo '</ul>';
		echo '</div>';
	}
	
	function deleteOffer($offerId)
	{
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'localliving_plg_offer_list';
		
		$result = $wpdb->get_row("
        SELECT * FROM $table_name
        WHERE offer_id = $offerId
    ");
		
		$offerName = $result->offer_name;
		
		if ($offerName != '') {
			$folder = explode('_', $offerName)[1];
			$fileDestination = PDF_LOGS . $folder . '/' . $offerName . '.pdf';
			
			if (file_exists($fileDestination)) {
				unlink($fileDestination);
			}
		}
		
		$data = array(
			'offer_url' => '',
			'offer_status' => 'deleted'
		);
		
		$where = array(
			'offer_id' => $offerId
		);
		
		$wpdb->update($table_name, $data, $where);
	}
	
	function openSendMailPopup($receiverMail)
	{
		echo
			'<script>
            window.open("mailto:'.$receiverMail.'", "_blank");
        </script>';
	}
	
	function renderOfferStatusFilter()
	{
		$offerStatusList = array(
            'black'  => 'Vis alle',
			'red'    => 'Vis tilbud uden aktion de sidste 5 dage',
			'green'  => 'Vis lukkede tilbud',
			'yellow' => 'Vis tilbud som mangler afgørelse'
		);
		
		$selectedOfferStatus = $_POST['OfferStatus'] ?? '';
		
		foreach ($offerStatusList as $offerStatusValue => $offerStatusLabel) {
			$selected = $selectedOfferStatus == $offerStatusValue;
			
			if ($selected) {
				echo '<option value="' . $offerStatusValue . '" selected="selected">'
					. $offerStatusLabel
					. '</option>';
			} else {
				echo '<option value="' . $offerStatusValue . '">'
					. $offerStatusLabel
					. '</option>';
			}
		}
	}
    
    function renderTilbudResultRow($tilbudStatus, $tilbudId){
        
        $statusArr = ['red','green','yellow'];
        
        echo '<td class="offer-status-column">';
        echo '<select data-offer_id="'.$tilbudId.'" class="edit-offer-status-select">';
        foreach ($statusArr as $status) {
            echo '<option value="'.$status.'"';
            if($status == $tilbudStatus) {
                echo ' selected';
            }
            echo '>';
            echo $status;
            echo '</option>';
        }
        echo '</select>';
        echo '</td>';
    }
	
	if (isset($_POST['DeleteOffer'])) {
		$deleteOfferId = $_POST['DeleteOffer'];
		
		if ($deleteOfferId != '') {
			deleteOffer($deleteOfferId);
		}
	}
	
	if (isset($_POST['SendOffer'])) {
		$sendOfferId = $_POST['SendOffer'];
		
		if ($sendOfferId != '') {
			global $wpdb;
			
			$table_name = $wpdb->prefix . 'localliving_plg_offer_list';
			
			$result = $wpdb->get_row("
            SELECT * FROM $table_name
            WHERE offer_id = $sendOfferId
            AND offer_status != 'deleted'
        ");
			
			if ($result) {
				$receiverName = $result->offer_receiver;
				$receiverMail = $result->offer_receiver_email;
				$offerUrl = $result->offer_url;
				
				openSendMailPopup($receiverMail);
				
				$data = array(
					'offer_status' => 'yellow'
				);
				
				$where = array(
					'offer_id' => $sendOfferId
				);
				
				$wpdb->update($table_name, $data, $where);
			}
		}
	}
?>
<div class="loading-first-wrapper">
    <div class="loading-first">
        <div class="loader"></div>
    </div>
</div>
<div class="localliving-tilbud" style="display:none">
    <div class="header sticky top-menu">
        <div class="page-title top-menu-left">
            <h1>Tilbud</h1>
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
    <div class="search-offer-list-wrapper">
        <div id="localliving-offer-list">
            <form name="offer-list-form" method="POST">
                <div class="offer-list-wrapper filter-wrapper">
                    <div class="offer-status filter-status" data-color="<?php echo $_POST['OfferStatus'] ?? 'black' ?>">
                        <select id="offer-status-select" name="OfferStatus">
							<?php renderOfferStatusFilter() ?>
                        </select>
                    </div>
                    <div class="offer-search-combine filter-search">
                        <input id="offer-search-combine-input"
                               name="OfferSearchCombine"
                               placeholder="Søg på modtager navn, e-mail eller tilbudsnummer"
                               value="<?php echo $_POST['OfferSearchCombine'] ?? '' ?>"
                        />
                    </div>
                    <div class="search-offer">
                        <button id="search-offer-btn" class="btn btn-primary" name="SearchOffer" value="SearchOffer">
                            Søg
                        </button>
                    </div>
                    <div class="reset">
                        <button id="btn-reset"></button>
                    </div>
                </div>
                <table class="offer-list-table" cellSpacing="0">
                    <tr>
                        <th width="4%" align="left"></th>
                        <th align="left">Modtager</th>
                        <th align="left">Tilbudsnummer</th>
                        <th align="left">Dato for tilbudsgivning</th>
                        <th align="left">Se tilbud</th>
                        <th align="right">Påmind</th>
                        <th width="10%" align="right">Slet</th>
                    </tr>
					<?php
						global $wpdb;
						
						$table_name = $wpdb->prefix . 'localliving_plg_offer_list';
						
						$itemsPerPage = 20;
						$currentPage = isset($_POST['cpage']) ? abs((int)$_POST['cpage']) : 1;
						$offset = ($currentPage * $itemsPerPage) - $itemsPerPage;
						
						$query = "
                            SELECT *
                            FROM $table_name
                            WHERE offer_status != 'deleted'
                        ";
						
						if (isset($_POST['OfferStatus']) || isset($_POST['OfferSearchCombine'])
                            || isset($_POST['cpage'])) {
							$offerStatus = $_POST['OfferStatus'] ?? '';
							$offerSearchCombine = $_POST['OfferSearchCombine'] ?? '';
							
							if ($offerStatus != '' && $offerStatus != 'black' && $offerSearchCombine == '') {
								$query = "
                            SELECT *
                            FROM $table_name
                            WHERE offer_status = '$offerStatus'
                            AND offer_status != 'deleted'
                        ";
								$result = $wpdb->get_results($query . "LIMIT $offset, $itemsPerPage");
							}
							
							if ($offerSearchCombine != '' && ($offerStatus == '' || $offerStatus == 'black')) {
								$query = "
                            SELECT *
                            FROM $table_name
                            WHERE offer_receiver LIKE '%$offerSearchCombine%'
                            OR offer_receiver_email LIKE '%$offerSearchCombine%'
                            OR offer_name LIKE '%$offerSearchCombine%'
                            AND offer_status != 'deleted'
                        ";
								$result = $wpdb->get_results($query . "LIMIT $offset, $itemsPerPage");
							}
							
							if ($offerStatus != '' && $offerStatus != 'black' && $offerSearchCombine != '') {
								$query = "
                            SELECT *
                            FROM $table_name
                            WHERE offer_status = '$offerStatus'
                            AND (offer_receiver LIKE '%$offerSearchCombine%'
                            OR offer_receiver_email LIKE '%$offerSearchCombine%'
                            OR offer_name LIKE '%$offerSearchCombine%')
                            AND offer_status != 'deleted'
                        ";
							}
						}
						
                        $query .= " ORDER BY offer_id DESC";
                        
						$result = $wpdb->get_results($query . " LIMIT $offset, $itemsPerPage");
						
						if (count($result) > 0) {
							foreach ($result as $dataRow) {
								$offerStatus            = $dataRow->offer_status;
								$offerGenerateTimestamp = $dataRow->offer_generate_timestamp;
                                $offerId                = $dataRow->offer_id;
								
								echo '<tr>';
								renderTilbudResultRow($offerStatus, $offerId);
								echo '<td class="offer-receiver">' . $dataRow->offer_receiver . '</td>';
								echo '<td class="offer-name">' . $dataRow->offer_name . '</td>';
								echo '<td>' . date('d.m.Y', $offerGenerateTimestamp) . '</td>';
								echo '<td>';
								echo '<a href="' . $dataRow->offer_url . '" target="_blank">Se PDF</a>';
								echo '</td>';
								echo '<td align="right">';
								echo '<button class="send-offer" name="SendOffer" value="' . $dataRow->offer_id . '">Send påmindelse</button>';
								echo '</td>';
								echo '<td align="right">';
								echo '<button class="delete-offer" name="DeleteOffer" value="' . $dataRow->offer_id . '">Slet</button>';
								echo '</td>';
								echo '</tr>';
							}
       
							$totalResult = $wpdb->get_var("SELECT COUNT(1) FROM ($query) AS combined_table");
						}
					?>
                </table>
                <?php
	                if(isset($totalResult)) {
		                renderPagination($totalResult, $itemsPerPage, $currentPage);
                    }
                ?>
            </form>
        </div>
    </div>
</div>