import React, { useEffect, useState } from "react";
import axios from "axios";
import TransactionsTable from "./TransactionsTable";
import TransactionsTableAll from "./TransactionsTableAll";
import CumulativeChart from "./CumulativeChart";
import PieChartComponent from "./PieChartComponent";
import MonthlyIncomeForYear from "./MonthlyIncomeForYear";
import { API_BASE_URL, API_DEFAULT_LANGUAGE } from "../../constants/apiConstants";
import LocalizedStrings from "react-localization";

let strings = new LocalizedStrings({
  en: {
    all: "All Sources",
    atlassian: "Atlassian Pty Ltd",
    kela: "Pension Insurance",
    hourly: "Hourly Rate Customers",
    grandparents: "Grandparents' Inheritance",
    year: "Year",
  },
  fi: {
    all: "Kaikki lähteet",
    atlassian: "Atlassian Pty Ltd",
    kela: "Eläkevakuutus",
    hourly: "Tuntiveloitusasiakkaat",
    grandparents: "Isovanhempien perintö",
    year: "Vuosi",
  },
  sv: {
    all: "Alla källor",
    atlassian: "Atlassian Pty Ltd",
    kela: "Pensionsförsäkring",
    hourly: "Timdebiterade kunder",
    grandparents: "Mor- och farföräldrars arv",
    year: "År",
  },
});

const Charts = () => {
  const [revenueSource, setRevenueSource] = useState("all");
  const [availableYears, setAvailableYears] = useState([]); // [2019, 2020, ...]
  const [year, setYear] = useState(new Date().getFullYear());
  const [lang, setLang] = useState(document.documentElement.lang || API_DEFAULT_LANGUAGE);

  // keep localization synced with <html lang="">
  useEffect(() => {
    const observer = new MutationObserver(() => {
      setLang(document.documentElement.lang || API_DEFAULT_LANGUAGE);
    });
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ["lang"] });
    return () => observer.disconnect();
  }, []);
  useEffect(() => strings.setLanguage(lang), [lang]);

  // fetch list of years whenever the source changes
useEffect(() => {
  let cancelled = false;

  (async () => {
    try {
      const url = `${API_BASE_URL}/api/reports/income-years?source=${encodeURIComponent(revenueSource)}`;
      const resp = await axios.get(url);
      const years = Array.isArray(resp?.data?.years) ? resp.data.years : [];
      if (cancelled) return;

      if (years.length) {
        const sorted = years.slice().sort((a, b) => a - b);
        setAvailableYears(sorted);
        // keep current year if still available, otherwise pick latest
        setYear(prev => (sorted.includes(prev) ? prev : sorted[sorted.length - 1]));
      } else {
        const cur = new Date().getFullYear();
        setAvailableYears([cur]);
        setYear(cur);
      }
    } catch {
      if (cancelled) return;
      const cur = new Date().getFullYear();
      setAvailableYears([cur]);
      setYear(cur);
    }
  })();

  return () => { cancelled = true; };
}, [revenueSource]);

  return (
    <div>
      {/* Controls */}
      <div style={{ display: "flex", gap: 12, marginBottom: 20, flexWrap: "wrap" }}>
        <select
          className="revenue-source-select"
          value={revenueSource}
          onChange={(e) => setRevenueSource(e.target.value)}
        >
          <option value="all">{strings.all}</option>
          <option value="atlassian">{strings.atlassian}</option>
          <option value="kela">{strings.kela}</option>
          <option value="hourly">{strings.hourly}</option>
          <option value="grandparents">{strings.grandparents}</option>
        </select>
      </div>

      {/* Tables & Charts */}
      <TransactionsTable revenueSource={revenueSource} />
      <select
          className="revenue-source-select"
          value={year}
          onChange={(e) => setYear(Number(e.target.value))}
          aria-label={strings.year}
        >
          {availableYears.map((y) => (
            <option key={y} value={y}>{y}</option>
          ))}
      </select>
      <MonthlyIncomeForYear revenueSource={revenueSource} year={year} />
      <TransactionsTableAll revenueSource={revenueSource} />
      <CumulativeChart revenueSource={revenueSource} />
      <PieChartComponent revenueSource={revenueSource} />
    </div>
  );
};

export default Charts;
