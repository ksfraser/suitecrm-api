<?php
/**
 * Email Service
 *
 * Business logic for SuiteCRM Emails operations.
 * Handles email tracking, archiving, and communication history.
 *
 * @package SuiteAPI
 * @subpackage Services
 * @author AI Assistant
 * @since 1.0.0
 *
 * @requirement REQ-SUITE-041: Email Service Implementation
 * @requirement REQ-SUITE-042: Email Validation Rules
 *
 * UML Class Diagram:
 * ```
 * +---------------------+
 * |   EmailService      |
 * +---------------------+
 * | -requiredFields     |
 * | -validationRules    |
 * +---------------------+
 * | +createEmail()      |
 * | +updateEmail()      |
 * | +findByDateRange()  |
 * | +archiveEmail()     |
 * +---------------------+
 * ```
 */

namespace SuiteAPI\Services;

use SuiteAPI\Interfaces\SuiteCrmApiInterface;
use SuiteAPI\Exceptions\SuiteApiException;
use SuiteAPI\Exceptions\ValidationException;

/**
 * Email Service
 *
 * Provides business logic for managing SuiteCRM Emails.
 * Includes email tracking, archiving, and communication history.
 */
class EmailService extends BaseSuiteCrmService
{
    /**
     * Required fields for email creation
     *
     * @var array
     */
    protected $requiredFields = [
        'name'
    ];

    /**
     * Validation rules for email fields
     *
     * @var array
     */
    protected $validationRules = [
        'name' => [
            'max_length' => 255
        ],
        'description' => [
            'max_length' => 65535
        ],
        'from_addr' => [
            'max_length' => 255,
            'email' => true
        ],
        'to_addrs' => [
            'max_length' => 255
        ],
        'cc_addrs' => [
            'max_length' => 255
        ],
        'bcc_addrs' => [
            'max_length' => 255
        ],
        'subject' => [
            'max_length' => 255
        ],
        'body' => [
            'max_length' => 65535
        ],
        'status' => [
            'in' => ['sent', 'received', 'draft', 'archived']
        ],
        'type' => [
            'in' => ['inbound', 'outbound', 'archived', 'draft']
        ],
        'parent_type' => [
            'in' => [
                'Accounts', 'Contacts', 'Opportunities', 'Leads', 'Cases',
                'Bugs', 'Projects', 'ProjectTask', 'Tasks'
            ]
        ],
        'message_id' => [
            'max_length' => 255
        ],
        'reply_to_addr' => [
            'max_length' => 255,
            'email' => true
        ],
        'intent' => [
            'in' => ['pick', 'see', 'do', 'delegate', 'file', 'bounce']
        ],
        'mailbox_id' => [
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
        parent::__construct($api, 'Emails');
    }

    /**
     * Create a new email
     *
     * @param array $emailData Email data
     * @return string|null Created email ID
     * @throws ValidationException
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-041
     */
    public function createEmail(array $emailData): ?string
    {
        // Set default status if not provided
        if (!isset($emailData['status'])) {
            $emailData['status'] = 'draft';
        }

        // Set default type if not provided
        if (!isset($emailData['type'])) {
            $emailData['type'] = 'outbound';
        }

        return $this->create($emailData);
    }

    /**
     * Log a sent email
     *
     * @param array $emailData Email data
     * @return string|null Created email ID
     * @throws ValidationException
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-041
     */
    public function logSentEmail(array $emailData): ?string
    {
        // Set status to sent for logged emails
        $emailData['status'] = 'sent';
        $emailData['type'] = 'outbound';

        // Set sent date if not provided
        if (!isset($emailData['date_sent'])) {
            $emailData['date_sent'] = date('Y-m-d H:i:s');
        }

        return $this->create($emailData);
    }

    /**
     * Log a received email
     *
     * @param array $emailData Email data
     * @return string|null Created email ID
     * @throws ValidationException
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-041
     */
    public function logReceivedEmail(array $emailData): ?string
    {
        // Set status to received for logged emails
        $emailData['status'] = 'received';
        $emailData['type'] = 'inbound';

        // Set received date if not provided
        if (!isset($emailData['date_received'])) {
            $emailData['date_received'] = date('Y-m-d H:i:s');
        }

        return $this->create($emailData);
    }

    /**
     * Update an existing email
     *
     * @param string $emailId Email ID
     * @param array $emailData Updated email data
     * @return bool True on success
     * @throws ValidationException
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-041
     */
    public function updateEmail(string $emailId, array $emailData): bool
    {
        return $this->update($emailId, $emailData);
    }

    /**
     * Find email by ID
     *
     * @param string $emailId Email ID
     * @return array|null Email data or null if not found
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-041
     */
    public function findEmailById(string $emailId): ?array
    {
        return $this->findById($emailId);
    }

    /**
     * Find emails by date range
     *
     * @param string $startDate Start date (Y-m-d)
     * @param string $endDate End date (Y-m-d)
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of matching emails
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-041
     */
    public function findEmailsByDateRange(string $startDate, string $endDate, int $limit = 20, int $offset = 0): array
    {
        return $this->search(
            [
                'date_entered' => ['operator' => 'between', 'value' => [$startDate . ' 00:00:00', $endDate . ' 23:59:59']]
            ],
            [],
            $limit,
            $offset
        );
    }

    /**
     * Find emails by status
     *
     * @param string $status Email status
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of matching emails
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-041
     */
    public function findEmailsByStatus(string $status, int $limit = 20, int $offset = 0): array
    {
        return $this->search(
            ['status' => $status],
            [],
            $limit,
            $offset
        );
    }

    /**
     * Find emails by type
     *
     * @param string $type Email type (inbound/outbound)
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of matching emails
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-041
     */
    public function findEmailsByType(string $type, int $limit = 20, int $offset = 0): array
    {
        return $this->search(
            ['type' => $type],
            [],
            $limit,
            $offset
        );
    }

    /**
     * Find emails by assigned user
     *
     * @param string $userId User ID
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of matching emails
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-041
     */
    public function findEmailsByAssignedUser(string $userId, int $limit = 20, int $offset = 0): array
    {
        return $this->search(
            ['assigned_user_id' => $userId],
            [],
            $limit,
            $offset
        );
    }

    /**
     * Find emails by subject
     *
     * @param string $subject Subject to search for
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of matching emails
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-041
     */
    public function findEmailsBySubject(string $subject, int $limit = 20, int $offset = 0): array
    {
        return $this->search(
            ['subject' => ['operator' => 'contains', 'value' => $subject]],
            [],
            $limit,
            $offset
        );
    }

    /**
     * Find emails from specific sender
     *
     * @param string $fromAddr Sender email address
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of matching emails
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-041
     */
    public function findEmailsFromSender(string $fromAddr, int $limit = 20, int $offset = 0): array
    {
        return $this->search(
            ['from_addr' => $fromAddr],
            [],
            $limit,
            $offset
        );
    }

    /**
     * Find emails to specific recipient
     *
     * @param string $toAddr Recipient email address
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of matching emails
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-041
     */
    public function findEmailsToRecipient(string $toAddr, int $limit = 20, int $offset = 0): array
    {
        return $this->search(
            ['to_addrs' => ['operator' => 'contains', 'value' => $toAddr]],
            [],
            $limit,
            $offset
        );
    }

    /**
     * Archive email
     *
     * @param string $emailId Email ID
     * @return bool True on success
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-041
     */
    public function archiveEmail(string $emailId): bool
    {
        return $this->update($emailId, ['status' => 'archived']);
    }

    /**
     * Mark email as read
     *
     * @param string $emailId Email ID
     * @return bool True on success
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-041
     */
    public function markAsRead(string $emailId): bool
    {
        return $this->update($emailId, ['status' => 'read']);
    }

    /**
     * Get email statistics
     *
     * @return array Email statistics
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-041
     */
    public function getEmailStatistics(): array
    {
        $emails = $this->search([], ['status', 'type'], 1000);

        $stats = [
            'total' => count($emails),
            'by_status' => [],
            'by_type' => [],
            'sent' => 0,
            'received' => 0,
            'draft' => 0,
            'archived' => 0,
            'inbound' => 0,
            'outbound' => 0
        ];

        foreach ($emails as $email) {
            $status = $email['status'] ?? 'Unknown';
            $type = $email['type'] ?? 'Unknown';

            // Count by status
            if (!isset($stats['by_status'][$status])) {
                $stats['by_status'][$status] = 0;
            }
            $stats['by_status'][$status]++;

            // Count by type
            if (!isset($stats['by_type'][$type])) {
                $stats['by_type'][$type] = 0;
            }
            $stats['by_type'][$type]++;

            // Count specific statuses
            switch ($status) {
                case 'sent':
                    $stats['sent']++;
                    break;
                case 'received':
                    $stats['received']++;
                    break;
                case 'draft':
                    $stats['draft']++;
                    break;
                case 'archived':
                    $stats['archived']++;
                    break;
            }

            // Count by direction
            if ($type === 'inbound') {
                $stats['inbound']++;
            } elseif ($type === 'outbound') {
                $stats['outbound']++;
            }
        }

        return $stats;
    }

    /**
     * Preprocess email data
     *
     * @param array $data Raw email data
     * @return array Processed email data
     */
    protected function preprocessData(array $data): array
    {
        $processed = parent::preprocessData($data);

        // Validate email addresses
        $emailFields = ['from_addr', 'reply_to_addr'];
        foreach ($emailFields as $field) {
            if (isset($processed[$field]) && !empty($processed[$field])) {
                if (!filter_var($processed[$field], FILTER_VALIDATE_EMAIL)) {
                    throw new ValidationException(
                        "Invalid email address format for {$field}",
                        [$field => 'Must be a valid email address']
                    );
                }
            }
        }

        // Ensure date fields are properly formatted
        $dateFields = ['date_sent', 'date_received'];
        foreach ($dateFields as $field) {
            if (isset($processed[$field]) && !empty($processed[$field])) {
                $processed[$field] = $this->normalizeDateTime($processed[$field]);
            }
        }

        return $processed;
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

        // Try to parse other formats
        $timestamp = strtotime($dateTime);
        if ($timestamp) {
            return date('Y-m-d H:i:s', $timestamp);
        }

        // Return original if we can't parse it
        return $dateTime;
    }
}