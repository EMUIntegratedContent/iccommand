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
      <div class="card">
        <div class="card-header" id="headingPrograms">
          <h5 class="mb-0">
            <button
                class="btn btn-link"
                data-toggle="collapse"
                data-target="#collapsePrograms"
                aria-expanded="true"
                aria-controls="collapsePrograms">
              Catalog Programs
              <span
                  v-if="!loadingPrograms"
                  class="badge badge-primary">{{ totalPrograms }}</span>
              <span v-else><i class="fa fa-spinner"></i></span>
            </button>
          </h5>
        </div>
        <div
            id="collapsePrograms"
            class="collapse show"
            aria-labelledby="headingPrograms"
            data-parent="#accordion">
          <div class="card-body">
<!--                  {{ programs }}-->
            <div v-if="!loadingPrograms" class="table-responsive">
              <table class="table table-hover table-sm">
                <thead>
                <tr>
                  <th scope="col">Program</th>
                  <th scope="col">Catalog</th>
                  <th scope="col">Actions</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="program in programs" :id="program.id" :key="`program-${program.id}`">
                  <td>
                    {{ program.program }}
                  </td>
                  <td>
                    {{ program.catalog }}
                  </td>
                  <td>
                    <a v-if="userCanEdit" :href="'/programs/' + program.id"><i class="fa fa-eye"></i></a>
                  </td>
                </tr>
                </tbody>
              </table>
            </div>
            <div v-else>
              <p style="text-align: center"><img src="/images/loading.gif" alt="Loading..."/></p>
            </div>
            <external-paginator
                v-show="!loadingPrograms"
                :ext-curr-pg="programsCurrentPage"
                :ext-items-per-pg="programsItemsPerPage"
                :total-recs="totalPrograms"
                :items="programs"
                @itemsPerPageChanged="handleProgramsItemsPerPageChanged"
                @pageChanged="handleProgramsPageChanged"
            >
            </external-paginator>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<style></style>
<script>
// import Heading from "../utils/Heading.vue";
import ExternalPaginator from "../utils/ExternalPaginator.vue";

export default {
  created () {
    this.fetchPrograms();
  },
  components: {
    // Heading,
    ExternalPaginator
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
    handleProgramsPageChanged: function (currentPage) {
      this.programsCurrentPage = currentPage;
      this.fetchPrograms();
    },

    /**
     * When paginator items per page is changed.
     * @param itemsPerPage
     */
    handleProgramsItemsPerPageChanged: function (itemsPerPage) {
      this.programsItemsPerPage = itemsPerPage;
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
