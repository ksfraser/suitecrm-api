<?php
/**
 * SuiteCRM API Usage Examples
 *
 * Comprehensive examples showing how to use the refactored SuiteCRM API.
 * Demonstrates proper usage patterns, error handling, and best practices.
 *
 * @package SuiteAPI
 * @subpackage Examples
 * @author AI Assistant
 * @since 1.0.0
 *
 * @requirement REQ-SUITE-012: API Usage Examples
 * @requirement REQ-SUITE-013: Error Handling Examples
 *
 * Usage Flow:
 * ```
 * 1. Configure API
 * 2. Create API instance
 * 3. Login
 * 4. Perform operations
 * 5. Logout
 * ```
 */

namespace SuiteAPI\Examples;

use SuiteAPI\Core\SuiteCrmApiFactory;
use SuiteAPI\Core\SuiteCrmConfig;
use SuiteAPI\Exceptions\SuiteApiException;
use SuiteAPI\Exceptions\AuthenticationException;
use SuiteAPI\Exceptions\ValidationException;

/**
 * SuiteCRM API Usage Examples
 *
 * This class demonstrates proper usage of the SuiteCRM API following
 * SOLID principles, dependency injection, and proper error handling.
 */
class SuiteCrmApiExamples
{
    /**
     * Example 1: Basic API setup and authentication
     *
     * Demonstrates the simplest way to set up and use the API.
     */
    public static function basicSetupExample(): void
    {
        echo "=== Basic Setup Example ===\n";

        try {
            // Create API instance from environment variables
            $setup = SuiteCrmApiFactory::createFullSetup();

            $api = $setup['api'];
            $contactService = $setup['contactService'];

            // Login
            $api->login(
                $_ENV['SUITE_CRM_USERNAME'] ?? 'admin',
                $_ENV['SUITE_CRM_PASSWORD'] ?? 'password'
            );

            echo "✓ Successfully authenticated with SuiteCRM\n";

            // Perform operations...
            $contacts = $contactService->searchContacts('John', 5);
            echo "✓ Found " . count($contacts) . " contacts matching 'John'\n";

            // Logout
            $api->logout();
            echo "✓ Successfully logged out\n";

        } catch (AuthenticationException $e) {
            echo "✗ Authentication failed: " . $e->getMessage() . "\n";
        } catch (SuiteApiException $e) {
            echo "✗ API error: " . $e->getMessage() . "\n";
        } catch (\Exception $e) {
            echo "✗ Unexpected error: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Example 2: Manual configuration setup
     *
     * Shows how to manually configure the API with specific settings.
     */
    public static function manualConfigExample(): void
    {
        echo "\n=== Manual Configuration Example ===\n";

        try {
            // Create configuration manually
            $config = new SuiteCrmConfig(
                'https://your-suitecrm-instance.com/service/v4_1/rest.php',
                'your-username',
                'your-password',
                60, // 60 second timeout
                true, // debug mode
                true // SSL verify
            );

            // Create API instance
            $api = SuiteCrmApiFactory::createApi($config);
            $contactService = SuiteCrmApiFactory::createContactService($api);

            // Login
            $api->login($config->getUsername(), $config->getPassword());
            echo "✓ Authenticated with manual configuration\n";

            // Example operations would go here...

            $api->logout();

        } catch (\Exception $e) {
            echo "✗ Error: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Example 3: Contact management operations
     *
     * Demonstrates CRUD operations on contacts using the service layer.
     */
    public static function contactManagementExample(): void
    {
        echo "\n=== Contact Management Example ===\n";

        try {
            $setup = SuiteCrmApiFactory::createFullSetup();
            $api = $setup['api'];
            $contactService = $setup['contactService'];

            $api->login(
                $_ENV['SUITE_CRM_USERNAME'] ?? 'admin',
                $_ENV['SUITE_CRM_PASSWORD'] ?? 'password'
            );

            // Create a new contact
            $contactData = [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john.doe@example.com',
                'phone_work' => '(555) 123-4567',
                'title' => 'Financial Advisor',
                'primary_address_city' => 'Toronto',
                'primary_address_country' => 'Canada'
            ];

            $contactId = $contactService->createContact($contactData);
            echo "✓ Created contact with ID: {$contactId}\n";

            // Find contact by email
            $foundContact = $contactService->findContactByEmail('john.doe@example.com');
            if ($foundContact) {
                echo "✓ Found contact: {$foundContact['first_name']} {$foundContact['last_name']}\n";
            }

            // Update contact
            $updateData = [
                'phone_mobile' => '(555) 987-6543',
                'department' => 'Wealth Management'
            ];

            $updated = $contactService->updateContact($contactId, $updateData);
            if ($updated) {
                echo "✓ Updated contact successfully\n";
            }

            // Get detailed contact information
            $contactDetails = $contactService->getContactDetails($contactId);
            if ($contactDetails) {
                echo "✓ Contact details retrieved:\n";
                echo "  Name: {$contactDetails['full_name']}\n";
                echo "  Email: {$contactDetails['email']}\n";
                echo "  Company: {$contactDetails['company']}\n";
            }

            $api->logout();

        } catch (ValidationException $e) {
            echo "✗ Validation error: " . $e->getMessage() . "\n";
            foreach ($e->getValidationErrors() as $error) {
                echo "  - {$error}\n";
            }
        } catch (SuiteApiException $e) {
            echo "✗ API error: " . $e->getMessage() . "\n";
        } catch (\Exception $e) {
            echo "✗ Unexpected error: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Example 4: Error handling and recovery
     *
     * Shows proper error handling patterns and recovery strategies.
     */
    public static function errorHandlingExample(): void
    {
        echo "\n=== Error Handling Example ===\n";

        try {
            $setup = SuiteCrmApiFactory::createFullSetup();
            $api = $setup['api'];

            // Try to perform operation without authentication
            $contactService = SuiteCrmApiFactory::createContactService($api);

            try {
                $contactService->findContactByEmail('test@example.com');
                echo "✗ Should have failed due to no authentication\n";
            } catch (AuthenticationException $e) {
                echo "✓ Correctly caught authentication error: {$e->getMessage()}\n";
            }

            // Now login and try invalid data
            $api->login(
                $_ENV['SUITE_CRM_USERNAME'] ?? 'admin',
                $_ENV['SUITE_CRM_PASSWORD'] ?? 'password'
            );

            try {
                $contactService->createContact([
                    'email' => 'invalid-email-format' // Invalid email
                ]);
                echo "✗ Should have failed due to validation error\n";
            } catch (ValidationException $e) {
                echo "✓ Correctly caught validation error: {$e->getMessage()}\n";
                foreach ($e->getValidationErrors() as $error) {
                    echo "  - {$error}\n";
                }
            }

            $api->logout();

        } catch (\Exception $e) {
            echo "✗ Unexpected error in error handling example: {$e->getMessage()}\n";
        }
    }

    /**
     * Example 5: Low-level API operations
     *
     * Shows direct API usage for advanced operations not covered by services.
     */
    public static function lowLevelApiExample(): void
    {
        echo "\n=== Low-Level API Example ===\n";

        try {
            $setup = SuiteCrmApiFactory::createFullSetup();
            $api = $setup['api'];

            $api->login(
                $_ENV['SUITE_CRM_USERNAME'] ?? 'admin',
                $_ENV['SUITE_CRM_PASSWORD'] ?? 'password'
            );

            // Direct API calls for custom operations
            $accounts = $api->searchRecords(
                'Accounts',
                [],
                ['id', 'name', 'website', 'phone_office'],
                10
            );

            echo "✓ Found " . count($accounts) . " accounts\n";

            if (!empty($accounts)) {
                $firstAccount = $accounts[0];
                echo "✓ First account: {$firstAccount['name']}\n";

                // Get full account details
                $accountDetails = $api->getRecord('Accounts', $firstAccount['id']);
                if ($accountDetails) {
                    echo "✓ Account details retrieved for: {$accountDetails['name']}\n";
                }
            }

            $api->logout();

        } catch (SuiteApiException $e) {
            echo "✗ API error: " . $e->getMessage() . "\n";
        } catch (\Exception $e) {
            echo "✗ Unexpected error: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Run all examples
     *
     * Executes all example methods in sequence.
     */
    public static function runAllExamples(): void
    {
        echo "SuiteCRM API Examples\n";
        echo "=====================\n\n";

        // Check if environment variables are set
        if (empty($_ENV['SUITE_CRM_URL'] ?? '') ||
            empty($_ENV['SUITE_CRM_USERNAME'] ?? '') ||
            empty($_ENV['SUITE_CRM_PASSWORD'] ?? '')) {
            echo "⚠ Warning: SuiteCRM environment variables not set.\n";
            echo "   Please set SUITE_CRM_URL, SUITE_CRM_USERNAME, and SUITE_CRM_PASSWORD\n";
            echo "   Examples will use default/demo values and may fail.\n\n";
        }

        self::basicSetupExample();
        self::manualConfigExample();
        self::contactManagementExample();
        self::errorHandlingExample();
        self::lowLevelApiExample();

        echo "\n=== Examples Complete ===\n";
    }
}

// Auto-run examples if this file is executed directly
if (basename(__FILE__) === basename($_SERVER['PHP_SELF'] ?? '')) {
    SuiteCrmApiExamples::runAllExamples();
}