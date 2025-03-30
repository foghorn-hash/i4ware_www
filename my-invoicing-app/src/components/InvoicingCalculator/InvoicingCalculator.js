import React, { useState } from "react";
import { Card, Form, Button } from "react-bootstrap";

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

  return (
    <div className="p-4 max-w-xl mx-auto">
      <Card className="shadow-sm">
        <Card.Body>
          <h1 className="text-center mb-4">IT-projektin Hintalaskuri</h1>

          <Form>
            <Form.Group className="mb-3">
              <Form.Label>Tuntihinta (€)</Form.Label>
              <Form.Control
                type="number"
                value={hourlyRate}
                onChange={(e) => setHourlyRate(parseFloat(e.target.value) || 0)}
              />
            </Form.Group>

            <Form.Group className="mb-3">
              <Form.Label>ALV-kanta (%)</Form.Label>
              <Form.Control
                type="text"
                value={vatRate}
                onChange={(e) => setVatRate(parseFloat(e.target.value) || 0)}
              />
            </Form.Group>

            <Form.Group className="mb-3">
              <Form.Label>Tunteja viikossa</Form.Label>
              <Form.Control
                type="text"
                value={hoursPerWeek}
                onChange={(e) => setHoursPerWeek(parseFloat(e.target.value) || 0)}
              />
            </Form.Group>

            <Form.Group className="mb-3">
              <Form.Label>Viikkoja kuukaudesa</Form.Label>
              <Form.Control
                type="text"
                value={weeksPerMonth}
                onChange={(e) => setWeeksPerMonth(parseFloat(e.target.value) || 0)}
              />
            </Form.Group>

            <Form.Group className="mb-3">
              <Form.Label>Projektin kesto (Kuukausia)</Form.Label>
              <Form.Control
                type="number"
                value={projectMonths}
                onChange={(e) => setProjectMonths(parseFloat(e.target.value) || 0)}
              />
            </Form.Group>

            <Button variant="primary" className="w-100" disabled>
              Laske
            </Button>
          </Form>

          <div className="mt-4">
            <h2 className="text-center">Tulokset:</h2>
            <p>Tunteja kuukaudesa: {result.hoursPerMonth}</p>
            <p>Tuntihinta: €{result.baseAmount}</p>
            <p>ALV-osuus: €{result.vatAmount}</p>
            <p>Hinta sis. ALV: €{result.totalWithVat}</p>
            <p>Projektin hinta: €{result.totalForProject}</p>
          </div>
        </Card.Body>
      </Card>
    </div>
  );
};

export default InvoicingCalculator;
