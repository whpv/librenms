<?php

use LibreNMS\RRD\RrdDefinition;

foreach (dbFetchRows('SELECT * FROM mempools WHERE device_id = ?', array($device['device_id'])) as $mempool) {
    echo 'Mempool '.$mempool['mempool_descr'].': ';

    $mempool_type = $mempool['mempool_type'];
    $mempool_index = $mempool['mempool_index'];
    $mempool_descr = $mempool['mempool_descr'];

    $file = $config['install_dir'].'/includes/polling/mempools/'. $mempool_type .'.inc.php';
    if (is_file($file)) {
        include $file;
    }

    if ($mempool['total']) {
        $percent = round(($mempool['used'] / $mempool['total'] * 100), 2);
    } else {
        $percent = 0;
    }

    echo $percent.'% ';

    $rrd_name = array('mempool', $mempool_type, $mempool_index);
    $rrd_def = RrdDefinition::make()
        ->addDataset('used', 'GAUGE', 0)
        ->addDataset('free', 'GAUGE', 0);
    //转mb
    $uesed = $mempool['used']/(1024 * 1024);
    $free = $mempool['free']/(1024 * 1024);
    $total = $mempool['total']/(1024 * 1024);
    /*$fields = array(
        'used' => $mempool['used'],
        'free' => $mempool['free'],
        'pct_used' => $mempool['used'] / $mempool['total'],
        'pct_usabe' => $mempool['free'] / $mempool['total'],
        'total' => $mempool['total'],
    );*/
    $fields = array(
        'used' => $uesed,
        'free' => $free,
        'pct_used' => $uesed / $total,
        'pct_usabe' => $free / $total,
        'total' => $total,
    );

    $tags = compact('mempool_type', 'mempool_index', 'rrd_name', 'rrd_def', 'mempool_descr');
    data_update($device, 'mempool', $tags, $fields);

    $mempool['state'] = array(
                         'mempool_used'  => $mempool['used'],
                         'mempool_perc'  => $percent,
                         'mempool_free'  => $mempool['free'],
                         'mempool_total' => $mempool['total'],
                        );

    if (!empty($mempool['largestfree'])) {
        $mempool['state']['mempool_largestfree'] = set_numeric($mempool['largestfree']);
    }

    if (!empty($mempool['lowestfree'])) {
        $mempool['state']['mempool_lowestfree'] = set_numeric($mempool['lowestfree']);
    }

    dbUpdate($mempool['state'], 'mempools', '`mempool_id` = ?', array($mempool['mempool_id']));

    echo "\n";
}//end foreach
