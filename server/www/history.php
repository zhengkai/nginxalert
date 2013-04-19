<?php
//header('Content-Type: text/plain; charset=utf8');
header('Server: Soulogic');

require_once dirname(__DIR__).'/common.inc.php';
require_once dirname(__DIR__).'/dbz.inc.php';
require_once dirname(__DIR__).'/fn_getdata.inc.php';
require_once dirname(__DIR__).'/tpl_table.inc.php';

$oDB = new DBz();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8" />
<title>HTTP Monitor</title>
<link rel="stylesheet" href="style.css" type="text/css" />
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
</head>
<body>

<p>
<a href="index.php">overview</a> | history
</p>
<hr />

<?php
$sQuery = 'SELECT * FROM history ORDER BY date, hour LIMIT 744';
$lHistory = $oDB->getAll($sQuery, FALSE) ?: [];

$lHistory = array_map(function ($aHistory) {
	$aHistory['content'] = json_decode(gzuncompress($aHistory['content']), TRUE);
	return $aHistory;
}, $lHistory);

//json_dump(current($lHistory));

$lChart = [
	'rate' => [],
	'avg_time' => [],
	'max_time' => [],
	'num' => [],
	'transfer' => [],
];

foreach ($lChart as $sKey => $aNull) {
	$lChart[$sKey] = [
		['time', $sKey],
	];

}

foreach ($lHistory as $aHistory) {
	$aContent =& $aHistory['content'];

	$sDate = $aHistory['date'];
	$sDate = substr($sDate, 0, 4).'-'.substr($sDate, 4, 2).'-'.substr($sDate, 6, 2);

	$iTime = strtotime($sDate.' '.$aHistory['hour'].':00:00');

	$sKey = date('m-d H', $iTime);

	$lChart['rate'][]     = [$sKey, floatval(substr($aContent['total']['rate'], 0, -1))];
	$lChart['avg_time'][] = [$sKey, $aContent['by_http_code'][200]['avg']['time_cost']];
	$lChart['max_time'][] = [$sKey, $aContent['by_http_code'][200]['max']['time_cost']];
	$lChart['num'][]      = [$sKey, $aContent['total']['num']];
	$lChart['transfer'][] = [$sKey, $aContent['total']['total']['in'] + $aContent['total']['total']['out']];
}

foreach ($lChart as $sKey => $aChart) {
	?>
	<div id="chart_div_<?= $sKey; ?>" style="width: 1200px; height: 300px;"></div>
	<?php
}
?>

<script type="text/javascript">
google.load("visualization", "1", {packages:["corechart"]});

<?php
foreach ($lChart as $sKey => $aChart) {

	$sFnName = 'drawChart_'.$sKey;
	?>
	google.setOnLoadCallback(<?= $sFnName; ?>);

	function <?= $sFnName; ?>() {
		var data = google.visualization.arrayToDataTable(<?php echo json_encode($aChart); ?>);

		var options = {
			'chartArea': {'width': '80%', 'height': '70%'},
		};

		var chart = new google.visualization.LineChart(document.getElementById('chart_div_<?= $sKey; ?>'));
		chart.draw(data, options);
	}
	<?php
}
?>

</script>

</body>
</html>
