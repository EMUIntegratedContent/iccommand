<template>
  <div v-if="isDataLoaded === false">
    <img src="/images/loading.gif" alt="Loading..." />
  </div>
  <div v-else>
    <heading>
      <span slot="icon" v-html="headingIcon">{{ headingIcon }}</span>
      <span slot="title">Map {{ record.itemType | capitalize }}</span>
    </heading>
    <div class="row">
      <div class="col-12">
        <form>
          <fieldset>
            <legend>Basic Information</legend>
            <div class="form-group">
              <label>Name</label>
              <input type="text" class="form-control" :class="{'is-invalid': errors.name}" v-model="record.name">
              <div class="invalid-feedback">
                {{ errors.name }}
              </div>
            </div>
            <div class="form-group">
              <label>Slug</label>
              <input type="text" class="form-control" :class="{'is-invalid': errors.slug}" v-model="record.slug">
              <div class="invalid-feedback">
                {{ errors.slug }}
              </div>
            </div>
            <div class="form-group">
              <label>Description</label>
              <textarea class="form-control" :class="{'is-invalid': errors.description}" v-model="record.description"></textarea>
              <div class="invalid-feedback">
                {{ errors.description }}
              </div>
            </div>
          </fieldset>
          <!-- GOOGLE MAP -->
          <fieldset>
            <legend>Set {{ record.itemType }} coordinates</legend>
            <p>Click on the map at the desired location. Use the "Set Location" button to set a marker.</p>
            <div class="row">
              <div class="col-md-8">
                <gmap-map
                  :center="center"
                  :zoom="16"
                  style="width: 100%; height: 300px"
                  @click="setTempPosition"
                >
                  <gmap-marker
                    :key="index"
                    v-for="(m, index) in markers"
                    :position="m.position"
                    :clickable="true"
                    :draggable="true"
                    @click="center=m.position"
                  ></gmap-marker>
                </gmap-map>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label>Satellite Coordinates</label>
                  <input readonly class="form-control-plaintext" :value="record.latitudeSatellite + ', ' + record.longitudeSatellite">
                </div>
                <div class="form-group">
                  <label>Illustration Coordinates</label>
                  <input readonly class="form-control-plaintext" :value="record.latitudeIllustration + ', ' + record.longitudeIllustration">
                </div>
                <template v-if="tempLongitudeSatellite != null && tempLatitudeSatellite != null">
                  <button class="btn btn-success" type="button" @click="setLocation(tempLatitudeSatellite, tempLongitudeSatellite)">Set location</button>
                  <button class="btn btn-default" type="button" @click="clearTempLocation">Clear</button>
                </template>
              </div>
            </div>
          </fieldset>
          <!--<fieldset>
            <legend>Tags</legend>
            <multiselect
              v-model="record.tags"
              :options="tags"
              :multiple="true"
              placeholder="Choose tags"
              label="tag"
              track-by="id"
              >
            </multiselect>
          </fieldset>-->

          <!-- BUILDING FIELDS -->
          <template v-if="record.itemType == 'building'">
            BUILIDING
          </template>
          <!-- BATHROOM FIELDS -->
          <template v-if="record.itemType == 'bathroom'">
            <fieldset>
              <legend>{{ record.itemType | capitalize }} specific fields</legend>
              <div class="form-group">
                <label class="checkbox-inline">
                  <input type="checkbox" :class="{'is-invalid': errors.isGenderNeutral}" v-model="record.isGenderNeutral"> Gender Neutral?
                </label>
              </div>
            </fieldset>
          </template>
          <button class="btn btn-success spacer-top" type="button" @click="submitForm">{{ itemExists ? 'Update ' + record.itemType : 'Create ' + record.itemType }}</button>
        </form>
      </div>
    </div>
  </div>
</template>
<style>

</style>
<style src="vue-multiselect/dist/vue-multiselect.min.css"></style>
<script>
  import Vue from 'vue'
  import Heading from '../utils/HeadingComponent.vue'
  import Multiselect from 'vue-multiselect'
  import * as VueGoogleMaps from 'vue2-google-maps'

  Vue.use(VueGoogleMaps, {
    load: {
      key: 'AIzaSyC5B3IcIel6XCAq4bwyZpxo6bl1pdUQpN8',
      libraries: 'places', // This is required if you use the Autocomplete plugin
      // OR: libraries: 'places,drawing'
      // OR: libraries: 'places,drawing,visualization'
      // (as you require)
    }
  })

  export default {
    mounted() {
      if(this.itemExists === false){
        // Set the kind of map item being created via the itemType property from NewMapItemChoices
        this.isDataLoaded = true
        this.record.itemType = this.itemType
      } else {
        // fetch the existing record using the prop itemId
        this.fetchMapItem(this.itemId)
      }
    },
    components: {Heading, Multiselect},
    props:{
      itemType: {
        type: String,
        required: true // not required of existing items because type is already known
      },
      itemExists: {
        type: Boolean,
        required: true
      },
      itemId:{
        type: String,
        required: false
      }
    },
    data: function() {
      return {
        // the list of potential tags for this map item
        tags:[
          {
              id: 4,
              tag: 'bob',
          },
          {
              id: 5,
              tag: 'tom',
          },
          {
              id: 6,
              tag: 'rob',
          },
        ],
        center: {lat: 42.24782481187385, lng: -83.62301669499783}, // center coordinates for google map
        markers: [], // google map markers
        tempLatitudeSatellite: null,
        tempLongitudeSatellite: null,
        errors:{},
        isDataLoaded: false,
        record: {
          id: '',
          description: '',
          isGenderNeutral: false,
          itemType: '',
          latitudeSatellite: null,
          longitudeSatellite: null,
          latitudeIllustration: null,
          longitudeIllustration: null,
          name: '',
          slug: '',
          tags:[],
        },
      }
    },
    computed: {
      headingIcon: function() {
        switch(this.record.itemType){
          case 'building':
            return '<i class="fa fa-building fa-3x"></i>'
          case 'bathroom':
            return '<i class="fa fa-male fa-3x"></i><i class="fa fa-female fa-3x"></i>'
          default:
            return false
        }
      },
      isInvalid: function(){
        return 'is-invalid'
      },
    },
    methods: {
      afterSubmitSucceeds: function(){
        this.clearErrors() // clear any previous validation errors
        // New item has been submitted, go to edit
        if(!this.itemExists){
          let newurl = '/map/items/' + this.record.id + '/edit'
          document.location = newurl;
        }
      },
      clearErrors: function(){
        this.errors = {}
      },
      clearMarkers: function(){
        this.markers = []
      },
      clearTempLocation: function(){
        this.tempLatitudeSatellite = null;
        this.tempLongitudeSatellite = null;
      },
      fetchMapItem(itemId){
        let self = this
        axios.get('/api/mapitems/' + itemId)
        // success
        .then(function (response) {
          self.record = response.data
          self.isDataLoaded = true;
        })
        // fail
        .catch(function (error) {
          console.log("NAH FAM...")
        })
      },
      fetchTags: function(){
        console.log("fetching tags")
      },
      setLocation: function(lat, lng){
        this.clearMarkers() // get rid of existing markers
        this.record.latitudeSatellite = lat
        this.record.longitudeSatellite = lng
        // make a new marker for these coordinates
        this.markers.push({
          position: {
            lat: lat,
            lng: lng,
          }
        })
        this.clearTempLocation()
      },
      setTempPosition: function(e){
        this.tempLatitudeSatellite = e.latLng.lat()
        this.tempLongitudeSatellite = e.latLng.lng()
      },
      // Submit the form via the API
      submitForm: function(){
        let self = this // 'this' loses scope within axios

        let method = (this.itemExists) ? 'put' : 'post'
        let route =  (this.itemExists) ? '/api/mapitem' : '/api/mapitems';

        // AJAX (axios) submission
        axios({
          method: method,
          url: route,
          data: self.record
        })
          // success
          .then(function (response) {
            self.record.id = response.data.id // set the item's id
            self.afterSubmitSucceeds()
          })
          // fail
          .catch(function (error) {
            console.log("ERRORS!")
            self.errors = {}; // clear any previous errors
            let errors = error.response.data
            // Add any validation errors to the errors object
            errors.forEach(function(error){
              let key = error.propertyPath
              let message = error.messageTemplate
              self.errors[key] = message
            })
          })
      }
    },
    filters: {
      capitalize: function (value) {
        if (!value) return ''
        value = value.toString()
        return value.charAt(0).toUpperCase() + value.slice(1)
      }
    }
  }
</script>
