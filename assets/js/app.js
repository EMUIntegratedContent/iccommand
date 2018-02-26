require('../css/app.scss')
require('./bootstrap')

$(document).ready(function() {
    $('[data-toggle="popover"]').popover()
});

/** VUE SETUP **/
import Vue from 'vue'

/** DECLARE COMPONENTS HERE **/
Vue.component('new-map-item', require('./components/map/NewMapItem.vue').default)
Vue.component('map-item-form', require('./components/map/MapItemForm.vue').default)

/** VUE APP **/
const app = new Vue({
    el: '#app',
});
