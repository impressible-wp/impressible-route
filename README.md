# Impressible Route


[![CI][badge-ci]][link-ci] [![badge-coverage][badge-coverage]][link-coverage] [![Packagist][badge-packagist]][link-packagist]

A routing framework for coding modern PHP in [Wordpress](https://wordpress.org/) Plugin.

[badge-ci]: https://gitlab.com/impressible/impressible-route/badges/main/pipeline.svg?key_text=main
[link-ci]: https://gitlab.com/impressible/impressible-route/-/pipelines?page=1&ref=main&scope=branches
[badge-coverage]: https://gitlab.com/impressible/impressible-route/badges/main/coverage.svg
[link-coverage]: https://gitlab.com/impressible/impressible-route
[badge-packagist]: https://img.shields.io/packagist/v/impressible/impressible-route.svg
[link-packagist]: https://packagist.org/packages/impressible/impressible-route

## Why?

For PHP developer who already adapted modern PHP appropaches (e.g. composer
package management, PSR-4 autoloading with namespace, service container, etc),
it's quite painful to work in the Wordpress environment.

Let's say you want to:
1. write your Wordpress plugin with custom routing; and you
2. want to structure your code into controller that works with [psr/http-message][psr-http-message] request and response (for future flexibilities); and
3. you want to lazy-load your controller with custom initialization logics or
   even PSR compliant [service container][psr-container].

Then this library is for you.

## How to Use This?

### Step 1: Routing and Controller

When writing your plugin, add these hook implementations:

```php
use Impressible\ImpressibleRoute\Http\Router;
use Impressible\ImpressibleRoute\LazyLoadObject;

require_once __DIR__ . '/vendor/autoload.php';

/**
 * Register routes to the global WP_Rewrite object.
 */
function my_plugin_register_routes() {
   /**
    * @var \wpdb $wpdb
    */
   global $wpdb;

   // Lazyload a MyController that, for demo purpose only, somehow need to use wpdb.
   /** @var MyController $controller */
   $controller = new LazyLoadObject(fn() => new MyController($wpdb));

   // Create a router instance and register routes with it.
   $router = Router::fromEnvironment(
         'my_plugin_route', // query parameter used for routing.
         __DIR__            // folder for Wordpress template.
      )
      // Example route 1
      ->addRoute(new Route(
        'mycontent$',
        $controller->handleContentIndex(...)
      ));
      // Example route 2
      ->addRoute(
        (new Route(
          'mycontent/mymedia/(\d+)$',
          $controller->handleMediaEndpoint(...),
          // Define query arguments supplied to the global \WP_Query
          // that will be passed to the controller method.
          [
            'post_id' => '$matches[1]',
            'post_type' => 'mymedia',
          ],
        ))->withPreGetPosts(function (\WP_Query $wpQuery) {
          // Show all mymedia to the author (after login)
          if (($userId = get_current_user_id()) != 0) {
            $author = get_user_by('slug', $query->get('author_name'));
            if ($userId === $author->ID) {
              // Get post of all status to the post author.
              $statuses = array_keys(get_post_statuses());
              $query->set('post_status', $statuses);
            }
          }
        })
      )
      // register the router methods to the Wordpress environment.
      ->register();
}
add_action('init', my_plugin_register_routes(...));

/**
 * Flush route rewrites on installation.
 * 
 * @see https://developer.wordpress.org/reference/functions/register_post_type/#flushing-rewrite-on-activation
 * @see https://developer.wordpress.org/reference/functions/flush_rewrite_rules/
 */
function my_plugin_rewrite_flush() {
    // First, register the routes you define
    my_plugin_register_routes();

    // Then, update the .htaccess with the new rewrite rules set
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, my_plugin_rewrite_flush(...));
```

In your Controller, you have the flexibility to do things in old-style
Wordpress way, or the PSR server request / response way:

```php

use Impressible\ImpressibleRoute\Http\TemplatedResponse;
use Impressible\ImpressibleRoute\Http\NotFoundResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Psr7\Response;

class MyController
{
   ...

   public function handleContentIndex(ServerRequestInterface $request)
   {
      return new TemplatedResponse('content-index.php');
   }

   public function handleMediaEndpoint(ServerRequestInterface $request)
   {
      /**
       * @var \WP_Query
       */
      $query = $request->getAttribute('wp_query');
      if (!$query->have_posts()) {
        return new NotFoundResponse();
      }
      $post = $query->next_post();
      return new Response(
         200,
         ['Content-Type' => $post->mymedia_content_type],
         fopen($post->mymedia_content, 'r')
      );
   }
}

```


### Step 2: Update Wordpress setting to use your routes

The routes might not work without proper configurations.

You routes depends on:
- Wordpress being setup with friendly URL (so that rewrite is enabled); and
- Your latest routing rules is in the rewrite rules cache; and
- For some setup, Wordpress to update .htaccess to notify Apache about the routing arrangements.

The easiest way to ensure this is to:
1. Go to `/wp-admin/options-permalink.php` of your Wordpress installation, and
2. Make sure that *Permalink structure* is **NOT** set to *Plain*, and then
3. Hit *Save Changes*

Wordpress should then be ready to go with your routes.

> ‼️**Important Note**‼️
>
> **EVERYTIME after you changed your routes**, you need to tell Wordpress to clear the rewrite rules cache.
>
> You may either:
> - Go to `/wp-admin/options-permalink.php` of your Wordpress installation and just hit *Save Changes* or
> - Manually call [flush_rewrite_rules](https://developer.wordpress.org/reference/functions/flush_rewrite_rules/). Using [wp cli](https://wp-cli.org/), you may run:
>    ```bash
>    wp eval "flush_rewrite_rules();"
>    ```
>
> The rewrite rules cached will be flushed and .htaccess will be updated.


### (Optional) Step 3: Admin Interface Routing

We also support admin page routing in a similar manner with the "admin_menu" and
"admin_init" hooks.

```php
use Impressible\ImpressibleRoute\Http\AdminRouter;
use Impressible\ImpressibleRoute\Http\AdminRoute;
use Impressible\ImpressibleRoute\LazyLoadObject;

require_once __DIR__ . '/vendor/autoload.php';

function my_plugin_register_admin_routes() {
   /**
    * @var \wpdb $wpdb
    */
   global $wpdb;

   // Lazyload a MyController that, for demo purpose only, somehow need to use wpdb.
   $controller = new LazyLoadObject(fn() => new MyAdminController($wpdb));

   // Create a router instance and register routes with it.
   $router = new AdminRouter()
      ->addRoute(AdminRoute::menu(
        'My Admin Section',
        'My Section',
        'some-capability',
        'menu_slug_1',
        $controller->handleAdminSection(...),
        'icon-1',
        1 // position
      ))
      ->addRoute(AdminRoute::menu(
        'menu_slug_1',
        'My Admin Sub-section',
        'My Subection',
        'some-capability',
        'menu_slug_2',
        $controller->handleAdminSubection(...),
        1 // position
      ));
      // register the router methods to the Wordpress environment.
      ->register();
}
add_action('admin_menu', my_plugin_register_admin_routes(...));
```

In your MyAdminController, you have the flexibility:

```php

use Impressible\ImpressibleRoute\Http\TemplatedResponse;
use Impressible\ImpressibleRoute\Http\NotFoundResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Psr7\Response;

class MyAdminController
{
   ...
    public function handleAdminSection(ServerRequestInterface $request)
    {
        // For ordinary admin page responses.
        return new AdminPageResponse(function () use ($request) {
            // The code here will be delayed to execute the time
            // ordinarly Wordpress admin menu callback is run.
            require 'some/path/some/script.php';
        });
    }

    public function handleAdminSubection(ServerRequestInterface $request)
    {
        // For export or other pages without dashboard top bar and sidebar.
        // This will be executed when admin_init hook is run.
        return new Response(
            200,
            [
                'Content-Type' => 'application/json',
            ],
            json_encode([
                'status' => 'success',
                'msg' => 'Successful API call',
            ])
        );
    }

}

```


## License

This library is licensed under the [MIT License](LICENSE.md).


[packagist]: https://packagist.org/
[psr-http-message]: https://packagist.org/packages/psr/http-message
[psr-container]: https://packagist.org/packages/psr/container
