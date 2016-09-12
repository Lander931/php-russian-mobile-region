<?php
use \PHPUnit\Framework\TestCase;

class MobileRegionTest extends TestCase
{
    public function testKnownPhones()
    {
        $regionChecker = new \solohin\RegionChecker;

        $this->assertEquals($regionChecker->getRegion('+7 918 288 16 00'), 'Краснодарский край');
        $this->assertEquals($regionChecker->getRegion('8 918 288 16 00'), 'Краснодарский край');
        $this->assertEquals($regionChecker->getRegion('8918-288-16-00'), 'Краснодарский край');
        $this->assertEquals($regionChecker->getRegion('79182881600'), 'Краснодарский край');

        $this->assertEquals($regionChecker->getRegion('+7 (999) 636-19-00'), 'Краснодарский край');
        $this->assertEquals($regionChecker->getRegion('+7 (981) 765-05-00'), 'Санкт - Петербург и Ленинградская область');
        $this->assertEquals($regionChecker->getRegion('+7 (961) 573-45-00'), 'Свердловская область');

        $this->assertEquals($regionChecker->getRegion('8495 888 88 88'), 'г. Москва (Троицкий)');
        $this->assertEquals($regionChecker->getRegion('8 (8617) 777-614'), 'г. Новороссийск');
        $this->assertEquals($regionChecker->getRegion('+7 812 730 30 30'), 'г. Санкт-Петербург');
    }

    public function testUnknownPhones()
    {
        $regionChecker = new \solohin\RegionChecker;
        $this->assertEquals($regionChecker->getRegion('+7 (100) 573-45-00'), 'Неизвестный регион');
        $this->assertEquals($regionChecker->getRegion('00000'), 'Неизвестный регион');
        $this->assertEquals($regionChecker->getRegion('123'), 'Неизвестный регион');
        $this->assertEquals($regionChecker->getRegion('253422'), 'Неизвестный регион');
    }

    public function testSpeed()
    {
        $regionChecker = new \solohin\RegionChecker;
        unset($regionChecker);

        $start = microtime(true);
        $regionChecker = new \solohin\RegionChecker;
        $count = 10;

        for ($i = 0; $i < $count; $i++) {
            $regionChecker->getRegion('+7 918 ' . rand(1000000, 9999999));
        }

        $spent = microtime(true) - $start;

        $this->assertLessThan($count * 0.15, $spent);
    }

    public function testSourceExists()
    {
        $context = stream_context_create(['http' => ['method' => 'HEAD']]);

        foreach (\solohin\RegionChecker::CSV_LINKS as $link) {
            $fd = fopen($link, 'rb', false, $context);
            $responseFirstLine = stream_get_meta_data($fd)['wrapper_data'][0];
            fclose($fd);

            $this->assertEquals($responseFirstLine, "HTTP/1.1 200 OK");
        }
    }
}