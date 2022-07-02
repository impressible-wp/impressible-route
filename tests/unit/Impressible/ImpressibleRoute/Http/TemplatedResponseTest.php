<?php
namespace Impressible\ImpressibleRoute\Http;

use Impressible\ImpressibleRoute\Http\TemplatedResponse;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Impressible\ImpressibleRoute\Http\TemplatedResponse
 */
class TemplatedResponseTest extends TestCase
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    public function testGetFilename()
    {
        $n = rand(1, 100);
        $type = "foobar-{$n}";
        $response = new TemplatedResponse($type);
        $this->assertEquals($type . '.php', $response->getFilename(), 'filename get from the response object should be equal to what was set to it.');
    }

    public function testToString()
    {
        $n = rand(1, 100);
        $type = "foobar-{$n}";
        $response = new TemplatedResponse($type);
        $this->assertEquals($type . '.php', (string) $response, 'filename get from the response object should be equal to what was set to it.');
    }
}
