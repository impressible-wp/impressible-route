<?php

namespace Impressible\ImpressibleRoute\Http;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Impressible\ImpressibleRoute\Http\AdminRoute
 */
class AdminRouteTest extends TestCase
{
    public function testMenu()
    {
        $callback = function () {};
        $route = AdminRoute::menu(
            'page_title',
            'menu_title',
            'capability',
            'menu_slug',
            $callback,
            'icon_url',
            123
        );

        $this->assertTrue($route->isMenu());
        $this->assertFalse($route->isSubMenu());
        $this->assertEquals('page_title', $route->getPageTitle());
        $this->assertEquals('menu_title', $route->getMenuTitle());
        $this->assertEquals('capability', $route->getCapability());
        $this->assertEquals('menu_slug', $route->getMenuSlug());
        $this->assertEquals($callback, $route->getCallback());
        $this->assertEquals('icon_url', $route->getIconUrl());
        $this->assertEquals(123, $route->getPosition());
    }

    public function testSubMenu()
    {
        $callback = function () {};
        $route = AdminRoute::subMenu(
            'parent_slug',
            'page_title',
            'menu_title',
            'capability',
            'menu_slug',
            $callback,
            123
        );

        $this->assertFalse($route->isMenu());
        $this->assertTrue($route->isSubMenu());
        $this->assertEquals('parent_slug', $route->getParentSlug());
        $this->assertEquals('page_title', $route->getPageTitle());
        $this->assertEquals('menu_title', $route->getMenuTitle());
        $this->assertEquals('capability', $route->getCapability());
        $this->assertEquals('menu_slug', $route->getMenuSlug());
        $this->assertEquals($callback, $route->getCallback());
        $this->assertEquals(123, $route->getPosition());
    }

    public function testMenuWithInvalidCallback()
    {
        $route = AdminRoute::menu(
            'page_title',
            'menu_title',
            'capability',
            'menu_slug',
            'invalid_callback',
            'icon_url',
            123
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Callback is not a callable: ' . var_export('invalid_callback', true));
        $route->getCallback();
    }
}
