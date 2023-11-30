export default function (method, route, data = {}) {
    const url = `${window.fluent_forms_global_var.rest.url}/${route}`;

    const headers = { "X-WP-Nonce": window.fluent_forms_global_var.rest.nonce };

    if (["PUT", "PATCH", "DELETE"].indexOf(method.toUpperCase()) !== -1) {
        headers["X-HTTP-Method-Override"] = method;
        method = "POST";
    }

    data.query_timestamp = Date.now();

    return new Promise((resolve, reject) => {
        window.jQuery
            .ajax({
                url: url,
                type: method,
                data: data,
                headers: headers,
            })
            .then(response => resolve(response))
            .fail(errors => {
                if (errors.responseJSON && errors.responseJSON.code == 'rest_cookie_invalid_nonce') {
                    // Renew nonce from the server and retry the original request.
                    window.FluentFormsGlobal.$get({
                        action: "fluentform_renew_rest_nonce",
                    }).then(response => {
                        if (response.nonce) {
                            window.fluent_forms_global_var.rest.nonce = response.nonce;
                            method = method.toLowerCase();

                            window.FluentFormsGlobal.$rest[method](route, data).then(response => {
                                resolve(response)
                            })
                            .fail(errors => reject(errors.responseJSON));
                        }
                    });
                } else {
                    reject(errors.responseJSON)
                }
            });
    });
}
