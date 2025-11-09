<?php
/**
 * Prospects Service
 *
 * Service for managing SuiteCRM Prospects module.
 * Handles prospect management, lead qualification, and conversion tracking.
 *
 * @package SuiteAPI
 * @subpackage Services
 * @author AI Assistant
 * @since 1.0.0
 *
 * @requirement REQ-SUITE-018: Prospects Module Support
 * @requirement REQ-SUITE-031: Lead Qualification Integration
 *
 * UML Class Diagram:
 * ```
 * +---------------------+
 * |   ProspectsService  |
 * +---------------------+
 * | -api: SuiteCrmApiInterface |
 * | -moduleName: "Prospect" |
 * +---------------------+
 * | +createProspect()   |
 * | +updateProspect()   |
 * | +findProspectById() |
 * | +searchProspects()  |
 * | +deleteProspect()   |
 * | +convertToLead()    |
 * | +convertToContact() |
 * | +getConversionRate()|
 * | +bulkImport()       |
 * +---------------------+
 *           ^
 *           |
 * +---------------------+
 * | BaseSuiteCrmService|
 * +---------------------+
 * ```
 */

namespace Ksfraser\SuiteAPI\Services;

use Ksfraser\SuiteAPI\Interfaces\SuiteCrmApiInterface;
use Ksfraser\SuiteAPI\Exceptions\SuiteApiException;
use Ksfraser\SuiteAPI\Exceptions\ValidationException;

/**
 * Prospects Service
 *
 * Manages SuiteCRM Prospects with lead qualification and conversion tracking.
 */
class ProspectsService extends BaseSuiteCrmService
{
    /**
     * Constructor
     *
     * @param SuiteCrmApiInterface $api SuiteCRM API instance
     */
    public function __construct(SuiteCrmApiInterface $api)
    {
        parent::__construct($api, 'Prospect');

        $this->requiredFields = [
            'first_name',
            'last_name'
        ];

        $this->validationRules = [
            'first_name' => [
                'max_length' => 100
            ],
            'last_name' => [
                'max_length' => 100
            ],
            'email' => [
                'email' => true,
                'max_length' => 255
            ],
            'phone_work' => [
                'phone' => true,
                'max_length' => 50
            ],
            'phone_mobile' => [
                'phone' => true,
                'max_length' => 50
            ],
            'phone_home' => [
                'phone' => true,
                'max_length' => 50
            ],
            'lead_source' => [
                'in' => ['Cold Call', 'Existing Customer', 'Self Generated', 'Employee', 'Partner', 'Public Relations', 'Direct Mail', 'Conference', 'Trade Show', 'Web Site', 'Word of mouth', 'Email', 'Campaign', 'Other']
            ],
            'status' => [
                'in' => ['New', 'Assigned', 'In Process', 'Converted', 'Recycled', 'Dead']
            ],
            'salutation' => [
                'in' => ['Mr.', 'Ms.', 'Mrs.', 'Dr.', 'Prof.']
            ]
        ];
    }

    /**
     * Create a new prospect
     *
     * @param array $data Prospect data
     * @return string|null Created prospect ID
     * @throws ValidationException
     * @throws SuiteApiException
     */
    public function createProspect(array $data): ?string
    {
        return $this->create($data);
    }

    /**
     * Update an existing prospect
     *
     * @param string $id Prospect ID
     * @param array $data Updated prospect data
     * @return bool True on success
     * @throws ValidationException
     * @throws SuiteApiException
     */
    public function updateProspect(string $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    /**
     * Find prospect by ID
     *
     * @param string $id Prospect ID
     * @param array $fields Optional fields to retrieve
     * @return array|null Prospect data or null if not found
     * @throws SuiteApiException
     */
    public function findProspectById(string $id, array $fields = []): ?array
    {
        return $this->findById($id, $fields);
    }

    /**
     * Search for prospects
     *
     * @param array $criteria Search criteria
     * @param array $fields Fields to return
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of matching prospects
     * @throws SuiteApiException
     */
    public function searchProspects(array $criteria = [], array $fields = [], int $limit = 20, int $offset = 0): array
    {
        return $this->search($criteria, $fields, $limit, $offset);
    }

    /**
     * Delete a prospect
     *
     * @param string $id Prospect ID
     * @return bool True on success
     * @throws SuiteApiException
     */
    public function deleteProspect(string $id): bool
    {
        return $this->delete($id);
    }

    /**
     * Convert prospect to lead
     *
     * @param string $prospectId Prospect ID
     * @param array $additionalData Additional data for the lead
     * @return string|null Created lead ID
     * @throws ValidationException
     * @throws SuiteApiException
     */
    public function convertToLead(string $prospectId, array $additionalData = []): ?string
    {
        $prospect = $this->findProspectById($prospectId);
        if (!$prospect) {
            throw new ValidationException("Prospect with ID '{$prospectId}' does not exist");
        }

        // Prepare lead data from prospect data
        $leadData = array_merge($additionalData, [
            'first_name' => $prospect['first_name'],
            'last_name' => $prospect['last_name'],
            'email' => $prospect['email'] ?? '',
            'phone_work' => $prospect['phone_work'] ?? '',
            'phone_mobile' => $prospect['phone_mobile'] ?? '',
            'phone_home' => $prospect['phone_home'] ?? '',
            'lead_source' => $prospect['lead_source'] ?? '',
            'description' => $prospect['description'] ?? '',
            'salutation' => $prospect['salutation'] ?? '',
            'title' => $prospect['title'] ?? '',
            'department' => $prospect['department'] ?? '',
            'account_name' => $prospect['account_name'] ?? '',
            'website' => $prospect['website'] ?? '',
            'status' => 'New'
        ]);

        // Create the lead
        $leadService = new LeadService($this->api);
        $leadId = $leadService->createLead($leadData);

        if ($leadId) {
            // Update prospect status to converted
            $this->updateProspect($prospectId, ['status' => 'Converted']);

            // Link the prospect to the lead (if SuiteCRM supports this relationship)
            // This might require custom relationship handling
        }

        return $leadId;
    }

    /**
     * Convert prospect to contact
     *
     * @param string $prospectId Prospect ID
     * @param string $accountId Account ID to associate with
     * @param array $additionalData Additional data for the contact
     * @return string|null Created contact ID
     * @throws ValidationException
     * @throws SuiteApiException
     */
    public function convertToContact(string $prospectId, string $accountId, array $additionalData = []): ?string
    {
        $prospect = $this->findProspectById($prospectId);
        if (!$prospect) {
            throw new ValidationException("Prospect with ID '{$prospectId}' does not exist");
        }

        // Validate account exists
        $accountService = new AccountService($this->api);
        $account = $accountService->findAccountById($accountId);
        if (!$account) {
            throw new ValidationException("Account with ID '{$accountId}' does not exist");
        }

        // Prepare contact data from prospect data
        $contactData = array_merge($additionalData, [
            'first_name' => $prospect['first_name'],
            'last_name' => $prospect['last_name'],
            'email' => $prospect['email'] ?? '',
            'phone_work' => $prospect['phone_work'] ?? '',
            'phone_mobile' => $prospect['phone_mobile'] ?? '',
            'phone_home' => $prospect['phone_home'] ?? '',
            'title' => $prospect['title'] ?? '',
            'department' => $prospect['department'] ?? '',
            'account_id' => $accountId,
            'lead_source' => $prospect['lead_source'] ?? '',
            'description' => $prospect['description'] ?? ''
        ]);

        // Create the contact
        $contactService = new ContactService($this->api);
        $contactId = $contactService->createContact($contactData);

        if ($contactId) {
            // Update prospect status to converted
            $this->updateProspect($prospectId, ['status' => 'Converted']);
        }

        return $contactId;
    }

    /**
     * Get prospect conversion rate statistics
     *
     * @param array $dateRange Optional date range filter
     * @return array Conversion statistics
     * @throws SuiteApiException
     */
    public function getConversionRate(array $dateRange = []): array
    {
        $criteria = [];
        if (!empty($dateRange)) {
            $criteria['date_entered'] = $dateRange;
        }

        $allProspects = $this->searchProspects($criteria, ['status'], 1000);
        $convertedProspects = array_filter($allProspects, function($prospect) {
            return $prospect['status'] === 'Converted';
        });

        $totalProspects = count($allProspects);
        $convertedCount = count($convertedProspects);
        $conversionRate = $totalProspects > 0 ? ($convertedCount / $totalProspects) * 100 : 0;

        return [
            'total_prospects' => $totalProspects,
            'converted_prospects' => $convertedCount,
            'conversion_rate_percentage' => round($conversionRate, 2),
            'period' => $dateRange ?: 'all_time'
        ];
    }

    /**
     * Bulk import prospects
     *
     * @param array $prospects Array of prospect data arrays
     * @return array Import results with success/failure counts
     * @throws SuiteApiException
     */
    public function bulkImport(array $prospects): array
    {
        $results = [
            'successful' => 0,
            'failed' => 0,
            'errors' => []
        ];

        foreach ($prospects as $index => $prospectData) {
            try {
                $this->createProspect($prospectData);
                $results['successful']++;
            } catch (ValidationException | SuiteApiException $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'index' => $index,
                    'data' => $prospectData,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
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
            'modified_user_id' => 'User',
            'campaign_id' => 'Campaign'
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
        // Validate email format if provided
        if (isset($data['email']) && !empty($data['email'])) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                throw new ValidationException("Invalid email format");
            }
        }

        // Ensure at least one phone number is provided for qualified prospects
        if (isset($data['status']) && in_array($data['status'], ['Assigned', 'In Process'])) {
            $hasPhone = !empty($data['phone_work']) || !empty($data['phone_mobile']) || !empty($data['phone_home']);
            if (!$hasPhone) {
                throw new ValidationException("At least one phone number is required for qualified prospects");
            }
        }
    }
}