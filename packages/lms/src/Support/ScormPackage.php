<?php

namespace Elibrary\Lms\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use SimpleXMLElement;
use ZipArchive;

/**
 * Unpacks a SCORM 1.2 / 2004 zip onto the private disk and finds the page it
 * starts on. Packages are served back only through ScormController (never as
 * public files, never executed as PHP).
 */
class ScormPackage
{
    public const MAX_FILES = 5000;

    public const MAX_UNPACKED_BYTES = 1024 * 1024 * 1024; // 1 GB

    /** Things no SCORM package needs, refused outright. */
    private const BLOCKED_EXTENSIONS = ['php', 'phtml', 'phar', 'php3', 'php4', 'php5', 'php7', 'php8', 'htaccess', 'htpasswd', 'exe', 'sh', 'bat', 'cmd'];

    /** @return array{path: string, launch: string, version: string} */
    public static function import(UploadedFile $file, int $tenantId): array
    {
        $zip = new ZipArchive;
        if ($zip->open($file->getRealPath()) !== true) {
            self::fail('That file isn\'t a readable zip.');
        }

        try {
            $manifest = $zip->getFromName('imsmanifest.xml');
            if ($manifest === false) {
                self::fail('This zip has no imsmanifest.xml at its top level, so it isn\'t a SCORM package. Export it again as SCORM 1.2 or 2004.');
            }

            [$launch, $version] = self::readManifest($manifest);
            self::checkEntries($zip);

            $path = "scorm/{$tenantId}/".Str::random(32);
            $root = Storage::disk('local')->path($path);
            if (! $zip->extractTo($root)) {
                self::fail('The package couldn\'t be unpacked.');
            }
        } finally {
            $zip->close();
        }

        if (! is_file($root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, strtok($launch, '?#')))) {
            Storage::disk('local')->deleteDirectory($path);
            self::fail("The manifest says the package starts at \"{$launch}\", but that file isn't in the zip.");
        }

        return ['path' => $path, 'launch' => $launch, 'version' => $version];
    }

    public static function delete(?string $path): void
    {
        if ($path && str_starts_with($path, 'scorm/')) {
            Storage::disk('local')->deleteDirectory($path);
        }
    }

    /** @return array{0: string, 1: string} launch href and version ("1.2" or "2004") */
    private static function readManifest(string $xml): array
    {
        $previous = libxml_use_internal_errors(true);
        // LIBXML_NONET: never fetch external entities/DTDs referenced by the package.
        $doc = simplexml_load_string($xml, SimpleXMLElement::class, LIBXML_NONET);
        libxml_use_internal_errors($previous);
        if (! $doc) {
            self::fail('The package\'s imsmanifest.xml couldn\'t be read.');
        }

        $schema = strtolower(trim((string) ($doc->xpath('//*[local-name()="schemaversion"]')[0] ?? '')));
        $version = str_contains($schema, '2004') || str_contains($schema, 'cam 1.3') ? '2004' : '1.2';

        // The default organisation's first item that points at a resource.
        // Identifiers are matched in PHP, never interpolated into XPath.
        $withId = fn (array $nodes, string $id) => collect($nodes)->first(fn ($node) => (string) $node['identifier'] === $id);

        $organizations = $doc->xpath('//*[local-name()="organizations"]')[0] ?? null;
        $default = $organizations ? (string) $organizations['default'] : '';
        $allOrganizations = $doc->xpath('//*[local-name()="organization"]') ?: [];
        $organization = ($default !== '' ? $withId($allOrganizations, $default) : null) ?? ($allOrganizations[0] ?? null);
        $item = $organization ? ($organization->xpath('.//*[local-name()="item"][@identifierref]')[0] ?? null) : null;

        $resources = $doc->xpath('//*[local-name()="resource"]') ?: [];
        $resource = $item ? $withId($resources, (string) $item['identifierref']) : null;
        $resource ??= collect($resources)->first(fn ($node) => trim((string) $node['href']) !== '');

        $href = $resource ? trim((string) $resource['href']) : '';
        if ($href === '') {
            self::fail('The manifest doesn\'t say which page the package starts on.');
        }

        // xml:base on the resource prefixes its href.
        $base = $resource ? trim((string) ($resource->attributes('xml', true)['base'] ?? '')) : '';
        $launch = ltrim(($base !== '' ? rtrim($base, '/').'/' : '').$href, '/');
        if (str_contains($launch, '..') || preg_match('#^[a-z]+:#i', $launch)) {
            self::fail('The package\'s start page points outside the package.');
        }

        return [$launch, $version];
    }

    private static function checkEntries(ZipArchive $zip): void
    {
        if ($zip->numFiles > self::MAX_FILES) {
            self::fail('The package has too many files (over '.self::MAX_FILES.').');
        }

        $total = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $name = str_replace('\\', '/', $stat['name']);
            $total += $stat['size'];

            // Zip-slip: every entry must stay inside the package folder.
            if (str_starts_with($name, '/') || preg_match('#(^|/)\.\.(/|$)#', $name) || preg_match('#^[a-z]:#i', $name)) {
                self::fail('The package contains a file path that points outside it.');
            }
            if (in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), self::BLOCKED_EXTENSIONS, true) || str_ends_with(strtolower($name), '.htaccess')) {
                self::fail("The package contains a file type that isn't allowed ({$name}).");
            }
        }

        if ($total > self::MAX_UNPACKED_BYTES) {
            self::fail('The package is too large once unpacked (over 1 GB).');
        }
    }

    private static function fail(string $message): never
    {
        throw ValidationException::withMessages(['scorm_package' => $message]);
    }
}
