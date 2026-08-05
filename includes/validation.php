<?php

/**
 * Sanitize a plain-text field submitted by a user.
 * HTML is not accepted in page titles, page content, names, or comments.
 */
function sanitizePlainText(mixed $value): string
{
    if (!is_string($value)) {
        return '';
    }

    return trim(strip_tags(str_replace("\0", '', $value)));
}

/** Reject non-printing control characters, while permitting tabs/new lines. */
function containsInvalidControlCharacters(string $value): bool
{
    return preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', $value) === 1;
}

/** Retrieve and validate a positive numeric ID from GET or POST. */
function positiveInputId(int $inputType, string $name): ?int
{
    $id = filter_input($inputType, $name, FILTER_VALIDATE_INT);

    return ($id !== false && $id !== null && $id > 0) ? $id : null;
}
