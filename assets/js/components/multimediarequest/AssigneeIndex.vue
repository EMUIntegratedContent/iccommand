<template>
  <div>
    <heading>
      <span slot="icon" v-html="headingIcon">{{ headingIcon }}</span>
      <span slot="title">Multimedia Request Assignees</span>
    </heading>
    <p><a href="/multimediarequests/assignees/create" class="btn btn-info">New Assignee</a></p>
    <div v-if="apiError.status" class="alert alert-danger fade show" role="alert">
      {{ apiError.message }}
    </div>
    <div v-if="!loadingAssignees" class="table-responsive">
      <table class="table table-hover table-sm">
        <thead>
          <tr>
            <th scope="col">Last name</th>
            <th scope="col">First name</th>
            <th scope="col">Email</th>
            <th scope="col">Status</th>
            <th scope="col">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="assignee in paginatedAssignees">
            <th scope="row">{{ assignee.lastName }}</th>
            <td>{{ assignee.firstName }}</td>
            <td>{{ assignee.email }}</td>
            <td>{{ assignee.status ? assignee.status.status : '' }}</td>
            <td><a :href="'/multimediarequests/assignees/' + assignee.id"><i class="fa fa-eye"></i></a></td>
          </tr>
        </tbody>
      </table>
    </div>
    <div v-else>
      <p style="text-align: center"><img src="/images/loading.gif" alt="Loading..." /></p>
    </div>
    <paginator v-show="!loadingAssignees" :items="assignees" @itemsPerPageChanged="setPaginatedAssignees"></paginator>
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
        this.fetchAssignees()
      },
      components: {Heading, Paginator},
      props:{},
      data: function() {
        return {
          apiError: {
            message: null,
            status: null
          },
          assignees: [],
          loadingAssignees: true,
          paginatedAssignees: null,
        }
      },
      computed: {
        headingIcon: function() {
          return '<i class="fa fa-user"></i>'
        }
      },
      methods: {
        fetchAssignees: function(){
          let self = this
          axios.get('/api/multimediaassignees')
          // success
          .then(function (response) {
            self.assignees = response.data
          })
          // fail
          .catch(function (error) {
            self.apiError.status = error.response.status
            switch(error.response.status){
              case 403:
                self.apiError.message = "You do not have sufficient privileges to retrieve assignees."
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
            self.loadingAssignees = false
          })
        },
        setPaginatedAssignees: function(assignees){
          this.loadingAssignees = true // show loading wheel
          this.paginatedAssignees = assignees // set paginated assignees returned from child paginator components
          this.loadingAssignees = false // turn off loading wheel
        }
      },
      filters: {},
    }
</script>
