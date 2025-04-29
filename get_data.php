<?php
header('Content-Type: application/javascript');
header('Access-Control-Allow-Origin: *');

$json_data = file_get_contents('analysis_results.json');
if ($json_data === false) {
    echo "console.error('Failed to read analysis_results.json');";
} else {
    echo "renderCharts(" . $json_data . ");";
}
?> 