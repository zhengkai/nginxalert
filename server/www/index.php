<?php
//header('Content-Type: text/plain; charset=utf8');
header('Server: Soulogic');

require_once dirname(__DIR__).'/common.inc.php';
require_once dirname(__DIR__).'/dbz.inc.php';
require_once dirname(__DIR__).'/fn_getdata.inc.php';
require_once dirname(__DIR__).'/tpl_table.inc.php';
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8" />
<title>HTTP Monitor</title>
<link rel="stylesheet" href="style.css" type="text/css" />
</head>
<body>

<p>last record in <?php
$oDB = new DBz();
$sQuery = 'SELECT UNIX_TIMESTAMP() as now, MAX(time_create) as last FROM log';
list($iNow, $iLast) = array_values($oDB->getRow($sQuery) ?: [0, 0]);
$iDiff = $iNow - $iLast;
if ($iDiff < 120) {
	echo $iDiff;
} else {
	echo '<span style="font-weight: bold; color: red;">'.number_format($iDiff).'</span>';
}
?> sec ago</p>

<p>last 1 min</p>
<?php
tplTable($iLast - 60,   60);
?>

<p>last 10 mins</p>
<?php
tplTable($iLast - 600,  600);
?>

<p>last 1 hour</p>
<?php
tplTable($iLast - 3600, 3600);
?>

</body>
</html>
