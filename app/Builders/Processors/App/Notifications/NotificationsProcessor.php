<?php

namespace App\Builders\Processors\App\Notifications;

use Closure;
use Illuminate\Support\Facades\File;
use App\Builders\Helpers\BlueprintHelpers;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Builders\Processors\PHPBuilderProcessor;
use App\Builders\PHP\Laravel\Framework\App\Notifications\NotificationsBuilder;

/**
 * Class NotificationsProcessor
 * @package App\Builders\Processors\App\Notifications
 */
class NotificationsProcessor extends PHPBuilderProcessor
{
    /**
     * @var mixed
     */
    private array $blueprints;
    /**
     * @var mixed
     */
    private array $mailables;
    /**
     * @var mixed
     */
    private bool $usingHorizon;

    /**
     * @param $builder
     * @param Closure $next
     * @return bool
     */
    public function process($builder, Closure $next): bool
    {
        $projectRoot = app('project-dir');

        $this->blueprints = app('mutations')->for('database')['blueprints'];
        $this->mailables = app('mutations')->for('mail')['mailables'];
        $this->usingHorizon = app('mutations')->for('queues')['packages']['installHorizon'];

        $notifications = app('mutations')->for('notifications')['notifications'];

        $notificationsDirectory = $projectRoot . '/app/Notifications';

        if (count($notifications)) {
            File::ensureDirectoryExists($notificationsDirectory);
        }

        foreach ($notifications as $notification) {
            $this->buildNotificationClass($builder, $notification ?? []);
        }

        $next($builder);

        return true;
    }

    /**
     * @param NotificationsBuilder $builder
     * @param array $notification
     */
    private function buildNotificationClass(NotificationsBuilder $builder, array $notification): void
    {
        $notificationName = $notification['name'] ?? null;
        $typeHinted = $notification['typeHint'] ?? [];

        if (!$notificationName) {
            return;
        }

        $builder
            ->setClassDefinition($notificationName, 'App\Notifications', 'Notification')
            ->updateClassDefinition();

        $builder->use(Notification::class);

        $via = $notification['via'] ?? [];

        $viaMail = $via['mail']['enabled'] ?? false;
        $viaSlack = $via['slack']['enabled'] ?? false;
        $viaSMS = $via['sms']['enabled'] ?? false;

        if ($this->usingHorizon) {
            $builder
                ->use('Illuminate\Bus\Queueable')
                ->use(ShouldQueue::class);

            $builder->setTraits(['Queueable']);
            $builder->setImplements(['ShouldQueue']);
        }

        $typeHintedModels = $this->getMappedTypeHintedModels($typeHinted);

        $modelAssignmentStatements = [];

        foreach ($typeHintedModels as $typeHintedModel) {
            $hintedModelName = $typeHintedModel['model'];
            $singularHintedModelName = $typeHintedModel['singular'];

            $builder->use("App\Models\\$hintedModelName");

            $hintedModelProperty = $builder->getNewPropertyBuilder($singularHintedModelName);
            $hintedModelProperty
                ->makePrivate()
                ->getDocBuilder()
                ->addVar($hintedModelName);

            $builder->addPropertyBuilder($hintedModelProperty);

            $modelAssignmentStatements[] = $this->inlineAssign(
                $this->var($singularHintedModelName),
                $this->propFetch('this', $singularHintedModelName)
            );
        }

        if (count($modelAssignmentStatements)) {
            $modelAssignmentStatements[] = $this->nop();
        }

        $this->addConstructMethod($builder, $typeHintedModels)
            ->addViaMethod(
                $builder,
                collect($via)
                    ->filter(fn ($v) => $v['enabled'])
                    ->keys()
                    ->map(fn ($k) => $k === 'sms' ? 'nexmo' : $k)
                    ->toArray()
            );

        if ($viaMail) {
            $this->addToMailMethod($builder, $via['mail'] ?? [], $notificationName, $typeHintedModels, $modelAssignmentStatements);
        }

        if ($viaSlack) {
            $builder->use('Illuminate\Notifications\Messages\SlackMessage');
            $this->addToSlackMethod($builder, $via['slack'] ?? [], $modelAssignmentStatements);
        }

        if ($viaSMS) {
            $builder->use('Illuminate\Notifications\Messages\NexmoMessage');
            $this->addToNexmoMethod($builder, $via['sms'] ?? [], $modelAssignmentStatements);
        }

        $this->addToArrayMethod($builder);

        $builder
            ->setFilename("$notificationName.php")
            ->toDisk();

        $builder->reset();

    }

    /**
     * @param NotificationsBuilder $builder
     * @param $typeHintedModels
     *
     * @return NotificationsProcessor
     */
    private function addConstructMethod(NotificationsBuilder $builder, $typeHintedModels): NotificationsProcessor
    {
        $constructMethodBuilder = $builder->getNewMethodBuilder('__construct');

        $params = [];
        $stmts = [];

        foreach ($typeHintedModels as $hintedModel) {
            $hintedModelName = $hintedModel['model'];
            $singularHintedModelName = $hintedModel['singular'];

            $params[] = $this->param($singularHintedModelName, $hintedModelName);

            $stmts[] = $this->inlineAssign(
                $this->propFetch('this', $singularHintedModelName),
                $this->var($singularHintedModelName)
            );
        }

        if (!count($stmts)) {
            $stmts = [$this->comment()];
        }

        $constructMethodBuilder
            ->addParameters($params)
            ->addStatements($stmts)
            ->getDocBuilder()
            ->addCommentLine('Create a new notification instance.')
            ->setReturnType('void');

        $builder->addMethodBuilder($constructMethodBuilder);

        return $this;
    }

    /**
     * @param NotificationsBuilder $builder
     * @return void
     */
    private function addToArrayMethod(NotificationsBuilder $builder): void
    {
        $toArrayMethodBuilder = $builder->getNewMethodBuilder('toArray');

        $toArrayMethodBuilder->addStatement(
            $this->return($this->arr([]))
        )
            ->setReturnType('array')
            ->getDocBuilder()
            ->addCommentLine('Get the array representation of the notification.');

        $builder->addMethodBuilder($toArrayMethodBuilder);

    }

    /**
     * @param NotificationsBuilder $builder
     * @param $mailConfig
     * @param $notificationName
     * @param $typeHintedModels
     * @param $modelAssignmentStatements
     *
     * @return void
     */
    private function addToMailMethod(NotificationsBuilder $builder, $mailConfig, $notificationName, $typeHintedModels, $modelAssignmentStatements): void
    {
        $toMailMethodBuilder = $builder->getNewMethodBuilder('toMail');
        $toMailMethodBuilder->addParameter($this->param('notifiable'));

        $mailable = head($mailConfig['mailables'] ?? []);

        $mailable = collect($this->mailables)->first(fn ($m) => $m['id'] === ($mailable['id'] ?? null));

        $mailableName = $mailable['name'] ?? null;

        if ($mailableName) {
            $alias = $mailableName === $notificationName ? "${mailableName}Mailable" : null;
            $builder->use("App\Mail\\$mailableName", $alias);

            $mailableTypeHinted = collect($mailable['typeHint'] ?? [])
                ->map(fn ($t) => $t['name'])
                ->toArray();

            $mailableArgs = collect($typeHintedModels)
                ->filter(fn ($m) => in_array($m['model'], $mailableTypeHinted, true))
                ->map(fn ($m) => $this->var($m['singular']))
                ->toArray();

            $toMailMethodBuilder->addStatements($modelAssignmentStatements);

            $toMailMethodBuilder->addStatements([
                $this->inlineAssign(
                    'mailable',
                    $this->new_($alias ?: $mailableName, $mailableArgs)
                ),
                $this->methodCall('mailable', 'to', [$this->propFetch('notifiable', 'email')]),
                $this->nop(),
                $this->return($this->var('mailable'))
            ])
                ->setReturnType($alias ?: $mailableName)
                ->getDocBuilder()
                ->addCommentLine('Returns the mailable for this notification.')
                ->setReturnType($alias ?: $mailableName);
        } else {
            $toMailMethodBuilder->addStatement($this->comment());
        }

        $builder->addMethodBuilder($toMailMethodBuilder);

    }

    /**
     * @param NotificationsBuilder $builder
     * @param $config
     * @param $modelAssignmentStatements
     *
     * @return void
     */
    private function addToNexmoMethod(NotificationsBuilder $builder, $config, $modelAssignmentStatements): void
    {
        $content = $config['content'] ?? '[Test] Testing Nexmo Notifications!';

        $from = $config['from'] ?? '';

        $toNexmoMethodBuilder = $builder->getNewMethodBuilder('toNexmo');

        $returnStmt = $this->methodCall(
            $this->new_('NexmoMessage'),
            'content',
            [$this->string($content)]
        );

        $returnStmt = $this->methodCall(
            $returnStmt,
            'from',
            [$this->string($from)]
        );

        $toNexmoMethodBuilder->addStatements($modelAssignmentStatements);

        $toNexmoMethodBuilder
            ->addStatement($this->return($returnStmt))
            ->setReturnType('NexmoMessage')
            ->getDocBuilder()
            ->addCommentLine('Build the notification for nexmo.')
            ->setReturnType('NexmoMessage');

        $builder->addMethodBuilder($toNexmoMethodBuilder);

    }

    /**
     * @param NotificationsBuilder $builder
     * @param $config
     * @param $modelAssignmentStatements
     *
     * @return void
     */
    private function addToSlackMethod(NotificationsBuilder $builder, $config, $modelAssignmentStatements): void
    {
        $content = $config['content'] ?? '[Test] Testing Slack Notifications!';

        $from = $config['from'] ?? [];

        $username = $from['username'] ?? 'App';

        $icon = $from['icon'] ?? null;

        $channel = $config['channel'] ?? 'general';

        $fromMethodArgs = [$this->string($username)];

        $parsedUrl = parse_url($icon);

        $isIconRemoteImage = array_key_exists('scheme', $parsedUrl) || array_key_exists('host', $parsedUrl);

        if ($icon && !$isIconRemoteImage) {
            $fromMethodArgs[] = $this->string($icon);
        }

        $toSlackMethodBuilder = $builder->getNewMethodBuilder('toSlack');

        $returnStmt = $this->methodCall(
            $this->new_('SlackMessage'),
            'content',
            [$this->string($content)]
        );

        if ($isIconRemoteImage) {
            $returnStmt = $this->methodCall(
                $returnStmt,
                'image',
                [$this->string($icon)]
            );
        }

        $returnStmt = $this->methodCall(
            $returnStmt,
            'from',
            $fromMethodArgs
        );

        $returnStmt = $this->methodCall(
            $returnStmt,
            'to',
            [$this->string($channel)]
        );

        $toSlackMethodBuilder->addStatements($modelAssignmentStatements);

        $toSlackMethodBuilder
            ->addStatement($this->return($returnStmt))
            ->setReturnType('SlackMessage')
            ->getDocBuilder()
            ->addCommentLine('Build the notification for slack.')
            ->setReturnType('SlackMessage');

        $builder->addMethodBuilder($toSlackMethodBuilder);

    }

    /**
     * @param NotificationsBuilder $builder
     * @param $via
     *
     * @return void
     */
    private function addViaMethod(NotificationsBuilder $builder, $via): void
    {
        $viaMethodBuilder = $builder->getNewMethodBuilder('via');

        $viaMethodBuilder
            ->addStatement(
                $this->return($this->arr(collect($via)->map(fn ($v) => $this->string($v))->toArray()))
            )
            ->addParameter($this->param('notifiable'))
            ->setReturnType('array')
            ->getDocBuilder()
            ->addCommentLine("Get the notification's delivery channels.")
            ->setReturnType('array');

        $builder->addMethodBuilder($viaMethodBuilder);

    }

    /**
     * @param array $typeHintedModels
     *
     * @return array
     */
    private function getMappedTypeHintedModels(array $typeHintedModels): array
    {
        $models = collect($this->blueprints);

        $mailableTypeHintedModels = collect($this->mailables)->map(fn ($m) => $m['typeHint'])->collapse()->toArray();

        $typeHintedModels = array_merge($typeHintedModels, $mailableTypeHintedModels);

        $typeHintedModels = collect($typeHintedModels)->unique('name')->toArray();

        return BlueprintHelpers::resolveTypeHintedModels($typeHintedModels, $models);
    }
}
