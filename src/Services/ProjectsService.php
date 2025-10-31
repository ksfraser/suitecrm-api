<?php
/**
 * Projects Service
 *
 * Service for managing SuiteCRM Projects module.
 * Handles project lifecycle, task management, and resource allocation.
 *
 * @package SuiteAPI
 * @subpackage Services
 * @author AI Assistant
 * @since 1.0.0
 *
 * @requirement REQ-SUITE-017: Projects Module Support
 * @requirement REQ-SUITE-030: Project Management Integration
 *
 * UML Class Diagram:
 * ```
 * +---------------------+
 * |    ProjectsService  |
 * +---------------------+
 * | -api: SuiteCrmApiInterface |
 * | -moduleName: "Project" |
 * +---------------------+
 * | +createProject()    |
 * | +updateProject()    |
 * | +findProjectById()  |
 * | +searchProjects()   |
 * | +deleteProject()    |
 * | +addProjectTask()   |
 * | +getProjectTasks()  |
 * | +getProjectStatus() |
 * | +assignResources()  |
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
 * Projects Service
 *
 * Manages SuiteCRM Projects with task management and resource allocation.
 */
class ProjectsService extends BaseSuiteCrmService
{
    /**
     * Constructor
     *
     * @param SuiteCrmApiInterface $api SuiteCRM API instance
     */
    public function __construct(SuiteCrmApiInterface $api)
    {
        parent::__construct($api, 'Project');

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
            'priority' => [
                'in' => ['Low', 'Medium', 'High', 'Urgent']
            ],
            'estimated_start_date' => [
                // Date validation would be handled by SuiteCRM
            ],
            'estimated_end_date' => [
                // Date validation would be handled by SuiteCRM
            ]
        ];
    }

    /**
     * Create a new project
     *
     * @param array $data Project data
     * @return string|null Created project ID
     * @throws ValidationException
     * @throws SuiteApiException
     */
    public function createProject(array $data): ?string
    {
        return $this->create($data);
    }

    /**
     * Update an existing project
     *
     * @param string $id Project ID
     * @param array $data Updated project data
     * @return bool True on success
     * @throws ValidationException
     * @throws SuiteApiException
     */
    public function updateProject(string $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    /**
     * Find project by ID
     *
     * @param string $id Project ID
     * @param array $fields Optional fields to retrieve
     * @return array|null Project data or null if not found
     * @throws SuiteApiException
     */
    public function findProjectById(string $id, array $fields = []): ?array
    {
        return $this->findById($id, $fields);
    }

    /**
     * Search for projects
     *
     * @param array $criteria Search criteria
     * @param array $fields Fields to return
     * @param int $limit Maximum results
     * @param int $offset Result offset
     * @return array Array of matching projects
     * @throws SuiteApiException
     */
    public function searchProjects(array $criteria = [], array $fields = [], int $limit = 20, int $offset = 0): array
    {
        return $this->search($criteria, $fields, $limit, $offset);
    }

    /**
     * Delete a project
     *
     * @param string $id Project ID
     * @return bool True on success
     * @throws SuiteApiException
     */
    public function deleteProject(string $id): bool
    {
        return $this->delete($id);
    }

    /**
     * Add a task to a project
     *
     * @param string $projectId Project ID
     * @param array $taskData Task data
     * @return string|null Created task ID
     * @throws ValidationException
     * @throws SuiteApiException
     */
    public function addProjectTask(string $projectId, array $taskData): ?string
    {
        // Validate project exists
        $project = $this->findProjectById($projectId);
        if (!$project) {
            throw new ValidationException("Project with ID '{$projectId}' does not exist");
        }

        // Set project relationship
        $taskData['parent_type'] = 'Project';
        $taskData['parent_id'] = $projectId;

        // Use TaskService to create the task
        $taskService = new TaskService($this->api);
        return $taskService->createTask($taskData);
    }

    /**
     * Get all tasks for a project
     *
     * @param string $projectId Project ID
     * @param array $fields Task fields to return
     * @return array Array of project tasks
     * @throws SuiteApiException
     */
    public function getProjectTasks(string $projectId): array
    {
        $taskService = new TaskService($this->api);
        return $taskService->search([
            'parent_type' => 'Project',
            'parent_id' => $projectId
        ]);
    }

    /**
     * Get project status with progress information
     *
     * @param string $projectId Project ID
     * @return array Project status information
     * @throws SuiteApiException
     */
    public function getProjectStatus(string $projectId): array
    {
        $project = $this->findProjectById($projectId);
        if (!$project) {
            throw new ValidationException("Project with ID '{$projectId}' does not exist");
        }

        $tasks = $this->getProjectTasks($projectId);
        $totalTasks = count($tasks);
        $completedTasks = count(array_filter($tasks, function($task) {
            return $task['status'] === 'Completed';
        }));

        $progress = $totalTasks > 0 ? ($completedTasks / $totalTasks) * 100 : 0;

        return [
            'project' => $project,
            'total_tasks' => $totalTasks,
            'completed_tasks' => $completedTasks,
            'progress_percentage' => round($progress, 2),
            'status' => $project['status']
        ];
    }

    /**
     * Assign resources to a project
     *
     * @param string $projectId Project ID
     * @param array $userIds Array of user IDs to assign
     * @return bool True on success
     * @throws ValidationException
     * @throws SuiteApiException
     */
    public function assignResources(string $projectId, array $userIds): bool
    {
        // Validate project exists
        $project = $this->findProjectById($projectId);
        if (!$project) {
            throw new ValidationException("Project with ID '{$projectId}' does not exist");
        }

        // Validate users exist
        $userService = new UserService($this->api);
        foreach ($userIds as $userId) {
            $user = $userService->findUserById($userId);
            if (!$user) {
                throw new ValidationException("User with ID '{$userId}' does not exist");
            }
        }

        // Add relationships (this would depend on SuiteCRM's relationship structure)
        // For now, we'll update the project with assigned users
        $assignedUsers = isset($project['assigned_user_ids']) ? $project['assigned_user_ids'] : [];
        $assignedUsers = array_unique(array_merge($assignedUsers, $userIds));

        return $this->updateProject($projectId, [
            'assigned_user_ids' => $assignedUsers
        ]);
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
            'modified_user_id' => 'User'
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
        if (isset($data['estimated_start_date']) && isset($data['estimated_end_date'])) {
            $startDate = strtotime($data['estimated_start_date']);
            $endDate = strtotime($data['estimated_end_date']);

            if ($startDate && $endDate && $startDate > $endDate) {
                throw new ValidationException("Project start date cannot be after end date");
            }
        }

        // Validate priority for active projects
        if (isset($data['status']) && $data['status'] === 'Active' && !isset($data['priority'])) {
            throw new ValidationException("Priority is required for active projects");
        }
    }
}