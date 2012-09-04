<html>
<head>
	<title>Perflogs</title>
	<style type="text/css">
	code { font-weight: bolder; color: #c98314; }
	</style>
</head>
<body>
	<div style="float:right"><a href="?">Refresh</a> <a href="?raz=1">RAZ</a></div>
	<h1>Perflogs</h1>
<?php

$files = glob(dirname(__FILE__) . '/../src/perflog/*.perflog');

if (@$_GET['raz'] == '1') {
	foreach ($files as $file) {
		if (!is_file($file)) continue;
		unlink($file);
	}
	echo '<p>RAZ done. <a href="?">Refresh</a></p>';
	echo '</body></html>';
	exit();
}

// Fetch queries
foreach ($files as $file) {
	
	if (!is_file($file)) continue;
	
	$runs = unserialize(file_get_contents($file));
	
	echo '<hr /><h2>['.$runs[0]['api'].'] <u>'.$runs[0]['version']['name'].'</u> on <i>'.$runs[0]['uri'].'</i></h2>';
	
	$average = array('_total' => array('_total' => 0));
	$versions = array();
	
	// Fetch runs
	foreach ($runs as $i => $log) {
		
		if (!isset($log['ticks']['shutdown']) || !isset($log['ticks']['usercode'])) {
			continue;
		}
		
		// Times
		$usercode_duration = $log['ticks']['shutdown'] - (isset($log['ticks']['usercode']) ? $log['ticks']['usercode'] : $log['ticks']['shutdown']);
		$total_duration    = $log['ticks']['stop'] - $log['ticks']['start'];
		$service_duration  = $total_duration - $usercode_duration;
		
		// Debug
		/*echo "<li>Run #$i <ul>";		
		echo "<li>Total: $total_duration</li>";
		echo "<li>User: $usercode_duration</li>";
		echo "<li>Service: $service_duration</li>";
		echo '</ul></li>';*/
		
		// Version
		if (!isset($versions[$log['version']['mtime']])) {
			$versions[$log['version']['mtime']] = array();
		}
		$versions[$log['version']['mtime']][] = $service_duration;
		
		// Ticks
		$ref_name = null;
		$ref_value = 0;
		foreach ($log['ticks'] as $name => $value) {
			
			$sub = '_main';
			
			if ($name != 'start') {
				
				if (strpos($name, '.') !== false) {
					list($name, $sub) = explode('.', $name, 2);
				}
				
				$duration = $value - $ref_value;
				
				if (!array_key_exists($ref_name[0], $average)) {
					$average[$ref_name[0]] = array(
						'_total' => 0 
					);
				}
				if (!array_key_exists($ref_name[1], $average[$ref_name[0]])) {
					$average[$ref_name[0]][$ref_name[1]] = 0;
				}	

				$average[$ref_name[0]][$ref_name[1]] += $duration;
				$average[$ref_name[0]]['_total'] += $duration;
				
			}
			
			$ref_name = array($name, $sub);
			$ref_value = $value;
			
		}
		
		$average['_total']['_total'] += $service_duration;
		
	}
	
	$average['_total']['_main'] = $average['_total']['_total'];
	
	// Averages calculation
	$length = sizeof($runs);
	foreach ($average as $name => $subs) {
		foreach ($subs as $key => $value) {
			$average[$name][$key] = $value / $length;
		}
	}
	
	// Pie chart
	$values = array();
	foreach ($average as $name => $subs) {
		
		// Le total n'apparait pas sur le chart
		if ($name === '_total') continue;
		
		// Il n'y a pas de subs en fait
		if (sizeof($subs) < 3) {
			$total = $subs['_total'];
		}
		
		// Il y a plusieurs subs : on additionne
		else {
			$total = 0;
			foreach ($subs as $key => $value) {
				if ($key === '_total') continue;
				$total += $value;
			}
			// Et on renormalise
			$total = $total / (sizeof($subs) - 1);
		}
		
		$pc = round($total / $average['_total']['_total'] * 100);
		
		if ($pc === 0) $pc = '0';
		
		$values["$name ($pc%)"] = $pc;
		
	}
	echo '<img src="https://chart.googleapis.com/chart?cht=p&amp;chd=t:'.implode(',',$values).'&amp;chs=450x200&amp;chdl='.implode('|', array_keys($values)).'">';
	
	// Ticks list
	echo '<ul>';
	foreach ($average as $name => $subs) {
		if ($name == '_total') continue;
		if (sizeof($subs) < 3) {
			echo "<li><code>$name</code> : <small>{$subs['_total']}</small> (<b>".round($subs['_total'] / $average['_total']['_total'] * 100)."%</b>)</li>";
			continue;
		}
		echo "<li><code>$name</code> : <small>{$subs['_total']}</small> (<b>".round($subs['_total'] / $average['_total']['_total'] * 100)."%</b>)<ul>";
		foreach ($subs as $key => $value) {
			if ($key == '_total') continue;
			echo "<li><code>$key</code> : <small>{$value}</small> (".round($value / $average['_total']['_total'] * 100)."%)</li>";
		}
		echo '</ul>';
	}
	echo '</ul>';
	
	// Line chart
	if (sizeof($versions) > 1) {
		$max = 0;
		foreach ($versions as $time => $delay) {
			$total = 0;
			foreach ($delay as $value) $total += $value;
			$versions[$time] = $total / sizeof($delay);
			$max = max($max, $versions[$time]);
		}
		$legend_x = array(
			date('d/m/y h:i', array_shift(array_keys($versions))),
			date('d/m/y h:i', array_pop(array_keys($versions)))
		);
		$legend_y = array(
			'',
			round($max / 2, 4) . ' s',
			round($max, 4) . ' s'
		);

		$tmp = $versions;
		foreach ($tmp as &$v) $v = round($v / $max * 100);
		echo '<img src="https://chart.googleapis.com/chart?cht=lc&amp;chs=600x170&amp;chxt=x,y&amp;chm=V,CCCCCC,0,0:'.sizeof($versions).':1,1,-1&amp;chd=t:'.implode(',', $tmp).'&amp;chxl=0:|'.implode('|', $legend_x).'|1:|'.implode('|||', $legend_y).'">';
	}
	
	echo '<p>Records: '.$length.' - Average time: ' . $average['_total']['_total'] . ' sec</p>';
	
}

?>
</body>
</html>