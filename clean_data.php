<?php

// Function to clean and normalize data
function cleanData($data) {
    // Remove any whitespace from values and handle UTF-8 BOM
    $data = array_map(function($value) {
        return trim($value, " \t\n\r\0\x0B\xEF\xBB\xBF");
    }, $data);
    
    // Convert price to numeric value (remove $ and commas)
    if (isset($data['Price ($)'])) {
        $data['Price ($)'] = (float) str_replace(['$', ','], '', $data['Price ($)']);
    }
    
    // Normalize storage and RAM values
    if (isset($data['Storage '])) {
        $data['Storage'] = str_replace(' ', '', $data['Storage ']);
        unset($data['Storage ']);  // Remove the old key
    }
    if (isset($data['RAM '])) {
        $data['RAM'] = str_replace(' ', '', $data['RAM ']);
        unset($data['RAM ']);  // Remove the old key
    }
    
    // Normalize camera specifications
    if (isset($data['Camera (MP)'])) {
        $data['Camera (MP)'] = str_replace(['MP', ' '], '', $data['Camera (MP)']);
    }
    
    // Convert battery capacity to numeric
    if (isset($data['Battery Capacity (mAh)'])) {
        $data['Battery Capacity (mAh)'] = (int) $data['Battery Capacity (mAh)'];
    }
    
    return $data;
}

// Check if file exists
$csvFile = 'data.csv';
if (!file_exists($csvFile)) {
    die("CSV file not found: $csvFile");
}

// Read the CSV file
$handle = fopen($csvFile, 'r');
if ($handle === false) {
    die("Error opening CSV file");
}

// Get headers and normalize them
$headers = fgetcsv($handle);
if ($headers === false) {
    die("Error reading CSV headers");
}

$headers = array_map(function($header) {
    return trim($header, " \t\n\r\0\x0B\xEF\xBB\xBF");
}, $headers);

// Process data
$data = [];
$uniqueKeys = [];

while (($row = fgetcsv($handle)) !== false) {
    if (count($row) !== count($headers)) {
        continue;  // Skip malformed rows
    }
    
    // Combine headers with row data
    $rowData = array_combine($headers, $row);
    
    // Clean the data
    $rowData = cleanData($rowData);
    
    // Create a unique key for deduplication
    $uniqueKey = $rowData['Brand'] . '|' . $rowData['Model'] . '|' . $rowData['Storage'];
    
    // Only add if not a duplicate
    if (!isset($uniqueKeys[$uniqueKey])) {
        $data[] = $rowData;
        $uniqueKeys[$uniqueKey] = true;
    }
}

fclose($handle);

// Save as JSON
$jsonData = json_encode($data, JSON_PRETTY_PRINT);
if ($jsonData === false) {
    die("Error encoding JSON data");
}

if (file_put_contents('cleaned_data.json', $jsonData) === false) {
    die("Error writing JSON file");
}

echo "Data has been cleaned and saved to cleaned_data.json\n";
echo "Total records processed: " . count($data) . "\n";
?> 