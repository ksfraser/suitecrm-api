<?php
/**
 * Projects Service Test
 *
 * Unit tests for ProjectsService class.
 *
 * @package SuiteAPI
 * @subpackage Tests
 * @author AI Assistant
 * @since 1.0.0
 */

namespace Ksfraser\SuiteAPI\Tests\Services;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Ksfraser\SuiteAPI\Interfaces\SuiteCrmApiInterface;
use Ksfraser\SuiteAPI\Services\ProjectsService;
use Ksfraser\SuiteAPI\Exceptions\ValidationException;
use Ksfraser\SuiteAPI\Exceptions\SuiteApiException;

/**
 * Test cases for ProjectsService
 */
class ProjectsServiceTest extends TestCase
{
    /**
     * @var SuiteCrmApiInterface&MockObject
     */
    private $apiMock;

    /**
     * @var ProjectsService
     */
    private $projectsService;

    /**
     * Set up test environment
     */
    protected function setUp(): void
    {
        $this->apiMock = $this->createMock(SuiteCrmApiInterface::class);
        $this->projectsService = new ProjectsService($this->apiMock);
    }

    /**
     * Test successful project creation
     */
    public function testCreateProjectSuccess(): void
    {
        $projectData = [
            'name' => 'Test Project',
            'status' => 'Planning',
            'description' => 'A test project'
        ];

        $expectedId = 'test-project-id';

        $this->apiMock->expects($this->once())
            ->method('createRecord')
            ->with('Project', $this->anything())
            ->willReturn($expectedId);

        $result = $this->projectsService->createProject($projectData);

        $this->assertEquals($expectedId, $result);
    }

    /**
     * Test project creation with validation error
     */
    public function testCreateProjectValidationError(): void
    {
        $this->expectException(ValidationException::class);

        $invalidData = [
            'status' => 'Planning'
            // Missing required 'name' field
        ];

        $this->projectsService->createProject($invalidData);
    }

    /**
     * Test finding project by ID
     */
    public function testFindProjectById(): void
    {
        $projectId = 'test-project-id';
        $expectedData = [
            'id' => $projectId,
            'name' => 'Test Project',
            'status' => 'Active'
        ];

        $this->apiMock->expects($this->once())
            ->method('getRecord')
            ->with('Project', $projectId, [])
            ->willReturn($expectedData);

        $result = $this->projectsService->findProjectById($projectId);

        $this->assertEquals($expectedData, $result);
    }

    /**
     * Test adding project task
     */
    public function testAddProjectTask(): void
    {
        $projectId = 'test-project-id';
        $taskData = [
            'name' => 'Test Task',
            'status' => 'Not Started'
        ];
        $expectedTaskId = 'test-task-id';

        // Mock project exists
        $this->apiMock->expects($this->at(0))
            ->method('getRecord')
            ->with('Project', $projectId)
            ->willReturn(['id' => $projectId, 'name' => 'Test Project']);

        // Mock task creation (this would actually call TaskService)
        // For this test, we'll assume the method works
        $this->apiMock->expects($this->at(1))
            ->method('createRecord')
            ->with('Tasks', $this->callback(function($data) use ($projectId) {
                return isset($data['parent_type']) &&
                       $data['parent_type'] === 'Project' &&
                       $data['parent_id'] === $projectId;
            }))
            ->willReturn($expectedTaskId);

        // Note: This test would need to be adjusted based on actual TaskService integration
        // For now, it demonstrates the expected behavior
        $this->markTestIncomplete('TaskService integration needs to be mocked properly');
    }

    /**
     * Test project status calculation
     */
    public function testGetProjectStatus(): void
    {
        $projectId = 'test-project-id';
        $projectData = [
            'id' => $projectId,
            'name' => 'Test Project',
            'status' => 'Active'
        ];

        $this->apiMock->expects($this->once())
            ->method('getRecord')
            ->with('Project', $projectId)
            ->willReturn($projectData);

        // Mock task search (simplified)
        $this->apiMock->expects($this->once())
            ->method('searchRecords')
            ->willReturn([]);

        $result = $this->projectsService->getProjectStatus($projectId);

        $this->assertArrayHasKey('project', $result);
        $this->assertArrayHasKey('total_tasks', $result);
        $this->assertArrayHasKey('progress_percentage', $result);
        $this->assertEquals(0, $result['progress_percentage']);
    }

    /**
     * Test relationship validation
     */
    public function testRelationshipValidation(): void
    {
        $projectData = [
            'name' => 'Test Project',
            'status' => 'Planning',
            'assigned_user_id' => 'invalid-user-id'
        ];

        $this->apiMock->expects($this->once())
            ->method('getRecord')
            ->with('User', 'invalid-user-id')
            ->willReturn(null); // User doesn't exist

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Relationship validation failed for Project');

        $this->projectsService->createProject($projectData);
    }

    /**
     * Test business logic validation - end date before start date
     */
    public function testBusinessLogicValidation(): void
    {
        $projectData = [
            'name' => 'Test Project',
            'status' => 'Planning',
            'estimated_start_date' => '2024-12-31',
            'estimated_end_date' => '2024-01-01' // End before start
        ];

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Project start date cannot be after end date');

        $this->projectsService->createProject($projectData);
    }
}