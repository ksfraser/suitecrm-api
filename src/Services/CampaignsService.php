<?php
/**
 * Campaigns Service
 *
 * Service for managing SuiteCRM Campaigns module.
 * Handles marketing campaigns, email automation, and lead nurturing.
 *
 * @package SuiteAPI
 * @subpackage Services
 * @author AI Assistant
 * @since 1.0.0
 *
 * @requirement REQ-SUITE-020: Campaigns Module Support
 * @requirement REQ-SUITE-033: Marketing Automation Integration
 *
 * UML Class Diagram:
 * ```
 * +---------------------+
 * |  CampaignsService   |
 * +---------------------+
 * | -api: SuiteCrmApiInterface |
 * | -moduleName: "Campaign" |
 * +---------------------+
 * | +createCampaign()   |
 * | +updateCampaign()   |
 * | +findCampaignById() |
 * | +searchCampaigns()  |
 * | +deleteCampaign()   |
 * | +addTargets()       |
 * | +scheduleCampaign() |
 * | +getCampaignStats() |
 * | +launchCampaign()   |
 * +---------------------+
 *           ^
 *           |
 * +---------------------+
 * | BaseSuiteCrmService|
 * +---------------------+
 * ```
 */

namespace SuiteAPI\Services;

use SuiteAPI\Interfaces\SuiteCrmApiInterface;
use SuiteAPI\Exceptions\SuiteApiException;
use SuiteAPI\Exceptions\ValidationException;

/**
 * Campaigns Service
 *
 * Manages SuiteCRM Campaigns with marketing automation and analytics.
 */
class CampaignsService extends BaseSuiteCrmService
{
    /**
     * Constructor
     *
     * @param SuiteCrmApiInterface $api SuiteCRM API instance
     */
    public function __construct(SuiteCrmApiInterface $api)
    {
        parent::__construct($api, 'Campaign');

        $this->requiredFields = [
            'name',
            'status'
        ];

        $this->validationRules = [
            'name' => [
                'max_length' => 255
            ],
            'status' => [
                'in' => ['Planning', 'Active', 'Inactive', 'Complete']
            ],
            'campaign_type' => [
                'in' => ['Telesales', 'Mail', 'Email', 'Print', 'Web', 'Radio', 'Television']
            ],
            'frequency' => [
                'in' => ['Weekly', 'Monthly', 'Quarterly', 'Annually', 'One Time']
            ],
            'budget' => [
                // Numeric validation would be handled by SuiteCRM
            ],
            'expected_cost' => [
                // Numeric validation would be handled by SuiteCRM
            ],
            'actual_cost' => [
                // Numeric validation would be handled by SuiteCRM
            ],
            'expected_revenue' => [
                // Numeric validation would be handled by SuiteCRM
            ]
        ];
    }

    /**
     * Create a new campaign
     *
     * @param array $data Campaign data
     * @return string|null Created campaign ID
     * @throws ValidationException
     * @throws SuiteApiException
     */
    public function createCampaign(array $data): ?string
    {
        return $this->create($data);
    }

    /**
     * Update an existing campaign
     *
     * @param string $id Campaign ID
     * @param array $data Updated campaign data
     * @return bool True on success
     * @throws ValidationException
     * @throws SuiteApiException
     */
    public function updateCampaign(string $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    /**
     * Find campaign by ID
     *
     * @param string $id Campaign ID
     * @param array $fields Optional fields to retrieve
     * @return array|null Campaign data or null if not found
     * @throws SuiteApiException
     */
    public function findCampaignById(string $id, array $fields = []): ?array
    {
        return $this->findById($id, $fields);
    }

    /**
     * Search for campaigns
     *
     * @param array $criteria Search criteria
     * @param array $fields Fields to return
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of matching campaigns
     * @throws SuiteApiException
     */
    public function searchCampaigns(array $criteria = [], array $fields = [], int $limit = 20, int $offset = 0): array
    {
        return $this->search($criteria, $fields, $limit, $offset);
    }

    /**
     * Delete a campaign
     *
     * @param string $id Campaign ID
     * @return bool True on success
     * @throws SuiteApiException
     */
    public function deleteCampaign(string $id): bool
    {
        return $this->delete($id);
    }

    /**
     * Add targets to a campaign
     *
     * @param string $campaignId Campaign ID
     * @param array $targetIds Array of target/contact/lead IDs
     * @param string $targetType Type of targets ('Contacts', 'Leads', 'Prospects')
     * @return bool True on success
     * @throws ValidationException
     * @throws SuiteApiException
     */
    public function addTargets(string $campaignId, array $targetIds, string $targetType = 'Contacts'): bool
    {
        // Validate campaign exists
        $campaign = $this->findCampaignById($campaignId);
        if (!$campaign) {
            throw new ValidationException("Campaign with ID '{$campaignId}' does not exist");
        }

        // Validate target type
        $validTypes = ['Contacts', 'Leads', 'Prospects'];
        if (!in_array($targetType, $validTypes)) {
            throw new ValidationException("Invalid target type. Must be one of: " . implode(', ', $validTypes));
        }

        // Validate targets exist
        $this->validateTargetsExist($targetIds, $targetType);

        // Add targets to campaign (this would depend on SuiteCRM's relationship structure)
        // For now, we'll update the campaign with target lists
        $existingTargets = isset($campaign['target_ids']) ? $campaign['target_ids'] : [];
        $updatedTargets = array_unique(array_merge($existingTargets, $targetIds));

        return $this->updateCampaign($campaignId, [
            'target_ids' => $updatedTargets,
            'target_type' => $targetType
        ]);
    }

    /**
     * Schedule a campaign for launch
     *
     * @param string $campaignId Campaign ID
     * @param string $launchDate Launch date (YYYY-MM-DD)
     * @param string $endDate Optional end date
     * @return bool True on success
     * @throws ValidationException
     * @throws SuiteApiException
     */
    public function scheduleCampaign(string $campaignId, string $launchDate, string $endDate = null): bool
    {
        // Validate campaign exists
        $campaign = $this->findCampaignById($campaignId);
        if (!$campaign) {
            throw new ValidationException("Campaign with ID '{$campaignId}' does not exist");
        }

        // Validate dates
        if (!strtotime($launchDate)) {
            throw new ValidationException("Invalid launch date format");
        }

        if ($endDate && !strtotime($endDate)) {
            throw new ValidationException("Invalid end date format");
        }

        if ($endDate && strtotime($launchDate) > strtotime($endDate)) {
            throw new ValidationException("Launch date cannot be after end date");
        }

        return $this->updateCampaign($campaignId, [
            'start_date' => $launchDate,
            'end_date' => $endDate,
            'status' => 'Active'
        ]);
    }

    /**
     * Get campaign statistics and performance metrics
     *
     * @param string $campaignId Campaign ID
     * @return array Campaign statistics
     * @throws SuiteApiException
     */
    public function getCampaignStats(string $campaignId): array
    {
        $campaign = $this->findCampaignById($campaignId);
        if (!$campaign) {
            throw new ValidationException("Campaign with ID '{$campaignId}' does not exist");
        }

        // This would typically query campaign log entries and related records
        // For now, return basic stats from the campaign record
        return [
            'campaign' => $campaign,
            'impressions' => $campaign['impressions'] ?? 0,
            'responses' => [
                'total' => $campaign['responses'] ?? 0,
                'viewed' => $campaign['viewed'] ?? 0,
                'clicked' => $campaign['clicked'] ?? 0,
                'subscribed' => $campaign['subscribed'] ?? 0,
                'unsubscribed' => $campaign['unsubscribed'] ?? 0
            ],
            'roi' => $this->calculateROI($campaign),
            'conversion_rate' => $this->calculateConversionRate($campaign)
        ];
    }

    /**
     * Launch a scheduled campaign
     *
     * @param string $campaignId Campaign ID
     * @return bool True on success
     * @throws ValidationException
     * @throws SuiteApiException
     */
    public function launchCampaign(string $campaignId): bool
    {
        $campaign = $this->findCampaignById($campaignId);
        if (!$campaign) {
            throw new ValidationException("Campaign with ID '{$campaignId}' does not exist");
        }

        if (($campaign['status'] ?? '') !== 'Active') {
            throw new ValidationException("Campaign must be in Active status to launch");
        }

        if (empty($campaign['start_date'])) {
            throw new ValidationException("Campaign must have a start date to launch");
        }

        // Check if launch date has arrived
        $launchDate = strtotime($campaign['start_date']);
        $now = time();

        if ($launchDate > $now) {
            throw new ValidationException("Campaign launch date is in the future");
        }

        // Mark as launched and update timestamps
        return $this->updateCampaign($campaignId, [
            'date_launched' => date('Y-m-d H:i:s'),
            'launched' => true
        ]);
    }

    /**
     * Create an email campaign with template
     *
     * @param array $campaignData Campaign data
     * @param string $templateId Email template ID
     * @param array $targetIds Target IDs
     * @return string|null Created campaign ID
     * @throws ValidationException
     * @throws SuiteApiException
     */
    public function createEmailCampaign(array $campaignData, string $templateId, array $targetIds): ?string
    {
        // Validate template exists
        $emailTemplatesService = new EmailTemplatesService($this->api);
        $template = $emailTemplatesService->findTemplateById($templateId);
        if (!$template) {
            throw new ValidationException("Email template with ID '{$templateId}' does not exist");
        }

        // Validate targets
        $this->validateTargetsExist($targetIds, 'Contacts');

        // Set campaign type and template
        $campaignData['campaign_type'] = 'Email';
        $campaignData['email_template_id'] = $templateId;
        $campaignData['target_ids'] = $targetIds;

        $campaignId = $this->createCampaign($campaignData);

        if ($campaignId) {
            // Link template to campaign (SuiteCRM-specific relationship)
            // This might require additional API calls
        }

        return $campaignId;
    }

    /**
     * Validate that targets exist
     *
     * @param array $targetIds Target IDs
     * @param string $targetType Target type
     * @throws ValidationException
     */
    private function validateTargetsExist(array $targetIds, string $targetType): void
    {
        $serviceMap = [
            'Contacts' => 'ContactService',
            'Leads' => 'LeadService',
            'Prospects' => 'ProspectsService'
        ];

        if (!isset($serviceMap[$targetType])) {
            throw new ValidationException("Unsupported target type: {$targetType}");
        }

        $serviceClass = 'SuiteAPI\\Services\\' . $serviceMap[$targetType];
        $service = new $serviceClass($this->api);

        foreach ($targetIds as $targetId) {
            $method = 'find' . substr($serviceMap[$targetType], 0, -7) . 'ById'; // Remove 'Service'
            $target = $service->$method($targetId);
            if (!$target) {
                throw new ValidationException("{$targetType} with ID '{$targetId}' does not exist");
            }
        }
    }

    /**
     * Calculate campaign ROI
     *
     * @param array $campaign Campaign data
     * @return float ROI percentage
     */
    private function calculateROI(array $campaign): float
    {
        $revenue = (float) ($campaign['expected_revenue'] ?? 0);
        $cost = (float) ($campaign['actual_cost'] ?? $campaign['expected_cost'] ?? 0);

        if ($cost == 0) {
            return 0;
        }

        return (($revenue - $cost) / $cost) * 100;
    }

    /**
     * Calculate campaign conversion rate
     *
     * @param array $campaign Campaign data
     * @return float Conversion rate percentage
     */
    private function calculateConversionRate(array $campaign): float
    {
        $responses = (int) ($campaign['responses'] ?? 0);
        $impressions = (int) ($campaign['impressions'] ?? 0);

        if ($impressions == 0) {
            return 0;
        }

        return ($responses / $impressions) * 100;
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
            'email_template_id' => 'EmailTemplate'
        ];
    }

    /**
     * Validate business logic rules
     *
     * @param array $data Data to validate
     * @param bool $isCreate Whether this is for create operation
     * @throws ValidationException
     */
    protected function validateBusinessLogic(array $data, bool $isCreate): void
    {
        // Validate date logic
        if (isset($data['start_date']) && isset($data['end_date'])) {
            $startDate = strtotime($data['start_date']);
            $endDate = strtotime($data['end_date']);

            if ($startDate && $endDate && $startDate > $endDate) {
                throw new ValidationException("Campaign start date cannot be after end date");
            }
        }

        // Validate budget logic
        if (isset($data['budget']) && isset($data['expected_cost'])) {
            $budget = (float) $data['budget'];
            $expectedCost = (float) $data['expected_cost'];

            if ($expectedCost > $budget) {
                throw new ValidationException("Expected cost cannot exceed budget");
            }
        }

        // Validate email campaign requirements
        if (isset($data['campaign_type']) && $data['campaign_type'] === 'Email') {
            if (empty($data['email_template_id'])) {
                throw new ValidationException("Email campaigns require an email template");
            }
        }
    }
}