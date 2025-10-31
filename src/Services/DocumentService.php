<?php
/**
 * Document Service
 *
 * Business logic for SuiteCRM Documents operations.
 * Handles document management, file attachments, and document versioning.
 *
 * @package SuiteAPI
 * @subpackage Services
 * @author AI Assistant
 * @since 1.0.0
 *
 * @requirement REQ-SUITE-035: Document Service Implementation
 * @requirement REQ-SUITE-036: Document Validation Rules
 *
 * UML Class Diagram:
 * ```
 * +---------------------+
 * |  DocumentService    |
 * +---------------------+
 * | -requiredFields     |
 * | -validationRules    |
 * +---------------------+
 * | +createDocument()   |
 * | +updateDocument()   |
 * | +findByCategory()   |
 * | +uploadFile()       |
 * +---------------------+
 * ```
 */

namespace SuiteAPI\Services;

use SuiteAPI\Interfaces\SuiteCrmApiInterface;
use SuiteAPI\Exceptions\SuiteApiException;
use SuiteAPI\Exceptions\ValidationException;

/**
 * Document Service
 *
 * Provides business logic for managing SuiteCRM Documents.
 * Includes document lifecycle, file management, and attachment handling.
 */
class DocumentService extends BaseSuiteCrmService
{
    /**
     * Required fields for document creation
     *
     * @var array
     */
    protected $requiredFields = [
        'document_name'
    ];

    /**
     * Validation rules for document fields
     *
     * @var array
     */
    protected $validationRules = [
        'document_name' => [
            'max_length' => 255
        ],
        'description' => [
            'max_length' => 65535
        ],
        'category' => [
            'max_length' => 100
        ],
        'type' => [
            'max_length' => 100
        ],
        'revision' => [
            'max_length' => 100
        ],
        'file_upload_path' => [
            'max_length' => 255
        ],
        'save_filename' => [
            'max_length' => 255
        ],
        'status' => [
            'in' => ['Active', 'Inactive', 'Draft']
        ],
        'parent_type' => [
            'in' => [
                'Accounts', 'Contacts', 'Opportunities', 'Leads', 'Cases',
                'Bugs', 'Projects', 'ProjectTask', 'Tasks', 'Meetings', 'Calls'
            ]
        ]
    ];

    /**
     * Constructor
     *
     * @param SuiteCrmApiInterface $api SuiteCRM API instance
     */
    public function __construct(SuiteCrmApiInterface $api)
    {
        parent::__construct($api, 'Documents');
    }

    /**
     * Create a new document
     *
     * @param array $documentData Document data
     * @return string|null Created document ID
     * @throws ValidationException
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-035
     */
    public function createDocument(array $documentData): ?string
    {
        // Set default status if not provided
        if (!isset($documentData['status'])) {
            $documentData['status'] = 'Active';
        }

        // Set default revision if not provided
        if (!isset($documentData['revision'])) {
            $documentData['revision'] = '1.0';
        }

        return $this->create($documentData);
    }

    /**
     * Update an existing document
     *
     * @param string $documentId Document ID
     * @param array $documentData Updated document data
     * @return bool True on success
     * @throws ValidationException
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-035
     */
    public function updateDocument(string $documentId, array $documentData): bool
    {
        return $this->update($documentId, $documentData);
    }

    /**
     * Find document by ID
     *
     * @param string $documentId Document ID
     * @return array|null Document data or null if not found
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-035
     */
    public function findDocumentById(string $documentId): ?array
    {
        return $this->findById($documentId);
    }

    /**
     * Find documents by category
     *
     * @param string $category Document category
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of matching documents
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-035
     */
    public function findDocumentsByCategory(string $category, int $limit = 20, int $offset = 0): array
    {
        return $this->search(
            ['category' => $category],
            [],
            $limit,
            $offset
        );
    }

    /**
     * Find documents by type
     *
     * @param string $type Document type
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of matching documents
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-035
     */
    public function findDocumentsByType(string $type, int $limit = 20, int $offset = 0): array
    {
        return $this->search(
            ['type' => $type],
            [],
            $limit,
            $offset
        );
    }

    /**
     * Find documents by parent record
     *
     * @param string $parentType Parent record type
     * @param string $parentId Parent record ID
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of matching documents
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-035
     */
    public function findDocumentsByParent(string $parentType, string $parentId, int $limit = 20, int $offset = 0): array
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
     * Find documents by status
     *
     * @param string $status Document status
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of matching documents
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-035
     */
    public function findDocumentsByStatus(string $status, int $limit = 20, int $offset = 0): array
    {
        return $this->search(
            ['status' => $status],
            [],
            $limit,
            $offset
        );
    }

    /**
     * Find active documents
     *
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of active documents
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-035
     */
    public function findActiveDocuments(int $limit = 20, int $offset = 0): array
    {
        return $this->findDocumentsByStatus('Active', $limit, $offset);
    }

    /**
     * Create document with file attachment
     *
     * @param array $documentData Document data
     * @param string $filePath Path to file to upload
     * @param string $fileName Original filename
     * @return string|null Created document ID
     * @throws ValidationException
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-035
     */
    public function createDocumentWithFile(array $documentData, string $filePath, string $fileName): ?string
    {
        // Validate file exists
        if (!file_exists($filePath)) {
            throw new ValidationException(
                "File does not exist: {$filePath}",
                ['file_path' => 'File does not exist']
            );
        }

        // Add file information to document data
        $documentData['save_filename'] = $fileName;
        $documentData['file_upload_path'] = $filePath;

        // Note: In a real implementation, you would upload the file to SuiteCRM
        // and get back the file URL/path. For now, we'll just create the document record.

        return $this->createDocument($documentData);
    }

    /**
     * Create new revision of document
     *
     * @param string $documentId Original document ID
     * @param array $revisionData Revision data
     * @return string|null New revision document ID
     * @throws ValidationException
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-035
     */
    public function createDocumentRevision(string $documentId, array $revisionData): ?string
    {
        $original = $this->findById($documentId);

        if (!$original) {
            throw new SuiteApiException("Document not found: {$documentId}");
        }

        // Create revision data based on original
        $revisionData = array_merge([
            'document_name' => $original['document_name'] ?? '',
            'description' => $original['description'] ?? '',
            'category' => $original['category'] ?? '',
            'type' => $original['type'] ?? '',
            'parent_type' => $original['parent_type'] ?? null,
            'parent_id' => $original['parent_id'] ?? null,
            'status' => 'Active'
        ], $revisionData);

        // Auto-increment revision number
        $currentRevision = $original['revision'] ?? '1.0';
        $revisionData['revision'] = $this->incrementRevision($currentRevision);

        return $this->createDocument($revisionData);
    }

    /**
     * Get document statistics
     *
     * @return array Document statistics
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-035
     */
    public function getDocumentStatistics(): array
    {
        $documents = $this->search([], ['status', 'category', 'type'], 1000);

        $stats = [
            'total' => count($documents),
            'by_status' => [],
            'by_category' => [],
            'by_type' => [],
            'active' => 0
        ];

        foreach ($documents as $document) {
            $status = $document['status'] ?? 'Unknown';
            $category = $document['category'] ?? 'Unknown';
            $type = $document['type'] ?? 'Unknown';

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

            // Count by type
            if (!isset($stats['by_type'][$type])) {
                $stats['by_type'][$type] = 0;
            }
            $stats['by_type'][$type]++;

            // Count active documents
            if ($status === 'Active') {
                $stats['active']++;
            }
        }

        return $stats;
    }

    /**
     * Increment revision number
     *
     * @param string $currentRevision Current revision string
     * @return string Next revision string
     */
    private function incrementRevision(string $currentRevision): string
    {
        // Try to parse as major.minor format
        if (preg_match('/^(\d+)\.(\d+)$/', $currentRevision, $matches)) {
            $major = (int) $matches[1];
            $minor = (int) $matches[2];
            return $major . '.' . ($minor + 1);
        }

        // If not in expected format, append .1
        return $currentRevision . '.1';
    }
}