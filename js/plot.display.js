var updateInterval = 10000;

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

function setUpdateInterval(input) {
    var v = input.val();
    if (v && !isNaN(+v)) {
        updateInterval = +v;
        if (updateInterval < 1000)
            updateInterval = 1000;
        if (updateInterval > 20000)
            updateInterval = 20000;
        input.val(updateInterval);
    }
}



function updatePlot(div, param, options, smoothValue) {
    $.ajax({
        url:"get-data.php?param=" + param + "&folder=" + folder,
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
        updatePlotForId(id, $("#" + id + "_name").html(), $("#" + id + "_log_scale").attr('checked'),
            $("#" + id + "_smooth").attr('checked'), $("#" + id + "_smooth_value").val());
    }
    setTimeout(update, updateInterval);
}

function updatePlotForId(id, parameter, log_scale, smooth, smooth_value) {
    var options;
    if (log_scale) {
        options = log_options;
    } else {
        options = default_options;
    }
    if (smooth && !isNaN(smooth_value) && smooth_value != "") {
        if (smooth_value > 1)
            smooth_value = 1;
        else if (smooth_value < 0)
            smooth_value = 0;
    } else {
        smooth_value = 1;
    }

    updatePlot(id + "_plot", parameter, options, smooth_value);
}

function updatePlotTitleFor(div,value) {
    div.html(value.val());
}

function smooth(oldValue, newValue, alpha) {
    if (oldValue == -1) {
        return newValue;
    }
    return alpha * newValue + (1 - alpha) * oldValue;
}

function build(tableDiv) {
    var id = 1;
    tableDiv.html("");
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
    tableDiv.html(table);
    update();
}

function toggle(div, infoDiv, name) {
    if (div.css("display") == "block") {
        div.css("display", "none");
        infoDiv.html(name + " [Show]");
    } else {
        div.css("display", "block");
        infoDiv.html(name + " [Hide]");
    }
}

function unique(array) {
    if (!(array instanceof Array)) {
        return [];
    }
    var tmp = [];
    for (var p in array) {
        tmp[array[p]] = true;
    }
    var result = [];
    for (p in tmp) {
        result.push(p);
    }
    return result;
}

function populateOptions(select, optionArray) {
    select.empty();
    var uniqueArray = unique(optionArray);
    for (i in uniqueArray) {
        var option = document.createElement('option');
        option.value = option.innerHTML = uniqueArray[i];
        select.append(option);
    }
}