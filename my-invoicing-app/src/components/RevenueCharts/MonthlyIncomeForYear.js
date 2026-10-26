import React, { useEffect, useState } from "react";
import axios from "axios";
import Table from 'react-bootstrap/Table';
import LOADING from "../../tube-spinner.svg";
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
import { API_BASE_URL, API_DEFAULT_LANGUAGE } from "../../constants/apiConstants";
import LocalizedStrings from "react-localization";

let strings = new LocalizedStrings({
  en: { 
    title: "Monthly Income", 
    loading: "Loading...", 
    error: "Failed to fetch monthly income.", 
    total: "Total",
    month: "Month",
    income: "Monthly Income",
    share: "Share (%)",
    src_all: "All Sources", 
    src_atlassian: "Atlassian Pty Ltd", 
    src_paypal: "PayPal Subscriptions",
    src_kela: "Pension Insurance",
    src_hourly: "Hourly Rate Customers", 
    src_grandparents: "Grandparents' Inheritance",
    src_capital: "Capital Income",
    src_kk: "Monthly Income"
  },
  fi: { 
    title: "Kuukausitulot", 
    loading: "Ladataan...", 
    error: "Kuukausitulojen hakeminen epäonnistui.", 
    total: "Yhteensä",
    month: "Kuukausi",
    income: "Kuukausitulo",
    share: "Osuus (%)",
    src_all: "Kaikki lähteet", 
    src_atlassian: "Atlassian Pty Ltd", 
    src_paypal: "PayPal-tilaukset",
    src_kela: "Eläkevakuutus",
    src_hourly: "Tuntiveloitusasiakkaat", 
    src_grandparents: "Isovanhempien perintö",
    src_capital: "Pääomatulot",
    src_kk: "Kuukausittainen tulo"
  },
  sv: { 
    title: "Månadsinkomst", 
    loading: "Laddar...", 
    error: "Misslyckades att hämta månadsinkomst.", 
    total: "Totalt",
    month: "Månad",
    income: "Månadsinkomst",
    share: "Andel (%)",
    src_all: "Alla källor", 
    src_atlassian: "Atlassian Pty Ltd", 
    src_paypal: "PayPal-prenumerationer",
    src_kela: "Pensionsförsäkring",
    src_hourly: "Timdebiterade kunder", 
    src_grandparents: "Mor- och farföräldrars arv",
    src_capital: "Kapitalinkomster",
    src_kk: "Månatlig inkomst"
  },
  ar: { 
    title: "الدخل الشهري", 
    loading: "جاري التحميل...", 
    error: "فشل في جلب الدخل الشهري.", 
    total: "الإجمالي",
    month: "الشهر",
    income: "الدخل الشهري",
    share: "النسبة (%)",
    src_all: "جميع المصادر", 
    src_atlassian: "Atlassian Pty Ltd", 
    src_paypal: "اشترا اشتراكات PayPal",
    src_kela: "التأمين التقاعدي",
    src_hourly: "عملاء السعر بالساعة", 
    src_grandparents: "ميراث الأجداد",
    src_capital: "دخل رأس المال",
    src_kk: "الدخل الشهري"
  },
});

const MonthlyIncomeForYear = ({ revenueSource = "all", year = new Date().getFullYear() }) => {
  const [lang, setLang] = useState(document.documentElement.lang || API_DEFAULT_LANGUAGE);
  const [data, setData] = useState([]); // 12 rows: {label, total}
  const [yearTotal, setYearTotal] = useState(0);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const keyToSourceLabel = (key) => strings[`src_${key}`] || key;

  // track <html lang="">
  useEffect(() => {
    const obs = new MutationObserver(() => setLang(document.documentElement.lang || API_DEFAULT_LANGUAGE));
    obs.observe(document.documentElement, { attributes: true, attributeFilter: ["lang"] });
    return () => obs.disconnect();
  }, []);
  useEffect(() => strings.setLanguage(lang), [lang]);

  useEffect(() => {
    let cancelled = false;
    (async () => {
      setLoading(true); setError(null);
      try {
        const url = `${API_BASE_URL}/api/reports/merged-monthly-sums?year=${encodeURIComponent(year)}&source=${encodeURIComponent(revenueSource)}`;
        const r = await axios.get(url);
        if (!cancelled) {
          setData(Array.isArray(r?.data?.root) ? r.data.root : []);
          setYearTotal(Number(r?.data?.yearTotal || 0));
        }
      } catch (e) {
        if (!cancelled) setError(strings.error);
      } finally {
        if (!cancelled) setLoading(false);
      }
    })();
    return () => { cancelled = true; };
  }, [revenueSource, year]);

  if (loading) return <div className="loading-screen"><img src={LOADING} alt={strings.loading} /></div>;
  if (error) return <p style={{ color: "red" }}>{error}</p>;

  return (
    <div style={{ marginTop: 30, marginBottom: 40 }}>
      <h2 className="calculator-title">
        {strings.title} — {year} ({keyToSourceLabel(revenueSource)})
      </h2>
      <div style={{ marginBottom: 16, color: '#e2e8f0', fontSize: '1.05rem' }}>
        <strong>{strings.total}:</strong> <span className="highlight-val font-mono">{yearTotal.toFixed(2)} €</span>
      </div>

      <ResponsiveContainer width="100%" height={380}>
        <BarChart data={data} margin={{ top: 16, right: 24, left: 16, bottom: 24 }} barCategoryGap="25%">
          <CartesianGrid strokeDasharray="3 3" stroke="rgba(255,255,255,0.08)" />
          <XAxis dataKey="label" tickMargin={8} height={40} stroke="#94a3b8" />
          <YAxis stroke="#94a3b8" tickFormatter={(v) => `${v} €`} />
          <Tooltip formatter={(v)=>[`${Number(v).toFixed(2)} €`, strings.total]} />
          <Bar dataKey="total" name={strings.total} fill="#d71bdd" radius={[4, 4, 0, 0]} />
        </BarChart>
      </ResponsiveContainer>

      {/* HTML Table of Monthly Breakdown */}
      {data.length > 0 && (
        <div style={{ marginTop: 20 }}>
          <Table striped bordered hover responsive variant="dark" className="modern-stats-table">
            <thead>
              <tr>
                <th className="text-start">{strings.month}</th>
                <th className="text-end">{strings.income}</th>
                <th className="text-end">{strings.share}</th>
              </tr>
            </thead>
            <tbody>
              {data.map((row, idx) => {
                const totalVal = Number(row.total || 0);
                const share = yearTotal > 0 ? ((totalVal / yearTotal) * 100).toFixed(1) : "0.0";
                return (
                  <tr key={`${row.label}-${idx}`}>
                    <td className="text-start">
                      <strong>{row.label}</strong>
                    </td>
                    <td className="text-end font-mono">
                      {totalVal.toFixed(2)} €
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
                  <strong>{strings.total} ({year})</strong>
                </td>
                <td className="text-end font-mono table-total-amount">
                  <strong>{yearTotal.toFixed(2)} €</strong>
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

export default MonthlyIncomeForYear;
