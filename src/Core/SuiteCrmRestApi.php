<?php
/**
 * SuiteCRM REST API Implementation
 *
 * Main implementation of SuiteCrmApiInterface using REST API.
 * Follows SOLID principles with single responsibility for API communication.
 *
 * @package SuiteAPI
 * @subpackage Core
 * @author AI Assistant
 * @since 1.0.0
 *
 * @requirement REQ-SUITE-001: SuiteCRM API Integration
 * @requirement REQ-SUITE-002: REST API Communication
 * @requirement REQ-SUITE-003: Authentication Management
 *
 * UML Class Diagram:
 * ```
 * +---------------------+
 * | SuiteCrmRestApi     |
 * +---------------------+
 * | -config: SuiteCrmConfig |
 * | -httpClient: HttpClientInterface |
 * | -sessionId: string|null |
 * | -lastResponse: array|null |
 * +---------------------+
 * | +login()            |
 * | +logout()           |
 * | +createRecord()     |
 * | +updateRecord()     |
 * | +getRecord()        |
 * | +searchRecords()    |
 * | +deleteRecord()     |
 * +---------------------+
 * ```
 */

namespace SuiteAPI\Core;

use SuiteAPI\Interfaces\SuiteCrmApiInterface;
use SuiteAPI\Interfaces\HttpClientInterface;
use SuiteAPI\Exceptions\SuiteApiException;
use SuiteAPI\Exceptions\AuthenticationException;
use SuiteAPI\Exceptions\ConnectionException;
use SuiteAPI\Exceptions\ValidationException;
use SuiteAPI\Exceptions\RecordNotFoundException;

/**
 * SuiteCRM REST API Implementation
 *
 * Handles all communication with SuiteCRM REST API.
 * Uses dependency injection for configuration and HTTP client.
 */
class SuiteCrmRestApi implements SuiteCrmApiInterface
{
    /**
     * SuiteCRM configuration
     *
     * @var SuiteCrmConfig
     */
    private $config;

    /**
     * HTTP client for API communication
     *
     * @var HttpClientInterface
     */
    private $httpClient;

    /**
     * Current session ID
     *
     * @var string|null
     */
    private $sessionId;

    /**
     * Last API response for debugging
     *
     * @var array|null
     */
    private $lastResponse;

    /**
     * Constructor with dependency injection
     *
     * @param SuiteCrmConfig $config SuiteCRM configuration
     * @param HttpClientInterface $httpClient HTTP client implementation
     */
    public function __construct(SuiteCrmConfig $config, HttpClientInterface $httpClient)
    {
        $this->config = $config;
        $this->httpClient = $httpClient;

        // Configure HTTP client
        $this->httpClient->setTimeout($config->getTimeout());
        $this->httpClient->setSslVerify($config->isSslVerify());
        $this->httpClient->setHeaders([
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
    }

    /**
     * Authenticate with SuiteCRM and obtain session
     *
     * @param string $username SuiteCRM username
     * @param string $password SuiteCRM password
     * @return bool True on successful authentication
     * @throws AuthenticationException
     * @throws ConnectionException
     *
     * @requirement REQ-SUITE-003
     */
    public function login(string $username, string $password): bool
    {
        $loginData = [
            'user_auth' => [
                'user_name' => $username,
                'password' => md5($password),
                'version' => '1'
            ],
            'application_name' => 'SuiteAPI',
            'name_value_list' => []
        ];

        try {
            $response = $this->makeRequest('login', $loginData);

            if (!isset($response->id)) {
                throw new AuthenticationException(
                    'Authentication failed: Invalid credentials or response',
                    0,
                    $this->lastResponse
                );
            }

            $this->sessionId = $response->id;
            return true;

        } catch (SuiteApiException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new AuthenticationException(
                'Authentication failed: ' . $e->getMessage(),
                0,
                $this->lastResponse,
                null,
                $e
            );
        }
    }

    /**
     * Logout and destroy session
     *
     * @return bool True on successful logout
     *
     * @requirement REQ-SUITE-003
     */
    public function logout(): bool
    {
        if (!$this->isAuthenticated()) {
            return true; // Already logged out
        }

        try {
            $this->makeRequest('logout', ['session' => $this->sessionId]);
            $this->sessionId = null;
            return true;
        } catch (\Exception $e) {
            // Even if logout fails, clear session locally
            $this->sessionId = null;
            return false;
        }
    }

    /**
     * Create a new record in specified module
     *
     * @param string $module Module name (e.g., 'Contacts', 'Accounts')
     * @param array $data Record data as key-value pairs
     * @return string|null Created record ID or null on failure
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-002
     */
    public function createRecord(string $module, array $data): ?string
    {
        $this->ensureAuthenticated();

        $requestData = [
            'session' => $this->sessionId,
            'module_name' => $module,
            'name_value_list' => $this->formatNameValueList($data)
        ];

        $response = $this->makeRequest('set_entry', $requestData);

        if (!isset($response->id)) {
            throw new SuiteApiException(
                "Failed to create record in module '{$module}'",
                0,
                $this->lastResponse,
                $module
            );
        }

        return $response->id;
    }

    /**
     * Update an existing record
     *
     * @param string $module Module name
     * @param string $id Record ID
     * @param array $data Updated data
     * @return bool True on successful update
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-002
     */
    public function updateRecord(string $module, string $id, array $data): bool
    {
        $this->ensureAuthenticated();

        $nameValueList = $this->formatNameValueList($data);
        $nameValueList[] = ['name' => 'id', 'value' => $id];

        $requestData = [
            'session' => $this->sessionId,
            'module_name' => $module,
            'name_value_list' => $nameValueList
        ];

        $response = $this->makeRequest('set_entry', $requestData);

        return isset($response->id) && $response->id === $id;
    }

    /**
     * Retrieve a single record by ID
     *
     * @param string $module Module name
     * @param string $id Record ID
     * @param array $fields Optional fields to retrieve
     * @return array|null Record data or null if not found
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-002
     */
    public function getRecord(string $module, string $id, array $fields = []): ?array
    {
        $this->ensureAuthenticated();

        $requestData = [
            'session' => $this->sessionId,
            'module_name' => $module,
            'id' => $id,
            'select_fields' => $fields,
            'link_name_to_fields_array' => [],
            'track_view' => false
        ];

        try {
            $response = $this->makeRequest('get_entry', $requestData);

            if (!isset($response->entry_list) || empty($response->entry_list)) {
                return null;
            }

            return $this->parseEntryList($response->entry_list)[0] ?? null;

        } catch (SuiteApiException $e) {
            if (strpos($e->getMessage(), 'not found') !== false) {
                throw new RecordNotFoundException($module, $id, $this->lastResponse);
            }
            throw $e;
        }
    }

    /**
     * Search for records matching criteria
     *
     * @param string $module Module name
     * @param array $query Search criteria
     * @param array $fields Fields to return
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of matching records
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-002
     */
    public function searchRecords(
        string $module,
        array $query = [],
        array $fields = [],
        int $limit = 20,
        int $offset = 0
    ): array {
        $this->ensureAuthenticated();

        $requestData = [
            'session' => $this->sessionId,
            'module_name' => $module,
            'query' => $this->buildSearchQuery($query),
            'order_by' => '',
            'offset' => $offset,
            'select_fields' => $fields,
            'link_name_to_fields_array' => [],
            'max_results' => $limit,
            'deleted' => 0,
            'favorites' => false
        ];

        $response = $this->makeRequest('get_entry_list', $requestData);

        if (!isset($response->entry_list)) {
            return [];
        }

        return $this->parseEntryList($response->entry_list);
    }

    /**
     * Delete a record
     *
     * @param string $module Module name
     * @param string $id Record ID
     * @return bool True on successful deletion
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-002
     */
    public function deleteRecord(string $module, string $id): bool
    {
        $this->ensureAuthenticated();

        $requestData = [
            'session' => $this->sessionId,
            'module_name' => $module,
            'name_value_list' => [
                ['name' => 'id', 'value' => $id],
                ['name' => 'deleted', 'value' => '1']
            ]
        ];

        $response = $this->makeRequest('set_entry', $requestData);

        return isset($response->id);
    }

    /**
     * Check if API is authenticated
     *
     * @return bool True if authenticated
     */
    public function isAuthenticated(): bool
    {
        return !empty($this->sessionId);
    }

    /**
     * Get last API response for debugging
     *
     * @return array|null Last response data
     */
    public function getLastResponse(): ?array
    {
        return $this->lastResponse;
    }

    /**
     * Ensure user is authenticated
     *
     * @throws AuthenticationException
     */
    private function ensureAuthenticated(): void
    {
        if (!$this->isAuthenticated()) {
            throw new AuthenticationException('Not authenticated. Please login first.');
        }
    }

    /**
     * Make API request
     *
     * @param string $method API method
     * @param array $data Request data
     * @return \stdClass API response
     * @throws SuiteApiException
     */
    private function makeRequest(string $method, array $data): \stdClass
    {
        $url = $this->config->getUrl() . '/service/v4_1/rest.php';

        $postData = [
            'method' => $method,
            'input_type' => 'JSON',
            'response_type' => 'JSON',
            'rest_data' => json_encode($data)
        ];

        try {
            $response = $this->httpClient->post($url, $postData);

            $this->lastResponse = $response;

            if ($response['error']) {
                throw new ConnectionException(
                    'HTTP request failed: ' . $response['error'],
                    0,
                    $response
                );
            }

            if ($response['status_code'] !== 200) {
                throw new ConnectionException(
                    'HTTP request failed with status ' . $response['status_code'],
                    $response['status_code'],
                    $response
                );
            }

            $decodedResponse = json_decode($response['body']);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new SuiteApiException(
                    'Invalid JSON response from API',
                    0,
                    $response
                );
            }

            return $decodedResponse;

        } catch (\Exception $e) {
            if ($e instanceof SuiteApiException) {
                throw $e;
            }
            throw new ConnectionException(
                'Request failed: ' . $e->getMessage(),
                0,
                $this->lastResponse,
                null,
                $e
            );
        }
    }

    /**
     * Format data as SuiteCRM name_value_list
     *
     * @param array $data Key-value data
     * @return array Formatted name-value list
     */
    private function formatNameValueList(array $data): array
    {
        $nameValueList = [];
        foreach ($data as $name => $value) {
            $nameValueList[] = [
                'name' => $name,
                'value' => $value
            ];
        }
        return $nameValueList;
    }

    /**
     * Parse SuiteCRM entry_list response
     *
     * @param array $entryList Raw entry list from API
     * @return array Parsed records
     */
    private function parseEntryList(array $entryList): array
    {
        $records = [];
        foreach ($entryList as $entry) {
            if (isset($entry->name_value_list)) {
                $record = [];
                foreach ($entry->name_value_list as $field) {
                    $record[$field->name] = $field->value;
                }
                $records[] = $record;
            }
        }
        return $records;
    }

    /**
     * Build search query string
     *
     * @param array $query Search criteria
     * @return string Query string
     */
    private function buildSearchQuery(array $query): string
    {
        if (empty($query)) {
            return '';
        }

        $conditions = [];
        foreach ($query as $field => $value) {
            $conditions[] = "{$field} = '{$value}'";
        }

        return implode(' AND ', $conditions);
    }
}