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

        $override = PageGuide::where('key', $routeName)->value('content');
        if ($override !== null) {
            return $override;
        }

        $path = resource_path("guides/{$routeName}.md");

        return File::exists($path) ? File::get($path) : null;
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
