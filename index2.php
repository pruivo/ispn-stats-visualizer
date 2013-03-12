<? require("get-info.php"); ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
    <script src="js/plot.display.js" type="text/javascript"></script>
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

    <script type="text/javascript">
        var rootFolder = <?php echo json_encode(getRootFolder()); ?>;
        var clusterInfo = <?php echo json_encode(getClusterInfo()); ?>;
    </script>
    <title>Workload Monitor [Real Time plots]</title>
</head>
<body>

<div id="configHeaderDiv">
    <h1>Workload Monitor - Real Time plots</h1>

    <div align="right"><a id="configHeader"
                          href='javascript:toggle($("#configContent"), $("#configHeader"),"Configuration");'>Configuration
        [Show]</a></div>
</div>

<div style="clear:both;"></div>
<div id="configDiv">
    <div id="configContent" style="display: none;">
        <script type="text/javascript">
            function populateInstances() {
                var instanceSelect = $("#instanceComboBox");
                var instanceArray = [];
                for (var i in clusterInfo) {
                    instanceArray.push(clusterInfo[i][0]);
                }
                populateOptions(instanceSelect, instanceArray);
                populateCategory();
            }

            function populateCategory() {
                var instance = $("#instanceComboBox").find(":selected").text();
                var categorySelect = $("#categoryComboBox");
                var categoryArray = [];
                for (var i in clusterInfo) {
                    if (clusterInfo[i][0] == instance)
                        categoryArray.push(clusterInfo[i][1]);
                }
                populateOptions(categorySelect, categoryArray);
                populateAttributes();
            }

            function populateAttributes() {
                var instance = $("#instanceComboBox").find(":selected").text();
                var category = $("#categoryComboBox").find(":selected").text();
                var attributeSelect = $("#attributeComboBox");
                var attributeArray = [];
                for (var i in clusterInfo) {
                    if (clusterInfo[i][0] == instance && clusterInfo[i][1] == category) {
                        var instanceInfoArray = clusterInfo[i];
                        for (var index = 2; index < instanceInfoArray.length; ++index) {
                            attributeArray.push(instanceInfoArray[index]);
                        }
                        break;
                    }
                }
                populateOptions(attributeSelect, attributeArray);
            }

            function addPlot() {
                var instance = $("#instanceComboBox").find(":selected").text();
                var category = $("#categoryComboBox").find(":selected").text();
                var attribute = $("#attributeComboBox").find(":selected").text();
                var req = [[instance, category, attribute, 0]];
                var url = "get-data-v2.php?folder=" + rootFolder + "&instances=" + JSON.stringify(req);
                alert(rootFolder + "/" + instance + "/" + instance + "_" + category + "_numeric.csv\nAttribute:" + attribute + "\nurl:" + url);

                $.ajax({
                    url:url,
                    method:'GET',
                    dataType:'json',
                    success:function (text) {
                        alert(text);
                    }
                });
            }
        </script>
        <table summary="">
            <tr>
                <th>Instance</th>
                <th>Category</th>
                <th>Attribute</th>
                <th>Plot Title</th>
                <th>Log Scale (Y axis)</th>
                <th>Smooth</th>
                <th></th>
            </tr>
            <tr>
                <td>
                    <select id="instanceComboBox" onchange="javascript:populateCategory()"></select>
                </td>
                <td>
                    <select id="categoryComboBox" onchange="javascript:populateAttributes()"></select>
                </td>
                <td>
                    <select id="attributeComboBox"></select>
                </td>
                <td>
                    <input type="text" size="50" id="title" value="New Plot"/>
                </td>
                <td>Not Available</td>
                <td>Not Available</td>
                <td><button onclick="javascript:addPlot()">Add</button></td>
                <!--
                echo '<input type="checkbox" id="' . $ids . '_smooth" onchange="update();"/>';
                echo '<input type="text" size="5" id="' . $ids . '_smooth_value" onchange="update();"/>';
                -->
            </tr>
        </table>
        <p>Time between updates: <input id="updateInterval" type="text" value="" style="text-align: right; width:5em">
            milliseconds</p>
        <script type="text/javascript">
            populateInstances();
        </script>
    </div>
</div>
<script type="text/javascript">
    <?php
    //echo 'var maxIds = ' . $ids . ';';
    ?>
</script>
<p/>

<table style="border:1px solid black;" id="plot_table">
</table>
<!--
<script type="text/javascript">
    function test() {
        var req = ["test-instance", "NET", "incoming", 5, "outcoming", 2, "traffic", 9];
        var url = "get-data-v2.php?folder=test&parameters=" + JSON.stringify(req);

        $.ajax({
            url:url,
            method:'GET',
            dataType:'json',
            success:function (text) {
                alert(text);
            }
        });
    }
</script>
<button onclick="javascript:test();">Blah</button>
-->
</body>
</html>
