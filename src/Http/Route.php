<?php declare(strict_types=1);

namespace Impressible\ImpressibleRoute\Http;

/**
 * Representation of a route.
 *
 * @see https://developer.wordpress.org/reference/functions/add_rewrite_rule/
 * @see https://developer.wordpress.org/reference/classes/wp_rewrite/
 * @see https://www.php.net/manual/en/language.types.callable.php
 */
class Route
{
    /**
     * Regular expression to match request against.
     *
     * @var string
     */
    private $regex;

    /**
     * The callable for the route.
     *
     * @var callable
     */
    private $callable;

    /**
     * The corrisponding query vars for this rewrite rule.
     *
     * @var array
     */
    private $query = [];

    /**
     * The route slug to use for the route.
     * If null, will be decided by the router.
     *
     * @var null|string
     */
    private $routeSlug = null;

    /**
     * Optional. Priority of the new rule.
     * Accepts 'top' or 'bottom'. Default 'bottom'.
     *
     * @var string
     */
    private $after = 'top';

    /**
     * The callable for the pre_get_posts hook.
     *
     * @see https://developer.wordpress.org/reference/hooks/pre_get_posts/
     *
     * @var callable|null
     */
    private $preGetPostCallable = null;

    /**
     * Class constc
     *
     * @param string      $regex     Regular expression to match request against.
     * @param callable    $callable  The callable for the route.
     * @param array       $query     The corrisponding query vars for this rewrite rule.
     * @param string|null $routeSlug Optional route slug to use for the route.
     *                               If null, will be decided by the router.
     * @param string      $after     Optional. Priority of the new rule.
     *                               Accepts 'top' or 'bottom'. Default 'bottom'.
     *
     * @see \WP_Rewrite::add_rule()
     */
    public function __construct(
        string $regex,
        callable $callable,
        array $query = [],
        ?string $routeSlug = null,
        string $after = 'top'
    )
    {
        $this->regex = $regex;
        $this->callable = $callable;
        $this->query = $query;
        $this->routeSlug = $routeSlug;
        $this->after = $after;
    }

    /**
     * Get parameters of a rewrite rule.
     *
     * @return array An array of \WP_Rewrite::add_rule arguments:
     *  - string $regex  Regular expression to match request against.
     *  - array  $query  The corresponding query vars for this rewrite rule.
     *  - string $after  Optional. Priority of the new rule. Accepts 'top' or 'bottom'. Default 'bottom'.
     */
    public function getRewriteRuleParams(): array
    {
        return [
            $this->regex,
            $this->query,
            $this->after,
        ];
    }

    /**
     * Get the callable for the route.
     *
     * @return  callable
     */
    public function getCallable(): callable
    {
        return $this->callable;
    }

    /**
     * Get if null, will be decided by the router.
     *
     * @return  null|string
     */
    public function getRouteSlug(): ?string
    {
        return $this->routeSlug;
    }

    /**
     * Set the routeSlug string for the routing.
     *
     * @param string
     *
     * @return self
     */
    public function setRouteSlug(string $routeSlug)
    {
        $this->routeSlug = $routeSlug;
        return $this;
    }

    /**
     * Get the corrisponding query vars for this rewrite rule.
     *
     * @return  array
     */
    public function getQuery(): array
    {
        return $this->query;
    }

    /**
     * Set the corrisponding query vars for this rewrite rule.
     *
     * @param  array  $query  The corrisponding query vars for this rewrite rule.
     *
     * @return  self
     */
    public function setQuery(array $query)
    {
        $this->query = $query;
        return $this;
    }

    /**
     * Create a new instance of Route with the query
     * parameter $key set to $value. Overwrites existing
     * query parameters.
     *
     * @param string $key
     * @param mixed $value
     *
     * @return Route
     */
    public function withQueryParam(string $key, $value)
    {
        $instance = new static(
            $this->regex,
            $this->callable,
            [$key => $value] + $this->query,
            $this->routeSlug,
            $this->after
        );
        return $instance;
    }

    /**
     * Retrieve the pre_get_posts hook function for
     * the route, if any.
     *
     * @return callable|null
     */
    public function getPreGetPost(): ?callable
    {
        return $this->preGetPostCallable;
    }

    /**
     * With a pre_get_posts hook callable.
     *
     * Triggers only when the route matches current request.
     *
     * @param callable $callable
     *
     * @return Route
     */
    public function withPreGetPost(callable $callable)
    {
        $instance = new static(
            $this->regex,
            $this->callable,
            $this->query,
            $this->routeSlug,
            $this->after
        );
        $instance->preGetPostCallable = $callable;
        return $instance;
    }
}
