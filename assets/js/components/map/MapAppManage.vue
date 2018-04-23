<template>
  <div>
    <heading>
      <span slot="icon" v-html="headingIcon">{{ headingIcon }}</span>
      <span slot="title">{{ appName }} Users</span>
    </heading>
    <!--<p><button type="button" class="btn btn-sm btn-info" @click="openAddUserModal">Add User</button></p>-->
    <div v-if="apiError.status" class="alert alert-danger fade show" role="alert">
      {{ apiError.message }}
    </div>
    <!--
    <div v-if="!loadingUsers" class="table-responsive">
      <table class="table table-hover table-sm">
        <thead>
          <tr>
            <th scope="col">User name</th>
            <th scope="col">First name</th>
            <th scope="col">Last name</th>
            <th scope="col">App Admin?</th>
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
            <td><a :href="'/map/users/' + user.username"><i class="fa fa-eye"></i></a></td>
          </tr>
        </tbody>
      </table>
    </div>
    -->
    <div v-if="!loadingUsers" class="row">
      <div v-for="(user, index) in paginatedUsers" class="col-xs-12 col-sm-6 col-md-4 pl-4 pb-2">
        <div class="card card-accent card-accent-red">
          <div class="card-header">
            <template v-if="user.username == 'New User'">
              <div class="form-row">
                <div class="form-group col-md-12">
                  <label :for="'newuser-' + index" class="sr-only">Building Type</label>
                  <multiselect
                    v-validate="'required'"
                    data-vv-as="user"
                    :options="usersWithoutAppPermissions"
                    :multiple="false"
                    placeholder="Choose user"
                    label="username"
                    track-by="id"
                    :id="'newuser-' + index"
                    class="form-control"
                    style="padding:0"
                    name="userWithoutAppPermissions"
                    :class="{'is-invalid': errors.has('newuser-' + index) }"
                    >
                  </multiselect>
                  <div class="invalid-feedback">
                    {{ errors.first('newuser-' + index) }}
                  </div>
                </div>
              </div>
            </template>
            <template v-else>
              {{ user.username }}
            </template>
            <button type="button" @click="removeUserFromApp(appPrefix, user.id)" class="close pull-right"><span aria-hidden="true">&times;</span></button>
          </div>
          <div class="card-body">
            <p-check v-for="role in roles" v-model="user.roles" :key="role" :value="role" @change="flagCardModified(index)" class="p-default p-thick" color="primary-o">{{ role }}</p-check>
            <div class="user-card-buttons pt-4" :class="{'d-none' : !isCardModified(index)}">
              <button class="btn btn-success" type="submit" @click="saveUser(user.id)"><i class="fa fa-save"></i></button>
              <button class="btn btn-default ml-2" @click="resetUser(user.id, index)"><i class="fa fa-undo"></i></button>
            </div>
          </div>
        </div>
      </div>
      <div class="col-xs-12 col-sm-6 col-md-4">
        <div class="card mapitem-add-aux" @click="addUserCard">
          <div class="card-body">
            <i class="fa fa-plus fa-5x"></i><br />
            Add user
          </div>
        </div>
      </div>
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
    import Multiselect from 'vue-multiselect'

    export default {
      created() {},
      mounted() {
        this.fetchUsers()
        this.fetchRoles()
      },
      components: {Heading, Paginator, Multiselect},
      props:{
        appName: {
          type: String,
          required: true,
        },
        appSlug: {
          type: String,
          required: true
        },
        rolePrefix: {
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
          modifiedCards: [],
          roles: [],
          users: [],
          usersWithoutAppPermissions: [],
          originalUserRoles: [],
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
        addUserCard: function(){
          let newUser = new Object()
          newUser.username = 'New User'
          this.users.push(newUser)
        },
        fetchRoles: function(){
          let self = this
          axios.get('/api/admin/roles')
          // success
          .then(function (response) {
            // Filter the various roles by their application type
            // Roles are defined in the pattern ROLE_APPNAME in the backend
            for(let key in response.data){
              if(key.startsWith(self.rolePrefix)){
                self.roles.push(key)
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
        fetchUsers: function(){
          let self = this
          axios.get('/api/admin/mapusers')
          // success
          .then(function (response) {
            self.users = response.data
            // store original roles of each user
            self.users.forEach(function(user){
              self.storeUserOriginalRoles(user)
            })
          })
          // fail
          .catch(function (error) {
            self.apiError.status = error.response.status
            switch(error.response.status){
              case 403:
                self.apiError.message = "You do not have sufficient privileges to retrieve map users."
                break
              case 404:
                self.apiError.message = "Map users were not found."
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
        flagCardModified: function(index){
          let cardAlreadyFlagged = false
          this.modifiedCards.forEach(function(card){
            if(card == index){
              cardAlreadyFlagged = true
            }
          })
          if(!cardAlreadyFlagged){
            this.modifiedCards.push(index)
          }
        },
        isCardModified: function(index){
          let isCardModified = false
          this.modifiedCards.forEach(function(card){
            if(card == index){
              isCardModified = true
            }
          })
          return isCardModified
        },
        storeUserOriginalRoles: function(user){
          let self = this
          console.log('user ' + user.id + ' original roles being stored!')
          let userObj = new Object()
          userObj.userId = user.id
          userObj.originalRoles = JSON.stringify(user.roles)

          this.originalUserRoles.push(userObj)
        },
        isUserAdmin: function(user){
          let isAdmin = false
          user.roles.forEach(function(role){
            if(role == 'ROLE_MAP_ADMIN' || role.startsWith('ROLE_GLOBAL_ADMIN')){
              isAdmin = true
            }
          })
          return isAdmin
        },
        removeUserFromApp: function(rolePrefix, userId){
          console('removing all ' + rolePrefix + ' roles from user ' + userId)
        },
        resetUser: function(userId, index){
          let self = this
          this.originalUserRoles.forEach(function(item){
            if(item.userId == userId){
              self.users[index].roles = JSON.parse(item.originalRoles) // restore original roles
              self.modifiedCards.splice(self.modifiedCards[index], 1) // unmark card as being modified (need VALUE of index, not the index itself)
            }
          })
        },
        saveUser: function(userId){
          console.log('saving user ' + userId)
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
