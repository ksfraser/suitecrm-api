<?php
/**
 * Lead Service
 *
 * Business logic for SuiteCRM Lead operations.
 * Handles lead creation, updates, qualification, and management.
 *
 * @package SuiteAPI
 * @subpackage Services
 * @author AI Assistant
 * @since 1.0.0
 *
 * @requirement REQ-SUITE-019: Lead Service Implementation
 * @requirement REQ-SUITE-020: Lead Validation Rules
 *
 * UML Class Diagram:
 * ```
 * +---------------------+
 * |     LeadService     |
 * +---------------------+
 * | -requiredFields     |
 * | -validationRules    |
 * +---------------------+
 * | +createLead()       |
 * | +updateLead()       |
 * | +findLeadByEmail()  |
 * | +convertToContact() |
 * +---------------------+
 * ```
 */

namespace Ksfraser\SuiteAPI\Services;

use Ksfraser\SuiteAPI\Interfaces\SuiteCrmApiInterface;
use Ksfraser\SuiteAPI\Exceptions\SuiteApiException;
use Ksfraser\SuiteAPI\Exceptions\ValidationException;

/**
 * Lead Service
 *
 * Provides business logic for managing SuiteCRM Leads.
 * Includes validation, data transformation, and lead qualification methods.
 */
class LeadService extends BaseSuiteCrmService
{
    /**
     * Required fields for lead creation
     *
     * @var array
     */
    protected $requiredFields = [
        'last_name'
    ];

    /**
     * Validation rules for lead fields
     *
     * @var array
     */
    protected $validationRules = [
        'first_name' => [
            'max_length' => 100
        ],
        'last_name' => [
            'max_length' => 100
        ],
        'title' => [
            'max_length' => 100
        ],
        'department' => [
            'max_length' => 100
        ],
        'account_name' => [
            'max_length' => 150
        ],
        'phone_home' => [
            'phone' => true
        ],
        'phone_mobile' => [
            'phone' => true
        ],
        'phone_work' => [
            'phone' => true
        ],
        'phone_other' => [
            'phone' => true
        ],
        'phone_fax' => [
            'phone' => true
        ],
        'email1' => [
            'email' => true
        ],
        'primary_address_street' => [
            'max_length' => 150
        ],
        'primary_address_city' => [
            'max_length' => 100
        ],
        'primary_address_postal' => [
            'max_length' => 20
        ],
        'primary_address_state' => [
            'max_length' => 100
        ],
        'primary_address_country' => [
            'max_length' => 255
        ],
        'alt_address_street' => [
            'max_length' => 150
        ],
        'alt_address_city' => [
            'max_length' => 100
        ],
        'alt_address_postal' => [
            'max_length' => 20
        ],
        'alt_address_state' => [
            'max_length' => 100
        ],
        'alt_address_country' => [
            'max_length' => 255
        ],
        'lead_source' => [
            'in' => [
                'Cold Call', 'Existing Customer', 'Self Generated', 'Employee',
                'Partner', 'Public Relations', 'Direct Mail', 'Conference',
                'Trade Show', 'Web Site', 'Word of mouth', 'Email', 'Campaign',
                'Other'
            ]
        ],
        'salutation' => [
            'in' => ['Mr.', 'Ms.', 'Mrs.', 'Dr.', 'Prof.']
        ],
        'assistant' => [
            'max_length' => 75
        ],
        'assistant_phone' => [
            'phone' => true
        ]
    ];

    /**
     * Constructor
     *
     * @param SuiteCrmApiInterface $api SuiteCRM API instance
     */
    public function __construct(SuiteCrmApiInterface $api)
    {
        parent::__construct($api, 'Leads');
    }

    /**
     * Create a new lead
     *
     * @param array $leadData Lead data
     * @return string|null Created lead ID
     * @throws ValidationException
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-019
     */
    public function createLead(array $leadData): ?string
    {
        return $this->create($leadData);
    }

    /**
     * Update an existing lead
     *
     * @param string $leadId Lead ID
     * @param array $leadData Updated lead data
     * @return bool True on success
     * @throws ValidationException
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-019
     */
    public function updateLead(string $leadId, array $leadData): bool
    {
        return $this->update($leadId, $leadData);
    }

    /**
     * Find lead by ID
     *
     * @param string $leadId Lead ID
     * @return array|null Lead data or null if not found
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-019
     */
    public function findLeadById(string $leadId): ?array
    {
        return $this->findById($leadId);
    }

    /**
     * Find leads by email
     *
     * @param string $email Email address
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of matching leads
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-019
     */
    public function findLeadsByEmail(string $email, int $limit = 20, int $offset = 0): array
    {
        return $this->search(
            ['email1' => $email],
            [],
            $limit,
            $offset
        );
    }

    /**
     * Find leads by name
     *
     * @param string $firstName First name
     * @param string $lastName Last name
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of matching leads
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-019
     */
    public function findLeadsByName(string $firstName = '', string $lastName = '', int $limit = 20, int $offset = 0): array
    {
        $criteria = [];

        if (!empty($firstName)) {
            $criteria['first_name'] = ['operator' => 'contains', 'value' => $firstName];
        }

        if (!empty($lastName)) {
            $criteria['last_name'] = ['operator' => 'contains', 'value' => $lastName];
        }

        return $this->search($criteria, [], $limit, $offset);
    }

    /**
     * Find leads by lead source
     *
     * @param string $leadSource Lead source
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of matching leads
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-019
     */
    public function findLeadsBySource(string $leadSource, int $limit = 20, int $offset = 0): array
    {
        return $this->search(
            ['lead_source' => $leadSource],
            [],
            $limit,
            $offset
        );
    }

    /**
     * Find leads by account name
     *
     * @param string $accountName Account name
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of matching leads
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-019
     */
    public function findLeadsByAccount(string $accountName, int $limit = 20, int $offset = 0): array
    {
        return $this->search(
            ['account_name' => ['operator' => 'contains', 'value' => $accountName]],
            [],
            $limit,
            $offset
        );
    }

    /**
     * Convert lead to contact
     *
     * @param string $leadId Lead ID to convert
     * @param array $conversionData Additional data for conversion
     * @return array Conversion result with contact and account IDs
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-019
     */
    public function convertToContact(string $leadId, array $conversionData = []): array
    {
        // This would typically involve SuiteCRM's lead conversion process
        // For now, we'll create a contact from the lead data
        $lead = $this->findById($leadId);

        if (!$lead) {
            throw new SuiteApiException("Lead not found: {$leadId}");
        }

        $contactData = [
            'first_name' => $lead['first_name'] ?? '',
            'last_name' => $lead['last_name'] ?? '',
            'title' => $lead['title'] ?? '',
            'department' => $lead['department'] ?? '',
            'phone_work' => $lead['phone_work'] ?? '',
            'phone_mobile' => $lead['phone_mobile'] ?? '',
            'phone_home' => $lead['phone_home'] ?? '',
            'phone_other' => $lead['phone_other'] ?? '',
            'phone_fax' => $lead['phone_fax'] ?? '',
            'email1' => $lead['email1'] ?? '',
            'primary_address_street' => $lead['primary_address_street'] ?? '',
            'primary_address_city' => $lead['primary_address_city'] ?? '',
            'primary_address_state' => $lead['primary_address_state'] ?? '',
            'primary_address_postalcode' => $lead['primary_address_postalcode'] ?? '',
            'primary_address_country' => $lead['primary_address_country'] ?? '',
            'description' => $lead['description'] ?? ''
        ];

        // Merge any additional conversion data
        $contactData = array_merge($contactData, $conversionData);

        // Create contact using ContactService
        $contactService = new ContactService($this->api);
        $contactId = $contactService->createContact($contactData);

        return [
            'lead_id' => $leadId,
            'contact_id' => $contactId,
            'converted' => true
        ];
    }

    /**
     * Preprocess lead data
     *
     * @param array $data Raw lead data
     * @return array Processed lead data
     */
    protected function preprocessData(array $data): array
    {
        $processed = parent::preprocessData($data);

        // Generate full name if not provided
        if (!isset($processed['name']) && (isset($processed['first_name']) || isset($processed['last_name']))) {
            $firstName = $processed['first_name'] ?? '';
            $lastName = $processed['last_name'] ?? '';
            $processed['name'] = trim($firstName . ' ' . $lastName);
        }

        // Format phone numbers
        $phoneFields = ['phone_home', 'phone_mobile', 'phone_work', 'phone_other', 'phone_fax', 'assistant_phone'];
        foreach ($phoneFields as $field) {
            if (isset($processed[$field])) {
                $processed[$field] = $this->formatPhoneNumber($processed[$field]);
            }
        }

        return $processed;
    }

    /**
     * Format phone number
     *
     * @param string $phone Phone number
     * @return string Formatted phone number
     */
    private function formatPhoneNumber(string $phone): string
    {
        // Remove all non-digit characters except + and spaces
        $cleaned = preg_replace('/[^\d+\s\-\(\)\.]/', '', $phone);
        return trim($cleaned);
    }
}