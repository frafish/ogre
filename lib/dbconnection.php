<?php
include_once (__DIR__."/../CONFIG.php");

session_start();

// CONNESSIONE AL DB
$path = DB_FILE;
$dir = dirname($path);
if (!is_dir($dir)) {
	mkdir($dir);
	@chmod($dir, 0777);
}

$db_init = false;
if(!file_exists($path)) {
	dev_log('ATTENZIONE: Creo il nuovo DATABASE in '.$path);
	$db_init = true;	
} else {
	if (!is_writable($path) || !is_writable($dir)) {
		die('ERRORE: Il file del database (o la cartella) è in sola lettura per il server web. <br>Questo accade solitamente quando si copia o importa il file manualmente.<br><br>Per risolvere, esegui questo comando da terminale (nella cartella del progetto):<br><br><code>chmod 777 -R ' . dirname($path) . '</code>');
	}
}
//$database_connection = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
//$database_connection = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASS);
try {
	$database_connection = new PDO('sqlite:'.$path);
	if ($db_init) {
		@chmod($path, 0666);
	}
} catch(PDOException $e) {
   // Print PDOException message
	die('ERRORE: Problemi di connessione al DB: ' . $e->getMessage()); 
}
// Set errormode to exceptions
$database_connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$database_connection->exec('PRAGMA journal_mode=WAL;');
$database_connection->exec('PRAGMA busy_timeout=5000;');

if ($db_init) {
	db_install();	
}	



function db_query($sql, $connessione = false, $sync = true, $params = []) {
	if (!$connessione) {
		global $database_connection;
		$connessione = $database_connection; 
	}
	//var_dump($sql);
	//list($operazione, $altro) = explode(' ', $sql, 2);
	$pezzi = explode(' ', $sql);
	$operazione = strtolower(reset($pezzi));
	
	$no_return_action = array('update', 'delete', 'truncate');
	
	if ($operazione == 'insert' && empty($params)) {
		$sql = db_fill_query($sql);
	}
	//echo $sql;
	
	//dev_log($sql, 'sql');
	
	if (!empty($params)) {
		$stmt = $connessione->prepare($sql);
		$success = $stmt->execute($params);
		if (!$success) {
			$db_error = print_r($stmt->errorInfo(), true);
			echo '<div class="alert alert-danger">SQL: '.$sql.'<br>ERROR:'.$db_error.'</div>';
			set_alert('SQL: '.$sql.'<br>ERROR:'.$db_error, 'danger');
			dev_log('SQL: '.$sql.' --> ERROR:'.$db_error);
			return false;
		}
		$result = $stmt;
	} else {
		//$result = mysqli_query($connessione, $sql);
		$result = $connessione->query($sql, PDO::FETCH_ASSOC);
		if (!$result) { 
			$db_error = print_r($connessione->errorInfo(), true);
			echo '<div class="alert alert-danger">SQL: '.$sql.'<br>ERROR:'.$db_error.'</div>';
			set_alert('SQL: '.$sql.'<br>ERROR:'.$db_error, 'danger');
			dev_log('SQL: '.$sql.' --> ERROR:'.$db_error);
			return false;
		}
	}
	
	// se è una query di inserimento ottengo il suo id
	if ($operazione == 'insert') {
		//return mysqli_insert_id($connessione);
		$last_id = $connessione->lastInsertId();	
	}
			
	// se è una query che non restituisce informazioni esco subito
	if (in_array($operazione, $no_return_action)) {
		return true;
	}
	// se è una query di inserimento restituisco il suo id
	if ($operazione == 'insert') {
		//return mysqli_insert_id($connessione);
		return $last_id;	
	}
	// genero l'array di risultati con id come chiave
	$ret = array();
	while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
		foreach($row as $fid => $field) {
			if (is_numeric($field)) {
				$row[$fid] = $field + 0;
			}
		}
		if (isset($row['id'])) {
			$ret[$row['id']] = $row;
		} else {
			$ret[] = $row;
		}
	}
	return $ret;
}

function db_sum($table, $field, $where = null) {
	$sql = "SELECT SUM(".$field.") as tot FROM ".$table;
	if ($where) {
		$sql .= "  WHERE ".$where;
	}
	$totali = db_query($sql);
	$totale_array = reset($totali);
	if (isset($totale_array['tot'])) {
		$totale = $totale_array['tot'];
		return $totale;
	}	
	return 0;
}

function db_count($table, $where = null) {
	$sql = "SELECT COUNT(*) as qnt FROM ".$table;
	if ($where) {
		$sql .= "  WHERE ".$where;
	}
	$totali = db_query($sql);
	$totale_array = reset($totali);
	if (isset($totale_array['qnt'])) {
		$totale = $totale_array['qnt'];
		return $totale;
	}	
	return 0;
}

function db_save($table, $row, $old = null, $conn = null, $sync = true) {
	if (!$old && !isset($row['id'])) {
		$db_table_default_values = json_decode(DB_TABLE_DEFAULT_VALUES, true);
		if (isset($db_table_default_values[$table])) {
			foreach ($db_table_default_values[$table] as $df => $dv) {
				if (!array_key_exists($df, $row) && $df !== 'id') {
					$row[$df] = $dv;
				}
			}
		}
	}
	$fields = array_keys($row);
	$id = (isset($row['id'])) ? $row['id'] : null;
	//var_dump($row); echo $id; die();
	if ($id) {
		if (!$old) {
			$old = find($table, $row['id']);		
		}
		if ($old) {
			$key = array_search('id', $fields);
			if (false !== $key) {
			    unset($fields[$key]);
			}
			// UPDATE
			foreach($fields as $field) {
				if (!isset($old[$field]) || $row[$field] != $old[$field]) {
					db_query('UPDATE '.$table.' SET '.$field.' = ? WHERE id = ?', $conn, $sync, [$row[$field], $id]);			
				}		
			}
			return true;
		}
	} 
	if (!$old) {
		unset($fields['id']);
		$key = array_search('id', $fields);
		if (false !== $key) {
		    unset($fields[$key]);
		    $fields = array_values($fields);
		}
		unset($row['id']);
		// INSERT
		$placeholders = implode(',', array_fill(0, count($fields), '?'));
		
		$params = array();
		foreach($fields as $f) {
			$params[] = $row[$f];
		}
		
		$sql = 'INSERT INTO '.$table.' ('.implode(',',$fields).') VALUES ('.$placeholders.')';
		//echo $sql;
		$id = db_query($sql, $conn, $sync, $params);
		return $id;
	}
	return false;
}

function db_get_fields($table, $field = null) {
	
	$ret = [];
	$db_table_default_values = json_decode(DB_TABLE_DEFAULT_VALUES, true);	
	// se ho i dati per questa tabella
	if (isset($db_table_default_values[$table])) {
		$fields = array_keys($db_table_default_values[$table]);
		foreach($fields as $afield) {
			$ret[$afield] = array(
				'tipo' => get_field_type($db_table_default_values[$table][$afield]),
				'commento' => '',
			);		
		}
	}
	/*
	global $db_name;
	$sql = 'SHOW FIELDS FROM '.$table;
	if ($field) {
		$sql .= " WHERE Field = '".$field."'";	
	}
	$fields = db_query($sql);
	
	$ret = array();	
	foreach ($fields as $afield) {
		$ret[$afield['Field']] = $afield;
		
		$ret[$afield['Field']]['commento'] = '';
		$comments = db_query("SELECT *, COLUMN_COMMENT
			FROM information_schema.COLUMNS
			WHERE TABLE_SCHEMA = '".$db_name."' AND TABLE_NAME = '".$table."' AND COLUMN_NAME = '".$afield['Field']."'");
		if (!empty($comments)) {
			//var_dump($comments);
			foreach($comments as $comment) {
				//if ($comment[''])
				$ret[$afield['Field']]['commento'] = $comment["COLUMN_COMMENT"];			
			}		
		}
		
		// TODO:  semplificare tipo di campo
		switch($afield['Type']) {
			case 'int(11)': $ret[$afield['Field']]['tipo'] = 'integer'; break;
			case 'tinyint(1)': $ret[$afield['Field']]['tipo'] = 'boolean'; break;
			case 'varchar(255)': $ret[$afield['Field']]['tipo'] = 'text'; break;  
			default: $ret[$afield['Field']]['tipo'] = $afield['Type'];
		}
	}
	*/
	if($field) {
		/*
		foreach($ret as $fkey => $aret) {
			if ($field == $fkey) {
				return $aret['Type'];			
			}		
		}
		*/
		if(isset($ret[$field])) {
			return $ret[$field]['tipo'];
		}
		return false;
	}
	return $ret;
}

function get_field_type($value) {
	return (is_numeric($value)) ? 'REAL' /*'INTEGER'*/ : 'TEXT';;
}

function db_get_field($table, $field, $value = null) {
	$afield = db_get_fields($table, $field);
	if($afield && $value) {
		if(isset($afield[$value])) {
			return $afield[$value];
		}
		return false;	
	}
	return $afield;
}

function is_foreign_key($field) {
	return (db_get_table_by_fk($field)) ? true : false;
}

function validate_date($date, $format = 'Y-m-d H:i:s')
{
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) == $date;
}

function db_get_table_from_sql($sql) {
	$sql = trim($sql);
	$pezzi = explode(' ', $sql);	
	$operazione = reset($pezzi);
	switch(trim(strtolower($operazione))) {
		case 'update':
			$table = trim($pezzi[1]);
			break;
		case 'insert':
		case 'delete':
		case 'truncate':
			$table = trim($pezzi[2]);
			break;
		default:
			return false;
	}
	return $table;
}

function db_get_fields_from_sql($sql) {
	$sql = trim($sql);
	$pezzi = explode('(', $sql, 3);
		$tmp = explode('INTO', reset($pezzi));
		$tmp = explode(')', $pezzi[1], 2);
	$fields = array_map('trim', explode(',', reset($tmp)));
	return $fields;
}

function db_get_values_from_sql($sql) {
	$sql = trim($sql);
	$pezzi = explode('(', $sql, 3);
		$tmp = explode('INTO', reset($pezzi));
		$tmp = explode(')', $pezzi[1], 2);
		$tmp = substr($pezzi[2], 0, -1); //explode(')', $pezzi[2]);
	$values = array_map('trim', explode(',', $tmp));
	return $values;
}

function db_fill_query($sql) {

	$table = db_get_table_from_sql($sql);
	$fields = db_get_fields_from_sql($sql);
	$values = db_get_values_from_sql($sql);
	
	$db_table_default_values = json_decode(DB_TABLE_DEFAULT_VALUES, true);
	
	// se ho i dati per questa tabella
	if (isset($db_table_default_values[$table])) {
		// recupero i campi a disposizione
		$defaults = array_keys($db_table_default_values[$table]);
		// aggingo tutti i campi a disposizione che non ho passato nella query originale
		$fields_not_found = array_diff($defaults, $fields);
		foreach($fields_not_found as $afield) {
			$fields[] = $afield;
			if (is_int($db_table_default_values[$table][$afield])) {
				$values[] = $db_table_default_values[$table][$afield];			
			} else {
				$values[] = '"'.$db_table_default_values[$table][$afield].'"';
			}
		}	
		$sql = 'INSERT INTO '.$table.' ('.implode(', ', $fields).') VALUES ('.implode(', ', $values).')';
	}
	return $sql;		
}

function db_install($conn = null) {
	$tables = array(); //db_query("SHOW TABLES", $conn);
	$db_table_default_values = json_decode(DB_TABLE_DEFAULT_VALUES, true);
	$new_tables = array_keys($db_table_default_values);
	dev_log('Installo le tabelle: '.print_r($new_tables, true));
	foreach($db_table_default_values as $table => $fields) {
		//if (!in_array($table, $tables)) {
			// inserisco la tabella
			$sql = "CREATE TABLE IF NOT EXISTS ".$table." (
				id INTEGER PRIMARY KEY,";
				$n_fields = count($fields);
				$i = 1;
				foreach($fields as $field => $value) {
					$type = get_field_type($value);
					$sql .= $field." ".$type;
					if ($i < $n_fields) {
						$sql .= ",";
					}
					$i++;			
				}
			//$sql .= "PRIMARY KEY ('id')";
			$sql .= ")";
			db_query($sql, $conn, false);
		//}	
	}
	
	// inserisco i dati minimi
	$user = array('id' => 1, 'nome' => 'ADMIN', 'admin' => 1, 'status' => 1); db_save('utenti', $user, null, $conn, false);
	//db_query('INSERT INTO utenti (id, nome, admin, status) VALUES (1, "ADMIN", 1, 1)', $conn, false);
	$printer = array('id' => 1, 'nome' => 'StampanteDiEsempio', 'ip' => '192.168.x.x', 'status' => 1); db_save('stampanti', $printer, null, $conn, false);
	//db_query('INSERT INTO stampanti (id, nome, status) VALUES (1, "StampanteDiEsempio", 0)', $conn, false);
	$cassa = array('id' => 1, 'nome' => 'A', 'status' => 1); db_save('casse', $cassa, null, $conn, false);
	//db_query('INSERT INTO casse (id, nome, status) VALUES (1, "A", 1)', $conn, false);
	if (!$conn && !db_count('opzioni')) {
		$default_options = json_decode(DEFAULT_OPTIONS, true);
		foreach($default_options as $aopt) {
			set_option($aopt['nome'], $aopt['valore'], $aopt['descrizione']);	
		}
	}
	
	db_sync();
}

function db_get_table_by_fk($field) {
	$pezzi = explode("_", $field, 2);
	if (count($pezzi) == 2 && reset($pezzi) == 'id') {
		return end($pezzi);	
	}
	return false;
}

function db_get_data($table, $id, $field, $object = null) {
	if (!$object) {
		$object = find($table, $id);	
	}
	return $object[$field];
	if (is_foreign_key($field)) {
		return find(get_table_by_fk($field), $object[$field]) ;	
	}
}

		
function will_be_first($table, $where = '') {
	$first = find_one_by($table, $where);
	if (!$first) {
		return true;	
	}
	return false;
}
function db_get_last_id($table) {
	$last = find_one_by($table, '', 'id DESC');
	if($last) {
		return $last['id'];
	}
	return 0;	
}


function slugify($str) {
	$str = trim($str);
	$str = strtolower($str);
	$str = html_entity_decode($str);
	$str = str_replace('à', 'a', $str);
	$str = str_replace('è', 'e', $str);
	$str = str_replace('ì', 'i', $str);
	$str = str_replace('ò', 'o', $str);
	$str = str_replace('ù', 'u', $str);
	$str = preg_replace("/[^a-zA-Z0-9\s]/", "", $str);
	$str = str_replace (" ", "-", $str);
	return $str;
}


function db_backup_get_connection($path = null) {
	if (!$path) {
		$path = DB_BACKUP;
		$dir = dirname($path);
		if (!is_dir($dir)) {
			mkdir($dir);
			@chmod($dir, 0777);
		}
	}
	
	$db_init = false;
	if(!file_exists($path)) {
		dev_log('ATTENZIONE: Creo il nuovo BACKUP in '.$path);
		$db_init = true;	
	}
	
	// Create (connect to) SQLite database in file
	try {
	   $file_db = new PDO('sqlite:'.$path);
	   if ($db_init) {
	       @chmod($path, 0666);
	   }
	} catch ( Exception $e ) {
		dev_log($e->getMessage());
		return false;
	}
	
	// Set errormode to exceptions
	$file_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$file_db->exec('PRAGMA journal_mode=WAL;');
	$file_db->exec('PRAGMA busy_timeout=5000;');
	
	if ($db_init) {
		db_install($file_db);
	}
	   
   return $file_db;
}

function db_sync($table = null, $inverse = false) {
	$tables = array();
	if ($table) {
		$tables[] = $table;
	} else { 
		$db_table_default_values = json_decode(DB_TABLE_DEFAULT_VALUES, true);
		$tables = array_keys($db_table_default_values); 
	}
	//var_dump($tables);
	foreach($tables as $table) {
		$id_origin = db_get_last_id($table);
		//var_dump($table);
		$sql = "SELECT id FROM ".$table." ORDER BY id DESC LIMIT 1";
		$result = db_query($sql, db_backup_get_connection());
		$id_dest = ($result) ? reset($result)['id'] : 0;
		//dev_log('Inserisco nella tabella '.$table.' gli id da '.$id_dest.' a '.$id_origin);
		if ($id_origin > $id_dest) {
			$rows = find_by($table, 'id > '.$id_dest.' AND id <= '.$id_origin);
			if ($rows) {
				foreach ($rows as $obj) {
					db_backup_insert($table, $obj);
				}
			}
		}
	}
}

function implode_quote($values) {
	$ret = '';
	foreach ($values as $val) {
		if ($ret) {
			$ret .= ', ';		
		}
		if (is_numeric($val)) {
			$ret .= $val;
		} else {
			$ret .= '"'.$val.'"';
		}	
	}
	return $ret;
}

function db_backup_insert($table, $obj) {

	$ofields = array_keys($obj);
	$fields = implode(',', $ofields);
	$values = implode_quote($obj);
	
	$sql = "INSERT OR IGNORE INTO ".$table." (".$fields.") VALUES (".$values.")";
	//dev_log('BACKUP: '.$sql);
	//dev_log('TRY to BACKUP: ['.$table.']['.$obj['id'].'] - '.$sql);
	$id = db_query($sql, db_backup_get_connection(), false);
	if ($id) {
		//dev_log('BACKUP: ['.$table.']['.$id.'] - '.$sql);
		return $id;
	}
	return false;
}

/*function mcd($n1, $n2){
	//echo 'mcd: '.$n1.' - '.$n2.'<br>';
	while ($n1 != $n2){
		if ($n1 > $n2){
			$n1 = $n1 - $n2;
		} else {
			$n2 = $n2 - $n1;
		}
	}
	return $n1;
}*/
function mcd($a,$b) {
	while($b) list($a,$b) = array($b, $a % $b);
	return $a;
}

function find_by($from, $where = null, $order = null, $limit = null, $field = null) {
	if (!$field) { 
		$field = '*'; 
	}
	$sql = "SELECT ".$field." FROM ".$from;
	if ($where) {
		$sql .= " WHERE ".$where;
	}	
	if ($order) {
		$sql .= " ORDER BY ".$order;	
	}	
	if ($limit) {
		$sql .= " LIMIT ".$limit;	
	}
	$ret = db_query($sql);
	return $ret;
}

function find_one_by($from, $where = null, $order = null, $field = null) {
	
	if (is_numeric($where) && intval($where)) {
		$where = 'id = '.$where;
	}	
	
	$ret = find_by($from, $where, $order, '1');
	if ($ret) {
		$first = reset($ret);
		if ($field) {
			if (isset($first[$field])) {
				return $first[$field];		
			}
			return false;
		}
		return $first;
	}
	return false;
}

function find($from, $id) {
	return find_one_by($from, 'id = '.$id);
}

function get_or_insert($table, $field, $value) {
	$element = find_one_by($table, $field.' = "'.$value.'"', null, 'id');
	if ($element) {
		return $element;	
	}
	return db_query('INSERT INTO '.$table.' ('.$field.') VALUES ("'.$value.'")');
}

function get_option($name) {
	$ret = find_one_by('opzioni',"nome = '".$name."'", null, 'valore');
	if ($ret) {
		if (is_numeric($ret)) {
			return intval($ret);		
		}
		return $ret;	
	}
	return false;
}

function set_option($name, $value, $commento = null) {
	$ret = find_one_by('opzioni', "nome = '".$name."'", null, 'id');
	if ($ret) {
		$sql = "UPDATE opzioni SET valore = '".$value."' WHERE id = ".$ret;	
	} else {
		$sql = "INSERT INTO opzioni (nome, valore) VALUES ('".$name."', '".$value."')";
		if ($commento) {
			$sql = 'INSERT INTO opzioni (nome, valore, descrizione) VALUES ("'.$name.'", "'.$value.'", "'.$commento.'")';		
		}	
	}
	db_query($sql);
	return true;
}

function normalize_text($text) {
	$text = trim($text);
	
	$text = htmlspecialchars($text, ENT_QUOTES | ENT_IGNORE, "iso-8859-1");
	$text = str_replace('à', '&agrave;', $text);
	$text = str_replace('è', '&egrave;', $text);
	$text = str_replace('ì', '&igrave;', $text);
	$text = str_replace('ò', '&ograve;', $text);
	$text = str_replace('ù', '&ugrave;', $text);
	
	$text = mb_convert_encoding($text, "utf-8");
	
	return nl2br($text);
}

function termina_sessione() {
	$_SESSION['id_utente'] = "";
	$_SESSION['id_cassa'] = "";
	$_SESSION['id_stampante'] = "";
	unset($_SESSION['id_utente']);
	unset($_SESSION['id_cassa']);
	unset($_SESSION['id_stampante']);
	unset($_SESSION);
	session_destroy();
	header("Location: /");
	die();
}

function dev_log($msg, $suffisso = null) {
	//die($msg);
	if($suffisso) {
		$suffisso = "_".$suffisso;
	} else { 
		$suffisso = ''; 
	}
	if (!is_dir(CARTELLA_LOG)) {
		mkdir(CARTELLA_LOG, 0777, true);
	}

	$dir = CARTELLA_LOG.DIRECTORY_SEPARATOR.date('Ymd').$suffisso.".txt";
	$logfile = fopen($dir, "a+") or die("Unable to open file!");
	$txt = "[".date('Y-m-d H:i:s')."] ".$msg.PHP_EOL;
	fwrite($logfile, $txt);
	fclose($logfile);
	return true;
}

function get_cassa($id = null) {
	if (!$id && isset($_SESSION['id_cassa'])) {
		$id = $_SESSION['id_cassa'];	
	}
	if (!$id) {
		return false;
	}
	$cassaInfo = find_one_by('casse','id = '.$id);
	return $cassaInfo;
}

function get_tipo($asporto = 0, $omaggi = 0) {
	/*if ($asporto) {
		$where = 'asporto = 1';
	} else {
		$where = 'asporto = 0';
	}
	$where .= ' AND ';
	if ($omaggi) {
		$where .= 'omaggio = 1';
	} else {
		$where .= 'omaggio = 0';
	}
	$ret = find_one_by('tipi', $where, null, 'nome');*/
	$ret = '';
	if ($asporto) {
		$ret .= 'ASPORTO';	
	} else {
		$ret .= 'TAVOLO';
	}
	if ($omaggi) {
		$ret .= '-OMAGGIO';	
	}
	return $ret;	
}

function get_prodotti_plus($id_testata = null, $where = null) {
	$sql = 'SELECT prodotti.*, dettagli.quantita, dettagli.omaggio, dettagli.nota FROM dettagli, prodotti WHERE dettagli.id_prodotti = prodotti.id';
	if ($id_testata) {
		$sql .= ' AND dettagli.id_testate = '.$id_testata;	
	}
	if ($where) {
		$sql .= ' AND '.$where;	
	}
	return db_query($sql);
}

function get_ingredienti_by_prodotto($id_prodotto, $separatore = null) {
	$ricette = find_by('ricette', 'id_prodotti = '.$id_prodotto);
	$ret = array();
	foreach($ricette as $ricetta) {
		$ingrediente = find('ingredienti', $ricetta['id_ingredienti']);
		if ($ingrediente) {
			$ret[$ingrediente['nome']] = $ricetta['quantita'];
		}
	}
	if($separatore) {
		$stringaIngredienti = array();
		if(!empty($ret)) {
			foreach($ret as $ingrediente => $quantita) {
				$stringaIngredienti[] = $quantita.' '.$ingrediente;				
			}
		}
		return implode($separatore, $stringaIngredienti);
	}
	return $ret;
}

function get_nome_prodotto($id) {
	$prodotto = find('prodotti', $id);
	if ($prodotto['corto']) {
		return $prodotto['corto']; 	
	}
	return $prodotto['nome'];
}








function get_start_time($real = false) {
    $inizio = get_option('inizio_sessione');
    if ($inizio) {
        if ($real) {
			return get_real_start_time($inizio);	
		}
        return $inizio;
    }

	$result_sessione = find_one_by('accessi', "act LIKE 'stop'", 'time DESC', 'time');
	if ($result_sessione) {
		//var_dump($result_sessione);
		if ($real) {
			return get_real_start_time($result_sessione);	
		}
		return $result_sessione;
	}
	
	// prima sessione in assoluto
	$result_sessione = find_one_by('accessi', "act LIKE 'in'", 'time ASC', 'time');
	if($result_sessione) {
		return $result_sessione;
	}	
	
	// ancora nessuna sessione iniziata
	return ZERO_DATE;		
}


function get_real_start_time($sstart = null) {
	if (!$sstart) {
		$sstart = get_start_time();
	}
	$prima_testata_sessione = find_one_by('testate', "time > '".$sstart."'", 'time');
	if ($prima_testata_sessione) {
		return $prima_testata_sessione['time'];
	}
	// prima sessione in assoluto
	return $sstart;		
}

function is_session_started() {
    $attiva = get_option('sessione_attiva');
    if ($attiva !== false && $attiva !== null) {
        return $attiva == '1';
    }

	$sstart = get_start_time();
	if($sstart == ZERO_DATE) {
		return false;	
	}
	
	// Fallback legacy: consideriamo la sessione chiusa solo se l'ultima azione registrata è uno 'stop'.
	// Un 'out' (logout) di un singolo utente NON chiude la sessione globale.
	$last_act = find_one_by('accessi', '', 'time DESC', 'act');
	if($last_act == 'stop') {
		return false;
	}
	return true;
}

function get_real_end_time ($sstime = null) {
	if ($sstime) {
		$ultima_testata_sessione = find_one_by('testate', "time < '".$sstime."'", 'time DESC');
		if ($ultima_testata_sessione) {
			return $ultima_testata_sessione['time'];
		}
	}
	
	$sstart = get_start_time();
	$last_order = get_last_order();
	$last_login = get_last_login();
	$max = ZERO_DATE;
	
	$max = ($max > $sstart) ? $max : $sstart;
	
	if ($last_order) {
		$max = ($last_order['time'] > $max) ? $last_order['time'] : $max;	
	}
	
	if ($last_login) {
		$max = ($last_login['time'] > $max) ? $last_login['time'] : $max;	
	}
	
	// prima sessione in assoluto
	return $max;
}

function get_last_login() {
	$last = find_one_by("accessi", "act = 'in'", 'time DESC');
	if ($last) {
		return $last;	
	}
	return false;
}

function get_active_users() {
	$sstart = get_start_time();
	$users = array();
	$accessi = find_by('accessi', "time > '".$sstart."' AND act LIKE 'in'", 'time');
	if(!empty($accessi)) {
		foreach($accessi as $accesso) {	
			//$out = find_one_by('accessi', "id_utenti = ".$accesso['id_utenti']." AND time > '".$accesso['time']."' AND act LIKE 'out' AND ip LIKE '".$accesso['ip']."'");
			$out = find_one_by('accessi', "id_utenti = ".$accesso['id_utenti']." AND time > '".$accesso['time']."' AND act LIKE 'out'");
			if(!$out) {
				$users[$accesso['id_utenti']] = $accesso;
			}
		}
	}
	return $users;		
}

function count_active_users() {
	return count(get_active_users());
}

function is_user() {
	if (get_user()) {
		return true;
	}
	return false;	
}
function get_user() {
	//var_dump($_SESSION['id_utente']);
	if (isset($_SESSION['id_utente'])) {
		if ($_SESSION['id_utente'] == 0)  {
			return true;	
		}
		$user = find('utenti', $_SESSION['id_utente']);
		if ($user) {
			return $user;
		}
	}
	return false;
}
function is_superman() {
	$user = get_user();
	if($user) {
		return $user['admin'];
	}
	return false;	
}
function get_login_form($go = '/') {
	$form = ' 
		<form class="login well" action="login.php" method="post">
			<h1 class="text-center">Login</h1>
			<input class="form-control" type="password" name="password" value="">
			<input type="hidden" name="go" value="'.$go.'"> 
			<input class="btn btn-primary mt-10 btn-full" type="submit" value="Accedi">
		</form>
	';
	return $form;
}
function user_logout($id_utente = 0) {
	if (!$id_utente) {
		return termina_sessione();	
	}
	$sstart = get_start_time();
	// se l'utente ha già fatto login per questa sessione si ignora il conteggio
	$login = find_one_by("accessi", "id_utenti = ".$id_utente." AND time > '".$sstart."' AND act = 'in'", 'time DESC');
	//var_dump($login); die();
	if ($login) {
		$logout = find_one_by('accessi', "id_utenti = ".$id_utente." AND time > '".$login['time']."' AND act = 'out'");
		if (!$logout) {
			// inserisco la nuova riga aggiornando il contatore
			return db_query("INSERT INTO accessi (session_id, id_utenti, act, time) VALUES ('".$login['session_id']."', ".$id_utente.", 'out', '".date('Y-m-d H:i:s')."')");	
		} else {
			//var_dump($logout); die();
		}
	}
	return false;
}

function is_logged_in($sstart = null) {
	if(!is_user()) {
		return false;	
	}
	if(!$sstart) {
		$sstart = get_start_time();
	}
	$login = find_one_by('accessi', "id_utenti = ".$_SESSION['id_utente']." AND time > '".$sstart."' AND act = 'in' AND session_id = '".session_id()."'", "time DESC");
	if ($login) {
		$logout = find_one_by('accessi', "id_utenti = ".$_SESSION['id_utente']." AND time > '".$login['time']."' AND act = 'out' AND session_id = '".session_id()."'");
		if ($logout) {
			return false;
		}
		return true;
	}
	return false;
}





function get_filename_by_id($id_testata) {
	$testata = find('testate', $id_testata);
	$time = strtotime($testata['time']);
	$nome_pdf = date('Y-m-d_H-i-s', $time)."_".$testata['id_casse']."_".$testata['progressivo']."_".$id_testata;
	return $nome_pdf.'.pdf';
}
function get_id_by_filename($path) {
	$filename = basename($path);
	// rimuovo l'estensione
	$pezzi = explode('.', $filename);
	$nome_pdf = $pezzi[0];
	$pezzi = explode('_', $nome_pdf);
	return $pezzi[4];	
}

function get_printer_by_reparto($id_reparto) {
	$reparto = find('reparti', $id_reparto);
	// se è stata impostata una stampante specifica per il reparto
	if ($reparto['id_stampanti']) {
		return $reparto['id_stampanti'];
	}
	// se la ricevuta è attiva e ho impostato una stampante in sessione
	if ($reparto['ricevuta'] && isset($_SESSION['id_stampante'])) {
		return $_SESSION['id_stampante'];	
	}
	// se la ricevuta è attiva per questo reparto e sono loggato come cassa
	if ($reparto['ricevuta'] && isset($_SESSION['id_cassa'])) {
		$cassa = find('casse', $_SESSION['id_cassa']);
		return $cassa['stampante'];
	}
	// la prima
	$prima = find_one_by('stampanti');
	$prima['id'];
}

function get_next_progressivo() {
	$progressivo = 1;
	$sstart = get_start_time();
	$precedente = find_one_by('testate', "time > '".$sstart."'", 'id DESC', 'progressivo');
	// se esiste una comanda precedente prenderà il successivo
	if ($precedente) {
		$progressivo = ++$precedente;	
		//return $progressivo;
	} else {
		$custom = get_option('progressivo-custom');
		if ($custom) {
			$progressivo = $custom;
		} else {
			$progressivo = 1;
		}
	}
	
	// se va oltre il valore massimo riparto dall'inizio
	if ($progressivo > get_option('progressivo-max') || $progressivo <= 0) {
		$progressivo = 1;
	}
	
	return $progressivo;	
}


function get_soglie($id_ingrediente = null) {
	$where = '';
	if ($id_ingrediente) {
		$where = ' WHERE id_ingredienti = '.$id_ingrediente;	
	}
	return db_query("SELECT id_ingredienti as id, soglia FROM magazzino".$where." ORDER BY time DESC");	
}
function get_soglia() {
	$soglia = get_soglie($id_ingrediente);
	if ($soglia && !empty($soglia)) {
		return reset($soglia);	
	}
	return 0;	
}

function get_scorte($time = null, $id_ingrediente = null, $field = null) {
	if (!$time) {
		$time = get_start_time();	
	}
	//$scorte = find_by("magazzino", "time > '".$sstart."' OR durevole = 1"); // solo gli ingredienti
	$where_id = '';
	if ($id_ingrediente) {
		$where_id = ' AND id_ingredienti = '.$id_ingrediente;
	}
	$sql = "SELECT id_ingredienti as id, id_ingredienti, SUM(quantita) as quantita FROM magazzino WHERE (time > '".$time."' OR durevole = 1)".$where_id." GROUP BY id_ingredienti";
	$scorte = db_query($sql);
	foreach($scorte as $skey => $ascorta) {
		$scorte[$skey]['inserito'] = true;	
	}
	$soglie = db_query("SELECT id_ingredienti as id, soglia FROM magazzino WHERE soglia > 0".$where_id." ORDER BY time DESC");
	foreach ($soglie as $sid => $soglia) {
		$scorte[$sid]['soglia'] = $soglia['soglia'];
		$scorte[$sid]['id_ingredienti'] = $sid;
		$scorte[$sid]['quantita'] = (isset($scorte[$sid]['quantita'])) ? $scorte[$sid]['quantita'] : 0;
	}
	if ($id_ingrediente) {
		if ($field) {
			if(isset($scorte[$id_ingrediente][$field])) {
				return $scorte[$id_ingrediente][$field];
			}	
			return 0;	
		}
		if(isset($scorte[$id_ingrediente])) {
			return $scorte[$id_ingrediente];
		}	
		return false;
	}
	return $scorte;
}


function get_scorta_ingrediente($id) {
	return get_scorte(get_start_time(), $id, 'quantita');
}
function get_vendita_ingredienti($id = null, $sstart = null, $eend = null) {
	if (!$sstart) {
		$sstart = get_start_time();
	}
	$where_end = '';
	if ($eend) {
		$where_end = " AND testate.time <=	'".$eend."'";
	}
	//$dettagli = find_by('dettagli', "time >= '".$sstart."'".$where_end); // conterrà gli ingredienti
	$dettagli = db_query("SELECT dettagli.* FROM dettagli, testate WHERE testate.id = dettagli.id_testate AND testate.time >= '".$sstart."'".$where_end);
	$ingredienti = array();
	foreach ($dettagli as $dettaglio) {
		// ottengo gli ingredienti di ogni prodotto
	    $ricette = find_by('ricette', "id_prodotti = ".$dettaglio["id_prodotti"]);
	    foreach($ricette as $ricetta) {
			$qnt = $dettaglio['quantita'] + $dettaglio['omaggio'];
	    	$ingredienti[$ricetta['id_ingredienti']] = (isset($ingredienti[$ricetta['id_ingredienti']])) ? $ingredienti[$ricetta['id_ingredienti']] + $qnt * $ricetta['quantita'] : $qnt * $ricetta['quantita']; // aggiungo alla lista degli ingredienti
	    }
	}
	if (!$id) {
		return $ingredienti;	
	}
	if (isset($ingredienti[$id])) {
		return $ingredienti[$id];
	}
	return 0;
}
function get_disponibilita_ingrediente($id) {
	return get_scorta_ingrediente($id) - get_vendita_ingredienti($id);
}

function get_vendita_prodotti($id = null, $sstart = null, $eend = null) {
	if (!$sstart) {
		$sstart = get_start_time();
	}
	$where_end = '';
	if ($eend) {
		$where_end = " AND testate.time < '".$eend."'";
	}
	//$dettagli = find_by('dettagli', "time > '".$sstart."'".$where_end); // conterrà gli ingredienti
	$dettagli = db_query("SELECT dettagli.* FROM dettagli, testate WHERE dettagli.id_testate = testate.id AND testate.time > '".$sstart."'".$where_end); // conterrà gli ingredienti
	$prodotti = array();
	foreach ($dettagli as $dettaglio) {
		$qnt = $dettaglio['quantita'] + $dettaglio['omaggio'];
	    $prodotti[$dettaglio['id_prodotti']] = (isset($prodotti[$dettaglio['id_prodotti']])) ? $prodotti[$dettaglio['id_prodotti']] + $qnt : $qnt; // aggiungo alla lista degli ingredienti
	}
	if (!$id) {
		return $prodotti;	
	}
	if (isset($prodotti[$id])) {
		return $prodotti[$id];
	}
	return 0;
}

function get_reparti($id_testata = null, $all = false) {
	$reparti = array();
	if ($id_testata) {
		$testata = find('testate', $id_testata);
		if ($testata) {
			$dettagli = get_prodotti_plus($id_testata);
			if (!empty($dettagli)) {				
				foreach($dettagli as $dkey => $dvalue) {
					if (!isset($reparti[$dvalue['id_reparti']])) {
						$reparti[$dvalue['id_reparti']] = find('reparti', $dvalue['id_reparti']);
					}
				}
				//return $reparti;			
			}
		}
	} else {
		$reparti = find_by('reparti');
	}
	if ($all) {
		$reparti[-2] = array('id' => -2, 'nome' => CARTELLA_RICEVUTA_COMPLETA, 'ricevuta' => 0, 'fila' => 0, 'coperti' => 0, 'id_stampanti' => 0);
		$reparti[-1] = array('id' => -1, 'nome' => CARTELLA_RICEVUTA_CASSA, 'ricevuta' => 0, 'fila' => 0, 'coperti' => 0, 'id_stampanti' => 0);
		$reparti[0] = array('id' => 0, 'nome' => CARTELLA_RICEVUTA_CLIENTE, 'ricevuta' => 1, 'fila' => 0, 'coperti' => 0, 'id_stampanti' => 0);	
	}
	return $reparti;
}


function re_array_files($file_post) {
    $file_ary = array();
    $file_count = count($file_post['name']);
    $file_keys = array_keys($file_post);
    for ($i=0; $i<$file_count; $i++) {
        foreach ($file_keys as $key) {
            $file_ary[$i][$key] = $file_post[$key][$i];
        }
    }
    return $file_ary;
}

function get_csv($table = null, $rows = null, $cols = null, $download = false, $filename = null) {
	$csv = '';
	if (!$rows) {
		$rows = find_by($table);	
	}
	if (!$cols /*&& $cols != ''*/) {
		if ($rows) {
			$arow = reset($rows);
			$colonne = array();
			foreach($arow as $rkey => $rvalue) {
				$colonne[$rkey] = $rkey;			
			}
		} else {
			$colonne = get_fields($table);
		}
		//var_dump($cols); die();
	} else {
		$colonne = $cols;
	}
	
	//var_dump($cols); die();
	
	$fp = fopen('php://temp', 'r+');
	
	if ($cols !== '') {
		fputcsv($fp, $colonne, ';', '"');
	}
	
	foreach ($rows as $rkey => $arow) {
		$row_data = array();
		foreach ($colonne as $ckey => $acol) {
			if(isset($arow[$ckey])) {
				$row_data[] = html_entity_decode($arow[$ckey], ENT_QUOTES | ENT_HTML5, 'UTF-8');
			}
		}
		fputcsv($fp, $row_data, ';', '"');
	}
	
	rewind($fp);
	$csv = stream_get_contents($fp);
	fclose($fp);
	
	if ($download) {
		header('Content-Type: application/csv');
		// TODO: gli forzo anche un nome
		if($filename) {
			header('Content-Disposition: attachment; filename="'.$filename.'";');
		}
		echo $csv;
		die();
	}
	if ($filename) {
		return file_put_contents($filename, $csv);	
	}
	return $csv;	
}

function set_alert($message, $type = 'info') {
	$_SESSION['alerts'][] = array('type' => $type, 'message' => $message);
	return true;
}
function get_alerts() {
	if(isset($_SESSION['alerts'])) {
		return $_SESSION['alerts'];
	}
	return false;	
}
function has_alerts() {
	$alerts = get_alerts();
	if ($alerts) {
		return count($alerts);
	}
	return false;
}
function print_alerts() {
	$ret = '';
	$alerts = get_alerts();
	//var_dump($alerts);
	if ($alerts) {
		foreach($alerts as $akey => $alert) {
			$ret .= '
			<div role="alert" class="alert alert-'.$alert['type'].' alert-dismissible">
				<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				'.$alert['message'].'
			</div>
			';	
			$_SESSION['alerts'][$akey] = "";
			unset($_SESSION['alerts'][$akey]);
		}
	}
	echo $ret;
	return true;
}

// genero i pulsanti per scorrere le tabelle
function print_categorie($categorie = null, $kcategoria = null, $id = true, $classi = '') {
	if (empty($categorie)) {
		$categorie = find_by('categorie');		
	}
	
	$cassa = get_cassa();
	$where_prodotti = "status = 1";
	if ($cassa['id_reparti'] > 0) {
		$conds = array();
		$conds[] = "id_reparti = ".$cassa['id_reparti'];
		
		$cat_uni = find_by('categorie', 'universale = 1');
		if (!empty($cat_uni)) {
			$uni_ids = array();
			foreach ($cat_uni as $cu) {
				$uni_ids[] = $cu['id'];
			}
			$conds[] = "id_categorie IN (".implode(",", $uni_ids).")";
		}
		
		if (!empty($conds)) {
			$where_prodotti .= " AND (".implode(" OR ", $conds).")";
		}
	}
	
  echo '<ul'; if ($id && $kcategoria) { echo ' id="'.slugify($categorie[$kcategoria]['nome']).'-'.$kcategoria.'"'; } echo' class="elenco-categorie '; echo ($kcategoria == reset($categorie)['id']) ? 'first ' : 'other '; echo $classi.'" role="tablist">';
  foreach ($categorie as $key => $acategoria) {
  		$prodotti_categoria = find_by("prodotti", "id_categorie = ".$acategoria['id']." AND ".$where_prodotti);
		if(!empty($prodotti_categoria)) { 
			$cat_slug = slugify($acategoria['nome']);
			echo '<li role="presentation" class="elenco-categoria'; if ($key == $kcategoria) { echo ' active'; } echo '">';
				echo '<a href="#'.$cat_slug.'-'.$key.'">'.$acategoria['nome'].'</a>';
				//echo '<a href="#tab-'.$cat_slug.'" aria-controls="tab-'.$cat_slug.'" role="tab" data-toggle="tab">'.$acategoria['nome'].'</a>';
			echo '</li>';
			}
	  }
   echo '</ul>';
}

function get_last_order() {
	$last = find_one_by("testate", null, 'id DESC');
	if ($last) {
		return $last;	
	}
	return false;
}

function aggiorna_quantita_prodotti() {
    $time = get_start_time();
    $sql_in = "SELECT id_ingredienti, SUM(quantita) as quantita FROM magazzino WHERE (time > '".$time."' OR durevole = 1) GROUP BY id_ingredienti";
    $magazzino_in = db_query($sql_in);
    
    $sql_out = "SELECT r.id_ingredienti, SUM((d.quantita + d.omaggio) * r.quantita) as consumato 
                FROM dettagli d
                JOIN testate t ON t.id = d.id_testate
                JOIN ricette r ON r.id_prodotti = d.id_prodotti
                WHERE t.time >= '".$time."'
                GROUP BY r.id_ingredienti";
    $magazzino_out = db_query($sql_out);
    
    $disponibilita_ing = [];
    if($magazzino_in) {
        foreach ($magazzino_in as $row) {
            $disponibilita_ing[$row['id_ingredienti']] = floatval($row['quantita']);
        }
    }
    if($magazzino_out) {
        foreach ($magazzino_out as $row) {
            if (isset($disponibilita_ing[$row['id_ingredienti']])) {
                $disponibilita_ing[$row['id_ingredienti']] -= floatval($row['consumato']);
            }
        }
    }
    
    $prodotti = find_by('prodotti');
    $ricette = find_by('ricette');
    
    $ricette_prodotto = [];
    if($ricette) {
        foreach ($ricette as $r) {
            $ricette_prodotto[$r['id_prodotti']][] = $r;
        }
    }
    
    if($prodotti) {
        foreach ($prodotti as $p) {
            $pid = $p['id'];
            $max_avail = -1;
            if (isset($ricette_prodotto[$pid])) {
                foreach ($ricette_prodotto[$pid] as $r) {
                    $id_ing = $r['id_ingredienti'];
                    $qnt_req = floatval($r['quantita']);
                    
                    if (isset($disponibilita_ing[$id_ing]) && $qnt_req > 0) {
                        $avail = floor($disponibilita_ing[$id_ing] / $qnt_req);
                        if ($max_avail < 0 || $avail < $max_avail) {
                            $max_avail = $avail;
                        }
                    }
                }
            }
            db_query('UPDATE prodotti SET quantita = ? WHERE id = ?', false, true, [$max_avail, $pid]);
        }
    }
}

?>
