import React, { useState } from "react";
import { Card, Form, Button } from "react-bootstrap";
import { API_DEFAULT_LANGUAGE } from "../../constants/apiConstants";
// ES6 module syntax
import LocalizedStrings from "react-localization";

let strings = new LocalizedStrings({
  en: {
    title: "Legal Fee Calculator",
    hourlyRate: "Hourly Rate (€)",
    vatRate: "VAT Rate (%)",
    projectHours: "Project Hours",
    calculate: "Calculate",
    results: "Results:",
    baseAmount: "Total Hourly Fee (excl. VAT):",
    vatAmount: "VAT Amount:",
    totalWithVat: "Total Fee (incl. VAT):",
  },
  fi: {
    title: "Asianajajan Hintalaskuri",
    hourlyRate: "Tuntihinta (€)",
    vatRate: "ALV-kanta (%)",
    projectHours: "Tunteja projektissa",
    calculate: "Laske",
    results: "Tulokset:",
    baseAmount: "Tuntihinta yhteensä (ilman ALV):",
    vatAmount: "ALV-osuus:",
    totalWithVat: "Hinta yhteensä (sis. ALV):",
  },
  sv: {
    title: "Advokatarvodesräknare",
    hourlyRate: "Timpris (€)",
    vatRate: "Moms (%)",
    projectHours: "Projekttimmar",
    calculate: "Beräkna",
    results: "Resultat:",
    baseAmount: "Total timpris (exkl. moms):",
    vatAmount: "Momsbelopp:",
    totalWithVat: "Total avgift (inkl. moms):",
  },
});

const LegalFeeCalculator = () => {
  const [hourlyRate, setHourlyRate] = useState(250);
  const [vatRate, setVatRate] = useState(25.5); // Default VAT in Finland is 25.5%
  const [projectHours, setProjectHours] = useState(100);

  const calculate = () => {
    const baseAmount = projectHours * hourlyRate;
    const vatAmount = (baseAmount * vatRate) / 100;
    const totalWithVat = baseAmount + vatAmount;

    return {
      baseAmount: baseAmount.toFixed(2),
      vatAmount: vatAmount.toFixed(2),
      totalWithVat: totalWithVat.toFixed(2),
    };
  };

  const result = calculate();

  const [lang, setLang] = useState(API_DEFAULT_LANGUAGE);

  var query = window.location.search.substring(1);
  var urlParams = new URLSearchParams(query);
  var localization = urlParams.get("lang");

  if (localization === null) {
    strings.setLanguage(API_DEFAULT_LANGUAGE);
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
              <Form.Label>{strings.hourlyRate}</Form.Label>
              <Form.Control
                type="number"
                value={hourlyRate}
                onChange={(e) => setHourlyRate(parseFloat(e.target.value) || 0)}
              />
            </Form.Group>

            <Form.Group className="mb-3">
              <Form.Label>{strings.vatRate}</Form.Label>
              <Form.Control
                type="text"
                value={vatRate}
                onChange={(e) => setVatRate(parseFloat(e.target.value) || 0)}
              />
            </Form.Group>

            <Form.Group className="mb-3">
              <Form.Label>{strings.projectHours}</Form.Label>
              <Form.Control
                type="number"
                value={projectHours}
                onChange={(e) => setProjectHours(parseFloat(e.target.value) || 0)}
              />
            </Form.Group>

            <Button variant="primary" className="w-100" disabled>
              {strings.calculate}
            </Button>
          </Form>

          <div className="mt-4">
            <h2 className="text-center">{strings.results}</h2>
            <p>{strings.baseAmount} €{result.baseAmount}</p>
            <p>{strings.vatAmount} €{result.vatAmount}</p>
            <p>{strings.totalWithVat} €{result.totalWithVat}</p>
          </div>
        </Card.Body>
      </Card>
    </div>
  );
};

export default LegalFeeCalculator;