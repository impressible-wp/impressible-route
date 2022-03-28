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

When writing your plugin, add this hook implementation:

```php
use Impressible\ImpressibleRoute\Http\Router;
use Impressible\ImpressibleRoute\LazyLoadObject;

require __DIR__ . '/vendor/autoload.php';

function my_plugin_register_routes() {
   /**
    * @var \wpdb $wpdb
    */
   global $wpdb;

   // Lazyload a MyController that, for demo purpose only, somehow need to use wpdb.
   $controller = new LazyLoadObject(fn() => new MyController($wpdb));

   // Create a router instance and register routes with it.
   $router = Router::fromEnvironment(
         'my_plugin_route', // query parameter used for routing.
         __DIR__            // folder for Wordpress template.
      )
      ->addRoute('mycontent$', [$controller, 'handleContentIndex']);
      ->addRoute(
         'mycontent/media/(\d+)$',
         [$controller, 'handleMediaEndpoint'],
         ['media_id' => '$matches[1]'],
      )
      ->registerRoutes();

   // Will whitelist the queryVarName for handleRoute to reference.
   add_filter('query_vars', [$router, 'keepQueryVar']);

   // Will handle the routing.
   add_filter('template_include', [$router, 'handleRoute']);
}
add_action('init', 'my_plugin_register_routes');
```

In your Controller, you have the flexibility to do things in old-style
Wordpress way, or the PSR server request / response way:

```php

use Impressible\ImpressibleRoute\Http\TemplatedResponse;
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

   public function handleArticlePage(ServerRequestInterface $request)
   {
      return new Response(
         200,
         ['Content-Type' => 'audio/mpeg']
         fopen('example.mp3', 'r')
      );
   }
}

```


## License

This library is licensed under the [MIT License](LICENSE.md).


[packagist]: https://packagist.org/
[psr-http-message]: https://packagist.org/packages/psr/http-message
[psr-container]: https://packagist.org/packages/psr/container
