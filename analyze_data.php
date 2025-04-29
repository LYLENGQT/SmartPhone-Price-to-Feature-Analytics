<?php

// Function to normalize storage/RAM value
function normalizeValue($value) {
    // Remove all spaces and convert to uppercase
    $value = trim($value);
    $value = strtoupper($value);
    
    // Extract numeric value, removing any non-numeric characters except decimal point
    preg_match('/(\d+(?:\.\d+)?)/', $value, $matches);
    if (!empty($matches[1])) {
        // Convert to integer to remove any decimal places
        $numericValue = intval($matches[1]);
        return $numericValue . 'GB';
    }
    
    // If no numeric value found, return original with GB (shouldn't happen with clean data)
    return '0GB';
}

// Function to analyze camera specifications
function analyzeCamera($cameraSpec) {
    $cameras = explode('+', $cameraSpec);
    $totalMP = 0;
    $mainCamera = 0;
    
    foreach ($cameras as $camera) {
        $mp = floatval($camera);
        $totalMP += $mp;
        if ($mp > $mainCamera) {
            $mainCamera = $mp;
        }
    }
    
    return [
        'total_mp' => $totalMP,
        'main_camera' => $mainCamera,
        'camera_count' => count($cameras)
    ];
}

// Read the cleaned JSON file
$jsonFile = 'cleaned_data.json';
if (!file_exists($jsonFile)) {
    die("JSON file not found: $jsonFile");
}

$jsonData = file_get_contents($jsonFile);
if ($jsonData === false) {
    die("Error reading JSON file");
}

$phones = json_decode($jsonData, true);
if ($phones === null) {
    die("Error decoding JSON data");
}

// Initialize analysis arrays
$analysis = [
    'total_phones' => count($phones),
    'brands' => [],
    'price_ranges' => [
        '0-200' => 0,
        '201-400' => 0,
        '401-600' => 0,
        '601-800' => 0,
        '801-1000' => 0,
        '1001+' => 0
    ],
    'storage_distribution' => [],
    'ram_distribution' => [],
    'camera_analysis' => [
        'average_total_mp' => 0,
        'average_camera_count' => 0,
        'highest_mp' => 0
    ],
    'battery_analysis' => [
        'average_capacity' => 0,
        'min_capacity' => PHP_INT_MAX,
        'max_capacity' => 0
    ],
    'screen_size_analysis' => [
        'average_size' => 0,
        'min_size' => PHP_INT_MAX,
        'max_size' => 0
    ]
];

$totalMP = 0;
$totalCameras = 0;
$totalBattery = 0;
$totalScreenSize = 0;

// Process each phone
foreach ($phones as $phone) {
    // Brand analysis
    $brand = $phone['Brand'];
    if (!isset($analysis['brands'][$brand])) {
        $analysis['brands'][$brand] = 0;
    }
    $analysis['brands'][$brand]++;
    
    // Price range analysis
    $price = $phone['Price ($)'];
    if ($price <= 200) {
        $analysis['price_ranges']['0-200']++;
    } elseif ($price <= 400) {
        $analysis['price_ranges']['201-400']++;
    } elseif ($price <= 600) {
        $analysis['price_ranges']['401-600']++;
    } elseif ($price <= 800) {
        $analysis['price_ranges']['601-800']++;
    } elseif ($price <= 1000) {
        $analysis['price_ranges']['801-1000']++;
    } else {
        $analysis['price_ranges']['1001+']++;
    }
    
    // Storage analysis with normalization
    $storage = normalizeValue($phone['Storage']);
    if (!isset($analysis['storage_distribution'][$storage])) {
        $analysis['storage_distribution'][$storage] = 0;
    }
    $analysis['storage_distribution'][$storage]++;
    
    // RAM analysis with normalization
    $ram = normalizeValue($phone['RAM']);
    if (!isset($analysis['ram_distribution'][$ram])) {
        $analysis['ram_distribution'][$ram] = 0;
    }
    $analysis['ram_distribution'][$ram]++;
    
    // Camera analysis
    $cameraAnalysis = analyzeCamera($phone['Camera (MP)']);
    $totalMP += $cameraAnalysis['total_mp'];
    $totalCameras += $cameraAnalysis['camera_count'];
    if ($cameraAnalysis['main_camera'] > $analysis['camera_analysis']['highest_mp']) {
        $analysis['camera_analysis']['highest_mp'] = $cameraAnalysis['main_camera'];
    }
    
    // Battery analysis
    $battery = $phone['Battery Capacity (mAh)'];
    $totalBattery += $battery;
    if ($battery < $analysis['battery_analysis']['min_capacity']) {
        $analysis['battery_analysis']['min_capacity'] = $battery;
    }
    if ($battery > $analysis['battery_analysis']['max_capacity']) {
        $analysis['battery_analysis']['max_capacity'] = $battery;
    }
    
    // Screen size analysis
    $screenSize = floatval($phone['Screen Size (inches)']);
    $totalScreenSize += $screenSize;
    if ($screenSize < $analysis['screen_size_analysis']['min_size']) {
        $analysis['screen_size_analysis']['min_size'] = $screenSize;
    }
    if ($screenSize > $analysis['screen_size_analysis']['max_size']) {
        $analysis['screen_size_analysis']['max_size'] = $screenSize;
    }
}

// Sort storage and RAM distributions by capacity
uksort($analysis['storage_distribution'], function($a, $b) {
    $a_val = intval(preg_replace('/[^0-9]/', '', $a));
    $b_val = intval(preg_replace('/[^0-9]/', '', $b));
    return $a_val - $b_val;
});

uksort($analysis['ram_distribution'], function($a, $b) {
    $a_val = intval(preg_replace('/[^0-9]/', '', $a));
    $b_val = intval(preg_replace('/[^0-9]/', '', $b));
    return $a_val - $b_val;
});

// Calculate averages
$analysis['camera_analysis']['average_total_mp'] = $totalMP / count($phones);
$analysis['camera_analysis']['average_camera_count'] = $totalCameras / count($phones);
$analysis['battery_analysis']['average_capacity'] = $totalBattery / count($phones);
$analysis['screen_size_analysis']['average_size'] = $totalScreenSize / count($phones);

// Sort brand distribution by count
arsort($analysis['brands']);

// Save analysis results
$jsonResults = json_encode($analysis, JSON_PRETTY_PRINT);
if ($jsonResults === false) {
    die("Error encoding analysis results");
}

if (file_put_contents('analysis_results.json', $jsonResults) === false) {
    die("Error writing analysis results");
}

echo "Analysis completed and saved to analysis_results.json\n";
?> 