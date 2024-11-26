<template>
  <div>
    <heading>
      <span>Catalog Programs</span>
    </heading>
    <div v-if="apiError.status" class="alert alert-danger fade show" role="alert">
      {{ apiError.message }}
    </div>
    <div>
      <p>
        This is a list of all emich.edu websites that are affiliated with current and previous catalog programs.
        If you see a <span class="badge badge-info">UN</span> label in the row it means that the program is not in the current year's catalog.
      </p>
      <p>
        Clicking the <i class="fa fa-eye"></i> icon will take you to the program page if the program is in the current year's catalog. From there, you can update the URL for this program.
        For unaffiliated programs, you will be taken to the website's edit page where you can update the program name or delete the website record.
      </p>
    </div>
    <div id="accordion">
      <div class="card">
        <div class="card-header" id="headingWebsites">
          <h5 class="mb-0">
            <button
                class="btn btn-link"
                data-toggle="collapse"
                data-target="#collapseWebsites"
                aria-expanded="true"
                aria-controls="collapseWebsites">
              Catalog Program Websites
              <span
                  v-if="!loadingWebsites"
                  class="badge badge-primary">{{ totalWebsites }}</span>
              <span v-else><i class="fa fa-spinner"></i></span>
            </button>
          </h5>
        </div>
        <div
            id="collapseWebsites"
            class="collapse show"
            aria-labelledby="headingWebsites"
            data-parent="#accordion">
          <div class="card-body">
            <div>
              <label for="selectedwebsite" class="sr-only">Search program websites</label>
              <VueMultiselect
                  :options="searchResults"
                  :multiple="false"
                  :clear-on-select="true"
                  placeholder="Search program websites (type at least 3 characters of the program name)"
                  label="display"
                  track-by="prog_id"
                  id="selectedwebsite"
                  class="form-control"
                  style="padding:0"
                  name="selectedwebsite"
                  @input="handleSearchInput"
                  @select="handleWebsiteSelected"
              >
              </VueMultiselect>
            </div>
            <div v-if="!loadingWebsites" class="table-responsive">
              <table class="table table-hover table-sm">
                <thead>
                <tr>
                  <th scope="col">Website</th>
                  <th scope="col">Program</th>
                  <th scope="col">Actions</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="site in websites" :id="`website-${site.id}`" :key="`website-${site.id}`">
                  <td>
                    {{ site.url }}
                  </td>
                  <td>
                    {{ site.program }}
                  </td>
                  <td>
                    <template v-if="userCanEdit">
                      <a v-if="site.prog_id" :href="'/programs/' + site.prog_id"><i class="fa fa-eye"></i></a>
                      <a v-else :href="'/programs/websites/' + site.id">
                        <i class="fa fa-eye mr-2"></i>
                        <span class="badge badge-info" title="This link is not affiliated with a current catalog program">UN</span>
                      </a>
                    </template>
                  </td>
                </tr>
                </tbody>
              </table>
            </div>
            <div v-else>
              <p style="text-align: center"><img src="/images/loading.gif" alt="Loading..."/></p>
            </div>
            <external-paginator
                v-show="!loadingWebsites"
                :ext-curr-pg="currentPage"
                :ext-items-per-pg="itemsPerPage"
                :total-recs="totalWebsites"
                :items="websites"
                @itemsPerPageChanged="handleItemsPerPageChanged"
                @pageChanged="handlePageChanged"
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
    this.fetchWebsites();
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

      loadingWebsites: true,
      searchTerm: '',
      websites: [],

      //External Paginator Data
      currentPage: 1,
      itemsPerPage: 50,
      totalWebsites: 0,

      // Search results
      searchResults: [],
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
    handlePageChanged: function (currentPage) {
      this.currentPage = currentPage;
      this.fetchWebsites();
    },
    handleItemsPerPageChanged: function (itemsPerPage) {
      this.itemsPerPage = itemsPerPage;
      this.fetchWebsites();
    },
    handleSearchInput: function (evt) {
      this.searchTerm = evt.target.value;
      if (this.searchTerm.length > 2) {
        this.searchWebsites();
      }
    },
    handleWebsiteSelected: function (evt) {
      if (this.userCanEdit) {
        if(evt.prog_id) {
          window.location.href = '/programs/' + evt.prog_id
        } else {
          window.location.href = '/programs/websites/' + evt.id
        }
      }
    },
    fetchWebsites: function () {
      let self = this; // "this" loses scope within Axios.

      /* Ajax (Axios) Submission */
      axios.get(`/api/programs/websites?page=${self.currentPage}&limit=${self.itemsPerPage}`)
      .then(function (response) { // Success.
        self.totalWebsites = response.data.totalRows;
        self.websites = response.data.websites;
      })
      .catch(function (error) { // Failure.
        self.apiError.status = error.response.status;

        switch (error.response.status) {
          case 403:
            self.apiError.message = "You do not have sufficient privileges to retrieve program websites.";
            break;
          case 404:
            self.apiError.message = "Websites were not found.";
            break;
          case 500:
            self.apiError.message = "An internal error occurred.";
            break;
          default:
            self.apiError.message = "An error occurred.";
            break;
        }
      });

      this.loadingWebsites = false;
    },
    searchWebsites: function () {
      let self = this; // "this" loses scope within Axios.

      if (self.searchTerm.length > 2) {
        /* Ajax (Axios) Submission */
        axios.get(`/api/programs/searchwebsites?searchterm=${self.searchTerm}`)
        .then(function (response) { // Success.
          self.searchResults = response.data;
        })
        .catch(function (error) { // Failure.
          self.apiError.status = error.response.status;

          switch (error.response.status) {
            case 403:
              self.apiError.message = "You do not have sufficient privileges to retrieve program websites.";
              break;
            case 404:
              self.apiError.message = "Program websites were not found.";
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
