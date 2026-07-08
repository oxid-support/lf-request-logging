<?php

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Tests\Unit\Component\RequestLogger\Security;

use OxidSupport\Heartbeat\Component\RequestLogger\Controller\GraphQL\SettingController;
use OxidSupport\Heartbeat\Component\RequestLogger\Framework\PermissionProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Security tests for Authorization
 * Verifies all endpoints have proper authentication and authorization attributes
 */
#[CoversClass(SettingController::class)]
#[CoversClass(PermissionProvider::class)]
class AuthorizationSecurityTest extends TestCase
{
    // ===========================================
    // SETTING CONTROLLER AUTHORIZATION
    // ===========================================

    /**
     * @dataProvider settingControllerMethodsProvider
     */
    #[DataProvider('settingControllerMethodsProvider')]
    public function testSettingControllerMethodsRequireAuth(string $method): void
    {
        $reflection = new ReflectionMethod(SettingController::class, $method);
        $attributes = $this->getAttributeNames($reflection);

        $this->assertContains(
            'TheCodingMachine\GraphQLite\Annotations\Logged',
            $attributes,
            "$method must have #[Logged] attribute"
        );

        $this->assertContains(
            'TheCodingMachine\GraphQLite\Annotations\Right',
            $attributes,
            "$method must have #[Right] attribute"
        );
    }

    public static function settingControllerMethodsProvider(): array
    {
        return [
            ['requestLoggerSettings'],
            ['requestLoggerLogLevel'],
            ['requestLoggerLogFrontend'],
            ['requestLoggerLogAdmin'],
            ['requestLoggerRedact'],
            ['requestLoggerRedactAllValues'],
            ['requestLoggerLogLevelChange'],
            ['requestLoggerLogFrontendChange'],
            ['requestLoggerLogAdminChange'],
            ['requestLoggerRedactAllValuesChange'],
        ];
    }

    /**
     * The redact field list decides which values never reach the logs. Only the shop
     * admin may change it via the admin settings form; there must be no remote
     * (GraphQL) write path for it.
     */
    public function testRedactFieldsCannotBeChangedRemotely(): void
    {
        $this->assertFalse(
            method_exists(SettingController::class, 'requestLoggerRedactChange'),
            'requestLoggerRedactChange must not exist - redact fields are shop admin only'
        );

        $this->assertNotContains(
            'requestLoggerRedactChange',
            \OxidSupport\Heartbeat\Module\Module::SUPPORTED_OPERATIONS,
            'requestLoggerRedactChange must not be advertised as a supported operation'
        );
    }

    // ===========================================
    // PERMISSION PROVIDER TESTS
    // ===========================================

    public function testPermissionsAreDefinedForApiUserGroup(): void
    {
        $provider = new PermissionProvider();
        $permissions = $provider->getPermissions();

        $this->assertArrayHasKey('oxsheartbeat_api', $permissions);
        $this->assertNotEmpty($permissions['oxsheartbeat_api']);
    }

    public function testAdminGroupIsNotGranted(): void
    {
        $provider = new PermissionProvider();
        $permissions = $provider->getPermissions();

        // Support-only: shop admins have no GraphQL rights here (least privilege, OXS-3050).
        $this->assertArrayNotHasKey('oxidadmin', $permissions);
    }

    public function testAllRequiredPermissionsExist(): void
    {
        $provider = new PermissionProvider();
        $permissions = $provider->getPermissions();

        $requiredPermissions = [
            'REQUEST_LOGGER_VIEW',
            'REQUEST_LOGGER_CHANGE',
        ];

        foreach (['oxsheartbeat_api'] as $group) {
            foreach ($requiredPermissions as $permission) {
                $this->assertContains(
                    $permission,
                    $permissions[$group],
                    "Permission $permission must be defined for group $group"
                );
            }
        }
    }

    public function testNoExcessivePermissionsGranted(): void
    {
        $provider = new PermissionProvider();
        $permissions = $provider->getPermissions();

        // Verify no wildcard or overly broad permissions
        foreach ($permissions as $group => $perms) {
            foreach ($perms as $perm) {
                $this->assertNotEquals('*', $perm, "Wildcard permissions are not allowed");
                $this->assertStringNotContainsString('ADMIN', $perm, "Should not grant general ADMIN permissions");
                $this->assertStringNotContainsString('SUPER', $perm, "Should not grant SUPER permissions");
            }
        }
    }

    // ===========================================
    // MUTATION VS QUERY SEGREGATION
    // ===========================================

    public function testReadOperationsAreQueries(): void
    {
        $readMethods = [
            [SettingController::class, 'requestLoggerSettings'],
            [SettingController::class, 'requestLoggerLogLevel'],
            [SettingController::class, 'requestLoggerLogFrontend'],
            [SettingController::class, 'requestLoggerLogAdmin'],
            [SettingController::class, 'requestLoggerRedact'],
            [SettingController::class, 'requestLoggerRedactAllValues'],
        ];

        foreach ($readMethods as [$class, $method]) {
            $reflection = new ReflectionMethod($class, $method);
            $attributes = $this->getAttributeNames($reflection);

            $this->assertContains(
                'TheCodingMachine\GraphQLite\Annotations\Query',
                $attributes,
                "$class::$method should be a Query, not a Mutation"
            );
        }
    }

    public function testWriteOperationsAreMutations(): void
    {
        $writeMethods = [
            [SettingController::class, 'requestLoggerLogLevelChange'],
            [SettingController::class, 'requestLoggerLogFrontendChange'],
            [SettingController::class, 'requestLoggerLogAdminChange'],
            [SettingController::class, 'requestLoggerRedactAllValuesChange'],
        ];

        foreach ($writeMethods as [$class, $method]) {
            $reflection = new ReflectionMethod($class, $method);
            $attributes = $this->getAttributeNames($reflection);

            $this->assertContains(
                'TheCodingMachine\GraphQLite\Annotations\Mutation',
                $attributes,
                "$class::$method should be a Mutation, not a Query"
            );
        }
    }

    // ===========================================
    // HELPER METHODS
    // ===========================================

    private function getAttributeNames(ReflectionMethod $reflection): array
    {
        return array_map(
            fn($attr) => $attr->getName(),
            $reflection->getAttributes()
        );
    }
}
