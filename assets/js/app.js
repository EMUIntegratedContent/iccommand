import "../css/app.scss";
require("./bootstrap");
require('es6-promise').polyfill();

// Init function.
$(document).ready(function() {
    $("[data-toggle='popover']").popover();

    // Set active main navigation class.
    let urlPath = window.location.pathname;
    $("#iccommand-mainnav-container li.nav-item").each(function(index) {
      // Match a path where the urlPath at least starts with this nav item"s path.
      let urlPathRegExp = new RegExp("^" + $("a", this).attr("href") + "+");

      if (urlPathRegExp.test(urlPath)) {
        $(this).addClass("active");
      } else {
        $(this).removeClass("active");
      }
    })
});

/* ******************************** IE Polyfills **************************** */
if (!String.prototype.startsWith) {
    String.prototype.startsWith = function(searchString, position) {
        position = position || 0;
        return this.indexOf(searchString, position) === position;
    };
}

if (!String.prototype.includes) {
    String.prototype.includes = function(search, start) {
        'use strict';
        if (typeof start !== 'number') {
            start = 0;
        }

        if (start + search.length > this.length) {
            return false;
        } else {
            return this.indexOf(search, start) !== -1;
        }
    };
}

/* ******************************** Vue Setup ******************************* */
import Vue from "vue";
import VeeValidate from "vee-validate";
import PrettyCheckbox from "pretty-checkbox-vue";
import VueCkeditor from "vue-ckeditor2";

Vue.use(VeeValidate);
Vue.use(PrettyCheckbox);
Vue.use(VueCkeditor);

/* ************************* Declare Components Here ************************ */

/* Home */
Vue.component("home-page", require("./components/Homepage.vue").default);

/* Admin */
Vue.component("admin-user-index", require("./components/admin/UserIndex.vue").default);
Vue.component("admin-user-manage", require("./components/admin/UserManage.vue").default);
Vue.component("app-manage", require("./components/admin/AppManage.vue").default);
Vue.component("user-profile", require("./components/Profile.vue").default);

/* Campus Map Application */
Vue.component("map-index", require("./components/map/MapIndex.vue").default);
Vue.component("map-item-form", require("./components/map/MapItemForm.vue").default);
Vue.component("new-map-item", require("./components/map/NewMapItem.vue").default);

/* Multimedia Requests */
Vue.component("multimedia-assignee-form", require("./components/multimediarequest/AssigneeForm.vue").default);
Vue.component("multimedia-assignee-index", require("./components/multimediarequest/AssigneeIndex.vue").default);
Vue.component("multimedia-headshot-calendar", require("./components/multimediarequest/HeadshotCalendar.vue").default);
Vue.component("multimedia-request-form", require("./components/multimediarequest/MultimediaRequestForm.vue").default);
Vue.component("multimedia-request-index", require("./components/multimediarequest/MultimediaRequestIndex.vue").default);

/* Redirects Application */
Vue.component("redirect-index", require("./components/redirect/RedirectIndex.vue").default);
Vue.component("new-redirect-item", require("./components/redirect/NewRedirectItem.vue").default);
Vue.component("redirect-item-form", require("./components/redirect/RedirectItemForm.vue").default);
Vue.component("redirect-list", require("./components/redirect/RedirectList.vue").default);

/* Oucampus Signup List */
Vue.component("ousignup-list", require("./components/ousignup/SignupList.vue").default);

/* Department Directory */
Vue.component("department-directory", require("./components/departmentdirectory/DepartmentDirectory.vue").default);

/* ********************************* Vue App ******************************** */
const app = new Vue({
    el: "#app",
});
