function addStat(category, attribute, cluster) {
    if (cluster) {
        addPlotToTable($("#plot_table"), 'cluster', 'none', attribute, 'cluster' + ":" + attribute, false, false);
    } else {
        addForAllPlotsToTable(category, attribute + "_0");
    }
}

function lardStats(cluster) {
    //add waiting time in queue
    addStat('FENIX', 'geograph.queueSize', cluster);
    addStat('FENIX', 'geograph.numberOfRunningThreads', cluster);
    update();
    REDIPS.drag.init();
}

function resourcesStats(cluster) {
    addStat('MEMORY', 'MemoryInfo.used', cluster);
    addStat('CPU', 'CpuPerc.user', cluster);
    update();
    REDIPS.drag.init();
}

function networkStats(cluster) {
    addStat('JMX', 'AvgPrepareRtt', cluster);
    addStat('JMX', 'AvgCommitRtt', cluster);
    addStat('JMX', 'AvgRemoteGetRtt', cluster);
    update();
    REDIPS.drag.init();
}

function transactionStats(cluster) {
    addStat('JMX', 'Throughput', cluster);
    addStat('JMX', 'AbortRate', cluster);
    addStat('JMX', 'LocalUpdateTxTotalResponseTime', cluster);
    addStat('JMX', 'ReadOnlyTxTotalResponseTime', cluster);
    addStat('JMX', 'AvgRemoteGetsPerROTransaction', cluster);
    addStat('JMX', 'AvgRemoteGetsPerWrTransaction', cluster);
    addStat('JMX', 'LocalActiveTransactions', cluster);
    addStat('JMX', 'NumberOfEntries', cluster);
    update();
    REDIPS.drag.init();
}
