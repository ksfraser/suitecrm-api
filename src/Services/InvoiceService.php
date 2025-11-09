<?php
/**
 * Invoice Service
 *
 * Business logic for SuiteCRM AOS_Invoices operations.
 * Handles invoice creation, payment tracking, and billing workflows.
 *
 * @package SuiteAPI
 * @subpackage Services
 * @author AI Assistant
 * @since 1.0.0
 *
 * @requirement REQ-SUITE-033: Invoice Service Implementation
 * @requirement REQ-SUITE-034: Invoice Validation Rules
 *
 * UML Class Diagram:
 * ```
 * +---------------------+
 * |   InvoiceService    |
 * +---------------------+
 * | -requiredFields     |
 * | -validationRules    |
 * +---------------------+
 * | +createInvoice()    |
 * | +updateInvoice()    |
 * | +findByStatus()     |
 * | +recordPayment()    |
 * +---------------------+
 * ```
 */

namespace Ksfraser\Ksfraser\SuiteAPI\Services;

use Ksfraser\SuiteAPI\Interfaces\SuiteCrmApiInterface;
use Ksfraser\SuiteAPI\Exceptions\SuiteApiException;
use Ksfraser\SuiteAPI\Exceptions\ValidationException;

/**
 * Invoice Service
 *
 * Provides business logic for managing SuiteCRM AOS_Invoices.
 * Includes invoice lifecycle, payment tracking, and billing operations.
 */
class InvoiceService extends BaseSuiteCrmService
{
    /**
     * Required fields for invoice creation
     *
     * @var array
     */
    protected $requiredFields = [
        'name'
    ];

    /**
     * Validation rules for invoice fields
     *
     * @var array
     */
    protected $validationRules = [
        'name' => [
            'max_length' => 255
        ],
        'number' => [
            'max_length' => 255
        ],
        'aos_invoices_type' => [
            'in' => ['Invoice', 'Credit Note']
        ],
        'status' => [
            'in' => ['Draft', 'Sent', 'Paid', 'Unpaid', 'Cancelled']
        ],
        'subtotal_amount' => [
            // Should be numeric
        ],
        'tax_amount' => [
            // Should be numeric
        ],
        'total_amount' => [
            // Should be numeric
        ],
        'discount_amount' => [
            // Should be numeric
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
        ],
        'due_date' => [
            // Should be a valid date
        ]
    ];

    /**
     * Constructor
     *
     * @param SuiteCrmApiInterface $api SuiteCRM API instance
     */
    public function __construct(SuiteCrmApiInterface $api)
    {
        parent::__construct($api, 'AOS_Invoices');
    }

    /**
     * Create a new invoice
     *
     * @param array $invoiceData Invoice data
     * @return string|null Created invoice ID
     * @throws ValidationException
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-033
     */
    public function createInvoice(array $invoiceData): ?string
    {
        // Set default status if not provided
        if (!isset($invoiceData['status'])) {
            $invoiceData['status'] = 'Draft';
        }

        // Set default type if not provided
        if (!isset($invoiceData['aos_invoices_type'])) {
            $invoiceData['aos_invoices_type'] = 'Invoice';
        }

        return $this->create($invoiceData);
    }

    /**
     * Update an existing invoice
     *
     * @param string $invoiceId Invoice ID
     * @param array $invoiceData Updated invoice data
     * @return bool True on success
     * @throws ValidationException
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-033
     */
    public function updateInvoice(string $invoiceId, array $invoiceData): bool
    {
        return $this->update($invoiceId, $invoiceData);
    }

    /**
     * Find invoice by ID
     *
     * @param string $invoiceId Invoice ID
     * @return array|null Invoice data or null if not found
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-033
     */
    public function findInvoiceById(string $invoiceId): ?array
    {
        return $this->findById($invoiceId);
    }

    /**
     * Find invoices by status
     *
     * @param string $status Invoice status
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of matching invoices
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-033
     */
    public function findInvoicesByStatus(string $status, int $limit = 20, int $offset = 0): array
    {
        return $this->search(
            ['status' => $status],
            [],
            $limit,
            $offset
        );
    }

    /**
     * Find invoices by account
     *
     * @param string $accountId Account ID
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of matching invoices
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-033
     */
    public function findInvoicesByAccount(string $accountId, int $limit = 20, int $offset = 0): array
    {
        return $this->search(
            ['billing_account_id' => $accountId],
            [],
            $limit,
            $offset
        );
    }

    /**
     * Find overdue invoices
     *
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of overdue invoices
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-033
     */
    public function findOverdueInvoices(int $limit = 20, int $offset = 0): array
    {
        $today = date('Y-m-d');

        return $this->search(
            [
                'due_date' => ['operator' => 'less_than', 'value' => $today],
                'status' => ['operator' => 'not_in', 'value' => ['Paid', 'Cancelled']]
            ],
            [],
            $limit,
            $offset
        );
    }

    /**
     * Find invoices due soon
     *
     * @param int $days Number of days to look ahead
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of invoices due soon
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-033
     */
    public function findInvoicesDueSoon(int $days = 7, int $limit = 20, int $offset = 0): array
    {
        $dueDate = date('Y-m-d', strtotime("+{$days} days"));
        $today = date('Y-m-d');

        return $this->search(
            [
                'due_date' => ['operator' => 'between', 'value' => [$today, $dueDate]],
                'status' => ['operator' => 'not_in', 'value' => ['Paid', 'Cancelled']]
            ],
            [],
            $limit,
            $offset
        );
    }

    /**
     * Record payment for invoice
     *
     * @param string $invoiceId Invoice ID
     * @param float $amount Payment amount
     * @param string $paymentDate Payment date
     * @param string|null $paymentMethod Payment method
     * @return bool True on success
     * @throws ValidationException
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-033
     */
    public function recordPayment(string $invoiceId, float $amount, string $paymentDate, ?string $paymentMethod = null): bool
    {
        $invoice = $this->findById($invoiceId);

        if (!$invoice) {
            throw new SuiteApiException("Invoice not found: {$invoiceId}");
        }

        $updateData = [
            'status' => 'Paid'
        ];

        // Add payment information to description or work log
        $paymentNote = "Payment recorded: {$amount} on {$paymentDate}";
        if ($paymentMethod) {
            $paymentNote .= " via {$paymentMethod}";
        }

        $existingDescription = $invoice['description'] ?? '';
        $updateData['description'] = $existingDescription . "\n\n" . $paymentNote;

        return $this->update($invoiceId, $updateData);
    }

    /**
     * Mark invoice as sent
     *
     * @param string $invoiceId Invoice ID
     * @return bool True on success
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-033
     */
    public function markAsSent(string $invoiceId): bool
    {
        return $this->update($invoiceId, ['status' => 'Sent']);
    }

    /**
     * Cancel invoice
     *
     * @param string $invoiceId Invoice ID
     * @param string $reason Cancellation reason
     * @return bool True on success
     * @throws ValidationException
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-033
     */
    public function cancelInvoice(string $invoiceId, string $reason): bool
    {
        $updateData = [
            'status' => 'Cancelled',
            'description' => ($this->findById($invoiceId)['description'] ?? '') . "\n\n[CANCELLED] " . date('Y-m-d H:i:s') . ": {$reason}"
        ];

        return $this->update($invoiceId, $updateData);
    }

    /**
     * Get invoice statistics
     *
     * @return array Invoice statistics
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-033
     */
    public function getInvoiceStatistics(): array
    {
        $invoices = $this->search([], ['status', 'total_amount', 'due_date'], 1000);

        $stats = [
            'total' => count($invoices),
            'by_status' => [],
            'total_value' => 0.0,
            'paid_value' => 0.0,
            'outstanding_value' => 0.0,
            'overdue_count' => 0,
            'overdue_value' => 0.0
        ];

        $today = date('Y-m-d');

        foreach ($invoices as $invoice) {
            $status = $invoice['status'] ?? 'Unknown';
            $amount = (float) ($invoice['total_amount'] ?? 0);
            $dueDate = $invoice['due_date'] ?? null;

            // Count by status
            if (!isset($stats['by_status'][$status])) {
                $stats['by_status'][$status] = 0;
            }
            $stats['by_status'][$status]++;

            // Calculate financial totals
            $stats['total_value'] += $amount;

            if ($status === 'Paid') {
                $stats['paid_value'] += $amount;
            } elseif ($status !== 'Cancelled') {
                $stats['outstanding_value'] += $amount;

                // Check if overdue
                if ($dueDate && $dueDate < $today) {
                    $stats['overdue_count']++;
                    $stats['overdue_value'] += $amount;
                }
            }
        }

        return $stats;
    }

    /**
     * Preprocess invoice data
     *
     * @param array $data Raw invoice data
     * @return array Processed invoice data
     */
    protected function preprocessData(array $data): array
    {
        $processed = parent::preprocessData($data);

        // Ensure numeric fields are properly formatted
        $numericFields = ['subtotal_amount', 'tax_amount', 'total_amount', 'discount_amount'];
        foreach ($numericFields as $field) {
            if (isset($processed[$field])) {
                $processed[$field] = (float) $processed[$field];
            }
        }

        // Ensure date fields are properly formatted
        if (isset($processed['due_date']) && !empty($processed['due_date'])) {
            $processed['due_date'] = $this->normalizeDate($processed['due_date']);
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
}