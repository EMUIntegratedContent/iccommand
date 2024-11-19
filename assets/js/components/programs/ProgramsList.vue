<template>
  <div>
<!--    <heading>-->
<!--      <span>Catalog Programs</span>-->
<!--    </heading>-->
    <div v-if="apiError.status" class="alert alert-danger fade show" role="alert">
      {{ apiError.message }}
    </div>
    <div>
<!--      <input-->
<!--          name="searchTerm"-->
<!--          type="text"-->
<!--          class="form-control"-->
<!--          placeholder="Search"-->
<!--          v-model="searchTerm"-->
<!--          @change="filterRedirects"/>-->
    </div>
    <br/>
    <div id="accordion">
      {{ programs }}
    </div>
  </div>
</template>
<style></style>
<script>
import Heading from "../utils/Heading.vue";
import ExternalPaginator from "../utils/ExternalPaginator.vue";

export default {
  created () {
    this.fetchPrograms();
  },
  components: {
    // Heading,
    // ExternalPaginator
  },
  props: {
    permissions: {
      type: Array,
      required: true
    }
  },
  data: function () {
    return {
      apiError: {
        message: null,
        status: null
      },

      loadingPrograms: true,
      searchTerm: "",
      programs: [],

      //External Paginator Data
      programsCurrentPage: 1,
      programsItemsPerPage: 10,
      totalPrograms: 0
    };
  },

  computed: {
    /**
     * Gets the heading icon.
     * @return {string} The heading icon.
     */
    headingIcon: function () {
      return "<i class='fa fa-list'></i>";
    },

    /**
     * Determines if the user can edit.
     * @return {boolean} True if the user can edit; false otherwise.
     */
    userCanEdit: function () {
      return this.permissions[0].user ? true : false;
    }
  },
  methods: {
    /**
     * When paginator page is changed.
     * @param currentPage
     */
    handleProgramsItemsPageChanged: function (currentPage) {
      this.shortenedRedirectsCurrentPage = currentPage;
      this.fetchPrograms();
    },

    /**
     * Gets the programs.
     */
    fetchPrograms: function () {
      this.loadingPrograms = true;
      this.fetchedRedirectsOfBrokenLinks = [];
      let self = this; // "this" loses scope within Axios.

      /* Ajax (Axios) Submission */
      axios.get(`/api/programs/list?page=${this.programsCurrentPage}&limit=${this.programsItemsPerPage}`)
      .then(function (response) { // Success.
        self.totalPrograms = response.data.totalRows;
        self.programs = response.data.programs;
      })
      .catch(function (error) { // Failure.
        self.apiError.status = error.response.status;

        switch (error.response.status) {
          case 403:
            self.apiError.message = "You do not have sufficient privileges to retrieve programs.";
            break;
          case 404:
            self.apiError.message = "Programs were not found.";
            break;
          case 500:
            self.apiError.message = "An internal error occurred.";
            break;
          default:
            self.apiError.message = "An error occurred.";
            break;
        }
      });
      self.loadingPrograms = false;
    }
  }
};
</script>
