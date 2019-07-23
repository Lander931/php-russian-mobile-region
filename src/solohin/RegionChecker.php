<?php

namespace solohin;

use PDO;

class RegionChecker
{
    private $regionsDatabase = null;
    private $db = null;
    private $verbose = false;
    const CSV_LINKS = [
        'https://rossvyaz.ru/data/ABC-3xx.csv',
        'https://rossvyaz.ru/data/ABC-4xx.csv',
        'https://rossvyaz.ru/data/ABC-8xx.csv',
        'https://rossvyaz.ru/data/DEF-9xx.csv',
    ];
    const DB_UPDATE_FREQUENCY = 60 * 60 * 24 * 180;

    public function __construct($forceReload = false, $verbose = false)
    {
        $this->verbose = $verbose;
        $this->downloadRegionsDatabase($forceReload);
    }

    public function getRegion($number)
    {
        $number = intval($this->format($number));
        $statement = $this->getPDO()->prepare('SELECT region FROM codes WHERE from_number < :num AND to_number > :num LIMIT 1');
        $statement->execute([':num' => $number]);
        if (($row = $statement->fetch()) !== false) {
            $region = $row['region'];
            if (mb_strpos($region, '|') !== false) {
                $region = explode('|', $region)[0];
            }
            return $region;
        }
        return 'Неизвестный регион';
    }

    private function format($number)
    {
        $number = preg_replace("/[^0-9]/", "", $number);
        return substr($number, -10);
    }

    private function parse($csvString, $delimiter = ';', $skip = 1)
    {
        $result = [];
        $csvString = mb_convert_encoding($csvString, "utf-8", "windows-1251");
        $lines = explode("\n", $csvString);

        $lines = array_slice($lines, $skip);

        foreach ($lines as $line) {
            $lineData = explode($delimiter, $line);
            $lineData = array_map('trim', $lineData);

            if (!isset($lineData[5])) {
                continue;
            }

            $result[$lineData[5]][] = [
                'from' => $lineData[0] . $lineData[1],
                'to' => $lineData[0] . $lineData[2],
            ];
        }
        return $result;
    }

    /**
     * @return PDO
     */
    private function getPDO()
    {
        if (is_null($this->db)) {
            $dir = dirname(dirname(__DIR__)) . '/var/';
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }

            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $filePath = 'sqlite:' . str_replace('\\', '/', $dir) . 'regions_database.sqlite';
            } else {
                $filePath = 'sqlite:/' . $dir . 'regions_database.sqlite';
            }

            $this->db = new PDO($filePath) or die("cannot open the database");

            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        return $this->db;
    }

    private function isDbUpdated()
    {
        try {
            $pdo = $this->getPDO();
            $pdo->query('SELECT 1 FROM codes');
            $statement = $pdo->query('SELECT value FROM status WHERE key = \'updated_ts\'');

            $statement->execute();
            if (($row = $statement->fetch(PDO::FETCH_ASSOC)) !== false) {
                return ($row['value']) > (time() - self::DB_UPDATE_FREQUENCY);
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function clearRegionsDB()
    {
        $pdo = $this->getPDO();
        $pdo->exec("CREATE TABLE IF NOT EXISTS codes (from_number INTEGER, to_number INTEGER, region TEXT)");
        $pdo->exec("CREATE TABLE IF NOT EXISTS status (key TEXT, value TEXT)");
        $pdo->exec("DELETE FROM codes");
        $pdo->exec("DELETE FROM status");
    }

    private function setUpdatedTS()
    {
        $statement = $this->getPDO()->prepare("INSERT INTO status(key,value) VALUES('updated_ts',:value)");
        $statement->execute([':value' => time()]);
    }

    private function addToRegionsDatabase($parsed, $fileName)
    {
        $time = microtime(true);
        $regionIndex = 0;

        foreach ($parsed as $region => $masks) {
            $pdo = $this->getPDO();
            $statement = $pdo->prepare("INSERT INTO codes(from_number,to_number,region) VALUES(:from,:to,:region)");

            $index = 0;
            $regionIndex++;

            foreach ($masks as $mask) {
                $statement->execute([
                    ':from' => $this->format($mask['from']),
                    ':to' => $this->format($mask['to']),
                    ':region' => $region,
                ]);
                if ($this->verbose) {
                    $index++;
                    if ($index % 500 === 0) {
                        passthru('clear;');
                        printf("%.3f\n", microtime(true) - $time);
                        printf("Маска %s/%s\n", $index, count($masks));
                        printf("Регион %s/%s\n", $regionIndex, count($parsed));
                        printf("Файл %s\n", $fileName);
                    }
                }

            }
        }
    }

    private function downloadRegionsDatabase($forceReload)
    {
        if (
            $forceReload
            || !$this->isDbUpdated()
        ) {
            $this->clearRegionsDB();

            foreach (self::CSV_LINKS as $link) {
                $csvContent = file_get_contents($link);
                $parsed = $this->parse($csvContent);
                $this->addToRegionsDatabase($parsed, basename($link));
            }

            $this->setUpdatedTS();
        }
    }
}