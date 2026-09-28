<?php

namespace Elibrary\Library\Documents;

/** Turns Word, Excel and PowerPoint files into PDF for secure viewing. */
interface DocumentConverter
{
    /** Whether this server can convert at all. */
    public function isAvailable(): bool;

    /**
     * Converts $sourcePath (an absolute path) into a PDF written to
     * $pdfPath (also absolute). Returns false if it couldn't.
     */
    public function toPdf(string $sourcePath, string $pdfPath): bool;
}
