<?php

namespace App\Builders\Processors\Modules;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use App\Builders\Processors\PHPBuilderProcessor;

/**
 * Class BladePartialsModuleProcessor
 * @package App\Builders\Processors\Modules
 */
class BladePartialsModuleProcessor extends PHPBuilderProcessor
{
    /**
     * @param $builder
     * @param Closure $next
     * @return bool
     */
    public function process($builder, Closure $next): bool
    {
        $this
            ->buildAlertPartials()
            ->buildNavPartials()
            ->buildCustomPartials();

        $next($builder);

        return true;
    }

    /**
     *
     * @return BladePartialsModuleProcessor
     */
    private function buildAlertPartials(): BladePartialsModuleProcessor
    {
        $alertPartials = app('mutations')->for('frontend')['alertPartials'];

        if (!count($alertPartials)) {
            return $this;
        }

        $alertPartialsDir = app('project-dir') . '/resources/views/partials/alert';

        File::ensureDirectoryExists($alertPartialsDir);

        foreach ($alertPartials as $alertPartial) {
            $type = $alertPartial['type'] ?? 'custom';
            $name = $alertPartial['name'] ?? null;

            if ($type !== 'custom') {
                $partial = "@error('$name')" . PHP_EOL;
                $partial .= "\t<div class=\"alert alert-$type\">{{ \$message }}</div>" . PHP_EOL;
                $partial .= "@enderror";
            } else {
                $partial = "@if(session('$name'))" . PHP_EOL;
                $partial .= "\t<div class=\"alert alert-info\">{{ session('$name') }}</div>" . PHP_EOL;
                $partial .= "@endif";
            }

            File::put($alertPartialsDir . "/$name.blade.php", $partial);
        }

        return $this;
    }

    /**
     * @return void
     */
    private function buildCustomPartials(): void
    {
        $customPartials = app('mutations')->for('frontend')['customPartials'];

        foreach ($customPartials as $customPartial) {
            $name = $customPartial['name'] ?? null;
            $code = $customPartial['code'] ?? null;

            if (!$code || trim($code) === '') {
                continue;
            }

            if (!$name) {
                $name = 'partial.' . Str::random();
            }

            $dir = $this->getCustomPartialDir($name);

            $dir = app('project-dir') . '/resources/views/partials' . $dir;

            $path = $dir . $this->getCustomPartialName($name) . '.blade.php';

            File::ensureDirectoryExists($dir);

            File::put($path, $code);
        }

    }

    /**
     *
     * @return BladePartialsModuleProcessor
     */
    private function buildNavPartials(): BladePartialsModuleProcessor
    {
        $frontendMutations = app('mutations')->for('frontend');

        $navPartials = $frontendMutations['navPartials'];

        $views = collect($frontendMutations['views']);

        $config = $navPartials['config'];

        $items = collect($navPartials['items'] ?? [])
            ->map(function ($i) use ($views) {
                $view = $views->first(fn ($v) => $v['id'] === $i['uri']);
                $i['view'] = $view;
                return $i;
            });

        if (!count($items)) {
            return $this;
        }

        $style = $config['listStyle'] ?? 'ul';
        $navClass = $config['navClass'] ?? '';
        $listClass = $config['listClass'] ?? '';
        $listItemClass = $config['listItemClass'] ?? '';
        $listItemSelectedClass = $config['listItemSelectedClass'] ?? 'active';
        $listAnchorClass = $config['listAnchorClass'] ?? '';

        $navHtml = "<nav class=\"$navClass\">" . PHP_EOL . "\t<$style class=\"$listClass\">" . PHP_EOL;

        foreach ($items as $item) {
            $text = $item['text'] ?? 'Link Text';

            $view = $item['view'] ?? [];

            $viewName = $view['name'] ?? null;

            $routeName = $viewName ? str_replace([
                '-',
                '_'
            ], '.', Str::snake($viewName)) : null;

            $routeCheckCode = $routeName ? " {{ request()->routeIs('show.$routeName') ? '$listItemSelectedClass' : '' }}" : '';

            if ($listItemClass) {
                $navHtml .= "\t\t<li class=\"$listItemClass$routeCheckCode\">" . PHP_EOL;
            } else {
                $routeCheckCode = trim($routeCheckCode);
                $navHtml .= "\t\t<li class=\"$routeCheckCode\">" . PHP_EOL;
            }

            if ($viewName) {
                $navHtml .= "\t\t\t<a href=\"{{ route('show.$routeName') }}\" class=\"$listAnchorClass\">" . $text . "</a>" . PHP_EOL;
            } else {
                $navHtml .= "\t\t\t<a href=\"#\" class=\"$listAnchorClass\">" . $text . "</a>" . PHP_EOL;
            }

            $navHtml .= "\t\t</li>" . PHP_EOL;
        }

        $navHtml .= "\t</$style>" . PHP_EOL . "</nav>";

        $customPartialsDir = app('project-dir') . '/resources/views/partials';

        File::ensureDirectoryExists($customPartialsDir);

        File::put($customPartialsDir . '/nav.blade.php', $navHtml);

        return $this;
    }

    /**
     * @param $partialName
     * @return array|string|string[]
     */
    private function getCustomPartialDir($partialName)
    {
        $partialName = str_ireplace('.', '/', $partialName);

        $dir = pathinfo($partialName, PATHINFO_DIRNAME);

        if ($dir === '.') {
            $dir = '';
        }

        if (!Str::startsWith($dir, '/')) {
            $dir = "/$dir";
        }

        if (!Str::endsWith($dir, '/')) {
            $dir = "$dir/";
        }

        return $dir;
    }

    /**
     * @param $partialName
     * @return array|string|string[]
     */
    private function getCustomPartialName($partialName)
    {
        $partialName = str_ireplace('.', '/', $partialName);

        return pathinfo($partialName, PATHINFO_BASENAME);
    }
}
