import React, { useState, useEffect } from "react";
import axios from "axios";
import Table from 'react-bootstrap/Table';
import LOADING from '../../tube-spinner.svg';
import {
  BarChart,
  Bar,
  XAxis,
  YAxis,
  Tooltip,
  CartesianGrid,
  ResponsiveContainer,
} from "recharts";
import { API_BASE_URL, API_DEFAULT_LANGUAGE } from "../../constants/apiConstants";
// ES6 module syntax
import LocalizedStrings from 'react-localization';

let strings = new LocalizedStrings({
  en:{
    title:"Transactions with Bar Chart",
    error:"Failed to fetch transactions. Please try again.",
    loading:"Loading...",
    name:"Vendor Amount",
  },
  fi: {
    title: "Tapahtumat pylväskaavion kanssa",
    error: "Tapahtumien hakeminen epäonnistui. Yritä uudelleen.",
    loading: "Ladataan...",
    name: "Toimittajan määrä"
  },
  sv: {
    title: "Transaktioner med stapeldiagram",
    error: "Misslyckades med att hämta transaktioner. Försök igen.",
    loading: "Laddar...",
    name: "Leverantörens belopp"
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

const TransactionsTableAll = ({ revenueSource }) => {
  const [transactions, setTransactions] = useState([]);
  const [transactionsMerged, setTransactionsMerged] = useState([]);
  const [chartData, setChartData] = useState([]);
  const [chartDataMerged, setChartDataMerged] = useState([]);
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
    fetchMergedTransactions();
  }, [revenueSource]); // Add revenueSource as dependency

  const fetchMergedTransactions = async () => {
    try {
      const response = await axios.get(
        `${API_BASE_URL}/api/reports/merged-sales?source=${revenueSource}`
      ); // Pass revenueSource to backend
      const data = response.data.root;

      // Prepare chart data
      const formattedChartData = data.map((item) => ({
        saleDate: item.saleDate,
        vendorAmount: parseFloat(item.vendorAmount),
        balanceVendor: parseFloat(item.balanceVendor),
      }));

      setTransactionsMerged(data);
      setChartDataMerged(formattedChartData);
    } catch (err) {
      setError(strings.error);
    } finally {
      setLoading(false);
    }
  };

  if (loading) return <div className="loading-screen"><img src={LOADING} alt={strings.loading} /></div>;
  if (error) return <p style={{ color: "red" }}>{error}</p>;

  return (
    <div>
      <h2 className="calculator-title">{strings.title}</h2>
      {/* Bar Chart */}
      <ResponsiveContainer width="100%" height={420}>
        <BarChart
          data={chartDataMerged}
          margin={{ top: 16, right: 24, left: 16, bottom: 70 }}
          barCategoryGap="20%"       // more space between bars
        >
          <CartesianGrid strokeDasharray="3 3" />
          <XAxis
            dataKey="saleDate"
            angle={-45}               // easier to read than -90
            textAnchor="end"
            interval="preserveStartEnd" // auto-skip in between
            minTickGap={20}           // force gaps between ticks
            tickMargin={10}
            tick={{ fontSize: 12 }}   // bump size a bit
            allowDuplicatedCategory={false}
            tickFormatter={(d) => d.slice(0, 7)} // e.g. "YYYY-MM" (shorter)
          />
          <YAxis />
          <Tooltip content={<CustomTooltip />} />
          <Bar dataKey="vendorAmount" fill="#007bff" name={strings.name} />
        </BarChart>
      </ResponsiveContainer>
    </div>
  );
};

export default TransactionsTableAll;