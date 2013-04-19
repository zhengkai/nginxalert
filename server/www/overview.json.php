<?php
header('Content-Type: application/json; charset=utf8');
header('Server: Soulogic');

require_once dirname(__DIR__).'/common.inc.php';
require_once dirname(__DIR__).'/dbz.inc.php';
require_once dirname(__DIR__).'/fn_getdata.inc.php';

$oDB = new DBz();
$sQuery = 'SELECT UNIX_TIMESTAMP() as now, MAX(time_create) as last FROM log';
list($iNow, $iLast) = array_values($oDB->getRow($sQuery) ?: [0, 0]);
$iDiff = $iNow - $iLast;

json_dump([
	'diff' => $iDiff,
	'1' => getData($iLast - 60, 60),
	'10' => getData($iLast - 600, 600),
	'60' => getData($iLast - 3600, 3600),
]);
