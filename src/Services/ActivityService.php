<?php
/**
 * Activity Service Base Class
 *
 * Base class for SuiteCRM activity-related modules (Meetings, Calls, Tasks, Events).
 * Provides common scheduling, status management, and relationship functionality.
 *
 * @package SuiteAPI
 * @subpackage Services
 * @author AI Assistant
 * @since 1.0.0
 *
 * @requirement REQ-SUITE-045: Activity Service Base Class
 * @requirement REQ-SUITE-046: Common Activity Validation Rules
 *
 * UML Class Diagram:
 * ```
 * +---------------------+
 * |  ActivityService    |
 * +---------------------+
 * | -activityFields     |
 * | -schedulingRules    |
 * +---------------------+
 * | +scheduleActivity() |
 * | +rescheduleActivity()|
 * | +cancelActivity()   |
 * | +completeActivity() |
 * +---------------------+
 *           ^
 *           |
 *   +-------+-------+
 *   |       |       |
 * Meeting Call  Task  Event
 * Service Service Service Service
 * ```
 */

namespace Ksfraser\Ksfraser\SuiteAPI\Services;

use Ksfraser\SuiteAPI\Interfaces\SuiteCrmApiInterface;
use Ksfraser\SuiteAPI\Exceptions\SuiteApiException;
use Ksfraser\SuiteAPI\Exceptions\ValidationException;

/**
 * Activity Service Base Class
 *
 * Provides common functionality for activity-related SuiteCRM modules.
 * Handles scheduling, status management, reminders, and parent relationships.
 */
abstract class ActivityService extends BaseSuiteCrmService
{
    /**
     * Common activity fields that all activity modules should have
     *
     * @var array
     */
    protected $activityFields = [
        'name',
        'description',
        'date_start',
        'date_end',
        'duration_hours',
        'duration_minutes',
        'status',
        'parent_type',
        'parent_id',
        'assigned_user_id',
        'reminder_time',
        'email_reminder_time'
    ];

    /**
     * Common validation rules for activity fields
     *
     * @var array
     */
    protected $activityValidationRules = [
        'name' => [
            'max_length' => 255
        ],
        'description' => [
            'max_length' => 65535
        ],
        'status' => [
            'in' => ['Planned', 'Held', 'Not Held', 'Cancelled']
        ],
        'parent_type' => [
            'in' => [
                'Accounts', 'Contacts', 'Opportunities', 'Leads', 'Cases',
                'Bugs', 'Projects', 'ProjectTask', 'Tasks', 'Meetings', 'Calls', 'Emails'
            ]
        ],
        'duration_hours' => [
            'min' => 0,
            'max' => 24
        ],
        'duration_minutes' => [
            'min' => 0,
            'max' => 59
        ],
        'reminder_time' => [
            'min' => 0
        ],
        'email_reminder_time' => [
            'min' => 0
        ]
    ];

    /**
     * Constructor
     *
     * @param SuiteCrmApiInterface $api SuiteCRM API instance
     * @param string $moduleName Module name for this activity type
     */
    public function __construct(SuiteCrmApiInterface $api, string $moduleName)
    {
        parent::__construct($api, $moduleName);
    }

    /**
     * Schedule a new activity
     *
     * @param array $activityData Activity data including scheduling information
     * @return string|null Created activity ID
     * @throws ValidationException
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-045
     */
    public function scheduleActivity(array $activityData): ?string
    {
        // Set default status if not provided
        if (!isset($activityData['status'])) {
            $activityData['status'] = 'Planned';
        }

        // Validate scheduling data
        $this->validateSchedulingData($activityData);

        return $this->create($activityData);
    }

    /**
     * Reschedule an existing activity
     *
     * @param string $activityId Activity ID
     * @param string $newStartDate New start date/time
     * @param string $newEndDate New end date/time
     * @param string|null $reason Rescheduling reason
     * @return bool True on success
     * @throws ValidationException
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-045
     */
    public function rescheduleActivity(string $activityId, string $newStartDate, string $newEndDate, ?string $reason = null): bool
    {
        $updateData = [
            'date_start' => $newStartDate,
            'date_end' => $newEndDate
        ];

        if ($reason) {
            $existingDescription = $this->findById($activityId)['description'] ?? '';
            $updateData['description'] = $existingDescription . "\n\n[RESCHEDULED] " . date('Y-m-d H:i:s') . ": {$reason}";
        }

        return $this->update($activityId, $updateData);
    }

    /**
     * Cancel an activity
     *
     * @param string $activityId Activity ID
     * @param string $reason Cancellation reason
     * @return bool True on success
     * @throws ValidationException
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-045
     */
    public function cancelActivity(string $activityId, string $reason): bool
    {
        $updateData = [
            'status' => 'Cancelled',
            'description' => ($this->findById($activityId)['description'] ?? '') . "\n\n[CANCELLED] " . date('Y-m-d H:i:s') . ": {$reason}"
        ];

        return $this->update($activityId, $updateData);
    }

    /**
     * Mark activity as completed
     *
     * @param string $activityId Activity ID
     * @return bool True on success
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-045
     */
    public function completeActivity(string $activityId): bool
    {
        return $this->update($activityId, ['status' => 'Held']);
    }

    /**
     * Find activities by date range
     *
     * @param string $startDate Start date (Y-m-d)
     * @param string $endDate End date (Y-m-d)
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of matching activities
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-045
     */
    public function findActivitiesByDateRange(string $startDate, string $endDate, int $limit = 20, int $offset = 0): array
    {
        return $this->search(
            [
                'date_start' => ['operator' => 'between', 'value' => [$startDate . ' 00:00:00', $endDate . ' 23:59:59']]
            ],
            [],
            $limit,
            $offset
        );
    }

    /**
     * Find activities by status
     *
     * @param string $status Activity status
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of matching activities
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-045
     */
    public function findActivitiesByStatus(string $status, int $limit = 20, int $offset = 0): array
    {
        return $this->search(
            ['status' => $status],
            [],
            $limit,
            $offset
        );
    }

    /**
     * Find activities by assigned user
     *
     * @param string $userId User ID
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of matching activities
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-045
     */
    public function findActivitiesByAssignedUser(string $userId, int $limit = 20, int $offset = 0): array
    {
        return $this->search(
            ['assigned_user_id' => $userId],
            [],
            $limit,
            $offset
        );
    }

    /**
     * Find upcoming activities
     *
     * @param int $hours Number of hours to look ahead
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of upcoming activities
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-045
     */
    public function findUpcomingActivities(int $hours = 24, int $limit = 20, int $offset = 0): array
    {
        $endDate = date('Y-m-d H:i:s', strtotime("+{$hours} hours"));

        return $this->search(
            [
                'date_start' => ['operator' => 'greater_than', 'value' => date('Y-m-d H:i:s')],
                'date_start' => ['operator' => 'less_than', 'value' => $endDate],
                'status' => ['operator' => 'not_in', 'value' => ['Held', 'Not Held', 'Cancelled']]
            ],
            [],
            $limit,
            $offset
        );
    }

    /**
     * Find activities for today
     *
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of today's activities
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-045
     */
    public function findTodaysActivities(int $limit = 20, int $offset = 0): array
    {
        $today = date('Y-m-d');

        return $this->search(
            [
                'date_start' => ['operator' => 'between', 'value' => [$today . ' 00:00:00', $today . ' 23:59:59']],
                'status' => ['operator' => 'not_in', 'value' => ['Cancelled']]
            ],
            [],
            $limit,
            $offset
        );
    }

    /**
     * Find activities by parent record
     *
     * @param string $parentType Parent module type
     * @param string $parentId Parent record ID
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of matching activities
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-045
     */
    public function findActivitiesByParent(string $parentType, string $parentId, int $limit = 20, int $offset = 0): array
    {
        return $this->search(
            [
                'parent_type' => $parentType,
                'parent_id' => $parentId
            ],
            [],
            $limit,
            $offset
        );
    }

    /**
     * Get activity statistics
     *
     * @return array Activity statistics
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-045
     */
    public function getActivityStatistics(): array
    {
        $activities = $this->search([], ['status'], 1000);

        $stats = [
            'total' => count($activities),
            'by_status' => [],
            'upcoming' => 0,
            'completed' => 0,
            'cancelled' => 0,
            'overdue' => 0
        ];

        $now = date('Y-m-d H:i:s');

        foreach ($activities as $activity) {
            $status = $activity['status'] ?? 'Unknown';
            $startDate = $activity['date_start'] ?? null;

            // Count by status
            if (!isset($stats['by_status'][$status])) {
                $stats['by_status'][$status] = 0;
            }
            $stats['by_status'][$status]++;

            // Count upcoming vs completed vs cancelled vs overdue
            if ($status === 'Cancelled') {
                $stats['cancelled']++;
            } elseif ($status === 'Held') {
                $stats['completed']++;
            } elseif ($startDate && $startDate > $now) {
                $stats['upcoming']++;
            } elseif ($startDate && $startDate < $now && $status === 'Planned') {
                $stats['overdue']++;
            }
        }

        return $stats;
    }

    /**
     * Validate scheduling data
     *
     * @param array $data Activity data to validate
     * @throws ValidationException
     */
    protected function validateSchedulingData(array $data): void
    {
        // Validate date/time fields
        if (isset($data['date_start']) && isset($data['date_end'])) {
            $startDate = strtotime($data['date_start']);
            $endDate = strtotime($data['date_end']);

            if ($startDate >= $endDate) {
                throw new ValidationException(
                    "Activity end date must be after start date",
                    ['date_end' => 'End date must be after start date']
                );
            }
        }

        // Validate duration fields
        if (isset($data['duration_hours']) && $data['duration_hours'] < 0) {
            throw new ValidationException(
                "Duration hours cannot be negative",
                ['duration_hours' => 'Must be non-negative']
            );
        }

        if (isset($data['duration_minutes']) && ($data['duration_minutes'] < 0 || $data['duration_minutes'] > 59)) {
            throw new ValidationException(
                "Duration minutes must be between 0 and 59",
                ['duration_minutes' => 'Must be between 0 and 59']
            );
        }
    }

    /**
     * Preprocess activity data
     *
     * @param array $data Raw activity data
     * @return array Processed activity data
     */
    protected function preprocessData(array $data): array
    {
        $processed = parent::preprocessData($data);

        // Ensure date/time fields are properly formatted
        $dateTimeFields = ['date_start', 'date_end'];
        foreach ($dateTimeFields as $field) {
            if (isset($processed[$field]) && !empty($processed[$field])) {
                $processed[$field] = $this->normalizeDateTime($processed[$field]);
            }
        }

        // Ensure numeric fields are properly formatted
        $numericFields = ['duration_hours', 'duration_minutes', 'reminder_time', 'email_reminder_time'];
        foreach ($numericFields as $field) {
            if (isset($processed[$field])) {
                $processed[$field] = (int) $processed[$field];
            }
        }

        // Validate scheduling data
        $this->validateSchedulingData($processed);

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