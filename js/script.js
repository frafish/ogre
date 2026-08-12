// we will add our javascript code here  
$(document).ready(function(){
	
	reset(); // mi assicuro che tutti i campi siano svuotati
	$("table").hide();
	$("table").first().show(); // mostra solo la prima tabella
	 
	// MODIFICAZIONI DI STILE
	bottoni_mostra(); alert('alt');
	
	// faccio scomparire la finestra
	$("#subito_trasparenza").click(function(){
		$("#subito").hide();
		return false;
	});
	
	// evito di dover reinserire il tipo di ordine
	$("#subito-tipoordine").change(function () {
		//alert($("#subito-tipoordine").val() +"-"+ $("#tipoordine").val());
        $("#tipoordine").val($("#subito-tipoordine").val());
    });
	
	// tipo ordine comporta dei cambiamenti
	$("#tipoordine").change(function () {
		//alert("potresti non pagare");
		if($(this).val() > 2) { // le voci ASPORTO devono avere id > 2 <-------------------------------------------------------
			$("#quantita-coperti").val("0");
		} else {
			$("#quantita-coperti").val("0");
		}		
		update_totale();
    });
	
	// evito di dover reinserire il numero di coperti
	$("#quantita-subitocoperti").change(function () {
		//alert($("#subito-tipoordine").val() +"-"+ $("#tipoordine").val());
        $("#quantita-coperti").val($("#quantita-subitocoperti").val());
    });
	
	// confermo la scelta della finestra inziale
	$("#conferma-subito").click(function(){
	  $("#subito").hide();
	  $("#quantita-coperti").val($("#quantita-subitocoperti").val());
	  update_subtot("coperti");
	  return false;
	});
	
	// mostra solo la tabella scelta
	$('.mostra').click(function(){
	  $('.mostra').removeClass("active-mostra");
	  $(this).addClass("active-mostra");
	  var idbutton = $(this).attr("id");
	  var tts = idbutton.split("-")[1];
	  $("table").hide();
	  //alert("#"+tts);
	  $("#"+tts).show();
	  return false;
	});
	
	// azzera tutti i campi possibili
	$("#reset").click(function(){
	  reset();
	  return false;
	});
	
	//$(":button").css({background:"yellow". border:"3px red solid"});
	/*$('[type="button"]').click(function(){
		alert($(this).attr("id"));
	}*/
	
	// pulsanti per aumentare le quantità
	$('[id^="aumenta"]').click(function(){
	  var idbutton = $(this).attr("id");
	  var id = idbutton.split("-")[1];
	  var valorecorrente = $("#quantita-"+id).val();
	  valorecorrente++;
	  //alert(valorecorrente);
	  $("#quantita-"+id).val(valorecorrente);
	  update_subtot(id);
	  return false;
	});
	
	// pulsanti per diminuire le quantità
	$('[id^="diminuisci"]').click(function(){
	  var idbutton = $(this).attr("id");
	  var id = idbutton.split("-")[1];
	  var valorecorrente = $("#quantita-"+id).val();
	  if (valorecorrente > 0) {
		valorecorrente--;
		$("#quantita-"+id).val(valorecorrente);
		}
	  update_subtot(id);
	  return false;
	});
	
	// pulsanti della calcolatrice
	$('[id^="calcolatrice"]').click(function(){
	  var idbutton = $(this).attr("id");
	  var id = idbutton.split("-")[1];
	  if (id == "reset") { $("#versato").text("0.00"); }
	  else {
		var valorecorrente = new Number(parseFloat($("#versato").text()));
		valorecorrente += parseFloat(id);
		$("#versato").text(valorecorrente.toFixed(2).toString());
	  }
	  update_rimanente();
	  return false;
	});
	
	// controllo che non ci sia stato un inserimento diretto della quantità
	$('[id^="quantita"]').change(function () {
		//alert("Ciao");
        var idbutton = $(this).attr("id");
		var id = idbutton.split("-")[1];
        update_subtot(id);
    });
	
	// pressione tasto per concludere l'ordine
	$("#nuovo-ordine").click(function(){
		//alert("Nuovo ordine");
		if (parseFloat($("#versato").text()) < parseFloat($("#totale").text())) {
			alert("L'UTENTE NON HA PAGATO?!?");
			return false;
		}
		var dettagli = "";
		$(".cod").each(function() {
			var cod = $(this).text();
			//alert(id);
			if ($("#quantita-"+cod).val() > 0) {
				dettagli = dettagli + cod + "," + $("#quantita-"+cod).val() +  ";" ;
			}
		});
		$("#oordine").val(dettagli);
		$("#opagato").val($("#versato").text());
		$("#ototale").val($("#totale").text());
		$("#ocoperti").val($("#quantita-coperti").val());
		$("#onote").val($("#note-cucina").val());
		$("#otipo").val($("#tipoordine").val());
		$("#ostampante").val($("#selstampante").val());
		//alert(dettagli);
	  //return false;
	});
	
	// STATISTICHE
	$('[id^="carica"]').click(function(){
		//alert("Caricamento pagina esterna");
		var idbutton = $(this).attr("id");
		var id = idbutton.split("-")[1];
		var prog = "";
		if (id == "storico") {
			prog = "?prog="+$("#prog-precedente").text();
		}
		//alert(id+'.php'+prog);
		$("#subito_contenitore").load(id+'.php'+prog);
		$("#subito_contenitore").css("height","400px");
		$("#subito_contenitore").css("margin-top","-200px");
		$("#subito").show();
		return false;
	});
	
});

function update_subtot(id) {
	var n = new Number($("#quantita-"+id).val());
	//alert($("#prezzo-"+id).text());
	var prezzo = new Number(parseFloat($("#prezzo-"+id).text()));
	var subtot = new Number(n * prezzo);
	subtot = Math.round(subtot*100)/100;
	//alert("Prezzo " + n + " * " + prezzo + " = " + subtot.toString());
	$("#subtot-"+id).text(subtot.toFixed(2).toString());
	update_totale();
}

function update_totale() {
	var sum = 0;
	if($("#tipoordine").val() % 2) { // le voci OMAGGIO devono avere id pari <-------------------------------------------------------
		//alert("entro");
    $('[id^="subtot"]').each(function() {
        sum += parseFloat($(this).text());
    });
   }

	$("#totale").text(sum.toFixed(2));
	
	$("#lista").empty();
	$(".cod").each(function() {
		var id = $(this).text();
		//alert(id);
        if ($("#quantita-"+id).val() > 0) {
			$("#lista").append("<li>" + $("#quantita-"+id).val() + " x "/*" (" + id + ") "*/ + $("#aumenta-"+id).text() + " = " + $("#subtot-"+id).text() + "&euro;</li>");
		}
    });
	
	update_rimanente();
}

function update_rimanente() {
	var tot = new Number(parseFloat($("#totale").text()));
	var ver = new Number(parseFloat($("#versato").text()));
	$("#rimanente").text((ver-tot).toFixed(2).toString());
}

function reset() {
	$('[id^="quantita"]').each(function() {
        $(this).val(0);
	});
	$('[id^="subtot-"]').each(function() {
        $(this).text("0.00");
	});
	$("#note-cucina").val("");
	update_totale();
	$("#quantita-coperti").val("1");
}

function bottoni_mostra() {
	var nmostra = $('.li-mostra').length;
	var width = new Number(100/nmostra);
	width = Math.round(width)-1;
	alert(width);
	$('.li-mostra').each(function() {
        $(this).css("width",width+"%");
   });
	return false;
}