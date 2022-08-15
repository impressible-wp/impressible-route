<?php

namespace Impressible\ImpressibleRoute\Http;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Impressible\ImpressibleRoute\Http\AdminRouter
 * @covers \Impressible\ImpressibleRoute\Http\AdminRoute
 */
class AdminRouterTest extends TestCase
{
    public function testRegisterActions()
    {
        $router = new AdminRouter;
        $actual = null;
        $add_action = function ($hook, $callable) use (&$actual) {
            $actual = [$hook, $callable];
        };
        $router->registerActions($add_action);

        // Check if the add_action function got the expected data.
        $this->assertEquals(['admin_init', [$router, 'handleRoute']], $actual);
    }

    public function testRegisterMenuPages()
    {
        $callback1 = function () {};
        $callback2 = function () {};
        $callback3 = function () {};
        $callback4 = function () {};
        $router = new AdminRouter;
        $router
            ->addRoute(AdminRoute::menu(
                'Page Title 1',
                'Menu Title 1',
                'capability 1',
                'menu_slug_1',
                $callback1,
                'icon-1',
                1
            ))
            ->addRoute(AdminRoute::menu(
                'Page Title 2',
                'Menu Title 2',
                'capability 2',
                'menu_slug_2',
                $callback2,
                'icon-2',
                2
            ))
            ->addRoute(AdminRoute::subMenu(
                'menu_slug_1',
                'Page Title 3',
                'Menu Title 3',
                'capability 3',
                'menu_slug_3',
                $callback3,
                123
            ))
            ->addRoute(AdminRoute::subMenu(
                'menu_slug_2',
                'Page Title 4',
                'Menu Title 4',
                'capability 4',
                'menu_slug_4',
                $callback4,
                124
            ));

        $menu = [];
        $add_menu_page = function ($page_title, $menu_title, $capability, $menu_slug, $function = '', $icon_url = '', $position = null) use (&$menu) {
            $menu[] = [
                $page_title,
                $menu_title,
                $capability,
                $menu_slug,
                $function,
                $icon_url,
                $position
            ];
        };

        $submenu = [];
        $add_submenu_page = function ($parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function = '', $position = null ) use (&$submenu) {
            $submenu[] = [
                $parent_slug,
                $page_title,
                $menu_title,
                $capability,
                $menu_slug,
                $function,
                $position
            ];
        };

        $router->registerMenuPages($add_menu_page, $add_submenu_page);

        $this->assertEquals([
            'Page Title 1',
            'Menu Title 1',
            'capability 1',
            'menu_slug_1',
            [$router, 'triggerAdminResponse'],
            'icon-1',
            1
        ], $menu[0], 'Expect first menu item to be set correctly.');
        $this->assertEquals([
            'Page Title 2',
            'Menu Title 2',
            'capability 2',
            'menu_slug_2',
            [$router, 'triggerAdminResponse'],
            'icon-2',
            2
        ], $menu[1], 'Expect second menu item to be set correctly.');
        $this->assertEquals([
            'menu_slug_1',
            'Page Title 3',
            'Menu Title 3',
            'capability 3',
            'menu_slug_3',
            [$router, 'triggerAdminResponse'],
            123
        ], $submenu[0], 'Expect first submenu item to be set correctly.');
        $this->assertEquals([
            'menu_slug_2',
            'Page Title 4',
            'Menu Title 4',
            'capability 4',
            'menu_slug_4',
            [$router, 'triggerAdminResponse'],
            124
        ], $submenu[1], 'Expect second submenu item to be set correctly.');
    }

    public function testRoute()
    {
        $callback1 = function () { return 'callback1'; };
        $callback2 = function () { return 'callback2'; };
        $router = new AdminRouter;
        $router
            ->addRoute(AdminRoute::menu(
                'Page Title 1',
                'Menu Title 1',
                'capability 1',
                'menu_slug_1',
                $callback1,
                'icon-1',
                1
            ))
            ->addRoute(AdminRoute::subMenu(
                'menu_slug_1',
                'Page Title 2',
                'Menu Title 2',
                'capability 2',
                'menu_slug_2',
                $callback2,
                1
            ));

        ob_start();
        $router->handleRoute('menu_slug_1');
        $response = ob_get_clean();
        $this->assertEquals('callback1', $response);

        ob_start();
        $router->handleRoute('menu_slug_2');
        $response = ob_get_clean();
        $this->assertEquals('callback2', $response);

    }
}
