<?php
function getData($iTime, $iOffset) {

	if ($iOffset < 1) {
		$iOffset = 1;
	}
	$iOffset--;

	$sQueryWhere = 'WHERE time_create BETWEEN '.$iTime.' AND '.($iTime + $iOffset);

	$lValidKey = [
		200,
		304,
	];
	$lAvgKey = [
		'time_cost',
		'in',
		'out',
	];

	$fnParse = function ($aRow) use ($lAvgKey, $iOffset) {

		$iNum = $aRow['num'];
		$aRow += [
			'max' => [],
			'avg' => [],
			'total' => [],
		];

		$aRow['req_pre_sec'] = sprintf('%.02f', $iNum / ($iOffset ?: 1));

		foreach ($lAvgKey as $sKey) {
			$aRow['max'][$sKey] = $aRow['max_'.$sKey];
			unset($aRow['max_'.$sKey]);
		}
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

	$sQuery = 'SELECT http_code, '
		.'count(*) as num, '
		.'SUM(time_cost) as time_cost, '
		.'SUM(`in`) as `in`, '
		.'SUM(`out`) as `out`, '
		.'MAX(time_cost) as max_time_cost, '
		.'MAX(`in`) as `max_in`, '
		.'MAX(`out`) as `max_out` '
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
				'max_time_cost' => 0,
				'max_in' => 0,
				'max_out' => 0,
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
	unset($aTotal['http_code']);

	$lReturn['total'] = $fnParse($aTotal);

	$iSUM = $aTotal['num'];

	$lData = array_map($fnParse, $lData);

	$lData  = array_map(function ($aRow) use ($iSUM) {
		$aRow['rate'] = sprintf('%.02f%%', $iSUM ? ($aRow['num'] / $iSUM * 100) : 0);
		return $aRow;
	}, $lData);

	$lReturn['total']['rate'] = sprintf('%.02f%%', $iSUM ? (($lData[200]['num'] + $lData[304]['num']) / $iSUM * 100) : 0);

	$lReturn['by_http_code'] = $lData;

	$sQuery = 'SELECT uri, '
		.'count(*) as num, '
		.'SUM(time_cost) as time_cost, '
		.'SUM(`in`) as `in`, '
		.'SUM(`out`) as `out`, '
		.'MAX(time_cost) as max_time_cost, '
		.'MAX(`in`) as `max_in`, '
		.'MAX(`out`) as `max_out` '
		.'FROM log '
		.$sQueryWhere.' AND http_code = 200 '
		.'GROUP BY uri '
		.'LIMIT 20';

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
