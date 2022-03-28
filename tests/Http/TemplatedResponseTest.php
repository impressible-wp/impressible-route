<?php

namespace Impressible\ImpressibleRouteTest\Http;

use Impressible\ImpressibleRoute\Http\TemplatedResponse;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Impressible\ImpressibleRoute\Http\TemplatedResponse
 */
final class TemplatedResponseTest extends TestCase
{
    public function testGetFilename()
    {
        $n = rand(1, 100);
        $filename = "foobar-{$n}.php";
        $response = new TemplatedResponse($filename);
        $this->assertEquals($filename, $response->getFilename(), 'filename get from the response object should be equal to what was set to it.');
    }

    public function testToString()
    {
        $n = rand(1, 100);
        $filename = "foobar-{$n}.php";
        $response = new TemplatedResponse($filename);
        $this->assertEquals($filename, (string) $response, 'filename get from the response object should be equal to what was set to it.');
    }
}