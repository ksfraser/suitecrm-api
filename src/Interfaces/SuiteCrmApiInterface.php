<?php
/**
 * SuiteCRM API Interface
 *
 * This interface defines the contract for SuiteCRM API implementations.
 * It follows the Interface Segregation Principle by providing focused,
 * specific methods for SuiteCRM operations.
 *
 * @package SuiteAPI
 * @subpackage Interfaces
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
 * | <<interface>>      |
 * | SuiteCrmApiInterface|
 * +---------------------+
 * | +login()            |
 * | +logout()           |
 * | +createRecord()     |
 * | +updateRecord()     |
 * | +getRecord()        |
 * | +searchRecords()    |
 * | +deleteRecord()     |
 * +---------------------+
 *           ^
 *           |
 * +---------------------+
 * | SuiteCrmRestApi     |
 * +---------------------+
 * ```
 */

namespace SuiteAPI\Interfaces;

/**
 * SuiteCRM API Interface
 *
 * Defines the core operations that any SuiteCRM API implementation must provide.
 * This follows the Interface Segregation Principle by keeping the interface focused
 * and specific to SuiteCRM operations.
 */
interface SuiteCrmApiInterface
{
    /**
     * Authenticate with SuiteCRM and obtain session
     *
     * @param string $username SuiteCRM username
     * @param string $password SuiteCRM password
     * @return bool True on successful authentication
     * @throws SuiteAPI\Exceptions\AuthenticationException
     *
     * @requirement REQ-SUITE-003
     */
    public function login(string $username, string $password): bool;

    /**
     * Logout and destroy session
     *
     * @return bool True on successful logout
     *
     * @requirement REQ-SUITE-003
     */
    public function logout(): bool;

    /**
     * Create a new record in specified module
     *
     * @param string $module Module name (e.g., 'Contacts', 'Accounts')
     * @param array $data Record data as key-value pairs
     * @return string|null Created record ID or null on failure
     * @throws SuiteAPI\Exceptions\ApiException
     *
     * @requirement REQ-SUITE-002
     */
    public function createRecord(string $module, array $data): ?string;

    /**
     * Update an existing record
     *
     * @param string $module Module name
     * @param string $id Record ID
     * @param array $data Updated data
     * @return bool True on successful update
     * @throws SuiteAPI\Exceptions\ApiException
     *
     * @requirement REQ-SUITE-002
     */
    public function updateRecord(string $module, string $id, array $data): bool;

    /**
     * Retrieve a single record by ID
     *
     * @param string $module Module name
     * @param string $id Record ID
     * @param array $fields Optional fields to retrieve
     * @return array|null Record data or null if not found
     * @throws SuiteAPI\Exceptions\ApiException
     *
     * @requirement REQ-SUITE-002
     */
    public function getRecord(string $module, string $id, array $fields = []): ?array;

    /**
     * Search for records matching criteria
     *
     * @param string $module Module name
     * @param array $query Search criteria
     * @param array $fields Fields to return
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of matching records
     * @throws SuiteAPI\Exceptions\ApiException
     *
     * @requirement REQ-SUITE-002
     */
    public function searchRecords(
        string $module,
        array $query = [],
        array $fields = [],
        int $limit = 20,
        int $offset = 0
    ): array;

    /**
     * Delete a record
     *
     * @param string $module Module name
     * @param string $id Record ID
     * @return bool True on successful deletion
     * @throws SuiteAPI\Exceptions\ApiException
     *
     * @requirement REQ-SUITE-002
     */
    public function deleteRecord(string $module, string $id): bool;

    /**
     * Check if API is authenticated
     *
     * @return bool True if authenticated
     */
    public function isAuthenticated(): bool;

    /**
     * Get last API response for debugging
     *
     * @return array|null Last response data
     */
    public function getLastResponse(): ?array;
}