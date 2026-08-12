<?php

	$panicFile = 'panic.txt';
	$panicMode = file_exists($panicFile);
	
	
	if (isset($_REQUEST['panic'])) {	
		// toggle_panic();
		if ($panicMode) {
			unlink($panicFile);
			$panicMode = false;
		} else {
			file_put_contents($panicFile, $_REQUEST['panic']);
			$panicMode = true;
		}
	}
	
	$panicValue = ($panicMode) ? file_get_contents($panicFile) : 40;
?>
<html>
	<head>
		
		<title>PANIC MODE</title>
		
		<link rel="stylesheet" type="text/css" href="css/bootstrap.min.css" />
		<link rel="stylesheet" type="text/css" href="css/base.css" />
		<style>
			#dontpush {
				width: 200px;
				height: 200px;
				border: 1px solid red;
				background-color: #f44336;
				border-radius: 100px;
				display: block;
				position: absolute;
				left: 50%;
				margin-left: -100px;
				top: 50%;
				margin-top: -100px;
				box-shadow: -10px -10px 0 #b71c1c;
				transition: 1s all;
				outline: none;
			}
			#dontpush:hover {
				margin-top: -110px;
				margin-left: -110px;
				box-shadow: 0 0 0 red;
			}
			
			
			@keyframes changebg {
			  from {
				background-color: #fff176;
			  }
			  to {
				background-color: #ef5350;
			  }
			}

			.panic-mode {
			  animation-duration: 0.5s;
			  animation-name: changebg;
			  animation-iteration-count: infinite;
			  animation-direction: alternate;
			}
				
		</style>
	
	
		<script src="js/jquery-1.11.1.min.js"></script>
		<script>
			jQuery(document).ready(function(){
				jQuery('#dontpush').click(function(){
					if (confirm("Sei sicuro?")) {
						return true;
					}
					return false;
				});
			});
		</script>
	</head>

<body<?php if ($panicMode) {?> class="panic-mode"<?php } ?>>

	<div class="container-fluid">
	
	<a href="/" class="btn btn-default btn-lg btn-back pull-left"><span class="glyphicon glyphicon-backward" aria-hidden="true"></span> Indietro</a>
	<h1 class="title">PANICO</h1>

<form action="" method="post">
	<input type="number" name="panic" value="<?php echo $panicValue; ?>" id="tempo">
	<button type="submit" id="dontpush" class="btn btn-danger">PANIC MODE!!</button>
</form>


</body>

</html>
