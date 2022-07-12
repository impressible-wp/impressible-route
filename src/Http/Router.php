<?php declare(strict_types=1);

namespace Impressible\ImpressibleRoute\Http;

use GuzzleHttp\Psr7\ServerRequest;
use Psr\Http\Message\ResponseInterface;

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
     * @var array
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
     * Add a route to this router.
     *
     * Route won't be effective until the registerRoutes() method is called.
     *
     * The $regex, $query and $after will be used by WP_Rewrite::add_rule()
     * (a.k.a. the add_rewrite_rule function).
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
        $n = sizeof($this->routes) + 1;
        $this->routes[$routeSlug ?? "route-{$n}"] = [
            $regex,
            $callable,
            $query,
            $after,
        ];
        return $this;
    }

    /**
     * To register everything necessary to the Wordpress ecosystem for the
     * routing to work.
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
            list($regex, $callable, $query, $after) = $route;
            $this->extraQueryVars += array_keys($query); // whitelist the extra query parameter, if needed.
            $query = [$this->queryVarName => $routeSlug] + $query;
            $this->wpRewrite->add_rule($regex, $query, $after);
        }
        return $this;
    }

    /**
     * Add the methods of this router as propert filters to the
     * current wordpress environment.
     *
     * Essential for the query_vars based routing to work.
     *
     * @param callable $callable (Optional) Specify the callable
     *     to add filters with. Default: 'add_filter'.
     *
     * @return self
     */
    public function addFilters($callable = 'add_filter')
    {
        if (!is_callable($callable)) {
            throw new \Exception('unable to find function "add_filter".');
        }

        // Will whitelist the queryVarName for handleRoute to reference.
        $callable('query_vars', [$this, 'keepQueryVar']);

        // Will handle the routing.
        $callable('template_include', [$this, 'handleRoute']);

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
        if (($callable = $this->dispatch($this->getRouteSlug())) === null) {
            return $template;
        }

        // Generate request object from globals.
        $request = ServerRequest::fromGlobals()
            ->withAttribute('wp_query', $this->wpQuery);

        // Use the callback found to handle the request.
        // If it returns a string, assume it is template filename and pass along.
        // If it returns boolean false, assume it has already sent out response body and stop the PHP process.
        if (($template = $this->handleResponse($callable($request))) === false) {
           exit();
        }
        return $template;
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
     * @return callable|null  The callable for the slug, or null if none found.
     */
    public function dispatch($slug): ?callable
    {
        // If slug query var do not exists, simply return null.
        if ($slug === false) {
            return null;
        }
        if (isset($this->routes[$slug])) {
            list($regex, $callable, $query) = $this->routes[$slug];
            return $callable;
        }
        return null;
    }

    /**
     * Handle response from a route callback.
     *
     * @param ResponseInterface|TemplatedResponse|string $response  Response from callback.
     *
     * @return string|false  The template string to use
     */
    public function handleResponse($response)
    {
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

        // Return the templated response.
        if ($response instanceof TemplatedResponse) {
            $wp_template = $response->getTemplate();
            http_response_code($response->getStatusCode());

            // Wordpress default template search behaviour.
            if (!empty($wp_template) && is_file($wp_template)) {
                return $wp_template;
            }

            // If template directory is specified, do extra template search.
            if (!empty($this->templateDir)) {
                return $this->templateDir . DIRECTORY_SEPARATOR . $response->getFilename();
            }
        }

        // For whatever else, return it as a string and exit Wordpress environment.
        echo (string) $response;
        return false;
    }
}
