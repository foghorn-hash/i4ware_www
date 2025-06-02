<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>i4ware Software – Räätälöityä ohjelmistokehitystä</title>
    <meta name="description" content="i4ware Software tarjoaa räätälöityjä ohjelmistoratkaisuja. Tutustu palveluihimme ja ota yhteyttä tänään.">
    <script src="https://unpkg.com/react@17/umd/react.production.min.js" crossorigin></script>
    <script src="https://unpkg.com/react-dom@17/umd/react-dom.production.min.js" crossorigin></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="static/css/main.fb961db8.css">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <header>
        <div class="header-container">
            <div class="language-switcher">
                <a href="index.php?lang=fi">FI</a> | <a href="home.php?lang=en">EN</a>
            </div>
            <a href="index.php?lang=fi"><img src="assets/i4ware-software.png" alt="i4ware Software -logo" class="logo" /></a>
            <div class="header-text">
                <h1>Mitä teemme?</h1>
                <p>Luomme koodia, joka ratkaisee ongelmasi.</p>
            </div>
    
        </div>
        <div class="nav-container">
            <nav class="navbar">
                <!-- Non-overlay menu for large screens -->
                <ul class="non-overlay-menu">
                    <li><a href="index.php?lang=fi#services">Palvelut</a></li>
                    <li><a href="index.php?lang=fi#technologies">Teknologiat</a></li>
                    <li><a href="index.php?lang=fi#partners">Kumppanuutemme</a></li>
                    <li><a href="index.php?lang=fi#team">Tiimi</a></li>
                    <li><a href="index.php?lang=fi#testimonials">Asiakaspalautteet</a></li>
                    <li><a href="index.php?lang=fi#contact">Ota yhteyttä</a></li>
                </ul>

                <!-- Hamburger button for small screens -->
                <button class="hamburger" id="hamburgerBtn" aria-label="Toggle navigation">
                    ☰
                </button>

                <!-- Overlay menu for small screens -->
                <div class="overlay" id="overlayMenu">
                    <ul>
                        <li><a href="index.php?lang=fi#services">Palvelut</a></li>
                        <li><a href="index.php?lang=fi#technologies">Teknologiat</a></li>
                        <li><a href="index.php?lang=fi#partners">Kumppanuutemme</a></li>
                        <li><a href="index.php?lang=fi#team">Tiimi</a></li>
                        <li><a href="index.php?lang=fi#testimonials">Asiakaspalautteet</a></li>
                        <li><a href="index.php?lang=fi#contact">Ota yhteyttä</a></li>
                    </ul>
                </div>
            </nav>
        </div>
    </header>
    <main>
        <section id="services" class="services">
            <h2>Palvelumme</h2>
            <div class="image-container">
                <img src="assets/dreamstime_s_158835340.jpg" alt="Koodia" />
                <img src="assets/dreamstime_m_50188994.jpg" alt="Koodia" />
            </div>
            <p>Tarjoamme kattavia ohjelmointipalveluita, jotka räätälöidään tarpeidesi mukaan. Osaamisemme kattaa:</p>
            <ul>
                <li>Web-kehityksen moderneilla teknologioilla (React, Laravel, jne.)</li>
                <li>Integraatiot ja API-kehitys</li>
                <li>Verkkosivustojen ja sovellusten suunnittelu ja toteutus</li>
                <li>Ohjelmistokehitys ja -ylläpito</li>
                <li>Tehokkaat pilviratkaisut</li>
            </ul>
            <div id="technologies">
                <h2>Teknologiat:</h2>
                <div class="image-container">
                    <img src="assets/dreamstime_xl_88380514.jpg" alt="Koodia" />
                    <img src="assets/deep-learning-ai-empowering-businesses-with-intel-2023-11-27-04-58-50-utc.jpg" alt="Tekoäly" />
                </div>
                <p>Tomcat, Apache 2, Python, Java EE, Spring Boot, Ubuntu Server, Red Hat Enterprise Linux, MySQL, PostgreSQL, PHP, Laravel, React, TypeScript, CSS, HTML, XML, AJAX, REST, RESTful, JSON, JSONP, JavaScript, Node.js, Firebase (NoSQL-tietokanta), Firebase (PHP-kehys JSON Web Tokenille), React Native, C#</p>
                <p>Ota meihin yhteyttä sähköpostitse: <a href="mailto:info@i4ware.fi">info@i4ware.fi</a>; tai puhelimitse: +358 40 8200 691, niin keskustellaan tarkemmin tarpeistasi ja siitä, miten voimme auttaa sinua saavuttamaan tavoitteesi.</p>
            </div>
            <div id="partners">
                <h2>Kumppanuudet</h2>
                <p>Olemme ylpeitä kumppanuuksistamme ja sertifikaateistamme, jotka tukevat asiakkaitamme parhaalla mahdollisella tavalla. Kumppanuutemme tarjoavat meille pääsyn uusimpiin teknologioihin ja resursseihin, jotta voimme tarjota asiakkaillemme parasta mahdollista palvelua.</p>
                <p>Olemme kumppaneita seuraavien organisaatioiden kanssa:</p>
                <div class="up-logo-container">
                    <a href="https://www.yrittajat.fi/" target="_blank">
                        <img src="assets/jasesenyritys_banneri_25_1000x500_fin_musta.png" class="up-partner-logo" alt="Yrittäjätkumppanilogo" />
                    </a>    
                </div>
                <div class="logo-container">
                    <a href="https://www.redhat.com/" target="_blank">
                        <img src="assets/red_hat-technology_partner.png" class="partner-logo" alt="Red Hat -teknologiakumppanilogo" />
                    </a>
                    <a href="https://www.redhat.com/" target="_blank">
                        <img src="assets/rh_readyisvlogo_rgb.png" class="partner-logo" alt="Red Hat -teknologiakumppanilogo" />
                    </a>
                    <a href="https://marketplace.atlassian.com/" target="_blank">
                        <img src="assets/marketplace_partner_wht_nobg.png" class="partner-logo" alt="Atlassian Marketplace -kumppanilogo" />
                    </a>
                    <a href="https://www.yrittajat.fi/" target="_blank">
                        <img src="assets/jasenyritys_banneri_23_625x313px_fin_musta.jpg" class="partner-logo" alt="Yrittäjätkumppanilogo" />
                    </a>
                    <a href="https://www.yrittajat.fi/" target="_blank">
                        <img src="assets/jasenyritys_banneri_24_625x312px_fin_musta.png" class="partner-logo" alt="Yrittäjätkumppanilogo" />
                    </a>
                    <a href="https://netvisor.fi/" target="_blank">
                        <img src="assets/netvisor-logo-vud22-horizontal-cutoutwhite-900px.png" class="partner-logo" alt="Netvisorkumppanilogo" />
                    </a>
                </div>
                <div class="ent-logo-container">
                    <a href="https://www.yrittajat.fi/" target="_blank">
                        <img src="assets/sy_jasenyritys_2017_150x75px.png" class="partner-logo" alt="Yrittäjätkumppanilogo" />
                    </a>
                    <a href="https://www.yrittajat.fi/" target="_blank">
                        <img src="assets/sy_jasenyritys2018_suomi_150x75.png" class="partner-logo" alt="Yrittäjätkumppanilogo" />
                    </a>
                    <a href="https://www.yrittajat.fi/" target="_blank">
                        <img src="assets/sy_jasenyritys2019_su_150x75_0.png" class="partner-logo" alt="Yrittäjätkumppanilogo" />
                    </a>
                </div>
            </div>
        </section>

        <section id="testimonials" class="testimonials">
            <div class="image-container">
                <img src="assets/dreamstime_xl_31414185.jpg" alt="Matematiikkaa" />
                <img src="assets/group-of-young-people-working-together-creative-b-2023-11-27-05-09-35-utc.jpg" alt="Yhteystyö" />
            </div>  
            <h2>Asiakaspalautteet</h2>
            <p>"i4ware Software ratkaisi liiketoimintamme kriittiset IT-haasteet nopeasti ja ammattitaitoisesti."</p>
            <h2>Tyytyväinen asiakas</h2>
            <div class="testimonial">
                <p><strong>Vili Vepsäläinen</strong>, Kehitysjohtaja (Doberman Consulting Oy) ja Koulutussuunnittelija (Edupoli):  
                ”Mielestäni Matti Kiviharju suoritti tehtävänsä kiitettävällä nopeudella ja tehokkuudella. Hän toi työhön oman luovan panoksensa ja onnistui kehittämään työryhmänsä toimintaa työn loppuun saattamiseksi. Matti Kiviharju toteutti Etelä-Uudenmaan koulutuskuntayhtymän (Edupoli) ADP-tiimin verkkosivuston kehitysprojektin etätyönä harjoittelijana. Hän hoiti tehtävänsä harjoittelijatiimin projektipäällikkönä erittäin hyvin.”</p>
                <a href="https://www.linkedin.com/in/vilivepsalainen/" target="_blank">LinkedIn-profiili</a>
            </div>
            
            <div class="testimonial">
                <p><strong>Olli Saranen</strong>, Toimitusjohtaja, Avoset Oy:  
                ”Matti Kiviharjun tehtävät tulevat suoritetuiksi luovalla tavalla, ja hän tekee aina sen, mitä sopimuksessa on sovittu. Matti sopii ohjelmistokehitysalalle ohjelmoijana, ja se on hänen tulevaisuuden tehtävänsä.”</p>
                <a href="https://www.linkedin.com/in/ollisaranen/" target="_blank">LinkedIn-profiili</a>
            </div>
            
            <div class="testimonial">
                <p><strong>Henri Memonen</strong>, Toimitusjohtaja:  
                ”Matti on erittäin luova ja tekee aina tilatut työt. Olen työskennellyt hänen kanssaan vuodesta 2013. Hän on toteuttanut erittäin laajoja järjestelmiä. Suosittelen todella lämpimästi Matti Kiviharjua kaikille ja tarkoitan sitä.”</p>
                <a href="https://www.linkedin.com/in/henri-memonen/" target="_blank">LinkedIn-profiili</a>
            </div>
            
            <div class="testimonial">
                <p><strong>Mikko Mäkelä</strong>, Toimitusjohtaja:  
                ”Matti on äärimmäisen kykenevä hoitamaan useita tehtäviä samanaikaisesti. Hän oli tiimimme johtaja suuressa verkkosivustoportaaliprojektissa täällä Suomessa. Hänellä on vahvat johtamistaidot ja erinomainen sinnikkyys, jotka auttavat hallitsemaan stressiä.”</p>
                <a href="https://www.linkedin.com/in/ruffnekk/" target="_blank">LinkedIn-profiili</a>
            </div>

            <div class="testimonial">
                <p><strong>Pauli Kiviharju</strong>, Asianajaja, Asianajotoimisto Pauli Kiviharju Ky:  
                ”Matti Kiviharju toteutti verkkosivut toimistolleni, tarjoten erityisesti IT/ICT-alan asianajajan tarpeisiin räätälöityjä palveluita. Hän työskenteli ammattitaitoisesti ja toimitti ratkaisun nopeasti ja tehokkaasti.”</p>
                <a href="https://www.paulikiviharju.fi/" target="_blank">Kotisivut</a>
            </div>

            <div class="testimonial">
                <h2>i4ware Software ja Polarion ALM:n Diagramming Tool</h2>
                <p>i4ware Software toteutti alun perin <strong>Diagramming Toolin</strong> Polarion ALM -ohjelmistoon vuonna <strong>2010</strong>, jolloin ohjelmiston omisti saksalainen <strong>Polarion Software</strong>. Myöhemmin <strong>Siemens hankki Polarionin</strong>, ja nykyään ohjelmisto tunnetaan nimellä <strong>Siemens Polarion AG</strong>.</p>
                <p>Tämä työkalu on ollut keskeinen osa Polarion ALM:n sovelluksen elinkaaren hallinnan ekosysteemiä. i4ware Softwaren ohjelmistoinsinöörien asiantuntemus on parantanut merkittävästi työkalun toiminnallisuutta ja käytettävyyttä.</p>
                <p>Teknologiat: Gliffy REST API, Polarion SDK, Eclipse, Java, JSP, Ext JS, CSS ja HTML</p>
                <blockquote>
                    <p><em>"Polarion ALM:n Diagramming Tool, jonka i4ware Software alun perin toteutti vuonna 2010, on ollut keskeinen osa sovelluksen elinkaaren hallinnan ekosysteemiämme. i4ware Softwaren ohjelmistoinsinöörien asiantuntemus on merkittävästi parantanut työkalun toiminnallisuutta ja käytettävyyttä."</em></p>
                    <p>- Siemens Polarion AG</p>
                </blockquote>
                <h2>Esittelyvideot</h2>
                <div class="video-container">
                    <iframe src="https://www.youtube.com/embed/ocOENQhqEjk" allowfullscreen></iframe>
                    <iframe src="https://www.youtube.com/embed/DD8ctrwCfrQ" allowfullscreen></iframe>                    
                </div>
            </div>

            <div class="testimonial">
                <h2>i4ware Timesheet for Jira -pilvi</h2>
                <p>i4ware Software toteutti <strong>i4ware Timesheet for Jira -pilven</strong>, joka on integroitu Atlassianin Jira-työkaluihin. Tämä työkalu mahdollistaa tehokkaan ajanhallinnan ja projektinhallinnan.</p>
                <p>i4ware Timesheet for Jira -pilvi on suunniteltu erityisesti tiimien ja projektipäälliköiden tarpeisiin, ja se tarjoaa kattavat raportointiominaisuudet sekä joustavat aikaraportointivaihtoehdot.</p>
                <p>Työkalu on saanut positiivista palautetta käyttäjiltä, ja se on auttanut monia organisaatioita parantamaan projektinhallintaprosessejaan.</p>
                <p>Teknologiat: Zend Framework, Atlassian Jira Cloud REST API, Ext JS, CSS ja HTML</p>
                <blockquote>
                    <p><em>"i4ware Timesheet for Jira -pilvi on ollut erinomainen lisä Atlassianin Jira-työkaluihin. Se on parantanut tiimimme ajanhallintaa ja projektinhallintaprosesseja merkittävästi."</em></p>
                    <p>- Atlassian Marketplace -asiakkaat</p>
                </blockquote>
                <h2>Esittelyvideot</h2>
                <div class="video-container">
                    <iframe src="https://www.youtube.com/embed/zQ8ZVqIMaW4" allowfullscreen></iframe>
                </div>
            </div>

            <div class="testimonial">
                <h2>i4ware OpenAI Chat for Confluence</h2>
                <p>i4ware Software toteutti <strong>i4ware OpenAI Chat for Confluence</strong>, joka on integroitu Atlassianin Confluence Data Center -työkaluihin. Tämä työkalu mahdollistaa tehokkaan keskustelun ja yhteistyön tiimien välillä.</p>
                <p>i4ware OpenAI Chat for Confluence on suunniteltu erityisesti tiimien ja projektipäälliköiden tarpeisiin, ja se tarjoaa kattavat keskusteluominaisuudet sekä joustavat yhteistyömahdollisuudet.</p>
                <p>Työkalu on saanut positiivista palautetta käyttäjiltä, ja se on auttanut monia organisaatioita parantamaan tiimityöskentelyään.</p>
                <blockquote>
                    <p><em>"i4ware OpenAI Chat for Confluence on ollut erinomainen lisä Atlassianin Confluence Data Center -työkaluihin. Se on parantanut tiimimme yhteistyötä ja keskustelua merkittävästi."</em></p>
                    <p>- Atlassian Marketplace -asiakkaat</p>
                </blockquote>
                <p>Teknologiat: Pusher, ChatGPT 4o OpenAI, Atlassian Confluence Java REST API, Java, React, CSS ja HTML</p>
                <h2>Esittelyvideot</h2>
                <div class="video-container">
                    <iframe src="https://www.youtube.com/embed/eQfk34Ry_gM" allowfullscreen></iframe>
                </div>
            </div>

            <div class="testimonial">
                <h2>i4ware SDK</h2>
                <p>i4ware SDK:n lisenssi on <strong>MIT</strong>, joka on hyvin salliva avoimen lähdekoodin lisenssi. Tämä mahdollistaa sekä avoimen lähdekoodin että suljetun lähdekoodin sovellusten kehittämisen asiakkaille ilman rajoituksia. SDK tarjoaa joustavan ja kustannustehokkaan ratkaisun organisaatioille, jotka etsivät skaalautuvaa ja helposti integroitavaa alustaa.</p>
                <p>i4ware SDK on erityisesti suunniteltu vastaamaan nykyaikaisten sovelluskehitystiimien tarpeisiin, tarjoten tehokkaat työkalut ja joustavat ominaisuudet, jotka tukevat nopeaa kehitystä ja helppoa käyttöönottoa.</p>
                <p>Voit tutustua i4ware SDK:hon käytännössä julkisen demon avulla. Demo tarjoaa mahdollisuuden kokeilla SDK:n ominaisuuksia ja nähdä, miten se voi hyödyttää organisaatiotasi.</p>
                <p><a href="https://saas.i4ware.fi/" target="_blank">Tutustu julkiseen demoon täällä</a></p>
                <p><a href="https://github.com/foghorn-hash/i4ware_SDK" target="_blank">Tutustu i4ware SDK:n lähdekoodiin GitHubissa</a></p>
                <blockquote>
                    <p><em>"i4ware SDK on ollut erinomainen ratkaisu organisaatiomme tarpeisiin. Sen kevyt rakenne ja helppo integrointi ovat säästäneet meiltä merkittävästi resursseja."</em></p>
                    <p>- Toimitusjohtaja, SaaS IT -yritys</p>
                    <p><em>"MIT-lisenssi antaa meille vapauden kehittää suljetun lähdekoodin sovelluksia ilman rajoituksia. i4ware SDK on ollut täydellinen valinta projektiimme."</em></p>
                    <p>- Tomitusjohtaja, Startup-yritys</p>
                    <p><em>"PHP- ja React-pohjainen i4ware SDK on huomattavasti kevyempi kuin Java-kilpailijat, mikä on vähentänyt palvelinkustannuksiamme."</em></p>
                    <p>- Toimitusjohtaja, Mainostoimisto</p>
                    <p><em>"i4ware SDK:n kevyt PHP- ja React-pohjainen arkkitehtuuri on ollut merkittävä etu meille. Se on vähentänyt palvelinkustannuksiamme ja parantanut järjestelmän suorituskykyä huomattavasti."</em></p>
                    <p>- Toimitusjohtaja ja Hallituksen puheenjohtaja, Henkilöstöpalveluyritys</p>
                </blockquote>
                <p>Teknologiat: Pusher, ChatGPT 4o OpenAI, PHP, Laravel, jQuery, JavaScript, React, CSS ja HTML</p>
                <h2>Esittelyvideot</h2>
                <div class="video-container">
                    <iframe src="https://www.youtube.com/embed/ewjaUiTantg" allowfullscreen></iframe>
                    <iframe src="https://www.youtube.com/embed/06LzzTwjs1A" allowfullscreen></iframe>
                </div>
            </div>
        </section>

        <section id="team">
            <h2>Tiimi</h2>
            <div class="team-member">
                <img src="assets/matti.jpg" alt="Matti Kiviharju" />
                <h3>Matti Kiviharju, Tradenomi</h3>
                <p>Yrittäjä, perustaja ja asiantuntija Full-Stack-kehittäjä sekä arkkitehti</p>
                <p>Matti Kiviharju on kokenut ohjelmistoarkkitehti ja Full-Stack-kehittäjä, joka hallitsee sekä käyttöliittymien että taustajärjestelmien suunnittelun. Hänen visionäärinen johtajuutensa ja tekninen asiantuntemuksensa ovat olleet avainasemassa yrityksen menestyksessä.</p>
                <p>Hän on työskennellyt lukuisissa projekteissa eri toimialoilla, ja hänen kykynsä yhdistää liiketoiminnan tavoitteet teknisiin ratkaisuihin tekee hänestä arvokkaan resurssin asiakkaillemme.</p>
                <p>Hän on myös aktiivinen yhteisön jäsen ja jakaa mielellään tietämystään ja kokemustaan ohjelmistokehityksen alalta.</p>
                <p>Hän on työskennellyt ohjelmistokehityksen parissa vuodesta 2004 ja erikoistunut erityisesti Java- ja PHP-teknologioihin.</p>
                <p>Hän on myös sertifioitu Red Hat B-to-B -myyjä:</p>
                <div class="certificate-container">
                    <img src="assets/redhat1.png" alt="Red Hat -sertifikaatti 1" />
                    <img src="assets/redhat2.png" alt="Red Hat -sertifikaatti 2" />
                </div>
            </div>
        </section>

        <section class="contact" id="contact">
            <h2>Ota yhteyttä</h2>
            <p>Olemme täällä auttamassa sinua. Ota meihin yhteyttä sähköpostitse: <a href="mailto:info@i4ware.fi">info@i4ware.fi</a>; tai puhelimitse: +358 40 8200 691.</p>
            <p>ALV-rek. FI27395946</p>
            <p>Y-tunnus: 2739594-6</p>
            <div id="root"></div>
            <script src="static/js/main.4d9a1c42.js"></script>
        </section>
    </main>
    <footer>
        <h2>Oikeudellisetsopimukset</h2>
        <ul class="legal-links">
            <li><a href="tietosuojakaytanto.php">Tietosuojakäytäntö</a></li>    
        </ul>
        <p>&copy; 2025 i4ware Software. Kaikki oikeudet pidätetään.</p>
    </footer>
    <button id="scrollToTopBtn" title="Scroll to top">↑</button>
    <script>
        // Get the button
        const scrollToTopBtn = document.getElementById("scrollToTopBtn");

        // Show the button when the user scrolls down 100px
        window.onscroll = function () {
            if (document.body.scrollTop > 100 || document.documentElement.scrollTop > 100) {
                scrollToTopBtn.style.display = "block";
            } else {
                scrollToTopBtn.style.display = "none";
            }
        };

        // Scroll to the top when the button is clicked
        scrollToTopBtn.onclick = function () {
            window.scrollTo({ top: 0, behavior: "smooth" });
        };

        const hamburgerBtn = document.getElementById("hamburgerBtn");
        const overlayMenu = document.getElementById("overlayMenu");

        // Toggle the overlay menu on hamburger button click
        hamburgerBtn.addEventListener("click", () => {
            overlayMenu.classList.toggle("active");
        });

        // Close the menu when clicking outside or on a link
        overlayMenu.addEventListener("click", (e) => {
            if (e.target.tagName === "A" || e.target === overlayMenu) {
                overlayMenu.classList.remove("active");
            }
        });
    </script>
</body>
</html>