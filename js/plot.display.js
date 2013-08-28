var updateInterval = 10000;
var uniquePlotManager = {};
var plotColorManager = {};
var plotAttributesManager = {};
var updating = false;
var previousPoint = null;

var default_options = {
    series:{ shadowSize:0 },
    xaxis:{
        mode:"time",
        timeformat:"%H:%M.%S"
    },
    grid:{
        hoverable:true
    }
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
        if (updateInterval < 5000)
            updateInterval = 5000;
        if (updateInterval > 60000)
            updateInterval = 60000;
        input.val(updateInterval);
    }
}

function a1Sort(a, b){
    var cnt= 0, tem;
    a= String(a).toLowerCase();
    b= String(b).toLowerCase();
    if(a== b) return 0;
    if(/\d/.test(a) ||  /\d/.test(b)){
        var Rx=  /^\d+(\.\d+)?/;
        while(a.charAt(cnt)=== b.charAt(cnt) &&
            !Rx.test(a.substring(cnt))){
            cnt++;
        }
        a= a.substring(cnt);
        b= b.substring(cnt);
        if(Rx.test(a) || Rx.test(b)){
            if(!Rx.test(a)) return a? 1: -1;
            if(!Rx.test(b)) return b? -1: 1;
            tem= parseFloat(a)-parseFloat(b);
            if(tem!= 0) return tem;
            a= a.replace(Rx,'');
            b= b.replace(Rx,'');
            if(/\d/.test(a) ||  /\d/.test(b)){
                return a1Sort(a, b);
            }
        }
    }
    if(a== b) return 0;
    return a> b? 1: -1;
}

function generateId(instance, category, attribute) {
    var id = instance + category + attribute;
    return id.replace(/\./g, '').replace(/_/g, '');
}

/*function updatePlot(div, param, options, smoothValue) {
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
}*/

/*function updateProtocol() {
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
}*/

function update() {
    if (updating) {
        return;
    }
    updating = true;
    var request = [];
    for (var instance in plotAttributesManager) {
        for (var category in plotAttributesManager[instance]) {
            var temp = [instance, category];
            for (var index = 0; index < plotAttributesManager[instance][category].length; ++index) {
                var attribute = plotAttributesManager[instance][category][index];
                temp.push(attribute);
                temp.push(uniquePlotManager[generateId(instance, category, attribute)][0]);
            }
            request.push(temp);
        }
    }

    $.ajax({
        url:"get-data.php?folder=" + rootFolder,
        type:'POST',
        data: { instances: JSON.stringify(request) },
        dataType:'json',
        success:function (text) {
            for (var index = 0; index < text.length; ++index) {
                var instance = text[index][0];
                var category = text[index][1];
                for (var attributeIndex = 2; attributeIndex < text[index].length; ++attributeIndex) {
                    var attribute = text[index][attributeIndex][0];
                    var id = generateId(instance, category, attribute);
                    var plotObject = uniquePlotManager[id][1];
                    var minTimeStamp = uniquePlotManager[id][0];
                    var maxTimeStamp = minTimeStamp;
                    var needRePlot = false;
                    for (var dataIndex = 3; dataIndex < text[index][attributeIndex].length; ++dataIndex) {
                        var pair = text[index][attributeIndex][dataIndex];
                        if (minTimeStamp >= pair[0]) {
                            continue;
                        }
                        plotObject["data"].push(pair);
                        if (maxTimeStamp < pair[0]) {
                            maxTimeStamp = pair[0];
                        }
                        needRePlot = true;
                    }
                    uniquePlotManager[id][0] = maxTimeStamp;
                    if (needRePlot) {
                        $.plot($("#" + id), [plotObject], default_options);
                    }
                }
            }
            updating = false;
        }
    })
    ;

    setTimeout(update, updateInterval);
}

/*function updatePlotForId(id, parameter, log_scale, smooth, smooth_value) {
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
}*/

/*function smooth(oldValue, newValue, alpha) {
    if (oldValue == -1) {
        return newValue;
    }
    return alpha * newValue + (1 - alpha) * oldValue;
}*/

function addPlotToTable(tableDiv, instance, category, attribute, plotTitle, failIfDuplicated, forceUpdate) {
    var tbody = tableDiv.children(0);
    var id = generateId(instance, category, attribute);
    if (id in uniquePlotManager) {
        if (failIfDuplicated) {
            alert("Plot with " + attribute + "(" + instance + ") already exists!");
        }
        return;
    }
    uniquePlotManager[id] = [0, {data:[], color:plotColorManager[instance] }];
    if (!(instance in plotAttributesManager)) {
        plotAttributesManager[instance] = {};
    }
    if (!(category in plotAttributesManager[instance])) {
        plotAttributesManager[instance][category] = [];
    }
    plotAttributesManager[instance][category].push(attribute);


    if (tbody.children().length == 0) {
        createTableLine(tbody).appendChild(createPlotDiv(id, plotTitle));
    } else {
        var lastTr = tbody.find("tr:last");
        if (lastTr.children().length >= 3) {
            createTableLine(tbody).appendChild(createPlotDiv(id, plotTitle));
        } else {
            lastTr.append(createPlotDiv(id, plotTitle));
        }
    }
    if (forceUpdate) {
        update();
    }
}

function createTableLine(tableDiv) {
    var tr = document.createElement('tr');
    tableDiv.append(tr);
    return tr;
}

function createPlotDiv(id, plotTitle) {
    var td = document.createElement('td');
    var tdDiv = document.createElement('div');
    tdDiv.className = "drag";
    tdDiv.style.cursor = "move";
    var title = document.createElement('p');
    var center = document.createElement('center');
    center.appendChild(document.createTextNode(plotTitle));
    title.appendChild(center);
    var plot = document.createElement('div');
    plot.id = id;
    plot.style.width = "500px";
    plot.style.height = "300px";

    tdDiv.appendChild(title);
    tdDiv.appendChild(plot);
    td.appendChild(tdDiv);
    //td.appendChild(title);
    //td.appendChild(plot);
    return td;
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
    var tmp = {};
    for (var i = 0; i < array.length; ++i) {
        tmp[array[i]] = true;
    }
    var result = [];
    for (var p in tmp) {
        result.push(p);
    }
    return result;
}

function populateOptions(select, optionArray) {
    select.empty();
    var uniqueArray = unique(optionArray).sort(a1Sort);
    for (var i = 0; i < uniqueArray.length; ++i) {
        var option = document.createElement('option');
        option.value = option.innerHTML = uniqueArray[i];
        select.append(option);
    }
}

function populateColors(array) {
    var colorId = 1;
    var uniqueArray = unique(array);
    for (var i = 0; i < uniqueArray.length; ++i) {
        plotColorManager[uniqueArray[i]] = colorId++;
    }
}

function showTooltip(x, y, contents) {
    $("<div id='tooltip'>" + contents + "</div>").css({
        position:"absolute",
        display:"none",
        top:y + 5,
        left:x + 5,
        border:"1px solid #fdd",
        padding:"2px",
        "background-color":"#fee",
        opacity:0.80
    }).appendTo("body").fadeIn(200);
}

function onMouseOver(event, pos, item) {
    var toolTip = $("#tooltip");
    if (item) {
        if (previousPoint != item.dataIndex) {

            previousPoint = item.dataIndex;

            toolTip.remove();
            var x = item.datapoint[0],
                y = item.datapoint[1];

            showTooltip(item.pageX, item.pageY, item.series.xaxis.tickFormatter(x, item.series.xaxis) + " = " + y);
        }
    } else {
        toolTip.remove();
        previousPoint = null;
    }
}