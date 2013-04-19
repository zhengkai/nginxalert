#! /usr/bin/env php
<?php
require_once __DIR__.'/common.inc.php';
require_once __DIR__.'/dbz.inc.php';
require_once __DIR__.'/fn_getdata.inc.php';

$oDB = new DBz();
$sQuery = 'SELECT MAX(time_create) as last FROM log';
$iLast = $oDB->getSingle($sQuery);
if ($iLast < 1) {
	die('db error, exit');
}

$iLastHour = strtotime(date('Y-m-d H:00:00', $iLast));

foreach (range(0, 30) as $iDiff) {

	$iTime = $iLastHour - 3600 * $iDiff;

	$iDate = date('Ymd', $iTime);
	$iHour = date('G', $iTime);

	echo date('Y-m-d H:i:s', $iTime), ' : ';

	$sQuery = 'SELECT date FROM history WHERE date = '.$iDate.' AND hour = '.$iHour;
	if ($oDB->getSingle($sQuery)) {
		echo "skip\n";
		continue;
	}

	$lData = getData($iTime, 3600);

	$lData['timestamp'] = $iTime;

	echo strlen(json($lData)), "\n";

	$sQuery = 'INSERT INTO history '
		.'SET date = '.$iDate.', '
		.'hour = '.$iHour.', '
		.'content = "'.addslashes(gzcompress(json($lData), 9)).'"';

	$oDB->exec($sQuery);
}
