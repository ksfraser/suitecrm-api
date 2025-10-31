<?php
/**
 * Email Templates Service
 *
 * Service for managing SuiteCRM Email Templates module.
 * Handles email template creation, management, and personalization.
 *
 * @package SuiteAPI
 * @subpackage Services
 * @author AI Assistant
 * @since 1.0.0
 *
 * @requirement REQ-SUITE-019: Email Templates Module Support
 * @requirement REQ-SUITE-032: Email Marketing Integration
 *
 * UML Class Diagram:
 * ```
 * +---------------------+
 * | EmailTemplatesService|
 * +---------------------+
 * | -api: SuiteCrmApiInterface |
 * | -moduleName: "EmailTemplate" |
 * +---------------------+
 * | +createTemplate()   |
 * | +updateTemplate()   |
 * | +findTemplateById() |
 * | +searchTemplates()  |
 * | +deleteTemplate()   |
 * | +cloneTemplate()    |
 * | +getTemplateVariables()|
 * | +validateTemplate() |
 * | +sendTestEmail()    |
 * +---------------------+
 *           ^
 *           |
 * +---------------------+
 * | BaseSuiteCrmService|
 * +---------------------+
 * ```
 */

namespace SuiteAPI\Services;

use SuiteAPI\Interfaces\SuiteCrmApiInterface;
use SuiteAPI\Exceptions\SuiteApiException;
use SuiteAPI\Exceptions\ValidationException;

/**
 * Email Templates Service
 *
 * Manages SuiteCRM Email Templates with personalization and validation.
 */
class EmailTemplatesService extends BaseSuiteCrmService
{
    /**
     * Constructor
     *
     * @param SuiteCrmApiInterface $api SuiteCRM API instance
     */
    public function __construct(SuiteCrmApiInterface $api)
    {
        parent::__construct($api, 'EmailTemplate');

        $this->requiredFields = [
            'name',
            'subject',
            'body'
        ];

        $this->validationRules = [
            'name' => [
                'max_length' => 255
            ],
            'subject' => [
                'max_length' => 255
            ],
            'body' => [
                'max_length' => 32000 // Large text field
            ],
            'description' => [
                'max_length' => 1000
            ],
            'type' => [
                'in' => ['campaign', 'email', 'workflow', 'system']
            ],
            'published' => [
                'in' => ['0', '1', 'on', 'off']
            ]
        ];
    }

    /**
     * Create a new email template
     *
     * @param array $data Template data
     * @return string|null Created template ID
     * @throws ValidationException
     * @throws SuiteApiException
     */
    public function createTemplate(array $data): ?string
    {
        return $this->create($data);
    }

    /**
     * Update an existing email template
     *
     * @param string $id Template ID
     * @param array $data Updated template data
     * @return bool True on success
     * @throws ValidationException
     * @throws SuiteApiException
     */
    public function updateTemplate(string $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    /**
     * Find template by ID
     *
     * @param string $id Template ID
     * @param array $fields Optional fields to retrieve
     * @return array|null Template data or null if not found
     * @throws SuiteApiException
     */
    public function findTemplateById(string $id, array $fields = []): ?array
    {
        return $this->findById($id, $fields);
    }

    /**
     * Search for email templates
     *
     * @param array $criteria Search criteria
     * @param array $fields Fields to return
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of matching templates
     * @throws SuiteApiException
     */
    public function searchTemplates(array $criteria = [], array $fields = [], int $limit = 20, int $offset = 0): array
    {
        return $this->search($criteria, $fields, $limit, $offset);
    }

    /**
     * Delete an email template
     *
     * @param string $id Template ID
     * @return bool True on success
     * @throws SuiteApiException
     */
    public function deleteTemplate(string $id): bool
    {
        return $this->delete($id);
    }

    /**
     * Clone an existing template
     *
     * @param string $templateId Template ID to clone
     * @param string $newName New template name
     * @param array $modifications Optional modifications to apply
     * @return string|null Created template ID
     * @throws ValidationException
     * @throws SuiteApiException
     */
    public function cloneTemplate(string $templateId, string $newName, array $modifications = []): ?string
    {
        $originalTemplate = $this->findTemplateById($templateId);
        if (!$originalTemplate) {
            throw new ValidationException("Template with ID '{$templateId}' does not exist");
        }

        // Prepare clone data
        $cloneData = array_merge($originalTemplate, $modifications, [
            'name' => $newName,
            'published' => '0' // Start as draft
        ]);

        // Remove system fields that shouldn't be copied
        unset($cloneData['id'], $cloneData['date_entered'], $cloneData['date_modified'],
              $cloneData['created_by'], $cloneData['modified_user_id']);

        return $this->createTemplate($cloneData);
    }

    /**
     * Get available template variables
     *
     * @param string $module Module name for context-specific variables
     * @return array Array of available variables
     */
    public function getTemplateVariables(string $module = 'Contact'): array
    {
        // Common variables available in SuiteCRM email templates
        $commonVariables = [
            '$contact_first_name',
            '$contact_last_name',
            '$contact_email',
            '$contact_phone_work',
            '$contact_phone_mobile',
            '$contact_account_name',
            '$contact_title',
            '$contact_department',
            '$user_first_name',
            '$user_last_name',
            '$user_email',
            '$user_phone_work',
            '$current_date',
            '$current_time'
        ];

        // Module-specific variables
        $moduleVariables = [
            'Contact' => [
                '$contact_birthdate',
                '$contact_assistant',
                '$contact_assistant_phone'
            ],
            'Lead' => [
                '$lead_first_name',
                '$lead_last_name',
                '$lead_email',
                '$lead_phone_work',
                '$lead_status',
                '$lead_lead_source'
            ],
            'Account' => [
                '$account_name',
                '$account_website',
                '$account_phone_office',
                '$account_billing_address_street',
                '$account_billing_address_city',
                '$account_billing_address_state',
                '$account_billing_address_postalcode',
                '$account_billing_address_country'
            ],
            'Opportunity' => [
                '$opportunity_name',
                '$opportunity_amount',
                '$opportunity_close_date',
                '$opportunity_sales_stage'
            ]
        ];

        $variables = $commonVariables;
        if (isset($moduleVariables[$module])) {
            $variables = array_merge($variables, $moduleVariables[$module]);
        }

        return [
            'module' => $module,
            'variables' => $variables,
            'description' => 'Available variables for email template personalization'
        ];
    }

    /**
     * Validate template syntax and variables
     *
     * @param array $templateData Template data to validate
     * @return array Validation results
     */
    public function validateTemplate(array $templateData): array
    {
        $results = [
            'valid' => true,
            'errors' => [],
            'warnings' => []
        ];

        // Check for required fields
        if (empty($templateData['subject'])) {
            $results['errors'][] = 'Subject is required';
            $results['valid'] = false;
        }

        if (empty($templateData['body'])) {
            $results['errors'][] = 'Body is required';
            $results['valid'] = false;
        }

        // Check for malformed variables (basic check)
        if (!empty($templateData['body'])) {
            $body = $templateData['body'];

            // Find all variables in the template
            preg_match_all('/\$[a-zA-Z_][a-zA-Z0-9_]*/', $body, $matches);
            $usedVariables = $matches[0];

            if (!empty($usedVariables)) {
                $availableVars = $this->getTemplateVariables()['variables'];

                foreach ($usedVariables as $variable) {
                    if (!in_array($variable, $availableVars)) {
                        $results['warnings'][] = "Variable '{$variable}' may not be available in all contexts";
                    }
                }
            }
        }

        // Check HTML structure if it's HTML email
        if (!empty($templateData['body']) && strpos($templateData['body'], '<') !== false) {
            // Basic HTML validation
            $openTags = preg_match_all('/<[^\/][^>]*>/', $templateData['body'], $openMatches);
            $closeTags = preg_match_all('/<\/[^>]+>/', $templateData['body'], $closeMatches);

            if ($openTags !== $closeTags) {
                $results['warnings'][] = 'HTML tags may not be properly balanced';
            }
        }

        return $results;
    }

    /**
     * Send a test email using the template
     *
     * @param string $templateId Template ID
     * @param string $testEmail Test email address
     * @param array $testData Test data for variable replacement
     * @return bool True on success
     * @throws ValidationException
     * @throws SuiteApiException
     */
    public function sendTestEmail(string $templateId, string $testEmail, array $testData = []): bool
    {
        $template = $this->findTemplateById($templateId);
        if (!$template) {
            throw new ValidationException("Template with ID '{$templateId}' does not exist");
        }

        if (!filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException("Invalid test email address");
        }

        // Prepare test data with defaults
        $defaultTestData = [
            'contact_first_name' => 'John',
            'contact_last_name' => 'Doe',
            'contact_email' => $testEmail,
            'user_first_name' => 'Test',
            'user_last_name' => 'User',
            'current_date' => date('Y-m-d'),
            'current_time' => date('H:i:s')
        ];

        $mergeData = array_merge($defaultTestData, $testData);

        // Replace variables in subject and body
        $subject = $this->replaceVariables($template['subject'], $mergeData);
        $body = $this->replaceVariables($template['body'], $mergeData);

        // Use EmailService to send the test email
        $emailService = new EmailService($this->api);
        return $emailService->sendEmail([
            'to' => [$testEmail],
            'subject' => $subject,
            'body' => $body,
            'body_type' => strpos($body, '<') !== false ? 'html' : 'text'
        ]);
    }

    /**
     * Replace variables in template content
     *
     * @param string $content Template content
     * @param array $data Variable data
     * @return string Content with variables replaced
     */
    private function replaceVariables(string $content, array $data): string
    {
        foreach ($data as $key => $value) {
            $variable = '$' . $key;
            $content = str_replace($variable, $value, $content);
        }

        return $content;
    }

    /**
     * Get relationship fields for validation
     *
     * @return array Map of field names to module names
     */
    protected function getRelationshipFields(): array
    {
        return [
            'assigned_user_id' => 'User',
            'created_by' => 'User',
            'modified_user_id' => 'User'
        ];
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
        // Validate template syntax
        $validationResults = $this->validateTemplate($data);
        if (!$validationResults['valid']) {
            $errors = implode(', ', $validationResults['errors']);
            throw new ValidationException("Template validation failed: {$errors}");
        }

        // Check for duplicate names
        if ($isCreate && isset($data['name'])) {
            $existing = $this->searchTemplates(['name' => $data['name']]);
            if (!empty($existing)) {
                throw new ValidationException("Template with name '{$data['name']}' already exists");
            }
        }
    }
}