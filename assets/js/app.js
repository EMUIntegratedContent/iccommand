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

/* import the fontawesome core */
import { library } from '@fortawesome/fontawesome-svg-core'

/* import font awesome icon component */
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faPenToSquare } from '@fortawesome/free-solid-svg-icons'

library.add(faPenToSquare)
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

/* Redirects Application */
app.component("redirect-index", require("./components/redirect/RedirectIndex.vue").default);
app.component("new-redirect-item", require("./components/redirect/NewRedirectItem.vue").default);
app.component("redirect-item-form", require("./components/redirect/RedirectItemForm.vue").default);
app.component("redirect-list", require("./components/redirect/RedirectList.vue").default);

/* Directory Application */
app.component("department-form", require("./components/directory/DepartmentForm.vue").default);
app.component("department-list", require("./components/directory/DepartmentList.vue").default);
app.component("department-delete-modal", require("./components/directory/DepartmentDeleteModal.vue").default);

/* Catalog Programs */
app.component("programs-index", require("./components/programs/ProgramsIndex.vue").default);
app.component("programs-list", require("./components/programs/ProgramsList.vue").default);
app.component("program-form", require("./components/programs/ProgramForm.vue").default);
app.component("websites-list", require("./components/programs/WebsitesList.vue").default);
app.component("website-form", require("./components/programs/WebsiteForm.vue").default);
app.component("keywords-list", require("./components/programs/KeywordsList.vue").default);

/* CrimeLog */
app.component("crimelog-index", require("./components/crimelog/CrimeLogIndex.vue").default);

/* Photo Requests */
app.component("photorequests-list", require("./components/photorequest/PhotoRequestList.vue").default);
app.component("photorequests-show", require("./components/photorequest/PhotoRequestShow.vue").default);
app.component("photorequests-form", require("./components/photorequest/PhotoRequestForm.vue").default);

/* Emergency Banner and Notices */
app.component("emergency-banner", require("./components/emergency/EmergencyBanner.vue").default);


app.component('font-awesome-icon', FontAwesomeIcon)
app.use(CKEditor)
app.mount('#app')



