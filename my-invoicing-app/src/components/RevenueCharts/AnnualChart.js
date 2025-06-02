import React, { useState, useEffect } from "react";
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from "recharts";
import axios from "axios";
import LOADING from '../../tube-spinner.svg';
import { API_BASE_URL, API_DEFAULT_LANGUAGE } from "../../constants/apiConstants";
// ES6 module syntax
import LocalizedStrings from 'react-localization';

let strings = new LocalizedStrings({
    en: {
      title: "Solvency %",
      error: "Failed to fetch transactions. Please try again.",
      loading: "Loading...",
      name: "Solvency",
    },
    fi: {
      title: "Omavaraisuus %",
      error: "Tapahtumien hakeminen epäonnistui. Yritä uudelleen.",
      loading: "Ladataan...",
      name: "Omavaraisuus",
    },
    sv: {
      title: "Soliditet %",
      error: "Misslyckades med att hämta transaktioner. Försök igen.",
      loading: "Laddar...",
      name: "Soliditet",
    },
  });

const AnnualChart = () => {
  const [chartData, setChartData] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [lang, setLang] = useState(API_DEFAULT_LANGUAGE);

  const htmlLang = document.documentElement.lang || API_DEFAULT_LANGUAGE;
  strings.setLanguage(htmlLang);

  useEffect(() => {
    const fetchData = async () => {
      try {
        const response = await axios.get(API_BASE_URL + "/api/reports/solvency-data");
        const data = response.data;

        // Format data for Recharts
        const formattedData = data.map((item) => ({
          year: item.year,
          solvency: item.solvency, // Omavaraisuus %
        }));

        setChartData(formattedData);
        setLoading(false);
      } catch (err) {
        setError(strings.error);
        setLoading(false);
      }
    };

    fetchData();
  }, []);

  if (loading) return <div className="loading-screen"><img src={LOADING} alt={strings.loading} /></div>;
  if (error) return <p style={{ color: "red" }}>{error}</p>;

  return (
    <div>
        <h2 className="calculator-title">{strings.title}</h2>
        <ResponsiveContainer width="100%" height="100%">
            <BarChart data={chartData} margin={{ top: 20, right: 30, left: 20, bottom: 5 }}>
                <CartesianGrid strokeDasharray="3 3" />
                <XAxis dataKey="year" />
                <YAxis tickFormatter={(value) => `${value}%`} />
                <Tooltip formatter={(value) => `${value}%`} />
                <Bar dataKey="solvency" fill="#0088FE" name={strings.name} />
            </BarChart>
        </ResponsiveContainer>
    </div>
  );
};

export default AnnualChart;
