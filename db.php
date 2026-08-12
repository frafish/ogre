<?php
include_once("CONFIG.php");
include_once("lib/dbconnection.php");
include_once("lib/print.php");

if (!is_superman()) {
    header("Location: index.php");
    exit;
}

$db_dir = dirname(DB_FILE);
$bk_dir = dirname(DB_BACKUP);
if (!is_dir($bk_dir)) {
    mkdir($bk_dir, 0777, true);
}

if (isset($_REQUEST['azione'])) {
    switch ($_REQUEST['azione']) {
        case 'backup':
            $bk_name = 'ogre_manual_'.date('Y-m-d_H-i-s').'_db.sqlite';
            $bk_copy = $bk_dir.DIRECTORY_SEPARATOR.$bk_name;
            if(copy(DB_FILE, $bk_copy)) {
                @chmod($bk_copy, 0666);
                set_alert('Backup creato correttamente: '.$bk_name, 'success');
            } else {
                set_alert('Errore durante la creazione del backup.', 'danger');
            }
            header("Location: db.php");
            exit;
            
        case 'restore':
            if (isset($_REQUEST['file']) && !empty($_REQUEST['file'])) {
                $file = basename($_REQUEST['file']);
                $source = $bk_dir.DIRECTORY_SEPARATOR.$file;
                if (file_exists($source)) {
                    // Rinominiamo l'attuale
                    $safe_bk = $db_dir.DIRECTORY_SEPARATOR.'db_'.date('Y-m-d_H-i-s').'.sqlite';
                    rename(DB_FILE, $safe_bk);
                    
                    if (copy($source, DB_FILE)) {
                        @chmod(DB_FILE, 0666);
                        set_alert('Database ripristinato con successo dal file di backup: '.$file, 'success');
                    } else {
                        set_alert('Errore durante il ripristino del database.', 'danger');
                    }
                }
            }
            header("Location: db.php");
            exit;
            
        case 'restore_local':
            if (isset($_REQUEST['file']) && !empty($_REQUEST['file'])) {
                $file = basename($_REQUEST['file']);
                $source = $db_dir.DIRECTORY_SEPARATOR.$file;
                if (file_exists($source) && $source != DB_FILE) {
                    // Rinominiamo l'attuale
                    $safe_bk = $db_dir.DIRECTORY_SEPARATOR.'db_'.date('Y-m-d_H-i-s').'.sqlite';
                    rename(DB_FILE, $safe_bk);
                    
                    if (copy($source, DB_FILE)) {
                        @chmod(DB_FILE, 0666);
                        set_alert('Database ripristinato con successo dal file locale: '.$file, 'success');
                    } else {
                        set_alert('Errore durante il ripristino del database locale.', 'danger');
                    }
                }
            }
            header("Location: db.php");
            exit;
            
        case 'delete':
            if (isset($_REQUEST['file']) && !empty($_REQUEST['file'])) {
                $file = basename($_REQUEST['file']);
                $target = $bk_dir.DIRECTORY_SEPARATOR.$file;
                if (file_exists($target)) {
                    if (unlink($target)) {
                        set_alert('File di backup eliminato: '.$file, 'success');
                    } else {
                        set_alert('Impossibile eliminare il file.', 'danger');
                    }
                }
            }
            header("Location: db.php");
            exit;
    }
}

// Lettura dei file in db/
$db_files = array();
if (is_dir($db_dir) && $handle = opendir($db_dir)) {
    while (false !== ($entry = readdir($handle))) {
        if ($entry != "." && $entry != ".." && pathinfo($entry, PATHINFO_EXTENSION) == 'sqlite') {
            $is_current = ($db_dir.DIRECTORY_SEPARATOR.$entry == DB_FILE);
            $db_files[] = array(
                'name' => $entry,
                'file' => $entry,
                'size' => filesize($db_dir.DIRECTORY_SEPARATOR.$entry),
                'time' => filemtime($db_dir.DIRECTORY_SEPARATOR.$entry),
                'is_current' => $is_current
            );
        }
    }
    closedir($handle);
}

// Ordina per data (dal più recente al più vecchio)
usort($db_files, function($a, $b) {
    return $b['time'] - $a['time'];
});

// Lettura dei file in backup/
$bk_files = array();
if ($handle = opendir($bk_dir)) {
    while (false !== ($entry = readdir($handle))) {
        if ($entry != "." && $entry != ".." && pathinfo($entry, PATHINFO_EXTENSION) == 'sqlite') {
            $bk_files[] = array(
                'name' => $entry,
                'file' => $entry,
                'size' => filesize($bk_dir.DIRECTORY_SEPARATOR.$entry),
                'time' => filemtime($bk_dir.DIRECTORY_SEPARATOR.$entry)
            );
        }
    }
    closedir($handle);
}

// Ordina per data (dal più recente al più vecchio)
usort($bk_files, function($a, $b) {
    return $b['time'] - $a['time'];
});

function formatBytes($size, $precision = 2) {
    $base = log($size, 1024);
    $suffixes = array('', 'KB', 'MB', 'GB', 'TB');   
    return round(pow(1024, $base - floor($base)), $precision) .' '. $suffixes[floor($base)];
}
?>
<html>
    <head>
        <title>Gestione Database</title>
        <link rel="stylesheet" type="text/css" href="css/bootstrap.min.css" />
        <link rel="stylesheet" type="text/css" href="css/base.css" />
        <script src="js/jquery-1.11.1.min.js"></script>
        <script src="js/bootstrap.min.js"></script>
    </head>
    <body>
        
        <div class="container-fluid" id="principale">
            <?php
            $utente = get_user();
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

            <a href="/" class="btn btn-default btn-lg btn-back pull-left admin absolute"><span class="glyphicon glyphicon-backward" aria-hidden="true"></span> Indietro</a>
            <h1 class="title">Gestione Database</h1>
            
            <?php print_alerts(); ?>
            
            <div class="row">
                <!-- Colonna sinistra: DB Attuali -->
                <div class="col-md-6">
                    <div class="well well-white">
                        <div class="row mb-20">
                            <div class="col-md-12">
                                <h3 class="mt-0"><span class="glyphicon glyphicon-hdd"></span> Database Locali (`db/`)</h3>
                                <p class="text-muted">I database presenti nella cartella di lavoro.</p>
                                <a href="pma/index.php" target="_blank" class="btn btn-primary btn-sm"><span class="glyphicon glyphicon-folder-open" aria-hidden="true"></span> Apri cartella in phpLiteAdmin</a>
                            </div>
                        </div>
                        
                        <table class="table table-striped table-hover table-bordered">
                            <thead>
                                <tr class="info">
                                    <th>File</th>
                                    <th>Data Modifica</th>
                                    <th>Dimensione</th>
                                    <th width="150">Azioni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($db_files as $f) { ?>
                                <tr <?php if($f['is_current']) echo 'class="success text-bold"'; ?>>
                                    <td><?php echo $f['name']; ?></td>
                                    <td><?php echo date('d/m/Y H:i:s', $f['time']); ?></td>
                                    <td><?php echo formatBytes($f['size']); ?></td>
                                    <td>
                                        <?php if (!$f['is_current']) { ?>
                                            <a href="?azione=restore_local&file=<?php echo urlencode($f['file']); ?>" class="btn btn-warning btn-xs" onclick="return confirm('ATTENZIONE: Sei sicuro di voler SOVRASCRIVERE il database IN USO con questo file locale? I dati correnti andranno persi (verrà comunque creato un backup di sicurezza)!');" title="Ripristina su quello in uso"><span class="glyphicon glyphicon-open" aria-hidden="true"></span> Ripristina</a>
                                        <?php } ?>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Colonna destra: Backup -->
                <div class="col-md-6">
                    <div class="well well-white">
                        <div class="row mb-20">
                            <div class="col-md-12">
                                <h3 class="mt-0"><span class="glyphicon glyphicon-compressed"></span> File di Backup (`backup/`)</h3>
                                <p class="text-muted">Copie di sicurezza salvate (manuali o da chiusura sessione).</p>
                                <a href="?azione=backup" class="btn btn-success btn-sm"><span class="glyphicon glyphicon-floppy-save" aria-hidden="true"></span> Crea Backup Ora</a>
                            </div>
                        </div>
                        
                        <table class="table table-striped table-hover table-bordered">
                            <thead>
                                <tr class="warning">
                                    <th>File</th>
                                    <th>Data Modifica</th>
                                    <th>Dimensione</th>
                                    <th width="150">Azioni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($bk_files)) { ?>
                                    <tr><td colspan="4" class="text-center text-muted">Nessun backup trovato.</td></tr>
                                <?php } else { ?>
                                    <?php foreach ($bk_files as $f) { ?>
                                    <tr>
                                        <td><?php echo $f['name']; ?></td>
                                        <td><?php echo date('d/m/Y H:i:s', $f['time']); ?></td>
                                        <td><?php echo formatBytes($f['size']); ?></td>
                                        <td>
                                            <a href="?azione=restore&file=<?php echo urlencode($f['file']); ?>" class="btn btn-warning btn-xs mb-5" onclick="return confirm('ATTENZIONE: Sei sicuro di voler SOVRASCRIVERE il database IN USO con questo backup? I dati correnti andranno persi!');" title="Ripristina su quello in uso"><span class="glyphicon glyphicon-open" aria-hidden="true"></span></a>
                                            <a href="?azione=delete&file=<?php echo urlencode($f['file']); ?>" class="btn btn-danger btn-xs mb-5" onclick="return confirm('Eliminare definitivamente questo backup?');"><span class="glyphicon glyphicon-trash" aria-hidden="true"></span></a>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
        </div>
    </body>
</html>
