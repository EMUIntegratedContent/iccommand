require('../css/app.scss')

var $ = require('jquery')
// JS is equivalent to the normal "bootstrap" package
// no need to set this to a variable, just require it. This is BOOTSTRAP 4!
require('bootstrap')

// or you can include specific pieces
// require('bootstrap-sass/javascripts/bootstrap/tooltip');
// require('bootstrap-sass/javascripts/bootstrap/popover');

$(document).ready(function() {
    $('[data-toggle="popover"]').popover();
});

/** VUE SETUP **/
import Vue from 'vue'

/** DECLARE COMPONENTS HERE **/
Vue.component('app', require('./components/ExampleComponent.vue').default);

/** VUE APP **/
const app = new Vue({
    el: '#app'
});
