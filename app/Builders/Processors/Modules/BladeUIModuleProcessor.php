<?php

namespace App\Builders\Processors\Modules;

use Closure;
use Throwable;
use PHPHtmlParser\Dom;
use PHPHtmlParser\Options;
use Illuminate\Support\Str;
use App\Helpers\RegexHelpers;
use App\Services\AssetService;
use App\Helpers\ArchiveHelpers;
use Illuminate\Support\Facades\File;
use App\Builders\PHP\Laravel\PrettierCommands;
use App\Builders\Processors\PHPBuilderProcessor;

/**
 * Class BladeUIModuleProcessor
 * @package App\Builders\Processors\Modules
 */
class BladeUIModuleProcessor extends PHPBuilderProcessor
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
        $this->projectRoot = app('project-dir');

        $customLayout = app('mutations')->for('ui')['customLayout'];

        $navPartials = collect(app('mutations')->for('frontend')['navPartials']);

        if ($customLayout) {
            $this->buildLayout($customLayout, $navPartials, $this->projectRoot . '/resources');
        }

        $next($builder);

        return true;
    }

    /**
     * @param $html
     * @param $navPartials
     * @return array|string|string[]
     */
    private function processHeader($html, $navPartials)
    {
        $dom = $this->getDom($html);
        $html = (string) $dom;

        $header = $this->getNodeHtml('header', $html) ?? $this->getNodeHtml('nav', $html);

        if ($header) {
            $matches = [];
            $anchorPattern = '/<a.*?href="(.*?)">(.*?)(<\/a>)/is';
            preg_match($anchorPattern, $header, $matches);
            $appName = RegexHelpers::match('/\w+/', $matches[2] ?? '');

            if (!empty($appName) && count($matches) > 1) {
                $replacement = str_replace([
                    $matches[1],
                    $appName
                ], [
                    "{{ route('home') }}",
                    "{{ config('app.name') }}"
                ], $matches[0]);
                $newHeader = str_replace($matches[0], $replacement, $header);
                $html = preg_replace('/<header.*?>.*?<\/header>/is', $newHeader, $html);
            }

            $replacementType = $navPartials['config']['navContainerType'] ?? 'first';

            if ($replacementType === 'first') {
                $navContainerHtml = $this->getNodeHtml('nav', $html);
            } else {
                $customNavContainerSelector = $navPartials['config']['navContainer'] ?? 'nav';

                $navContainerHtml = $this->getNodeHtml($customNavContainerSelector, $html);
            }

            $html = str_replace($navContainerHtml, "@include('partials.nav')", $html);
        }

        return $html;
    }

    /**
     * @param $layout
     * @param $navPartials
     * @param $outputDir
     * @return void
     */
    private function buildLayout($layout, $navPartials, $outputDir): void
    {
        $layoutId = $layout['id'];

        $theme = AssetService::getAsset($layoutId, app('project-id'));

        if (!$theme) {
            return;
        }

        $baseLayoutContents = $this->getBaseLayoutContent($theme, $layout);

        if (!$baseLayoutContents) {
            return;
        }

        $relativeLayoutPath = $layout['path'] ?? 'layouts';

        $layoutDir = dirname($relativeLayoutPath);
        $layoutName = pathinfo($relativeLayoutPath, PATHINFO_BASENAME);

        File::ensureDirectoryExists($outputDir . '/views/' . $layoutDir);

        $layoutPath = $outputDir . '/views/layouts';

        $contentWrapper = $layout['contentWrapper'];

        $partials = $layout['partials'];

        $views = collect($layout['views']);

        $this->createViews($theme, $views, $contentWrapper, $layoutName);

        $parsedLayout = $this->parseLayout($baseLayoutContents, $layout, $navPartials, $partials, $outputDir);

        $layoutCode = $parsedLayout['html'];

        $partialsDir = $parsedLayout['partialsDir'];

        $parsedPartials = $parsedLayout['partials'];

        if (!File::exists($partialsDir)) {
            File::makeDirectory($partialsDir, 0755, true);
        }

        foreach ($parsedPartials as $parsedPartial) {
            $path = $parsedPartial['path'];
            $code = $parsedPartial['code'];

            $scriptNodes = $this->getNodeHtml('script', $code, true);

            $scripts = collect($scriptNodes)->map(fn ($n) => $n->outerHtml)->join(PHP_EOL);

            $code = $this->substituteScripts($scripts, $layout, $code);

            $code = $this->processHeader($code, $navPartials);

            // Use absolute path for assets.
            $code = $this->replaceRelativeAssetPaths($code);

            File::put($path, $code);

            PrettierCommands::add($path, 'html');
        }

        $layoutCode = $this->substituteContentWrapper($contentWrapper, $layoutCode);

        $layoutCode = $this->replaceRelativeAssetPaths($layoutCode);

        File::put($layoutPath . "/$layoutName", $layoutCode);

        PrettierCommands::add($layoutPath . "/$layoutName", 'html');

    }

    /**
     * @param $directiveStart
     * @param $directiveEnd
     * @param $directiveStartDesc
     * @param $directiveEndDesc
     * @param $content
     * @return string
     * @noinspection PhpSameParameterValueInspection
     */
    private function addBladeDirective($directiveStart, $directiveEnd, $directiveStartDesc, $directiveEndDesc, $content): string
    {
        return PHP_EOL . PHP_EOL . "<!-- $directiveStartDesc -->" . PHP_EOL . $directiveStart . PHP_EOL . $content . PHP_EOL . $directiveEnd . PHP_EOL . "<!-- $directiveEndDesc -->" . PHP_EOL;
    }

    /**
     * @param $theme
     * @param $views
     * @param $contentWrapper
     * @param $layoutName
     * @return void
     */
    private function createViews($theme, $views, $contentWrapper, $layoutName): void
    {
        $layoutName = str_ireplace('.blade.php', '', $layoutName);

        $pagesDir = $this->projectRoot . '/resources/views/pages';

        File::ensureDirectoryExists($pagesDir);

        foreach ($views as $view) {
            $viewName = $view['name'] ?? Str::random();

            $viewSourceFilePath = $view['layoutFile']['path'] ?? null;

            $layoutPath = $viewSourceFilePath ?? null;

            $customContentWrapper = $view['customContentWrapper'] ?? false;

            $customContentWrapperSelector = $view['contentWrapper'] ?? null;

            $viewOutputPath = $pagesDir . '/' . $viewName . '.blade.php';

            $contentWrapperHtml = null;

            $layoutContents = $viewSourceFilePath ? ArchiveHelpers::getFileContents($viewSourceFilePath, $theme->filename) : '';

            if ($contentWrapper && $layoutPath && $customContentWrapper && $customContentWrapperSelector && trim($customContentWrapperSelector) !== '') {
                $contentWrapperHtml = $this->getNodeInnerHtml($customContentWrapperSelector, $layoutContents);
            }

            if ($contentWrapper && $layoutPath && !$customContentWrapper) {
                $selectorType = $contentWrapper['type'] ?? 'class';
                $selector = $contentWrapper['selector'] ?? null;
                $tag = $contentWrapper['tagName'] ?? null;

                if ($selector) {
                    $selector = $this->parseSelector($selector, $selectorType, $tag);

                    $contentWrapperHtml = $this->getNodeInnerHtml($selector, $layoutContents);
                }
            }

            $headHtml = $this->getNodeHtml('head', $layoutContents);

            $styles = $this->getNodes('style', $headHtml);

            $styles = implode(PHP_EOL, collect($styles)->map(fn ($s) => $s->outerHtml)->toArray());

            $content = $contentWrapperHtml ?? '';

            PrettierCommands::add($viewOutputPath, 'html');

            $viewHtml = "<!-- Extend $layoutName -->" . PHP_EOL . "@extends('layouts.$layoutName')" . PHP_EOL . "<!-- End Extend Statement -->";

            $viewHtml .= $this->addBladeDirective("@section('styles')", '@endsection', 'Page Specific Styles', 'End Page Specific Styles', $styles);
            $viewHtml .= $this->addBladeDirective("@section('content')", '@endsection', 'Page Content', 'End Page Content', $content);
            $viewHtml .= $this->addBladeDirective("@section('scripts')", '@endsection', 'Page Specific Scripts', 'End Page Specific Scripts', '');

            $viewHtml = $this->replaceRelativeAssetPaths($viewHtml);

            File::put($viewOutputPath, $viewHtml);
        }

    }

    /**
     * @param $code
     * @return array|string|string[]|null
     */
    private function replaceRelativeAssetPaths($code)
    {
        return preg_replace('/(?<=src=["\'])(.*?)(?=["\'])/is', "{{ secure_asset('$1') }}", $code);
    }

    /**
     * @param $contentWrapper
     * @param $html
     * @return mixed|string|string[]
     */
    private function substituteContentWrapper($contentWrapper, $html)
    {
        $contentWrapperHtml = null;

        if ($contentWrapper) {
            $selectorType = $contentWrapper['type'] ?? 'class';
            $selector = $contentWrapper['selector'] ?? null;
            $tag = $contentWrapper['tagName'] ?? null;

            if (!$selector) {
                return $html;
            }

            $selector = $this->parseSelector($selector, $selectorType, $tag);

            try {
                $dom = (new Dom)
                    ->setOptions((new Options())->setRemoveStyles(false)->setRemoveScripts(false))
                    ->loadStr($html);

                $contentWrapper = $dom->find($selector);

                if (count($contentWrapper)) {
                    collect($contentWrapper[0]->getChildren())->each(fn ($c) => $c->delete());
                }

                $html = (string) $dom;
            } catch (Throwable $throwable) {

            }

            $contentWrapperHtml = $this->getNodeHtml($selector, $html);
        }

        if ($contentWrapperHtml) {
            $originalContentWrapperHtml = $contentWrapperHtml;
            $contentWrapperHtml = str_ireplace([
                "\r",
                "\n",
                PHP_EOL
            ], '', $contentWrapperHtml);
            $contentWrapperHtml = preg_replace('/(<.*?>)(.*?)(<\/.*?>)/', '$1' . PHP_EOL . "@yield('content')" . PHP_EOL . '$3', $contentWrapperHtml);
            $html = str_replace($originalContentWrapperHtml, $contentWrapperHtml, $html);
        }

        return $html;
    }

    /**
     * @param $selector
     * @param $selectorType
     * @param $tag
     * @return string
     */
    private function parseSelector($selector, $selectorType, $tag): string
    {
        if ($selectorType === 'class') {
            $selector = str_ireplace(' ', '.', $selector);
        }

        if (!empty($tag)) {
            $selector = $selectorType === 'class' ? "$tag.$selector" : "$tag#$selector";
        } else {
            $selector = $selectorType === 'class' ? ".$selector" : "#$selector";
        }

        return $selector;
    }

    /**
     * @param $selector
     * @param $html
     * @param false $all
     * @return array|mixed|Dom\Node\Collection|null
     */
    private function getNodeHtml($selector, $html, $all = false)
    {
        $nodes = $this->getNodes($selector, $html);
        return $all ? $nodes : ($nodes[0]->outerhtml ?? null);
    }

    /**
     * @param $selector
     * @param $html
     * @return array|mixed|Dom\Node\Collection|null
     */
    private function getNodeInnerHtml($selector, $html)
    {
        $nodes = $this->getNodes($selector, $html);
        return $nodes[0]->innerHtml ?? null;
    }

    /**
     * @param $selector
     * @param $html
     * @return array|mixed|Dom\Node\Collection|null
     */
    private function getNodes($selector, $html)
    {
        try {
            $dom = $this->getDom($html);

            if (!$dom) {
                return [];
            }

            return $dom->find($selector);
        } catch (Throwable $throwable) {
            return [];
        }
    }

    /**
     * @param $html
     * @return array|Dom
     */
    private function getDom($html)
    {
        try {
            return (new Dom)->setOptions((new Options())->setRemoveStyles(false)->setRemoveScripts(false))->loadStr($html);
        } catch (Throwable $throwable) {
            return [];
        }
    }

    /**
     * @param $selector
     * @param $html
     * @return mixed|null
     */
    private function getFirstNode($selector, $html)
    {
        return $this->getNodes($selector, $html)[0] ?? null;
    }

    /**
     * @param $baseLayoutContents
     * @param $layout
     * @param $navPartials
     * @param $partials
     * @param $outputDir
     * @return array
     */
    private function parseLayout($baseLayoutContents, $layout, $navPartials, $partials, $outputDir): array
    {
        $htmlTagAttrs = RegexHelpers::match('/(?<=<html).*?(?=>)/is', $baseLayoutContents) ?? '';

        $head = $this->getNodeHtml('head', $baseLayoutContents) ?? '';
        $head = preg_replace('/<title(.*?)>(.*)?<\/title>/is', "<title$1>{{ \$title ?? config('app.name') }}</title>", $head);
        $head = $this->substituteStylesheets($head, $layout);
        $head = trim($head);
        $head = substr($head, 0, -7) . "@yield('styles') </head>";

        $head = preg_replace('/(?<=href=["\'])(.*?)(?=["\'])/is', "{{ secure_asset('$1') }}", $head);

        $baseLayoutContents = $this->processHeader($baseLayoutContents, $navPartials);

        $scriptNodes = $this->getNodeHtml('script', $baseLayoutContents, true);

        $scripts = collect($scriptNodes)->map(fn ($n) => $n->outerHtml)->join(PHP_EOL);

        $baseLayoutContents = $this->substituteScripts($scripts, $layout, $baseLayoutContents);

        $parsedPartials = $this->parsePartials($outputDir, $partials);

        $bodyElement = $this->getFirstNode('body', $baseLayoutContents);

        $bodyHTML = $bodyElement->outerHTML ?? '';

        foreach ($parsedPartials as $parsedPartial) {
            $partialName = $parsedPartial['name'] ?? null;

            if (!$partialName) {
                continue;
            }

            $selector = $parsedPartial['node']['selector'];

            $type = $parsedPartial['node']['type'];

            $selector = $type === 'id' ? "#$selector" : ".$selector";

            $partialNode = $this->getFirstNode($selector, $bodyHTML);

            if ($partialNode) {
                $bodyHTML = str_replace($partialNode->outerHTML, "@include('partials.$partialName')", $bodyHTML);
            }
        }

        $partialsDir = $outputDir . '/views/partials';

        /** @noinspection HtmlRequiredLangAttribute */
        return [
            'html'        => "<!doctype html>
                        <html $htmlTagAttrs>
                            $head
                            $bodyHTML
                        </html>",
            'partialsDir' => $partialsDir,
            'partials'    => $parsedPartials,
        ];
    }

    /**
     * @param $head
     * @param $layout
     * @return mixed|string|string[]
     */
    private function substituteStylesheets($head, $layout)
    {
        $layoutId = $layout['id'] ?? null;
        $layoutName = $layout['name'] ?? null;

        if (!$layoutId || !$layoutName) {
            return $head;
        }

        $grouped = collect($layout['stylesheets'])
            ->map(fn ($g) => [
                'output'      => $g['outputPath'],
                'stylesheets' => collect($g['assets'] ?? [])
                    ->filter(fn ($a) => $a['enabled'] ?? true)
                    ->map(fn ($a) => $a['path'])
            ])
            ->toArray();

        $outputPaths = [];

        $links = collect(RegexHelpers::matches('/\<link.*?\/>/i', $head));

        foreach ($grouped as $group) {
            $output = $group['output'];
            foreach ($group['stylesheets'] as $stylesheet) {
                $link = $links->first(fn ($l) => strpos($l, $stylesheet) !== false);

                if (!$link) {
                    continue;
                }

                if (in_array($output, $outputPaths, true)) {
                    $head = str_ireplace($link, '', $head);
                } else {
                    $href = RegexHelpers::match('/(?<=href=").*?(?=")/i', $link);
                    $replacement = "{{ secure_asset('css/$layoutName/$output') }}";
                    $updatedLink = str_replace($href, $replacement, $link);

                    $head = str_ireplace($link, $updatedLink, $head);
                    $outputPaths[] = $output;
                }
            }
        }

        return $head;
    }

    /**
     * @param $scripts
     * @param $layout
     * @param $baseLayoutContents
     * @return mixed|string|string[]
     */
    private function substituteScripts($scripts, $layout, $baseLayoutContents)
    {
        $layoutId = $layout['id'] ?? null;
        $layoutName = $layout['name'] ?? null;

        if (!$layoutId || !$layoutName) {
            return $scripts;
        }

        $grouped = collect($layout['scripts'])
            ->map(fn ($g) => [
                'output'  => $g['outputPath'],
                'scripts' => collect($g['assets'] ?? [])
                    ->filter(fn ($a) => $a['enabled'] ?? true)
                    ->map(fn ($a) => $a['path'])
            ])
            ->toArray();

        $outputPaths = [];

        $tags = collect(RegexHelpers::matches('/\<script.*?\/script>/i', $scripts));

        foreach ($grouped as $group) {
            $output = $group['output'];
            foreach ($group['scripts'] as $script) {
                $src = $tags->first(fn ($l) => strpos($l, $script) !== false);

                if (!$src) {
                    continue;
                }

                if (in_array($output, $outputPaths, true)) {
                    $baseLayoutContents = str_ireplace($src, '', $baseLayoutContents);
                } else {
                    $href = RegexHelpers::match('/(?<=src=").*?(?=")/i', $src);
                    $replacement = "{{ secure_asset('js/$layoutName/$output') }}";
                    $updatedLink = str_replace($href, $replacement, $src);

                    $baseLayoutContents = str_ireplace($src, $updatedLink, $baseLayoutContents);
                    $outputPaths[] = $output;
                }
            }
        }

        return $baseLayoutContents;
    }

    /**
     * @param $outputDir
     * @param $partials
     * @return array
     */
    private function parsePartials($outputDir, $partials): array
    {
        $parsed = [];

        foreach ($partials as $partial) {
            $partial = $partial['value'] ?? [];

            if (!($partial['name'] ?? null)) {
                continue;
            }

            $name = $partial['name'];

            $partialDir = $outputDir . '/views/partials';

            $partialPath = $partialDir . '/' . $name . '.blade.php';

            $code = trim($partial['code'] ?? '');

            $parsed[$partial['id']] = [
                'name' => $name,
                'path' => $partialPath,
                'code' => $code,
                'node' => $partial['node']
            ];
        }

        return $parsed;
    }

    /**
     * @param $theme
     * @param $layout
     * @return false|string|null
     */
    private function getBaseLayoutContent($theme, $layout)
    {
        $baseViewFile = $layout['baseViewFile'];

        $baseViewPath = $baseViewFile['path'] ?? null;

        if (!$baseViewPath) {
            return null;
        }

        return ArchiveHelpers::getFileContents($baseViewPath, $theme->filename);
    }
}
