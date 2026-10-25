// Registry of module nav configs, keyed by MODULES[].key (resources/js/modules.js).
import customers from './customers';
import finance from './finance';
import inventory from './inventory';
import purchasing from './purchasing';
import reports from './reports';
import sales from './sales';
import suppliers from './suppliers';
import systemAdmin from './system-admin';
import vehicleReference from './vehicle-reference';
import workshop from './workshop';

export const NAVS = {
    inventory,
    sales,
    purchasing,
    workshop,
    customers,
    suppliers,
    finance,
    'vehicle-reference': vehicleReference,
    reports,
    'system-admin': systemAdmin,
};

export function navFor(moduleKey) {
    return NAVS[moduleKey] ?? null;
}
