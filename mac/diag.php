<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if(session_status() == PHP_SESSION_NONE) session_start();

$proj = str_replace('\\','/',dirname(__DIR__));
$root = str_replace('\\','/',rtrim($_SERVER['DOCUMENT_ROOT'],'/'));
$base = rtrim(str_replace($root,'',$proj),'/').'/';
if($base===''||$base==='//') $base='/';
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Diag</title>
<style>body{font-family:monospace;padding:20px} .ok{color:green;font-weight:bold} .fail{color:red;font-weight:bold} table{border-collapse:collapse} td,th{border:1px solid #ccc;padding:6px 14px}</style>
</head>
<body>
<h3>VPS / Server Diagnostic</h3>
<table>
<tr><th>Check</th><th>Value</th><th>Result</th></tr>
<tr>
  <td>session_status</td>
  <td><?= session_status() ?></td>
  <td><?= session_status()==PHP_SESSION_ACTIVE ? '<span class="ok">ACTIVE</span>' : '<span class="fail">NOT ACTIVE</span>' ?></td>
</tr>
<tr>
  <td>user_token (session)</td>
  <td><?= isset($_SESSION['user_token']) ? 'SET' : '-- EMPTY --' ?></td>
  <td><?= isset($_SESSION['user_token']) ? '<span class="ok">LOGGED IN</span>' : '<span class="fail">NOT LOGGED IN</span>' ?></td>
</tr>
<tr>
  <td>PHPSESSID cookie</td>
  <td><?= isset($_COOKIE['PHPSESSID']) ? 'YES' : 'NO' ?></td>
  <td><?= isset($_COOKIE['PHPSESSID']) ? '<span class="ok">OK</span>' : '<span class="fail">COOKIE MISSING</span>' ?></td>
</tr>
<tr>
  <td>DOCUMENT_ROOT</td>
  <td><?= htmlspecialchars($root) ?></td>
  <td>-</td>
</tr>
<tr>
  <td>Project Root</td>
  <td><?= htmlspecialchars($proj) ?></td>
  <td>-</td>
</tr>
<tr>
  <td>basePath (computed)</td>
  <td><?= htmlspecialchars($base) ?></td>
  <td><?= ($base==='/' || preg_match('#^/[a-z0-9/_-]+/$#i',$base)) ? '<span class="ok">OK</span>' : '<span class="fail">WRONG</span>' ?></td>
</tr>
<tr>
  <td>PHP_SELF</td>
  <td><?= htmlspecialchars($_SERVER['PHP_SELF']) ?></td>
  <td>-</td>
</tr>
<tr>
  <td>PHP Version</td>
  <td><?= phpversion() ?></td>
  <td>-</td>
</tr>
</table>
<p style="color:red;margin-top:20px"><b>Use ke baad is file ko DELETE kar dena!</b></p>
</body>
</html>
