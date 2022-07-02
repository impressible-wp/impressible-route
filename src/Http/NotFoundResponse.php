<?php declare(strict_types=1);

namespace Impressible\ImpressibleRoute\Http;

/**
 * Represents a Wordpress default 404 response.
 * For using with the routing logics in this plugin.
 */
class NotFoundResponse extends QueryTemplateResponse
{
    /**
     * Class constructor
     */
    function __construct()
    {
        parent::__construct('404');
    }

    /**
     * {@inheritDoc}
     */
    function getStatus(): int
    {
        return 404;
    }
}
