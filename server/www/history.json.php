<?php
header('Content-Type: application/json; charset=utf8');
header('Server: Soulogic');

require_once dirname(__DIR__).'/common.inc.php';
require_once dirname(__DIR__).'/dbz.inc.php';
require_once dirname(__DIR__).'/fn_getdata.inc.php';
require_once dirname(__DIR__).'/tpl_table.inc.php';

$oDB = new DBz();

$sQuery = 'SELECT * FROM history ORDER BY date, hour LIMIT 744';
$lHistory = $oDB->getAll($sQuery, FALSE) ?: [];

$lHistory = array_map(function ($aHistory) {
	$aHistory['content'] = json_decode(gzuncompress($aHistory['content']), TRUE);
	return $aHistory;
}, $lHistory);

json_dump($lHistory);
