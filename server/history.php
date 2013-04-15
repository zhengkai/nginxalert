#! /usr/bin/env php
<?php
require_once __DIR__.'/common.inc.php';
require_once __DIR__.'/dbz.inc.php';
require_once __DIR__.'/fn_getdata.inc.php';

$iNowHour = strtotime(date('Y-m-d H:00:00', $_SERVER['REQUEST_TIME']));

$oDB = new DBz();

foreach (range(0, 20) as $iDiff) {

	$iTime = $iNowHour - 3600 * $iDiff;

	$iDate = date('Ymd', $iTime);
	$iHour = date('G', $iTime);

	$sQuery = 'SELECT date FROM history WHERE date = '.$iDate.' AND hour = '.$iHour;
	if ($oDB->getSingle($sQuery)) {
		continue;
	}

	$sQueryWhere = 'WHERE time_create >= '.$iTime.' '
		.'AND time_create < '.($iTime + 3600);
	$lData = getData($sQueryWhere);

	echo $sQueryWhere, ' : ', strlen(json($lData)), "\n";

	$sQuery = 'INSERT INTO history '
		.'SET date = '.$iDate.', '
		.'hour = '.$iHour.', '
		.'content = "'.addslashes(gzcompress(json($lData), 9)).'"';

	$oDB->exec($sQuery);
}
