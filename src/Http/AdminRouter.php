<?php declare(strict_types=1);

namespace Impressible\ImpressibleRoute\Http;

use Psr\Http\Message\ResponseInterface;

class AdminRouter
{
    /**
     * An assoc array of all routes registered
     * by their menu slug.
     *
     * @var AdminRoute[]
     */
    private $routes = [];

    /**
     * A stored admin response or null.
     *
     * Storing admin response, if any, returned by
     * callable in handleRoute stage (admin_init hook).
     *
     * @var ?AdminResponse
     */
    private $adminResponse = null;

    /**
     * Add an admin route to the router.
     *
     * @param AdminRoute $route
     *
     * @return self
     */
    public function addRoute(
        AdminRoute $route
    )
    {
        $this->routes[$route->getMenuSlug()] = $route;
        return $this;
    }

    /**
     * Handle regular admin route callback.
     *
     * @return void
     */
    public function handleRoute(string $slug = '')
    {
        $slug = $slug ?: $this->getRouteSlug();
        $route = $this->routes[$slug] ?? false;
        if ($route === false) {
            // Do nothing.
            return;
        }

        $callable = $route->getCallback();
        if ($callable === null) {
            // Do nothing.
            return;
        }

        $response = $callable();
        if ($response instanceof AdminPageResponse) {
            $this->adminResponse = $response;
            return;
        }

        // If this is a PSR response, emit the response.
        if ($response instanceof ResponseInterface) {
            $http_line = sprintf('HTTP/%s %s %s',
                $response->getProtocolVersion(),
                $response->getStatusCode(),
                $response->getReasonPhrase()
            );
            header($http_line, true, $response->getStatusCode());
            foreach ($response->getHeaders() as $name => $values) {
                foreach ($values as $value) {
                    header("$name: $value", false);
                }
            }
            $stream = $response->getBody();
            if ($stream->isSeekable()) {
                $stream->rewind();
            }
            while (!$stream->eof()) {
                echo $stream->read(1024 * 8);
            }
            return false;
        }

        // For whatever else, return it as a string and exit Wordpress environment.
        echo (string) $response;
        return false;
    }

    /**
     * Get the current route slug.
     *
     * @return string
     */
    private function getRouteSlug(): string
    {
        return $_GET['page'];
    }

    /**
     * Triggers the stored AdminResponse, if any.
     *
     * @return void
     */
    public function triggerAdminResponse()
    {
        if ($this->adminResponse !== null)
        {
            // Call the stored callable
            $this->adminResponse->call();
        }
    }

    /**
     * Use add_menu_page and add_submenu_page to register
     * all routes.
     *
     * @param callable $add_menu_page  The callable to add menu pages to Wordpress.
     * @param callable $add_submenu_page  The callable to add submenu pages to Wordpress.
     *
     * @return self
     */
    public function registerMenuPages(
        $add_menu_page = 'add_menu_page',
        $add_submenu_page = 'add_submenu_page'
    )
    {
        foreach ($this->routes as $route) {
            if ($route->isMenu()) {
                $add_menu_page(
                    $route->getPageTitle(),
                    $route->getMenuTitle(),
                    $route->getCapability(),
                    $route->getMenuSlug(),
                    [$this, 'triggerAdminResponse'], // use router's own handle
                    $route->getIconUrl(),
                    $route->getPosition()
                );
            } elseif ($route->isSubMenu()) {
                $add_submenu_page(
                    $route->getParentSlug(),
                    $route->getPageTitle(),
                    $route->getMenuTitle(),
                    $route->getCapability(),
                    $route->getMenuSlug(),
                    [$this, 'triggerAdminResponse'], // use router's own handle
                    $route->getPosition()
                );
            }
        }
        return $this;
    }

    /**
     * Use add_action to register Wordpress action hook needed
     * for the routing to work.
     *
     * @param string $add_action
     * @return void
     */
    public function registerActions($add_action = 'add_action')
    {
        // Use admin_init to run custom routing.
        $add_action('admin_init', [$this, 'handleRoute']);
    }

    /**
     * Register hooks for actual routing to happen.
     *
     * @param callable|string $add_action  Callable to add actions to Wordpress
     *                                     hook system.
     *
     * @return void
     */
    public function register()
    {
        // Register all admin routes.
        $this->registerMenuPages();

        // Use admin_init to run custom routing.
        $this->registerActions();
    }
}
