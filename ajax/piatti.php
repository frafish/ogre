<?php
include ("../CONFIG.php");
include ("../lib/dbconnection.php");
include_once ("../lib/view_helpers.php");

$ret = array(); // conterrà gli ID di tutti i piatti ancora da consegnare

$id_reparto = (isset($_GET['reparto'])) ? $_GET['reparto'] : 1;

switch($_GET['stato']) {
	case 'all': $where = '(preparazione = "'.ZERO_DATE.'" OR consegnato = "'.ZERO_DATE.'" OR ritirato = "'.ZERO_DATE.'")'; break;
	case 'consegnato': $where = '(preparazione > "'.ZERO_DATE.'" AND consegnato > "'.ZERO_DATE.'" AND ritirato = "'.ZERO_DATE.'")'; break;
	case 'preparazione': $where = '(preparazione > "'.ZERO_DATE.'" AND ritirato = "'.ZERO_DATE.'")'; break;
	case 'ritirato': $where = '(preparazione > "'.ZERO_DATE.'" AND consegnato > "'.ZERO_DATE.'" AND ritirato > "'.ZERO_DATE.'")'; break;
	case 'attesa': $where = 'preparazione = "'.ZERO_DATE.'" AND ritirato = "'.ZERO_DATE.'"'; break;
}

$sstart = get_start_time();
$where .= " AND time > '".$sstart."'";

$piatti = find_by('testate', $where);

foreach ($piatti as $pid => $piatto) {
	
	$ci_sono_prodotti_per_questo_reparto = false;
	// controllo che ci siano effettivamente prodotti destinati a questo reparto
	$sql = "SELECT prodotti.id_reparti
   	     FROM   dettagli, prodotti
      	  WHERE  dettagli.id_prodotti = prodotti.id AND dettagli.id_testate = ".$pid;
	$presult = db_query($sql);
   foreach ($presult as $prow) {
    	if($prow['id_reparti'] == $id_reparto) {
    		$ci_sono_prodotti_per_questo_reparto = true;
    	}
   }
	if ($ci_sono_prodotti_per_questo_reparto) {	
		if (isset($_GET['full_json']) && $_GET['full_json'] == 1) {
			$ret[$pid] = array(
				'progressivo' => $piatto["progressivo"],
				'asporto' => $piatto["asporto"],
				'html' => get_html_dettagli_piatti($pid, $id_reparto),
				'stato_preparazione' => ($piatto['preparazione'] > ZERO_DATE && $piatto['ritirato'] == ZERO_DATE) ? 1 : 0,
				'stato_consegnato' => ($piatto['consegnato'] > ZERO_DATE && $piatto['ritirato'] == ZERO_DATE) ? 1 : 0
			);
		} else {
   			$ret[$pid] = $piatto["progressivo"];
		}
   }
}

echo json_encode($ret);
?>