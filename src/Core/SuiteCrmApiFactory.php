<?php
/**
 * SuiteCRM API Factory
 *
 * Factory class for creating SuiteCRM API instances.
 * Follows the Factory pattern and Dependency Injection principles.
 *
 * @package SuiteAPI
 * @subpackage Core
 * @author AI Assistant
 * @since 1.0.0
 *
 * @requirement REQ-SUITE-010: API Factory Pattern
 * @requirement REQ-SUITE-011: Dependency Injection Container
 *
 * UML Class Diagram:
 * ```
 * +---------------------+
 * | SuiteCrmApiFactory  |
 * +---------------------+
 * | +createApi()        |
 * | +createFromEnv()    |
 * | +createContactService()|
 * +---------------------+
 *           ^
 *           |
 * +---------------------+
 * | SuiteCrmRestApi     |
 * | ContactService      |
 * +---------------------+
 * ```
 */

namespace Ksfraser\Ksfraser\SuiteAPI\Core;

use Ksfraser\SuiteAPI\Interfaces\SuiteCrmApiInterface;
use Ksfraser\SuiteAPI\Interfaces\HttpClientInterface;
use Ksfraser\SuiteAPI\Services\ContactService;

/**
 * SuiteCRM API Factory
 *
 * Creates configured instances of SuiteCRM API components.
 * Centralizes object creation and dependency injection.
 */
class SuiteCrmApiFactory
{
    /**
     * Create SuiteCRM API instance with custom configuration
     *
     * @param SuiteCrmConfig $config SuiteCRM configuration
     * @param HttpClientInterface|null $httpClient HTTP client (optional)
     * @return SuiteCrmApiInterface Configured API instance
     *
     * @requirement REQ-SUITE-010
     */
    public static function createApi(
        SuiteCrmConfig $config,
        ?HttpClientInterface $httpClient = null
    ): SuiteCrmApiInterface {
        $httpClient = $httpClient ?? new CurlHttpClient();
        return new SuiteCrmRestApi($config, $httpClient);
    }

    /**
     * Create SuiteCRM API instance from environment variables
     *
     * @param array $env Environment variables (for testing)
     * @param HttpClientInterface|null $httpClient HTTP client (optional)
     * @return SuiteCrmApiInterface Configured API instance
     * @throws \RuntimeException If required environment variables are missing
     *
     * @requirement REQ-SUITE-010
     */
    public static function createFromEnv(
        array $env = null,
        ?HttpClientInterface $httpClient = null
    ): SuiteCrmApiInterface {
        $config = SuiteCrmConfig::fromEnvironment($env);
        return self::createApi($config, $httpClient);
    }

    /**
     * Create SuiteCRM API instance from configuration array
     *
     * @param array $configArray Configuration array
     * @param HttpClientInterface|null $httpClient HTTP client (optional)
     * @return SuiteCrmApiInterface Configured API instance
     *
     * @requirement REQ-SUITE-010
     */
    public static function createFromArray(
        array $configArray,
        ?HttpClientInterface $httpClient = null
    ): SuiteCrmApiInterface {
        $config = SuiteCrmConfig::fromArray($configArray);
        return self::createApi($config, $httpClient);
    }

    /**
     * Create Contact Service with API instance
     *
     * @param SuiteCrmApiInterface $api SuiteCRM API instance
     * @return ContactService Contact service instance
     *
     * @requirement REQ-SUITE-011
     */
    public static function createContactService(SuiteCrmApiInterface $api): ContactService
    {
        return new ContactService($api);
    }

    /**
     * Create complete SuiteCRM setup from environment
     *
     * @param array $env Environment variables (for testing)
     * @return array Array with 'api' and 'contactService' keys
     * @throws \RuntimeException If required environment variables are missing
     *
     * @requirement REQ-SUITE-010
     * @requirement REQ-SUITE-011
     */
    public static function createFullSetup(array $env = null): array
    {
        $api = self::createFromEnv($env);
        $contactService = self::createContactService($api);

        return [
            'api' => $api,
            'contactService' => $contactService
        ];
    }

    /**
     * Create complete SuiteCRM setup from configuration array
     *
     * @param array $configArray Configuration array
     * @return array Array with 'api' and 'contactService' keys
     *
     * @requirement REQ-SUITE-010
     * @requirement REQ-SUITE-011
     */
    public static function createFullSetupFromArray(array $configArray): array
    {
        $api = self::createFromArray($configArray);
        $contactService = self::createContactService($api);

        return [
            'api' => $api,
            'contactService' => $contactService
        ];
    }
}