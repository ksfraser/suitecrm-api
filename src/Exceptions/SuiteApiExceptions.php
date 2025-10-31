<?php
/**
 * SuiteCRM API Exceptions
 *
 * Custom exception classes for SuiteCRM API operations.
 * Follows the principle of using specific exception types for different error conditions.
 *
 * @package SuiteAPI
 * @subpackage Exceptions
 * @author AI Assistant
 * @since 1.0.0
 *
 * @requirement REQ-SUITE-004: Error Handling Strategy
 *
 * UML Class Diagram:
 * ```
 * +---------------------+
 * | Exception           |
 * +---------------------+
 *           ^
 *           |
 * +---------------------+
 * | SuiteApiException   |
 * +---------------------+
 *           ^
 *           |
 * +---------+----------+
 * |        |          |
 * +---------------------+
 * | AuthenticationException |
 * | ConnectionException     |
 * | ValidationException     |
 * +---------------------+
 * ```
 */

namespace SuiteAPI\Exceptions;

/**
 * Base SuiteCRM API Exception
 *
 * All SuiteCRM-specific exceptions should extend this class.
 */
class SuiteApiException extends \Exception
{
    /**
     * API response data that caused the exception
     *
     * @var array|null
     */
    protected $apiResponse;

    /**
     * Module name where error occurred
     *
     * @var string|null
     */
    protected $module;

    /**
     * Constructor
     *
     * @param string $message Error message
     * @param int $code Error code
     * @param array|null $apiResponse API response data
     * @param string|null $module Module name
     * @param \Throwable|null $previous Previous exception
     */
    public function __construct(
        string $message,
        int $code = 0,
        ?array $apiResponse = null,
        ?string $module = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->apiResponse = $apiResponse;
        $this->module = $module;
    }

    /**
     * Get API response data
     *
     * @return array|null
     */
    public function getApiResponse(): ?array
    {
        return $this->apiResponse;
    }

    /**
     * Get module name
     *
     * @return string|null
     */
    public function getModule(): ?string
    {
        return $this->module;
    }
}

/**
 * Authentication Exception
 *
 * Thrown when authentication with SuiteCRM fails.
 */
class AuthenticationException extends SuiteApiException
{
    // Specific authentication error handling can be added here
}

/**
 * Connection Exception
 *
 * Thrown when unable to connect to SuiteCRM API.
 */
class ConnectionException extends SuiteApiException
{
    // Specific connection error handling can be added here
}

/**
 * Validation Exception
 *
 * Thrown when API request data fails validation.
 */
class ValidationException extends SuiteApiException
{
    /**
     * Validation errors
     *
     * @var array
     */
    protected $validationErrors;

    /**
     * Constructor
     *
     * @param string $message Error message
     * @param array $validationErrors Specific validation errors
     * @param array|null $apiResponse API response data
     * @param string|null $module Module name
     */
    public function __construct(
        string $message,
        array $validationErrors = [],
        ?array $apiResponse = null,
        ?string $module = null
    ) {
        parent::__construct($message, 0, $apiResponse, $module);
        $this->validationErrors = $validationErrors;
    }

    /**
     * Get validation errors
     *
     * @return array
     */
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }
}

/**
 * Record Not Found Exception
 *
 * Thrown when a requested record is not found.
 */
class RecordNotFoundException extends SuiteApiException
{
    /**
     * Record ID that was not found
     *
     * @var string
     */
    protected $recordId;

    /**
     * Constructor
     *
     * @param string $module Module name
     * @param string $recordId Record ID
     * @param array|null $apiResponse API response data
     */
    public function __construct(string $module, string $recordId, ?array $apiResponse = null)
    {
        $message = "Record not found in module '{$module}' with ID '{$recordId}'";
        parent::__construct($message, 0, $apiResponse, $module);
        $this->recordId = $recordId;
    }

    /**
     * Get record ID
     *
     * @return string
     */
    public function getRecordId(): string
    {
        return $this->recordId;
    }
}