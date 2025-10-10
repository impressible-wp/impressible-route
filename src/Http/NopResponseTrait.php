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
     */
    public function getStatus(): int {
        return 200;
    }

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
     */
    public function getReasonPhrase(): string {
        switch ($this->getStatusCode()) {
            case 100: return 'Continue'; break;
            case 101: return 'Switching Protocols'; break;
            case 200: return 'OK'; break;
            case 201: return 'Created'; break;
            case 202: return 'Accepted'; break;
            case 203: return 'Non-Authoritative Information'; break;
            case 204: return 'No Content'; break;
            case 205: return 'Reset Content'; break;
            case 206: return 'Partial Content'; break;
            case 300: return 'Multiple Choices'; break;
            case 301: return 'Moved Permanently'; break;
            case 302: return 'Moved Temporarily'; break;
            case 303: return 'See Other'; break;
            case 304: return 'Not Modified'; break;
            case 305: return 'Use Proxy'; break;
            case 400: return 'Bad Request'; break;
            case 401: return 'Unauthorized'; break;
            case 402: return 'Payment Required'; break;
            case 403: return 'Forbidden'; break;
            case 404: return 'Not Found'; break;
            case 405: return 'Method Not Allowed'; break;
            case 406: return 'Not Acceptable'; break;
            case 407: return 'Proxy Authentication Required'; break;
            case 408: return 'Request Time-out'; break;
            case 409: return 'Conflict'; break;
            case 410: return 'Gone'; break;
            case 411: return 'Length Required'; break;
            case 412: return 'Precondition Failed'; break;
            case 413: return 'Request Entity Too Large'; break;
            case 414: return 'Request-URI Too Large'; break;
            case 415: return 'Unsupported Media Type'; break;
            case 500: return 'Internal Server Error'; break;
            case 501: return 'Not Implemented'; break;
            case 502: return 'Bad Gateway'; break;
            case 503: return 'Service Unavailable'; break;
            case 504: return 'Gateway Time-out'; break;
            case 505: return 'HTTP Version not supported'; break;
            default:
                throw new \ValueError('Unknown HTTP status code ' . $this->getStatusCode());
            break;
        }
    }

    /**
     * {@inheritDoc}
     *
     * @see method throwNoOpException.
     */
    public function getProtocolVersion() {
        return '1.1';
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
