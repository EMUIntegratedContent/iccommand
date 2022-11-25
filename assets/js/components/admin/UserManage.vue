<template>
  <div>
    <not-found v-if="is404 === true"></not-found>
    <div v-if="isDataLoaded === false">
      <p style="text-align: center"><img src="/images/loading.gif" alt="Loading..."/></p>
    </div>
    <!--<p><a href="/register" class="btn btn-primary">New User</a></p>-->
    <div v-if="apiError.status" class="alert alert-danger fade show" role="alert">
      {{ apiError.message }}
    </div>
    <!-- MAIN AREA -->
    <div v-if="isDataLoaded === true && isDeleted === false && is404 === false">
      <heading>
        <span slot="icon" v-html="headingIcon"></span>
        <span slot="title">Configure user {{ username }}</span>
      </heading>
      <div class="button-holder" role="group">
        <a href="/admin/users" class="btn btn-info pull-left"><i class="fa fa-arrow-left"></i></a>
        <button type="button" class="btn btn-info pull-right" @click="toggleEdit"><span v-html="lockIcon"></span>
        </button>
      </div>
      <form class="form" id="permissionsForm" @submit.prevent="checkForm">
        <fieldset>
          <legend>Basic Information</legend>
          <div class="form-group row">
            <div class="col-sm-6">
              <label>First Name</label>
<!--              <input-->
<!--                  data-vv-as="first name"-->
<!--                  name="firstName"-->
<!--                  type="text"-->
<!--                  class="form-control"-->
<!--                  :class="{ 'is-invalid': errors.has('firstName'), 'form-control-plaintext': !isEditMode }"-->
<!--                  :readonly="!isEditMode"-->
<!--                  v-model="user.firstName">-->
<!--              <div class="invalid-feedback">-->
<!--                {{ errors.first('firstName') }}-->
<!--              </div>-->
            </div>
            <div class="col-sm-6">
              <label>Last Name</label>
<!--              <input-->
<!--                  data-vv-as="last name"-->
<!--                  name="lastName"-->
<!--                  type="text"-->
<!--                  class="form-control"-->
<!--                  :class="{ 'is-invalid': errors.has('lastName'), 'form-control-plaintext': !isEditMode }"-->
<!--                  :readonly="!isEditMode"-->
<!--                  v-model="user.lastName">-->
<!--              <div class="invalid-feedback">-->
<!--                {{ errors.first('lastName') }}-->
<!--              </div>-->
            </div>
          </div>
          <div class="form-group row">
            <div class="col-sm-6">
              <label>Job Title</label>
<!--              <input-->
<!--                  data-vv-as="job title"-->
<!--                  name="jobTitle"-->
<!--                  type="text"-->
<!--                  class="form-control"-->
<!--                  :class="{ 'is-invalid': errors.has('jobTitle'), 'form-control-plaintext': !isEditMode }"-->
<!--                  :readonly="!isEditMode"-->
<!--                  v-model="user.jobTitle">-->
<!--              <div class="invalid-feedback">-->
<!--                {{ errors.first('jobTitle') }}-->
<!--              </div>-->
            </div>
            <div class="col-sm-3">
              <label>Department</label>
<!--              <input-->
<!--                  data-vv-as="department"-->
<!--                  name="department"-->
<!--                  type="text"-->
<!--                  class="form-control"-->
<!--                  :class="{ 'is-invalid': errors.has('department'), 'form-control-plaintext': !isEditMode }"-->
<!--                  :readonly="!isEditMode"-->
<!--                  v-model="user.department">-->
<!--              <div class="invalid-feedback">-->
<!--                {{ errors.first('department') }}-->
<!--              </div>-->
            </div>
            <div class="col-sm-3">
              <label>Phone Number</label>
<!--              <input-->
<!--                  v-validate="{max: 16}"-->
<!--                  data-vv-as="phone"-->
<!--                  name="phone"-->
<!--                  type="text"-->
<!--                  class="form-control"-->
<!--                  :class="{ 'is-invalid': errors.has('phone'), 'form-control-plaintext': !isEditMode }"-->
<!--                  :readonly="!isEditMode"-->
<!--                  v-model="user.phone">-->
<!--              <div class="invalid-feedback">-->
<!--                {{ errors.first('phone') }}-->
<!--              </div>-->
            </div>
          </div>
          <div class="form-group">
              <input type="checkbox"
                     v-model="user.enabled"
                     :disabled="username == loggedInUser || !isEditMode"
                     true-value="1"
                     false-value="0"
              > {{ user.enabled ? 'Enabled' : 'Disabled' }}
            {{ (username == loggedInUser) && isEditMode ? '(you cannot disable your own account)' : '' }}
          </div>
        </fieldset>
        <fieldset>
          <legend>User Roles</legend>
          <div class="row">
            <div class="col-xs-12 col-sm-6 col-md-4 pl-4 pb-2">
              <div class="card card-accent card-accent-red">
                <div class="card-header">Administrative</div>
                <div class="card-body">
                  <p v-if="username == loggedInUser">You cannot change your own administrative settings.</p>
                  <template v-for="role in rolesAdmin" :key="'admin-role-'+role">
                    <input type="checkbox"
                       v-model="user.roles"
                       :disabled="username == loggedInUser || !isEditMode"
                       :value="role"
                    > {{ role }} <br>
                  </template>
                </div>
              </div>
            </div>
            <div class="col-xs-12 col-sm-6 col-md-4 pl-4 pb-2">
              <div class="card card-accent card-accent-blue">
                <div class="card-header">Campus Map</div>
                <div class="card-body">
                  <template v-for="role in rolesMap" :key="'user-map-'+role">
                    <input type="checkbox"
                           v-model="user.roles"
                           :disabled="!isEditMode"
                           :value="role"
                    > {{ role }} <br>
                  </template>
                </div>
              </div>
            </div>
            <div class="col-xs-12 col-sm-6 col-md-4 pl-4 pb-2">
              <div class="card card-accent card-accent-blue">
                <div class="card-header">Redirect</div>
                <div class="card-body">
                  <template v-for="role in rolesRedirect" :key="'user-redirect-'+role">
                    <input type="checkbox"
                           v-model="user.roles"
                           :disabled="!isEditMode"
                           :value="role"
                    > {{ role }} <br>
                  </template>
                </div>
              </div>
            </div>
            <div class="col-xs-12 col-sm-6 col-md-4 pl-4 pb-2">
              <div class="card card-accent card-accent-blue">
                <div class="card-header">Multimedia Request</div>
                <div class="card-body">
                  <template v-for="role in rolesMultimedia" :key="'user-multi-'+role">
                    <input type="checkbox"
                           v-model="user.roles"
                           :disabled="!isEditMode"
                           :value="role"
                    > {{ role }} <br>
                  </template>
                </div>
              </div>
            </div>
          </div>
          <!-- VALIDATION AND SUCCESS/ERROR MESSAGES -->
<!--          <div v-if="$validator.errors.count() > 0" class="alert alert-danger fade show" role="alert">-->
<!--            You have <strong>{{ $validator.errors.count() }} error<span-->
<!--              v-if="$validator.errors.count() > 1">s</span></strong> in your submission:-->
<!--            <ul>-->
<!--              <li v-for="error in $validator.errors.all()">-->
<!--                <strong>{{ error }}</strong>-->
<!--              </li>-->
<!--            </ul>-->
<!--          </div>-->
          <div v-if="success" class="alert alert-success fade show" role="alert">
            {{ successMessage }}
          </div>
          <div v-if="isDeleteError === true" class="alert alert-danger fade show" role="alert">
            There was an error deleting this item.
          </div>
          <!-- ACTION BUTTONS -->
          <button v-if="isEditMode" class="btn btn-success spacer-top" type="submit">{{ 'Update ' + username }}</button>
        </fieldset>
      </form>
    </div>
  </div>
</template>
<style>
.button-holder {
  height: 50px;
}
</style>
<script>
import Heading from '../utils/Heading.vue'
import NotFound from '../utils/NotFound.vue'

export default {
  created() {
  },
  mounted() {
    this.fetchUser()
    this.fetchRoles()
  },
  components: {Heading, NotFound},
  props: {
    username: {
      type: String,
      required: true
    },
    loggedInUser: {
      type: String,
      required: true
    }
  },
  data: function () {
    return {
      apiError: {
        message: null,
        status: null
      },
      is404: false,
      isDataLoaded: false,
      isDeleted: false,
      isDeleteError: false,
      isEditMode: false, // true = make forms editable
      roles: [],
      rolesAdmin: [],
      rolesMap: [],
      rolesMultimedia: [],
      rolesRedirect: [],
      success: false,
      user: {
        id: '',
        roles: []
      },
    }
  },
  computed: {
    // are there any validation errors?
    haveErrors: function () {
      return this.$validator.errors.count() > 0 ? true : false
    },
    headingIcon: function () {
      return '<i class="fa fa-user"></i>'
    },
    lockIcon: function () {
      return this.isEditMode ? '<i class="fa fa-unlock"></i>' : '<i class="fa fa-lock"></i>'
    },
  },
  methods: {
    afterSubmitSucceeds: function () {
      let self = this
      this.success = true
      this.successMessage = "Update successful."
      // remove the message after 3 seconds
      setTimeout(function () {
        self.success = false
      }, 3000)
    },
    // Run prior to submitting
    checkForm: function () {
      let self = this
      // this.$validator.validateAll()
      //     .then((result) => {
      //       // if all fields valid, submit the form
      //       if (result) {
      //         self.submitForm()
      //         return
      //       }
      //     })
      //     .catch((error) => {
      //       self.apiError.status = 500
      //       self.apiError.message = "Something went wrong that wasn't validation related."
      //     });
    },
    fetchRoles: function () {
      let self = this
      axios.get('/api/admin/roles')
          // success
          .then(function (response) {
            self.roles = response.data
            // Filter the various roles by their application type
            // Roles are defined in the pattern ROLE_APPNAME in the backend
            for (let key in response.data) {
              if (key.startsWith('ROLE_GLOBAL_ADMIN')) {
                self.rolesAdmin.push(key)
              }
              if (key.startsWith('ROLE_MAP_')) {
                self.rolesMap.push(key)
              }
              if (key.startsWith('ROLE_MULTIMEDIA_')) {
                self.rolesMultimedia.push(key)
              }
              if (key.startsWith('ROLE_REDIRECT_')) {
                self.rolesRedirect.push(key)
              }
            }
          })
          // fail
          .catch(function (error) {
            self.apiError.status = error.response.status
            switch (error.response.status) {
              case 403:
                self.apiError.message = "You do not have sufficient privileges to retrieve roles."
                break
              case 404:
                self.apiError.message = "Roles not found."
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
    fetchUser: function () {
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
            switch (error.response.status) {
              case 403:
                self.apiError.message = "You do not have sufficient privileges to retrieve users."
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
    // Submit the form via the API
    submitForm: function () {
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
            errors.forEach(function (error) {
              let key = error.property_path
              let message = error.message
              self.$validator.errors.add(key, message)
            })
          })
    },
    toggleEdit: function () {
      this.isEditMode === true ? this.isEditMode = false : this.isEditMode = true
    },
    userHasRole: function (role) {
      return this.user.roles.includes(role) ? true : false
    },
  },
  filters: {},
}
</script>
