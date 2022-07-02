<?php declare(strict_types=1);

namespace Impressible\ImpressibleRoute\Http;

/**
 * Represents a proper Wordpress template response.
 * For using with the routing logics in this plugin.
 */
class TemplatedResponse
{
    /**
     * Template basename to search for
     *
     * @var string
     */
    private $filename;

    /**
     * Constructor
     *
     * @param string $filename       The filename of the template.
     */
    public function __construct(string $filename)
    {
        $this->filename = $filename;
    }

    /**
     * Get the filename specified.
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * Magic method. Uses the getFilename() method internally.
     *
     * @return string
     * @see getFilename()
     */
    public function __toString()
    {
        return $this->getFilename();
    }
}
