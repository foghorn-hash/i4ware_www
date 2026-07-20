# i4ware_www

i4ware Software -verkkosivuston ja siihen liittyvien liiketoimintasovellusten päärepo. Repo sisältää räätälöidyn WordPress-teeman, useita WordPress-lisäosia, erillisiä React-sovelluksia sekä Laravel-pohjaisen SaaS-sovelluksen.

## 📁 Projektin Hakemistorakenne

### 🎨 WordPress-teema: [i4waresoftware/](i4waresoftware/)
i4ware Softwaren virallinen mukautettu WordPress-teema.

- **Pääkomponentit ja tiedostot:**
  - [functions.php](i4waresoftware/functions.php) – Teeman pääfunktiot, asetukset, tyylien/skriptien lataukset ja valikkorekisteröinnit.
  - [jira-timesheet-shortcode.php](i4waresoftware/jira-timesheet-shortcode.php) – `[jira_timesheet]`-lyhytkoodi Jira-työaikakirjausten ja raportoinnin hallintaan.
  - [google-ai-shortcode.php](i4waresoftware/google-ai-shortcode.php) – Google AI -pohjaiset integraatiot ja lyhytkoodit.
  - [web-hotellipalvelu-shortcode.php](i4waresoftware/web-hotellipalvelu-shortcode.php) – Web-hotellipalveluiden esittely- ja tilauskomponentit.
  - [wordpress-kehitys-shortcode.php](i4waresoftware/wordpress-kehitys-shortcode.php) – WordPress-kehityspalvelujen esittelykomponentit.
  - [index.php](i4waresoftware/index.php) & [style.css](i4waresoftware/style.css) – Teeman pääsivu ja tyylit.
  - [assets/](i4waresoftware/assets/) – Teeman JavaScript-, CSS- ja kuvaresurssit (mm. `dropdown-menu.js`, `main.js`, `site.webmanifest`).
  - [template-parts/](i4waresoftware/template-parts/) – Uudelleenkäytettävät pohjaosat:
    - [header.php](i4waresoftware/template-parts/header.php) – Sivuston ylätunniste ja navigaatio.
    - [footer.php](i4waresoftware/template-parts/footer.php) – Sivuston alatunniste.
    - [blog.php](i4waresoftware/template-parts/blog.php) – Blogiartikkeleiden esityspohja.
    - [content.php](i4waresoftware/template-parts/content.php) – Sisältösivujen mallipohja.
  - **ACF (Advanced Custom Fields) JSON -vientialustukset:**
    - `acf-timesheet-landing-fields.json`
    - `import-customers-acf.json`
    - `import-partners-acf.json`
    - `import-team-members-acf.json`
    - `timesheet-landing-content.json`

---

### 🔌 WordPress-lisäosat (Plugins)

#### 1. [ats_job_application/](ats_job_application/) (MH ATS Job Application)
- Rekrytointi- ja hakijaseurantajärjestelmä (ATS).
- Hyödyntää OpenAI-tekoälyä hakijoiden ansioluetteloiden (CV) analysointiin ja automaattiseen pisteytykseen.
- Luo omat tietokantataulukot hakijoille, dokumenteille ja pisteille.
- Sisältää REST API -päätepisteet lomakehakemusten käsittelyyn sekä WordPress-hallintasivun OpenAI API -avaimen säätöön.

#### 2. [cv-openai-polylang-translator/](cv-openai-polylang-translator/) (CV OpenAI Polylang Translator)
- Tekoälypohjainen käännöslisäosa suomenkielisten blogikirjoitusten kääntämiseen englanniksi OpenAI API:n ja Polylang-monikielisyyslisäosan avulla.
- Sisältää hallintasivun muokattaville käännöskomennoille (prompt) ja asetuksille.

#### 3. [i4ware-roi-calculator/](i4ware-roi-calculator/) (I4ware ROI Calculator)
- React-pohjainen sijoitetun pääoman tuoton (ROI) ja tuntihinnoittelun laskuri.
- Upotettavissa mihin tahansa sivuun lyhytkoodilla `[i4ware_roi_calculator]`.

#### 4. [i4ware-team-contact/](i4ware-team-contact/) (Team Contact)
- Tiimin yhteystieto- ja yhteydenottolomakelisäosa.
- Sisältää JavaScript-resurssit interaktiivista lomakekäsittelyä varten.

#### 5. [i4ware-testimonials/](i4ware-testimonials/) (Testimonials)
- Asiakassuositteluiden ja -palautteiden hallintajärjestelmä.
- Rekisteröi mukautetun artikkelityypin (`testimonial`).
- Google reCAPTCHA -roskapostisuojaus sekä hallintaliittymä suositusten tarkistamiseen ja julkaisuun.

#### 6. [i4ware_job_application_form/](i4ware_job_application_form/) (Job Application Form)
- Kevyt työhakemuslomakkeen käsittelijälisäosa.

#### 7. [job-application-form/](job-application-form/) (Job Application Form Extended)
- Laajennettu työhakemuslomakelisäosa staattisilla resursseilla ja kehittyneillä hakemusohjausmekanismeilla.

#### 8. [legal-react-app/](legal-react-app/) (Legal React App)
- React-pohjainen lisäosa lakisääteisten ehtojen ja dokumentaation esittämiseen WordPress-ympäristössä.

#### 9. [revenue-react-app/](revenue-react-app/) (Revenue React App)
- React-pohjainen liikevaihto- ja analytiikkanäkymä (dashboard) WordPress-sivustolle.

#### 10. [woo-rest/](woo-rest/) (WooCommerce REST Integration)
- WooCommerce REST API -laajennus mukautettujen päätepisteiden ja verkkokauppadatan käsittelyyn.

#### 11. [word-to-blog-ai/](word-to-blog-ai/) (Word to Blog AI)
- AI-avusteinen lisäosa, joka muuntaa Word-dokumentit (.docx) automaattisesti muotoilluiksi WordPress-blogiartikkeleiksi.
- Käyttää Composer-riippuvuuksia (mm. PHPWord) sekä OpenAI-integraatiota.

---

### ⚛️ Erilliset React-sovellukset

#### 1. [job_application/](job_application/)
- Erillinen React-pohjainen rekrytointisovellus (Create React App).
- Sisältää lähdekoodin ([src/](job_application/src/)), komponentit ja valmiiksi käännetyt `build/`-tiedostot.

#### 2. [my-invoicing-app/](my-invoicing-app/)
- TypeScriptillä toteutettu erillinen React-laskutussovellus laskujen hallintaan ja luontiin.

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
   - Kopioi haluamasi lisäosahakemistot (esim. `ats_job_application`, `cv-openai-polylang-translator`, `word-to-blog-ai` jne.) WordPressin lisäosahakemistoon: `wp-content/plugins/`.
   - Aktivoi lisäosat hallintapaneelista (*Lisäosat > Asennetut lisäosat*).
   - Määritä tarvittavat API-avaimet (esim. OpenAI API Key) lisäosien asetussivuilla.

3. **React-sovellukset (`job_application`, `my-invoicing-app`):**
   - Siirry sovellushakemistoon (`cd job_application` tai `cd my-invoicing-app`).
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

## 📜 Lisenssi

Katso tarkemmat lisenssitiedot [LICENSE](LICENSE)-tiedostosta.
