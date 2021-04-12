<?php

namespace App\Builders\Processors\App\Models;

use Closure;
use Throwable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Notifications\Notifiable;
use App\Builders\Processors\PHPBuilderProcessor;
use App\Builders\Helpers\Mutations\UIMutationHelpers;
use App\Builders\PHP\Laravel\Framework\Database\TableColumn;
use App\Builders\PHP\Laravel\Framework\App\Models\UserModelBuilder;

/**
 * Class UserModelProcessor
 * @package App\Builders\Processors\App\Models
 */
class UserModelProcessor extends PHPBuilderProcessor
{
    /**
     * @param $builder
     * @param Closure $next
     * @return bool
     */
    public function process($builder, Closure $next): bool
    {
        $this
            ->processAttributes($builder)
            ->processAuthentication($builder)
            ->processAPI($builder)
            ->processNotifications($builder);

        if (UIMutationHelpers::installOrchid()) {
            $this->processOrchid($builder);
        }

        $next($builder);

        return true;
    }

    /**
     * @param UserModelBuilder $builder
     * @return UserModelProcessor
     */
    private function processAPI(UserModelBuilder $builder): UserModelProcessor
    {
        $apiConfig = app('mutations')->for('api');

        if (!$apiConfig['generate'] || !$apiConfig['jwtAuth']) {
            return $this;
        }

        $builder
            ->use('Tymon\JWTAuth\Contracts\JWTSubject')
            ->setImplements(['JWTSubject']);

        $getJWTIdentifierMethodBuilder = $builder->getNewMethodBuilder('getJWTIdentifier');

        $getJWTIdentifierMethodBuilder
            ->addStatement(
                $this->return($this->methodCall('this', 'getKey'))
            )
            ->getDocBuilder()
            ->addCommentLine('Get the identifier that will be stored in the subject claim of the JWT.')
            ->setReturnType('mixed');

        $getJWTCustomClaimsMethodBuilder = $builder->getNewMethodBuilder('getJWTCustomClaims');

        $getJWTCustomClaimsMethodBuilder
            ->addStatement(
                $this->return($this->arr([]))
            )
            ->getDocBuilder()
            ->addCommentLine('Return a key value array, containing any custom claims to be added to the JWT.')
            ->addCommentLine()
            ->setReturnType('array');

        $builder->addMethodBuilders([
            $getJWTIdentifierMethodBuilder,
            $getJWTCustomClaimsMethodBuilder
        ]);

        return $this;
    }

    /**
     * @param UserModelBuilder $builder
     * @return UserModelProcessor
     */
    private function processAttributes(UserModelBuilder $builder): UserModelProcessor
    {
        $unguarded = $builder->isUnguarded();

        if (!$unguarded) {
            $fillableColumns = $this->getColumnNamesWithAttribute($builder->getColumns(), 'f');

            foreach ($fillableColumns as $fillableColumn) {
                $builder->addFillable($fillableColumn);
            }
        } else {
            $builder->addFillable('*');
        }

        $hiddenColumns = $this->getColumnNamesWithAttribute($builder->getColumns(), 'h');

        if (count($hiddenColumns)) {
            foreach ($hiddenColumns as $hiddenColumn) {
                $builder->addHidden($hiddenColumn);
            }
        } else {
            $builder
                ->addHidden('password')
                ->addHidden('remember_token');
        }

        $dateColumns = collect($builder->getColumns())
            ->filter(static fn (TableColumn $column) => $column->getName() === 'timestamp' || $column->getName() === 'date')
            ->map(static fn (TableColumn $column) => $column->getName())
            ->toArray();

        // When soft deleting, the base model builder will add deleted_at to dates
        // property so we remove it from the date columns to add.
        if ($builder->shouldSoftDelete() && in_array('deleted_at', $dateColumns, true)) {
            $dateColumns = array_filter($dateColumns, static fn ($column) => $column !== 'deleted_at');
        }

        foreach ($dateColumns as $dateColumn) {
            $builder->addDate($dateColumn);
        }

        return $this;
    }

    /**
     * @param UserModelBuilder $builder
     * @return UserModelProcessor
     */
    private function processAuthentication(UserModelBuilder $builder): UserModelProcessor
    {
        $authMutations = app('mutations')->for('auth');

        $authEnabled = $authMutations['config']['enabled'];

        if (!$authEnabled) {
            return $this;
        }

        $authModule = $authMutations['module'];

        if ($authModule === 'ui' || $authModule === 'breeze') {
            $config = $authModule === 'ui' ? $authMutations['ui'] : $authMutations['breeze'];
            $verifyEmail = $config['verify'];

            if ($verifyEmail) {
                $builder
                    ->setShouldUseMustVerifyEmail(true)
                    ->useMustVerifyEmail()
                    ->setImplements(['MustVerifyEmail']);
            }
        }

        return $this;
    }

    /**
     * @param UserModelBuilder $builder
     * @return void
     */
    private function processNotifications(UserModelBuilder $builder): void
    {
        $notificationMutations = app('mutations')->for('notifications');

        $notifications = collect($notificationMutations['notifications']);

        $addMailRouteNotificationMethod = $notifications
                ->first(fn ($n) => $n['via']['mail']['enabled'] ?? false) !== null;

        $addSlackRouteNotificationMethod = $notifications
                ->first(fn ($n) => $n['via']['slack']['enabled'] ?? false) !== null;

        $addNexmoRouteNotificationMethod = $notifications
                ->first(fn ($n) => $n['via']['sms']['enabled'] ?? false) !== null;

        if ($addMailRouteNotificationMethod) {
            $addMailRouteNotificationMethod = $builder->getNewMethodBuilder('routeNotificationForMail');
            $addMailRouteNotificationMethod
                ->addStatement(
                    $this->return(
                        $this->arr(
                            [
                                $this->assoc(
                                    $this->propFetch('this', 'email'),
                                    $this->propFetch('this', 'name'),
                                ),
                            ]
                        )
                    )
                )
                ->setReturnType('array')
                ->getDocBuilder()
                ->addCommentLine('Returns the name and email of the user for use in the mailables.')
                ->addCommentLine()
                ->addCommentLine('@noinspection PhpUnused')
                ->setReturnType('array');

            $builder->addMethodBuilder($addMailRouteNotificationMethod);
        }

        if ($addSlackRouteNotificationMethod) {
            $slackWebhook = $notificationMutations['slackWebhook'] ?? '';

            $routeNotificationForSlackMethodBuilder = $builder->getNewMethodBuilder('routeNotificationForSlack');
            $routeNotificationForSlackMethodBuilder
                ->addStatement(
                    $this->return($this->string($slackWebhook))
                )
                ->setReturnType('string')
                ->getDocBuilder()
                ->addCommentLine('Route notifications for the Slack channel.')
                ->addCommentLine()
                ->addCommentLine('@noinspection PhpUnused')
                ->setReturnType('string');

            $builder->addMethodBuilder($routeNotificationForSlackMethodBuilder);
        }

        if ($addNexmoRouteNotificationMethod) {
            $nexmoRecipient = $notificationMutations['nexmoRecipient'] ?? null;

            if (!$nexmoRecipient) {
                $builder->setModelCustomDocBlockProperties(
                    [
                        [
                            'name' => 'phone_number',
                            'type' => 'string',
                        ],
                    ]
                );
            }

            $returnStmt = $nexmoRecipient ? $this->string($nexmoRecipient) : $this->propFetch('this', 'phone_number');

            $routeNotificationForNexmoMethodBuilder = $builder->getNewMethodBuilder('routeNotificationForNexmo');
            $routeNotificationForNexmoMethodBuilder
                ->addStatement(
                    $this->return($returnStmt)
                )
                ->setReturnType('string')
                ->getDocBuilder()
                ->addCommentLine('Route notifications for the Nexmo channel.')
                ->addCommentLine()
                ->addCommentLine('@noinspection PhpUnused')
                ->setReturnType('string');

            $builder->addMethodBuilder($routeNotificationForNexmoMethodBuilder);
        }

    }

    /**
     * @param UserModelBuilder $builder
     * @return void
     */
    private function processOrchid(UserModelBuilder $builder): void
    {
        $builder
            ->use(Hash::class)
            ->use('App\Orchid\Presenters\UserPresenter')
            ->use('Orchid\Access\UserAccess')
            ->use('Orchid\Access\UserInterface')
            ->use('Orchid\Filters\Filterable')
            ->use('Orchid\Metrics\Chartable')
            ->use('Orchid\Support\Facades\Dashboard')
            ->use('Laravel\Scout\Searchable')
            ->use(Throwable::class)
            ->use(Notifiable::class)
            ->setImplements(['UserInterface'])
            ->addTraits([
                'Notifiable',
                'UserAccess',
                'Filterable',
                'Chartable',
                'Searchable'
            ])
            ->addFillable('name')
            ->addFillable('email')
            ->addFillable('password')
            ->addFillable('permissions')
            ->addHidden('remember_token')
            ->addHidden('permissions')
            ->addCast('permissions', 'array')
            ->addCast('email_verified_at', 'datetime');

        $allowedFiltersProperty = $builder->getNewPropertyBuilder('allowedFilters');

        $allowedFiltersProperty->setValue(

            $this->arr(
                [
                    $this->string('id'),
                    $this->string('name'),
                    $this->string('email'),
                    $this->string('permissions'),
                ]
            )
        )
            ->makeProtected()
            ->getDocBuilder()
            ->addCommentLine('The attributes for which you can use filters in url.')
            ->addVar('array');

        $builder->addPropertyBuilder($allowedFiltersProperty);

        $allowedSortsProperty = $builder->getNewPropertyBuilder('allowedSorts');

        $allowedSortsProperty
            ->setValue(
                $this->arr(
                    [
                        $this->string('id'),
                        $this->string('name'),
                        $this->string('email'),
                        $this->string('created_at'),
                        $this->string('updated_at'),
                    ]
                )
            )
            ->makeProtected()
            ->getDocBuilder()
            ->addCommentLine('The attributes for which can use sort in url.')
            ->addVar('array');

        $builder->addPropertyBuilder($allowedSortsProperty);

        $presenterMethod = $builder->getNewMethodBuilder('presenter');

        $presenterMethod->setReturnType('UserPresenter')
            ->addStatement(
                $this->return($this->new_('UserPresenter', [$this->var('this')]))
            );

        $builder->addMethodBuilder($presenterMethod);

        $createAdminMethod = $builder->getNewMethodBuilder('createAdmin');

        $createAdminMethod
            ->makePublic()
            ->makeStatic()
            ->addParameters(
                [
                    $this->param('name', 'string'),
                    $this->param('email', 'string'),
                    $this->param('password', 'string'),
                ]
            )
            ->addStatement(
                $this->funcCall(
                    'throw_if', [
                        $this->methodCall(
                            $this->staticCall(
                                'static', 'where', [
                                    $this->string('email'),
                                    $this->var('email'),
                                ]
                            ),
                            'exists'
                        ),
                        $this->const('Throwable::class'),
                        $this->arr([$this->string('User Exists!')]),
                    ]
                )
            )
            ->addStatement($this->nop())
            ->addStatement(
                $this->staticCall(
                    'static', 'create', [
                        $this->arr(
                            [
                                $this->assoc('name', $this->var('name')),
                                $this->assoc('email', $this->var('email')),
                                $this->assoc('password', $this->staticCall('Hash', 'make', [$this->var('password')])),
                                $this->assoc('permissions', $this->staticCall('Dashboard', 'getAllowAllPermission')),
                            ]
                        ),
                    ]
                )
            )
            ->getDocBuilder()
            ->addCommentLine('@noinspection PhpUndefinedMethodInspection')
            ->addCommentLine('@throws Throwable');

        $builder->addMethodBuilder($createAdminMethod);

        $searchableAs = $builder->getNewMethodBuilder('searchableAs');

        $searchableAs->addStatement(
            $this->return($this->string('users_index'))
        )
            ->setReturnType('string')
            ->getDocBuilder()
            ->addCommentLine('Get the name of the index associated with the model.')
            ->setReturnType('string');

        $builder->addMethodBuilder($searchableAs);

        $toSearchableArrayMethod = $builder->getNewMethodBuilder('toSearchableArray');

        $toSearchableArrayMethod->setReturnType('array')
            ->addStatement(
                $this->return(
                    $this->arr(
                        [
                            $this->assoc('name', $this->propFetch('this', 'name')),
                            $this->assoc('email', $this->propFetch('this', 'email')),
                        ]
                    )
                )
            )
            ->setReturnType('array')
            ->getDocBuilder()
            ->addCommentLine('Get the searchable attributes for this model.')
            ->setReturnType('array');

        $builder->addMethodBuilder($toSearchableArrayMethod);

    }

    private function getColumnNamesWithAttribute(array $columns, string $attribute): array
    {
        return collect($columns)
            ->filter(static fn (TableColumn $column) => ($column->getRawAttributes()[$attribute] ?? false) === true)
            ->map(static fn (TableColumn $column) => $column->getName())
            ->toArray();
    }
}
