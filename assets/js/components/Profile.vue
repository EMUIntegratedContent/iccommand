<template>
  <div>
    <not-found v-if="is404 === true"></not-found>
    <div v-if="isDataLoaded === false">
      <p style="text-align: center"><img src="/images/loading.gif" alt="Loading..." /></p>
    </div>
    <div v-if="apiError.status" class="alert alert-danger fade show" role="alert">
      {{ apiError.message }}
    </div>
    <!-- MAIN AREA -->
    <div v-if="isDataLoaded === true && isDeleted === false && is404 === false">
      <heading>
        <span slot="icon" v-html="headingIcon">{{ headingIcon }}</span>
        <span slot="title">Configure user {{ username }}</span>
      </heading>
      <div class="button-holder" role="group">
        <a href="/" class="btn btn-primary pull-left"><i class="fa fa-home"></i></a>
        <button type="button" class="btn btn-info pull-right" @click="toggleEdit"><span v-html="lockIcon"></span></button>
      </div>
      <div class="row">
        <div class="col-sm-12 col-md-4 col-lg-3">
          <div class="profile-image-container">
            <button v-if="user.image" type="button" id="delete-image-button" class="btn btn-sm btn-danger" @click="deleteProfileImage"><i class="fa fa-times" aria-hidden="true"></i></button>
            <img width="205px" height="305px" :src="user.image ? imageResizedURL + user.image.path : '/images/no-profile-image.png'" :alt="username + '\'s profile image'">
          </div>
          <form enctype="multipart/form-data" novalidate v-if="isEditMode">
            <label>
              <input type="file" name="uploadProfileImage" id="profilefile" class="form-control-file" accept="image/*" :disabled="isUploadSaving" @change="filesChange($event.target.name, $event.target.files);">
              <span class="custom-file-control"></span>
            </label>
          </form>
        </div>
        <div class="col-sm-12 col-md-8 col-lg-9">
          <form class="form" id="permissionsForm" @submit.prevent="checkForm">
            <fieldset>
              <legend>Basic Information</legend>
              <div class="form-group row">
                <div class="col-sm-6">
                  <label>First Name</label>
                  <input
                    data-vv-as="first name"
                    name="firstName"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': errors.has('firstName'), 'form-control-plaintext': !isEditMode }"
                    :readonly="!isEditMode"
                    v-model="user.firstName">
                  <div class="invalid-feedback">
                    {{ errors.first('firstName') }}
                  </div>
                </div>
                <div class="col-sm-6">
                  <label>Last Name</label>
                  <input
                    data-vv-as="last name"
                    name="lastName"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': errors.has('lastName'), 'form-control-plaintext': !isEditMode }"
                    :readonly="!isEditMode"
                    v-model="user.lastName">
                  <div class="invalid-feedback">
                    {{ errors.first('lastName') }}
                  </div>
                </div>
              </div>
              <div class="form-group row">
                <div class="col-sm-6">
                  <label>Job Title</label>
                  <input
                    data-vv-as="job title"
                    name="jobTitle"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': errors.has('jobTitle'), 'form-control-plaintext': !isEditMode }"
                    :readonly="!isEditMode"
                    v-model="user.jobTitle">
                  <div class="invalid-feedback">
                    {{ errors.first('jobTitle') }}
                  </div>
                </div>
                <div class="col-sm-3">
                  <label>Department</label>
                  <input
                    data-vv-as="department"
                    name="department"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': errors.has('department'), 'form-control-plaintext': !isEditMode }"
                    :readonly="!isEditMode"
                    v-model="user.department">
                  <div class="invalid-feedback">
                    {{ errors.first('department') }}
                  </div>
                </div>
                <div class="col-sm-3">
                  <label>Phone Number</label>
                  <input
                    v-validate="{max: 16}"
                    data-vv-as="phone"
                    name="phone"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': errors.has('phone'), 'form-control-plaintext': !isEditMode }"
                    :readonly="!isEditMode"
                    v-model="user.phone">
                  <div class="invalid-feedback">
                    {{ errors.first('phone') }}
                  </div>
                </div>
              </div>
              <!-- VALIDATION AND SUCCESS/ERROR MESSAGES -->
              <div v-if="this.$validator.errors.count() > 0" class="alert alert-danger fade show" role="alert">
                You have <strong>{{ this.$validator.errors.count() }} error<span v-if="this.$validator.errors.count() > 1">s</span></strong> in your submission:
                <ul>
                  <li v-for="error in this.$validator.errors.all()">
                    <strong>{{ error }}</strong>
                  </li>
                </ul>
              </div>
              <div v-if="success" class="alert alert-success fade show" role="alert">
                {{ successMessage }}
              </div>
              <div v-if="isDeleteError === true" class="alert alert-danger fade show" role="alert">
                There was an error deleting this item.
              </div>
              <!--IMAGE UPLOAD SUCCESS-->
              <div v-if="isUploadSuccess">
                <div v-if="uploadErrors.length == 0" class="alert alert-success" role="alert">
                  <p><strong>Your profile picture was uploaded successfully.</strong></p>
                </div>
                <div v-else class="alert alert-warning" role="alert">
                  <p><strong>Uploaded your image with the following errors.</strong></p>
                  <ul>
                    <li v-for="uploadError in uploadErrors">{{ uploadError }}</li>
                  </ul>
                </div>
              </div>
              <!--IMAGE DELETED SUCCESS-->
              <div v-if="isUploadDeleted">
                <div v-if="uploadErrors.length == 0" class="alert alert-success" role="alert">
                  <p><strong>Your profile picture was deleted.</strong></p>
                </div>
                <div v-else class="alert alert-warning" role="alert">
                  <p><strong>Your profile image was not deleted.</strong></p>
                  <ul>
                    <li v-for="uploadError in uploadErrors">{{ uploadError }}</li>
                  </ul>
                </div>
              </div>
              <!--IMAGE UPLOAD FAILED-->
              <div v-if="isUploadFailed" class="alert alert-danger" role="alert">
                <p><strong>Upload failed.</strong></p>
                <pre v-for="uploadError in uploadErrors">{{ uploadErrors }}</pre>
              </div>
              <!-- ACTION BUTTONS -->
              <button v-if="isEditMode" class="btn btn-success spacer-top" type="submit">{{ 'Update ' + username }}</button>
            </fieldset>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>
<style>
#delete-image-button{
  position: absolute;
  top:5px;
  left:20px;
}
</style>
<script>
    import Heading from './utils/Heading.vue'
    import NotFound from './utils/NotFound.vue'

    const STATUS_INITIAL = 0, STATUS_SAVING = 1, STATUS_SUCCESS = 2, STATUS_FAILED = 3, STATUS_DELETED = 4

    export default {
      created() {},
      mounted() {
        this.fetchUser()
      },
      components: {Heading, NotFound},
      props:{
        username: {
          type: String,
          required: false
        },
      },
      data: function() {
        return {
          apiError: {
            message: null,
            status: null
          },
          currentStatus: null,
          deleteConfirm: null,
          is404: false,
          isDataLoaded: false,
          isDeleted: false,
          isDeleteError: false,
          isEditMode: false, // true = make forms editable
          success: false,
          uploadedFile: null,
          uploadErrors: [],
          imageResizedURL: '/media/cache/resolve/profile/uploads/profile/',
          user: {
            id: ''
          },
        }
      },
      computed: {
        // are there any validation errors?
        haveErrors: function(){
          return this.$validator.errors.count() > 0 ? true : false
        },
        headingIcon: function() {
          return '<i class="fa fa-user"></i>'
        },
        lockIcon: function(){
          return this.isEditMode ? '<i class="fa fa-unlock"></i>' : '<i class="fa fa-lock"></i>'
        },
        // PHOTOS
        isUploadInitial() {
          return this.currentStatus === STATUS_INITIAL
        },
        isUploadSaving() {
          return this.currentStatus === STATUS_SAVING
        },
        isUploadSuccess() {
          return this.currentStatus === STATUS_SUCCESS
        },
        isUploadFailed() {
          return this.currentStatus === STATUS_FAILED
        },
        isUploadDeleted() {
          return this.currentStatus === STATUS_DELETED
        },
        // -end PHOTOS
      },
      methods: {
        afterSubmitSucceeds: function(){
          let self = this
          this.success = true
          this.successMessage = "Update successful."
          // remove the message after 3 seconds
          setTimeout(function(){
              self.success = false
          }, 3000)
        },
        // Run prior to submitting
        checkForm: function(){
          let self = this
          this.$validator.validateAll()
          .then((result) => {
            // if all fields valid, submit the form
            if (result) {
              self.submitForm()
              return
            }
          })
          .catch((error) => {
            self.apiError.status = 500
            self.apiError.message = "Something went wrong that wasn't validation related."
          });
        },
        deleteProfileImage: function() {
          let self = this
          this.currentStatus = STATUS_SAVING
          axios.delete('/api/userimages/' + this.user.image.id)
          .then(function(response) {
            self.currentStatus = STATUS_DELETED
            // remove the message after 3 seconds
            setTimeout(function(){
                self.currentStatus = STATUS_INITIAL
            }, 3000)
          })
          .catch(function(error) {
            self.apiError.status = error.response.status
            switch(error.response.status){
              case 403:
                self.apiError.message = "You do not have sufficient privileges to delete this photo."
                break
              case 404:
                self.apiError.message = "Photo was not found."
                break
              case 500:
                self.apiError.message = "An internal error occurred."
                break
              default:
                self.apiError.message = "An error occurred."
                break
            }
          })
        },
        fetchUser: function(){
          let self = this
          axios.get('/api/admin/users/' + this.username)
          // success
          .then(function (response) {
            self.user = response.data
            self.isDataLoaded = true
          })
          // fail
          .catch(function (error) {
            self.apiError.status = error.response.status
            switch(error.response.status){
              case 403:
                self.apiError.message = "You do not have sufficient privileges to retrieve a user."
                break
              case 404:
                self.apiError.message = "User was not found."
                break
              case 500:
                self.apiError.message = "An internal error occurred."
                break
              default:
                self.apiError.message = "An error occurred."
                break
            }
          })
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
          this.uploadImage(formData)
        },
        // Submit the form via the API
        submitForm: function(){
          let self = this // 'this' loses scope within axios
          // AJAX (axios) submission
          axios({
            method: 'put',
            url: '/api/admin/users/' + this.username,
            data: self.user
          })
            // success
            .then(function (response) {
              self.afterSubmitSucceeds()
            })
            // fail
            .catch(function (error) {
              let errors = error.response.data
              // Add any validation errors to the vee validator error bag
              errors.forEach(function(error){
                let key = error.property_path
                let message = error.message
                self.$validator.errors.add(key, message)
              })
            })
        },
        toggleEdit: function(){
          this.isEditMode === true ? this.isEditMode = false : this.isEditMode = true
        },
        uploadImage: function(formData){
          let self = this
          this.currentStatus = STATUS_SAVING
          // append map item ID to form data
          formData.append('user_id', this.user.id)

          // AJAX (axios) submission
          axios.post('/api/userimages/uploads', formData, {
              headers: {
                'Content-Type': 'multipart/form-data'
              }
          })
            // success
            .then(function (response) {
              // add the new images to the record's array of images
              self.user.image = response.data.processedImage
              self.uploadedFile = response.data.processedImage
              self.uploadErrors = response.data.errors
              self.currentStatus = STATUS_SUCCESS
              // remove the message after 3 seconds
              setTimeout(function(){
                  self.currentStatus = STATUS_INITIAL
              }, 3000)
            })
            // fail
            .catch(function (error) {
              self.currentStatus = STATUS_FAILED
              switch(error.response.status){
                case 403:
                  self.apiError.message = "You do not have sufficient privileges to upload images."
                  break
                case 404:
                  self.apiError.message = "Images were not found."
                  break
                case 500:
                  self.apiError.message = "An internal error occurred."
                  break
                default:
                  self.apiError.message = "An error occurred."
                  break
              }
              // remove the message after 10 seconds
              setTimeout(function(){
                  self.currentStatus = STATUS_INITIAL
              }, 10000)
            })
        },
      },
      filters: {},
    }
</script>
