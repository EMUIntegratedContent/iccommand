<template>
<div>
    <heading>
      <span slot="title">Department Directory</span>
    </heading>
    <div v-if="!loadingDepartmentDirectory" class="table-responsive">
    <table class="table table-hover table-sm table-bordered">
        <thead>
            <tr>
            <th scope="col">id</th>
            <th scope="col">Updated</th>
            <th scope="col">Department</th>
            <th scope="col">Search terms</th>
            <th scope="col">Address1</th>
            <th scope="col">Phone</th>
            <th scope="col">Email</th>
            <th scope="col">Website</th>
            <th scope="col">Faculty list</th>
            <th scope="col">Staff list</th>
            </tr>
        </thead>  
    <tbody>
        <tr v-for="department in paginatedDepartmentDirectory" :id="department.id">
        <td>{{ department.id }}</td>
        <td>{{ department.updated }}</td>
        <td>{{ department.department_name }}</td>
        <td>{{ department.search_terms }}</td>
        <td>{{ department.address }}</td>
        <td>{{ department.phone }}</td>
        <td>{{ department.email }}</td>
        <td>{{ department.website }}</td>
        <td>{{ department.faculty_list }}</td>
        <td>{{ department.staff_list }}</td>
        </tr>
    </tbody>
    </table>
    </div>

    <br>

    <paginator
                v-show="!loadingDepartmentDirectory"
                :items="resultedDepartmentDirectory"
                @itemsPerPageChanged="setPaginatedDepartmentDirectory">
    </paginator>
</div>
</template>

<script>
import Heading from "../utils/Heading.vue";
import Paginator from "../utils/Paginator.vue";

export default {
  created() {},

  mounted() {
    this.fetchDepartmentDirectory();
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
            
            fetchedDepartmentDirectory : [],

            resultedDepartmentDirectory : [],

            paginatedDepartmentDirectory : [],

            loadingDepartmentDirectory : true
           
        }; 
    },

  computed: {},

  methods: {
    /**
     * Gets the Department directory.
     */
    fetchDepartmentDirectory: function() {

      this.turnOnLoadingWheels();
      this.fetchedDepartmentDirectory = [];
      this.resultedDepartmentDirectory = [];
      let self = this; // "this" loses scope within Axios.

      /* Ajax (Axios) Submission */
      axios.get("/api/ousignup/ousignup")
      .then(function(response) { // Success.
        response.data.forEach(function(department) {
          self.DepartmentDirectory.push(department);
        });

        self.resultedDepartmentDirectory = self.fetchedDepartmentDirectory.slice();
        self.loadingDepartmentDirectory = false;
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

    setPaginatedDepartmentDirectory: function(DepartmentList) {
      this.loadingDepartmentDirectory= true; // Show the loading wheel.
      this.paginatedDepartmentDirectory = DepartmentList;
      this.loadingDepartmentDirectory = false; // Turn off the loading wheel.
    },
    
    /**
     * Sets the loading variables to false to hide all loading wheels.
     */
    turnOffLoadingWheels: function() {
      this.loadingDepartmentDirectory = false;
    },

    /**
     * Sets the loading variables to true to show all loading wheels.
     */
    turnOnLoadingWheels: function() {
      this.loadingDepartmentDirectory = true;
    },
  },

  filters: {}
}
</script>