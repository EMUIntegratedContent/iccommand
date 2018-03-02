<template>
  <div>
    <not-found v-if="is404 === true"></not-found>
    <div v-if="isDataLoaded === false">
      <img src="/images/loading.gif" alt="Loading..." />
    </div>
    <div v-if="isDeleted === true" class="alert alert-info alert-dismissible fade show" role="alert">
      {{ record.itemType | capitalize }} "{{ record.name }}" item has been deleted.
      <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
      </button>
    </div>
    <!-- MAIN AREA -->
    <div v-if="isDataLoaded === true && isDeleted === false && is404 === false">
      <heading>
        <span slot="icon" v-html="headingIcon">{{ headingIcon }}</span>
        <span v-if="!itemExists" slot="title">Step 2/2: Provide {{ record.itemType }} information</span>
        <span v-else slot="title">Map {{ record.itemType }}: {{ record.name }}</span>
      </heading>
      <div class="row">
        <div class="col-md-12">
          <form>
            <fieldset>
              <legend>Basic Information <button v-if="itemExists && this.permissions[0].edit" type="button" class="btn btn-info pull-right" @click="toggleEdit"><span v-html="lockIcon"></span></button></legend>
              <div class="form-group">
                <label>Name</label>
                <input type="text" class="form-control" :class="{'is-invalid': errors.name, 'form-control-plaintext': !userCanEdit || !isEditMode}" :readonly="!userCanEdit || !isEditMode" v-model="record.name">
                <div class="invalid-feedback">
                  {{ errors.name }}
                </div>
              </div>
              <div class="form-group">
                <label>Slug</label>
                <!--<input type="text" class="form-control" :class="{'is-invalid': errors.slug, 'form-control-plaintext': !userCanEdit || !isEditMode}" readonly v-model="record.slug">-->
                <input type="text" class="form-control form-control-plaintext" :class="{'is-invalid': errors.slug}" readonly v-model="slugify">
                <div class="invalid-feedback">
                  {{ errors.slug }}
                </div>
              </div>
              <div class="form-group">
                <label>Description</label>
                <textarea class="form-control" :class="{'is-invalid': errors.description, 'form-control-plaintext': !userCanEdit || !isEditMode}" :readonly="!userCanEdit || !isEditMode" v-model="record.description"></textarea>
                <div class="invalid-feedback">
                  {{ errors.description }}
                </div>
              </div>
            </fieldset>
            <!-- GOOGLE MAP (edit mode only!)-->
            <fieldset v-if="userCanEdit && isEditMode">
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
                <div v-if="userCanEdit && isEditMode" class="form-group">
                  <label class="checkbox-inline">
                    <input type="checkbox" :class="{'is-invalid': errors.isGenderNeutral}" v-model="record.isGenderNeutral"> Gender Neutral?
                  </label>
                </div>
                <div v-else>
                  <p>Bathroom <strong>is <span v-if="!record.isGenderNeutral">not</span></strong> designated as gender neutral</p>
                </div>
              </fieldset>
            </template>
            <div v-if="Object.keys(errors).length" class="alert alert-danger alert-dismissible fade show" role="alert">
              Please fix the <strong>{{ Object.keys(errors).length }} error(s)</strong> before submitting.
              <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div v-if="success" class="alert alert-success alert-dismissible fade show" role="alert">
              {{ successMessage }}
              <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div v-if="isDeleteError === true" class="alert alert-danger alert-dismissible fade show" role="alert">
              There was an error deleting this item.
              <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
          </form><!-- /end form -->
          <!--IMAGE UPLOAD-->
          <template v-if="itemExists">
            <form enctype="multipart/form-data" novalidate v-if="isInitial || isSaving">
              <fieldset>
                <legend>Upload Images</legend>
                <div class="dropbox">
                  <input type="file" multiple name="uploadFiles[]" :disabled="isSaving" @change="filesChange($event.target.name, $event.target.files); fileCount = $event.target.files.length" accept="image/*" class="input-file">
                    <p v-if="isInitial">
                      Drag your file(s) here to begin<br> or click to browse
                    </p>
                    <p v-if="isSaving">
                      Uploading {{ fileCount }} files...
                    </p>
                  <input type="hidden" name="record_id" :value="record.id" />
                </div>
              </fieldset>
            </form>
            <h3>{{ record.itemType }} Images</h3>
            <img v-for="image in uploadedFiles" :src="image.subdir + '/' + image.path" :alt="image.name" />
          </template>
          <template v-else>
            <div class="alert alert-info" role="alert">
              You will be able to upload images once you create the {{ record.itemType }}
            </div>
          </template>
          <!-- ACTION BUTTONS -->
          <div v-if="userCanEdit && isEditMode">
            <button class="btn btn-success spacer-top" type="button" @click="submitForm">{{ itemExists ? 'Update ' + record.itemType : 'Create ' + record.itemType }}</button>
            <button v-if="itemExists && this.permissions[0].delete" type="button" class="btn btn-danger spacer-top" data-toggle="modal" data-target="#deleteModal">Delete {{ record.itemType }}</button>
          </div>
        </div><!-- end .col-md-12 -->
      </div><!-- end .row -->
    </div>
    <!-- DELETE MODAL -->
    <mapitem-delete-modal
    :mapitem="record"
    @itemDeleted="markItemDeleted"
    @itemDeleteError="markItemDeleteError"
    ></mapitem-delete-modal>
  </div>
</template>
<style>
.dropbox {
    outline: 2px dashed grey; /* the dash box */
    outline-offset: -10px;
    background: lightcyan;
    color: dimgray;
    padding: 10px 10px;
    min-height: 200px; /* minimum height */
    position: relative;
    cursor: pointer;
  }

  .input-file {
    opacity: 0; /* invisible but it's there! */
    width: 100%;
    height: 200px;
    position: absolute;
    cursor: pointer;
  }

  .dropbox:hover {
    background: lightblue; /* when mouse over to the drop zone, change color */
  }

  .dropbox p {
    font-size: 1.2em;
    text-align: center;
    padding: 50px 0;
  }
</style>
<style src="vue-multiselect/dist/vue-multiselect.min.css"></style>
<script>
  import Vue from 'vue'
  import MapitemDeleteModal from './MapitemDeleteModal.vue'
  import Heading from '../utils/Heading.vue'
  import Multiselect from 'vue-multiselect'
  import NotFound from '../utils/NotFound.vue'
  import * as VueGoogleMaps from 'vue2-google-maps'

  const STATUS_INITIAL = 0, STATUS_SAVING = 1, STATUS_SUCCESS = 2, STATUS_FAILED = 3;

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
      this.reset();
      // detect if the form should be in edit mode from the start (default is false)
      if(this.startMode == 'edit'){
        this.isEditMode = true
      }

      if(this.itemExists === false){
        // Set the kind of map item being created via the itemType property from NewMapItemChoices
        this.isDataLoaded = true
        this.record.itemType = this.itemType
      } else {
        // fetch the existing record using the prop itemId
        this.fetchMapItem(this.itemId)
      }
    },
    components: {Heading, Multiselect, MapitemDeleteModal, NotFound},
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
      },
      permissions: {
        type: Array,
        required: true
      },
      startMode: {
        type: String,
        required: false
      }
    },
    data: function() {
      return {
        uploadedFiles: [],
        uploadError: null,
        currentStatus: null,

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
        is404: false,
        isDataLoaded: false,
        isDeleted: false,
        isDeleteError: false,
        isEditMode: false, // true = make forms editable
        record: {
          id: '',
          description: '',
          isGenderNeutral: false,
          images: [],
          itemType: '',
          latitudeSatellite: null,
          longitudeSatellite: null,
          latitudeIllustration: null,
          longitudeIllustration: null,
          name: '',
          slug: '',
          tags:[],
        },
        recordSlug: '',
        success: false,
        successMessage: '',
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

      // PHOTOS
      isInitial() {
        return this.currentStatus === STATUS_INITIAL;
      },
      isSaving() {
        return this.currentStatus === STATUS_SAVING;
      },
      isSuccess() {
        return this.currentStatus === STATUS_SUCCESS;
      },
      isFailed() {
        return this.currentStatus === STATUS_FAILED;
      },
      // -end PHOTOS

      lockIcon: function(){
        return this.isEditMode ? '<i class="fa fa-unlock"></i>' : '<i class="fa fa-lock"></i>'
      },
      slugify: function(){
        if(this.record.name){
          return this.record.name.toString().toLowerCase()
                                .replace(/\s+/g, '-')           // Replace spaces with -
                                .replace(/[^\w\-]+/g, '')       // Remove all non-word chars
                                .replace(/\-\-+/g, '-')         // Replace multiple - with single -
                                .replace(/^-+/, '')             // Trim - from start of text
                                .replace(/-+$/, ''); // Trim - from end of text
        }
      },
      userCanEdit: function(){
        // An existing record can be edited by a user with edit permissions, a new record can be created by a user with create permissions
        return this.itemExists && this.permissions[0].edit || !this.itemExists && this.permissions[0].create ? true : false
      }
    },
    methods: {
      afterSubmitSucceeds: function(){
        this.clearErrors() // clear any previous validation errors
        // New item has been submitted, go to edit
        if(!this.itemExists){
          this.success = true
          this.successMessage = "Item created."

          let newurl = '/map/items/' + this.record.id
          document.location = newurl
        } else {
          this.success = true
          this.successMessage = "Update successful."
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
          if(error.request.status == 404){
            self.is404 = true
            self.isDataLoaded = true
          }
        })
      },
      fetchTags: function(){
        console.log("fetching tags")
      },
      filesChange(fieldName, fileList) {
        // handle file changes
        const formData = new FormData()
        console.log(fieldName + " tony")
        if (!fileList.length) return

        // append the files to FormData
        Array
          .from(Array(fileList.length).keys())
          .map(x => {
            formData.append(fieldName, fileList[x], fileList[x].name)
          });

        // save it
        this.uploadImages(formData);
      },
      // Called from the @itemDeleted event emission from the Delete Modal
      markItemDeleted: function () {
          this.isDeleteError = false
          this.isDeleted = true
      },
      markItemDeleteError: function(){
          this.isDeleted = false
          this.isDeleteError = true
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
      setSlug: function(){
        this.record.slug = this.slugify
      },
      setTempPosition: function(e){
        this.tempLatitudeSatellite = e.latLng.lat()
        this.tempLongitudeSatellite = e.latLng.lng()
      },
      // Submit the form via the API
      submitForm: function(){
        let self = this // 'this' loses scope within axios

        this.currentStatus = STATUS_SAVING;

        let method = (this.itemExists) ? 'put' : 'post'
        let route =  (this.itemExists) ? '/api/mapitem' : '/api/mapitems';

        this.setSlug() // slug is only computed until this point, not set into record

        // AJAX (axios) submission
        axios({
          method: method,
          url: route,
          data: self.record
        })
          // success
          .then(function (response) {
            self.record.id = response.data.id // set the item's id

            //this.uploadedFiles = [].concat(response.data.images);
            this.currentStatus = STATUS_SUCCESS;

            self.afterSubmitSucceeds()
          })
          // fail
          .catch(function (error) {
            self.clearErrors() // clear any previous errors
            let errors = error.response.data
            // Add any validation errors to the errors object
            errors.forEach(function(error){
              let key = error.property_path
              let message = error.message
              self.errors[key] = message
            })

            this.uploadError = "ERROR!";
            this.currentStatus = STATUS_FAILED;
          })
      },
      reset: function() {
        // reset form to initial state
        this.currentStatus = STATUS_INITIAL;
        this.uploadedFiles = [];
        this.uploadError = null;
      },
      uploadImages: function(formData){
        let self = this

        // AJAX (axios) submission
        axios.post('/api/mapitemimages/uploads', formData, {
            headers: {
              'Content-Type': 'multipart/form-data'
            }
        })
          // success
          .then(function (response) {
            console.log("Images Uploaded")
            self.uploadedFiles = [].concat(response.data);
            self.currentStatus = STATUS_SUCCESS;
          })
          // fail
          .catch(function (error) {
            console.log("NO DICE")
            self.uploadError = "Images failed to upload.";
            self.currentStatus = STATUS_FAILED;
          })
      },
      toggleEdit: function(){
        this.isEditMode === true ? this.isEditMode = false : this.isEditMode = true
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
