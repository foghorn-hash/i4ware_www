import './App.css';
import 'bootstrap/dist/css/bootstrap.min.css';
import AttorneyFeeCalculator from './components/AttorneyFeeCalculator/AttorneyFeeCalculator.js';
import { API_DEFAULT_LANGUAGE } from "./constants/apiConstants";
// ES6 module syntax
import LocalizedStrings from 'react-localization';

let strings = new LocalizedStrings({
  en: {
    license: "License",
    copyright: 'Copyright © 2022-present i4ware Software',
    permission: 'Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:',
    conditions: 'The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.',
    warranty: 'THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.',
  },
  fi: {
    license: "Lisenssi",
    copyright: "Tekijänoikeus © 2022–nykyhetki i4ware Software",
    permission: 'Täten myönnetään lupa maksutta kenelle tahansa, joka hankkii tämän ohjelmiston ja siihen liittyvät dokumentaatiotiedostot (jäljempänä "Ohjelmisto"), käyttää Ohjelmistoa ilman rajoituksia, mukaan lukien oikeudet käyttää, kopioida, muokata, yhdistää, julkaista, levittää, alilisensoida ja/tai myydä Ohjelmiston kopioita sekä antaa Ohjelmiston saaneille henkilöille lupa tehdä näin, edellyttäen että seuraavat ehdot täyttyvät:', 
    conditions: 'Yllä oleva tekijänoikeusilmoitus ja tämä lupailmoitus on sisällytettävä kaikkiin Ohjelmiston kopioihin tai olennaisiin osiin siitä.',
    warranty: 'OHJELMISTO TARJOTAAN "SELLAISENAAN", ILMAN MINKÄÄNLAISTA TAKUUTA, OLIVAT NE SITTEN NIMELLISIÄ TAI OLETETTUJA, MUKAAN LUKIEN, MUTTA EI RAJOITTUEN, KAUPALLISUUSTAKUUT, TIETTYYN TARKOITUKSEEN SOPIVUUSTAKUUT JA LOUKKAAMATTOMUUSTAKUUT. MISSÄÄN TAPAUKSESSA TEKIJÄT TAI TEKIJÄNOIKEUDEN HALTIJAT EIVÄT OLE VASTUUSSA MISTÄÄN VAATEISTA, VAHINGOISTA TAI MUUSTA VASTUUSTA, OLI KYSE SOPIMUKSESTA, TUOTTAMUKSESTA TAI MUUSTA SEIKASTA, JOKA JOHTUU OHJELMISTON TAI SEN KÄYTÖN TAI MUUN TOIMINNAN YHTEYDESSÄ TAI SIITÄ JOHTUEN.',
  },
  sv: {
    license: "Licens",
    copyright: "Upphovsrätt © 2022–nutid i4ware Software",
    permission: 'Härmed ges tillstånd, kostnadsfritt, till varje person som erhåller en kopia av denna programvara och tillhörande dokumentationsfiler (nedan kallad "Programvara"), att använda Programvaran utan begränsningar, inklusive rätten att använda, kopiera, modifiera, sammanfoga, publicera, distribuera, underlicensiera och/eller sälja kopior av Programvaran samt att ge personer till vilka Programvaran tillhandahålls tillstånd att göra detsamma, under förutsättning att följande villkor uppfylls:',
    conditions: 'Ovanstående upphovsrättsmeddelande och detta tillståndsmeddelande ska inkluderas i alla kopior eller väsentliga delar av Programvaran.', 
    warranty: 'PROGRAMVARAN TILLHANDAHÅLLS "I BEFINTLIGT SKICK", UTAN GARANTI AV NÅGOT SLAG, VARE SIG UTTRYCKT ELLER UNDERFÖRSTÅDD, INKLUSIVE MEN INTE BEGRÄNSAT TILL GARANTIER OM SÄLJBARHET, ANPASSNING FÖR ETT VISST SYFTE OCH OFRÄNKBARHET. UNDER INGA OMSTÄNDIGHETER SKA UPPHOVSRÄTTSHAVARE ELLER UPPHOVSPERSONER VARA ANSVARIGA FÖR NÅGRA KRAV, SKADOR ELLER ANNAN ANSVARSSKYLDIGHET, OAVSETT OM DET GÄLLER KONTRAKT, SKULD, ELLER ANNAT, SOM UPPSTÅR FRÅN, UTANFÖR ELLER I SAMBAND MED PROGRAMVARAN ELLER ANVÄNDNINGEN ELLER ANDRA ÅTGÄRDER MED PROGRAMVARAN.',
  }
});

function App() {

  const htmlLang = document.documentElement.lang || API_DEFAULT_LANGUAGE;
  strings.setLanguage(htmlLang);

  return (
    <div className="App">
      <AttorneyFeeCalculator />
    </div>
  );
}

export default App;
