<?php
header('Content-Type: text/plain; charset=utf8');
header('Server: Soulogic');

$aUpload =& $_FILES['upload'];
if (!$aUpload) {
	die('no upload');
}

if (empty($aUpload['size'])) {
	die('empty file');
}

if (!empty($aUpload['error'])) {
	print_r($aUpload);
	die('upload failed '.$aUpload['error']);
}

$lContent = file($aUpload['tmp_name'], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

// fastcgi_finish_request();

require_once dirname(__DIR__).'/dbz.inc.php';

$lURI = array();

$aRowKey = array(
	'time_create',
	'time_cost',
	'in',
	'out',
	'http_code',
	'uri',
);

$oDB = new DBz();

$lFill = array();

function fill($lFill, $aRowKey, $oDB) {

	$lFill = array_map(function ($aRow) {
		return '('.implode(', ', $aRow).')';
	}, $lFill);

	$sQuery = 'INSERT IGNORE INTO `log` '
		.'(`'.implode('`, `', $aRowKey).'`) '
		.'VALUES '
		.implode(', ', $lFill);

	$oDB->exec($sQuery);
}

foreach ($lContent as $sLine) {
	$aRow = explode(' ', $sLine, 6);
	if (count($aRow) < 6) {
		continue;
	}

	$aRow = array_combine($aRowKey, $aRow);

	$sURIHash = md5($aRow['uri']);
	$iURI =& $lURI[$sURIHash];
	if (!$iURI) {
		$sQuery = 'SELECT id FROM uri WHERE hash = 0x'.$sURIHash;
		$iURI = $oDB->getSingle($sQuery);
		if (!$iURI) {
			$sQuery = 'INSERT INTO uri '
				.'SET hash = 0x'.$sURIHash.', '
				.'uri = "'.addslashes($aRow['uri']).'"';
			$iURI = $oDB->getInsertID($sQuery);
		}
	}
	$aRow['uri'] = $iURI;
	unset($iURI);

	$aRow['time_cost'] = $aRow['time_cost'] * 1000;

	$aRow = array_values($aRow);
	$aRow = array_map('round', $aRow);

	$lFill[] = $aRow;

	if (count($lFill) > 10000) {
		fill($lFill, $aRowKey, $oDB);
		$lFill = array();
	}
}

if ($lFill) {
	fill($lFill, $aRowKey, $oDB);
}
