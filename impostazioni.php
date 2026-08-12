<?php
include_once("CONFIG.php");
include_once("lib/dbconnection.php");
include_once("lib/print.php");

$tables = array('utenti', 'casse', 'stampanti', 'opzioni'); // 'reparti');
if (db_count('reparti')) {
    $tables[] = 'reparti';
}

if (isset($_REQUEST['azione'])) {
    switch ($_REQUEST['azione']) {
        case 'export':
            $table = $_REQUEST['table'];
            get_csv($table, null, null, true, time() . '_ogre_' . $table . '.csv');
            break;
    }
}

// salvo tutto
if (isset($_REQUEST['salva'])) {
    foreach ($tables as $table) {
        $old = find_by($table);
        $new = $_REQUEST[$table];
        foreach ($new as $id => $elemento) {
            if (is_int($id) && isset($old[$id])) {
                // aggiorno esistente
                $elemento['id'] = $id;
                db_save($table, $elemento, $old[$id]);
            } else {
                // inserisco nuovo
                $elemento['id'] = null;
                //var_dump($elemento);
                db_save($table, $elemento);
            }
        }
    }
    set_alert("Aggiornamento avvenuto con successo", 'success');

    //var_dump($_FILES['import']);
    if (!empty($_FILES['import'])) {
        $delimeter = ';';
        foreach ($_FILES['import']['name'] as $table => $afile) {
            if ($afile) {
                if (!is_dir(__DIR__ . DIRECTORY_SEPARATOR . CARTELLA_IMPORTATI)) {
                    mkdir(__DIR__ . DIRECTORY_SEPARATOR . CARTELLA_IMPORTATI);
                }
                $imported_file = __DIR__ . DIRECTORY_SEPARATOR . CARTELLA_IMPORTATI . DIRECTORY_SEPARATOR . $afile;
                if (rename($_FILES['import']['tmp_name'][$table], $imported_file)) {
                    $rows = file($imported_file);
                    foreach ($rows as $krow => $row) {
                        if (!$krow) {
                            $fields = explode($delimeter, $row);
                        } else {
                            $vals = explode($delimeter, $row);
                            foreach ($vals as $vkey => $value) {
                                $obj[$fields[$vkey]] = $value;
                            }
                            db_save($table, $obj);
                        }
                    }
                    set_alert('Importato il file <a href="' . CARTELLA_IMPORTATI . DIRECTORY_SEPARATOR . $afile . '">' . $afile . '</a>', 'success');
                }
            }
        }
    }
}

if (!db_count('reparti', 'fila = 1')) {
    set_alert('ATTENZIONE: probabilmente dovrai impostare un reparto che necessita di attesa.');
}

if (!db_count('utenti', 'status = 1')) {
    set_alert('ATTENZIONE: abilita almeno un utente.', 'warning');
}

if (!db_count('casse', 'status = 1')) {
    set_alert('ATTENZIONE: abilita almeno una cassa.', 'warning');
}

//var_dump($_SESSION['alerts']);

function print_import_export($table) {
    ?>
    <div class="well well-white-asd relative">
        <h2><?php if (db_count($table)) { ?>Aggiornamento<?php } else { ?>Importazione<?php } ?> <?php echo $table; ?></h2>
        <div class="panel panel-default mb-0">
            <div class="panel-heading">
                <label for="file">Seleziona il csv da importare:</label>
            </div>
            <div class="panel-body">
                <div class="input-group-asd mb-5">
                    <input class="input-group-addon-asd absolute right-20 mr-15 btn btn-lg-asd btn-warning" type="submit" name="submit" value="Carica <?php echo $table; ?>" title="Aggiorna <?php echo $table; ?> già presenti e aggiunge quelli nuovi" />
                    <input name="import[<?php echo $table; ?>]" id="file-<?php echo $table; ?>" type="file" class="input-lg-ads form-control">
                </div>			     
            </div>
        </div>				
        <a class="btn btn-success absolute top mt-20 right mr-20" title="Esporta l'elenco attuale dei <?php echo $table; ?> già inseriti" target="_blank" href="?azione=export&table=<?php echo $table; ?>"><span class="glyphicon glyphicon-export" aria-hidden="true"></span> Esporta</a>
    </div>  
<?php }
?>
<html>

    <head>
        <title>Impostazioni</title>

        <link rel="stylesheet" type="text/css" href="css/bootstrap.min.css" />
        <link href="css/bootstrap-switch.min.css" rel="stylesheet">
        <link rel="stylesheet" type="text/css" href="css/base.css" />
        <script src="js/jquery-1.11.1.min.js"></script>
        <script src="js/bootstrap.min.js"></script>
        <script src="js/bootstrap-switch.min.js"></script>
        <script src="js/RowSorter.js"></script>


        <script type="text/javascript">
            jQuery(document).ready(function () {
                jQuery('.btn-delete').on('click', function () {
                    //var id = jQuery(this).closest('tr').find('.field-id').val();
                    var ahref = jQuery(this).attr('href');
                    var info = ahref.substr(1).split('-');
                    var tab = info[0];
                    var aid = info[1];
                    //alert(aid);
                    jQuery.post("ajax/delete.php", {table: tab, id: aid}, function (result) {
                        if (result) {
                            alert(result);
                        }
                    });
                    return false;
                });

                jQuery('.btn-add').on('click', function () {
                    var new_id = 'new';
                    var ahref = jQuery(this).attr('href');
                    var tab = ahref.substr(1);
                    var last_row = jQuery(ahref).find('tbody tr').last();
                    var last_row_id = last_row.attr('id');
                    var info = last_row_id.split('-');
                    var aid = info[1];
                    
                    var more = (jQuery("[id^='" + tab + "-" + new_id + "']").length) ? jQuery("[id^='" + tab + "-" + new_id + "']").length : '';
                    var next_aid = new_id + "" + more;
                    
                    var new_row = last_row.clone(true);
                    new_row.attr('id', tab + '-' + next_aid);
                    
                    // Copia i valori attuali delle select, che clone() potrebbe non riportare a seconda del browser
                    var originalSelects = last_row.find('select');
                    new_row.find('select').each(function(index, item) {
                         jQuery(item).val(originalSelects.eq(index).val());
                    });
                    
                    // Aggiorna accuratamente gli attributi name e gli ahref
                    new_row.find('input, select, textarea').each(function() {
                        var name = jQuery(this).attr('name');
                        if (name) {
                            // sostituisce esattamente la chiave nell'array (es. [1] o [new] -> [new1])
                            jQuery(this).attr('name', name.replace('[' + aid + ']', '[' + next_aid + ']'));
                        }
                    });
                    
                    new_row.find('a.btn-delete').each(function() {
                        var href = jQuery(this).attr('href');
                        if (href) {
                            jQuery(this).attr('href', href.replace('-' + aid, '-' + next_aid));
                        }
                    });

                    jQuery(ahref).find('tbody').append(new_row);
                    return false;
                });

                jQuery('.btn-import-printer').on('click', function () {
                    jQuery('#tab-stampanti').find('.btn-add').trigger('click');
                    var nome = jQuery(jQuery(this).attr('href')).val();
                    jQuery('#stampanti tbody tr').last().find('.field-nome').val(nome);
                    return false;
                });

            });

        </script>

        <style type="text/css">
            tr th {
                text-align: center;
                text-transform: uppercase;		
            }
            .field-id {
                max-width: 40px;
            }
        </style>
    </head>

    <body>
        <div class="container-fluid">

            <a href="/" class="btn btn-default btn-lg btn-back pull-left admin absolute"><span class="glyphicon glyphicon-backward" aria-hidden="true"></span> Indietro</a>

            <?php $utente = get_user();
            if ($utente) {
                ?>
                <div class="pull-right">
                    <div class="input-group input-group mt-5">
                        <span class="input-group-addon"><span class="glyphicon glyphicon-user" aria-hidden="true"></span></span>
                        <input class="disabled form-control" value="<?php echo $utente['nome']; ?>" disabled="">
                        <a class="input-group-addon btn btn-danger" href="logout.php">LOGOUT</a>
                    </div>
                </div>
<?php } ?>

            <h1 class="title">Impostazioni</h1>

            <?php
            if (is_user()) { // controllo se l'utente è autenticato 
                ?>

                    <?php print_alerts(); ?>

                <ul class="nav nav-tabs" role="tablist">
                    <?php 
                    // Mappatura delle descrizioni dei campi
                    $desc_campi = array(
                        'id' => 'Identificativo univoco interno (ID)',
                        'nome' => 'Il nome identificativo',
                        'valore' => 'Il valore assegnato all\'opzione',
                        'descrizione' => 'Descrizione dell\'opzione',
                        'ip' => 'Indirizzo IP (es. per stampanti di rete o limitazioni accessi)',
                        'password' => 'La password di accesso (lasciare vuota per non modificarla)',
                        'admin' => 'Determina se l\'utente ha i privilegi completi di amministratore',
                        'status' => 'Abilita o disabilita l\'elemento nel sistema',
                        'id_stampanti' => 'La stampante di rete assegnata per questo reparto/cassa',
                        'asporto' => 'Attiva in automatico la modalità asporto per questa cassa',
                        'id_categorie' => 'La categoria preferita preselezionata',
                        'id_reparti' => 'Il reparto assegnato o di competenza',
                        'universale' => 'Rende l\'elemento disponibile a tutte le casse/reparti',
                        'ordine' => 'Ordine di visualizzazione a schermo (numeri più bassi prima)',
                        'formato' => 'Formato carta per la stampa (es. A4, A5, ecc.)',
                        'ricevuta' => 'Abilita o disabilita la stampa di uno scontrino per questo reparto',
                        'fila' => 'Abilita la gestione dei numeri elimina-code/ritiro per il reparto',
                        'coperti' => 'Forza l\'inserimento o il calcolo del numero di coperti al tavolo'
                    );
                    foreach ($tables as $tkey => $table) { ?>
                        <li role="presentation"<?php if (!$tkey) { ?> class="active"<?php } ?>><a href="#tab-<?php echo $table; ?>" aria-controls="tab-<?php echo $table; ?>" role="tab" data-toggle="tab"><?php echo $table; ?></a></li>
    <?php } ?>
                </ul>

                <form action="" method="post" enctype="multipart/form-data">

                    <!-- Tab panes -->
                    <div class="tab-content">


                            <?php $table = 'opzioni'; ?>
                        <div role="tabpanel" class="tab-pane well well-white" id="tab-<?php echo $table; ?>">
                            <?php
                            $rows = find_by($table, null, 'nome');
                            //$fields = array_keys(reset($rows));
                            $fields = db_get_fields($table);
                            //var_dump($fields);
                            //if(($key = array_search('time', $fields)) !== false) { unset($fields[$key]); } // rimuovo la colonna time
                            if (isset($fields['id'])) {
                                unset($fields['id']);
                            }

                            //print_import_export($table);
                            ?>
                            <table id="<?php echo $table; ?>" class="table table-striped table-hover table-condensed table-bordered">
                                <thead>
                                    <tr class="info">
                                        <?php foreach ($fields as $field => $afield) { ?>
                                            <th>
                                                <?php if (isset($desc_campi[$field])) { ?><abbr title="<?php echo htmlspecialchars($desc_campi[$field]); ?>"><?php } ?>
                                                <?php echo $field; ?>
                                                <?php if (isset($desc_campi[$field])) { ?></abbr><?php } ?>
                                            </th>
                                    <?php } ?>
                                    </tr>
                                </thead>
                                <tbody>
                                            <?php foreach ($rows as $rid => $row) { ?>
                                        <tr id="<?php echo $table . '-' . $rid; ?>">
                                                <?php foreach ($fields as $field => $afield) { ?>
                                                <td>
                                                    <?php switch ($field) {
                                                        case 'descrizione':
                                                            ?>
                                                            <?php echo $row[$field]; ?> 
                                                        <?php
                                                        break;
                                                    default:
                                                        ?>
                                                            <input data-tipo="<?php echo $afield['tipo']; ?>" class="form-control field-<?php echo $field; ?><?php if ($field == 'nome') { ?> disabled<?php } ?>" type="text" value="<?php echo $row[$field]; ?>" name="<?php echo $table; ?>[<?php echo $row['id']; ?>][<?php echo $field; ?>]"<?php if ($field == 'nome') { ?> disabled="disabled"<?php } ?>>
            <?php } ?>    				
                                                </td>
        <?php } ?>
                                        </tr>
                        <?php } ?>
                                </tbody>
                            </table>
                        </div>


                            <?php $table = 'utenti'; ?>
                        <div role="tabpanel" class="tab-pane well well-white active" id="tab-<?php echo $table; ?>">
                            <?php
                            $rows = find_by($table, null, 'id');
                            //$fields = array_keys(reset($rows));
                            $fields = db_get_fields($table);
                            //var_dump($fields);
                            //if(($key = array_search('time', $fields)) !== false) { unset($fields[$key]); } // rimuovo la colonna time
                            if (isset($fields['time'])) {
                                unset($fields['time']);
                            }

                            print_import_export($table);
                            ?>
                            <table id="<?php echo $table; ?>" class="table table-striped table-hover table-condensed table-bordered">
                                <thead>
                                    <tr class="info">
                                    <?php foreach ($fields as $field => $afield) { ?>
                                            <th>
                                                <?php if (isset($desc_campi[$field])) { ?><abbr title="<?php echo htmlspecialchars($desc_campi[$field]); ?>"><?php } ?>
                                                <?php echo $field; ?>
                                                <?php if (isset($desc_campi[$field])) { ?></abbr><?php } ?>
                                            </th>
                                            <?php } ?>
                                    </tr>
                                </thead>
                                <tbody>
                                            <?php foreach ($rows as $rid => $row) { ?>
                                        <tr id="<?php echo $table . '-' . $rid; ?>">
                                                    <?php foreach ($fields as $field => $afield) { ?>
                                                <td>
                                                    <?php
                                                    switch ($field) {
                                                        case 'status':
                                                        case 'admin':
                                                            $values = array('0' => 'No', '1' => 'Si');
                                                            ?>
                                                            <select class="form-control field-<?php echo $field; ?>" name="<?php echo $table; ?>[<?php echo $row['id']; ?>][<?php echo $field; ?>]">
                                                    <?php foreach ($values as $value => $label) { ?><option value="<?php echo $value; ?>"<?php if ($value == $row[$field]) { ?> selected="selected"<?php } ?>><?php echo $label; ?></option><?php } ?>	    						
                                                            </select>  
                    <?php
                    break;
                default:
                    ?>
                                                            <input data-tipo="<?php echo $afield['tipo']; ?>" class="form-control field-<?php echo $field; ?><?php if ($field == 'id') { ?> disabled<?php } ?>" type="text" value="<?php echo $row[$field]; ?>" name="<?php echo $table; ?>[<?php echo $row['id']; ?>][<?php echo $field; ?>]"<?php if ($field == 'id') { ?> disabled="disabled"<?php } ?>>
                                <?php } ?>    				
                                                </td>
                                <?php } ?>
                                        </tr>
                            <?php } ?>
                                </tbody>
                            </table>
                            <a class="btn btn-lg btn-primary btn-add" href="#<?php echo $table; ?>"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span> Aggiungi <?php echo $table; ?></a>
                        </div>

                            <?php $table = 'casse'; ?>
                        <div role="tabpanel" class="tab-pane well well-white" id="tab-<?php echo $table; ?>">
    <?php
    $rows = find_by($table);
    //$fields = array_keys(reset($rows));
    $fields = db_get_fields($table);
    //var_dump($fields);
    //if(($key = array_search('time', $fields)) !== false) { unset($fields[$key]); } // rimuovo la colonna time
    if (isset($fields['time'])) {
        unset($fields['time']);
    }

    print_import_export($table);
    ?>
                            <table id="<?php echo $table; ?>" class="table table-striped table-hover table-condensed table-bordered">
                                <thead>
                                    <tr class="info">
                                            <?php foreach ($fields as $field => $afield) { ?>
                                            <th>
                                                <?php if (isset($desc_campi[$field])) { ?><abbr title="<?php echo htmlspecialchars($desc_campi[$field]); ?>"><?php } ?>
                                                <?php echo $field; ?>
                                                <?php if (isset($desc_campi[$field])) { ?></abbr><?php } ?>
                                            </th>
                                            <?php } ?>
                                    </tr>
                                </thead>
                                <tbody>
                                            <?php foreach ($rows as $rid => $row) { ?>
                                        <tr id="<?php echo $table . '-' . $rid; ?>">
                                                <?php foreach ($fields as $field => $afield) { ?>
                                                <td>
                                                    <?php
                                                    if (is_foreign_key($field)) {
                                                        $ftable = db_get_table_by_fk($field);
                                                        $values = find_by($ftable);
                                                        if ($field == 'id_reparti') {
                                                            $values[0] = array('nome' => 'Tutti i reparti', 'id' => 0);
                                                        }
                                                        if ($field == 'id_categorie') {
                                                            $values[0] = array('nome' => 'Nessuna categoria preferita', 'id' => 0);
                                                        }
                                                        if ($field == 'id_stampanti') {
                                                            $values = get_printers();
                                                        }
                                                        ?>
                                                        <select class="form-control field-<?php echo $field; ?>" name="<?php echo $table; ?>[<?php echo $row['id']; ?>][<?php echo $field; ?>]">
                                                        <?php foreach ($values as $value => $label) { ?><option value="<?php echo $value; ?>"<?php if ($value == $row[$field]) { ?> selected="selected"<?php } ?>><?php echo $label['nome']; ?></option><?php } ?>	    						
                                                        </select>  
                                                    <?php
                                                    } else {
                                                        switch ($field) {
                                                            case 'status':
                                                            case 'asporto':
                                                                $values = array('0' => 'No', '1' => 'Si');
                                                                ?>
                                                                <select class="form-control field-<?php echo $field; ?>" name="<?php echo $table; ?>[<?php echo $row['id']; ?>][<?php echo $field; ?>]">
                        <?php foreach ($values as $value => $label) { ?><option value="<?php echo $value; ?>"<?php if ($value == $row[$field]) { ?> selected="selected"<?php } ?>><?php echo $label; ?></option><?php } ?>	    						
                                                                </select>  
                        <?php
                        break;
                    default:
                        ?>
                                                                <input data-tipo="<?php echo $afield['tipo']; ?>" class="form-control field-<?php echo $field; ?><?php if ($field == 'id') { ?> disabled<?php } ?>" type="text" value="<?php echo $row[$field]; ?>" name="<?php echo $table; ?>[<?php echo $row['id']; ?>][<?php echo $field; ?>]"<?php if ($field == 'id') { ?> disabled="disabled"<?php } ?>>
                                        <?php
                                        }
                                    }
                                    ?>    				
                                                </td>
                                <?php } ?>
                                        </tr>
                            <?php } ?>
                                </tbody>
                            </table>
                            <a class="btn btn-lg btn-primary btn-add" href="#<?php echo $table; ?>"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span> Aggiungi <?php echo $table; ?></a>
                        </div>


                                            <?php $table = 'stampanti'; ?>
                        <div role="tabpanel" class="tab-pane well well-white" id="tab-<?php echo $table; ?>">
                                        <?php
                                        $rows = find_by($table);
                                        $fields = db_get_fields($table);
                                        // rimuovo la colonna time
                                        if (isset($fields['time'])) {
                                            unset($fields['time']);
                                        }

                                        print_import_export($table);
                                        ?>
                            <table id="<?php echo $table; ?>" class="table table-striped table-hover table-condensed table-bordered">
                                <thead>
                                    <tr class="info">
                                                <?php foreach ($fields as $field => $afield) { ?>
                                            <th>
                                                <?php if (isset($desc_campi[$field])) { ?><abbr title="<?php echo htmlspecialchars($desc_campi[$field]); ?>"><?php } ?>
                                                <?php echo $field; ?>
                                                <?php if (isset($desc_campi[$field])) { ?></abbr><?php } ?>
                                            </th>
                                            <?php } ?>
                                    </tr>
                                </thead>
                                <tbody>
                                            <?php foreach ($rows as $rid => $row) { ?>
                                        <tr id="<?php echo $table . '-' . $rid; ?>">
                                                <?php foreach ($fields as $field => $afield) { ?>
                                                <td>
                                                    <?php
                                                    switch ($field) {
                                                        case 'status':
                                                            $values = array('0' => 'No', '1' => 'Si');
                                                            ?>
                                                            <select class="form-control field-<?php echo $field; ?>" name="<?php echo $table; ?>[<?php echo $row['id']; ?>][<?php echo $field; ?>]">
                    <?php foreach ($values as $value => $label) { ?><option value="<?php echo $value; ?>"<?php if ($value == $row[$field]) { ?> selected="selected"<?php } ?>><?php echo $label; ?></option><?php } ?>	    						
                                                            </select>  
                    <?php
                    break;
                case 'formato':
                    $values = array('A4', 'A5', 'A6');
                    ?>
                                                            <select class="form-control field-<?php echo $field; ?>" name="<?php echo $table; ?>[<?php echo $row['id']; ?>][<?php echo $field; ?>]">
                    <?php foreach ($values as $value) { ?><option value="<?php echo $value; ?>"<?php if ($value == $row[$field]) { ?> selected="selected"<?php } ?>><?php echo $value; ?></option><?php } ?>	    						
                                                            </select> 
                                                    <?php break;
                                                default:
                                                    ?>
                                                            <input class="form-control field-<?php echo $field; ?><?php if ($field == 'id') { ?> disabled<?php } ?>" type="text" value="<?php echo $row[$field]; ?>" name="<?php echo $table; ?>[<?php echo $row['id']; ?>][<?php echo $field; ?>]"<?php if ($field == 'id') { ?> disabled="disabled"<?php } ?>>
                                            <?php } ?>    				
                                                </td>
                                        <?php } ?>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                            <a class="btn btn-lg btn-primary btn-add mb-20" href="#<?php echo $table; ?>"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span> Aggiungi <?php echo $table; ?></a>


                                    <?php
                                    $sysprinters = get_system_printers();
                                    if (!empty($sysprinters)) {
                                        ?>
                                <div class="input-group">
                                    <select name="<?php echo $table; ?>-sistema" id="<?php echo $table; ?>-sistema" class="form-control">
                                <?php
                                // aggiungo le stampanti del server
                                foreach ($sysprinters as $pname) {
                                    $gia_presente = false;
                                    foreach ($rows as $aprinter) {
                                        if ($pname == $aprinter['nome']) {
                                            $gia_presente = true;
                                        }
                                    }
                                    if (!$gia_presente) {
                                        ?>
                                                <option value="<?php echo $pname; ?>"><?php echo $pname; ?></option>
                                        <?php
                                    }
                                }
                                ?>
                                    </select>
                                    <span class="input-group-btn">
                                        <a class="btn btn btn-primary btn-import-printer" href="#<?php echo $table; ?>-sistema"><span class="glyphicon glyphicon-import" aria-hidden="true"></span> Importa</a>
                                    </span>
                                </div>
                                            <?php } ?>
                        </div> 


                                        <?php $table = 'reparti'; ?>
                        <div role="tabpanel" class="tab-pane well well-white" id="tab-<?php echo $table; ?>">
    <?php
    $table = 'reparti';
    $rows = find_by($table);
    $fields = db_get_fields($table);
    //var_dump($fields);
    // rimuovo la colonna time
    if (isset($fields['id'])) {
        unset($fields['id']);
    }

    //print_import_export($table);
    ?>
                            <table id="<?php echo $table; ?>" class="table table-striped table-hover table-condensed table-bordered">
                                <thead>
                                    <tr class="info">
                                                <?php foreach ($fields as $field => $afield) { ?>
                                            <th>
                                                <?php if (isset($desc_campi[$field])) { ?><abbr title="<?php echo htmlspecialchars($desc_campi[$field]); ?>"><?php } else if ($afield['commento']) { ?><abbr title="<?php echo $afield['commento']; ?>"><?php } ?>
                                                <?php echo $field; ?>
                                                <?php if (isset($desc_campi[$field]) || $afield['commento']) { ?></abbr><?php } ?>    				
                                            </th>
                                            <?php } ?>
                                    </tr>
                                </thead>
                                <tbody>
                                            <?php foreach ($rows as $rid => $row) { ?>
                                        <tr id="<?php echo $table . '-' . $rid; ?>">
                                                <?php foreach ($fields as $field => $afield) { ?>
                                                <td>
                                                    <?php switch ($field) {
                                                        case 'attivo':
                                                            ?>
                                                            <a href="#<?php echo $table; ?>-<?php echo $rid; ?>" class="btn btn-danger btn-confirm btn-delete"><span class="glyphicon glyphicon-trash" aria-hidden="true"></span></a> 
                                                    <?php
                                                    break;
                                                case 'fila':
                                                case 'ricevuta':
                                                case 'coperti':
                                                    $values = array('0' => 'No', '1' => 'Si');
                                                    ?>
                                                            <select class="form-control field-<?php echo $field; ?>" name="<?php echo $table; ?>[<?php echo $row['id']; ?>][<?php echo $field; ?>]">
                    <?php foreach ($values as $value => $label) { ?><option value="<?php echo $value; ?>"<?php if ($value == $row[$field]) { ?> selected="selected"<?php } ?>><?php echo $label; ?></option><?php } ?>	    						
                                                            </select>  
                    <?php
                    break;
                case 'id_stampanti':
                    $values = find_by('stampanti');
                    array_unshift($values, array('nome' => 'Predefinita', 'ip' => '###'));
                    ?>
                                                            <select class="form-control field-<?php echo $field; ?>" name="<?php echo $table; ?>[<?php echo $row['id']; ?>][<?php echo $field; ?>]">
                                <?php foreach ($values as $value => $stampante) { ?><option value="<?php echo $value; ?>"<?php if ($value == $row[$field]) { ?> selected="selected"<?php } ?>>[<?php echo $stampante['ip']; ?>] <?php echo $stampante['nome']; ?></option><?php } ?>	    						
                                                            </select> 
                    <?php break;
                default:
                    ?>
                                                            <input class="form-control field-<?php echo $field; ?><?php if ($field == 'id') { ?> disabled<?php } ?>" type="text" value="<?php echo $row[$field]; ?>" name="<?php echo $table; ?>[<?php echo $row['id']; ?>][<?php echo $field; ?>]"<?php /* if ($field == 'id') { ?> disabled="disabled"<?php } */ ?>>
            <?php } ?>    				
                                                </td>
        <?php } ?>
                                        </tr>
    <?php } ?>
                                </tbody>
                            </table>

                        </div>

                    </div>		

                    <input type="hidden" value="true" name="salva">
                    <input type="submit" class="btn btn-lg btn-warning pull-right mt-20" value="SALVA">

                </form>

    <?php
} else { // accesso negato, deve autenticarsi
    echo get_login_form();
}
?>	

        </div>

    </body>

</html>