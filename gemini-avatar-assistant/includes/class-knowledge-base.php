<?php
/**
 * Knowledge Base Indexer and Search Engine
 *
 * Scans published, public WordPress content and provides relevant
 * excerpts to the Gemini assistant.
 *
 * @package Gemini_Avatar_Assistant
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Gemini_Avatar_Knowledge_Base {

	const INDEX_OPTION = 'gemini_avatar_knowledge_index';
	const INDEX_TIME_OPTION = 'gemini_avatar_knowledge_indexed_at';

	/**
	 * Build or rebuild the knowledge base index
	 *
	 * @return array
	 */
	public static function build_index() {
		$chunks = array();
		$settings = Gemini_Avatar_Settings::get_all();
		$allowed_types = ! empty( $settings['included_post_types'] ) ? (array) $settings['included_post_types'] : array();
		$home_url = home_url( '/' );

		// 1. General Company & Contact Chunks for each language (fi, en, ar)
		$company_chunks = self::build_company_chunks( $home_url );
		foreach ( $company_chunks as $cc ) {
			$chunks[] = $cc;
		}

		// 2. Structured Sitemap & Page Directory Chunks (listing real published pages and subpages)
		$sitemap_chunks = self::build_sitemap_chunks( $home_url );
		foreach ( $sitemap_chunks as $sc ) {
			$chunks[] = $sc;
		}

		// 3. Structured Commercial Chunks: Products, Pricing, Subscriptions, Advertising, Marketplace (fi, en, ar)
		$commercial_chunks = self::build_commercial_chunks( $home_url );
		foreach ( $commercial_chunks as $comm_c ) {
			$chunks[] = $comm_c;
		}

		// 3. Extra Approved Knowledge from Settings
		$extra = trim( (string) $settings['approved_extra_knowledge'] );
		if ( ! empty( $extra ) ) {
			$chunks[] = array(
				'id'       => 'extra_approved',
				'lang'     => 'fi',
				'title'    => 'Ylläpitäjän hyväksymät lisätiedot ja yritystiedot',
				'category' => 'Yleistieto',
				'url'      => $home_url,
				'content'  => $extra,
				'tokens'   => self::tokenize( 'Ylläpitäjän hyväksymät lisätiedot i4ware Software yritystiedot palvelut tuotteet ' . $extra ),
			);
		}

		// 4. Process each allowed post type (published only, all languages via Polylang)
		foreach ( $allowed_types as $post_type ) {
			$post_type = sanitize_key( $post_type );

			$query_args = array(
				'post_type'      => $post_type,
				'post_status'    => 'publish',
				'has_password'   => false,
				'posts_per_page' => -1, // Guarantee all pages, subpages and posts are indexed
				'lang'           => '', // Fetch all Polylang languages (fi, en, ar)
			);

			if ( 'page' === $post_type ) {
				$query_args['orderby'] = 'post_parent menu_order title';
				$query_args['order']   = 'ASC';
			} else {
				$query_args['orderby'] = 'date';
				$query_args['order']   = 'DESC';
			}

			$posts = get_posts( $query_args );
			foreach ( $posts as $p ) {
				$chunk = self::process_post( $p, $home_url );
				if ( ! empty( $chunk ) ) {
					$chunks[] = $chunk;
				}
			}
		}

		// Save index
		update_option( self::INDEX_OPTION, $chunks, false );
		update_option( self::INDEX_TIME_OPTION, current_time( 'mysql' ), false );

		return $chunks;
	}

	/**
	 * Build multilingual commercial chunks covering i4ware Software's verified products, services,
	 * advertising spaces, subscriptions, one-time payments, and Atlassian Marketplace offerings.
	 *
	 * @param string $home_url
	 * @return array
	 */
	public static function build_commercial_chunks( $home_url ) {
		$chunks = array();
		$langs = array( 'fi', 'en', 'ar' );

		foreach ( $langs as $lang ) {
			$lang_home_url = function_exists( 'pll_home_url' ) ? pll_home_url( $lang ) : $home_url;

			if ( 'en' === $lang ) {
				// 1. Advertising Spaces (en)
				$chunks[] = array(
					'id'       => 'commercial_advertising_' . $lang,
					'lang'     => $lang,
					'title'    => 'i4ware Software - Advertising Spaces (Commercial Advertising Service)',
					'category' => 'Advertising & Services',
					'url'      => 'https://www.i4ware.fi/#paypal-advertising',
					'content'  => "Product: Commercial advertising space on i4ware Software's website in the agreed format and placement.\n"
					. "Formats: Company logo with link, graphic banner with link, or text showcase ad with link.\n"
					. "Placements: Footer & Partners section, Sidebar / Article pages, or Frontpage / Themed pages.\n"
					. "Billing Options: One-time payment (fixed 30-day visibility, does not renew automatically) or continuous monthly subscription (ongoing visibility in 1-month billing cycles).\n"
					. "Cancellation: Monthly subscriptions can be cancelled easily at any time directly from the customer's PayPal account with no cancellation fees or notice period. Visibility continues until the end of the paid billing cycle.\n"
					. "Price & Taxes: Prices and taxes are calculated at checkout. Finnish VAT 25.5% (AVL 1 §) applies to domestic purchases. EU B2B buyers with a valid VIES VAT ID receive 0% Reverse Charge. Non-EU sales are classified as 0% tax-free export.\n"
					. "Official PDF Receipt: In addition to PayPal's receipt, i4ware sends an official numbered PDF receipt to the customer's email from the VPS server via authenticated TLS SMTP upon verified webhook payment confirmation.\n"
					. "Ordering Channel: Order directly via the secure PayPal checkout form on the website (https://www.i4ware.fi/#paypal-advertising).",
					'tokens'   => self::tokenize( 'advertising space ads banner logo subscription monthly onetime paypal receipt pdf invoice vat tax' ),
				);

				// 2. Payments & Subscriptions (en)
				$chunks[] = array(
					'id'       => 'commercial_payments_' . $lang,
					'lang'     => $lang,
					'title'    => 'i4ware Software - Monthly Subscriptions, One-Time Payments and PDF Receipts',
					'category' => 'Payments & Terms',
					'url'      => 'https://www.i4ware.fi/#paypal-advertising',
					'content'  => "Monthly Subscription: Customer receives continuous advertising visibility or service. Billed automatically in 1-month cycles via PayPal. Cancel anytime via PayPal without fees or notice period.\n"
					. "One-Time Payment: Covers a fixed period of 30 days from payment confirmation and ad approval. Does not renew automatically and does not constitute a permanent ad space.\n"
					. "Taxes: VAT is calculated at checkout based on buyer establishment and verified VAT registration. Never promise tax-free status in chat.\n"
					. "Official PDF Receipt: i4ware issues an official, sequential PDF receipt directly from the VPS server for every confirmed PayPal payment.",
					'tokens'   => self::tokenize( 'subscription monthly onetime payment paypal terms cancel receipt pdf tax vat' ),
				);

				// 3. Timesheet for Jira (en)
				$chunks[] = array(
					'id'       => 'commercial_timesheet_' . $lang,
					'lang'     => $lang,
					'title'    => 'Timesheet for Jira - Atlassian Marketplace Apps',
					'category' => 'Software & Products',
					'url'      => 'https://marketplace.atlassian.com/apps/1223446/timesheet-for-jira-for-healthcare',
					'content'  => "Product: Timesheet for Jira is an enterprise time tracking, worklog reporting, and timesheet solution for Jira Cloud and Data Center.\n"
					. "Atlassian Marketplace Apps:\n"
					. "  * Timesheet for Jira for Railroad: https://marketplace.atlassian.com/apps/3844312374/timesheet-for-jira-for-railroad (Showcase page: https://www.i4ware.fi/tuotteet/timesheet-for-jira-for-railroad/)\n"
					. "  * Timesheet for Jira for Healthcare: https://marketplace.atlassian.com/apps/1223446/timesheet-for-jira-for-healthcare (Showcase page: https://www.i4ware.fi/tuotteet/timesheet-for-jira-for-healthcare/)\n"
					. "  * Timesheet for Jira for Cities: https://marketplace.atlassian.com/apps/942289713/timesheet-for-jira-for-cities\n"
					. "Features: Fast Excel-style time entry grid (10x faster logging), real-time timers, worklog approvals, export for invoicing, and comprehensive reporting.\n"
					. "PURCHASING CHANNEL: All Timesheet for Jira apps are purchased and licensed exclusively via the Atlassian Marketplace. They are NOT purchased through i4ware's PayPal form! A free 30-day trial is available on the Marketplace.",
					'tokens'   => self::tokenize( 'timesheet jira atlassian marketplace railroad healthcare cities time tracking worklogs buy purchase license app' ),
				);

				// 4. Web Hosting (en)
				$chunks[] = array(
					'id'       => 'commercial_hosting_' . $lang,
					'lang'     => $lang,
					'title'    => 'i4ware Software - Web Hosting for WordPress and PHP',
					'category' => 'Hosting & Infrastructure',
					'url'      => 'https://www.i4ware.fi/en/services/web-hosting-service-wordpress-open-source-php-hosting/',
					'content'  => "Service: High-performance, secure web hosting for WordPress and PHP applications in the Leaseweb data center in Amsterdam.\n"
					. "Product Page: https://www.i4ware.fi/en/services/web-hosting-service-wordpress-open-source-php-hosting/\n"
					. "Price: 20 € / month + VAT 25.5% (billed annually, payment term 14 days net).\n"
					. "Includes: Unlimited MySQL and PostgreSQL databases, unlimited email mailboxes, SpamAssassin & Abusix AI spam filtering, ClamAV antivirus scanning, and Let’s Encrypt SSL/TLS certificates (SSL Labs Grade A/A+).\n"
					. "Security: FTP credentials are not distributed for security reasons; software and configurations are securely set up on your behalf.\n"
					. "Ordering: Request a quote or contact info@i4ware.fi or call +358 40 820 0691.",
					'tokens'   => self::tokenize( 'web hosting wordpress php leaseweb databases email price 20 euro quote order' ),
				);

				// 5. Custom Software & WordPress Development (en)
				$chunks[] = array(
					'id'       => 'commercial_dev_' . $lang,
					'lang'     => $lang,
					'title'    => 'i4ware Software - Custom Software, WordPress and Atlassian Consulting',
					'category' => 'Services & Development',
					'url'      => 'https://www.i4ware.fi/en/services/wordpress-development/',
					'content'  => "Services: Custom web applications (Laravel, React, Node.js), WordPress development (https://www.i4ware.fi/en/services/wordpress-development/), plugins and themes, system integrations (ERP, CRM, API), Atlassian Forge apps, and AI/LLM automation solutions.\n"
					. "Pricing: Project-based quotes tailored to client requirements.\n"
					. "Contact & Quotes: email info@i4ware.fi or phone +358 40 820 0691 (Contact page: https://www.i4ware.fi/en/company/contact-us-team/).",
					'tokens'   => self::tokenize( 'software development wordpress laravel react integration atlassian forge ai consulting quote' ),
				);

			} elseif ( 'ar' === $lang ) {
				// 1. Advertising Spaces (ar)
				$chunks[] = array(
					'id'       => 'commercial_advertising_' . $lang,
					'lang'     => $lang,
					'title'    => 'i4ware Software - المساحات الإعلانية (خدمة إعلانية مدفوعة)',
					'category' => 'المساحات الإعلانية والخدمات',
					'url'      => 'https://www.i4ware.fi/#paypal-advertising',
					'content'  => "المنتج: مساحة إعلانية تجارية مدفوعة على موقع i4ware Software بالشكل والموضع المتفق عليهما.\n"
					. "أشكال الإعلانات: شعار الشركة مع رابط، أو بانر رسومي مع رابط، أو إعلان نصي تعريفي مع رابط.\n"
					. "مواقع الظهور: قسم التذييل والشركاء (Footer & Partners)، الشريط الجانبي / صفحات المقالات، أو الصفحة الرئيسية / الصفحات المواضيعية.\n"
					. "خيارات الدفع: دفعة واحدة (مدة محددة 30 يوماً، لا تتجدد تلقائياً) أو اشتراك شهري مستمر (ظهور مستمر بفترات شهرية).\n"
					. "إلغاء الاشتراك: يمكن إلغاء الاشتراك الشهري في أي وقت وبكل سهولة مباشرة من حساب PayPal الخاص بالعميل دون أي رسوم إضافية أو فترة إشعار، ويستمر ظهور الإعلان حتى نهاية الفترة المدفوعة.\n"
					. "الأسعار والضرائب: تُحسب الأسعار والضرائب عند الدفع في الكاشير. تُطبق ضريبة القيمة المضافة الفنلندية 25.5% (AVL 1 §) داخل فنلندا. وتُطبق آلية التكليف العكسي 0% لشركات الاتحاد الأوروبي التي تمتلك معرّف VIES صالحاً. المبيعات خارج الاتحاد الأوروبي معفاة بنسبة 0% كصادرات.\n"
					. "إيصال PDF رسمي: بالإضافة إلى إشعار PayPal، ترسل i4ware إيصال PDF رسمياً مرقماً متسلسلاً إلى بريد العميل من خادم VPS عبر TLS SMTP بمجرد تأكيد الـ webhook بنجاح الدفع.\n"
					. "طريقة الطلب: الطلب عبر نموذج كاشير PayPal الآمن على الموقع (https://www.i4ware.fi/#paypal-advertising).",
					'tokens'   => self::tokenize( 'مساحات إعلانية إعلانات بانر شعار اشتراك شهري دفعة واحدة باي بال إيصال ضريبة فاتورة paypal' ),
				);

				// 2. Payments & Subscriptions (ar)
				$chunks[] = array(
					'id'       => 'commercial_payments_' . $lang,
					'lang'     => $lang,
					'title'    => 'i4ware Software - الاشتراكات الشهرية، والدفع لمرة واحدة، وإيصالات PDF',
					'category' => 'المدفوعات والشروط',
					'url'      => 'https://www.i4ware.fi/#paypal-advertising',
					'content'  => "الاشتراك الشهري: يحصل العميل على ظهور إعلاني مستمر أو خدمة متجددة تلقائياً كل شهر عبر PayPal. يمكن الإلغاء في أي وقت عبر حساب PayPal دون رسوم.\n"
					. "الدفعة لمرة واحدة: تغطي فترة محددة مدتها 30 يوماً تبدأ بعد تأكيد الدفع واعتماد المواد الإعلانية. لا تتجدد تلقائياً ولا تعني حجزاً دائماً للمساحة.\n"
					. "الضرائب: تُحسب ضريبة القيمة المضافة عند الدفع بناءً على مقر العميل وحالته الضريبية. لا نعد بالإعفاء الضريبي في المحادثة دون التحقق في الكاشير.\n"
					. "إيصال PDF رسمي: تصدر i4ware إيصال PDF رسمياً برقم تسلسلي مباشرة من خادم VPS لكل عملية دفع مؤكدة عبر PayPal.",
					'tokens'   => self::tokenize( 'اشتراك شهري دفعة واحدة إلغاء باي بال شروط إيصال ضريبة paypal pdf' ),
				);

				// 3. Timesheet for Jira (ar)
				$chunks[] = array(
					'id'       => 'commercial_timesheet_' . $lang,
					'lang'     => $lang,
					'title'    => 'Timesheet for Jira - تطبيقات Atlassian Marketplace',
					'category' => 'البرمجيات والمنتجات',
					'url'      => 'https://marketplace.atlassian.com/apps/1223446/timesheet-for-jira-for-healthcare',
					'content'  => "المنتج: Timesheet for Jira هو حل متقدم لتسجيل ومتابعة ساعات العمل وتقارير الدوام لـ Jira Cloud و Data Center.\n"
					. "تطبيقات Atlassian Marketplace:\n"
					. "  * Timesheet for Jira for Railroad: https://marketplace.atlassian.com/apps/3844312374/timesheet-for-jira-for-railroad (صفحة المنتج: https://www.i4ware.fi/tuotteet/timesheet-for-jira-for-railroad/)\n"
					. "  * Timesheet for Jira for Healthcare: https://marketplace.atlassian.com/apps/1223446/timesheet-for-jira-for-healthcare (صفحة المنتج: https://www.i4ware.fi/ar/%d8%a7%d9%84%d9%85%d9%86%d8%aa%d8%ac%d8%a7%d8%aa/timesheet-for-jira-for-healthcare/)\n"
					. "  * Timesheet for Jira for Cities: https://marketplace.atlassian.com/apps/942289713/timesheet-for-jira-for-cities\n"
					. "الميزات: شبكة إدخال سريعة شبيهة بـ Excel، مؤقتات زمنية حية، مسارات عمل لاعتماد السجلات، تصدير الفواتير، وتقارير شاملة.\n"
					. "قناة الشراء الحصرية: يتم شراء وترخيص جميع تطبيقات Timesheet for Jira حصرياً ومباشرة عبر Atlassian Marketplace. ولا يتم شراؤها عبر نموذج PayPal الخاص بموقع i4ware! يتوفر إصدار تجريبي مجاني لمدة 30 يوماً على Marketplace.",
					'tokens'   => self::tokenize( 'timesheet jira atlassian marketplace railroad healthcare cities تسجيل ساعات العمل دوام شراء ترخيص تطبيق' ),
				);

				// 4. Web Hosting (ar)
				$chunks[] = array(
					'id'       => 'commercial_hosting_' . $lang,
					'lang'     => $lang,
					'title'    => 'i4ware Software - استضافة المواقع وتطبيقات WordPress و PHP',
					'category' => 'الاستضافة والبنية التحتية',
					'url'      => 'https://www.i4ware.fi/ar/%d8%a7%d9%84%d8%ae%d8%af%d9%85%d8%a7%d8%aa/%d8%ae%d8%af%d9%85%d8%a9-%d8%a7%d8%b3%d8%aa%d8%b6%d8%a7%d9%81%d8%a9-%d8%a7%d9%84%d9%88%d9%8a%d8%a8/',
					'content'  => "الخدمة: استضافة ويب عالية الأداء وآمنة لتطبيقات WordPress و PHP في مركز بيانات Leaseweb في أمستردام.\n"
					. "صفحة الخدمة: https://www.i4ware.fi/ar/%d8%a7%d9%84%d8%ae%d8%af%d9%85%d8%a7%d8%aa/%d8%ae%d8%af%d9%85%d8%a9-%d8%a7%d8%b3%d8%aa%d8%b6%d8%a7%d9%81%d8%a9-%d8%a7%d9%84%d9%88%d9%8a%d8%a8/\n"
					. "السعر: 20 يورو شهرياً + ضريبة القيمة المضافة 25.5% (دورة الفوترة سنوية، ومدة السداد 14 يوماً صافية).\n"
					. "تشمل الخدمة: قواعد بيانات MySQL و PostgreSQL غير محدودة، وصناديق بريد إلكتروني غير محدودة، وفلترة الرسائل المزعجة عبر SpamAssassin و Abusix AI، وفحص الفيروسات عبر ClamAV، وشهادات أمان Let’s Encrypt SSL/TLS (تصنيف SSL Labs Grade A/A+).\n"
					. "الأمان: لا يتم تسليم بيانات FTP لأسباب أمنية؛ نقوم بتثبيت وضبط البرمجيات والإعدادات بشكل آمن نيابة عنك.\n"
					. "الطلب: اطلب عرض أسعار عبر info@i4ware.fi أو الهاتف +358 40 820 0691.",
					'tokens'   => self::tokenize( 'استضافة مواقع ووردبريس لينكس خادم قواعد بيانات بريد سعر 20 يورو طلب عرض أسعار leaseweb' ),
				);

				// 5. Custom Software & WordPress Development (ar)
				$chunks[] = array(
					'id'       => 'commercial_dev_' . $lang,
					'lang'     => $lang,
					'title'    => 'i4ware Software - تطوير البرمجيات المخصصة وحلول WordPress والاستشارات',
					'category' => 'الخدمات والتطوير',
					'url'      => 'https://www.i4ware.fi/ar/%d8%a7%d9%84%d8%ae%d8%af%d9%85%d8%a7%d8%aa/%d8%aa%d8%b7%d9%88%d9%8a%d8%b1-wordpress/',
					'content'  => "الخدمات: تطبيقات ويب مخصصة (Laravel, React, Node.js)، وتطوير WordPress (https://www.i4ware.fi/ar/%d8%a7%d9%84%d8%ae%d8%af%d9%85%d8%a7%d8%aa/%d8%aa%d8%b7%d9%88%d9%8a%d8%b1-wordpress/)، إضافات وقوالب، تكامل الأنظمة (ERP, CRM, API)، وتطبيقات Atlassian Forge، وحلول الذكاء الاصطناعي.\n"
					. "التسعير: عروض أسعار تعتمد على متطلبات وحجم المشروع.\n"
					. "التواصل وعروض الأسعار: البريد الإلكتروني info@i4ware.fi أو الهاتف +358 40 820 0691 (صفحة التواصل: https://www.i4ware.fi/ar/%d8%b4%d8%b1%d9%83%d8%a9/%d8%a8%d9%8a%d8%a7%d9%86%d8%a7%d8%aa-%d8%a7%d9%84%d8%a7%d8%aa%d8%b5%d8%a7%d9%84-%d8%a7%d9%84%d9%81%d8%b1%d9%8a%d9%82/).",
					'tokens'   => self::tokenize( 'تطوير برمجيات برمجة ووردبريس تكامل أنظمة لارفيل رياكت ذكاء اصطناعي عرض أسعار استشارات' ),
				);

			} else {
				// 1. Mainospaikat (fi)
				$chunks[] = array(
					'id'       => 'commercial_advertising_' . $lang,
					'lang'     => $lang,
					'title'    => 'i4ware Software - Mainospaikat verkkosivustolla (Vastikkeellinen mainospalvelu)',
					'category' => 'Mainospalvelu & Tilaukset',
					'url'      => 'https://www.i4ware.fi/#paypal-advertising',
					'content'  => "Tuote: Vastikkeellinen kaupallinen mainospaikka i4ware Softwaren verkkosivustolla sovitussa muodossa ja sijoittelussa.\n"
						. "Mainosmuodot: Yrityksen logo linkillä, graafinen banneri linkillä tai tekstimainos esittelykappaleella ja linkillä.\n"
						. "Sijoituspaikat: Alatunniste & Kumppanit -osio, sivupalkki / artikkelisivut tai pääsivu / teemasivut.\n"
						. "Tilausvaihtoehdot: Kertamaksu (määräaikainen 30 vuorokauden näkyvyys, ei uusiudu automaattisesti) tai jatkuva kuukausitilaus (jatkuva näkyvyys 1 kuukauden laskutusjaksoissa).\n"
						. "Kuukausitilauksen peruuttaminen: Kuukausitilauksen voi peruuttaa milloin tahansa helposti omalta PayPal-tililtä ilman lisäkuluja tai irtisanomisaikaa. Peruutuksen jälkeen mainosnäkyvyys säilyy maksetun laskutuskauden loppuun saakka.\n"
						. "Kertamaksun ehdot: Kertamaksu koskee määräaikaista 30 vuorokauden jaksoa maksun ja aineiston vahvistumisesta. Se ei tarkoita pysyvää mainospaikkaa eikä uusiudu automaattisesti.\n"
						. "Aineiston toimitus: Logo, banneri tai teksti sekä kohdelinkki toimitetaan kassalomakkeella tai sähköpostitse osoitteeseen info@i4ware.fi. Aineisto tarkistetaan ja julkaistaan 1–2 arkipäivässä hyväksymisestä.\n"
						. "Hinnat ja verot: Hinta ja soveltuva arvonlisävero vahvistetaan ja lasketaan kassalla. Kotimaassa hintaan lisätään Suomen ALV 25,5 % (AVL 1 §). EU-yrityksille ALV 0 % käännetty verovelvollisuus edellyttää voimassa olevaa VIES ALV-tunnistetta. EU:n ulkopuolelle myynti on ALV 0 % vientiä.\n"
						. "Virallinen PDF-kuitti: Jokaisesta onnistuneesta maksusta lähetetään PayPalin oman maksuvahvistuksen lisäksi i4ware Softwaren virallinen PDF-kuitti asiakkaan sähköpostiin i4waren VPS-palvelimelta autentikoidun TLS SMTP:n kautta heti, kun PayPalin webhook vahvistaa maksun.\n"
						. "Tilauskanava: Tilataan suoraan verkkosivuston suojatulla PayPal-kassalomakkeella (https://www.i4ware.fi/#paypal-advertising). Mainospaikka on vastikkeellinen kaupallinen palvelu sovituin näkyvyysehdoin.",
					'tokens'   => self::tokenize( 'mainospaikka mainos mainospalvelu banneri logo osta tilaa kuukausitilaus kertamaksu peruutus 30 päivää paypal kuitti pdf alv vero' ),
				);

				// 2. Maksuehdot ja kuitit (fi)
				$chunks[] = array(
					'id'       => 'commercial_payments_' . $lang,
					'lang'     => $lang,
					'title'    => 'i4ware Software - Kuukausitilaukset, PayPal-kertamaksut ja PDF-kuitti',
					'category' => 'Maksutavat & Ehdot',
					'url'      => 'https://www.i4ware.fi/#paypal-advertising',
					'content'  => "Kuukausitilaus: Asiakas saa jatkuvan mainosnäkyvyyden tai palvelun automaattisesti uusiutuvalla 1 kuukauden laskutusjaksolla PayPalin kautta. Voidaan peruuttaa milloin tahansa omalta PayPal-tililtä ilman kuluja tai irtisanomisaikaa. Näkyvyys jatkuu maksetun kauden loppuun saakka.\n"
						. "Kertamaksu: Asiakas maksaa määräaikaisesta 30 päivää (30 vrk) kestävästä palvelusta tai näkyvyydestä hyväksymisestä alkaen. Ei uusiudu automaattisesti eikä tarkoita pysyvää mainospaikkaa.\n"
						. "Arvonlisävero: Vero määräytyy asiakkaan sijoittautumismaan ja ALV-statuksen mukaan kassalla (Suomi 25,5 %, EU B2B 0 %, non-EU 0 %). Älä lupaa verotonta myyntiä keskustelussa ilman kassatarkistusta.\n"
						. "PDF-kuitti: i4ware toimittaa PayPalin maksuvahvistuksen lisäksi oman virallisen PDF-kuitin juoksevalla tositenumerolla asiakkaan sähköpostiin VPS-palvelimelta autentikoidun TLS SMTP -yhteyden kautta heti webhook-vahvistuksen jälkeen.",
					'tokens'   => self::tokenize( 'kuukausitilaus kertamaksu paypal automaattisesti uusiutuva peruuttaa peruutus 30 päivää alv vero pdf kuitti tositenumero laskutus' ),
				);

				// 3. Timesheet for Jira (fi)
				$chunks[] = array(
					'id'       => 'commercial_timesheet_' . $lang,
					'lang'     => $lang,
					'title'    => 'Timesheet for Jira - Atlassian Marketplace -sovellukset',
					'category' => 'Ohjelmistot & Tuotteet',
					'url'      => 'https://marketplace.atlassian.com/apps/1223446/timesheet-for-jira-for-healthcare',
					'content'  => "Tuote: Timesheet for Jira on huippunopea työajanseuranta-, raportointi- ja tuntikirjauslisäosa Jira Cloud ja Data Center -alustoille.\n"
						. "Atlassian Marketplace -sovellukset:\n"
						. "  * Timesheet for Jira for Railroad: https://marketplace.atlassian.com/apps/3844312374/timesheet-for-jira-for-railroad (sivuston esittelysivu: https://www.i4ware.fi/tuotteet/timesheet-for-jira-for-railroad/)\n"
						. "  * Timesheet for Jira for Healthcare: https://marketplace.atlassian.com/apps/1223446/timesheet-for-jira-for-healthcare (sivuston esittelysivu: https://www.i4ware.fi/tuotteet/timesheet-for-jira-for-healthcare/)\n"
						. "  * Timesheet for Jira for Cities: https://marketplace.atlassian.com/apps/942289713/timesheet-for-jira-for-cities\n"
						. "Ominaisuudet: Excel-tyylinen nopea näppäimistöruudukko (10x nopeampi kirjaus), reaaliaikaiset ajastimet, Jira Service Management -tuki, hyväksyntätyönkulut, laskutettavien tuntien vienti ja yritystason raportit.\n"
						. "TÄRKEÄ OSTOPAIKKA: Kaikki Timesheet for Jira -sovellukset ostetaan ja lisensoidaan suoraan **Atlassian Marketplacesta**. Niitä EI osteta i4waren omalla PayPal-lomakkeella! Marketplacesta on saatavilla ilmainen 30 päivän kokeiluversio.",
					'tokens'   => self::tokenize( 'timesheet jira atlassian marketplace railroad healthcare cities työajanseuranta tuntikirjaus sovellus osta lisenssi kokeilu' ),
				);

				// 4. Web-hotellipalvelu (fi)
				$chunks[] = array(
					'id'       => 'commercial_hosting_' . $lang,
					'lang'     => $lang,
					'title'    => 'i4ware Software - Web-hotellipalvelu WordPress- ja PHP-sivustoille',
					'category' => 'Web-hotelli & Ylläpito',
					'url'      => 'https://www.i4ware.fi/palvelut/web-hotellipalvelu-wordpress-ja-open-source-php-sovellusten-hostaus/',
					'content'  => "Palvelu: Luotettava ja nopea web-hotellipalvelu WordPressille ja avoimen lähdekoodin PHP-sovelluksille Leasewebin suuressa datakeskuksessa Amsterdamissa.\n"
						. "Esittelysivu: https://www.i4ware.fi/palvelut/web-hotellipalvelu-wordpress-ja-open-source-php-sovellusten-hostaus/\n"
						. "Hinta: 20 € / kk + ALV 25,5 % (laskutusväli 1 vuosi, maksuehto 14 pv netto).\n"
						. "Sisältää: Rajattomat MySQL- ja PostgreSQL-tietokannat, rajattomat sähköpostilaatikot omalla verkkotunnuksella, SpamAssassin & Abusix AI -roskapostisuodattimen, ClamAV-virusskannauksen ja ilmaisen Let’s Encrypt SSL/TLS -salauksen (SSL Labs Grade A/A+).\n"
						. "Tietoturvakäytäntö: FTP-tunnuksia ei luovuteta tietoturvasyistä; asennukset ja päivitykset hoidetaan turvallisesti puolestasi.\n"
						. "Tilaaminen: Tilaa ottamalla yhteyttä tai pyytämällä tarjous: info@i4ware.fi tai puhelimitse +358 40 820 0691.",
					'tokens'   => self::tokenize( 'webhotelli web hotelli wordpress php leaseweb sähköposti tietokanta hinta 20 euroa tarjous ylläpito' ),
				);

				// 5. Räätälöidyt ohjelmistot ja kehitys (fi)
				$chunks[] = array(
					'id'       => 'commercial_dev_' . $lang,
					'lang'     => $lang,
					'title'    => 'i4ware Software - Räätälöity ohjelmistokehitys ja WordPress-kehitys',
					'category' => 'Palvelut & Kehitys',
					'url'      => 'https://www.i4ware.fi/palvelut/wordpress-kehitys/',
					'content'  => "Palvelut: Räätälöidyt verkkosovellukset (Laravel, React, Node.js), WordPress-kehitys (https://www.i4ware.fi/palvelut/wordpress-kehitys/), lisäosat ja teemat, järjestelmäintegraatiot (ERP, CRM, API-rajapinnat), Atlassian Forge Cloud -lisäosat ja räätälöidyt tekoälyratkaisut.\n"
						. "Hinnoittelu: Projektikohtainen tarjous asiakkaan tavoitteiden ja laajuuden mukaisesti.\n"
						. "Yhteydenotto: Pyydä tarjous tai ota yhteyttä: sähköposti info@i4ware.fi tai puhelin +358 40 820 0691 (Yhteystiedot ja tiimi: https://www.i4ware.fi/yritys/yhteystiedot-ja-tiimi/).",
					'tokens'   => self::tokenize( 'ohjelmistokehitys wordpress räätälöity lisäosa teema integraatio erp crm atlassian forge tarjous' ),
				);
			}
		}

		return $chunks;
	}

	/**
	 * Build multilingual company overview and contact chunks (fi, en, ar)
	 *
	 * @param string $home_url
	 * @return array
	 */
	public static function build_company_chunks( $home_url ) {
		$company_chunks = array();
		$langs = array( 'fi', 'en', 'ar' );

		foreach ( $langs as $lang ) {
			if ( 'en' === $lang ) {
				$title    = 'i4ware Software - Company Overview, Team and Contact Details';
				$category = 'Company & Contact';
				$url      = 'https://www.i4ware.fi/en/company/contact-us-team/';
				$content  = "Company: i4ware Software\n"
					. "Founder & Lead Architect: Matti Kiviharju (Software Architect, Full Stack Developer, Entrepreneur)\n"
					. "Business ID (Y-tunnus): 2739594-6\n"
					. "VAT Identification (ALV): FI27395946\n"
					. "Address: Kasarmikatu 15, 13100 Hämeenlinna, Finland\n"
					. "Email: info@i4ware.fi\n"
					. "Phone: +358 40 820 0691\n"
					. "Official Website: https://www.i4ware.fi/en/home-english/\n"
					. "Contact & Team Page: https://www.i4ware.fi/en/company/contact-us-team/\n"
					. "Core Offerings: Atlassian Marketplace apps (Timesheet for Jira for Railroad, Healthcare, Cities), cloud products (i4ware SDK, AI Assistant, Invoicing Cloud, Job Seeker Autopilot), premium web hosting in Leaseweb Amsterdam, custom WordPress and software development, and website advertising spaces.";
			} elseif ( 'ar' === $lang ) {
				$title    = 'i4ware Software - نبذة عن الشركة، الفريق وبيانات التواصل';
				$category = 'الشركة وبيانات الاتصال';
				$url      = 'https://www.i4ware.fi/ar/%d8%b4%d8%b1%d9%83%d8%a9/%d8%a8%d9%8a%d8%a7%d9%86%d8%a7%d8%aa-%d8%a7%d9%84%d8%a7%d8%aa%d8%b5%d8%a7%d9%84-%d8%a7%d9%84%d9%81%d8%b1%d9%8a%d9%82/';
				$content  = "الشركة: i4ware Software\n"
					. "المؤسس وكبير المهندسين: ماتي كيفيهاريو Matti Kiviharju (مهندس برمجيات، مطور Full Stack، ورائد أعمال)\n"
					. "رقم السجل التجاري (Y-tunnus): 2739594-6\n"
					. "الرقم الضريبي (VAT): FI27395946\n"
					. "العنوان: Kasarmikatu 15, 13100 Hämeenlinna, فنلندا\n"
					. "البريد الإلكتروني: info@i4ware.fi\n"
					. "الهاتف: +358 40 820 0691\n"
					. "الموقع الرسمي: https://www.i4ware.fi/ar/%d8%a7%d9%84%d8%b5%d9%81%d8%ad%d8%a9-%d8%a7%d9%84%d8%b1%d8%a6%d9%8a%d8%b3%d9%8a%d8%a9/\n"
					. "صفحة الاتصال والفريق: https://www.i4ware.fi/ar/%d8%b4%d8%b1%d9%83%d8%a9/%d8%a8%d9%8a%d8%a7%d9%86%d8%a7%d8%aa-%d8%a7%d9%84%d8%a7%d8%aa%d8%b5%d8%a7%d9%84-%d8%a7%d9%84%d9%81%d8%b1%d9%8a%d9%82/\n"
					. "المنتجات والخدمات الرئيسية: تطبيقات Atlassian Marketplace (Timesheet for Jira for Railroad, Healthcare, Cities)، والحلول السحابية (i4ware SDK, AI Assistant, Invoicing Cloud, Job Seeker Autopilot)، واستضافة المواقع الآمنة، وتطوير ووردبريس، والمساحات الإعلانية.";
			} else {
				$title    = 'i4ware Software - Yritystiedot, tiimi ja yhteystiedot';
				$category = 'Yritys & Yhteystiedot';
				$url      = 'https://www.i4ware.fi/yritys/yhteystiedot-ja-tiimi/';
				$content  = "Yritys: i4ware Software\n"
					. "Perustaja ja pääkehittäjä: Matti Kiviharju (ohjelmistoarkkitehti, Full Stack -kehittäjä, yrittäjä)\n"
					. "Y-tunnus: 2739594-6\n"
					. "ALV-tunniste: FI27395946\n"
					. "Käynti- ja postiosoite: Kasarmikatu 15, 13100 Hämeenlinna, Suomi\n"
					. "Sähköposti: info@i4ware.fi\n"
					. "Puhelin: +358 40 820 0691\n"
					. "Virallinen verkkosivusto: https://www.i4ware.fi/\n"
					. "Yhteystiedot & Tiimi -sivu: https://www.i4ware.fi/yritys/yhteystiedot-ja-tiimi/\n"
					. "Tuotteet ja ydinpalvelut: Atlassian Marketplace -sovellukset (Timesheet for Jira for Railroad, Healthcare, Cities), pilviohjelmistot (i4ware SDK, AI Assistant, Laskutuspilvi, Job Seeker Autopilot), Leaseweb Amsterdamissa toimiva nopea web-hotellipalvelu, räätälöity ohjelmisto- ja WordPress-kehitys sekä verkkosivuston mainospaikat.";
			}

			$company_chunks[] = array(
				'id'       => 'company_overview_' . $lang,
				'lang'     => $lang,
				'title'    => $title,
				'category' => $category,
				'url'      => $url,
				'content'  => $content,
				'tokens'   => self::tokenize( $title . ' ' . $content ),
			);
		}

		return $company_chunks;
	}

	/**
	 * Build multilingual directory chunks listing all published, active pages and subpages on i4ware.fi
	 *
	 * @param string $home_url
	 * @return array
	 */
	public static function build_sitemap_chunks( $home_url ) {
		$sitemap_chunks = array();
		$langs = array( 'fi', 'en', 'ar' );

		foreach ( $langs as $lang ) {
			if ( 'en' === $lang ) {
				$title   = 'i4ware Software - Published Pages and Subpages Directory';
				$url     = 'https://www.i4ware.fi/en/home-english/';
				$content = "Official pages and subpages of i4ware Software:\n"
					. "1. Home: https://www.i4ware.fi/en/home-english/\n"
					. "2. Products (Main: https://www.i4ware.fi/en/products/):\n"
					. "   - i4ware SDK: https://www.i4ware.fi/en/products/i4ware-sdk/\n"
					. "   - i4ware AI Assistant: https://www.i4ware.fi/en/products/multi-user-and-multi-organization-real-time-ai-chat/\n"
					. "   - i4ware Invoicing Cloud: https://www.i4ware.fi/en/products/i4ware-invoice-cloud-ms-excel-invoice-template-pdf/\n"
					. "   - i4ware Job Seeker Autopilot AI: https://www.i4ware.fi/en/products/i4ware-job-seeker-autopilot-ai-life-cycle-management-system/\n"
					. "3. Services (Main: https://www.i4ware.fi/en/services/):\n"
					. "   - Web Hosting Service: https://www.i4ware.fi/en/services/web-hosting-service-wordpress-open-source-php-hosting/\n"
					. "   - WordPress Development: https://www.i4ware.fi/en/services/wordpress-development/\n"
					. "4. Company (Main: https://www.i4ware.fi/en/company/):\n"
					. "   - Contact Us & Team: https://www.i4ware.fi/en/company/contact-us-team/\n"
					. "   - Blog: https://www.i4ware.fi/en/company/blog/\n"
					. "   - Partnerships: https://www.i4ware.fi/en/company/partnerships/\n"
					. "   - Customers: https://www.i4ware.fi/en/company/customers/\n"
					. "   - Careers: https://www.i4ware.fi/en/company/career/\n"
					. "   - Investors: https://www.i4ware.fi/en/company/investors/\n"
					. "5. Shop & Legal:\n"
					. "   - Shop: https://www.i4ware.fi/en/shop/\n"
					. "   - Advertising Space PayPal Form: https://www.i4ware.fi/#paypal-advertising\n"
					. "   - Order and Delivery Terms: https://www.i4ware.fi/en/order-and-delivery-terms/\n"
					. "   - Privacy Policy: https://www.i4ware.fi/en/privacy-policy/\n"
					. "6. Atlassian Marketplace Apps:\n"
					. "   - Timesheet for Jira for Railroad: https://marketplace.atlassian.com/apps/3844312374/timesheet-for-jira-for-railroad\n"
					. "   - Timesheet for Jira for Healthcare: https://marketplace.atlassian.com/apps/1223446/timesheet-for-jira-for-healthcare\n"
					. "   - Timesheet for Jira for Cities: https://marketplace.atlassian.com/apps/942289713/timesheet-for-jira-for-cities";
			} elseif ( 'ar' === $lang ) {
				$title   = 'i4ware Software - دليل الصفحات والصفحات الفرعية المنشورة';
				$url     = 'https://www.i4ware.fi/ar/%d8%a7%d9%84%d8%b5%d9%81%d8%ad%d8%a9-%d8%a7%d9%84%d8%b1%d8%a6%d9%8a%d8%b3%d9%8a%d8%a9/';
				$content = "الصفحات والصفحات الفرعية الرسمية لموقع i4ware Software:\n"
					. "1. الصفحة الرئيسية: https://www.i4ware.fi/ar/%d8%a7%d9%84%d8%b5%d9%81%d8%ad%d8%a9-%d8%a7%d9%84%d8%b1%d8%a6%d9%8a%d8%b3%d9%8a%d8%a9/\n"
					. "2. المنتجات (الرئيسية: https://www.i4ware.fi/ar/%d8%a7%d9%84%d9%85%d9%86%d8%aa%d8%ac%d8%a7%d8%aa/):\n"
					. "   - Timesheet for Jira for Healthcare: https://www.i4ware.fi/ar/%d8%a7%d9%84%d9%85%d9%86%d8%aa%d8%ac%d8%a7%d8%aa/timesheet-for-jira-for-healthcare/\n"
					. "   - i4ware SDK low-code-platform: https://www.i4ware.fi/ar/%d8%a7%d9%84%d9%85%d9%86%d8%aa%d8%ac%d8%a7%d8%aa/i4ware-sdk/\n"
					. "   - i4ware AI Assistant: https://www.i4ware.fi/ar/%d8%a7%d9%84%d9%85%d9%86%d8%aa%d8%ac%d8%a7%d8%aa/i4ware-ai-assistant/\n"
					. "   - i4ware Job Seeker Autopilot AI: https://www.i4ware.fi/ar/%d8%a7%d9%84%d9%85%d9%86%d8%aa%d8%ac%d8%a7%d8%aa/i4ware-job-seeker-autopilot-ai/\n"
					. "3. الخدمات (الرئيسية: https://www.i4ware.fi/ar/%d8%a7%d9%84%d8%ae%d8%af%d9%85%d8%a7%d8%aa/):\n"
					. "   - خدمة استضافة الويب: https://www.i4ware.fi/ar/%d8%a7%d9%84%d8%ae%d8%af%d9%85%d8%a7%d8%aa/%d8%ae%d8%af%d9%85%d8%a9-%d8%a7%d8%b3%d8%aa%d8%b6%d8%a7%d9%81%d8%a9-%d8%a7%d9%84%d9%88%d9%8a%d8%a8/\n"
					. "   - تطوير WordPress: https://www.i4ware.fi/ar/%d8%a7%d9%84%d8%ae%d8%af%d9%85%d8%a7%d8%aa/%d8%aa%d8%b7%d9%88%d9%8a%d8%b1-wordpress/\n"
					. "4. الشركة (الرئيسية: https://www.i4ware.fi/ar/%d8%b4%d8%b1%d9%83%d8%a9/):\n"
					. "   - بيانات الاتصال والفريق: https://www.i4ware.fi/ar/%d8%b4%d8%b1%d9%83%d8%a9/%d8%a8%d9%8a%d8%a7%d9%86%d8%a7%d8%aa-%d8%a7%d9%84%d8%a7%d8%aa%d8%b5%d8%a7%d9%84-%d8%a7%d9%84%d9%81%d8%b1%d9%8a%d9%82/\n"
					. "   - الشراكات: https://www.i4ware.fi/ar/%d8%b4%d8%b1%d9%83%d8%a9/%d8%a7%d9%84%d8%b4%d8%b1%d8%a7%d9%83%d8%a7%d8%aa/\n"
					. "   - العملاء: https://www.i4ware.fi/ar/%d8%b4%d8%b1%d9%83%d8%a9/%d8%a7%d9%84%d8%b9%d9%85%d9%84%d8%a7%d8%a1/\n"
					. "   - المدونة: https://www.i4ware.fi/ar/%d9%85%d8%af%d9%88%d9%86%d8%a9/\n"
					. "5. الشروط والخصوصية:\n"
					. "   - كاشير باي بال للمساحات الإعلانية: https://www.i4ware.fi/#paypal-advertising\n"
					. "   - شروط التسليم: https://www.i4ware.fi/ar/%d8%b4%d8%b1%d9%88%d8%b7-%d8%a7%d9%84%d8%aa%d8%b3%d9%84%d9%8a%d9%85/\n"
					. "   - سياسة الخصوصية: https://www.i4ware.fi/ar/%d8%b3%d9%8a%d8%a7%d8%b3%d8%a9-%d8%a7%d9%84%d8%ae%d8%b5%d9%88%d8%b5%d9%8a%d8%a9/\n"
					. "6. تطبيقات Atlassian Marketplace:\n"
					. "   - Timesheet for Jira for Railroad: https://marketplace.atlassian.com/apps/3844312374/timesheet-for-jira-for-railroad\n"
					. "   - Timesheet for Jira for Healthcare: https://marketplace.atlassian.com/apps/1223446/timesheet-for-jira-for-healthcare\n"
					. "   - Timesheet for Jira for Cities: https://marketplace.atlassian.com/apps/942289713/timesheet-for-jira-for-cities";
			} else {
				$title   = 'i4ware Software - Sivukartta ja olemassa olevat sivut ja alisivut';
				$url     = 'https://www.i4ware.fi/';
				$content = "i4ware Softwaren verkkosivuston viralliset sivut ja alisivut:\n"
					. "1. Etusivu: https://www.i4ware.fi/\n"
					. "2. Tuotteet (Pääsivu: https://www.i4ware.fi/tuotteet/):\n"
					. "   - Timesheet for Jira for Railroad: https://www.i4ware.fi/tuotteet/timesheet-for-jira-for-railroad/\n"
					. "   - Timesheet for Jira for Healthcare: https://www.i4ware.fi/tuotteet/timesheet-for-jira-for-healthcare/\n"
					. "   - i4ware SDK low-code-alusta: https://www.i4ware.fi/tuotteet/i4ware-sdk/\n"
					. "   - i4ware AI Assistant: https://www.i4ware.fi/tuotteet/i4ware-ai-assistant-reaaliaikainen-chat/\n"
					. "   - i4ware-Laskutuspilvi: https://www.i4ware.fi/tuotteet/i4ware-laskupilvi-ms-excel-laskupohja-pdf/\n"
					. "   - i4ware Job Seeker Autopilot AI: https://www.i4ware.fi/tuotteet/i4ware-job-seeker-autopilot-ai-life-cycle-management-system/\n"
					. "3. Palvelut (Pääsivu: https://www.i4ware.fi/palvelut/):\n"
					. "   - Web-hotellipalvelu: https://www.i4ware.fi/palvelut/web-hotellipalvelu-wordpress-ja-open-source-php-sovellusten-hostaus/\n"
					. "   - WordPress-kehitys: https://www.i4ware.fi/palvelut/wordpress-kehitys/\n"
					. "   - Lakiasiantuntija- ja juridiset tukipalvelut: https://www.i4ware.fi/palvelut/lakiasiantuntija-ja-juridiset-tukipalvelut/\n"
					. "4. Yritys (Pääsivu: https://www.i4ware.fi/yritys/):\n"
					. "   - Yhteystiedot ja tiimi: https://www.i4ware.fi/yritys/yhteystiedot-ja-tiimi/\n"
					. "   - Blogi: https://www.i4ware.fi/yritys/blogi/\n"
					. "   - Kumppanuudet: https://www.i4ware.fi/yritys/kumppanuudet/\n"
					. "   - Asiakkaat: https://www.i4ware.fi/yritys/asiakkaat/\n"
					. "   - Urat: https://www.i4ware.fi/yritys/urat/\n"
					. "   - Sijoittajille: https://www.i4ware.fi/yritys/sijoittajille/\n"
					. "5. Kauppa ja ehdot:\n"
					. "   - Kauppa: https://www.i4ware.fi/kauppa/\n"
					. "   - Mainospaikkojen PayPal-kassa: https://www.i4ware.fi/#paypal-advertising\n"
					. "   - Toimitusehdot: https://www.i4ware.fi/toimitusehdot/\n"
					. "   - Tietosuojakäytäntö: https://www.i4ware.fi/tietosuojakaytanto/\n"
					. "6. Atlassian Marketplace -sovellukset:\n"
					. "   - Timesheet for Jira for Railroad: https://marketplace.atlassian.com/apps/3844312374/timesheet-for-jira-for-railroad\n"
					. "   - Timesheet for Jira for Healthcare: https://marketplace.atlassian.com/apps/1223446/timesheet-for-jira-for-healthcare\n"
					. "   - Timesheet for Jira for Cities: https://marketplace.atlassian.com/apps/942289713/timesheet-for-jira-for-cities";
			}

			$sitemap_chunks[] = array(
				'id'       => 'site_directory_' . $lang,
				'lang'     => $lang,
				'title'    => $title,
				'category' => 'Navigointi & Sivukartta',
				'url'      => $url,
				'content'  => $content,
				'tokens'   => self::tokenize( $title . ' ' . $content ),
			);
		}

		return $sitemap_chunks;
	}

	/**
	 * Process a single WP_Post into a knowledge chunk
	 *
	 * @param WP_Post $post
	 * @param string $home_url
	 * @return array|null
	 */
	private static function process_post( $post, $home_url ) {
		$post_id   = $post->ID;
		$post_type = $post->post_type;
		$title     = trim( $post->post_title );

		// Detect Polylang language
		$post_lang = 'fi';
		if ( function_exists( 'pll_get_post_language' ) ) {
			$detected_lang = pll_get_post_language( $post_id );
			if ( ! empty( $detected_lang ) ) {
				$post_lang = $detected_lang;
			}
		}

		if ( empty( $title ) && empty( $post->post_content ) ) {
			return null;
		}

		$category = 'Artikkeli';
		$url      = get_permalink( $post_id );
		$details  = array();

		switch ( $post_type ) {
			case 'page':
				$category = ( 'ar' === $post_lang ) ? 'صفحة' : ( ( 'en' === $post_lang ) ? 'Page' : 'Sivu' );
				if ( $post_id === (int) get_option( 'page_on_front' ) ) {
					$url = $home_url;
				}

				// Check if this is a subpage (alisivu)
				if ( ! empty( $post->post_parent ) && $post->post_parent > 0 ) {
					$parent_id    = (int) $post->post_parent;
					$parent_title = get_the_title( $parent_id );
					$category     = ( 'ar' === $post_lang ) ? 'صفحة فرعية' : ( ( 'en' === $post_lang ) ? 'Subpage' : 'Alisivu' );
					if ( ! empty( $parent_title ) ) {
						$details[] = ( 'ar' === $post_lang ) ? "الصفحة الرئيسية التابعة لها: {$parent_title}" : ( ( 'en' === $post_lang ) ? "Parent Page: {$parent_title}" : "Pääsivu / Yläsivu: {$parent_title}" );
					}
				} else {
					// Check if this main page has subpages
					$subpages = get_posts( array(
						'post_type'      => 'page',
						'post_parent'    => $post_id,
						'post_status'    => 'publish',
						'posts_per_page' => 20,
						'fields'         => 'ids',
					) );
					if ( ! empty( $subpages ) && is_array( $subpages ) ) {
						$sub_titles = array();
						foreach ( $subpages as $sub_id ) {
							$sub_title = get_the_title( $sub_id );
							if ( ! empty( $sub_title ) ) {
								$sub_titles[] = $sub_title;
							}
						}
						if ( ! empty( $sub_titles ) ) {
							$category  = ( 'ar' === $post_lang ) ? 'صفحة رئيسية' : ( ( 'en' === $post_lang ) ? 'Main Page' : 'Pääsivu' );
							$details[] = ( 'ar' === $post_lang ) ? "الصفحات الفرعية التابعة: " . implode( ', ', $sub_titles ) : ( ( 'en' === $post_lang ) ? "Subpages: " . implode( ', ', $sub_titles ) : "Alisivut: " . implode( ', ', $sub_titles ) );
						}
					}
				}

				// Clean content: strip shortcodes, Gutenberg block comments, HTML tags, and entities
				$raw_content   = strip_shortcodes( (string) $post->post_content );
				$raw_content   = preg_replace( '/<!--\s*\/?wp:[^>]*-->/', ' ', $raw_content );
				$clean_content = wp_strip_all_tags( $raw_content );
				$clean_content = html_entity_decode( $clean_content, ENT_QUOTES, 'UTF-8' );
				$clean_content = preg_replace( '/\s+/u', ' ', trim( $clean_content ) );
				if ( ! empty( $clean_content ) ) {
					$details[] = wp_trim_words( $clean_content, 450, '...' );
				}
				break;

			case 'post':
				$category = ( 'ar' === $post_lang ) ? 'المدونة' : ( ( 'en' === $post_lang ) ? 'Blog' : 'Blogi' );

				// Publication date
				$pub_date = get_the_date( 'Y-m-d', $post );
				if ( ! empty( $pub_date ) ) {
					$details[] = ( 'ar' === $post_lang ) ? "تاريخ النشر: {$pub_date}" : ( ( 'en' === $post_lang ) ? "Published: {$pub_date}" : "Julkaisuaika: {$pub_date}" );
				}

				// Categories
				if ( function_exists( 'wp_get_post_categories' ) ) {
					$post_cats = wp_get_post_categories( $post_id, array( 'fields' => 'names' ) );
					if ( ! empty( $post_cats ) && is_array( $post_cats ) ) {
						$details[] = ( 'ar' === $post_lang ) ? "التصنيفات: " . implode( ', ', $post_cats ) : ( ( 'en' === $post_lang ) ? "Categories: " . implode( ', ', $post_cats ) : "Kategoriat: " . implode( ', ', $post_cats ) );
					}
				}

				// Tags
				if ( function_exists( 'wp_get_post_tags' ) ) {
					$post_tags = wp_get_post_tags( $post_id, array( 'fields' => 'names' ) );
					if ( ! empty( $post_tags ) && is_array( $post_tags ) ) {
						$details[] = ( 'ar' === $post_lang ) ? "الوسوم: " . implode( ', ', $post_tags ) : ( ( 'en' === $post_lang ) ? "Tags: " . implode( ', ', $post_tags ) : "Avainsanat: " . implode( ', ', $post_tags ) );
					}
				}

				// Excerpt if available
				$excerpt = ! empty( $post->post_excerpt ) ? wp_strip_all_tags( $post->post_excerpt ) : '';
				if ( ! empty( $excerpt ) ) {
					$details[] = ( 'ar' === $post_lang ) ? "الموجز: {$excerpt}" : ( ( 'en' === $post_lang ) ? "Excerpt: {$excerpt}" : "Tiivistelmä: {$excerpt}" );
				}

				// Clean content: strip shortcodes, Gutenberg block comments, HTML tags, and entities
				$raw_content   = strip_shortcodes( (string) $post->post_content );
				$raw_content   = preg_replace( '/<!--\s*\/?wp:[^>]*-->/', ' ', $raw_content );
				$clean_content = wp_strip_all_tags( $raw_content );
				$clean_content = html_entity_decode( $clean_content, ENT_QUOTES, 'UTF-8' );
				$clean_content = preg_replace( '/\s+/u', ' ', trim( $clean_content ) );
				if ( ! empty( $clean_content ) ) {
					$details[] = wp_trim_words( $clean_content, 400, '...' );
				}
				break;

			default:
				$category = ( 'ar' === $post_lang ) ? 'محتوى' : ( ( 'en' === $post_lang ) ? 'Content' : 'Sisältö' );
				$content  = ! empty( $post->post_excerpt ) ? $post->post_excerpt : wp_strip_all_tags( $post->post_content );
				if ( ! empty( $content ) ) {
					$details[] = wp_trim_words( $content, 200, '...' );
				}
				break;
		}

		$content_str = implode( "\n", $details );
		if ( empty( $content_str ) ) {
			$content_str = $title;
		}

		return array(
			'id'       => $post_type . '_' . $post_id,
			'lang'     => $post_lang,
			'title'    => $title,
			'category' => $category,
			'url'      => $url,
			'content'  => $content_str,
			'tokens'   => self::tokenize( $title . ' ' . $category . ' ' . $content_str ),
		);
	}

	/**
	 * Tokenize text into lowercase keywords
	 *
	 * @param string $text
	 * @return array
	 */
	public static function tokenize( $text ) {
		$text = mb_strtolower( (string) $text, 'UTF-8' );
		// Replace non-alphanumeric (keep Scandinavian letters, Arabic letters, and numbers)
		$text = preg_replace( '/[^\p{L}\p{N}\s]+/u', ' ', $text );
		$words = preg_split( '/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY );

		// Common Finnish, English & Arabic stop words to filter
		$stopwords = array(
			'ja', 'on', 'ei', 'se', 'tai', 'eli', 'että', 'joka', 'jotka', 'niin', 'kun',
			'kuin', 'mutta', 'vaan', 'myös', 'sekä', 'kanssa', 'ovat', 'ollut', 'oli',
			'olen', 'olet', 'me', 'te', 'he', 'hän', 'tämä', 'tuo', 'nuo', 'nämä',
			'mitä', 'mikä', 'kuka', 'missä', 'miksi', 'miten', 'milloin', 'paljonko',
			'onko', 'voiko', 'saako', 'kerro', 'the', 'and', 'is', 'in', 'at', 'of',
			'to', 'for', 'with', 'on', 'that', 'this', 'are', 'was', 'by', 'an', 'be',
			// Arabic stopwords
			'في', 'من', 'على', 'إلى', 'عن', 'مع', 'هذا', 'هذه', 'ذلك', 'تلك', 'التي',
			'الذي', 'الذين', 'كان', 'كانت', 'ما', 'ماذا', 'هل', 'كيف', 'أين', 'متى',
			'لماذا', 'كم', 'هو', 'هي', 'هم', 'نحن', 'أنا', 'أنت', 'أن', 'إن', 'أو',
			'ثم', 'كل', 'بعض', 'غير', 'لا', 'لم', 'لن', 'ليس', 'إلا', 'لدى', 'عند'
		);
		$stop_map = array_flip( $stopwords );

		$tokens = array();
		foreach ( $words as $w ) {
			if ( mb_strlen( $w, 'UTF-8' ) >= 2 && ! isset( $stop_map[ $w ] ) ) {
				$tokens[ $w ] = true;
			}
		}

		return array_keys( $tokens );
	}

	/**
	 * Search indexed chunks by query relevance and requested language
	 *
	 * @param string $query
	 * @param int $limit
	 * @param string $lang
	 * @return array
	 */
	public static function search( $query, $limit = 6, $lang = 'fi' ) {
		$index = get_option( self::INDEX_OPTION, null );
		if ( ! is_array( $index ) || empty( $index ) ) {
			$index = self::build_index();
		}

		$query_tokens = self::tokenize( $query );
		$lower_query  = mb_strtolower( trim( $query ), 'UTF-8' );

		$scored = array();
		foreach ( $index as $idx => $chunk ) {
			$score = 0;
			$chunk_lang    = ! empty( $chunk['lang'] ) ? $chunk['lang'] : 'fi';
			$lower_title   = mb_strtolower( $chunk['title'], 'UTF-8' );
			$lower_cat     = mb_strtolower( $chunk['category'], 'UTF-8' );
			$lower_content = mb_strtolower( $chunk['content'], 'UTF-8' );

			// Exact full phrase in title or content
			if ( ! empty( $lower_query ) && mb_strpos( $lower_title, $lower_query ) !== false ) {
				$score += 15;
			}
			if ( ! empty( $lower_query ) && mb_strpos( $lower_content, $lower_query ) !== false ) {
				$score += 8;
			}

			// Token matches
			$chunk_tokens = array_flip( $chunk['tokens'] );
			foreach ( $query_tokens as $qt ) {
				// Exact token in chunk tokens
				if ( isset( $chunk_tokens[ $qt ] ) ) {
					$score += 4;
				}
				// Substring match in title
				if ( mb_strpos( $lower_title, $qt ) !== false ) {
					$score += 6;
				}
				// Substring match in category
				if ( mb_strpos( $lower_cat, $qt ) !== false ) {
					$score += 4;
				}
				// Substring match in content
				if ( mb_strpos( $lower_content, $qt ) !== false ) {
					$score += 2;
				}
			}

			// High-priority topic checks (Multilingual: FI, EN, AR)
			// 1. Advertising spaces and banner placement
			if ( ( preg_match( '/\b(mainos\w*|mainok\w*|mainost\w*|banner\w*|sponsor\w*|ad|ads|advertising)\b/iu', $lower_query ) || preg_match( '/(إعلان|اعلان|بانر|مساحة إعلانية)/u', $lower_query ) ) && strpos( $chunk['id'], 'commercial_advertising' ) !== false ) {
				$score += 45;
			}
			// 2. Cancellation of subscription
			if ( ( preg_match( '/\b(peruut\w*|irtisan\w*|cancel\w*)\b/iu', $lower_query ) || preg_match( '/(إلغاء|الغاء|انهاء|إنهاء)/u', $lower_query ) ) && strpos( $chunk['id'], 'commercial_payments' ) !== false ) {
				$score += 40;
			}
			// 3. Subscriptions, one-time payments, PayPal, and PDF receipts
			if ( ( preg_match( '/\b(kuukausitila\w*|kuukausimak\w*|kertamak\w*|paypal\w*|maksu\w*|veloitu\w*|kuitti\w*|pdf\w*|lasku\w*|subscription\w*|onetime\w*|payment\w*|receipt\w*|invoice\w*)\b/iu', $lower_query ) || preg_match( '/(اشتراك|شهري|دفعة واحدة|باي بال|دفع|فاتورة|إيصال|ايصال|ضريبة)/u', $lower_query ) ) && ( strpos( $chunk['id'], 'commercial_payments' ) !== false || strpos( $chunk['id'], 'commercial_advertising' ) !== false ) ) {
				$score += 30;
			}
			// 4. Timesheet for Jira & Atlassian Marketplace
			if ( ( preg_match( '/\b(timesheet\w*|jira\w*|tuntikirja\w*|työajanseuran\w*|työloki\w*|marketplace\w*|atlassian\w*|worklog\w*)\b/iu', $lower_query ) || preg_match( '/(تايم شيت|جايرا|ساعات العمل|تسجيل الوقت)/u', $lower_query ) ) && strpos( $chunk['id'], 'commercial_timesheet' ) !== false ) {
				$score += 45;
			}
			// 5. Web Hosting
			if ( ( preg_match( '/\b(webhotelli\w*|web-hotelli\w*|hosting\w*|leaseweb\w*|palvelin\w*)\b/iu', $lower_query ) || preg_match( '/(استضافة|خادم|سيرفر)/u', $lower_query ) ) && strpos( $chunk['id'], 'commercial_hosting' ) !== false ) {
				$score += 45;
			}
			// 6. Custom software & WordPress development
			if ( ( preg_match( '/\b(ohjelmisto\w*|kehity\w*|wordpress\w*|lisäosa\w*|plugin\w*|teema\w*|theme\w*|sovellu\w*|laravel\w*|react\w*|integraatio\w*|integration\w*)\b/iu', $lower_query ) || preg_match( '/(برمجيات|برمجة|تطوير|ووردبريس|تطبيق|إضافة|قالب)/u', $lower_query ) ) && strpos( $chunk['id'], 'commercial_dev' ) !== false ) {
				$score += 30;
			}
			// 7. Buying, ordering, general offering ("mitä voi ostaa", "mitä i4ware tekee", etc.)
			if ( preg_match( '/(mitä.*(ostaa|tilata|tekee)|tuotteet|palvelut|tarjonta|osta|tilaa|what.*(buy|order|sell|do)|products|services|شراء|طلب|منتجات|خدمات|ماذا تقدم)/iu', $lower_query ) ) {
				if ( strpos( $chunk['id'], 'commercial_' ) !== false ) {
					$score += 20;
				}
			}
			// 8. Pricing, quotes and offers
			if ( ( preg_match( '/\b(hinta\w*|hinna\w*|hinnasto\w*|paljonko|maksa\w*|price\w*|pricing\w*|cost\w*|tarjous\w*|tarjouspyyn\w*|quote\w*)\b/iu', $lower_query ) || preg_match( '/(سعر|أسعار|اسعار|تكلفة|عرض أسعار)/u', $lower_query ) ) ) {
				if ( strpos( $chunk['id'], 'commercial_' ) !== false ) {
					$score += 20;
				}
			}
			// 9. Contact information
			if ( ( preg_match( '/\b(yhteys|yhteystiedot|sähköposti|puhelin|contact|email|phone)\b/i', $lower_query ) || preg_match( '/(اتصال|تواصل|بريد|هاتف)/u', $lower_query ) ) && ( 'profile_overview_' . $lang === $chunk['id'] || 'extra_approved' === $chunk['id'] ) ) {
				$score += 25;
			}
			if ( preg_match( '/\b(atlassian|jira|confluence|forge)\b/i', $lower_query ) && preg_match( '/\b(atlassian|jira|confluence|forge)\b/i', $chunk['title'] . ' ' . $chunk['content'] ) ) {
				$score += 10;
			}
			if ( preg_match( '/\b(erp|integraatio|integration|sap|visma|procountor|lemonsoft)\b/i', $lower_query ) && preg_match( '/\b(erp|integraatio|integration)\b/i', $chunk['title'] . ' ' . $chunk['content'] ) ) {
				$score += 10;
			}
			// 10. Blog articles and posts booster
			if ( ( preg_match( '/\b(blogi\w*|artikkeli\w*|kirjoitu\w*|uutis\w*|blog\w*|post\w*|article\w*)\b/iu', $lower_query ) || preg_match( '/(مدونة|مقال|مقالات|أخبار)/u', $lower_query ) ) && ( 'Blogi' === $chunk['category'] || 'Blog' === $chunk['category'] || 'المدونة' === $chunk['category'] || strpos( $chunk['id'], 'post_' ) === 0 ) ) {
				$score += 35;
			}
			// 11. Pages and subpages booster
			if ( ( preg_match( '/\b(sivu\w*|alisivu\w*|alasivu\w*|verkkosivu\w*|page\w*|subpage\w*)\b/iu', $lower_query ) || preg_match( '/(صفحة|صفحات|فرعية)/u', $lower_query ) ) && ( strpos( $chunk['category'], 'Sivu' ) !== false || strpos( $chunk['category'], 'Alisivu' ) !== false || strpos( $chunk['category'], 'Page' ) !== false || strpos( $chunk['category'], 'Subpage' ) !== false || strpos( $chunk['category'], 'صفحة' ) !== false ) ) {
				$score += 30;
			}

			// Significant bonus for chunks matching the requested language
			if ( $score > 0 ) {
				if ( $chunk_lang === $lang ) {
					$score += 25;
				}
				$scored[] = array(
					'score' => $score,
					'chunk' => $chunk,
				);
			}
		}

		// Sort by score descending
		usort( $scored, function( $a, $b ) {
			return $b['score'] <=> $a['score'];
		} );

		$results = array();
		foreach ( array_slice( $scored, 0, $limit ) as $item ) {
			$results[] = $item['chunk'];
		}

		// If no matches (e.g. empty or generic greeting), include profile overview for requested language
		if ( empty( $results ) ) {
			foreach ( $index as $chunk ) {
				if ( 'profile_overview_' . $lang === $chunk['id'] ) {
					$results[] = $chunk;
					break;
				}
			}
			if ( empty( $results ) ) {
				foreach ( $index as $chunk ) {
					if ( ! empty( $chunk['lang'] ) && $chunk['lang'] === $lang ) {
						$results[] = $chunk;
						if ( count( $results ) >= 3 ) {
							break;
						}
					}
				}
			}
		}

		return $results;
	}

	/**
	 * Get formatted context string for the Gemini prompt and array of sources
	 *
	 * @param string $query
	 * @param int $limit
	 * @param string $lang
	 * @return array
	 */
	public static function get_prompt_context( $query, $limit = 6, $lang = 'fi' ) {
		$chunks = self::search( $query, $limit, $lang );

		$context_lines = array();
		$sources       = array();
		$seen_urls     = array();

		$source_label = 'LÄHDE';
		if ( 'en' === $lang ) {
			$source_label = 'SOURCE';
		} elseif ( 'ar' === $lang ) {
			$source_label = 'المصدر';
		}

		foreach ( $chunks as $i => $chunk ) {
			$num = $i + 1;
			$context_lines[] = "--- [{$source_label} {$num}: {$chunk['title']} ({$chunk['category']})] ---";
			$context_lines[] = $chunk['content'];
			$context_lines[] = "URL: " . $chunk['url'];
			$context_lines[] = "";

			if ( ! empty( $chunk['url'] ) && ! isset( $seen_urls[ $chunk['url'] ] ) ) {
				$seen_urls[ $chunk['url'] ] = true;
				$sources[] = array(
					'title'    => $chunk['title'],
					'category' => $chunk['category'],
					'url'      => $chunk['url'],
				);
			}
		}

		return array(
			'context_text' => implode( "\n", $context_lines ),
			'sources'      => $sources,
		);
	}

	/**
	 * Get statistics about the index
	 *
	 * @return array
	 */
	public static function get_stats() {
		$index = get_option( self::INDEX_OPTION, array() );
		$last_updated = get_option( self::INDEX_TIME_OPTION, '' );

		$categories = array();
		if ( is_array( $index ) ) {
			foreach ( $index as $chunk ) {
				$cat = ! empty( $chunk['category'] ) ? $chunk['category'] : 'Muu';
				$categories[ $cat ] = ( $categories[ $cat ] ?? 0 ) + 1;
			}
		}

		return array(
			'total_chunks'  => is_array( $index ) ? count( $index ) : 0,
			'last_updated'  => $last_updated,
			'by_category'   => $categories,
		);
	}

	/**
	 * Clear index
	 */
	public static function clear_index() {
		delete_option( self::INDEX_OPTION );
		delete_option( self::INDEX_TIME_OPTION );
	}
}
