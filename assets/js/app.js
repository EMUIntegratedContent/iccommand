require("../css/app.scss")
require("./bootstrap")
require('es6-promise').polyfill()

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
import { createApp } from "vue"
import CKEditor from '@ckeditor/ckeditor5-vue';
// import PrettyCheckbox from "pretty-checkbox-vue"

/* ********************************* Vue App ******************************** */
const app = createApp({

})
/* Home */
app.component("home-page", require("./components/Homepage.vue").default);

/* Admin */
app.component("admin-user-index", require("./components/admin/UserIndex.vue").default);
app.component("admin-user-manage", require("./components/admin/UserManage.vue").default);
app.component("app-manage", require("./components/admin/AppManage.vue").default);
app.component("user-profile", require("./components/Profile.vue").default);

/* Campus Map Application */
app.component("map-index", require("./components/map/MapIndex.vue").default);
app.component("map-item-form", require("./components/map/MapItemForm.vue").default);
app.component("new-map-item", require("./components/map/NewMapItem.vue").default);

/* Multimedia Requests */
app.component("multimedia-assignee-form", require("./components/multimediarequest/AssigneeForm.vue").default);
app.component("multimedia-assignee-index", require("./components/multimediarequest/AssigneeIndex.vue").default);
app.component("multimedia-headshot-calendar", require("./components/multimediarequest/HeadshotCalendar.vue").default);
app.component("multimedia-request-form", require("./components/multimediarequest/MultimediaRequestForm.vue").default);
app.component("multimedia-request-index", require("./components/multimediarequest/MultimediaRequestIndex.vue").default);
app.component("multimediarequest-delete-modal", require("./components/multimediarequest/MultimediaRequestDeleteModal.vue").default);

/* Redirects Application */
app.component("redirect-index", require("./components/redirect/RedirectIndex.vue").default);
app.component("new-redirect-item", require("./components/redirect/NewRedirectItem.vue").default);
app.component("redirect-item-form", require("./components/redirect/RedirectItemForm.vue").default);
app.component("redirect-list", require("./components/redirect/RedirectList.vue").default);
app.use(CKEditor)
app.mount('#app')
// app.use(PrettyCheckbox);



