<?php

error_reporting(E_ALL);
ini_set("display_errors", 0);


date_default_timezone_set('Europe/Rome');

/*
$server_ip = "localhost";
$OS = "linux";
$path_ricevute = "/var/www/html/ricevute/";
*/
/*
// id utente db
$id_utente_griglia = 5;
$id_utente_cucina = 6;
$id_utente_distribuzione = 7;
*/
// password di accesso generale
//$psw = "noi2015";

// credenziali di accesso al db
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'fra');
define('DB_NAME', 'sagra');

define('DB_FILE', __DIR__.DIRECTORY_SEPARATOR.'db'.DIRECTORY_SEPARATOR.'db.sqlite');
define('DB_BACKUP', __DIR__.DIRECTORY_SEPARATOR.'backup'.DIRECTORY_SEPARATOR.'bk.sqlite');

/*
// credianziali utente server per accesso ssh
$ssh_user = "fra";
$ssh_pass = "fra";
$ssh_server = 'localhost';
$ssh_port = 22;
$usa_ssh = false;//true;
*/
/*
// pubblicita
$pubblicita = true;
*/

define('CARTELLA_RICEVUTE', 'ricevute');
define('CARTELLA_RICEVUTA_COMPLETA', 'all');
define('CARTELLA_RICEVUTA_CASSA', 'cassa');
define('CARTELLA_RICEVUTA_CLIENTE', 'cliente');

define('CARTELLA_LOG', __DIR__.DIRECTORY_SEPARATOR.'log');

// Gestione errori 500 e logging
function custom_error_logger($message) {
    $log_file = CARTELLA_LOG . DIRECTORY_SEPARATOR . 'ERROR.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] $message\n";
    @file_put_contents($log_file, $log_message, FILE_APPEND);
}

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    custom_error_logger("Error [$errno]: $errstr in $errfile on line $errline");
    return false;
});

set_exception_handler(function($exception) {
    custom_error_logger("Uncaught Exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine());
});

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        custom_error_logger("Fatal Error [{$error['type']}]: " . $error['message'] . " in " . $error['file'] . " on line " . $error['line']);
    }
});

define('CARTELLA_IMPORTATI', 'import');

$giorni_settimana = array(
	"Domenica",
	"Luned&igrave;",
	"Marted&igrave;",
	"Mercoled&igrave;",
	"Gioved&igrave;",
	"Venerd&igrave;",
	"Sabato"
);
define('WEEK', json_encode($giorni_settimana));

define('ZERO_DATE', '2000-01-01 01:01:01');

$db_table_default_values = array(
		'accessi' => array(
				//'id' => 0,
				'time' => date('Y-m-d H:i:s'),
				'id_utenti' => 0,
				'act' => 'boh',
				'id_casse' => 0,				
				'browser' => '',
				'ip' => '',
				'browser' => '',
				'session_id' => 0,
			),
		'casse' => array(
				//'id' => 0,
				'nome' => 'cassa',
				'id_stampanti' => 0,
				'asporto' => 0,
				'id_categorie' => 0,
				'id_reparti' => 0,
				'status' => 1,
			),
		'categorie' => array(
				//'id' => 0,
				'nome' => 'categoria',
				'universale' => 0,
				'ordine' => 0,
				'status' => 1,
			),
		'dettagli' => array(
				//'id' => 0,
				'id_prodotti' => 0,
				'id_testate' => 0,
				'quantita' => 0,
				'omaggio' => 0,
				'nota' => '',
			),
		'ingredienti' => array(
				//'id' => 0,
				'nome' => 'ingrediente',
				'griglie' => 0,
			),
		'magazzino' => array(
				//'id' => 0,
				'id_ingredienti' => 0,
				'quantita' => -1,
				'soglia' => 0,
				'durevole' => 0,
				'time' => date('Y-m-d H:i:s'),
			),
		'opzioni' => array(
				//'id' => 0,
				'nome' => 'ingrediente',
				'valore' => '',
				'descrizione' => '',
			),		
		'prodotti' => array(
				//'id' => 0,
				'nome' => 'prodotto',
				'corto' => '',
				'prezzo' => 0,
				'quantita' => -1,
				'id_categorie' => 0,
				'id_reparti' => 0,
				'ordine' => 0,
				'status' => 1,
				'colore' => '',
			),
		'reparti' => array(
				//'id' => 0,
				'nome' => 'reparto',
				'ricevuta' => 0,
				'fila' => 0,
				'coperti' => 0,
				'id_stampanti' => 0,
			),
		'ricette' => array(
				//'id' => 0,
				'id_prodotti' => 0,
				'id_ingredienti' => 0,
				'quantita' => 0,
			),
		'stampanti' => array(
				//'id' => 0,
				'nome' => 'stampante',
				'ip' => '',
				'formato' => 'A5',
				'status' => 1,
			),
		'testate' => array(
				//'id' => 0,
				'progressivo' => 0,
				'id_casse' => 0,
				'totale' => 0,
				'pagato' => 0,
				'asporto' => 0,
				'pos' => 0,
				'coperti' => 0,
				'omaggi' => 0,
				'tavolo' => '',
				'cliente' => '',
				'id_utenti' => 0,
				'note' => '',
				'time' => date('Y:m:d H:i:s'),
				'consegnato' => ZERO_DATE, // '0000-00-00 00:00:00',
				'preparazione' => ZERO_DATE, // '0000-00-00 00:00:00',
				'ritirato' => ZERO_DATE, // '0000-00-00 00:00:00',
			),
		'utenti' => array(
				//'id' => 0,
				'nome' => 'utente',
				'ip' => '',
				'password' => '',
				'admin' => 1,
				'status' => 1,
			),
	);
define("DB_TABLE_DEFAULT_VALUES", json_encode($db_table_default_values));

$default_options = array(
  array('nome' => 'nome-evento','valore' => 'Sagra Paesana','descrizione' => 'Nome dell\'evento in questione.'),
  array('nome' => 'password-main','valore' => 'sagra','descrizione' => 'La password comune per accedere ai servizi standard.'),

  array('nome' => 'progressivo-custom','valore' => '0','descrizione' => 'Se impostato la numerazione del progressivo della sessione avviata partir&agrave; da questo numero.'),
  array('nome' => 'progressivo-max','valore' => '1000','descrizione' => 'Il numero massimo di prograssivo della ricevuta. Oltre questo numero ripartir&agrave; dall\'inizio.'),
  array('nome' => 'tavolo','valore' => '0','descrizione' => 'Abilita il campo del tavolo e lo rende obbligatorio.'),
  array('nome' => 'cliente','valore' => '0','descrizione' => 'Abilita e rende obbligatorio inserire il nome del cliente.'),
  
  array('nome' => 'categorie-pieno-schermo','valore' => '1','descrizione' => 'Determina se nella schermata della cassa le colonne delle categorie assumono tutta l\'altezza a disposizione.'),
  array('nome' => 'max-colonne-cassa-rapida','valore' => '4','descrizione' => 'Numero massimo di colonne per poter abilitare la visualizzazione di tutti i prodotti in contemporanea.'),
  
  array('nome' => 'prodotti-surgelati','valore' => '1','descrizione' => 'Abilita avviso che alcuni prodotti sono surgelati.'),
  array('nome' => 'testo-footer-ricevuta','valore' => '* In mancanza di prodotto fresco sarà utilizzato prodotto surgelato.<br />Il Comitato organizzativo ringrazia quanti hanno partecipato alla manifestazione ed augura buon appetito.','descrizione' => 'Testo opzionale da utilizzare nel footer della ricevuta al cliente.'),
  
  array('nome' => 'asporto-stampa','valore' => '0','descrizione' => 'Abilita la stampa di TUTTE le ricevute prodotte nella stampate della cassa.'),  
  array('nome' => 'asporto-automatico','valore' => '1','descrizione' => 'Attiva automaticamente il pulsante di asporto per le casse impostate come tali'),
  array('nome' => 'sessione-automatica','valore' => '1','descrizione' => 'Avvia automaticamente una nuova sessione nel momento in cui una prima cassa effettua accesso'),
  
  array('nome' => 'usa-ssh','valore' => '0','descrizione' => 'Abilitare i comandi da SSH (es. computer remoto). Richiede che siano configurate le credenziali di accesso nel file di configurazione.'),  
);
define("DEFAULT_OPTIONS", json_encode($default_options));

?>
