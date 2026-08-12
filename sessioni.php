<?php
include ("CONFIG.php");
include ("lib/dbconnection.php");

if(isset($_REQUEST['session'])) {
	$admin = find_one_by('utenti', 'admin = 1');
	//echo $sdo; die();
	if ($_REQUEST['session']) {
		db_query("INSERT INTO accessi (id_utenti, id_casse, act, time) VALUES(".$admin['id'].", 0, 'in', '".date('Y-m-d H:i:s')."')");
		set_option('progressivo-custom', $_REQUEST['progressivo']);
		set_option('sessione_attiva', 1);
		set_option('inizio_sessione', date('Y-m-d H:i:s'));
	} else {
		// forzo il logout di tutti gli utenti connessi
		$accessi = get_active_users();
		//var_dump($accessi);
		if(!empty($accessi)) {
			foreach($accessi as $accesso) {	
				user_logout($accesso['id_utenti']);
			}
		}
		$sstart = get_start_time();
		db_query("INSERT INTO accessi (id_utenti, id_casse, act, time) VALUES(".$admin['id'].", 0, 'stop', '".date('Y-m-d H:i:s')."')");
		set_option('progressivo-custom', 0);
		set_option('sessione_attiva', 0);
		
		// ora che ho chiuso tutto eseguo un backup del db
		if (file_exists(DB_FILE)) {
			$bk_dir = dirname(DB_BACKUP);
			if (!is_dir($bk_dir)) {
				mkdir($bk_dir);
			}
			$bk_name = 'ogre_'.slugify($giorni_settimana[date("w",strtotime($sstart))]).'_'.date('Y-m-d_h-i-s').'_db.sqlite';
			$bk_copy = $bk_dir.DIRECTORY_SEPARATOR.$bk_name;
			dev_log('Fine sessione, effettuo backup in: '.$bk_copy);
			if(copy(DB_FILE, $bk_copy)) {
				set_alert('Sessione terminata correttamente', 'success');
			}
		}
	}	
}

if(isset($_REQUEST['azione'])) {
    if ($_REQUEST['azione'] == 'unisci' && isset($_REQUEST['id_stop'])) {
        $id_stop = intval($_REQUEST['id_stop']);
        if ($id_stop > 0) {
            db_query("DELETE FROM accessi WHERE id = ".$id_stop." AND act = 'stop' AND id_casse = 0");
            set_alert('Serate unite correttamente. Il marcatore di chiusura &egrave; stato rimosso.', 'success');
        }
    }
    
    if ($_REQUEST['azione'] == 'dividi' && isset($_REQUEST['data_stop'])) {
        $data_stop = $_REQUEST['data_stop'];
        $admin = find_one_by('utenti', 'admin = 1');
        if (strtotime($data_stop) !== false) {
            $formatted_date = date('Y-m-d H:i:s', strtotime($data_stop));
            db_query("INSERT INTO accessi (id_utenti, id_casse, act, time) VALUES(".$admin['id'].", 0, 'stop', '".$formatted_date."')");
            set_alert('Nuovo marcatore di chiusura (STOP) inserito correttamente. Le serate sono state divise.', 'success');
        } else {
            set_alert('Formato data non valido.', 'danger');
        }
    }
}

// l'ultima sessione	
$sstart = get_start_time();
$ssend = get_real_end_time();
$utenti = find_by('utenti');

//echo $sstart." - ".$ssend;
?>
<html>
<head>
	<title>UTENTI</title>
	<link rel="stylesheet" type="text/css" href="css/bootstrap.min.css" />
	<link rel="stylesheet" href="css/font-awesome.min.css">
	<link rel="stylesheet" href="css/base.css">
</head>


<body>

<div class="container-fluid">

	<a href="/" class="btn btn-default btn-lg btn-back pull-left absolute"><span class="glyphicon glyphicon-backward" aria-hidden="true"></span> Indietro</a>
	<h1 class="title">SESSIONE & UTENTI</h1>

<?php
if(is_user()) { // controllo se l'utente è autenticato
//echo "<h1>".session_id()."</h1>"; 
?>

<?php print_alerts(); ?>

<form method="post" action="">
<?php if (!is_session_started()) { ?>
	<input type="hidden" value="1" name="session">
	<input type="submit" class="btn btn-lg btn-block btn-success p-20" value="START" />
	<div class="well">Inizia con il progressivo <input class="form-control input-100" type="text" name="progressivo" value="<?php echo get_next_progressivo(); ?>"> fino al massimo <?php echo get_option('progressivo-max'); ?>, dopodich&egrave; riparte da 1.</div>
<?php } else { ?>
	<input type="hidden" value="0" name="session">
	<input type="submit" class="btn btn-lg btn-block btn-danger p-20" value="STOP" />
<?php } ?>
</form>

<?php
$accessi = get_active_users();
if (!empty($accessi)) { ?>
	<h3>Utenti collegati ora: <?php echo count($accessi); ?></h3>
	<ul class="list-group">
	<?php
	foreach($accessi as $accesso) { ?>				
		<li class="list-group-item clearfix">
			<strong><?php echo isset($utenti[$accesso['id_utenti']]) ? $utenti[$accesso['id_utenti']]['nome'] : $accesso['id_utenti']; ?></strong>
			<abbr title="<?php echo $accesso['time']; ?>"><span class="glyphicon glyphicon-time" aria-hidden="true"></span></abbr>
			<?php if ($accesso['id_casse']) { 
				$cassa = find('casse', $accesso['id_casse']);			
			?>
				[CASSA: <?php echo $cassa['nome']; ?>]
			<?php } ?>
			<a class="btn btn-xs btn-warning" href="logout.php?id_utente=<?php echo $accesso['id_utenti']; ?>">LOGOUT</a>	
		</li>		
		<?php
	}
	?>
	</ul>

	<?php $begin = get_real_start_time(); ?>
	<h3>Sessione iniziata: <?php echo $giorni_settimana[date('w', strtotime($begin))]; ?> <?php echo $begin; ?></h3>
	<?php if(get_real_end_time($sstart)) { ?>
		<h4>Sessione precedente terminata: <?php echo get_real_end_time($sstart); ?></h4>
	<?php } ?>
<?php }	?>

<hr>
<h2>Modifica manuale Storico Sessioni</h2>
<div class="well">
    <p>Qui puoi vedere tutte le chiusure cassa storiche. Se hai dimenticato di premere STOP a fine serata o l'hai premuto per sbaglio, puoi unire o dividere le serate storiche da qui.</p>
    <ul class="list-group">
    <?php
    $stops = find_by('accessi', "id_casse = 0 AND act = 'stop'", 'time DESC');
    if ($stops) {
        foreach($stops as $stop) { ?>
            <li class="list-group-item clearfix">
                <strong>Chiusura Cassa:</strong> <?php echo $stop['time']; ?> 
                <a class="btn btn-xs btn-danger pull-right" href="sessioni.php?azione=unisci&id_stop=<?php echo $stop['id']; ?>" onclick="return confirm('Sei sicuro di voler unire la serata precedente con quella successiva a questo stop?');"><span class="glyphicon glyphicon-remove" aria-hidden="true"></span> UNISCI (Rimuovi Stop)</a>
            </li>
        <?php }
    } else {
        echo "<p>Nessuno stop registrato.</p>";
    }
    ?>
    </ul>
    
    <h4>Dividi una Serata (Inserisci Stop Manuale)</h4>
    <form class="form-inline mt-10" method="post" action="sessioni.php">
        <input type="hidden" name="azione" value="dividi">
        <div class="form-group">
            <label>Data e Ora della divisione (es. 04:00 di mattina): </label>
            <input type="datetime-local" class="form-control ml-10" name="data_stop" value="<?php echo date('Y-m-d\T04:00'); ?>">
        </div>
        <button type="submit" class="btn btn-primary ml-10">DIVIDI (Inserisci Stop)</button>
    </form>
</div>
	
<?php
} else { // accesso negato, deve autenticarsi
	echo get_login_form(basename($_SERVER['PHP_SELF']));
}
?>		
	
</div>
</body>

</html>
