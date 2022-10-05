<?php declare(strict_types=1);

namespace Impressible\ImpressibleRoute\Http;

use Psr\Http\Message\StreamInterface;

/**
 * A Nop implementation of methods set to implement
 * PSR Response. Helps classes that are not supposed
 * to be used as a PSR response to pretend to be it.
 */
trait NopResponseTrait
{

    /**
     * {@inheritDoc}
     *
     * @see method throwNoOpException.
     */
    public function withStatus($code, $reasonPhrase = '') {
        $this->throwNoOpException();
    }

    /**
     * {@inheritDoc}
     *
     * @see method throwNoOpException.
     */
    public function getReasonPhrase() {
        $this->throwNoOpException();
    }

    /**
     * {@inheritDoc}
     *
     * @see method throwNoOpException.
     */
    public function getProtocolVersion() {
        $this->throwNoOpException();
    }

    /**
     * {@inheritDoc}
     *
     * @see method throwNoOpException.
     */
    public function withProtocolVersion($version) {
        $this->throwNoOpException();
    }

    /**
     * {@inheritDoc}
     *
     * @see method throwNoOpException.
     */
    public function getHeaders() {
        $this->throwNoOpException();
    }

    /**
     * {@inheritDoc}
     *
     * @see method throwNoOpException.
     */
    public function hasHeader($name) {
        $this->throwNoOpException();
    }

    /**
     * {@inheritDoc}
     *
     * @see method throwNoOpException.
     */
    public function getHeader($name) {
        $this->throwNoOpException();
    }

    /**
     * {@inheritDoc}
     *
     * @see method throwNoOpException.
     */
    public function getHeaderLine($name) {
        $this->throwNoOpException();
    }

    /**
     * {@inheritDoc}
     *
     * @see method throwNoOpException.
     */
    public function withHeader($name, $value) {
        $this->throwNoOpException();
    }

    /**
     * {@inheritDoc}
     *
     * @see method throwNoOpException.
     */
    public function withAddedHeader($name, $value) {
        $this->throwNoOpException();
    }

    /**
     * {@inheritDoc}
     *
     * @see method throwNoOpException.
     */
    public function withoutHeader($name) {
        $this->throwNoOpException();
    }

    /**
     * {@inheritDoc}
     *
     * @see method throwNoOpException.
     */
    public function getBody() {
        $this->throwNoOpException();
    }

    /**
     * {@inheritDoc}
     *
     * @see method throwNoOpException.
     */
    public function withBody(StreamInterface $body) {
        $this->throwNoOpException();
    }

    /**
     * Always throw exception. Warn user they should not use
     * NopResponseTrait method directly.
     *
     * @throws \Exception
     *
     * @return void
     */
    protected function throwNoOpException()
    {
        throw new \Exception('Unexpectedly using NopResponseTrait methods.');
    }
}
