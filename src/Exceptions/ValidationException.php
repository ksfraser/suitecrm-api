<?php
/**
 * Validation Exception
 *
 * Thrown when API request data fails validation.
 *
 * @package SuiteAPI
 * @subpackage Exceptions
 * @author AI Assistant
 * @since 1.0.0
 */

namespace Ksfraser\Ksfraser\SuiteAPI\Exceptions;

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