<?php
/**
 * Note Service
 *
 * Business logic for SuiteCRM Notes operations.
 * Handles note creation, attachment management, and annotation tracking.
 *
 * @package SuiteAPI
 * @subpackage Services
 * @author AI Assistant
 * @since 1.0.0
 *
 * @requirement REQ-SUITE-043: Note Service Implementation
 * @requirement REQ-SUITE-044: Note Validation Rules
 *
 * UML Class Diagram:
 * ```
 * +---------------------+
 * |    NoteService      |
 * +---------------------+
 * | -requiredFields     |
 * | -validationRules    |
 * +---------------------+
 * | +createNote()       |
 * | +updateNote()       |
 * | +findByParent()     |
 * | +attachFile()       |
 * +---------------------+
 * ```
 */

namespace Ksfraser\Ksfraser\SuiteAPI\Services;

use Ksfraser\SuiteAPI\Interfaces\SuiteCrmApiInterface;
use Ksfraser\SuiteAPI\Exceptions\SuiteApiException;
use Ksfraser\SuiteAPI\Exceptions\ValidationException;

/**
 * Note Service
 *
 * Provides business logic for managing SuiteCRM Notes.
 * Includes note creation, attachment management, and annotation tracking.
 */
class NoteService extends BaseSuiteCrmService
{
    /**
     * Required fields for note creation
     *
     * @var array
     */
    protected $requiredFields = [
        'name'
    ];

    /**
     * Validation rules for note fields
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
        'filename' => [
            'max_length' => 255
        ],
        'file_mime_type' => [
            'max_length' => 100
        ],
        'parent_type' => [
            'in' => [
                'Accounts', 'Contacts', 'Opportunities', 'Leads', 'Cases',
                'Bugs', 'Projects', 'ProjectTask', 'Tasks', 'Meetings', 'Calls', 'Emails'
            ]
        ],
        'contact_name' => [
            'max_length' => 255
        ],
        'note_source' => [
            'in' => ['internal', 'external', 'web', 'email', 'api']
        ],
        'sms_number' => [
            'max_length' => 50
        ],
        'portal_flag' => [
            'in' => ['0', '1']
        ],
        'embed_flag' => [
            'in' => ['0', '1']
        ]
    ];

    /**
     * Constructor
     *
     * @param SuiteCrmApiInterface $api SuiteCRM API instance
     */
    public function __construct(SuiteCrmApiInterface $api)
    {
        parent::__construct($api, 'Notes');
    }

    /**
     * Create a new note
     *
     * @param array $noteData Note data
     * @return string|null Created note ID
     * @throws ValidationException
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-043
     */
    public function createNote(array $noteData): ?string
    {
        // Set default note source if not provided
        if (!isset($noteData['note_source'])) {
            $noteData['note_source'] = 'internal';
        }

        return $this->create($noteData);
    }

    /**
     * Create a note with file attachment
     *
     * @param array $noteData Note data
     * @param string $filePath Path to file to attach
     * @param string $filename Original filename
     * @param string $mimeType MIME type of the file
     * @return string|null Created note ID
     * @throws ValidationException
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-043
     */
    public function createNoteWithAttachment(array $noteData, string $filePath, string $filename, string $mimeType): ?string
    {
        // Validate file exists
        if (!file_exists($filePath)) {
            throw new ValidationException(
                "Attachment file does not exist: {$filePath}",
                ['filePath' => 'File must exist']
            );
        }

        // Add file information to note data
        $noteData['filename'] = $filename;
        $noteData['file_mime_type'] = $mimeType;
        $noteData['file_upload_path'] = $filePath;

        // Set default note source if not provided
        if (!isset($noteData['note_source'])) {
            $noteData['note_source'] = 'internal';
        }

        // Create the note first
        $noteId = $this->create($noteData);

        if ($noteId) {
            // Upload the file attachment
            $this->attachFileToNote($noteId, $filePath);
        }

        return $noteId;
    }

    /**
     * Update an existing note
     *
     * @param string $noteId Note ID
     * @param array $noteData Updated note data
     * @return bool True on success
     * @throws ValidationException
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-043
     */
    public function updateNote(string $noteId, array $noteData): bool
    {
        return $this->update($noteId, $noteData);
    }

    /**
     * Find note by ID
     *
     * @param string $noteId Note ID
     * @return array|null Note data or null if not found
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-043
     */
    public function findNoteById(string $noteId): ?array
    {
        return $this->findById($noteId);
    }

    /**
     * Find notes by parent record
     *
     * @param string $parentType Parent module type
     * @param string $parentId Parent record ID
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of matching notes
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-043
     */
    public function findNotesByParent(string $parentType, string $parentId, int $limit = 20, int $offset = 0): array
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
     * Find notes by assigned user
     *
     * @param string $userId User ID
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of matching notes
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-043
     */
    public function findNotesByAssignedUser(string $userId, int $limit = 20, int $offset = 0): array
    {
        return $this->search(
            ['assigned_user_id' => $userId],
            [],
            $limit,
            $offset
        );
    }

    /**
     * Find notes by contact
     *
     * @param string $contactId Contact ID
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of matching notes
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-043
     */
    public function findNotesByContact(string $contactId, int $limit = 20, int $offset = 0): array
    {
        return $this->search(
            ['contact_id' => $contactId],
            [],
            $limit,
            $offset
        );
    }

    /**
     * Find notes by subject
     *
     * @param string $subject Subject to search for
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of matching notes
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-043
     */
    public function findNotesBySubject(string $subject, int $limit = 20, int $offset = 0): array
    {
        return $this->search(
            ['name' => ['operator' => 'contains', 'value' => $subject]],
            [],
            $limit,
            $offset
        );
    }

    /**
     * Find notes with attachments
     *
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of notes with attachments
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-043
     */
    public function findNotesWithAttachments(int $limit = 20, int $offset = 0): array
    {
        return $this->search(
            ['filename' => ['operator' => 'not_empty']],
            [],
            $limit,
            $offset
        );
    }

    /**
     * Attach file to existing note
     *
     * @param string $noteId Note ID
     * @param string $filePath Path to file to attach
     * @return bool True on success
     * @throws ValidationException
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-043
     */
    public function attachFileToNote(string $noteId, string $filePath): bool
    {
        // Validate file exists
        if (!file_exists($filePath)) {
            throw new ValidationException(
                "Attachment file does not exist: {$filePath}",
                ['filePath' => 'File must exist']
            );
        }

        // This would typically involve calling the SuiteCRM API's file upload functionality
        // For now, we'll update the note with file information
        // In a real implementation, this would upload the file and link it to the note

        $fileInfo = pathinfo($filePath);
        $updateData = [
            'filename' => $fileInfo['basename'],
            'file_mime_type' => mime_content_type($filePath) ?: 'application/octet-stream'
        ];

        return $this->update($noteId, $updateData);
    }

    /**
     * Create a quick note/annotation
     *
     * @param string $subject Note subject
     * @param string $description Note description
     * @param string|null $parentType Parent module type
     * @param string|null $parentId Parent record ID
     * @return string|null Created note ID
     * @throws ValidationException
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-043
     */
    public function createQuickNote(string $subject, string $description, ?string $parentType = null, ?string $parentId = null): ?string
    {
        $noteData = [
            'name' => $subject,
            'description' => $description,
            'note_source' => 'internal'
        ];

        if ($parentType && $parentId) {
            $noteData['parent_type'] = $parentType;
            $noteData['parent_id'] = $parentId;
        }

        return $this->create($noteData);
    }

    /**
     * Get note statistics
     *
     * @return array Note statistics
     * @throws SuiteApiException
     *
     * @requirement REQ-SUITE-043
     */
    public function getNoteStatistics(): array
    {
        $notes = $this->search([], ['note_source', 'parent_type'], 1000);

        $stats = [
            'total' => count($notes),
            'by_source' => [],
            'by_parent_type' => [],
            'with_attachments' => 0,
            'without_attachments' => 0
        ];

        foreach ($notes as $note) {
            $source = $note['note_source'] ?? 'Unknown';
            $parentType = $note['parent_type'] ?? 'None';
            $filename = $note['filename'] ?? '';

            // Count by source
            if (!isset($stats['by_source'][$source])) {
                $stats['by_source'][$source] = 0;
            }
            $stats['by_source'][$source]++;

            // Count by parent type
            if (!isset($stats['by_parent_type'][$parentType])) {
                $stats['by_parent_type'][$parentType] = 0;
            }
            $stats['by_parent_type'][$parentType]++;

            // Count attachments
            if (!empty($filename)) {
                $stats['with_attachments']++;
            } else {
                $stats['without_attachments']++;
            }
        }

        return $stats;
    }

    /**
     * Preprocess note data
     *
     * @param array $data Raw note data
     * @return array Processed note data
     */
    protected function preprocessData(array $data): array
    {
        $processed = parent::preprocessData($data);

        // Validate file information if filename is provided
        if (isset($processed['filename']) && !empty($processed['filename'])) {
            if (!isset($processed['file_mime_type']) || empty($processed['file_mime_type'])) {
                // Try to determine MIME type from filename
                $extension = strtolower(pathinfo($processed['filename'], PATHINFO_EXTENSION));
                $mimeTypes = [
                    'pdf' => 'application/pdf',
                    'doc' => 'application/msword',
                    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'xls' => 'application/vnd.ms-excel',
                    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'txt' => 'text/plain',
                    'jpg' => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'png' => 'image/png',
                    'gif' => 'image/gif'
                ];

                $processed['file_mime_type'] = $mimeTypes[$extension] ?? 'application/octet-stream';
            }
        }

        // Ensure boolean flags are properly formatted
        $booleanFields = ['portal_flag', 'embed_flag'];
        foreach ($booleanFields as $field) {
            if (isset($processed[$field])) {
                $processed[$field] = $processed[$field] ? '1' : '0';
            }
        }

        return $processed;
    }
}