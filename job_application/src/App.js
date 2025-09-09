import React, { useEffect, useMemo, useState, useRef } from "react";
import ReCAPTCHA from "react-google-recaptcha";
import "./App.css";
import { API_BASE_URL, API_DEFAULT_LANGUAGE } from "./constants/apiConstants";
import LocalizedStrings from "react-localization";

// --- Localization dictionaries ---
let strings = new LocalizedStrings({
  en: {
    appTitle: "Job Application Form",
    // Generic / buttons
    submit: "Submit",
    reset: "Reset",
    requiredMark: "*",

    // Validation & errors
    errPdfOnly: "Only PDF files are allowed.",
    errMaxSize: "File size exceeds 8 MB.",

    // Section labels
    personalInfo: "Personal Information",
    educationInfo: "Education & Experience",

    // Fields
    firstname: "Firstname",
    lastname: "Lastname",
    email: "Email",
    country: "Country",
    address1: "Address Line 1",
    address2: "Address Line 2",
    zip: "Zip",
    city: "City",
    phone: "Phone",
    mobile: "Mobile",
    www: "Website",
    cv: "Curriculum Vitae (PDF, max 8MB)",
    motivation: "Motivation Letter (PDF, max 8MB)",
    application: "Job Application (PDF, max 8MB)",
    additional: "Additional Information",
    notRobot: "I'm not a robot",

    education: "Education",
    qualifications: "Qualifications",
    skills: "Skills and Knowledge",
    workexp: "Work Experience",

    // Placeholders
    phFirstname: "John",
    phLastname: "Doe",
    phEmail: "john.doe@example.com",
    phCountry: "Finland",
    phAddress1: "Example Street 123",
    phAddress2: "Apartment 45B",
    phZip: "33100",
    phCity: "Tampere",
    phPhone: "+358 3 123 4567",
    phMobile: "+358 40 123 4567",
    phWWW: "http://www.example.com",
    phAdditional:
      "I have 16 years of experience working in the IT/software development field...",
    phEducation: "Degree Programme in Business Information Technology, TAMK, 2007-2011",
    phQualifications: "Red Hat Certified Salesperson, Red Hat University, 2011",
    phSkills: "HTML, CSS, JavaScript, React, SQL, Java, PHP, Python...",
    phWorkexp: "i4ware Software, Product Owner, 2004 - present",

    // UI
    chooseLang: "Language",
    formSubmitted: "Form submitted! (Backend integration pending)",
  },
  fi: {
    appTitle: "Työhakemuslomake",
    // Generic / buttons
    submit: "Lähetä",
    reset: "Tyhjennä",
    requiredMark: "*",

    // Validation & errors
    errPdfOnly: "Vain PDF-tiedostot ovat sallittuja.",
    errMaxSize: "Tiedoston koko ylittää 8 Mt.",

    // Section labels
    personalInfo: "Henkilötiedot",
    educationInfo: "Koulutus ja työkokemus",

    // Fields
    firstname: "Etunimi",
    lastname: "Sukunimi",
    email: "Sähköposti",
    country: "Maa",
    address1: "Katuosoite",
    address2: "Osoitteen lisätieto",
    zip: "Postinumero",
    city: "Kaupunki",
    phone: "Puhelin",
    mobile: "Matkapuhelin",
    www: "WWW",
    cv: "Ansioluettelo (PDF, max 8 Mt)",
    motivation: "Motivaatiokirje (PDF, max 8 Mt)",
    application: "Työhakemus (PDF, max 8 Mt)",
    additional: "Lisätiedot",
    notRobot: "En ole robotti",

    education: "Koulutus",
    qualifications: "Pätevyydet",
    skills: "Taidot ja osaaminen",
    workexp: "Työkokemus",

    // Placeholders
    phFirstname: "Matti",
    phLastname: "Meikäläinen",
    phEmail: "etunimi.sukunimi@esimerkki.fi",
    phCountry: "Suomi",
    phAddress1: "Esimerkkikatu 123",
    phAddress2: "Asunto 45B",
    phZip: "33100",
    phCity: "Tampere",
    phPhone: "+358 3 123 4567",
    phMobile: "+358 40 123 4567",
    phWWW: "http://www.esimerkki.fi",
    phAdditional:
      "Minulla on 16 vuoden kokemus IT-/ohjelmistokehitysalalta...",
    phEducation: "Tietojenkäsittelyn koulutusohjelma, TAMK, 2007–2011",
    phQualifications: "Red Hat Certified Salesperson, Red Hat University, 2011",
    phSkills: "HTML, CSS, JavaScript, React, SQL, Java, PHP, Python...",
    phWorkexp: "i4ware Software, Product Owner, 2004 – nykyinen",

    // UI
    chooseLang: "Kieli",
    formSubmitted: "Lomake lähetetty! (Taustajärjestelmä tulossa)",
  },
  sv: {
    appTitle: "Jobbansökningsformulär",
    // Generic / buttons
    submit: "Skicka",
    reset: "Återställ",
    requiredMark: "*",

    // Validation & errors
    errPdfOnly: "Endast PDF-filer är tillåtna.",
    errMaxSize: "Filstorleken överskrider 8 MB.",

    // Section labels
    personalInfo: "Personuppgifter",
    educationInfo: "Utbildning och arbetslivserfarenhet",

    // Fields
    firstname: "Förnamn",
    lastname: "Efternamn",
    email: "E-post",
    country: "Land",
    address1: "Adressrad 1",
    address2: "Adressrad 2",
    zip: "Postnummer",
    city: "Stad",
    phone: "Telefon",
    mobile: "Mobil",
    www: "Webbplats",
    cv: "CV (PDF, max 8 MB)",
    motivation: "Motivationsbrev (PDF, max 8 MB)",
    application: "Arbetsansökan (PDF, max 8 MB)",
    additional: "Ytterligare information",
    notRobot: "Jag är inte en robot",

    education: "Utbildning",
    qualifications: "Kvalifikationer",
    skills: "Färdigheter och kunskaper",
    workexp: "Arbetslivserfarenhet",

    // Placeholders
    phFirstname: "Johan",
    phLastname: "Svensson",
    phEmail: "johan.svensson@example.com",
    phCountry: "Finland",
    phAddress1: "Exempelgatan 123",
    phAddress2: "Lägenhet 45B",
    phZip: "33100",
    phCity: "Tammerfors",
    phPhone: "+358 3 123 4567",
    phMobile: "+358 40 123 4567",
    phWWW: "http://www.exempel.se",
    phAdditional:
      "Jag har 16 års erfarenhet av IT-/programvarubranschen...",
    phEducation: "Utbildningsprogram i databehandling, TAMK, 2007–2011",
    phQualifications: "Red Hat Certified Salesperson, Red Hat University, 2011",
    phSkills: "HTML, CSS, JavaScript, React, SQL, Java, PHP, Python...",
    phWorkexp: "i4ware Software, Product Owner, 2004 – nu",

    // UI
    chooseLang: "Språk",
    formSubmitted: "Formuläret skickades! (Backendintegration på gång)",
  },
});

export default function App() {
  const [formData, setFormData] = useState({});
  const [fileError, setFileError] = useState("");
  const [recaptchaToken, setRecaptchaToken] = useState("");
  const recaptchaRef = useRef(null);

  // Determine initial language from <html lang> or API default
  const [lang, setLang] = useState(API_DEFAULT_LANGUAGE);

  const htmlLang = document.documentElement.lang || API_DEFAULT_LANGUAGE;
  strings.setLanguage(htmlLang);

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!recaptchaToken) return; // block if no token

    // send token with your payload
    const res = await fetch(window.JAF_REC.verifyUrl, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ ...formData, recaptcha: recaptchaToken }),
    });
    const data = await res.json();

    if (data?.ok) {
      alert("Form submitted!");      // your localized message
      recaptchaRef.current?.reset(); // reset captcha
      setRecaptchaToken("");
    } else {
      alert("reCAPTCHA failed.");
    }
  };

  useEffect(() => {
    strings.setLanguage(lang);
  }, [lang]);

  const handleChange = (e) => {
    const { name, value, type, checked, files } = e.target;
    if (type === "file") {
      const file = files?.[0];
      if (file && file.type !== "application/pdf") {
        setFileError(strings.errPdfOnly);
        return;
      }
      if (file && file.size > 8192 * 1024) {
        setFileError(strings.errMaxSize);
        return;
      }
      setFileError("");
      setFormData({ ...formData, [name]: file });
    } else {
      setFormData({ ...formData, [name]: type === "checkbox" ? checked : value });
    }
  };

  return (
    <div className="app-container">
      <form className="app-card" onSubmit={handleSubmit}>
        <div className="two-col">
          <div className="ats-form-section">
            <h2>{strings.personalInfo}</h2>

            <label>
              {strings.firstname}
              {strings.requiredMark}
            </label>
            <input
              type="text"
              name="firstname"
              placeholder={strings.phFirstname}
              required
              onChange={handleChange}
            />

            <label>
              {strings.lastname}
              {strings.requiredMark}
            </label>
            <input
              type="text"
              name="lastname"
              placeholder={strings.phLastname}
              required
              onChange={handleChange}
            />

            <label>
              {strings.email}
              {strings.requiredMark}
            </label>
            <input
              type="email"
              name="email"
              placeholder={strings.phEmail}
              required
              onChange={handleChange}
            />

            <label>
              {strings.country}
              {strings.requiredMark}
            </label>
            <input
              type="text"
              name="country"
              placeholder={strings.phCountry}
              required
              onChange={handleChange}
            />

            <label>
              {strings.address1}
              {strings.requiredMark}
            </label>
            <input
              type="text"
              name="address1"
              placeholder={strings.phAddress1}
              required
              onChange={handleChange}
            />

            <label>{strings.address2}</label>
            <input
              type="text"
              name="address2"
              placeholder={strings.phAddress2}
              onChange={handleChange}
            />

            <label>
              {strings.zip}
              {strings.requiredMark}
            </label>
            <input
              type="text"
              name="zip"
              placeholder={strings.phZip}
              required
              onChange={handleChange}
            />

            <label>
              {strings.city}
              {strings.requiredMark}
            </label>
            <input
              type="text"
              name="city"
              placeholder={strings.phCity}
              required
              onChange={handleChange}
            />

            <label>{strings.phone}</label>
            <input
              type="tel"
              name="phone"
              placeholder={strings.phPhone}
              onChange={handleChange}
            />

            <label>
              {strings.mobile}
              {strings.requiredMark}
            </label>
            <input
              type="tel"
              name="mobile"
              placeholder={strings.phMobile}
              required
              onChange={handleChange}
            />

            <label>{strings.www}</label>
            <input
              type="url"
              name="www"
              placeholder={strings.phWWW}
              onChange={handleChange}
            />

            <label>
              {strings.cv}
              {strings.requiredMark}
            </label>
            <input
              type="file"
              name="cv"
              accept="application/pdf"
              required
              onChange={handleChange}
            />

            <label>{strings.motivation}</label>
            <input
              type="file"
              name="motivation"
              accept="application/pdf"
              onChange={handleChange}
            />

            <label>{strings.application}</label>
            <input
              type="file"
              name="application"
              accept="application/pdf"
              onChange={handleChange}
            />

            {fileError && <p className="error-text">{fileError}</p>}

            <label>
              {strings.additional}
              {strings.requiredMark}
            </label>
            <textarea
              name="additional"
              placeholder={strings.phAdditional}
              required
              onChange={handleChange}
            />
          </div>

          <div className="ats-form-section">
            <h2>{strings.educationInfo}</h2>

            <label>
              {strings.education}
              {strings.requiredMark}
            </label>
            <textarea
              name="education"
              placeholder={strings.phEducation}
              required
              onChange={handleChange}
            />

            <label>
              {strings.qualifications}
              {strings.requiredMark}
            </label>
            <textarea
              name="qualifications"
              placeholder={strings.phQualifications}
              required
              onChange={handleChange}
            />

            <label>
              {strings.skills}
              {strings.requiredMark}
            </label>
            <textarea
              name="skills"
              placeholder={strings.phSkills}
              required
              onChange={handleChange}
            />

            <label>
              {strings.workexp}
              {strings.requiredMark}
            </label>
            <textarea
              name="workexp"
              placeholder={strings.phWorkexp}
              required
              onChange={handleChange}
            />
          </div>
        </div>

        <div className="form-actions">
          <button type="submit" className="btn btn-primary">{strings.submit}</button>
          <button type="reset" className="btn btn-secondary">{strings.reset}</button>
        </div>
      </form>
    </div>
  );
}
