<?php
/**
 * Account Service
 *
 * Business logic for SuiteCRM Account operations.
 * Handles account creation, updates, and management.
 *
 * @package SuiteAPI
 * @subpackage Services
 * @author AI Assistant
 * @since 1.0.0
 *
 * @requirement REQ-SUITE-017: Account Service Implementation
 * @requirement REQ-SUITE-018: Account Validation Rules
 *
 * UML Class Diagram:
 * ```
 * +---------------------+
 * |    AccountService   |
 * +---------------------+
 * | -requiredFields     |
 * | -validationRules    |
 * +---------------------+
 * | +createAccount()    |
 * | +updateAccount()    |
 * | +findAccountByName()|
 * | +findAccountsByType()|
 * +---------------------+
 * ```
 */

namespace SuiteAPI\Services;

use SuiteAPI\Interfaces\SuiteCrmApiInterface;
use SuiteAPI\Exceptions\SuiteApiException;
use SuiteAPI\Exceptions\ValidationException;

/**
 * Account Service
 *
 * Provides business logic for managing SuiteCRM Accounts.
 * Includes validation, data transformation, and specialized search methods.
 */
class AccountService extends BaseSuiteCrmService
{
    /**
     * Required fields for account creation
     *
     * @var array
     */
    protected $requiredFields = [
        'name'
    ];

    /**
     * Validation rules for account fields
     *
     * @var array
     */
    protected $validationRules = [
        'name' => [
            'max_length' => 150
        ],
        'account_type' => [
            'in' => ['Customer', 'Partner', 'Reseller', 'Vendor', 'Other']
        ],
        'industry' => [
            'in' => [
                'Apparel', 'Banking', 'Biotechnology', 'Chemicals', 'Communications',
                'Construction', 'Consulting', 'Education', 'Electronics', 'Energy',
                'Engineering', 'Entertainment', 'Environmental', 'Finance', 'Government',
                'Healthcare', 'Hospitality', 'Insurance', 'Machinery', 'Manufacturing',
                'Media', 'Not For Profit', 'Recreation', 'Retail', 'Shipping',
                'Technology', 'Telecommunications', 'Transportation', 'Utilities', 'Other'
            ]
        ],
        'phone_office' => [
            'phone' => true
        ],
        'phone_fax' => [
            'phone' => true
        ],
        'phone_alternate' => [
            'phone' => true
        ],
        'website' => [
            'max_length' => 255
        ],
        'email1' => [
            'email' => true
        ],
        'billing_address_postalcode' => [
            'max_length' => 20
        ],
        'shipping_address_postalcode' => [
            'max_length' => 20
        ],
        'sic_code' => [
            'max_length' => 10
        ],
        'ticker_symbol' => [
            'max_length' => 10
        ]
    ];

    /**
     * Constructor
     *
     * @param SuiteCrmApiInterface $api SuiteCRM API instance
     */
    public function __construct(SuiteCrmApiInterface $api)
    {
        parent::__construct($api, 'Accounts');
    }

    /**
     * Create a new account
     *
     * @param array $accountData Account data
     * @return string|null Created account ID
     * @throws ValidationException
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-017
     */
    public function createAccount(array $accountData): ?string
    {
        return $this->create($accountData);
    }

    /**
     * Update an existing account
     *
     * @param string $accountId Account ID
     * @param array $accountData Updated account data
     * @return bool True on success
     * @throws ValidationException
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-017
     */
    public function updateAccount(string $accountId, array $accountData): bool
    {
        return $this->update($accountId, $accountData);
    }

    /**
     * Find account by ID
     *
     * @param string $accountId Account ID
     * @return array|null Account data or null if not found
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-017
     */
    public function findAccountById(string $accountId): ?array
    {
        return $this->findById($accountId);
    }

    /**
     * Find accounts by name
     *
     * @param string $name Account name to search for
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of matching accounts
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-017
     */
    public function findAccountsByName(string $name, int $limit = 20, int $offset = 0): array
    {
        return $this->search(
            ['name' => ['operator' => 'contains', 'value' => $name]],
            [],
            $limit,
            $offset
        );
    }

    /**
     * Find accounts by type
     *
     * @param string $accountType Account type
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of matching accounts
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-017
     */
    public function findAccountsByType(string $accountType, int $limit = 20, int $offset = 0): array
    {
        return $this->search(
            ['account_type' => $accountType],
            [],
            $limit,
            $offset
        );
    }

    /**
     * Find accounts by industry
     *
     * @param string $industry Industry
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of matching accounts
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-017
     */
    public function findAccountsByIndustry(string $industry, int $limit = 20, int $offset = 0): array
    {
        return $this->search(
            ['industry' => $industry],
            [],
            $limit,
            $offset
        );
    }

    /**
     * Find accounts by email
     *
     * @param string $email Email address
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of matching accounts
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-017
     */
    public function findAccountsByEmail(string $email, int $limit = 20, int $offset = 0): array
    {
        return $this->search(
            ['email1' => $email],
            [],
            $limit,
            $offset
        );
    }

    /**
     * Preprocess account data
     *
     * @param array $data Raw account data
     * @return array Processed account data
     */
    protected function preprocessData(array $data): array
    {
        $processed = parent::preprocessData($data);

        // Ensure name is properly formatted
        if (isset($processed['name'])) {
            $processed['name'] = trim($processed['name']);
        }

        // Format phone numbers if present
        $phoneFields = ['phone_office', 'phone_fax', 'phone_alternate'];
        foreach ($phoneFields as $field) {
            if (isset($processed[$field])) {
                $processed[$field] = $this->formatPhoneNumber($processed[$field]);
            }
        }

        // Ensure website has protocol
        if (isset($processed['website']) && !empty($processed['website'])) {
            if (!preg_match('/^https?:\/\//', $processed['website'])) {
                $processed['website'] = 'http://' . $processed['website'];
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
            'parent_id' => 'Accounts' // For hierarchical accounts
        ];
    }
}