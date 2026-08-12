<?php
	// TODO: tutte le tabelle avranno un campo status per rendere la riga virtualmente eliminata dal db

	$id = $_REQUEST['id'];
	$table = $_REQUEST['table'];
	echo $table.' -- '.$id;
?>