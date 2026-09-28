<?php

namespace Tests\Feature;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

/**
 * public/css/tailwind.css is built ahead of time (npm run build:css). This
 * fails when a view uses a class the committed build doesn't contain — i.e.
 * someone changed markup and forgot to rebuild.
 */
class BuiltStylesheetTest extends TestCase
{
    public function test_every_class_used_in_the_views_is_in_the_built_stylesheet(): void
    {
        $built = public_path('css/tailwind.css');
        $this->assertFileExists($built, 'Run `npm run build:css` to create public/css/tailwind.css.');

        // Compare against unescaped selectors (".sm\:p-8" -> ".sm:p-8").
        $css = str_replace('\\', '', file_get_contents($built).file_get_contents(public_path('css/app.css')));

        $missing = [];
        foreach ($this->viewFiles() as $file) {
            // PDF templates style themselves inline for the PDF renderer.
            if (str_ends_with($file, 'pdf.blade.php')) {
                continue;
            }
            preg_match_all('/\bclass="([^"]*)"/', file_get_contents($file), $matches);
            foreach ($matches[1] as $list) {
                $list = preg_replace('/\{\{.*?\}\}|\{!!.*?!!\}|@\w+(\(.*?\))?/', ' ', $list);
                foreach (preg_split('/\s+/', trim($list)) as $class) {
                    // Hooks for third-party scripts, not styles (Cloudflare Turnstile finds .cf-turnstile).
                    if (in_array($class, ['cf-turnstile'], true)) {
                        continue;
                    }
                    if ($class === '' || str_contains($class, '$') || str_contains($class, "'")) {
                        continue;
                    }
                    if (! str_contains($css, '.'.$class)) {
                        $missing[$class] = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file);
                    }
                }
            }
        }

        $this->assertSame([], $missing, "These classes aren't in public/css/tailwind.css — run `npm run build:css` and commit the result.");
    }

    /** @return array<int, string> */
    private function viewFiles(): array
    {
        $dirs = array_merge([resource_path('views')], glob(base_path('packages/*/resources/views')));
        $files = [];
        foreach ($dirs as $dir) {
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)) as $file) {
                if (str_ends_with($file->getFilename(), '.blade.php')) {
                    $files[] = $file->getPathname();
                }
            }
        }

        return $files;
    }
}
