<template>
  <div>
    <heading>
      <span slot="icon" v-html="headingIcon">{{ headingIcon }}</span>
      <span slot="title">Registered Users</span>
    </heading>
    <p><a href="/register" class="btn btn-info">New User</a></p>
    <div v-if="apiError.status" class="alert alert-danger fade show" role="alert">
      {{ apiError.message }}
    </div>
    <div v-if="!loadingUsers" class="table-responsive">
      <table class="table table-hover table-sm">
        <thead>
          <tr>
            <th scope="col">User name</th>
            <th scope="col">First name</th>
            <th scope="col">Last name</th>
            <th scope="col">Admin?</th>
            <th scope="col">Enabled?</th>
            <th scope="col">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="user in paginatedUsers">
            <th scope="row">{{ user.username }}</th>
            <td>{{ user.firstName }}</td>
            <td>{{ user.lastName }}</td>
            <td>
              <span v-if="isUserAdmin(user)"><i class="fa fa-star"></i></span>
            </td>
            <td><i :class="user.enabled ? 'fa fa-check-circle' : 'fa fa-times-circle'"></i></td>
            <td><a :href="'/admin/users/' + user.username"><i class="fa fa-eye"></i></a></td>
          </tr>
        </tbody>
      </table>
    </div>
    <div v-else>
      <p style="text-align: center"><img src="/images/loading.gif" alt="Loading..." /></p>
    </div>
    <paginator v-show="!loadingUsers" :items="users" @itemsPerPageChanged="setPaginatedUsers"></paginator>
  </div>
</template>
<style>
</style>
<script>
    import Heading from '../utils/Heading.vue'
    import Paginator from '../utils/Paginator.vue'

    export default {
      created() {},
      mounted() {
        this.fetchUsers()
      },
      components: {Heading, Paginator},
      props:{},
      data: function() {
        return {
          apiError: {
            message: null,
            status: null
          },
          users: [],
          loadingUsers: true,
          paginatedUsers: null,
        }
      },
      computed: {
        headingIcon: function() {
          return '<i class="fa fa-user"></i>'
        }
      },
      methods: {
        fetchUsers: function(){
          let self = this
          axios.get('/api/admin/users')
          // success
          .then(function (response) {
            self.users = response.data
          })
          // fail
          .catch(function (error) {
            self.apiError.status = error.response.status
            switch(error.response.status){
              case 403:
                self.apiError.message = "You do not have sufficient privileges to retrieve users."
                break
              case 404:
                self.apiError.message = "Users were not found."
                break
              case 500:
                self.apiError.message = "An internal error occurred."
                break
              default:
                self.apiError.message = "An error occurred."
                break
            }
            self.loadingUsers = false
          })
        },
        isUserAdmin: function(user){
          let isAdmin = false
          user.roles.forEach(function(role){
            if(role.startsWith('ROLE_GLOBAL_ADMIN')){
              isAdmin = true
            }
          })
          return isAdmin
        },
        setPaginatedUsers: function(users){
          this.loadingUsers = true // show loading wheel
          this.paginatedUsers = users // set paginated users returned from child paginator components
          this.loadingUsers = false // turn off loading wheel
        }
      },
      filters: {},
    }
</script>
