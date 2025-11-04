<?php

namespace Laravel\Nova\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Laravel\Nova\Script;
use Laravel\Nova\Style;

use function Illuminate\Filesystem\join_paths;

trait InteractsWithAssets
{
    /**
     * All of the registered Nova tool scripts.
     *
     * @var array<int, \Laravel\Nova\Script>
     */
    public static array $scripts = [];

    /**
     * All of the registered Nova tool CSS.
     *
     * @var array<int, \Laravel\Nova\Style>
     */
    public static array $styles = [];

    /**
     * Get all of the additional scripts that should be registered.
     *
     * @return array<int, \Laravel\Nova\Script>
     */
    public static function allScripts(): array
    {
        return static::$scripts;
    }

    /**
     * Get all of the available scripts that should be registered.
     *
     * @return array<int, \Laravel\Nova\Script>
     */
    public static function availableScripts(Request $request): array
    {
        if (\is_null(static::user($request))) {
            return [];
        }

        return static::$scripts;
    }

    /**
     * Get all of the additional stylesheets that should be registered.
     *
     * @return array<int, \Laravel\Nova\Style>
     */
    public static function allStyles(): array
    {
        return static::$styles;
    }

    /**
     * Get all of the available stylesheets that should be registered.
     *
     * @return array<int, \Laravel\Nova\Style>
     */
    public static function availableStyles(Request $request): array
    {
        if (\is_null(static::user($request))) {
            return [];
        }

        return static::$styles;
    }

    /**
     * Register the given remote script file with Nova.
     */
    public static function remoteScript(string $path): static
    {
        return static::script(Script::remote($path), $path);
    }

    /**
     * Register the given script file with Nova.
     */
    public static function script(Script|string $name, string $path): static
    {
        static::$scripts[] = new Script($name, $path);

        return new static;
    }

    /**
     * Register the given remote CSS file with Nova.
     */
    public static function remoteStyle(string $path): static
    {
        return static::style(Style::remote($path), $path);
    }

    /**
     * Register the given CSS file with Nova.
     */
    public static function style(Style|string $name, string $path): static
    {
        static::$styles[] = new Style($name, $path);

        return new static;
    }

    /**
     * Register the given assets from `mix-manifest.json` file with Nova.
     *
     * @param  array<int, string>|null  $assets
     */
    public static function mix(string $name, string $path, ?array $assets = null): static
    {
        $path = rtrim($path, '/');
        $manifest = null;

        if (File::isDirectory($path)) {
            $manifest = join_paths($path, 'mix-manifest.json');
        } else {
            $manifest = $path;
            $path = \dirname($path);
        }

        if (File::exists($manifest)) {
            collect(File::json($manifest))
                ->filter(static function ($file, $filename) use ($assets) {
                    if (empty($assets)) {
                        return Str::endsWith($filename, ['.js', '.css']);
                    }

                    return \in_array($filename, $assets);
                })->each(static function ($file, $filename) use ($name, $path) {
                    $key = \sprintf('%s-%s', $name, hash('xxh128', $file));

                    if (str_ends_with($filename, '.js')) {
                        static::script($key, join_paths($path, $filename));
                    } else {
                        static::style($key, join_paths($path, $filename));
                    }
                });
        }

        return new static;
    }
}
