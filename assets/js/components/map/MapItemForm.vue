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
      <p><button v-if="itemExists && this.permissions[0].edit" type="button" class="btn btn-info pull-right" @click="toggleEdit"><span v-html="lockIcon"></span></button></p>
      <!-- TABS -->
      <ul class="nav nav-tabs" id="myTab" role="tablist">
        <li class="nav-item">
          <a class="nav-link active" id="home-tab" data-toggle="tab" href="#information" role="tab" aria-controls="information" aria-selected="true">Information</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" id="profile-tab" data-toggle="tab" href="#photos" role="tab" aria-controls="photos" aria-selected="false">Photos</a>
        </li>
      </ul>
      <div class="tab-content" id="mapitemTabContent">
        <div class="tab-pane fade show active" id="information" role="tabpanel" aria-labelledby="information-tab">
          <form>
            <fieldset>
              <legend>Basic Information</legend>
              <div class="form-group">
                <label>Name</label>
                <input type="text" class="form-control" :class="{'is-invalid': errors.name, 'form-control-plaintext': !userCanEdit || !isEditMode}" :readonly="!userCanEdit || !isEditMode" v-model="record.name">
                <div class="invalid-feedback">
                  {{ errors.name }}
                </div>
              </div>
              <div class="form-group">
                <label>Slug</label>
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
            <!-- ACTION BUTTONS -->
            <div v-if="userCanEdit && isEditMode">
              <button class="btn btn-success spacer-top" type="button" @click="submitForm">{{ itemExists ? 'Update ' + record.itemType : 'Create ' + record.itemType }}</button>
              <button v-if="itemExists && this.permissions[0].delete" type="button" class="btn btn-danger spacer-top" data-toggle="modal" data-target="#deleteModal">Delete {{ record.itemType }}</button>
            </div>
          </form><!-- /end form -->
        </div><!-- end .tab-pane #information -->
        <div class="tab-pane fade show active" id="photos" role="tabpanel" aria-labelledby="photos-tab">
          <div class="row">
            <div class="col-xs-12 col-md-6">
              <h3>{{ record.itemType | capitalize }} Images ({{ record.images.length }})</h3>
              <!-- Image order change buttons -->
              <template v-if="isImageOrderChanged">
                <button class="btn btn-success" @click="updateImageOrder()">Confirm</button>
                <button class="btn btn-default" @click="resetImageOrder()">Reset</button>
              </template>
              <div v-if="itemExists && userCanEdit && isEditMode">
                <draggable v-model="record.images" :options="{}" @start="drag=true" @end="onDragEnd">
                  <li class="list-group-item" :class="{'image-deleted-border': index === deletedImageIndex}" v-for="(image, index) in record.images">
                    <div class="row">
                        <div class="col-sm-9">
                          <h6 class="box-title">{{image.name}} <span><i class="fa fa-pencil" aria-hidden="true"></i></span></h6>
                        </div><!-- /.col-md-12 -->
                        <div class="col-sm-3">
                          <button type="button" class="btn btn-sm btn-danger pull-right" @click="openDeleteImageModal(image)"><i class="fa fa-times" aria-hidden="true"></i></button>
                        </div><!-- /.col-md-12 -->
                    </div><!-- /.row -->
                    <div class="row">
                      <div class="col-sm-12">
                        <img :src="uploadsThumbnailURL + image.path" :alt="image.name" class="rounded" />
                      </div>
                    </div>
                  </li><!-- /li -->
                </draggable>
              </div>
            </div><!-- end .col-md-6 (images)-->
            <div class="col-xs-12 col-md-6">
              <h5>Primary Photo</h5>
              <template v-if="record.images.length > 0">
                <img id="primary-image" :src="record.images[0].subdir + '/' + record.images[0].path" :alt="record.images[0].name" />
                <p><a :href="record.images[0].subdir + '/' + record.images[0].path" target="_blank">Full Size</a></p>
              </template>
              <template v-else>
                <p>No photos have been uploaded yet.</p>
              </template>
            </div><!-- end .col-md-6 (images)-->
          </div><!-- end .row (images) -->
          <!--IMAGE UPLOAD-->
          <div class="row">
            <div class="col-xs-12 col-md-12">
              <template v-if="itemExists && userCanEdit && isEditMode">
                <!--SUCCESS-->
                <div v-if="isUploadSuccess">
                  <div v-if="uploadErrors.length == 0" class="alert alert-success" role="alert">
                    <p><strong>Uploaded {{ uploadedFiles.length }} file(s).</strong> <a class="btn btn-default" href="javascript:void(0)" @click="resetUploadForm()">Upload more images</a></p>
                  </div>
                  <div v-else class="alert alert-warning" role="alert">
                    <p><strong>Uploaded {{ uploadedFiles.length }} file(s) with the following errors.</strong> <a class="btn btn-default" href="javascript:void(0)" @click="resetUploadForm()">Upload more images</a></p>
                    <ul>
                      <li v-for="uploadError in uploadErrors">{{ uploadError }}</li>
                    </ul>
                  </div>
                </div>
                <!--FAILED-->
                <div v-if="isUploadFailed" class="alert alert-danger" role="alert">
                  <p><strong>Upload failed.</strong> <a href="javascript:void(0)" @click="resetUploadForm()">Try again</a></p>
                  <pre v-for="uploadError in uploadErrors">{{ uploadErrors }}</pre>
                </div>
                <!-- UPLOAD FORM -->
                <form enctype="multipart/form-data" novalidate v-if="isUploadInitial || isUploadSaving">
                  <fieldset>
                    <legend>Upload Images</legend>
                    <div class="dropbox">
                      <input type="file" multiple name="uploadFiles[]" :disabled="isUploadSaving" @change="filesChange($event.target.name, $event.target.files); fileCount = $event.target.files.length" accept="image/*" class="input-file">
                        <p v-if="isUploadInitial">
                          Drag your image(s) here to begin<br> or click to browse.
                        </p>
                        <p v-if="isUploadSaving">
                          Uploading {{ fileCount }} files...
                        </p>
                    </div>
                  </fieldset>
                </form>
              </template>
              <template v-else>
                <div class="alert alert-info" role="alert">
                  <p v-if="!itemExists">You will be able to upload images once you create the {{ record.itemType }}</p>
                  <p v-if="!userCanEdit">You do not have sufficient privileges to upload images</p>
                  <p v-if="!isEditMode">Photo upload only available in edit mode</p>
                </div>
              </template>
            </div><!-- end .col-xs-12 (uploads) -->
          </div><!-- end .row (uploads) -->
        </div><!-- end .tab-pane #photos -->
      </div><!-- end .tab-content -->
    </div>
    <!-- DELETE ITEM MODAL -->
    <mapitem-delete-modal
    :mapitem="record"
    @itemDeleted="markItemDeleted"
    @itemDeleteError="markItemDeleteError"
    ></mapitem-delete-modal>
    <!-- DELETE IMAGE MODAL -->
    <mapitem-image-delete-modal
    id="deleteImageModal"
    :image="deleteImageModalData"
    @imageDeleteRequested="deleteImage(deleteImageModalData)"
    ></mapitem-image-delete-modal>
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

  #primary-image{
    max-width:100%;
  }

  .list-group-item, .list-group-item:hover{
    z-index: auto;
  }

  .image-deleted-border{
    border: 3px solid red;
  }
</style>
<style src="vue-multiselect/dist/vue-multiselect.min.css"></style>
<script>
  import MapitemDeleteModal from './MapitemDeleteModal.vue'
  import MapitemImageDeleteModal from './MapitemImageDeleteModal.vue'
  import ImageThumbnailPod from '../utils/ImageThumbnailPod.vue'
  import Heading from '../utils/Heading.vue'
  import Multiselect from 'vue-multiselect'
  import NotFound from '../utils/NotFound.vue'
  import Draggable from 'vuedraggable'

  const STATUS_INITIAL = 0, STATUS_SAVING = 1, STATUS_SUCCESS = 2, STATUS_FAILED = 3;

  export default {
    created() {},
    mounted() {
      this.resetUploadForm();
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
    components: {Heading, Multiselect, MapitemDeleteModal, MapitemImageDeleteModal, ImageThumbnailPod, NotFound, Draggable},
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
        currentStatus: null,
        deletedImageIndex: -1,
        deleteImageModalData: null,
        errors:{},
        is404: false,
        isDataLoaded: false,
        isDeleted: false,
        isDeleteError: false,
        isEditMode: false, // true = make forms editable
        isImageDeleted: false,
        isImageNameEdit: false,
        isImageOrderChanged: false,
        markers: [], // google map markers
        originalImageOrder: [], // when order of images is being re-arranged, put the initial images, in order, here
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
        tempLatitudeSatellite: null,
        tempLongitudeSatellite: null,
        uploadedFiles: [],
        uploadErrors: [],
        uploadsThumbnailURL: '/media/cache/resolve/squared_thumbnail/uploads/map/',
        uploadsUpdateURL: '/api/mapitem/image/rename',
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
      imageDeleted: function(){
          return this.isImageDeleted ? 'image-deleted-border' : ''
      },
      isInvalid: function(){
        return 'is-invalid'
      },

      // PHOTOS
      isUploadInitial() {
        return this.currentStatus === STATUS_INITIAL;
      },
      isUploadSaving() {
        return this.currentStatus === STATUS_SAVING;
      },
      isUploadSuccess() {
        return this.currentStatus === STATUS_SUCCESS;
      },
      isUploadFailed() {
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
        this.tempLatitudeSatellite = null
        this.tempLongitudeSatellite = null
      },
      deleteImage: function(image){
        let self = this

        axios.delete('/api/mapitemimages/' + image.id)
          .then(function(response){
              // mark the deleted image in a colored border for 1.5 seconds, then remove it from the record
              self.deletedImageIndex = self.record.images.indexOf(image)
              setTimeout(function(){
                  self.deletedImageIndex = -1 // reset the index
                  self.record.images.splice(self.record.images.indexOf(image), 1) // splice the deleted item from the record
                  self.setOriginalImages(self.record.images)
              }, 1500)
          })
          .catch(function(error){
            console.log(error)
          })
      },

      fetchMapItem(itemId){
        let self = this
        axios.get('/api/mapitems/' + itemId)
        // success
        .then(function (response) {
          self.record = response.data
          self.isDataLoaded = true
          self.setOriginalImages(self.record.images)
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
      // When a user has finished dragging (re-ordering) an image
      onDragEnd: function(evt){
        this.isImageOrderChanged = true
      },
      openDeleteImageModal(image){
        this.deleteImageModalData = image
        $('#deleteImageModal').modal('show')
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
      // JSON.stringify the original image order in case a user wants to reset the drag and drop order
      setOriginalImages: function(imageArr){
        this.originalImageOrder = JSON.parse(JSON.stringify(imageArr))
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

        let method = (this.itemExists) ? 'put' : 'post'
        let route =  (this.itemExists) ? '/api/mapitem' : '/api/mapitems'

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
            self.afterSubmitSucceeds()
          })
          // fail
          .catch(function (error) {
            self.clearErrors() // clear any previous errors
            console.log(error)
            let errors = error.response.data
            // Add any validation errors to the errors object
            errors.forEach(function(error){
              let key = error.property_path
              let message = error.message
              self.errors[key] = message
            })
          })
      },
      resetUploadForm: function() {
        // reset form to initial state
        this.currentStatus = STATUS_INITIAL
        this.uploadedFiles = [];
        this.uploadErrors = [];
      },
      resetImageOrder: function(){
        this.record.images = this.originalImageOrder
        this.isImageOrderChanged = false
      },
      updateImageOrder: function(){
        let self = this
        // Get current images in order
        let imageIdsObj = {
          imageIds: []
        }
        this.record.images.forEach(function(image){
          imageIdsObj.imageIds.push(image.id)
        })

        // AJAX (axios) submission
        axios.put('/api/mapitemimage/reorder', imageIdsObj)
          // success
          .then(function (response) {
            self.isImageOrderChanged = false
            //self.resetImageOrder()
          })
          // fail
          .catch(function (error) {
            console.log(error)
          })
      },
      uploadImages: function(formData){
        let self = this

        this.currentStatus = STATUS_SAVING

        // append map item ID to form data
        formData.append('mapitem_id', this.record.id)

        // AJAX (axios) submission
        axios.post('/api/mapitemimages/uploads', formData, {
            headers: {
              'Content-Type': 'multipart/form-data'
            }
        })
          // success
          .then(function (response) {
            // add the new images to the record's array of images
            response.data.processedImages.forEach(function(image){
              self.record.images.push(image)
            })
            self.setOriginalImages(self.record.images) // set original image order to reflect new image
            self.uploadedFiles = [].concat(response.data.processedImages)
            self.uploadErrors = response.data.errors
            self.currentStatus = STATUS_SUCCESS
          })
          // fail
          .catch(function (error) {
            self.currentStatus = STATUS_FAILED
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
