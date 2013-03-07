<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
    <script src="js/excanvas.js" type="text/javascript"></script>
    <script src="js/excanvas.min.js" type="text/javascript"></script>
    <script src="js/jquery.js" type="text/javascript"></script>
    <script src="js/jquery.flot.js" type="text/javascript"></script>
    <!--script src="js/jquery.colorhelpers.js" type="text/javascript"></script>
    <script src="js/jquery.flot.categories.js" type="text/javascript"></script>
    <script src="js/jquery.flot.crosshair.js" type="text/javascript"></script>
    <script src="js/jquery.flot.fillbetween.js" type="text/javascript"></script>
    <script src="js/jquery.flot.image.js" type="text/javascript"></script>
    <script src="js/jquery.flot.navigate.js" type="text/javascript"></script>
    <script src="js/jquery.flot.pie.js" type="text/javascript"></script>
    <script src="js/jquery.flot.resize.js" type="text/javascript"></script>
    <script src="js/jquery.flot.selection.js" type="text/javascript"></script>
    <script src="js/jquery.flot.stack.js" type="text/javascript"></script>
    <script src="js/jquery.flot.symbol.js" type="text/javascript"></script>
    <script src="js/jquery.flot.threshold.js" type="text/javascript"></script>
    <script src="js/jquery.flot.time.js" type="text/javascript"></script-->
    <title>Workload Monitor [Real Time plots]</title>
    <script type="text/javascript">
        <?php
        $folder = "files";

        if (isset($_REQUEST['apps'])) {
            $folder = $_REQUEST['apps'];
        }

        echo 'var folder = "' . $folder . '";';
        echo 'var folderArray = [';
        $phpFolderArray = preg_split('/,/', $folder);
        echo '"' . $phpFolderArray[0] . '"';
        for ($idx = 1; $idx < count($phpFolderArray); ++$idx) {
            echo ',"' . $phpFolderArray[$idx] . '"';
        }
        echo '];';

        if (isset($_REQUEST['smooth'])) {
            echo 'var smoothAlpha = ' . $_REQUEST['smooth'] . ';';
        } else {
            echo 'var smoothAlpha = 1;';
        }
        ?>

        function smooth(oldValue, newValue, alpha) {
            if (oldValue == -1 || alpha >= 1) return newValue;
            if (alpha <= 0) return oldValue;
            return alpha * newValue + (1 - alpha) * oldValue;
        }
    </script>
</head>
<body>
<h1>Workload Monitor - Real Time plots</h1>

<!--<table>
    <?php
    /*$numberOfColumns = 4;
    foreach ($phpFolderArray as &$f) {
        echo '<tr><td>Current protocol for <b>' . $f . '</b> is <span id="' . $f . '_protocol">N/A</span><td></tr>';
    }*/
    ?>
</table>-->

<table style="border:1px solid black;">
    <tr>
        <td>Throughput (transaction per second)</td>
        <td>Write Tx Throughput (transaction per second)</td>
        <td>Read Tx Throughput (transaction per second)</td>
    </tr>
    <tr>
        <td>
            <div id="throughput" style="width:500px;height:300px"></div>
        </td>
        <td>
            <div id="writeThroughput" style="width:500px;height:300px"></div>
        </td>
        <td>
            <div id="readThroughput" style="width:500px;height:300px"></div>
        </td>
    </tr>
    <tr>
        <td>Write Percentage</td>
        <td>Commit Latency (microseconds)</td>
        <td>Abort Rate (percentage)</td>
    </tr>
    <tr>
        <td>
            <div id="wrtPer" style="width:500px;height:300px"></div>
        </td>
        <td>
            <div id="commitLatency" style="width:500px;height:300px"></div>
        </td>
        <td>
            <div id="abortRate" style="width:500px;height:300px"></div>
        </td>
    </tr>
    <tr>
        <td>Cpu Usage (percentage)</td>
        <td>Memory Usage (GigaByte)</td>
        <td>Average Lock Hold Time (microseconds)</td>
    </tr>
    <tr>
        <td>
            <div id="cpu" style="width:500px;height:300px"></div>
        </td>
        <td>
            <div id="memory" style="width:500px;height:300px"></div>
        </td>
        <td>
            <div id="lockHold" style="width:500px;height:300px"></div>
        </td>
    </tr>
    <tr>
        <td>Average RO tx execution time (microseconds)</td>
        <td>Average Wr tx execution time (microseconds)</td>
        <td>Current Protocol</td>
    </tr>
    <tr>
        <td>
            <div id="roExec" style="width:500px;height:300px"></div>
        </td>
        <td>
            <div id="wrExec" style="width:500px;height:300px"></div>
        </td>
        <td>
            <div id="prot" style="width:500px;height:300px"></div>
        </td>
    </tr>
</table>

<p>Time between updates: <input id="updateInterval" type="text" value="" style="text-align: right; width:5em">
    milliseconds</p>

<script type="text/javascript">
    $(function () {
        // setup control widget
        var updateInterval = 10000;

        $("#updateInterval").val(updateInterval).change(function () {
            var v = $(this).val();
            if (v && !isNaN(+v)) {
                updateInterval = +v;
                if (updateInterval < 500)
                    updateInterval = 500;
                if (updateInterval > 20000)
                    updateInterval = 20000;
                $(this).val("" + updateInterval);
            }
        });

        // setup plot
        var default_options = {
            series:{ shadowSize:0 }, // drawing is faster without shadows
            legend:{ position: "nw"},
            yaxis:{ min:0 },
            xaxis:{ min:0 }
        };

        var log_options = {
            series:{ shadowSize:0 }, // drawing is faster without shadows
            legend:{ position: "nw"},
            yaxis:{ transform:function (v) {
                if (v == 0) return 0;
                return Math.log(v);
            },
                inverseTransform:function (v) {
                    if (v == 0) return 0;
                    return Math.exp(v);
                },
                ticks:function logTickGenerator(axis) {
                    var res = [], v = 100;
                    do {
                        v = v * 10;
                        res.push(v);
                    } while (v < axis.max);

                    return res;
                }},
            xaxis:{ min:0 }
        };

        var protocol_options = {
            series:{ shadowSize:0 }, // drawing is faster without shadows
            legend:{ position: "nw"},
            yaxis:{
                ticks:[
                    [0, "N/A"],
                    [1, "PB"],
                    [2, "2PC"],
                    [3, "TO"]
                ],
                min:-0.1,
                max:3.1},
            xaxis:{ min:0 }
        }

        function updatePlot(div, param, avg, options) {
            $.ajax({
                url:"get-data.php?param=" + param + "&avg=" + avg + "&folder=" + folder,
                method:'GET',
                dataType:'text',
                success:function (text) {
                    var lines = text.split("\n");
                    var allData = [];
                    var dataIdx = 0;
                    var oldValue = -1;
                    var dataObj = { data:[], color:dataIdx, label:folderArray[dataIdx++]}
                    for (var i = 0, j = 0; i < lines.length; i++) {
                        if (lines[i] == ".") {
                            allData.push(dataObj);
                            dataObj = { data:[], color:dataIdx, label:folderArray[dataIdx++]};
                            j = 0;
                            continue;
                        }
                        var keyValue = lines[i].split("|");
                        if (keyValue[0] == "" || keyValue[1] == "") continue;
                        oldValue = smooth(oldValue, keyValue[1], smoothAlpha);
                        dataObj.data[j++] = new Array(keyValue[0], oldValue);
                    }
                    $.plot($("#" + div), allData, options);
                }
            });
        }

        function updateProtocolPlot(div, options) {
            $.ajax({
                url:"get-data.php?param=protocol&folder=" + folder,
                method:'GET',
                dataType:'text',
                success:function (text) {
                    var lines = text.split("\n");
                    var allData = [];
                    var dataIdx = 0;
                    var oldValue = -1;
                    var dataObj = { data:[], color:dataIdx, label:folderArray[dataIdx++]}
                    for (var i = 0, j = 0; i < lines.length; i++) {
                        if (lines[i] == ".") {
                            allData.push(dataObj);
                            dataObj = { data:[], color:dataIdx, label:folderArray[dataIdx++]};
                            j = 0;
                            continue;
                        }
                        var keyValue = lines[i].split("|");
                        if (keyValue[0] == "" || keyValue[1] == "") continue;
                        var numericValue = 0;
                        switch (keyValue[1]) {
                            case "PB": numericValue = 1; break;
                            case "2PC": numericValue = 2; break;
                            case "TO": numericValue = 3; break;
                        }
                        dataObj.data[j++] = new Array(keyValue[0], numericValue);
                    }
                    $.plot($("#" + div), allData, options);
                }
            });
        }

        function updateProtocol() {
            $.ajax({
                url:"get-data.php?param=protocol&folder=" + folder,
                method:'GET',
                dataType:'text',
                success:function (text) {
                    var lines = text.split("\n");
                    var lastValue = "N/A";
                    var folderIndex = 0;
                    for (var i = 0, j = 0; i < lines.length; i++) {
                        if (lines[i] == ".") {
                            $("#" + folderArray[folderIndex++] + "_protocol").html(lastValue);
                            lastValue = "N/A";
                            continue;
                        }
                        var keyValue = lines[i].split("|");
                        if (keyValue[0] == "" || keyValue[1] == "") continue;
                        lastValue = keyValue[1];
                    }
                }
            });
        }

        function update() {
            //updateProtocol();
            updatePlot("throughput", "throughput", "false", default_options);
            updatePlot("writeThroughput", "writeTxThroughput", "false", default_options);
            updatePlot("readThroughput", "readTxThroughput", "false", default_options);
            updatePlot("abortRate", "abortRate", "true", default_options);
            updatePlot("cpu", "CPU", "true", default_options);
            updatePlot("memory", "Memory.Usage", "true", default_options);
            updatePlot("wrtPer", "percentageWriteTransactions", "true", default_options);
            updatePlot("commitLatency", "CommitLatency", "true", log_options);
            updatePlot("lockHold", "avgLockHoldTime", "true", default_options);
            updatePlot("roExec", "avgReadOnlyTxDuration", "true", default_options);
            updatePlot("wrExec", "avgWriteTxDuration", "true", default_options);
            updateProtocolPlot("prot",  protocol_options);
            setTimeout(update, updateInterval);
        }

        update();
    });
</script>
</body>
</html>
