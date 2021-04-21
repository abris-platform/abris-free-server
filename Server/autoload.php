<?php

require_once 'services_methods.php';

function getConfigContent($filePath, $mainRequiresList = array()) {
    $addList = json_decode(file_get_contents($filePath), true);

    $filePath = str_replace('/configs', '', dirname($filePath));

    if (count($addList) > 0)
        $addList = array_map(function ($element) use ($filePath) {
            return "$filePath/$element";
        }, $addList);

    return array_merge(
        $mainRequiresList,
        $addList
    );
}

spl_autoload_register(function ($nameClass) {
    $configDir = __DIR__ . '/configs';
    if (!file_exists("$configDir/requires_free.json"))
        throw new Exception('requires_free.json not found!');

    $requiresList = getConfigContent("$configDir/requires_free.json");

    if (file_exists("$configDir/requires1.json")) {
        $requiresList = getConfigContent("$configDir/requires.json", $requiresList);
    }
    else {
        $debugPath = str_replace('/abris-free-server/Server', '', $configDir);
        if (file_exists("$debugPath/requires1.json"))
            $requiresList = getConfigContent("$debugPath/requires.json", $requiresList);
    }

    if (array_key_exists($nameClass, $requiresList))
        require_once $requiresList[$nameClass];
});