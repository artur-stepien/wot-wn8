<?php

use PHPUnit\Framework\TestCase;
use Wargaming\WoT\Tests\Mockup\API;
use Wargaming\WoT\WN8;

/**
 * Test WN8 calculation.
 */
final class CalculationTest extends TestCase
{
    public function testCalculation()
    {

        // Mock API instance
        $api = new API('demo');

        // Create instance
        $wn8 = new WN8($api, 'FAKE_ACCOUNT');

        // Get Expected data from stored json
        $expected = file_get_contents(__DIR__ . '/Fixtures/wn8exp.json');
        $expected = json_decode($expected);
        $wn8->setExpectedTankValues($expected->data);

        // Calculate WN8
        $calculated = $wn8->calculate();

        $this->assertEquals(2728, $calculated, 'Calculated WN8 matches expected.');
    }
}