// jQuery
import $ from 'jquery';
window.$ = $;
// Bootstrap
import '@popperjs/core';
import 'bootstrap';
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap-slider';
import 'bootstrap-slider/dist/css/bootstrap-slider.min.css';
// Stimulus
import { startStimulusApp } from '@symfony/stimulus-bundle';
const app = startStimulusApp();
// DataTables
import DataTable from 'datatables.net-bs5';
import DataTablesLib from 'datatables.net-bs5';
import jszip from "jszip";
DataTable.use(DataTablesLib);
DataTablesLib.Buttons.jszip(jszip);
import 'datatables.net-bs5/css/dataTables.bootstrap5.min.css';
import 'datatables.net-buttons-bs5';
import 'datatables.net-buttons-bs5/css/buttons.bootstrap5.min.css'
import 'datatables.net-responsive-bs5';
import 'datatables.net-responsive-bs5/css/responsive.bootstrap5.min.css';
import 'datatables.net-select-bs5';
import 'datatables.net-select-bs5/css/select.bootstrap5.min.css';
import 'datatables.net-buttons/js/buttons.html5.js';
import 'datatables.net-buttons/js/buttons.print.js';
import 'datatables.net-rowreorder-bs5';
import 'datatables.net-rowreorder-bs5/css/rowReorder.bootstrap5.min.css';
import './datatables-accents.js';
// Font Awesome 6
import { fab } from '@fortawesome/free-brands-svg-icons';
import { far } from '@fortawesome/free-regular-svg-icons';
import { fas } from '@fortawesome/free-solid-svg-icons';
import { library } from '@fortawesome/fontawesome-svg-core';
import '@fortawesome/fontawesome-free';
library.add(fab, far, fas);
import '@fortawesome/fontawesome-free/css/fontawesome.min.css';
import '@fortawesome/fontawesome-svg-core/styles.min.css';
// Estilos propios
import './styles/app.scss';
