<?php
defined('BASEPATH') OR exit('No direct script access allowed');

//require_once(dirname(__FILE__) . '/dompdf/autoload.inc.php');
require_once __DIR__ . '/dompdf/autoload.inc.php';


class Pdf
{
	function createPDF($html, $filename='', $download=TRUE, $paper='A4', $orientation='portrait'){
<<<<<<< HEAD
		// Old dompdf triggers E_DEPRECATED on PHP 8.2+; any output breaks PDF streaming.
		$previousLevel = error_reporting();
		error_reporting($previousLevel & ~E_DEPRECATED & ~E_USER_DEPRECATED);

		try {
			if (ob_get_level()) {
				ob_end_clean();
			}

			$dompdf = new Dompdf\Dompdf();
			$dompdf->load_html($html);
			$dompdf->set_paper($paper, $orientation);
			$dompdf->render();
			$streamOptions = array('Attachment' => (bool) $download);
			$dompdf->stream($filename . '.pdf', $streamOptions);
		} finally {
			error_reporting($previousLevel);
		}
=======
		$dompdf = new Dompdf\Dompdf();
		$dompdf->load_html($html);
		$dompdf->set_paper($paper, $orientation);
		$dompdf->render();
		if($download)
			$dompdf->stream($filename.'.pdf', array('Attachment' => true,'isRemoteEnabled' => false,));
		else
			$dompdf->stream($filename.'.pdf', array('Attachment' => true,'isRemoteEnabled' => true));
>>>>>>> 808554ff5caea0db9a21de0721b02d4d60db333d
	}
}
?>
