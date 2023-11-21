<?php

declare(strict_types=1);

namespace Iodev\Whois\Modules\Asn;

use InvalidArgumentException;
use Iodev\Whois\Loaders\FakeSocketLoader;
use Iodev\Whois\Whois;
use PHPUnit\Framework\TestCase;

class AsnParsingTest extends TestCase
{
    /**
     * @param $filename
     * @return string
     * @throws InvalidArgumentException
     */
    public static function loadContent($filename)
    {
        $file = __DIR__ . '/parsing_data/' . $filename;
        if (!file_exists($file)) {
            throw new InvalidArgumentException("File '$file' not found");
        }
        return file_get_contents($file);
    }

    /**
     * @param string $filename
     * @return Whois
     */
    private static function whoisFrom($filename)
    {
        $l = new FakeSocketLoader();
        $l->text = self::loadContent($filename);
        return new Whois($l);
    }

    /**
     * @param array $items
     */
    private static function assertDataItems($items)
    {
        foreach ($items as $item) {
            list ($domain, $text, $json) = $item;
            self::assertData($domain, $text, $json);
        }
    }

    /**
     * @param string $asn
     * @param string $srcTextFilename
     * @param string $expectedJsonFilename
     */
    private static function assertData($asn, $srcTextFilename, $expectedJsonFilename)
    {
        $w = self::whoisFrom($srcTextFilename);
        $info = $w->loadAsnInfo($asn);

        if (empty($expectedJsonFilename)) {
            self::assertNull($info, "Loaded info should be null for empty response ($srcTextFilename)");
            return;
        }

        $expected = json_decode(self::loadContent($expectedJsonFilename), true);
        self::assertNotEmpty($expected, "Failed to load/parse expected json");

        self::assertNotNull($info, "Loaded info should not be null ($srcTextFilename)");

        self::assertEquals(
            $expected["asn"],
            $info->asn,
            "ASN mismatch ($srcTextFilename)"
        );

        $actualRoutes = $info->routes;
        $expectedRoutes = $expected['routes'];

        self::assertEquals(
            count($expectedRoutes),
            count($actualRoutes),
            "Routes count mismatch ($srcTextFilename)"
        );

        foreach ($actualRoutes as $index => $actualRoute) {
            $expectedRoute = $expectedRoutes[$index];
            self::assertEquals(
                $expectedRoute["route"],
                $actualRoute->route,
                "Route ($index) 'route' mismatch ($srcTextFilename)"
            );
            self::assertEquals(
                $expectedRoute["route6"],
                $actualRoute->route6,
                "Route ($index) 'route6' mismatch ($srcTextFilename)"
            );
            self::assertEquals(
                $expectedRoute["descr"],
                $actualRoute->descr,
                "Route ($index) 'descr' mismatch ($srcTextFilename)"
            );
            self::assertEquals(
                $expectedRoute["origin"],
                $actualRoute->origin,
                "Route ($index) 'origin' mismatch ($srcTextFilename)"
            );
            self::assertEquals(
                $expectedRoute["mntBy"],
                $actualRoute->mntBy,
                "Route ($index) 'mntBy' mismatch ($srcTextFilename)"
            );
            self::assertEquals(
                $expectedRoute["changed"],
                $actualRoute->changed,
                "Route ($index) 'changed' mismatch ($srcTextFilename)"
            );
            self::assertEquals(
                $expectedRoute["source"],
                $actualRoute->source,
                "Route ($index) 'source' mismatch ($srcTextFilename)"
            );
        }
    }


    public function test_AS32934()
    {
        self::assertDataItems([
            [ "AS32934", "AS32934/whois.ripe.net.txt", null ],
            [ "AS32934", "AS32934/whois.radb.net.txt", "AS32934/whois.radb.net.json" ],
        ]);
    }

    public function test_AS62041()
    {
        self::assertDataItems([
            [ "AS62041", "AS62041/whois.ripe.net.txt", "AS62041/whois.ripe.net.json" ],
            [ "AS62041", "AS62041/whois.radb.net.txt", "AS62041/whois.radb.net.json" ],
        ]);
    }
}