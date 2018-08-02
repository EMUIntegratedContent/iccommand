<template>
  <div>
    <template v-if="typeof username != 'undefined'">
      <div class="row">
        <div class="col-xs-12">
          <h2>My Applications</h2>
        </div>
      </div>
      <div class="row">
        <div v-for="module in userModules" v-if="module.display" class="card col-sm-6 col-md-4">
          <div class="card-body">
            <h5 class="card-title">{{ module.title }}</h5>
            <p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p>
            <a :href="module.buttonLink" class="btn btn-primary">{{ module.buttonText }}</a>
          </div>
        </div>
        <p v-if="!userHasModules">You do not currently belong to any applications.</p>
      </div>
    </template>
    <template v-else>
      <p>Please <a href="login">sign in</a> to begin.</p>
    </template>
  </div>
</template>
<style>
</style>
<script>
    export default {
      created() {},
      mounted() {
        // If the user has any roles (or if the user accessing the homepage is logged in)
        if(this.userroles){
          let self = this
          this.userroles.forEach(function(role){
            self.registerUserModule(role)
          })
        }
      },
      components: {},
      props:{
        username: {
          type: String,
          required: false
        },
        userroles: {
          type: Array,
          required: false
        },
      },
      data: function() {
        return {
          userModules: {
            map: {
              title: "Campus Map",
              description: "The campus map application contains all points of interest at EMU. These items are displayed at emich.edu/maps.",
              buttonText: "Open Application",
              buttonLink: "/map/items",
              display: false
            },
            multimedia: {
              title: "Multimedia Requests",
              description: "The photo requests application is a scheduler for photographer/videographer/graphic designer inquiries on campus.",
              buttonText: "See Requests",
              buttonLink: "/multimediarequests",
              display: false
            },
            redirect: {
              title: "Redirect Application",
              description: "The redirect application is responsible for setting up 301 redirects and vanity URLs for all EMU pages.",
              buttonText: "Manage Redirects",
              buttonLink: "/redirects",
              display: false
            },
          }
        }
      },
      computed: {
        // Does this user have permission to access any applications?
        userHasModules: function(){
          let hasModules = false
          for (let module in this.userModules) {
            if(this.userModules[module].display === true){
              hasModules = true
            }
          }
          return hasModules
        }
      },
      methods: {
        // Based on permissions passed to this component, enable module display for appropriate system applications
        registerUserModule: function(role){
          if(role.includes('ROLE_MAP_') || role.includes('ROLE_GLOBAL_ADMIN_')){
            this.userModules.map.display = true
          }
          if(role.includes('ROLE_MULTIMEDIA_') || role.includes('ROLE_GLOBAL_ADMIN_')){
            this.userModules.multimedia.display = true
          }
          if(role.includes('ROLE_REDIRECT_') || role.includes('ROLE_GLOBAL_ADMIN_')){
            this.userModules.redirect.display = true
          }
        },
      },
      filters: {},
      watch: {},
    }
</script>
