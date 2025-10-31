<?php
/**
 * HTTP Client Interface
 *
 * Defines the contract for HTTP client implementations.
 * Follows the Dependency Inversion Principle by depending on abstraction.
 *
 * @package SuiteAPI
 * @subpackage Interfaces
 * @author AI Assistant
 * @since 1.0.0
 *
 * @requirement REQ-SUITE-002: REST API Communication
 * @requirement REQ-SUITE-007: HTTP Client Abstraction
 *
 * UML Class Diagram:
 * ```
 * +---------------------+
 * | <<interface>>      |
 * | HttpClientInterface |
 * +---------------------+
 * | +post()             |
 * | +get()              |
 * | +setTimeout()       |
 * | +setHeaders()       |
 * +---------------------+
 *           ^
 *           |
 * +---------------------+
 * | CurlHttpClient      |
 * | GuzzleHttpClient    |
 * +---------------------+
 * ```
 */

namespace SuiteAPI\Interfaces;

/**
 * HTTP Client Interface
 *
 * Defines the contract for HTTP client implementations.
 * This abstraction allows different HTTP client libraries to be used.
 */
interface HttpClientInterface
{
    /**
     * Send POST request
     *
     * @param string $url Request URL
     * @param array $data Request data
     * @param array $headers Additional headers
     * @return array Response data with 'body', 'headers', 'status_code', 'error'
     */
    public function post(string $url, array $data = [], array $headers = []): array;

    /**
     * Send GET request
     *
     * @param string $url Request URL
     * @param array $params Query parameters
     * @param array $headers Additional headers
     * @return array Response data
     */
    public function get(string $url, array $params = [], array $headers = []): array;

    /**
     * Set request timeout
     *
     * @param int $seconds Timeout in seconds
     * @return void
     */
    public function setTimeout(int $seconds): void;

    /**
     * Set default headers
     *
     * @param array $headers Default headers
     * @return void
     */
    public function setHeaders(array $headers): void;

    /**
     * Enable/disable SSL verification
     *
     * @param bool $verify SSL verification flag
     * @return void
     */
    public function setSslVerify(bool $verify): void;
}