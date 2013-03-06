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
    <?php
    $attributes = array('Throughput', 'AbortRate', 'CPU', 'Memory.Usage');
    $folder = "files";

    if (isset($_REQUEST['apps'])) {
        $folder = $_REQUEST['apps'];
    }

    echo '<script type="text/javascript">';
    echo 'var folder = "' . $folder . '";';
    echo 'var folderArray = [';
    $array = split(",", $folder);
    echo '"' . $array[0] . '"';
    for ($idx = 1; $idx < count($array); ++$idx) {
        echo ',"' . $array[$idx] . '"';
    }
    echo '];';
    echo '</script>';
    ?>
</head>
<body>
<script type="text/javascript">
    //Toggles the configuration menu
    function configurationToggle() {
        if ($("#configContent").css("display") == "block") {
            $("#configContent").css("display", "none");
            $("#configHeader").html("Configuration [Show]");
        } else {
            $("#configContent").css("display", "block");
            $("#configHeader").html("Configuration [Hide]");
        }
    }
</script>

<div id="configHeaderDiv">
    <h1>Workload Monitor - Real Time plots</h1>

    <div align="right"><a id="configHeader" href="javascript:configurationToggle();">Configuration [Show]</a></div>
</div>

<div style="clear:both;"></div>
<div id="configDiv">
    <div id="configContent" style="display: none;">
        <table summary="">
            <tr>
                <th>Show</th>
                <th>Attribute Name</th>
                <th>Plot Title</th>
                <th>Log Scale (Y axis)</th>
                <th>Smooth</th>
            </tr>
            <?php
            $ids = 1;
            foreach ($attributes as $attr) {
                echo '<tr><td align="center">';
                echo '<input type="checkbox" id="' . $ids . '_show" onchange="build();"/>';
                echo '</td><td>';
                echo '<div id="' . $ids . '_name" >' . $attr . '</div>';
                echo '</td><td>';
                echo '<input type="text" size="50" id="' . $ids . '_title" value="' . $attr . '" onchange="updatePlotTitleFor(' . $ids . ');" />';
                echo '</td><td align="center">';
                echo '<input type="checkbox" id="' . $ids . '_log_scale" onchange="updatePlotForId(' . $ids . ');" />';
                echo '</td><td>';
                echo '<input type="checkbox" id="' . $ids . '_smooth" onchange="updatePlotForId(' . $ids . ');"/><input type="text" size="5" id="' . $ids . '_smooth_value" onchange="updatePlotForId(' . $ids . ');"/>';
                echo '</td></tr>';
                $ids++;
            }
            ?>
        </table>
        <p>Time between updates:
            <input id="updateInterval" type="text" value="" style="text-align: right; width:5em">
            milliseconds</p>
    </div>
</div>
<script type="text/javascript">
    <?php
    echo 'var maxIds = ' . $ids . ';';
    ?>
    function smooth(oldValue, newValue, alpha) {
        if (oldValue == -1) {
            return newValue;
        }
        return alpha * newValue + (1 - alpha) * oldValue;
    }

    function build() {
        var id = 1;
        $("#plot_table").html("");
        var table = "";
        while (id < maxIds) {
            var tmpHeader = "";
            var tmpPlot = "";
            for (var collumn = 0; collumn < 3; collumn++) {
                while (!$("#" + id + "_show").attr('checked') && id < maxIds) {
                    id++;
                }
                if (id >= maxIds) {
                    tmpHeader += "<td></td>";
                    tmpPlot += "<td></td>";
                } else {
                    tmpHeader += "<td id=\"" + id + "_plot_title\" >" + $("#" + id + "_title").val() + "</td>";
                    tmpPlot += "<td><div id=\"" + id + "_plot\" style=\"width:500px;height:300px\"></div></td>";
                }
                id++;
            }
            table += "<tr>" + tmpHeader + "</tr>";
            table += "<tr>" + tmpPlot + "</tr>";

        }
        $("#plot_table").html(table);
        update();
    }
</script>
<p/>

<table style="border:1px solid black;" id="plot_table">
</table>

<!--<table style="border:1px solid black;">
   <tr>
      <td>Throughput (transaction per second)</td>
      <td>Abort Rate (percentage)</td>
      <td>Cpu Usage (percentage)</td>
   </tr>
   <tr>
      
      <td><div id="abortRate" style="width:500px;height:300px"></div></td>
      <td><div id="cpu" style="width:500px;height:300px"></div></td>
   </tr>      
   <tr>
      <td>Write Percentage</td>
      <td>Commit Latency (microseconds)</td>
      <td>Memory Usage (GigaByte)</td>
   </tr>
   <tr>
      <td><div id="wrtPer" style="width:500px;height:300px"></div></td>
      <td><div id="commitLatency" style="width:500px;height:300px"></div></td>
      <td><div id="memory" style="width:500px;height:300px"></div></td>
   </tr>
</table>-->

<script type="text/javascript">

    var updateInterval = 10000;

    $("#updateInterval").val(updateInterval).change(function () {
        var v = $(this).val();
        if (v && !isNaN(+v)) {
            updateInterval = +v;
            if (updateInterval < 1000)
                updateInterval = 1000;
            if (updateInterval > 20000)
                updateInterval = 20000;
            $(this).val("" + updateInterval);
        }
    });

    var default_options = {
        series:{ shadowSize:0 }, // drawing is faster without shadows
        yaxis:{ min:0 },
        xaxis:{ min:0 }
    };

    var log_options = {
        series:{ shadowSize:0 }, // drawing is faster without shadows
        yaxis:{ transform:function (v) {
            if (v == 0) return 0;
            return Math.log(v);
        },
            inverseTransform:function (v) {
                if (v == 0) return 0;
                return Math.exp(v);
            },
            ticks:function logTickGenerator(axis) {
                var res = [], v = 0.00001;
                while (true) {
                    if (v > axis.min) {
                        v /= 100;
                        break;
                    } else {
                        v *= 10;
                    }
                }
                do {
                    v = v * 10;
                    res.push(v);
                } while (v < axis.max);

                return res;
            }},
        xaxis:{ min:0 }
    };

    function updatePlot(div, param, options, smoothValue) {
        $.ajax({
            url:"get-multiple-data.php?param=" + param + "&folder=" + folder,
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

                    oldValue = smooth(oldValue, keyValue[1], smoothValue);
                    dataObj.data[j++] = new Array(keyValue[0], oldValue);
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
        for (var id = 1; id < maxIds; ++id) {
            updatePlotForId(id);
        }
        //updatePlot("throughput", "Throughput",default_options);
        //updatePlot("abortRate", "AbortRate", default_options);
        //updatePlot("cpu", "CPU", default_options);
        //updatePlot("memory", "Memory.Usage", default_options);
        //updatePlot("wrtPer", "PercentageWriteTransactions", default_options);
        //updatePlot("commitLatency", "CommitLatency",log_options);
        setTimeout(update, updateInterval);
    }

    function updatePlotForId(id) {
        if (!$("#" + id + "_show").attr('checked')) {
            return;
        }
        var options;
        if ($("#" + id + "_log_scale").attr('checked')) {
            options = log_options;
        } else {
            options = default_options;
        }
        var smoothValue = 1;
        if ($("#" + id + "_smooth").attr('checked') && !isNaN($("#" + id + "_smooth_value").val()) && $("#" + id + "_smooth_value").val() != "") {
            smoothValue = $("#" + id + "_smooth_value").val();
            if (smoothValue > 1)
                smoothValue = 1;
            else if (smoothValue < 0)
                smoothValue = 0;
        }

        updatePlot(id + "_plot", $("#" + id + "_name").html(), options, smoothValue);
    }

    function updatePlotTitleFor(id) {
        $("#" + id + "_plot_title").html($("#" + id + "_title").val());
    }
</script>
</body>
</html>
