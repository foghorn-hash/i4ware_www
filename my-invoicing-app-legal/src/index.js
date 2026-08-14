import React from 'react';
import ReactDOM from 'react-dom/client';
import './index.css';
import App from './App';
import reportWebVitals from './reportWebVitals';

// Set public path for Webpack dynamically at runtime if provided by WordPress
if (window.legalReactAppConfig && window.legalReactAppConfig.publicPath) {
  // eslint-disable-next-line no-undef
  __webpack_public_path__ = window.legalReactAppConfig.publicPath;
}

// Get the root DOM element (use legal-root for WordPress, fallback to root for local dev)
const rootElement = document.getElementById("legal-root") || document.getElementById("root");

if (rootElement) {
  // Create a root and render your React app
  const root = ReactDOM.createRoot(rootElement);
  root.render(<App />);
}

// If you want to start measuring performance in your app, pass a function
// to log results (for example: reportWebVitals(console.log))
// or send to an analytics endpoint. Learn more: https://bit.ly/CRA-vitals
reportWebVitals();

