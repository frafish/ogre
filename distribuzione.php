<?php
include("CONFIG.php");
include_once("lib/dbconnection.php");
$pubblicita = true;

$id_reparto = (isset($_GET['id'])) ? $_GET['id'] : 1;
$reparto = find('reparti', $id_reparto);
?>
<html>
<head>
<title>DISTRIBUZIONE <?php echo $reparto['nome']; ?></title>

<link rel="stylesheet" type="text/css" href="css/bootstrap.min.css" />
<link rel="stylesheet" type="text/css" href="css/base.css" />
<script src="js/jquery-1.11.1.min.js"></script>

<?php
if(is_user()) { // controllo se l'utente � autenticato
?>
<script>
	var time = new Date();
	var t = 1;

	jQuery(document).ready(function () {
		//alert("partenza!");

		// faccio sparire il piatto, lo aggiungo alla lista dei recenti e notifico in db
		jQuery(document).on("click", ".piatti", function() {
			  //$(this).slideUp();
			  var pezzi = $(this).attr("id").split("-");
			  var id = pezzi[1];
			  //alert("click "+id);
			  jQuery.ajax({
		  			url: "ajax/piatto.php?stato=ritirato&reparto=<?php echo $id_reparto; ?>", // notifico che il piatto � stato ritirato dall'utente
		  			type: "GET",
		 	 		data: {"id" : id},
				}).done(function(ret) {
					  sparisci(ret);
				});
			});

			// se ho premuto per sbaglio un piatto posso recuperarlo cliccando sul numero nei recenti
			jQuery(document).on("click", "#recenti li", function() {
			  var id = $(this).attr("id").split("p")[1];
			  //alert("ripristino il piatto "+id);
			  jQuery.ajax({
		  			url: "ajax/piatto.php?stato=back&reparto=<?php echo $id_reparto; ?>", // notifico che il piatto non � stato ritirato dall'utente
		  			type: "GET",
		 	 		data: {"id" : id},
				}).done(function(ret) {
				  jQuery("#p"+ret).remove();
				  jQuery("#piatto-"+ret).css("position","relative");
			  	  jQuery("#piatto-"+ret).css("left","0px");
			  	  jQuery("#piatto-"+ret).removeClass("piatto-ritirato");
				});
			});

  		updateScreen();
	});

	// aggiorno la pagina continuamente per inserire i nuovi piatti in preparazione
	function updateScreen() {
		//alert("ciao");
		var ipiatti = jQuery('.piatti').length - jQuery('.piatto-ritirato').length;
		//alert(ipiatti);
		// gestisco le dimensioni dei piatti
		if (ipiatti > 8) {
			jQuery('#main').addClass('more-8');
		} else {
			jQuery('#main').removeClass('more-8');
		}
		if (ipiatti > 15) {
			jQuery('#main').addClass('more-15');
		} else {
			jQuery('#main').removeClass('more-15');
		}


		jQuery('.piatti.preparazione').addClass('old-preparazione'); // contrassegno quelli precedenti ancora in preparazione
		// recupero la lista dei vassoi che devono ancora essere servite
		jQuery.ajax({
		  url: "ajax/piatti.php?stato=preparazione&reparto=<?php echo $id_reparto; ?>",
		  cache: false
		}).done(function( html ) {
		   //alert(html);
     		var nuovipiatti = jQuery.parseJSON(html);
			//alert(nuovipiatti);
			// per ogni testata non servita
			jQuery.each(nuovipiatti, function(id, val) {
				jQuery("#piatto-"+id).removeClass('old-preparazione');
				if (!(jQuery("#piatto-"+id).length)) {
					//alert(val);
					//var pezzi = val.split("-");
					// inserisco un nuovo piatto
	      		var pclone = '<div id="piatto-'+id+'" class="piatti preparazione"><div class="lasporto">ASPORTO</div><h1>'+val+'</h1><div class="lpronto">PRONTO!!</div></div>';
					// impiatto i dettagli corrispondenti al piatto
					jQuery('#main').append(pclone);
					jQuery("#piatto-"+id).hide().fadeIn(2000);
					/*if (parseInt(pezzi[1]) > 2) {
						jQuery('#piatto-'+id).children(".lasporto").show();
					}*/
					jQuery.ajax({
			  			url: "ajax/piatto.php?id="+id+"&stato=asporto&reparto=<?php echo $id_reparto; ?>", // verifico se il piatto � d'asporto
			  			type: "GET"
					}).done(function(ret) {
					  if (ret) {
					  	 //alert(ret);
					  	 jQuery('#piatto-'+ret).children(".lasporto").show();
					  }
					});
				}
    		});
    		// rimovo i piatti che avevo aggiunto in precedenza per sbaglio
    		jQuery('.piatti.old-preparazione').remove();
		});

		// recupero la lista delle comande in attesa di essere prese in carico
		jQuery.ajax({
		  url: "ajax/piatti.php?stato=attesa&reparto=<?php echo $id_reparto; ?>",
		  cache: false
		}).done(function( html ) {
		    //alert(html);
			//alert(nuovipiatti);
			jQuery("#coda ul").html("");
			var piattiInAttesa = jQuery.parseJSON(html);
			if (html.length && piattiInAttesa) {
				// per ogni testata non servita
				jQuery.each(piattiInAttesa, function(id, val) {
					//var pezzi = val.split("-");
					jQuery("#coda ul").append('<li id="pia'+id+'">'+val+'</li>');
	    		});
	    	}
		});


		// segnalo i vassoi pronti
		jQuery(".piatti.piatto-pronto").addClass("old-piatto-pronto");
		jQuery.ajax({
		  url: "ajax/piatti.php?stato=consegnato&reparto=<?php echo $id_reparto; ?>",
		  cache: false
		}).done(function( html ) {
		   //alert(html);
     		var piattipronti = jQuery.parseJSON(html);
			//alert(nuovipiatti);
			// per ogni testata non servita
			jQuery.each(piattipronti, function(id, val) {
				jQuery("#piatto-"+id).removeClass('old-piatto-pronto');
				if ((jQuery("#piatto-"+id).length)) {
					jQuery("#piatto-"+id).addClass("piatto-pronto").removeClass('preparazione');
				}
    		});
    		jQuery('.piatti.old-piatto-pronto').addClass('preparazione').removeClass("piatto-pronto");
		});

		// faccio sparire i vassoi ritirati
		jQuery.ajax({
		  url: "ajax/piatti.php?stato=ritirato&reparto=<?php echo $id_reparto; ?>",
		  cache: false
		}).done(function( html ) {
		   //alert(html);
     		var piattipronti = jQuery.parseJSON(html);
			//alert(nuovipiatti);
			// per ogni testata non servita
			jQuery.each(piattipronti, function(id, val) {
				if ((jQuery("#piatto-"+id).length)) {
					sparisci(id);
				}
    		});
		});

		jQuery.ajax({
			url: 'panic.txt',
			type: 'GET',
			error: function() { /*not exists*/
				jQuery('#aaaah').hide();
				jQuery('.piatti').show();
			},
			success: function(t) { /* exists */
				jQuery('#panic-time').text(t);
				jQuery('#aaaah').show();
				jQuery('.piatti').hide();
			}
		});

		t = setTimeout("updateScreen()",5000); // ogni 5 secondi
	}

	function sparisci(ret) {
	  var out = $(window).width();
	  //alert(out);
	  //$("#piatto-"+ret).css("position","relative");
	  jQuery("#piatto-"+ret).addClass('piatto-ritirato');
	  jQuery("#piatto-"+ret).animate({
    		left: "-"+out+"px",
  			}, 1500, function() {
  				if (!($("#p"+ret).length)) { // se premo sul piatto per la prima volta
  					//alert($("#p"+ret).length);
  					//$("#piatto-"+ret).removeClass("piatto-pronto");
		    		jQuery("#piatto-"+ret).css("position","absolute");
		    		var prog = $("#piatto-"+ret).children("h1").html();
		    	 	jQuery("#recenti ul").prepend('<li id="p'+ret+'">'+prog+'</li>');
			  // elimino l'ultimo piatto in coda
			 	//alert($("#recenti li").length);
			  if (jQuery("#recenti li").length > 10) { // elimino il piatto pi� anziano gi� consegnato
			  		//alert("elimino piatto-"+$("#recenti li:last").html());
			  		jQuery("#piatto-"+$("#recenti li:last").html()).remove(); // elimino il piatto
			  		jQuery("#recenti li:last").remove(); // elimino dalla lista
			  }
			}
	  });
	}

</script>


<style type="text/css">
* {
	margin: 0px;
	padding: 0px;
	cursor: url(img/smile.gif),auto;
	font-family:"Arial";
}
body{
   background-color:#333;
   /*background:url(img/bg.jpg) #A98436 no-repeat left top;
   background-size: 100%;*/
   height: 100%;
   /*overflow: hidden;*/
}
h1, h2 {
	text-align:center;
	margin: 0;
}
h2 {
	font-size: 23px;
}
.stiamo-servendo {
	color: white;
}
p {
	font-size:20px;
}

#main {
	height: 100%;
}
.piatti {
	width: 300px;
	height: 300px;
	padding: 0px;
	float: left;
	margin-left: 20px;
	margin-top: 20px;
	text-align: center;
	/*background-image: url("img/piatto.png");*/
	background-position: center center;
	background-size: 100% 100%;
	background-repeat: no-repeat;
	background-color: white;
	border: 1px solid black;
	position: relative;
	border-radius: 50%;
	/*box-shadow: 20px 20px 40px #666;*/
	/*transition: all 1s;*/
	z-index: 2;
}

.piatti h1 {
	margin-top: 40px;
	/*margin-left: -30px;*/
	padding: 0px;
	font-size: 170px;
	letter-spacing: -10px;
	text-shadow: 2px 2px #000000;
	color:#FF5722;
	margin-left: -15px;
}

.piatto-pronto {
	/*background-image: url("img/piatto-pronto.png");*/
	background-color: #009688;
	/*
	background: #c67700;
	background: -moz-radial-gradient(center, ellipse cover, #c67700 0%, #008a00 100%);
	background: -webkit-gradient(radial, center center, 0px, center center, 100%, color-stop(0%,#c67700), color-stop(100%,#008a00));
	background: -webkit-radial-gradient(center, ellipse cover, #c67700 0%,#008a00 100%);
	background: -o-radial-gradient(center, ellipse cover, #c67700 0%,#008a00 100%);
	background: -ms-radial-gradient(center, ellipse cover, #c67700 0%,#008a00 100%);
	background: radial-gradient(ellipse at center, #c67700 0%,#008a00 100%);
	filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#c67700', endColorstr='#008a00',GradientType=1 );
	*/
}
.piatto-pronto h1 {
	color: white;
}
.piatto-pronto .lpronto {
	display: block !important;
}
#piatto-vuoto {
	display: none;
}

#coda {
	float: right;
	border-left: 4px solid black;
	height: 100%;
	padding: 0 20px;
	background-color: whitesmoke;
	overflow: hidden;
}
#recenti {
	float: left;
	border-right: 4px solid black;
	height: 100%;
	padding: 0 20px;
	background-color: whitesmoke;
}

#recenti li {
	color: #009688;
	list-style: none;
	font-size: 40px;
	text-align: center;
}
#coda li {
	color:#FF5722;
	list-style: none;
	font-size: 40px;
	text-align: center;
}

.lpronto {
	font-weight: 900;
	display: none;
	font-size: 45px;
	margin-top: -35px;
	color: orange;
   text-shadow: 0 0 2px white;
}

.lasporto {
	font-weight: 900;
	display: none;
	font-size: 30px;
	position: absolute;
	top: 15px;
	left: 0px;
	width: 100%;
	text-align: center;
	color: purple;
	text-shadow: 0 0 2px white;
}


.more-8 .piatti {
	width: 200px;
	height: 200px;
}
.more-8 .piatti h1 {
	font-size: 120px;
	margin-top: 20px;
}
.more-8 .piatti .lasporto {
	font-size: 25px;
	top: 15px;
}
.more-8 .piatti .lpronto {
	font-size: 35px;
	margin-top: -20px;
}

.more-15 .piatti {
	width: 100px;
	height: 100px;
}
.more-15 .piatti h1 {
	font-size: 55px;
	letter-spacing: -1px;
	margin-top: 15px;
}
.more-15 .piatti .lasporto {
	font-size: 10px;
	top: 10px;
}
.more-15 .piatti .lpronto {
	font-size: 15px;
	margin-top: -10px;
}


.notaben {
	bottom: 0;
    font-weight: bold;
    left: 0;
    position: absolute;
    width: 100%;
    font-size: 40px;
    z-index: 10;
}

#speta {
	background-color: white;
}
#fra {
	background-color: #fff59d;
}

#aaaah {
	position: fixed;
	width: 100%;
	height: 100%;
	top: 0;
	left: 0;
	display: none;
	background-color: #ff8a65;
	font-size: 40px;
}
#aaaah h2.timetowait {
	text-align: center;

}
#aaaah h1.timetowait {
	font-size: 380px;
	line-height: 300px;
}
#aaaah h1.timetowait small {
	font-size: 60px;
	line-height: 60px;
}
#aaaah li {
	margin-left: 20px;
	list-style: circle;
}

#aaaah .remember {
	position: absolute;
	bottom: 60px;
	left: 20px;
	font-weight: bold;
}

<?php if (get_option('video-distribuzione')) { ?>
.video {
  position: absolute;
  z-index: 1;
  width: 100%;
  height: 100%;
  left: 0;
  top: 0;
}
.piatti {
	opacity: 0.8;
}
#main {
	background-color: black;
}
<?php } ?>

</style>
<?php
}
?>

</head>


<body>

<?php
if(is_user()) { // controllo se l'utente � autenticato
?>


<div class="container-fluid">
	<div class="row">
	<div id="recenti" class="col-md-1">
		<h2>Serviti di<br /> recente:</h2>
		<ul></ul>
	</div>


	<div id="main" class="col-md-10">

		<?php if (get_option('video-distribuzione')) { ?>
		<video autoplay loop muted class="video" width="300" height="150">
		  <source src="media/video-bg.mp4" type="video/mp4" />
		  <source src="media/video-bg.ogv" type="video/ogg" />
		  <source src="media/video-bg.webm" type="video/webm" />
		  Your browser doesn't support HTML5 video. Here's a <a href="#">link</a> to download the video.
		</video>
		<?php } ?>


		<h1 class="stiamo-servendo"><strong class="text-upper"><?php echo $reparto['nome']; ?></strong>: stiamo servendo...</h1>

	</div>


	<div id="coda" class="col-md-1">
		<h2>In coda:</h2>
		<ul></ul>
	</div>

</div>
</div>


	<marquee behavior="scroll" direction="left" scrollamount="4" id="speta" class="notaben">
	<?php if ($pubblicita) { ?>
		Software by Pesce Francesco
		&nbsp; - &nbsp; Ti serve un gestionale di cassa per sagra o negozio? CONTATTAMI al 3463633463 o job@pescefrancesco.it
		&nbsp; - &nbsp; Problemi col pc? CONTATTAMI al 3463633463 o job@pescefrancesco.it
		&nbsp; - &nbsp; La tecnologia di oggi � troppo complessa per te? Offro corsi privati e di gruppo, CONTATTAMI al 3463633463 o job@pescefrancesco.it
		&nbsp; - &nbsp; Vorresti avere un sito internet per la tua azienda? Realizzo siti web a prezzi convenienti, CONTATTAMI al 3463633463 o job@pescefrancesco.it
	<?php } else {?>
		La direzione vi augura buona cena.
			&nbsp; - &nbsp; Di pazienza sono armati i forti.
			&nbsp; - &nbsp; Chi ha pazienza ha quel che vuole.
			&nbsp; - &nbsp; Chi non � paziente si lamenti di s�, non della gente.
			&nbsp; - &nbsp; Chi non ha pazienza non ha pace.
			&nbsp; - &nbsp; La pazienza � la madre di tutte le virt�.
			&nbsp; - &nbsp; Senza pazienza non c'� saggezza.
			&nbsp; - &nbsp; Spesso si vince con la pazienza quel che non si pu� vincere con la violenza.
			&nbsp; - &nbsp; Keep calm e speta el to turno!
	<?php } ?>
	</marquee>

	<div id="aaaah">
		<br>
		<h2 class="timetowait">Tempo medio di attesa:</h2>
		<h1 class="timetowait"><span id="panic-time">40</span><br><small>minuti circa</small></h1>
		<br><br>
		<h1>Ci dispiace che tua stia aspettando...ma stiamo dando il massimo per servirti il prima possibile!!</h1>
		<div class="remember">
			Inoltre ricorda che:
			<ul>
				<li>questo NON � un ristorante, quindi se devi criticare almeno che siano critiche "costruttive"</li>
				<li>qui siamo tutti volontari, nessuno viene pagato per lavorare per te</li>
				<li>se pensi che stai aspettando troppo guardati attorno e comprendi da solo come mai</li>
				<li>mentre tu sei li tranquillo ad aspettare noi qui stiamo lavorando sodo</li>
				<li>noi siamo gente educata...e tu?</li>
			</ul>
		</div>
	</div>

	<?php
	} else { // accesso negato, deve autenticarsi
		echo get_login_form(basename($_SERVER['PHP_SELF']));
	}
	?>

</body>
</html>
