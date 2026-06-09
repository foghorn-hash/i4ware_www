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
      title: 'Timesheet for Jira ROI-laskuri',
      hourlyRate: 'Oman työntekijän palkka (Tuntihinta EUR)',
      vatRate: 'ALV-kanta (%)',
      projectHours: 'Omaan kehitykseen käytetyt tunnit / kk',
      employeeCount: 'Työntekijämäärä (Tiimin koko)',
      usdToEurRate: 'USD -> EUR kurssi',
      calculate: 'Laske',
      notice: 'Laskuri laskee asiakkaan kokonaiskustannukset (oma työvoima + oma SaaS-tuote) ja vähentää niistä Timesheet for Jiran hinnan.',
      resultsTitle: 'Tulokset:',
      employeesIncluded: 'Työntekijöitä mukana: ',
      // Section 1: Employee cost
      employeeCostTitle: 'Asiakkaan työvoimakustannukset',
      subtotalNoVat: 'Oman työvoiman hinta (ilman ALV): ',
      vatAmount: 'ALV-osuus: ',
      totalWithVat: 'Oman työvoiman hinta (sis. ALV): ',
      // Section 2: Customer SaaS product
      saasPricingTitle: 'Asiakkaan oma SaaS-tuotteen hinta',
      pricingModel: 'Hinnoittelumalli',
      perUserModel: 'Käyttäjäkohtainen hinta',
      flatModel: 'Kiinteä hinta',
      numberOfUsers: 'Käyttäjämäärä',
      pricePerUser: 'Hinta per käyttäjä (USD / kk)',
      flatPrice: 'Kiinteä hinta (USD / kk)',
      customerSaasCostText: 'Asiakkaan SaaS-tuotteen hinta',
      customerSaasCostEur: 'Asiakkaan SaaS-hinta euroina',
      // Section 3: Timesheet for Jira cost
      timesheetPricingTitle: 'Timesheet for Jira hinnoittelu',
      timesheetTeamSize: 'Tiimin koko',
      timesheetMonthlyPrice: 'Kuukausihinta',
      timesheetCostText: 'Timesheet for Jira -hinta',
      timesheetCostEur: 'Timesheet for Jira euroina',
      tierUpTo10: 'Enintään 10 (kiinteä hinta)',
      perMonthFlat: '/ kk (kiinteä)',
      perUserPerMonth: '/ käyttäjä / kk',
      // Billing
      billingCycle: 'Laskutusjakso',
      monthly: 'Kuukausittain',
      annual: 'Vuosittain (10x kuukausihinta)',
      // Results
      totalCustomerCost: 'Asiakkaan kokonaiskustannukset (Työvoima + SaaS): ',
      timesheetCostResult: 'Timesheet for Jiran hinta: ',
      finalSavings: 'Nettosäästö (Kokonaiskust. - Timesheet for Jira): ',
      pricingModelUsed: 'Hinnoittelumalli: ',
      perUserLabel: 'Käyttäjäkohtainen',
      flatLabel: 'Kiinteä hinta',
      monthlyCost: 'Kuukausihinta',
      annualCost: 'Vuosihinta'
    },
    en: {
      title: 'Timesheet for Jira ROI Calculator',
      hourlyRate: 'Employee Salary (Hourly rate EUR)',
      vatRate: 'VAT rate (%)',
      projectHours: 'Hours spent on custom dev / month',
      employeeCount: 'Number of employees (Team size)',
      usdToEurRate: 'USD -> EUR rate',
      calculate: 'Calculate',
      notice: 'This calculator computes the customer\'s total costs (workforce + own SaaS product) and subtracts the Timesheet for Jira price.',
      resultsTitle: 'Results:',
      employeesIncluded: 'Employees included: ',
      // Section 1: Employee cost
      employeeCostTitle: 'Customer Workforce Costs',
      subtotalNoVat: 'Workforce cost (excl. VAT): ',
      vatAmount: 'VAT amount: ',
      totalWithVat: 'Workforce cost (incl. VAT): ',
      // Section 2: Customer SaaS product
      saasPricingTitle: 'Customer\'s Own SaaS Product Price',
      pricingModel: 'Pricing Model',
      perUserModel: 'Per User Price',
      flatModel: 'Flat Price',
      numberOfUsers: 'Number of Users',
      pricePerUser: 'Price per User (USD / month)',
      flatPrice: 'Flat Price (USD / month)',
      customerSaasCostText: 'Customer SaaS product price',
      customerSaasCostEur: 'Customer SaaS price in EUR',
      // Section 3: Timesheet for Jira cost
      timesheetPricingTitle: 'Timesheet for Jira Pricing',
      timesheetTeamSize: 'Team size',
      timesheetMonthlyPrice: 'Monthly price',
      timesheetCostText: 'Timesheet for Jira price',
      timesheetCostEur: 'Timesheet for Jira in EUR',
      tierUpTo10: 'Up to 10 (flat fee)',
      perMonthFlat: '/ month (flat)',
      perUserPerMonth: '/ user / month',
      // Billing
      billingCycle: 'Billing Cycle',
      monthly: 'Monthly',
      annual: 'Annually (10x monthly price)',
      // Results
      totalCustomerCost: 'Customer total costs (Workforce + SaaS): ',
      timesheetCostResult: 'Timesheet for Jira price: ',
      finalSavings: 'Net savings (Total Costs - Timesheet for Jira): ',
      pricingModelUsed: 'Pricing model: ',
      perUserLabel: 'Per user',
      flatLabel: 'Flat price',
      monthlyCost: 'Monthly cost',
      annualCost: 'Annual cost'
    }
  };

  function t(key) {
    if (translations[currentLang] && translations[currentLang][key]) {
      return translations[currentLang][key];
    }
    return translations.en[key] || key;
  }

  // Timesheet for Jira pricing tiers (from screenshot)
  var TIMESHEET_TIERS = [
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

  function findTimesheetTier(employees) {
    for (var i = 0; i < TIMESHEET_TIERS.length; i += 1) {
      var tier = TIMESHEET_TIERS[i];
      if (employees >= tier.min && employees <= tier.max) {
        return tier;
      }
    }
    return TIMESHEET_TIERS[TIMESHEET_TIERS.length - 1];
  }

  function formatEuro(value) {
    return new Intl.NumberFormat(currentLang === 'fi' ? 'fi-FI' : 'en-US', {
      style: 'currency',
      currency: 'EUR',
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    }).format(value);
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

  function SelectField(props) {
    return createElement('div', { className: 'i4ware-roi-field' }, [
      createElement('label', { key: props.id, htmlFor: props.id }, props.label),
      createElement('select', {
        key: props.id + '-select',
        id: props.id,
        value: props.value,
        onChange: function (event) {
          props.onChange(event.target.value);
        }
      }, props.options.map(function(opt) {
        return createElement('option', { key: opt.value, value: opt.value }, opt.label);
      }))
    ]);
  }

  function renderTimesheetTierRow(tier, key, billingCycle) {
    var teamSizeLabel = tier.labelKey ? t(tier.labelKey) : tier.label;
    var multiplier = billingCycle === 'annual' ? 10 : 1;
    var price = tier.perUser * multiplier;
    var priceText = '';
    if (tier.flatFee) {
      priceText = 'USD ' + price.toFixed(2) + ' ' + t('perMonthFlat');
    } else {
      priceText = 'USD ' + price.toFixed(2) + ' ' + t('perUserPerMonth');
    }
    return createElement('tr', { key: key }, [
      createElement('td', { key: key + '-1' }, teamSizeLabel),
      createElement('td', { key: key + '-2' }, priceText)
    ]);
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

    var _g = useState('monthly');
    var billingCycle = _g[0];
    var setBillingCycle = _g[1];

    // Customer SaaS pricing fields
    var _h = useState('perUser');
    var pricingModel = _h[0];
    var setPricingModel = _h[1];

    var _i = useState('10');
    var numberOfUsers = _i[0];
    var setNumberOfUsers = _i[1];

    var _j = useState('8.60');
    var pricePerUser = _j[0];
    var setPricePerUser = _j[1];

    var _k = useState('90');
    var flatPrice = _k[0];
    var setFlatPrice = _k[1];

    function calculate(event) {
      if (event) event.preventDefault();

      var rate = Number(hourlyRate) || 0;
      var vat = Number(vatRate) || 0;
      var hours = Number(projectHours) || 0;
      var employees = Math.max(Number(employeeCount) || 0, 0);
      var usdToEur = Number(usdToEurRate) || 0;

      // 1. Employee / workforce cost
      var subtotal = rate * hours * employees;
      var vatAmount = subtotal * (vat / 100);
      var totalEmployeeCost = subtotal + vatAmount;

      // 2. Customer's own SaaS product cost (USD)
      var customerSaasMonthlyUsd = 0;
      if (pricingModel === 'perUser') {
        var users = Number(numberOfUsers) || 0;
        var perUser = Number(pricePerUser) || 0;
        customerSaasMonthlyUsd = users * perUser;
      } else {
        customerSaasMonthlyUsd = Number(flatPrice) || 0;
      }
      var customerSaasAnnualUsd = customerSaasMonthlyUsd * 10;
      var customerSaasCostUsd = billingCycle === 'annual' ? customerSaasAnnualUsd : customerSaasMonthlyUsd;
      var customerSaasCostEur = customerSaasCostUsd * usdToEur;

      // Total customer cost = workforce + their SaaS
      var totalCustomerCost = totalEmployeeCost + customerSaasCostEur;

      // 3. Timesheet for Jira cost (based on team size from tier table)
      var tier = employees > 0 ? findTimesheetTier(employees) : null;
      var timesheetMonthlyUsd = 0;
      if (tier) {
        timesheetMonthlyUsd = tier.flatFee ? tier.perUser : (tier.perUser * employees);
      }
      var timesheetAnnualUsd = timesheetMonthlyUsd * 10;
      var timesheetCostUsd = billingCycle === 'annual' ? timesheetAnnualUsd : timesheetMonthlyUsd;
      var timesheetCostEur = timesheetCostUsd * usdToEur;

      // Net savings = (Employee cost + Customer SaaS) - Timesheet for Jira
      var netSavings = totalCustomerCost - timesheetCostEur;

      setResults({
        employees: employees,
        subtotal: subtotal,
        vatAmount: vatAmount,
        totalEmployeeCost: totalEmployeeCost,
        customerSaasMonthlyUsd: customerSaasMonthlyUsd,
        customerSaasAnnualUsd: customerSaasAnnualUsd,
        customerSaasCostUsd: customerSaasCostUsd,
        customerSaasCostEur: customerSaasCostEur,
        totalCustomerCost: totalCustomerCost,
        timesheetTierLabel: tier ? (tier.labelKey ? t(tier.labelKey) : tier.label) : '-',
        timesheetMonthlyUsd: timesheetMonthlyUsd,
        timesheetAnnualUsd: timesheetAnnualUsd,
        timesheetCostUsd: timesheetCostUsd,
        timesheetCostEur: timesheetCostEur,
        usdToEur: usdToEur,
        netSavings: netSavings,
        pricingModel: pricingModel
      });
    }

    // Build customer SaaS pricing fields
    var customerSaasFields = [
      createElement(SelectField, {
        key: 'pricingModel',
        id: 'i4ware-pricing-model',
        label: t('pricingModel'),
        value: pricingModel,
        onChange: setPricingModel,
        options: [
          { value: 'perUser', label: t('perUserModel') },
          { value: 'flat', label: t('flatModel') }
        ]
      })
    ];

    if (pricingModel === 'perUser') {
      customerSaasFields.push(
        createElement(NumberField, {
          key: 'numberOfUsers',
          id: 'i4ware-number-of-users',
          label: t('numberOfUsers'),
          min: '0',
          step: '1',
          value: numberOfUsers,
          onChange: setNumberOfUsers
        }),
        createElement(NumberField, {
          key: 'pricePerUser',
          id: 'i4ware-price-per-user',
          label: t('pricePerUser'),
          min: '0',
          step: '0.01',
          value: pricePerUser,
          onChange: setPricePerUser
        })
      );
    } else {
      customerSaasFields.push(
        createElement(NumberField, {
          key: 'flatPrice',
          id: 'i4ware-flat-price',
          label: t('flatPrice'),
          min: '0',
          step: '0.01',
          value: flatPrice,
          onChange: setFlatPrice
        })
      );
    }

    return createElement('div', { className: 'i4ware-roi-wrap' },
      createElement('form', { className: 'i4ware-roi-card', onSubmit: calculate }, [
        createElement('h2', { key: 'title' }, t('title')),

        // Billing cycle
        createElement(SelectField, {
          key: 'billingCycle',
          id: 'i4ware-billing-cycle',
          label: t('billingCycle'),
          value: billingCycle,
          onChange: setBillingCycle,
          options: [
            { value: 'monthly', label: t('monthly') },
            { value: 'annual', label: t('annual') }
          ]
        }),

        // Section 1: Employee costs
        createElement('div', { key: 'employee-box', className: 'i4ware-roi-pricing-box' }, [
          createElement('h3', { key: 'emp-title' }, t('employeeCostTitle')),
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
          })
        ]),

        // Section 2: Customer SaaS product pricing
        createElement('div', { key: 'customer-saas-box', className: 'i4ware-roi-pricing-box' }, [
          createElement('h3', { key: 'customer-saas-title' }, t('saasPricingTitle'))
        ].concat(customerSaasFields)),

        // Section 3: Timesheet for Jira pricing table
        createElement('div', { key: 'timesheet-box', className: 'i4ware-roi-pricing-box' }, [
          createElement('h3', { key: 'ts-pricing-title' }, t('timesheetPricingTitle')),
          createElement(NumberField, {
            key: 'usdToEurRate',
            id: 'i4ware-usd-eur-rate',
            label: t('usdToEurRate'),
            min: '0',
            step: '0.0001',
            value: usdToEurRate,
            onChange: setUsdToEurRate
          }),
          createElement('table', { key: 'ts-pricing-table', className: 'i4ware-roi-pricing-table' }, [
            createElement('thead', { key: 'ts-thead' },
              createElement('tr', { key: 'ts-head-row' }, [
                createElement('th', { key: 'ts-h1' }, t('timesheetTeamSize')),
                createElement('th', { key: 'ts-h2' }, t('timesheetMonthlyPrice'))
              ])
            ),
            createElement('tbody', { key: 'ts-tbody' }, TIMESHEET_TIERS.map(function (tier, index) {
              return renderTimesheetTierRow(tier, 'ts-tier-' + index, billingCycle);
            }))
          ])
        ]),

        createElement('button', { key: 'submit', type: 'submit', style: { marginTop: '20px' } }, t('calculate')),
        createElement('p', { key: 'sales-note', className: 'i4ware-roi-note' }, t('notice')),

        // Results
        results && createElement('div', { key: 'results', className: 'i4ware-roi-results' }, [
          createElement('h3', { key: 'results-title' }, t('resultsTitle')),

          // Employee costs
          createElement('p', { key: 'r1' }, t('employeesIncluded') + results.employees),
          createElement('p', { key: 'r2' }, t('subtotalNoVat') + formatEuro(results.subtotal)),
          createElement('p', { key: 'r3' }, t('vatAmount') + formatEuro(results.vatAmount)),
          createElement('p', { key: 'r4' }, t('totalWithVat') + formatEuro(results.totalEmployeeCost)),

          createElement('hr', { key: 'hr1' }),

          // Customer SaaS cost
          createElement('p', { key: 'r_model' }, t('pricingModelUsed') + (results.pricingModel === 'perUser' ? t('perUserLabel') : t('flatLabel'))),
          createElement('p', { key: 'r5_m' }, t('customerSaasCostText') + ' (' + t('monthlyCost') + '): USD ' + results.customerSaasMonthlyUsd.toFixed(2)),
          createElement('p', { key: 'r5_a' }, t('customerSaasCostText') + ' (' + t('annualCost') + '): USD ' + results.customerSaasAnnualUsd.toFixed(2)),
          createElement('p', { key: 'r5_eur' }, t('customerSaasCostEur') + ': ' + formatEuro(results.customerSaasCostEur)),

          createElement('hr', { key: 'hr2' }),

          // Total customer cost
          createElement('p', { key: 'r_total', style: { fontWeight: 'bold' } }, t('totalCustomerCost') + formatEuro(results.totalCustomerCost)),

          createElement('hr', { key: 'hr3' }),

          // Timesheet for Jira cost
          createElement('p', { key: 'r6_tier' }, t('timesheetCostText') + ' (' + results.timesheetTierLabel + ')'),
          createElement('p', { key: 'r6_m' }, t('timesheetCostText') + ' (' + t('monthlyCost') + '): USD ' + results.timesheetMonthlyUsd.toFixed(2)),
          createElement('p', { key: 'r6_a' }, t('timesheetCostText') + ' (' + t('annualCost') + '): USD ' + results.timesheetAnnualUsd.toFixed(2)),
          createElement('p', { key: 'r6_eur' }, t('timesheetCostEur') + ': ' + formatEuro(results.timesheetCostEur)),

          createElement('hr', { key: 'hr4' }),

          // Net savings
          createElement('p', { key: 'r7', style: { fontWeight: 'bold', fontSize: '1.3em' } }, t('finalSavings') + formatEuro(results.netSavings))
        ])
      ])
    );
  }

  var rootNode = document.getElementById('i4ware-timesheet-roi-root');
  if (!rootNode) {
    return;
  }

  if (el.render) {
    el.render(createElement(Calculator), rootNode);
  } else if (window.ReactDOM && window.ReactDOM.createRoot) {
    window.ReactDOM.createRoot(rootNode).render(createElement(Calculator));
  }
})();
