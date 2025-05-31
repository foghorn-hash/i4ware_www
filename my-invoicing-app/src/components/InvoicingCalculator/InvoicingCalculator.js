import React, { useState } from "react";
import { Card, Form, Button } from "react-bootstrap";
import { API_DEFAULT_LANGUAGE } from "../../constants/apiConstants";
import LocalizedStrings from "react-localization";

let strings = new LocalizedStrings({
  en: {
    title: "IT Project Pricing Calculator",
    hourlyRate: "Hourly Rate (€)",
    vatRate: "VAT Rate (%)",
    hoursPerWeek: "Hours per Week",
    weeksPerMonth: "Weeks per Month",
    projectDuration: "Project Duration (Months)",
    calculate: "Calculate",
    results: "Results:",
    hoursPerMonth: "Hours per Month:",
    baseAmount: "Base Amount:",
    vatAmount: "VAT Amount:",
    totalWithVat: "Total (incl. VAT):",
    totalForProject: "Total Project Cost:",
  },
  fi: {
    title: "IT-projektin Hintalaskuri",
    hourlyRate: "Tuntihinta (€)",
    vatRate: "ALV-kanta (%)",
    hoursPerWeek: "Tunteja viikossa",
    weeksPerMonth: "Viikkoja kuukaudessa",
    projectDuration: "Projektin kesto (Kuukausia)",
    calculate: "Laske",
    results: "Tulokset:",
    hoursPerMonth: "Tunteja kuukaudessa:",
    baseAmount: "Tuntihinta:",
    vatAmount: "ALV-osuus:",
    totalWithVat: "Hinta sis. ALV:",
    totalForProject: "Projektin hinta:",
  },
  sv: {
    title: "IT-projektets Prisräknare",
    hourlyRate: "Timpris (€)",
    vatRate: "Moms (%)",
    hoursPerWeek: "Timmar per vecka",
    weeksPerMonth: "Veckor per månad",
    projectDuration: "Projektets längd (Månader)",
    calculate: "Beräkna",
    results: "Resultat:",
    hoursPerMonth: "Timmar per månad:",
    baseAmount: "Grundbelopp:",
    vatAmount: "Momsbelopp:",
    totalWithVat: "Totalt (inkl. moms):",
    totalForProject: "Projektets totalkostnad:",
  },
});

const InvoicingCalculator = () => {
  const [hourlyRate, setHourlyRate] = useState(95);
  const [vatRate, setVatRate] = useState(25.5);
  const [hoursPerWeek, setHoursPerWeek] = useState(37.5);
  const [weeksPerMonth, setWeeksPerMonth] = useState(4.33);
  const [projectMonths, setProjectMonths] = useState(1);

  const calculate = () => {
    const hoursPerMonth = hoursPerWeek * weeksPerMonth;
    const baseAmount = hoursPerMonth * hourlyRate;
    const vatAmount = (baseAmount * vatRate) / 100;
    const totalWithVat = baseAmount + vatAmount;
    const totalForProject = totalWithVat * projectMonths;

    return {
      hoursPerMonth: hoursPerMonth.toFixed(2),
      baseAmount: baseAmount.toFixed(2),
      vatAmount: vatAmount.toFixed(2),
      totalWithVat: totalWithVat.toFixed(2),
      totalForProject: totalForProject.toFixed(2),
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
          <h1 className="text-center mb-4 calculator-title">{strings.title}</h1>

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
              <Form.Label>{strings.hoursPerWeek}</Form.Label>
              <Form.Control
                type="text"
                value={hoursPerWeek}
                onChange={(e) => setHoursPerWeek(parseFloat(e.target.value) || 0)}
              />
            </Form.Group>

            <Form.Group className="mb-3">
              <Form.Label>{strings.weeksPerMonth}</Form.Label>
              <Form.Control
                type="text"
                value={weeksPerMonth}
                onChange={(e) => setWeeksPerMonth(parseFloat(e.target.value) || 0)}
              />
            </Form.Group>

            <Form.Group className="mb-3">
              <Form.Label>{strings.projectDuration}</Form.Label>
              <Form.Control
                type="number"
                value={projectMonths}
                onChange={(e) => setProjectMonths(parseFloat(e.target.value) || 0)}
              />
            </Form.Group>

            <Button variant="primary" className="w-100" disabled>
              {strings.calculate}
            </Button>
          </Form>

          <div className="mt-4 calculator-container">
            <h2 className="text-center calculator-title">{strings.results}</h2>
            <p>{strings.hoursPerMonth} {result.hoursPerMonth}</p>
            <p>{strings.baseAmount} €{result.baseAmount}</p>
            <p>{strings.vatAmount} €{result.vatAmount}</p>
            <p>{strings.totalWithVat} €{result.totalWithVat}</p>
            <p>{strings.totalForProject} €{result.totalForProject}</p>
          </div>
        </Card.Body>
      </Card>
    </div>
  );
};

export default InvoicingCalculator;
