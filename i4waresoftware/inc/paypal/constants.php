<?php
/**
 * i4ware PayPal & Tax Constants and Country Reference Data
 *
 * Distinguishes EU VAT Territory vs. EEA, payment types, customer types, and tax classifications.
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('I4WARE_PAYMENT_ADVERTISING_SERVICE')) {
    define('I4WARE_PAYMENT_ADVERTISING_SERVICE', 'advertising_service');
}
if (!defined('I4WARE_PAYMENT_COMMERCIAL_SPONSORSHIP')) {
    define('I4WARE_PAYMENT_COMMERCIAL_SPONSORSHIP', 'commercial_sponsorship');
}
if (!defined('I4WARE_PAYMENT_SOFTWARE_SERVICE')) {
    define('I4WARE_PAYMENT_SOFTWARE_SERVICE', 'software_service');
}
if (!defined('I4WARE_PAYMENT_GRATUITOUS_SUPPORT')) {
    define('I4WARE_PAYMENT_GRATUITOUS_SUPPORT', 'gratuitous_support');
}

// Customer types (Asiakastyypit)
if (!defined('I4WARE_CUSTOMER_BUSINESS')) {
    define('I4WARE_CUSTOMER_BUSINESS', 'business');
}
if (!defined('I4WARE_CUSTOMER_INDIVIDUAL')) {
    define('I4WARE_CUSTOMER_INDIVIDUAL', 'individual');
}

// Tax Classifications (Veroluokitukset)
if (!defined('I4WARE_TAX_FI_DOMESTIC_TAXABLE')) {
    define('I4WARE_TAX_FI_DOMESTIC_TAXABLE', 'FI_DOMESTIC_TAXABLE'); // Suomen verollinen myynti (25,5 %)
}
if (!defined('I4WARE_TAX_EU_REVERSE_CHARGE')) {
    define('I4WARE_TAX_EU_REVERSE_CHARGE', 'EU_REVERSE_CHARGE'); // Käännetty verovelvollisuus (AVL 65 § / Direktiivi 2006/112/EY art. 44)
}
if (!defined('I4WARE_TAX_EU_CONSUMER_TAXABLE')) {
    define('I4WARE_TAX_EU_CONSUMER_TAXABLE', 'EU_CONSUMER_TAXABLE'); // EU-kuluttajamyynti (Suomen ALV 25,5 %)
}
if (!defined('I4WARE_TAX_NON_EU_EXPORT')) {
    define('I4WARE_TAX_NON_EU_EXPORT', 'NON_EU_EXPORT'); // Kansainvälinen B2B-palvelumyynti EU:n ulkopuolelle (AVL 65 §)
}
if (!defined('I4WARE_TAX_NON_EU_CONSUMER')) {
    define('I4WARE_TAX_NON_EU_CONSUMER', 'NON_EU_CONSUMER'); // Kansainvälinen B2C-palvelumyynti EU:n ulkopuolelle (AVL 68-69 §)
}
if (!defined('I4WARE_TAX_OUTSIDE_SCOPE_GRATUITOUS')) {
    define('I4WARE_TAX_OUTSIDE_SCOPE_GRATUITOUS', 'OUTSIDE_SCOPE_GRATUITOUS'); // Vastikkeeton tuki, ALV:n soveltamisalan ulkopuolella (EI 'ALV 0%')
}
if (!defined('I4WARE_TAX_MANUAL_REVIEW_REQUIRED')) {
    define('I4WARE_TAX_MANUAL_REVIEW_REQUIRED', 'MANUAL_REVIEW_REQUIRED'); // Manuaalista selvitystä vaativa tapaus
}

// Standard Finnish VAT Rate (Standard ALV-kanta 25.5 % voimaan 1.9.2024 alkaen)
if (!defined('I4WARE_STANDARD_FINNISH_VAT_RATE')) {
    define('I4WARE_STANDARD_FINNISH_VAT_RATE', 25.5);
}

// VIES Status Codes
if (!defined('I4WARE_VIES_VALID')) {
    define('I4WARE_VIES_VALID', 'VALID');
}
if (!defined('I4WARE_VIES_INVALID')) {
    define('I4WARE_VIES_INVALID', 'INVALID');
}
if (!defined('I4WARE_VIES_SERVICE_UNAVAILABLE')) {
    define('I4WARE_VIES_SERVICE_UNAVAILABLE', 'SERVICE_UNAVAILABLE');
}
if (!defined('I4WARE_VIES_REQUIRES_CLARIFICATION')) {
    define('I4WARE_VIES_REQUIRES_CLARIFICATION', 'REQUIRES_CLARIFICATION');
}
if (!defined('I4WARE_VIES_LOCAL_NETWORK_ERROR')) {
    define('I4WARE_VIES_LOCAL_NETWORK_ERROR', 'LOCAL_NETWORK_ERROR');
}
if (!defined('I4WARE_VIES_API_ERROR')) {
    define('I4WARE_VIES_API_ERROR', 'API_ERROR');
}
if (!defined('I4WARE_VIES_NOT_APPLICABLE')) {
    define('I4WARE_VIES_NOT_APPLICABLE', 'NOT_APPLICABLE');
}

/**
 * Returns list of European Union VAT Member State ISO-2 country codes.
 *
 * IMPORTANT: ETA / EEA is NOT equivalent to EU VAT Territory!
 * Norway (NO), Iceland (IS), and Liechtenstein (LI) are in the EEA but NOT in the EU VAT territory.
 * Switzerland (CH) and United Kingdom (GB) are also outside the EU VAT territory.
 * Certain territories (e.g. Canary Islands, Ceuta, Melilla, French DOM) have special VAT status.
 */
function i4ware_get_eu_vat_countries()
{
    return [
        'AT' => 'Itävalta (Austria)',
        'BE' => 'Belgia (Belgium)',
        'BG' => 'Bulgaria',
        'CY' => 'Kypros (Cyprus)',
        'CZ' => 'Tšekki (Czechia)',
        'DE' => 'Saksa (Germany)',
        'DK' => 'Tanska (Denmark)',
        'EE' => 'Viro (Estonia)',
        'ES' => 'Espanja (Spain)',
        'FI' => 'Suomi (Finland)',
        'FR' => 'Ranska (France)',
        'GR' => 'Kreikka (Greece)',
        'HR' => 'Kroatia (Croatia)',
        'HU' => 'Unkari (Hungary)',
        'IE' => 'Irlanti (Ireland)',
        'IT' => 'Italia (Italy)',
        'LT' => 'Liettua (Lithuania)',
        'LU' => 'Luxemburg (Luxembourg)',
        'LV' => 'Latvia',
        'MT' => 'Malta',
        'NL' => 'Alankomaat (Netherlands)',
        'PL' => 'Puola (Poland)',
        'PT' => 'Portugali (Portugal)',
        'RO' => 'Romania',
        'SE' => 'Ruotsi (Sweden)',
        'SI' => 'Slovenia',
        'SK' => 'Slovakia',
    ];
}

/**
 * Check if a country code belongs to the EU VAT territory.
 * Note: Greece uses 'EL' for VAT numbers in VIES, while ISO-2 code is 'GR'.
 */
function i4ware_is_eu_vat_member_state($country_code)
{
    $country_code = strtoupper(trim($country_code));
    if ($country_code === 'EL') {
        return true;
    }
    $eu_countries = i4ware_get_eu_vat_countries();
    return isset($eu_countries[$country_code]);
}

/**
 * Normalize VAT country code for VIES (e.g. Greece GR -> EL).
 */
function i4ware_normalize_vies_country_code($country_code)
{
    $country_code = strtoupper(trim($country_code));
    if ($country_code === 'GR') {
        return 'EL';
    }
    return $country_code;
}

/**
 * Returns comprehensive list of countries for selection.
 */
function i4ware_get_all_countries()
{
    $eu = i4ware_get_eu_vat_countries();
    $other = [
        'NO' => 'Norja (Norway) [EEA / Non-EU VAT]',
        'IS' => 'Islanti (Iceland) [EEA / Non-EU VAT]',
        'LI' => 'Liechtenstein [EEA / Non-EU VAT]',
        'CH' => 'Sveitsi (Switzerland)',
        'GB' => 'Yhdistynyt kuningaskunta (United Kingdom)',
        'US' => 'Yhdysvallat (United States)',
        'CA' => 'Kanada (Canada)',
        'AU' => 'Australia',
        'NZ' => 'Uusi-Seelanti (New Zealand)',
        'JP' => 'Japani (Japan)',
        'SG' => 'Singapore',
        'AE' => 'Yhdistyneet arabiemiirikunnat (United Arab Emirates)',
        'IL' => 'Israel',
        'IN' => 'Intia (India)',
        'BR' => 'Brasilia (Brazil)',
        'MX' => 'Meksiko (Mexico)',
        'ZA' => 'Etelä-Afrikka (South Africa)',
        'OTHER' => 'Muu maa (Other country)'
    ];
    return array_merge($eu, $other);
}
