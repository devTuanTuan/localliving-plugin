<?php
	add_action('admin_footer', function () {
        echo '
            <script>
            jQuery(document).ready(function ($) {
                $(document).on("click", ".edit-property-mapping", function() {
                     $("button[name=EditMappingRecord]").prop("disabled", true);
                     
                    var recordId = $(this).data("record-id");
                    $.ajax({
                        type: "POST",
                        url: "'. admin_url("admin-ajax.php") .'",
                        data: {
                        action: "get_record_property_mapping",
                        recordId: recordId
                    },
                    context: this,
                    success: function($response) {
                        propertyMapping = JSON.parse($response);
                        
                        if(propertyMapping) {
                            $("input[name=EditTerretrucheID]").val(propertyMapping.property_txt_file_id);
                            $("input[name=EditTerretruchePropertyName]").val(propertyMapping.property_name);
                            $("input[name=EditUniqueRef]").val(propertyMapping.unit_unique_ref);
                            $("input[name=EditRecordId]").val(propertyMapping.id);
                            $("button[name=EditMappingRecord]").prop("disabled", false);
                        }
                    }});
                });
            });
            </script>
        ';
    });
    function getPropertyMapping()
    {
	    global $wpdb;
	
	    $table_name_terretrusche_property_map = $wpdb->prefix . 'localliving_plg_terretrusche_property_map';
	
	    $query = "SELECT *
        FROM $table_name_terretrusche_property_map";
	
	    return $wpdb->get_results($query);
    }
	
	function addPropertyMapping($terretruscheId, $propertyName, $uniqueRef) {
		global $wpdb;
		
		$table_name_terretrusche_property_map = $wpdb->prefix . 'localliving_plg_terretrusche_property_map';
		
		$data = array(
            'property_txt_file_id' => $terretruscheId,
            'property_name'        => $propertyName,
            'unit_unique_ref'      => $uniqueRef
        );
		
		$wpdb->insert($table_name_terretrusche_property_map, $data);
	}
    
    function updatePropertyMapping($recordId, $terretruscheId, $propertyName, $uniqueRef) {
	    global $wpdb;
	
	    $table_name_terretrusche_property_map = $wpdb->prefix . 'localliving_plg_terretrusche_property_map';
	
	    $data = array(
		    'property_txt_file_id' => $terretruscheId,
		    'property_name'        => $propertyName,
		    'unit_unique_ref'      => $uniqueRef
	    );
        
        $where = array(
            'id' => $recordId
        );
	
	    $wpdb->update($table_name_terretrusche_property_map, $data, $where);
    }
    
    if($_POST['SaveNewMappingRecord'] == 'SaveNewMappingRecord') {
	    $terretruscheId = $_POST['TerretrucheID'];
        $propertyName   = $_POST['TerretruchePropertyName'];
        $uniqueRef      = $_POST['UniqueRef'];
	
	    addPropertyMapping($terretruscheId, $propertyName, $uniqueRef);
    }
    
    if($_POST['EditMappingRecord'] == 'EditMappingRecord') {
        $recordId       = $_POST['EditRecordId'];
	    $terretruscheId = $_POST['EditTerretrucheID'];
	    $propertyName   = $_POST['EditTerretruchePropertyName'];
	    $uniqueRef      = $_POST['EditUniqueRef'];
        
        updatePropertyMapping($recordId, $terretruscheId, $propertyName, $uniqueRef);
    }
?>

<div class="loading-first-wrapper">
    <div class="loading-first">
        <div class="loader"></div>
    </div>
</div>
<div class="localliving-terretrusche-property-mapping">
    <div class="header sticky top-menu">
        <div class="page-title top-menu-left text-nowrap">
            <h1>Terretrusche Property Mapping</h1>
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
    <div>
        <div class="add-new-mapping mb-2">
            <button id="add-new-mapping-btn" type="button"
                    class="btn btn-primary w-auto px-5 py-3"
                    data-bs-toggle="modal"
                    data-bs-target="#add-new-mapping-modal"
            >
                Add new
            </button>
        </div>
        <table class="terretrusche_property_mapping_table">
            <tr>
                <th>No.</th>
                <th>Terretrusche ID</th>
                <th>Property Name</th>
                <th>Unique REF</th>
                <th>Action</th>
            </tr>
			<?php
				$propertyMappingList = getPropertyMapping();
				
				foreach ($propertyMappingList as $index => $propertyMapping) {
					echo '<tr><form>';
					echo '<td>'.($index + 1).'</td>';
					echo '<td>'.$propertyMapping->property_txt_file_id.'</td>';
					echo '<td>'.$propertyMapping->property_name.'</td>';
					echo '<td>'.$propertyMapping->unit_unique_ref.'</td>';
					echo '<td>';
                    echo '<a
                    class="edit-property-mapping"
                    data-record-id="'.$propertyMapping->id.'"
                    data-bs-toggle="modal"
                    data-bs-target="#edit-mapping-modal"
                    href="#">Edit</a>';
                    echo'</td>';
					echo '</form></tr>';
				}
			?>
        </table>
    </div>
    <div id="add-new-mapping-modal" class="modal fade" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body">
                    <form name="add-new-mapping-form" method="POST">
                        <div class="w-75 mx-auto my-4">
                            <h2 class="add-new-mapping-title">Add new a mapping record</h2>
                            <label>
                                Terretrusche ID:
                                <input type="text" name="TerretrucheID" required/>
                            </label>
                            <label>
                                Terretrusche Property Name:
                                <input type="text" name="TerretruchePropertyName"/>
                            </label>
                            <label>
                                Unique REF:
                                <input type="text" name="UniqueRef" required/>
                            </label>
                            <button class="btn btn-primary py-3 px-5 mt-3" type="submit" name="SaveNewMappingRecord" value="SaveNewMappingRecord">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div id="edit-mapping-modal" class="modal fade" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body">
                    <form name="edit-mapping-form" method="POST">
                        <div class="w-75 mx-auto my-4">
                            <h2 class="edit-mapping-title">Edit a mapping record</h2>
                            <label>
                                Terretrusche ID:
                                <input type="text" name="EditTerretrucheID" required/>
                            </label>
                            <label>
                                Terretrusche Property Name:
                                <input type="text" name="EditTerretruchePropertyName"/>
                            </label>
                            <label>
                                Unique REF:
                                <input type="text" name="EditUniqueRef" required/>
                            </label>
                            <input type="hidden" name="EditRecordId" value="" required/>
                            <button class="btn btn-primary py-3 px-5 mt-3" type="submit" name="EditMappingRecord" value="EditMappingRecord" disabled>Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>