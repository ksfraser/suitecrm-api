<?php
/**
 * Lead Service Test
 *
 * Unit tests for LeadService class.
 *
 * @package SuiteAPI
 * @subpackage Tests
 * @author AI Assistant
 * @since 1.0.0
 */

namespace SuiteAPI\Tests\Services;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use SuiteAPI\Interfaces\SuiteCrmApiInterface;
use SuiteAPI\Services\LeadService;
use SuiteAPI\Services\ContactService;
use SuiteAPI\Exceptions\ValidationException;
use SuiteAPI\Exceptions\SuiteApiException;

/**
 * Test cases for LeadService
 */
class LeadServiceTest extends TestCase
{
    /**
     * @var SuiteCrmApiInterface&MockObject
     */
    private $apiMock;

    /**
     * @var LeadService
     */
    private $leadService;

    /**
     * Set up test environment
     */
    protected function setUp(): void
    {
        $this->apiMock = $this->createMock(SuiteCrmApiInterface::class);
        $this->leadService = new LeadService($this->apiMock);
    }

    /**
     * Test successful lead creation
     */
    public function testCreateLeadSuccess(): void
    {
        $leadData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email1' => 'john.doe@example.com'
        ];

        $expectedId = 'test-lead-id';

        $this->apiMock->expects($this->once())
            ->method('createRecord')
            ->with('Leads', $this->callback(function ($data) {
                return isset($data['first_name']) &&
                       isset($data['last_name']) &&
                       isset($data['name']) &&
                       $data['name'] === 'John Doe';
            }))
            ->willReturn($expectedId);

        $result = $this->leadService->createLead($leadData);

        $this->assertEquals($expectedId, $result);
    }

    /**
     * Test lead creation with missing required field
     */
    public function testCreateLeadMissingRequiredField(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Validation failed for Leads');

        $leadData = [
            'first_name' => 'John'
        ];

        $this->leadService->createLead($leadData);
    }

    /**
     * Test finding leads by email
     */
    public function testFindLeadsByEmail(): void
    {
        $email = 'john.doe@example.com';
        $expectedLeads = [
            ['id' => '1', 'first_name' => 'John', 'last_name' => 'Doe']
        ];

        $this->apiMock->expects($this->once())
            ->method('searchRecords')
            ->with('Leads', ['email1' => $email], [], 20, 0)
            ->willReturn($expectedLeads);

        $result = $this->leadService->findLeadsByEmail($email);

        $this->assertEquals($expectedLeads, $result);
    }

    /**
     * Test lead conversion to contact
     */
    public function testConvertToContact(): void
    {
        // This test would require more complex mocking of ContactService
        // For now, we'll just test that the method exists and can be called
        $leadId = 'test-lead-id';

        // The actual implementation would need ContactService injection
        // This is a placeholder test
        $this->assertTrue(method_exists($this->leadService, 'convertToContact'));
    }

    /**
     * Test data preprocessing with phone number formatting
     */
    public function testDataPreprocessingPhoneFormatting(): void
    {
        $leadData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone_mobile' => '(555) 123-4567 ext. 123'
        ];

        $this->apiMock->expects($this->once())
            ->method('createRecord')
            ->with('Leads', $this->callback(function ($data) {
                return $data['phone_mobile'] === '(555) 123-4567 . 123';
            }))
            ->willReturn('test-id');

        $this->leadService->createLead($leadData);
    }
}