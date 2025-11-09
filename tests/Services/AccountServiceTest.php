<?php
/**
 * Account Service Test
 *
 * Unit tests for AccountService class.
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
use Ksfraser\SuiteAPI\Services\AccountService;
use Ksfraser\SuiteAPI\Exceptions\ValidationException;
use Ksfraser\SuiteAPI\Exceptions\SuiteApiException;

/**
 * Test cases for AccountService
 */
class AccountServiceTest extends TestCase
{
    /**
     * @var SuiteCrmApiInterface&MockObject
     */
    private $apiMock;

    /**
     * @var AccountService
     */
    private $accountService;

    /**
     * Set up test environment
     */
    protected function setUp(): void
    {
        $this->apiMock = $this->createMock(SuiteCrmApiInterface::class);
        $this->accountService = new AccountService($this->apiMock);
    }

    /**
     * Test successful account creation
     */
    public function testCreateAccountSuccess(): void
    {
        $accountData = [
            'name' => 'Test Account',
            'account_type' => 'Customer',
            'industry' => 'Technology'
        ];

        $expectedId = 'test-account-id';

        $this->apiMock->expects($this->once())
            ->method('createRecord')
            ->with('Accounts', $this->callback(function ($data) {
                return isset($data['name']) && $data['name'] === 'Test Account';
            }))
            ->willReturn($expectedId);

        $result = $this->accountService->createAccount($accountData);

        $this->assertEquals($expectedId, $result);
    }

    /**
     * Test account creation with missing required field
     */
    public function testCreateAccountMissingRequiredField(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Validation failed for Accounts');

        $accountData = [
            'account_type' => 'Customer'
        ];

        $this->accountService->createAccount($accountData);
    }

    /**
     * Test account creation with invalid account type
     */
    public function testCreateAccountInvalidAccountType(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Validation failed for Accounts');

        $accountData = [
            'name' => 'Test Account',
            'account_type' => 'InvalidType'
        ];

        $this->accountService->createAccount($accountData);
    }

    /**
     * Test finding account by ID
     */
    public function testFindAccountById(): void
    {
        $accountId = 'test-account-id';
        $expectedAccount = [
            'id' => $accountId,
            'name' => 'Test Account'
        ];

        $this->apiMock->expects($this->once())
            ->method('getRecord')
            ->with('Accounts', $accountId, [])
            ->willReturn($expectedAccount);

        $result = $this->accountService->findAccountById($accountId);

        $this->assertEquals($expectedAccount, $result);
    }

    /**
     * Test finding accounts by name
     */
    public function testFindAccountsByName(): void
    {
        $accountName = 'Test Account';
        $expectedAccounts = [
            ['id' => '1', 'name' => 'Test Account 1'],
            ['id' => '2', 'name' => 'Test Account 2']
        ];

        $this->apiMock->expects($this->once())
            ->method('searchRecords')
            ->with(
                'Accounts',
                ['name' => ['operator' => 'contains', 'value' => $accountName]],
                [],
                20,
                0
            )
            ->willReturn($expectedAccounts);

        $result = $this->accountService->findAccountsByName($accountName);

        $this->assertEquals($expectedAccounts, $result);
    }

    /**
     * Test finding accounts by type
     */
    public function testFindAccountsByType(): void
    {
        $accountType = 'Customer';
        $expectedAccounts = [
            ['id' => '1', 'name' => 'Customer Account 1'],
            ['id' => '2', 'name' => 'Customer Account 2']
        ];

        $this->apiMock->expects($this->once())
            ->method('searchRecords')
            ->with(
                'Accounts',
                ['account_type' => $accountType],
                [],
                20,
                0
            )
            ->willReturn($expectedAccounts);

        $result = $this->accountService->findAccountsByType($accountType);

        $this->assertEquals($expectedAccounts, $result);
    }

    /**
     * Test data preprocessing
     */
    public function testDataPreprocessing(): void
    {
        $accountData = [
            'name' => '  Test Account  ',
            'phone_office' => '(555) 123-4567',
            'website' => 'example.com'
        ];

        $this->apiMock->expects($this->once())
            ->method('createRecord')
            ->with('Accounts', $this->callback(function ($data) {
                return $data['name'] === 'Test Account' &&
                       $data['phone_office'] === '(555) 123-4567' &&
                       $data['website'] === 'http://example.com';
            }))
            ->willReturn('test-id');

        $this->accountService->createAccount($accountData);
    }

    /**
     * Test account update
     */
    public function testUpdateAccount(): void
    {
        $accountId = 'test-account-id';
        $updateData = [
            'name' => 'Updated Account Name',
            'industry' => 'Finance'
        ];

        $this->apiMock->expects($this->once())
            ->method('updateRecord')
            ->with('Accounts', $accountId, $updateData)
            ->willReturn(true);

        $result = $this->accountService->updateAccount($accountId, $updateData);

        $this->assertTrue($result);
    }

    /**
     * Test account deletion
     */
    public function testDeleteAccount(): void
    {
        $accountId = 'test-account-id';

        $this->apiMock->expects($this->once())
            ->method('deleteRecord')
            ->with('Accounts', $accountId)
            ->willReturn(true);

        $result = $this->accountService->delete($accountId);

        $this->assertTrue($result);
    }
}