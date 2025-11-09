<?php
/**
 * SuiteCRM Configuration
 *
 * Immutable configuration class for SuiteCRM API settings.
 * Follows the Immutability principle and Dependency Injection pattern.
 *
 * @package SuiteAPI
 * @subpackage Core
 * @author AI Assistant
 * @since 1.0.0
 *
 * @requirement REQ-SUITE-005: Configuration Management
 * @requirement REQ-SUITE-006: Security by Design
 *
 * UML Class Diagram:
 * ```
 * +---------------------+
 * | SuiteCrmConfig      |
 * +---------------------+
 * | -url: string        |
 * | -username: string   |
 * | -password: string   |
 * | -timeout: int       |
 * | -debug: bool        |
 * +---------------------+
 * | +getUrl()           |
 * | +getUsername()      |
 * | +getPassword()      |
 * | +getTimeout()       |
 * | +isDebug()          |
 * +---------------------+
 * ```
 */

namespace Ksfraser\Ksfraser\SuiteAPI\Core;

/**
 * SuiteCRM Configuration Class
 *
 * Immutable configuration object that holds SuiteCRM connection settings.
 * All properties are set at construction time and cannot be modified.
 */
class SuiteCrmConfig
{
    /**
     * SuiteCRM API URL
     *
     * @var string
     */
    private $url;

    /**
     * SuiteCRM username
     *
     * @var string
     */
    private $username;

    /**
     * SuiteCRM password
     *
     * @var string
     */
    private $password;

    /**
     * API timeout in seconds
     *
     * @var int
     */
    private $timeout;

    /**
     * Debug mode flag
     *
     * @var bool
     */
    private $debug;

    /**
     * SSL verification flag
     *
     * @var bool
     */
    private $sslVerify;

    /**
     * Constructor - creates immutable configuration
     *
     * @param string $url SuiteCRM API URL
     * @param string $username SuiteCRM username
     * @param string $password SuiteCRM password
     * @param int $timeout API timeout in seconds (default: 30)
     * @param bool $debug Enable debug mode (default: false)
     * @param bool $sslVerify Enable SSL verification (default: true)
     *
     * @throws \InvalidArgumentException If required parameters are empty
     */
    public function __construct(
        string $url,
        string $username,
        string $password,
        int $timeout = 30,
        bool $debug = false,
        bool $sslVerify = true
    ) {
        // Validate required parameters
        if (empty($url)) {
            throw new \InvalidArgumentException('SuiteCRM URL cannot be empty');
        }
        if (empty($username)) {
            throw new \InvalidArgumentException('Username cannot be empty');
        }
        if (empty($password)) {
            throw new \InvalidArgumentException('Password cannot be empty');
        }
        if ($timeout <= 0) {
            throw new \InvalidArgumentException('Timeout must be positive');
        }

        $this->url = rtrim($url, '/');
        $this->username = $username;
        $this->password = $password;
        $this->timeout = $timeout;
        $this->debug = $debug;
        $this->sslVerify = $sslVerify;
    }

    /**
     * Get SuiteCRM API URL
     *
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Get username
     *
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * Get password
     *
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * Get API timeout
     *
     * @return int
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * Check if debug mode is enabled
     *
     * @return bool
     */
    public function isDebug(): bool
    {
        return $this->debug;
    }

    /**
     * Check if SSL verification is enabled
     *
     * @return bool
     */
    public function isSslVerify(): bool
    {
        return $this->sslVerify;
    }

    /**
     * Create config from environment variables
     *
     * @param array $env Array of environment variables (for testing)
     * @return self
     *
     * @throws \RuntimeException If required environment variables are missing
     */
    public static function fromEnvironment(array $env = null): self
    {
        $env = $env ?? $_ENV;

        $url = $env['SUITE_CRM_URL'] ?? '';
        $username = $env['SUITE_CRM_USERNAME'] ?? '';
        $password = $env['SUITE_CRM_PASSWORD'] ?? '';
        $timeout = (int)($env['SUITE_CRM_TIMEOUT'] ?? 30);
        $debug = filter_var($env['SUITE_CRM_DEBUG'] ?? 'false', FILTER_VALIDATE_BOOLEAN);
        $sslVerify = filter_var($env['SUITE_CRM_SSL_VERIFY'] ?? 'true', FILTER_VALIDATE_BOOLEAN);

        if (empty($url) || empty($username) || empty($password)) {
            throw new \RuntimeException(
                'Missing required environment variables: SUITE_CRM_URL, SUITE_CRM_USERNAME, SUITE_CRM_PASSWORD'
            );
        }

        return new self($url, $username, $password, $timeout, $debug, $sslVerify);
    }

    /**
     * Create config from array
     *
     * @param array $config Configuration array
     * @return self
     */
    public static function fromArray(array $config): self
    {
        return new self(
            $config['url'] ?? '',
            $config['username'] ?? '',
            $config['password'] ?? '',
            $config['timeout'] ?? 30,
            $config['debug'] ?? false,
            $config['ssl_verify'] ?? true
        );
    }
}