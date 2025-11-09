<?php
/**
 * Task Service
 *
 * Business logic for SuiteCRM Task operations.
 * Handles task creation, updates, status management, and task assignment.
 *
 * @package SuiteAPI
 * @subpackage Services
 * @author AI Assistant
 * @since 1.0.0
 *
 * @requirement REQ-SUITE-025: Task Service Implementation
 * @requirement REQ-SUITE-026: Task Validation Rules
 *
 * UML Class Diagram:
 * ```
 * +---------------------+
 * |     TaskService     |
 * +---------------------+
 * | -requiredFields     |
 * | -validationRules    |
 * +---------------------+
 * | +createTask()       |
 * | +updateTask()       |
 * | +findTasksByStatus()|
 * | +completeTask()     |
 * +---------------------+
 *           ^
 *           |
 *   +-------------+
 *   | ActivityService |
 *   +-------------+
 * ```
 */

namespace Ksfraser\Ksfraser\SuiteAPI\Services;

use Ksfraser\SuiteAPI\Interfaces\SuiteCrmApiInterface;
use Ksfraser\SuiteAPI\Exceptions\SuiteApiException;
use Ksfraser\SuiteAPI\Exceptions\ValidationException;

/**
 * Task Service
 *
 * Provides business logic for managing SuiteCRM Tasks.
 * Includes validation, data transformation, and task management methods.
 */
class TaskService extends ActivityService
{
    /**
     * Required fields for task creation
     *
     * @var array
     */
    protected $requiredFields = [
        'name'
    ];

    /**
     * Validation rules for task fields
     *
     * @var array
     */
    protected $validationRules = [
        'name' => [
            'max_length' => 255
        ],
        'status' => [
            'in' => [
                'Not Started', 'In Progress', 'Completed', 'Pending Input',
                'Deferred', 'Cancelled'
            ]
        ],
        'priority' => [
            'in' => ['High', 'Medium', 'Low']
        ],
        'description' => [
            'max_length' => 65535
        ],
        'date_start' => [
            // Should be a valid date
        ],
        'date_due' => [
            // Should be a valid date
        ],
        'parent_type' => [
            'in' => [
                'Accounts', 'Contacts', 'Opportunities', 'Leads', 'Cases',
                'Bugs', 'Projects', 'ProjectTask'
            ]
        ],
        'contact_id' => [
            // Should be a valid contact ID
        ],
        'parent_id' => [
            // Should be a valid parent record ID
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
        parent::__construct($api, 'Tasks');
    }

    /**
     * Create a new task
     *
     * @param array $taskData Task data
     * @return string|null Created task ID
     * @throws ValidationException
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-025
     */
    public function createTask(array $taskData): ?string
    {
        // Set default status if not provided
        if (!isset($taskData['status'])) {
            $taskData['status'] = 'Not Started';
        }

        // Set default priority if not provided
        if (!isset($taskData['priority'])) {
            $taskData['priority'] = 'Medium';
        }

        return $this->create($taskData);
    }

    /**
     * Update an existing task
     *
     * @param string $taskId Task ID
     * @param array $taskData Updated task data
     * @return bool True on success
     * @throws ValidationException
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-025
     */
    public function updateTask(string $taskId, array $taskData): bool
    {
        return $this->update($taskId, $taskData);
    }

    /**
     * Find task by ID
     *
     * @param string $taskId Task ID
     * @return array|null Task data or null if not found
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-025
     */
    public function findTaskById(string $taskId): ?array
    {
        return $this->findById($taskId);
    }

    /**
     * Find overdue tasks
     *
     * @param string|null $userId User ID (null for all users)
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of overdue tasks
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-025
     */
    public function findOverdueTasks(?string $userId = null, int $limit = 20, int $offset = 0): array
    {
        $criteria = [
            'date_due' => ['operator' => 'less_than', 'value' => date('Y-m-d')],
            'status' => ['operator' => 'not_in', 'value' => ['Completed', 'Cancelled']]
        ];

        if ($userId) {
            $criteria['assigned_user_id'] = $userId;
        }

        return $this->search($criteria, [], $limit, $offset);
    }

    /**
     * Find tasks due today
     *
     * @param string|null $userId User ID (null for all users)
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of tasks due today
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-025
     */
    public function findTasksDueToday(?string $userId = null, int $limit = 20, int $offset = 0): array
    {
        $criteria = [
            'date_due' => date('Y-m-d'),
            'status' => ['operator' => 'not_in', 'value' => ['Completed', 'Cancelled']]
        ];

        if ($userId) {
            $criteria['assigned_user_id'] = $userId;
        }

        return $this->search($criteria, [], $limit, $offset);
    }

    /**
     * Complete a task
     *
     * @param string $taskId Task ID
     * @return bool True on success
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-025
     */
    public function completeTask(string $taskId): bool
    {
        return $this->update($taskId, [
            'status' => 'Completed',
            'date_completed' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Start a task
     *
     * @param string $taskId Task ID
     * @return bool True on success
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-025
     */
    public function startTask(string $taskId): bool
    {
        return $this->update($taskId, [
            'status' => 'In Progress',
            'date_start' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get task statistics
     *
     * @param string|null $userId User ID (null for all users)
     * @return array Task statistics
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-025
     */
    public function getTaskStatistics(?string $userId = null): array
    {
        $criteria = [];
        if ($userId) {
            $criteria['assigned_user_id'] = $userId;
        }

        $tasks = $this->search($criteria, ['status', 'priority'], 1000);

        $stats = [
            'total' => count($tasks),
            'by_status' => [],
            'by_priority' => [],
            'completed' => 0,
            'overdue' => 0
        ];

        $today = date('Y-m-d');

        foreach ($tasks as $task) {
            $status = $task['status'] ?? 'Unknown';
            $priority = $task['priority'] ?? 'Unknown';
            $dateDue = $task['date_due'] ?? null;

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

            // Count completed
            if ($status === 'Completed') {
                $stats['completed']++;
            }

            // Count overdue
            if ($dateDue && $dateDue < $today && $status !== 'Completed' && $status !== 'Cancelled') {
                $stats['overdue']++;
            }
        }

        return $stats;
    }

    /**
     * Preprocess task data
     *
     * @param array $data Raw task data
     * @return array Processed task data
     */
    protected function preprocessData(array $data): array
    {
        $processed = parent::preprocessData($data);

        // Ensure date formats are correct
        $dateFields = ['date_start', 'date_due', 'date_completed'];
        foreach ($dateFields as $field) {
            if (isset($processed[$field]) && !empty($processed[$field])) {
                // Convert to proper datetime format if needed
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
            'parent_id' => 'Accounts' // Can be various parent types
        ];
    }
}