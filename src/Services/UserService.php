<?php
/**
 * User Service
 *
 * Business logic for SuiteCRM User operations.
 * Handles user management, authentication, and user-related queries.
 *
 * @package SuiteAPI
 * @subpackage Services
 * @author AI Assistant
 * @since 1.0.0
 *
 * @requirement REQ-SUITE-027: User Service Implementation
 * @requirement REQ-SUITE-028: User Validation Rules
 *
 * UML Class Diagram:
 * ```
 * +---------------------+
 * |     UserService     |
 * +---------------------+
 * | -requiredFields     |
 * | -validationRules    |
 * +---------------------+
 * | +findUserById()     |
 * | +findUsersByRole()  |
 * | +getCurrentUser()   |
 * | +searchUsers()      |
 * +---------------------+
 * ```
 */

namespace Ksfraser\Ksfraser\SuiteAPI\Services;

use Ksfraser\SuiteAPI\Interfaces\SuiteCrmApiInterface;
use Ksfraser\SuiteAPI\Exceptions\SuiteApiException;
use Ksfraser\SuiteAPI\Exceptions\ValidationException;

/**
 * User Service
 *
 * Provides business logic for managing SuiteCRM Users.
 * Includes user queries and user-related operations.
 */
class UserService extends BaseSuiteCrmService
{
    /**
     * Required fields for user creation
     *
     * @var array
     */
    protected $requiredFields = [
        'user_name',
        'first_name',
        'last_name'
    ];

    /**
     * Validation rules for user fields
     *
     * @var array
     */
    protected $validationRules = [
        'user_name' => [
            'max_length' => 60
        ],
        'first_name' => [
            'max_length' => 30
        ],
        'last_name' => [
            'max_length' => 30
        ],
        'title' => [
            'max_length' => 50
        ],
        'department' => [
            'max_length' => 50
        ],
        'phone_work' => [
            'phone' => true
        ],
        'phone_mobile' => [
            'phone' => true
        ],
        'phone_home' => [
            'phone' => true
        ],
        'phone_other' => [
            'phone' => true
        ],
        'phone_fax' => [
            'phone' => true
        ],
        'email1' => [
            'email' => true
        ],
        'address_street' => [
            'max_length' => 150
        ],
        'address_city' => [
            'max_length' => 100
        ],
        'address_state' => [
            'max_length' => 100
        ],
        'address_postalcode' => [
            'max_length' => 20
        ],
        'address_country' => [
            'max_length' => 100
        ],
        'status' => [
            'in' => ['Active', 'Inactive']
        ],
        'employee_status' => [
            'in' => ['Active', 'Terminated', 'Leave of Absence']
        ]
    ];

    /**
     * Constructor
     *
     * @param SuiteCrmApiInterface $api SuiteCRM API instance
     */
    public function __construct(SuiteCrmApiInterface $api)
    {
        parent::__construct($api, 'Users');
    }

    /**
     * Find user by ID
     *
     * @param string $userId User ID
     * @return array|null User data or null if not found
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-027
     */
    public function findUserById(string $userId): ?array
    {
        return $this->findById($userId);
    }

    /**
     * Find user by username
     *
     * @param string $userName Username
     * @return array|null User data or null if not found
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-027
     */
    public function findUserByUserName(string $userName): ?array
    {
        $users = $this->search(['user_name' => $userName], [], 1);
        return $users[0] ?? null;
    }

    /**
     * Find users by name
     *
     * @param string $firstName First name
     * @param string $lastName Last name
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of matching users
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-027
     */
    public function findUsersByName(string $firstName = '', string $lastName = '', int $limit = 20, int $offset = 0): array
    {
        $criteria = [];

        if (!empty($firstName)) {
            $criteria['first_name'] = ['operator' => 'contains', 'value' => $firstName];
        }

        if (!empty($lastName)) {
            $criteria['last_name'] = ['operator' => 'contains', 'value' => $lastName];
        }

        return $this->search($criteria, [], $limit, $offset);
    }

    /**
     * Find users by email
     *
     * @param string $email Email address
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of matching users
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-027
     */
    public function findUsersByEmail(string $email, int $limit = 20, int $offset = 0): array
    {
        return $this->search(
            ['email1' => $email],
            [],
            $limit,
            $offset
        );
    }

    /**
     * Find active users
     *
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of active users
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-027
     */
    public function findActiveUsers(int $limit = 20, int $offset = 0): array
    {
        return $this->search(
            ['status' => 'Active'],
            [],
            $limit,
            $offset
        );
    }

    /**
     * Find users by department
     *
     * @param string $department Department name
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of matching users
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-027
     */
    public function findUsersByDepartment(string $department, int $limit = 20, int $offset = 0): array
    {
        return $this->search(
            ['department' => $department],
            [],
            $limit,
            $offset
        );
    }

    /**
     * Get current user information
     *
     * @return array|null Current user data or null if not authenticated
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-027
     */
    public function getCurrentUser(): ?array
    {
        // This would typically use the API's getCurrentUser method
        // For now, we'll return null as this requires authentication context
        return null;
    }

    /**
     * Get user statistics
     *
     * @return array User statistics
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-027
     */
    public function getUserStatistics(): array
    {
        $users = $this->search([], ['status', 'employee_status', 'department'], 1000);

        $stats = [
            'total' => count($users),
            'active' => 0,
            'inactive' => 0,
            'by_department' => [],
            'by_employee_status' => []
        ];

        foreach ($users as $user) {
            $status = $user['status'] ?? 'Unknown';
            $employeeStatus = $user['employee_status'] ?? 'Unknown';
            $department = $user['department'] ?? 'Unknown';

            // Count by status
            if ($status === 'Active') {
                $stats['active']++;
            } elseif ($status === 'Inactive') {
                $stats['inactive']++;
            }

            // Count by department
            if (!isset($stats['by_department'][$department])) {
                $stats['by_department'][$department] = 0;
            }
            $stats['by_department'][$department]++;

            // Count by employee status
            if (!isset($stats['by_employee_status'][$employeeStatus])) {
                $stats['by_employee_status'][$employeeStatus] = 0;
            }
            $stats['by_employee_status'][$employeeStatus]++;
        }

        return $stats;
    }

    /**
     * Preprocess user data
     *
     * @param array $data Raw user data
     * @return array Processed user data
     */
    protected function preprocessData(array $data): array
    {
        $processed = parent::preprocessData($data);

        // Generate full name if not provided
        if (!isset($processed['name']) && (isset($processed['first_name']) || isset($processed['last_name']))) {
            $firstName = $processed['first_name'] ?? '';
            $lastName = $processed['last_name'] ?? '';
            $processed['name'] = trim($firstName . ' ' . $lastName);
        }

        // Format phone numbers
        $phoneFields = ['phone_work', 'phone_mobile', 'phone_home', 'phone_other', 'phone_fax'];
        foreach ($phoneFields as $field) {
            if (isset($processed[$field])) {
                $processed[$field] = $this->formatPhoneNumber($processed[$field]);
            }
        }

        // Set default status if not provided
        if (!isset($processed['status'])) {
            $processed['status'] = 'Active';
        }

        return $processed;
    }

    /**
     * Format phone number
     *
     * @param string $phone Phone number
     * @return string Formatted phone number
     */
    private function formatPhoneNumber(string $phone): string
    {
        // Remove all non-digit characters except + and spaces
        $cleaned = preg_replace('/[^\d+\s\-\(\)\.]/', '', $phone);
        return trim($cleaned);
    }
}