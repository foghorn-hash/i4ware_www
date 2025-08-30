import React, { useEffect, useMemo, useState } from "react";
import { API_DEFAULT_LANGUAGE } from "../../constants/apiConstants";
import "./YelCalculator.css";
import { Container, Row, Col, Card, Form, Button } from "react-bootstrap";
import LocalizedStrings from "react-localization";

// --- i18n strings -----------------------------------------------------------
let strings = new LocalizedStrings({
  en: {
    appTitle: "YEL calculator",
    inputs: "Inputs",
    income: "YEL income (€/year)",
    birthMonth: "Birth month",
    birthYear: "Birth year",
    installments: "Number of installments per year",
    firstInstallmentMonth: "1st installment month",
    calculate: "Calculate",
    results: "Results",
    premiumPerInstallment: "Premium per installment",
    premiumPerYear: "Premium per year",
    newEntrepreneur: "New entrepreneur (first 48 months)",
    discountedPerInstallment: "Discounted per installment",
    discountedPerYear: "Discounted per year",
    assumptionsTitle: "Assumptions & notes",
    assumptionsText:
      "This calculator uses simplified assumptions (age-based rate bands and a 22% new entrepreneur discount). Actual YEL percentages and benefits change annually; check your pension provider for official figures.",
    ageBandA: "Under 53 or 63+",
    ageBandB: "Age 53–62",
    choose: "Choose",
    estimatePension: "Very rough future pension estimate",
    targetAtAge: "at retirement age",
    euroPerMonth: "/mo",
    reset: "Reset",
  },
  fi: {
    appTitle: "YEL-laskuri",
    inputs: "Syötteet",
    income: "YEL-työtulo (€/vuosi)",
    birthMonth: "Syntymäkuukausi",
    birthYear: "Syntymävuosi",
    installments: "Maksuerien määrä vuodessa",
    firstInstallmentMonth: "Ensimmäisen erän kuukausi",
    calculate: "Laske",
    results: "Tulokset",
    premiumPerInstallment: "Vakuutusmaksu / erä",
    premiumPerYear: "Vakuutusmaksu / vuosi",
    newEntrepreneur: "Aloittava yrittäjä (ensimmäiset 48 kk)",
    discountedPerInstallment: "Alennettu / erä",
    discountedPerYear: "Alennettu / vuosi",
    assumptionsTitle: "Oletukset ja huomiot",
    assumptionsText:
      "Tämä laskuri käyttää yksinkertaistettuja oletuksia (ikäpohjaiset prosentit ja 22 %:n alennus aloittaville). Todelliset YEL-prosentit muuttuvat vuosittain – tarkista virallinen tieto eläkeyhtiöstäsi.",
    ageBandA: "Alle 53 v tai 63+",
    ageBandB: "Iältään 53–62 v",
    choose: "Valitse",
    estimatePension: "Erittäin karkea eläkearvio",
    targetAtAge: "eläkeiässä",
    euroPerMonth: "/kk",
    reset: "Tyhjennä",
  },
  sv: {
    appTitle: "YEL-räknare",
    inputs: "Inmatning",
    income: "YEL-arbetsinkomst (€/år)",
    birthMonth: "Födelsemånad",
    birthYear: "Födelseår",
    installments: "Antal rater per år",
    firstInstallmentMonth: "Första ratens månad",
    calculate: "Beräkna",
    results: "Resultat",
    premiumPerInstallment: "Premie per rat",
    premiumPerYear: "Premie per år",
    newEntrepreneur: "Ny företagare (första 48 mån)",
    discountedPerInstallment: "Rabatterad per rat",
    discountedPerYear: "Rabatterad per år",
    assumptionsTitle: "Antaganden och noter",
    assumptionsText:
      "Räknaren använder förenklade antaganden (åldersband och 22 % rabatt för nya företagare). Kontrollera alltid officiella siffror hos ditt pensionsbolag.",
    ageBandA: "Under 53 eller 63+",
    ageBandB: "Ålder 53–62",
    choose: "Välj",
    estimatePension: "Väldigt grov pensionsuppskattning",
    targetAtAge: "vid pensionsålder",
    euroPerMonth: "/mån",
    reset: "Rensa",
  },
});

export default function YelCalculator() {
  const [income, setIncome] = useState(25000);
  const [birthMonth, setBirthMonth] = useState(1);
  const [birthYear, setBirthYear] = useState(1980);
  const [installments, setInstallments] = useState(12);
  const [firstInstallmentMonth, setFirstInstallmentMonth] = useState(1);
  const [isNewEntrepreneur, setIsNewEntrepreneur] = useState(true);

  const [lang, setLang] = useState(document.documentElement.lang || API_DEFAULT_LANGUAGE);
  
    useEffect(() => {
      const observer = new MutationObserver(() => {
        setLang(document.documentElement.lang || API_DEFAULT_LANGUAGE);
      });
      observer.observe(document.documentElement, { attributes: true, attributeFilter: ['lang'] });
      return () => observer.disconnect();
    }, []);
  
    useEffect(() => {
      strings.setLanguage(lang);
    }, [lang]);

  // --- Assumptions ---------------------------------------------------------
  const AGE_BAND_A_RATE = 0.241; // <53 or 63+
  const AGE_BAND_B_RATE = 0.256; // 53–62
  const NEW_ENTREPRENEUR_DISCOUNT = 0.22; // 22%

  const age = useMemo(() => {
    const today = new Date();
    const m = today.getMonth() + 1;
    let years = today.getFullYear() - birthYear;
    if (m < birthMonth) years -= 1;
    return Math.max(0, years);
  }, [birthMonth, birthYear]);

  const rate = age >= 53 && age <= 62 ? AGE_BAND_B_RATE : AGE_BAND_A_RATE;
  const annualPremium = useMemo(() => income * rate, [income, rate]);
  const perInstallment = useMemo(
    () => (annualPremium / Math.max(installments, 1)),
    [annualPremium, installments]
  );
  const discountedAnnual = useMemo(
    () => (isNewEntrepreneur ? annualPremium * (1 - NEW_ENTREPRENEUR_DISCOUNT) : annualPremium),
    [annualPremium, isNewEntrepreneur]
  );
  const discountedPerInstallment = useMemo(
    () => (discountedAnnual / Math.max(installments, 1)),
    [discountedAnnual, installments]
  );

  // rough feeler for pension
  const roughMonthlyPension = useMemo(() => (income * 0.015) / 12, [income]);

  const months = ["1","2","3","4","5","6","7","8","9","10","11","12"];
  const numberFmt = new Intl.NumberFormat(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 0 });
  const money = (v) => `${numberFmt.format(Math.round(v))} €`;

  const reset = () => {
    setIncome(25000);
    setBirthMonth(1);
    setBirthYear(1980);
    setInstallments(12);
    setFirstInstallmentMonth(1);
    setIsNewEntrepreneur(true);
  };

  return (
    <Container className="yelb py-4">
      <Card className="shadow-sm">
        <Card.Body>
      <Row className="mb-3">
        <Col>
          <h1 className="yel-title display-6 fw-bold">{strings.appTitle}</h1>
        </Col>
      </Row>

      <Row className="mb-3">
        {/* Left: inputs */}
        <div lg={6}>
          
              <Form>
                <Row className="gy-3 mt-1">
                  <Col md={8}>
                    <Form.Label className="text-uppercase small fw-semibold">{strings.income}</Form.Label>
                    <Form.Control
                      type="number"
                      value={income}
                      min={1000}
                      step={500}
                      onChange={(e) => setIncome(parseFloat(e.target.value || "0"))}
                    />
                  </Col>

                  <Col md={4} className="new-entrepreneur d-flex align-items-center">
                    <Form.Check
                      type="checkbox"
                      id="new-entrepreneur"
                      label={strings.newEntrepreneur}
                      checked={isNewEntrepreneur}
                      onChange={(e) => setIsNewEntrepreneur(e.target.checked)}
                    />
                  </Col>
                </Row>
                <Row className="gy-3 mt-1">  
                  <Col xs={6} md={3}>
                    <Form.Label className="text-uppercase small fw-semibold">{strings.birthMonth}</Form.Label>
                    <Form.Select
                      value={birthMonth}
                      onChange={(e) => setBirthMonth(parseInt(e.target.value))}
                    >
                      {months.map((m, i) => (
                        <option key={m} value={i + 1}>{m}</option>
                      ))}
                    </Form.Select>
                  </Col>

                  <Col xs={6} md={3}>
                    <Form.Label className="text-uppercase small fw-semibold">{strings.birthYear}</Form.Label>
                    <Form.Control
                      type="number"
                      value={birthYear}
                      min={1920}
                      max={new Date().getFullYear()}
                      onChange={(e) => setBirthYear(parseInt(e.target.value || "0"))}
                    />
                  </Col>
                </Row>
                <Row className="gy-3 mt-1"> 
                  <Col xs={6} md={3}>
                    <Form.Label className="text-uppercase small fw-semibold">{strings.installments}</Form.Label>
                    <Form.Select
                      value={installments}
                      onChange={(e) => setInstallments(parseInt(e.target.value))}
                    >
                      {[1,2,3,4,6,7,8,9,10,11,12].map(n => <option key={n} value={n}>{n}</option>)}
                    </Form.Select>
                  </Col>
                  
                  <Col xs={6} md={3}>
                    <Form.Label className="text-uppercase small fw-semibold">{strings.firstInstallmentMonth}</Form.Label>
                    <Form.Select
                      value={firstInstallmentMonth}
                      onChange={(e) => setFirstInstallmentMonth(parseInt(e.target.value))}
                    >
                      {months.map((m, i) => (
                        <option key={m} value={i + 1}>{m}</option>
                      ))}
                    </Form.Select>
                  </Col>
                </Row>
                <Row className="gy-3 mt-1"> 
                  <Col xs="auto">
                    <Button variant="outline-primary" className="reset px-4" onClick={reset}>
                      {strings.reset}
                    </Button>
                  </Col>
                </Row>
              </Form>
        </div>

        {/* Right: results */}
        <div className="text-white" lg={6}>

              <div className="text-white fw-medium mb-2">{strings.newEntrepreneur}</div>
              <CardRow className="text-white" label={strings.discountedPerInstallment} value={money(discountedPerInstallment)} />
              <CardRow className="text-white" label={strings.discountedPerYear} value={money(discountedAnnual)} subtle />

              <div className="text-white fw-medium mb-2">{strings.assumptionsTitle}</div>
              <p className="text-white small text-secondary mb-0">{strings.assumptionsText}</p>
        </div>
      </Row>
      </Card.Body>
     </Card>
    </Container>
  );
}

function CardRow({ label, value, subtle }) {
  return (
    <div className={`d-flex justify-content-between align-items-center py-2`}>
      <div className="small text-white">{label}</div>
      <div className="fs-5 fw-bold text-white">{value}</div>
    </div>
  );
}
