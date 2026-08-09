

import Alpine from 'alpinejs';
import flatpickr from 'flatpickr';
import { Turkish } from 'flatpickr/dist/l10n/tr.js';

flatpickr.l10ns.tr = Turkish;

window.Alpine = Alpine;
window.flatpickr = flatpickr;

Alpine.start();
