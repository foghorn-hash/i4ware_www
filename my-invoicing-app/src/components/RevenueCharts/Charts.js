import React, { useState, useContext, useEffect } from "react";
import TransactionsTable from "./TransactionsTable";
import TransactionsTableAll from "./TransactionsTableAll";
import CumulativeChart from "./CumulativeChart";
import PieChartComponent from "./PieChartComponent";
import { API_DEFAULT_LANGUAGE } from "../../constants/apiConstants";
// ES6 module syntax
import LocalizedStrings from 'react-localization';

let strings = new LocalizedStrings({
  en: {
    all: "All Sources",
    atlassian: "Atlassian Pty Ltd",
    kela: "Pension Insurance",
    horuly: "Hourly Rate Customers",
    grandparents: "Grandparents' Inheritance"
  },
  fi: {
    all: "Kaikki lähteet",
    atlassian: "Atlassian Pty Ltd",
    kela: "Eläkevakuutus",
    horuly: "Tuntiveloitusasiakkaat",
    grandparents: "Isovanhempien perintö"
  },
  sv: {
    all: "Alla källor",
    atlassian: "Atlassian Pty Ltd",
    kela: "Pensionsförsäkring",
    horuly: "Timdebiterade kunder",
    grandparents: "Mor- och farföräldrars arv"
  },
});

const Charts = () => {
    const [revenueSource, setRevenueSource] = useState('all');

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

    return (
      <div>
        <select
          className="revenue-source-select"
          value={revenueSource}
          onChange={(e) => setRevenueSource(e.target.value)}
          style={{ marginBottom: 20 }}
        >
          <option value="all">{strings.all}</option>
          <option value="atlassian">{strings.atlassian}</option>
          <option value="kela">{strings.kela}</option>
          <option value="hourly">{strings.horuly}</option>
          <option value="grandparents">{strings.grandparents}</option>
        </select>
        <TransactionsTable revenueSource={revenueSource} />
        <TransactionsTableAll revenueSource={revenueSource} />
        <CumulativeChart revenueSource={revenueSource} />
        <PieChartComponent revenueSource={revenueSource} />
      </div>
    );
};

export default Charts;
