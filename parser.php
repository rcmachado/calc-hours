<?php
if (isset($_GET['file'])) {
	$filename = $_GET['file'];
} elseif (isset($argv[1])) {
	$filename = $argv[1];
} else {
	trigger_error('Filename not specified', E_USER_ERROR);
	exit;
}

// For PHP < 5.3
if (!defined(INI_SCANNER_RAW)) {
	define('INI_SCANNER_RAW', 1);
}

date_default_timezone_set('America/Sao_Paulo');

// Read config file
$config = parse_ini_file(dirname(__FILE__) . '/config.ini', true, INI_SCANNER_RAW);

$file = new SplFileObject($filename);
$file->setFlags(SplFileObject::SKIP_EMPTY);

$previous_timestamp = null;
$previous_event = null;
$time_diff = array();

foreach ($file as $line) {
	$fields = explode(',', $line, 2);

	$date_parsed = strptime(trim($fields[0]), $config['file']['date_format']);
	$event_type = trim($fields[1]);

	$timestamp = mktime($date_parsed['tm_hour'], $date_parsed['tm_min'], $date_parsed['tm_sec'], $date_parsed['tm_mon'], $date_parsed['tm_mday'], $date_parsed['tm_year']+1900);

	if ($event_type == 'Entrada') {
		$previous_event = $event_type;
		$previous_timestamp = $timestamp;
	} elseif ($event_type == 'Saida') {
		// we need to use previous timestamp
		// (the 'Saida' event can occur on next day)
		$date = date('Y-m-d', $previous_timestamp);
		$diff = ($timestamp - $previous_timestamp) / 3600;

		$time_diff[$date] += $diff;
		$previous_event = null;
		$previous_timestamp = null;
	}
}

// now its complete, check which dates has extra hours
// and which days we worked less than 8 hours
$extra_hours = 0;
foreach ($time_diff as $date => $hours) {
	if ($hours > 8) {
		$extra_hours = $extra_hours + ($hours - 8);
	} elseif ($hours < 8) {
		$extra_hours = $extra_hours - (8 - $hours);
	}
}

echo "Total hours: " . array_sum($time_diff) . "\n";
echo "Extra hours: $extra_hours\n";
echo "Detailed info: ", print_r($time_diff, true), "\n";
?>