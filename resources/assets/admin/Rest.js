import Route from "./Route.js";

export default {
    get(route, data = {}) {
        return window.FluentFormsGlobal.request('GET', route, data);
    },
    post(route, data = {}) {
        return window.FluentFormsGlobal.request('POST', route, data);
    },
    delete(route, data = {}) {
        return window.FluentFormsGlobal.request('DELETE', route, data);
    },
    put(route, data = {}) {
        return window.FluentFormsGlobal.request('PUT', route, data);
    },
    patch(route, data = {}) {
        return window.FluentFormsGlobal.request('PATCH', route, data);
    },
    route(name, ...args) {
        return Route.get(name, ...args);
    }
};

jQuery(($) => {
    (() => {
        $.ajaxSetup({
            success: function(response, status, xhr) {
                const nonce = xhr.getResponseHeader('X-WP-Nonce');
                if (nonce) {
                    window.fluent_forms_global_var.rest.nonce = nonce;
                }
            }
        });
    })();
});

setInterval(() => {
    FluentFormsGlobal.$rest.get('forms/ping');
}, 60000);
