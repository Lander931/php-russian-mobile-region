<?php
/**
 * Created by PhpStorm.
 * User: solohin
 * Date: 12.09.16
 * Time: 14:19
 */
if(file_exists(__DIR__.'/../vendor/autoload.php')){
    require_once __DIR__.'/../vendor/autoload.php';
}else{
    require_once __DIR__.'/../../../autoload.php';
}

$regions = new \solohin\RegionChecker(true, true);