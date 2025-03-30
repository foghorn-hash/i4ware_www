import React, { useState, useContext, useEffect } from "react";
import TransactionsTable from "./TransactionsTable";
import TransactionsTableAll from "./TransactionsTableAll";
import CumulativeChart from "./CumulativeChart";
import PieChartComponent from "./PieChartComponent";

const Charts = () => {
    return (
      <div>
        <TransactionsTable />
        <TransactionsTableAll />
        <CumulativeChart />
        <PieChartComponent />
      </div>
    );
};

export default Charts;
