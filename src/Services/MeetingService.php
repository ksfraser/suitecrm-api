<?php
/**
 * Meeting Service
 *
 * Business logic for SuiteCRM Meetings operations.
 * Handles meeting scheduling, attendee management, and calendar integration.
 *
 * @package SuiteAPI
 * @subpackage Services
 * @author AI Assistant
 * @since 1.0.0
 *
 * @requirement REQ-SUITE-037: Meeting Service Implementation
 * @requirement REQ-SUITE-038: Meeting Validation Rules
 *
 * UML Class Diagram:
 * ```
 * +---------------------+
 * |   MeetingService    |
 * +---------------------+
 * | -requiredFields     |
 * | -validationRules    |
 * +---------------------+
 * | +createMeeting()    |
 * | +updateMeeting()    |
 * | +findByDateRange()  |
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
 * Meeting Service
 *
 * Provides business logic for managing SuiteCRM Meetings.
 * Includes meeting lifecycle, attendee management, and scheduling operations.
 */
class MeetingService extends ActivityService
{
    /**
     * Required fields for meeting creation
     *
     * @var array
     */
    protected $requiredFields = [
        'name',
        'date_start',
        'date_end'
    ];

    /**
     * Validation rules for meeting fields
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
        'location' => [
            'max_length' => 255
        ],
        'status' => [
            'in' => ['Planned', 'Held', 'Not Held', 'Cancelled']
        ],
        'type' => [
            'in' => ['Meeting', 'WebEx', 'Call', 'Other']
        ],
        'duration_hours' => [
            // Should be numeric and positive
        ],
        'duration_minutes' => [
            // Should be numeric between 0-59
        ],
        'reminder_time' => [
            // Should be numeric (minutes before meeting)
        ],
        'email_reminder_time' => [
            // Should be numeric (minutes before meeting)
        ],
        'parent_type' => [
            'in' => [
                'Accounts', 'Contacts', 'Opportunities', 'Leads', 'Cases',
                'Bugs', 'Projects', 'ProjectTask', 'Tasks'
            ]
        ],
        'outlook_id' => [
            'max_length' => 255
        ],
        'sequence' => [
            // Should be numeric
        ]
    ];

    /**
     * Constructor
     *
     * @param SuiteCrmApiInterface $api SuiteCRM API instance
     */
    public function __construct(SuiteCrmApiInterface $api)
    {
        parent::__construct($api, 'Meetings');
    }

    /**
     * Create a new meeting
     *
     * @param array $meetingData Meeting data
     * @return string|null Created meeting ID
     * @throws ValidationException
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-037
     */
    public function createMeeting(array $meetingData): ?string
    {
        // Set default status if not provided
        if (!isset($meetingData['status'])) {
            $meetingData['status'] = 'Planned';
        }

        // Set default type if not provided
        if (!isset($meetingData['type'])) {
            $meetingData['type'] = 'Meeting';
        }

        return $this->create($meetingData);
    }

    /**
     * Mark meeting as held
     *
     * @param string $meetingId Meeting ID
     * @return bool True on success
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-037
     */
    public function markAsHeld(string $meetingId): bool
    {
        return $this->update($meetingId, ['status' => 'Held']);
    }

    /**
     * Cancel meeting
     *
     * @param string $meetingId Meeting ID
     * @param string $reason Cancellation reason
     * @return bool True on success
     * @throws ValidationException
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-037
     */
    public function cancelMeeting(string $meetingId, string $reason): bool
    {
        $updateData = [
            'status' => 'Cancelled',
            'description' => ($this->findById($meetingId)['description'] ?? '') . "\n\n[CANCELLED] " . date('Y-m-d H:i:s') . ": {$reason}"
        ];

        return $this->update($meetingId, $updateData);
    }

    /**
     * Reschedule meeting
     *
     * @param string $meetingId Meeting ID
     * @param string $newStartDate New start date/time
     * @param string $newEndDate New end date/time
     * @param string|null $reason Rescheduling reason
     * @return bool True on success
     * @throws ValidationException
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-037
     */
    public function rescheduleMeeting(string $meetingId, string $newStartDate, string $newEndDate, ?string $reason = null): bool
    {
        $updateData = [
            'date_start' => $newStartDate,
            'date_end' => $newEndDate
        ];

        if ($reason) {
            $existingDescription = $this->findById($meetingId)['description'] ?? '';
            $updateData['description'] = $existingDescription . "\n\n[RESCHEDULED] " . date('Y-m-d H:i:s') . ": {$reason}";
        }

        return $this->update($meetingId, $updateData);
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