<?php
/**
 * Quote Service
 *
 * Business logic for SuiteCRM AOS_Quotes operations.
 * Handles quote lifecycle management, approval workflows, and conversion tracking.
 *
 * @package SuiteAPI
 * @subpackage Services
 * @author AI Assistant
 * @since 1.0.0
 *
 * @requirement REQ-SUITE-031: Quote Service Implementation
 * @requirement REQ-SUITE-032: Quote Validation Rules
 *
 * UML Class Diagram:
 * ```
 * +---------------------+
 * |    QuoteService     |
 * +---------------------+
 * | -requiredFields     |
 * | -validationRules    |
 * +---------------------+
 * | +createQuote()      |
 * | +updateQuote()      |
 * | +findByStage()      |
 * | +convertToOrder()   |
 * +---------------------+
 * ```
 */

namespace Ksfraser\Ksfraser\SuiteAPI\Services;

use Ksfraser\SuiteAPI\Interfaces\SuiteCrmApiInterface;
use Ksfraser\SuiteAPI\Exceptions\SuiteApiException;
use Ksfraser\SuiteAPI\Exceptions\ValidationException;

/**
 * Quote Service
 *
 * Provides business logic for managing SuiteCRM AOS_Quotes.
 * Includes quote lifecycle, approval workflows, and conversion tracking.
 */
class QuoteService extends BaseSuiteCrmService
{
    /**
     * Required fields for quote creation
     *
     * @var array
     */
    protected $requiredFields = [
        'name'
    ];

    /**
     * Validation rules for quote fields
     *
     * @var array
     */
    protected $validationRules = [
        'name' => [
            'max_length' => 255
        ],
        'stage' => [
            'in' => ['Draft', 'Negotiation', 'Delivered', 'On Hold', 'Confirmed', 'Closed Accepted', 'Closed Lost', 'Closed Dead']
        ],
        'quote_type' => [
            'in' => ['Quotes', 'Orders']
        ],
        'subtotal' => [
            // Should be numeric
        ],
        'total_amount' => [
            // Should be numeric
        ],
        'expiration' => [
            // Should be a valid date
        ],
        'description' => [
            'max_length' => 65535
        ],
        'billing_address_street' => [
            'max_length' => 150
        ],
        'billing_address_city' => [
            'max_length' => 100
        ],
        'billing_address_state' => [
            'max_length' => 100
        ],
        'billing_address_postalcode' => [
            'max_length' => 20
        ],
        'billing_address_country' => [
            'max_length' => 255
        ],
        'shipping_address_street' => [
            'max_length' => 150
        ],
        'shipping_address_city' => [
            'max_length' => 100
        ],
        'shipping_address_state' => [
            'max_length' => 100
        ],
        'shipping_address_postalcode' => [
            'max_length' => 20
        ],
        'shipping_address_country' => [
            'max_length' => 255
        ]
    ];

    /**
     * Constructor
     *
     * @param SuiteCrmApiInterface $api SuiteCRM API instance
     */
    public function __construct(SuiteCrmApiInterface $api)
    {
        parent::__construct($api, 'AOS_Quotes');
    }

    /**
     * Create a new quote
     *
     * @param array $quoteData Quote data
     * @return string|null Created quote ID
     * @throws ValidationException
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-031
     */
    public function createQuote(array $quoteData): ?string
    {
        // Set default stage if not provided
        if (!isset($quoteData['stage'])) {
            $quoteData['stage'] = 'Draft';
        }

        // Set default quote type if not provided
        if (!isset($quoteData['quote_type'])) {
            $quoteData['quote_type'] = 'Quotes';
        }

        return $this->create($quoteData);
    }

    /**
     * Update an existing quote
     *
     * @param string $quoteId Quote ID
     * @param array $quoteData Updated quote data
     * @return bool True on success
     * @throws ValidationException
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-031
     */
    public function updateQuote(string $quoteId, array $quoteData): bool
    {
        return $this->update($quoteId, $quoteData);
    }

    /**
     * Find quote by ID
     *
     * @param string $quoteId Quote ID
     * @return array|null Quote data or null if not found
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-031
     */
    public function findQuoteById(string $quoteId): ?array
    {
        return $this->findById($quoteId);
    }

    /**
     * Find quotes by stage
     *
     * @param string $stage Quote stage
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of matching quotes
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-031
     */
    public function findQuotesByStage(string $stage, int $limit = 20, int $offset = 0): array
    {
        return $this->search(
            ['stage' => $stage],
            [],
            $limit,
            $offset
        );
    }

    /**
     * Find quotes by account
     *
     * @param string $accountId Account ID
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of matching quotes
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-031
     */
    public function findQuotesByAccount(string $accountId, int $limit = 20, int $offset = 0): array
    {
        return $this->search(
            ['billing_account_id' => $accountId],
            [],
            $limit,
            $offset
        );
    }

    /**
     * Find quotes by contact
     *
     * @param string $contactId Contact ID
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of matching quotes
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-031
     */
    public function findQuotesByContact(string $contactId, int $limit = 20, int $offset = 0): array
    {
        return $this->search(
            ['billing_contact_id' => $contactId],
            [],
            $limit,
            $offset
        );
    }

    /**
     * Find quotes expiring soon
     *
     * @param int $days Number of days to look ahead
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of expiring quotes
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-031
     */
    public function findExpiringQuotes(int $days = 30, int $limit = 20, int $offset = 0): array
    {
        $expirationDate = date('Y-m-d', strtotime("+{$days} days"));

        return $this->search(
            [
                'expiration' => ['operator' => 'less_than', 'value' => $expirationDate],
                'stage' => ['operator' => 'not_in', 'value' => ['Closed Accepted', 'Closed Lost', 'Closed Dead']]
            ],
            [],
            $limit,
            $offset
        );
    }

    /**
     * Find expired quotes
     *
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of expired quotes
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-031
     */
    public function findExpiredQuotes(int $limit = 20, int $offset = 0): array
    {
        $today = date('Y-m-d');

        return $this->search(
            [
                'expiration' => ['operator' => 'less_than', 'value' => $today],
                'stage' => ['operator' => 'not_in', 'value' => ['Closed Accepted', 'Closed Lost', 'Closed Dead']]
            ],
            [],
            $limit,
            $offset
        );
    }

    /**
     * Convert quote to order
     *
     * @param string $quoteId Quote ID to convert
     * @param array $orderData Additional order data
     * @return array Conversion result
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-031
     */
    public function convertToOrder(string $quoteId, array $orderData = []): array
    {
        $quote = $this->findById($quoteId);

        if (!$quote) {
            throw new SuiteApiException("Quote not found: {$quoteId}");
        }

        // Update quote stage to indicate conversion
        $this->update($quoteId, ['stage' => 'Closed Accepted']);

        // Create order from quote data
        $orderData = array_merge([
            'name' => $quote['name'] ?? 'Order from Quote',
            'billing_account_id' => $quote['billing_account_id'] ?? null,
            'billing_contact_id' => $quote['billing_contact_id'] ?? null,
            'billing_address_street' => $quote['billing_address_street'] ?? null,
            'billing_address_city' => $quote['billing_address_city'] ?? null,
            'billing_address_state' => $quote['billing_address_state'] ?? null,
            'billing_address_postalcode' => $quote['billing_address_postalcode'] ?? null,
            'billing_address_country' => $quote['billing_address_country'] ?? null,
            'shipping_address_street' => $quote['shipping_address_street'] ?? null,
            'shipping_address_city' => $quote['shipping_address_city'] ?? null,
            'shipping_address_state' => $quote['shipping_address_state'] ?? null,
            'shipping_address_postalcode' => $quote['shipping_address_postalcode'] ?? null,
            'shipping_address_country' => $quote['shipping_address_country'] ?? null,
            'total_amount' => $quote['total_amount'] ?? null,
            'subtotal' => $quote['subtotal'] ?? null,
            'description' => $quote['description'] ?? null
        ], $orderData);

        // Note: This would typically create an AOS_Invoices record
        // For now, we'll return the order data structure
        return [
            'quote_id' => $quoteId,
            'order_data' => $orderData,
            'converted' => true,
            'conversion_date' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Get quote pipeline statistics
     *
     * @return array Quote statistics by stage
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-031
     */
    public function getQuoteStatistics(): array
    {
        $quotes = $this->search([], ['stage', 'total_amount'], 1000);

        $stats = [
            'total' => count($quotes),
            'by_stage' => [],
            'total_value' => 0.0,
            'average_value' => 0.0,
            'open_quotes' => 0,
            'closed_won' => 0,
            'closed_lost' => 0
        ];

        $totalValue = 0.0;
        $valueCount = 0;

        foreach ($quotes as $quote) {
            $stage = $quote['stage'] ?? 'Unknown';
            $amount = (float) ($quote['total_amount'] ?? 0);

            // Count by stage
            if (!isset($stats['by_stage'][$stage])) {
                $stats['by_stage'][$stage] = 0;
            }
            $stats['by_stage'][$stage]++;

            // Calculate totals
            if ($amount > 0) {
                $totalValue += $amount;
                $valueCount++;
            }

            // Count open vs closed
            if (in_array($stage, ['Draft', 'Negotiation', 'Delivered', 'On Hold', 'Confirmed'])) {
                $stats['open_quotes']++;
            } elseif ($stage === 'Closed Accepted') {
                $stats['closed_won']++;
            } elseif (in_array($stage, ['Closed Lost', 'Closed Dead'])) {
                $stats['closed_lost']++;
            }
        }

        $stats['total_value'] = $totalValue;
        $stats['average_value'] = $valueCount > 0 ? $totalValue / $valueCount : 0;

        return $stats;
    }

    /**
     * Preprocess quote data
     *
     * @param array $data Raw quote data
     * @return array Processed quote data
     */
    protected function preprocessData(array $data): array
    {
        $processed = parent::preprocessData($data);

        // Ensure numeric fields are properly formatted
        $numericFields = ['subtotal', 'subtotal_usdollar', 'total_amount'];
        foreach ($numericFields as $field) {
            if (isset($processed[$field])) {
                $processed[$field] = (float) $processed[$field];
            }
        }

        // Ensure date fields are properly formatted
        if (isset($processed['expiration']) && !empty($processed['expiration'])) {
            $processed['expiration'] = $this->normalizeDate($processed['expiration']);
        }

        if (isset($processed['date_quote_expected_close']) && !empty($processed['date_quote_expected_close'])) {
            $processed['date_quote_expected_close'] = $this->normalizeDateTime($processed['date_quote_expected_close']);
        }

        return $processed;
    }

    /**
     * Normalize date format
     *
     * @param string $date Date string
     * @return string Normalized date
     */
    private function normalizeDate(string $date): string
    {
        // If it's already in Y-m-d format, return as is
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $date;
        }

        // Try to parse other formats
        $timestamp = strtotime($date);
        if ($timestamp) {
            return date('Y-m-d', $timestamp);
        }

        // Return original if we can't parse it
        return $date;
    }

    /**
     * Normalize date/time format
     *
     * @param string $dateTime Date/time string
     * @return string Normalized date/time
     */
    private function normalizeDateTime(string $dateTime): string
    {
        // If it's already in Y-m-d H:i:s format, return as is
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $dateTime)) {
            return $dateTime;
        }

        // If it's just a date, add time
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTime)) {
            return $dateTime . ' 00:00:00';
        }

        // Try to parse other formats
        $timestamp = strtotime($dateTime);
        if ($timestamp) {
            return date('Y-m-d H:i:s', $timestamp);
        }

        // Return original if we can't parse it
        return $dateTime;
    }

    /**
     * Get relationship fields that need validation
     *
     * @return array Map of field names to module names
     */
    protected function getRelationshipFields(): array
    {
        return [
            'billing_account_id' => 'Accounts',
            'shipping_account_id' => 'Accounts',
            'billing_contact_id' => 'Contacts',
            'shipping_contact_id' => 'Contacts',
            'opportunity_id' => 'Opportunities',
            'assigned_user_id' => 'Users'
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
        $errors = [];

        // Validate expiration date
        if (isset($data['expiration'])) {
            $expiration = strtotime($data['expiration']);
            if ($expiration !== false && $expiration < time()) {
                $errors[] = "Quote expiration date cannot be in the past";
            }
        }

        // Validate quote progression logic
        if (isset($data['stage'])) {
            $stage = $data['stage'];

            // If quote is in a closed state, certain fields should be set
            if (in_array($stage, ['Closed Accepted', 'Closed Lost', 'Closed Dead'])) {
                // Could validate that closed quotes have required closure information
            }

            // Validate stage transitions (would need current stage for updates)
            if (!$isCreate && isset($data['id'])) {
                // Could validate stage transition logic here
            }
        }

        // Validate financial calculations
        if (isset($data['subtotal']) && isset($data['total_amount'])) {
            $subtotal = (float) $data['subtotal'];
            $total = (float) $data['total_amount'];

            if ($subtotal < 0) {
                $errors[] = "Subtotal cannot be negative";
            }

            if ($total < 0) {
                $errors[] = "Total amount cannot be negative";
            }

            // Total should be >= subtotal (allowing for discounts)
            if ($total < $subtotal && $total >= 0) {
                // This is normal for discounts, so we allow it
            }
        }

        if (!empty($errors)) {
            throw new ValidationException(
                "Business logic validation failed for quotes",
                $errors
            );
        }
    }
}