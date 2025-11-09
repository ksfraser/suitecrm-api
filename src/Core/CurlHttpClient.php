<?php
/**
 * cURL HTTP Client Implementation
 *
 * Concrete implementation of HttpClientInterface using PHP cURL.
 * Follows the Single Responsibility Principle by handling only HTTP communication.
 *
 * @package SuiteAPI
 * @subpackage Core
 * @author AI Assistant
 * @since 1.0.0
 *
 * @requirement REQ-SUITE-007: HTTP Client Abstraction
 * @requirement REQ-SUITE-002: REST API Communication
 *
 * UML Class Diagram:
 * ```
 * +---------------------+
 * | CurlHttpClient      |
 * +---------------------+
 * | -timeout: int       |
 * | -headers: array     |
 * | -sslVerify: bool    |
 * +---------------------+
 * | +post()             |
 * | +get()              |
 * | +setTimeout()       |
 * | +setHeaders()       |
 * | +setSslVerify()     |
 * +---------------------+
 * ```
 */

namespace Ksfraser\SuiteAPI\Core;

use Ksfraser\SuiteAPI\Interfaces\HttpClientInterface;

/**
 * cURL HTTP Client Implementation
 *
 * Handles HTTP communication using PHP's cURL extension.
 * Provides a clean interface for making HTTP requests to SuiteCRM API.
 */
class CurlHttpClient implements HttpClientInterface
{
    /**
     * Request timeout in seconds
     *
     * @var int
     */
    private $timeout = 30;

    /**
     * Default headers
     *
     * @var array
     */
    private $headers = [];

    /**
     * SSL verification flag
     *
     * @var bool
     */
    private $sslVerify = true;

    /**
     * Send POST request
     *
     * @param string $url Request URL
     * @param array $data Request data
     * @param array $headers Additional headers
     * @return array Response data with 'body', 'headers', 'status_code', 'error'
     *
     * @throws \RuntimeException If cURL is not available
     */
    public function post(string $url, array $data = [], array $headers = []): array
    {
        if (!function_exists('curl_init')) {
            throw new \RuntimeException('cURL extension is required but not available');
        }

        $ch = curl_init();

        // Set URL
        curl_setopt($ch, CURLOPT_URL, $url);

        // Set POST method
        curl_setopt($ch, CURLOPT_POST, true);

        // Set POST data
        if (!empty($data)) {
            $jsonData = json_encode($data);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        }

        // Set headers
        $allHeaders = array_merge($this->headers, $headers);
        if (!empty($allHeaders)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $allHeaders);
        }

        // Common cURL options
        $response = $this->executeRequest($ch);

        curl_close($ch);

        return $response;
    }

    /**
     * Send GET request
     *
     * @param string $url Request URL
     * @param array $params Query parameters
     * @param array $headers Additional headers
     * @return array Response data
     *
     * @throws \RuntimeException If cURL is not available
     */
    public function get(string $url, array $params = [], array $headers = []): array
    {
        if (!function_exists('curl_init')) {
            throw new \RuntimeException('cURL extension is required but not available');
        }

        // Add query parameters to URL
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $ch = curl_init();

        // Set URL
        curl_setopt($ch, CURLOPT_URL, $url);

        // Set headers
        $allHeaders = array_merge($this->headers, $headers);
        if (!empty($allHeaders)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $allHeaders);
        }

        // Common cURL options
        $response = $this->executeRequest($ch);

        curl_close($ch);

        return $response;
    }

    /**
     * Execute cURL request with common options
     *
     * @param \CurlHandle|resource $ch cURL handle
     * @return array Response data
     */
    private function executeRequest($ch): array
    {
        // Return response as string instead of outputting it
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Include headers in response
        curl_setopt($ch, CURLOPT_HEADER, true);

        // Set timeout
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);

        // SSL verification
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->sslVerify);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $this->sslVerify ? 2 : 0);

        // Follow redirects
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);

        // Execute request
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // Parse response
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

        // Parse headers
        $parsedHeaders = [];
        foreach (explode("\r\n", $headers) as $header) {
            if (strpos($header, ': ') !== false) {
                list($key, $value) = explode(': ', $header, 2);
                $parsedHeaders[$key] = $value;
            }
        }

        return [
            'body' => $body,
            'headers' => $parsedHeaders,
            'status_code' => $statusCode,
            'error' => $error ?: null
        ];
    }

    /**
     * Set request timeout
     *
     * @param int $seconds Timeout in seconds
     * @return void
     */
    public function setTimeout(int $seconds): void
    {
        $this->timeout = $seconds;
    }

    /**
     * Set default headers
     *
     * @param array $headers Default headers
     * @return void
     */
    public function setHeaders(array $headers): void
    {
        $this->headers = $headers;
    }

    /**
     * Enable/disable SSL verification
     *
     * @param bool $verify SSL verification flag
     * @return void
     */
    public function setSslVerify(bool $verify): void
    {
        $this->sslVerify = $verify;
    }
}