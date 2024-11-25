<template>
  <div>
    <heading>
      <span>Redirect Items</span>
    </heading>
    <div v-if="apiError.status" class="alert alert-danger fade show" role="alert">
      {{ apiError.message }}
    </div>
    <br/>
    <div id="accordion">
      <!-- Broken Links Section -->
      <div class="card">
        <div class="card-header" id="headingRedirectsOfBrokenLinks">
          <h5 class="mb-0">
            <button
                class="btn btn-link"
                data-toggle="collapse"
                data-target="#collapseRedirectsOfBrokenLinks"
                aria-expanded="true"
                aria-controls="collapseRedirectsOfBrokenLinks">
              Redirects of Broken Links
              <span
                  v-if="!loadingRedirectsOfBrokenLinks"
                  class="badge badge-primary">{{ totalBrokenRedirects }}</span>
              <span v-else><i class="fa fa-spinner"></i></span>
            </button>
          </h5>
        </div>
        <div
            id="collapseRedirectsOfBrokenLinks"
            class="collapse show"
            aria-labelledby="headingRedirectsOfBrokenLinks"
            data-parent="#accordion">
          <div class="card-body">
            <div>
              <label for="selectedredir" class="sr-only">Search broken redirects</label>
              <VueMultiselect
                  :options="brokenSearchResults"
                  :multiple="false"
                  :clear-on-select="true"
                  placeholder="Search broken redirects (type at least 3 characters)"
                  label="linkDescr"
                  track-by="id"
                  id="selectedredir"
                  class="form-control"
                  style="padding:0"
                  name="selectedredir"
                  @input="handleBrokenSearchInput"
                  @select="handleRedirectSelected"
              >
              </VueMultiselect>
            </div>
            <div v-if="!loadingRedirectsOfBrokenLinks" class="table-responsive">
              <table class="table table-hover table-sm">
                <thead>
                <tr>
                  <th scope="col">Broken Links</th>
                  <th scope="col">Actual Links</th>
                  <th scope="col">Last Visit</th>
                  <th scope="col">Visits</th>
                  <th scope="col">Created</th>
                  <th scope="col">Updated</th>
                  <th scope="col">Actions</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="redirect in fetchedRedirectsOfBrokenLinks" :id="redirect.id" :key="`broken-${redirect.id}`">
                  <td>
                    <a
                        :href="'https://www.emich.edu' + redirect.fromLink"
                        target="_blank"
                        title="Go to this Eastern Michigan University page.">{{ redirect.fromLink }}</a>
                  </td>
                  <td>
                    <a
                        :href="getFixedLink(redirect.toLink)"
                        target="_blank"
                        title="Go to this Eastern Michigan University page.">{{ redirect.toLink }}</a>
                  </td>
                  <td>{{ formatDate(redirect.lastVisit) }}</td>
                  <td>{{ redirect.visits }}</td>
                  <td>{{ redirect.createdBy }}</td>
                  <td>{{ redirect.contentChanged }}</td>
                  <td>
                    <a v-if="userCanEdit" :href="'/redirects/' + redirect.id"><i class="fa fa-eye"></i></a>
                  </td>
                </tr>
                </tbody>
              </table>
            </div>
            <div v-else>
              <p style="text-align: center"><img src="/images/loading.gif" alt="Loading..."/></p>
            </div>
            <external-paginator
                v-show="!loadingRedirectsOfBrokenLinks"
                :ext-curr-pg="brokenRedirectsCurrentPage"
                :ext-items-per-pg="brokenRedirectsItemsPerPage"
                :total-recs="totalBrokenRedirects"
                :items="fetchedRedirectsOfBrokenLinks"
                @itemsPerPageChanged="handleBrokenItemsPerPageChanged"
                @pageChanged="handleBrokenItemsPageChanged"></external-paginator>
          </div>
        </div>
      </div>
      <br>
      <!-- Shortened Links Section -->
      <div class="card">
        <div class="card-header" id="headingRedirectsOfShortenedLinks">
          <h5 class="mb-0">
            <button
                class="btn btn-link collapsed"
                data-toggle="collapse"
                data-target="#collapseRedirectsOfShortenedLinks"
                aria-expanded="false"
                aria-controls="collapseRedirectsOfShortenedLinks">
              Redirects of Shortened Links
              <span
                  v-if="!loadingRedirectsOfShortenedLinks"
                  class="badge badge-primary">{{ totalShortenedRedirects }}</span>
              <span v-else><i class="fa fa-spinner"></i></span>
            </button>
          </h5>
        </div>
        <div
            id="collapseRedirectsOfShortenedLinks"
            class="collapse"
            aria-labelledby="headingRedirectsOfShortenedLinks"
            data-parent="#accordion">
          <div class="card-body">
            <div>
              <label for="selectedshort" class="sr-only">Search shortened redirects</label>
              <VueMultiselect
                  :options="shortenedSearchResults"
                  :multiple="false"
                  :clear-on-select="true"
                  placeholder="Search shortened redirects (type at least 3 characters)"
                  label="linkDescr"
                  track-by="id"
                  id="selectedshort"
                  class="form-control"
                  style="padding:0"
                  name="selectedshort"
                  @input="handleShortenedSearchInput"
                  @select="handleRedirectSelected"
              >
              </VueMultiselect>
            </div>
            <div v-if="!loadingRedirectsOfShortenedLinks" class="table-responsive">
              <table class="table table-hover table-sm">
                <thead>
                <tr>
                  <th scope="col">Shortened/Vanity Links</th>
                  <th scope="col">Full Links</th>
                  <th scope="col">Last Visit</th>
                  <th scope="col">Visits</th>
                  <th scope="col">Created</th>
                  <th scope="col">Updated</th>
                  <th scope="col">Actions</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="redirect in fetchedRedirectsOfShortenedLinks" :id="redirect.id" :key="`shortened-${redirect.id}`">
                  <td>
                    <a
                        :href="'https://www.emich.edu' + redirect.fromLink"
                        target="_blank"
                        title="Go to this Eastern Michigan University page.">{{ redirect.fromLink }}</a>
                  </td>
                  <td>
                    <a
                        :href="getFixedLink(redirect.toLink)"
                        target="_blank"
                        title="Go to this Eastern Michigan University page.">{{ redirect.toLink }}</a>
                  </td>
                  <td>{{ formatDate(redirect.lastVisit) }}</td>
                  <td>{{ redirect.visits }}</td>
                  <td>{{ redirect.createdBy }}</td>
                  <td>{{ redirect.contentChanged }}</td>
                  <td>
                    <a v-if="userCanEdit" :href="'/redirects/' + redirect.id"><i class="fa fa-eye"></i></a>
                  </td>
                </tr>
                </tbody>
              </table>
            </div>
            <div v-else>
              <p style="text-align: center"><img src="/images/loading.gif" alt="Loading..."/></p>
            </div>
            <external-paginator
                v-show="!loadingRedirectsOfShortenedLinks"
                :items="fetchedRedirectsOfShortenedLinks"
                :ext-curr-pg="shortenedRedirectsCurrentPage"
                :ext-items-per-pg="shortenedRedirectsItemsPerPage"
                :total-recs="totalShortenedRedirects"
                @itemsPerPageChanged="handleShortenedItemsPerPageChanged"
                @pageChanged="handleShortenedItemsPageChanged"></external-paginator>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<style></style>
<script>
import Heading from "../utils/Heading.vue";
import ExternalPaginator from "../utils/ExternalPaginator.vue";
import VueMultiselect from 'vue-multiselect'
export default {
  created () {
    this.fetchBrokenRedirects();
    this.fetchShortenedRedirects();
  },
  components: { Heading, ExternalPaginator, VueMultiselect },
  props: {
    permissions: {
      type: Array,
      required: true
    }
  },
  data: function () {
    return {
      /* **************************** Error Data **************************** */

      /**
       * The error for the API controller consists of a message and a status.
       * @type {Object}
       */
      apiError: {
        message: null,
        status: null
      },

      /* *************************** Fetched Data *************************** */

      /**
       * The expired redirects that are fetched when this list is mounted.
       * @type {Array.<Redirect>}
       */
      fetchedExpiredRedirects: [],

      /**
       * The redirects of the broken links that are fetched when this list is
       * mounted.
       * @type {Array.<Redirect>}
       */
      fetchedRedirectsOfBrokenLinks: [],

      /**
       * The redirects of the shortened links that are fetched when this list is
       * mounted.
       * @type {Array.<Redirect>}
       */
      fetchedRedirectsOfShortenedLinks: [],

      /**
       * The invalid redirects that are fetched when this list is mounted.
       * @type {Array.<Redirect>}
       */
      fetchedInvalidRedirects: [],

      /* ************************** Processing Data ************************* */

      /**
       * Is used to check if the list is loading the expired redirects.
       * @type {boolean}
       */
      loadingExpiredRedirects: true,

      /**
       * Is used to check if the list is loading the redirects of the broken
       * links.
       * @type {boolean}
       */
      loadingRedirectsOfBrokenLinks: true,

      /**
       * Is used to check if the list is loading the redirects of the shortened
       * links.
       * @type {boolean}
       */
      loadingRedirectsOfShortenedLinks: true,

      /**
       * Is used to check if the list is loading the invalid redirects.
       * @type {boolean}
       */
      loadingInvalidRedirects: true,

      /**
       * The search term/key that filters the redirects.
       * @type {string}
       */
      searchTerm: "",

      /**
       * External Paginator Data
       */
      brokenRedirectsCurrentPage: 1,
      brokenRedirectsItemsPerPage: 10,
      totalBrokenRedirects: 0,
      shortenedRedirectsCurrentPage: 1,
      shortenedRedirectsItemsPerPage: 10,
      totalShortenedRedirects: 0,

      // Search results
      brokenSearchTerm: '',
      brokenSearchResults: [],
      shortenedSearchTerm: '',
      shortenedSearchResults: []
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
     * When the broken redirects search input is changed.
     * @param evt
     */
    handleBrokenSearchInput: function (evt) {
      this.brokenSearchTerm = evt.target.value;
      if(this.brokenSearchTerm.length > 2) {
        this.searchRedirects('broken');
      }
    },
    /**
     * When a redirect is selected from the search results
     * @param evt
     */
    handleRedirectSelected: function (evt) {
      if(this.userCanEdit) {
        window.location.href = '/redirects/' + evt.id
      }
    },
    /**
     * When the shortened redirects search input is changed.
     * @param evt
     */
    handleShortenedSearchInput: function (evt) {
      this.shortenedSearchTerm = evt.target.value;
      if(this.shortenedSearchTerm.length > 2) {
        this.searchRedirects('shortened');
      }
    },
    /**
     * When paginator items per page is changed.
     * @param itemsPerPage
     */
    handleBrokenItemsPerPageChanged: function (itemsPerPage) {
      this.brokenRedirectsItemsPerPage = itemsPerPage;
      this.fetchBrokenRedirects();
    },

    /**
     * When paginator page is changed.
     * @param currentPage
     */
    handleBrokenItemsPageChanged: function (currentPage) {
      this.brokenRedirectsCurrentPage = currentPage;
      this.fetchBrokenRedirects();
    },

    /**
     * When paginator items per page is changed.
     * @param itemsPerPage
     */
    handleShortenedItemsPerPageChanged: function (itemsPerPage) {
      this.shortenedRedirectsItemsPerPage = itemsPerPage;
      this.fetchShortenedRedirects();
    },

    /**
     * When paginator page is changed.
     * @param currentPage
     */
    handleShortenedItemsPageChanged: function (currentPage) {
      this.shortenedRedirectsCurrentPage = currentPage;
      this.fetchShortenedRedirects();
    },

    /**
     * Gets the broken redirects.
     */
    fetchBrokenRedirects: function () {
      this.loadingRedirectsOfBrokenLinks = true;
      this.fetchedRedirectsOfBrokenLinks = [];
      let self = this; // "this" loses scope within Axios.

      /* Ajax (Axios) Submission */
      axios.get(`/api/redirects/broken?page=${this.brokenRedirectsCurrentPage}&limit=${this.brokenRedirectsItemsPerPage}`)
      .then(function (response) { // Success.
        self.totalBrokenRedirects = response.data.totalRows;
        self.fetchedRedirectsOfBrokenLinks = response.data.redirects;
      })
      .catch(function (error) { // Failure.
        self.apiError.status = error.response.status;

        switch (error.response.status) {
          case 403:
            self.apiError.message = "You do not have sufficient privileges to retrieve redirects.";
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
      self.loadingRedirectsOfBrokenLinks = false;
    },

    /**
     * Gets the shortened redirects.
     */
    fetchShortenedRedirects: function () {
      this.loadingRedirectsOfShortenedLinks = true;
      this.fetchedRedirectsOfShortenedLinks = [];
      let self = this; // "this" loses scope within Axios.

      /* Ajax (Axios) Submission */
      axios.get(`/api/redirects/shortened?page=${this.shortenedRedirectsCurrentPage}&limit=${this.shortenedRedirectsItemsPerPage}`)
      .then(function (response) { // Success.
        self.totalShortenedRedirects = response.data.totalRows;
        self.fetchedRedirectsOfShortenedLinks = response.data.redirects;
      })
      .catch(function (error) { // Failure.
        self.apiError.status = error.response.status;

        switch (error.response.status) {
          case 403:
            self.apiError.message = "You do not have sufficient privileges to retrieve redirects.";
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
      self.loadingRedirectsOfShortenedLinks = false;
    },

    /**
     * Formats the specified date to the "MMM DD, YYYY" form.
     * @param {string} date The specified date to be formatted.
     * @return {string} If the date is null, return "N/A"; the formatted date otherwise.
     */
    formatDate: function (date) {
      if (date) {
        var newDate = new Date(date);
        var formattedDate;

        switch (newDate.getMonth()) {
          case 0:
            formattedDate = "Jan. ";
            break;
          case 1:
            formattedDate = "Feb. ";
            break;
          case 2:
            formattedDate = "Mar. ";
            break;
          case 3:
            formattedDate = "Apr. ";
            break;
          case 4:
            formattedDate = "May ";
            break;
          case 5:
            formattedDate = "Jun. ";
            break;
          case 6:
            formattedDate = "Jul. ";
            break;
          case 7:
            formattedDate = "Aug. ";
            break;
          case 8:
            formattedDate = "Sept. ";
            break;
          case 9:
            formattedDate = "Oct. ";
            break;
          case 10:
            formattedDate = "Nov. ";
            break;
          case 11:
            formattedDate = "Dec. ";
            break;
        }

        formattedDate += newDate.getDate() + ", " + newDate.getFullYear();

        return formattedDate;
      }
      else {
        return "N/A";
      }
    },

    /**
     * Obtains the fixed link by the specified link based if the link is an
     * Eastern Michigan University link.
     * @param {string} link The link to be fixed.
     * @return {string} The fixed link from the specified link.
     */
    getFixedLink: function (link) {
      return link.charAt(0) == "/" ? "https://www.emich.edu" + link : link;
    },

    searchRedirects: function (type) {
      let self = this; // "this" loses scope within Axios.

      let searchTerm = ''
      if(type === 'broken') {
        searchTerm = this.brokenSearchTerm
      } else if(type === 'shortened') {
        searchTerm = this.shortenedSearchTerm
      }

      if(searchTerm.length > 2) {
        /* Ajax (Axios) Submission */
        axios.get(`/api/redirects/search?searchterm=${searchTerm}&type=${type}`)
        .then(function (response) { // Success.
          if(type === 'broken') {
            self.brokenSearchResults = response.data;
          } else {
            self.shortenedSearchResults = response.data;
          }
        })
        .catch(function (error) { // Failure.
          self.apiError.status = error.response.status;

          switch (error.response.status) {
            case 403:
              self.apiError.message = "You do not have sufficient privileges to retrieve redirects.";
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
