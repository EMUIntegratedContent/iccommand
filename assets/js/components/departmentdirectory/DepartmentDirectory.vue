<template>
<div>
  <div class="modal" ref="modal" id="loginModal">
        <div class="modal-dialog">
          <div class="modal-content">
                <div class="modal-header">
                      <span slot="title">{{ departmentInformation.department }}</span>
                </div>

                <div class="modal-body">

                <div class="row">
                  <table class="table table-hover table-sm table-bordered">
                  <tbody>
                  <tr><td>Department Name</td><td>{{ departmentInformation.department }}</td></tr>
                  <tr><td>Updated</td><td>{{ departmentInformation.updated }}</td></tr>
                  <tr><td>Email</td><td>{{ departmentInformation.email }}</td></tr>
                  <tr><td>Website</td><td>{{ departmentInformation.website }}</td></tr>
                  <tr><td>Search Terms</td><td>{{ departmentInformation.search_terms }}</td></tr>
                  <tr><td>Map Building Name</td><td>{{ departmentInformation.map_building_name }}</td></tr>
                  <tr><td>Address 1</td><td>{{ departmentInformation.address_1 }}</td></tr>
                  <tr><td>Address 2</td><td>{{ departmentInformation.address_2 }}</td></tr>
                  <tr><td>City</td><td>{{ departmentInformation.city }}</td></tr>
                  <tr><td>State</td><td>{{ departmentInformation.state }}</td></tr>
                  <tr><td>Zip</td><td>{{ departmentInformation.zip }}</td></tr>
                  <tr><td>Faculty List</td><td>{{ departmentInformation.faculty_list }}</td></tr>
                  <tr><td>Staff List</td><td>{{ departmentInformation.staff_list }}</td></tr>
                  </tbody>
                  </table>
                </div>
                
                <div class="row">
                    <div class="col">
                      <button class="btn btn-info" @click="editDepartmentInformation(departmentInformation.id)">
                        Edit
                      </button>
                    </div>

                    <div class="col">
                      <button class="btn btn-danger" @click="deleteDepartmentInformation(departmentInformation.id)">
                        Delete
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
      <span slot="title">Department Directory</span>
    </heading>
    <div v-if="!loadingDepartmentDirectory" class="table-responsive">
    <table class="table table-hover table-sm table-bordered">
        <thead>
            <tr>
            <th scope="col">id</th>
            <th scope="col">Updated</th>
            <th scope="col">Department</th>
            <th scope="col">Phone</th>
            <th scope="col">Email</th>
            <th scope="col">View</th>
            </tr>
        </thead>  
    <tbody>
        <tr v-for="department in paginatedDepartmentDirectory" :id="department.id">
        <td>{{ department.id }}</td>
        <td>{{ department.updated }}</td>
        <td>{{ department.department }}</td>
        <td>{{ department.phone }}</td>
        <td>{{ department.email }}</td>
        <td>
            <button class="btn-eye" @click="departmentDirectoryItem(department)" data-target="#loginModal"><i class="fa fa-eye"></i></button>
        </td>
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

            apiError: {
                message: null,
                status: null
            },

            isDeleted: false,
            isDeleteError: false,
            success: false,
            successMessage: '',


            isEditMode: false,
            
            fetchedDepartmentDirectory : [],

            resultedDepartmentDirectory : [],

            paginatedDepartmentDirectory : [],

            loadingDepartmentDirectory : true,

            departmentInformation : '',
           
        }; 
    },

  computed: {
      haveErrors: function () {
          return this.$validator.errors.count() > 0 ? true : false
      },
      headingIcon: function () {

      },
      isInvalid: function () {
          return 'is-invalid'
      },
      // -end PHOTOS
      lockIcon: function () {
          return this.isEditMode ? '<i class="fa fa-unlock"></i>' : '<i class="fa fa-lock"></i>'
      },
      userCanEdit: function () {
          // An existing record can be edited by a user with edit permissions, a new record can be created by a user with create permissions
          return this.permissions[0].edit || this.permissions[0].create ? true : false
      },
      userCanEmail: function () {
          // An email can be sent by a user with email permissions
          return this.permissions[0].email
      }
  },

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
      axios.get("/api/departmentdirectory")
      .then(function(response) { // Success.
        response.data.forEach(function(department) {
          self.fetchedDepartmentDirectory.push(department);
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

    departmentDirectoryItem: function(selected_department) {
      this.departmentInformation = selected_department;
      $('#loginModal').modal('show');
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