<?php
/**
 * Opportunity Service
 *
 * Business logic for SuiteCRM Opportunity operations.
 * Handles opportunity creation, updates, pipeline management, and forecasting.
 *
 * @package SuiteAPI
 * @subpackage Services
 * @author AI Assistant
 * @since 1.0.0
 *
 * @requirement REQ-SUITE-021: Opportunity Service Implementation
 * @requirement REQ-SUITE-022: Opportunity Validation Rules
 *
 * UML Class Diagram:
 * ```
 * +---------------------+
 * | OpportunityService  |
 * +---------------------+
 * | -requiredFields     |
 * | -validationRules    |
 * +---------------------+
 * | +createOpportunity()|
 * | +updateOpportunity()|
 * | +findBySalesStage() |
 * | +calculatePipeline()|
 * +---------------------+
 * ```
 */

namespace SuiteAPI\Services;

use SuiteAPI\Interfaces\SuiteCrmApiInterface;
use SuiteAPI\Exceptions\SuiteApiException;
use SuiteAPI\Exceptions\ValidationException;

/**
 * Opportunity Service
 *
 * Provides business logic for managing SuiteCRM Opportunities.
 * Includes validation, data transformation, and sales pipeline methods.
 */
class OpportunityService extends BaseSuiteCrmService
{
    /**
     * Required fields for opportunity creation
     *
     * @var array
     */
    protected $requiredFields = [
        'name',
        'account_id'
    ];

    /**
     * Validation rules for opportunity fields
     *
     * @var array
     */
    protected $validationRules = [
        'name' => [
            'max_length' => 255
        ],
        'amount' => [
            // Amount should be numeric and positive
        ],
        'sales_stage' => [
            'in' => [
                'Prospecting', 'Qualification', 'Needs Analysis', 'Value Proposition',
                'Id. Decision Makers', 'Perception Analysis', 'Proposal/Price Quote',
                'Negotiation/Review', 'Closed Won', 'Closed Lost'
            ]
        ],
        'probability' => [
            // Probability should be between 0 and 100
        ],
        'next_step' => [
            'max_length' => 100
        ],
        'description' => [
            'max_length' => 65535
        ],
        'lead_source' => [
            'in' => [
                'Cold Call', 'Existing Customer', 'Self Generated', 'Employee',
                'Partner', 'Public Relations', 'Direct Mail', 'Conference',
                'Trade Show', 'Web Site', 'Word of mouth', 'Email', 'Campaign',
                'Other'
            ]
        ],
        'campaign_id' => [
            // Should be a valid campaign ID
        ],
        'billing_address_street' => [
            'max_length' => 150
        ],
        'billing_address_city' => [
            'max_length' => 100
        ],
        'billing_address_state' => [
            'max_length' => 100
        ],
        'billing_address_postalcode' => [
            'max_length' => 20
        ],
        'billing_address_country' => [
            'max_length' => 255
        ],
        'shipping_address_street' => [
            'max_length' => 150
        ],
        'shipping_address_city' => [
            'max_length' => 100
        ],
        'shipping_address_state' => [
            'max_length' => 100
        ],
        'shipping_address_postalcode' => [
            'max_length' => 20
        ],
        'shipping_address_country' => [
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
        parent::__construct($api, 'Opportunities');
    }

    /**
     * Create a new opportunity
     *
     * @param array $opportunityData Opportunity data
     * @return string|null Created opportunity ID
     * @throws ValidationException
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-021
     */
    public function createOpportunity(array $opportunityData): ?string
    {
        return $this->create($opportunityData);
    }

    /**
     * Update an existing opportunity
     *
     * @param string $opportunityId Opportunity ID
     * @param array $opportunityData Updated opportunity data
     * @return bool True on success
     * @throws ValidationException
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-021
     */
    public function updateOpportunity(string $opportunityId, array $opportunityData): bool
    {
        return $this->update($opportunityId, $opportunityData);
    }

    /**
     * Find opportunity by ID
     *
     * @param string $opportunityId Opportunity ID
     * @return array|null Opportunity data or null if not found
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-021
     */
    public function findOpportunityById(string $opportunityId): ?array
    {
        return $this->findById($opportunityId);
    }

    /**
     * Find opportunities by account
     *
     * @param string $accountId Account ID
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of matching opportunities
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-021
     */
    public function findOpportunitiesByAccount(string $accountId, int $limit = 20, int $offset = 0): array
    {
        return $this->search(
            ['account_id' => $accountId],
            [],
            $limit,
            $offset
        );
    }

    /**
     * Find opportunities by sales stage
     *
     * @param string $salesStage Sales stage
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of matching opportunities
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-021
     */
    public function findOpportunitiesBySalesStage(string $salesStage, int $limit = 20, int $offset = 0): array
    {
        return $this->search(
            ['sales_stage' => $salesStage],
            [],
            $limit,
            $offset
        );
    }

    /**
     * Find opportunities by assigned user
     *
     * @param string $userId User ID
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of matching opportunities
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-021
     */
    public function findOpportunitiesByAssignedUser(string $userId, int $limit = 20, int $offset = 0): array
    {
        return $this->search(
            ['assigned_user_id' => $userId],
            [],
            $limit,
            $offset
        );
    }

    /**
     * Calculate pipeline value for user
     *
     * @param string $userId User ID
     * @param array $salesStages Sales stages to include (default: open stages)
     * @return float Pipeline value
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-021
     */
    public function calculatePipelineValue(string $userId, array $salesStages = []): float
    {
        if (empty($salesStages)) {
            // Default to open sales stages
            $salesStages = [
                'Prospecting', 'Qualification', 'Needs Analysis', 'Value Proposition',
                'Id. Decision Makers', 'Perception Analysis', 'Proposal/Price Quote',
                'Negotiation/Review'
            ];
        }

        $opportunities = $this->search(
            [
                'assigned_user_id' => $userId,
                'sales_stage' => ['operator' => 'in', 'value' => $salesStages]
            ],
            ['amount', 'probability'],
            1000 // Large limit to get all pipeline opportunities
        );

        $pipelineValue = 0.0;
        foreach ($opportunities as $opportunity) {
            $amount = (float) ($opportunity['amount'] ?? 0);
            $probability = (float) ($opportunity['probability'] ?? 0);

            // Calculate weighted amount based on probability
            $weightedAmount = $amount * ($probability / 100);
            $pipelineValue += $weightedAmount;
        }

        return $pipelineValue;
    }

    /**
     * Get sales forecast by stage
     *
     * @param string $userId User ID
     * @return array Forecast data by sales stage
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-021
     */
    public function getSalesForecast(string $userId): array
    {
        $opportunities = $this->search(
            ['assigned_user_id' => $userId],
            ['amount', 'probability', 'sales_stage'],
            1000
        );

        $forecast = [];
        foreach ($opportunities as $opportunity) {
            $stage = $opportunity['sales_stage'] ?? 'Unknown';
            $amount = (float) ($opportunity['amount'] ?? 0);
            $probability = (float) ($opportunity['probability'] ?? 0);

            if (!isset($forecast[$stage])) {
                $forecast[$stage] = [
                    'count' => 0,
                    'total_amount' => 0.0,
                    'weighted_amount' => 0.0
                ];
            }

            $forecast[$stage]['count']++;
            $forecast[$stage]['total_amount'] += $amount;
            $forecast[$stage]['weighted_amount'] += $amount * ($probability / 100);
        }

        return $forecast;
    }

    /**
     * Preprocess opportunity data
     *
     * @param array $data Raw opportunity data
     * @return array Processed opportunity data
     */
    protected function preprocessData(array $data): array
    {
        $processed = parent::preprocessData($data);

        // Ensure amount is numeric
        if (isset($processed['amount'])) {
            $processed['amount'] = (float) $processed['amount'];
        }

        // Ensure probability is within valid range
        if (isset($processed['probability'])) {
            $probability = (float) $processed['probability'];
            $processed['probability'] = max(0, min(100, $probability));
        }

        // Set default probability based on sales stage if not provided
        if (!isset($processed['probability']) && isset($processed['sales_stage'])) {
            $processed['probability'] = $this->getDefaultProbabilityForStage($processed['sales_stage']);
        }

        return $processed;
    }

    /**
     * Get default probability for sales stage
     *
     * @param string $salesStage Sales stage
     * @return int Default probability percentage
     */
    private function getDefaultProbabilityForStage(string $salesStage): int
    {
        $defaultProbabilities = [
            'Prospecting' => 10,
            'Qualification' => 20,
            'Needs Analysis' => 30,
            'Value Proposition' => 50,
            'Id. Decision Makers' => 60,
            'Perception Analysis' => 70,
            'Proposal/Price Quote' => 80,
            'Negotiation/Review' => 90,
            'Closed Won' => 100,
            'Closed Lost' => 0
        ];

        return $defaultProbabilities[$salesStage] ?? 0;
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
            'account_id' => 'Accounts',
            'contact_id' => 'Contacts',
            'campaign_id' => 'Campaign'
        ];
    }
}