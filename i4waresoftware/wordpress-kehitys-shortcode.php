<?php
/**
 * Shortcode for WordPress Development page [i4ware_wordpress_page]
 * Supports Polylang localizations (FI / EN / AR), Lightbox JS Screenshot Gallery,
 * and enqueues separate CSS from the SDK page.
 */
add_shortcode('i4ware_wordpress_page', function () {
    $lang = function_exists('pll_current_language') ? pll_current_language() : 'fi';

    // Enqueue the SDK page CSS for consistent layout and styling
    wp_enqueue_style('i4ware-sdk-page', get_template_directory_uri() . '/assets/css/sdk-page.css', array(), '1.1');

    // Localized strings
    $data = array(
        'fi' => array(
            'eyebrow' => 'WordPress · Avoin Lähdekoodi · Räätälöity',
            'hero_title' => 'WordPress-kehitys ammattitaidolla.',
            'hero_desc' => 'Tarjoamme kattavia WordPress-kehityspalveluita, jotka räätälöidään juuri sinun liiketoimintasi tarpeisiin. Toteutamme laadukkaat ja toimivat ratkaisut vuosien kokemuksella.',
            'request_quote' => 'Pyydä tarjous →',
            'github_code' => 'Lähdekoodi GitHubissa',
            'flexibility' => 'Joustavuus & Hinta',
            'flex_title' => 'Koodia tarpeidesi ja budjettisi mukaan.',
            'flex_lead' => 'Alempaa löydät tarjouspyyntölomakkeen. Tarjoamme palveluita suoraan loppuasiakkaille sekä alihankintana mainostoimistoille ja ohjelmistoyrityksille, jotka tarvitsevat kokeneen ohjelmoijan joustavasti kausiavuksi, sijaiseksi tai tuuraajaksi.',
            'flex_p1' => 'Olitpa köyhä opiskelijayrittäjä tai varakas pitkän linjan yrittäjä, löydämme yhdessä hinnan, joka miellyttää tilaajaa – sillä jo 20 € + ALV 25,5 % saat noin 20 € edestä koodia, ja esimerkiksi 500 €:lla saat vastaavan arvon mukaisen määrän koodirivejä.',
            'flex_p2' => 'Tämä on mahdollista, koska hyödynnämme tekoälyä koodin generoinnissa – voit esimerkiksi lähettää meille kuvatiedoston siitä, mitä haluat, ja tuotamme ratkaisun sen pohjalta nopeasti ja kustannustehokkaasti.',
            'badge1' => '20+ vuoden kokemus',
            'badge2' => 'Tekoälyavusteinen',
            'badge3' => 'Joustava kausiapulainen',
            'features' => 'Palvelumme',
            'features_title' => 'Räätälöidyt WordPress-ratkaisut.',
            'features_lead' => 'Suunnittelemme ja toteutamme asiakaskohtaiset lisäosat ja teemat modernilla teknologiapinolla.',
            'feat1_title' => 'Räätälöidyt lisäosat',
            'feat1_desc' => 'Toteutamme asiakaskohtaiset plugin-ratkaisut hyödyntäen JavaScriptiä, jQueryä, Reactia, PHP:tä ja MySQL-tietokantoja.',
            'feat2_title' => 'Tekoäly & Integraatiot',
            'feat2_desc' => 'OpenAI API -rajapinnat automaatioon ja sisällöntuotantoon. Monipuoliset API-integraatiot muihin järjestelmiin.',
            'feat3_title' => 'ACF & Monikielisyys',
            'feat3_desc' => 'Sisäänrakennettu Advanced Custom Fields (ACF) -tuki ja Polylang-monikielisyystuki vaativiin tarpeisiin.',
            'feat4_title' => 'Teemojen HTML/CSS-taitto',
            'feat4_desc' => 'Rakennamme yksilölliset ja responsiiviset WordPress-teemat, jotka näyttävät hyvältä ja toimivat nopeasti kaikilla laitteilla.',
            'feat5_title' => 'Selainkäyttöiset asetukset',
            'feat5_desc' => 'Teemoihin voidaan rakentaa selainkäyttöiset asetussivut, joiden kautta hallitset sivustosi sisältöä ja ominaisuuksia.',
            'feat6_title' => 'Salasanasuojatut asetukset',
            'feat6_desc' => 'Muokkaa väriteemoja, logoja, metatietoja tai valikoita turvallisesti suoraan selaimesta ilman hallintapaneelia.',
            'gallery_eyebrow' => 'Kuvagalleria & Näytteet',
            'gallery_title' => 'WordPress-toteutusten esimerkit.',
            'gallery_lead' => 'Tutustu toteuttamiimme räätälöityihin teemoihin, lisäosiin ja hallintapaneeleihin. Klikkaa kuvaa avataksesi sen suurempana.',
            'gallery_item1_title' => 'Räätälöity WordPress-teema ja HTML/CSS-taitto',
            'gallery_item1_desc' => 'Puhdas, moderni ja kaikilla laitteilla responsiivinen ulkoasu.',
            'gallery_item2_title' => 'ACF-kentät ja lisäosien hallintapaneeli',
            'gallery_item2_desc' => 'Helppokäyttöinen selainpohjainen sisällönhallinta ja asetussivu.',
            'gallery_item3_title' => 'OpenAI API -tekoälyautomaatio ja integraatio',
            'gallery_item3_desc' => 'Automaattinen sisällöntuotanto ja älykäs datankäsittely.',
            'gallery_item4_title' => 'Polylang-monikielisyys (FI / EN / AR)',
            'gallery_item4_desc' => 'Täysi tuki useille kielille ml. oikealta vasemmalle (RTL) luettavat kielet.',
            'lb_close' => 'Sulje',
            'lb_prev' => 'Edellinen',
            'lb_next' => 'Seuraava',
            'image_viewer' => 'Kuvakatselin',
            'why_us' => 'Miksi valita meidät?',
            'why_title' => 'Kokemus, laatu ja nopeus yhdessä.',
            'why_lead' => 'Kehitysprosessimme on hiottu huippuunsa vastaamaan moderneja vaatimuksia.',
            'why1_title' => 'Pitkä kokemus',
            'why1_desc' => 'Yli 20 vuoden kokemus web- ja ohjelmistokehityksestä takaa toimivat ratkaisut.',
            'why2_title' => 'Nopeus & Joustavuus',
            'why2_desc' => 'Tekoälyavusteinen koodin generointi tekee kehityksestä nopeaa ja erittäin kustannustehokasta.',
            'why3_title' => 'Asiakaslähtöisyys',
            'why3_desc' => 'Suunnittelemme ratkaisut juuri sinun liiketoimintasi tarpeita ja budjettia ajatellen.',
            'why4_title' => 'Turvallinen koodi',
            'why4_desc' => 'Kirjoitamme laadukasta, tietoturvallista ja helposti ylläpidettävää koodia parhaiden käytäntöjen mukaan.',
            'quote_request' => 'Tarjouspyyntö',
            'quote_title' => 'Ota yhteyttä ja pyydä tarjous WordPress-ratkaisusta.',
            'quote_desc' => 'WordPress-ratkaisu, joka palvelee liiketoimintaasi parhaalla mahdollisella tavalla!',
            'help_text' => '💡 Apua täyttöön? Soita +358 40 8200 491 tai meilaa osoitteeseen',
        ),
        'en' => array(
            'eyebrow' => 'WordPress · Open Source · Custom',
            'hero_title' => 'Professional WordPress Development.',
            'hero_desc' => 'We offer comprehensive WordPress development services tailored to your business needs. We deliver high-quality and functional solutions with years of experience.',
            'request_quote' => 'Request a quote →',
            'github_code' => 'Source Code on GitHub',
            'flexibility' => 'Flexibility & Pricing',
            'flex_title' => 'Code according to your needs and budget.',
            'flex_lead' => 'Below you will find the request for quote form. We offer services directly to end customers and as subcontracting to advertising agencies and software companies who need an experienced programmer flexibly for seasonal help, substitutes or stand-ins.',
            'flex_p1' => 'Whether you are a low-budget student entrepreneur or a wealthy long-time business owner, we will find a price that pleases the subscriber – because for just €20 + VAT 25.5% you get about €20 worth of code, and for €500 you get a corresponding value in lines of code.',
            'flex_p2' => 'This is possible because we utilize AI in code generation – for example, you can send us an image file of what you want, and we produce the solution quickly and cost-effectively.',
            'badge1' => '20+ years of experience',
            'badge2' => 'AI-Assisted',
            'badge3' => 'Flexible contractor helper',
            'features' => 'Our Services',
            'features_title' => 'Tailored WordPress Solutions.',
            'features_lead' => 'We design and implement custom plugins and themes with a modern technology stack.',
            'feat1_title' => 'Custom Plugins',
            'feat1_desc' => 'We build custom plugin solutions utilizing JavaScript, jQuery, React, PHP, and MySQL databases.',
            'feat2_title' => 'AI & Integrations',
            'feat2_desc' => 'OpenAI API interfaces for automation and content generation. Versatile API integrations with other systems.',
            'feat3_title' => 'ACF & Multilingual',
            'feat3_desc' => 'Built-in support for Advanced Custom Fields (ACF) and Polylang multilingual integration for demanding needs.',
            'feat4_title' => 'Theme HTML/CSS Layout',
            'feat4_desc' => 'We build unique and responsive WordPress themes that look great and load fast on all devices.',
            'feat5_title' => 'Browser Settings Pages',
            'feat5_desc' => 'Browser-based settings pages can be built into themes to help you easily manage content and features.',
            'feat6_title' => 'Password-Protected Settings',
            'feat6_desc' => 'Modify color themes, logos, metadata, or menus securely direct from browser without the WP admin panel.',
            'gallery_eyebrow' => 'Screenshot Gallery & Demos',
            'gallery_title' => 'WordPress Implementation Examples.',
            'gallery_lead' => 'Explore our custom themes, plugins, and administration panels. Click an image to open it in full size.',
            'gallery_item1_title' => 'Custom WordPress Theme & Responsive Layout',
            'gallery_item1_desc' => 'Clean, modern, and responsive layout for all devices.',
            'gallery_item2_title' => 'ACF Custom Fields & Plugin Admin Panel',
            'gallery_item2_desc' => 'User-friendly browser-based content management & settings page.',
            'gallery_item3_title' => 'OpenAI API AI Automation & System Integration',
            'gallery_item3_desc' => 'Automated content creation and intelligent data processing.',
            'gallery_item4_title' => 'Polylang Multilingual Support (FI / EN / AR)',
            'gallery_item4_desc' => 'Full support for multiple languages including Right-to-Left (RTL) scripts.',
            'lb_close' => 'Close',
            'lb_prev' => 'Previous',
            'lb_next' => 'Next',
            'image_viewer' => 'Image Viewer',
            'why_us' => 'Why Choose Us?',
            'why_title' => 'Experience, quality and speed combined.',
            'why_lead' => 'Our development process is optimized to meet modern requirements.',
            'why1_title' => 'Decades of Experience',
            'why1_desc' => 'Over 20 years of experience in web and software development guarantees solid and working solutions.',
            'why2_title' => 'Speed & Flexibility',
            'why2_desc' => 'AI-assisted code generation makes our development process extremely fast and cost-effective.',
            'why3_title' => 'Customer Centric',
            'why3_desc' => 'We design solutions with your specific business goals, requirements, and budget in mind.',
            'why4_title' => 'Secure Codebase',
            'why4_desc' => 'We write high-quality, highly secure, and easy-to-maintain code following WordPress standards.',
            'quote_request' => 'Request Quote',
            'quote_title' => 'Contact us and request a quote for a WordPress solution.',
            'quote_desc' => 'A WordPress solution that serves your business in the best possible way!',
            'help_text' => '💡 Need help filling in? Call +358 40 8200 491 or email us at',
        ),
        'ar' => array(
            'eyebrow' => 'ووردبريس · مصدر مفتوح · مخصص',
            'hero_title' => 'تطوير ووردبريس باحترافية عالية.',
            'hero_desc' => 'نقدم خدمات تطوير ووردبريس شاملة ومخصصة لتبتكر حلولاً تناسب احتياجات عملك. ننفذ حلولاً عالية الجودة وفعالة بخبرة سنوات.',
            'request_quote' => 'طلب عرض سعر ←',
            'github_code' => 'الكود المصدري على GitHub',
            'flexibility' => 'المرونة والأسعار',
            'flex_title' => 'كود يناسب احتياجاتك وميزانيتك.',
            'flex_lead' => 'ستجد أدناه نموذج طلب عرض السعر. نقدم الخدمات مباشرة للعملاء النهائيين وكذلك كعقود فرعية لوكالات الإعلان وشركات البرمجيات التي تحتاج إلى مبرمج ذي خبرة بمرونة للمساعدة الموسمية أو البديلة.',
            'flex_p1' => 'سواء كنت رائد أعمال ميزانيتك محدودة أو صاحب عمل مخضرم، سنجد معاً السعر الذي يرضيك – فمقابل 20 يورو + ضريبة القيمة المضافة 25.5% تحصل على كود بقيمة 20 يورو تقريباً، ومقابل 500 يورو تحصل على عدد أسطر كود يماثل هذه القيمة.',
            'flex_p2' => 'هذا ممكن لأننا نستخدم الذكاء الاصطناعي في توليد الكود – على سبيل المثال يمكنك إرسال صورة لما تريد، ونحن ننتج الحل بسرعة وبكفاءة عالية من حيث التكلفة.',
            'badge1' => 'خبرة أكثر من 20 عاماً',
            'badge2' => 'مدعوم بالذكاء الاصطناعي',
            'badge3' => 'مساعد موثوق ومستقل',
            'features' => 'خدماتنا',
            'features_title' => 'حلول ووردبريس مخصصة.',
            'features_lead' => 'نصمم وننفذ إضافات وقوالب مخصصة للعملاء باستخدام تقنيات حديثة.',
            'feat1_title' => 'إضافات مخصصة',
            'feat1_desc' => 'ننفذ إضافات برمجية مخصصة للعملاء باستخدام JavaScript وjQuery وReact وPHP وقواعد بيانات MySQL.',
            'feat2_title' => 'الذكاء الاصطناعي والتكامل',
            'feat2_desc' => 'واجهات برمجة التطبيقات OpenAI API للأتمتة وإنشاء المحتوى. تكامل شامل لـ API مع الأنظمة الأخرى.',
            'feat3_title' => 'ACF وتعدد اللغات',
            'feat3_desc' => 'دعم مدمج لـ Advanced Custom Fields (ACF) ودعم Polylang متعدد اللغات للاحتياجات المتقدمة.',
            'feat4_title' => 'تصميم قوالب HTML/CSS',
            'feat4_desc' => 'نبني قوالب ووردبريس فريدة واستجابية تبدو رائعة وتعمل بسرعة على جميع الأجهزة.',
            'feat5_title' => 'صفحات إعدادات عبر المتصفح',
            'feat5_desc' => 'يمكن بناء صفحات إعدادات عبر المتصفح في القوالب لتتمكن من إدارة المحتوى والميزات بسهولة.',
            'feat6_title' => 'إعدادات محمية بكلمة مرور',
            'feat6_desc' => 'تعديل السيمات والألوان والشعارات والبيانات الوصفية أو القوائم بأمان مباشرة من المتصفح دون لوحة تحكم WP.',
            'gallery_eyebrow' => 'معرض الصور والنماذج',
            'gallery_title' => 'أمثلة على تنفيذ ووردبريس.',
            'gallery_lead' => 'استكشف القوالب والإضافات ولوحات التحكم المخصصة التي قمنا بتنفيذها. انقر على الصورة لفتحها بحجم كامل.',
            'gallery_item1_title' => 'قالب ووردبريس مخصص وتصميم استجابي',
            'gallery_item1_desc' => 'تصميم نظيف ومودرن واستجابي لجميع الأجهزة.',
            'gallery_item2_title' => 'حقول ACF المخصصة ولواحق لوحة التحكم',
            'gallery_item2_desc' => 'إدارة محتوى وصفحة إعدادات سهلة الاستخدام عبر المتصفح.',
            'gallery_item3_title' => 'أتمتة الذكاء الاصطناعي مع OpenAI API والتكامل',
            'gallery_item3_desc' => 'إنشاء محتوى تلقائي ومعالجة بيانات ذكية.',
            'gallery_item4_title' => 'دعم Polylang متعدد اللغات (الفنلندية / الإنجليزية / العربية)',
            'gallery_item4_desc' => 'دعم كامل للغات متعددة بما في ذلك الكتابة من اليمين إلى اليسار (RTL).',
            'lb_close' => 'إغلاق',
            'lb_prev' => 'السابق',
            'lb_next' => 'التالي',
            'image_viewer' => 'عارض الصور',
            'why_us' => 'لماذا تختارنا؟',
            'why_title' => 'الخبرة والجودة والسرعة معاً.',
            'why_lead' => 'تم تحسين عملية التطوير لدينا لتلبية المتطلبات الحديثة.',
            'why1_title' => 'خبرة طويلة',
            'why1_desc' => 'أكثر من 20 عاماً من الخبرة في تطوير الويب والبرمجيات تضمن حلولاً عملية وموثوقة.',
            'why2_title' => 'السرعة والمرونة',
            'why2_desc' => 'توليد الكود بمساعدة الذكاء الاصطناعي يجعل التطوير سريعاً واقتصادياً للغاية.',
            'why3_title' => 'التركيز على العميل',
            'why3_desc' => 'نصمم الحلول مع مراعاة أهداف عملك المحددة ومتطلباتك وميزانيتك.',
            'why4_title' => 'كود آمن',
            'why4_desc' => 'نكتب كوداً عالي الجودة وآمناً للغاية وسهل الصيانة وفقاً لأفضل الممارسات.',
            'quote_request' => 'طلب عرض سعر',
            'quote_title' => 'تواصل معنا واطلب عرض سعر لحل ووردبريس.',
            'quote_desc' => 'حل ووردبريس يخدم عملك بأفضل طريقة ممكنة!',
            'help_text' => '💡 هل تحتاج مساعدة في التعبئة؟ اتصل على 491 8200 40 358+ أو أرسل بريداً إلكترونياً إلى',
        )
    );

    // Default to 'fi' if language not supported
    $s = isset($data[$lang]) ? $data[$lang] : $data['fi'];

    // Fetch Screenshots from CPT 'wordpress_screenshot' (WordPress Screenshots in admin)
    $gallery_items = array();

    $cpt_args = array(
        'post_type'      => 'wordpress_screenshot',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'orderby'        => 'menu_order title',
        'order'          => 'ASC',
    );
    if (function_exists('pll_current_language')) {
        $cpt_args['lang'] = $lang;
    }

    $cpt_query = new WP_Query($cpt_args);

    if ($cpt_query->have_posts()) {
        while ($cpt_query->have_posts()) {
            $cpt_query->the_post();
            $p_id = get_the_ID();
            $img_url = get_the_post_thumbnail_url($p_id, 'full');

            // Free ACF Image field check if Featured Image is not set
            if (!$img_url && function_exists('get_field')) {
                $acf_img = get_field('screenshot_image', $p_id);
                if ($acf_img) {
                    $img_url = is_array($acf_img) ? $acf_img['url'] : (is_numeric($acf_img) ? wp_get_attachment_url($acf_img) : $acf_img);
                }
            }

            if ($img_url) {
                $gallery_items[] = array(
                    'url'   => $img_url,
                    'title' => get_the_title(),
                    'desc'  => get_the_excerpt(),
                );
            }
        }
        wp_reset_postdata();
    }

    // Check ACF Page Fields if CPT is empty
    if (empty($gallery_items) && function_exists('get_field')) {
        $acf_repeater = get_field('wordpress_page_screenshots');
        if (!empty($acf_repeater) && is_array($acf_repeater)) {
            foreach ($acf_repeater as $row) {
                $img_url = '';
                if (!empty($row['image'])) {
                    $img_url = is_array($row['image']) ? $row['image']['url'] : (is_numeric($row['image']) ? wp_get_attachment_url($row['image']) : $row['image']);
                }
                if ($img_url) {
                    $title_key = 'title_' . $lang;
                    $desc_key = 'description_' . $lang;
                    $title = !empty($row[$title_key]) ? $row[$title_key] : (!empty($row['title']) ? $row['title'] : '');
                    $desc = !empty($row[$desc_key]) ? $row[$desc_key] : (!empty($row['description']) ? $row['description'] : '');

                    $gallery_items[] = array(
                        'url'   => $img_url,
                        'title' => $title,
                        'desc'  => $desc,
                    );
                }
            }
        }

        if (empty($gallery_items)) {
            $acf_gallery = get_field('wordpress_page_gallery');
            if (!empty($acf_gallery) && is_array($acf_gallery)) {
                foreach ($acf_gallery as $img) {
                    $img_url = is_array($img) ? $img['url'] : (is_numeric($img) ? wp_get_attachment_url($img) : $img);
                    $img_title = is_array($img) ? (!empty($img['title']) ? $img['title'] : (!empty($img['caption']) ? $img['caption'] : '')) : '';
                    $img_desc = is_array($img) ? (!empty($img['description']) ? $img['description'] : (!empty($img['alt']) ? $img['alt'] : '')) : '';

                    if ($img_url) {
                        $gallery_items[] = array(
                            'url'   => $img_url,
                            'title' => $img_title,
                            'desc'  => $img_desc,
                        );
                    }
                }
            }
        }
    }

    // Default fallback items if no ACF images uploaded
    if (empty($gallery_items)) {
        $gallery_items = array(
            array(
                'url' => get_template_directory_uri() . '/assets/businessman-working-on-tablet-using-ai.jpg',
                'title' => $s['gallery_item1_title'],
                'desc' => $s['gallery_item1_desc'],
            ),
            array(
                'url' => get_template_directory_uri() . '/assets/dreamstime_xl_153709197.jpg',
                'title' => $s['gallery_item2_title'],
                'desc' => $s['gallery_item2_desc'],
            ),
            array(
                'url' => get_template_directory_uri() . '/assets/i4ware-software-og.jpg',
                'title' => $s['gallery_item3_title'],
                'desc' => $s['gallery_item3_desc'],
            ),
            array(
                'url' => get_template_directory_uri() . '/assets/52311-background.png',
                'title' => $s['gallery_item4_title'],
                'desc' => $s['gallery_item4_desc'],
            ),
        );
    }

    ob_start();
    ?>
    <style>
        /* Screenshot Gallery Styles */
        .ok-gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 24px;
            margin-top: 32px;
        }
        .ok-gallery-card {
            background: var(--ok-panel, #0e1320);
            border: 1px solid var(--ok-border, #1e293b);
            border-radius: 16px;
            overflow: hidden;
            cursor: pointer;
            transition: transform 0.25s ease, border-color 0.25s ease, box-shadow 0.25s ease;
            display: flex;
            flex-direction: column;
        }
        .ok-gallery-card:hover {
            transform: translateY(-4px);
            border-color: var(--ok-secondary, #a855f7);
            box-shadow: 0 12px 30px rgba(168, 85, 247, 0.25);
        }
        .ok-gallery-thumb {
            position: relative;
            width: 100%;
            height: 200px;
            background: #000;
            overflow: hidden;
        }
        .ok-gallery-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        .ok-gallery-card:hover .ok-gallery-thumb img {
            transform: scale(1.05);
        }
        .ok-gallery-overlay {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(14, 19, 32, 0.6);
            backdrop-filter: blur(2px);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.25s ease;
        }
        .ok-gallery-card:hover .ok-gallery-overlay {
            opacity: 1;
        }
        .ok-gallery-info {
            padding: 20px;
            flex-grow: 1;
        }
        .ok-gallery-info h3 {
            margin: 0 0 8px 0;
            font-size: 16px;
            color: #fff;
        }
        .ok-gallery-info p {
            margin: 0;
            font-size: 13px;
            color: var(--ok-mut, #94a3b8);
            line-height: 1.5;
        }

        /* Lightbox Modal */
        .ok-lightbox {
            position: fixed;
            top: 0; left: 0; width: 100vw; height: 100vh;
            background: rgba(8, 11, 17, 0.92);
            backdrop-filter: blur(10px);
            z-index: 99999;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }
        .ok-lightbox.active {
            opacity: 1;
            pointer-events: auto;
        }
        .ok-lb-content {
            position: relative;
            max-width: 90vw;
            max-height: 85vh;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .ok-lb-content img {
            max-width: 100%;
            max-height: 75vh;
            object-fit: contain;
            border-radius: 12px;
            border: 1px solid var(--ok-border, #1e293b);
            box-shadow: 0 20px 50px rgba(0,0,0,0.8);
        }
        .ok-lb-caption {
            margin-top: 14px;
            font-size: 15px;
            color: #fff;
            text-align: center;
            max-width: 700px;
            line-height: 1.4;
            font-family: 'Outfit', sans-serif;
        }
        .ok-lb-counter {
            margin-top: 6px;
            font-size: 13px;
            color: var(--ok-mut, #94a3b8);
        }
        .ok-lb-close, .ok-lb-prev, .ok-lb-next {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #fff;
            font-size: 24px;
            border-radius: 50%;
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.2s ease, transform 0.2s ease;
            z-index: 100000;
        }
        .ok-lb-close:hover, .ok-lb-prev:hover, .ok-lb-next:hover {
            background: var(--ok-secondary, #a855f7);
            border-color: var(--ok-secondary, #a855f7);
            transform: scale(1.1);
        }
        .ok-lb-close {
            top: 24px;
            right: 24px;
        }
        .ok-lb-prev {
            left: 24px;
            top: 50%;
            transform: translateY(-50%);
        }
        .ok-lb-next {
            right: 24px;
            top: 50%;
            transform: translateY(-50%);
        }
        .ok-lb-prev:hover {
            transform: translateY(-50%) scale(1.1);
        }
        .ok-lb-next:hover {
            transform: translateY(-50%) scale(1.1);
        }
    </style>

    <div class="ok-wrap">

        <!-- HERO -->
        <section class="ok-hero">
            <span class="ok-eyebrow"><?php echo esc_html($s['eyebrow']); ?></span>
            <h1><?php echo esc_html($s['hero_title']); ?></h1>
            <p><?php echo esc_html($s['hero_desc']); ?></p>
            <div class="ok-cta">
                <a class="ok-btn ok-btn-primary" href="#tarjous"><?php echo esc_html($s['request_quote']); ?></a>
                <a class="ok-btn ok-btn-ghost" href="https://github.com/foghorn-hash/i4ware_www" target="_blank"
                    rel="noopener">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path
                            d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22">
                        </path>
                    </svg>
                    <?php echo esc_html($s['github_code']); ?>
                </a>
            </div>
            <div class="ok-links">
                <a class="ok-chip" href="https://antigravity.google/" target="_blank" rel="noopener">⚡ Antigravity</a>
                <a class="ok-chip" href="https://openai.com/" target="_blank" rel="noopener">🤖 OpenAI API</a>
                <a class="ok-chip" href="https://wordpress.org/" target="_blank" rel="noopener">💻 WordPress</a>
            </div>
        </section>

        <!-- COOPERATION / PRICING & FLEXIBILITY -->
        <section class="ok-section">
            <div class="ok-section-header">
                <span class="ok-eyebrow"><?php echo esc_html($s['flexibility']); ?></span>
                <h2><?php echo esc_html($s['flex_title']); ?></h2>
                <p class="ok-lead"><?php echo esc_html($s['flex_lead']); ?></p>
            </div>

            <div class="ok-coop-grid">
                <div class="ok-coop-info">
                    <p><?php echo esc_html($s['flex_p1']); ?></p>
                    <p><?php echo esc_html($s['flex_p2']); ?></p>
                    <div class="ok-links" style="margin-top: 20px;">
                        <a class="ok-chip" href="https://github.com/foghorn-hash/i4ware_www" target="_blank" rel="noopener">
                            💻 GitHub Source Code
                        </a>
                    </div>
                </div>
                <div class="ok-coop-badge-container">
                    <div class="ok-coop-badge">
                        <div class="ok-coop-badge-icon"></div>
                        <?php echo esc_html($s['badge1']); ?>
                    </div>
                    <div class="ok-coop-badge">
                        <div class="ok-coop-badge-icon"></div>
                        <?php echo esc_html($s['badge2']); ?>
                    </div>
                    <div class="ok-coop-badge">
                        <div class="ok-coop-badge-icon"></div>
                        <?php echo esc_html($s['badge3']); ?>
                    </div>
                </div>
            </div>
        </section>

        <!-- SERVICES / FEATURES GRID -->
        <section class="ok-section">
            <div class="ok-section-header">
                <span class="ok-eyebrow"><?php echo esc_html($s['features']); ?></span>
                <h2><?php echo esc_html($s['features_title']); ?></h2>
                <p class="ok-lead"><?php echo esc_html($s['features_lead']); ?></p>
            </div>

            <div class="ok-grid">
                <!-- Custom Plugins -->
                <div class="ok-card">
                    <div class="ok-ico">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="16 18 22 12 16 6"></polyline>
                            <polyline points="8 6 2 12 8 18"></polyline>
                        </svg>
                    </div>
                    <h3><?php echo esc_html($s['feat1_title']); ?></h3>
                    <p><?php echo esc_html($s['feat1_desc']); ?></p>
                </div>
                <!-- AI & Integrations -->
                <div class="ok-card">
                    <div class="ok-ico">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                        </svg>
                    </div>
                    <h3><?php echo esc_html($s['feat2_title']); ?></h3>
                    <p><?php echo esc_html($s['feat2_desc']); ?></p>
                </div>
                <!-- ACF & Multilingual -->
                <div class="ok-card">
                    <div class="ok-ico">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="2" y1="12" x2="22" y2="12"></line>
                            <path
                                d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z">
                            </path>
                        </svg>
                    </div>
                    <h3><?php echo esc_html($s['feat3_title']); ?></h3>
                    <p><?php echo esc_html($s['feat3_desc']); ?></p>
                </div>
                <!-- Theme Layout -->
                <div class="ok-card">
                    <div class="ok-ico">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="3" y1="9" x2="21" y2="9"></line>
                            <line x1="9" y1="21" x2="9" y2="9"></line>
                        </svg>
                    </div>
                    <h3><?php echo esc_html($s['feat4_title']); ?></h3>
                    <p><?php echo esc_html($s['feat4_desc']); ?></p>
                </div>
                <!-- Settings Pages -->
                <div class="ok-card">
                    <div class="ok-ico">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <line x1="4" y1="21" x2="4" y2="14"></line>
                            <line x1="4" y1="10" x2="4" y2="3"></line>
                            <line x1="12" y1="21" x2="12" y2="12"></line>
                            <line x1="12" y1="8" x2="12" y2="3"></line>
                            <line x1="20" y1="21" x2="20" y2="16"></line>
                            <line x1="20" y1="12" x2="20" y2="3"></line>
                            <line x1="1" y1="14" x2="7" y2="14"></line>
                            <line x1="9" y1="8" x2="15" y2="8"></line>
                            <line x1="17" y1="16" x2="23" y2="16"></line>
                        </svg>
                    </div>
                    <h3><?php echo esc_html($s['feat5_title']); ?></h3>
                    <p><?php echo esc_html($s['feat5_desc']); ?></p>
                </div>
                <!-- Password Protection -->
                <div class="ok-card">
                    <div class="ok-ico">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                    </div>
                    <h3><?php echo esc_html($s['feat6_title']); ?></h3>
                    <p><?php echo esc_html($s['feat6_desc']); ?></p>
                </div>
            </div>
        </section>

        <!-- SCREENSHOT GALLERY (LIGHTBOX) -->
        <section class="ok-section" id="galleria">
            <div class="ok-section-header">
                <span class="ok-eyebrow"><?php echo esc_html($s['gallery_eyebrow']); ?></span>
                <h2><?php echo esc_html($s['gallery_title']); ?></h2>
                <p class="ok-lead"><?php echo esc_html($s['gallery_lead']); ?></p>
            </div>

            <div class="ok-gallery-grid">
                <?php foreach ($gallery_items as $idx => $item) : 
                    $item_title = !empty($item['title']) ? $item['title'] : ($s['gallery_title'] . ' ' . ($idx + 1));
                    $item_desc = !empty($item['desc']) ? $item['desc'] : '';
                    $caption = $item_title . ($item_desc ? ' - ' . $item_desc : '');
                ?>
                <div class="ok-gallery-card" data-src="<?php echo esc_url($item['url']); ?>" data-caption="<?php echo esc_attr($caption); ?>">
                    <div class="ok-gallery-thumb">
                        <img src="<?php echo esc_url($item['url']); ?>" alt="<?php echo esc_attr($item_title); ?>" loading="lazy" />
                        <div class="ok-gallery-overlay">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                <line x1="11" y1="8" x2="11" y2="14"></line>
                                <line x1="8" y1="11" x2="14" y2="11"></line>
                            </svg>
                        </div>
                    </div>
                    <div class="ok-gallery-info">
                        <h3><?php echo esc_html($item_title); ?></h3>
                        <?php if ($item_desc) : ?>
                            <p><?php echo esc_html($item_desc); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- LIGHTBOX MODAL -->
        <div class="ok-lightbox" id="okLightbox" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr($s['image_viewer']); ?>">
            <button class="ok-lb-close" id="okLbClose" aria-label="<?php echo esc_attr($s['lb_close']); ?>">✕</button>
            <button class="ok-lb-prev" id="okLbPrev" aria-label="<?php echo esc_attr($s['lb_prev']); ?>">‹</button>
            <div class="ok-lb-content">
                <img id="okLbImg" src="" alt="" />
                <div class="ok-lb-caption" id="okLbCaption"></div>
                <div class="ok-lb-counter" id="okLbCounter"></div>
            </div>
            <button class="ok-lb-next" id="okLbNext" aria-label="<?php echo esc_attr($s['lb_next']); ?>">›</button>
        </div>

        <!-- WHY CHOOSE US -->
        <section class="ok-section">
            <div class="ok-section-header">
                <span class="ok-eyebrow"><?php echo esc_html($s['why_us']); ?></span>
                <h2><?php echo esc_html($s['why_title']); ?></h2>
                <p class="ok-lead"><?php echo esc_html($s['why_lead']); ?></p>
            </div>

            <div class="ok-grid">
                <div class="ok-card">
                    <h3><?php echo esc_html($s['why1_title']); ?></h3>
                    <p><?php echo esc_html($s['why1_desc']); ?></p>
                </div>
                <div class="ok-card">
                    <h3><?php echo esc_html($s['why2_title']); ?></h3>
                    <p><?php echo esc_html($s['why2_desc']); ?></p>
                </div>
                <div class="ok-card">
                    <h3><?php echo esc_html($s['why3_title']); ?></h3>
                    <p><?php echo esc_html($s['why3_desc']); ?></p>
                </div>
                <div class="ok-card">
                    <h3><?php echo esc_html($s['why4_title']); ?></h3>
                    <p><?php echo esc_html($s['why4_desc']); ?></p>
                </div>
            </div>
        </section>

        <!-- PRICING & ORDER FORM -->
        <section class="ok-section" id="tarjous">
            <div class="ok-section-header">
                <span class="ok-eyebrow"><?php echo esc_html($s['quote_request']); ?></span>
                <h2><?php echo esc_html($s['quote_title']); ?></h2>
                <p class="ok-lead"><?php echo esc_html($s['quote_desc']); ?></p>
            </div>

            <div class="ok-form-area">
                <div class="ok-note" style="margin-bottom: 28px;">
                    <strong><?php echo wp_kses($s['help_text'], array('strong' => array())); ?></strong> <a
                        href="mailto:matti.kiviharju@i4ware.fi">matti.kiviharju@i4ware.fi</a>.
                </div>
                <?php echo do_shortcode('[wp_quote]'); ?>
            </div>
        </section>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const galleryCards = Array.from(document.querySelectorAll('.ok-gallery-card'));
            const lightbox = document.getElementById('okLightbox');
            if (!lightbox || galleryCards.length === 0) return;

            const lbImg = document.getElementById('okLbImg');
            const lbCaption = document.getElementById('okLbCaption');
            const lbCounter = document.getElementById('okLbCounter');
            const lbClose = document.getElementById('okLbClose');
            const lbPrev = document.getElementById('okLbPrev');
            const lbNext = document.getElementById('okLbNext');

            let currentIndex = 0;

            function openLightbox(index) {
                currentIndex = index;
                const card = galleryCards[currentIndex];
                const src = card.getAttribute('data-src');
                const caption = card.getAttribute('data-caption');

                lbImg.src = src;
                lbCaption.textContent = caption;
                lbCounter.textContent = (currentIndex + 1) + ' / ' + galleryCards.length;

                lightbox.classList.add('active');
                document.body.style.overflow = 'hidden';
            }

            function closeLightbox() {
                lightbox.classList.remove('active');
                document.body.style.overflow = '';
            }

            function showPrev() {
                currentIndex = (currentIndex - 1 + galleryCards.length) % galleryCards.length;
                openLightbox(currentIndex);
            }

            function showNext() {
                currentIndex = (currentIndex + 1) % galleryCards.length;
                openLightbox(currentIndex);
            }

            galleryCards.forEach((card, idx) => {
                card.addEventListener('click', function() {
                    openLightbox(idx);
                });
            });

            if (lbClose) lbClose.addEventListener('click', closeLightbox);
            if (lbPrev) lbPrev.addEventListener('click', function(e) { e.stopPropagation(); showPrev(); });
            if (lbNext) lbNext.addEventListener('click', function(e) { e.stopPropagation(); showNext(); });

            lightbox.addEventListener('click', function(e) {
                if (e.target === lightbox) {
                    closeLightbox();
                }
            });

            document.addEventListener('keydown', function(e) {
                if (!lightbox.classList.contains('active')) return;
                if (e.key === 'Escape') closeLightbox();
                if (e.key === 'ArrowLeft') showPrev();
                if (e.key === 'ArrowRight') showNext();
            });
        });
    </script>
    <?php
    return ob_get_clean();
});

/**
 * Register WordPress Screenshots Custom Post Type [WordPress Screenshots]
 * Works 100% with Free ACF (Advanced Custom Fields free version) and Polylang translations!
 */
add_action('init', 'i4ware_register_wordpress_screenshot_cpt');
function i4ware_register_wordpress_screenshot_cpt() {
    $labels = array(
        'name'               => 'WordPress Screenshots',
        'singular_name'      => 'WordPress Screenshot',
        'menu_name'          => 'WordPress Screenshots',
        'name_admin_bar'     => 'WordPress Screenshot',
        'add_new'            => 'Add New Screenshot',
        'add_new_item'       => 'Add New Screenshot',
        'new_item'           => 'New Screenshot',
        'edit_item'          => 'Edit Screenshot',
        'view_item'          => 'View Screenshot',
        'all_items'          => 'All Screenshots',
        'search_items'       => 'Search Screenshots',
        'parent_item_colon'  => 'Parent Screenshots:',
        'not_found'          => 'No screenshots found.',
        'not_found_in_trash' => 'No screenshots found in Trash.',
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => false,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array('slug' => 'wordpress-screenshot'),
        'capability_type'    => 'post',
        'has_archive'        => false,
        'hierarchical'       => false,
        'menu_position'      => 21,
        'menu_icon'          => 'dashicons-format-gallery',
        'supports'           => array('title', 'thumbnail', 'excerpt', 'page-attributes'),
    );

    register_post_type('wordpress_screenshot', $args);
}

/**
 * Register CPT with Polylang for language translations & admin flags (FI, EN, AR)
 */
add_filter('pll_get_post_types', function($post_types) {
    $post_types['wordpress_screenshot'] = 'wordpress_screenshot';
    return $post_types;
});

