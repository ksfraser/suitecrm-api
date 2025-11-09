<?php
/**
 * Base SuiteCRM Service
 *
 * Abstract base class for SuiteCRM module services.
 * Provides common functionality and enforces consistent patterns.
 *
 * @package SuiteAPI
 * @subpackage Services
 * @author AI Assistant
 * @since 1.0.0
 *
 * @requirement REQ-SUITE-015: Base Service Class
 * @requirement REQ-SUITE-016: Consistent Service Patterns
 *
 * UML Class Diagram:
 * ```
 * +---------------------+
 * | <<abstract>>       |
 * | BaseSuiteCrmService|
 * +---------------------+
 * | -api: SuiteCrmApiInterface |
 * | -moduleName: string |
 * +---------------------+
 * | +create()           |
 * | +update()           |
 * | +findById()         |
 * | +search()           |
 * | +delete()           |
 * +---------------------+
 *           ^
 *           |
 * +---------------------+
 * | AccountService     |
 * | ContactService     |
 * | LeadService        |
 * +---------------------+
 * ```
 */

namespace Ksfraser\SuiteAPI\Services;

use Ksfraser\SuiteAPI\Interfaces\SuiteCrmApiInterface;
use Ksfraser\SuiteAPI\Exceptions\SuiteApiException;
use Ksfraser\SuiteAPI\Exceptions\ValidationException;

/**
 * Base SuiteCRM Service
 *
 * Provides common functionality for all SuiteCRM module services.
 * Enforces consistent patterns and reduces code duplication.
 */
abstract class BaseSuiteCrmService
{
    /**
     * SuiteCRM API instance
     *
     * @var SuiteCrmApiInterface
     */
    protected $api;

    /**
     * Module name for this service
     *
     * @var string
     */
    protected $moduleName;

    /**
     * Required fields for create operations
     *
     * @var array
     */
    protected $requiredFields = [];

    /**
     * Field validation rules
     *
     * @var array
     */
    protected $validationRules = [];

    /**
     * Constructor with dependency injection
     *
     * @param SuiteCrmApiInterface $api SuiteCRM API instance
     * @param string $moduleName Module name for this service
     */
    public function __construct(SuiteCrmApiInterface $api, string $moduleName)
    {
        $this->api = $api;
        $this->moduleName = $moduleName;
    }

    /**
     * Create a new record
     *
     * @param array $data Record data
     * @return string|null Created record ID
     * @throws ValidationException
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-016
     */
    public function create(array $data): ?string
    {
        $this->validateData($data, true);
        $this->validateBusinessLogic($data, true);
        $this->validateRelationships($data, $this->getRelationshipFields());
        $processedData = $this->preprocessData($data);

        return $this->api->createRecord($this->moduleName, $processedData);
    }

    /**
     * Update an existing record
     *
     * @param string $id Record ID
     * @param array $data Updated data
     * @return bool True on success
     * @throws ValidationException
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-016
     */
    public function update(string $id, array $data): bool
    {
        $this->validateData($data, false);
        $this->validateBusinessLogic($data, false);
        $this->validateRelationships($data, $this->getRelationshipFields());
        $processedData = $this->preprocessData($data);

        return $this->api->updateRecord($this->moduleName, $id, $processedData);
    }

    /**
     * Find record by ID
     *
     * @param string $id Record ID
     * @param array $fields Optional fields to retrieve
     * @return array|null Record data or null if not found
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-016
     */
    public function findById(string $id, array $fields = []): ?array
    {
        $record = $this->api->getRecord($this->moduleName, $id, $fields);

        if ($record) {
            return $this->postprocessData($record);
        }

        return null;
    }

    /**
     * Search for records
     *
     * @param array $criteria Search criteria
     * @param array $fields Fields to return
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of matching records
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-016
     */
    public function search(array $criteria = [], array $fields = [], int $limit = 20, int $offset = 0): array
    {
        $records = $this->api->searchRecords($this->moduleName, $criteria, $fields, $limit, $offset);

        return array_map([$this, 'postprocessData'], $records);
    }

    /**
     * Delete a record
     *
     * @param string $id Record ID
     * @return bool True on success
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-016
     */
    public function delete(string $id): bool
    {
        return $this->api->deleteRecord($this->moduleName, $id);
    }

    /**
     * Validate data according to service rules
     *
     * @param array $data Data to validate
     * @param bool $isCreate Whether this is for create operation
     * @throws ValidationException
     */
    protected function validateData(array $data, bool $isCreate): void
    {
        $errors = [];

        // Check required fields for create operations
        if ($isCreate) {
            foreach ($this->requiredFields as $field) {
                if (!isset($data[$field]) || $data[$field] === '') {
                    $errors[] = "Field '{$field}' is required";
                }
            }
        }

        // Apply validation rules
        foreach ($this->validationRules as $field => $rules) {
            if (isset($data[$field])) {
                $value = $data[$field];

                foreach ($rules as $rule => $ruleValue) {
                    switch ($rule) {
                        case 'email':
                            if ($ruleValue && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                                $errors[] = "Field '{$field}' must be a valid email address";
                            }
                            break;

                        case 'phone':
                            if ($ruleValue && !preg_match('/^[\d\s\-\+\(\)\.extEXT]+$/', $value)) {
                                $errors[] = "Field '{$field}' must be a valid phone number";
                            }
                            break;

                        case 'max_length':
                            if (strlen($value) > $ruleValue) {
                                $errors[] = "Field '{$field}' must not exceed {$ruleValue} characters";
                            }
                            break;

                        case 'in':
                            if (!in_array($value, $ruleValue)) {
                                $errors[] = "Field '{$field}' must be one of: " . implode(', ', $ruleValue);
                            }
                            break;
                    }
                }
            }
        }

        if (!empty($errors)) {
            throw new ValidationException(
                "Validation failed for {$this->moduleName}",
                $errors
            );
        }
    }

    /**
     * Validate that referenced entities exist
     *
     * @param array $data Data containing relationship fields
     * @param array $relationshipFields Map of field names to module names
     * @throws ValidationException
     */
    protected function validateRelationships(array $data, array $relationshipFields): void
    {
        $errors = [];

        foreach ($relationshipFields as $field => $module) {
            if (isset($data[$field]) && !empty($data[$field])) {
                $entityId = $data[$field];

                // Check if the referenced entity exists
                try {
                    $record = $this->api->getRecord($module, $entityId);
                    if ($record === null) {
                        $errors[] = "Referenced {$module} record '{$entityId}' does not exist for field '{$field}'";
                    }
                } catch (SuiteApiException $e) {
                    $errors[] = "Unable to validate {$module} record '{$entityId}' for field '{$field}': " . $e->getMessage();
                }
            }
        }

        if (!empty($errors)) {
            throw new ValidationException(
                "Relationship validation failed for {$this->moduleName}",
                $errors
            );
        }
    }

    /**
     * Validate business logic rules
     *
     * @param array $data Data to validate
     * @param bool $isCreate Whether this is for create operation
     * @throws ValidationException
     */
    protected function validateBusinessLogic(array $data, bool $isCreate): void
    {
        // Default implementation - subclasses can override
        // This method is called after basic validation but before relationship validation
    }

    /**
     * Preprocess data before sending to API
     *
     * @param array $data Raw data
     * @return array Processed data
     */
    protected function preprocessData(array $data): array
    {
        // Default implementation returns data unchanged
        // Subclasses can override for specific processing
        return $data;
    }

    /**
     * Postprocess data after receiving from API
     *
     * @param array $data Raw API data
     * @return array Processed data
     */
    protected function postprocessData(array $data): array
    {
        // Default implementation returns data unchanged
        // Subclasses can override for specific processing
        return $data;
    }

    /**
     * Get the module name
     *
     * @return string
     */
    public function getModuleName(): string
    {
        return $this->moduleName;
    }

    /**
     * Get relationship fields that need validation
     *
     * @return array Map of field names to module names
     */
    protected function getRelationshipFields(): array
    {
        // Default implementation returns empty array
        // Subclasses should override to specify relationship fields
        return [];
    }
}