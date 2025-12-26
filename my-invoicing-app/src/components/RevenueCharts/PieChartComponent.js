import React, { useState, useEffect } from "react";
import axios from "axios";
import { PieChart, Pie, Cell, Tooltip, ResponsiveContainer } from "recharts";
import LOADING from "../../tube-spinner.svg";
import { API_BASE_URL, API_DEFAULT_LANGUAGE } from "../../constants/apiConstants";
// ES6 module syntax
import LocalizedStrings from 'react-localization';

let strings = new LocalizedStrings({
    en: {
      title: "Yearly Sales Distribution",
      error: "Failed to fetch yearly sales data. Please try again.",
      loading: "Loading yearly sales data...",
      name: "Yearly Sales Percentage"
    },
    fi: {
      title: "Vuotiset myynnin jakaumat",
      error: "Vuotuisten myyntitietojen hakeminen epäonnistui. Yritä uudelleen.",
      loading: "Ladataan vuotuisia myyntitietoja...",
      name: "Vuotuinen myynnin prosenttiosuus"
    },
    sv: {
      title: "Årlig försäljningsfördelning",
      error: "Misslyckades med att hämta årliga försäljningsdata. Försök igen.",
      loading: "Laddar årliga försäljningsdata...",
      name: "Årlig försäljningsprocent"
    }
  });  

const COLORS = ["#0088FE", "#00C49F", "#FFBB28", "#FF8042", "#ff0066"];

const PieChartComponent = ({ revenueSource }) => {
  const [data, setData] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [lang, setLang] = useState(API_DEFAULT_LANGUAGE);

  const htmlLang = document.documentElement.lang || API_DEFAULT_LANGUAGE;
  strings.setLanguage(htmlLang);

  useEffect(() => {
    fetchPieData();
  }, [revenueSource]); // Add revenueSource as dependency

  const fetchPieData = async () => {
    try {
      setLoading(true); setError(null);
      const response = await axios.get(
        `${API_BASE_URL}/api/reports/sales-distribution?source=${revenueSource}`
      ); // Pass revenueSource to backend
      const fetchedData = response.data.root;

      // Format data for pie chart
      const formattedData = fetchedData.map((item) => ({
        year: item.year,
        value: parseFloat(item.salesPercentage),
      }));

      setData(formattedData);
    } catch (err) {
      setError(strings.error);
    } finally {
      setLoading(false);
    }
  };

  if (loading) return <div className="loading-screen"><img src={LOADING} alt={strings.loading} /></div>;
  if (error) return <p style={{ color: "red" }}>{error}</p>;

  return (
    <div className="chart-container">
      <h2 className="calculator-title">{strings.title}</h2>
      <ResponsiveContainer width="100%" height={400}>
        <PieChart>
          <Pie
            data={data}
            cx="50%"
            cy="50%"
            label={(entry) => `${entry.year} (${entry.value}%)`}
            outerRadius={150}
            fill="#8884d8"
            dataKey="value"
          >
            {data.map((entry, index) => (
              <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
            ))}
          </Pie>
          <Tooltip />
        </PieChart>
      </ResponsiveContainer>
    </div>
  );
};

export default PieChartComponent;
