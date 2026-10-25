<?php
/**
 * i4ware PayPal Multi-Step Pre-Checkout Modal & Shortcodes
 *
 * Implements the 4-stage checkout funnel:
 * 1. Product / Tier Selection
 * 2. Customer & Tax Details Collection
 * 3. Server Verification, Tax Calculation & Terms Review
 * 4. PayPal Smart Subscription Execution
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enhanced translation dictionary for PayPal modal & shortcodes
 */
if (!function_exists('i4ware_get_paypal_translations')) {
    function i4ware_get_paypal_translations($lang = null)
    {
        if (!$lang) {
            $lang = function_exists('pll_current_language') ? pll_current_language() : 'fi';
        }
        if ($lang !== 'fi' && $lang !== 'en' && $lang !== 'ar') {
            $lang = 'en';
        }

        $translations = [
            'fi' => [
                'modal_badge' => 'Suojattu 256-bit SSL -yhteys',
                'modal_title' => 'Mainospaikan tilaus & turvallinen PayPal-maksu',
                'modal_subtitle' => 'Tarkista mainospaketti, valinnat, asiakastiedot ja verolaskelma ennen maksamista.',
                'lead_text' => 'Tilaa mainospaikka i4waren verkkosivustolta kertamaksulla tai kuukausitilauksella. Mainos julkaistaan sovitussa muodossa. Mainoksen sisältö, sijoittelu ja näkyvyyden kesto sovitaan tilauksen yhteydessä.',

                // Steps
                'step_1' => '1. Mainospaketti',
                'step_2' => '2. Asiakas- & aineistotiedot',
                'step_3' => '3. Tarkistus & Ehdot',
                'step_4' => '4. PayPal-maksu',

                // Ad specifications
                'billing_mode_label' => 'Maksutapa & näkyvyyden kesto *',
                'mode_subscription' => 'Jatkuva kuukausitilaus (jatkuva näkyvyys, voi peruuttaa milloin tahansa)',
                'mode_onetime' => 'Kertamaksu (määräaikainen 30 päivää, ei uusiudu automaattisesti)',
                'ad_format_label' => 'Mainoksen muoto *',
                'format_logo' => 'Logo tai yritystunnus linkillä',
                'format_banner' => 'Banneri tai graafinen kuvamainos linkillä',
                'format_text' => 'Tekstimainos tai esittelykappale linkillä',
                'ad_placement_label' => 'Sijoituspaikka *',
                'placement_footer' => 'Alatunniste & Kumppanit -osio sivustolla',
                'placement_sidebar' => 'Sivupalkki / Artikkelisivut',
                'placement_header' => 'Pääsivu / Teemasivujen näkyvyys',
                'duration_label' => 'Näkyvyyden kesto:',
                'duration_monthly_text' => 'Toistaiseksi jatkuva kuukausittainen näkyvyys (1 kuukauden laskutusjaksoissa)',
                'duration_onetime_text' => 'Määräaikainen 30 vuorokauden näkyvyys maksun ja aineiston vahvistuttua',
                'submission_proc_label' => 'Aineiston toimitus- ja hyväksymismenettely:',
                'submission_proc_desc' => 'Mainosaineisto (logo, banneri tai teksti ja kohde-URL) toimitetaan joko tällä lomakkeella tai sähköpostitse osoitteeseen info@i4ware.fi. Aineisto tarkistetaan ja julkaistaan 1–2 arkipäivän kuluessa aineiston hyväksymisestä.',
                'ad_target_url' => 'Mainoksen kohdeosoite (URL)',
                'ad_copy' => 'Mainosteksti tai lisäohjeet julkaisuun (vapaaehtoinen)',

                // Customer form
                'customer_type_label' => 'Asiakastyyppi:',
                'type_business' => 'Yritysasiakas',
                'type_individual' => 'Yksityishenkilö',
                'company_name' => 'Yrityksen virallinen nimi *',
                'establishment_country' => 'Yrityksen sijoittautumismaa *',
                'billing_country' => 'Laskutusmaa *',
                'billing_address' => 'Laskutusosoite (katuosoite, postinumero, kaupunki) *',
                'business_id' => 'Yritystunnus / Rekisterinumero (Y-tunnus tai vast.)',
                'vat_registered_label' => 'Onko yritys ALV-rekisteröity?',
                'vat_yes' => 'Kyllä',
                'vat_no' => 'Ei',
                'vat_unknown' => 'En tiedä',
                'vat_country' => 'ALV-tunnisteen myöntänyt maa',
                'vat_number' => 'ALV-tunniste (ilman maakoodia)',
                'fixed_establishment_label' => 'Palvelun vastaanottava kiinteä toimipaikka (jos eri kuin kotipaikka):',
                'no_fixed_establishment' => 'Ei erillistä toimipaikkaa / Sama kuin kotipaikka',
                'contact_name' => 'Yhteyshenkilön nimi *',
                'contact_email' => 'Sähköpostiosoite *',
                'contact_phone' => 'Puhelinnumero (vapaaehtoinen)',
                'individual_note' => 'Yksityishenkilöiltä ei vaadita yritys- tai ALV-tunnistetta.',

                // Buttons
                'btn_continue_to_details' => 'Jatka asiakastietoihin →',
                'btn_verify_and_continue' => 'Tarkista tiedot ja laske verot →',
                'btn_back' => '← Takaisin',
                'btn_proceed_to_paypal' => 'Hyväksy ja siirry PayPaliin →',
                'btn_cancel' => 'Peruuta',
                'btn_close' => 'Sulje',

                // Verification statuses
                'verifying_vies' => 'Tarkistetaan yritystietoja ja lasketaan veroja...',
                'vies_valid' => 'ALV-tunniste on voimassa.',
                'vies_invalid' => 'ALV-tunnistetta ei voitu vahvistaa. Tarkista tiedot.',
                'vies_unavailable' => 'Tarkistuspalvelu ei ole juuri nyt käytettävissä. Yritä uudelleen.',
                'vies_clarification' => 'Yritystiedot edellyttävät lisätarkistusta.',
                'gratuitous_disabled' => 'Vastikkeettoman tuen uudet kuukausitilaukset ovat toistaiseksi poissa käytöstä rahankeräyslain edellytysten selvittämisen ajaksi.',

                // Summary
                'summary_title' => 'Tilauksen yhteenveto',
                'contracting_party_label' => 'Sopimusosapuoli:',
                'contracting_party_value' => 'i4ware Software (Y-tunnus 2018861-9, Tampere, Suomi, info@i4ware.fi)',
                'service_content_label' => 'Palvelun sisältö:',
                'payment_nature_label' => 'Maksutapa & kesto:',
                'ad_format_review_label' => 'Mainosmuoto & sijoittelu:',
                'summary_price_net' => 'Veroton hinta:',
                'summary_vat' => 'Arvonlisävero (25,5 %):',
                'summary_total' => 'Maksettava loppusumma yhteensä:',
                'summary_tax_note' => 'Veromerkintä:',
                'next_billing_label' => 'Seuraava veloitus:',
                'next_billing_sub' => '1 kuukauden kuluttua, ellei tilausta peruuteta.',
                'next_billing_once' => 'Ei jatkoveloitusta (määräaikainen 30 päivää).',
                'terms_subscription' => 'Toistaiseksi voimassa oleva jatkuva kuukausitilaus. Voit peruuttaa tilauksen milloin tahansa helposti omalta PayPal-tililtäsi ilman lisäkuluja tai irtisanomisaikaa. Peruutuksen jälkeen mainosnäkyvyys säilyy maksetun laskutuskauden loppuun saakka.',
                'terms_one_time' => 'Kertamaksulla ostettu mainospaikka on voimassa määräajan 30 päivää maksun vahvistumisesta ja aineiston hyväksymisestä. Kertamaksu ei tarkoita pysyvää mainospaikkaa, eikä se uusiudu automaattisesti.',
                'security_note' => 'Maksutapahtuma suoritetaan turvallisesti PayPalin 256-bittisesti salatussa ympäristössä. Sivusto ei tallenna maksukorttitietoja.',
                'privacy_notice' => 'Tietojasi käsitellään laskutusta, mainosaineiston hallintaa, kirjanpitolain mukaista tositearkistointia ja verotarkastuksia varten.',
                'consent_prefix' => 'Olen tutustunut',
                'terms_link_text' => 'toimitusehtoihin',
                'and_text' => 'sekä',
                'privacy_link_text' => 'tietosuojaselosteeseen',
                'consent_suffix' => ' ja hyväksyn tilauksen ehdot.',
                'alert_must_agree' => 'Sinun tulee hyväksyä sopimusehdot jatkaaksesi.',
                'company_footer' => 'i4ware Software • info@i4ware.fi • Tampere, Suomi',
                'month' => 'kk',
                'vat_incl' => 'sis. ALV',
                'incl_vat' => 'sis. ALV',
                'one_time' => 'Kertamaksu (30 pv)',
                'recurring_monthly' => 'Kuukausimaksu',
                'subscribe' => 'Tilaa mainospaikka',
                'support_level' => 'Mainospaketti',
                'price' => 'Hinta (alv 0 %)',
                'alert_thanks' => 'Kiitos tilauksestasi! Tilaustunnuksesi on: ',
                'error_missing' => 'Virhe: [paypal_support_table] shortcode vaatii \'client_id\' -attribuutin.',
                'error_no_plans' => 'Huomio: Yhtään tilaussuunnitelman ID:tä ei ole määritetty [paypal_support_table] -lyhytkoodiin.',
                'donate_default_name' => 'Mainospaikka (30 päivää)',
                'donate_default_desc' => 'Määräaikainen 30 päivän mainospaikka i4waren verkkosivustolla. Aineisto toimitetaan osoitteeseen info@i4ware.fi.',
                'donate_btn_text' => 'Tilaa mainospaikka (30 pv)',
                'buy_btn_text' => 'Tilaa mainospaikka',
                'subscribe_btn_text' => 'Tilaa kuukausitilauksella',
                'levels' => [
                    'community' => 'Pronssi-mainospaikka',
                    'opensource' => 'Hopea-mainospaikka',
                    'development' => 'Kulta-mainospaikka',
                    'professional' => 'Platina-mainospaikka',
                    'business' => 'Yrityskumppani-mainospaikka',
                    'enterprise' => 'Pääpartneri-mainospaikka'
                ]
            ],
            'en' => [
                'modal_badge' => 'Secure 256-bit SSL Connection',
                'modal_title' => 'Order Advertising Space & Secure PayPal Checkout',
                'modal_subtitle' => 'Review advertising package, selections, customer details, and tax calculation before payment.',
                'lead_text' => 'Order an advertising space on i4ware\'s website with a one-time payment or monthly subscription. The advertisement is published in the agreed format. Ad content, placement, and visibility duration are agreed upon order.',

                // Steps
                'step_1' => '1. Ad Package',
                'step_2' => '2. Customer & Ad Details',
                'step_3' => '3. Review & Terms',
                'step_4' => '4. PayPal Payment',

                // Ad specifications
                'billing_mode_label' => 'Payment method & duration *',
                'mode_subscription' => 'Continuous monthly subscription (ongoing visibility, cancel anytime)',
                'mode_onetime' => 'One-time payment (fixed 30 days, does not renew automatically)',
                'ad_format_label' => 'Ad format *',
                'format_logo' => 'Logo or company brand with link',
                'format_banner' => 'Banner or graphic image ad with link',
                'format_text' => 'Text ad or showcase summary with link',
                'ad_placement_label' => 'Placement *',
                'placement_footer' => 'Footer & Partners Section on website',
                'placement_sidebar' => 'Sidebar / Article Pages',
                'placement_header' => 'Frontpage / Themed Pages visibility',
                'duration_label' => 'Visibility duration:',
                'duration_monthly_text' => 'Continuous monthly visibility (1-month billing cycles until cancelled)',
                'duration_onetime_text' => 'Fixed 30-day visibility upon payment confirmation and material approval',
                'submission_proc_label' => 'Material submission and approval procedure:',
                'submission_proc_desc' => 'Ad materials (logo, banner, or text and target URL) are submitted either via this form or by email to info@i4ware.fi. Materials are reviewed and published within 1–2 business days of approval.',
                'ad_target_url' => 'Ad target URL (URL)',
                'ad_copy' => 'Ad text copy or special publishing instructions (optional)',

                // Customer form
                'customer_type_label' => 'Customer Type:',
                'type_business' => 'Business Customer',
                'type_individual' => 'Private Individual',
                'company_name' => 'Official Company Name *',
                'establishment_country' => 'Company Establishment Country *',
                'billing_country' => 'Billing Country *',
                'billing_address' => 'Billing Address (street, postal code, city) *',
                'business_id' => 'Business ID / Registration Number',
                'vat_registered_label' => 'Is the company VAT registered?',
                'vat_yes' => 'Yes',
                'vat_no' => 'No',
                'vat_unknown' => 'Don\'t know',
                'vat_country' => 'VAT ID Issuing Country',
                'vat_number' => 'VAT Number (without country prefix)',
                'fixed_establishment_label' => 'Fixed establishment receiving service (if different from domicile):',
                'no_fixed_establishment' => 'No separate establishment / Same as domicile',
                'contact_name' => 'Contact Person Name *',
                'contact_email' => 'Email Address *',
                'contact_phone' => 'Phone Number (optional)',
                'individual_note' => 'Business registration or VAT ID is not required for private individuals.',

                // Buttons
                'btn_continue_to_details' => 'Continue to customer details →',
                'btn_verify_and_continue' => 'Verify details & calculate tax →',
                'btn_back' => '← Back',
                'btn_proceed_to_paypal' => 'Confirm and Proceed to PayPal →',
                'btn_cancel' => 'Cancel',
                'btn_close' => 'Close',

                // Verification statuses
                'verifying_vies' => 'Verifying company details and calculating taxes...',
                'vies_valid' => 'VAT ID is valid.',
                'vies_invalid' => 'VAT ID could not be validated. Please check the details.',
                'vies_unavailable' => 'Verification service is temporarily unavailable. Please retry.',
                'vies_clarification' => 'Company details require additional verification.',
                'gratuitous_disabled' => 'New recurring subscriptions for voluntary gratuitous support are currently paused pending regulatory review under the Money Collection Act.',

                // Summary
                'summary_title' => 'Order Summary',
                'contracting_party_label' => 'Contracting Party:',
                'contracting_party_value' => 'i4ware Software (Business ID FI20188619, Tampere, Finland, info@i4ware.fi)',
                'service_content_label' => 'Service Description:',
                'payment_nature_label' => 'Payment Method & Duration:',
                'ad_format_review_label' => 'Ad Format & Placement:',
                'summary_price_net' => 'Net Price:',
                'summary_vat' => 'VAT (25.5%):',
                'summary_total' => 'Total Payable Amount:',
                'summary_tax_note' => 'Tax Note:',
                'next_billing_label' => 'Next billing:',
                'next_billing_sub' => 'In 1 month, unless cancelled.',
                'next_billing_once' => 'No recurring charges (fixed 30 days).',
                'terms_subscription' => 'Continuous monthly recurring subscription. You can cancel anytime easily from your PayPal account with no extra fees or notice period. Visibility remains active until the end of the paid billing cycle.',
                'terms_one_time' => 'Ad space purchased with a one-time payment is valid for a fixed duration of 30 days from payment confirmation and material approval. A one-time payment does not grant a permanent ad space and does not recur automatically.',
                'security_note' => 'Your payment is processed securely in PayPal’s 256-bit SSL encrypted environment. This website never stores your payment card details.',
                'privacy_notice' => 'Your information is processed for billing, ad management, statutory accounting records, and tax compliance.',
                'consent_prefix' => 'I have read the',
                'terms_link_text' => 'terms of service',
                'and_text' => 'and',
                'privacy_link_text' => 'privacy policy',
                'consent_suffix' => ' and accept the advertising service terms.',
                'alert_must_agree' => 'You must agree to the terms to proceed.',
                'company_footer' => 'i4ware Software • info@i4ware.fi • Tampere, Finland',
                'month' => 'month',
                'vat_incl' => 'incl. VAT',
                'incl_vat' => 'incl. VAT',
                'one_time' => 'One-time (30 days)',
                'recurring_monthly' => 'Monthly billing',
                'subscribe' => 'Order Ad Space',
                'support_level' => 'Ad Package',
                'price' => 'Price (excl. VAT)',
                'alert_thanks' => 'Thank you for your order! Your order ID is: ',
                'error_missing' => 'Error: The [paypal_support_table] shortcode requires the \'client_id\' attribute.',
                'error_no_plans' => 'Warning: No subscription plan IDs have been configured in the [paypal_support_table] shortcode.',
                'donate_default_name' => 'Ad Space (30 days)',
                'donate_default_desc' => 'Fixed-term 30-day advertising space on i4ware\'s website. Ad materials sent to info@i4ware.fi.',
                'donate_btn_text' => 'Order Ad Space (30 days)',
                'buy_btn_text' => 'Order Ad Space',
                'subscribe_btn_text' => 'Order Monthly Subscription',
                'levels' => [
                    'community' => 'Bronze Ad Space',
                    'opensource' => 'Silver Ad Space',
                    'development' => 'Gold Ad Space',
                    'professional' => 'Platinum Ad Space',
                    'business' => 'Business Partner Ad Space',
                    'enterprise' => 'Main Partner Ad Space'
                ]
            ],
            'ar' => [
                'modal_badge' => 'اتصال آمن مشفر 256-bit SSL',
                'modal_title' => 'طلب مساحة إعلانية والدفع الآمن عبر باي بال',
                'modal_subtitle' => 'يرجى مراجعة باقة الإعلان، والخيارات، وبيانات العميل، وحساب الضرائب قبل الدفع.',
                'lead_text' => 'اطلب مساحة إعلانية على موقع i4ware بدفعة لمرة واحدة أو باشتراك شهري. يُنشر الإعلان بالشكل المتفق عليه. يتم الاتفاق على محتوى الإعلان، وموضعه، ومدة ظهوره عند الطلب.',

                // Steps
                'step_1' => '1. باقة الإعلان',
                'step_2' => '2. بيانات العميل والمواد الإعلانية',
                'step_3' => '3. المراجعة والشروط',
                'step_4' => '4. الدفع عبر باي بال',

                // Ad specifications
                'billing_mode_label' => 'طريقة الدفع والمدة *',
                'mode_subscription' => 'اشتراك شهري مستمر (ظهور دائم، يمكن الإلغاء في أي وقت)',
                'mode_onetime' => 'دفع لمرة واحدة (مدة محددة 30 يوماً، لا يتجدد تلقائياً)',
                'ad_format_label' => 'شكل الإعلان *',
                'format_logo' => 'شعار أو رمز الشركة مع رابط',
                'format_banner' => 'بنر أو إعلان مصور مع رابط',
                'format_text' => 'إعلان نصي أو نبذة تعريفية مع رابط',
                'ad_placement_label' => 'موضع الإعلان *',
                'placement_footer' => 'التذييل وقسم الشركاء في الموقع',
                'placement_sidebar' => 'الشريط الجانبي / صفحات المقالات',
                'placement_header' => 'الصفحة الرئيسية / الصفحات المخصصة',
                'duration_label' => 'مدة ظهور الإعلان:',
                'duration_monthly_text' => 'ظهور مستمر شهرياً (فترات فاتورة شهرية حتى الإلغاء)',
                'duration_onetime_text' => 'ظهور محدد لمدة 30 يوماً بعد تأكيد الدفع واعتماد المواد',
                'submission_proc_label' => 'إجراءات تسليم المواد والموافقة عليها:',
                'submission_proc_desc' => 'يتم إرسال المواد الإعلانية (الشعار أو البنر أو النص ورابط الوجهة) عبر هذا النموذج أو إلى info@i4ware.fi. تُراجع المواد وتُنشر خلال يوم إلى يومي عمل بعد الموافقة.',
                'ad_target_url' => 'رابط وجهة الإعلان (URL)',
                'ad_copy' => 'نص الإعلان أو تعليمات خاصة للنشر (اختياري)',

                // Customer form
                'customer_type_label' => 'نوع العميل:',
                'type_business' => 'عميل تجاري (شركة)',
                'type_individual' => 'فرد (مستهلك)',
                'company_name' => 'الاسم الرسمي للشركة *',
                'establishment_country' => 'بلد تأسيس الشركة *',
                'billing_country' => 'بلد الفاتورة *',
                'billing_address' => 'عنوان الفاتورة *',
                'business_id' => 'السجل التجاري / الرقم التعريفي',
                'vat_registered_label' => 'هل الشركة مسجلة في ضريبة القيمة المضافة؟',
                'vat_yes' => 'نعم',
                'vat_no' => 'لا',
                'vat_unknown' => 'لا أعلم',
                'vat_country' => 'بلد إصدار الرقم الضريبي',
                'vat_number' => 'الرقم الضريبي',
                'fixed_establishment_label' => 'المنشأة الدائمة المتلقية للخدمة (إن وجدت):',
                'no_fixed_establishment' => 'لا توجد منشأة منفصلة / نفس بلد المقر',
                'contact_name' => 'اسم جهة الاتصال *',
                'contact_email' => 'البريد الإلكتروني *',
                'contact_phone' => 'رقم الهاتف (اختياري)',
                'individual_note' => 'لا يُطلب رقم تعريفي تجاري أو رقم ضريبي من الأفراد.',

                // Buttons
                'btn_continue_to_details' => 'المتابعة إلى بيانات العميل →',
                'btn_verify_and_continue' => 'التحقق وحساب الضرائب →',
                'btn_back' => '← رجوع',
                'btn_proceed_to_paypal' => 'تأكيد والمتابعة إلى باي بال →',
                'btn_cancel' => 'إلغاء',
                'btn_close' => 'إغلاق',

                // Verification statuses
                'verifying_vies' => 'جاري التحقق من بيانات الشركة وحساب الضرائب...',
                'vies_valid' => 'الرقم الضريبي سارٍ.',
                'vies_invalid' => 'تعذر تأكيد الرقم الضريبي. يرجى مراجعة البيانات.',
                'vies_unavailable' => 'خدمة التحقق غير متاحة حالياً. يرجى إعادة المحاولة.',
                'vies_clarification' => 'تتطلب بيانات الشركة مراجعة إضافية.',
                'gratuitous_disabled' => 'الاشتراكات الجديدة للدعم التطوعي متوقفة مؤقتاً لحين استكمال المراجعة التنظيمية.',

                // Summary
                'summary_title' => 'ملخص الطلب',
                'contracting_party_label' => 'الطرف المتعاقد:',
                'contracting_party_value' => 'i4ware Software (تامبيري، فنلندا)',
                'service_content_label' => 'وصف الخدمة:',
                'payment_nature_label' => 'طريقة الدفع والمدة:',
                'ad_format_review_label' => 'شكل وموضع الإعلان:',
                'summary_price_net' => 'السعر الصافي:',
                'summary_vat' => 'ضريبة القيمة المضافة (25.5%):',
                'summary_total' => 'المبلغ الإجمالي المستحق:',
                'summary_tax_note' => 'الملاحظة الضريبية:',
                'next_billing_label' => 'الفاتورة القادمة:',
                'next_billing_sub' => 'بعد شهر واحد، ما لم يتم الإلغاء.',
                'next_billing_once' => 'لا توجد دفعات متكررة (مدة محددة 30 يوماً).',
                'terms_subscription' => 'اشتراك شهري مستمر. يمكنك الإلغاء في أي وقت بسهولة من حساب باي بال الخاص بك دون أي رسوم إضافية. يستمر ظهور الإعلان حتى نهاية فترة الفاتورة المدفوعة بعد الإلغاء.',
                'terms_one_time' => 'المساحة الإعلانية المشتراة بدفعة لمرة واحدة صالحة لمدة محددة قدرها 30 يوماً من تأكيد الدفع واعتماد المواد. الدفع لمرة واحدة لا يعني مساحة إعلانية دائمة ولا يتجدد تلقائياً.',
                'security_note' => 'تتم معالجة دفعتك بأمان في بيئة باي بال المشفرة بتقنية 256-bit SSL.',
                'privacy_notice' => 'تتم معالجة بياناتك لأغراض الفواتير وإدارة الإعلانات والامتثال الضريبي القانوني.',
                'consent_prefix' => 'لقد قرأت',
                'terms_link_text' => 'شروط الخدمة',
                'and_text' => 'و',
                'privacy_link_text' => 'سياسة الخصوصية',
                'consent_suffix' => ' وأوافق على شروط خدمة الإعلانات.',
                'alert_must_agree' => 'يجب عليك الموافقة على الشروط للمتابعة.',
                'company_footer' => 'i4ware Software • info@i4ware.fi • تامبيري، فنلندا',
                'month' => 'شهر',
                'vat_incl' => 'شامل الضريبة',
                'incl_vat' => 'شامل الضريبة',
                'one_time' => 'دفع لمرة واحدة (30 يوماً)',
                'recurring_monthly' => 'فاتورة شهرية',
                'subscribe' => 'طلب مساحة إعلانية',
                'support_level' => 'باقة الإعلان',
                'price' => 'السعر (بدون ضريبة)',
                'alert_thanks' => 'شكراً لطلبك! معرف الطلب الخاص بك هو: ',
                'error_missing' => 'خطأ: يتطلب الكود القصير [paypal_support_table] سمة \'client_id\'.',
                'error_no_plans' => 'تنبيه: لم يتم تكوين أي معرفات لخطط الاشتراك في الكود القصير [paypal_support_table].',
                'donate_default_name' => 'مساحة إعلانية (30 يوماً)',
                'donate_default_desc' => 'مساحة إعلانية محددة المدة لمدة 30 يوماً على موقع i4ware. تُرسل المواد إلى info@i4ware.fi.',
                'donate_btn_text' => 'طلب مساحة إعلانية (30 يوماً)',
                'buy_btn_text' => 'طلب مساحة إعلانية',
                'subscribe_btn_text' => 'طلب باشتراك شهري',
                'levels' => [
                    'community' => 'مساحة إعلانية برونزية',
                    'opensource' => 'مساحة إعلانية فضية',
                    'development' => 'مساحة إعلانية ذهبية',
                    'professional' => 'مساحة إعلانية بلاتينية',
                    'business' => 'مساحة إعلانية للشريك التجاري',
                    'enterprise' => 'مساحة إعلانية للشريك الرئيسي'
                ]
            ]
        ];

        $t = isset($translations[$lang]) ? $translations[$lang] : $translations['en'];

        if (function_exists('pll__')) {
            foreach ($t as $k => $val) {
                if (is_string($val)) {
                    $pll_val = pll__($val);
                    if (!empty($pll_val) && is_string($pll_val)) {
                        $t[$k] = html_entity_decode(wp_specialchars_decode($pll_val, ENT_QUOTES), ENT_QUOTES, 'UTF-8');
                    }
                }
            }
        }

        return $t;
    }
}

/**
 * Render the enhanced PayPal Pre-Checkout & Terms Modal in wp_footer
 */
if (!function_exists('i4ware_render_paypal_modal_system')) {
    function i4ware_render_paypal_modal_system()
    {
        static $modal_rendered = false;
        if ($modal_rendered) {
            return;
        }
        $modal_rendered = true;

        $lang = function_exists('pll_current_language') ? pll_current_language() : 'fi';
        if ($lang !== 'fi' && $lang !== 'en' && $lang !== 'ar') {
            $lang = 'en';
        }
        $t = i4ware_get_paypal_translations($lang);
        $is_rtl = ($lang === 'ar');

        $default_terms_link = get_theme_mod("wp_quote_terms_link_$lang", '#');
        $default_privacy_link = get_theme_mod("wp_quote_privacy_link_$lang", '#');
        $all_countries = i4ware_get_all_countries();
        $eu_countries = i4ware_get_eu_vat_countries();
        $api_endpoint = esc_url(rest_url('i4ware-paypal/v1/validate-checkout'));
        $confirm_endpoint = esc_url(rest_url('i4ware-paypal/v1/confirm-subscription'));
        $rest_nonce = wp_create_nonce('wp_rest');
        ?>
        <!-- i4ware PayPal Multi-Step Checkout & Tax Modal System -->
        <div id="i4ware-paypal-modal" class="i4ware-paypal-modal-overlay" aria-hidden="true" role="dialog" aria-modal="true"
            aria-labelledby="i4ware-modal-title" <?php echo $is_rtl ? 'dir="rtl"' : 'dir="ltr"'; ?>>
            <div class="i4ware-paypal-modal-backdrop" onclick="i4warePayPalModal.close()"></div>
            <div class="i4ware-paypal-modal-dialog">
                <!-- Modal Header -->
                <div class="i4ware-paypal-modal-header">
                    <div class="i4ware-paypal-badge">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
                            <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-1 16l-4-4 1.41-1.41L11 14.17l6.59-6.59L19 9l-8 8z" />
                        </svg>
                        <span id="i4ware-modal-badge"><?php echo esc_html($t['modal_badge']); ?></span>
                    </div>
                    <button type="button" class="i4ware-paypal-modal-close" onclick="i4warePayPalModal.close()"
                        aria-label="<?php echo esc_attr($t['btn_close']); ?>">&times;</button>
                </div>

                <div class="i4ware-paypal-modal-title-area">
                    <h3 id="i4ware-modal-title" class="i4ware-paypal-modal-title"><?php echo esc_html($t['modal_title']); ?></h3>
                    <p id="i4ware-modal-subtitle" class="i4ware-paypal-modal-subtitle"><?php echo esc_html($t['modal_subtitle']); ?></p>
                </div>

                <!-- Stepper Progress Indicator -->
                <div class="i4ware-stepper">
                    <div class="i4ware-step-item is-active" id="i4ware-step-indicator-1">
                        <span class="step-num">1</span>
                        <span class="step-title"><?php echo esc_html($t['step_1']); ?></span>
                    </div>
                    <div class="i4ware-step-line"></div>
                    <div class="i4ware-step-item" id="i4ware-step-indicator-2">
                        <span class="step-num">2</span>
                        <span class="step-title"><?php echo esc_html($t['step_2']); ?></span>
                    </div>
                    <div class="i4ware-step-line"></div>
                    <div class="i4ware-step-item" id="i4ware-step-indicator-3">
                        <span class="step-num">3</span>
                        <span class="step-title"><?php echo esc_html($t['step_3']); ?></span>
                    </div>
                    <div class="i4ware-step-line"></div>
                    <div class="i4ware-step-item" id="i4ware-step-indicator-4">
                        <span class="step-num">4</span>
                        <span class="step-title"><?php echo esc_html($t['step_4']); ?></span>
                    </div>
                </div>

                <!-- Modal Body with Steps -->
                <div class="i4ware-paypal-modal-body">
                    
                    <!-- STEP 1: ITEM SELECTION & ADVERTISING OVERVIEW -->
                    <div id="i4ware-step-1" class="i4ware-checkout-step is-active">
                        <!-- Mandatory Customer Lead Text -->
                        <div class="i4ware-lead-notice-card" style="background: rgba(14, 165, 233, 0.08); border: 1px solid rgba(14, 165, 233, 0.28); border-radius: 12px; padding: 12px 14px; margin-bottom: 14px; font-size: 13px; line-height: 1.5; color: #bae6fd;">
                            📢 <?php echo esc_html($t['lead_text']); ?>
                        </div>

                        <div class="i4ware-paypal-summary-card">
                            <div class="i4ware-summary-top">
                                <div class="i4ware-summary-item-info">
                                    <span class="i4ware-summary-label"><?php echo esc_html($t['support_level']); ?></span>
                                    <h4 id="i4ware-modal-item-name" class="i4ware-item-name"></h4>
                                </div>
                                <div class="i4ware-summary-price-box">
                                    <span id="i4ware-modal-price" class="i4ware-price-amount"></span>
                                    <span id="i4ware-modal-frequency" class="i4ware-price-frequency"></span>
                                </div>
                            </div>
                            <p id="i4ware-modal-description" class="i4ware-modal-desc"></p>

                            <!-- Payment Type Badge & Note -->
                            <div id="i4ware-payment-type-box" class="i4ware-payment-type-box">
                                <span class="i4ware-type-pill" id="i4ware-type-pill-text">Mainospalvelu</span>
                                <p id="i4ware-type-description" class="i4ware-type-desc">Mainospaikka i4waren verkkosivustolla sovitussa muodossa ja sijoittelussa.</p>
                            </div>
                        </div>

                        <!-- Advertising Specifications: Billing mode, format & placement -->
                        <div class="i4ware-form-group">
                            <label class="i4ware-form-label"><?php echo esc_html($t['billing_mode_label']); ?></label>
                            <div class="i4ware-radio-toggle">
                                <label class="i4ware-radio-option">
                                    <input type="radio" name="i4ware_modal_billing_mode" value="subscription" checked onchange="i4warePayPalModal.onBillingModeChange()" />
                                    <span><?php echo esc_html($t['mode_subscription']); ?></span>
                                </label>
                                <label class="i4ware-radio-option">
                                    <input type="radio" name="i4ware_modal_billing_mode" value="onetime" onchange="i4warePayPalModal.onBillingModeChange()" />
                                    <span><?php echo esc_html($t['mode_onetime']); ?></span>
                                </label>
                            </div>
                        </div>

                        <div class="i4ware-form-row">
                            <div class="i4ware-form-group" style="flex: 1;">
                                <label for="i4ware-ad-format" class="i4ware-form-label"><?php echo esc_html($t['ad_format_label']); ?></label>
                                <select id="i4ware-ad-format" class="i4ware-select" onchange="i4warePayPalModal.onAdSpecChange()">
                                    <option value="logo"><?php echo esc_html($t['format_logo']); ?></option>
                                    <option value="banner"><?php echo esc_html($t['format_banner']); ?></option>
                                    <option value="text"><?php echo esc_html($t['format_text']); ?></option>
                                </select>
                            </div>
                            <div class="i4ware-form-group" style="flex: 1;">
                                <label for="i4ware-ad-placement" class="i4ware-form-label"><?php echo esc_html($t['ad_placement_label']); ?></label>
                                <select id="i4ware-ad-placement" class="i4ware-select" onchange="i4warePayPalModal.onAdSpecChange()">
                                    <option value="footer_partner"><?php echo esc_html($t['placement_footer']); ?></option>
                                    <option value="sidebar"><?php echo esc_html($t['placement_sidebar']); ?></option>
                                    <option value="header_frontpage"><?php echo esc_html($t['placement_header']); ?></option>
                                </select>
                            </div>
                        </div>

                        <div class="i4ware-spec-duration-card" style="background: rgba(0, 0, 0, 0.25); border: 1px solid rgba(255, 255, 255, 0.07); border-radius: 8px; padding: 10px 12px; margin-bottom: 12px; font-size: 12.5px;">
                            <span style="color: #94a3b8; font-weight: 700;"><?php echo esc_html($t['duration_label']); ?></span>
                            <span id="i4ware-spec-duration-display" style="color: #38bdf8; font-weight: 600; margin-left: 6px;"><?php echo esc_html($t['duration_monthly_text']); ?></span>
                        </div>

                        <div class="i4ware-submission-info-card" style="background: rgba(255, 255, 255, 0.03); border: 1px dashed rgba(255, 255, 255, 0.12); border-radius: 8px; padding: 10px 12px; font-size: 12px; color: #94a3b8; line-height: 1.45; margin-bottom: 14px;">
                            <strong style="color: #cbd5e1; display: block; margin-bottom: 2px;">ℹ️ <?php echo esc_html($t['submission_proc_label']); ?></strong>
                            <?php echo esc_html($t['submission_proc_desc']); ?>
                        </div>

                        <div class="i4ware-step-actions">
                            <button type="button" class="i4ware-btn-proceed" onclick="i4warePayPalModal.goToStep(2)">
                                <span><?php echo esc_html($t['btn_continue_to_details']); ?></span>
                            </button>
                        </div>
                    </div>

                    <!-- STEP 2: CUSTOMER & AD DETAILS -->
                    <div id="i4ware-step-2" class="i4ware-checkout-step">
                        <form id="i4ware-customer-form" onsubmit="event.preventDefault(); i4warePayPalModal.submitCustomerDetails();">
                            <!-- Ad Material Information (Optional during order, can be provided via email) -->
                            <div class="i4ware-form-group">
                                <label for="i4ware-ad-target-url" class="i4ware-form-label"><?php echo esc_html($t['ad_target_url']); ?></label>
                                <input type="url" id="i4ware-ad-target-url" class="i4ware-input" placeholder="https://..." />
                            </div>
                            <div class="i4ware-form-group">
                                <label for="i4ware-ad-copy" class="i4ware-form-label"><?php echo esc_html($t['ad_copy']); ?></label>
                                <textarea id="i4ware-ad-copy" class="i4ware-textarea" rows="2" placeholder="Esim. Mainosteksti tai toive julkaisusta..."></textarea>
                            </div>

                            <div class="i4ware-form-group">
                                <label class="i4ware-form-label"><?php echo esc_html($t['customer_type_label']); ?></label>
                                <div class="i4ware-radio-toggle">
                                    <label class="i4ware-radio-option">
                                        <input type="radio" name="i4ware_customer_type" value="business" checked onchange="i4warePayPalModal.onCustomerTypeChange()" />
                                        <span><?php echo esc_html($t['type_business']); ?></span>
                                    </label>
                                    <label class="i4ware-radio-option">
                                        <input type="radio" name="i4ware_customer_type" value="individual" onchange="i4warePayPalModal.onCustomerTypeChange()" />
                                        <span><?php echo esc_html($t['type_individual']); ?></span>
                                    </label>
                                </div>
                            </div>

                            <!-- Business Only Fields -->
                            <div id="i4ware-business-fields">
                                <div class="i4ware-form-group">
                                    <label for="i4ware-company-name" class="i4ware-form-label"><?php echo esc_html($t['company_name']); ?></label>
                                    <input type="text" id="i4ware-company-name" class="i4ware-input" required />
                                </div>

                                <div class="i4ware-form-row">
                                    <div class="i4ware-form-group" style="flex: 1;">
                                        <label for="i4ware-establishment-country" class="i4ware-form-label"><?php echo esc_html($t['establishment_country']); ?></label>
                                        <select id="i4ware-establishment-country" class="i4ware-select" onchange="i4warePayPalModal.onEstablishmentCountryChange()">
                                            <?php foreach ($all_countries as $code => $name) : ?>
                                                <option value="<?php echo esc_attr($code); ?>" <?php selected('FI', $code); ?>><?php echo esc_html($name); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="i4ware-form-group" style="flex: 1;">
                                        <label for="i4ware-business-id" class="i4ware-form-label"><?php echo esc_html($t['business_id']); ?></label>
                                        <input type="text" id="i4ware-business-id" class="i4ware-input" placeholder="esim. 1234567-8" />
                                    </div>
                                </div>

                                <div class="i4ware-form-group">
                                    <label class="i4ware-form-label"><?php echo esc_html($t['vat_registered_label']); ?></label>
                                    <div class="i4ware-vat-radios">
                                        <label><input type="radio" name="i4ware_vat_registered" value="yes" onchange="i4warePayPalModal.onVatRegisteredChange()" /> <?php echo esc_html($t['vat_yes']); ?></label>
                                        <label><input type="radio" name="i4ware_vat_registered" value="no" checked onchange="i4warePayPalModal.onVatRegisteredChange()" /> <?php echo esc_html($t['vat_no']); ?></label>
                                        <label><input type="radio" name="i4ware_vat_registered" value="unknown" onchange="i4warePayPalModal.onVatRegisteredChange()" /> <?php echo esc_html($t['vat_unknown']); ?></label>
                                    </div>
                                </div>

                                <div id="i4ware-vat-details-row" class="i4ware-form-row" style="display: none;">
                                    <div class="i4ware-form-group" style="flex: 1;">
                                        <label for="i4ware-vat-country" class="i4ware-form-label"><?php echo esc_html($t['vat_country']); ?></label>
                                        <select id="i4ware-vat-country" class="i4ware-select">
                                            <?php foreach ($eu_countries as $code => $name) : ?>
                                                <option value="<?php echo esc_attr($code); ?>"><?php echo esc_html($code . ' - ' . $name); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="i4ware-form-group" style="flex: 1.5;">
                                        <label for="i4ware-vat-number" class="i4ware-form-label"><?php echo esc_html($t['vat_number']); ?></label>
                                        <input type="text" id="i4ware-vat-number" class="i4ware-input" placeholder="esim. 12345678" />
                                    </div>
                                </div>

                                <div id="i4ware-branch-clarification" class="i4ware-form-group" style="display: none;">
                                    <label for="i4ware-fixed-establishment" class="i4ware-form-label"><?php echo esc_html($t['fixed_establishment_label']); ?></label>
                                    <select id="i4ware-fixed-establishment" class="i4ware-select">
                                        <option value=""><?php echo esc_html($t['no_fixed_establishment']); ?></option>
                                        <?php foreach ($eu_countries as $code => $name) : ?>
                                            <option value="<?php echo esc_attr($code); ?>"><?php echo esc_html($code . ' - ' . $name); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Individual Notice -->
                            <div id="i4ware-individual-notice" style="display: none; padding: 10px; background: rgba(56, 189, 248, 0.08); border-radius: 8px; margin-bottom: 12px; font-size: 13px; color: #38bdf8;">
                                ℹ️ <?php echo esc_html($t['individual_note']); ?>
                            </div>

                            <!-- Billing Address & Common Fields -->
                            <div class="i4ware-form-group">
                                <label for="i4ware-billing-country" class="i4ware-form-label"><?php echo esc_html($t['billing_country']); ?></label>
                                <select id="i4ware-billing-country" class="i4ware-select">
                                    <?php foreach ($all_countries as $code => $name) : ?>
                                        <option value="<?php echo esc_attr($code); ?>" <?php selected('FI', $code); ?>><?php echo esc_html($name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="i4ware-form-group">
                                <label for="i4ware-billing-address" class="i4ware-form-label"><?php echo esc_html($t['billing_address']); ?></label>
                                <textarea id="i4ware-billing-address" class="i4ware-textarea" rows="2" required placeholder="Esim. Esimerkkikatu 12, 33100 Tampere"></textarea>
                            </div>

                            <div class="i4ware-form-row">
                                <div class="i4ware-form-group" style="flex: 1;">
                                    <label for="i4ware-contact-name" class="i4ware-form-label"><?php echo esc_html($t['contact_name']); ?></label>
                                    <input type="text" id="i4ware-contact-name" class="i4ware-input" required />
                                </div>
                                <div class="i4ware-form-group" style="flex: 1;">
                                    <label for="i4ware-contact-email" class="i4ware-form-label"><?php echo esc_html($t['contact_email']); ?></label>
                                    <input type="email" id="i4ware-contact-email" class="i4ware-input" required />
                                </div>
                            </div>

                            <div class="i4ware-form-group">
                                <label for="i4ware-contact-phone" class="i4ware-form-label"><?php echo esc_html($t['contact_phone']); ?></label>
                                <input type="tel" id="i4ware-contact-phone" class="i4ware-input" placeholder="+358..." />
                            </div>

                            <!-- Live Feedback Alert Area -->
                            <div id="i4ware-step-2-alert" class="i4ware-alert-box" style="display: none;"></div>

                            <div class="i4ware-step-actions">
                                <button type="button" class="i4ware-btn-cancel" onclick="i4warePayPalModal.goToStep(1)"><?php echo esc_html($t['btn_back']); ?></button>
                                <button type="submit" id="i4ware-verify-btn" class="i4ware-btn-proceed">
                                    <span id="i4ware-verify-btn-text"><?php echo esc_html($t['btn_verify_and_continue']); ?></span>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- STEP 3: REVIEW, TAX CALCULATION & TERMS -->
                    <div id="i4ware-step-3" class="i4ware-checkout-step">
                        <!-- Verification Status Banner -->
                        <div id="i4ware-vies-status-banner" class="i4ware-vies-banner"></div>

                        <!-- Full Breakdown Card -->
                        <div class="i4ware-paypal-summary-card">
                            <div class="i4ware-summary-row">
                                <span class="label"><?php echo esc_html($t['contracting_party_label']); ?></span>
                                <span class="val"><strong><?php echo esc_html($t['contracting_party_value']); ?></strong></span>
                            </div>
                            <div class="i4ware-summary-row">
                                <span class="label"><?php echo esc_html($t['service_content_label']); ?></span>
                                <span id="i4ware-review-item-name" class="val"></span>
                            </div>
                            <div class="i4ware-summary-row">
                                <span class="label"><?php echo esc_html($t['ad_format_review_label']); ?></span>
                                <span id="i4ware-review-ad-specs" class="val"></span>
                            </div>
                            <div class="i4ware-summary-row">
                                <span class="label"><?php echo esc_html($t['payment_nature_label']); ?></span>
                                <span id="i4ware-review-billing-mode" class="val"></span>
                            </div>
                            <div class="i4ware-summary-row">
                                <span class="label"><?php echo esc_html($t['summary_price_net']); ?></span>
                                <span id="i4ware-review-net" class="val"></span>
                            </div>
                            <div class="i4ware-summary-row">
                                <span class="label"><?php echo esc_html($t['summary_vat']); ?></span>
                                <span id="i4ware-review-vat" class="val"></span>
                            </div>
                            <div class="i4ware-summary-row highlight">
                                <span class="label"><?php echo esc_html($t['summary_total']); ?></span>
                                <span id="i4ware-review-gross" class="val"></span>
                            </div>
                            <div class="i4ware-tax-note-box">
                                <span class="tax-tag" id="i4ware-review-tax-code"></span>
                                <p id="i4ware-review-tax-note" class="tax-note"></p>
                            </div>
                        </div>

                        <!-- Terms & Cancellation Policy Box -->
                        <div class="i4ware-paypal-terms-card">
                            <div class="i4ware-terms-row">
                                <div class="i4ware-terms-icon">📄</div>
                                <div class="i4ware-terms-text">
                                    <strong id="i4ware-review-terms-title"><?php echo esc_html($t['terms_subscription']); ?></strong>
                                    <p id="i4ware-review-next-billing" style="margin: 4px 0 0; color: #38bdf8; font-weight: 600;"><?php echo esc_html($t['next_billing_label'] . ' ' . $t['next_billing_sub']); ?></p>
                                </div>
                            </div>
                            <div class="i4ware-terms-row">
                                <div class="i4ware-terms-icon">📢</div>
                                <div class="i4ware-terms-text">
                                    <p><?php echo esc_html($t['submission_proc_desc']); ?></p>
                                </div>
                            </div>
                            <div class="i4ware-terms-row">
                                <div class="i4ware-terms-icon">🔒</div>
                                <div class="i4ware-terms-text">
                                    <p><?php echo esc_html($t['privacy_notice']); ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Explicit Unchecked Consent Checkbox -->
                        <div class="i4ware-modal-consent-box">
                            <label class="i4ware-modal-consent-label" for="i4ware-modal-consent-checkbox">
                                <!-- UNCHECKED by default in compliance with consumer and data protection regulations -->
                                <input type="checkbox" id="i4ware-modal-consent-checkbox" />
                                <span class="i4ware-consent-text">
                                    <?php echo esc_html($t['consent_prefix']); ?>
                                    <a id="i4ware-modal-terms-link" href="<?php echo esc_url($default_terms_link); ?>" target="_blank" rel="noopener"><?php echo esc_html($t['terms_link_text']); ?></a>
                                    <?php echo esc_html($t['and_text']); ?>
                                    <a id="i4ware-modal-privacy-link" href="<?php echo esc_url($default_privacy_link); ?>" target="_blank" rel="noopener"><?php echo esc_html($t['privacy_link_text']); ?></a><?php echo esc_html($t['consent_suffix']); ?>
                                </span>
                            </label>
                            <div id="i4ware-modal-consent-error" class="i4ware-consent-error" style="display: none;">
                                <?php echo esc_html($t['alert_must_agree']); ?>
                            </div>
                        </div>

                        <div class="i4ware-step-actions">
                            <button type="button" class="i4ware-btn-cancel" onclick="i4warePayPalModal.goToStep(2)"><?php echo esc_html($t['btn_back']); ?></button>
                            <button type="button" id="i4ware-step-3-proceed" class="i4ware-btn-proceed" onclick="i4warePayPalModal.proceedToPayPalStep()">
                                <span><?php echo esc_html($t['btn_proceed_to_paypal']); ?></span>
                            </button>
                        </div>
                    </div>

                    <!-- STEP 4: PAYPAL PAYMENT EXECUTION -->
                    <div id="i4ware-step-4" class="i4ware-checkout-step">
                        <div class="i4ware-paypal-payment-wrap">
                            <div class="i4ware-payment-head">
                                <span id="i4ware-step-4-item-summary"></span>
                                <h3 id="i4ware-step-4-amount-summary" style="margin: 4px 0 16px; color: #38bdf8;"></h3>
                            </div>

                            <!-- PayPal Smart Buttons Container -->
                            <div id="i4ware-modal-smart-btn-container" style="min-height: 50px;"></div>

                            <div id="i4ware-payment-status" class="i4ware-alert-box" style="display: none; margin-top: 15px;"></div>
                        </div>

                        <div class="i4ware-step-actions" style="margin-top: 20px;">
                            <button type="button" class="i4ware-btn-cancel" onclick="i4warePayPalModal.goToStep(3)"><?php echo esc_html($t['btn_back']); ?></button>
                        </div>
                    </div>

                </div>

                <div class="i4ware-paypal-modal-bottom-brand">
                    <?php echo esc_html($t['company_footer']); ?>
                </div>
            </div>
        </div>

        <!-- Styles for Multi-Step Modal -->
        <style>
            .i4ware-paypal-modal-overlay {
                position: fixed;
                inset: 0;
                width: 100vw;
                height: 100vh;
                z-index: 999999;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 16px;
                box-sizing: border-box;
                opacity: 0;
                visibility: hidden;
                pointer-events: none;
                transition: opacity 0.28s cubic-bezier(0.16, 1, 0.3, 1), visibility 0.28s;
            }
            .i4ware-paypal-modal-overlay.is-active {
                opacity: 1;
                visibility: visible;
                pointer-events: auto;
            }
            .i4ware-paypal-modal-backdrop {
                position: absolute;
                inset: 0;
                background: rgba(4, 7, 18, 0.85);
                backdrop-filter: blur(10px);
                -webkit-backdrop-filter: blur(10px);
            }
            .i4ware-paypal-modal-dialog {
                position: relative;
                z-index: 10;
                background: linear-gradient(165deg, #13172e 0%, #0c0e1c 100%);
                border: 1px solid rgba(77, 163, 255, 0.25);
                border-radius: 22px;
                width: 100%;
                max-width: 580px;
                max-height: 92vh;
                overflow-y: auto;
                box-shadow: 0 25px 60px rgba(0, 0, 0, 0.75), 0 0 35px rgba(0, 112, 186, 0.25);
                color: #e2e8f0;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
                padding: 26px 24px 20px;
                box-sizing: border-box;
                transform: translateY(20px) scale(0.97);
                transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            }
            .i4ware-paypal-modal-overlay.is-active .i4ware-paypal-modal-dialog {
                transform: translateY(0) scale(1);
            }
            .i4ware-paypal-modal-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 12px;
            }
            .i4ware-paypal-badge {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                background: rgba(16, 185, 129, 0.12);
                border: 1px solid rgba(16, 185, 129, 0.3);
                color: #34d399;
                padding: 5px 12px;
                border-radius: 999px;
                font-size: 11.5px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.4px;
            }
            .i4ware-paypal-modal-close {
                background: rgba(255, 255, 255, 0.08);
                border: 1px solid rgba(255, 255, 255, 0.1);
                color: #94a3b8;
                width: 32px;
                height: 32px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 20px;
                cursor: pointer;
                transition: all 0.2s ease;
            }
            .i4ware-paypal-modal-close:hover {
                background: rgba(239, 68, 68, 0.2);
                border-color: rgba(239, 68, 68, 0.4);
                color: #f87171;
            }
            .i4ware-paypal-modal-title {
                margin: 0 0 4px;
                font-size: 19px;
                font-weight: 800;
                color: #ffffff;
            }
            .i4ware-paypal-modal-subtitle {
                margin: 0 0 16px;
                font-size: 13px;
                color: #94a3b8;
            }
            
            /* Stepper */
            .i4ware-stepper {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 20px;
                padding: 10px 14px;
                background: rgba(0, 0, 0, 0.25);
                border-radius: 12px;
                border: 1px solid rgba(255, 255, 255, 0.05);
            }
            .i4ware-step-item {
                display: flex;
                align-items: center;
                gap: 6px;
                color: #64748b;
                font-size: 11.5px;
                font-weight: 600;
            }
            .i4ware-step-item.is-active {
                color: #38bdf8;
            }
            .i4ware-step-item.is-complete {
                color: #10b981;
            }
            .i4ware-step-item .step-num {
                width: 20px;
                height: 20px;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.08);
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 11px;
            }
            .i4ware-step-item.is-active .step-num {
                background: #0284c7;
                color: #ffffff;
            }
            .i4ware-step-item.is-complete .step-num {
                background: #10b981;
                color: #ffffff;
            }
            .i4ware-step-line {
                flex: 1;
                height: 2px;
                background: rgba(255, 255, 255, 0.08);
                margin: 0 8px;
            }

            /* Step Containers */
            .i4ware-checkout-step {
                display: none;
            }
            .i4ware-checkout-step.is-active {
                display: block;
                animation: i4wareFadeIn 0.25s ease-out;
            }
            @keyframes i4wareFadeIn {
                from { opacity: 0; transform: translateY(6px); }
                to { opacity: 1; transform: translateY(0); }
            }

            /* Form Elements */
            .i4ware-form-group {
                margin-bottom: 14px;
            }
            .i4ware-form-row {
                display: flex;
                gap: 12px;
            }
            .i4ware-form-label {
                display: block;
                font-size: 12px;
                font-weight: 700;
                color: #94a3b8;
                margin-bottom: 5px;
                text-transform: uppercase;
                letter-spacing: 0.4px;
            }
            .i4ware-input, .i4ware-select, .i4ware-textarea {
                width: 100%;
                background: rgba(15, 23, 42, 0.7);
                border: 1px solid rgba(255, 255, 255, 0.12);
                border-radius: 8px;
                color: #ffffff;
                padding: 9px 12px;
                font-size: 13.5px;
                box-sizing: border-box;
                font-family: inherit;
                transition: border-color 0.2s, box-shadow 0.2s;
            }
            .i4ware-input:focus, .i4ware-select:focus, .i4ware-textarea:focus {
                outline: none;
                border-color: #38bdf8;
                box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.2);
            }
            .i4ware-radio-toggle {
                display: flex;
                gap: 10px;
            }
            .i4ware-radio-option {
                flex: 1;
                background: rgba(255, 255, 255, 0.04);
                border: 1px solid rgba(255, 255, 255, 0.1);
                border-radius: 8px;
                padding: 8px 12px;
                cursor: pointer;
                display: flex;
                align-items: center;
                gap: 8px;
                font-size: 13px;
                transition: all 0.2s;
            }
            .i4ware-radio-option input {
                accent-color: #0284c7;
            }
            .i4ware-vat-radios {
                display: flex;
                gap: 16px;
                font-size: 13px;
                color: #cbd5e1;
            }
            .i4ware-vat-radios label {
                cursor: pointer;
            }
            .i4ware-vat-radios input {
                accent-color: #0284c7;
            }

            /* Step Actions */
            .i4ware-step-actions {
                display: flex;
                gap: 12px;
                margin-top: 18px;
            }
            .i4ware-btn-proceed {
                flex: 1;
                background: linear-gradient(135deg, #0070ba 0%, #005ea6 100%);
                color: #ffffff;
                border: none;
                padding: 12px 20px;
                font-size: 14.5px;
                font-weight: 700;
                border-radius: 50px;
                cursor: pointer;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                transition: all 0.2s ease;
                box-shadow: 0 4px 15px rgba(0, 112, 186, 0.4);
                font-family: inherit;
            }
            .i4ware-btn-proceed:hover {
                background: linear-gradient(135deg, #0086e0 0%, #0070ba 100%);
                transform: translateY(-2px);
            }
            .i4ware-btn-cancel {
                background: rgba(255, 255, 255, 0.06);
                border: 1px solid rgba(255, 255, 255, 0.1);
                color: #94a3b8;
                padding: 12px 18px;
                font-size: 13.5px;
                font-weight: 600;
                border-radius: 50px;
                cursor: pointer;
                transition: all 0.2s ease;
                font-family: inherit;
            }
            .i4ware-btn-cancel:hover {
                background: rgba(255, 255, 255, 0.1);
                color: #ffffff;
            }

            /* Summary Cards */
            .i4ware-paypal-summary-card {
                background: rgba(255, 255, 255, 0.035);
                border: 1px solid rgba(255, 255, 255, 0.08);
                border-radius: 14px;
                padding: 14px 16px;
                margin-bottom: 14px;
            }
            .i4ware-summary-top {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                gap: 12px;
                border-bottom: 1px solid rgba(255, 255, 255, 0.06);
                padding-bottom: 10px;
                margin-bottom: 10px;
            }
            .i4ware-summary-label {
                font-size: 11px;
                text-transform: uppercase;
                letter-spacing: 0.8px;
                color: #64748b;
                font-weight: 700;
                display: block;
                margin-bottom: 2px;
            }
            .i4ware-item-name {
                margin: 0;
                font-size: 16px;
                font-weight: 700;
                color: #ffffff;
            }
            .i4ware-price-amount {
                font-size: 18px;
                font-weight: 800;
                color: #38bdf8;
                display: block;
            }
            .i4ware-price-frequency {
                font-size: 11px;
                color: #94a3b8;
            }
            .i4ware-summary-row {
                display: flex;
                justify-content: space-between;
                padding: 6px 0;
                font-size: 13px;
                color: #cbd5e1;
            }
            .i4ware-summary-row.highlight {
                border-top: 1px solid rgba(255, 255, 255, 0.08);
                padding-top: 10px;
                margin-top: 6px;
                font-size: 15px;
                font-weight: 700;
                color: #38bdf8;
            }
            .i4ware-tax-note-box {
                background: rgba(0, 0, 0, 0.25);
                border-radius: 8px;
                padding: 8px 12px;
                margin-top: 10px;
            }
            .i4ware-tax-note-box .tax-tag {
                display: inline-block;
                background: rgba(14, 165, 233, 0.15);
                border: 1px solid rgba(14, 165, 233, 0.3);
                color: #38bdf8;
                font-size: 10.5px;
                font-weight: 700;
                padding: 2px 6px;
                border-radius: 4px;
                text-transform: uppercase;
                margin-bottom: 4px;
            }
            .i4ware-tax-note-box .tax-note {
                margin: 0;
                font-size: 12px;
                color: #94a3b8;
                line-height: 1.4;
            }

            /* Payment type pill */
            .i4ware-payment-type-box {
                margin-top: 8px;
                padding-top: 8px;
                border-top: 1px dashed rgba(255, 255, 255, 0.08);
            }
            .i4ware-type-pill {
                display: inline-block;
                background: rgba(192, 132, 252, 0.12);
                border: 1px solid rgba(192, 132, 252, 0.3);
                color: #c084fc;
                font-size: 11px;
                font-weight: 700;
                padding: 2px 8px;
                border-radius: 20px;
                text-transform: uppercase;
            }
            .i4ware-type-desc {
                margin: 6px 0 0;
                font-size: 12px;
                color: #94a3b8;
            }

            /* Alert Box */
            .i4ware-alert-box {
                padding: 10px 14px;
                border-radius: 8px;
                font-size: 13px;
                margin-top: 12px;
                line-height: 1.45;
            }
            .i4ware-alert-box.info {
                background: rgba(2, 132, 199, 0.12);
                border: 1px solid rgba(2, 132, 199, 0.3);
                color: #38bdf8;
            }
            .i4ware-alert-box.success {
                background: rgba(16, 185, 129, 0.12);
                border: 1px solid rgba(16, 185, 129, 0.3);
                color: #34d399;
            }
            .i4ware-alert-box.warning {
                background: rgba(245, 158, 11, 0.12);
                border: 1px solid rgba(245, 158, 11, 0.3);
                color: #fbbf24;
            }
            .i4ware-alert-box.error {
                background: rgba(239, 68, 68, 0.12);
                border: 1px solid rgba(239, 68, 68, 0.3);
                color: #f87171;
            }

            .i4ware-paypal-terms-card {
                background: rgba(15, 23, 42, 0.6);
                border: 1px solid rgba(255, 255, 255, 0.06);
                border-radius: 12px;
                padding: 12px 14px;
                margin-bottom: 14px;
            }
            .i4ware-terms-row {
                display: flex;
                gap: 10px;
                margin-bottom: 8px;
            }
            .i4ware-terms-row:last-child {
                margin-bottom: 0;
            }
            .i4ware-terms-icon {
                font-size: 16px;
            }
            .i4ware-terms-text {
                font-size: 12px;
                color: #94a3b8;
                line-height: 1.4;
            }
            .i4ware-terms-text strong {
                color: #e2e8f0;
            }
            .i4ware-modal-consent-box {
                margin-bottom: 14px;
                padding: 10px 12px;
                background: rgba(255, 255, 255, 0.025);
                border-radius: 8px;
                border: 1px dashed rgba(255, 255, 255, 0.12);
            }
            .i4ware-modal-consent-label {
                display: flex;
                align-items: flex-start;
                gap: 10px;
                cursor: pointer;
                font-size: 12.5px;
                color: #cbd5e1;
                line-height: 1.4;
            }
            .i4ware-modal-consent-label input[type="checkbox"] {
                margin-top: 2px;
                width: 16px;
                height: 16px;
                accent-color: #0070ba;
                cursor: pointer;
            }
            .i4ware-modal-consent-label a {
                color: #38bdf8;
                text-decoration: underline;
            }
            .i4ware-consent-error {
                color: #f87171;
                font-size: 11.5px;
                font-weight: 600;
                margin-top: 6px;
                padding-left: 26px;
            }
            .i4ware-paypal-modal-bottom-brand {
                text-align: center;
                font-size: 11px;
                color: #64748b;
                margin-top: 10px;
            }
        </style>

        <!-- JavaScript for Multi-Step Checkout Modal -->
        <script type="text/javascript">
            window.i4warePayPalModal = {
                currentStep: 1,
                activeConfig: null,
                sessionToken: null,
                validatedOrder: null,
                apiValidateUrl: '<?php echo esc_js($api_endpoint); ?>',
                apiConfirmUrl: '<?php echo esc_js($confirm_endpoint); ?>',
                restNonce: '<?php echo esc_js($rest_nonce); ?>',
                lang: '<?php echo esc_js($lang); ?>',
                translations: <?php echo wp_json_encode($t); ?>,

                open: function (config) {
                    var modal = document.getElementById('i4ware-paypal-modal');
                    if (!modal) return;

                    this.activeConfig = config || {};
                    this.sessionToken = null;
                    this.validatedOrder = null;

                    // Populate Step 1 item details
                    document.getElementById('i4ware-modal-item-name').innerText = config.itemName || '';
                    document.getElementById('i4ware-modal-price').innerText = config.price || '';
                    document.getElementById('i4ware-modal-frequency').innerText = config.frequency || '';
                    
                    var descEl = document.getElementById('i4ware-modal-description');
                    if (config.description) {
                        descEl.innerText = config.description;
                        descEl.style.display = 'block';
                    } else {
                        descEl.style.display = 'none';
                    }

                    // Payment type pill
                    var paymentType = config.paymentType || '<?php echo esc_js(I4WARE_PAYMENT_ADVERTISING_SERVICE); ?>';
                    this.activeConfig.paymentType = paymentType;
                    var typePill = document.getElementById('i4ware-type-pill-text');
                    var typeDesc = document.getElementById('i4ware-type-description');

                    if (paymentType === '<?php echo esc_js(I4WARE_PAYMENT_ADVERTISING_SERVICE); ?>') {
                        typePill.innerText = 'Mainospalvelu';
                        typeDesc.innerText = 'Mainospaikka i4waren verkkosivustolla sovitussa muodossa ja sijoittelussa.';
                    } else if (paymentType === '<?php echo esc_js(I4WARE_PAYMENT_COMMERCIAL_SPONSORSHIP); ?>') {
                        typePill.innerText = 'Vastikkeellinen sponsorointi';
                        typeDesc.innerText = 'Tuki sisältää sovitun näkyvyyden tai mainoskumppanuuden verkkosivuillamme.';
                    } else if (paymentType === '<?php echo esc_js(I4WARE_PAYMENT_SOFTWARE_SERVICE); ?>') {
                        typePill.innerText = 'Ohjelmisto- tai kehityspalvelu';
                        typeDesc.innerText = 'Toimitusehtojen mukainen kehitys- tai lisenssitilaus.';
                    } else {
                        typePill.innerText = 'Mainospalvelu';
                        typeDesc.innerText = 'Tilaa mainospaikka i4waren verkkosivustolta kertamaksulla tai kuukausitilauksella.';
                    }

                    // Set billing mode
                    var billingMode = config.billingMode || 'subscription';
                    var modeRadios = document.querySelectorAll('input[name="i4ware_modal_billing_mode"]');
                    for (var r = 0; r < modeRadios.length; r++) {
                        modeRadios[r].checked = (modeRadios[r].value === billingMode);
                    }
                    this.onBillingModeChange();

                    // Set ad format and placement if provided
                    if (config.adFormat && document.getElementById('i4ware-ad-format')) {
                        document.getElementById('i4ware-ad-format').value = config.adFormat;
                    }
                    if (config.adPlacement && document.getElementById('i4ware-ad-placement')) {
                        document.getElementById('i4ware-ad-placement').value = config.adPlacement;
                    }
                    if (config.adTargetUrl && document.getElementById('i4ware-ad-target-url')) {
                        document.getElementById('i4ware-ad-target-url').value = config.adTargetUrl;
                    }

                    // Reset form & steps
                    this.goToStep(1);

                    // Ensure consent checkbox is UNCHECKED by default
                    var consentCheck = document.getElementById('i4ware-modal-consent-checkbox');
                    if (consentCheck) consentCheck.checked = false;

                    modal.classList.add('is-active');
                    modal.setAttribute('aria-hidden', 'false');
                    document.body.style.overflow = 'hidden';

                    if (typeof gtag === 'function') {
                        gtag('event', 'open_paypal_modal', {
                            event_category: 'PayPal Funnel',
                            item_name: config.itemName,
                            payment_type: paymentType
                        });
                    }
                },

                close: function () {
                    var modal = document.getElementById('i4ware-paypal-modal');
                    if (!modal) return;
                    modal.classList.remove('is-active');
                    modal.setAttribute('aria-hidden', 'true');
                    document.body.style.overflow = '';
                    this.activeConfig = null;
                },

                goToStep: function (stepNum) {
                    this.currentStep = stepNum;

                    // Update step visibility
                    for (var i = 1; i <= 4; i++) {
                        var stepEl = document.getElementById('i4ware-step-' + i);
                        var indEl = document.getElementById('i4ware-step-indicator-' + i);
                        if (stepEl) {
                            if (i === stepNum) {
                                stepEl.classList.add('is-active');
                            } else {
                                stepEl.classList.remove('is-active');
                            }
                        }
                        if (indEl) {
                            indEl.classList.remove('is-active', 'is-complete');
                            if (i === stepNum) {
                                indEl.classList.add('is-active');
                            } else if (i < stepNum) {
                                indEl.classList.add('is-complete');
                            }
                        }
                    }

                    var alertBox = document.getElementById('i4ware-step-2-alert');
                    if (alertBox && stepNum === 2) alertBox.style.display = 'none';
                },

                onBillingModeChange: function () {
                    var mode = document.querySelector('input[name="i4ware_modal_billing_mode"]:checked') ? document.querySelector('input[name="i4ware_modal_billing_mode"]:checked').value : 'subscription';
                    var durationDisplay = document.getElementById('i4ware-spec-duration-display');
                    var freqDisplay = document.getElementById('i4ware-modal-frequency');

                    if (mode === 'onetime') {
                        if (durationDisplay) durationDisplay.innerText = this.translations.duration_onetime_text;
                        if (freqDisplay) freqDisplay.innerText = '(' + this.translations.one_time + ')';
                    } else {
                        if (durationDisplay) durationDisplay.innerText = this.translations.duration_monthly_text;
                        if (freqDisplay) freqDisplay.innerText = '/ ' + this.translations.month;
                    }
                },

                onAdSpecChange: function () {
                    // Ad specification update handler
                },

                onCustomerTypeChange: function () {
                    var customerType = document.querySelector('input[name="i4ware_customer_type"]:checked').value;
                    var bizFields = document.getElementById('i4ware-business-fields');
                    var indNotice = document.getElementById('i4ware-individual-notice');
                    var companyNameInput = document.getElementById('i4ware-company-name');

                    if (customerType === 'business') {
                        bizFields.style.display = 'block';
                        indNotice.style.display = 'none';
                        if (companyNameInput) companyNameInput.required = true;
                    } else {
                        bizFields.style.display = 'none';
                        indNotice.style.display = 'block';
                        if (companyNameInput) companyNameInput.required = false;
                    }
                },

                onEstablishmentCountryChange: function () {
                    var estCountry = document.getElementById('i4ware-establishment-country').value;
                    var branchBox = document.getElementById('i4ware-branch-clarification');
                    var euCountries = <?php echo wp_json_encode(array_keys($eu_countries)); ?>;
                    var isEu = euCountries.indexOf(estCountry) !== -1;

                    // If non-EU established company, show branch question
                    if (!isEu) {
                        branchBox.style.display = 'block';
                    } else {
                        branchBox.style.display = 'none';
                    }

                    // Keep billing country synced by default if unchanged
                    var billCountry = document.getElementById('i4ware-billing-country');
                    if (billCountry && !billCountry.dataset.userEdited) {
                        billCountry.value = estCountry;
                    }
                },

                onVatRegisteredChange: function () {
                    var isVat = document.querySelector('input[name="i4ware_vat_registered"]:checked').value;
                    var vatRow = document.getElementById('i4ware-vat-details-row');
                    if (isVat === 'yes') {
                        vatRow.style.display = 'flex';
                    } else {
                        vatRow.style.display = 'none';
                    }
                },

                submitCustomerDetails: function () {
                    var self = this;
                    var customerType = document.querySelector('input[name="i4ware_customer_type"]:checked').value;
                    var paymentType = self.activeConfig ? self.activeConfig.paymentType : '<?php echo esc_js(I4WARE_PAYMENT_ADVERTISING_SERVICE); ?>';
                    var baseAmount = self.activeConfig ? self.activeConfig.rawAmount : 0;
                    var currency = self.activeConfig ? self.activeConfig.currency : 'EUR';
                    var planId = self.activeConfig ? self.activeConfig.planId : '';
                    var itemName = self.activeConfig ? self.activeConfig.itemName : '';
                    var billingMode = document.querySelector('input[name="i4ware_modal_billing_mode"]:checked') ? document.querySelector('input[name="i4ware_modal_billing_mode"]:checked').value : 'subscription';
                    var adFormat = document.getElementById('i4ware-ad-format') ? document.getElementById('i4ware-ad-format').value : 'logo';
                    var adPlacement = document.getElementById('i4ware-ad-placement') ? document.getElementById('i4ware-ad-placement').value : 'footer_partner';
                    var adTargetUrl = document.getElementById('i4ware-ad-target-url') ? document.getElementById('i4ware-ad-target-url').value.trim() : '';
                    var adCopy = document.getElementById('i4ware-ad-copy') ? document.getElementById('i4ware-ad-copy').value.trim() : '';

                    var payload = {
                        lang: self.lang,
                        payment_type: paymentType,
                        billing_mode: billingMode,
                        ad_format: adFormat,
                        ad_placement: adPlacement,
                        ad_target_url: adTargetUrl,
                        ad_copy: adCopy,
                        customer_type: customerType,
                        company_name: document.getElementById('i4ware-company-name') ? document.getElementById('i4ware-company-name').value.trim() : '',
                        establishment_country: document.getElementById('i4ware-establishment-country') ? document.getElementById('i4ware-establishment-country').value : '',
                        fixed_establishment_country: document.getElementById('i4ware-fixed-establishment') ? document.getElementById('i4ware-fixed-establishment').value : '',
                        billing_country: document.getElementById('i4ware-billing-country').value,
                        billing_address: document.getElementById('i4ware-billing-address').value.trim(),
                        business_id: document.getElementById('i4ware-business-id') ? document.getElementById('i4ware-business-id').value.trim() : '',
                        vat_registered: document.querySelector('input[name="i4ware_vat_registered"]:checked') ? document.querySelector('input[name="i4ware_vat_registered"]:checked').value : 'no',
                        vat_country: document.getElementById('i4ware-vat-country') ? document.getElementById('i4ware-vat-country').value : '',
                        vat_number: document.getElementById('i4ware-vat-number') ? document.getElementById('i4ware-vat-number').value.trim() : '',
                        contact_name: document.getElementById('i4ware-contact-name').value.trim(),
                        contact_email: document.getElementById('i4ware-contact-email').value.trim(),
                        contact_phone: document.getElementById('i4ware-contact-phone') ? document.getElementById('i4ware-contact-phone').value.trim() : '',
                        plan_id: planId,
                        item_name: itemName,
                        base_amount: baseAmount,
                        currency: currency
                    };

                    var alertBox = document.getElementById('i4ware-step-2-alert');
                    var verifyBtn = document.getElementById('i4ware-verify-btn');

                    alertBox.className = 'i4ware-alert-box info';
                    alertBox.innerText = self.translations.verifying_vies;
                    alertBox.style.display = 'block';
                    verifyBtn.disabled = true;

                    var reqHeaders = {
                        'Content-Type': 'application/json'
                    };
                    var nonce = self.restNonce || ((typeof wpApiSettings !== 'undefined' && wpApiSettings.nonce) ? wpApiSettings.nonce : '');
                    if (nonce) {
                        reqHeaders['X-WP-Nonce'] = nonce;
                    }

                    fetch(self.apiValidateUrl, {
                        method: 'POST',
                        headers: reqHeaders,
                        body: JSON.stringify(payload)
                    })
                    .then(function(res) { return res.json(); })
                    .then(function(data) {
                        verifyBtn.disabled = false;
                        if (!data) {
                            alertBox.className = 'i4ware-alert-box error';
                            alertBox.innerText = 'Palvelin ei vastannut pyyntöön.';
                            return;
                        }

                        if (data.status === 'blocked') {
                            alertBox.className = 'i4ware-alert-box warning';
                            alertBox.innerText = data.message || self.translations.gratuitous_disabled;
                            return;
                        }

                        if (data.status === 'invalid') {
                            alertBox.className = 'i4ware-alert-box error';
                            alertBox.innerText = data.message || self.translations.vies_invalid;
                            return;
                        }

                        if (data.status === 'requires_manual_review') {
                            alertBox.className = 'i4ware-alert-box warning';
                            alertBox.innerText = (data.message || self.translations.vies_clarification) + ' (Tilaus siirretty ylläpidon manuaaliseen tarkistukseen).';
                            return;
                        }

                        if (data.success && data.status === 'approved') {
                            self.sessionToken = data.token;
                            self.validatedOrder = data;
                            self.validatedOrder.billing_mode = billingMode;

                            // Fill Step 3 Review details
                            document.getElementById('i4ware-review-item-name').innerText = itemName;

                            var formatSelect = document.getElementById('i4ware-ad-format');
                            var formatText = formatSelect ? formatSelect.options[formatSelect.selectedIndex].text : '';
                            var placementSelect = document.getElementById('i4ware-ad-placement');
                            var placementText = placementSelect ? placementSelect.options[placementSelect.selectedIndex].text : '';
                            document.getElementById('i4ware-review-ad-specs').innerText = formatText + ' • ' + placementText;

                            document.getElementById('i4ware-review-billing-mode').innerText = (billingMode === 'subscription') ? self.translations.mode_subscription : self.translations.mode_onetime;
                            document.getElementById('i4ware-review-net').innerText = data.calculation.net_amount.toFixed(2) + ' ' + data.calculation.currency;
                            
                            var vatLabel = data.calculation.vat_rate !== null ? (data.calculation.vat_rate + ' %: ' + data.calculation.vat_amount.toFixed(2) + ' ' + data.calculation.currency) : '0,00 ' + data.calculation.currency;
                            document.getElementById('i4ware-review-vat').innerText = vatLabel;

                            var freqSuffix = (billingMode === 'subscription') ? (' / ' + self.translations.month) : (' (' + self.translations.one_time + ')');
                            document.getElementById('i4ware-review-gross').innerText = data.calculation.gross_amount.toFixed(2) + ' ' + data.calculation.currency + freqSuffix;
                            document.getElementById('i4ware-review-tax-code').innerText = data.calculation.tax_classification;
                            document.getElementById('i4ware-review-tax-note').innerText = data.calculation.tax_note;

                            // Terms & Next billing note
                            document.getElementById('i4ware-review-terms-title').innerText = (billingMode === 'subscription') ? self.translations.terms_subscription : self.translations.terms_one_time;
                            document.getElementById('i4ware-review-next-billing').innerText = (billingMode === 'subscription') ? (self.translations.next_billing_label + ' ' + self.translations.next_billing_sub) : (self.translations.next_billing_label + ' ' + self.translations.next_billing_once);

                            // VIES status banner in Step 3
                            var banner = document.getElementById('i4ware-vies-status-banner');
                            if (data.vies && data.vies.status === 'VALID') {
                                banner.innerHTML = '<div class="i4ware-alert-box success">✓ ' + self.translations.vies_valid + ' (' + (data.vies.consultation_number ? 'Ref: ' + data.vies.consultation_number : '') + ')</div>';
                                banner.style.display = 'block';
                            } else {
                                banner.style.display = 'none';
                            }

                            // Advance to Step 3 Review
                            self.goToStep(3);
                        } else {
                            alertBox.className = 'i4ware-alert-box error';
                            alertBox.innerText = data.message || 'Tietojen tarkistus epäonnistui.';
                        }
                    })
                    .catch(function(err) {
                        verifyBtn.disabled = false;
                        alertBox.className = 'i4ware-alert-box error';
                        alertBox.innerText = 'Verkkovirhe: ' + err.message;
                    });
                },

                proceedToPayPalStep: function () {
                    var consentCheck = document.getElementById('i4ware-modal-consent-checkbox');
                    var errorEl = document.getElementById('i4ware-modal-consent-error');

                    if (!consentCheck || !consentCheck.checked) {
                        if (errorEl) {
                            errorEl.style.display = 'block';
                            errorEl.classList.add('shake');
                            setTimeout(function () { errorEl.classList.remove('shake'); }, 500);
                        }
                        return;
                    }
                    if (errorEl) errorEl.style.display = 'none';

                    if (!this.sessionToken || !this.validatedOrder) {
                        alert('Tilaustietoja ei ole vielä vahvistettu.');
                        this.goToStep(2);
                        return;
                    }

                    // Render PayPal Smart Buttons in Step 4
                    var container = document.getElementById('i4ware-modal-smart-btn-container');
                    container.innerHTML = '';
                    
                    var billingMode = this.validatedOrder.billing_mode || 'subscription';
                    var freqSuffix = (billingMode === 'subscription') ? (' / ' + this.translations.month) : (' (' + this.translations.one_time + ')');
                    document.getElementById('i4ware-step-4-item-summary').innerText = this.activeConfig.itemName + (billingMode === 'onetime' ? ' (30 pv)' : '');
                    document.getElementById('i4ware-step-4-amount-summary').innerText = this.validatedOrder.calculation.gross_amount.toFixed(2) + ' ' + this.validatedOrder.calculation.currency + freqSuffix;

                    var self = this;
                    this.goToStep(4);

                    if (typeof paypal !== 'undefined') {
                        var btnConfig = {
                            style: {
                                shape: 'rect',
                                color: 'blue',
                                layout: 'vertical',
                                label: (billingMode === 'subscription') ? 'subscribe' : 'pay'
                            }
                        };

                        if (billingMode === 'subscription' && self.activeConfig.planId) {
                            btnConfig.createSubscription = function(data, actions) {
                                return actions.subscription.create({
                                    "plan_id": self.activeConfig.planId
                                });
                            };
                        } else {
                            btnConfig.createOrder = function(data, actions) {
                                return actions.order.create({
                                    purchase_units: [{
                                        amount: {
                                            value: self.validatedOrder.calculation.gross_amount.toFixed(2),
                                            currency_code: self.validatedOrder.calculation.currency
                                        },
                                        description: self.activeConfig.itemName + ' (30 pv)'
                                    }]
                                });
                            };
                        }

                        btnConfig.onApprove = function(data, actions) {
                            var orderOrSubId = data.subscriptionID || data.orderID;
                            if (!orderOrSubId && actions.order) {
                                return actions.order.capture().then(function(details) {
                                    return self.finalizeConfirmation(details.id);
                                });
                            }
                            return self.finalizeConfirmation(orderOrSubId);
                        };

                        btnConfig.onError = function(err) {
                            console.error('PayPal Smart Button error:', err);
                            var statusBox = document.getElementById('i4ware-payment-status');
                            statusBox.className = 'i4ware-alert-box error';
                            statusBox.innerText = 'PayPal-virhe: ' + err;
                            statusBox.style.display = 'block';
                        };

                        paypal.Buttons(btnConfig).render(container);
                    }
                },

                finalizeConfirmation: function (txnId) {
                    var self = this;
                    var statusBox = document.getElementById('i4ware-payment-status');
                    statusBox.className = 'i4ware-alert-box info';
                    statusBox.innerText = 'Vahvistetaan maksua palvelimella...';
                    statusBox.style.display = 'block';

                    var confirmHeaders = {
                        'Content-Type': 'application/json'
                    };
                    var confirmNonce = self.restNonce || ((typeof wpApiSettings !== 'undefined' && wpApiSettings.nonce) ? wpApiSettings.nonce : '');
                    if (confirmNonce) {
                        confirmHeaders['X-WP-Nonce'] = confirmNonce;
                    }

                    fetch(self.apiConfirmUrl, {
                        method: 'POST',
                        headers: confirmHeaders,
                        body: JSON.stringify({
                            token: self.sessionToken,
                            subscription_id: txnId
                        })
                    })
                    .then(function(res) { return res.json(); })
                    .then(function(resData) {
                        if (resData.success) {
                            statusBox.className = 'i4ware-alert-box success';
                            statusBox.innerText = self.translations.alert_thanks + txnId;
                            setTimeout(function() {
                                self.close();
                            }, 4000);
                        } else {
                            statusBox.className = 'i4ware-alert-box error';
                            statusBox.innerText = resData.message || 'Maksun vahvistus epäonnistui.';
                        }
                    })
                    .catch(function(e) {
                        statusBox.className = 'i4ware-alert-box error';
                        statusBox.innerText = 'Yhteysvirhe tilausta vahvistettaessa: ' + e.message;
                    });
                }
            };

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' || e.key === 'Esc') {
                    if (window.i4warePayPalModal) {
                        window.i4warePayPalModal.close();
                    }
                }
            });
        </script>
        <?php
    }
    add_action('wp_footer', 'i4ware_render_paypal_modal_system');
}

/**
 * Shortcode [paypal_support_table]
 */
if (!function_exists('i4ware_paypal_support_table_shortcode')) {
    function i4ware_paypal_support_table_shortcode($atts)
    {
        static $paypal_sdk_loaded = false;

        $atts = is_array($atts) ? $atts : [];
        $atts = array_change_key_case($atts, CASE_LOWER);

        $a = shortcode_atts([
            'client_id' => '',
            'payment_type' => I4WARE_PAYMENT_ADVERTISING_SERVICE,
            'community' => '',
            'opensource' => '',
            'development' => '',
            'professional' => '',
            'business' => '',
            'enterprise' => '',
            'env' => 'production',
            'currency' => 'EUR'
        ], $atts, 'paypal_support_table');

        $client_id = sanitize_text_field($a['client_id']);
        $payment_type = sanitize_text_field($a['payment_type']);
        $currency = sanitize_text_field($a['currency']);

        $lang = function_exists('pll_current_language') ? pll_current_language() : 'fi';
        if ($lang !== 'fi' && $lang !== 'en' && $lang !== 'ar') {
            $lang = 'en';
        }

        $t = i4ware_get_paypal_translations($lang);

        if (empty($client_id)) {
            return '<p style="color: red; font-weight: bold;">' . esc_html($t['error_missing']) . '</p>';
        }

        $align_left = ($lang === 'ar') ? 'right' : 'left';
        $align_right = ($lang === 'ar') ? 'left' : 'right';
        $month_label = !empty($t['month']) ? $t['month'] : 'kk';
        $currency_symbol = ($currency === 'USD') ? '$' : (($currency === 'GBP') ? '£' : '€');

        $levels = [
            [
                'name' => $t['levels']['community'],
                'price' => $currency_symbol . '5 / ' . $month_label,
                'raw_amount' => 5,
                'id' => sanitize_text_field($a['community']),
            ],
            [
                'name' => $t['levels']['opensource'],
                'price' => $currency_symbol . '10 / ' . $month_label,
                'raw_amount' => 10,
                'id' => sanitize_text_field($a['opensource']),
            ],
            [
                'name' => $t['levels']['development'],
                'price' => $currency_symbol . '25 / ' . $month_label,
                'raw_amount' => 25,
                'id' => sanitize_text_field($a['development']),
            ],
            [
                'name' => $t['levels']['professional'],
                'price' => $currency_symbol . '50 / ' . $month_label,
                'raw_amount' => 50,
                'id' => sanitize_text_field($a['professional']),
            ],
            [
                'name' => $t['levels']['business'],
                'price' => $currency_symbol . '100 / ' . $month_label,
                'raw_amount' => 100,
                'id' => sanitize_text_field($a['business']),
            ],
            [
                'name' => $t['levels']['enterprise'],
                'price' => $currency_symbol . '250 / ' . $month_label,
                'raw_amount' => 250,
                'id' => sanitize_text_field($a['enterprise']),
            ]
        ];

        $active_levels = [];
        foreach ($levels as $level) {
            if (!empty($level['id'])) {
                $active_levels[] = $level;
            }
        }

        if (empty($active_levels)) {
            return '<p style="color: orange; font-weight: bold;">' . esc_html($t['error_no_plans']) . '</p>';
        }

        $output = '';

        if (!$paypal_sdk_loaded) {
            $sdk_url = 'https://www.paypal.com/sdk/js?client-id=' . esc_attr($client_id) . '&vault=true&intent=subscription';
            if (!empty($currency)) {
                $sdk_url .= '&currency=' . esc_attr($currency);
            }
            $output .= '<script src="' . esc_url($sdk_url) . '" data-sdk-integration-source="button-factory"></script>';
            $paypal_sdk_loaded = true;
        }

        $output .= '<div class="i4ware-support-table-wrapper" style="overflow-x: auto; margin: 30px 0; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Oxygen, Ubuntu, Cantarell, \'Open Sans\', \'Helvetica Neue\', sans-serif; background: #131526; border-radius: 16px; border: 1px solid rgba(255, 255, 255, 0.08); padding: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.3);" ' . ($lang === 'ar' ? 'dir="rtl"' : 'dir="ltr"') . '>';
        $output .= '<table style="width: 100%; border-collapse: collapse; text-align: ' . $align_left . '; background: transparent; overflow: hidden;">';
        $output .= '<thead style="background: #0b0d19; border-bottom: 2px solid rgba(255, 255, 255, 0.08);">';
        $output .= '<tr>';
        $output .= '<th style="padding: 18px 20px; font-weight: 700; color: #8f9cae; font-size: 13px; text-transform: uppercase; letter-spacing: 0.8px; text-align: ' . $align_left . ';">' . esc_html($t['support_level']) . '</th>';
        $output .= '<th style="padding: 18px 20px; font-weight: 700; color: #8f9cae; font-size: 13px; text-align: ' . $align_right . '; text-transform: uppercase; letter-spacing: 0.8px;">' . esc_html($t['price']) . '</th>';
        $output .= '<th style="padding: 18px 20px; font-weight: 700; color: #8f9cae; font-size: 13px; text-align: center; width: 220px; text-transform: uppercase; letter-spacing: 0.8px;">' . esc_html($t['subscribe']) . '</th>';
        $output .= '</tr>';
        $output .= '</thead>';
        $output .= '<tbody>';

        foreach ($active_levels as $level) {
            $output .= '<tr style="border-bottom: 1px solid rgba(255, 255, 255, 0.08); transition: background 0.2s;" onmouseover="this.style.background=\'rgba(255, 255, 255, 0.03)\'" onmouseout="this.style.background=\'transparent\'">';
            $output .= '<td style="padding: 18px 20px; font-weight: 600; color: #ffffff; font-size: 15px; text-align: ' . $align_left . ';">' . esc_html($level['name']) . '</td>';
            $output .= '<td style="padding: 18px 20px; text-align: ' . $align_right . '; font-weight: 700; color: #c084fc; background: linear-gradient(90deg, #c084fc, #818cf8); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-size: 16px;">' . esc_html($level['price']) . '</td>';
            $output .= '<td style="padding: 12px 20px; text-align: center; vertical-align: middle;">';
            $output .= '<button type="button" class="i4ware-tier-sub-btn" onclick="i4wareOpenSupportTierModal(\'' . esc_js($level['id']) . '\', \'' . esc_js($level['name']) . '\', \'' . esc_js($level['price']) . '\', ' . (float) $level['raw_amount'] . ', \'' . esc_js($currency) . '\', \'' . esc_js($payment_type) . '\')" style="background: linear-gradient(135deg, #0070ba 0%, #005ea6 100%); color: #ffffff; border: none; padding: 10px 22px; font-size: 14px; font-weight: 700; border-radius: 50px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.25s ease; box-shadow: 0 4px 10px rgba(0, 112, 186, 0.3); outline: none;">';
            $output .= '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M20.007 6.467a3.003 3.003 0 0 0-3.003-2.92h-6.223L8.03 16.784a.5.5 0 0 0 .49.584h2.528l1.458-9.055a.5.5 0 0 1 .494-.42h4.524a1.002 1.002 0 0 1 .983 1.201l-1.458 9.055a.5.5 0 0 1-.494.42H11.53l.42 2.61a.5.5 0 0 0 .493.42H16.03a3.003 3.003 0 0 0 2.977-2.58l1.458-9.056a3.003 3.003 0 0 0-.458-2.072zM6.973 17.368H3.97a.5.5 0 0 1-.49-.584l2.75-17.072A1.5 1.5 0 0 1 7.712-.584h6.223a4.004 4.004 0 0 1 3.97 3.513l.458 2.842a1 1 0 0 1-.983 1.158H12.86a2 2 0 0 0-1.977 1.68l-1.458 9.056a2.002 2.002 0 0 0 .494 1.705l-2.946-.042z"/></svg>';
            $output .= esc_html($t['subscribe']);
            $output .= '</button>';
            $output .= '</td>';
            $output .= '</tr>';
        }

        $output .= '</tbody>';
        $output .= '</table>';
        $output .= '</div>';

        $output .= '<script type="text/javascript">
            function i4wareOpenSupportTierModal(planId, levelName, priceFormatted, rawAmount, currency, paymentType) {
                if (window.i4warePayPalModal) {
                    window.i4warePayPalModal.open({
                        planId: planId,
                        itemName: levelName,
                        price: priceFormatted,
                        rawAmount: rawAmount,
                        currency: currency || "EUR",
                        frequency: "' . esc_js($t['recurring_monthly']) . '",
                        paymentType: paymentType || "advertising_service",
                        billingMode: "subscription"
                    });
                }
            }
        </script>';

        return $output;
    }
    add_shortcode('paypal_support_table', 'i4ware_paypal_support_table_shortcode');
    add_shortcode('paypal_advertising_table', 'i4ware_paypal_support_table_shortcode');
}

/**
 * Shortcode [paypal_subscribe]
 */
if (!function_exists('i4ware_paypal_subscribe_shortcode')) {
    function i4ware_paypal_subscribe_shortcode($atts)
    {
        static $paypal_sdk_loaded = false;

        $atts = is_array($atts) ? $atts : [];
        $atts = array_change_key_case($atts, CASE_LOWER);

        $lang = function_exists('pll_current_language') ? pll_current_language() : 'fi';
        if ($lang !== 'fi' && $lang !== 'en' && $lang !== 'ar') {
            $lang = 'en';
        }

        $t = i4ware_get_paypal_translations($lang);

        $a = shortcode_atts([
            'client_id' => '',
            'plan_id' => '',
            'item_name' => $t['subscribe_btn_text'],
            'price' => '',
            'amount' => '',
            'payment_type' => I4WARE_PAYMENT_ADVERTISING_SERVICE,
            'currency' => 'EUR',
            'button_text' => $t['subscribe_btn_text'],
            'description' => ''
        ], $atts, 'paypal_subscribe');

        $client_id = sanitize_text_field($a['client_id']);
        $plan_id = sanitize_text_field($a['plan_id']);

        if (empty($plan_id) || empty($client_id)) {
            return '<p style="color: red; font-weight: bold;">Virhe: [paypal_subscribe] shortcode vaatii sekä \'client_id\' että \'plan_id\' -attribuutit.</p>';
        }

        $item_name = sanitize_text_field($a['item_name']);
        $price = !empty($a['price']) ? sanitize_text_field($a['price']) : sanitize_text_field($a['amount']);
        $raw_amount = (float) preg_replace('/[^0-9.]/', '', $price);
        $button_text = sanitize_text_field($a['button_text']);
        $description = sanitize_text_field($a['description']);
        $payment_type = sanitize_text_field($a['payment_type']);
        $currency = sanitize_text_field($a['currency']);
        $unique_btn_id = 'paypal-sub-trigger-' . uniqid();

        $output = '';

        if (!$paypal_sdk_loaded) {
            $sdk_url = 'https://www.paypal.com/sdk/js?client-id=' . esc_attr($client_id) . '&vault=true&intent=subscription';
            if (!empty($currency)) {
                $sdk_url .= '&currency=' . esc_attr($currency);
            }
            $output .= '<script src="' . esc_url($sdk_url) . '" data-sdk-integration-source="button-factory"></script>';
            $paypal_sdk_loaded = true;
        }

        $output .= '<div class="i4ware-paypal-subscribe-container" style="margin: 25px 0; text-align: center;">';
        $output .= '<button type="button" id="' . esc_attr($unique_btn_id) . '" onclick="i4wareOpenSmartSubModal(\'' . esc_js($plan_id) . '\', \'' . esc_js($item_name) . '\', \'' . esc_js($price) . '\', ' . (float) $raw_amount . ', \'' . esc_js($currency) . '\', \'' . esc_js($description) . '\', \'' . esc_js($payment_type) . '\')" style="background: #0070ba; color: #ffffff; border: none; padding: 12px 28px; font-size: 16px; font-weight: 700; border-radius: 50px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 10px; transition: all 0.3s ease; box-shadow: 0 4px 6px rgba(0, 112, 186, 0.2); outline: none;">';
        $output .= '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="currentColor" style="vertical-align: middle;"><path d="M20.007 6.467a3.003 3.003 0 0 0-3.003-2.92h-6.223L8.03 16.784a.5.5 0 0 0 .49.584h2.528l1.458-9.055a.5.5 0 0 1 .494-.42h4.524a1.002 1.002 0 0 1 .983 1.201l-1.458 9.055a.5.5 0 0 1-.494.42H11.53l.42 2.61a.5.5 0 0 0 .493.42H16.03a3.003 3.003 0 0 0 2.977-2.58l1.458-9.056a3.003 3.003 0 0 0-.458-2.072zM6.973 17.368H3.97a.5.5 0 0 1-.49-.584l2.75-17.072A1.5 1.5 0 0 1 7.712-.584h6.223a4.004 4.004 0 0 1 3.97 3.513l.458 2.842a1 1 0 0 1-.983 1.158H12.86a2 2 0 0 0-1.977 1.68l-1.458 9.056a2.002 2.002 0 0 0 .494 1.705l-2.946-.042z"/></svg>';
        $output .= esc_html($button_text);
        $output .= '</button>';
        $output .= '</div>';

        $output .= '<script type="text/javascript">
            function i4wareOpenSmartSubModal(planId, itemName, price, rawAmount, currency, desc, paymentType) {
                if (window.i4warePayPalModal) {
                    window.i4warePayPalModal.open({
                        planId: planId,
                        itemName: itemName,
                        price: price,
                        rawAmount: rawAmount || 0,
                        currency: currency || "EUR",
                        frequency: "' . esc_js($t['recurring_monthly']) . '",
                        description: desc,
                        paymentType: paymentType || "advertising_service",
                        billingMode: "subscription"
                    });
                }
            }
        </script>';

        return $output;
    }
    add_shortcode('paypal_subscribe', 'i4ware_paypal_subscribe_shortcode');
}

/**
 * Shortcode [paypal_donate]
 * Transformed to commercial one-time ad purchase (30 days) for backward compatibility
 */
if (!function_exists('i4ware_paypal_donate_shortcode')) {
    function i4ware_paypal_donate_shortcode($atts)
    {
        $atts = is_array($atts) ? $atts : [];
        $atts = array_change_key_case($atts, CASE_LOWER);

        $lang = function_exists('pll_current_language') ? pll_current_language() : 'fi';
        if ($lang !== 'fi' && $lang !== 'en' && $lang !== 'ar') {
            $lang = 'en';
        }

        $t = i4ware_get_paypal_translations($lang);

        $a = shortcode_atts([
            'email' => get_option('admin_email'),
            'currency' => 'EUR',
            'amount' => '',
            'item_name' => $t['donate_default_name'],
            'button_text' => '',
            'description' => $t['donate_default_desc'],
            'payment_type' => I4WARE_PAYMENT_ADVERTISING_SERVICE
        ], $atts, 'paypal_donate');

        $email = sanitize_email($a['email']);
        $currency = sanitize_text_field($a['currency']);
        $amount = sanitize_text_field($a['amount']);
        $raw_amount = (float) preg_replace('/[^0-9.]/', '', $amount);
        $item_name = sanitize_text_field($a['item_name']);
        $button_text = sanitize_text_field($a['button_text']);
        $description = sanitize_text_field($a['description']);
        $payment_type = sanitize_text_field($a['payment_type']);

        $currency_symbol = ($currency === 'USD') ? '$' : (($currency === 'GBP') ? '£' : '€');
        $formatted_price = !empty($amount) ? ($currency === 'USD' || $currency === 'GBP' ? $currency_symbol . $amount : $amount . ' ' . $currency_symbol) : $t['one_time'];

        if (empty($button_text)) {
            $button_text = !empty($amount) ? ($t['donate_btn_text'] . ' (' . $formatted_price . ')') : $t['donate_btn_text'];
        }

        $unique_id = 'paypal-ad-onetime-form-' . uniqid();

        $output = '<div class="i4ware-paypal-donate-container" style="margin: 25px 0; text-align: center;">';
        $output .= '<form id="' . esc_attr($unique_id) . '" action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank" style="display: inline-block;">';
        $output .= '<input type="hidden" name="cmd" value="_xclick" />';
        $output .= '<input type="hidden" name="business" value="' . esc_attr($email) . '" />';
        $output .= '<input type="hidden" name="currency_code" value="' . esc_attr($currency) . '" />';
        $output .= '<input type="hidden" name="item_name" value="' . esc_attr($item_name) . '" />';
        if (!empty($amount)) {
            $output .= '<input type="hidden" name="amount" value="' . esc_attr($amount) . '" />';
        }
        $output .= '<button type="button" class="i4ware-paypal-trigger-btn" onclick="i4wareOpenDonateModal(\'' . esc_js($unique_id) . '\', \'' . esc_js($item_name) . '\', \'' . esc_js($formatted_price) . '\', ' . (float) $raw_amount . ', \'' . esc_js($currency) . '\', \'' . esc_js($description) . '\')" style="background: #0070ba; color: #ffffff; border: none; padding: 12px 28px; font-size: 16px; font-weight: 700; border-radius: 50px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 10px; transition: all 0.3s ease; box-shadow: 0 4px 6px rgba(0, 112, 186, 0.2); outline: none;">';
        $output .= '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="currentColor" style="vertical-align: middle;"><path d="M20.007 6.467a3.003 3.003 0 0 0-3.003-2.92h-6.223L8.03 16.784a.5.5 0 0 0 .49.584h2.528l1.458-9.055a.5.5 0 0 1 .494-.42h4.524a1.002 1.002 0 0 1 .983 1.201l-1.458 9.055a.5.5 0 0 1-.494.42H11.53l.42 2.61a.5.5 0 0 0 .493.42H16.03a3.003 3.003 0 0 0 2.977-2.58l1.458-9.056a3.003 3.003 0 0 0-.458-2.072zM6.973 17.368H3.97a.5.5 0 0 1-.49-.584l2.75-17.072A1.5 1.5 0 0 1 7.712-.584h6.223a4.004 4.004 0 0 1 3.97 3.513l.458 2.842a1 1 0 0 1-.983 1.158H12.86a2 2 0 0 0-1.977 1.68l-1.458 9.056a2.002 2.002 0 0 0 .494 1.705l-2.946-.042z"/></svg>';
        $output .= esc_html($button_text);
        $output .= '</button>';
        $output .= '</form>';
        $output .= '</div>';

        $output .= '<script type="text/javascript">
            function i4wareOpenDonateModal(formId, itemName, priceFormatted, rawAmount, currency, desc) {
                if (window.i4warePayPalModal) {
                    window.i4warePayPalModal.open({
                        itemName: itemName,
                        price: priceFormatted,
                        rawAmount: rawAmount,
                        currency: currency,
                        frequency: "' . esc_js($t['one_time']) . '",
                        description: desc,
                        paymentType: "advertising_service",
                        billingMode: "onetime"
                    });
                }
            }
        </script>';

        return $output;
    }
    add_shortcode('paypal_donate', 'i4ware_paypal_donate_shortcode');
}

/**
 * Shortcode [paypal_button]
 */
if (!function_exists('i4ware_paypal_button_shortcode')) {
    function i4ware_paypal_button_shortcode($atts)
    {
        $atts = is_array($atts) ? $atts : [];
        $atts = array_change_key_case($atts, CASE_LOWER);

        $lang = function_exists('pll_current_language') ? pll_current_language() : 'fi';
        if ($lang !== 'fi' && $lang !== 'en' && $lang !== 'ar') {
            $lang = 'en';
        }

        $t = i4ware_get_paypal_translations($lang);

        $a = shortcode_atts([
            'url' => '',
            'id' => '',
            'button_text' => $t['buy_btn_text'],
            'item_name' => '',
            'price' => '',
            'amount' => '',
            'currency' => 'EUR',
            'payment_type' => I4WARE_PAYMENT_ADVERTISING_SERVICE,
            'description' => ''
        ], $atts, 'paypal_button');

        $button_text = sanitize_text_field($a['button_text']);
        $item_name = !empty($a['item_name']) ? sanitize_text_field($a['item_name']) : $button_text;
        $price = !empty($a['price']) ? sanitize_text_field($a['price']) : sanitize_text_field($a['amount']);
        $raw_amount = (float) preg_replace('/[^0-9.]/', '', $price);
        $currency = sanitize_text_field($a['currency']);
        $currency_symbol = ($currency === 'USD') ? '$' : (($currency === 'GBP') ? '£' : '€');
        $formatted_price = !empty($price) ? ($currency === 'USD' || $currency === 'GBP' ? $currency_symbol . $price : $price . ' ' . $currency_symbol) : '';
        $description = sanitize_text_field($a['description']);
        $payment_type = sanitize_text_field($a['payment_type']);

        // Check if direct URL is provided
        if (!empty($a['url'])) {
            $url = esc_url($a['url']);
            $unique_id = 'paypal-btn-link-' . uniqid();
            $output = '<div class="i4ware-paypal-button-container" style="margin: 25px 0; text-align: center;">';
            $output .= '<button type="button" id="' . esc_attr($unique_id) . '" onclick="i4wareOpenBuyLinkModal(\'' . esc_js($url) . '\', \'' . esc_js($item_name) . '\', \'' . esc_js($formatted_price) . '\', ' . (float) $raw_amount . ', \'' . esc_js($currency) . '\', \'' . esc_js($description) . '\', \'' . esc_js($payment_type) . '\')" style="background: #0070ba; color: #ffffff; padding: 12px 28px; font-size: 16px; font-weight: 700; border-radius: 50px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 10px; transition: all 0.3s ease; box-shadow: 0 4px 6px rgba(0, 112, 186, 0.2); outline: none; border: none;">';
            $output .= '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="currentColor" style="vertical-align: middle;"><path d="M20.007 6.467a3.003 3.003 0 0 0-3.003-2.92h-6.223L8.03 16.784a.5.5 0 0 0 .49.584h2.528l1.458-9.055a.5.5 0 0 1 .494-.42h4.524a1.002 1.002 0 0 1 .983 1.201l-1.458 9.055a.5.5 0 0 1-.494.42H11.53l.42 2.61a.5.5 0 0 0 .493.42H16.03a3.003 3.003 0 0 0 2.977-2.58l1.458-9.056a3.003 3.003 0 0 0-.458-2.072zM6.973 17.368H3.97a.5.5 0 0 1-.49-.584l2.75-17.072A1.5 1.5 0 0 1 7.712-.584h6.223a4.004 4.004 0 0 1 3.97 3.513l.458 2.842a1 1 0 0 1-.983 1.158H12.86a2 2 0 0 0-1.977 1.68l-1.458 9.056a2.002 2.002 0 0 0 .494 1.705l-2.946-.042z"/></svg>';
            $output .= esc_html($button_text);
            $output .= '</button>';
            $output .= '</div>';

            $output .= '<script type="text/javascript">
                function i4wareOpenBuyLinkModal(url, itemName, priceFormatted, rawAmount, currency, desc, paymentType) {
                    if (window.i4warePayPalModal) {
                        window.i4warePayPalModal.open({
                            itemName: itemName,
                            price: priceFormatted,
                            rawAmount: rawAmount,
                            currency: currency,
                            frequency: "' . esc_js($t['one_time']) . '",
                            description: desc,
                            paymentType: paymentType || "advertising_service",
                            billingMode: "onetime"
                        });
                    }
                }
            </script>';

            return $output;
        }

        // Check if Hosted Button ID is provided
        if (!empty($a['id'])) {
            $hosted_button_id = sanitize_text_field($a['id']);
            $unique_id = 'paypal-hosted-form-' . uniqid();
            $output = '<div class="i4ware-paypal-button-container" style="margin: 25px 0; text-align: center;">';
            $output .= '<form id="' . esc_attr($unique_id) . '" action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank" style="display: inline-block;">';
            $output .= '<input type="hidden" name="cmd" value="_s-xclick" />';
            $output .= '<input type="hidden" name="hosted_button_id" value="' . esc_attr($hosted_button_id) . '" />';
            $output .= '<button type="button" onclick="i4wareOpenHostedModal(\'' . esc_js($unique_id) . '\', \'' . esc_js($item_name) . '\', \'' . esc_js($formatted_price) . '\', ' . (float) $raw_amount . ', \'' . esc_js($currency) . '\', \'' . esc_js($description) . '\', \'' . esc_js($payment_type) . '\')" style="background: #0070ba; color: #ffffff; border: none; padding: 12px 28px; font-size: 16px; font-weight: 700; border-radius: 50px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 10px; transition: all 0.3s ease; box-shadow: 0 4px 6px rgba(0, 112, 186, 0.2); outline: none;">';
            $output .= '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="currentColor" style="vertical-align: middle;"><path d="M20.007 6.467a3.003 3.003 0 0 0-3.003-2.92h-6.223L8.03 16.784a.5.5 0 0 0 .49.584h2.528l1.458-9.055a.5.5 0 0 1 .494-.42h4.524a1.002 1.002 0 0 1 .983 1.201l-1.458 9.055a.5.5 0 0 1-.494.42H11.53l.42 2.61a.5.5 0 0 0 .493.42H16.03a3.003 3.003 0 0 0 2.977-2.58l1.458-9.056a3.003 3.003 0 0 0-.458-2.072zM6.973 17.368H3.97a.5.5 0 0 1-.49-.584l2.75-17.072A1.5 1.5 0 0 1 7.712-.584h6.223a4.004 4.004 0 0 1 3.97 3.513l.458 2.842a1 1 0 0 1-.983 1.158H12.86a2 2 0 0 0-1.977 1.68l-1.458 9.056a2.002 2.002 0 0 0 .494 1.705l-2.946-.042z"/></svg>';
            $output .= esc_html($button_text);
            $output .= '</button>';
            $output .= '</form>';
            $output .= '</div>';

            $output .= '<script type="text/javascript">
                function i4wareOpenHostedModal(formId, itemName, priceFormatted, rawAmount, currency, desc, paymentType) {
                    if (window.i4warePayPalModal) {
                        window.i4warePayPalModal.open({
                            itemName: itemName,
                            price: priceFormatted,
                            rawAmount: rawAmount,
                            currency: currency,
                            frequency: "' . esc_js($t['one_time']) . '",
                            description: desc,
                            paymentType: paymentType || "advertising_service",
                            billingMode: "onetime"
                        });
                    }
                }
            </script>';

            return $output;
        }

        return '';
    }
    add_shortcode('paypal_button', 'i4ware_paypal_button_shortcode');
}
