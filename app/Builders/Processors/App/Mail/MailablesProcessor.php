<?php

namespace App\Builders\Processors\App\Mail;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\File;
use App\Builders\Helpers\BlueprintHelpers;
use App\Builders\Processors\PHPBuilderProcessor;
use App\Builders\PHP\Parser\Printers\Extensions\Standard;
use App\Builders\PHP\Laravel\Framework\App\Mail\MailablesBuilder;

/**
 * Class MailablesProcessor
 * @package App\Builders\Processors\App\Mail
 */
class MailablesProcessor extends PHPBuilderProcessor
{
    /**
     * @var string
     */
    private string $projectRoot;

    /**
     * @param $builder
     * @param Closure $next
     * @return bool
     */
    public function process($builder, Closure $next): bool
    {
        $projectRoot = $this->projectRoot = app('project-dir');

        $mailablesDirectory = $projectRoot . '/app/Mail';

        $mailables = app('mutations')->for('mail')['mailables'];

        $blueprints = app('mutations')->for('database')['blueprints'];

        if (count($mailables)) {
            File::ensureDirectoryExists($mailablesDirectory);

            foreach ($mailables as $mailable) {
                $this->buildMailable($builder, $mailable, $blueprints);
            }
        }

        $next($builder);

        return true;
    }

    /**
     * @param MailablesBuilder $builder
     * @param $mailable
     * @param array $blueprints
     */
    protected function buildMailable(MailablesBuilder $builder, $mailable, array $blueprints): void
    {
        $name = Str::studly($mailable['name']);
        $markdown = $mailable['markdown'] ?? false;
        $markdownMessage = $mailable['markdownMessage'] ?? null;
        $typeHinted = $mailable['typeHint'] ?? [];
        $passAuthenticated = $mailable['passAuthenticated'] ?? false;

        $builder
            ->setClassDefinition($name, 'App\Mail', 'Mailable')
            ->updateClassDefinition();

        $viewFileName = 'emails.' . str_replace('_', '.', Str::snake($name));

        $builder->use(Mailable::class);
        $builder->use('Illuminate\Bus\Queueable');
        $builder->use('Illuminate\Queue\SerializesModels');

        $builder->setTraits([
            'Queueable',
            'SerializesModels'
        ]);

        $hintedModels = BlueprintHelpers::resolveTypeHintedModels($typeHinted, $blueprints);

        foreach ($hintedModels as $hintedModel) {
            $hintedModelName = $hintedModel['model'];
            $singularHintedModelName = $hintedModel['singular'];

            $builder->use("App\Models\\$hintedModelName");

            $hintedModelProperty = $builder->getNewPropertyBuilder($singularHintedModelName);
            $hintedModelProperty
                ->makePrivate()
                ->getDocBuilder()
                ->addVar($hintedModelName);

            $builder->addPropertyBuilder($hintedModelProperty);
        }

        $constructMethodBuilder = $builder->getNewMethodBuilder('__construct');

        foreach ($hintedModels as $hintedModel) {
            $hintedModelName = $hintedModel['model'];
            $singularHintedModelName = $hintedModel['singular'];

            $constructMethodBuilder->addParameter(
                $this->param($singularHintedModelName, $hintedModelName)
            );

            $constructMethodBuilder->addStatement(
                $this->inlineAssign(
                    $this->propFetch('this', $singularHintedModelName),
                    $this->var($singularHintedModelName)
                )
            );
        }

        $constructMethodBuilder
            ->getDocBuilder()
            ->addCommentLine('Create a new instance of the mailable.');

        $builder->addMethodBuilder($constructMethodBuilder);

        $buildMethodBuilder = $builder->getNewMethodBuilder('build');

        foreach ($hintedModels as $hintedModel) {
            $singularHintedModelName = $hintedModel['singular'];

            $buildMethodBuilder->addStatement(
                $this->inlineAssign(
                    $this->var($singularHintedModelName),
                    $this->propFetch('this', $singularHintedModelName)
                )
            );
        }

        $isUserModelTypeHinted = collect($hintedModels)->first(fn ($m) => $m['model'] === 'User') !== null;

        if ($passAuthenticated) {
            if (count($hintedModels)) {
                $buildMethodBuilder->addStatement($this->nop());
            }

            if ($isUserModelTypeHinted) {
                $buildMethodBuilder->addStatements([
                    $this->comment('Since the User model is type hinted, it takes'),
                    $this->comment('precedence over the authenticated user assigned below.'),
                    $this->nop(),
                    $this->comment((new Standard)->prettyPrint([
                        $this->nestedAssign(
                            'user',
                            $this->chainedFuncCalls([
                                $this->funcCall('auth'),
                                $this->funcCall('user')
                            ])
                        )
                    ])),
                ]);
            }

            if (!$isUserModelTypeHinted) {
                $buildMethodBuilder->addStatement(
                    $this->inlineAssign(
                        'user',
                        $this->chainedFuncCalls([
                            $this->funcCall('auth'),
                            $this->funcCall('user')
                        ])
                    )
                );
            }
        }

        if (count($hintedModels) > 0) {
            $buildMethodBuilder->addStatement($this->nop());
        }

        $passableVars = collect($hintedModels)->map(fn ($m) => $this->string($m['singular']))->toArray();

        if ($passAuthenticated && !$isUserModelTypeHinted) {
            $passableVars[] = $this->string('user');
        }

        $passable = count($passableVars)
            ? $this->funcCall('compact', $passableVars)
            : $this->nopExpr();

        if ($markdown) {
            $buildMethodBuilder->addStatement(
                $this->return(
                    $this->methodCall('this', 'markdown', [
                        $this->string($viewFileName),
                        $passable
                    ])
                )
            );
        } else {
            $buildMethodBuilder->addStatement(
                $this->return(
                    $this->methodCall('this', 'view', [
                        $this->string($viewFileName),
                        $passable
                    ])
                )
            );
        }

        $buildMethodBuilder
            ->setReturnType($name)
            ->getDocBuilder()
            ->addCommentLine('Build the message.')
            ->setReturnType($name);

        $builder->addMethodBuilder($buildMethodBuilder);

        $viewDir = $this->projectRoot . '/resources/views/';

        $viewPathSegments = explode('.', $viewFileName);

        $relativeEmailPathSegments = array_slice($viewPathSegments, 0, count($viewPathSegments) - 1);

        $viewDir .= implode('/', $relativeEmailPathSegments);

        File::ensureDirectoryExists($viewDir);

        $viewFilename = $viewPathSegments[count($viewPathSegments) - 1] . '.blade.php';

        $viewPath = "$viewDir/$viewFilename";

        if ($markdown) {
            $this->buildMarkdownViewFile($markdownMessage, $viewPath);
        } else {
            file_put_contents($viewPath, '');
        }

        $builder
            ->setFilename("$name.php")
            ->toDisk();

        $builder->reset();
    }

    /** @noinspection SpellCheckingInspection */
    private function buildMarkdownViewFile($message, $path): void
    {
        $items = $message['items'] ?? [];

        if (!count($items)) {
            return;
        }

        $output = "@component('mail::message')" . PHP_EOL;

        $componentOutput = '';

        foreach ($items as $item) {
            $type = $item['type'] ?? null;

            if (!$type) {
                continue;
            }

            if ($type === 'custom') {
                $componentOutput .= ($item['value'] ?? '') . PHP_EOL;
            }

            if ($type === 'line') {
                $componentOutput .= PHP_EOL;
            }

            if ($type === 'button') {
                $label = $item['label'] ?? 'Button';
                $url = "url('/')";

                $componentOutput .= "@component('mail::button', ['url' => $url])" . PHP_EOL . $label . PHP_EOL . "@endcomponent" . PHP_EOL;
            }

            if ($type === 'panel') {
                $componentOutput .= "@component('mail::panel')" . PHP_EOL . ($item['value'] ?? '') . PHP_EOL . "@endcomponent" . PHP_EOL;
            }
        }

        $componentOutput = trim($componentOutput);

        if ($componentOutput === '') {
            $output .= "\t" . PHP_EOL . "@endcomponent";
        } else {
            $output .= $componentOutput . PHP_EOL . "@endcomponent";
        }

        File::put($path, $output);
    }
}
