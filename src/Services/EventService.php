<?php
/**
 * Event Service
 *
 * Business logic for SuiteCRM Events operations.
 * Handles event scheduling, attendee management, and event lifecycle.
 *
 * @package SuiteAPI
 * @subpackage Services
 * @author AI Assistant
 * @since 1.0.0
 *
 * @requirement REQ-SUITE-047: Event Service Implementation
 * @requirement REQ-SUITE-048: Event Validation Rules
 *
 * UML Class Diagram:
 * ```
 * +---------------------+
 * |   EventService      |
 * +---------------------+
 * | -requiredFields     |
 * | -validationRules    |
 * +---------------------+
 * | +createEvent()      |
 * | +updateEvent()      |
 * | +manageBudget()     |
 * | +sendInvitations()  |
 * +---------------------+
 *           ^
 *           |
 *   +-------------+
 *   | ActivityService |
 *   +-------------+
 * ```
 */

namespace SuiteAPI\Services;

use SuiteAPI\Interfaces\SuiteCrmApiInterface;
use SuiteAPI\Exceptions\SuiteApiException;
use SuiteAPI\Exceptions\ValidationException;

/**
 * Event Service
 *
 * Provides business logic for managing SuiteCRM Events.
 * Includes event scheduling, budget management, and invitation handling.
 */
class EventService extends ActivityService
{
    /**
     * Required fields for event creation
     *
     * @var array
     */
    protected $requiredFields = [
        'name',
        'date_start',
        'date_end'
    ];

    /**
     * Validation rules for event fields
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
        'budget' => [
            'min' => 0
        ],
        'currency_id' => [
            'max_length' => 36
        ],
        'invite_templates' => [
            'max_length' => 255
        ],
        'accept_redirect' => [
            'max_length' => 255,
            'url' => true
        ],
        'decline_redirect' => [
            'max_length' => 255,
            'url' => true
        ],
        'activity_status_type' => [
            'in' => ['Active', 'Inactive', 'Completed', 'Cancelled']
        ],
        'location' => [
            'max_length' => 255
        ],
        'max_attendees' => [
            'min' => 1
        ],
        'registration_fee' => [
            'min' => 0
        ]
    ];

    /**
     * Constructor
     *
     * @param SuiteCrmApiInterface $api SuiteCRM API instance
     */
    public function __construct(SuiteCrmApiInterface $api)
    {
        parent::__construct($api, 'Events');
    }

    /**
     * Create a new event
     *
     * @param array $eventData Event data
     * @return string|null Created event ID
     * @throws ValidationException
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-047
     */
    public function createEvent(array $eventData): ?string
    {
        // Set default status if not provided
        if (!isset($eventData['status'])) {
            $eventData['status'] = 'Planned';
        }

        // Set default activity status type if not provided
        if (!isset($eventData['activity_status_type'])) {
            $eventData['activity_status_type'] = 'Active';
        }

        return $this->create($eventData);
    }

    /**
     * Update an existing event
     *
     * @param string $eventId Event ID
     * @param array $eventData Updated event data
     * @return bool True on success
     * @throws ValidationException
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-047
     */
    public function updateEvent(string $eventId, array $eventData): bool
    {
        return $this->update($eventId, $eventData);
    }

    /**
     * Find event by ID
     *
     * @param string $eventId Event ID
     * @return array|null Event data or null if not found
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-047
     */
    public function findEventById(string $eventId): ?array
    {
        return $this->findById($eventId);
    }

    /**
     * Find events by budget range
     *
     * @param float $minBudget Minimum budget
     * @param float $maxBudget Maximum budget
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of matching events
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-047
     */
    public function findEventsByBudgetRange(float $minBudget, float $maxBudget, int $limit = 20, int $offset = 0): array
    {
        return $this->search(
            [
                'budget' => ['operator' => 'between', 'value' => [$minBudget, $maxBudget]]
            ],
            [],
            $limit,
            $offset
        );
    }

    /**
     * Find events by activity status
     *
     * @param string $status Activity status type
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of matching events
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-047
     */
    public function findEventsByActivityStatus(string $status, int $limit = 20, int $offset = 0): array
    {
        return $this->search(
            ['activity_status_type' => $status],
            [],
            $limit,
            $offset
        );
    }

    /**
     * Update event budget
     *
     * @param string $eventId Event ID
     * @param float $newBudget New budget amount
     * @param string|null $currencyId Currency ID
     * @return bool True on success
     * @throws ValidationException
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-047
     */
    public function updateEventBudget(string $eventId, float $newBudget, ?string $currencyId = null): bool
    {
        $updateData = ['budget' => $newBudget];

        if ($currencyId) {
            $updateData['currency_id'] = $currencyId;
        }

        return $this->update($eventId, $updateData);
    }

    /**
     * Set event invitation template
     *
     * @param string $eventId Event ID
     * @param string $inviteTemplate Template name or ID
     * @param string|null $acceptRedirect Accept redirect URL
     * @param string|null $declineRedirect Decline redirect URL
     * @return bool True on success
     * @throws ValidationException
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-047
     */
    public function setEventInvitationTemplate(string $eventId, string $inviteTemplate, ?string $acceptRedirect = null, ?string $declineRedirect = null): bool
    {
        $updateData = ['invite_templates' => $inviteTemplate];

        if ($acceptRedirect) {
            $updateData['accept_redirect'] = $acceptRedirect;
        }

        if ($declineRedirect) {
            $updateData['decline_redirect'] = $declineRedirect;
        }

        return $this->update($eventId, $updateData);
    }

    /**
     * Complete event
     *
     * @param string $eventId Event ID
     * @return bool True on success
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-047
     */
    public function completeEvent(string $eventId): bool
    {
        return $this->update($eventId, [
            'status' => 'Held',
            'activity_status_type' => 'Completed'
        ]);
    }

    /**
     * Cancel event
     *
     * @param string $eventId Event ID
     * @param string $reason Cancellation reason
     * @return bool True on success
     * @throws ValidationException
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-047
     */
    public function cancelEvent(string $eventId, string $reason): bool
    {
        $updateData = [
            'status' => 'Cancelled',
            'activity_status_type' => 'Cancelled',
            'description' => ($this->findById($eventId)['description'] ?? '') . "\n\n[CANCELLED] " . date('Y-m-d H:i:s') . ": {$reason}"
        ];

        return $this->update($eventId, $updateData);
    }

    /**
     * Get event statistics
     *
     * @return array Event statistics
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-047
     */
    public function getEventStatistics(): array
    {
        $events = $this->search([], ['status', 'activity_status_type'], 1000);

        $stats = [
            'total' => count($events),
            'by_status' => [],
            'by_activity_status' => [],
            'total_budget' => 0.0,
            'active_events' => 0,
            'completed_events' => 0,
            'cancelled_events' => 0
        ];

        foreach ($events as $event) {
            $status = $event['status'] ?? 'Unknown';
            $activityStatus = $event['activity_status_type'] ?? 'Unknown';
            $budget = (float) ($event['budget'] ?? 0);

            // Count by status
            if (!isset($stats['by_status'][$status])) {
                $stats['by_status'][$status] = 0;
            }
            $stats['by_status'][$status]++;

            // Count by activity status
            if (!isset($stats['by_activity_status'][$activityStatus])) {
                $stats['by_activity_status'][$activityStatus] = 0;
            }
            $stats['by_activity_status'][$activityStatus]++;

            // Sum budgets
            $stats['total_budget'] += $budget;

            // Count by activity status type
            if ($activityStatus === 'Active') {
                $stats['active_events']++;
            } elseif ($activityStatus === 'Completed') {
                $stats['completed_events']++;
            } elseif ($activityStatus === 'Cancelled') {
                $stats['cancelled_events']++;
            }
        }

        return $stats;
    }

    /**
     * Preprocess event data
     *
     * @param array $data Raw event data
     * @return array Processed event data
     */
    protected function preprocessData(array $data): array
    {
        $processed = parent::preprocessData($data);

        // Ensure budget is properly formatted
        if (isset($processed['budget'])) {
            $processed['budget'] = (float) $processed['budget'];
        }

        // Ensure registration fee is properly formatted
        if (isset($processed['registration_fee'])) {
            $processed['registration_fee'] = (float) $processed['registration_fee'];
        }

        // Ensure max attendees is properly formatted
        if (isset($processed['max_attendees'])) {
            $processed['max_attendees'] = (int) $processed['max_attendees'];
        }

        // Validate URLs if provided
        $urlFields = ['accept_redirect', 'decline_redirect'];
        foreach ($urlFields as $field) {
            if (isset($processed[$field]) && !empty($processed[$field])) {
                if (!filter_var($processed[$field], FILTER_VALIDATE_URL)) {
                    throw new ValidationException(
                        "Invalid URL format for {$field}",
                        [$field => 'Must be a valid URL']
                    );
                }
            }
        }

        return $processed;
    }
}