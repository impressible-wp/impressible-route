<?php

/**
 * Plugin Name:       Impressible Example Plugin
 * Plugin URI:        https://github.com/impressible-wp/impressible-route
 * Description:       An example plugin for Impressible Route package.
 * Version:           2.0.0
 * Requires at least: 5.2
 * Requires PHP:      8.1
 * Author:            Koala Yeung
 * Author URI:        https://github.com/yookoala/
 * License:           MIT
 * License URI:       https://mit-license.org/
 */

use Impressible\ImpressibleRoute\Http\Route;
use Impressible\ImpressibleRoute\Http\Router;
use Impressible\ImpressibleRoute\LazyLoadObject;
use Impressible\ImpressibleExample\Controller;

// Try to load local autoloader if there is no class loader yet.
if (!class_exists(Controller::class) && is_file(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

/**
 * Implements init hook to declare and register routes to WP_Rewrite.
 *
 * Register routes to the global WP_Rewrite object.
 * 
 * @return void
 */
function example_plugin_register_routes(): void
{
    /** @var \wpdb $wpdb */
    global $wpdb;

    // Lazyload a MyController that, for demo purpose only, somehow need to use wpdb.
    // For usage purpose, this is the same as using Controller directly. Force to typehint
    // the variable as Controller to help IDEs understand the type.

    /** @var Controller $controller */
    $controller = new LazyLoadObject(fn() => new Controller($wpdb));

    // Create a router instance and register routes with it.
    $router = Router::fromEnvironment(
        'example_plugin_route',  // query parameter used for routing unique to this plugin.
        __DIR__ . '/templates',  // folder for Wordpress template.
    );

    // Example route 1
    $router->add(new Route(
        'mycontent$',
        $controller->handleContentIndex(...),
    ));

    // Example route 2
    $router->add(
        (new Route(
            'mycontent/mypost/(\d+)\.json$',
            $controller->handleJsonEndpoint(...),
            // Define query arguments supplied to the global \WP_Query
            // that will be passed to the controller method.
            [
                'post_id' => '$matches[1]',
                'post_type' => 'post',
            ],
        )),
    );

    // Example route 3
    $router->add(
        (new Route(
            'mycontent/mymedia/(\d+)$',
            $controller->handleMediaEndpoint(...),
            // Define query arguments supplied to the global \WP_Query
            // that will be passed to the controller method.
            [
                'post_id' => '$matches[1]',
                'post_type' => 'mymedia',
            ],
        ))->withPreGetPost(function (\WP_Query $wpQuery) {
            // Show all mymedia to the author (after login)
            if (($userId = get_current_user_id()) != 0) {
                $author = get_user_by('slug', $wpQuery->get('author_name'));
                if ($userId === $author->ID) {
                    // Get post of all status to the post author.
                    $statuses = array_keys(get_post_statuses());
                    $wpQuery->set('post_status', $statuses);
                }
            }
        }),
    );

    // register the router methods to the Wordpress environment.
    $router->register();
}
add_action('init', example_plugin_register_routes(...));

/**
 * Flush route rewrites on installation.
 * 
 * This is useful if your Wordpress site has already been installed with friendly-URL enabled.
 * 
 * @see https://developer.wordpress.org/reference/functions/register_post_type/#flushing-rewrite-on-activation
 * @see https://developer.wordpress.org/reference/functions/flush_rewrite_rules/
 * 
 * @return void
 */
function example_plugin_rewrite_flush(): void
{
    // First, register the routes you define
    example_plugin_register_routes();

    // Then, update the .htaccess with the new rewrite rules set
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, example_plugin_rewrite_flush(...));
