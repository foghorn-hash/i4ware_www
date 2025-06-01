import React, { useState, useEffect } from "react";
import axios from "axios";
import Table from 'react-bootstrap/Table';
import { BarChart, Bar, XAxis, YAxis, Tooltip, CartesianGrid, ResponsiveContainer } from "recharts";
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
    description: "These come from our real-time accounting system via a REST API interface from databases."
  },
  fi: {
    title: "Vuotuiset myyntitapahtumat",
    error: "Tapahtumien hakeminen epäonnistui. Yritä uudelleen.",
    loading: "Ladataan...",
    name: "Toimittajan saldo",
    description: "Nämä tulevat reaaliaikaisesta kirjanpidotamme REST API -rajapinnan kautta tietokannoista."
  },
  sv: {
    title: "Årliga försäljningstransaktioner",
    error: "Misslyckades med att hämta transaktioner. Försök igen.",
    loading: "Laddar...",
    name: "Leverantörssaldo",
    description: "Dessa kommer från vårt realtidsbokföringssystem via ett REST API-gränssnitt från databaser."
  }
});

const TransactionsTable = ({ revenueSource }) => {
  const [transactions, setTransactions] = useState([]);
  const [chartData, setChartData] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [lang, setLang] = useState(API_DEFAULT_LANGUAGE);

  var query = window.location.search.substring(1);
  var urlParams = new URLSearchParams(query);
  var localization = urlParams.get('lang');

  if (localization===null) {
    strings.setLanguage(API_DEFAULT_LANGUAGE);
  } else {
    strings.setLanguage(localization);
  }

  useEffect(() => {
    const fetchData = async () => {
      try {
        const response = await axios.get(
          `${API_BASE_URL}/api/reports/sales-report?source=${revenueSource}`
        );
        const data = response.data.root || [];

        // Format data for Recharts
        const formattedData = data.map((item) => ({
          year: item.saleYear,
          revenue: item.balanceVendor,
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

  return (
    <div>
      <h2 className="calculator-title">{strings.title}</h2>
      <p>{strings.description}</p>
      {/* Bar Chart */}
      <ResponsiveContainer width="100%" height={400}>
        <BarChart data={chartData}>
          <CartesianGrid strokeDasharray="3 3" />
          <XAxis dataKey="year" />
          <YAxis />
          <Tooltip />
          <Bar dataKey="revenue" fill="#ff0066" name={strings.name} />
        </BarChart>
      </ResponsiveContainer>
    </div>
  );
};

export default TransactionsTable;