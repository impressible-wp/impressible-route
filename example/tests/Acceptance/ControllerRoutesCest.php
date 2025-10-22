<?php

declare(strict_types=1);


namespace Impressible\ImpressibleExample\Tests\Acceptance;

use Codeception\Exception\InjectionException;
use Impressible\ImpressibleExample\Tests\Support\AcceptanceTester;

final class ControllerRoutesCest
{
    public function _before(AcceptanceTester $I): void
    {
        // Code here will be executed before each test.
    }

    /**
     * Test if the supposed route to the content index page works.
     *
     * @param AcceptanceTester $I 
     * @return void 
     * @throws InjectionException 
     */
    public function tryContentIndexPage(AcceptanceTester $I): void
    {
        $I->wantToTest('if the content index page is served correctly.');
        $I->amOnPage('/mycontent');
        $I->see('Example Plugin Page');
        $I->see('This is an example page served by the example plugin using Impressible Route.');
    }

    /**
     * Test if the supposed route to the JSON endpoint works.
     *
     * @param AcceptanceTester $I 
     * @return void 
     * @throws InjectionException 
     */
    public function tryJsonEndpoint(AcceptanceTester $I): void
    {
        // Assuming there is a post with ID 1 in the test database.
        $I->wantToTest('if the JSON endpoint is served correctly.');
        $I->amOnPage('/mycontent/mypost/1.json');
        $I->makeScreenshot('json-endpoint-page');

        // Fetch the JSON content directly to test.
        $base_url = getenv('WORDPRESS_CI_URL') ?? 'http://localhost:8080';
        $url = $base_url . '/mycontent/mypost/1.json';
        $content = file_get_contents($url);
        $data = json_decode($content, associative: true, flags: JSON_THROW_ON_ERROR);

        // Get the response header
        // Ensure response header is in the environment.
        // After PHP 8.5, this function is available and preferred.
        $response_header = function_exists("http_get_last_response_headers")
            ? http_get_last_response_headers()
            : $http_response_header;

        // Assert response header
        $I->assertContains("HTTP/1.1 200 OK", $response_header);
        $I->assertContains("Content-Type: application/json", $response_header);

        // Assert page contents
        $I->assertIsArray($data);
        $I->assertArrayHasKey('id', $data);
        $I->assertEquals(1, $data['id']);
        $I->assertArrayHasKey('title', $data);
        $I->assertEquals('Hello world!', $data['title']); // title of WP post ID 1 by installation
        $I->assertArrayHasKey('content', $data);
    }
}
