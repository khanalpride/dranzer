<?php

namespace App\Builders\Processors\App\Providers;

use Closure;
use App\Helpers\StringHelpers;
use App\Builders\Processors\PHPBuilderProcessor;
use App\Builders\PHP\Laravel\Framework\App\Providers\PermissionsServiceProviderBuilder;

/**
 * Class PermissionsServiceProviderProcessor
 * @package App\Builders\Processors\App\Providers
 */
class PermissionsServiceProviderProcessor extends PHPBuilderProcessor
{
    /**
     * @param $builder
     * @param Closure $next
     * @return bool
     */
    public function process($builder, Closure $next): bool
    {
        $this->processAuthorization($builder);

        $next($builder);

        return true;
    }

    /**
     * @param PermissionsServiceProviderBuilder $builder
     * @return void
     */
    private function processAuthorization(PermissionsServiceProviderBuilder $builder): void
    {
        $authorizationMutations = app('mutations')->for('authorization');

        $roles = $authorizationMutations['roles'];

        $permissions = $authorizationMutations['permissions'];

        if (!count($roles) && !count($permissions)) {
            $builder->setCanBuild(false);
            return;
        }

        $stmts = [];

        $rolesCollection = collect($roles);

        $standalonePermissions = collect($permissions)
            ->map(static fn ($p) => $p['name'])
            ->filter(
                static fn ($p) => $rolesCollection->first(
                    static fn ($r) => !collect($r['permissions'])->first(static fn ($per) => $per === $p)
                )
            );

        if (count($standalonePermissions)) {
            $permissionStmts = [];

            foreach ($standalonePermissions as $permission) {
                $permissionStmts[] = $this->chainableMethodCall(
                    'addPermission',
                    [
                        $this->string($permission),
                        $this->string(StringHelpers::humanize($permission))
                    ]
                );
            }

            $stmts[] = $this->inlineAssign('permissions', $this->chainableStaticCall(
                'ItemPermission',
                'group', [$this->string('admin')],
                $permissionStmts
            ));

            $stmts[] = $this->nop();
            $stmts[] = $this->methodCall('dashboard', 'registerPermissions', [$this->var('permissions')]);
            $stmts[] = $this->nop();
        }

        foreach ($roles as $role) {
            $roleName = $role['name'] ?? null;

            if (!$roleName) {
                continue;
            }

            $permissions = $role['permissions'] ?? [];

            if (!count($permissions)) {
                continue;
            }

            $permissionStmts = [];

            foreach ($permissions as $permission) {
                $permissionStmts[] = $this->chainableMethodCall(
                    'addPermission',
                    [
                        $this->string($permission),
                        $this->string(StringHelpers::humanize($permission))
                    ]
                );
            }

            $stmts[] = $this->inlineAssign('permissions', $this->chainableStaticCall(
                'ItemPermission',
                'group', [$this->string($roleName)],
                $permissionStmts
            ));

            $stmts[] = $this->nop();
            $stmts[] = $this->methodCall('dashboard', 'registerPermissions', [$this->var('permissions')]);
            $stmts[] = $this->nop();
        }

        $builder->getBootMethodBuilder()
            ->addParameter($this->param('dashboard', 'Dashboard'))
            ->addStatements($stmts);

    }
}
