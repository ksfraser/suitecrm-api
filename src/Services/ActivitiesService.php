<?php
/**
 * Activities Service
 *
 * Service for managing SuiteCRM Activities module.
 * Provides unified access to Tasks, Meetings, Calls, and other activities.
 *
 * @package SuiteAPI
 * @subpackage Services
 * @author AI Assistant
 * @since 1.0.0
 *
 * @requirement REQ-SUITE-021: Activities Module Support
 * @requirement REQ-SUITE-034: Unified Activity Management
 *
 * UML Class Diagram:
 * ```
 * +---------------------+
 * |  ActivitiesService  |
 * +---------------------+
 * | -api: SuiteCrmApiInterface |
 * | -activityServices   |
 * +---------------------+
 * | +getAllActivities() |
 * | +getActivitiesByDate()|
 * | +getActivitiesByUser()|
 * | +getOverdueActivities()|
 * | +getUpcomingActivities()|
 * | +getActivityStats() |
 * | +searchActivities() |
 * +---------------------+
 *           ^
 *           |
 * +---------------------+
 * | BaseSuiteCrmService|
 * +---------------------+
 * ```
 */

namespace Ksfraser\Ksfraser\SuiteAPI\Services;

use Ksfraser\SuiteAPI\Interfaces\SuiteCrmApiInterface;
use Ksfraser\SuiteAPI\Exceptions\SuiteApiException;
use Ksfraser\SuiteAPI\Exceptions\ValidationException;

/**
 * Activities Service
 *
 * Provides unified access to all SuiteCRM activity types.
 */
class ActivitiesService extends BaseSuiteCrmService
{
    /**
     * Activity service instances
     *
     * @var array
     */
    private $activityServices;

    /**
     * Activity types supported
     *
     * @var array
     */
    private $activityTypes = ['Task', 'Meeting', 'Call', 'Event'];

    /**
     * Constructor
     *
     * @param SuiteCrmApiInterface $api SuiteCRM API instance
     */
    public function __construct(SuiteCrmApiInterface $api)
    {
        parent::__construct($api, 'Activities');

        // Initialize activity services
        $this->activityServices = [
            'Task' => new TaskService($api),
            'Meeting' => new MeetingService($api),
            'Call' => new CallService($api),
            'Event' => new EventService($api)
        ];
    }

    /**
     * Get all activities across all types
     *
     * @param array $criteria Search criteria
     * @param int $limit Maximum results per type
     * @param int $offset Result offset
     * @return array Combined activities from all types
     * @throws SuiteApiException
     */
    public function getAllActivities(array $criteria = [], int $limit = 20, int $offset = 0): array
    {
        $allActivities = [];

        foreach ($this->activityServices as $type => $service) {
            try {
                $activities = $service->search($criteria, [], $limit, $offset);

                // Add activity type to each record
                foreach ($activities as &$activity) {
                    $activity['activity_type'] = $type;
                }

                $allActivities = array_merge($allActivities, $activities);
            } catch (SuiteApiException $e) {
                // Log error but continue with other activity types
                // In a real implementation, you'd use a logger
                continue;
            }
        }

        // Sort by date (assuming all activities have a date field)
        usort($allActivities, function($a, $b) {
            $dateA = $this->getActivityDate($a);
            $dateB = $this->getActivityDate($b);
            return strtotime($dateB) <=> strtotime($dateA); // Descending order
        });

        return array_slice($allActivities, $offset, $limit);
    }

    /**
     * Get activities by date range
     *
     * @param string $startDate Start date (YYYY-MM-DD)
     * @param string $endDate End date (YYYY-MM-DD)
     * @param array $criteria Additional criteria
     * @return array Activities in date range
     * @throws SuiteApiException
     */
    public function getActivitiesByDate(string $startDate, string $endDate, array $criteria = []): array
    {
        $activities = [];

        foreach ($this->activityServices as $type => $service) {
            try {
                // Use the date range search methods from ActivityService
                if ($service instanceof ActivityService) {
                    $typeActivities = $service->findActivitiesByDateRange($startDate, $endDate);
                } else {
                    // For services without date range method, use general search
                    $dateCriteria = array_merge($criteria, [
                        'date_start' => ['gte' => $startDate, 'lte' => $endDate]
                    ]);
                    $typeActivities = $service->search($dateCriteria);
                }

                foreach ($typeActivities as &$activity) {
                    $activity['activity_type'] = $type;
                }

                $activities = array_merge($activities, $typeActivities);
            } catch (SuiteApiException $e) {
                continue;
            }
        }

        return $activities;
    }

    /**
     * Get activities assigned to a specific user
     *
     * @param string $userId User ID
     * @param array $criteria Additional criteria
     * @param int $limit Maximum results
     * @return array User's activities
     * @throws SuiteApiException
     */
    public function getActivitiesByUser(string $userId, array $criteria = [], int $limit = 50): array
    {
        $userCriteria = array_merge($criteria, ['assigned_user_id' => $userId]);
        return $this->getAllActivities($userCriteria, $limit);
    }

    /**
     * Get overdue activities
     *
     * @param string $currentDate Current date (YYYY-MM-DD)
     * @param array $criteria Additional criteria
     * @return array Overdue activities
     * @throws SuiteApiException
     */
    public function getOverdueActivities(string $currentDate = null, array $criteria = []): array
    {
        if (!$currentDate) {
            $currentDate = date('Y-m-d');
        }

        $overdueActivities = [];

        foreach ($this->activityServices as $type => $service) {
            try {
                if ($service instanceof ActivityService) {
                    // Use ActivityService's date range method for past activities
                    $activities = $service->findActivitiesByDateRange('1970-01-01', $currentDate);

                    // Filter for incomplete activities
                    $incompleteActivities = array_filter($activities, function($activity) {
                        return !in_array($activity['status'] ?? '', ['Completed', 'Held', 'Done']);
                    });

                    foreach ($incompleteActivities as &$activity) {
                        $activity['activity_type'] = $type;
                    }

                    $overdueActivities = array_merge($overdueActivities, $incompleteActivities);
                }
            } catch (SuiteApiException $e) {
                continue;
            }
        }

        return $overdueActivities;
    }

    /**
     * Get upcoming activities
     *
     * @param string $currentDate Current date (YYYY-MM-DD)
     * @param int $daysAhead Number of days to look ahead
     * @param array $criteria Additional criteria
     * @return array Upcoming activities
     * @throws SuiteApiException
     */
    public function getUpcomingActivities(string $currentDate = null, int $daysAhead = 7, array $criteria = []): array
    {
        if (!$currentDate) {
            $currentDate = date('Y-m-d');
        }

        $endDate = date('Y-m-d', strtotime($currentDate . " +{$daysAhead} days"));

        return $this->getActivitiesByDate($currentDate, $endDate, $criteria);
    }

    /**
     * Get activity statistics across all types
     *
     * @param array $criteria Filter criteria
     * @return array Activity statistics
     * @throws SuiteApiException
     */
    public function getActivityStats(array $criteria = []): array
    {
        $stats = [
            'total_activities' => 0,
            'by_type' => [],
            'by_status' => [],
            'overdue' => 0,
            'completed' => 0,
            'in_progress' => 0
        ];

        foreach ($this->activityServices as $type => $service) {
            try {
                $activities = $service->search($criteria, ['status'], 1000);

                $stats['by_type'][$type] = count($activities);
                $stats['total_activities'] += count($activities);

                foreach ($activities as $activity) {
                    $status = $activity['status'] ?? 'Unknown';

                    if (!isset($stats['by_status'][$status])) {
                        $stats['by_status'][$status] = 0;
                    }
                    $stats['by_status'][$status]++;

                    // Categorize by completion status
                    if (in_array($status, ['Completed', 'Held', 'Done'])) {
                        $stats['completed']++;
                    } elseif (in_array($status, ['In Progress', 'Planned', 'Not Started'])) {
                        $stats['in_progress']++;
                    }
                }
            } catch (SuiteApiException $e) {
                continue;
            }
        }

        // Calculate overdue (this is a simplified calculation)
        $overdueActivities = $this->getOverdueActivities();
        $stats['overdue'] = count($overdueActivities);

        return $stats;
    }

    /**
     * Search activities across all types
     *
     * @param string $query Search query
     * @param array $criteria Additional criteria
     * @param int $limit Maximum results
     * @return array Search results
     * @throws SuiteApiException
     */
    public function searchActivities(string $query, array $criteria = [], int $limit = 20): array
    {
        $results = [];

        foreach ($this->activityServices as $type => $service) {
            try {
                $searchCriteria = array_merge($criteria, [
                    'name' => ['contains' => $query]
                ]);

                $activities = $service->search($searchCriteria, [], $limit);

                foreach ($activities as &$activity) {
                    $activity['activity_type'] = $type;
                }

                $results = array_merge($results, $activities);
            } catch (SuiteApiException $e) {
                continue;
            }
        }

        return array_slice($results, 0, $limit);
    }

    /**
     * Create a new activity
     *
     * @param string $activityType Type of activity ('Task', 'Meeting', 'Call', 'Event')
     * @param array $data Activity data
     * @return string|null Created activity ID
     * @throws ValidationException
     * @throws SuiteApiException
     */
    public function createActivity(string $activityType, array $data): ?string
    {
        if (!isset($this->activityServices[$activityType])) {
            throw new ValidationException("Unsupported activity type: {$activityType}");
        }

        $service = $this->activityServices[$activityType];
        $method = 'create' . $activityType;

        return $service->$method($data);
    }

    /**
     * Update an existing activity
     *
     * @param string $activityType Type of activity
     * @param string $activityId Activity ID
     * @param array $data Updated data
     * @return bool True on success
     * @throws ValidationException
     * @throws SuiteApiException
     */
    public function updateActivity(string $activityType, string $activityId, array $data): bool
    {
        if (!isset($this->activityServices[$activityType])) {
            throw new ValidationException("Unsupported activity type: {$activityType}");
        }

        $service = $this->activityServices[$activityType];
        $method = 'update' . $activityType;

        return $service->$method($activityId, $data);
    }

    /**
     * Delete an activity
     *
     * @param string $activityType Type of activity
     * @param string $activityId Activity ID
     * @return bool True on success
     * @throws ValidationException
     * @throws SuiteApiException
     */
    public function deleteActivity(string $activityType, string $activityId): bool
    {
        if (!isset($this->activityServices[$activityType])) {
            throw new ValidationException("Unsupported activity type: {$activityType}");
        }

        $service = $this->activityServices[$activityType];
        $method = 'delete' . $activityType;

        return $service->$method($activityId);
    }

    /**
     * Get activity by ID and type
     *
     * @param string $activityType Type of activity
     * @param string $activityId Activity ID
     * @return array|null Activity data
     * @throws ValidationException
     * @throws SuiteApiException
     */
    public function getActivityById(string $activityType, string $activityId): ?array
    {
        if (!isset($this->activityServices[$activityType])) {
            throw new ValidationException("Unsupported activity type: {$activityType}");
        }

        $service = $this->activityServices[$activityType];
        $method = 'find' . $activityType . 'ById';

        $activity = $service->$method($activityId);
        if ($activity) {
            $activity['activity_type'] = $activityType;
        }

        return $activity;
    }

    /**
     * Extract date from activity for sorting
     *
     * @param array $activity Activity data
     * @return string Date string
     */
    private function getActivityDate(array $activity): string
    {
        // Try different date fields that might exist
        $dateFields = ['date_start', 'date_due', 'date_entered', 'date_modified'];

        foreach ($dateFields as $field) {
            if (isset($activity[$field]) && !empty($activity[$field])) {
                return $activity[$field];
            }
        }

        return '1970-01-01'; // Default fallback
    }

    /**
     * Get supported activity types
     *
     * @return array List of supported activity types
     */
    public function getSupportedActivityTypes(): array
    {
        return $this->activityTypes;
    }

    /**
     * Get relationship fields for validation
     *
     * @return array Map of field names to module names
     */
    protected function getRelationshipFields(): array
    {
        // Activities service doesn't create records directly
        return [];
    }
}