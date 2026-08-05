<?php

/**
 * Confirm that a stored blog-image path points to an existing project file.
 * Invalid, removed, absolute, or out-of-project paths return false.
 */
function blogImageExists(?string $imagePath): bool
{
    if ($imagePath === null || trim($imagePath) === '') {
        return false;
    }

    $imagePath = str_replace('\\', '/', trim($imagePath));

    if (
        str_contains($imagePath, "\0") ||
        str_starts_with($imagePath, '/') ||
        preg_match('/^[A-Za-z]:\//', $imagePath) === 1
    ) {
        return false;
    }

    $projectRoot = realpath(dirname(__DIR__));
    $candidate = realpath(
        dirname(__DIR__) . DIRECTORY_SEPARATOR .
        str_replace('/', DIRECTORY_SEPARATOR, $imagePath)
    );

    if ($projectRoot === false || $candidate === false || !is_file($candidate)) {
        return false;
    }

    $projectPrefix = strtolower($projectRoot . DIRECTORY_SEPARATOR);

    return str_starts_with(strtolower($candidate), $projectPrefix);
}
