<?php

require_once 'services_methods.php';

function GetConfigContent($filePath, $mainRequiresList = array()) {
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

function RegisterLibrary($fullPath, $nameClass) {
    if (!file_exists($fullPath))
        throw new Exception("$fullPath not found!");

    $requiresList = GetConfigContent($fullPath);

    if (array_key_exists($nameClass, $requiresList))
        require_once $requiresList[$nameClass];
}


spl_autoload_register(function ($nameClass) {
    $configDir = __DIR__ . '/configs';
    $fileName = 'requires_free.json';

    RegisterLibrary("$configDir/$fileName", $nameClass);
});