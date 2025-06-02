import React, { useState, useEffect } from "react";
import axios from "axios";
import Table from 'react-bootstrap/Table';
import LOADING from '../../tube-spinner.svg';
import {
  BarChart,
  Bar,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
} from "recharts";
import { API_BASE_URL, API_DEFAULT_LANGUAGE } from "../../constants/apiConstants";
// ES6 module syntax
import LocalizedStrings from 'react-localization';

let strings = new LocalizedStrings({
  en:{
    title:"Cumulative Sales Chart",
    error:"Failed to fetch transactions. Please try again.",
    loading:"Loading...",
    name:"Cumulative Vendor Balance",
  },
  fi: {
    title: "Kumulatiivinen myyntikaavio",
    error: "Tapahtumien hakeminen epäonnistui. Yritä uudelleen.",
    loading: "Ladataan...",
    name: "Kumulatiivinen toimittajan saldo"
  },
  sv: {
    title: "Kumulativ försäljningsdiagram",
    error: "Misslyckades med att hämta transaktioner. Försök igen.",
    loading: "Laddar...",
    name: "Kumulativ leverantörssaldo"
  }
 });

 const CustomTooltip = ({ active, payload, label }) => {
  if (active && payload && payload.length) {
    return (
      <div className="custom-tooltip" style={{ background: "#000", border: "1px solid #ccc", padding: 10 }}>
        <div>
          <strong>{strings.title}:</strong> {label}
        </div>
        <div>
          <strong>{strings.name}:</strong> {payload[0].value}
        </div>
      </div>
    );
  }
  return null;
};

const CumulativeChart = ({ revenueSource }) => {
  const [chartData, setChartData] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [lang, setLang] = useState(API_DEFAULT_LANGUAGE);

  const htmlLang = document.documentElement.lang || API_DEFAULT_LANGUAGE;
  strings.setLanguage(htmlLang);

  useEffect(() => {
    fetchCumulativeData();
  }, [revenueSource]); // Add revenueSource as dependency

  const fetchCumulativeData = async () => {
    try {
      const response = await axios.get(
        `${API_BASE_URL}/api/reports/cumulative-sales?source=${revenueSource}`
      ); // Pass revenueSource to backend
      setChartData(response.data.root);
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
      <ResponsiveContainer width="100%" height={400}>
        <BarChart data={chartData}>
          <CartesianGrid strokeDasharray="3 3" />
          <XAxis dataKey="saleDate" />
          <YAxis />
          <Tooltip content={<CustomTooltip />} />
          <Bar dataKey="cumulativeVendorBalance" fill="#99ff00" name={strings.name} />
        </BarChart>
      </ResponsiveContainer>
    </div>
  );
};

export default CumulativeChart;