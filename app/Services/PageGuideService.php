<?php

namespace App\Services;

use App\Models\PageGuide;
use Illuminate\Support\Facades\File;

class PageGuideService
{
    /**
     * Guide markdown for a route name: DB override wins over the shipped file.
     * Shipped guides: resources/guides/{route.name}.md (modules add their own
     * under Modules/{X}/resources/guides/ once nwidart modules land).
     */
    public function contentFor(?string $routeName): ?string
    {
        if (! $routeName) {
            return null;
        }

        // Every page ships a guide (AGENTS §13a): a show/create/edit page
        // inherits its sub-module's guide when it has none of its own. Try the
        // exact route, then its sibling `.index`, then progressively shorter
        // prefixes' `.index`.
        foreach ($this->candidates($routeName) as $key) {
            $override = PageGuide::where('key', $key)->value('content');
            if ($override !== null) {
                return $override;
            }
            $path = resource_path("guides/{$key}.md");
            if (File::exists($path)) {
                return File::get($path);
            }
        }

        return null;
    }

    /** Ordered guide keys to try for a route, most specific first. */
    private function candidates(string $routeName): array
    {
        $out = [$routeName];

        if (str_contains($routeName, '.')) {
            $out[] = preg_replace('/\.[^.]+$/', '.index', $routeName);
        }

        $parts = explode('.', $routeName);
        while (count($parts) > 1) {
            array_pop($parts);
            $prefix = implode('.', $parts);
            $out[] = "{$prefix}.index";
            $out[] = $prefix;
        }

        return array_values(array_unique($out));
    }

    /** All known guide keys (shipped + overridden) for the admin editor. */
    public function allKeys(): array
    {
        $shipped = collect(File::exists(resource_path('guides')) ? File::files(resource_path('guides')) : [])
            ->map(fn ($f) => $f->getFilenameWithoutExtension());

        $overridden = PageGuide::pluck('key');

        return $shipped->merge($overridden)->unique()->sort()->values()->all();
    }
}
