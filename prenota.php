<?php
include("CONFIG.php");
include ("lib/dbconnection.php");

  $prodotti = array();

  // genero i pulsanti per scorrere le tabelle
  $categorie = find_by("categorie");
  foreach($categorie as $cat) {
	$prodotti_cat = find_by("prodotti", "id_categorie = ".$cat['id']." AND status = 1", "ordine ASC");
	$prodotti[$cat['nome']] = $prodotti_cat;
  }
?>
<html>
	<head>
		
		<title>Prenota</title
		
		<meta name="viewport" content="width=device-width, initial-scale=1">
		
		<link rel="stylesheet" type="text/css" href="css/bootstrap.min.css" />
		<script src="js/bootstrap.min.js"></script>
		<script src="js/jquery-1.11.1.min.js"></script>
		
		<style>
			/*.container {
				max-width: 600px;
			}*/
			
			h1 {
				margin: 0;
			}
			
			thead th {
				background-color: #C2185B;
				color: white;
			}
			
			.categoria {
				width: 100%;
				text-transform: uppercase;
				font-weight: bold;
			}
			
			.prezzo, .parziale {
				font-style: italic;
			}
			
			#benvenuto {
				background-color: #3F51B5;
				color: white;
			}
			#totale-container {
				background-color: #FF4081;
				color: white;
			}
			
			/* Small Devices, Tablets */
			@media only screen and (max-width : 768px) {
				#benvenuto {
					margin: 0;
					border-radius: 0;
					position: absolute;
					top: 0;
					left: 0;
					width: 100%;
				}
				
				table, thead, tbody, tr, td, th {
					display: block !important;
					border: none !important;
				}
				td, th {
					float: left !important;
					
				}
				tr {
					border-top: 1px solid black !important;
					margin: 10px 0;
				}
				.input-group input, .input-group button {
						height: 80px;
				}
				.input-group button {
						width: 80px;
				}
				.categoria {
					background-color: black !important;
					color: white !important;
					box-sizing: content-box;
					left: -15px;
					padding: 15px !important;
					position: relative !important;
					top: -1px;
				}
				.pname {
					background-color: #202020 !important;
					color: white !important;
					font-weight: bold;
				}
				.pprice {
					background-color: #727272 !important;
					color: white !important;
				}
				#table-product {
					margin: 200px 0;
				}
				#totale-container {
					position: fixed;
					bottom: 0;
					left: 0;
					width: 100%;
					margin: 0;
					border-radius: 0;
					z-index: 10;
				}
			}
		</style>
	</head>
	
	<body>
	
	
		<div class="container">
			
			<div id="benvenuto" class="well">
				<h1>Benvenuti alla Sagra di Marano</h1>
			</div>
			
			<table id="table-product" class="table table-striped table-bordered table-hover table-rounded container">
				<thead class="hidden-xs hidden-sm">
					<tr class="row">
						<th class="col-md-3">Prodotto</th>
						<th class="col-md-3">Quantit&agrave;</th>
						<th class="col-md-3">Prezzo</th>
						<th class="col-md-3">Parziale</th>
					</tr>
				</thead>
				
				<tbody>
					
					<?php foreach($prodotti as $tid => $acat) { ?>
						<tr class="row"><td class="col-md-12 col-sm-12 col-xs-12 text-center categoria" colspan="4"><?php echo $tid; ?></td></tr>
						<?php foreach($acat as $aprod) { ?>
							<tr class="row">
								<td class="col-md-7 col-sm-5 col-xs-12 pname"><?php echo $aprod['nome']; ?></td>
								<td class="col-md-3 col-sm-3 col-xs-12 pprice">									
									<div class="input-group">
									  <div class="input-group-btn">
										<button class="btn btn-success btn-large btn-add" data-target="p-<?php echo $aprod['id']; ?>">+</button>
									  </div>
									  <input class="form-control text-center quantita" name="prodotti[<?php echo $aprod['id']; ?>]" id="p-<?php echo $aprod['id']; ?>" value="0" type="number">
									  <div class="input-group-btn">
										<button class="btn btn-warning btn-large btn-less" data-target="p-<?php echo $aprod['id']; ?>">-</button>
									  </div>
									</div><!-- /input-group -->
								</td>
								<td class="col-md-1 col-sm-2 col-xs-6 pprice"> x <span class="prezzo"><?php echo $aprod['prezzo']; ?></span> &euro;</td>
								<td class="col-md-1 col-sm-2 col-xs-6 pprice"> = <span class="parziale">0.00</span> &euro;</td>
							</tr>
						<?php } ?>
					<?php } ?>
				
					
				</tbody>
			
			</table>
			
			<div id="totale-container" class="well">
				<h1>Totale: <strong class="pull-right"><span id="totale">0.00</span> &euro;</strong></h1>
			</div>
			
		</div>
	
	</body>
</html>
