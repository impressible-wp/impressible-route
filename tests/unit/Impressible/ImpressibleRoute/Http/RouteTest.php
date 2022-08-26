<?php
namespace Impressible\ImpressibleRoute\Http;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Impressible\ImpressibleRoute\Http\Route
 */
class RouteTest extends TestCase
{
    public function testBasicOperations()
    {
        $regex = 'regex-' . rand(1, 100);
        $callable = fn() => null;
        $query = ['key-' . rand(1, 100) => 'value-' . rand(1, 100)];
        $routeSlug = 'route-slug-' . rand(1, 100);
        $after = 'after-' . rand(1, 100);

        $route1 = new Route(
            $regex,
            $callable
        );
        $this->assertEquals([$regex, [], 'top'], $route1->getRewriteRuleParams());
        $this->assertEquals([], $route1->getQuery());
        $this->assertNull($route1->getRouteSlug());
        $this->assertSame($callable, $route1->getCallable());

        $route2 = new Route(
            $regex,
            $callable,
            $query
        );
        $this->assertEquals([$regex, $query, 'top'], $route2->getRewriteRuleParams());
        $this->assertEquals($query, $route2->getQuery());
        $this->assertNull($route2->getRouteSlug());
        $this->assertSame($callable, $route2->getCallable());

        $route3 = new Route(
            $regex,
            $callable,
            $query,
            $routeSlug
        );
        $this->assertEquals([$regex, $query, 'top'], $route3->getRewriteRuleParams());
        $this->assertEquals($query, $route3->getQuery());
        $this->assertEquals($routeSlug, $route3->getRouteSlug());
        $this->assertSame($callable, $route3->getCallable());

        $route4 = new Route(
            $regex,
            $callable,
            $query,
            $routeSlug,
            $after
        );
        $this->assertEquals([$regex, $query, $after], $route4->getRewriteRuleParams());
        $this->assertEquals($query, $route4->getQuery());
        $this->assertEquals($routeSlug, $route4->getRouteSlug());
        $this->assertSame($callable, $route4->getCallable());
    }

    public function testWithQueryParam()
    {
        $key = 'key-' . rand(1, 100);
        $value = 'value-' . rand(1, 100);

        $regex = 'regex-' . rand(1, 100);
        $callable = fn() => null;
        $query1 = ['key-' . rand(1, 100) => 'value-' . rand(1, 100)];
        $query2 = $query1 + [$key => $value];
        $routeSlug = 'route-slug-' . rand(1, 100);
        $after = 'after-' . rand(1, 100);

        $route1 = new Route(
            $regex,
            $callable,
            $query1,
            $routeSlug,
            $after
        );
        $route2 = $route1->withQueryParam($key, $value);

        // Check query2 changed and query1 not affected.
        $this->assertEquals($query1, $route1->getQuery());
        $this->assertEquals($query2, $route2->getQuery());

        // Check query2 retains query1 values.
        $this->assertEquals([$regex, $query2, $after], $route2->getRewriteRuleParams());
        $this->assertEquals($routeSlug, $route2->getRouteSlug());
        $this->assertSame($callable, $route2->getCallable());
    }

    public function testWithPreGetPosts()
    {
        $regex = 'regex-' . rand(1, 100);
        $callable = fn() => null;
        $query = ['key-' . rand(1, 100) => 'value-' . rand(1, 100)];
        $routeSlug = 'route-slug-' . rand(1, 100);
        $after = 'after-' . rand(1, 100);
        $preGetPostCallable = fn() => null;

        $route1 = new Route(
            $regex,
            $callable,
            $query,
            $routeSlug,
            $after
        );
        $route2 = $route1->withPreGetPost($preGetPostCallable);

        // Check route2 changed and route1 not affected.
        $this->assertNull($route1->getPreGetPost());
        $this->assertSame($preGetPostCallable, $route2->getPreGetPost());

        // Check route2 retains route1 values.
        $this->assertEquals([$regex, $query, $after], $route2->getRewriteRuleParams());
        $this->assertEquals($query, $route2->getQuery());
        $this->assertEquals($routeSlug, $route2->getRouteSlug());
        $this->assertSame($callable, $route2->getCallable());
    }
}
