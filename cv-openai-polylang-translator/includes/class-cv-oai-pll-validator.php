<?php
/**
 * Class CV_OAI_PLL_Validator
 *
 * Validates translated strings against source Finnish text to prevent AI
 * hallucinations, structural breakages, and security risks.
 *
 * @package CV_OpenAI_Polylang_Translator
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_OAI_PLL_Validator {
    /**
     * Validates translated data array against the original source data array.
     *
     * @param array $source_data     Original flat or structured key/value array.
     * @param array $translated_data Translated key/value array returned by OpenAI.
     * @return true|WP_Error Returns true if valid, or a WP_Error describing the failure.
     */
    public static function validate($source_data, $translated_data) {
        if (!is_array($translated_data)) {
            return new WP_Error('validation_invalid_format', __('OpenAI response is not a valid JSON structure.', 'cv-openai-polylang-translator'));
        }

        foreach ($source_data as $key => $source_val) {
            // Verify the key exists in the translation response
            if (!array_key_exists($key, $translated_data)) {
                return new WP_Error(
                    'validation_missing_key',
                    sprintf(__('Validation failed: Missing key "%s" in translation response.', 'cv-openai-polylang-translator'), $key)
                );
            }

            $trans_val = $translated_data[$key];

            // If we have arrays (e.g. nested block content), recursively validate
            if (is_array($source_val)) {
                $sub_validation = self::validate($source_val, $trans_val);
                if (is_wp_error($sub_validation)) {
                    return $sub_validation;
                }
                continue;
            }

            // Perform checks on text content
            if (is_string($source_val) && is_string($trans_val)) {
                $source_val_clean = trim($source_val);
                $trans_val_clean  = trim($trans_val);

                // Prevent source content from being deleted without replacement
                if (!empty($source_val_clean) && empty($trans_val_clean)) {
                    return new WP_Error(
                        'validation_empty_translation',
                        sprintf(__('Validation failed: Text for "%s" was deleted without translation.', 'cv-openai-polylang-translator'), $key)
                    );
                }

                // Security check: No script or iframe added unless present in source
                if (stripos($trans_val_clean, '<script') !== false && stripos($source_val_clean, '<script') === false) {
                    return new WP_Error('validation_security_script', __('Security check failed: The translation introduced an unauthorized <script> tag.', 'cv-openai-polylang-translator'));
                }
                if (stripos($trans_val_clean, '<iframe') !== false && stripos($source_val_clean, '<iframe') === false) {
                    return new WP_Error('validation_security_iframe', __('Security check failed: The translation introduced an unauthorized <iframe> tag.', 'cv-openai-polylang-translator'));
                }

                // Extract URLs and verify presence
                $source_urls = self::extract_urls($source_val_clean);
                $trans_urls  = self::extract_urls($trans_val_clean);
                foreach ($source_urls as $url) {
                    if (!in_array($url, $trans_urls, true)) {
                        return new WP_Error(
                            'validation_missing_url',
                            sprintf(__('Validation failed: URL "%s" was missing or altered in translation.', 'cv-openai-polylang-translator'), $url)
                        );
                    }
                }

                // Verify no unknown external domains are introduced
                $source_hosts = array_filter(array_map(function($url) { return parse_url($url, PHP_URL_HOST); }, $source_urls));
                $trans_hosts  = array_filter(array_map(function($url) { return parse_url($url, PHP_URL_HOST); }, $trans_urls));
                foreach ($trans_hosts as $host) {
                    if ($host && !in_array($host, $source_hosts, true)) {
                        return new WP_Error(
                            'validation_new_domain',
                            sprintf(__('Validation failed: Unknown domain name "%s" was introduced in translation.', 'cv-openai-polylang-translator'), $host)
                        );
                    }
                }

                // Extract and verify Email addresses
                $source_emails = self::extract_emails($source_val_clean);
                $trans_emails  = self::extract_emails($trans_val_clean);
                foreach ($source_emails as $email) {
                    if (!in_array($email, $trans_emails, true)) {
                        return new WP_Error(
                            'validation_missing_email',
                            sprintf(__('Validation failed: Email address "%s" was missing or altered in translation.', 'cv-openai-polylang-translator'), $email)
                        );
                    }
                }

                // Extract and verify Phone numbers
                $source_phones = self::extract_phone_numbers($source_val_clean);
                $trans_phones  = self::extract_phone_numbers($trans_val_clean);
                foreach ($source_phones as $phone) {
                    if (!in_array($phone, $trans_phones, true)) {
                        return new WP_Error(
                            'validation_missing_phone',
                            sprintf(__('Validation failed: Phone number "%s" was missing or altered in translation.', 'cv-openai-polylang-translator'), $phone)
                        );
                    }
                }

                // Extract and verify Numbers (to protect prices, version numbers, currencies, coordinates, counts)
                $source_numbers = self::extract_numbers($source_val_clean);
                $trans_numbers  = self::extract_numbers($trans_val_clean);
                foreach ($source_numbers as $num) {
                    if (!in_array($num, $trans_numbers, true)) {
                        return new WP_Error(
                            'validation_missing_number',
                            sprintf(__('Validation failed: Number "%s" was missing or modified in translation.', 'cv-openai-polylang-translator'), $num)
                        );
                    }
                }

                // Check critical B2B terms and product names (must remain in Latin)
                $product_names = [
                    'i4ware',
                    'i4ware Software',
                    'i4ware®',
                    'Timesheet for Jira',
                    'Jira',
                    'Atlassian',
                    'OpenAI',
                    'Microsoft',
                    'SAP',
                    'ERP',
                    'SaaS',
                    'WordPress',
                    'Polylang',
                    'Advanced Custom Fields',
                    'ACF',
                    'Freshworks',
                    'Freshchat',
                    'Microsoft Teams'
                ];
                foreach ($product_names as $product) {
                    // Match word boundary to avoid substrings (e.g. Microsoft matching Microsoft Teams)
                    $escaped_product = preg_quote($product, '/');
                    if (preg_match('/\b' . $escaped_product . '\b/i', $source_val_clean)) {
                        if (!preg_match('/\b' . $escaped_product . '\b/i', $trans_val_clean)) {
                            return new WP_Error(
                                'validation_missing_product',
                                sprintf(__('Validation failed: Product name or trademark "%s" was missing or altered in translation.', 'cv-openai-polylang-translator'), $product)
                            );
                        }
                    }
                }

                // Extract and verify shortcodes
                $source_shortcodes = self::extract_shortcodes($source_val_clean);
                $trans_shortcodes  = self::extract_shortcodes($trans_val_clean);
                foreach ($trans_shortcodes as $sc) {
                    if (!in_array($sc, $source_shortcodes, true)) {
                        return new WP_Error(
                            'validation_new_shortcode',
                            sprintf(__('Validation failed: Unexpected shortcode "[%s]" was introduced in translation.', 'cv-openai-polylang-translator'), $sc)
                        );
                    }
                }
            }
        }

        return true;
    }

    /**
     * Helper: Extract URLs from text.
     */
    private static function extract_urls($text) {
        preg_match_all('/https?:\/\/[^\s\'"<>\(\)]+/i', $text, $matches);
        return is_array($matches) && !empty($matches[0]) ? array_unique($matches[0]) : [];
    }

    /**
     * Helper: Extract email addresses from text.
     */
    private static function extract_emails($text) {
        preg_match_all('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/i', $text, $matches);
        return is_array($matches) && !empty($matches[0]) ? array_unique($matches[0]) : [];
    }

    /**
     * Helper: Extract phone numbers from text.
     */
    private static function extract_phone_numbers($text) {
        // Matches typical phone formats, e.g. +358 40 1234567, or 040-1234567, etc.
        preg_match_all('/\+?[0-9][0-9\s\-]{4,}[0-9]/', $text, $matches);
        return is_array($matches) && !empty($matches[0]) ? array_unique(array_map('trim', $matches[0])) : [];
    }

    /**
     * Helper: Extract numbers (integers and decimals) from text.
     */
    private static function extract_numbers($text) {
        preg_match_all('/\b\d+(?:[\.,]\d+)?\b/', $text, $matches);
        return is_array($matches) && !empty($matches[0]) ? array_unique($matches[0]) : [];
    }

    /**
     * Helper: Extract shortcode names (e.g. 'gallery', 'contact-form') from text.
     */
    private static function extract_shortcodes($text) {
        preg_match_all('/\[([a-zA-Z0-9_\-]+)/', $text, $matches);
        return is_array($matches) && !empty($matches[1]) ? array_unique($matches[1]) : [];
    }
}
