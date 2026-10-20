import './bootstrap';
import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react'
import { createRoot } from 'react-dom/client'
import { route as ziggyRoute } from 'ziggy-js';

// Make route function available globally
window.route = (name, params, absolute) => {
  return ziggyRoute(name, params, absolute, window.Ziggy);
};

// The window title carries the business name captured under System Admin › Company.
const brand = () => {
  try {
    const el = document.getElementById('app');
    const company = el ? JSON.parse(el.dataset.page).props.company : null;
    return company?.trading_name || company?.name || 'SparesPro';
  } catch { return 'SparesPro'; }
};

createInertiaApp({
  title: (title) => `${title} - ${brand()}`,
  resolve: name => {
    const pages = import.meta.glob('./Pages/**/*.jsx', { eager: true })
    return pages[`./Pages/${name}.jsx`]
  },
  setup({ el, App, props }) {
    createRoot(el).render(<App {...props} />)
  },
  progress: {
    color: '#3b82f6',
    delay: 250,
    includeCSS: true,
    showSpinner: true,
  },
})
