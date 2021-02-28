<template>
<div>
    <heading>
      <span slot="title">Oucampus training signup list</span>
    </heading>
    <div class="table-responsive">
    <table class="table table-hover table-sm">
        <thead>
            <tr>
            <th scope="col">Participant</th>
            <th scope="col">Date/Time</th>
            <th scope="col">Email</th>
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
        <td>{{ registered_person.site }}</td>
        <td>{{ registered_person.new_user }}</td>
        <td>{{ registered_person.is_student }}</td>
        <td>{{ registered_person.supervisors }}</td>
        <td>{{ registered_person.comments }}</td>
        </tr>
    </tbody>
    </table>
    </div>

    <paginator
                v-show="!loadingSignupList"
                :items="resultedOucampusSignupList"
                @itemsPerPageChanged="setPaginatedOucampusSignupList">
    </paginator>
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
            
            fetchedOucampusSignupList : [],

            resultedOucampusSignupList : [],

            paginatedOucampusSignupList : [],

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

        if (self.resultedOucampusSignupList .length == 0) {
          self.loadingSignupList = false;
        }
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

        self.turnOffLoadingWheels();

      });
    },

    setPaginatedOucampusSignupList: function(SignupList) {
      this.loadingSignupList = true; // Show the loading wheel.
      this.paginatedOucampusSignupList = SignupList;
      this.loadingSignupList  = false; // Turn off the loading wheel.
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