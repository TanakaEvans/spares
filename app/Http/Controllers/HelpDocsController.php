<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use Inertia\Response;

class HelpDocsController extends Controller
{
    /**
     * Read-only in-app viewer for the docs/ markdown tree, so page guides
     * can deep-link full specs (docs/design/page-guide.md).
     */
    public function show(string $path = 'README.md'): Response
    {
        $base = realpath(base_path('docs'));

        if (! str_ends_with($path, '.md')) {
            $path .= '.md';
        }

        $resolved = realpath(base_path('docs/'.$path));

        // Block traversal: the resolved file must live inside docs/.
        abort_if(
            $resolved === false || ! str_starts_with($resolved, $base),
            404
        );

        return Inertia::render('Help/DocViewer', [
            'path' => $path,
            'content' => File::get($resolved),
        ]);
    }
}
