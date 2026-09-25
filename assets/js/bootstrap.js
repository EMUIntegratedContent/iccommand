// File created by CP 2/23/18
// https://github.com/laravel/laravel/blame/master/resources/assets/js/bootstrap.js#L28-L33

window._ = require('lodash');
window.Popper = require('popper.js').default;

/**
 * We'll load jQuery and the Bootstrap jQuery plugin which provides support
 * for JavaScript based Bootstrap features such as modals and tabs. This
 * code may be modified to fit the specific needs of your application.
 */

try {
    window.$ = window.jQuery = require('jquery');

    require('bootstrap');

    // or you can include specific pieces
    // require('bootstrap-sass/javascripts/bootstrap/tooltip');
    // require('bootstrap-sass/javascripts/bootstrap/popover');
} catch (e) {}

/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

window.axios = require('axios');

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Next we will register the CSRF Token as a common header with Axios so that
 * all outgoing HTTP requests automatically have it attached. This is just
 * a simple convenience so we don't have to attach every token manually.
 */

let token = document.head.querySelector('meta[name="csrf-token"]');

if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
} else {
    console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token');
}

/**
 * When the session has expired, the API answers 401 instead of redirecting to the login page
 * (see src/Security/ApiAwareEntryPoint.php). Send the user to log in rather than letting the
 * form treat the response as a successful save. After login they come back to this page.
 */
window.axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response && error.response.status === 401 && !window.__icLoginRedirect) {
            window.__icLoginRedirect = true;
            window.location.href = '/login?expired=1';
        }
        return Promise.reject(error);
    }
);
