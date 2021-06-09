<template>
    <div>
    <div class="modal" ref="modal" id="loginModal">
        <div class="modal-dialog">
          <div class="modal-content">
                <div class="modal-header">
                      <span slot="title">{{ participantInfo.full_name }}</span>
                </div>

                <div class="modal-body">

                <div class="row">
                  <table class="table table-hover table-sm table-bordered">
                  <tbody>
                  <tr><td>Participant</td><td>{{ participantInfo.full_name }}</td></tr>
                  <tr><td>Date/Time</td><td>{{ participantInfo.date }}</td></tr>
                  <tr><td>Email</td><td>{{ participantInfo.emich_email }}</td></tr>
                  <tr><td>URL</td><td>{{ participantInfo.site }}</td></tr>
                  <tr><td>New User</td><td>{{ participantInfo.new_user }}</td></tr>
                  <tr><td>Student</td><td>{{ participantInfo.is_student }}</td></tr>
                  <tr><td>Reports to</td><td>{{ participantInfo.supervisors }}</td></tr>
                  <tr><td>Comments / Questions</td><td>{{ participantInfo.comments }}</td></tr>
                  </tbody>
                  </table>
                </div>
                
                <div class="row">
                    <div class="col">
                      <button class="btn copy-button" @click="">
                      copy
                      </button>
                    </div>

                    <div class="col">
                      <button class="btn btn-danger" @click="deleteSignupListItem(participantInfo.id)">
                      delete
                      </button>
                    </div>
                  </div>

                <br>
                  <div v-if="apiError.status" class="alert alert-danger fade show" role="alert">
                    {{ apiError.message }}
                </div>
                <div v-if="success" class="alert alert-success fade show" role="alert">
                    {{ successMessage }}
                </div>
                <div v-if="isDeleteError === true" class="alert alert-danger fade show" role="alert">
                    There was an error deleting this request.
                </div>

                </div>
          </div>
        </div>
    </div>

    <heading>
      <span slot="title">Oucampus training signup list</span>
    </heading>
    <div v-if="!loadingSignupList" class="table-responsive">
    <table class="table table-hover table-sm table-bordered">
        <thead>
            <tr>
            <th scope="col">Participant</th>
            <th scope="col">Date/Time</th>
            <th scope="col">Email</th>

            <th scope="col">View</th>
            <th scope="col">URL</th>
            <th scope="col">New User</th>
            <th scope="col">Student</th>
            <th scope="col">Reports to</th>
            <th scope="col">Comments / Questions</th>

            </tr>
        </thead>  
    <tbody>
        <tr v-for="registered_person in paginatedOucampusSignupList" :id="registered_person.id">
        <td>{{ registered_person.full_name }}</td>
        <td>{{ registered_person.date }}</td>
        <td>{{ registered_person.emich_email }}</td>

        <td>
            <button class="btn-eye" @click="signupListItem(registered_person)" data-target="#loginModal"><i class="fa fa-eye"></i></button>
        </td>

        <td>{{ registered_person.site }}</td>
        <td>{{ registered_person.new_user }}</td>
        <td>{{ registered_person.is_student }}</td>
        <td>{{ registered_person.supervisors }}</td>
        <td>{{ registered_person.comments }}</td>

        </tr>
    </tbody>
    </table>
    </div>

    <br>

    <paginator
                v-show="!loadingSignupList"
                :items="resultedOucampusSignupList"
                @itemsPerPageChanged="setPaginatedOucampusSignupList">
    </paginator>

  </div>
</template>

<script>
  // Variables used for the copy feature:
  var counter = 0;
  var netIds = [];
  var firstNames = [];
  var lastNames = [];

  $(document).ready(function() {
    $(".copy-button").each(function() {
      var rowId = (counter <= 99)
        ? ((counter >= 10) ? "0" + counter : "00" + counter) : counter;
      $(this).attr("id", rowId);
      ++counter;
    });

    $(".copy-content").hide();

    $(".copy-button").click(function() {
      var netId = netIds[parseInt($(this).attr("id"))];

      $(".copy-content").val("(function(net,f,l) {" +
        "$('#restrictions .control-group').show();" +
        "$('#username').val(net);" +
        "$('#first_name').val(f);" +
        "$('#last_name').val(l);" +
        "$('#email').val(net+'@emich.edu');" +
        "$('#privilege').val(6);" +
        "$('#toolbar').val('User');" +
        "$('#allow_overwrite').prop('checked', true);" +
        "$('#allow_delete').prop('checked', true);" +
        "$('#ldap_host').val('ldap.emich.edu');" +
        "$('#ldap_dn').val('cn='+net+',ou=people,o=campus');" +
        "} (\"" + netId + "\", \"" + firstNames[parseInt($(this).attr("id"))] +
        "\", \"" + lastNames[parseInt($(this).attr("id"))] + "\"))");

      $(".copy-content").show();
      $(".copy-content").select();
      document.execCommand("Copy");
      $(".copy-content").hide();

      console.log(netId + "'s data has been copied.");
    });
  });
</script>

<script>

</div>
</template>

<script>

import Heading from "../utils/Heading.vue";
import Paginator from "../utils/Paginator.vue";

export default {
  created() {},

  mounted() {
    this.fetchOusignupList();
  },

components: {Heading, Paginator},

  props: {
    permissions: {
      type: Array,
      required: false
    }
  },

  data: function() { 
        return {


            apiError: {
                message: null,
                status: null
            },

            isDeleted: false,
            isDeleteError: false,
            success: false,
            successMessage: '',
          
            fetchedOucampusSignupList : [],

            resultedOucampusSignupList : [],

            paginatedOucampusSignupList : [],

            participantInfo : '',

            loadingSignupList : true
           
        }; 
    },

  computed: {},

  methods: {
    /**
     * Gets the oucampus signup list.
     */
    fetchOusignupList: function() {

      this.turnOnLoadingWheels();
      this.fetchedOucampusSignupList = [];
      this.resultedOucampusSignupList = [];
      let self = this; // "this" loses scope within Axios.

      /* Ajax (Axios) Submission */
      axios.get("/api/ousignup/ousignup")
      .then(function(response) { // Success.
        response.data.forEach(function(signup_data) {
          self.fetchedOucampusSignupList.push(signup_data);
        });

        self.resultedOucampusSignupList = self.fetchedOucampusSignupList.slice();
        self.loadingSignupList = false;
      })
      .catch(function(error) { // Failure.
        self.apiError.status = error.response.status;

        switch (error.response.status) {
          case 403:
            self.apiError.message = "You do not have sufficient privileges to see oucampus signup list.";
            break;
          case 404:
            self.apiError.message = "Redirects were not found.";
            break;
          case 500:
            self.apiError.message = "An internal error occurred.";
            break;
          default:
            self.apiError.message = "An error occurred.";
            break;
        }
        

      });
    },

    setPaginatedOucampusSignupList: function(SignupList) {
      this.loadingSignupList = true; // Show the loading wheel.
      this.paginatedOucampusSignupList = SignupList;
      this.loadingSignupList  = false; // Turn off the loading wheel.
    },

    signupListItem: function(selected_person) {
      this.participantInfo = selected_person;
      $('#loginModal').modal('show');
    },

    deleteSignupListItem: function(selected_person_id) {
      let self = this
      console.log(selected_person_id);
      axios.delete('/api/ousignup/ousignup/delete/' + selected_person_id)
      .then(function(response) {
        console.log(response);
        self.markItemDeleted(); 
      })
      .catch(function(error) {
        console.log(error);
        self.markItemDeleteError()
      })
    },
    // Emit an event to the parent component telling it the item has been deleted
    //itemDeleted: function(){
    //  this.$emit('itemDeleted')
    //},
    // Emit an event to the parent component telling it the item has not been deleted
    //itemDeleteError: function(){
    //  this.$emit('itemDeleteError')
    //},
    markItemDeleted: function () {
        this.isDeleteError = false;
        this.isDeleted = true;
        this.success = true;
        this.successMessage = "Participant record has been deleted."
        setTimeout(function () {
            // This record doesn't exist anymore, so send the user back to the assignees list page
            window.location.replace('/ousignup')
        }, 3000)
    },
    markItemDeleteError: function () {
        let self = this
        this.isDeleted = false
        this.isDeleteError = true
        setTimeout(function () {
            self.isDeleteError = false
        }, 5000)
    },

    /**
     * Sets the loading variables to false to hide all loading wheels.
     */
    turnOffLoadingWheels: function() {
      this.loadingSignupList = false;
    },

    /**
     * Sets the loading variables to true to show all loading wheels.
     */
    turnOnLoadingWheels: function() {
      this.loadingSignupList = true;
    },
  },

  filters: {}
}
</script>