require('../css/app.scss')
require('./bootstrap')

// $.ajaxSetup({
//         headers: {
//             'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
//         }
// });

$(document).ready(function() {
    $('[data-toggle="popover"]').popover()
});

/** VUE SETUP **/
import Vue from 'vue'
import * as VueGoogleMaps from 'vue2-google-maps'
import VeeValidate from 'vee-validate'
import PrettyCheckbox from 'pretty-checkbox-vue'

Vue.use(VeeValidate)
Vue.use(PrettyCheckbox)
Vue.use(VueGoogleMaps, {
  load: {
    key: 'AIzaSyC5B3IcIel6XCAq4bwyZpxo6bl1pdUQpN8',
    //libraries: 'places', // This is required if you use the Autocomplete plugin
    // OR: libraries: 'places,drawing'
    libraries: 'places,drawing,visualization'
    // (as you require)
  }
})

/** DECLARE COMPONENTS HERE **/
Vue.component('home-page', require('./components/Homepage.vue').default)
Vue.component('admin-user-index', require('./components/admin/UserIndex.vue').default)
Vue.component('admin-user-manage', require('./components/admin/UserManage.vue').default)
Vue.component('map-index', require('./components/map/MapIndex.vue').default)
Vue.component('new-map-item', require('./components/map/NewMapItem.vue').default)
Vue.component('map-item-form', require('./components/map/MapItemForm.vue').default)

/** VUE APP **/
const app = new Vue({
    el: '#app',
});
