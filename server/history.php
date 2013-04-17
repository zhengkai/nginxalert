#! /usr/bin/env php
<?php
require_once __DIR__.'/common.inc.php';
require_once __DIR__.'/dbz.inc.php';
require_once __DIR__.'/fn_getdata.inc.php';

$iNow = $_SERVER['REQUEST_TIME'];
$iNow = 1366000622;

$iNowHour = strtotime(date('Y-m-d H:00:00', $iNow));

$oDB = new DBz();

foreach (range(0, 20) as $iDiff) {

	$iTime = $iNowHour - 3600 * $iDiff;

	$iDate = date('Ymd', $iTime);
	$iHour = date('G', $iTime);

	echo date('Y-m-d H:i:s', $iTime), ' : ';

	$sQuery = 'SELECT date FROM history WHERE date = '.$iDate.' AND hour = '.$iHour;
	if ($oDB->getSingle($sQuery)) {
		echo "skip\n";
		continue;
	}

	$lData = getData($iTime, 3600);

	echo strlen(json($lData)), "\n";

	$sQuery = 'INSERT INTO history '
		.'SET date = '.$iDate.', '
		.'hour = '.$iHour.', '
		.'content = "'.addslashes(gzcompress(json($lData), 9)).'"';

	$oDB->exec($sQuery);
}
