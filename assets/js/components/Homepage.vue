<template>
  <div>
    <div class="row">
      <div class="col-xs-12">
        <h2>My Applications</h2>
      </div>
    </div>
    <div class="row">
      <template v-for="module in userModules">
        <div v-if="module.display" class="card col-sm-6 col-md-4">
          <img class="card-img-top" src="" alt="Card image cap">
          <div class="card-body">
            <h5 class="card-title">{{ module.title }}</h5>
            <p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p>
            <a :href="module.buttonLink" class="btn btn-primary">{{ module.buttonText }}</a>
          </div>
        </div>
      </template>
      <template v-if="userModules.length = 0">
        <p>You do not currently belong to any applications.</p>
      </template>
    </div>
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
              buttonText: "OMG MAPAPP",
              buttonLink: "/map/items",
              display: false
            },
            photo: {
              title: "Photo Requests",
              description: "The photo requests application is a scheduler for photographer inquiries on campus.",
              buttonText: "See Requests",
              buttonLink: "/photorequests",
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
      },
      methods: {
        // Based on permissions passed to this component, enable module display for appropriate system applications
        registerUserModule: function(role){
          if(role.includes('ROLE_MAP_')){
            this.userModules.map.display = true
          }
          if(role.includes('ROLE_PHOTO_')){
            this.userModules.photo.display = true
          }
          if(role.includes('ROLE_REDIRECT_')){
            this.userModules.redirect.display = true
          }
        },
      },
      filters: {},
    }
</script>
