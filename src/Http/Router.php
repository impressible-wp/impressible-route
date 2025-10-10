<?php declare(strict_types=1);

namespace Impressible\ImpressibleRoute\Http;

use GuzzleHttp\Psr7\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;

/**
 * This is a router-ish routing handler that rides on
 * Wordpress own hook and filter system, particularly rewrite_rules.
 *
 * The regex-based routing depends entirely on WP_Rewrite.
 * The routing decision are passed along with WP_Query.
 *
 * @see https://developer.wordpress.org/reference/functions/add_rewrite_rule/
 * @see https://developer.wordpress.org/reference/classes/wp_rewrite/
 * @see https://developer.wordpress.org/reference/functions/get_query_var/
 * @see https://developer.wordpress.org/reference/classes/wp_query/
 */
class Router
{
    /**
     * The query variable name to use for routing.
     *
     * @var string
     */
    private $queryVarName;

    /**
     * An array of query variables to be whitelisted.
     *
     * @var string[]
     */
    private $extraQueryVars = [];

    /**
     * The array of routes to remember.
     *
     * @var Route[]
     */
    private $routes = [];

    /**
     * The Wordpress's WP_Rewrite instance.
     *
     * @var \WP_Rewrite
     */
    private $wpRewrite;

    /**
     * The Wordpress's WP_Query instance.
     *
     * @var \WP_Query
     */
    private $wpQuery;

    /**
     * The middleware stack to apply to the HTTP kernel.
     *
     * @var MiddlewareInterface[]
     */
    private $middlewares = [];

    /**
     * The plugin's template directory.
     *
     * @var string|null
     */
    private $templateDir;

    /**
     * Constructor.
     *
     * @param \WP_Rewrite $wp_rewrite              The Wordpress WP_Rewrite object.
     * @param \WP_Query   $wp_query                The Wordpress WP_Query object.
     * @param string      $queryVarName            The name of query variable to use for routing.
     *                                             Just make sure this is not a query variable name
     *                                             you'd use elsewhere in your Wordpress.
     * @param string     $templateDir              The directory to find plugin templates.
     */
    public function __construct(
        $wp_rewrite,
        $wp_query,
        string $queryVarName,
        ?string $templateDir = null
    )
    {
        $this->wpRewrite = $wp_rewrite;
        $this->wpQuery = $wp_query;
        $this->queryVarName = $queryVarName;
        $this->templateDir = is_null($templateDir)
            ? null
            : rtrim($templateDir, DIRECTORY_SEPARATOR);
    }

    /**
     * Create a router with the $wp_rewrite and $wp_query in
     * the environment.
     *
     * @param string $queryVarName
     *     The variable name for passing on the route slug.
     *     Be careful not to use any variable already used in the
     *     wordpress installation.
     * @param string|null $templateDir (Optional)
     *     The directory for template suggestion. If a
     *     TemplatedResponse specifies a template type that
     *     does not exists in the theme (child theme and parent theme)
     *     folder, then router will attempt to load template here.
     *     If set to null, then no extra template suggestion is done.
     *     Default: null
     *
     * @return Router
     */
    public static function fromEnvironment(
        string $queryVarName,
        ?string $templateDir = null
    ): Router
    {
        global $wp_rewrite, $wp_query;
        return new static(
            $wp_rewrite,
            $wp_query,
            $queryVarName,
            $templateDir
        );
    }

    /**
     * Add a route to the router.
     *
     * @param Route $route
     *
     * @return self
     */
    public function add(Route $route)
    {
        if (empty($route->getRouteSlug())) {
            $n = sizeof($this->routes) + 1;
            $route->setRouteSlug("route-{$n}");
        }
        $this->routes[$route->getRouteSlug()] = $route;
        return $this;
    }

    /**
     * Add a route to this router.
     *
     * Route won't be effective until the registerRoutes() method is called.
     *
     * The $regex, $query and $after will be used by WP_Rewrite::add_rule()
     * (a.k.a. the add_rewrite_rule function).
     *
     * @deprecated v2.0  Use Router::add() instead.
     *
     * @param string      $regex      Regular express matches request against.
     *                                The string will be prefixed with '#^/*' and
     *                                suffixed with '#'.
     *                                See add_rewrite_rule() for details.
     * @param callable    $callable   The callable to handle the route.
     *                                Can be a function name, an anonymous function or etc.
     *                                See Callable in PHP documentation.
     * @param array       $query      (Optional) The corresponding query vars for this rewrite rule.
     *                                See add_rewrite_rule() for details.
     * @param string|null $routeSlug  (Optional) The value for the routing $queryVarName.
     *                                If not specified, will use "route-{n}" where "n" is
     *                                the order where the route was added.
     *                                Slug should be unique. Later added route of same
     *                                slug will overwrite previous ones.
     * @param string      $after      (Optional) Priority of the new rule. Accepts 'top' or 'bottom'.
     *                                See add_rewrite_rule() for details.
     *                                Default value: 'top'
     *
     * @see https://developer.wordpress.org/reference/functions/add_rewrite_rule/
     * @see https://developer.wordpress.org/reference/classes/wp_rewrite/
     * @see https://www.php.net/manual/en/language.types.callable.php
     * @see registerRoutes()
     *
     * @return self
     */
    public function addRoute(
        string $regex,
        callable $callable,
        array $query = [],
        ?string $routeSlug = null,
        string $after = 'top'
    ) {
        return $this->add(new Route(
            $regex,
            $callable,
            $query,
            $routeSlug,
            $after
        ));
    }

    /**
     * To register everything necessary to the Wordpress ecosystem for the
     * routing to work.
     *
     * @return self
     */
    public function register()
    {
        return $this
            ->registerRoutes()
            ->addFilters()
            ->addActions();
    }

    /**
     * To register rewrite rules to the Wordpress WP_Rewrite object.
     *
     * Please note that whenever routing is updated, you'll need to "Save" again
     * in the "Options" > "Permlink" to make changes effective. Or the Wordpress
     * will stick to the old cached routings.
     *
     * @return self
     */
    public function registerRoutes()
    {
        foreach ($this->routes as $routeSlug => $route) {
            // register routes
            $this->extraQueryVars += array_keys($route->getQuery()); // whitelist the extra query parameter, if needed.
            $route = $route->withQueryParam($this->queryVarName, $routeSlug);
            $this->wpRewrite->add_rule(
                ...$route
                    ->withQueryParam($this->queryVarName, $routeSlug)
                    ->getRewriteRuleParams()
            );
        }
        return $this;
    }

    /**
     * Add the methods of this router as propert filters to the
     * current wordpress environment.
     *
     * Essential for the query_vars based routing to work.
     *
     * Add keepQueryVar method to 'query_vars' filter. And add
     * handleRoute method to 'template_include' filter.
     * 
     * @see https://developer.wordpress.org/reference/hooks/query_vars/
     * @see https://developer.wordpress.org/reference/hooks/template_include/
     *
     * @param callable $callable (Optional) Specify the callable
     *     to add filters with. Default: 'add_filter'.
     *
     * @return self
     */
    public function addFilters($callable = 'add_filter')
    {
        if (!is_callable($callable)) {
            throw new \Exception(is_string($callable)
                ? "unable to find function \"{$callable}\"."
                : 'unable to use $callable as callable.');
        }

        // Will whitelist the queryVarName for handleRoute to reference.
        $callable('query_vars', [$this, 'keepQueryVar']);

        // Will handle the routing.
        $callable('template_include', [$this, 'handleRoute']);

        return $this;
    }

    /**
     * Add the methods of this router as proper action hooks
     * to the current wordpress environment.
     *
     * Essential for pre_get_posts query rewrite.
     *
     * @param callable $callable Optional callable to add
     *     hooks with. Default: 'add_action'.
     *
     * @return self
     */
    public function addActions($callable = 'add_action')
    {
        if (!is_callable($callable)) {
            throw new \Exception(is_string($callable)
                ? "unable to find function \"{$callable}\"."
                : 'unable to use $callable as callable.');
        }

        $callable('pre_get_posts', [$this, 'handlePreGetPosts']);
        return $this;
    }

    /**
     * Add a middleware to the middleware stack for
     * the HTTP kernel.
     *
     * @param MiddlewareInterface $middleware
     *
     * @return $this
     */
    public function useMiddleware(MiddlewareInterface $middleware)
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    /**
     * Returns an array of variable to be whitelisted.
     * An implementation of Wordpress's query_vars filter.
     *
     * This is necessary for the queryVarName to be kept for handleRoute
     * to work with.
     *
     * @param string[] $vars The array of allowed query variable names.
     *
     * @return string[] The array of allowed query variable names, extended.
     *
     * @see https://developer.wordpress.org/reference/hooks/query_vars/
     */
    public function keepQueryVar(array $vars): array
    {
        $vars[] = $this->queryVarName; // whitelist the query param
        return array_merge($vars, $this->extraQueryVars);
    }

    /**
     * Either print out response to php://output itself, or return
     * the full path to the template file.
     *
     * An implementation of Wordpress's template_include filter.
     * Depends on registerRoute and keepQueryVar above to be
     * correctly added to appropriate Wordpress hooks.
     *
     * Assumes all callable added by addRoute to behave like this
     * function signature:
     *
     * ```php
     * function (\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface|TemplatedResponse;
     * ```
     *
     * If this is not your case, please override this function.
     *
     * @param string $template
     *
     * @see https://developer.wordpress.org/reference/hooks/template_include/
     */
    public function handleRoute(string $template)
    {
        // If no route callback is found,
        // simply return the default $template.
        if (($route = $this->dispatch($this->getRouteSlug())) === null) {
            return $template;
        }

        // Generate request object from globals.
        $request = ServerRequest::fromGlobals()
            ->withAttribute('wp_query', $this->wpQuery);

        // Initialize a PSR-15 compatible kernel with the given route.
        $handler = new RouteRequestHandler($route);
        foreach ($this->middlewares as $middleware) {
            $handler = $middleware->process($request, $handler);
        }

        // Use the callback found to handle the request.
        // If it returns a string, assume it is template filename and pass along.
        // If it returns boolean false, assume it has already sent out response body and stop the PHP process.
        if (($template = $this->handleResponse($handler->handle($request))) === false) {
           exit();
        }
        return $template;
    }

    /**
     * Implements pre_get_posts hook of Wordpress.
     *
     * Triggers side-effect to \WP_Query before getting post with it.
     *
     * @param \WP_Query $query
     *
     * @return void
     */
    public function handlePreGetPosts(\WP_Query $query)
    {
        // If no route callback is found,
        // simply return the default $template.
        if (($route = $this->dispatch($this->getRouteSlug())) === null) {
            return;
        }

        // If there is no pre_get_posts callable on the route, do nothing.
        if (($callable = $route->getPreGetPost()) === null) {
            return;
        }

        // Run the hook callable.
        $callable($query);
    }

    /**
     * Get the route slug for the current WP query.
     *
     * @return string|false  String of the found slug, or false if not found.
     */
    public function getRouteSlug()
    {
        return $this->wpQuery->get($this->queryVarName, false);
    }

    /**
     * Search the route with the slug specified. Then return the corrisponding
     * callable.
     *
     * @param string|false  $slug  The current route slug to dispatch
     *
     * @return Route|null  The callable for the slug, or null if none found.
     */
    public function dispatch($slug): ?Route
    {
        // If slug query var do not exists, simply return null.
        if ($slug === false) {
            return null;
        }
        if (isset($this->routes[$slug])) {
            return $this->routes[$slug];
        }
        return null;
    }

    /**
     * Implementation of Wordpress's 'template_include' filter.
     *
     * If the response is a TemplatedResponse, will attempt to do normal Wordpress template
     * suggestion logic. If template is not found in the theme (child theme and parent theme),
     * and if $templateDir is specified, will attempt to load template from there as fallback.
     *
     * So if your plugin has a template directory, you can put your templates there as the
     * default template, then let users override them by putting templates of same name in
     * their theme folder.
     * 
     * If the response is a PSR ResponseInterface, will emit the response to php://output and
     * tell Wordpress to stop processing by returning false.
     *
     * @see https://developer.wordpress.org/reference/hooks/template_include/
     *
     * @param ResponseInterface|TemplatedResponse|string $response  Response from callback.
     *
     * @return string|false  The template string to use, or false if response
     *                       has been sent to php://output already.
     */
    public function handleResponse($response)
    {
        // If this is a TemplatedResponse, that means the user attempt to use Wordpress
        // template logic to resolve the template file.
        if ($response instanceof TemplatedResponse) {
            // Find Wordpress suggested template file.
            $wp_template = static::suggestTemplateFilename($response);
            http_response_code($response->getStatusCode());

            // If the template file suggested from Wordpress is a proper file,
            // tell Wordpress to use it.
            if (!empty($wp_template) && is_file($wp_template)) {
                return $wp_template;
            }

            // Runs here only of no template is found in the theme folder.
            // In this case, if template directory is specified, do extra template search to
            // find the supposed fallback / default template.
            if (!empty($this->templateDir)) {
                return $this->templateDir . DIRECTORY_SEPARATOR . $response->getFilename();
            }
        }

        // If this is a PSR response, emit the response directly.
        // And tell Wordpress to stop processing by returning false.
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
     * Suggest full path to template file for a given TemplatedResponse.
     * 
     * Uses get_query_template() from Wordpress core to find the specified template file
     * from current theme (child theme and parent theme).
     * 
     * Returns null if get_query_template() is not a defined function.
     *
     * @return string|null
     */
    private static function suggestTemplateFilename(TemplatedResponse $response): ?string
    {
        return \function_exists('get_query_template')
            ? \get_query_template($response->getType(), $response->getTemplates())
            : null;
    }

}
