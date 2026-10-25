<?php
/**
 * i4ware EU VIES Server-Side Verification Client
 *
 * Implements official European Commission VIES REST API and SOAP fallback
 * for verifying EU VAT numbers, logging audit records in UTC, and checking
 * company name/address correspondence without assuming missing data is an error.
 */

if (!defined('ABSPATH')) {
    exit;
}

class I4ware_VIES_Client
{
    const OFFICIAL_REST_POST_URL = 'https://ec.europa.eu/taxation_customs/vies/rest-api/check-vat-number';
    const OFFICIAL_REST_GET_BASE = 'https://ec.europa.eu/taxation_customs/vies/rest-api/ms/';
    const OFFICIAL_SOAP_WSDL = 'https://ec.europa.eu/taxation_customs/vies/services/checkVatService?wsdl';

    // Default requester credentials for i4ware Software (Toiminimi, Finland)
    // Providing valid requester details generates an official consultation identifier from VIES
    const REQUESTER_COUNTRY = 'FI';
    const REQUESTER_VAT_NUMBER = '27395946'; // Correct Finnish VAT number for i4ware Software (Y-tunnus 2739594-6)

    public static function get_requester_country()
    {
        return strtoupper(trim((string) get_option('i4ware_vies_requester_country', self::REQUESTER_COUNTRY)));
    }

    public static function get_requester_number()
    {
        return preg_replace('/[^0-9A-Za-z]/', '', (string) get_option('i4ware_vies_requester_vat', self::REQUESTER_VAT_NUMBER));
    }

    public static function is_requester_enabled()
    {
        return (bool) get_option('i4ware_vies_use_requester', true);
    }

    public static function get_timeout()
    {
        $to = (int) get_option('i4ware_vies_timeout', 12);
        return ($to >= 5 && $to <= 30) ? $to : 12;
    }

    /**
     * Verify an EU VAT number against official EU VIES APIs.
     *
     * @param string $country_code ISO-2 country code (e.g. DE, FR, SE, or EL for Greece)
     * @param string $vat_number Pure VAT number string without country prefix
     * @param string $company_name Customer provided company name (optional)
     * @param string $company_address Customer provided address (optional)
     * @param int|null $timeout_seconds Request timeout in seconds (default 12s)
     * @return array Verification result details
     */
    public static function check_vat($country_code, $vat_number, $company_name = '', $company_address = '', $timeout_seconds = null)
    {
        $country_code = strtoupper(trim($country_code));
        $normalized_country = i4ware_normalize_vies_country_code($country_code);

        if ($timeout_seconds === null || $timeout_seconds < 8) {
            $timeout_seconds = self::get_timeout();
        }

        // Sanitize VAT number: remove spaces, dots, hyphens, and any leading country code
        $cleaned_vat = preg_replace('/[^A-Za-z0-9]/', '', $vat_number);
        if (stripos($cleaned_vat, $normalized_country) === 0) {
            $cleaned_vat = substr($cleaned_vat, strlen($normalized_country));
        } elseif (stripos($cleaned_vat, $country_code) === 0) {
            $cleaned_vat = substr($cleaned_vat, strlen($country_code));
        }
        $cleaned_vat = strtoupper(trim($cleaned_vat));

        if (!i4ware_is_eu_vat_member_state($country_code) && $normalized_country !== 'EL') {
            return [
                'status' => I4WARE_VIES_NOT_APPLICABLE,
                'is_valid' => false,
                'country_code' => $country_code,
                'vat_number' => $cleaned_vat,
                'full_vat' => $country_code . $cleaned_vat,
                'consultation_number' => '',
                'request_date_utc' => gmdate('Y-m-d\TH:i:s\Z'),
                'source' => 'local_validation',
                'name' => '',
                'address' => '',
                'error_category' => 'not_applicable',
                'name_match_status' => 'not_eu_member_state',
                'message' => 'Valittu maa ei kuulu EU:n ALV-alueeseen. VIES-tarkistusta ei sovelleta.'
            ];
        }

        if (empty($cleaned_vat)) {
            return [
                'status' => I4WARE_VIES_INVALID,
                'is_valid' => false,
                'country_code' => $country_code,
                'vat_number' => '',
                'full_vat' => '',
                'consultation_number' => '',
                'request_date_utc' => gmdate('Y-m-d\TH:i:s\Z'),
                'source' => 'local_validation',
                'name' => '',
                'address' => '',
                'error_category' => 'invalid_input',
                'name_match_status' => 'empty_vat_number',
                'message' => 'ALV-tunniste puuttuu.'
            ];
        }

        // 1. Try Official VIES REST API POST endpoint (check-vat-number) with bounded retry
        $result = self::query_rest_post($normalized_country, $cleaned_vat, $company_name, $company_address, $timeout_seconds);

        // 2. If POST failed due to service outage, fallback to REST GET endpoint
        if (!$result || $result['status'] === I4WARE_VIES_SERVICE_UNAVAILABLE) {
            $get_result = self::query_rest_get($normalized_country, $cleaned_vat, $timeout_seconds);
            if ($get_result && ($get_result['is_valid'] || $get_result['status'] === I4WARE_VIES_INVALID)) {
                $result = $get_result;
            }
        }

        // 3. If REST is unavailable and SOAP extension is present, try SOAP fallback
        if ((!$result || $result['status'] === I4WARE_VIES_SERVICE_UNAVAILABLE) && class_exists('SoapClient')) {
            $soap_result = self::query_soap($normalized_country, $cleaned_vat, $timeout_seconds);
            if ($soap_result && ($soap_result['is_valid'] || $soap_result['status'] === I4WARE_VIES_INVALID)) {
                $result = $soap_result;
            }
        }

        if (!$result) {
            $result = [
                'status' => I4WARE_VIES_SERVICE_UNAVAILABLE,
                'is_valid' => false,
                'country_code' => $country_code,
                'vat_number' => $cleaned_vat,
                'full_vat' => $normalized_country . $cleaned_vat,
                'consultation_number' => '',
                'request_date_utc' => gmdate('Y-m-d\TH:i:s\Z'),
                'source' => 'ec.europa.eu VIES REST/SOAP',
                'name' => '',
                'address' => '',
                'error_category' => 'vies_outage',
                'name_match_status' => 'service_unreachable',
                'message' => 'Tarkistuspalvelu ei ole juuri nyt käytettävissä. Yritä uudelleen.'
            ];
        }

        // 4. Verify company name and address matching when available
        if ($result['is_valid']) {
            $match_analysis = self::analyze_name_address_match(
                $company_name,
                $company_address,
                $result['name'],
                $result['address'],
                isset($result['raw']['traderNameMatch']) ? $result['raw']['traderNameMatch'] : null
            );

            $result['name_match_status'] = $match_analysis['match_status'];
            $result['name_match_details'] = $match_analysis['details'];

            // If name was provided by VIES but conflicts severely with the user's input, flag for clarification
            if ($match_analysis['match_status'] === 'mismatch') {
                $result['status'] = I4WARE_VIES_REQUIRES_CLARIFICATION;
                $result['message'] = 'Yritystiedot edellyttävät lisätarkistusta (ALV-rekisterin virallinen nimi ei vastaa ilmoitettua ostajaa).';
            } elseif ($match_analysis['match_status'] === 'matched') {
                $result['message'] = 'ALV-tunniste on voimassa ja yrityksen nimi vastaa rekisteriä.';
            } else {
                // Name was not returned by member state (e.g. Germany, Spain privacy suppression)
                $result['message'] = 'ALV-tunniste on voimassa.';
            }
        } elseif ($result['status'] === I4WARE_VIES_INVALID) {
            $result['message'] = 'ALV-tunnistetta ei voitu vahvistaa. Tarkista tiedot.';
        } elseif ($result['status'] === I4WARE_VIES_LOCAL_NETWORK_ERROR) {
            $result['message'] = 'Yhteys VIES-palveluun epäonnistui paikallisen verkko- tai aikakatkaisuvirheen vuoksi. Tilaus siirretään ylläpidon tarkistukseen.';
        } elseif ($result['status'] === I4WARE_VIES_SERVICE_UNAVAILABLE) {
            $result['message'] = 'EU VIES -tarkistuspalvelu tai jäsenvaltion rekisteri on tilapäisesti poissa käytöstä. Tilaus siirretään ylläpidon tarkistukseen.';
        }

        // 5. Store audit log in database
        self::log_audit_record($result);

        return $result;
    }

    /**
     * Query Official VIES REST API via POST
     */
    private static function query_rest_post($country, $vat_number, $name, $address, $timeout)
    {
        // 1. Check if WordPress configuration blocks outbound HTTP calls
        if (defined('WP_HTTP_BLOCK_EXTERNAL') && WP_HTTP_BLOCK_EXTERNAL) {
            $accessible = defined('WP_ACCESSIBLE_HOSTS') ? explode(',', WP_ACCESSIBLE_HOSTS) : [];
            $accessible = array_map('trim', $accessible);
            if (!in_array('ec.europa.eu', $accessible) && !in_array('*.europa.eu', $accessible)) {
                return [
                    'status' => I4WARE_VIES_LOCAL_NETWORK_ERROR,
                    'is_valid' => false,
                    'country_code' => $country,
                    'vat_number' => $vat_number,
                    'full_vat' => $country . $vat_number,
                    'consultation_number' => '',
                    'request_date_utc' => gmdate('Y-m-d\TH:i:s\Z'),
                    'source' => 'ec.europa.eu VIES REST POST',
                    'error_category' => 'local_network_error',
                    'error_code' => 'wp_http_block_external',
                    'error_detail' => 'WordPress WP_HTTP_BLOCK_EXTERNAL estää yhteydet. Lisää ec.europa.eu vakioon WP_ACCESSIBLE_HOSTS.',
                    'http_status' => 0,
                    'raw_snippet' => '',
                    'attempts' => 1
                ];
            }
        }

        $req_country = self::get_requester_country();
        $req_number = self::get_requester_number();
        $include_requester = self::is_requester_enabled() && !empty($req_country) && !empty($req_number);

        $max_attempts = 2;
        $attempt = 0;
        $last_result = null;

        while ($attempt < $max_attempts) {
            $attempt++;

            $payload = [
                'countryCode' => $country,
                'vatNumber' => $vat_number
            ];

            if ($include_requester) {
                $payload['requesterMemberStateCode'] = $req_country;
                $payload['requesterNumber'] = $req_number;
            }

            if (!empty($name)) {
                $payload['traderName'] = $name;
            }

            $url = self::OFFICIAL_REST_POST_URL;
            $args = [
                'method' => 'POST',
                'timeout' => $timeout,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'User-Agent' => 'i4ware-VAT-Validator/1.1 (+https://www.i4ware.fi)'
                ],
                'body' => wp_json_encode($payload),
                'sslverify' => true
            ];

            $response = wp_remote_post($url, $args);

            // A) Local Transport / Network Errors (is_wp_error)
            if (is_wp_error($response)) {
                $err_code = $response->get_error_code();
                $err_msg = $response->get_error_message();

                $subtype = 'network_error';
                if (stripos($err_msg, 'timed out') !== false || preg_match('/\bcURL error 28\b/i', $err_msg)) {
                    $subtype = 'timeout';
                } elseif (stripos($err_msg, 'SSL') !== false || stripos($err_msg, 'certificate') !== false || preg_match('/\bcURL error (?:35|51|58|59|60|77|80|82|83|90|91)\b/i', $err_msg)) {
                    $subtype = 'tls_certificate_error';
                } elseif (stripos($err_msg, 'Could not resolve host') !== false || preg_match('/\bcURL error 6\b/i', $err_msg)) {
                    $subtype = 'dns_failure';
                } elseif (stripos($err_msg, 'Failed to connect') !== false || preg_match('/\bcURL error 7\b/i', $err_msg)) {
                    $subtype = 'connection_refused_or_firewall';
                }

                $last_result = [
                    'status' => I4WARE_VIES_LOCAL_NETWORK_ERROR,
                    'is_valid' => false,
                    'country_code' => $country,
                    'vat_number' => $vat_number,
                    'full_vat' => $country . $vat_number,
                    'consultation_number' => '',
                    'request_date_utc' => gmdate('Y-m-d\TH:i:s\Z'),
                    'source' => 'ec.europa.eu VIES REST POST',
                    'error_category' => 'local_network_error',
                    'error_code' => $err_code . ':' . $subtype,
                    'error_detail' => $err_msg,
                    'http_status' => 0,
                    'raw_snippet' => '',
                    'attempts' => $attempt
                ];

                if ($attempt < $max_attempts) {
                    usleep(500000); // 500ms backoff
                    continue;
                }
                return $last_result;
            }

            // B) HTTP Response received from ec.europa.eu
            $code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            $snippet = substr($body, 0, 250);

            if (is_array($data)) {
                // Check if VIES errorWrappers returned
                if (!empty($data['errorWrappers']) && is_array($data['errorWrappers'])) {
                    $first_err = isset($data['errorWrappers'][0]['error']) ? $data['errorWrappers'][0]['error'] : 'UNKNOWN_VIES_ERROR';

                    // If requester info is invalid, retry immediately WITHOUT requester
                    if ($first_err === 'INVALID_REQUESTER_INFO' && $include_requester) {
                        $include_requester = false;
                        continue;
                    }

                    // Member state or central VIES outage
                    $outage_errors = ['MS_UNAVAILABLE', 'TIMEOUT', 'SERVICE_UNAVAILABLE', 'MS_MAX_CONCURRENT_REQ', 'SERVER_BUSY'];
                    if (in_array($first_err, $outage_errors, true)) {
                        $last_result = [
                            'status' => I4WARE_VIES_SERVICE_UNAVAILABLE,
                            'is_valid' => false,
                            'country_code' => $country,
                            'vat_number' => $vat_number,
                            'full_vat' => $country . $vat_number,
                            'consultation_number' => '',
                            'request_date_utc' => gmdate('Y-m-d\TH:i:s\Z'),
                            'source' => 'ec.europa.eu VIES REST POST',
                            'error_category' => 'vies_outage',
                            'error_code' => $first_err,
                            'error_detail' => 'VIES-palvelukatko: Jäsenvaltion tai VIES-keskusjärjestelmän palvelu tilapäisesti poissa käytöstä (' . $first_err . ').',
                            'http_status' => $code,
                            'raw_snippet' => $snippet,
                            'attempts' => $attempt,
                            'raw' => $data
                        ];

                        if ($attempt < $max_attempts) {
                            usleep(500000);
                            continue;
                        }
                        return $last_result;
                    }

                    // Malformed input (e.g. invalid characters or non-existent country)
                    if ($first_err === 'INVALID_INPUT') {
                        return [
                            'status' => I4WARE_VIES_INVALID,
                            'is_valid' => false,
                            'country_code' => $country,
                            'vat_number' => $vat_number,
                            'full_vat' => $country . $vat_number,
                            'consultation_number' => '',
                            'request_date_utc' => gmdate('Y-m-d\TH:i:s\Z'),
                            'source' => 'ec.europa.eu VIES REST POST',
                            'error_category' => 'api_request_error',
                            'error_code' => $first_err,
                            'error_detail' => 'Virheellinen ALV-tunnisteen tai maakoodin muoto (INVALID_INPUT).',
                            'http_status' => $code,
                            'raw_snippet' => $snippet,
                            'attempts' => $attempt,
                            'raw' => $data
                        ];
                    }

                    // Other VIES API error
                    return [
                        'status' => I4WARE_VIES_SERVICE_UNAVAILABLE,
                        'is_valid' => false,
                        'country_code' => $country,
                        'vat_number' => $vat_number,
                        'full_vat' => $country . $vat_number,
                        'consultation_number' => '',
                        'request_date_utc' => gmdate('Y-m-d\TH:i:s\Z'),
                        'source' => 'ec.europa.eu VIES REST POST',
                        'error_category' => 'api_request_error',
                        'error_code' => $first_err,
                        'error_detail' => 'VIES-rajapintavirhe: ' . $first_err,
                        'http_status' => $code,
                        'raw_snippet' => $snippet,
                        'attempts' => $attempt,
                        'raw' => $data
                    ];
                }

                // Normal VIES validation response
                if (isset($data['valid'])) {
                    $is_valid = !empty($data['valid']);
                    $consultation_id = isset($data['requestIdentifier']) ? $data['requestIdentifier'] : '';
                    $returned_name = isset($data['name']) && $data['name'] !== '---' ? trim($data['name']) : '';
                    $returned_address = isset($data['address']) && $data['address'] !== '---' ? trim($data['address']) : '';
                    $request_date = isset($data['requestDate']) ? $data['requestDate'] : gmdate('Y-m-d\TH:i:s\Z');

                    return [
                        'status' => $is_valid ? I4WARE_VIES_VALID : I4WARE_VIES_INVALID,
                        'is_valid' => $is_valid,
                        'country_code' => $country,
                        'vat_number' => $vat_number,
                        'full_vat' => $country . $vat_number,
                        'consultation_number' => $consultation_id,
                        'request_date_utc' => $request_date,
                        'source' => 'ec.europa.eu VIES REST POST',
                        'error_category' => $is_valid ? 'valid' : 'invalid',
                        'error_code' => $is_valid ? 'VALID' : 'INVALID',
                        'error_detail' => $is_valid ? 'ALV-tunniste on voimassa.' : 'ALV-tunnistetta ei löydy VIES-rekisteristä.',
                        'http_status' => $code,
                        'raw_snippet' => $snippet,
                        'attempts' => $attempt,
                        'name' => $returned_name,
                        'address' => $returned_address,
                        'raw' => $data
                    ];
                }
            }

            // HTTP 5xx Server Error from ec.europa.eu
            if ($code >= 500) {
                $last_result = [
                    'status' => I4WARE_VIES_SERVICE_UNAVAILABLE,
                    'is_valid' => false,
                    'country_code' => $country,
                    'vat_number' => $vat_number,
                    'full_vat' => $country . $vat_number,
                    'consultation_number' => '',
                    'request_date_utc' => gmdate('Y-m-d\TH:i:s\Z'),
                    'source' => 'ec.europa.eu VIES REST POST',
                    'error_category' => 'vies_outage',
                    'error_code' => 'HTTP_' . $code,
                    'error_detail' => 'ec.europa.eu palautti palvelinvirheen HTTP ' . $code,
                    'http_status' => $code,
                    'raw_snippet' => $snippet,
                    'attempts' => $attempt
                ];
                if ($attempt < $max_attempts) {
                    usleep(500000);
                    continue;
                }
                return $last_result;
            }

            // HTTP 4xx Client Error or non-JSON response
            return [
                'status' => I4WARE_VIES_SERVICE_UNAVAILABLE,
                'is_valid' => false,
                'country_code' => $country,
                'vat_number' => $vat_number,
                'full_vat' => $country . $vat_number,
                'consultation_number' => '',
                'request_date_utc' => gmdate('Y-m-d\TH:i:s\Z'),
                'source' => 'ec.europa.eu VIES REST POST',
                'error_category' => 'api_request_error',
                'error_code' => 'HTTP_' . $code,
                'error_detail' => 'Odottamaton vastaus VIES-palvelimelta (HTTP ' . $code . '): ' . substr($body, 0, 100),
                'http_status' => $code,
                'raw_snippet' => $snippet,
                'attempts' => $attempt
            ];
        }

        return $last_result;
    }

    /**
     * Query Official VIES REST API via GET
     */
    private static function query_rest_get($country, $vat_number, $timeout)
    {
        $req_country = self::get_requester_country();
        $req_number = self::get_requester_number();
        $include_requester = self::is_requester_enabled() && !empty($req_country) && !empty($req_number);

        $url = self::OFFICIAL_REST_GET_BASE . rawurlencode($country) . '/vat/' . rawurlencode($vat_number);
        if ($include_requester) {
            $url .= '?requesterMemberStateCode=' . rawurlencode($req_country) . '&requesterNumber=' . rawurlencode($req_number);
        }

        $args = [
            'method' => 'GET',
            'timeout' => $timeout,
            'headers' => [
                'Accept' => 'application/json',
                'User-Agent' => 'i4ware-VAT-Validator/1.1 (+https://www.i4ware.fi)'
            ],
            'sslverify' => true
        ];

        $response = wp_remote_get($url, $args);

        if (is_wp_error($response)) {
            $err_code = $response->get_error_code();
            $err_msg = $response->get_error_message();

            $subtype = 'network_error';
            if (stripos($err_msg, 'timed out') !== false || preg_match('/\bcURL error 28\b/i', $err_msg)) {
                $subtype = 'timeout';
            } elseif (stripos($err_msg, 'SSL') !== false || stripos($err_msg, 'certificate') !== false || preg_match('/\bcURL error (?:35|51|58|59|60|77|80|82|83|90|91)\b/i', $err_msg)) {
                $subtype = 'tls_certificate_error';
            } elseif (stripos($err_msg, 'Could not resolve host') !== false || preg_match('/\bcURL error 6\b/i', $err_msg)) {
                $subtype = 'dns_failure';
            } elseif (stripos($err_msg, 'Failed to connect') !== false || preg_match('/\bcURL error 7\b/i', $err_msg)) {
                $subtype = 'connection_refused_or_firewall';
            }

            return [
                'status' => I4WARE_VIES_LOCAL_NETWORK_ERROR,
                'is_valid' => false,
                'country_code' => $country,
                'vat_number' => $vat_number,
                'full_vat' => $country . $vat_number,
                'consultation_number' => '',
                'request_date_utc' => gmdate('Y-m-d\TH:i:s\Z'),
                'source' => 'ec.europa.eu VIES REST GET',
                'error_category' => 'local_network_error',
                'error_code' => $err_code . ':' . $subtype,
                'error_detail' => $err_msg,
                'http_status' => 0,
                'raw_snippet' => '',
                'attempts' => 1
            ];
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        $snippet = substr($body, 0, 250);

        if ($code === 200 && is_array($data)) {
            // Check if GET returned userError
            if (!empty($data['userError'])) {
                $user_err = $data['userError'];
                $outage_errors = ['MS_UNAVAILABLE', 'TIMEOUT', 'SERVICE_UNAVAILABLE', 'MS_MAX_CONCURRENT_REQ'];
                $is_outage = in_array($user_err, $outage_errors, true);

                return [
                    'status' => $is_outage ? I4WARE_VIES_SERVICE_UNAVAILABLE : I4WARE_VIES_INVALID,
                    'is_valid' => false,
                    'country_code' => $country,
                    'vat_number' => $vat_number,
                    'full_vat' => $country . $vat_number,
                    'consultation_number' => '',
                    'request_date_utc' => gmdate('Y-m-d\TH:i:s\Z'),
                    'source' => 'ec.europa.eu VIES REST GET',
                    'error_category' => $is_outage ? 'vies_outage' : 'api_request_error',
                    'error_code' => $user_err,
                    'error_detail' => 'VIES GET palautti virheen: ' . $user_err,
                    'http_status' => $code,
                    'raw_snippet' => $snippet,
                    'attempts' => 1,
                    'raw' => $data
                ];
            }

            if (isset($data['isValid']) || isset($data['valid'])) {
                $is_valid = !empty($data['isValid']) || !empty($data['valid']);
                return [
                    'status' => $is_valid ? I4WARE_VIES_VALID : I4WARE_VIES_INVALID,
                    'is_valid' => $is_valid,
                    'country_code' => $country,
                    'vat_number' => $vat_number,
                    'full_vat' => $country . $vat_number,
                    'consultation_number' => isset($data['requestIdentifier']) ? $data['requestIdentifier'] : '',
                    'request_date_utc' => isset($data['requestDate']) ? $data['requestDate'] : gmdate('Y-m-d\TH:i:s\Z'),
                    'source' => 'ec.europa.eu VIES REST GET',
                    'error_category' => $is_valid ? 'valid' : 'invalid',
                    'error_code' => $is_valid ? 'VALID' : 'INVALID',
                    'error_detail' => $is_valid ? 'ALV-tunniste on voimassa.' : 'ALV-tunnistetta ei löydy VIES-rekisteristä.',
                    'http_status' => $code,
                    'raw_snippet' => $snippet,
                    'attempts' => 1,
                    'name' => isset($data['name']) && $data['name'] !== '---' ? trim($data['name']) : '',
                    'address' => isset($data['address']) && $data['address'] !== '---' ? trim($data['address']) : '',
                    'raw' => $data
                ];
            }
        }

        return [
            'status' => I4WARE_VIES_SERVICE_UNAVAILABLE,
            'is_valid' => false,
            'country_code' => $country,
            'vat_number' => $vat_number,
            'full_vat' => $country . $vat_number,
            'consultation_number' => '',
            'request_date_utc' => gmdate('Y-m-d\TH:i:s\Z'),
            'source' => 'ec.europa.eu VIES REST GET',
            'error_category' => ($code >= 500) ? 'vies_outage' : 'api_request_error',
            'error_code' => 'HTTP_' . $code,
            'error_detail' => 'VIES GET palautti HTTP ' . $code,
            'http_status' => $code,
            'raw_snippet' => $snippet,
            'attempts' => 1
        ];
    }

    /**
     * Query Official VIES SOAP Web Service (Fallback)
     */
    private static function query_soap($country, $vat_number, $timeout)
    {
        $req_country = self::get_requester_country();
        $req_number = self::get_requester_number();

        try {
            $client = new SoapClient(self::OFFICIAL_SOAP_WSDL, [
                'connection_timeout' => $timeout,
                'exceptions' => true,
                'trace' => false
            ]);

            $params = [
                'countryCode' => $country,
                'vatNumber' => $vat_number,
                'requesterCountryCode' => $req_country,
                'requesterVatNumber' => $req_number
            ];

            $res = $client->checkVatApprox($params);

            if ($res) {
                $is_valid = !empty($res->valid);
                return [
                    'status' => $is_valid ? I4WARE_VIES_VALID : I4WARE_VIES_INVALID,
                    'is_valid' => $is_valid,
                    'country_code' => $country,
                    'vat_number' => $vat_number,
                    'full_vat' => $country . $vat_number,
                    'consultation_number' => isset($res->requestIdentifier) ? $res->requestIdentifier : '',
                    'request_date_utc' => isset($res->requestDate) ? $res->requestDate : gmdate('Y-m-d\TH:i:s\Z'),
                    'source' => 'ec.europa.eu VIES SOAP',
                    'error_category' => $is_valid ? 'valid' : 'invalid',
                    'error_code' => $is_valid ? 'VALID' : 'INVALID',
                    'error_detail' => $is_valid ? 'ALV-tunniste on voimassa.' : 'ALV-tunnistetta ei löydy VIES-rekisteristä.',
                    'http_status' => 200,
                    'raw_snippet' => '',
                    'attempts' => 1,
                    'name' => isset($res->traderName) && $res->traderName !== '---' ? trim($res->traderName) : '',
                    'address' => isset($res->traderAddress) && $res->traderAddress !== '---' ? trim($res->traderAddress) : '',
                    'raw' => (array) $res
                ];
            }
        } catch (Exception $e) {
            $fault_msg = $e->getMessage();
            $is_outage = (stripos($fault_msg, 'MS_UNAVAILABLE') !== false || stripos($fault_msg, 'TIMEOUT') !== false || stripos($fault_msg, 'SERVER_BUSY') !== false);

            return [
                'status' => I4WARE_VIES_SERVICE_UNAVAILABLE,
                'is_valid' => false,
                'country_code' => $country,
                'vat_number' => $vat_number,
                'full_vat' => $country . $vat_number,
                'consultation_number' => '',
                'request_date_utc' => gmdate('Y-m-d\TH:i:s\Z'),
                'source' => 'ec.europa.eu VIES SOAP',
                'error_category' => $is_outage ? 'vies_outage' : 'local_network_error',
                'error_code' => 'SOAP_FAULT',
                'error_detail' => $fault_msg,
                'http_status' => 0,
                'raw_snippet' => '',
                'attempts' => 1
            ];
        }

        return null;
    }

    /**
     * Analyze correspondence between customer provided company name and official VIES record.
     *
     * IMPORTANT:
     * - In some member states (e.g. Germany, Spain), name and address are withheld ('---') due to privacy law.
     *   Missing name or address in VIES is NOT an error.
     * - However, if a name is returned and diverges completely from the user's input, flag as 'mismatch'.
     */
    public static function analyze_name_address_match($input_name, $input_address, $vies_name, $vies_address, $trader_name_match = null)
    {
        $input_name = trim((string) $input_name);
        $vies_name = trim((string) $vies_name);

        // If VIES explicitly returned traderNameMatch
        if ($trader_name_match === 'VALID' || $trader_name_match === 1 || $trader_name_match === '1') {
            return [
                'match_status' => 'matched',
                'details' => 'VIES returned official traderNameMatch: VALID'
            ];
        }

        // If member state suppressed name or name is empty
        if (empty($vies_name) || $vies_name === '---') {
            return [
                'match_status' => 'not_provided_by_member_state',
                'details' => 'Jäsenvaltio ei palauta julkisesti yrityksen nimeä (tavanomaista esim. Saksassa ja Espanjassa).'
            ];
        }

        if (empty($input_name)) {
            return [
                'match_status' => 'input_name_empty',
                'details' => 'Asiakas ei antanut yritysnimeä vertailtavaksi.'
            ];
        }

        // Normalize both names for robust comparison
        $clean_input = self::normalize_company_string($input_name);
        $clean_vies = self::normalize_company_string($vies_name);

        // Exact match or substring inclusion
        if ($clean_input === $clean_vies || stripos($clean_vies, $clean_input) !== false || stripos($clean_input, $clean_vies) !== false) {
            return [
                'match_status' => 'matched',
                'details' => 'Yrityksen nimi vastaa rekisteröityä nimeä (' . $vies_name . ').'
            ];
        }

        // Tokenized word overlap
        $input_tokens = array_filter(explode(' ', $clean_input));
        $vies_tokens = array_filter(explode(' ', $clean_vies));
        $intersection = array_intersect($input_tokens, $vies_tokens);

        if (!empty($input_tokens) && count($intersection) / count($input_tokens) >= 0.5) {
            return [
                'match_status' => 'matched',
                'details' => 'Yrityksen nimessä vahva vastaavuus (' . $vies_name . ').'
            ];
        }

        // Levenshtein similarity
        similar_text($clean_input, $clean_vies, $percent);
        if ($percent >= 65.0) {
            return [
                'match_status' => 'matched',
                'details' => 'Yrityksen nimi vastaa rekisteröityä nimeä (' . round($percent, 1) . ' % vastaavuus).'
            ];
        }

        // Names are clearly divergent
        return [
            'match_status' => 'mismatch',
            'details' => sprintf(
                'Ilmoitettu yritysnimi "%s" poikkeaa VIES-rekisterin nimestä "%s".',
                $input_name,
                $vies_name
            )
        ];
    }

    /**
     * Clean and normalize company name for comparison
     */
    private static function normalize_company_string($str)
    {
        $str = mb_strtolower($str, 'UTF-8');
        // Remove common legal form suffixes
        $legal_suffixes = [
            ' oy', ' oyj', ' ab', ' ltd', ' limited', ' llc', ' gmbh', ' ag', ' sarl', ' s.a.r.l.',
            ' sl', ' s.l.', ' sa', ' s.a.', ' bv', ' b.v.', ' inc', ' incorporated', ' corp', ' corporation'
        ];
        foreach ($legal_suffixes as $suf) {
            $str = preg_replace('/' . preg_quote($suf, '/') . '$/i', '', $str);
        }
        // Remove punctuation and multiple spaces
        $str = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $str);
        $str = preg_replace('/\s+/', ' ', $str);
        return trim($str);
    }

    /**
     * Log audit record to database
     */
    public static function log_audit_record($data)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'i4ware_vat_audit_logs';

        // Check if table exists (handled during installation / setup)
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            return false;
        }

        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '';
        // Anonymize last octet of IP for GDPR privacy compliance
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $ip = preg_replace('/\.\d+$/', '.0', $ip);
        }

        $insert_data = [
            'vat_number' => isset($data['vat_number']) ? sanitize_text_field($data['vat_number']) : '',
            'vat_country' => isset($data['country_code']) ? sanitize_text_field($data['country_code']) : '',
            'status' => isset($data['status']) ? sanitize_text_field($data['status']) : '',
            'consultation_number' => isset($data['consultation_number']) ? sanitize_text_field($data['consultation_number']) : '',
            'source' => isset($data['source']) ? sanitize_text_field($data['source']) : '',
            'name_match_status' => isset($data['name_match_status']) ? sanitize_text_field($data['name_match_status']) : '',
            'request_utc' => isset($data['request_date_utc']) ? gmdate('Y-m-d H:i:s', strtotime($data['request_date_utc'])) : gmdate('Y-m-d H:i:s'),
            'response_summary' => wp_json_encode([
                'vies_name' => isset($data['name']) ? $data['name'] : '',
                'vies_address' => isset($data['address']) ? $data['address'] : '',
                'message' => isset($data['message']) ? $data['message'] : '',
                'match_details' => isset($data['name_match_details']) ? $data['name_match_details'] : '',
                'error_category' => isset($data['error_category']) ? $data['error_category'] : '',
                'error_code' => isset($data['error_code']) ? $data['error_code'] : '',
                'error_detail' => isset($data['error_detail']) ? $data['error_detail'] : '',
                'http_status' => isset($data['http_status']) ? $data['http_status'] : null,
                'raw_snippet' => isset($data['raw_snippet']) ? $data['raw_snippet'] : '',
                'attempts' => isset($data['attempts']) ? $data['attempts'] : 1
            ]),
            'ip_address' => $ip,
            'created_at' => gmdate('Y-m-d H:i:s')
        ];

        $wpdb->insert($table_name, $insert_data);
        return $wpdb->insert_id;
    }
}
