<?php
/**
 * Case Service
 *
 * Business logic for SuiteCRM Case operations.
 * Handles case creation, updates, status management, and customer support workflows.
 *
 * @package SuiteAPI
 * @subpackage Services
 * @author AI Assistant
 * @since 1.0.0
 *
 * @requirement REQ-SUITE-023: Case Service Implementation
 * @requirement REQ-SUITE-024: Case Validation Rules
 *
 * UML Class Diagram:
 * ```
 * +---------------------+
 * |     CaseService     |
 * +---------------------+
 * | -requiredFields     |
 * | -validationRules    |
 * +---------------------+
 * | +createCase()       |
 * | +updateCase()       |
 * | +findCasesByStatus()|
 * | +escalateCase()     |
 * +---------------------+
 * ```
 */

namespace SuiteAPI\Services;

use SuiteAPI\Interfaces\SuiteCrmApiInterface;
use SuiteAPI\Exceptions\SuiteApiException;
use SuiteAPI\Exceptions\ValidationException;

/**
 * Case Service
 *
 * Provides business logic for managing SuiteCRM Cases.
 * Includes validation, data transformation, and case management methods.
 */
class CaseService extends BaseSuiteCrmService
{
    /**
     * Required fields for case creation
     *
     * @var array
     */
    protected $requiredFields = [
        'name'
    ];

    /**
     * Validation rules for case fields
     *
     * @var array
     */
    protected $validationRules = [
        'name' => [
            'max_length' => 255
        ],
        'status' => [
            'in' => [
                'New', 'Assigned', 'Closed', 'Pending Input', 'Rejected',
                'Duplicate', 'Resolved', 'Out of Date'
            ]
        ],
        'priority' => [
            'in' => ['P1', 'P2', 'P3', 'P4']
        ],
        'type' => [
            'in' => [
                'Administration', 'Product', 'User', 'Other'
            ]
        ],
        'description' => [
            'max_length' => 65535
        ],
        'resolution' => [
            'max_length' => 65535
        ],
        'work_log' => [
            'max_length' => 65535
        ],
        'account_id' => [
            // Should be a valid account ID
        ],
        'contact_id' => [
            // Should be a valid contact ID
        ],
        'assigned_user_id' => [
            // Should be a valid user ID
        ]
    ];

    /**
     * Constructor
     *
     * @param SuiteCrmApiInterface $api SuiteCRM API instance
     */
    public function __construct(SuiteCrmApiInterface $api)
    {
        parent::__construct($api, 'Cases');
    }

    /**
     * Create a new case
     *
     * @param array $caseData Case data
     * @return string|null Created case ID
     * @throws ValidationException
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-023
     */
    public function createCase(array $caseData): ?string
    {
        // Set default status if not provided
        if (!isset($caseData['status'])) {
            $caseData['status'] = 'New';
        }

        // Set default priority if not provided
        if (!isset($caseData['priority'])) {
            $caseData['priority'] = 'P3';
        }

        return $this->create($caseData);
    }

    /**
     * Update an existing case
     *
     * @param string $caseId Case ID
     * @param array $caseData Updated case data
     * @return bool True on success
     * @throws ValidationException
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-023
     */
    public function updateCase(string $caseId, array $caseData): bool
    {
        return $this->update($caseId, $caseData);
    }

    /**
     * Find case by ID
     *
     * @param string $caseId Case ID
     * @return array|null Case data or null if not found
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-023
     */
    public function findCaseById(string $caseId): ?array
    {
        return $this->findById($caseId);
    }

    /**
     * Find cases by status
     *
     * @param string $status Case status
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of matching cases
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-023
     */
    public function findCasesByStatus(string $status, int $limit = 20, int $offset = 0): array
    {
        return $this->search(
            ['status' => $status],
            [],
            $limit,
            $offset
        );
    }

    /**
     * Find cases by priority
     *
     * @param string $priority Case priority
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of matching cases
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-023
     */
    public function findCasesByPriority(string $priority, int $limit = 20, int $offset = 0): array
    {
        return $this->search(
            ['priority' => $priority],
            [],
            $limit,
            $offset
        );
    }

    /**
     * Find cases by account
     *
     * @param string $accountId Account ID
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of matching cases
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-023
     */
    public function findCasesByAccount(string $accountId, int $limit = 20, int $offset = 0): array
    {
        return $this->search(
            ['account_id' => $accountId],
            [],
            $limit,
            $offset
        );
    }

    /**
     * Find cases by assigned user
     *
     * @param string $userId User ID
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of matching cases
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-023
     */
    public function findCasesByAssignedUser(string $userId, int $limit = 20, int $offset = 0): array
    {
        return $this->search(
            ['assigned_user_id' => $userId],
            [],
            $limit,
            $offset
        );
    }

    /**
     * Find open cases
     *
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of open cases
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-023
     */
    public function findOpenCases(int $limit = 20, int $offset = 0): array
    {
        return $this->search(
            ['status' => ['operator' => 'not_in', 'value' => ['Closed', 'Rejected', 'Duplicate']]],
            [],
            $limit,
            $offset
        );
    }

    /**
     * Escalate case
     *
     * @param string $caseId Case ID
     * @param string $reason Escalation reason
     * @param string $newPriority New priority level
     * @return bool True on success
     * @throws ValidationException
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-023
     */
    public function escalateCase(string $caseId, string $reason, string $newPriority = 'P1'): bool
    {
        $case = $this->findById($caseId);

        if (!$case) {
            throw new SuiteApiException("Case not found: {$caseId}");
        }

        $updateData = [
            'priority' => $newPriority,
            'status' => 'Pending Input',
            'work_log' => ($case['work_log'] ?? '') . "\n\n[ESCALATION] " . date('Y-m-d H:i:s') . ": {$reason}"
        ];

        return $this->update($caseId, $updateData);
    }

    /**
     * Close case
     *
     * @param string $caseId Case ID
     * @param string $resolution Resolution description
     * @param string $status Close status (default: 'Closed')
     * @return bool True on success
     * @throws ValidationException
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-023
     */
    public function closeCase(string $caseId, string $resolution, string $status = 'Closed'): bool
    {
        $updateData = [
            'status' => $status,
            'resolution' => $resolution,
            'work_log' => ($this->findById($caseId)['work_log'] ?? '') . "\n\n[CLOSED] " . date('Y-m-d H:i:s') . ": {$resolution}"
        ];

        return $this->update($caseId, $updateData);
    }

    /**
     * Get case statistics
     *
     * @param string|null $userId User ID (null for all users)
     * @return array Case statistics
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-023
     */
    public function getCaseStatistics(?string $userId = null): array
    {
        $criteria = [];
        if ($userId) {
            $criteria['assigned_user_id'] = $userId;
        }

        $cases = $this->search($criteria, ['status', 'priority'], 1000);

        $stats = [
            'total' => count($cases),
            'by_status' => [],
            'by_priority' => [],
            'open' => 0,
            'closed' => 0
        ];

        foreach ($cases as $case) {
            $status = $case['status'] ?? 'Unknown';
            $priority = $case['priority'] ?? 'Unknown';

            // Count by status
            if (!isset($stats['by_status'][$status])) {
                $stats['by_status'][$status] = 0;
            }
            $stats['by_status'][$status]++;

            // Count by priority
            if (!isset($stats['by_priority'][$priority])) {
                $stats['by_priority'][$priority] = 0;
            }
            $stats['by_priority'][$priority]++;

            // Count open vs closed
            if (in_array($status, ['New', 'Assigned', 'Pending Input'])) {
                $stats['open']++;
            } elseif (in_array($status, ['Closed', 'Rejected', 'Duplicate', 'Resolved'])) {
                $stats['closed']++;
            }
        }

        return $stats;
    }

    /**
     * Preprocess case data
     *
     * @param array $data Raw case data
     * @return array Processed case data
     */
    protected function preprocessData(array $data): array
    {
        $processed = parent::preprocessData($data);

        // Auto-generate case number if not provided
        if (!isset($processed['case_number']) && isset($processed['id'])) {
            // In SuiteCRM, case numbers are auto-generated, so we don't need to set this
        }

        // Ensure work log entries are timestamped
        if (isset($processed['work_log']) && !empty($processed['work_log'])) {
            $processed['work_log'] = date('Y-m-d H:i:s') . ": " . $processed['work_log'];
        }

        return $processed;
    }
}