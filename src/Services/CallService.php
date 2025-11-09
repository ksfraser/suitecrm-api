<?php
/**
 * Call Service
 *
 * Business logic for SuiteCRM Calls operations.
 * Handles call logging, scheduling, and communication tracking.
 *
 * @package SuiteAPI
 * @subpackage Services
 * @author AI Assistant
 * @since 1.0.0
 *
 * @requirement REQ-SUITE-039: Call Service Implementation
 * @requirement REQ-SUITE-040: Call Validation Rules
 *
 * UML Class Diagram:
 * ```
 * +---------------------+
 * |    CallService      |
 * +---------------------+
 * | -requiredFields     |
 * | -validationRules    |
 * +---------------------+
 * | +createCall()       |
 * | +updateCall()       |
 * | +findByDateRange()  |
 * | +logCall()          |
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
 * Call Service
 *
 * Provides business logic for managing SuiteCRM Calls.
 * Includes call logging, scheduling, and communication tracking.
 */
class CallService extends ActivityService
{
    /**
     * Required fields for call creation
     *
     * @var array
     */
    protected $requiredFields = [
        'name',
        'date_start',
        'date_end'
    ];

    /**
     * Validation rules for call fields
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
        'direction' => [
            'in' => ['Inbound', 'Outbound']
        ],
        'duration_hours' => [
            // Should be numeric and positive
        ],
        'duration_minutes' => [
            // Should be numeric between 0-59
        ],
        'reminder_time' => [
            // Should be numeric (minutes before call)
        ],
        'email_reminder_time' => [
            // Should be numeric (minutes before call)
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
        ],
        'accept_status' => [
            'in' => ['accept', 'decline', 'tentative', 'none']
        ]
    ];

    /**
     * Constructor
     *
     * @param SuiteCrmApiInterface $api SuiteCRM API instance
     */
    public function __construct(SuiteCrmApiInterface $api)
    {
        parent::__construct($api, 'Calls');
    }

    /**
     * Create a new call
     *
     * @param array $callData Call data
     * @return string|null Created call ID
     * @throws ValidationException
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-039
     */
    public function createCall(array $callData): ?string
    {
        // Set default status if not provided
        if (!isset($callData['status'])) {
            $callData['status'] = 'Planned';
        }

        // Set default direction if not provided
        if (!isset($callData['direction'])) {
            $callData['direction'] = 'Outbound';
        }

        return $this->create($callData);
    }

    /**
     * Log a completed call
     *
     * @param array $callData Call data
     * @return string|null Created call ID
     * @throws ValidationException
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-039
     */
    public function logCall(array $callData): ?string
    {
        // Set status to Held for logged calls
        $callData['status'] = 'Held';
        $callData['type'] = 'Outbound';

        // Set end date to start date if not provided (for instant calls)
        if (!isset($callData['date_end']) && isset($callData['date_start'])) {
            $callData['date_end'] = $callData['date_start'];
        }

        return $this->create($callData);
    }

    /**
     * Find calls by direction
     *
     * @param string $direction Call direction (Inbound/Outbound)
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of matching calls
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-039
     */
    public function findCallsByDirection(string $direction, int $limit = 20, int $offset = 0): array
    {
        return $this->search(
            ['direction' => $direction],
            [],
            $limit,
            $offset
        );
    }

    /**
     * Mark call as completed
     *
     * @param string $callId Call ID
     * @return bool True on success
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-039
     */
    public function markAsCompleted(string $callId): bool
    {
        return $this->update($callId, ['status' => 'Held']);
    }

    /**
     * Cancel call
     *
     * @param string $callId Call ID
     * @param string $reason Cancellation reason
     * @return bool True on success
     * @throws ValidationException
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-039
     */
    public function cancelCall(string $callId, string $reason): bool
    {
        $updateData = [
            'status' => 'Cancelled',
            'description' => ($this->findById($callId)['description'] ?? '') . "\n\n[CANCELLED] " . date('Y-m-d H:i:s') . ": {$reason}"
        ];

        return $this->update($callId, $updateData);
    }

    /**
     * Reschedule call
     *
     * @param string $callId Call ID
     * @param string $newStartDate New start date/time
     * @param string $newEndDate New end date/time
     * @param string|null $reason Rescheduling reason
     * @return bool True on success
     * @throws ValidationException
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-039
     */
    public function rescheduleCall(string $callId, string $newStartDate, string $newEndDate, ?string $reason = null): bool
    {
        $updateData = [
            'date_start' => $newStartDate,
            'date_end' => $newEndDate
        ];

        if ($reason) {
            $existingDescription = $this->findById($callId)['description'] ?? '';
            $updateData['description'] = $existingDescription . "\n\n[RESCHEDULED] " . date('Y-m-d H:i:s') . ": {$reason}";
        }

        return $this->update($callId, $updateData);
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