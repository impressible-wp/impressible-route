<?php
namespace Impressible\ImpressibleRoute\Http;

use GuzzleHttp\Psr7\Response;
use Impressible\ImpressibleRoute\Http\Router;
use Impressible\ImpressibleRoute\Http\TemplatedResponse;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Impressible\ImpressibleRoute\Http\Router
 */
class RouterTest extends TestCase
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    public function testRegisterRoutes()
    {
        /**
         * @see https://developer.wordpress.org/reference/classes/wp_rewrite/
         */
        $wp_rewrite = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['add_rule'])
            ->getMock();

        /**
         * @see https://developer.wordpress.org/reference/classes/wp_query/
         */
        $wp_query = $this->getMockBuilder(\stdClass::class)
            ->getMock();

        // Variable name to use for routing.
        $varName = 'test_var_' . rand(1,100);

        // define expectataions.
        $wp_rewrite->expects($this->exactly(2))
            ->method('add_rule')
            ->withConsecutive(
                [$this->equalTo('mycontents$'), $this->equalTo([$varName => 'route-1']), $this->equalTo('top')],
                [$this->equalTo('mymemories/(\d+)$'), $this->equalTo([$varName => 'route-2', 'mid' => '$matches[0]']), $this->equalTo('top')]
            );

        // do addRoute and registerRoutes routine.
        (new Router(
                $wp_rewrite,
                $wp_query,
                $varName,
                'template'
            ))
            ->addRoute('mycontents$', fn() => null)
            ->addRoute('mymemories/(\d+)$', fn() => null, ['mid' => '$matches[0]'])
            ->registerRoutes();
    }

    public function testKeepQueryVar()
    {
        /**
         * @see https://developer.wordpress.org/reference/classes/wp_rewrite/
         */
        $wp_rewrite = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['add_rule'])
            ->getMock();

        /**
         * @see https://developer.wordpress.org/reference/classes/wp_query/
         */
        $wp_query = $this->getMockBuilder(\stdClass::class)
            ->getMock();

        // Variable name to use for routing.
        $varName = 'test_var_' . rand(1,100);

        // do addRoute and registerRoutes routine.
        $router = (new Router(
                $wp_rewrite,
                $wp_query,
                $varName,
                'template'
            ))
            ->addRoute('mycontents$', fn() => null)
            ->addRoute('mymemories/(\d+)$', fn() => null, ['mid' => '$matches[0]'])
            ->registerRoutes();

        $this->assertSame(
            ['original', $varName, 'mid'],
            $router->keepQueryVar(['original']),
            'keepQueryVar should append the allowed query variable name list with the queryVarName and other extra variables used in routing.'
        );
    }

    public function testGetRouteSlug()
    {
        /**
         * @see https://developer.wordpress.org/reference/classes/wp_rewrite/
         */
        $wp_rewrite = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['add_rule'])
            ->getMock();

        /**
         * @see https://developer.wordpress.org/reference/classes/wp_query/
         */
        $wp_query = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['get'])
            ->getMock();

        // Variable name to use for routing.
        $varName = 'test_var_' . rand(1,100);

        // define expectataions.
        $wp_query->expects($this->exactly(1))
            ->method('get')
            ->with($this->equalTo($varName), $this->equalTo(false))
            ->willReturn('foobar-slug');

        // do addRoute and registerRoutes routine.
        $router = (new Router(
                $wp_rewrite,
                $wp_query,
                $varName,
                'template'
            ));

        $slug = $router->getRouteSlug();
        $this->assertEquals('foobar-slug', $slug, 'should return the slug from WP_Query::get');
    }

    public function testDispatch()
    {
        /**
         * @see https://developer.wordpress.org/reference/classes/wp_rewrite/
         */
        $wp_rewrite = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['add_rule'])
            ->getMock();

        /**
         * @see https://developer.wordpress.org/reference/classes/wp_query/
         */
        $wp_query = $this->getMockBuilder(\stdClass::class)
            ->getMock();

        // Variable name to use for routing.
        $varName = 'test_var_' . rand(1,100);

        // Mock handlers
        $handler1 = fn() => null;
        $handler2 = fn() => null;
        $handler3 = fn() => null;
        $handler4 = fn() => null;

        // do addRoute and registerRoutes routine.
        $router = (new Router(
            $wp_rewrite,
            $wp_query,
            $varName,
            'template'
        ))
            ->addRoute('mycontents$', $handler1)
            ->addRoute('mymemories/(\d+)$', $handler2, ['mid' => '$matches[0]'])
            ->addRoute('cool$', $handler3, [], 'my-slug')
            ->addRoute('coolToo$', $handler4, [], 'my-slug')
            ->registerRoutes();

        $this->assertSame(
            $handler1,
            $router->dispatch('route-1'),
            'dispatching the slug "route-1" should get callable $handler1'
        );
        $this->assertSame(
            $handler2,
            $router->dispatch('route-2'),
            'dispatching the slug "route-2" should get callable $handler2'
        );
        $this->assertSame(
            $handler4,
            $router->dispatch('my-slug'),
            'dispatching the slug "my-slug" should get callable $handler4'
        );
    }

    /**
     * @runInSeparateProcess
     */
    public function testHandleResponse_PsrResponseInterface()
    {
        /**
         * @see https://developer.wordpress.org/reference/classes/wp_rewrite/
         */
        $wp_rewrite = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['add_rule'])
            ->getMock();

        /**
         * @see https://developer.wordpress.org/reference/classes/wp_query/
         */
        $wp_query = $this->getMockBuilder(\stdClass::class)
            ->getMock();

        // Router to test with.
        $router = (new Router(
            $wp_rewrite,
            $wp_query,
            'some_var_name',
            'template'
        ));

        // Create response to test with.
        $expectedBody = 'This is a random response ' . rand(1, 100);
        $response = new Response(
            203,
            [
                'Content-Type' => 'text/foobar;charset=UTF-8',
                'X-Custom-Header' => ['value1', 'value2'],
            ],
            $expectedBody
        );

        // Do handleResponse withing an output buffer.
        ob_start();
        $return = $router->handleResponse($response);
        $resultBody = ob_get_clean();

        $this->assertFalse($return, 'The return value of handleResponse shoudl be false');
        $this->assertEquals(
            203,
            http_response_code(),
            'The response code sent should be the same as the PSR response code.'
        );
        $this->assertEquals(
            [
                'Content-Type: text/foobar;charset=UTF-8',
                'X-Custom-Header: value1',
                'X-Custom-Header: value2',
            ],
            call_user_func('xdebug_get_headers'),
            'The header sent should be the same as the PSR response header.'
        );
        $this->assertEquals(
            $expectedBody,
            $resultBody,
            'The body sent should be the same as the PSR response body.'
        );
    }

    /**
     * @runInSeparateProcess
     */
    public function testHandleResponse_TemplatedResponse()
    {
        /**
         * @see https://developer.wordpress.org/reference/classes/wp_rewrite/
         */
        $wp_rewrite = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['add_rule'])
            ->getMock();

        /**
         * @see https://developer.wordpress.org/reference/classes/wp_query/
         */
        $wp_query = $this->getMockBuilder(\stdClass::class)
            ->getMock();

        // Router to test with.
        $router = (new Router(
            $wp_rewrite,
            $wp_query,
            'some_var_name',
            'some/system/template-dir'
        ));

        // Create response to test with.
        $response = new TemplatedResponse('my-template-file');

        // Do handleResponse withing an output buffer.
        ob_start();
        $return = $router->handleResponse($response);
        $bufferedOutput = ob_get_clean();

        $this->assertEquals($return, 'some/system/template-dir/my-template-file', 'The return value of handleResponse should be the full path to the supposed template.');
        $this->assertEmpty($bufferedOutput, 'There should be no other output.');
    }
}
