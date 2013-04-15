<?php
header('Content-Type: text/plain; charset=utf8');
header('Server: Soulogic');

require_once dirname(__DIR__).'/common.inc.php';
require_once dirname(__DIR__).'/dbz.inc.php';
require_once dirname(__DIR__).'/fn_getdata.inc.php';

$iDate = strtotime(date('Y-m-d', $_SERVER['REQUEST_TIME']));

$iNow = strtotime(date('Y-m-d', $_SERVER['REQUEST_TIME']));

$sQueryWhere = 'WHERE time_create > '.($iNow - 60);
$lData = getData($sQueryWhere);

json_dump($lData);

$sQueryWhere = 'WHERE time_create > '.($iNow - 600);
$lData = getData($sQueryWhere);

json_dump($lData);

$sQueryWhere = 'WHERE time_create > '.($iNow - 3600);
$lData = getData($sQueryWhere);

json_dump($lData);

echo strlen(json($lData));
