<?php
/**
 * SuiteCRM Contact Service
 *
 * Service layer for Contact-related operations.
 * Provides business logic and data transformation for contact management.
 *
 * @package SuiteAPI
 * @subpackage Services
 * @author AI Assistant
 * @since 1.0.0
 *
 * @requirement REQ-SUITE-008: Contact Management
 * @requirement REQ-SUITE-009: Data Validation
 *
 * UML Class Diagram:
 * ```
 * +---------------------+
 * | ContactService      |
 * +---------------------+
 * | -api: SuiteCrmApiInterface |
 * +---------------------+
 * | +createContact()    |
 * | +updateContact()    |
 * | +findContactByEmail()|
 * | +getContactDetails()|
 * +---------------------+
 * ```
 */

namespace Ksfraser\SuiteAPI\Services;

use Ksfraser\SuiteAPI\Interfaces\SuiteCrmApiInterface;
use Ksfraser\SuiteAPI\Exceptions\SuiteApiException;
use Ksfraser\SuiteAPI\Exceptions\ValidationException;

/**
 * Contact Service
 *
 * Handles business logic for SuiteCRM contact operations.
 * Provides validation, data transformation, and high-level contact management.
 */
class ContactService
{
    /**
     * SuiteCRM API instance
     *
     * @var SuiteCrmApiInterface
     */
    private $api;

    /**
     * Constructor with dependency injection
     *
     * @param SuiteCrmApiInterface $api SuiteCRM API instance
     */
    public function __construct(SuiteCrmApiInterface $api)
    {
        $this->api = $api;
    }

    /**
     * Create a new contact
     *
     * @param array $contactData Contact information
     * @return string|null Created contact ID
     * @throws ValidationException
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-008
     */
    public function createContact(array $contactData): ?string
    {
        $this->validateContactData($contactData);

        // Transform data to SuiteCRM format
        $suiteData = $this->transformContactData($contactData);

        return $this->api->createRecord('Contacts', $suiteData);
    }

    /**
     * Update an existing contact
     *
     * @param string $contactId Contact ID
     * @param array $contactData Updated contact information
     * @return bool True on success
     * @throws ValidationException
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-008
     */
    public function updateContact(string $contactId, array $contactData): bool
    {
        $this->validateContactData($contactData, false); // Allow partial updates

        // Transform data to SuiteCRM format
        $suiteData = $this->transformContactData($contactData);

        return $this->api->updateRecord('Contacts', $contactId, $suiteData);
    }

    /**
     * Find contact by email address
     *
     * @param string $email Email address
     * @return array|null Contact data or null if not found
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-008
     */
    public function findContactByEmail(string $email): ?array
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException('Invalid email address format');
        }

        $contacts = $this->api->searchRecords(
            'Contacts',
            ['email1' => $email],
            ['id', 'first_name', 'last_name', 'email1', 'phone_work', 'account_name']
        );

        return $contacts[0] ?? null;
    }

    /**
     * Get detailed contact information
     *
     * @param string $contactId Contact ID
     * @return array|null Contact details or null if not found
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-008
     */
    public function getContactDetails(string $contactId): ?array
    {
        $contact = $this->api->getRecord('Contacts', $contactId, [
            'id', 'first_name', 'last_name', 'email1', 'phone_work', 'phone_mobile',
            'primary_address_street', 'primary_address_city', 'primary_address_state',
            'primary_address_postalcode', 'primary_address_country', 'account_name',
            'title', 'department', 'birthdate', 'description'
        ]);

        if ($contact) {
            return $this->transformContactFromSuite($contact);
        }

        return null;
    }

    /**
     * Search contacts by name or company
     *
     * @param string $searchTerm Search term
     * @param int $limit Maximum results
     * @return array Array of matching contacts
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-008
     */
    public function searchContacts(string $searchTerm, int $limit = 20): array
    {
        // Search by first name, last name, or account name
        $query = [
            "first_name LIKE '%{$searchTerm}%'",
            "last_name LIKE '%{$searchTerm}%'",
            "account_name LIKE '%{$searchTerm}%'"
        ];

        $contacts = $this->api->searchRecords(
            'Contacts',
            ['query' => implode(' OR ', $query)],
            ['id', 'first_name', 'last_name', 'email1', 'phone_work', 'account_name'],
            $limit
        );

        return array_map([$this, 'transformContactFromSuite'], $contacts);
    }

    /**
     * Validate contact data
     *
     * @param array $data Contact data to validate
     * @param bool $requireAllFields Whether all fields are required
     * @throws ValidationException
     *
     * @requirement REQ-SUITE-009
     */
    private function validateContactData(array $data, bool $requireAllFields = true): void
    {
        $errors = [];

        // Required fields for new contacts
        if ($requireAllFields) {
            if (empty($data['first_name']) && empty($data['last_name'])) {
                $errors[] = 'Either first name or last name is required';
            }
        }

        // Email validation
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email address format';
        }

        // Phone validation (basic)
        if (!empty($data['phone_work']) && !preg_match('/^[\d\s\-\+\(\)\.]+$/', $data['phone_work'])) {
            $errors[] = 'Invalid work phone format';
        }

        if (!empty($data['phone_mobile']) && !preg_match('/^[\d\s\-\+\(\)\.]+$/', $data['phone_mobile'])) {
            $errors[] = 'Invalid mobile phone format';
        }

        // Postal code validation (Canadian)
        if (!empty($data['primary_address_postalcode'])) {
            $postalCode = strtoupper(str_replace(' ', '', $data['primary_address_postalcode']));
            if (!preg_match('/^[A-Z]\d[A-Z]\d[A-Z]\d$/', $postalCode)) {
                $errors[] = 'Invalid Canadian postal code format';
            }
        }

        if (!empty($errors)) {
            throw new ValidationException(
                'Contact data validation failed',
                $errors
            );
        }
    }

    /**
     * Transform contact data to SuiteCRM format
     *
     * @param array $data Input contact data
     * @return array SuiteCRM formatted data
     */
    private function transformContactData(array $data): array
    {
        $suiteData = [];

        // Direct mappings
        $fieldMappings = [
            'first_name' => 'first_name',
            'last_name' => 'last_name',
            'email' => 'email1',
            'phone_work' => 'phone_work',
            'phone_mobile' => 'phone_mobile',
            'title' => 'title',
            'department' => 'department',
            'birthdate' => 'birthdate',
            'description' => 'description',
            'account_name' => 'account_name',
            'primary_address_street' => 'primary_address_street',
            'primary_address_city' => 'primary_address_city',
            'primary_address_state' => 'primary_address_state',
            'primary_address_postalcode' => 'primary_address_postalcode',
            'primary_address_country' => 'primary_address_country'
        ];

        foreach ($fieldMappings as $inputField => $suiteField) {
            if (isset($data[$inputField])) {
                $suiteData[$suiteField] = $data[$inputField];
            }
        }

        // Special handling for full name
        if (!empty($data['first_name']) || !empty($data['last_name'])) {
            $suiteData['name'] = trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));
        }

        return $suiteData;
    }

    /**
     * Transform contact data from SuiteCRM format
     *
     * @param array $suiteData SuiteCRM contact data
     * @return array Standardized contact data
     */
    private function transformContactFromSuite(array $suiteData): array
    {
        return [
            'id' => $suiteData['id'] ?? null,
            'first_name' => $suiteData['first_name'] ?? '',
            'last_name' => $suiteData['last_name'] ?? '',
            'full_name' => ($suiteData['first_name'] ?? '') . ' ' . ($suiteData['last_name'] ?? ''),
            'email' => $suiteData['email1'] ?? '',
            'phone_work' => $suiteData['phone_work'] ?? '',
            'phone_mobile' => $suiteData['phone_mobile'] ?? '',
            'title' => $suiteData['title'] ?? '',
            'department' => $suiteData['department'] ?? '',
            'company' => $suiteData['account_name'] ?? '',
            'birthdate' => $suiteData['birthdate'] ?? '',
            'description' => $suiteData['description'] ?? '',
            'address' => [
                'street' => $suiteData['primary_address_street'] ?? '',
                'city' => $suiteData['primary_address_city'] ?? '',
                'state' => $suiteData['primary_address_state'] ?? '',
                'postal_code' => $suiteData['primary_address_postalcode'] ?? '',
                'country' => $suiteData['primary_address_country'] ?? ''
            ]
        ];
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
            'account_id' => 'Accounts',
            'reports_to_id' => 'Contacts',
            'campaign_id' => 'Campaign'
        ];
    }
}