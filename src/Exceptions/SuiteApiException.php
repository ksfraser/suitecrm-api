<?php
/**
 * SuiteCRM API Exception
 *
 * Base exception class for all SuiteCRM API operations.
 *
 * @package SuiteAPI
 * @subpackage Exceptions
 * @author AI Assistant
 * @since 1.0.0
 */

namespace Ksfraser\Ksfraser\SuiteAPI\Exceptions;

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