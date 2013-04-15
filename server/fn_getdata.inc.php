<?php
function getData($sQueryWhere) {

	$lValidKey = [
		200,
		304,
	];
	$lAvgKey = [
		'time_cost',
		'in',
		'out',
	];

	$fnParse = function ($aRow) use ($lAvgKey) {

		$iNum = $aRow['num'];
		foreach ($lAvgKey as $sKey) {
			$aRow['avg'][$sKey] = $iNum ? round($aRow[$sKey] / $iNum) : 0;
		}
		foreach ($lAvgKey as $sKey) {
			$aRow['total'][$sKey] = $aRow[$sKey];
			unset($aRow[$sKey]);
		}
		unset($aRow['http_code']);

		return $aRow;
	};

	$lReturn = [];

	$oDB = new DBz();

	$sQuery = 'SELECT http_code, count(*) as num, SUM(time_cost) as time_cost, SUM(`in`) as `in`, SUM(`out`) as `out` '
		.'FROM log '
		.$sQueryWhere.' '
		.'GROUP BY http_code';

	$lData = $oDB->getAll($sQuery) ?: [];

	foreach ($lValidKey as $iKey) {
		$lData += [
			$iKey => [
				'http_code' => $iKey,
				'num' => 0,
				'time_cost' => 0,
				'in' => 0,
				'out' => 0,
			],
		];
	}

	$aTotal = array();
	foreach ($lData as $iKey => $aRow) {
		foreach ($aRow as $sKey => $iValue) {
			$iTotal =& $aTotal[$sKey];
			$iTotal += $iValue;
		}
	}
	unset($iTotal);

	$lData = array_map($fnParse, $lData);

	$lReturn['rate'] = $aTotal['num']
		? sprintf('%.02f', ($lData[200]['num'] + $lData[304]['num']) / $aTotal['num'] * 100)
		: '00.00';

	$lReturn['by_http_code'] = $lData;

	$sQuery = 'SELECT uri, count(*) as num, SUM(time_cost) as time_cost, SUM(`in`) as `in`, SUM(`out`) as `out` '
		.'FROM log '
		.$sQueryWhere.' AND http_code = 200 '
		.'GROUP BY uri';

	$lData = $oDB->getAll($sQuery) ?: [];
	$lData = array_map($fnParse, $lData);

	$sQuery = 'SELECT id, uri FROM uri LIMIT 10000';
	$lURI = $oDB->getAll($sQuery) ?: [];

	$lData = array_map(function ($aRow) use ($lURI) {
		$sURI =& $lURI[$aRow['uri']];
		$aRow['uri'] = $sURI ?: '(unknown)';
		return $aRow;
	}, $lData);

	uasort($lData, function ($a, $b) {
		return $b['avg']['time_cost'] - $a['avg']['time_cost'];
	});

	$lReturn['by_uri'] = $lData;

	return $lReturn;
}
