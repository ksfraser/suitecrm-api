<?php
/**
 * Product Service
 *
 * Business logic for SuiteCRM AOS_Products operations.
 * Handles product catalog management, pricing, and inventory.
 *
 * @package SuiteAPI
 * @subpackage Services
 * @author AI Assistant
 * @since 1.0.0
 *
 * @requirement REQ-SUITE-029: Product Service Implementation
 * @requirement REQ-SUITE-030: Product Validation Rules
 *
 * UML Class Diagram:
 * ```
 * +---------------------+
 * |    ProductService   |
 * +---------------------+
 * | -requiredFields     |
 * | -validationRules    |
 * +---------------------+
 * | +createProduct()    |
 * | +updateProduct()    |
 * | +findByCategory()   |
 * | +updatePricing()    |
 * +---------------------+
 * ```
 */

namespace SuiteAPI\Services;

use SuiteAPI\Interfaces\SuiteCrmApiInterface;
use SuiteAPI\Exceptions\SuiteApiException;
use SuiteAPI\Exceptions\ValidationException;

/**
 * Product Service
 *
 * Provides business logic for managing SuiteCRM AOS_Products.
 * Includes validation, pricing management, and product catalog operations.
 */
class ProductService extends BaseSuiteCrmService
{
    /**
     * Required fields for product creation
     *
     * @var array
     */
    protected $requiredFields = [
        'name'
    ];

    /**
     * Validation rules for product fields
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
        'maincode' => [
            'max_length' => 100
        ],
        'part_number' => [
            'max_length' => 100
        ],
        'category' => [
            'max_length' => 255
        ],
        'type' => [
            'in' => ['Good', 'Service']
        ],
        'cost' => [
            // Should be numeric and non-negative
        ],
        'price' => [
            // Should be numeric and non-negative
        ],
        'product_url' => [
            'max_length' => 255
        ],
        'status' => [
            'in' => ['Active', 'Inactive', 'Discontinued']
        ],
        'currency_id' => [
            // Should be a valid currency ID
        ]
    ];

    /**
     * Constructor
     *
     * @param SuiteCrmApiInterface $api SuiteCRM API instance
     */
    public function __construct(SuiteCrmApiInterface $api)
    {
        parent::__construct($api, 'AOS_Products');
    }

    /**
     * Create a new product
     *
     * @param array $productData Product data
     * @return string|null Created product ID
     * @throws ValidationException
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-029
     */
    public function createProduct(array $productData): ?string
    {
        // Set default status if not provided
        if (!isset($productData['status'])) {
            $productData['status'] = 'Active';
        }

        // Set default type if not provided
        if (!isset($productData['type'])) {
            $productData['type'] = 'Good';
        }

        return $this->create($productData);
    }

    /**
     * Update an existing product
     *
     * @param string $productId Product ID
     * @param array $productData Updated product data
     * @return bool True on success
     * @throws ValidationException
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-029
     */
    public function updateProduct(string $productId, array $productData): bool
    {
        return $this->update($productId, $productData);
    }

    /**
     * Find product by ID
     *
     * @param string $productId Product ID
     * @return array|null Product data or null if not found
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-029
     */
    public function findProductById(string $productId): ?array
    {
        return $this->findById($productId);
    }

    /**
     * Find products by category
     *
     * @param string $category Product category
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of matching products
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-029
     */
    public function findProductsByCategory(string $category, int $limit = 20, int $offset = 0): array
    {
        return $this->search(
            ['aos_product_category' => $category],
            [],
            $limit,
            $offset
        );
    }

    /**
     * Find products by type
     *
     * @param string $type Product type (Good/Service)
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of matching products
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-029
     */
    public function findProductsByType(string $type, int $limit = 20, int $offset = 0): array
    {
        return $this->search(
            ['type' => $type],
            [],
            $limit,
            $offset
        );
    }

    /**
     * Find products by status
     *
     * @param string $status Product status
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of matching products
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-029
     */
    public function findProductsByStatus(string $status, int $limit = 20, int $offset = 0): array
    {
        return $this->search(
            ['status' => $status],
            [],
            $limit,
            $offset
        );
    }

    /**
     * Find active products
     *
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of active products
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-029
     */
    public function findActiveProducts(int $limit = 20, int $offset = 0): array
    {
        return $this->findProductsByStatus('Active', $limit, $offset);
    }

    /**
     * Update product pricing
     *
     * @param string $productId Product ID
     * @param float $cost New cost
     * @param float $price New price
     * @param string|null $currencyId Currency ID (optional)
     * @return bool True on success
     * @throws ValidationException
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-029
     */
    public function updatePricing(string $productId, float $cost, float $price, ?string $currencyId = null): bool
    {
        $updateData = [
            'cost' => $cost,
            'price' => $price
        ];

        if ($currencyId) {
            $updateData['currency_id'] = $currencyId;
        }

        return $this->update($productId, $updateData);
    }

    /**
     * Get product catalog statistics
     *
     * @return array Product statistics
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-029
     */
    public function getProductStatistics(): array
    {
        $products = $this->search([], ['type', 'status', 'aos_product_category'], 1000);

        $stats = [
            'total' => count($products),
            'by_type' => [],
            'by_status' => [],
            'by_category' => [],
            'active' => 0
        ];

        foreach ($products as $product) {
            $type = $product['type'] ?? 'Unknown';
            $status = $product['status'] ?? 'Unknown';
            $category = $product['aos_product_category'] ?? 'Unknown';

            // Count by type
            if (!isset($stats['by_type'][$type])) {
                $stats['by_type'][$type] = 0;
            }
            $stats['by_type'][$type]++;

            // Count by status
            if (!isset($stats['by_status'][$status])) {
                $stats['by_status'][$status] = 0;
            }
            $stats['by_status'][$status]++;

            // Count by category
            if (!isset($stats['by_category'][$category])) {
                $stats['by_category'][$category] = 0;
            }
            $stats['by_category'][$category]++;

            // Count active products
            if ($status === 'Active') {
                $stats['active']++;
            }
        }

        return $stats;
    }

    /**
     * Preprocess product data
     *
     * @param array $data Raw product data
     * @return array Processed product data
     */
    protected function preprocessData(array $data): array
    {
        $processed = parent::preprocessData($data);

        // Ensure numeric fields are properly formatted
        $numericFields = ['cost', 'cost_usdollar', 'price', 'price_usdollar'];
        foreach ($numericFields as $field) {
            if (isset($processed[$field])) {
                $processed[$field] = (float) $processed[$field];
            }
        }

        // Validate pricing logic
        if (isset($processed['cost']) && isset($processed['price'])) {
            if ($processed['cost'] > $processed['price']) {
                // Cost should not exceed price, but we'll allow it for now
                // Could add business rule validation here
            }
        }

        return $processed;
    }

    /**
     * Get relationship fields that need validation
     *
     * @return array Map of field names to module names
     */
    protected function getRelationshipFields(): array
    {
        return [
            'aos_product_category_id' => 'AOS_Product_Categories'
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
        $errors = [];

        // Validate pricing logic
        if (isset($data['cost']) && isset($data['price'])) {
            $cost = (float) $data['cost'];
            $price = (float) $data['price'];

            if ($cost < 0) {
                $errors[] = "Cost cannot be negative";
            }

            if ($price < 0) {
                $errors[] = "Price cannot be negative";
            }

            // Warning if cost exceeds price, but allow it
            if ($cost > $price) {
                // Could log a warning here, but don't block the operation
            }
        }

        // Validate currency consistency
        if (isset($data['currency_id']) && isset($data['currency_id_usdollar'])) {
            if ($data['currency_id'] !== $data['currency_id_usdollar']) {
                $errors[] = "Base currency and USD currency should match";
            }
        }

        if (!empty($errors)) {
            throw new ValidationException(
                "Business logic validation failed for products",
                $errors
            );
        }
    }
}