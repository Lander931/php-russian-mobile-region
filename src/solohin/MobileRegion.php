<?php

namespace solohin;

class RegionChecker {
    private $regionsDatabase = null;
    const CSV_LINK = 'https://www.rossvyaz.ru/docs/articles/Kody_DEF-9kh.csv';
    const DB_UPDATE_FREQUENCY = 60*60*24*30;

    public function __construct($forceReload = false){
        $filePath = $this->downloadRegionsDatabase($forceReload);
        $this->loadRegionsDatabase($filePath);
    }

    public function getRegion($number){
        $number = intval($this->format($number));
        foreach($this->regionsDatabase as $region=>$masks){
            foreach($masks as $mask){
                if($number > intval($mask['from']) && $number < intval($mask['to'])){
                    return $region;
                }
            }
        }
        return 'Неизвестный регион';
    }

    private function format($number){
        $number = preg_replace("/[^0-9]/", "", $number);
        return substr($number, -10);
    }

    private function parse($csvString, $delimiter = ';', $skip = 1){
        $result = [];
        $csvString = mb_convert_encoding($csvString, "utf-8", "windows-1251");
        $lines  = explode("\n", $csvString);

        $lines = array_slice($lines, $skip);

        foreach($lines as $line){
            $lineData = explode($delimiter, $line);
            $lineData = array_map('trim', $lineData);

            if(!isset($lineData[5])){
                continue;
            }

            $result[$lineData[5]][] = [
                'from' => $lineData[0].$lineData[1],
                'to' => $lineData[0].$lineData[2],
            ];
        }
        return $result;
    }

    private function loadRegionsDatabase($filePath){
        if(is_null($this->regionsDatabase)){
            $json = file_get_contents($filePath);
            $this->regionsDatabase = json_decode($json, true);
        }
    }

    private function downloadRegionsDatabase($forceReload){
        $folderPath = dirname(dirname(__DIR__)).'/var/';
        if(!file_exists($folderPath)){
            mkdir($folderPath);
        }
        $filePath = $folderPath.basename(self::CSV_LINK, '.csv').'.json';
        if(
            $forceReload
            || !file_exists($filePath)
            || (time()-filectime($filePath)) > self::DB_UPDATE_FREQUENCY
        ){
            $csvContent = file_get_contents(self::CSV_LINK);
            $parsed = $this->parse($csvContent);
            $this->regionsDatabase = $parsed;
            $result = file_put_contents($filePath, json_encode($parsed));

            if(!$result){
                if(file_exists($filePath)){
                    trigger_error('Cannot update RosSvyaz database. Check write permisson on '.$filePath);
                }else{
                    throw new \Exception('Cannot load RosSvyaz database. Check write permisson on '.$filePath);
                }
            }
        }
        return $filePath;
    }
}