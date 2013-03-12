<?php
function endsWith($haystack,$needle,$case=true) {
    if($case){return (strcmp(substr($haystack, strlen($haystack) - strlen($needle)),$needle)===0);}
    return (strcasecmp(substr($haystack, strlen($haystack) - strlen($needle)),$needle)===0);
}

function extractParameters($filename) {
    trace("extract parameters from " . $filename);
    $fdFile = fopen($filename, "r");
    $parameters = preg_split("/,/", trim(fgets($fdFile)));
    $result = array();
    for ($index = 1; $index < count($parameters); $index++) {
        $result[$index - 1] = $parameters[$index];
    }
    fclose($fdFile);
    return $result;
}

function trace($msg) {
    //echo "<p>" . $msg . "</p>";
}

function getRootFolder() {
    return isset($_REQUEST['rootFolder']) ? $_REQUEST['rootFolder'] : null;
}

function getClusterInfo() {
    if (isset($_REQUEST['rootFolder'])) {
        $rootFolder = $_REQUEST['rootFolder'];
    }

    trace("root folder is " . $rootFolder);

    if (!isset($rootFolder) || !file_exists($rootFolder)) {
        trace( $rootFolder ." does not exist");
        return array();
    }

    $fdRootFolder = opendir($rootFolder);
    $resultsArray = array();
    while (false != ($dirName = readdir($fdRootFolder))) {
        if ($dirName == "." || $dirName == "..") {
            continue;
        }

        trace("analyzing " . $rootFolder . "/" . $dirName);
        $fdDir = opendir($rootFolder . "/" . $dirName);
        $insertIndex = 0;
        while (false != ($fileName = readdir($fdDir))) {
            if ($fileName == "." || $fileName == ".." || !endsWith($fileName, ".csv")) {
                continue;
            }
            trace("analyzing file " . $fileName);

            $arrayToInsert = array();
            //the file name format is <instance>_<category>_numeric.csv
            $filenameSplit = preg_split("/_/", substr($fileName, 0, strlen($fileName) - 4));
            $arrayToInsert[0] = $filenameSplit[0];
            if (count($filenameSplit) == 1) {
                //cluster csv file
                $arrayToInsert[1] = "none";
            } else{
                $categoryName = $filenameSplit[1];
                if ($filenameSplit[2] != "numeric") {
                    //non numeric csv file
                    continue;
                }
                $arrayToInsert[1] = $categoryName;
            }
            $i = 2;
            foreach (extractParameters($rootFolder . "/" . $dirName . "/" . $fileName) as $p) {
                $arrayToInsert[$i++] = $p;
            }
            $resultsArray[$insertIndex++] = $arrayToInsert;
        }
        closedir($fdDir);
    }
    closedir($fdRootFolder);
    return $resultsArray;
}