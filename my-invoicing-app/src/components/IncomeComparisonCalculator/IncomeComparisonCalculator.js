import React, { useState } from "react";
import { Card, Form, Button } from "react-bootstrap";
import { API_DEFAULT_LANGUAGE } from "../../constants/apiConstants";
// ES6 module syntax
import LocalizedStrings from "react-localization";

let strings = new LocalizedStrings({
  en: {
    title: "Income Comparison Calculator",
    competitorRate: "Competitor Hourly Rate (€)",
    hoursPerMonth: "Hours per Month",
    monthlyPension: "National Pension (€/month)",
    housingAllowance: "Housing Allowance (€/month)",
    guaranteePension: "Guarantee Pension (€/month)",
    yelIncome: "YEL Income (€/year)",
    isStartingEntrepreneur: "Starting Entrepreneur (22% discount on YEL fee)",
    taxRate: "Withholding Tax + Church Tax (%)",
    calculate: "Calculate",
    results: "Results:",
    grossIncome: "Your Gross Income (fixed monthly fee):",
    vatAmount: "VAT Amount:",
    taxAmount: "Taxes:",
    yelFee: "YEL Fee:",
    netIncome: "Net Income:",
    competitorGrossIncome: "Competitor Gross Income:",
    totalMonthlyIncome: "Total Monthly Income (incl. pension, housing allowance, and guarantee pension):",
  },
  fi: {
    title: "Hintavertailulaskuri",
    competitorRate: "Kilpailijan tuntihinta (€)",
    hoursPerMonth: "Tunteja kuukaudessa",
    monthlyPension: "Kansaneläke (€/kk)",
    housingAllowance: "Asumistuki (€/kk)",
    guaranteePension: "Takuueläke (€/kk)",
    yelIncome: "YEL-työtulo (€/vuosi)",
    isStartingEntrepreneur: "Aloittava yrittäjä (22% alennus YEL-maksusta)",
    taxRate: "Ennakonpidätys + Kirkollisvero (%)",
    calculate: "Laske",
    results: "Tulokset:",
    grossIncome: "Omat bruttotulot (kiinteä kuukausihinta):",
    vatAmount: "ALV-osuus:",
    taxAmount: "Verot:",
    yelFee: "YEL-maksu:",
    netIncome: "Nettotulot:",
    competitorGrossIncome: "Kilpailijan bruttopalkka:",
    totalMonthlyIncome: "Kokonaiskuukausitulot (sis. eläke, asumistuki ja takuueläke):",
  },
  sv: {
    title: "Inkomstjämförelsekalkylator",
    competitorRate: "Konkurrentens timpris (€)",
    hoursPerMonth: "Timmar per månad",
    monthlyPension: "Nationell pension (€/månad)",
    housingAllowance: "Bostadsbidrag (€/månad)",
    guaranteePension: "Garantipension (€/månad)",
    yelIncome: "YEL-inkomst (€/år)",
    isStartingEntrepreneur: "Startande företagare (22% rabatt på YEL-avgift)",
    taxRate: "Förskottsinnehållning + Kyrkoskatt (%)",
    calculate: "Beräkna",
    results: "Resultat:",
    grossIncome: "Din bruttoinkomst (fast månadsavgift):",
    vatAmount: "Momsbelopp:",
    taxAmount: "Skatter:",
    yelFee: "YEL-avgift:",
    netIncome: "Nettoinkomst:",
    competitorGrossIncome: "Konkurrentens bruttoinkomst:",
    totalMonthlyIncome: "Total månadsinkomst (inkl. pension, bostadsbidrag och garantipension):",
  },
});

const IncomeComparisonCalculator = () => {
  const [hourlyRate, setHourlyRate] = useState(50);
  const [hoursPerMonth, setHoursPerMonth] = useState(160);
  const [competitorRate, setCompetitorRate] = useState(95);
  const [monthlyPension, setMonthlyPension] = useState(783.41);
  const [housingAllowance, setHousingAllowance] = useState(470.09);
  const [guaranteePension, setGuaranteePension] = useState(202.89);
  const [taxRate, setTaxRate] = useState(20);
  const [yelIncome, setYelIncome] = useState(10000);
  const [isStartingEntrepreneur, setIsStartingEntrepreneur] = useState(true);

  const vatRate = 25.5;
  const fixedMonthlyFee = 980.3;
  const yelDiscountRate = isStartingEntrepreneur ? 0.22 : 0;

  const calculate = () => {
    const annualYelFee = yelIncome * 0.241;
    const discountedYelFee = annualYelFee * (1 - yelDiscountRate);
    const monthlyYelFee = discountedYelFee / 12;

    const grossIncome = fixedMonthlyFee;
    const vatAmount = (grossIncome * vatRate) / 100;
    const taxableIncome = vatAmount;
    const taxAmount = (taxableIncome * taxRate) / 100;
    const netIncome = grossIncome - taxAmount - monthlyYelFee;

    const competitorGrossIncome = competitorRate * hoursPerMonth;

    const totalMonthlyIncome = monthlyPension + housingAllowance + guaranteePension + netIncome;

    return {
      grossIncome: grossIncome.toFixed(2),
      vatAmount: vatAmount.toFixed(2),
      taxAmount: taxAmount.toFixed(2),
      netIncome: netIncome.toFixed(2),
      competitorGrossIncome: competitorGrossIncome.toFixed(2),
      totalMonthlyIncome: totalMonthlyIncome.toFixed(2),
      monthlyYelFee: monthlyYelFee.toFixed(2),
    };
  };

  const result = calculate();

  const [lang, setLang] = useState("en");

  var query = window.location.search.substring(1);
  var urlParams = new URLSearchParams(query);
  var localization = urlParams.get("lang");

  if (localization === null) {
    strings.setLanguage("en");
  } else {
    strings.setLanguage(localization);
  }

  return (
    <div className="p-4 max-w-xl mx-auto">
      <Card className="shadow-sm">
        <Card.Body>
          <h1 className="text-center mb-4">{strings.title}</h1>

          <Form>
            <Form.Group className="mb-3">
              <Form.Label>{strings.competitorRate}</Form.Label>
              <Form.Control
                type="number"
                value={competitorRate}
                onChange={(e) => setCompetitorRate(parseFloat(e.target.value) || 0)}
              />
            </Form.Group>

            <Form.Group className="mb-3">
              <Form.Label>{strings.hoursPerMonth}</Form.Label>
              <Form.Control
                type="text"
                value={hoursPerMonth}
                onChange={(e) => setHoursPerMonth(parseFloat(e.target.value) || 0)}
              />
            </Form.Group>

            <Form.Group className="mb-3">
              <Form.Label>{strings.monthlyPension}</Form.Label>
              <Form.Control
                type="text"
                value={monthlyPension}
                onChange={(e) => setMonthlyPension(parseFloat(e.target.value) || 0)}
              />
            </Form.Group>

            <Form.Group className="mb-3">
              <Form.Label>{strings.housingAllowance}</Form.Label>
              <Form.Control
                type="text"
                value={housingAllowance}
                onChange={(e) => setHousingAllowance(parseFloat(e.target.value) || 0)}
              />
            </Form.Group>

            <Form.Group className="mb-3">
              <Form.Label>{strings.guaranteePension}</Form.Label>
              <Form.Control
                type="text"
                value={guaranteePension}
                onChange={(e) => setGuaranteePension(parseFloat(e.target.value) || 0)}
              />
            </Form.Group>

            <Form.Group className="mb-3">
              <Form.Label>{strings.yelIncome}</Form.Label>
              <Form.Control
                type="number"
                value={yelIncome}
                onChange={(e) => setYelIncome(parseFloat(e.target.value) || 0)}
              />
            </Form.Group>

            <Form.Group className="mb-3">
              <Form.Check
                type="checkbox"
                label={strings.isStartingEntrepreneur}
                checked={isStartingEntrepreneur}
                onChange={(e) => setIsStartingEntrepreneur(e.target.checked)}
              />
            </Form.Group>

            <Form.Group className="mb-3">
              <Form.Label>{strings.taxRate}</Form.Label>
              <Form.Control
                type="number"
                value={taxRate}
                onChange={(e) => setTaxRate(parseFloat(e.target.value) || 0)}
              />
            </Form.Group>

            <Button variant="primary" className="w-100" disabled>
              {strings.calculate}
            </Button>
          </Form>

          <div className="mt-4">
            <h2 className="text-center">{strings.results}</h2>
            <p>{strings.grossIncome} €{result.grossIncome}</p>
            <p>{strings.vatAmount} €{result.vatAmount}</p>
            <p>{strings.taxAmount} €{result.taxAmount}</p>
            <p>{strings.yelFee} €{result.monthlyYelFee}</p>
            <p>{strings.netIncome} €{result.netIncome}</p>
            <p>{strings.competitorGrossIncome} €{result.competitorGrossIncome}</p>
            <p>{strings.totalMonthlyIncome} €{result.totalMonthlyIncome}</p>
          </div>
        </Card.Body>
      </Card>
    </div>
  );
};

export default IncomeComparisonCalculator;