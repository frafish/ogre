<?php
include_once("CONFIG.php");
include_once("lib/dbconnection.php");

//var_dump($_REQUEST);
$login = false;

if (isset($_REQUEST['utente']) || isset($_REQUEST['password'])) {
	
	$user_psw = '';
	if(isset($_REQUEST['utente'])) {
		$utente = find('utenti', $_REQUEST['utente']);
		if ($utente) {
			$user_psw = $utente['password'];
		}
	}
	$admin_psw = get_option('password-main');
	
	if(isset($_REQUEST['password'])) {
		if($_REQUEST['password'] == $admin_psw || ($user_psw != '' && $user_psw == $_REQUEST['password'])) {
			
			// se mi sono loggato specificando un particolare utente
			if (isset($_REQUEST['utente'])) {
				if ($_REQUEST['utente']) {
					if($utente) {
						$login = true;
						db_query('UPDATE utenti SET ip = "'.$_SERVER['REMOTE_ADDR'].'" WHERE id = '.$utente['id']);
						set_alert('Benvenuto '.$utente['nome'].'!', 'success');
					}
					// se mi sono loggato specificando un utente
					if ($login) {
						// butto fuori eventuali altre sessioni dell'utente
						user_logout($_REQUEST['utente']);
						$_SESSION['id_utente'] = $_REQUEST['utente']; // utente attivo
						
						// se ho impostato anche la cassa nel login
						if(isset($_REQUEST['cassa'])) {
							$_SESSION['id_cassa'] = $_REQUEST['cassa'];
							$sstart = get_start_time();
							// inserisco la nuova riga aggiornando il contatore
							db_query("INSERT INTO accessi (id_utenti, act, browser, ip, id_casse, session_id) VALUES (".$_SESSION['id_utente'].", 'in', '".$_SERVER['HTTP_USER_AGENT']."', '".$_SERVER['REMOTE_ADDR']."', ".$_SESSION['id_cassa'].", '".session_id()."')");
						}
					}
				}
			} else {
				if($_REQUEST['password'] == $admin_psw) {		
					$id_utente = find_one_by('utenti','admin = 1', 'id', 'id');
					$_SESSION['id_utente'] = $id_utente; //$_REQUEST['utente']; // utente attivo
					set_alert('Benvenuto AMMINISTRATORE!', 'success');
				}
			}
			header("Location: ".$_REQUEST['go']);
			die(); 
		} else {
			set_alert('PASSWORD ERRATA, Ritenta!', 'warning');
		}	
	}
}

?>
<html>

	<head>
		<title>LOGIN</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
		<link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
		<link rel="stylesheet" type="text/css" href="css/base.css">
		<script src="js/bootstrap.min.js"></script>
	</head>

<body>
	<div class="container-fluid">
	
	<?php print_alerts(); ?>

      <form class="well login form-signin" method="post" action="">
        <h2 class="form-signin-heading text-center">LOGIN</h2>
        
        <?php if (isset($_REQUEST['go']) && $_REQUEST['go'] == 'cassa.php') { ?>
		  <label for="cassa">Cassa</label>
		  <select name="cassa" id="cassa" class="form-control" required>
			<?php
				$casse = find_by("casse", 'status = 1');
				foreach($casse as $acassa) { ?>
				  <option value="<?php echo $acassa['id']; ?>"><?php echo $acassa['nome']; ?></option>
			<?php } ?>
			</select>
			<?php } ?>
        
        <label for="utente">Utente</label>
		  <select name="utente" id="utente" class="form-control" required>
			<?php
				$utenti = find_by("utenti", 'status = 1');
				foreach($utenti as $autente) { ?>
				  <option value="<?php echo $autente['id']; ?>"><?php echo $autente['nome']; ?></option>
			<?php	} ?>
			</select>        
        
        <label for="inputPassword">Password</label>
        <input type="password" id="inputPassword" name="password" class="form-control" placeholder="Password" required>

		  <input type="hidden" name="go" value="<?php echo (isset($_REQUEST['go'])) ? $_REQUEST['go'] : '/'; ?>" > 
        <input class="btn btn-lg btn-primary btn-block mt-20" type="submit" value="Accedi"/>
      </form>
      
  	</div>
 </body>
 </html>
