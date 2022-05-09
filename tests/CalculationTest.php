<?php

use PHPUnit\Framework\TestCase;
use Wargaming\WoT\Tests\Mockup\API;
use Wargaming\WoT\WN8;

/**
 * Test WN8 calculation.
 */
final class CalculationTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testCalculation()
    {

        // Mock API instance
        $language = new Wargaming\Language\EN();
        $server = new Wargaming\Server\EU('demo');
        $api = new API($language, $server);

        // Create instance
        $wn8 = new WN8($api, 'FAKE_ACCOUNT');

        // Get Expected data from stored json
        $expected = file_get_contents(__DIR__ . '/Fixtures/wn8exp.json');
        $expected = json_decode($expected, false);
        $wn8->setExpectedTankValues($expected->data);

        // Calculate WN8
        $calculated = $wn8->calculate();

        $this->assertEquals(2728, $calculated, 'Calculated WN8 matches expected.');
    }
}