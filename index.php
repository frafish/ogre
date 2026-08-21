<?php
include("CONFIG.php");
include("lib/dbconnection.php");
?>
<html>

<head>

<title>Gestionale Sagra</title>

<link rel="stylesheet" type="text/css" href="css/bootstrap.min.css" />
<link rel="stylesheet" href="css/font-awesome.min.css">
<link rel="stylesheet" href="css/base.css">


<style type="text/css">
	body {
		background-color: whitesmoke;	
	}
	
	
	.btn > big {
		font-size: 4em;
		margin-top: 10px;	
	}
	.btn-login {
		opacity: 0.7;	
	}
	
	.main-title {
		background-color: orange;
		color: whitesmoke;
		margin: 20px 0;
		padding: 20px;
		text-transform: uppercase;	
	}
	
	.footer {
		background-color: #ddd;
		padding: 10px;
		border-top: 5px solid #ccc;
		border-bottom: 10px solid #ccc;
	}
	.footer, .footer a {
		color: #666;	
	}
	.footer a:hover {
		color: #333;	
	}
</style>

</head>

<body>

<h1 class="main-title text-center"><strong>O</strong>pen Sa<strong>gre</strong></h1>

<div class="container">

	<?php print_alerts(); ?>

	<ul class="row">
		<?php if (db_count('prodotti')) { ?>
			<?php if (is_session_started() || get_option('sessione-automatica')) { ?>
				<?php if (db_count('utenti', 'status = 1') && db_count('casse', 'status = 1')) { ?>
					<li class="col-md-6"><a class="btn btn-lg btn-default btn-full text-upper hover-green mb-20" href="cassa.php">Cassiere<br /><big><i class="fa fa-money"></i></big></a></li>
				<?php } ?>
			<?php } ?>
			
			<li class="col-md-6"><a class="btn btn-lg btn-default btn-full text-upper hover-red mb-20" href="griglia.php">Griglie<br /><big><i class="fa fa-fire"></i></big></a></li>
			
			<?php
			$reparti_con_attesa = find_by('reparti', 'fila = 1');
			foreach ($reparti_con_attesa as $reparto) { ?>
				<li class="col-md-6"><a class="btn btn-lg btn-default btn-full text-upper mb-20" href="distribuzione.php?id=<?php echo $reparto['id']; ?>">Distribuzione <?php echo $reparto['nome']; ?><br /><big><i class="fa fa-hourglass asd-fa-spinner asd-fa-spin"></i></big></a></li>
				<li class="col-md-6"><a class="btn btn-lg btn-default btn-full text-upper mb-20" href="reparto.php?id=<?php echo $reparto['id']; ?>"><?php echo $reparto['nome']; ?><br /><big><i class="fa fa-cutlery"></i></big></a></li>
			<?php } ?>
		<?php } ?>
	</ul>
	
	<ul class="row">
		<?php if (is_user()) { ?>
			<li class="col-md-12"><a class="btn btn-lg btn-warning btn-block btn-login mb-20" href="logout.php"><span class="glyphicon glyphicon-log-out" aria-hidden="true"></span> LogOUT</a></li>
		<?php } else { ?>
			<li class="col-md-12"><a class="btn btn-lg btn-primary btn-block btn-login mb-20" href="login.php"><span class="glyphicon glyphicon-log-in" aria-hidden="true"></span> LogIN</a></li>
		<?php } ?>
	</ul>
	
	<ul class="row">
		<?php if (is_user()) { ?>
			<li class="col-md-2"><a class="btn btn-lg btn-<?php if (db_count('prodotti')) { ?>success<?php } else { ?>warning<?php } ?> btn-block mb-20" href="prodotti.php">Prodotti<br /><big><i class="fa fa-beer"></i><span class="hidden glyphicon glyphicon-apple" aria-hidden="true"></span></big></a></li>
			<?php if (db_count('prodotti')) { ?>
				<li class="col-md-2"><a class="btn btn-lg btn-<?php if (db_count('magazzino')) { ?>success<?php } else { ?>warning<?php } ?> btn-block mb-20" href="magazzino.php">Magazzino<br /><big><i class="fa fa-shopping-basket"></i></big></a></li>
			<?php } ?>
		<?php } ?>
		
		<?php if (is_superman()) { ?>
			<li class="col-md-2"><a class="btn btn-lg btn-<?php if (db_count('reparti', 'fila = 1') && db_count('utenti', 'status = 1') && db_count('casse', 'status = 1')) { ?>success<?php } else { ?>warning<?php } ?> btn-block mb-20" href="impostazioni.php">Impostazioni<br /><big><i class="fa fa-cog"></i></big></a></li>
			<li class="col-md-2"><a class="btn btn-lg btn-danger btn-block mb-20" href="db.php">Database<br /><big><i class="fa fa-database"></i></big></a></li>
            <?php 
            $libs_missing = !file_exists(__DIR__.'/lib/tcpdf/tcpdf.php') || !file_exists(__DIR__.'/lib/SumatraPDF.exe') || !file_exists(__DIR__.'/lib/Mobile_Detect.php');
            $lib_btn_class = $libs_missing ? 'btn-danger' : 'btn-info';
            ?>
			<li class="col-md-2"><a class="btn btn-lg <?php echo $lib_btn_class; ?> btn-block mb-20" href="aggiorna_librerie.php">Librerie<br /><big><i class="fa fa-refresh"></i></big></a></li>
		<?php } ?>
		
		<li class="col-md-2 hidden"><a class="btn btn-lg btn-primary btn-block mb-20" href="prenota.php">Prenota<br /><big><i class="fa fa-ticket"></i></big></a></li>
		
		<?php if (is_superman()) { ?>
			<?php if (db_count('prodotti')) { ?>
				<li class="col-md-2"><a class="btn btn-lg btn-primary btn-block mb-20" href="sessioni.php">Sessioni<br /><big><i class="fa fa-users"></i></big></a></li>
			<?php } ?>
		<?php } ?>
		
		<?php if (db_count('testate')) { ?>
			<li class="col-md-2"><a class="btn btn-lg btn-primary btn-block mb-20" href="storico.php"><?php /*echo db_count('testate');*/ ?> Storico<br /><big><i class="fa fa-history"></i></big></a></li>
			<li class="col-md-2"><a class="btn btn-lg btn-primary btn-block mb-20" href="statistiche.php">Statistiche<br /><big><i class="fa fa-line-chart"></i></big></a></li>
		<?php } ?>
						
		<?php if (is_superman()) { ?>
			<li class="col-md-2"><a class="btn btn-lg btn-danger btn-block mb-20" href="panic.php">Panico!<br /><big><i class="fa fa-bomb"></i></big></a></li>
		<?php } ?>
	
	</ul>

<?php 
if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
    $ip = $_SERVER['HTTP_CLIENT_IP'];
} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
} else {
    $ip = $_SERVER['REMOTE_ADDR'];
}
?>
	<div class="well well-white text-center">
		<label>Tuo IP:</label> <?php echo $ip; ?> 
		- 
		<label>Server IP:</label> <?php echo $_SERVER['SERVER_ADDR']; ?> 
		- 
		<label>Piattaforma:</label> <?php echo PHP_OS; ?>
		- 
		<label>OGRE:</label> v1.0.1
	</div>

</div>

<div class="footer text-center"><span class="glyphicon glyphicon-copyright-mark" aria-hidden="true"></span> <?php echo date('Y'); ?> OGRE - <a href="http://www.pescefrancesco.it" target="_blank"><span class="glyphicon glyphicon-globe" aria-hidden="true"></span> CREDITS</a> - <a href="mailto:job@pescefrancesco.it"><span class="glyphicon glyphicon-envelope" aria-hidden="true"></span> SUPPORTO</a></div>
</body>

</html>