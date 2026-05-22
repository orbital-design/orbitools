/**
 * Orbitools React admin — entry point.
 *
 * Mounts the app into the `<div id="orbitools-admin-root">` rendered
 * by Orbitools\Core\Admin\React_Admin::render_page().
 */
import { createRoot } from '@wordpress/element';
import { registerStore } from './store';
import { App } from './App';
import './scss/react-admin.scss';

registerStore();

const container = document.getElementById('orbitools-admin-root');
if (container) {
    const root = createRoot(container);
    root.render(<App />);
}
