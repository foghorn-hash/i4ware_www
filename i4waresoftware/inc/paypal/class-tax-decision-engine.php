<?php
/**
 * i4ware Tax Determination Decision Module (Verokäsittelyn päätösmoduuli)
 *
 * Implements authoritative tax classification rules based on:
 * - Nature of payment (vastikkeeton tuki vs. sponsorointi vs. ohjelmistopalvelu)
 * - Customer type (business vs. consumer)
 * - Country of establishment
 * - Receiving fixed establishment (kiinteä toimipaikka)
 * - VAT registration country & VIES verification result
 * - Proof of business status
 *
 * Compliant with Finnish VAT Act (AVL 1 §, 65 §, 66 §, 68-69 §), EU VAT Directive 2006/112/EC (art. 44),
 * and Finnish Money Collection Act (Rahankeräyslaki 863/2019).
 */

if (!defined('ABSPATH')) {
    exit;
}

class I4ware_Tax_Decision_Engine
{
    /**
     * Evaluate tax classification, applicable tax rate, and pricing details.
     *
     * @param array $input Order and customer attributes
     * @return array Tax decision, amounts, status, and legal notes
     */
    public static function evaluate(array $input)
    {
        $payment_type = isset($input['payment_type']) ? sanitize_text_field($input['payment_type']) : I4WARE_PAYMENT_ADVERTISING_SERVICE;
        $customer_type = isset($input['customer_type']) ? sanitize_text_field($input['customer_type']) : I4WARE_CUSTOMER_INDIVIDUAL;
        $establishment_country = isset($input['establishment_country']) ? strtoupper(trim($input['establishment_country'])) : '';
        $fixed_establishment_country = isset($input['fixed_establishment_country']) ? strtoupper(trim($input['fixed_establishment_country'])) : '';
        $billing_country = isset($input['billing_country']) ? strtoupper(trim($input['billing_country'])) : '';
        $vat_registered = isset($input['vat_registered']) ? sanitize_text_field($input['vat_registered']) : 'no'; // 'yes', 'no', 'unknown'
        $vat_country = isset($input['vat_country']) ? strtoupper(trim($input['vat_country'])) : '';
        $vat_number = isset($input['vat_number']) ? sanitize_text_field($input['vat_number']) : '';
        $vies_result = isset($input['vies_result']) && is_array($input['vies_result']) ? $input['vies_result'] : null;
        $business_id = isset($input['business_id']) ? sanitize_text_field($input['business_id']) : '';
        $business_proof_notes = isset($input['business_proof_notes']) ? sanitize_text_field($input['business_proof_notes']) : '';
        $base_amount = isset($input['base_amount']) ? (float) $input['base_amount'] : 0.0;
        $price_includes_tax = !empty($input['price_includes_tax']);
        $currency = isset($input['currency']) ? strtoupper(trim($input['currency'])) : 'EUR';
        $lang = isset($input['lang']) ? sanitize_text_field($input['lang']) : 'fi';

        if (empty($establishment_country)) {
            $establishment_country = !empty($billing_country) ? $billing_country : 'FI';
        }

        // -------------------------------------------------------------
        // 1. PAYMENT TYPE CHECK: GRATUITOUS SUPPORT (VASTIKEETON TUKI)
        // -------------------------------------------------------------
        if ($payment_type === I4WARE_PAYMENT_GRATUITOUS_SUPPORT) {
            // Check admin setting: whether gratuitous support subscriptions are allowed
            $allow_gratuitous = get_option('i4ware_allow_gratuitous_support', false);

            if (!$allow_gratuitous) {
                return [
                    'status' => 'blocked',
                    'tax_classification' => I4WARE_TAX_OUTSIDE_SCOPE_GRATUITOUS,
                    'is_taxable' => false,
                    'vat_rate' => null,
                    'net_amount' => $base_amount,
                    'vat_amount' => 0.0,
                    'gross_amount' => $base_amount,
                    'currency' => $currency,
                    'tax_note' => self::get_tax_note(I4WARE_TAX_OUTSIDE_SCOPE_GRATUITOUS, $lang),
                    'user_message' => self::get_translation('gratuitous_disabled_notice', $lang),
                    'can_proceed_to_paypal' => false,
                    'requires_manual_review' => false,
                    'reason' => 'Vastikkeettoman tuen uudet tilaukset ovat toistaiseksi poissa käytöstä rahankeräyslain edellytysten selvittämisen ajaksi.'
                ];
            }

            // If allowed by admin, gratuitous support is OUTSIDE the scope of VAT (NOT 'ALV 0%')
            return [
                'status' => 'approved',
                'tax_classification' => I4WARE_TAX_OUTSIDE_SCOPE_GRATUITOUS,
                'is_taxable' => false,
                'vat_rate' => null, // Explicitly null, not 0%
                'net_amount' => $base_amount,
                'vat_amount' => 0.0,
                'gross_amount' => $base_amount,
                'currency' => $currency,
                'tax_note' => self::get_tax_note(I4WARE_TAX_OUTSIDE_SCOPE_GRATUITOUS, $lang),
                'user_message' => self::get_tax_note(I4WARE_TAX_OUTSIDE_SCOPE_GRATUITOUS, $lang),
                'can_proceed_to_paypal' => true,
                'requires_manual_review' => false,
                'reason' => 'Vastikkeeton tuki on arvonlisäveron soveltamisalan ulkopuolinen erä.'
            ];
        }

        // -------------------------------------------------------------
        // 2. DOMESTIC FINLAND SALE (SUOMEN VEROLLINEN MYYNTI)
        // -------------------------------------------------------------
        // If company is established in Finland, OR receiving fixed establishment is in Finland
        $effective_country = !empty($fixed_establishment_country) ? $fixed_establishment_country : $establishment_country;

        if ($effective_country === 'FI') {
            $vat_rate = (float) get_option('i4ware_standard_vat_rate', I4WARE_STANDARD_FINNISH_VAT_RATE);
            if ($price_includes_tax) {
                $net_amount = round($base_amount / (1 + ($vat_rate / 100)), 2);
                $vat_amount = round($base_amount - $net_amount, 2);
                $gross_amount = round($base_amount, 2);
            } else {
                $net_amount = $base_amount;
                $vat_amount = round($base_amount * ($vat_rate / 100), 2);
                $gross_amount = round($base_amount + $vat_amount, 2);
            }

            return [
                'status' => 'approved',
                'tax_classification' => I4WARE_TAX_FI_DOMESTIC_TAXABLE,
                'is_taxable' => true,
                'vat_rate' => $vat_rate,
                'net_amount' => $net_amount,
                'vat_amount' => $vat_amount,
                'gross_amount' => $gross_amount,
                'currency' => $currency,
                'tax_note' => self::get_tax_note(I4WARE_TAX_FI_DOMESTIC_TAXABLE, $lang, $vat_rate),
                'user_message' => self::get_tax_note(I4WARE_TAX_FI_DOMESTIC_TAXABLE, $lang, $vat_rate),
                'can_proceed_to_paypal' => true,
                'requires_manual_review' => false,
                'reason' => 'Kotimaan mainos- ja palvelumyynti Suomeen (AVL 1 §, ALV 25,5 %). Kotimaan myynnissä ostajan suomalainen ALV-tunniste ei oikeuta verottomuuteen eikä käännettyyn verovelvollisuuteen.'
            ];
        }

        // -------------------------------------------------------------
        // 3. PRIVATE INDIVIDUAL (YKSITYISHENKILÖ / KULUTTAJA)
        // -------------------------------------------------------------
        if ($customer_type === I4WARE_CUSTOMER_INDIVIDUAL) {
            // Consumer in another EU country
            if (i4ware_is_eu_vat_member_state($establishment_country)) {
                $vat_rate = (float) get_option('i4ware_standard_vat_rate', I4WARE_STANDARD_FINNISH_VAT_RATE);
                if ($price_includes_tax) {
                    $net_amount = round($base_amount / (1 + ($vat_rate / 100)), 2);
                    $vat_amount = round($base_amount - $net_amount, 2);
                    $gross_amount = round($base_amount, 2);
                } else {
                    $net_amount = $base_amount;
                    $vat_amount = round($base_amount * ($vat_rate / 100), 2);
                    $gross_amount = round($base_amount + $vat_amount, 2);
                }

                return [
                    'status' => 'approved',
                    'tax_classification' => I4WARE_TAX_EU_CONSUMER_TAXABLE,
                    'is_taxable' => true,
                    'vat_rate' => $vat_rate,
                    'net_amount' => $net_amount,
                    'vat_amount' => $vat_amount,
                    'gross_amount' => $gross_amount,
                    'currency' => $currency,
                    'tax_note' => self::get_tax_note(I4WARE_TAX_EU_CONSUMER_TAXABLE, $lang, $vat_rate),
                    'user_message' => self::get_tax_note(I4WARE_TAX_EU_CONSUMER_TAXABLE, $lang, $vat_rate),
                    'can_proceed_to_paypal' => true,
                    'requires_manual_review' => false,
                    'reason' => 'EU-kuluttajamyynti (AVL 66 § / 69 §, Suomen ALV 25,5 %).'
                ];
            }

            // Consumer outside EU (e.g. US, UK, Norway, Switzerland)
            return [
                'status' => 'approved',
                'tax_classification' => I4WARE_TAX_NON_EU_CONSUMER,
                'is_taxable' => false,
                'vat_rate' => 0.0,
                'net_amount' => $base_amount,
                'vat_amount' => 0.0,
                'gross_amount' => $base_amount,
                'currency' => $currency,
                'tax_note' => self::get_tax_note(I4WARE_TAX_NON_EU_CONSUMER, $lang),
                'user_message' => self::get_tax_note(I4WARE_TAX_NON_EU_CONSUMER, $lang),
                'can_proceed_to_paypal' => true,
                'requires_manual_review' => false,
                'reason' => 'Kuluttajamyynti EU:n ulkopuolelle (AVL 68-69 §).'
            ];
        }

        // -------------------------------------------------------------
        // 4. BUSINESS CUSTOMERS (YRITYSASIAKKAAT)
        // -------------------------------------------------------------

        $is_establishment_in_eu = i4ware_is_eu_vat_member_state($establishment_country);
        $is_vat_country_in_eu = !empty($vat_country) && i4ware_is_eu_vat_member_state($vat_country);

        // -------------------------------------------------------------
        // SPECIAL CASE 4A: NON-EU ESTABLISHED BUSINESS WITH EU VAT ID
        // (E.g. Atlassian-type international corporate structure)
        // -------------------------------------------------------------
        // "EU:n ulkopuolelle sijoittautuneella yrityksellä voi olla EU-maan ALV-tunniste...
        // Selvitä aina kyseinen juridinen ostaja ja mahdollinen palvelun vastaanottava kiinteä toimipaikka.
        // Älä:
        // - Päättele sijoittautumismaata ALV-tunnisteen etuliitteestä.
        // - Päättele EU:ssa olevaa kiinteää toimipaikkaa pelkästä ALV-rekisteröinnistä.
        // - Päättele käännettyä verovelvollisuutta vain validista ALV-tunnisteesta.
        // - Hylkää yritystä automaattisesti siksi, että sen sijoittautumismaa ja ALV-rekisteröintimaa poikkeavat toisistaan.
        // Ohjaa epäselvä yhdistelmä ylläpitäjän tarkistettavaksi ennen maksua."
        if (!$is_establishment_in_eu && $is_vat_country_in_eu) {
            // Check if the buyer explicitly clarified a verified fixed establishment in that EU country
            $has_verified_eu_branch = !empty($fixed_establishment_country) && ($fixed_establishment_country === $vat_country);

            if (!$has_verified_eu_branch) {
                return [
                    'status' => 'requires_manual_review',
                    'tax_classification' => I4WARE_TAX_MANUAL_REVIEW_REQUIRED,
                    'is_taxable' => false,
                    'vat_rate' => null,
                    'net_amount' => $base_amount,
                    'vat_amount' => 0.0,
                    'gross_amount' => $base_amount,
                    'currency' => $currency,
                    'tax_note' => self::get_tax_note(I4WARE_TAX_MANUAL_REVIEW_REQUIRED, $lang),
                    'user_message' => self::get_translation('atlassian_case_clarification', $lang),
                    'can_proceed_to_paypal' => false,
                    'requires_manual_review' => true,
                    'reason' => sprintf(
                        'Yrityksen sijoittautumismaa (%s) on EU:n ulkopuolella ja ALV-tunniste on myönnetty EU-maassa (%s). Yhteys kiinteään toimipaikkaan vaatii ylläpidon manuaalisen selvityksen.',
                        $establishment_country,
                        $vat_country
                    )
                ];
            }
        }

        // -------------------------------------------------------------
        // 4B. EU B2B SERVICE SALE (EU-YRITYSMYYNTI / KÄÄNNETTY VEROVELVOLLISUUS)
        // -------------------------------------------------------------
        if ($is_establishment_in_eu && $establishment_country !== 'FI') {
            // If business says it is NOT VAT registered
            if ($vat_registered === 'no' || empty($vat_number)) {
                // Without valid VAT ID or proof of taxable person status, cannot apply reverse charge
                // Charge standard Finnish VAT or request manual verification
                $vat_rate = (float) get_option('i4ware_standard_vat_rate', I4WARE_STANDARD_FINNISH_VAT_RATE);
                if ($price_includes_tax) {
                    $net_amount = round($base_amount / (1 + ($vat_rate / 100)), 2);
                    $vat_amount = round($base_amount - $net_amount, 2);
                    $gross_amount = round($base_amount, 2);
                } else {
                    $net_amount = $base_amount;
                    $vat_amount = round($base_amount * ($vat_rate / 100), 2);
                    $gross_amount = round($base_amount + $vat_amount, 2);
                }

                return [
                    'status' => 'approved',
                    'tax_classification' => I4WARE_TAX_EU_CONSUMER_TAXABLE,
                    'is_taxable' => true,
                    'vat_rate' => $vat_rate,
                    'net_amount' => $net_amount,
                    'vat_amount' => $vat_amount,
                    'gross_amount' => $gross_amount,
                    'currency' => $currency,
                    'tax_note' => self::get_tax_note(I4WARE_TAX_EU_CONSUMER_TAXABLE, $lang, $vat_rate),
                    'user_message' => self::get_translation('eu_business_no_vat_msg', $lang),
                    'can_proceed_to_paypal' => true,
                    'requires_manual_review' => false,
                    'reason' => 'EU-yritys ilman validia ALV-tunnistetta käsitellään ilman käännettyä verovelvollisuutta (Suomen ALV 25,5 % peritään).'
                ];
            }

            // Customer provided an EU VAT number -> inspect VIES result
            if (!$vies_result) {
                return [
                    'status' => 'requires_manual_review',
                    'tax_classification' => I4WARE_TAX_MANUAL_REVIEW_REQUIRED,
                    'is_taxable' => false,
                    'vat_rate' => null,
                    'net_amount' => $base_amount,
                    'vat_amount' => 0.0,
                    'gross_amount' => $base_amount,
                    'currency' => $currency,
                    'tax_note' => self::get_tax_note(I4WARE_TAX_MANUAL_REVIEW_REQUIRED, $lang),
                    'user_message' => self::get_translation('vies_not_checked_notice', $lang),
                    'can_proceed_to_paypal' => false,
                    'requires_manual_review' => true,
                    'reason' => 'VIES-tarkistusta ei ole vielä suoritettu.'
                ];
            }

            // VIES status analysis
            if ($vies_result['status'] === I4WARE_VIES_INVALID) {
                return [
                    'status' => 'invalid',
                    'tax_classification' => I4WARE_TAX_MANUAL_REVIEW_REQUIRED,
                    'is_taxable' => false,
                    'vat_rate' => null,
                    'net_amount' => $base_amount,
                    'vat_amount' => 0.0,
                    'gross_amount' => $base_amount,
                    'currency' => $currency,
                    'tax_note' => self::get_tax_note(I4WARE_TAX_MANUAL_REVIEW_REQUIRED, $lang),
                    'user_message' => self::get_translation('vies_invalid_msg', $lang),
                    'can_proceed_to_paypal' => false,
                    'requires_manual_review' => true,
                    'reason' => 'VIES-tarkistus ilmoitti, että annettu ALV-tunniste ei ole voimassa.'
                ];
            }

            if ($vies_result['status'] === I4WARE_VIES_SERVICE_UNAVAILABLE || $vies_result['status'] === I4WARE_VIES_LOCAL_NETWORK_ERROR) {
                $is_net_err = ($vies_result['status'] === I4WARE_VIES_LOCAL_NETWORK_ERROR);
                return [
                    'status' => 'requires_manual_review',
                    'tax_classification' => I4WARE_TAX_MANUAL_REVIEW_REQUIRED,
                    'is_taxable' => false,
                    'vat_rate' => null,
                    'net_amount' => $base_amount,
                    'vat_amount' => 0.0,
                    'gross_amount' => $base_amount,
                    'currency' => $currency,
                    'tax_note' => self::get_tax_note(I4WARE_TAX_MANUAL_REVIEW_REQUIRED, $lang),
                    'user_message' => self::get_translation('vies_unavailable_msg', $lang),
                    'can_proceed_to_paypal' => false,
                    'requires_manual_review' => true,
                    'reason' => $is_net_err
                        ? 'Paikallinen yhteys- tai aikakatkaisuvirhe VIES-tarkistuksessa. Tilaus siirretään ylläpidon manuaaliseen tarkistukseen.'
                        : 'VIES-tarkistuspalvelu ei vastannut tai jäsenvaltion rekisteri on tilapäisesti poissa käytöstä.'
                ];
            }

            if ($vies_result['status'] === I4WARE_VIES_REQUIRES_CLARIFICATION) {
                return [
                    'status' => 'requires_manual_review',
                    'tax_classification' => I4WARE_TAX_MANUAL_REVIEW_REQUIRED,
                    'is_taxable' => false,
                    'vat_rate' => null,
                    'net_amount' => $base_amount,
                    'vat_amount' => 0.0,
                    'gross_amount' => $base_amount,
                    'currency' => $currency,
                    'tax_note' => self::get_tax_note(I4WARE_TAX_MANUAL_REVIEW_REQUIRED, $lang),
                    'user_message' => self::get_translation('vies_clarification_msg', $lang),
                    'can_proceed_to_paypal' => false,
                    'requires_manual_review' => true,
                    'reason' => 'VIES-tarkistus edellyttää lisäselvitystä (esim. yritysnimen vastaavuudessa huomattava poikkeama).'
                ];
            }

            // VIES returned VALID!
            if ($vies_result['status'] === I4WARE_VIES_VALID && !empty($vies_result['is_valid'])) {
                return [
                    'status' => 'approved',
                    'tax_classification' => I4WARE_TAX_EU_REVERSE_CHARGE,
                    'is_taxable' => false,
                    'vat_rate' => 0.0,
                    'net_amount' => $base_amount,
                    'vat_amount' => 0.0,
                    'gross_amount' => $base_amount,
                    'currency' => $currency,
                    'tax_note' => self::get_tax_note(I4WARE_TAX_EU_REVERSE_CHARGE, $lang),
                    'user_message' => self::get_tax_note(I4WARE_TAX_EU_REVERSE_CHARGE, $lang),
                    'can_proceed_to_paypal' => true,
                    'requires_manual_review' => false,
                    'vies_consultation_number' => isset($vies_result['consultation_number']) ? $vies_result['consultation_number'] : '',
                    'reason' => 'EU B2B -yleissääntö: käännetty verovelvollisuus (AVL 65 § / Direktiivi 2006/112/EY art. 44).'
                ];
            }
        }

        // -------------------------------------------------------------
        // 4C. NON-EU B2B SERVICE SALE (KANSAINVÄLINEN MYYNTI EU:N ULKOPUOLELLE)
        // -------------------------------------------------------------
        if (!$is_establishment_in_eu) {
            // For companies established outside the EU (e.g. US, CA, UK, JP, CH, NO):
            // Business status is verified via business ID, national trade register number, or manual check.
            $has_business_proof = !empty($business_id) || !empty($business_proof_notes);

            if ($has_business_proof) {
                return [
                    'status' => 'approved',
                    'tax_classification' => I4WARE_TAX_NON_EU_EXPORT,
                    'is_taxable' => false,
                    'vat_rate' => 0.0,
                    'net_amount' => $base_amount,
                    'vat_amount' => 0.0,
                    'gross_amount' => $base_amount,
                    'currency' => $currency,
                    'tax_note' => self::get_tax_note(I4WARE_TAX_NON_EU_EXPORT, $lang),
                    'user_message' => self::get_tax_note(I4WARE_TAX_NON_EU_EXPORT, $lang),
                    'can_proceed_to_paypal' => true,
                    'requires_manual_review' => false,
                    'reason' => 'Kansainvälinen palvelumyynti EU:n ulkopuolelle sijoittautuneelle elinkeinonharjoittajalle (AVL 65 §).'
                ];
            } else {
                // If business ID / registration info is missing from non-EU company, flag for review
                return [
                    'status' => 'requires_manual_review',
                    'tax_classification' => I4WARE_TAX_MANUAL_REVIEW_REQUIRED,
                    'is_taxable' => false,
                    'vat_rate' => null,
                    'net_amount' => $base_amount,
                    'vat_amount' => 0.0,
                    'gross_amount' => $base_amount,
                    'currency' => $currency,
                    'tax_note' => self::get_tax_note(I4WARE_TAX_MANUAL_REVIEW_REQUIRED, $lang),
                    'user_message' => self::get_translation('non_eu_business_id_required', $lang),
                    'can_proceed_to_paypal' => false,
                    'requires_manual_review' => true,
                    'reason' => 'EU:n ulkopuolisen yrityksen elinkeinonharjoittaja-asema vaatii rekisterinumeron tai ylläpitäjän tarkistuksen.'
                ];
            }
        }

        // Fallback default: requires manual review
        return [
            'status' => 'requires_manual_review',
            'tax_classification' => I4WARE_TAX_MANUAL_REVIEW_REQUIRED,
            'is_taxable' => false,
            'vat_rate' => null,
            'net_amount' => $base_amount,
            'vat_amount' => 0.0,
            'gross_amount' => $base_amount,
            'currency' => $currency,
            'tax_note' => self::get_tax_note(I4WARE_TAX_MANUAL_REVIEW_REQUIRED, $lang),
            'user_message' => self::get_translation('vies_clarification_msg', $lang),
            'can_proceed_to_paypal' => false,
            'requires_manual_review' => true,
            'reason' => 'Verokäsittely edellyttää manuaalista selvitystä.'
        ];
    }

    /**
     * Get statutory tax note text for summary, invoice, and receipts.
     */
    public static function get_tax_note($tax_classification, $lang = 'fi', $vat_rate = null)
    {
        $rate_str = $vat_rate !== null ? str_replace('.', ',', (string) $vat_rate) . ' %' : '25,5 %';

        $notes = [
            I4WARE_TAX_FI_DOMESTIC_TAXABLE => [
                'fi' => sprintf('Suomen arvonlisävero %s (AVL 1 §)', $rate_str),
                'en' => sprintf('Finnish VAT %s (AVL § 1)', $rate_str),
                'ar' => sprintf('ضريبة القيمة المضافة الفنلندية %s', $rate_str),
            ],
            I4WARE_TAX_EU_REVERSE_CHARGE => [
                'fi' => 'Käännetty verovelvollisuus – ALV 0 % (AVL 65 § / EU ALV-direktiivi 2006/112/EY art. 44). Ostaja verovelvollinen kohdemaassa.',
                'en' => 'Reverse charge – VAT 0% (Finnish VAT Act § 65 / EU VAT Directive 2006/112/EC art. 44). Purchaser liable for VAT.',
                'ar' => 'التحاسب العكسي - ضريبة القيمة المضافة 0٪ (المشتري مسؤول عن سداد الضريبة في بلد المقصد بموجب المادة 44).',
            ],
            I4WARE_TAX_EU_CONSUMER_TAXABLE => [
                'fi' => sprintf('EU-kuluttajamyynti – Suomen ALV %s (AVL 66 §)', $rate_str),
                'en' => sprintf('EU Consumer Supply – Finnish VAT %s (AVL § 66)', $rate_str),
                'ar' => sprintf('توريد لمستهلك في الاتحاد الأوروبي - ضريبة القيمة المضافة %s', $rate_str),
            ],
            I4WARE_TAX_NON_EU_EXPORT => [
                'fi' => 'Veroton kansainvälinen palvelumyynti EU:n ulkopuolelle (AVL 65 §).',
                'en' => 'VAT-exempt international service supply outside the EU (AVL § 65).',
                'ar' => 'تصدير خدمات معفى من الضريبة خارج الاتحاد الأوروبي.',
            ],
            I4WARE_TAX_NON_EU_CONSUMER => [
                'fi' => 'Myynti EU:n ulkopuolelle – Veroton (AVL 68-69 §).',
                'en' => 'Supply outside EU – Outside Finnish VAT scope (AVL § 68-69).',
                'ar' => 'توريد خارج الاتحاد الأوروبي - خارج نطاق ضريبة القيمة المضافة الفنلندية.',
            ],
            I4WARE_TAX_OUTSIDE_SCOPE_GRATUITOUS => [
                'fi' => 'Vastikkeeton erä, arvonlisäveron soveltamisalan ulkopuolella (ei ALV 0 % -myyntiä).',
                'en' => 'Gratuitous contribution outside the scope of VAT (not a 0% VAT supply).',
                'ar' => 'بند تطوعي بدون مقابل خارج نطاق ضريبة القيمة المضافة.',
            ],
            I4WARE_TAX_MANUAL_REVIEW_REQUIRED => [
                'fi' => 'Yritystiedot ja verokäsittely edellyttävät lisätarkistusta ennen maksun suorittamista.',
                'en' => 'Company details and tax treatment require manual verification before payment.',
                'ar' => 'تتطلب بيانات الشركة والمعالجة الضريبية التحقق اليدوي قبل إتمام الدفع.',
            ]
        ];

        if (isset($notes[$tax_classification][$lang])) {
            return $notes[$tax_classification][$lang];
        }
        if (isset($notes[$tax_classification]['en'])) {
            return $notes[$tax_classification]['en'];
        }
        return '';
    }

    /**
     * Helper translation retrieval
     */
    private static function get_translation($key, $lang)
    {
        $translations = [
            'gratuitous_disabled_notice' => [
                'fi' => 'Vastikkeettoman tuen uudet kuukausitilaukset ovat toistaiseksi poissa käytöstä rahankeräyslain edellytysten selvittämisen ajaksi.',
                'en' => 'New recurring subscriptions for voluntary gratuitous support are currently paused pending regulatory review under the Money Collection Act.',
                'ar' => 'الاشتراكات الشهرية الجديدة للدعم التطوعي متوقفة مؤقتاً لحين استكمال المراجعة التنظيمية.',
            ],
            'atlassian_case_clarification' => [
                'fi' => 'Yrityksen sijoittautumismaa on EU:n ulkopuolella ja ALV-tunniste on myönnetty EU-maassa. Tapaus edellyttää ylläpidon manuaalista selvitystä palvelua vastaanottavasta kiinteästä toimipaikasta ennen maksua.',
                'en' => 'Company is established outside the EU but holds an EU VAT number. This requires administrative verification of the receiving fixed establishment before checkout.',
                'ar' => 'الشركة مسجلة خارج الاتحاد الأوروبي ولكنها تحمل رقماً ضريبياً أوروبياً. يتطلب هذا الأمر مراجعة إدارية قبل الدفع.',
            ],
            'vies_invalid_msg' => [
                'fi' => 'ALV-tunnistetta ei voitu vahvistaa EU:n VIES-järjestelmästä. Tarkista annettu ALV-numero ja maa.',
                'en' => 'The VAT ID could not be validated in the EU VIES system. Please verify the number and country.',
                'ar' => 'تعذر التحقق من الرقم الضريبي في نظام VIES الأوروبي. يرجى مراجعة الرقم والدولة.',
            ],
            'vies_unavailable_msg' => [
                'fi' => 'EU:n VIES-tarkistuspalvelu ei ole juuri nyt käytettävissä. Yritä hetken kuluttua uudelleen tai ota yhteyttä ylläpitoon.',
                'en' => 'The EU VIES verification service is temporarily unavailable. Please retry shortly or contact support.',
                'ar' => 'خدمة التحقق VIES غير متاحة حالياً. يرجى المحاولة بعد قليل.',
            ],
            'vies_clarification_msg' => [
                'fi' => 'Yritystiedot edellyttävät lisätarkistusta (esim. ilmoitettu nimi ei vastaa ALV-rekisterin virallisia tietoja).',
                'en' => 'Company details require additional verification (e.g. name diverges from the official VAT registry).',
                'ar' => 'تتطلب بيانات الشركة مراجعة إضافية (الاسم لا يتطابق تماماً مع السجل الضريبي الرسمي).',
            ],
            'vies_not_checked_notice' => [
                'fi' => 'ALV-tunnisteen tarkistusta ei ole vielä suoritettu.',
                'en' => 'VAT validation has not yet been performed.',
                'ar' => 'لم يتم إجراء التحقق الضريبي بعد.',
            ],
            'eu_business_no_vat_msg' => [
                'fi' => 'Yrityksellä ei ole vahvistettua EU:n ALV-tunnistetta, joten laskuun lisätään Suomen arvonlisävero.',
                'en' => 'The company does not have a validated EU VAT number; Finnish VAT is applicable.',
                'ar' => 'الشركة ليس لديها رقم ضريبي أوروبي معتمد، وبالتالي تطبق ضريبة القيمة المضافة الفنلندية.',
            ],
            'non_eu_business_id_required' => [
                'fi' => 'Ilmoita yritystunnus tai rekisterinumero yritysaseman todentamiseksi EU:n ulkopuolista verotonta myyntiä varten.',
                'en' => 'Please provide a business registration or tax ID to verify commercial status for zero-rated non-EU export.',
                'ar' => 'يرجى تقديم الرقم الضريبي أو السجل التجاري لإثبات صفة الشركة للتصدير المعفى خارج الاتحاد الأوروبي.',
            ]
        ];

        if (isset($translations[$key][$lang])) {
            return $translations[$key][$lang];
        }
        if (isset($translations[$key]['en'])) {
            return $translations[$key]['en'];
        }
        return '';
    }
}
