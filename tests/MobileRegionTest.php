<?php
use \PHPUnit\Framework\TestCase;

class MobileRegionTest extends TestCase {
    public function testKnownPhones() {
        $regionChecker = new \solohin\RegionChecker;

        $this->assertEquals($regionChecker->getRegion('+7 918 288 16 00'), 'Краснодарский край');
        $this->assertEquals($regionChecker->getRegion('8 918 288 16 00'), 'Краснодарский край');
        $this->assertEquals($regionChecker->getRegion('8918-288-16-00'), 'Краснодарский край');
        $this->assertEquals($regionChecker->getRegion('79182881600'), 'Краснодарский край');

        $this->assertEquals($regionChecker->getRegion('+7 (999) 636-19-00'), 'Краснодарский край');
        $this->assertEquals($regionChecker->getRegion('+7 (981) 765-05-00'), 'г. Санкт-Петербург и Ленинградская область');
        $this->assertEquals($regionChecker->getRegion('+7 (961) 573-45-00'), 'Свердловская обл.');
    }

    public function tearDown() {
        passthru('rm -f ' . __DIR__ . '/../var/*');
    }

    public function testSourceExists() {
        $context = stream_context_create(['http' => ['method' => 'HEAD']]);
        $fd = fopen(\solohin\RegionChecker::CSV_LINK, 'rb', false, $context);
        $responseFirstLine = stream_get_meta_data($fd)['wrapper_data'][0];
        fclose($fd);

        $this->assertEquals($responseFirstLine, "HTTP/1.1 200 OK");
    }
}