/**
 * Theme `main.js` treats any stored `zynixlayout` value as "use horizontal nav",
 * which breaks vertical-only admin markup and sidebar/header branding CSS.
 * Clear it before `main.js` runs so `data-nav-layout` from the server stays vertical.
 */
(function () {
    'use strict';
    try {
        localStorage.removeItem('zynixlayout');
    } catch (e) {
        /* ignore (e.g. private mode) */
    }
})();
