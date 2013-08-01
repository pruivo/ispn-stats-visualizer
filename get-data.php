<?php

function trace($msg) {
    echo "<p>" . $msg . "</p>";
}

function parseFile($rootFolder, $instance, $category, $attributes, $initialValues)
{
    $size = count($attributes);
    $result = array();
    $attributesIndexes = array();
    for ($i = 0; $i < $size; $i++) {
        array_push($result, array($attributes[$i]));
    }
    $filename = $category == 'none' ? $rootFolder . '/' . $instance . '/' . $instance . '.csv' :
        $rootFolder . '/' . $instance . '/' . $instance . '_' . $category . '_numeric.csv';

    //trace("Looking for folder " . $filename . ". Attributes: " . print_r($attributes) . ". Initial Values: " . print_r($initialValues));

    if (!file_exists($filename)) {
        //trace($filename . " does not exist");
        return $result;
    }

    $fileHandle = fopen($filename, "r");
    if (!$fileHandle) {
        //trace("Cannot open ". $filename);
        return $result;
    }

    $csvLine = fgetcsv($fileHandle, 0, ",");
    //trace("First line is " . print_r($csvLine));

    if (!$csvLine) {
        //trace("Error getting header line " . print_r($csvLine));
        fclose($fileHandle);
        return $result;
    }

    for ($i = 0; $i < $size; $i++) {
        for ($j = 1; $j < count($csvLine); $j++) {
            if ($csvLine[$j] == $attributes[$i]) {
                $attributesIndexes[$i] = $j;
                break;
            }
        }
    }

    //trace("Attributes indexes are " . print_r($attributesIndexes));

    while (true) {
        $csvLine = fgetcsv($fileHandle, 0, ",");
        if ($csvLine == false || $csvLine == null) {
            break;
        }
        //trace("Processing line " . print_r($csvLine));
        $timestamp = $csvLine[0];
        for ($i = 0; $i < $size; $i++) {
            if ($initialValues[$i] >= $timestamp || !array_key_exists($i, $attributesIndexes)) {
                continue;
            }
            array_push($result[$i], array(intval($timestamp), floatval($csvLine[$attributesIndexes[$i]])));
        }
    }

    fclose($fileHandle);

    //trace("Final Result is " . print_r($result));
    return $result;
}

$instances = null;
$folder = "files";

if (isset($_REQUEST['folder'])) {
    $folder = $_REQUEST['folder'];
    //trace("Folder found: " . $folder);
}

if (isset($_REQUEST['instances'])) {
    $instances = json_decode($_REQUEST['instances']);
    //trace("request found: " . print_r($instances));
}

if (!isset($folder) || !isset($instances)) {
    echo json_encode(array());
    return;
}

//instances format [[instance, category or none, parameter, initValue, ...], ...]
$result = array();

foreach ($instances as $instanceAndCategoryRequest) {
    //trace("Analyzing request line " . print_r($instanceAndCategoryRequest));
    $instanceResult = array();
    array_push($instanceResult, $instanceAndCategoryRequest[0]);
    array_push($instanceResult, $instanceAndCategoryRequest[1]);
    $attributesOnly = array();
    $initValuesOnly = array();
    for ($i = 2; $i < count($instanceAndCategoryRequest); $i++) {
        array_push($attributesOnly, $instanceAndCategoryRequest[$i++]);
        array_push($initValuesOnly, $instanceAndCategoryRequest[$i]);
    }
    $parsedFile = parseFile($folder, $instanceAndCategoryRequest[0], $instanceAndCategoryRequest[1],
    $attributesOnly, $initValuesOnly);
    array_push($result, array_merge($instanceResult, $parsedFile));
}

echo json_encode($result);
?>
