<?php
/**
 * Calendar Service
 *
 * Business logic for SuiteCRM Calendar operations.
 * Handles calendar integration, event synchronization, and scheduling.
 *
 * @package SuiteAPI
 * @subpackage Services
 * @author AI Assistant
 * @since 1.0.0
 *
 * @requirement REQ-SUITE-049: Calendar Service Implementation
 * @requirement REQ-SUITE-050: Calendar Sync Architecture
 *
 * UML Class Diagram:
 * ```
 * +---------------------+
 * |  CalendarService    |
 * +---------------------+
 * | -syncConfig         |
 * | -calendarMappings   |
 * +---------------------+
 * | +syncFromNextcloud()|
 * | +syncToNextcloud()  |
 * | +getCalendarEvents()|
 * | +createCalendarEvent|
 * +---------------------+
 * ```
 */

namespace Ksfraser\Ksfraser\SuiteAPI\Services;

use Ksfraser\SuiteAPI\Interfaces\SuiteCrmApiInterface;
use Ksfraser\SuiteAPI\Exceptions\SuiteApiException;
use Ksfraser\SuiteAPI\Exceptions\ValidationException;
use Exception;

/**
 * Calendar Service
 *
 * Provides business logic for managing SuiteCRM Calendar operations.
 * Includes calendar synchronization with external systems like Nextcloud.
 */
class CalendarService extends BaseSuiteCrmService
{
    /**
     * Required fields for calendar entry creation
     *
     * @var array
     */
    protected $requiredFields = [
        'name',
        'date_start'
    ];

    /**
     * Validation rules for calendar fields
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
        'status' => [
            'in' => ['Planned', 'Held', 'Not Held', 'Cancelled']
        ],
        'parent_type' => [
            'in' => [
                'Accounts', 'Contacts', 'Opportunities', 'Leads', 'Cases',
                'Bugs', 'Projects', 'ProjectTask', 'Tasks', 'Meetings', 'Calls'
            ]
        ],
        'duration_minutes' => [
            'min' => 1,
            'max' => 1440 // 24 hours
        ],
        'location' => [
            'max_length' => 255
        ],
        'reminder_time' => [
            'min' => 0
        ]
    ];

    /**
     * Sync configuration
     *
     * @var array
     */
    private $syncConfig = [
        'nextcloud_url' => '',
        'nextcloud_username' => '',
        'nextcloud_password' => '',
        'sync_direction' => 'bidirectional', // 'from_nextcloud', 'to_nextcloud', 'bidirectional'
        'conflict_resolution' => 'nextcloud_wins', // 'suitecrm_wins', 'nextcloud_wins', 'manual'
        'last_sync' => null
    ];

    /**
     * Calendar mappings for external systems
     *
     * @var array
     */
    private $calendarMappings = [
        'nextcloud' => [
            'meeting' => 'VEVENT',
            'call' => 'VEVENT',
            'task' => 'VTODO',
            'event' => 'VEVENT'
        ]
    ];

    /**
     * Constructor
     *
     * @param SuiteCrmApiInterface $api SuiteCRM API instance
     */
    public function __construct(SuiteCrmApiInterface $api)
    {
        parent::__construct($api, 'Calendar');
    }

    /**
     * Create a calendar entry
     *
     * @param array $calendarData Calendar entry data
     * @return string|null Created calendar entry ID
     * @throws ValidationException
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-049
     */
    public function createCalendarEntry(array $calendarData): ?string
    {
        // Set default status if not provided
        if (!isset($calendarData['status'])) {
            $calendarData['status'] = 'Planned';
        }

        return $this->create($calendarData);
    }

    /**
     * Get calendar events for a date range
     *
     * @param string $startDate Start date (Y-m-d)
     * @param string $endDate End date (Y-m-d)
     * @param string|null $userId User ID (null for current user)
     * @return array Array of calendar events
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-049
     */
    public function getCalendarEvents(string $startDate, string $endDate, ?string $userId = null): array
    {
        $criteria = [
            'date_start' => ['operator' => 'between', 'value' => [$startDate . ' 00:00:00', $endDate . ' 23:59:59']]
        ];

        if ($userId) {
            $criteria['assigned_user_id'] = $userId;
        }

        return $this->search($criteria, [], 100);
    }

    /**
     * Sync calendar from Nextcloud
     *
     * @param string $nextcloudUrl Nextcloud CalDAV URL
     * @param string $username Nextcloud username
     * @param string $password Nextcloud password
     * @param string $calendarId Nextcloud calendar ID
     * @param string $since Last sync timestamp (ISO 8601)
     * @return array Sync results
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-050
     */
    public function syncFromNextcloud(string $nextcloudUrl, string $username, string $password, string $calendarId, string $since): array
    {
        $results = [
            'synced_events' => 0,
            'created_events' => 0,
            'updated_events' => 0,
            'deleted_events' => 0,
            'conflicts' => 0,
            'errors' => []
        ];

        try {
            // NOTE: Actual CalDAV implementation requires external libraries like 'sabre/dav'
            // For now, throw an exception indicating this requirement
            throw new SuiteApiException(
                "CalDAV sync requires external CalDAV library. " .
                "Install 'sabre/dav' or similar CalDAV client library to enable sync functionality."
            );

            // Placeholder for future implementation:
            // $caldav = new CalDAVClient($nextcloudUrl, $username, $password);

            // Get calendar events since last sync
            $externalEvents = $caldav->getEvents($calendarId, $since);

            foreach ($externalEvents as $externalEvent) {
                try {
                    // Map external event to SuiteCRM format
                    $suiteEvent = $this->mapEventFromExternal($externalEvent, 'nextcloud');

                    // Check if event already exists (by external ID or similar matching)
                    $existingEvent = $this->findEventByExternalId($externalEvent['id'], 'nextcloud');

                    if ($existingEvent) {
                        // Check for conflicts
                        if ($this->hasConflicts($existingEvent, $suiteEvent)) {
                            $results['conflicts']++;
                            // Handle conflict based on configuration
                            if ($this->syncConfig['conflict_resolution'] === 'nextcloud_wins') {
                                $this->update($existingEvent['id'], $suiteEvent);
                                $results['updated_events']++;
                            }
                            // If suitecrm_wins, do nothing (keep existing)
                        } else {
                            // No conflict, update
                            $this->update($existingEvent['id'], $suiteEvent);
                            $results['updated_events']++;
                        }
                    } else {
                        // Create new event
                        $this->createCalendarEntry($suiteEvent);
                        $results['created_events']++;
                    }

                    $results['synced_events']++;
                } catch (Exception $e) {
                    $results['errors'][] = "Failed to sync event {$externalEvent['id']}: " . $e->getMessage();
                }
            }

            // Update last sync timestamp
            $this->syncConfig['last_sync'] = date('c');

        } catch (Exception $e) {
            throw new SuiteApiException("CalDAV sync failed: " . $e->getMessage());
        }

        return $results;
    }

    /**
     * Sync calendar to Nextcloud
     *
     * @param string $nextcloudUrl Nextcloud CalDAV URL
     * @param string $username Nextcloud username
     * @param string $password Nextcloud password
     * @param string $calendarId Nextcloud calendar ID
     * @param string $since Last sync timestamp (ISO 8601)
     * @return array Sync results
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-050
     */
    public function syncToNextcloud(string $nextcloudUrl, string $username, string $password, string $calendarId, string $since): array
    {
        $results = [
            'synced_events' => 0,
            'created_events' => 0,
            'updated_events' => 0,
            'deleted_events' => 0,
            'conflicts' => 0,
            'errors' => []
        ];

        try {
            // NOTE: Actual CalDAV implementation requires external libraries like 'sabre/dav'
            // For now, throw an exception indicating this requirement
            throw new SuiteApiException(
                "CalDAV sync requires external CalDAV library. " .
                "Install 'sabre/dav' or similar CalDAV client library to enable sync functionality."
            );

            // Placeholder for future implementation:
            // $caldav = new CalDAVClient($nextcloudUrl, $username, $password);

            // Get SuiteCRM events since last sync
            $suiteEvents = $this->getCalendarEvents($since, date('Y-m-d H:i:s', strtotime('+1 year')), null);

            foreach ($suiteEvents as $suiteEvent) {
                try {
                    // Map SuiteCRM event to external format
                    $externalEvent = $this->mapEventToExternal($suiteEvent, 'nextcloud');

                    // Check if event exists in Nextcloud
                    $existingExternalEvent = $caldav->getEvent($calendarId, $suiteEvent['id']);

                    if ($existingExternalEvent) {
                        // Check for conflicts
                        if ($this->hasExternalConflicts($existingExternalEvent, $externalEvent)) {
                            $results['conflicts']++;
                            // Handle conflict based on configuration
                            if ($this->syncConfig['conflict_resolution'] === 'suitecrm_wins') {
                                $caldav->updateEvent($calendarId, $suiteEvent['id'], $externalEvent);
                                $results['updated_events']++;
                            }
                            // If nextcloud_wins, do nothing (keep external)
                        } else {
                            // No conflict, update
                            $caldav->updateEvent($calendarId, $suiteEvent['id'], $externalEvent);
                            $results['updated_events']++;
                        }
                    } else {
                        // Create new event in Nextcloud
                        $caldav->createEvent($calendarId, $externalEvent);
                        $results['created_events']++;
                    }

                    $results['synced_events']++;
                } catch (Exception $e) {
                    $results['errors'][] = "Failed to sync event {$suiteEvent['id']}: " . $e->getMessage();
                }
            }

            // Update last sync timestamp
            $this->syncConfig['last_sync'] = date('c');

        } catch (Exception $e) {
            throw new SuiteApiException("CalDAV sync to Nextcloud failed: " . $e->getMessage());
        }

        return $results;
    }

    /**
     * Perform bidirectional sync with Nextcloud
     *
     * @param string $nextcloudUrl Nextcloud CalDAV URL
     * @param string $username Nextcloud username
     * @param string $password Nextcloud password
     * @param string $calendarId Nextcloud calendar ID
     * @return array Sync results
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-050
     */
    public function bidirectionalSync(string $nextcloudUrl, string $username, string $password, string $calendarId): array
    {
        $since = $this->syncConfig['last_sync'] ?? date('c', strtotime('-1 day'));

        $fromResults = $this->syncFromNextcloud($nextcloudUrl, $username, $password, $calendarId, $since);
        $toResults = $this->syncToNextcloud($nextcloudUrl, $username, $password, $calendarId, $since);

        $combinedResults = [
            'from_nextcloud' => $fromResults,
            'to_nextcloud' => $toResults,
            'total_synced' => $fromResults['synced_events'] + $toResults['synced_events'],
            'total_created' => $fromResults['created_events'] + $toResults['created_events'],
            'total_updated' => $fromResults['updated_events'] + $toResults['updated_events'],
            'total_deleted' => $fromResults['deleted_events'] + $toResults['deleted_events'],
            'total_conflicts' => $fromResults['conflicts'] + $toResults['conflicts'],
            'total_errors' => count($fromResults['errors']) + count($toResults['errors']),
            'sync_timestamp' => date('c')
        ];

        // Update last sync timestamp
        $this->syncConfig['last_sync'] = $combinedResults['sync_timestamp'];

        return $combinedResults;
    }

    /**
     * Find event by external ID
     *
     * @param string $externalId External system event ID
     * @param string $sourceSystem Source system name
     * @return array|null SuiteCRM event data
     */
    private function findEventByExternalId(string $externalId, string $sourceSystem): ?array
    {
        // Search for events with matching external ID
        // This assumes there's a field or relationship for external IDs
        $criteria = [
            'external_id' => $externalId,
            'external_system' => $sourceSystem
        ];

        $events = $this->search($criteria, [], 1);
        return $events[0] ?? null;
    }

    /**
     * Check if there are conflicts between SuiteCRM and external events
     *
     * @param array $suiteEvent SuiteCRM event
     * @param array $externalEvent Mapped external event
     * @return bool True if conflicts exist
     */
    private function hasConflicts(array $suiteEvent, array $externalEvent): bool
    {
        // Compare key fields for conflicts
        $conflictFields = ['name', 'date_start', 'date_end', 'description'];

        foreach ($conflictFields as $field) {
            if (isset($suiteEvent[$field]) && isset($externalEvent[$field])) {
                // Simple string comparison - could be enhanced with date parsing, etc.
                if ($suiteEvent[$field] !== $externalEvent[$field]) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if there are conflicts between external events
     *
     * @param array $existingExternal Existing external event
     * @param array $newExternal New external event
     * @return bool True if conflicts exist
     */
    private function hasExternalConflicts(array $existingExternal, array $newExternal): bool
    {
        // Compare key fields for conflicts
        $conflictFields = ['summary', 'start', 'end', 'description'];

        foreach ($conflictFields as $field) {
            if (isset($existingExternal[$field]) && isset($newExternal[$field])) {
                if ($existingExternal[$field] !== $newExternal[$field]) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Configure sync settings
     *
     * @param array $config Sync configuration
     * @return void
     *
     * @requirement REQ-SUITE-050
     */
    public function configureSync(array $config): void
    {
        $this->syncConfig = array_merge($this->syncConfig, $config);
    }

    /**
     * Get sync configuration
     *
     * @return array Current sync configuration
     *
     * @requirement REQ-SUITE-050
     */
    public function getSyncConfig(): array
    {
        return $this->syncConfig;
    }

    /**
     * Map SuiteCRM event to external calendar format
     *
     * @param array $suiteEvent SuiteCRM event data
     * @param string $targetSystem Target system (nextcloud, google, etc.)
     * @return array Mapped event data
     *
     * @requirement REQ-SUITE-050
     */
    public function mapEventToExternal(array $suiteEvent, string $targetSystem = 'nextcloud'): array
    {
        $mapping = $this->calendarMappings[$targetSystem] ?? [];

        $mappedEvent = [
            'id' => $suiteEvent['id'],
            'summary' => $suiteEvent['name'],
            'description' => $suiteEvent['description'] ?? '',
            'start' => $suiteEvent['date_start'],
            'end' => $suiteEvent['date_end'] ?? $suiteEvent['date_start'],
            'status' => $this->mapStatus($suiteEvent['status'] ?? 'Planned', $targetSystem),
            'location' => $suiteEvent['location'] ?? '',
            'organizer' => $suiteEvent['assigned_user_name'] ?? '',
        ];

        // Add system-specific mappings
        if ($targetSystem === 'nextcloud') {
            $mappedEvent['type'] = $mapping[$suiteEvent['module_type'] ?? 'meeting'] ?? 'VEVENT';
            $mappedEvent['categories'] = [$suiteEvent['parent_type'] ?? 'SuiteCRM'];
        }

        return $mappedEvent;
    }

    /**
     * Map external event to SuiteCRM format
     *
     * @param array $externalEvent External event data
     * @param string $sourceSystem Source system (nextcloud, google, etc.)
     * @return array Mapped SuiteCRM event data
     *
     * @requirement REQ-SUITE-050
     */
    public function mapEventFromExternal(array $externalEvent, string $sourceSystem = 'nextcloud'): array
    {
        $mappedEvent = [
            'name' => $externalEvent['summary'] ?? $externalEvent['title'] ?? 'Untitled Event',
            'description' => $externalEvent['description'] ?? '',
            'date_start' => $externalEvent['start'] ?? $externalEvent['dtstart'],
            'date_end' => $externalEvent['end'] ?? $externalEvent['dtend'] ?? $externalEvent['start'],
            'status' => $this->mapStatusFromExternal($externalEvent['status'] ?? 'confirmed', $sourceSystem),
            'location' => $externalEvent['location'] ?? '',
            'external_id' => $externalEvent['id'] ?? $externalEvent['uid'],
            'external_source' => $sourceSystem
        ];

        // Calculate duration if not provided
        if ($mappedEvent['date_end'] && $mappedEvent['date_start']) {
            $start = strtotime($mappedEvent['date_start']);
            $end = strtotime($mappedEvent['date_end']);
            if ($start && $end) {
                $mappedEvent['duration_minutes'] = (int) (($end - $start) / 60);
            }
        }

        return $mappedEvent;
    }

    /**
     * Map SuiteCRM status to external system status
     *
     * @param string $suiteStatus SuiteCRM status
     * @param string $targetSystem Target system
     * @return string Mapped status
     */
    private function mapStatus(string $suiteStatus, string $targetSystem): string
    {
        $statusMappings = [
            'nextcloud' => [
                'Planned' => 'confirmed',
                'Held' => 'confirmed',
                'Not Held' => 'cancelled',
                'Cancelled' => 'cancelled'
            ]
        ];

        return $statusMappings[$targetSystem][$suiteStatus] ?? 'confirmed';
    }

    /**
     * Map external system status to SuiteCRM status
     *
     * @param string $externalStatus External status
     * @param string $sourceSystem Source system
     * @return string Mapped SuiteCRM status
     */
    private function mapStatusFromExternal(string $externalStatus, string $sourceSystem): string
    {
        $statusMappings = [
            'nextcloud' => [
                'confirmed' => 'Planned',
                'tentative' => 'Planned',
                'cancelled' => 'Cancelled'
            ]
        ];

        return $statusMappings[$sourceSystem][$externalStatus] ?? 'Planned';
    }

    /**
     * Preprocess calendar data
     *
     * @param array $data Raw calendar data
     * @return array Processed calendar data
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
        $numericFields = ['duration_minutes', 'reminder_time'];
        foreach ($numericFields as $field) {
            if (isset($processed[$field])) {
                $processed[$field] = (int) $processed[$field];
            }
        }

        // Validate date logic
        if (isset($processed['date_start']) && isset($processed['date_end'])) {
            if ($processed['date_start'] >= $processed['date_end']) {
                throw new ValidationException(
                    "Calendar event end date must be after start date",
                    ['date_end' => 'End date must be after start date']
                );
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