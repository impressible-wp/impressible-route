<?php declare(strict_types=1);

namespace Impressible\ImpressibleRoute\Http;

/**
 * Receives a callable that will be handled like an ordinary
 * Wordpress admin menu callback.
 */
class AdminPageResponse
{
    /**
     * Callable to run.
     *
     * @param callable $callable
     */
    protected $callable;

    public function __construct(callable $callable)
    {
        $this->callable = $callable;
    }

    /**
     * Proxy call the inner callable
     *
     * @return mixed
     */
    public function call()
    {
        return call_user_func_array($this->callable, func_get_args());
    }
}
