<?php
//error_reporting(E_ALL);
//ini_set("display_errors", 1); 

$PDF_PAGE_FORMAT = 'A5';

// Include the main TCPDF library (search for installation path).
require_once(__DIR__.'/../lib/tcpdf/tcpdf_import.php');

// create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, $PDF_PAGE_FORMAT, true, 'UTF-8', false);

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Gestionale sagra');
$pdf->SetTitle('TCPDF Example 061');
$pdf->SetSubject('TCPDF Tutorial');
$pdf->SetKeywords('TCPDF, PDF, example, test, guide');

// set default header data
//$pdf->SetHeaderData($PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 061', PDF_HEADER_STRING);

// set header and footer fonts
//$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
//$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// set margins
//$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetMargins(PDF_MARGIN_LEFT, 2, PDF_MARGIN_RIGHT);
//$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// set some language-dependent strings (optional)
if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
    require_once(dirname(__FILE__).'/lang/eng.php');
    $pdf->setLanguageArray($l);
}

// ---------------------------------------------------------

// set font
$pdf->SetFont('helvetica', '', 10);

// add a page
$pdf->AddPage();

$filename = 'ricevuta-cliente.html';

// define some HTML content with style
$html = file_get_contents($filename);


/*********************************************************************/
/*********************************************************************/

function remove_div_with_id($id, $html, $tag = '') {
	//return preg_replace('/<'.$tag.' id="'.$id.'">(.*?)<\/div>/s', '', $html);
	list($pre, $post) = explode(' id="'.$id.'">', $html, 2);
	if ($post) {
		$tags = explode('<', $pre);
		$tag = array_pop($tags); // rimuovo il tag spezzato
		list($text, $more) = explode($tag.'>', $post, 2);
		return implode('<', $tags).$more;
	}
	return $pre;
}

function get_tag_content($tag, $id, $html) {
	list($pre, $post) = explode('<'.$tag.' id="'.$id.'">', $html, 2);
	if ($post) {
		list($text, $more) = explode('</'.$tag.'>', $post, 2);
		return trim($text);
	}
	return false;
}

// DUMMY DATA
$nome_evento = 'Sagra Paesana';
$nome_cassa = 'a';
$progressivo = 123;
$time = date('d-m-Y H:i');
$id_testata = 345;
$tavolo = '23';
$cliente = 'Francesco';
$tipo = '';
$coperti = 4;
$prodotti = array(
	'0' => array(
		'nome' => 'Patatine fritte',
		'prezzo' => '2,50',
		'quantita' => 2,
	),
	'1' => array(
		'nome' => 'Grigliata mista',
		'prezzo' => '6,50',
		'quantita' => 1,
	),
	'3' => array(
		'nome' => 'Birra bionda media',
		'prezzo' => '4,50',
		'quantita' => 3,
	)
);
$testata = array(
	'totale' => 22.50,
	'pagato' => 50.00,
	'resto' => 27.50,
);
$testo_footer = '* In mancanza di prodotto fresco sarà utilizzato prodotto surgelato.<br />
Il Comitato organizzativo ringrazia quanti hanno partecipato alla manifestazione ed augura buon appetito.';


$html = str_replace('{{nome-evento}}', $nome_evento, $html);
$html = str_replace('{{id-testata}}', $id_testata, $html);
$html = str_replace('{{nome-cassa}}', $nome_cassa, $html);
$html = str_replace('{{data-ora}}', $time, $html);
$html = str_replace('{{progressivo}}', $progressivo, $html);
$html = str_replace('{{tavolo}}', $tavolo, $html);
$html = str_replace('{{cliente}}', $cliente, $html);
$html = str_replace('{{tipo}}', $tipo, $html);
$html = str_replace('{{coperti}}', $coperti, $html);

if (!$tavolo) {
	$html = remove_div_with_id('tavolo', $html);
}
if (!$cliente) {
	$html = remove_div_with_id('cliente', $html);
}

$sample_prodotto = get_tag_content('table', 'dettaglio', $html);
$sample_prodotto = remove_div_with_id('dettaglio-header', $sample_prodotto);
//var_dump($sample_prodotto);
$html_prodotti = '';
$totale = 0;
foreach($prodotti as $aprodotto) {
	$html_prodotto = $sample_prodotto;
	
	$html_prodotto = str_replace('{{nome-prodotto}}', $aprodotto['nome'], $html_prodotto);
	$html_prodotto = str_replace('{{quantita-prodotto}}', $aprodotto['quantita'], $html_prodotto);
	$html_prodotto = str_replace('{{prezzo-prodotto}}', $aprodotto['prezzo'], $html_prodotto);
	
	$subtotale = $aprodotto['quantita'] * $aprodotto['prezzo'];
	$totale += $subtotale;
	$html_prodotto = str_replace('{{subtotale-prodotto}}', number_format($subtotale, 2), $html_prodotto);
	
	$html_prodotti .= $html_prodotto;
}
$html = str_replace($sample_prodotto, $html_prodotti, $html);


$html = str_replace('{{totale}}', number_format($totale, 2), $html);
$romana = $totale / $coperti;
$html = str_replace('{{romana}}', number_format($romana, 2), $html);
if (!$coperti || !$totale) {
	$html = remove_div_with_id('romana', $html);
}
$html = str_replace('{{pagato}}', number_format($testata['pagato'], 2), $html);
$html = str_replace('{{resto}}', number_format($testata['resto'], 2), $html);
if (!$totale) {
	$html = remove_div_with_id('pagato', $html);
	$html = remove_div_with_id('resto', $html);
}

$html = str_replace('{{testo-footer}}', $testo_footer, $html);

/*********************************************************************/
/*********************************************************************/

// output the HTML content
$pdf->writeHTML($html, true, false, true, false, '');

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

// add a page
//$pdf->AddPage();
$html = '';// output the HTML content
$pdf->writeHTML($html, true, false, true, false, '');

// reset pointer to the last page
$pdf->lastPage();

// ---------------------------------------------------------

//Close and output PDF document
$pdf->Output('test.pdf', 'I');

//============================================================+
// END OF FILE
//============================================================+
