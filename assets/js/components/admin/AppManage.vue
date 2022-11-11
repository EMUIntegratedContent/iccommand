<template>
  <div>
    <heading>
      <span slot="icon" v-html="headingIcon"></span>
      <span slot="title">{{ appName }} Users</span>
    </heading>
    <!--<p><button type="button" class="btn btn-sm btn-info" @click="openAddUserModal">Add User</button></p>-->
    <div v-if="apiError.status" class="alert alert-danger fade show" role="alert">
      {{ apiError.message }}
    </div>
    <div v-if="!loadingUsers" class="row">
      <div v-for="(user, index) in paginatedUsers" class="col-xs-12 col-sm-6 col-md-4 pl-4 pb-2">
        <div class="card card-accent card-accent-blue" :class="{'card-accent-green' : isCardSaved(index), 'card-accent-yellow' : isCardDeleted(index), 'card-accent-red' : isCardError(index)}">
          <div class="card-header" :class="{'alert-success' : isCardSaved(index), 'alert-warning' : isCardDeleted(index), 'alert-danger' : isCardError(index)}">
            {{ user.username }}
            <button v-if="currentUser != user.username" type="button" @click="openDeleteModal(user, index)" class="close pull-right"><span aria-hidden="true">&times;</span></button>
          </div>
          <div class="card-body">
            <template v-if="currentUser == user.username">
              <p>You are an admin for this application.</p>
            </template>
            <template v-else>
              <template v-if="isCardDeleted(index)">
                <div class="alert alert-warning fade show" role="alert">
                  User's privileges have been revoked for this application.
                </div>
              </template>
              <template v-else>
                <p-check v-for="role in roles" v-model="user.roles" :key="role" :value="role" @change="flagCardModified(index)" class="p-default p-thick" color="primary-o">{{ role }}</p-check>
                <div v-if="isCardModified(index) && isCardError(index)" class="alert alert-danger fade show mt-4" role="alert">
                  There was a problem saving this user.
                </div>
                <div class="user-card-buttons pt-4" :class="{'d-none' : !isCardModified(index)}">
                  <button class="btn btn-success" type="submit" @click="saveUser(user, index)"><i class="fa fa-save"></i></button>
                  <button class="btn btn-default ml-2" @click="resetUser(user.id, index)"><i class="fa fa-undo"></i></button>
                </div>
              </template>
            </template>
          </div>
        </div>
      </div>
      <div class="col-xs-12 col-sm-6 col-md-4">
        <div class="card card-accent card-accent-orange">
          <div class="card-header">
            Add User
          </div>
          <div class="card-body">
            <div class="form-row">
              <div class="form-group col-md-12">
                <label for="newuser" class="sr-only">New User</label>
                <multiselect
                  v-validate="'required'"
                  data-vv-as="user"
                  :options="usersWithoutAppPermissions"
                  :multiple="false"
                  :clear-on-select="true"
                  placeholder="Choose user"
                  label="username"
                  track-by="id"
                  id="newuser"
                  class="form-control"
                  style="padding:0"
                  name="userWithoutAppPermissions"
                  :class="{'is-invalid': errors.has('newuser') }"
                  @input="newUserSelected"
                  >
                </multiselect>
                <div class="invalid-feedback">
                  {{ errors.first('newuser') }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div v-else>
      <p style="text-align: center"><img src="/images/loading.gif" alt="Loading..." /></p>
    </div>
    <paginator v-show="!loadingUsers" :items="users" @itemsPerPageChanged="setPaginatedUsers"></paginator>
    <!-- Delete Modal -->
    <div id="deleteModal" class="modal" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Revoke {{ deleteModalData.user ? deleteModalData.user.username : '' }}'s privileges</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <p>Are you sure you want to revoke {{ deleteModalData.user ? deleteModalData.user.username : '' }}'s privileges for {{ appName }}? Type the word <strong>"delete"</strong> to confirm.</p>
            <div class="form-group">
              <label for="deleteConfirm" class="sr-only" aria-hidden="true">Type "confirm" to delete</label>
              <input type="text" v-model="deleteConfirm" class="form-control" id="deleteConfirm"/>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-danger" data-dismiss="modal" @click="removeUserFromApp(deleteModalData.user, deleteModalData.index)" :disabled="deleteConfirm != 'delete'">Revoke</button>
          </div>
        </div>
      </div>
    </div>
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
        this.fetchUsersWithoutAppPermissions()
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
        currentUser: {
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
          cardClasses: {
            'card card-accent card-accent-red' : true,
          },
          deleteConfirm: null,
          deletedCards: [],
          deleteModalData: {
            user: null,
            index: null,
          },
          errorCards: [],
          modifiedCards: [],
          savedCards: [],
          roles: [],
          users: [],
          usersWithoutAppPermissions: [],
          originalUserRoles: [],
          loadingUsers: true,
          paginatedUsers: null
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
          newUser.roles = []
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
          axios.get('/api/admin/appusers/' + this.rolePrefix)
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
                self.apiError.message = "You do not have sufficient privileges to retrieve users."
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
        fetchUsersWithoutAppPermissions: function(){
          let self = this
          axios.get('/api/admin/appusers/not/'+ this.rolePrefix)
          // success
          .then(function (response) {
            self.usersWithoutAppPermissions = response.data
          })
          // fail
          .catch(function (error) {
            self.apiError.status = error.response.status
            switch(error.response.status){
              case 403:
                self.apiError.message = "You do not have sufficient privileges to retrieve users."
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
        isCardDeleted: function(index){
          let isCardDeleted = false
          this.deletedCards.forEach(function(card){
            if(card == index){
              isCardDeleted = true
            }
          })
          return isCardDeleted
        },
        isCardError: function(index){
          let isCardError = false
          this.errorCards.forEach(function(card){
            if(card == index){
              isCardError = true
            }
          })
          return isCardError
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
        isCardSaved: function(index){
          let isCardSaved = false
          this.savedCards.forEach(function(card){
            if(card == index){
              isCardSaved = true
            }
          })
          return isCardSaved
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
        // Add a user that was not previously given permissions for this app to the list of sanctioned users
        newUserSelected: function(user){
          let self = this
          if(user){
            let userExists = false
            this.users.forEach(function(existingUser){
              // make sure the user isn't accounted for already
              if(existingUser.id == user.id){
                userExists = true
              }
            })
            if(!userExists){
              this.users.push(user) // add the user to the sanctioned users
            }
            // remove the user from the list of non-sanctioned users
            this.usersWithoutAppPermissions.forEach(function(nonUser){
              if(nonUser.id == user.id){
                self.usersWithoutAppPermissions.splice(self.usersWithoutAppPermissions.indexOf(nonUser), 1)
              }
            })
          }
        },
        openDeleteModal(user, index){
            this.deleteModalData.user = user
            this.deleteModalData.index = index
            $('#deleteModal').modal('show')
        },
        storeUserOriginalRoles: function(user){
          let self = this
          let userObj = new Object()
          userObj.userId = user.id
          userObj.originalRoles = JSON.stringify(user.roles)

          this.originalUserRoles.push(userObj)
        },
        removeUserFromApp: function(user, index){
          this.deleteModalData.user = null; // reset delete modal user
          this.deleteModalData.index = null; // reset delete modal index
          // do NOT remove the user if 'delete' is not typed into the delete modal (if attempting delete)
          if(this.deleteConfirm != 'delete'){
            this.deleteConfirm = null; // reset delete text
            return
          }
          this.deleteConfirm = null; // reset delete text

          // Eliminate all user roles pertaining to this app from the current user object
          // TUTORIAL: https://gist.github.com/chad3814/2924672 (removing multiple items via splice)
          for (let i = user.roles.length - 1; i >= 0; i--) {
            if (user.roles[i].startsWith(this.rolePrefix)) {
              user.roles.splice(i, 1)
            }
          }
          // save the user to the database with all roles pertaining to this app taken away
          this.saveUser(user, index, true)
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
        // index = index of user in the list of cards
        // isDelete = is this saveUser request coming from the delete function?
        saveUser: function(user, index, isDelete){
          let self = this // 'this' loses scope within axios

          // AJAX (axios) submission
          axios({
            method: 'put',
            url: '/api/admin/users/' + user.username,
            data: user
          })
            // success
            .then(function (response) {
              if(isDelete){
                self.deletedCards.push(index)
                // remove from the deleted cards array after 5 seconds
                // remove the user from the list of app users
                setTimeout(function(){
                    self.deletedCards.splice(self.deletedCards.indexOf(index), 1)
                    self.usersWithoutAppPermissions.push(user) // add the deleted user to the list of users without permissions
                    self.users.splice(self.users.indexOf(user), 1)
                }, 5000)
              } else {
                // add to saved cards
                self.savedCards.push(index)
                // remove modified card
                self.modifiedCards.forEach(function(card){
                  if(card == index){
                    self.modifiedCards.splice(self.modifiedCards.indexOf(index))
                  }
                })
                // remove from the saved cards array after 5 seconds
                setTimeout(function(){
                    self.savedCards.splice(self.savedCards.indexOf(index), 1)
                }, 5000)
              }
            })
            // fail
            .catch(function (error) {
              self.errorCards.push(index)
              // remove from the error cards array after 10 seconds
              setTimeout(function(){
                  self.errorCards.splice(self.errorCards.indexOf(index), 1)
              }, 10000)
            })
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
