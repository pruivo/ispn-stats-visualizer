<?php

$instances = null;
$folder = "files";

if (isset($_REQUEST['folder'])) {
//    echo "<p>folder OK</p>";
    $folder = $_REQUEST['folder'];
}

if (isset($_REQUEST['instances'])) {
//    echo "<p>parameters OK</p>";
    $instances = json_decode($_REQUEST['instances']);
}

if (isset($_REQUEST['cluster'])) {
//    echo "<p>parameters OK</p>";
    $cluster = json_decode($_REQUEST['cluster']);
}

//echo "<p>" . $folder . "</p>";
//echo "<p>" . $parameter . "</p>";
//echo "<p>" . print_r($parameter) . "</p>";
//TODO parser
$array = array();
$array[0] = $instances[0];
$array[1] = $instances[1];
$insertIn = 2;

for ($i = 2; $i < count($instances); $i++) {
    $p = $instances[$i++];
    $v = $instances[$i];
    $array[$insertIn++] = $p;
    for ($j = 0; $j < $v; $j++) {
        $array[$insertIn++] = array($j, $j);
    }
}
echo json_encode($array);
?>
