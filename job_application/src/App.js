import React, { useEffect, useState, useRef } from "react";
import "./App.css";
import { API_DEFAULT_LANGUAGE } from "./constants/apiConstants";
import LocalizedStrings from "react-localization";

// --- Localization dictionaries ---
let strings = new LocalizedStrings({
  en: {
    appTitle: "Job Application Form",
    submit: "Submit Application",
    submitting: "Submitting application...",
    reset: "Reset Form",
    requiredMark: "*",

    // Validation & errors
    errPdfOnly: "Only PDF files are allowed.",
    errMaxSize: "File size exceeds 8 MB.",
    errCvRequired: "Please attach your CV (PDF).",
    submissionFailed: "Submission failed",
    errFillRequired: "Please fill in all required fields.",

    // Job / Position & Description
    jobPosition: "Applied Position or Internship",
    selectPositionPlaceholder: "Select position or internship...",
    otherPosition: "Other Position / Open Application / Internship",
    customPositionPlaceholder: "Enter job title or internship...",
    jobDescHeading: "About the Positions & Internship at i4ware",
    openingsListTitle: "Open Positions & Internship Opportunities:",
    badgeJobIntern: "Career & Internship",
    badgeRemote: "Remote & Flexible",
    badgeSkills: "React • PHP • WordPress • Laravel • Jira",

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
    phWWW: "https://www.example.com",
    phAdditional: "Tell us about your background, career goals, or internship requirements...",
    phEducation: "Degree Programme in Business Information Technology, TAMK, 2018-2022",
    phQualifications: "AWS Certified Developer, Scrum Master...",
    phSkills: "HTML, CSS, JavaScript, React, PHP, WordPress, Laravel, SQL, Git...",
    phWorkexp: "Software Developer, Acme Corp, 2021 - 2024",

    // Success screen
    successTitle: "Application Received Successfully!",
    successDesc: "Thank you for applying to i4ware Software! Your application and attached documents have been safely received and stored in our ATS.",
    applicantRef: "Application Reference ID",
    appliedFor: "Applied Position",
    weWillContact: "Our recruiting team will review your application and contact you as soon as possible.",
    submitAnother: "Submit Another Application"
  },
  fi: {
    appTitle: "Työhakemuslomake",
    submit: "Lähetä hakemus",
    submitting: "Lähetetään hakemusta...",
    reset: "Tyhjennä lomake",
    requiredMark: "*",

    // Validation & errors
    errPdfOnly: "Vain PDF-tiedostot ovat sallittuja.",
    errMaxSize: "Tiedoston koko ylittää 8 Mt.",
    errCvRequired: "Liitäthän mukaan ansioluettelosi (CV, PDF).",
    submissionFailed: "Hakemuksen lähetys epäonnistui",
    errFillRequired: "Tarkista lomake ja täytä kaikki pakolliset kentät.",

    // Job / Position & Description
    jobPosition: "Haettu työpaikka tai harjoittelupaikka",
    selectPositionPlaceholder: "Valitse haettava tehtävä tai harjoittelu...",
    otherPosition: "Muu tehtävä / Avoin hakemus / Harjoittelu",
    customPositionPlaceholder: "Kirjoita haettava tehtävänimike...",
    jobDescHeading: "Tietoa työ- ja harjoittelupaikoista i4warella",
    openingsListTitle: "Avoinna olevat työ- ja harjoittelupaikat:",
    badgeJobIntern: "Työpaikka & Harjoittelu",
    badgeRemote: "Joustava etätyö",
    badgeSkills: "React • PHP • WordPress • Laravel • Jira",

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
    www: "WWW-sivu",
    cv: "Ansioluettelo / CV (PDF, max 8 Mt)",
    motivation: "Motivaatiokirje (PDF, max 8 Mt)",
    application: "Vapaamuotoinen hakemus (PDF, max 8 Mt)",
    additional: "Lisätiedot",

    education: "Koulutus",
    qualifications: "Pätevyydet ja sertifikaatit",
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
    phWWW: "https://www.esimerkki.fi",
    phAdditional: "Kerro itsestäsi, taustoistasi tai harjoitteluun liittyvistä toiveistasi...",
    phEducation: "Tietojenkäsittelyn koulutusohjelma (Tradenomi / DI), TAMK, 2018–2022",
    phQualifications: "Red Hat Certified, AWS Certified, Scrum Master...",
    phSkills: "HTML, CSS, JavaScript, React, PHP, WordPress, Laravel, SQL, Git...",
    phWorkexp: "Ohjelmistokehittäjä, Esimerkki Oy, 2021 – 2024",

    // Success screen
    successTitle: "Hakemuksesi on vastaanotettu onnistuneesti!",
    successDesc: "Kiitos mielenkiinnostasi i4ware Softwarea kohtaan! Hakemuksesi ja liitetiedostosi on tallennettu rekrytointijärjestelmäämme.",
    applicantRef: "Hakemusnumero",
    appliedFor: "Haettu tehtävä",
    weWillContact: "Käsittelemme hakemuksesi huolellisesti ja olemme sinuun yhteydessä mahdollisimman pian.",
    submitAnother: "Lähetä toinen hakemus"
  },
  sv: {
    appTitle: "Jobbansökningsformulär",
    submit: "Skicka ansökan",
    submitting: "Skickar ansökan...",
    reset: "Återställ formulär",
    requiredMark: "*",

    // Validation & errors
    errPdfOnly: "Endast PDF-filer är tillåtna.",
    errMaxSize: "Filstorleken överskrider 8 MB.",
    errCvRequired: "Bifoga ditt CV (PDF).",
    submissionFailed: "Inlämningen misslyckades",
    errFillRequired: "Vänligen fyll i alla obligatoriska fält.",

    // Job / Position & Description
    jobPosition: "Sökt tjänst eller praktikplats",
    selectPositionPlaceholder: "Välj tjänst eller praktik...",
    otherPosition: "Annan tjänst / Öppen ansökan / Praktik",
    customPositionPlaceholder: "Skriv tjänstetitel eller praktik...",
    jobDescHeading: "Om tjänsterna och praktikplatserna på i4ware",
    openingsListTitle: "Lediga jobb och praktikplatser:",
    badgeJobIntern: "Karriär & Praktik",
    badgeRemote: "Flexibelt distansarbete",
    badgeSkills: "React • PHP • WordPress • Laravel • Jira",

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
    phWWW: "https://www.exempel.se",
    phAdditional: "Berätta om din bakgrund och dina karriärmål...",
    phEducation: "Utbildningsprogram i databehandling, TAMK, 2018–2022",
    phQualifications: "Certifieringar...",
    phSkills: "HTML, CSS, JavaScript, React, PHP, WordPress, Laravel, SQL, Git...",
    phWorkexp: "Programvaruutvecklare, 2021 – 2024",

    // Success screen
    successTitle: "Ansökan mottagen!",
    successDesc: "Tack för ditt intresse för i4ware Software! Din ansökan har sparats i vårt rekryteringssystem.",
    applicantRef: "Ansökningsreferens",
    appliedFor: "Sökt tjänst",
    weWillContact: "Vi granskar din ansökan och återkommer till dig så snart som möjligt.",
    submitAnother: "Skicka en ny ansökan"
  },
  ar: {
    appTitle: "نموذج طلب الوظيفة والتدريب",
    submit: "إرسال الطلب",
    submitting: "جاري إرسال الطلب...",
    reset: "إعادة تعيين النموذج",
    requiredMark: "*",

    // Validation & errors
    errPdfOnly: "يُسمح فقط بملفات PDF.",
    errMaxSize: "يتجاوز حجم الملف 8 ميجابايت.",
    errCvRequired: "يرجى إرفاق السيرة الذاتية (PDF).",
    submissionFailed: "فشل إرسال الطلب",
    errFillRequired: "يرجى ملء جميع الحقول المطلوبة.",

    // Job / Position & Description
    jobPosition: "الوظيفة أو فترة التدريب المطلوبة",
    selectPositionPlaceholder: "حدد الوظيفة أو التدريب...",
    otherPosition: "وظيفة أخرى / طلب مفتوح / تدريب",
    customPositionPlaceholder: "أدخل المسمى الوظيفي...",
    jobDescHeading: "معلومات حول الوظائف وفرص التدريب في i4ware",
    openingsListTitle: "الوظائف وفرص التدريب المتاحة:",
    badgeJobIntern: "وظائف وتدريب",
    badgeRemote: "عمل عن بعد مرن",
    badgeSkills: "React • PHP • WordPress • Laravel • Jira",

    // Section labels
    personalInfo: "المعلومات الشخصية",
    educationInfo: "التعليم والخبرة",

    // Fields
    firstname: "الاسم الأول",
    lastname: "الاسم الأخير",
    email: "البريد الإلكتروني",
    country: "البلد",
    address1: "سطر العنوان 1",
    address2: "سطر العنوان 2",
    zip: "الرمز البريدي",
    city: "المدينة",
    phone: "الهاتف",
    mobile: "الهاتف المحمول",
    www: "الموقع الإلكتروني",
    cv: "السيرة الذاتية (PDF، الحد الأقصى 8 ميجابايت)",
    motivation: "خطاب التحفيز (PDF، الحد الأقصى 8 ميجابايت)",
    application: "طلب الوظيفة (PDF، الحد الأقصى 8 ميجابايت)",
    additional: "معلومات إضافية",

    education: "التعليم",
    qualifications: "المؤهلات",
    skills: "المهارات والمعرفة",
    workexp: "الخبرة العملية",

    // Placeholders
    phFirstname: "محمد",
    phLastname: "أحمد",
    phEmail: "name@example.com",
    phCountry: "فنلندا",
    phAddress1: "شارع مثال 123",
    phAddress2: "شقة 45B",
    phZip: "33100",
    phCity: "تامبيري",
    phPhone: "+358 3 123 4567",
    phMobile: "+358 40 123 4567",
    phWWW: "https://www.example.com",
    phAdditional: "أخبرنا عن خلفيتك وخبراتك...",
    phEducation: "درجة في تكنولوجيا المعلومات، 2018-2022",
    phQualifications: "الشهادات والخبرات...",
    phSkills: "HTML, CSS, JavaScript, React, PHP, WordPress, Laravel, SQL, Git...",
    phWorkexp: "مطور برمجيات، 2021 - 2024",

    // Success screen
    successTitle: "تم استلام طلبك بنجاح!",
    successDesc: "شكرًا لاهتمامك بـ i4ware Software! تم استلام طلبك ومستنداتك بنجاح وحفظها في نظامنا.",
    applicantRef: "رقم المرجع",
    appliedFor: "الوظيفة المطلوبة",
    weWillContact: "سيقوم فريق التوظيف لدينا بمراجعة طلبك والتواصل معك في أقرب وقت ممكن.",
    submitAnother: "إرسال طلب آخر"
  }
});

export default function App() {
  const [formData, setFormData] = useState({});
  const [fileError, setFileError] = useState("");
  const [submitError, setSubmitError] = useState("");
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [submitSuccess, setSubmitSuccess] = useState(false);
  const [submittedData, setSubmittedData] = useState(null);

  const [jobDescription, setJobDescription] = useState("");
  const [positionsList, setPositionsList] = useState([]);
  const [selectedPosition, setSelectedPosition] = useState("");
  const [customPosition, setCustomPosition] = useState("");
  const formRef = useRef(null);

  // Determine language from <html lang> or fallback
  const htmlLang = (document.documentElement.lang || API_DEFAULT_LANGUAGE || "fi").substring(0, 2).toLowerCase();
  const lang = ["fi", "en", "sv", "ar"].includes(htmlLang) ? htmlLang : "fi";

  strings.setLanguage(lang);

  useEffect(() => {
    const root = document.getElementById("jafroot");
    if (!root) return;

    // Load description from data attribute or fallback
    if (root.dataset.description) {
      setJobDescription(root.dataset.description);
    } else {
      setJobDescription(
        lang === "fi"
          ? "Etsimme tiimiimme motivoituneita ohjelmistokehittäjiä sekä opintoihin liittyviä työharjoittelijoita (Full Stack Developer, React, TypeScript, PHP, WordPress, Laravel, Jira/Atlassian, REST API). Tarjoamme monipuolisia ja mielenkiintoisia asiakas- ja tuotekehitysprojekteja, joustavat etätyömahdollisuudet sekä kokeneen tiimin tuen ammatilliseen kehittymiseen."
          : "We are looking for motivated software developers and interns to join our team (Full Stack, React, TypeScript, PHP, WordPress, Laravel, Atlassian Jira, REST APIs). We offer versatile customer and product projects, flexible remote work, and supportive mentorship."
      );
    }

    // Load open positions list
    let posArr = [];
    if (root.dataset.openingsJson) {
      try {
        posArr = JSON.parse(root.dataset.openingsJson);
      } catch (e) {
        console.warn("Failed to parse openings JSON", e);
      }
    }

    const defaultJob = root.dataset.defaultJob || "Full Stack Developer";
    if (posArr.length === 0) {
      posArr = [defaultJob, "Ohjelmistokehittäjä (Harjoittelu)"];
    }

    setPositionsList(posArr);

    // Check if URL or hash specifies a position (e.g. ?position=React+Developer)
    const urlParams = new URLSearchParams(window.location.search);
    const queryPos = urlParams.get("position");

    if (queryPos) {
      setSelectedPosition(queryPos);
      if (!posArr.includes(queryPos)) {
        setPositionsList((prev) => [queryPos, ...prev]);
      }
    } else if (defaultJob) {
      setSelectedPosition(defaultJob);
    } else if (posArr.length > 0) {
      setSelectedPosition(posArr[0]);
    }

    // Register window global for server-rendered position tags to communicate with React form
    window.atsSelectPosition = (pos) => {
      handleSelectPosition(pos);
    };

    return () => {
      delete window.atsSelectPosition;
    };
  }, [lang]);

  const handleSelectPosition = (pos) => {
    setSelectedPosition(pos);
    if (formRef.current) {
      formRef.current.scrollIntoView({ behavior: "smooth", block: "start" });
    }
  };

  const handleChange = (e) => {
    const { name, value, type, checked, files } = e.target;
    if (type === "file") {
      const file = files?.[0];
      if (file) {
        const isPdf = file.type === "application/pdf" || file.name.toLowerCase().endsWith(".pdf");
        if (!isPdf) {
          setFileError(strings.errPdfOnly);
          return;
        }
        if (file.size > 8192 * 1024) {
          setFileError(strings.errMaxSize);
          return;
        }
      }
      setFileError("");
      setFormData((prev) => ({ ...prev, [name]: file }));
    } else {
      setFormData((prev) => ({ ...prev, [name]: type === "checkbox" ? checked : value }));
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();

    if (isSubmitting) return;

    setSubmitError("");
    setFileError("");

    const root = document.getElementById("jafroot");
    if (!root) {
      setSubmitError("Application container not found.");
      return;
    }

    // Determine final job title
    let finalJobTitle = selectedPosition;
    if (selectedPosition === "__other__") {
      finalJobTitle = customPosition.trim() || strings.otherPosition;
    }
    if (!finalJobTitle) {
      finalJobTitle = root.dataset.defaultJob || "Full Stack Developer";
    }

    // Validate CV is present
    if (!formData.cv) {
      setFileError(strings.errCvRequired);
      return;
    }

    setIsSubmitting(true);

    const endpoint = root.dataset.endpoint;
    const nonce = root.dataset.nonce;

    const payload = new FormData();
    for (const key in formData) {
      if (formData[key] !== undefined && formData[key] !== null) {
        payload.append(key, formData[key]);
      }
    }
    payload.append("job_title", finalJobTitle);

    try {
      const headers = {};
      if (nonce) {
        headers["X-WP-Nonce"] = nonce;
      }

      const res = await fetch(endpoint, {
        method: "POST",
        headers,
        body: payload,
      });

      const data = await res.json();

      if (res.ok && (data.success !== false)) {
        setSubmitSuccess(true);
        setSubmittedData({
          id: data.applicant_id || null,
          position: finalJobTitle,
        });
        setFormData({});
        if (formRef.current) {
          formRef.current.scrollIntoView({ behavior: "smooth", block: "start" });
        }
      } else {
        const errMsg = data.message || strings.submissionFailed;
        setSubmitError(errMsg);
      }
    } catch (err) {
      setSubmitError("Verkkovirhe / Network error: " + (err.message || "Connection timeout"));
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleResetForm = () => {
    setFormData({});
    setFileError("");
    setSubmitError("");
    setSubmitSuccess(false);
    setSubmittedData(null);
  };

  const hasServerDescription = typeof document !== "undefined" && !!document.querySelector(".ats-server-description");

  return (
    <div className="app-container" ref={formRef}>
      {/* 1. Job & Internship Description Card (Rendered if not already provided by PHP server-side) */}
      {!hasServerDescription && (
        <div className="ats-job-hero-card">
          <div className="ats-badge-row">
            <span className="ats-badge ats-badge-primary">✨ {strings.badgeJobIntern}</span>
            <span className="ats-badge ats-badge-secondary">🌐 {strings.badgeRemote}</span>
            <span className="ats-badge ats-badge-accent">⚡ {strings.badgeSkills}</span>
          </div>

          <h2 className="ats-job-hero-title">{strings.jobDescHeading}</h2>

          <div className="ats-job-hero-desc">
            {jobDescription ? (
              jobDescription.split("\n").map((para, i) => (
                para.trim() ? <p key={i}>{para}</p> : null
              ))
            ) : (
              <p>
                Etsimme tiimiimme motivoituneita ohjelmistokehittäjiä sekä opintoihin liittyviä harjoittelijoita.
                Tarjoamme mielenkiintoisia asiakasprojekteja ja joustavat etätyömahdollisuudet.
              </p>
            )}
          </div>

          {positionsList.length > 0 && (
            <div className="ats-openings-selector-box">
              <span className="ats-openings-label">{strings.openingsListTitle}</span>
              <div className="ats-position-tags">
                {positionsList.map((pos, idx) => (
                  <button
                    type="button"
                    key={idx}
                    className={`ats-position-tag ${selectedPosition === pos ? "active" : ""}`}
                    onClick={() => handleSelectPosition(pos)}
                  >
                    <span className="tag-dot" />
                    {pos}
                  </button>
                ))}
                <button
                  type="button"
                  className={`ats-position-tag ${selectedPosition === "__other__" ? "active" : ""}`}
                  onClick={() => handleSelectPosition("__other__")}
                >
                  <span className="tag-dot" />
                  {strings.otherPosition}
                </button>
              </div>
            </div>
          )}
        </div>
      )}

      {/* 2. Success Screen or Application Form */}
      {submitSuccess ? (
        <div className="ats-success-card">
          <div className="ats-success-icon-wrap">
            <svg className="ats-checkmark" viewBox="0 0 52 52">
              <circle className="ats-checkmark-circle" cx="26" cy="26" r="24" fill="none" />
              <path className="ats-checkmark-check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8" />
            </svg>
          </div>
          <h2 className="ats-success-title">{strings.successTitle}</h2>
          <p className="ats-success-desc">{strings.successDesc}</p>

          <div className="ats-success-meta">
            <div className="ats-meta-item">
              <span className="ats-meta-label">{strings.appliedFor}:</span>
              <strong className="ats-meta-value">{submittedData?.position || "–"}</strong>
            </div>
            {submittedData?.id && (
              <div className="ats-meta-item">
                <span className="ats-meta-label">{strings.applicantRef}:</span>
                <strong className="ats-meta-value">#{submittedData.id}</strong>
              </div>
            )}
          </div>

          <p className="ats-success-note">{strings.weWillContact}</p>

          <button
            type="button"
            className="btn btn-primary ats-btn-restart"
            onClick={handleResetForm}
          >
            {strings.submitAnother}
          </button>
        </div>
      ) : (
        <form className="app-card" onSubmit={handleSubmit} noValidate={false}>
          {/* Honeypot hidden input for bot protection */}
          <input
            type="text"
            name="_gotcha"
            style={{ display: "none" }}
            tabIndex={-1}
            autoComplete="off"
            onChange={handleChange}
          />

          {/* Applied Position Selector within Form */}
          <div className="ats-position-form-row">
            <label className="ats-position-select-label">
              <span className="label-icon">🎯</span>
              {strings.jobPosition}
              <span className="ats-req-star">{strings.requiredMark}</span>
            </label>
            <div className="ats-select-wrapper">
              <select
                name="job_title_select"
                value={selectedPosition}
                onChange={(e) => setSelectedPosition(e.target.value)}
                required
              >
                <option value="" disabled>{strings.selectPositionPlaceholder}</option>
                {positionsList.map((pos, idx) => (
                  <option key={idx} value={pos}>{pos}</option>
                ))}
                <option value="__other__">{strings.otherPosition}</option>
              </select>
            </div>

            {selectedPosition === "__other__" && (
              <div className="ats-custom-position-box">
                <input
                  type="text"
                  name="custom_position"
                  placeholder={strings.customPositionPlaceholder}
                  value={customPosition}
                  onChange={(e) => setCustomPosition(e.target.value)}
                  required
                />
              </div>
            )}
          </div>

          <div className="two-col">
            {/* Left Column: Personal Information */}
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
                disabled={isSubmitting}
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
                disabled={isSubmitting}
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
                disabled={isSubmitting}
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
                disabled={isSubmitting}
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
                disabled={isSubmitting}
                onChange={handleChange}
              />

              <label>{strings.address2}</label>
              <input
                type="text"
                name="address2"
                placeholder={strings.phAddress2}
                disabled={isSubmitting}
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
                disabled={isSubmitting}
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
                disabled={isSubmitting}
                onChange={handleChange}
              />

              <label>{strings.phone}</label>
              <input
                type="tel"
                name="phone"
                placeholder={strings.phPhone}
                disabled={isSubmitting}
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
                disabled={isSubmitting}
                onChange={handleChange}
              />

              <label>{strings.www}</label>
              <input
                type="url"
                name="www"
                placeholder={strings.phWWW}
                disabled={isSubmitting}
                onChange={handleChange}
              />

              <label>
                {strings.cv}
                {strings.requiredMark}
              </label>
              <input
                type="file"
                name="cv"
                accept=".pdf,application/pdf"
                required
                disabled={isSubmitting}
                onChange={handleChange}
              />

              <label>{strings.motivation}</label>
              <input
                type="file"
                name="motivation"
                accept=".pdf,application/pdf"
                disabled={isSubmitting}
                onChange={handleChange}
              />

              <label>{strings.application}</label>
              <input
                type="file"
                name="application"
                accept=".pdf,application/pdf"
                disabled={isSubmitting}
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
                disabled={isSubmitting}
                onChange={handleChange}
              />
            </div>

            {/* Right Column: Education & Experience */}
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
                disabled={isSubmitting}
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
                disabled={isSubmitting}
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
                disabled={isSubmitting}
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
                disabled={isSubmitting}
                onChange={handleChange}
              />
            </div>
          </div>

          {/* Inline Error Message */}
          {submitError && (
            <div className="ats-error-banner">
              <span className="error-icon">⚠️</span>
              <div className="error-content">
                <strong>{strings.submissionFailed}</strong>
                <p>{submitError}</p>
              </div>
            </div>
          )}

          {/* Form Actions with Loading Spinner */}
          <div className="form-actions">
            <button
              type="submit"
              className={`btn btn-primary ${isSubmitting ? "btn-submitting" : ""}`}
              disabled={isSubmitting}
            >
              {isSubmitting ? (
                <>
                  <span className="ats-spinner" />
                  <span>{strings.submitting}</span>
                </>
              ) : (
                strings.submit
              )}
            </button>
            <button
              type="reset"
              className="btn btn-secondary"
              disabled={isSubmitting}
              onClick={handleResetForm}
            >
              {strings.reset}
            </button>
          </div>
        </form>
      )}
    </div>
  );
}
