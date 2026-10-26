import React, { useState, useEffect, useMemo } from "react";
import axios from "axios";
import Table from 'react-bootstrap/Table';
import LOADING from '../../tube-spinner.svg';
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
import LocalizedStrings from 'react-localization';

let strings = new LocalizedStrings({
  en: {
    title: "Transactions Overview & Sales Statistics",
    error: "Failed to fetch transactions. Please try again.",
    loading: "Loading transactions...",
    name: "Vendor Amount",
    allTransactions: "Transactions Table",
    saleDate: "Sale Date",
    revenueSource: "Revenue Source",
    itemDescription: "Item / Service",
    customer: "Customer",
    vendorAmount: "Vendor Amount",
    totalSales: "Total Sales",
    transactionCount: "Transactions",
    averageSale: "Avg. Transaction",
    searchPlaceholder: "Search by date, source, customer or item...",
    page: "Page",
    of: "of",
    prev: "Previous",
    next: "Next",
    rowsPerPage: "Rows",
    total: "Total",
    noMatching: "No matching transactions found."
  },
  fi: {
    title: "Tapahtumakatsaus & Myyntitilastot",
    error: "Tapahtumien hakeminen epäonnistui. Yritä uudelleen.",
    loading: "Ladataan tapahtumia...",
    name: "Toimittajan määrä",
    allTransactions: "Tapahtumataulukko",
    saleDate: "Myyntipäivä",
    revenueSource: "Tulojen lähde",
    itemDescription: "Palvelu / Tuote",
    customer: "Asiakas",
    vendorAmount: "Toimittajan määrä",
    totalSales: "Kokonaismyynti",
    transactionCount: "Tapahtumia yht.",
    averageSale: "Keskiostos",
    searchPlaceholder: "Hae päivämäärällä, lähteellä, asiakkaalla...",
    page: "Sivu",
    of: "/",
    prev: "Edellinen",
    next: "Seuraava",
    rowsPerPage: "Riviä",
    total: "Yhteensä",
    noMatching: "Hakua vastaavia tapahtumia ei löytynyt."
  },
  sv: {
    title: "Transaktionsöversikt & Försäljningsstatistik",
    error: "Misslyckades med att hämta transaktioner. Försök igen.",
    loading: "Laddar transaktioner...",
    name: "Leverantörens belopp",
    allTransactions: "Transaktionstabell",
    saleDate: "Försäljningsdatum",
    revenueSource: "Inkomstkälla",
    itemDescription: "Tjänst / Produkt",
    customer: "Kund",
    vendorAmount: "Leverantörens belopp",
    totalSales: "Total försäljning",
    transactionCount: "Transaktioner",
    averageSale: "Genomsnitt",
    searchPlaceholder: "Sök efter datum, källa, kund...",
    page: "Sida",
    of: "av",
    prev: "Föregående",
    next: "Nästa",
    rowsPerPage: "Rader",
    total: "Totalt",
    noMatching: "Inga matchande transaktioner hittades."
  },
  ar: {
    title: "نظرة عامة على المعاملات وإحصاءات المبيعات",
    error: "فشل في جلب المعاملات. يرجى المحاولة مرة أخرى.",
    loading: "جاري تحميل المعاملات...",
    name: "مبلغ المورد",
    allTransactions: "جدول المعاملات",
    saleDate: "تاريخ البيع",
    revenueSource: "مصدر الإيرادات",
    itemDescription: "الخدمة / المنتج",
    customer: "العميل",
    vendorAmount: "مبلغ المورد",
    totalSales: "إجمالي المبيعات",
    transactionCount: "عدد المعاملات",
    averageSale: "متوسط المعاملة",
    searchPlaceholder: "البحث حسب التاريخ أو المصدر أو العميل...",
    page: "الصفحة",
    of: "من",
    prev: "السابق",
    next: "التالي",
    rowsPerPage: "الصفوف",
    total: "المجموع",
    noMatching: "لم يتم العثور على معاملات مطابقة."
  }
});

const CustomTooltip = ({ active, payload, label }) => {
  if (active && payload && payload.length) {
    return (
      <div className="custom-tooltip">
        <p>
          <strong>{label}</strong>: {Number(payload[0].value).toFixed(2)} €
        </p>
      </div>
    );
  }
  return null;
};

const isAtlassianCustomer = (cust) => {
  if (!cust) return false;
  return String(cust).toLowerCase().includes('atlassian');
};

const getSourceBadgeClass = (source) => {
  const s = String(source || '').toLowerCase();
  if (s.includes('paypal')) return 'badge-source-paypal';
  if (s.includes('atlassian')) return 'badge-source-atlassian';
  if (s.includes('tekijänoikeus') || s.includes('copyright')) return 'badge-source-copyright';
  if (s.includes('mainostulo') || s.includes('ads') || s.includes('youtube')) return 'badge-source-ads';
  if (s.includes('hourly') || s.includes('confidential') || s.includes('tunti')) return 'badge-source-hourly';
  if (s.includes('kela') || s.includes('varma')) return 'badge-source-kela';
  if (s.includes('perint')) return 'badge-source-capital';
  return 'badge-source-default';
};

const TransactionsTableAll = ({ revenueSource }) => {
  const [transactionsMerged, setTransactionsMerged] = useState([]);
  const [chartDataMerged, setChartDataMerged] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [searchTerm, setSearchTerm] = useState("");
  const [currentPage, setCurrentPage] = useState(1);
  const [pageSize, setPageSize] = useState(15);

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
    setCurrentPage(1);
  }, [revenueSource]);

  const fetchMergedTransactions = async () => {
    try {
      setLoading(true);
      setError(null);
      const response = await axios.get(
        `${API_BASE_URL}/api/reports/merged-sales?source=${revenueSource}`
      );
      const data = response.data.root || [];

      const formattedChartData = data.map((item) => ({
        saleDate: item.saleDate,
        vendorAmount: parseFloat(item.vendorAmount) || 0,
        balanceVendor: parseFloat(item.balanceVendor) || 0,
      }));

      setTransactionsMerged(data);
      setChartDataMerged(formattedChartData);
    } catch (err) {
      setError(strings.error);
    } finally {
      setLoading(false);
    }
  };

  const groupedByDay = useMemo(() => {
    return Object.values(
      chartDataMerged.reduce((acc, row) => {
        const key = row.saleDate?.slice(0, 10) || "";
        const amount = typeof row.vendorAmount === "number"
          ? row.vendorAmount
          : parseFloat(String(row.vendorAmount).replace(/[^\d.-]/g, "")) || 0;

        if (!acc[key]) acc[key] = { saleDate: key, vendorAmount: 0 };
        acc[key].vendorAmount += amount;
        return acc;
      }, {})
    ).sort((a, b) => new Date(a.saleDate) - new Date(b.saleDate));
  }, [chartDataMerged]);

  // Filtered and sorted transactions - Hides customer names other than Atlassian
  const filteredRows = useMemo(() => {
    const term = searchTerm.trim().toLowerCase();
    return transactionsMerged
      .slice()
      .sort((a, b) => new Date(b.saleDate) - new Date(a.saleDate)) // most recent first
      .filter((row) => {
        if (!term) return true;
        const d = String(row.saleDate || '').toLowerCase();
        const s = String(row.revenueSource || row.source || '').toLowerCase();
        const item = String(row.itemName || row.item_name || '').toLowerCase();
        const cust = isAtlassianCustomer(row.customer) ? String(row.customer).toLowerCase() : '';
        const amount = String(row.vendorAmount || '');
        return d.includes(term) || s.includes(term) || item.includes(term) || cust.includes(term) || amount.includes(term);
      });
  }, [transactionsMerged, searchTerm]);

  // Statistics calculations
  const totalAmount = useMemo(() => {
    return filteredRows.reduce((sum, r) => {
      const val = typeof r.vendorAmount === 'number' ? r.vendorAmount : parseFloat(String(r.vendorAmount).replace(/[^\d.-]/g, "")) || 0;
      return sum + val;
    }, 0);
  }, [filteredRows]);

  const avgAmount = useMemo(() => {
    return filteredRows.length > 0 ? (totalAmount / filteredRows.length) : 0;
  }, [filteredRows, totalAmount]);

  // Pagination
  const totalPages = Math.max(1, Math.ceil(filteredRows.length / pageSize));
  const paginatedRows = useMemo(() => {
    const start = (currentPage - 1) * pageSize;
    return filteredRows.slice(start, start + pageSize);
  }, [filteredRows, currentPage, pageSize]);

  if (loading) return <div className="loading-screen"><img src={LOADING} alt={strings.loading} /></div>;
  if (error) return <p style={{ color: "red" }}>{error}</p>;

  return (
    <div className="transactions-all-container" style={{ marginTop: 40, marginBottom: 50 }}>
      <h2 className="calculator-title">{strings.title}</h2>

      {/* KPI Stats Summary Cards */}
      <div className="stats-kpi-grid">
        <div className="stats-kpi-card highlight-blue">
          <div className="kpi-label">{strings.totalSales}</div>
          <div className="kpi-value highlight-val">
            {totalAmount.toLocaleString(lang === 'fi' ? 'fi-FI' : 'en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} €
          </div>
        </div>
        <div className="stats-kpi-card highlight-purple">
          <div className="kpi-label">{strings.transactionCount}</div>
          <div className="kpi-value">
            {filteredRows.length} kpl
          </div>
        </div>
        <div className="stats-kpi-card highlight-teal">
          <div className="kpi-label">{strings.averageSale}</div>
          <div className="kpi-value">
            {avgAmount.toFixed(2)} €
          </div>
        </div>
      </div>

      {/* Bar Chart by Day */}
      <div className="chart-wrapper-card" style={{ marginBottom: 30 }}>
        <ResponsiveContainer width="100%" height={380}>
          <BarChart
            data={groupedByDay}
            margin={{ top: 16, right: 24, left: 16, bottom: 60 }}
            barCategoryGap="20%"
          >
            <CartesianGrid strokeDasharray="3 3" stroke="rgba(255,255,255,0.08)" />
            <XAxis
              dataKey="saleDate"
              angle={-45}
              textAnchor="end"
              interval="preserveStartEnd"
              minTickGap={20}
              tickMargin={10}
              stroke="#94a3b8"
              tick={{ fontSize: 11 }}
              height={50}
            />
            <YAxis stroke="#94a3b8" tickFormatter={(v) => `${v} €`} />
            <Tooltip
              formatter={(v) => [`${Number(v).toFixed(2)} €`, strings.name]}
              content={<CustomTooltip />}
            />
            <Bar dataKey="vendorAmount" fill="#3b82f6" name={strings.name} radius={[4, 4, 0, 0]} />
            <Brush dataKey="saleDate" height={24} travellerWidth={8} stroke="#60a5fa" fill="rgba(30, 41, 59, 0.8)" />
          </BarChart>
        </ResponsiveContainer>
      </div>

      {/* HTML Table with Search & Pagination */}
      <div className="transactions-table-card">
        <div className="table-header-toolbar">
          <h3 className="section-sub-title" style={{ margin: 0 }}>{strings.allTransactions}</h3>
          
          <div className="table-controls-right">
            <input
              type="text"
              className="table-search-input"
              placeholder={strings.searchPlaceholder}
              value={searchTerm}
              onChange={(e) => {
                setSearchTerm(e.target.value);
                setCurrentPage(1);
              }}
            />
            <select
              className="page-size-select"
              value={pageSize}
              onChange={(e) => {
                setPageSize(Number(e.target.value));
                setCurrentPage(1);
              }}
              title={strings.rowsPerPage}
            >
              <option value={15}>15 {strings.rowsPerPage}</option>
              <option value={30}>30 {strings.rowsPerPage}</option>
              <option value={50}>50 {strings.rowsPerPage}</option>
              <option value={100}>100 {strings.rowsPerPage}</option>
            </select>
          </div>
        </div>

        <Table striped bordered hover responsive variant="dark" className="modern-stats-table">
          <thead>
            <tr>
              <th className="text-start" style={{ width: '130px' }}>{strings.saleDate}</th>
              <th className="text-start" style={{ width: '190px' }}>{strings.revenueSource}</th>
              <th className="text-start">{strings.itemDescription}</th>
              <th className="text-end" style={{ width: '140px' }}>{strings.vendorAmount}</th>
            </tr>
          </thead>
          <tbody>
            {paginatedRows.length === 0 ? (
              <tr>
                <td colSpan={4} className="text-center" style={{ padding: '28px', color: '#94a3b8' }}>
                  {strings.noMatching}
                </td>
              </tr>
            ) : (
              paginatedRows.map((row, index) => {
                const saleDate = row.saleDate?.slice(0, 10) || row.saleDate || "";
                const sourceLabel = row.source || row.revenueSource || revenueSource || "PayPal";
                const isAtlassian = isAtlassianCustomer(row.customer);
                const displayedCustomer = isAtlassian ? row.customer : null;
                const item = row.itemName || row.item_name || (displayedCustomer ? displayedCustomer : "Lasku");
                const amount = typeof row.vendorAmount === "number"
                  ? row.vendorAmount
                  : parseFloat(String(row.vendorAmount).replace(/[^\d.-]/g, "")) || 0;

                const badgeClass = getSourceBadgeClass(sourceLabel);

                return (
                  <tr key={`${saleDate}-${index}`}>
                    <td className="text-start font-mono">{saleDate}</td>
                    <td className="text-start">
                      <span className={`source-badge-pill ${badgeClass}`}>
                        {sourceLabel}
                      </span>
                    </td>
                    <td className="text-start">
                      <span className="table-item-name">{item}</span>
                      {displayedCustomer && row.itemName && (
                        <small className="table-item-customer">
                          {displayedCustomer}
                        </small>
                      )}
                    </td>
                    <td className="text-end font-mono">
                      <span className="table-amount-cell">{amount.toFixed(2)} €</span>
                    </td>
                  </tr>
                );
              })
            )}
          </tbody>
          <tfoot>
            <tr className="table-total-row">
              <td colSpan={3} className="text-start table-total-label">
                <strong>{strings.total} ({filteredRows.length} kpl)</strong>
              </td>
              <td className="text-end font-mono table-total-amount">
                <strong>{totalAmount.toFixed(2)} €</strong>
              </td>
            </tr>
          </tfoot>
        </Table>

        {/* Pagination Bar */}
        {totalPages > 1 && (
          <div className="pagination-bar">
            <button
              type="button"
              className="pagination-btn pagination-prev"
              disabled={currentPage <= 1}
              onClick={() => setCurrentPage((p) => Math.max(1, p - 1))}
              aria-label={strings.prev}
            >
              ← {strings.prev}
            </button>
            <div className="pagination-info">
              {strings.page} <strong className="pagination-current-page">{currentPage}</strong> {strings.of} <strong>{totalPages}</strong>
            </div>
            <button
              type="button"
              className="pagination-btn pagination-next"
              disabled={currentPage >= totalPages}
              onClick={() => setCurrentPage((p) => Math.min(totalPages, p + 1))}
              aria-label={strings.next}
            >
              {strings.next} →
            </button>
          </div>
        )}
      </div>
    </div>
  );
};

export default TransactionsTableAll;
