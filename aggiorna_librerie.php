<?php
include("CONFIG.php");
include("lib/dbconnection.php");

if (!is_user()) {
    header("Location: index.php");
    exit;
}

$logs = [];
function add_log($msg, $type = 'info') {
    global $logs;
    $logs[] = ['msg' => $msg, 'type' => $type];
}

add_log("Inizio aggiornamento librerie...", "info");

// Assicuriamoci che la cartella lib sia scrivibile
$lib_dir = __DIR__.'/lib';
if (!is_writable($lib_dir)) {
    @chmod($lib_dir, 0777);
}

// Funzione sicura per il download dei file
function download_file($url, $dest) {
    // Rimuove il file esistente se presente (previene errori di permessi in sovrascrittura)
    if (file_exists($dest)) {
        @unlink($dest);
    }
    
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        $fp = @fopen($dest, 'wb');
        if (!$fp) return false;
        
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
        $res = curl_exec($ch);
        if (is_resource($ch)) { curl_close($ch); }
        fclose($fp);
        return $res;
    } else {
        return file_put_contents($dest, @file_get_contents($url));
    }
}

// Funzione per ottenere l'ultimo tag 2.x di Mobile_Detect
function get_latest_mobile_detect_v2() {
    $api_url = "https://api.github.com/repos/serbanghita/Mobile-Detect/tags";
    if (function_exists('curl_init')) {
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'PHP-App');
        $res = curl_exec($ch);
        if (is_resource($ch)) { curl_close($ch); }
        if ($res) {
            $tags = json_decode($res, true);
            foreach ($tags as $tag) {
                if (strpos($tag['name'], '2.') === 0 || strpos($tag['name'], 'v2.') === 0) {
                    return str_replace('v', '', $tag['name']);
                }
            }
        }
    }
    return "2.8.41"; // Fallback
}

// Funzione per ottenere l'ultimo tag 6.x di TCPDF (versione legacy no-composer)
function get_latest_tcpdf_v6() {
    $api_url = "https://api.github.com/repos/tecnickcom/TCPDF/tags";
    if (function_exists('curl_init')) {
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'PHP-App');
        $res = curl_exec($ch);
        if (is_resource($ch)) { curl_close($ch); }
        if ($res) {
            $tags = json_decode($res, true);
            foreach ($tags as $tag) {
                if (strpos($tag['name'], '6.') === 0) {
                    return $tag['name'];
                }
            }
        }
    }
    return "6.11.3"; // Fallback
}

// Funzione per ottenere il link dell'ultima versione di SumatraPDF portatile
function get_latest_sumatra_url() {
    $sumatra_url = "https://www.sumatrapdfreader.org/dl/rel/3.6.1/SumatraPDF-3.6.1-64.zip"; // Fallback
    if (function_exists('curl_init')) {
        $ch = curl_init("https://www.sumatrapdfreader.org/download-free-pdf-viewer");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
        $html = curl_exec($ch);
        if (is_resource($ch)) { curl_close($ch); }
        if ($html) {
            if (preg_match('/href="(\/dl\/rel\/[0-9\.]+\/SumatraPDF-[0-9\.]+-64\.zip)"/', $html, $matches)) {
                $sumatra_url = "https://www.sumatrapdfreader.org" . $matches[1];
            }
        }
    }
    return $sumatra_url;
}

// TCPDF
$tcpdf_path = __DIR__.'/lib/tcpdf';
$latest_tcpdf = get_latest_tcpdf_v6();
add_log("Download archivio TCPDF (v{$latest_tcpdf}) in corso da GitHub...", "info");
$zip_url = "https://github.com/tecnickcom/TCPDF/archive/refs/tags/{$latest_tcpdf}.zip";
$zip_file = __DIR__.'/lib/tcpdf.zip';

if (download_file($zip_url, $zip_file)) {
    add_log("Scaricato archivio TCPDF da GitHub con successo.", "success");
    
    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive;
        if ($zip->open($zip_file) === TRUE) {
            $zip->extractTo(__DIR__.'/lib/');
            $zip->close();
            @unlink($zip_file);
            
            if (file_exists(__DIR__.'/lib/TCPDF-'.$latest_tcpdf)) {
                if (file_exists($tcpdf_path)) {
                    @rename($tcpdf_path, $tcpdf_path.'_old_'.time());
                    add_log("Vecchia cartella TCPDF rinominata per backup.", "info");
                }
                rename(__DIR__.'/lib/TCPDF-'.$latest_tcpdf, $tcpdf_path);
                add_log("Estrazione e aggiornamento TCPDF completati con successo.", "success");
            } else {
                add_log("Errore: cartella TCPDF-{$latest_tcpdf} non trovata nello zip.", "danger");
            }
        } else {
            add_log("Errore nell'apertura del file ZIP di TCPDF.", "danger");
        }
    } else {
        add_log("Classe ZipArchive non disponibile su questo server. Impossibile estrarre TCPDF.", "danger");
    }
} else {
    add_log("Errore durante il download dell'archivio TCPDF.", "danger");
}

// SumatraPDF
$sumatra_path = __DIR__.'/lib/SumatraPDF.exe';
$sumatra_url = get_latest_sumatra_url();
add_log("Download SumatraPDF in corso da: " . $sumatra_url, "info");

if (strpos($sumatra_url, '.zip') !== false) {
    $temp_zip = __DIR__.'/lib/sumatra_temp.zip';
    if (download_file($sumatra_url, $temp_zip)) {
        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive;
            if ($zip->open($temp_zip) === TRUE) {
                $zip->extractTo(__DIR__.'/lib/');
                $zip->close();
                // Rinomina l'eseguibile appena estratto
                foreach (glob(__DIR__.'/lib/SumatraPDF-*-64.exe') as $exe) {
                    rename($exe, $sumatra_path);
                }
                unlink($temp_zip);
                add_log("SumatraPDF scaricato, estratto e salvato con successo.", "success");
            } else {
                add_log("Errore estrazione ZIP SumatraPDF.", "danger");
            }
        } else {
            add_log("Classe ZipArchive non disponibile, impossibile estrarre SumatraPDF.", "danger");
        }
    } else {
        add_log("Errore durante il download del file ZIP di SumatraPDF.", "danger");
    }
} else {
    // Fallback se per qualche motivo trova direttamente l'exe (raro sul nuovo sito)
    if (download_file($sumatra_url, $sumatra_path)) {
        add_log("SumatraPDF (exe) scaricato e salvato con successo.", "success");
    } else {
        add_log("Errore durante il download di SumatraPDF.", "danger");
    }
}

// Mobile_Detect
$mobile_detect_path = __DIR__.'/lib/Mobile_Detect.php';
$latest_md = get_latest_mobile_detect_v2();
$mobile_detect_url = "https://raw.githubusercontent.com/serbanghita/Mobile-Detect/{$latest_md}/Mobile_Detect.php";
add_log("Download Mobile_Detect (v{$latest_md}) in corso da: " . $mobile_detect_url, "info");
$res = download_file($mobile_detect_url, $mobile_detect_path);
if ($res) {
    add_log("Mobile_Detect scaricato e salvato con successo.", "success");
} else {
    add_log("Errore durante il download di Mobile_Detect.", "danger");
}

add_log("Operazioni terminate.", "info");

?>
<html>
<head>
<title>Report Aggiornamento Librerie</title>
<link rel="stylesheet" type="text/css" href="css/bootstrap.min.css" />
<link rel="stylesheet" href="css/font-awesome.min.css">
<link rel="stylesheet" href="css/base.css">
<style type="text/css">
	body { background-color: whitesmoke; }
	.log-container { margin-top: 50px; background: #fff; padding: 20px; border-radius: 5px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
	.log-entry { margin-bottom: 10px; padding: 10px; border-radius: 4px; }
	.log-entry.info { background: #e9edf2; border-left: 5px solid #5bc0de; }
	.log-entry.success { background: #eafbe1; border-left: 5px solid #5cb85c; }
	.log-entry.danger { background: #fbe1e1; border-left: 5px solid #d9534f; }
	.log-entry.warning { background: #fdf5e6; border-left: 5px solid #f0ad4e; }
</style>
</head>
<body>
<div class="container">
	<div class="row">
		<div class="col-md-8 col-md-offset-2 log-container">
            <h1 class="text-center mb-20"><i class="fa fa-refresh"></i> Report Aggiornamento</h1>
            <?php foreach($logs as $log) { ?>
                <div class="log-entry <?php echo $log['type']; ?>">
                    <?php echo htmlspecialchars($log['msg']); ?>
                </div>
            <?php } ?>
            <div class="text-center mt-20">
                <a href="index.php" class="btn btn-lg btn-primary"><span class="glyphicon glyphicon-home"></span> Torna alla Home</a>
            </div>
            
            <div class="mt-40" style="margin-top: 40px;">
                <hr>
                <h4 class="text-danger"><i class="fa fa-life-ring"></i> Problemi? Aggiornamento Manuale</h4>
                <p>Se l'aggiornamento automatico dovesse fallire per restrizioni tecniche del server, puoi installare o aggiornare le librerie manualmente scaricandole dai link sottostanti e inserendole nella cartella <strong><code>lib/</code></strong> del gestionale:</p>
                <ul style="line-height: 1.8;">
                    <li><strong>TCPDF:</strong> Scarica l'archivio della versione legacy (es. 6.11.3) da <a href="https://github.com/tecnickcom/TCPDF/tags" target="_blank">GitHub Tags</a>. Estrai il contenuto, rinomina la cartella estratta in <code>tcpdf</code> e posizionala dentro <code>lib/</code> (il percorso finale dovrà essere <code>lib/tcpdf/tcpdf.php</code>).</li>
                    <li><strong>SumatraPDF:</strong> Scarica l'ultima versione "Portable" a 64-bit in formato ZIP dal sito <a href="https://www.sumatrapdfreader.org/download-free-pdf-viewer" target="_blank">SumatraPDFReader.org</a>. Estrai lo zip e metti il file <code>SumatraPDF.exe</code> dentro <code>lib/</code>.</li>
                    <li><strong>Mobile_Detect:</strong> Scarica il singolo file (versione compatibile 2.x) da <a href="https://raw.githubusercontent.com/serbanghita/Mobile-Detect/2.8.41/Mobile_Detect.php" target="_blank">questo link (Tasto destro -> Salva con nome)</a>. Assicurati che si chiami <code>Mobile_Detect.php</code> e inseriscilo dentro <code>lib/</code>.</li>
                </ul>
            </div>
		</div>
	</div>
</div>
</body>
</html>
