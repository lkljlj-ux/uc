<?php
// TEMPORARY DIAGNOSTIC FILE - DELETE AFTER USE
ob_start();
session_start();

$proj = str_replace('\\','/',dirname(__DIR__));
$root = str_replace('\\','/',rtrim($_SERVER['DOCUMENT_ROOT'],'/'));
$base = rtrim(str_replace($root,'',$proj),'/').'/';
if($base===''||$base==='//') $base='/';

$dbOk = false;
$dbErr = '';
$link2 = @mysqli_connect(
    '127.0.0.1',
    'aadhaar_test',
    'ftYI6.B#s2K&',
    'aadhaar_test'
);
if($link2){ $dbOk=true; mysqli_close($link2); }
else { $dbErr = mysqli_connect_error(); }

header_remove();
ob_end_clean();
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Diagnostic</title>
<style>body{font-family:monospace;padding:20px;} .ok{color:green;font-weight:bold;} .fail{color:red;font-weight:bold;} table{border-collapse:collapse;} td,th{border:1px solid #ccc;padding:6px 12px;text-align:left;}</style>
</head>
<body>
<h3>VPS Diagnostic Report</h3>
<table>
<tr><th>Item</th><th>Value</th><th>Status</th></tr>
<tr>
  <td>Session Status</td>
  <td><?= session_status() == PHP_SESSION_ACTIVE ? 'ACTIVE' : 'INACTIVE ('.session_status().')' ?></td>
  <td><?= session_status() == PHP_SESSION_ACTIVE ? '<span class="ok">OK</span>' : '<span class="fail">FAIL</span>' ?></td>
</tr>
<tr>
  <td>user_token in session</td>
  <td><?= isset($_SESSION['user_token']) ? htmlspecialchars($_SESSION['user_token']) : '-- NOT SET --' ?></td>
  <td><?= isset($_SESSION['user_token']) ? '<span class="ok">LOGGED IN</span>' : '<span class="fail">NOT LOGGED IN</span>' ?></td>
</tr>
<tr>
  <td>PHPSESSID cookie</td>
  <td><?= isset($_COOKIE['PHPSESSID']) ? 'SET ('.substr($_COOKIE['PHPSESSID'],0,8).'...)' : '-- NOT SET --' ?></td>
  <td><?= isset($_COOKIE['PHPSESSID']) ? '<span class="ok">OK</span>' : '<span class="fail">MISSING</span>' ?></td>
</tr>
<tr>
  <td>DOCUMENT_ROOT</td>
  <td><?= htmlspecialchars($root) ?></td>
  <td>-</td>
</tr>
<tr>
  <td>Project Root (dirname(__DIR__))</td>
  <td><?= htmlspecialchars($proj) ?></td>
  <td>-</td>
</tr>
<tr>
  <td>Computed basePath</td>
  <td><?= htmlspecialchars($base) ?></td>
  <td><?= ($base === '/' || preg_match('#^/[a-z0-9_-]+/$#i',$base)) ? '<span class="ok">OK</span>' : '<span class="fail">WRONG - basePath invalid!</span>' ?></td>
</tr>
<tr>
  <td>PHP_SELF</td>
  <td><?= htmlspecialchars($_SERVER['PHP_SELF']) ?></td>
  <td>-</td>
</tr>
<tr>
  <td>Database (current database.php creds)</td>
  <td><?= $dbOk ? 'Connected' : 'FAILED: '.htmlspecialchars($dbErr) ?></td>
  <td><?= $dbOk ? '<span class="ok">OK</span>' : '<span class="fail">FAIL - Update database.php!</span>' ?></td>
</tr>
<tr>
  <td>PHP Version</td>
  <td><?= phpversion() ?></td>
  <td>-</td>
</tr>
</table>

<br>
<p><b>Agar "user_token NOT SET" show ho raha hai aur aap logged in hain</b> — toh session cookie VPS pe kaam nahi kar rhi. Iska matlab: <code>mac/</code> subfolder pe alag session path set hai.</p>
<p><b>Agar "basePath WRONG" show ho raha hai</b> — toh sidebar links galat URLs pe ja rahe hain.</p>
<p style="color:red;"><b>Is file ko use ke baad DELETE kar dena (security risk).</b></p>
</body>
</html>
