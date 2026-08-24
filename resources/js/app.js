

import Alpine from 'alpinejs';
import flatpickr from 'flatpickr';
import { Turkish } from 'flatpickr/dist/l10n/tr.js';

flatpickr.l10ns.tr = Turkish;

window.Alpine = Alpine;
window.flatpickr = flatpickr;

Alpine.start();

const wizardForms = [...document.querySelectorAll('form[data-wizard-form]')];
let wizardDirty = false;

wizardForms.forEach((form) => {
    const markDirty = () => { wizardDirty = true; };
    form.addEventListener('input', markDirty);
    form.addEventListener('change', markDirty);
    form.addEventListener('submit', () => { wizardDirty = false; });
});

window.addEventListener('beforeunload', (event) => {
    if (!wizardDirty) return;
    event.preventDefault();
    event.returnValue = '';
});
