<?php
defined('BASEPATH') OR exit('No direct script access allowed');

//require_once(dirname(__FILE__) . '/dompdf/autoload.inc.php');
require_once __DIR__ . '/dompdf/autoload.inc.php';


class Pdf
{
	function createPDF($html, $filename='', $download=TRUE, $paper='A4', $orientation='portrait'){
		$dompdf = new Dompdf\Dompdf();
		$dompdf->load_html($html);
		$dompdf->set_paper($paper, $orientation);
		$dompdf->render();
		if($download)
			$dompdf->stream($filename.'.pdf', array('Attachment' => true,'isRemoteEnabled' => false,));
		else
			$dompdf->stream($filename.'.pdf', array('Attachment' => true,'isRemoteEnabled' => true));
	}
}
?>
