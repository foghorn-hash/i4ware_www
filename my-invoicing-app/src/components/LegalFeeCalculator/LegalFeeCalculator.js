import React, { useState } from "react";
import { Card, Form, Button } from "react-bootstrap";

const LegalFeeCalculator = () => {
  const [hourlyRate, setHourlyRate] = useState(250);
  const [vatRate, setVatRate] = useState(25.5); // Default VAT in Finland is 25.5%
  const [hoursPerWeek, setHoursPerWeek] = useState(37.5);
  const [weeksPerMonth, setWeeksPerMonth] = useState(4.33);
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

  return (
    <div className="p-4 max-w-xl mx-auto">
      <Card className="shadow-sm">
        <Card.Body>
          <h1 className="text-center mb-4">Asianajajan Hintalaskuri</h1>

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
              <Form.Label>Tunteja projektissa</Form.Label>
              <Form.Control
                type="number"
                value={projectHours}
                onChange={(e) => setProjectHours(parseFloat(e.target.value) || 0)}
              />
            </Form.Group>

            <Button variant="primary" className="w-100" disabled>
              Laske
            </Button>
          </Form>

          <div className="mt-4">
            <h2 className="text-center">Tulokset:</h2>
            <p>Tuntihinta yhteensä (ilman ALV): €{result.baseAmount}</p>
            <p>ALV-osuus: €{result.vatAmount}</p>
            <p>Hinta yhteensä (sis. ALV): €{result.totalWithVat}</p>
          </div>
        </Card.Body>
      </Card>
    </div>
  );
};

export default LegalFeeCalculator;