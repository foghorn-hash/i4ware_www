import React, { useState, useEffect } from "react";
import axios from "axios";
import Table from 'react-bootstrap/Table';
import {
  ResponsiveContainer,
  BarChart,
  CartesianGrid,
  XAxis,
  YAxis,
  Tooltip,
  Bar,
  Brush,
} from "recharts";
import LOADING from '../../tube-spinner.svg';
import { API_BASE_URL, API_DEFAULT_LANGUAGE } from "../../constants/apiConstants";
// ES6 module syntax
import LocalizedStrings from 'react-localization';

let strings = new LocalizedStrings({
  en: {
    title: "Yearly Sales Transactions",
    error: "Failed to fetch transactions. Please try again.",
    loading: "Loading...",
    name: "Vendor Balance",
    description: "These come from our real-time accounting system via a REST API interface from databases.",
    tableYear: "Year",
    tableSales: "Annual Sales",
    tableShare: "Share (%)",
    total: "Total"
  },
  fi: {
    title: "Vuotuiset myyntitapahtumat",
    error: "Tapahtumien hakeminen epäonnistui. Yritä uudelleen.",
    loading: "Ladataan...",
    name: "Toimittajan saldo",
    description: "Nämä tulevat reaaliaikaisesta kirjanpidostamme REST API -rajapinnan kautta tietokannoista.",
    tableYear: "Vuosi",
    tableSales: "Vuosimyynti",
    tableShare: "Osuus (%)",
    total: "Yhteensä"
  },
  sv: {
    title: "Årliga försäljningstransaktioner",
    error: "Misslyckades med att hämta transaktioner. Försök igen.",
    loading: "Laddar...",
    name: "Leverantörssaldo",
    description: "Dessa kommer från vårt realtidsbokföringssystem via ett REST API-gränssnitt från databaser.",
    tableYear: "År",
    tableSales: "Årsförsäljning",
    tableShare: "Andel (%)",
    total: "Totalt"
  },
  ar: {
    title: "معاملات المبيعات السنوية",
    error: "فشل في جلب المعاملات. يرجى المحاولة مرة أخرى.",
    loading: "جاري التحميل...",
    name: "رصيد المورد",
    description: "تأتي هذه البيانات من نظام المحاسبة الفوري الخاص بنا عبر واجهة REST API من قواعد البيانات.",
    tableYear: "السنة",
    tableSales: "المبيعات السنوية",
    tableShare: "النسبة (%)",
    total: "الإجمالي"
  }
});

const CustomTooltip = ({ active, payload, label }) => {
  if (active && payload && payload.length) {
    return (
      <div className="custom-tooltip">
        <p>
          <strong>{strings.title}:</strong> {label}{" "}
          <strong>{strings.name}:</strong> {Number(payload[0].value).toFixed(2)} €
        </p>
      </div>
    );
  }
  return null;
};

const TransactionsTable = ({ revenueSource }) => {
  const [transactions, setTransactions] = useState([]);
  const [chartData, setChartData] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

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

  useEffect(() => {
    const fetchData = async () => {
      setLoading(true); setError(null);
      try {
        const response = await axios.get(
          `${API_BASE_URL}/api/reports/sales-report?source=${revenueSource}`
        );
        const data = response.data.root || [];

        // Format data for Recharts
        const formattedData = data.map((item) => ({
          year: item.saleYear,
          revenue: parseFloat(item.balanceVendor) || 0,
        }));

        setTransactions(data);
        setChartData(formattedData);
        setLoading(false);
      } catch (err) {
        setError(strings.error);
        setLoading(false);
      }
    };

    fetchData();
  }, [revenueSource]);

  if (loading) return <div className="loading-screen"><img src={LOADING} alt={strings.loading} /></div>;
  if (error) return <p style={{ color: "red" }}>{error}</p>;

  const totalAnnualSales = chartData.reduce((acc, curr) => acc + curr.revenue, 0);

  return (
    <div style={{ marginBottom: 35 }}>
      <h2 className="calculator-title">{strings.title}</h2>
      <p style={{ color: '#94a3b8', fontSize: '0.95rem' }}>{strings.description}</p>
      
      {/* Bar Chart */}
      <ResponsiveContainer width="100%" height={380}>
        <BarChart data={chartData} margin={{ top: 10, right: 20, left: 10, bottom: 40 }}>
          <CartesianGrid strokeDasharray="3 3" stroke="rgba(255,255,255,0.08)" />
          <XAxis dataKey="year" stroke="#94a3b8" tickMargin={8} />
          <YAxis stroke="#94a3b8" tickFormatter={(v) => `${v} €`} />
          <Tooltip content={<CustomTooltip />} />
          <Bar dataKey="revenue" fill="#ff0066" name={strings.name} radius={[4, 4, 0, 0]} />
        </BarChart>
      </ResponsiveContainer>

      {/* HTML Table of Sales Statistics */}
      {chartData.length > 0 && (
        <div style={{ marginTop: 20 }}>
          <Table striped bordered hover responsive variant="dark" className="modern-stats-table">
            <thead>
              <tr>
                <th className="text-start">{strings.tableYear}</th>
                <th className="text-end">{strings.tableSales}</th>
                <th className="text-end">{strings.tableShare}</th>
              </tr>
            </thead>
            <tbody>
              {chartData.map((row) => {
                const share = totalAnnualSales > 0 ? ((row.revenue / totalAnnualSales) * 100).toFixed(1) : "0.0";
                return (
                  <tr key={row.year}>
                    <td className="text-start font-weight-bold">
                      <strong>{row.year}</strong>
                    </td>
                    <td className="text-end font-mono">
                      {row.revenue.toFixed(2)} €
                    </td>
                    <td className="text-end font-mono">
                      <span className="share-pill">{share} %</span>
                    </td>
                  </tr>
                );
              })}
            </tbody>
            <tfoot>
              <tr className="table-total-row">
                <td className="text-start table-total-label">
                  <strong>{strings.total}</strong>
                </td>
                <td className="text-end font-mono table-total-amount">
                  <strong>{totalAnnualSales.toFixed(2)} €</strong>
                </td>
                <td className="text-end font-mono table-total-amount">
                  <strong>100.0 %</strong>
                </td>
              </tr>
            </tfoot>
          </Table>
        </div>
      )}
    </div>
  );
};

export default TransactionsTable;