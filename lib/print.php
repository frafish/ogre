<?php

function remove_div_with_id($id, $html, $tag = '') {
	//return preg_replace('/<'.$tag.' id="'.$id.'">(.*?)<\/div>/s', '', $html);
	$pezzi = explode(' id="'.$id.'"', $html, 2);
	if (count($pezzi) == 2) {
		$pre = $pezzi[0];
		$post = $pezzi[1];
		$tags = explode('<', $pre);
		$tag = array_pop($tags); // rimuovo il tag spezzato
		list($text, $more) = explode($tag.'>', $post, 2);
		return implode('<', $tags).$more;
	}
	return $html;
}

function get_tag_content($tag, $id, $html, $prop = 'id') {
	$pezzi = explode('<'.$tag.' '.$prop.'="'.$id.'">', $html, 2);
	if (count($pezzi) == 2) {
		$pre = $pezzi[0];
		$post = $pezzi[1];
		list($text, $more) = explode('</'.$tag.'>', $post, 2);
		return trim($text);
	}
	return false;
}

function crea_pdf($id_testata, $output_target = '', $formato_carta = null, $id_reparto = null, $fogli = array(), $force_print = false, $pdf_lib = 'tcpdf') {
	//echo $output_target;
	if (!$id_testata) {
		return false;
	}
	
	// RECUPERO TUTTI I DATI DELLA RICEVUTA
	$testata = find_one_by('testate', $id_testata);
	$cassa = find_one_by('casse', $testata['id_casse']);
	
	$where_reparto = ($id_reparto > 0) ? 'id_reparti = '.$id_reparto : null;
	$prodotti = get_prodotti_plus($id_testata, $where_reparto);
	//$id_utente = $testata['id_utente'];
	
	if (!$formato_carta) {
		$id_printer = get_printer();
		if ($id_printer !== false) {
			$printer = get_printer($id_printer);
			$formato_carta = $printer['formato'];		
		} else {
			$formato_carta = 'A5';
		}
	}
	
	if (!is_array($fogli) || empty($fogli)) {
		$reparti = get_reparti(null, true);
		//var_dump($reparti);
		$fogli = array();

		if ($id_reparto == null) {
			// se ho il percorso della destinazione me lo ricavo dalla cartella
			if ($output_target) {
				$pezzi = explode(DIRECTORY_SEPARATOR, $output_target);
				$tipo_ricevuta = $pezzi[1];
				//var_dump($tipo_ricevuta);
				//$fogli[] = $tipo_ricevuta;
				foreach($reparti as $idr => $areparto) {
					if ($areparto['nome'] == $tipo_ricevuta) {
						$id_reparto = $idr;
					}
				}
			}		
		}
		//var_dump($id_reparto);
		//die();
		
		// se ho l'id del reparto stampo il documento collegato
		//if ($id_reparto != null) {			
		if ($id_reparto == 0) {
			$fogli[] = CARTELLA_RICEVUTA_CLIENTE;
		} elseif ($id_reparto > 0) {
			$reparto = $reparti[$id_reparto]; //find_one_by('reparti', $id_reparto);
			$tipo_ricevuta = $reparto['nome'];
			$fogli[] = $tipo_ricevuta;
		} else {
			switch($id_reparto) {
				case -1: // CASSA
					$fogli[] = CARTELLA_RICEVUTA_CLIENTE;
					$preparti = get_reparti($id_testata);
					if(!empty($preparti)) {
						foreach($preparti as $preparto) {
							if ($preparto['ricevuta'] && !$preparto['id_stampanti']) {
								$fogli[] = $preparto['nome'];
							}
						}
					}
					break;
				case -2:
				default: // ALL
					$fogli[] = CARTELLA_RICEVUTA_CLIENTE;
					$preparti = get_reparti($id_testata);
					if(!empty($preparti)) {
						foreach($preparti as $preparto) {
							$fogli[] = $preparto['nome'];	
						}
					}
			}					
		}
		//var_dump($fogli);
		//die();
		
		/*
		// altrimenti li stampo tutti
		if (empty($fogli)) {
			$fogli[] = CARTELLA_RICEVUTA_CLIENTE;
			$preparti = get_reparti($id_testata);
			if(!empty($preparti)) {
				foreach($preparti as $preparto) {
					$fogli[] = $preparto['nome'];	
				}
			}
		}
		*/
		
	}
	//var_dump($fogli);
	
	$tipo = get_tipo($testata['asporto']);//, $testata['omaggi']);
	$resto = $testata['pagato'] - $testata['totale'];	
	$nome_evento = get_option('nome-evento');
	$testo_footer = get_option('testo-footer-ricevuta');
	$titolo = $nome_evento.' '.$id_testata;
	//$coperti = ($testata['omaggi']) ? $testata['coperti'] . '(+'.$testata['omaggi'].') = '.($testata['coperti']+$testata['omaggi']) : $testata['coperti'];
	$coperti = $testata['coperti']+$testata['omaggi'];
	
	switch($pdf_lib) {
		case 'html2pdf':
			require_once(__DIR__.DIRECTORY_SEPARATOR.'html2pdf-4.4.0'.DIRECTORY_SEPARATOR.'html2pdf.class.php');
			$pdf = new HTML2PDF('P', $formato_carta, 'it');	
			$pdf->pdf->SetAuthor('Gestionale sagra');
			$pdf->pdf->SetTitle($titolo);
			//$pdf->pdf->SetSubject('Ricevuta '.$tipo_ricevuta);
			$pdf->pdf->SetDisplayMode('fullpage');
			break;
		case 'tcpdf':
			// Include the main TCPDF library (search for installation path).
			require_once(__DIR__.DIRECTORY_SEPARATOR.'tcpdf'.DIRECTORY_SEPARATOR.'tcpdf.php');
			$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, $formato_carta, true, 'UTF-8', false); // 
			$pdf->SetCreator(PDF_CREATOR);
			$pdf->SetAuthor('Gestionale sagra');
			$pdf->SetTitle($titolo);
			//$pdf->SetSubject('Ricevuta '.$tipo_ricevuta);
			break;		
	}	
	
	// create new PDF document
		
	// set document information
	
	//$pdf->SetKeywords('TCPDF, PDF, example, test, guide');
	
	// set default header data
	//$pdf->SetHeaderData($PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 061', PDF_HEADER_STRING);
	
	// set header and footer fonts
	//$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
	//$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
	
	// set default monospaced font
	//$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
	
	// set margins
	//$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
	//$pdf->SetMargins(PDF_MARGIN_LEFT, 10, PDF_MARGIN_RIGHT);
	//$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
	//$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
	$pdf->SetMargins(5, 1, 5, true);
	$pdf->SetAutoPageBreak(TRUE, 0);
	
	// set auto page breaks
	//$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
	$pdf->setCellPaddings(0,0,0,0);
	
	// set image scale factor
	//$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

	// set font
	//$pdf->SetFont('helvetica', '', 10);
	
	foreach($fogli as $key => $tipo_ricevuta) {

		// add a page
		//$pdf->AddPage();
		$filtro_reparto = false;
		if($id_reparto > 0) {			
			$filtro_reparto = $id_reparto;
		} else {
			$preparto = find_one_by('reparti', "nome LIKE '".$tipo_ricevuta."'");
			if ($preparto) {
				$filtro_reparto = $preparto['id'];
			}
		}
		
		$filename = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'template'.DIRECTORY_SEPARATOR.'ricevuta-'.$tipo_ricevuta.'.html';
		if (!file_exists($filename)) {
			$filename = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'template'.DIRECTORY_SEPARATOR.'ricevuta-reparto.html';
		}
		//dev_log('PDF foglio '.$key.' il template: '.$filename);
		
		// define some HTML content with style
		$html = file_get_contents($filename);
		

		$html = str_replace('<html>', '', $html);
		$html = str_replace('</html>', '', $html);
		$html = str_replace('<head>', '', $html);
		$html = str_replace('</head>', '', $html);
		switch($pdf_lib) {
			case 'html2pdf':
				$html = str_replace('<body>', '<page format="'.$formato_carta.'">', $html);
				$html = str_replace('</body>', '</page>', $html);			
				break;
			case 'tcpdf':
				$html = str_replace('<body>', '', $html);
				$html = str_replace('</body>', '', $html);
				$pdf->SetPrintHeader(false);
				$pdf->SetPrintFooter(false);
				$pdf->AddPage('P', $formato_carta);
				break;		
		}	
		
		$html = str_replace('{{tipo-ricevuta}}', $tipo_ricevuta, $html);
		$html = str_replace('{{nome-evento}}', $nome_evento, $html);
		$html = str_replace('{{id-testata}}', $id_testata, $html);
		$html = str_replace('{{nome-cassa}}', $cassa['nome'], $html);
		$html = str_replace('{{data-ora}}', date("d/m/Y H:i", strtotime($testata['time'])), $html);
		$html = str_replace('{{data}}', date("d/m/Y", strtotime($testata['time'])), $html);
		$html = str_replace('{{ora}}', date("H:i", strtotime($testata['time'])), $html);
		$html = str_replace('{{progressivo}}', $testata['progressivo'], $html);
		$html = str_replace('{{tavolo}}', $testata['tavolo'], $html);
		$html = str_replace('{{cliente}}', $testata['cliente'], $html);
		$html = str_replace('{{tipo}}', $tipo, $html);
		$html = str_replace('{{coperti}}', $coperti, $html);
		
		if (!$testata['tavolo']) {
			$html = remove_div_with_id('tavolo', $html);
		}
		if (!$testata['cliente']) {
			$html = remove_div_with_id('cliente', $html);
		}
		
		//$sample_prodotto = get_tag_content('table', 'dettaglio', $html);
		//$sample_prodotto = remove_div_with_id('dettaglio-header', $sample_prodotto);
		$sample_prodotto = '<tr class="prodotto">'.get_tag_content('tr', 'prodotto', $html, 'class').'</tr>';
		//var_dump($sample_prodotto);
		$html_prodotti = '';
		$totale = 0;
		//var_dump($prodotti);
		foreach($prodotti as $aprodotto) {
			// se Ã¨ il foglio di unp specifico reparto filtro i prodotti
			if (!$filtro_reparto || $aprodotto['id_reparti'] == $filtro_reparto) {		
				$html_prodotto = $sample_prodotto;
				
				$nome_prodotto_corto = ($aprodotto['corto']) ? $aprodotto['corto'] : $aprodotto['nome'];
				$html_prodotto = str_replace('{{nome-prodotto-corto}}', $nome_prodotto_corto, $html_prodotto);
				$html_prodotto = str_replace('{{nome-prodotto}}', $aprodotto['nome'], $html_prodotto);
				if ($filtro_reparto) {
					$quantita = ($aprodotto['omaggio']) ? $aprodotto['quantita']+$aprodotto['omaggio'] : $aprodotto['quantita'];
				} else {
					$quantita = ($aprodotto['omaggio']) ? $aprodotto['quantita'].'(+'.$aprodotto['omaggio'].')' : $aprodotto['quantita'];
				}
				$html_prodotto = str_replace('{{quantita-prodotto}}', $quantita, $html_prodotto);
				$html_prodotto = str_replace('{{prezzo-prodotto}}', number_format($aprodotto['prezzo'], 2), $html_prodotto);
				
				$subtotale = $aprodotto['quantita'] * $aprodotto['prezzo'];
				$totale += $subtotale;
				$html_prodotto = str_replace('{{subtotale-prodotto}}', number_format($subtotale, 2), $html_prodotto);
				
				$nota_prodotto = '';
				if($aprodotto['nota']) {
					$nota_prodotto = '<br/><strong>NOTA:</strong> '.$aprodotto['nota'];				
				}
				$html_prodotto = str_replace('{{nota-prodotto}}', $nota_prodotto, $html_prodotto);
				
				$html_prodotti .= $html_prodotto;
			}
		}
		$html = str_replace($sample_prodotto, $html_prodotti, $html);
		
		$totale = $testata['totale'];
		$html = str_replace('{{totale}}', number_format($totale, 2), $html);
		if ($testata['coperti']) {
			$romana = $totale / $testata['coperti'];
			$html = str_replace('{{romana}}', number_format($romana, 2), $html);
		}
		if (!$testata['coperti'] || !$totale) {
			$html = remove_div_with_id('romana', $html);
		}
		$html = str_replace('{{pagato}}', number_format($testata['pagato'], 2), $html);
		$html = str_replace('{{resto}}', number_format($resto, 2), $html);
		if (!$totale) {
			$html = remove_div_with_id('pagato', $html);
			$html = remove_div_with_id('resto', $html);
		}
		
		$note_testata = '';
		if($testata['note']) {
			$note_testata =  '<strong>NOTE:</strong> '.$testata['note'];
			$html = str_replace('{{note-testata}}', $note_testata, $html);
		} else {
			$html = remove_div_with_id('note', $html);
		}		
		
		$html = str_replace('{{testo-footer}}', $testo_footer, $html);
		
		// normalizzo il testo con i vari caratteri strani
		//$html = normalize_string($html);
		$html = iconv('UTF-8', 'windows-1252', $html);
		$html = mb_convert_encoding($html, 'UTF-8', 'ISO-8859-1');
		
		
		switch($pdf_lib) {
			case 'html2pdf':
			case 'tcpdf':
				// output the HTML content
				$pdf->writeHTML($html);
				break;		
		}
		//var_dump($html);
		
		// EAN 13
		$style = array(
		    'position' => '',
		    'align' => 'C',
		    'stretch' => false,
		    'fitwidth' => true,
		    'cellfitalign' => '',
		    'border' => false,
		    'hpadding' => 0,
		    'vpadding' => 0,
		    'fgcolor' => array(0,0,0),
		    'bgcolor' => false, //array(255,255,255),
		    'text' => false
		);
		
		//str_pad($id_testata, 13, '0', STR_PAD_LEFT)
		if ($filtro_reparto) {
			//$pdf->write1DBarcode($id_testata, 'EAN13', '', '', '', 10, 0.5, $style, 'N');
		}

	}	
	
	if ($force_print) {
		//if (!get_printer()) {
			// JS functions
			$js = "print();";
			// Add Javascript code
			$pdf->IncludeJS($js);
		//}	
	}
	
	// ---------------------------------------------------------
	
	if ($output_target) {
		$pathname = dirname($output_target);
		$filename = basename($output_target);
		if (!is_dir(__DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.$pathname)) {
			mkdir(__DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.$pathname, 0777, true);
			@chmod(__DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.$pathname, 0777);
		}
		$pdf_out = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.$output_target;
		$pdf_do = 'F';		
	} else {
		//Close and output PDF document
		$pdf_out = strtotime($testata['time']).'-'.$id_testata.'-'.$testata['progressivo'].'.pdf';
		$pdf_do = 'I';
	}
	
	switch($pdf_lib) {
		case 'html2pdf':
		case 'tcpdf':
			// output the HTML content
			$pdf->Output($pdf_out, $pdf_do);
			if ($pdf_do == 'F') {
				@chmod($pdf_out, 0666);
			}
			break;		
	}
	
	//============================================================+
	// END OF FILE
	//============================================================+

}

function html_to_pdf($html, $output, $formato_carta = 'A5', $titolo = 'Ricevuta', $pdf_lib = 'tcpdf') {
	// INIT
	switch($pdf_lib) {
		case 'html2pdf':
			require_once(__DIR__.DIRECTORY_SEPARATOR.'html2pdf-4.4.0'.DIRECTORY_SEPARATOR.'html2pdf.class.php');
			$pdf = new HTML2PDF('P', $formato_carta, 'it');	
			$pdf->pdf->SetAuthor('Gestionale sagra');
			$pdf->pdf->SetTitle($titolo);
			//$pdf->pdf->SetSubject('Ricevuta '.$tipo_ricevuta);
			$pdf->pdf->SetDisplayMode('fullpage');
			break;
		case 'tcpdf':
			// Include the main TCPDF library (search for installation path).
			require_once(__DIR__.DIRECTORY_SEPARATOR.'tcpdf'.DIRECTORY_SEPARATOR.'tcpdf_import.php');
			$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, $formato_carta, true, 'UTF-8', false);
			$pdf->SetCreator(PDF_CREATOR);
			$pdf->SetAuthor('Gestionale sagra');
			$pdf->SetTitle($titolo);
			//$pdf->SetSubject('Ricevuta '.$tipo_ricevuta);
			$pdf->SetPrintHeader(false);
			$pdf->SetPrintFooter(false);
			$pdf->AddPage('P', $formato_carta);
			break;		
	}
	// ADD HTML
	switch($pdf_lib) {
		case 'html2pdf':
		case 'tcpdf':
			// output the HTML content
			$pdf->writeHTML($html);
			break;		
	}
	// SAVE
	switch($pdf_lib) {
		case 'html2pdf':
		case 'tcpdf':
			// output the HTML content
			$pdf->Output($output, 'F');
			@chmod($output, 0666);
			break;		
	}
}

function cmd_print($pdf, $id_printer = null, $ssh = null, $formato = null) {
	
	if (!$id_printer) {
		$id_printer = get_printer();	
	}
	
	$printer = get_printers(true, $id_printer);
	if (!$formato) {
		$formato = $printer['formato'];		
	}
	$print_to = ''; // specifico a quale stampante destinare la stampa
		
	$pezzi = explode(DIRECTORY_SEPARATOR, __DIR__);
	array_pop($pezzi);
	$path = implode(DIRECTORY_SEPARATOR, $pezzi);
	$full_path_pdf = $path.DIRECTORY_SEPARATOR.$pdf;

	if(is_linux()) {
		if ($id_printer > 0) {
			$print_to = " -P ".escapeshellarg($printer['nome']);
		}
		$command = "lpr -o media=".escapeshellarg($formato).$print_to." ".escapeshellarg($full_path_pdf);
	}		
	if(is_windows()) {
		// https://github.com/sumatrapdfreader/sumatrapdf/wiki/Command-line-arguments
		if ($id_printer > 0) {
			$print_to = ' -print-to '.escapeshellarg($printer['nome']);
		}
		$command = escapeshellarg(__DIR__.DIRECTORY_SEPARATOR.'SumatraPDF.exe').$print_to.' -silent -exit-when-done '.escapeshellarg($full_path_pdf);
	}	 
	//echo $command;
	//dev_log('PRINT: '.$command);
	
	if ($ssh) {
		//$stream = ssh2_exec($connection, $command);
		$ret = $ssh->exec($command);
	} else {
		exec($command, $output, $ret);
		//shell_exec($command);//, $output, $return_var);
	}
	
	return $ret;
}

function get_printer($id_printer = NULL) {
	if ($id_printer != NULL) {
		return get_printers(true, $id_printer);	
	}
	if (isset($_SESSION['id_stampante'])) {
		return $_SESSION['id_stampante'];
	}
	if (isset($_SESSION['id_cassa'])) {
		$cassa = find('casse', $_SESSION['id_cassa']);	
		return $cassa['id_stampanti'];
	}
	return false;
}
function get_printers($all = false, $id_printer = NULL) {
	$where = null;
	if (!$all) {
		$where = 'status = 1';
	}
	$stampanti = find_by("stampanti", $where);
	$stampanti[0] = array('id' => 0, 'nome' => 'DEFAULT', 'ip' => $_SERVER['SERVER_ADDR'], 'status' => 1, 'formato' => 'A5');
	$stampanti[-1] = array('id' => -1, 'nome' => 'locale', 'ip' => 'USB', 'status' => 1, 'formato' => 'A5');
	if($id_printer != NULL) {
		return $stampanti[$id_printer];
	}
	ksort($stampanti);
	return $stampanti;
}

function get_system_printers() {
	$printers = array();	
	if(is_linux()) {	
		/*exec('lpstat -p',$output,$retval);
		foreach($output as $ariga) {
			if($ariga != "") {
				$pezzi = explode(" ", $ariga, 3);
				if (count($pezzi) == 3) {
					list($printer, $pname, $pstate) = $pezzi; 
					$printers[] = $pname;
				}
			}
		}*/
		exec("lpstat -a | cut -f1 -d ' '",$printers,$retval);
	}
	if(is_windows()) {	
		exec('wmic printer get name',$output,$retval);
		foreach($output as $akey => $ariga) {
                    if ($akey) {
                        $ariga = trim($ariga);
                        if ($ariga) {
                            $printers[] = $ariga;
                        }
                    }
		}
	}
	return $printers;
}


function is_linux() {
	return (strtoupper(PHP_OS) === 'LINUX') ? true : false;
}
function is_windows() {
	return (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? true : false;
}


function normalize_string($word) {

    $word = str_replace("@","%40",$word);
    $word = str_replace("`","%60",$word);
    $word = str_replace("Â¢","%A2",$word);
    $word = str_replace("Â£","%A3",$word);
    $word = str_replace("Â¥","%A5",$word);
    $word = str_replace("|","%A6",$word);
    $word = str_replace("Â«","%AB",$word);
    $word = str_replace("Â¬","%AC",$word);
    $word = str_replace("Â¯","%AD",$word);
    $word = str_replace("Âº","%B0",$word);
    $word = str_replace("Â±","%B1",$word);
    $word = str_replace("Âª","%B2",$word);
    $word = str_replace("Âµ","%B5",$word);
    $word = str_replace("Â»","%BB",$word);
    $word = str_replace("Â¼","%BC",$word);
    $word = str_replace("Â½","%BD",$word);
    $word = str_replace("Â¿","%BF",$word);
    $word = str_replace("Ã","%C0",$word);
    $word = str_replace("Ã","%C1",$word);
    $word = str_replace("Ã","%C2",$word);
    $word = str_replace("Ã","%C3",$word);
    $word = str_replace("Ã","%C4",$word);
    $word = str_replace("Ã","%C5",$word);
    $word = str_replace("Ã","%C6",$word);
    $word = str_replace("Ã","%C7",$word);
    $word = str_replace("Ã","%C8",$word);
    $word = str_replace("Ã","%C9",$word);
    $word = str_replace("Ã","%CA",$word);
    $word = str_replace("Ã","%CB",$word);
    $word = str_replace("Ã","%CC",$word);
    $word = str_replace("Ã","%CD",$word);
    $word = str_replace("Ã","%CE",$word);
    $word = str_replace("Ã","%CF",$word);
    $word = str_replace("Ã","%D0",$word);
    $word = str_replace("Ã","%D1",$word);
    $word = str_replace("Ã","%D2",$word);
    $word = str_replace("Ã","%D3",$word);
    $word = str_replace("Ã","%D4",$word);
    $word = str_replace("Ã","%D5",$word);
    $word = str_replace("Ã","%D6",$word);
    $word = str_replace("Ã","%D8",$word);
    $word = str_replace("Ã","%D9",$word);
    $word = str_replace("Ã","%DA",$word);
    $word = str_replace("Ã","%DB",$word);
    $word = str_replace("Ã","%DC",$word);
    $word = str_replace("Ã","%DD",$word);
    $word = str_replace("Ã","%DE",$word);
    $word = str_replace("Ã","%DF",$word);
    $word = str_replace("Ã ","%E0",$word);
    $word = str_replace("Ã¡","%E1",$word);
    $word = str_replace("Ã¢","%E2",$word);
    $word = str_replace("Ã£","%E3",$word);
    $word = str_replace("Ã¤","%E4",$word);
    $word = str_replace("Ã¥","%E5",$word);
    $word = str_replace("Ã¦","%E6",$word);
    $word = str_replace("Ã§","%E7",$word);
    $word = str_replace("Ã¨","%E8",$word);
    $word = str_replace("Ã©","%E9",$word);
    $word = str_replace("Ãª","%EA",$word);
    $word = str_replace("Ã«","%EB",$word);
    $word = str_replace("Ã¬","%EC",$word);
    $word = str_replace("Ã­","%ED",$word);
    $word = str_replace("Ã®","%EE",$word);
    $word = str_replace("Ã¯","%EF",$word);
    $word = str_replace("Ã°","%F0",$word);
    $word = str_replace("Ã±","%F1",$word);
    $word = str_replace("Ã²","%F2",$word);
    $word = str_replace("Ã³","%F3",$word);
    $word = str_replace("Ã´","%F4",$word);
    $word = str_replace("Ãµ","%F5",$word);
    $word = str_replace("Ã¶","%F6",$word);
    $word = str_replace("Ã·","%F7",$word);
    $word = str_replace("Ã¸","%F8",$word);
    $word = str_replace("Ã¹","%F9",$word);
    $word = str_replace("Ãº","%FA",$word);
    $word = str_replace("Ã»","%FB",$word);
    $word = str_replace("Ã¼","%FC",$word);
    $word = str_replace("Ã½","%FD",$word);
    $word = str_replace("Ã¾","%FE",$word);
    $word = str_replace("Ã¿","%FF",$word);
    return $word;
}

function get_dummy_data() {
	// DUMMY DATA
		$nome_evento = 'Sagra Paesana';
		$cassa = array('nome' => 'a');
		$tipo = '';
		$prodotti = array(
			'0' => array(
				'nome' => 'Patatine fritte',
				'prezzo' => 2.50,
				'quantita' => 2,
				'omaggio' => 1,
			),
			'1' => array(
				'nome' => 'Grigliata mista',
				'prezzo' => 6.50,
				'quantita' => 1,
				'omaggio' => 2,
			),
			'3' => array(
				'nome' => 'Birra bionda media',
				'prezzo' => 4.50,
				'quantita' => 3,
				'omaggio' => 0,
			)
		);
		$testata = array(
			'id' => 345,
			'progressivo' => 123,
			'totale' => 22.50,
			'pagato' => 50.00,
			'resto' => 27.50,
			'time' => date('Y-m-d H:i:s'),
			'tavolo' => '23',
			'cliente' => 'Francesco',
			'coperti' => 4,
			'omaggi' => 2,
			'asporto' => 0,
		);
		$testo_footer = '* In mancanza di prodotto fresco sarÃ  utilizzato prodotto surgelato.<br />
		Il Comitato organizzativo ringrazia quanti hanno partecipato alla manifestazione ed augura buon appetito.';	
}

