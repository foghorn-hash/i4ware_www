import React, { useState } from "react";
import { Card, Form, Button } from "react-bootstrap";

const IncomeComparisonCalculator = () => {
  const [hourlyRate, setHourlyRate] = useState(50); // Your hourly rate
  const [hoursPerMonth, setHoursPerMonth] = useState(160); // Default hours worked per month
  const [competitorRate, setCompetitorRate] = useState(95); // Competitor hourly rate
  const [monthlyPension, setMonthlyPension] = useState(783.41); // Pension income
  const [housingAllowance, setHousingAllowance] = useState(470.09); // Housing allowance
  const [guaranteePension, setGuaranteePension] = useState(202.89); // Guarantee pension
  const [taxRate, setTaxRate] = useState(20); // Income tax + church tax
  const [yelIncome, setYelIncome] = useState(10000); // YEL income per year
  const [isStartingEntrepreneur, setIsStartingEntrepreneur] = useState(true); // Flag for startup discount

  const vatRate = 25.5; // VAT percentage
  const fixedMonthlyFee = 980.3; // Fixed monthly fee
  const yelDiscountRate = isStartingEntrepreneur ? 0.22 : 0; // 22% discount for starting entrepreneurs

  const calculate = () => {
    // YEL calculations
    const annualYelFee = yelIncome * 0.241; // Base YEL fee rate is 24.1%
    const discountedYelFee = annualYelFee * (1 - yelDiscountRate);
    const monthlyYelFee = discountedYelFee / 12;

    // Your income
    const grossIncome = fixedMonthlyFee;
    const vatAmount = (grossIncome * vatRate) / 100;
    const taxableIncome = vatAmount; // Only VAT portion is taxable
    const taxAmount = (taxableIncome * taxRate) / 100;
    const netIncome = grossIncome - taxAmount - monthlyYelFee; // Subtract YEL fee

    // Competitor income
    const competitorGrossIncome = competitorRate * hoursPerMonth;

    // Total monthly income
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

  return (
    <div className="p-4 max-w-xl mx-auto">
      <Card className="shadow-sm">
        <Card.Body>
          <h1 className="text-center mb-4">Hintavertailulaskuri</h1>

          <Form>
            <Form.Group className="mb-3">
              <Form.Label>Kilpailijan tuntihinta (€)</Form.Label>
              <Form.Control
                type="number"
                value={competitorRate}
                onChange={(e) => setCompetitorRate(parseFloat(e.target.value) || 0)}
              />
            </Form.Group>

            <Form.Group className="mb-3">
              <Form.Label>Tunteja kuukaudessa</Form.Label>
              <Form.Control
                type="text"
                value={hoursPerMonth}
                onChange={(e) => setHoursPerMonth(parseFloat(e.target.value) || 0)}
              />
            </Form.Group>

            <Form.Group className="mb-3">
              <Form.Label>Kansaneläke (€/kk)</Form.Label>
              <Form.Control
                type="text"
                value={monthlyPension}
                onChange={(e) => setMonthlyPension(parseFloat(e.target.value) || 0)}
              />
            </Form.Group>

            <Form.Group className="mb-3">
              <Form.Label>Asumistuki (€/kk)</Form.Label>
              <Form.Control
                type="text"
                value={housingAllowance}
                onChange={(e) => setHousingAllowance(parseFloat(e.target.value) || 0)}
              />
            </Form.Group>

            <Form.Group className="mb-3">
              <Form.Label>Takuueläke (€/kk)</Form.Label>
              <Form.Control
                type="text"
                value={guaranteePension}
                onChange={(e) => setGuaranteePension(parseFloat(e.target.value) || 0)}
              />
            </Form.Group>

            <Form.Group className="mb-3">
              <Form.Label>YEL-työtulo (€/vuosi)</Form.Label>
              <Form.Control
                type="number"
                value={yelIncome}
                onChange={(e) => setYelIncome(parseFloat(e.target.value) || 0)}
              />
            </Form.Group>

            <Form.Group className="mb-3">
              <Form.Check
                type="checkbox"
                label="Aloittava yrittäjä (22% alennus YEL-maksusta)"
                checked={isStartingEntrepreneur}
                onChange={(e) => setIsStartingEntrepreneur(e.target.checked)}
              />
            </Form.Group>

            <Form.Group className="mb-3">
              <Form.Label>Ennakonpidätys + Kirkollisvero (%)</Form.Label>
              <Form.Control
                type="number"
                value={taxRate}
                onChange={(e) => setTaxRate(parseFloat(e.target.value) || 0)}
              />
            </Form.Group>

            <Button variant="primary" className="w-100" disabled>
              Laske
            </Button>
          </Form>

          <div className="mt-4">
            <h2 className="text-center">Tulokset:</h2>
            <p>Omat bruttotulot (kiinteä kuukausihinta): €{result.grossIncome}</p>
            <p>ALV-osuus: €{result.vatAmount}</p>
            <p>Verot: €{result.taxAmount}</p>
            <p>YEL-maksu: €{result.monthlyYelFee}</p>
            <p>Nettotulot: €{result.netIncome}</p>
            <p>Kilpailijan bruttopalkka: €{result.competitorGrossIncome}</p>
            <p>Kokonaiskuukausitulot (sis. eläke, asumistuki ja takuueläke): €{result.totalMonthlyIncome}</p>
          </div>
        </Card.Body>
      </Card>
    </div>
  );
};

export default IncomeComparisonCalculator;