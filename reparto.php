<?php
// TODO: tornare indietro se premo una comanda sbagliata
// TODO: elenco ultime comande evase con possibilità di recuperarla
include("CONFIG.php");
include("lib/dbconnection.php");

$id_reparto = $_GET['id'];
$reparto = find('reparti', $id_reparto);
?>
<html>
<head>
<title><?php echo $reparto['nome']; ?></title>

<link rel="stylesheet" type="text/css" href="css/bootstrap.min.css" />
<link rel="stylesheet" type="text/css" href="css/base.css" />
<script src="js/jquery-1.11.1.min.js"></script>

<?php
if(is_user()) { // controllo se l'utente è autenticato
?>
<script>
	
	var time = new Date();
	var t = 1;
	
	$(document).ready(function () {
		//alert("partenza!");
  		updateScreen();
		//updateList();
  		
  		// aggiungo una spunta all'ingrediente del piatto
  		$(document).on("click", ".piatti li", function(){
			//alert("piatto consegnato");
			if ($(this).children("strong").length < parseInt($(this).children("span").html())) {
		  		$(this).prepend("<strong>&radic;</strong> ");
		  		$(this).css("list-type-image","none");
		  	}
		});
		
		// avverto che il piatto è in preparazione (verrà quindi mostrato alla distribuzione)
		$(document).on("click", ".ordini .preparazione", function(){
	  		// chiamata ajax per salvare la dataora di uscita del piatto
	  		var pezzi = $(this).parent().attr("id").split("-");
	  		var id = pezzi[1];
	  		//alert(id);
	  		$.ajax({
	  			url: "ajax/piatto.php?stato=preparazione&reparto=<?php echo $reparto['id']; ?>",
	  			type: "GET",
	 	 		data: {"id" : id},
			}).done(function(ret) { 
	  			//alert(ret);
	  			//alert($(this).parent().attr("id"));
	  			$("#ordine-"+ret).addClass("ordine-preparazione");
	  			//$("#ordine-"+ret+" .consegnato").css("display", "block");
			});
			//$(this).remove();

		});
		
		// mostro il contenuto del vassoio (l'elenco dei piatti)
		$(document).on("click", ".ordini .visualizza", function(){
	  			$(this).parent().children(".vassoi").toggle();
			//$(this).remove();
		});
		
		// avverto che il vassoio è pronto per la consegna (il piatto diventerà verde in distribuzione)
		$(document).on("click", ".ordini .consegnato", function(){
	  		// chiamata ajax per salvare la dataora di uscita del piatto
	  		var pezzi = $(this).parent().attr("id").split("-");
	  		var id = pezzi[1];
	  		//alert(id);
	  		$.ajax({
	  			url: "ajax/piatto.php?stato=consegnato&reparto=<?php echo $reparto['id']; ?>",
	  			type: "GET",
	 	 		data: {"id" : id},
			}).done(function(ret) { 
	  			//alert(ret);
	  			//alert($(this).parent().attr("id"));
	  			$("#ordine-"+ret).addClass("ordine-consegnato");
	  			//$("#ordine-"+ret+" .ritirato").show();
			});
			//$(this).remove();

		});
		
		$(document).on("click", ".ordini .ritirato", function() {
		  //$(this).slideUp()
		  var pezzi = $(this).parent().attr("id").split("-");
	  	  var id = pezzi[1];
	  	  $.ajax({
	  			url: "ajax/piatto.php?stato=ritirato&reparto=<?php echo $reparto['id']; ?>", // notifico che il piatto è stato ritirato dall'utente
	  			type: "GET",
	 	 		data: {"id" : id},
			}).done(function(id) {
				  var prog = jQuery("#ordine-"+id).find('h1').text();
				  jQuery("#recenti ul").prepend('<li class="ml-5 pull-left" id="p-'+id+'"><a class="btn btn-sm btn-warning back" onClick="jQuery(this).hide();" href="#">'+prog+'</a></li>');
				  if (jQuery("#recenti li").length > 10) { // elimino il piatto più anziano già consegnato
				  		jQuery("#recenti li:last").remove(); // elimino dalla lista
				  } 
				  //var out = $(window).width();
				  //alert(out);
				  //$("#ordine-"+id).animate({
			      //	left: "-"+out+"px",
		  		  //}, 1500).slideUp(2000, function() {
				    	$("#ordine-"+id).remove();
				    	updateList();
				  //});
			});  
		});
		
		$(document).on("click", ".ordini .back, #recenti .back", function() {
		  //$(this).slideUp()
		  var prog = jQuery(this).text();
		  var pezzi = $(this).parent().attr("id").split("-");
	  	  var id = pezzi[1];
	  	  $.ajax({
	  			url: "ajax/piatto.php?stato=back&reparto=<?php echo $reparto['id']; ?>", // notifico che il piatto è stato ritirato dall'utente
	  			type: "GET",
	 	 		data: {"id" : id},
			}).done(function(id) {
			    	if (jQuery("#ordine-"+id).hasClass('ordine-consegnato')) {
			    		jQuery("#ordine-"+id).removeClass('ordine-consegnato');
			    	} else {
			    		if (jQuery("#ordine-"+id).hasClass('ordine-preparazione')) {
			    			jQuery("#ordine-"+id).removeClass('ordine-preparazione');
			    		}
			    	}
			    	
					if (!($("#ordine-"+id).length)) { // se il piatto non è già presente
						// inserisco un nuovo piatto
						//alert("Inserico il piatto "+id+" con progressivo "+val);
		      		var pclone = $('#ordine-vuoto').clone();
						jQuery(pclone).attr("id","ordine-"+id);
						jQuery(pclone).children(".vassoi").attr("id","vassoio-"+id);
						jQuery(pclone).children("h1").text(prog);
						// impiatto i dettagli corrispondenti al piatto
						jQuery(pclone).children(".vassoi").load("ajax/dettagli-piatti.php", { "id": id, "reparto": <?php echo $reparto['id']; ?> }, function(){
							//alert("The last 25 entries in the feed have been loaded");
						});
						pclone.prependTo('#ordini');
					}
					
			    	updateList();
			});  
		});
		
		// mostro che il piatto è consegnato
		$(document).on("click", ".piatti .impiattato", function(){
	  		$(this).parent().css("background-color","green");
			//$(this).remove();
		});
		
		// mostro tutti i vassoi
		$(document).on("click", "#expand-all", function(){
	  		$('.vassoi').show();
		});
		// contraggo tutti i vassoi
		$(document).on("click", "#close-all", function(){
	  		$('.vassoi').hide();
		});
		
		
		// mostro i vassoi in cui è presente il piatto
		$(document).on("click", ".litodo", function(){
			$(".vassoi").hide();
	  		var id = $(this).attr("id").split("-")[1];
	  		//alert(id);
	  		$('.pnome-'+id).each(function() {
	  			//alert(id);
	        	$(this).parent().parent().parent().show();
	    	});
		});
		
	});

	// aggiorno la schermata (prima ottengo il numero dei vassoi che contengo prodotti del reparto e poi ci carico il loro contenuto)
	function updateScreen() {
		$.ajax({
		  url: "ajax/piatti.php?stato=all&reparto=<?php echo $reparto['id']; ?>&full_json=1",
		  cache: false
		}).done(function( html ) {
     		var nuovipiatti = jQuery.parseJSON(html);
			jQuery.each(nuovipiatti, function(id, data) {
				var val = data.progressivo;
				if (!($("#ordine-"+id).length)) {
	      			var pclone = $('#ordine-vuoto').clone();
					$(pclone).attr("id","ordine-"+id);
					$(pclone).children(".vassoi").attr("id","vassoio-"+id);
					$(pclone).children("h1").html(val);
					$(pclone).children(".vassoi").html(data.html);
					if (data.asporto == 1) {
					  	 jQuery(pclone).children(".lasporto").show();	
					}
					pclone.appendTo('#ordini');
				}
				if (data.stato_preparazione == 1) {
					$("#ordine-"+id).addClass("ordine-preparazione");
				}
				if (data.stato_consegnato == 1) {
					$("#ordine-"+id).addClass("ordine-consegnato");
				}
    		});
    		jQuery('#n-comande').val(jQuery('.ordini').length - 1);
	    	jQuery('#n-preparazione').val(jQuery('.consegnato:visible').length);
    		jQuery('#n-consegnato').val(jQuery('.ritirato:visible').length);
		});
		
		updateList();
		t = setTimeout("updateScreen()",10000);
	}
	
	function updateList() {
		//alert("aggiorno lista");
		$("#todo").empty();
		$('.pqnt').each(function() {
			var id = $(this).attr("id").split("-")[1];
			//alert(id);
        	if ($("#ptodo-"+id).length) {
        		$("#ptq-"+id).html(parseInt($("#ptq-"+id).html()) + parseInt($(this).html()));
        	} else {
        		var nome = $(".pnome-"+id).first().html().split("(")[0];
        		var qnt = $(this).html();
        		//alert(qnt);
        		$("#todo").append('<li id="ptodo-'+id+'" class="litodo"><span id="ptq-'+id+'">'+qnt+'</span> '+nome+'</li>');
        	}
    	});
	}

</script>


<style type="text/css">
* {
	padding: 0px;
	margin: 0px;	
}

.ml-10 {
	margin-left: 10px;
}

body{
	background-color: #333333;
	padding: 10px 0;
}

h1{
	/*color: #C71585;*/
	color: whitesmoke;
	text-align:left;
	margin-top: -10px;
	font-size: 50px;
	text-shadow: 2px 2px #000000;
	text-transform: uppercase;
}


.ordini {
	padding: 10px;
	margin-top: 30px;
	text-align: center;
	background-color: #699;
	border-top: 5px solid white;
	position: relative;
}

.vassoi {
	background-color: black;
	border: 2px solid silver;
	border-radius: 20px;	
	padding: 10px;
	text-align: left;
	display: none;
	clear: both;
	position: relative;
	overflow: hidden;
}

.piatti {
	/*float: left;*/
	display: inline-block;
	text-align: left;
	background-color: whitesmoke;
	border-radius: 20px;	
	padding: 10px;
	margin: 5px;
	vertical-align: top;
}

.piatti h3 {
	border-bottom: 2px solid red;
	margin: 0;
}

.piatti ul, #recenti ul {
	margin: 0px;
	padding: 0px;
	list-style: none;	
}

.piatti li {
	margin: 0px;
	border-bottom: 1px solid black;
	padding: 0;	
	cursor: pointer;	
}

.piatti li strong {
	color: green;
	font-weight: 900;	
}

.note {
	float: right;
	max-width: 250px;
	padding: 10px;
	background-color: #FFFF99;
}

h1, h2, h3 {
	margin-top: 0;
}

#ordine-vuoto {
	display: none;
}

.ordini {
	position: relative;
}

.ordini .lasporto {
	position: absolute;
	top: -10px;
	left: 0;
	display: none;
	background-color: #eee;
	font-size: 10px;
	padding: 2px;
}

.ordini button {
	font-size: 30px;	
	/*display: block;*/	
	float: right;
	cursor: pointer;
	margin-left: 10px;
	margin-bottom: 10px;
}
.ordini .consegnato {
	display: none;	
}
.ordini .ritirato {
	display: none;
}
.ordini .back {
	display: none;
}

#todo li {
	margin-bottom: 5px;
	list-style: none;	
	cursor: pointer;
}

.impiattato {
	display: none;
}

.stat {
	width: 30px;
}

.back-to-top {
	display: block;
	margin: 0;
	position: fixed;
	bottom: 0;
	right: 0;
	z-index: 100;
	text-decoration: none; 
}

.reprint {
	position: absolute;
	right: 0;
	bottom: 0;
}

.ordine-preparazione .preparazione, .ordine-preparazione .ritirato {
	display: none;
}
.ordine-preparazione .consegnato, .ordine-preparazione .back {
	display: block;
}

.ordine-consegnato .preparazione, .ordine-consegnato .consegnato {
	display: none;
}
.ordine-consegnato .ritirato {
	display: block;
}

</style>
<?php } ?>
</head>

<body>
<?php
if(is_user()) { // controllo se l'utente è autenticato
?>
<div class="container-fluid">
<div class="row"> 
	
	<div id="main" class="col-md-9">
		
		<a href="/" class="btn btn-default btn-lg btn-back pull-left p-15 mr-20"><span class="glyphicon glyphicon-home" aria-hidden="true"></span></a>
		<h1><?php echo $reparto['nome']; ?></h1>
		
		<div class="row">
			<div class="col-md-6">			
				<div id="recenti" class="well p-10 clearfix">
					<h3 class="pull-left m-0">Recenti:</h3>
					<ul class="no-list pull-left"></ul>
				</div>
			</div>
			<div class="col-md-6">
				<div class="well p-10 text-right">
					<label for="n-comande"><abbr title="Numero totale di comande ancora da servire">Comande</abbr>:</label> <input type="text" class="stat" id="n-comande" value="0">
					&nbsp;|&nbsp;
					<label for="n-preparazione"><abbr title="Numero di comande prese in carico dal reparto competente">In preparazione</abbr>:</label> <input type="text" class="stat" id="n-preparazione" value="0">
					&nbsp;|&nbsp;
					<label for="n-consegnato"><abbr title="Numero di comande concluse dal reparto competente e consegnate alla distrubuzione">Consegnate</abbr>:</label> <input type="text" class="stat" id="n-consegnato" value="0">
				</div>
			</div>
		</div>
		
		
		<div id="ordine-vuoto" class="ordini">
			<div class="lasporto">ASPORTO</div>
			<button class="btn btn-default visualizza">Visualizza</button>
			<button class="btn btn-warning btn-only-one preparazione">Presa in carico...</button> 		
			<button class="btn btn-primary btn-only-one consegnato">...in distribuzione...</button> 			
			<button class="btn btn-success btn-only-one ritirato">...RITIRATO!</button>
			<button class="btn btn-danger btn-xs back"><span class="glyphicon glyphicon-erase" aria-hidden="true"></span></button>
			<h1>123</h1>
			<div style="clear: both;"></div> 
			<div id="vassoio-vuoto" class="vassoi"></div>			
		</div>
		
		<div id="ordini"></div>
	</div>
	
	<div id="todo-lista" class="col-md-3">
		<div class="well">
			<a class="btn btn-primary pull-right ml-10" id="expand-all" href="#" title="Espandi tutte le comande"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></a>
			<a class="btn btn-primary pull-right" id="close-all" href="#" title="Contrai tutte le comande"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></a>		
			<h2>Da fare:</h2>
			<ul id="todo"></ul>	
		</div>
	</div>
	
	<a class="btn btn-primary back-to-top" href="#">
		<span class="glyphicon glyphicon-eject" aria-hidden="true"></span>
	</a>
	
</div>
</div>

<?php
} else { // accesso negato, deve autenticarsi
	echo get_login_form(basename($_SERVER['PHP_SELF']).'?'.$_SERVER['QUERY_STRING']);
}
?>


</body>
</html> 


