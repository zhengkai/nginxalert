<?php
function tplTable($iTime, $iOffset) {
	?>
	<table class="list num nginxalert">
		<tr>
			<th rowspan="2">HTTP code</th>
			<th rowspan="2">request</th>
			<th rowspan="2">req. rate</th>
			<th rowspan="2">req./s</th>
			<th colspan="2">time cost</th>
			<th colspan="2">in</th>
			<th colspan="2">out</th>
		</tr>
		<tr>
			<th>avg.</th>
			<th>max</th>
			<th>avg.</th>
			<th>max</th>
			<th>avg.</th>
			<th>max</th>
		</tr>
	<?php
	$lData = getData($iTime, $iOffset);
	foreach ($lData['by_http_code'] as $iCode => $aRow) {
		echo '<tr>';
		echo '<td>'.$iCode.'</td>';
		echo '<td>'.number_format($aRow['num']).'</td>';
		echo '<td>'.$aRow['rate'].'</td>';
		echo '<td>'.$aRow['req_pre_sec'].'</td>';
		echo '<td>'.number_format($aRow['avg']['time_cost']).'</td>';
		echo '<td>'.number_format($aRow['max']['time_cost']).'</td>';
		echo '<td>'.number_format($aRow['avg']['in']).'</td>';
		echo '<td>'.number_format($aRow['max']['in']).'</td>';
		echo '<td>'.number_format($aRow['avg']['out']).'</td>';
		echo '<td>'.number_format($aRow['max']['out']).'</td>';
		echo '</tr>';
	}
	?>
	</table>
	<?php
}
