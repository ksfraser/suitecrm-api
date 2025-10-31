<?php
/**
 * SuiteCRM Configuration Test
 *
 * Unit tests for SuiteCrmConfig class.
 * Demonstrates proper unit testing following TDD principles.
 *
 * @package SuiteAPI
 * @subpackage Tests
 * @author AI Assistant
 * @since 1.0.0
 *
 * @requirement REQ-SUITE-014: Unit Testing
 * @covers SuiteAPI\Core\SuiteCrmConfig
 */

namespace SuiteAPI\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SuiteAPI\Core\SuiteCrmConfig;

/**
 * SuiteCrmConfig Test Case
 *
 * Tests the immutable configuration class.
 */
class SuiteCrmConfigTest extends TestCase
{
    /**
     * Test successful configuration creation
     *
     * @requirement REQ-SUITE-005
     */
    public function testConfigCreation(): void
    {
        $config = new SuiteCrmConfig(
            'https://suitecrm.example.com/api',
            'testuser',
            'testpass',
            45,
            true,
            false
        );

        $this->assertEquals('https://suitecrm.example.com/api', $config->getUrl());
        $this->assertEquals('testuser', $config->getUsername());
        $this->assertEquals('testpass', $config->getPassword());
        $this->assertEquals(45, $config->getTimeout());
        $this->assertTrue($config->isDebug());
        $this->assertFalse($config->isSslVerify());
    }

    /**
     * Test configuration with default values
     *
     * @requirement REQ-SUITE-005
     */
    public function testConfigDefaults(): void
    {
        $config = new SuiteCrmConfig(
            'https://suitecrm.example.com/api',
            'testuser',
            'testpass'
        );

        $this->assertEquals(30, $config->getTimeout());
        $this->assertFalse($config->isDebug());
        $this->assertTrue($config->isSslVerify());
    }

    /**
     * Test configuration validation - empty URL
     *
     * @requirement REQ-SUITE-005
     */
    public function testEmptyUrlValidation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('SuiteCRM URL cannot be empty');

        new SuiteCrmConfig('', 'user', 'pass');
    }

    /**
     * Test configuration validation - empty username
     *
     * @requirement REQ-SUITE-005
     */
    public function testEmptyUsernameValidation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Username cannot be empty');

        new SuiteCrmConfig('https://example.com', '', 'pass');
    }

    /**
     * Test configuration validation - empty password
     *
     * @requirement REQ-SUITE-005
     */
    public function testEmptyPasswordValidation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Password cannot be empty');

        new SuiteCrmConfig('https://example.com', 'user', '');
    }

    /**
     * Test configuration validation - invalid timeout
     *
     * @requirement REQ-SUITE-005
     */
    public function testInvalidTimeoutValidation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Timeout must be positive');

        new SuiteCrmConfig('https://example.com', 'user', 'pass', 0);
    }

    /**
     * Test configuration creation from array
     *
     * @requirement REQ-SUITE-005
     */
    public function testFromArray(): void
    {
        $configArray = [
            'url' => 'https://suitecrm.example.com/api',
            'username' => 'testuser',
            'password' => 'testpass',
            'timeout' => 60,
            'debug' => true,
            'ssl_verify' => false
        ];

        $config = SuiteCrmConfig::fromArray($configArray);

        $this->assertEquals('https://suitecrm.example.com/api', $config->getUrl());
        $this->assertEquals('testuser', $config->getUsername());
        $this->assertEquals('testpass', $config->getPassword());
        $this->assertEquals(60, $config->getTimeout());
        $this->assertTrue($config->isDebug());
        $this->assertFalse($config->isSslVerify());
    }

    /**
     * Test configuration creation from environment variables
     *
     * @requirement REQ-SUITE-005
     */
    public function testFromEnvironment(): void
    {
        $env = [
            'SUITE_CRM_URL' => 'https://suitecrm.example.com/api',
            'SUITE_CRM_USERNAME' => 'envuser',
            'SUITE_CRM_PASSWORD' => 'envpass',
            'SUITE_CRM_TIMEOUT' => '90',
            'SUITE_CRM_DEBUG' => 'true',
            'SUITE_CRM_SSL_VERIFY' => 'false'
        ];

        $config = SuiteCrmConfig::fromEnvironment($env);

        $this->assertEquals('https://suitecrm.example.com/api', $config->getUrl());
        $this->assertEquals('envuser', $config->getUsername());
        $this->assertEquals('envpass', $config->getPassword());
        $this->assertEquals(90, $config->getTimeout());
        $this->assertTrue($config->isDebug());
        $this->assertFalse($config->isSslVerify());
    }

    /**
     * Test environment configuration with missing variables
     *
     * @requirement REQ-SUITE-005
     */
    public function testFromEnvironmentMissingVariables(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Missing required environment variables');

        SuiteCrmConfig::fromEnvironment([]);
    }
}