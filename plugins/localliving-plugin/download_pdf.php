<?php
	$pdf_name = $_GET['pdf_name'] ?? '';
	
	if($pdf_name != '') {
		$pdf_server_path = PDF_LOGS  . date('Y') . '/' . $pdf_name . '.pdf';
		
		if(file_exists($pdf_server_path)) {
			header("Content-Description: File Transfer");
			header("Content-Type: application/pdf");
			header("Content-Disposition: attachment; filename=".basename($pdf_server_path));
			header("Content-Transfer-Encoding: binary");
			header("Expires: 0");
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
			header("Content-Length: " .filesize($pdf_server_path));
			ob_clean();
			flush();
			readfile($pdf_server_path);
			exit;
		}
	}
