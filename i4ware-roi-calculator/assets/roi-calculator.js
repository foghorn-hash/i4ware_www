(function () {
  var el = window.wp && window.wp.element;
  if (!el) {
    return;
  }

  var createElement = el.createElement;
  var useState = el.useState;
  var appConfig = window.i4wareRoiCalculator || {};
  var currentLang = appConfig.lang === 'fi' ? 'fi' : 'en';

  var translations = {
    fi: {
      title: 'ROI / Tuntipohjainen Hintalaskuri',
      hourlyRate: 'Tuntihinta (EUR)',
      vatRate: 'ALV-kanta (%)',
      projectHours: 'Tunteja projektissa',
      employeeCount: 'Tyontekijamaara',
      usdToEurRate: 'USD -> EUR kurssi',
      pricingTitle: 'Avoimen lahdekoodin kuukausihinnat (i4ware Software -tiimi)',
      teamSize: 'Tiimin koko',
      monthlyPrice: 'Kuukausihinta',
      calculate: 'Laske',
      notice: 'Huomautus: i4waren asiakas maksaa koodista kuukausimaksua, ja eteenpain myytavat muokkaukset laskutetaan tuntihinnalla loppuasiakkaan tarpeisiin.',
      resultsTitle: 'Tulokset:',
      employeesIncluded: 'Tyontekijoita mukana: ',
      subtotalNoVat: 'Tyon hinta yhteensa (ilman ALV): ',
      vatAmount: 'ALV-osuus: ',
      totalWithVat: 'Hinta yhteensa (sis. ALV): ',
      openSourceMonthlyPrice: 'i4ware avoimen lahdekoodin kuukausihinta',
      deductionInEur: 'Vahennys euroissa (USD->EUR ',
      finalAfterDeduction: 'Kokonaishinta vahennyksen jalkeen: ',
      flatFeePricing: 'Hinnoittelu: kiintea kuukausimaksu.',
      perUserPricing: 'Hinnoittelu: kayttajakohtainen kuukausimaksu.',
      tierUpTo10: 'Enintaan 10 (kiintea hinta)',
      perMonthFlat: '/ kk (kiintea)',
      perUserPerMonth: '/ kayttaja / kk'
    },
    en: {
      title: 'ROI / Hourly Pricing Calculator',
      hourlyRate: 'Hourly rate (EUR)',
      vatRate: 'VAT rate (%)',
      projectHours: 'Project hours',
      employeeCount: 'Team size',
      usdToEurRate: 'USD -> EUR rate',
      pricingTitle: 'Open-source monthly pricing (i4ware Software team)',
      teamSize: 'Team size',
      monthlyPrice: 'Monthly price',
      calculate: 'Calculate',
      notice: 'Note: The i4ware customer pays a monthly fee for the code, and onward customizations are billed hourly for end-customer needs.',
      resultsTitle: 'Results:',
      employeesIncluded: 'Employees included: ',
      subtotalNoVat: 'Total work price (excl. VAT): ',
      vatAmount: 'VAT amount: ',
      totalWithVat: 'Total price (incl. VAT): ',
      openSourceMonthlyPrice: 'i4ware open-source monthly price',
      deductionInEur: 'Deduction in EUR (USD->EUR ',
      finalAfterDeduction: 'Total after deduction: ',
      flatFeePricing: 'Pricing: flat monthly fee.',
      perUserPricing: 'Pricing: per-user monthly fee.',
      tierUpTo10: 'Up to 10 (flat fee)',
      perMonthFlat: '/ month (flat)',
      perUserPerMonth: '/ user / month'
    }
  };

  function t(key) {
    if (translations[currentLang] && translations[currentLang][key]) {
      return translations[currentLang][key];
    }
    return translations.en[key] || key;
  }

  var PRICING_TIERS = [
    { min: 1, max: 10, perUser: 90.0, flatFee: true, labelKey: 'tierUpTo10' },
    { min: 11, max: 100, perUser: 8.6, label: '11-100' },
    { min: 101, max: 250, perUser: 7.3, label: '101-250' },
    { min: 251, max: 1000, perUser: 6.1, label: '251-1000' },
    { min: 1001, max: 2500, perUser: 5.5, label: '1001-2500' },
    { min: 2501, max: 5000, perUser: 5.15, label: '2501-5000' },
    { min: 5001, max: 7500, perUser: 4.85, label: '5001-7500' },
    { min: 7501, max: 10000, perUser: 4.55, label: '7501-10000' },
    { min: 10001, max: 15000, perUser: 3.95, label: '10001-15000' },
    { min: 15001, max: 20000, perUser: 3.6, label: '15001-20000' },
    { min: 20001, max: 25000, perUser: 3.4, label: '20001-25000' },
    { min: 25001, max: 30000, perUser: 3.2, label: '25001-30000' },
    { min: 30001, max: 35000, perUser: 2.5, label: '30001-35000' },
    { min: 35001, max: 40000, perUser: 2.45, label: '35001-40000' },
    { min: 40001, max: 45000, perUser: 2.4, label: '40001-45000' },
    { min: 45001, max: 50000, perUser: 2.35, label: '45001-50000' },
    { min: 50001, max: 60000, perUser: 2.35, label: '50001-60000' },
    { min: 60001, max: 70000, perUser: 2.35, label: '60001-70000' },
    { min: 70001, max: 80000, perUser: 2.35, label: '70001-80000' },
    { min: 80001, max: 90000, perUser: 2.35, label: '80001-90000' },
    { min: 90001, max: 100000, perUser: 2.35, label: '90001-100000' }
  ];

  function formatEuro(value) {
    return new Intl.NumberFormat(currentLang === 'fi' ? 'fi-FI' : 'en-US', {
      style: 'currency',
      currency: 'EUR',
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    }).format(value);
  }

  function findPricingTier(employees) {
    for (var i = 0; i < PRICING_TIERS.length; i += 1) {
      var tier = PRICING_TIERS[i];
      if (employees >= tier.min && employees <= tier.max) {
        return tier;
      }
    }
    return PRICING_TIERS[PRICING_TIERS.length - 1];
  }

  function NumberField(props) {
    return createElement('div', { className: 'i4ware-roi-field' }, [
      createElement('label', { key: props.id, htmlFor: props.id }, props.label),
      createElement('input', {
        key: props.id + '-input',
        id: props.id,
        type: 'number',
        min: props.min,
        step: props.step,
        value: props.value,
        onChange: function (event) {
          props.onChange(event.target.value);
        }
      })
    ]);
  }

  function renderTierRow(tier, key) {
    var teamSizeLabel = tier.labelKey ? t(tier.labelKey) : tier.label;
    var priceText = tier.flatFee
      ? 'USD ' + tier.perUser.toFixed(2) + ' ' + t('perMonthFlat')
      : 'USD ' + tier.perUser.toFixed(2) + ' ' + t('perUserPerMonth');
    return createElement('tr', { key: key }, [
      createElement('td', { key: key + '-1' }, teamSizeLabel),
      createElement('td', { key: key + '-2' }, priceText)
    ]);
  }

  function getTierLabel(tier) {
    return tier && tier.labelKey ? t(tier.labelKey) : tier ? tier.label : '-';
  }

  function Calculator() {
    var _a = useState('95');
    var hourlyRate = _a[0];
    var setHourlyRate = _a[1];

    var _b = useState('25.5');
    var vatRate = _b[0];
    var setVatRate = _b[1];

    var _c = useState('100');
    var projectHours = _c[0];
    var setProjectHours = _c[1];

    var _d = useState('1');
    var employeeCount = _d[0];
    var setEmployeeCount = _d[1];

    var _e = useState(null);
    var results = _e[0];
    var setResults = _e[1];

    var _f = useState('0.92');
    var usdToEurRate = _f[0];
    var setUsdToEurRate = _f[1];

    function calculate(event) {
      event.preventDefault();

      var rate = Number(hourlyRate) || 0;
      var vat = Number(vatRate) || 0;
      var hours = Number(projectHours) || 0;
      var employees = Math.max(Number(employeeCount) || 0, 0);

      var subtotal = rate * hours * employees;
      var vatAmount = subtotal * (vat / 100);
      var total = subtotal + vatAmount;
      var tier = employees > 0 ? findPricingTier(employees) : null;
      var monthlyOpenSourceCost = 0;
      var usdToEur = Number(usdToEurRate) || 0;

      if (tier) {
        monthlyOpenSourceCost = tier.flatFee ? tier.perUser : tier.perUser * employees;
      }

      var monthlyOpenSourceCostEur = monthlyOpenSourceCost * usdToEur;
      var discountedTotal = Math.max(total - monthlyOpenSourceCostEur, 0);

      setResults({
        subtotal: subtotal,
        vatAmount: vatAmount,
        total: total,
        employees: employees,
        tierLabel: getTierLabel(tier),
        tierPerUser: tier ? tier.perUser : 0,
        tierFlatFee: tier ? !!tier.flatFee : false,
        monthlyOpenSourceCostUsd: monthlyOpenSourceCost,
        monthlyOpenSourceCostEur: monthlyOpenSourceCostEur,
        usdToEur: usdToEur,
        discountedTotal: discountedTotal
      });
    }

    return createElement('div', { className: 'i4ware-roi-wrap' },
      createElement('form', { className: 'i4ware-roi-card', onSubmit: calculate }, [
        createElement('h2', { key: 'title' }, t('title')),
        createElement(NumberField, {
          key: 'hourlyRate',
          id: 'i4ware-hourly-rate',
          label: t('hourlyRate'),
          min: '0',
          step: '0.01',
          value: hourlyRate,
          onChange: setHourlyRate
        }),
        createElement(NumberField, {
          key: 'vatRate',
          id: 'i4ware-vat-rate',
          label: t('vatRate'),
          min: '0',
          step: '0.1',
          value: vatRate,
          onChange: setVatRate
        }),
        createElement(NumberField, {
          key: 'projectHours',
          id: 'i4ware-project-hours',
          label: t('projectHours'),
          min: '0',
          step: '1',
          value: projectHours,
          onChange: setProjectHours
        }),
        createElement(NumberField, {
          key: 'employeeCount',
          id: 'i4ware-employee-count',
          label: t('employeeCount'),
          min: '0',
          step: '1',
          value: employeeCount,
          onChange: setEmployeeCount
        }),
        createElement(NumberField, {
          key: 'usdToEurRate',
          id: 'i4ware-usd-eur-rate',
          label: t('usdToEurRate'),
          min: '0',
          step: '0.0001',
          value: usdToEurRate,
          onChange: setUsdToEurRate
        }),
        createElement('div', { key: 'pricing-box', className: 'i4ware-roi-pricing-box' }, [
          createElement('h3', { key: 'pricing-title' }, t('pricingTitle')),
          createElement('table', { key: 'pricing-table', className: 'i4ware-roi-pricing-table' }, [
            createElement('thead', { key: 'thead' },
              createElement('tr', { key: 'head-row' }, [
                createElement('th', { key: 'h1' }, t('teamSize')),
                createElement('th', { key: 'h2' }, t('monthlyPrice'))
              ])
            ),
            createElement('tbody', { key: 'tbody' }, PRICING_TIERS.map(function (tier, index) {
              return renderTierRow(tier, 'tier-' + index);
            }))
          ])
        ]),
        createElement('button', { key: 'submit', type: 'submit' }, t('calculate')),
        createElement('p', { key: 'sales-note', className: 'i4ware-roi-note' }, t('notice')),
        results && createElement('div', { key: 'results', className: 'i4ware-roi-results' }, [
          createElement('h3', { key: 'results-title' }, t('resultsTitle')),
          createElement('p', { key: 'r1' }, t('employeesIncluded') + results.employees),
          createElement('p', { key: 'r2' }, t('subtotalNoVat') + formatEuro(results.subtotal)),
          createElement('p', { key: 'r3' }, t('vatAmount') + formatEuro(results.vatAmount)),
          createElement('p', { key: 'r4' }, t('totalWithVat') + formatEuro(results.total)),
          createElement('p', { key: 'r5' }, t('openSourceMonthlyPrice') + ' (' + results.tierLabel + '): USD ' + results.monthlyOpenSourceCostUsd.toFixed(2)),
          createElement('p', { key: 'r6' }, t('deductionInEur') + results.usdToEur + '): ' + formatEuro(results.monthlyOpenSourceCostEur)),
          createElement('p', { key: 'r7' }, t('finalAfterDeduction') + formatEuro(results.discountedTotal)),
          createElement('p', { key: 'r8' }, results.tierFlatFee ? t('flatFeePricing') : t('perUserPricing'))
        ])
      ])
    );
  }

  var rootNode = document.getElementById('i4ware-roi-calculator-root');
  if (!rootNode) {
    return;
  }

  if (el.render) {
    el.render(createElement(Calculator), rootNode);
  } else if (window.ReactDOM && window.ReactDOM.createRoot) {
    window.ReactDOM.createRoot(rootNode).render(createElement(Calculator));
  }
})();
