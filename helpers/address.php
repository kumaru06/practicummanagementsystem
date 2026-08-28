<?php

function student_has_structured_address(array $student): bool
{
    foreach (['address_street', 'address_barangay', 'address_municipality', 'address_province'] as $field) {
        if (trim((string)($student[$field] ?? '')) === '') {
            return false;
        }
    }

    return true;
}

function student_has_legacy_address_only(array $student): bool
{
    return trim((string)($student['address'] ?? '')) !== '' && !student_has_structured_address($student);
}

function student_compose_address_from_parts(array $data): string
{
    $parts = array_filter([
        trim((string)($data['address_street'] ?? '')),
        trim((string)($data['address_barangay'] ?? '')),
        trim((string)($data['address_municipality'] ?? '')),
        trim((string)($data['address_province'] ?? '')),
    ], static fn (string $part): bool => $part !== '');

    return implode(', ', $parts);
}

function student_address_payload_has_structured(array $data): bool
{
    foreach (['address_street', 'address_barangay', 'address_municipality', 'address_province'] as $field) {
        if (trim((string)($data[$field] ?? '')) !== '') {
            return true;
        }
    }

    foreach (['address_barangay_code', 'address_municipality_code', 'address_province_code'] as $field) {
        if (trim((string)($data[$field] ?? '')) !== '') {
            return true;
        }
    }

    return false;
}

function student_structured_address_is_complete(array $data): bool
{
    foreach (['address_street', 'address_barangay', 'address_municipality', 'address_province'] as $field) {
        if (trim((string)($data[$field] ?? '')) === '') {
            return false;
        }
    }

    return true;
}

function student_display_address(array $student): string
{
    if (student_has_structured_address($student)) {
        return student_compose_address_from_parts($student);
    }

    return trim((string)($student['address'] ?? ''));
}
