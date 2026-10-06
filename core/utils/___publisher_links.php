<?php

require_once __DIR__ . '/../../account/publisher.php';

// Normalizes a domain/host to the canonical profile key (lowercase, no "www."), or null if invalid.
function sn_publisher_domain(string $domain): ?string
{
    return publisher_normalize_domain(publisher_domain_for_matching($domain));
}

// Public profile URL for a publisher domain, or '' if the domain is invalid.
function sn_publisher_profile_url(string $domain): string
{
    $normalized = sn_publisher_domain($domain);

    return $normalized === null ? '' : '/publisher-profile.php?domain=' . rawurlencode($normalized);
}
