<?php

use LibreNMS\RRD\RrdDefinition;

$storage_cache = array();

foreach (dbFetchRows('SELECT * FROM storage WHERE device_id = ?', array($device['device_id'])) as $storage) {
    $descr = $storage['storage_descr'];
    $mib = $storage['storage_mib'];

    echo 'Storage '. $descr .': ' . $mib . "\n\n\n\n";

    $rrd_name = array('storage', $mib, $descr);
    $rrd_def = RrdDefinition::make()
        ->addDataset('used', 'GAUGE', 0)
        ->addDataset('free', 'GAUGE', 0);

    $file = $config['install_dir'].'/includes/polling/storage/'. $mib .'.inc.php';
    if (is_file($file)) {
        include $file;
    }

    d_echo($storage);

    if ($storage['size']) {
        $percent = round(($storage['used'] / $storage['size'] * 100));
    } else {
        $percent = 0;
    }

    echo $percent.'% ';
    //采集数据的单位Bytes 单位转换为 kb / 1024
    $storage['used'] = $storage['used']/1024;
    $storage['free'] = $storage['free']/1024;
    $storage['size'] = $storage['size']/1024;
    $fields = array(
        'used'   => $storage['used'],
        'free'   => $storage['free'],
        'total'   => $storage['size'],
        'in_use'   => $storage['used'] / $storage['size'],
    );

    $tags = compact('mib', 'descr', 'rrd_name', 'rrd_def');
    data_update($device, 'storage', $tags, $fields);

    $update = dbUpdate(array('storage_used' => $storage['used'], 'storage_free' => $storage['free'], 'storage_size' => $storage['size'], 'storage_units' => $storage['units'], 'storage_perc' => $percent), 'storage', '`storage_id` = ?', array($storage['storage_id']));

    echo "\n";
}//end foreach

unset($storage);
