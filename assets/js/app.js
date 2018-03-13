require('../css/app.scss')
require('./bootstrap')

$(document).ready(function() {
    $('[data-toggle="popover"]').popover()
});

/** VUE SETUP **/
import Vue from 'vue'
import * as VueGoogleMaps from 'vue2-google-maps'
import VeeValidate from 'vee-validate'

Vue.use(VeeValidate)

Vue.use(VueGoogleMaps, {
  load: {
    key: 'AIzaSyC5B3IcIel6XCAq4bwyZpxo6bl1pdUQpN8',
    libraries: 'places', // This is required if you use the Autocomplete plugin
    // OR: libraries: 'places,drawing'
    // OR: libraries: 'places,drawing,visualization'
    // (as you require)
  }
})

/** DECLARE COMPONENTS HERE **/
Vue.component('new-map-item', require('./components/map/NewMapItem.vue').default)
Vue.component('map-item-form', require('./components/map/MapItemForm.vue').default)

/** VUE APP **/
const app = new Vue({
    el: '#app',
});
