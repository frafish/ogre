// we will add our javascript code here  
jQuery(document).ready(function(){
	
	autosize(jQuery('textarea.autosize'));
	
	jQuery.fn.tagName = function() {
	  return this.prop("tagName").toLowerCase();
	};
	
	jQuery('.a-toggle').click(function () {
		var elem = jQuery(jQuery(this).attr('href'));
		elem.toggle();
		if (elem.is(':visible')) {
			if (elem.tagName() == 'input' || elem.tagName() == 'textarea') {
				elem.focus();		
			}
		} else {
			if (elem.tagName() == 'input' || elem.tagName() == 'textarea') {
				if (elem.val()) {
					jQuery(this).removeClass('btn-primary').addClass('btn-warning');
				} else {
					jQuery(this).addClass('btn-primary').removeClass('btn-warning');
				}		
			}	
		}
		return false;
	});
	
	/*var isFormFilled = function() {
		var filled = true;
		$(':input', '#form').each(function() {
			var $input = $(this);
			if($input.val() == '' || !$input.prop('checked') || !$('option:selected', $input).length) {
				filled = false;
			}
		});
	
		return filled;
	};*/
	var isFormFilled = function() {
		var filled = true;
		if (jQuery('textarea:focus').length) {
			filled = false;
		}	
		return filled;
	};
	jQuery('#form').keypress(function(e) {
	 if(isFormFilled()) {
	  if (e.which == 13) {
	  	 //alert('Hai premuto il tasto invio');
	    return false;
	  }
	 }
	});

	
	//reset(); // mi assicuro che tutti i campi siano svuotati
	update_totale();
	
	//jQuery("table").hide();

	//jQuery( "#tabelle_contenitore" ).tabs();
	
	// MODIFICAZIONI DI STILE
	//bottoni_mostra(); // li allargo per fargli prendere tutto lo spazio a loro disposizione

	/*
	// faccio scomparire la finestra
	jQuery("#subito_trasparenza").click(function(){
		jQuery("#subito").hide();
		return false;
	});
	
	// evito di dover reinserire il tipo di ordine
	jQuery("#subito-tipoordine").change(function () {
		//alert(jQuery("#subito-tipoordine").val() +"-"+ jQuery("#tipoordine").val());
        jQuery("#tipoordine").val(jQuery("#subito-tipoordine").val());
    });
	*/

	// tipo ordine comporta dei cambiamenti
	/*jQuery("#tipoordine").change(function () {
		//alert("potresti non pagare");
		if(jQuery(this).val() > 2) { // le voci ASPORTO devono avere id > 2 <-------------------------------------------------------
			jQuery("#quantita-coperti").val("0");
		}
		jQuery('#tipoordine option').css('background', 'none');
    	jQuery('#tipoordine option:selected').css('background-color', 'red');

		update_totale();
    });*/

	 jQuery("#impostazioni a").click(function () {
	 	//alert('click');
	 	var inputQuick = jQuery(this).attr('href');
      jQuery('body').toggleClass("compact-view");
      if (!jQuery(inputQuick).is(':checked')) {
     		 jQuery(inputQuick).prop("checked", true);
      } else {
      	jQuery(inputQuick).prop("checked", false);
      }
      return false;
      /*jQuery('.col-sx').toggleClass('col-md-6')
      					  .toggleClass('col-sm-6')
      					  .toggleClass('col-xs-6')
      					  .toggleClass('col-md-12')
      					  .toggleClass('col-sm-12')
      					  .toggleClass('col-xs-12');*/
      //bottoni_mostra();
    });
    
    /*jQuery("#impostazioni-mobile").click(function () {
    	//alert('compact');
      jQuery('body').toggleClass("compact-view");
      return false;
    });*/

    jQuery("#label_asporto").click(function () {
      jQuery(this).toggleClass("btn-warning");
      /*if (!jQuery("#asporto").is(':checked')) {
      	jQuery("#asporto").prop("checked", true);
     		jQuery("#quantita-coperti").val("0");
      } else {
      	jQuery("#asporto").prop("checked", false);
      }*/
     });


     jQuery("#label_omaggio").click(function () {
		 
     	jQuery(this).toggleClass("btn-warning");
     	jQuery('body').toggleClass('omaggio-view');
     	jQuery('#quantita-coperti-omaggio').val(0);
     	jQuery('.quantita-omaggio').val(0);
      
	  // se ho omaggi significa che è attivo
	  var omaggi_inserite = false;
	  jQuery(".quantita-omaggio").each(function() {
		if(parseInt(jQuery(this).val())) {
			omaggi_inserite = true;
		}
	  });
	  
	  if (omaggi_inserite) {
			if (confirm("Vuoi trasferire le quantita degli omaggi a quelle a pagamento?")) {
				jQuery(".quantita-omaggio").each(function() {
					if(parseInt(jQuery(this).val())) {
						var qid = jQuery(this).attr('id').split("-")[2];
						//alert('#quantita-omaggio-'+qid+' -- '+jQuery(this).val());
						jQuery('#quantita-'+qid).val(parseInt(jQuery('#quantita-'+qid).val()) + parseInt(jQuery(this).val()));
						jQuery(this).val(0);
					}
				});
				jQuery('.quantita-omaggio').each(function(){
					jQuery(this).val(0);
				});
				jQuery('#quantita-coperti').val(parseInt(jQuery('#quantita-coperti').val()) + parseInt(jQuery('#quantita-coperti-omaggio').val()));
				jQuery('#quantita-coperti-omaggio').val(0);
			}
	   } else {
		    var quantita_inserite = false;
			jQuery(".quantita").each(function() {
				if(parseInt(jQuery(this).val())) {
					quantita_inserite = true;
				}
			});
			if (quantita_inserite) {
				if (confirm("Vuoi trasferire le quantita gia inserite in omaggi?")) {
					jQuery(".quantita").each(function() {
						if(parseInt(jQuery(this).val())) {
							var qid = jQuery(this).attr('id').split("-")[1];
							//alert('#quantita-omaggio-'+qid+' -- '+jQuery(this).val());
							jQuery('#quantita-omaggio-'+qid).val(jQuery(this).val());
							jQuery(this).val(0);
							jQuery(this).removeClass('attivo');
							jQuery(this).closest('.td_add').removeClass('attivo');
						}
					});
					jQuery('#quantita-coperti-omaggio').val(jQuery('#quantita-coperti').val());
					jQuery('#quantita-coperti').val(0);
				}
		    }
		 }

      update_totale();
    });
	

	/*
	// evito di dover reinserire il numero di coperti
	jQuery("#quantita-subitocoperti").change(function () {
		//alert(jQuery("#subito-tipoordine").val() +"-"+ jQuery("#tipoordine").val());
        jQuery("#quantita-coperti").val(jQuery("#quantita-subitocoperti").val());
    });
	
	// confermo la scelta della finestra inziale
	jQuery("#conferma-subito").click(function(){
	  jQuery("#subito").hide();
	  jQuery("#quantita-coperti").val(jQuery("#quantita-subitocoperti").val());
	  update_subtot("coperti");
	  return false;
	});
	*/
	
	// mostra solo la tabella scelta
	jQuery('.mostra').click(function(){
	  jQuery('.mostra').removeClass("active-mostra");
	  jQuery(this).addClass("active-mostra");
	  //var idbutton = jQuery(this).attr("id");
	  //var tts = idbutton.split("-")[1];
	  //jQuery("table").hide();
	  //alert("#"+tts);
	  //jQuery("#"+tts).show();
	  return false;
	});
	
	// azzera tutti i campi possibili
	jQuery("#reset").click(function(){
	  reset();
	  return false;
	});
	
	//jQuery(":button").css({background:"yellow". border:"3px red solid"});
	/*jQuery('[type="button"]').click(function(){
		alert(jQuery(this).attr("id"));
	}*/
	
	// pulsanti per aumentare le quantita
	jQuery(document).on('click', '.aumenta', function(){
	  //var idbutton = jQuery(this).attr("id");
	  //var id = idbutton.split("-")[1];
	  var id = jQuery(this).attr('data-target');
	  var valorecorrente = jQuery("#"+id).val();
	  valorecorrente++;
	  //alert(valorecorrente);
	  jQuery("#"+id).val(valorecorrente);
	  jQuery("#"+id).addClass("attivo");
	  if (jQuery(this).hasClass('articolo')) {
			jQuery(this).closest('.td_add').addClass('attivo');
	  }
	  update_totale();
	  return true;
	});
	
	function riduci(id) {
		  var valorecorrente = jQuery("#"+id).val();
		  if (valorecorrente > 0) {
			valorecorrente--;
			jQuery("#"+id).val(valorecorrente);
			}
		  if (valorecorrente <= 0) {
			jQuery("#"+id).removeClass("attivo");
			if (jQuery("#"+id).closest('.td_add').length) {
					jQuery("#"+id).closest('.td_add').removeClass('attivo');
					jQuery("#"+id).closest('.td_add').find('.prodotto-nota').hide();
			}
		  }
		  update_totale();
	}
	
	// pulsanti per diminuire le quantita
	jQuery(document).on('click', '.diminuisci', function(){
	  //var idbutton = jQuery(this).attr("id");
	  //var id = idbutton.split("-")[1];
	  var id = jQuery(this).attr('data-target');
	  riduci(id);
	  return true;
	});
	jQuery('#tabelle').on('contextmenu', '.aumenta', function(){
	  //var idbutton = jQuery(this).attr("id");
	  //var id = idbutton.split("-")[1];
	  var id = jQuery(this).attr('data-target');
	  riduci(id);
	  return false;
	});
	
	// pulsanti della calcolatrice
	jQuery('.moneta').click(function(){
	  var valorecorrente = new Number(parseFloat(jQuery("#versato").val()));
	  //alert(parseFloat(jQuery(this).val()));
     valorecorrente += parseFloat(jQuery(this).val());
   	if (valorecorrente >= parseFloat(jQuery("#totale").text())) {
   	   jQuery("#rimanente").removeClass("text-danger").addClass("text-success");
   	}
   	jQuery("#versato").val(valorecorrente.toFixed(2).toString());
     
	  update_rimanente();
	  return false;
	});
	
	jQuery("#versato").change(function () {
        update_rimanente();
    });
	
	jQuery('#calcolatrice-reset').click(function(){
	     jQuery("#versato").val("0.00"); 
	     jQuery("#rimanente").addClass("text-danger").removeClass("text-success");;
	     jQuery("#rimanente").text("0.00");
	     update_rimanente();
	     return false;
	});
   jQuery('#calcolatrice-ok').click(function(){
         jQuery("#versato").val(jQuery("#totale").val());
         update_rimanente();
         jQuery("#rimanente").removeClass("text-danger").addClass("text-success");
         return false;
	});
   jQuery("#label_pos").click(function () {
      jQuery(this).toggleClass("btn-primary");
      jQuery('#calcolatrice-ok').trigger('click');
     });
	jQuery('#calcolatrice-title').click(function(){
	     jQuery("#calcolatrice-pulsanti").slideToggle(); 
	});
	
	// controllo che non ci sia stato un inserimento diretto della quantit�
	jQuery(".quantita").change(function () {
        update_totale();
    });
	
	// pressione tasto per concludere l'ordine
	jQuery("#nuovo-ordine").click(function(){
		//alert("Nuovo ordine");


		if ((parseInt(jQuery("#quantita-coperti").val()) < 1 && parseInt(jQuery("#quantita-coperti-omaggio").val()) < 1) 
				&& !jQuery("#asporto").is(':checked')
				&& !jQuery("#quantita-coperti").hasClass('no-coperti')
		) {
			var coperti_necessari = false;
			jQuery(".quantita-omaggio, .quantita").each(function() {
				if(parseInt(jQuery(this).val())) {
					if(jQuery(this).hasClass('ha-coperto')) {
						coperti_necessari = true;
					}
				}
			});
			if (coperti_necessari) {
				//alert("Mangiano con le mani?!?");
				var r = confirm("Mangiano con le mani?!?");
				if (r == false) {
					return false;
				}
			}
		}

		// controllo che sia stato pagato
		if (parseFloat(jQuery("#versato").val()) < parseFloat(jQuery("#totale").val())) {
			alert("L'UTENTE NON HA PAGATO?!?");
			return false;
		}

		// mi recupero l'elenco dei prodotti acquistati
		/*var dettagli = "";
		jQuery(".cod").each(function() {
			var cod = jQuery(this).text();
			//alert(id);
			if (jQuery("#quantita-"+cod).val() > 0) {
				dettagli = dettagli + cod + "," + jQuery("#quantita-"+cod).val() +  ";" ;
			}
		});*/

		// controllo che ci sia qualcosa in carrello
		if (!jQuery('#lista li').length) {
			alert("Ti piace sprecare carta, eh?!?");
			return false;
		}

		// recupero tutti i dati necessari
		/*jQuery("#oordine").val(dettagli);
		jQuery("#opagato").val(jQuery("#versato").text());
		jQuery("#ototale").val(jQuery("#totale").text());
		jQuery("#ocoperti").val(jQuery("#quantita-coperti").val());
		jQuery("#onote").val(jQuery("#note-cucina").val());
		var tipoordine = 1;
		if (jQuery("#omaggio").is(':checked')) {
			tipoordine = 2;
		}
		if (jQuery("#asporto").is(':checked')) {
			tipoordine = 3;
		}
		if (jQuery("#omaggio").is(':checked') && jQuery("#asporto").is(':checked')) {
			tipoordine = 4;
		}
		//alert(tipoordine);
		jQuery("#otipo").val(tipoordine);
		jQuery("#ostampante").val(jQuery("#selstampante").val());*/
		
		//alert(dettagli);
	   //return false;
	});

	jQuery('#cassainfo').click(function(){
		jQuery.get('ajax/incasso.php', function(data) {
		  alert(data);
		});
	});
	
	
	jQuery(window).resize(function () {
		//alert('ah');
		fit_column();		
	});
	jQuery('.auto-height').resize(function () {
		//alert('ah');
		fit_column();		
	});
	
});

jQuery(window).load(function () {
	fit_column();
	
	 setInterval(function(){
	 	jQuery.ajax({
			url: 'ajax/utente.php',
			type: 'GET',
			error: function() { /*non risponde*/ 
				alert("ATTENZIONE: collegamento con il server interrotto!");
			},
			success: function(t) { /* verifico l'utente */
				//alert(t);
				if (parseInt(t)) {
					//alert('Tutto OK');
				} else {
					alert("ATTENZIONE: sessione terminata.");
					location.href = '/';
				}
			}
		}); 
	 }, 10000);
});

function fit_column() {
	jQuery('.col-full-height').each(function () {
		var height_col = jQuery(this).height();
		var height_real = jQuery(this).find('.auto-height').height();
		var height_filler = jQuery(this).find('.height-filler').height();
		var height_without_filler = height_real - height_filler;
		var height_diff = 0;
		if (height_real < height_col) {					
			var height_diff = height_col - height_without_filler; 			
		} else {
			if (height_without_filler < height_col) {
				var height_diff = height_col - height_without_filler;
			}
		}
		if (height_diff < 100) {
			height_diff = 100;
		}
		jQuery(this).find('.height-filler').height(height_diff);
	});
	
}

// aggiorna il subtotale di ogni prodotto (quantita x prezzo singolo)
function update_subtot(id) {
	var n = new Number(jQuery("#quantita-"+id).val());
	//alert(jQuery("#prezzo-"+id).text());
	var prezzo = new Number(parseFloat(jQuery("#prezzo-"+id).text()));
	var subtot = new Number(n * prezzo);
	subtot = Math.round(subtot*100)/100;
	//alert("Prezzo " + n + " * " + prezzo + " = " + subtot.toString());
	jQuery("#subtot-"+id).text(subtot.toFixed(2).toString());
	update_totale();
}

// aggiorna il conto totale mostrandolo a lato
function update_totale() {
	var sum = 0; // il totale
	jQuery("#lista").empty(); // svuoto la lista
	jQuery('.td_add').each(function() {
		var qnt = jQuery(this).find('.quantita').val();
		var qntOmaggio = jQuery(this).parent().find('.quantita-omaggio').val();
	 	if (qnt > 0 || qntOmaggio > 0) {
	 	  subtot = parseFloat(jQuery(this).find('.prezzo').text()) * qnt;
	     sum += subtot;
	     var newRow = '<li class="lista-li';
	     if (subtot < 0) {
				newRow += ' bg-danger';	     
	     }
	     newRow += '"><button class="btn btn-xs btn-warning diminuisci" data-target="quantita-' + jQuery(this).find('.product-id').text() + '">&nbsp;x&nbsp;</button> ' + qnt;
	     if (qntOmaggio > 0) { newRow += ' (+'+qntOmaggio+')'; }
	     newRow += ' x ' + jQuery(this).find('.nome-corto').html() + ' = ' + subtot + '&euro;</li>';
	     jQuery("#lista").append(newRow);
	   }
	});

	if (sum > 0) {
		jQuery("#totale").val(sum.toFixed(2)); // lo mostro
	} else {
		jQuery("#totale").val('0.00');
	}
	
	update_rimanente();
}

// tool per la calcolatrice per mostrare quanto manca da versare al cliente (differenza totale - versato)
function update_rimanente() {
	var tot = new Number(parseFloat(jQuery("#totale").val()));
	var ver = new Number(parseFloat(jQuery("#versato").val()));
	var resto = ver-tot;
	if (tot > 0) {
		jQuery("#rimanente").text((resto).toFixed(2).toString());
	} else {
		jQuery("#rimanente").text('0');
	}
	abilita_btn_concludi();
}

// ripristina tutto a valori di default (senza dover aggiornare la pagina)
function reset() {
	jQuery('.quantita, .quantita-omaggio').each(function() {
        jQuery(this).val(0);
	});
	jQuery('.subtot').each(function() {
        jQuery(this).text("0.00");
	});
	jQuery('#versato').text("0.00");
	jQuery('#resto').text("0.00");
	jQuery("#note-cucina").val("");
	update_totale();
	jQuery("#quantita-coperti").val("0");
	jQuery(".quantita").removeClass("attivo");
}

function abilita_btn_concludi() {
	var btn = jQuery('#nuovo-ordine');
	
	// controllo che ci siano i coperti o asporto
	if ((parseInt(jQuery("#quantita-coperti").val()) < 1 && parseInt(jQuery("#quantita-coperti-omaggio").val()) < 1) 
		  && !jQuery("#asporto").is(':checked') 
		  && !jQuery("#quantita-coperti").hasClass('no-coperti')
	) {
		btn.removeClass('btn-success').addClass('btn-danger');
		return false;
	}

	// controllo che sia stato pagato
	if (parseFloat(jQuery("#versato").val()) < parseFloat(jQuery("#totale").val())) {
		btn.removeClass('btn-success').addClass('btn-danger');
		return false;
	}

	// controllo che ci sia qualcosa in carrello
	if (!jQuery('#lista li').length) {
		btn.removeClass('btn-success').addClass('btn-danger');
		return false;
	}
	
	btn.removeClass('btn-danger').addClass('btn-success');
	return true;
}

function set_button_height() {
	jQuery('#tabelle .tabs').each(function () {
		var elecat = jQuery(this).find('.elenco-categorie');
		var altezza = jQuery(this).height() - elecat.outerHeight();
		//alert(elecat.outerHeight());
		var btns = jQuery(this).find('.reparto > li');
		var n_btn = btns.length;
		var btn_height = Math.floor(altezza/n_btn);
		//alert(btn_height);
		btns.height(btn_height);
		jQuery('#tabelle').addClass('force-btn-full-height');
	});	
}
