<template>
  <div>
    <not-found v-if="is404 === true"></not-found>
    <div v-if="isDataLoaded === false">
      <p style="text-align: center"><img src="/images/loading.gif" alt="Loading..." /></p>
    </div>
    <!--<p><a href="/register" class="btn btn-primary">New User</a></p>-->
    <div v-if="apiError.status" class="alert alert-danger fade show" role="alert">
      {{ apiError.message }}
    </div>
    <!-- MAIN AREA -->
    <div v-if="isDataLoaded === true && isDeleted === false && is404 === false">
      <heading>
        <span slot="icon" v-html="headingIcon">{{ headingIcon }}</span>
        <span slot="title">Configure user {{ username }}</span>
      </heading>
      <p><button type="button" class="btn btn-info pull-right" @click="toggleEdit"><span v-html="lockIcon"></span></button></p>
      <form class="form" id="permissionsForm" @submit.prevent="checkForm">
        <template v-if="isEditMode">
          <h4>Administrative Roles</h4>
          <p-check v-for="role in rolesAdmin" v-model="user.roles" :key="role" :value="role" class="p-default p-thick" color="primary-o">{{ role }}</p-check>
          <h4>Campus Map</h4>
          <p-check v-for="role in rolesMap" v-model="user.roles" :key="role" :value="role" class="p-default p-thick" color="primary-o">{{ role }}</p-check>
          <br />
          <p-check class="p-switch p-slim" v-model="user.enabled" color="success">{{ user.enabled ? 'Enabled' : 'Disabled' }}</p-check>
          <button class="btn btn-success spacer-top" type="submit">{{ 'Update ' + username }}</button>
        </template>
        <template v-else>
          <h4>Roles</h4>
          <p v-for="(role, index) in user.roles">
            {{ role }}<span v-if="index < (user.roles.length - 1)">, </span>
          </p>
        </template>
      </form>
    </div>
  </div>
</template>
<style>
</style>
<script>
    import Heading from '../utils/Heading.vue'
    import NotFound from '../utils/NotFound.vue'

    export default {
      created() {},
      mounted() {
        this.fetchUser()
        this.fetchRoles()
      },
      components: {Heading, NotFound},
      props:{
        username: {
          type: String,
          required: true
        },
      },
      data: function() {
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
          user: {
            id: '',
            roles: []
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
      },
      methods: {
        checkForm: function(){
          this.submitForm()
        },
        fetchRoles: function(){
          let self = this
          axios.get('/api/admin/roles')
          // success
          .then(function (response) {
            self.roles = response.data
            // Filter the various roles by their application type
            // Roles are defined in the pattern ROLE_APPNAME in the backend
            for(let key in response.data){
              if(key.startsWith('ROLE_GLOBAL_ADMIN')){
                self.rolesAdmin.push(key)
              }
              if(key.startsWith('ROLE_MAP_')){
                self.rolesMap.push(key)
              }
            }
          })
          // fail
          .catch(function (error) {
            self.apiError.status = error.response.status
            switch(error.response.status){
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
              console.log("YOU GUH")
            })
            // fail
            .catch(function (error) {
              console.log("NAH FAM")
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
        userHasRole: function(role){
          return this.user.roles.includes(role) ? true : false
        },
      },
      filters: {},
    }
</script>
