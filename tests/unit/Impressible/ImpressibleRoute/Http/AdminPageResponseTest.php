<?php

namespace Impressible\ImpressibleRoute\Http;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Impressible\ImpressibleRoute\Http\AdminPageResponse
 */
class AdminPageResponseTest extends TestCase
{
    public function testCall()
    {
        $rand1 = rand(0, 1000);
        $rand2 = rand(0, 1000);
        $response = new AdminPageResponse(function ($rand1) use ($rand2) {
            return [$rand1, $rand2];
        });

        $this->assertEquals([$rand1, $rand2], $response->call($rand1));
    }
}
