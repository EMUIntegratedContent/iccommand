<template>
  <div>
    <heading>
      <span>Catalog Programs</span>
    </heading>
    <div v-if="apiError.status" class="alert alert-danger fade show" role="alert">
      {{ apiError.message }}
    </div>
    <div id="accordion">
      <div class="card">
        <div class="card-header" id="headingUgPrograms">
          <h5 class="mb-0">
            <button
                class="btn btn-link"
                data-toggle="collapse"
                data-target="#collapseUgPrograms"
                aria-expanded="true"
                aria-controls="collapseUgPrograms">
              Undergraduate Catalog Programs
              <span
                  v-if="!loadingUgPrograms"
                  class="badge badge-primary">{{ totalUgPrograms }}</span>
              <span v-else><i class="fa fa-spinner"></i></span>
            </button>
          </h5>
        </div>
        <div
            id="collapseUgPrograms"
            class="collapse show"
            aria-labelledby="headingUgPrograms"
            data-parent="#accordion">
          <div class="card-body">
            <div>
              <label for="ugselectedprog" class="sr-only">Search undergrad catalog</label>
              <VueMultiselect
                  :options="ugSearchResults"
                  :multiple="false"
                  :clear-on-select="true"
                  placeholder="Search undergrad catalog (type at least 3 characters)"
                  label="program"
                  track-by="id"
                  id="ugselectedprog"
                  class="form-control"
                  style="padding:0"
                  name="ugselectedprog"
                  @input="handleUgSearchInput"
                  @select="handleProgSelected"
              >
              </VueMultiselect>
            </div>
            <div v-if="!loadingUgPrograms" class="table-responsive">
              <table class="table table-hover table-sm">
                <thead>
                <tr>
                  <th scope="col">Program</th>
                  <th scope="col">Website</th>
                  <th scope="col">Actions</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="ugp in ugPrograms" :id="ugp.id" :key="`program-${ugp.id}`">
                  <td>
                    {{ ugp.program }}
                  </td>
                  <td>TODO</td>
                  <td>
                    <a v-if="userCanEdit" :href="'/programs/' + ugp.id"><i class="fa fa-eye"></i></a>
                  </td>
                </tr>
                </tbody>
              </table>
            </div>
            <div v-else>
              <p style="text-align: center"><img src="/images/loading.gif" alt="Loading..."/></p>
            </div>
            <external-paginator
                v-show="!loadingUgPrograms"
                :ext-curr-pg="ugProgramsCurrentPage"
                :ext-items-per-pg="ugProgramsItemsPerPage"
                :total-recs="totalUgPrograms"
                :items="ugPrograms"
                @itemsPerPageChanged="handleUgProgramsItemsPerPageChanged"
                @pageChanged="handleUgProgramsPageChanged"
            >
            </external-paginator>
          </div>
        </div>
      </div>
    </div>
    <br>
    <div id="accordion">
      <div class="card">
        <div class="card-header" id="headingGradPrograms">
          <h5 class="mb-0">
            <button
                class="btn btn-link"
                data-toggle="collapse"
                data-target="#collapseGradPrograms"
                aria-expanded="true"
                aria-controls="collapseGradPrograms">
              Graduate Catalog Programs
              <span
                  v-if="!loadingUgPrograms"
                  class="badge badge-primary">{{ totalGradPrograms }}</span>
              <span v-else><i class="fa fa-spinner"></i></span>
            </button>
          </h5>
        </div>
        <div
            id="collapseGradPrograms"
            class="collapse show"
            aria-labelledby="headingGradPrograms"
            data-parent="#accordion">
          <div class="card-body">
            <div>
              <label for="gradselectedprog" class="sr-only">Search graduate catalog</label>
              <VueMultiselect
                  :options="gradSearchResults"
                  :multiple="false"
                  :clear-on-select="true"
                  placeholder="Search graduate catalog (type at least 3 characters)"
                  label="program"
                  track-by="id"
                  id="gradselectedprog"
                  class="form-control"
                  style="padding:0"
                  name="gradselectedprog"
                  @input="handleGradSearchInput"
                  @select="handleProgSelected"
              >
              </VueMultiselect>
            </div>
            <div v-if="!loadingGradPrograms" class="table-responsive">
              <table class="table table-hover table-sm">
                <thead>
                <tr>
                  <th scope="col">Program</th>
                  <th scope="col">Website</th>
                  <th scope="col">Actions</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="grad in gradPrograms" :id="grad.id" :key="`program-${grad.id}`">
                  <td>
                    {{ grad.program }}
                  </td>
                  <td>TODO</td>
                  <td>
                    <a v-if="userCanEdit" :href="'/programs/' + grad.id"><i class="fa fa-eye"></i></a>
                  </td>
                </tr>
                </tbody>
              </table>
            </div>
            <div v-else>
              <p style="text-align: center"><img src="/images/loading.gif" alt="Loading..."/></p>
            </div>
            <external-paginator
                v-show="!loadingGradPrograms"
                :ext-curr-pg="gradProgramsCurrentPage"
                :ext-items-per-pg="gradProgramsItemsPerPage"
                :total-recs="totalGradPrograms"
                :items="gradPrograms"
                @itemsPerPageChanged="handleGradProgramsItemsPerPageChanged"
                @pageChanged="handleGradProgramsPageChanged"
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
import Heading from "../utils/Heading.vue";
import ExternalPaginator from "../utils/ExternalPaginator.vue"
import VueMultiselect from 'vue-multiselect'

export default {
  created () {
    this.fetchPrograms('undergraduate');
    this.fetchPrograms('graduate');
  },
  components: {
    Heading,
    ExternalPaginator,
    VueMultiselect
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

      loadingGradPrograms: true,
      loadingUgPrograms: true,
      gradSearchTerm: '',
      ugSearchTerm: '',
      gradPrograms: [],
      ugPrograms: [],

      //External Paginator Data
      ugProgramsCurrentPage: 1,
      ugProgramsItemsPerPage: 50,
      totalUgPrograms: 0,
      gradProgramsCurrentPage: 1,
      gradProgramsItemsPerPage: 50,
      totalGradPrograms: 0,

      // Search results
      ugSearchResults: [],
      gradSearchResults: []
    };
  },

  computed: {
    headingIcon: function () {
      return "<i class='fa fa-list'></i>";
    },
    userCanEdit: function () {
      return this.permissions[0].user ? true : false;
    }
  },
  methods: {
    handleUgProgramsPageChanged: function (currentPage) {
      this.ugProgramsCurrentPage = currentPage;
      this.fetchPrograms('undergraduate');
    },
    handleUgProgramsItemsPerPageChanged: function (itemsPerPage) {
      this.ugProgramsItemsPerPage = itemsPerPage;
      this.fetchPrograms('undergraduate');
    },
    handleGradProgramsPageChanged: function (currentPage) {
      this.gradProgramsCurrentPage = currentPage;
      this.fetchPrograms('graduate');
    },
    handleGradProgramsItemsPerPageChanged: function (itemsPerPage) {
      this.gradProgramsItemsPerPage = itemsPerPage;
      this.fetchPrograms('graduate');
    },
    handleUgSearchInput: function (evt) {
      this.ugSearchTerm = evt.target.value;
      if(this.ugSearchTerm.length > 2) {
        this.searchPrograms('undergraduate');
      }
    },
    handleGradSearchInput: function (evt) {
      this.gradSearchTerm = evt.target.value;
      if(this.gradSearchTerm.length > 2) {
        this.searchPrograms('graduate');
      }
    },
    handleProgSelected: function (evt) {
      if(this.userCanEdit) {
        window.location.href = '/programs/' + evt.id
      }
    },
    fetchPrograms: function (catalog) {
      let self = this; // "this" loses scope within Axios.

      let currPage, currItemsPerPage
      if(catalog === 'undergraduate') {
        this.loadingUgPrograms = true;
        currPage = this.ugProgramsCurrentPage
        currItemsPerPage = this.ugProgramsItemsPerPage
      } else if (catalog === 'graduate') {
        this.loadingGradPrograms = true;
        currPage = this.gradProgramsCurrentPage
        currItemsPerPage = this.gradProgramsItemsPerPage
      } else {
        return false
      }

      /* Ajax (Axios) Submission */
      axios.get(`/api/programs/list?page=${currPage}&limit=${currItemsPerPage}&catalog=${catalog}`)
      .then(function (response) { // Success.
        if(catalog === 'undergraduate') {
          self.totalUgPrograms = response.data.totalRows;
          self.ugPrograms = response.data.programs;
        } else {
          self.totalGradPrograms = response.data.totalRows;
          self.gradPrograms = response.data.programs;
        }
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

      if(catalog === 'undergraduate') {
        this.loadingUgPrograms = false;
      } else if (catalog === 'graduate') {
        this.loadingGradPrograms = false;
      }
    },
    searchPrograms: function (catalog) {
      let self = this; // "this" loses scope within Axios.

      let searchTerm
      if(catalog === 'undergraduate') {
        searchTerm = this.ugSearchTerm;
      } else if (catalog === 'graduate') {
        searchTerm = this.gradSearchTerm;
      } else {
        return false
      }

      if(searchTerm.length > 2) {
        /* Ajax (Axios) Submission */
        axios.get(`/api/programs/search?searchterm=${searchTerm}&catalog=${catalog}`)
        .then(function (response) { // Success.
          if(catalog === 'undergraduate') {
            self.ugSearchResults = response.data;
          } else {
            self.gradSearchResults = response.data;
          }
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
      }
    }
  }
};
</script>
