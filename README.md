# i4ware_www

i4ware Software -verkkosivuston ja siihen liittyvien liiketoimintasovellusten päärepo. Repo sisältää räätälöidyn WordPress-teeman, useita WordPress-lisäosia, erillisiä React-sovelluksia sekä Laravel-pohjaisen SaaS-sovelluksen.

## 📁 Projektin Hakemistorakenne

### 🎨 WordPress-teema: [i4waresoftware/](i4waresoftware/)
i4ware Softwaren virallinen räätälöity WordPress-teema. Teema tarjoaa modernin, responsiivisen ja monikielisen (FI, EN, AR) verkkosivustoalustan ohjelmistopalveluiden, SaaS-tuotteiden ja kumppanuuksien esittelyyn sekä myyntiin.

#### Teeman keskeiset toiminnot ja arkkitehtuuri
- **Teeman perustoiminnot ([functions.php](i4waresoftware/functions.php)):**
  - **Teematuki ja valikot**: Rekisteröi sivun otsikkotuen (`title-tag`), artikkelikuvat (`post-thumbnails`) sekä pää- ja alatunnistevalikot (`primary` ja `footer`).
  - **Resurssien hallinta**: Lataa optimoidusti teeman päätyylit (`style.css`), ulkoasutyylit (`assets/css/main.css`), teeman JavaScriptit (`assets/js/main.js`) sekä monitasoisen alasvetovalikon skriptin (`assets/js/dropdown-menu.js`).
  - **PWA-valmius**: Sisältää web-sovellusmanifestin (`assets/site.webmanifest`) ja mukautetut kuvakeresurssit.
- **WordPress Mukautin (Customizer -integraatio):**
  - Monikieliset säätimet (suomi, englanti, arabia) Hero-otsikoille, esittelyteksteille, toimintakehotuksille (CTA) ja alatunnisteen sisällöille.
  - YouTube-videoupotuksen asetukset ja sumennustehosteet (blur effect).
- **Mukautetut artikkelityypit (Custom Post Types, CPT):**
  - `partner_logo`: Kumppani- ja affiliate-ohjelmien logot ja linkit ryhmittelyineen (`top`, `main`, `bottom`, `affiliate`).
  - `customer_logo`: Asiakasreferenssit, logot ja käyttötapaustarinat Polylang-kieliversioittain.
  - `tfj_screenshot`: Jira Timesheet -laskeutumissivun kuvakaappaukset ja kuvatekstit.
  - `sdk_screenshot`: i4ware SDK -kehitystyökalun kuvakaappaukset ja ominaisuustunnisteet.
  - `wordpress_screenshot`: WordPress-kehityspalveluiden työnäytteet ja portfoliokuvat.
- **Kuvakaappausten massalisäysmoduuli ([bulk-screenshots.php](i4waresoftware/bulk-screenshots.php)):**
  - WordPress-mediakirjaston massatoiminnot (`upload.php`) ja Työkalut / Media / CPT -hallintanäkymät (`wp.media`-monivalitsimella).
  - Automaattinen kuvien luonti CPT-artikkeleiksi (`tfj_screenshot`, `sdk_screenshot`, `wordpress_screenshot`), artikkelikuvan asetus ja ACF-kenttien synkronointi.
  - Monikielinen Polylang-tuki: automaattinen käännösten linkitys (FI, EN, AR) ja live-esikatselu muokattavilla otsikoilla ennen julkaisua.
- **Google Analytics 4 (GA4) -tapahtumaseuranta:**
  - Valmis analytiikkaintegraatio, joka lähettää mukautetut tapahtumat (`open_paypal_modal`, `click_paypal_donation`) kaikista teeman PayPal-painikkeista.
  - Parametrien välitys: `donation_type`, `item_name`, `amount`/`price`, `currency`, `plan_id`, `level_name`, `button_text`.
- **ACF (Advanced Custom Fields) JSON -määritykset:**
  - `acf-timesheet-landing-fields.json` – Jira Timesheet -sivun laajat sisältökentät.
  - `import-customers-acf.json` – Asiakasreferenssien kentät ja metatiedot.
  - `import-partners-acf.json` – Kumppanilogojen sijainti- ja ryhmittelykentät.
  - `import-team-members-acf.json` – Tiimin jäsenten profiilitiedot ja sosiaalisen median linkit.
  - `import-sdk-screenshots-acf.json` – SDK-kuvakaappausten kentät ja ulkoiset linkit.
  - `import-wordpress-gallery-acf.json` & `import-free-acf-wordpress-screenshots.json` – WordPress-kehityssivun kuvagalleriakentät.
  - `timesheet-landing-content.json` – Jira Timesheet -sivun staattinen sisältö ja lokalisoidut tekstit.
- **Uudelleenkäytettävät sivupohjat ([template-parts/](i4waresoftware/template-parts/)):**
  - [header.php](i4waresoftware/template-parts/header.php) – Sivuston ylätunniste, kielivalitsin (Polylang) ja päänavigaatio.
  - [footer.php](i4waresoftware/template-parts/footer.php) – Sivuston alatunniste, tekijänoikeustiedot ja toissijaiset linkit.
  - [blog.php](i4waresoftware/template-parts/blog.php) – Blogikirjoitusten esityspohja.
  - [content.php](i4waresoftware/template-parts/content.php) – Yleisten sisältösivujen muotoilupohja.

#### Teeman rekisteröimät lyhytkoodit (Shortcodes)
1. `[transactions_table_ai]` ([google-ai-shortcode.php](i4waresoftware/google-ai-shortcode.php))
   - Luo hakukone- ja tekoälyoptimoidun transaktio- ja liikevaihtotaulukon Google AI -indeksointia varten.
   - Parametrit: `revenue_source` (esim. `ALL`, `GOOGLE`), `api_base_url`.
2. `[jira_timesheet_landing]` ([jira-timesheet-shortcode.php](i4waresoftware/jira-timesheet-shortcode.php))
   - Tuottaa laajan SaaS-tuotelaskeutumissivun Jira Timesheet -integraatiolle dynaamisilla ACF-kentillä, ominaisuuslistoilla ja kuvakaappausgallerialla.
3. `[i4ware_wordpress_page]` ([wordpress-kehitys-shortcode.php](i4waresoftware/wordpress-kehitys-shortcode.php))
   - Ammattimaisen WordPress-kehityspalvelun laskeutumissivu, palvelutasot, hyödyt, tarjouspyyntölomake ja monikielisyystuki.
4. `[i4ware_web_hosting_page]` ([web-hotellipalvelu-shortcode.php](i4waresoftware/web-hotellipalvelu-shortcode.php))
   - Web-hotellipalveluiden esittelysivu: palvelupaketit, tietokantojen ja sähköpostien määrät, roskapostisuojaus ja palvelintiedot.
5. `[affiliate_programs]` ([affiliate-programs-shortcode.php](i4waresoftware/affiliate-programs-shortcode.php))
   - Näyttää yhteistyökumppanien ja affiliate-ohjelmien ostolinkit ja esittelyt (FI, EN, AR) ryhmittelyllä (`group`, `limit`).
6. `[partnerships]` ([functions.php](i4waresoftware/functions.php))
   - Hakee ja ryhmittelee kumppanilogot sijainnin mukaan (`top`, `main`, `bottom`) määritellyssä esitysjärjestyksessä.
7. `[customers]` ([functions.php](i4waresoftware/functions.php))
   - Esittää asiakaslogot, linkit ja käyttötapaukset aktiivisen kielen mukaisesti.
8. `[i4ware_pricing]` ([functions.php](i4waresoftware/functions.php))
   - Interaktiivinen välilehdellinen hinnoittelutaulukko suorilla yhteydenottotoiminnoilla (`default_tab`, `contact_page_id`).
9. `[i4ware_cta]` ([functions.php](i4waresoftware/functions.php))
   - Monikielinen toimintakehotuslohko (Call to Action) mukautettavilla painikelinkeillä (FI/EN/AR) ja Customizer-ohjauksella.
10. `[i4ware_video]` ([functions.php](i4waresoftware/functions.php))
    - Responsiivinen YouTube-videosoitin taustasumennuksella ja monikielisellä tekstipeitolla.
11. `[i4ware_saas_order_form]` ([functions.php](i4waresoftware/functions.php))
    - Interaktiivinen SaaS-projektin tilaus- ja arviointilomake ominaisuusvalitsimilla, budjettilaskurilla ja turvallisella nonce-käsittelyllä.
12. `[wp_quote]` ([functions.php](i4waresoftware/functions.php))
    - Asiakkaan interaktiivinen hinta-arviolomake WordPress-kehitykseen (Pronssi, Hopea, Kulta, tuntityö; layout: Figma, PSD, Sketch).
13. `[i4ware_sdk_page]` ([functions.php](i4waresoftware/functions.php))
    - Matala koodin i4ware SDK -esittelysivu, interaktiivinen kuvagalleria (`sdk_screenshot` CPT) ja avoimen lähdekoodin lisenssitiedot.
14. `[paypal_donate]` ([functions.php](i4waresoftware/functions.php))
    - PayPal-lahjoituspainike, joka avaa ennen maksua vahvistus- ja toimitusehtomodaalin (Pre-checkout Terms & Trust Modal).
15. `[paypal_button]` ([functions.php](i4waresoftware/functions.php))
    - Suora ostopainike PayPal-maksulinkille tai PayPal Hosted Button ID:lle turvamodaalilla ja GA4-seurannalla.
16. `[paypal_subscribe]` ([functions.php](i4waresoftware/functions.php))
    - Kuukausitilauksen PayPal Smart Subscription -painike toistuvaislaskutukseen.
17. `[paypal_support_table]` ([functions.php](i4waresoftware/functions.php))
    - 6-tasoinen tukitilaustaulukko (`community`, `opensource`, `development`, `professional`, `business`, `enterprise`) modal-vahvistuksella.

---

### 🔌 WordPress-lisäosat (Plugins)

#### 1. [ats_job_application/](ats_job_application/) – MH ATS Job Application
- **Käyttötarkoitus**: Rekrytointi- ja hakijaseurantajärjestelmä (Applicant Tracking System) avoimien työhakemusten vastaanottoon ja arviointiin.
- **Toiminnot ja ominaisuudet:**
  - `[job_application]` -lyhytkoodi: Upottaa työnhakulomakkeen ja sen käyttöliittymäjuuren (`#mh-ats-root`).
  - **Tekoälypohjainen CV-arviointi (OpenAI API)**: Analysoi automaattisesti hakijan ansioluettelon, työkokemuksen, koulutuksen ja osaamisen.
  - **Automaattinen pisteytys ja karsinta**: Pisteyttää hakemuksen (0–100) ja määrittää hakijan tilan (`accepted`, `maybe`, `rejected`) perustelujen kera.
  - **Omat tietokantataulukot**: Luo aktivoidessa taulukot hakijoille (`mh_ats_applicants`), liitetiedostoille (`mh_ats_documents`) ja pisteytyksille (`mh_ats_scores`).
  - **REST API -rajapinta**: Päätepiste `/wp-json/mh-ats/v1/applications` hakemusten asynkroniseen vastaanottoon ja validointiin.
  - **Hallintasivu**: Asetussivu WordPressin hallinnassa OpenAI API -avaimen turvalliseen tallentamiseen.

#### 2. [cv-openai-polylang-translator/](cv-openai-polylang-translator/) – CV OpenAI Polylang Translator
- **Käyttötarkoitus**: Tekoälyavusteinen monikielisyyskääntäjä suomenkielisten sivujen ja artikkeleiden kääntämiseen englanniksi ja B2B-arabiaksi.
- **Toiminnot ja ominaisuudet:**
  - **Sivupalkin metaboxi (Single-Post Translation)**: Artikkelikohtainen hallittu käännöstyökalu suoraan WordPressin lohko- ja klassisessa editorissa palvelinkuorman minimoimiseksi.
  - **B2B Modern Standard Arabic**: Erityisesti Dubain ja GCC-alueen yritysviestintään optimoidut tekoälykehotteet, jotka säilyttävät tuotenimet ja brändit latinalaisilla kirjaimilla.
  - **Palvelinresurssien suojaus ja globaali lukitus (`cv_oai_pll_global_lock`)**: Estää rinnakkaiset raskaat käännösajot 10 minuutin automaattisella aikakatkaisulla.
  - **Gutenberg-lohkoturvallinen jäsennin**: Erottaa tekstisisällön lohkorakenteesta ja HTML-muotoiluista kääntämisen ajaksi rikkomatta sivun asettelua.
  - **Rekursiivinen ACF-kääntäjä**: Kääntää hierarkkiset Advanced Custom Fields -kentät (ryhmät, toistokentät/repeaters ja tekstialueet) layout-rakenteen säilyttäen.
  - **Human-in-the-loop -tarkistusprosessi**: Luo käännökset luonnoksina (`draft`) ja edellyttää ihmisen hyväksyntää ennen julkaisua tekoälyn hallusinointien estämiseksi.
  - **Tietoturva- ja eheystarkistukset**: Estää skripti-injektiot sekä takaa, että linkit, URL-osoitteet, sähköpostit ja puhelinnumerot pysyvät muuttumattomina.
  - **Audit-historia ja lokitus**: Täysi tietokantapohjainen tapahtumaloki käännöksistä hallintasivulla ja artikkelien metatiedoissa.

#### 3. [i4ware-ai-seo-agent/](i4ware-ai-seo-agent/) – i4ware AI SEO Agent
- **Käyttötarkoitus**: Tekoälypohjainen SEO-agentti, joka analysoi sivuston sisältöä ja tuottaa hakukoneoptimoidut metatiedot ja toimenpidesuositukset.
- **Toiminnot ja ominaisuudet:**
  - **Reaaliaikainen sisältöanalyysi**: Analysoi artikkelin tai sivun sisällön OpenAI-mallien avulla suoraan muokkausnäkymässä.
  - **Automaattinen metatietojen generointi**: Tuottaa houkuttelevat ja optimoidut SEO-otsikot (title), metakuvaukset (meta description) sekä kohdeavainsanat (keyphrases).
  - **Toiminnalliset parannusehdotukset**: Antaa konkreettisia, priorisoituja toimenpide-ehdotuksia tekstin luettavuuden ja hakukonenäkyvyyden parantamiseksi.
  - **Google SERP -esikatselu**: Visuaalinen esikatselu näyttää tarkasti, miltä sivu näyttää Googlen hakutuloksissa työpöytä- ja mobiilinäkymässä.
  - **Massaanalyysin hallintapaneeli (Bulk Analysis Dashboard)**: Työkalu kaikkien julkaistujen artikkeleiden listaamiseen, analysointiin ja optimointiin yhdestä näkymästä.
  - **Automaattinen metatagien tulostus**: Injektoi optimoidut metatiedot ja sivun otsikon sivuston HTML-otsaketietoihin (`wp_head`, `document_title_parts`).

#### 4. [i4ware-lightbox-gallery/](i4ware-lightbox-gallery/) – i4ware Lightbox Gallery
- **Käyttötarkoitus**: Gutenberg-lohkona toimiva kuvagalleria modernilla tummasävyisellä lightbox-ponnahdusnäkymällä.
- **Toiminnot ja ominaisuudet:**
  - **Gutenberg-lohko `i4ware/lightbox-gallery`**: Helppo lisätä suoraan WordPressin lohkoeditorissa osaksi mitä tahansa sivua tai artikkelia.
  - **Tummasävyinen Lightbox-näkymä**: Tyylikäs täysikokoinen kuvaikkuna taustasumennuksella, pyyhkäisy- ja näppäimistönavigaatiolla (Esc, nuolinäppäimet) sekä sulkupainikkeella.
  - **Optimoitu koodirakenne**: Täysin erilliset ja minimoidut tyylit sekä skriptit editori- ja julkisivukäyttöön ilman raskaita ulkoisia riippuvuuksia.
  - **Responsiivinen kuvagridi**: Mukautuu automaattisesti eri näyttökokoihin säilyttäen kuvien kuvasuhteet.

#### 5. [i4ware-roi-calculator/](i4ware-roi-calculator/) – I4ware ROI Calculator
- **Käyttötarkoitus**: React-pohjainen sijoitetun pääoman tuoton (ROI) ja työajanseurannan säästölaskuri.
- **Toiminnot ja ominaisuudet:**
  - `[i4ware_roi_calculator]` -lyhytkoodi: Yleinen ROI- ja tuntihinnoittelulaskuri ohjelmistoprojekteille ja kehitysinvestoinneille.
  - `[timesheet_roi_calculator]` -lyhytkoodi: Spesiifi Jira Timesheet -säästölaskuri manuaalisen työajanseurannan tehostamisesta syntyvien säästöjen arviointiin.
  - **Monikielisyystuki**: Tunnistaa automaattisesti Polylang-kielen (fi, en, ar) ja lokalisoi laskurin tekstit ja valuutat.
  - **Reaaliaikainen laskentalogiikka**: Interaktiiviset säätimet työntekijämäärälle, tuntipalkoille ja säästetylle ajalle dynaamisella visualisoinnilla.

#### 6. [i4ware-team-contact/](i4ware-team-contact/) – i4ware Team & Contact Section
- **Käyttötarkoitus**: Tiimiesittelyiden ja interaktiivisen yhteydenottolomakkeen hallinta ja esittäminen.
- **Toiminnot ja ominaisuudet:**
  - `[i4ware_team]` -lyhytkoodi: Tuottaa tiimiosion sekä asynkronisen yhteydenottolomakkeen.
  - `[i4ware_team_members]` -lyhytkoodi: Esittää tiimiluettelon ja asiantuntijaprofiilit.
  - **WordPress Customizer -ohjaus**: Tiimitietojen, otsikoiden ja yhteystietojen joustava muokkaus kieliversioittain (FI, EN, AR).
  - **Asynkroninen AJAX-lomakekäsittely**: Lähettää yhteydenottoviestit ilman sivun uudelleenlatausta (`admin-ajax.php`, nonce-varmistus).
  - **Google reCAPTCHA -roskapostisuojaus**: Suojaa yhteydenottolomakkeen automaattisilta spamboteilta.
  - **Monikieliset skriptit**: Erilliset kielikohtaiset skriptit suomelle (`yhteys.js`), arabialle (`ittisal.js`) ja englannille (`contact.js`).

#### 7. [i4ware-testimonials/](i4ware-testimonials/) – i4ware Testimonials
- **Käyttötarkoitus**: Asiakassuositusten ja -arvostelujen kerääminen ja julkaiseminen sivustolla.
- **Toiminnot ja ominaisuudet:**
  - **Mukautettu artikkelityyppi (`i4ware_testimonial`)**: Suositusten tallennus ja hallinta suoraan WordPressin hallintavalikosta.
  - `[i4ware_testimonial_form]` -lyhytkoodi: Lomake, jolla asiakkaat voivat jättää anonyymin tai nimellisen suosituksen.
  - `[i4ware_testimonials]` -lyhytkoodi: Julkaistujen ja hyväksyttyjen suositusten responsiivinen esitysruudukko.
  - **Google reCAPTCHA v2 / v3 -integraatio**: Estää roskapostipalautteiden tallentumisen tietokantaan.
  - **Ylläpidon moderointi**: Palautteet tallentuvat oletuksena luonnoksina tai tarkistettaviksi ennen julkista esittämistä.

#### 8. [i4ware_job_application_form/](i4ware_job_application_form/) – i4ware Job Application Form (Placeholder)
- **Käyttötarkoitus**: Työhakemuslomakkeen kevyt varaus- ja kehityshakemisto (toiminnallisuus keskitetty laajennettuun `job-application-form`-lisäosaan).

#### 9. [job-application-form/](job-application-form/) – Job Application Form (React & ATS)
- **Käyttötarkoitus**: Täysiverinen React-pohjainen työhakemuslomake, avoimien työpaikkojen hallinta ja tekoälyavusteinen ATS-hakijaseurantajärjestelmä.
- **Toiminnot ja ominaisuudet:**
  - `[job_application_form]` -lyhytkoodi: Upottaa modernin, monivaiheisen React-työhakemuslomakkeen tiedostolatauksilla.
  - `[job_openings]` -lyhytkoodi: Listaa organisaation avoimet työpaikat ja hakuohjeet.
  - **Tekoälypohjainen hakijaseulonta (OpenAI API)**: Pisteyttää hakijat automaattisesti avoimen paikan kriteereiden (vaaditut taidot, minimikokemusvuodet, toivottu sijainti) perusteella.
  - **Omat tietokantataulut**: Hallitsee hakijoiden profiileja, CV- ja motivaatiokirjedokumentteja sekä pisteytystuloksia.
  - **Kattava ylläpidon hallintapaneeli**: Hakijaluettelo hakutoiminnoilla, liitetiedostojen lataus yhdellä klikkauksella, suodatus statuksen mukaan ja hakemusten poisto.
  - **GDPR-tietosuoja ja vientityökalut**: Hakijadatan turvallinen vienti (export) ja vanhojen hakemusten massapoisto tietosuojakäytäntöjen mukaisesti.
  - **REST API -päätepisteet**: Taustapäätepisteet (`mh-ats/v1/applications`) ja Polylang-käännösmerkkijonojen rekisteröinti.

#### 10. [legal-react-app/](legal-react-app/) – Legal React App
- **Käyttötarkoitus**: React-pohjaisen lakisääteisten ehtojen, lisenssien ja oikeudellisten kulujen laskurisovelluksen upottaminen WordPressiin.
- **Toiminnot ja ominaisuudet:**
  - `[legal_react_app]` -lyhytkoodi: Upottaa käännetyn React-sovelluksen kohdesivulle.
  - **Dynaaminen Webpack-resurssien lataus**: Tunnistaa automaattisesti `static/`-kansiosta tuotantoversion hash-muotoiset skriptit ja tyylit.
  - **Monikieliset ehdot ja laskuri**: Esittää ohjelmistolisenssit (MIT), ehdot ja oikeudellisten maksujen laskurin (LegalFeeCalculator).

#### 11. [revenue-react-app/](revenue-react-app/) – Revenue React App
- **Käyttötarkoitus**: React-pohjaisen liikevaihto- ja myyntianalytiikan kojelaudan (dashboard) integrointi WordPressiin.
- **Toiminnot ja ominaisuudet:**
  - `[revenue_react_app]` -lyhytkoodi: Upottaa liikevaihtonäkymän suoraan halutulle sivulle.
  - **Interaktiivinen visualisointi**: Esittää talouslukuja ja myyntidataa suojatussa ympäristössä.

#### 12. [woo-rest/](woo-rest/) – WooCommerce REST Orders API
- **Käyttötarkoitus**: Mukautettu suorituskykyinen REST API -rajapinta WooCommerce-tilausten ja talousdatan hakemiseen erillisiin sovelluksiin ja kirjanpitoon.
- **Toiminnot ja ominaisuudet:**
  - **REST-päätepiste**: `/wp-json/woo-rest/v1/orders`.
  - **Kattava ALV- ja verokäsittely**: Erottelee ja laskee automaattisesti Suomen kotimaisen arvonlisäveron, EU-alueen käännetyn verovelvollisuuden (reverse charge) sekä EU:n ulkopuolisen 0 % -myynnin.
  - **Monipuoliset kyselyparametrit**:
    - `status`: Tilausten suodatus tilan mukaan (esim. `completed`, `processing`, `any`).
    - `limit`: Haettavien tilausten lukumäärärajaus.
    - `compact`: Palauttaa vain päivämäärän ja myyjän saldon nopeuttaen vastausaikaa huomattavasti.
    - `tax_type`: Suodatus verokohtelun mukaan (`fi`, `eu_reverse`, `non_eu`).
    - `start_date` & `end_date`: Päivämäärävälin suodatus.
  - **Turvallisuus ja käyttöoikeudet**: Vaatii joko WordPressin WooCommerce-hallintaoikeudet (`manage_woocommerce`) tai suojatun API-avaimen (`X-API-Key`).

#### 13. [word-to-blog-ai/](word-to-blog-ai/) – Word to Blog AI
- **Käyttötarkoitus**: Word-asiakirjojen (.docx / .doc) automaattinen muuntaminen valmiiksi jäsennellyiksi WordPress-blogiartikkeleiksi tekoälyn avulla.
- **Toiminnot ja ominaisuudet:**
  - **Word-tiedostojen tuonti**: Lukee suomenkielisiä Word-asiakirjoja palvelimella PHPWord-kirjaston avulla.
  - **Automaattinen kuvien poiminta**: Tunnistaa ja irrottaa Word-dokumenttiin upotetut kuvat ja tallentaa ne suoraan WordPressin mediakirjastoon.
  - **OpenAI GPT-4o -integraatio**: Jäsentelee tekstin selkeiksi kappaleiksi, väliotsikoiksi (H2, H3), listoiksi ja korostuksiksi puhtaalla suomen kielellä.
  - **Tyylin ja äänensävyn valinta**: Valittavissa ammattimainen, rento, informatiivinen tai kiinnostava sävy.
  - **Turvallinen luonnostyönkulku**: Tallentaa luodut artikkelit aina luonnoksina (`draft`), jolloin ylläpitäjä voi tarkistaa sisällön ennen julkaisua.
  - **Erillinen hallintasivu**: Selkeä WordPress-hallintasivu tiedostolatauksille, asetuksille ja API-avaimen hallintaan.

---

### ⚛️ Erilliset React-sovellukset

#### 1. [job_application/](job_application/)
- Erillinen React-pohjainen rekrytointisovelluksen kehitysprojekti (Create React App).
- Sisältää lähdekoodin ([src/](job_application/src/)), komponentit lomakekentille ja liitetiedostoille sekä valmiiksi käännetyt `build/`-tiedostot.

#### 2. [my-invoicing-app/](my-invoicing-app/)
- TypeScriptillä toteutettu moderni React-laskutussovellus laskujen hallintaan, luontiin ja asiakaskohtaiseen laskutukseen.

#### 3. [my-invoicing-app-legal/](my-invoicing-app-legal/)
- Laskutussovellukseen liittyvä React-lisenssi- ja lakilaskurisovellus (Create React App).
- Sisältää monikielisen `LegalFeeCalculator`-komponentin (FI, EN, SV, AR) sekä ohjelmiston MIT-lisenssi- ja vastuuvapaustekstit.

---

### 🚀 Laravel SaaS -sovellus: [saas-app/](saas-app/)
- Täysiverinen Laravel-pohjainen SaaS-ohjelmistoalusta.
- **Rakenne:**
  - `app/` – Sovelluksen logiikka (Modelit, Controllerit jne.)
  - `config/` – Konfiguraatiotiedostot
  - `database/` – Tietokantamigraatiot ja seederit
  - `public/` & `resources/` – Julkiset resurssit, näkymät ja assetit
  - `routes/` – Reititykset (`web.php`, `api.php`)
  - `storage/` & `tests/` – Tiedostovarasto ja PHPUnit-testit
  - `webpack.mix.js` – Assetien kääntäminen

---

### 🌐 Staattiset Sivut ja Resurssit

- [css/](css/) – Yleiset CSS-tyylitiedostot.
- [static/](static/) – Yhteiset staattiset kuva- ja tyylitiedostot.
- [css-time.css](css-time.css) – Työajanseurannan mukautetut tyylit.
- [html-ar.html](html-ar.html) – Arabialainen staattinen landing-sivu.
- [html-en.html](html-en.html) – Englanninkielinen staattinen landing-sivu.
- [html-fi.html](html-fi.html) – Suomenkielinen staattinen landing-sivu.

---

### 📄 Juuritiedostot

- [composer.json](composer.json) & [composer.lock](composer.lock) – PHP-riippuvuudet (mm. PHPMailer sähköpostien lähetykseen).
- [LICENSE](LICENSE) – Lisenssiehdot.
- [README.md](README.md) – Tämä dokumentaatio.

---

## 🛠️ Asennus ja Käyttöönotto

1. **WordPress-teema:**
   - Kopioi `i4waresoftware/`-hakemisto WordPress-asennuksen teemahakemistoon: `wp-content/themes/i4waresoftware/`.
   - Aktivoi teema WordPressin hallintapaneelista (*Ulkoasu > Teemat*).

2. **WordPress-lisäosat:**
   - Kopioi haluamasi lisäosahakemistot (esim. `ats_job_application`, `cv-openai-polylang-translator`, `i4ware-ai-seo-agent`, `i4ware-lightbox-gallery`, `word-to-blog-ai` jne.) WordPressin lisäosahakemistoon: `wp-content/plugins/`.
   - Aktivoi lisäosat hallintapaneelista (*Lisäosat > Asennetut lisäosat*).
   - Määritä tarvittavat API-avaimet (esim. OpenAI API Key) lisäosien asetussivuilla.

3. **React-sovellukset (`job_application`, `my-invoicing-app`, `my-invoicing-app-legal`):**
   - Siirry sovellushakemistoon (`cd job_application`, `cd my-invoicing-app` tai `cd my-invoicing-app-legal`).
   - Asenna riippuvuudet: `npm install`
   - Käännä tuotantoversio: `npm run build`

4. **Laravel SaaS -sovellus (`saas-app`):**
   - Siirry hakemistoon `cd saas-app`.
   - Asenna PHP-riippuvuudet: `composer install`
   - Määritä `.env`-ympäristötiedosto ja aja migraatiot: `php artisan migrate`

---

## ⚙️ Järjestelmävaatimukset

- **WordPress:** 6.0 tai uudempi
- **PHP:** 7.4 / 8.0+
- **Node.js & npm:** (React-sovellusten kääntämiseen)
- **Composer:** (PHP-riippuvuuksien hallintaan)
- **MySQL / MariaDB:** Tietokanta WordPressille ja Laravelille

---

## 📊 Google Analytics (GA4) -tapahtumaseuranta

Sivusto sisältää valmiin Google Analytics 4 (GA4) -integraation PayPal-lahjoitus- ja tilauspainikkeiden klikkausten seuraamiseksi. Kun käyttäjä klikkaa mitä tahansa sivuston PayPal-painiketta (`[paypal_donate]`, `[paypal_button]`, `[paypal_subscribe]` tai `[paypal_support_table]`), lähetetään Google Analyticsiin mukautettu tapahtuma `click_paypal_donation`.

Yksityiskohtaiset ohjeet siitä, miten määrität nämä tapahtumat ja niiden parametrit (kuten `donation_type`, `plan_id`, `amount` jne.) Google Analytics -alustalla, löydät teeman omasta dokumentaatiosta:
👉 [i4waresoftware/README.md#google-analytics-ga4-paypal-event-tracking](i4waresoftware/README.md#google-analytics-ga4-paypal-event-tracking)

---

## 📜 Lisenssi

Katso tarkemmat lisenssitiedot [LICENSE](LICENSE)-tiedostosta.
