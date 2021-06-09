<template>
  <div>
    <heading>
      <span slot="icon" v-html="headingIcon">{{ headingIcon }}</span>
      <span slot="title">Redirect Items</span>
    </heading>
    <div v-if="apiError.status" class="alert alert-danger fade show" role="alert">
      {{ apiError.message }}
    </div>
    <div>
      <input
        name="searchTerm"
        type="text"
        class="form-control"
        placeholder="Search"
        v-model="searchTerm"
        @change="filterRedirects"/>
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
                class="badge badge-primary">{{ resultedRedirectsOfBrokenLinks.length }}</span>
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
                  <tr v-for="redirect in paginatedRedirectsOfBrokenLinks" :id="redirect.id">
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
            <paginator
              v-show="!loadingRedirectsOfBrokenLinks"
              :items="resultedRedirectsOfBrokenLinks"
              @itemsPerPageChanged="setPaginatedRedirectsOfBrokenLinks"></paginator>
          </div>
        </div>
      </div>
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
                class="badge badge-primary">{{ resultedRedirectsOfShortenedLinks.length }}</span>
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
                  <tr v-for="redirect in paginatedRedirectsOfShortenedLinks" :id="redirect.id">
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
            <paginator
              v-show="!loadingRedirectsOfShortenedLinks"
              :items="resultedRedirectsOfShortenedLinks"
              @itemsPerPageChanged="setPaginatedRedirectsOfShortenedLinks"></paginator>
          </div>
        </div>
      </div>
      <!-- Expired Redirects Section -->
      <!--
        <div class="card">
          <div class="card-header" id="headingExpiredRedirects">
            <h5 class="mb-0">
              <button
                class="btn btn-link collapsed"
                data-toggle="collapse"
                data-target="#collapseExpiredRedirects"
                aria-expanded="false"
                aria-controls="collapseInvalidRedirects">
                Expired Redirects
                <span
                  v-if="!loadingExpiredRedirects"
                  class="badge badge-primary">{{ resultedExpiredRedirects.length }}</span>
                <span v-else><i class="fa fa-spinner"></i></span>
              </button>
            </h5>
          </div>
          <div
            id="collapseExpiredRedirects"
            class="collapse"
            aria-labelledby="headingExpiredRedirects"
            data-parent="#accordion">
            <div class="card-body">
              <div v-if="!loadingExpiredRedirects" class="table-responsive">
                <table class="table table-hover table-sm">
                  <thead>
                    <tr>
                      <th scope="col">Redirect Links</th>
                      <th scope="col">Actual Links</th>
                      <th scope="col">Last Visit</th>
                      <th scope="col">Visits</th>
                      <th scope="col">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="redirect in paginatedExpiredRedirects" :id="redirect.id">
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
              <paginator
                v-show="!loadingExpiredRedirects"
                :items="resultedExpiredRedirects"
                @itemsPerPageChanged="setPaginatedExpiredRedirects"></paginator>
            </div>
          </div>
        </div>
      -->
      <!-- Invalid Redirects Section -->
      <!--
        <div class="card">
          <div class="card-header" id="headingInvalidRedirects">
            <h5 class="mb-0">
              <button
                class="btn btn-link collapsed"
                data-toggle="collapse"
                data-target="#collapseInvalidRedirects"
                aria-expanded="false"
                aria-controls="collapseInvalidRedirects">
                Invalid Redirects
                <span
                  v-if="!loadingInvalidRedirects"
                  class="badge badge-primary">{{ resultedInvalidRedirects.length }}</span>
                <span v-else><i class="fa fa-spinner"></i></span>
              </button>
            </h5>
          </div>
          <div
            id="collapseInvalidRedirects"
            class="collapse"
            aria-labelledby="headingInvalidRedirects"
            data-parent="#accordion">
            <div class="card-body">
              <div v-if="!loadingInvalidRedirects" class="table-responsive">
                <table class="table table-hover table-sm">
                  <thead>
                    <tr>
                      <th scope="col">Redirect Links</th>
                      <th scope="col">Actual Links</th>
                      <th scope="col">Last Visit</th>
                      <th scope="col">Visits</th>
                      <th scope="col">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="redirect in paginatedInvalidRedirects" :id="redirect.id">
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
              <paginator
                v-show="!loadingInvalidRedirects"
                :items="resultedInvalidRedirects"
                @itemsPerPageChanged="setPaginatedInvalidRedirects"></paginator>
            </div>
          </div>
        </div>
      -->
    </div><br/>
    <!-- Warning Message -->
    <div v-if="emptyRedirects" class="alert alert-danger fade show" role="alert">
      There are no {{ emptyRedirects }} redirects to delete.
    </div>
    <!-- Action Buttons -->
    <!--
    <div v-if="userCanEdit" aria-label="action buttons" class="mb-4">
      <button
        type="button"
        class="btn btn-success"
        @click="checkForExpiredRedirects()">Find Expired Redirects</button>
      <button
        type="button"
        class="btn btn-success"
        @click="checkForInvalidRedirects()">Find Invalid Redirects</button>
      <button
        type="button"
        class="btn btn-danger"
        @click="deleteRedirects('expired')">Delete All Expired Redirects</button>
      <button
        type="button"
        class="btn btn-danger"
        @click="deleteRedirects('invalid')">Delete All Invalid Redirects</button>
    </div>
    -->
  </div>
</template>

<style></style>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script>
import Heading from "../utils/Heading.vue";
import Paginator from "../utils/Paginator.vue";

export default {
  created() {},

  mounted() {
    this.fetchRedirects();

    console.log("Redirect list mounted.");
  },

  components: {Heading, Paginator},

  props: {
    permissions: {
      type: Array,
      required: true
    }
  },

  data: function() {
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

      /**
       * This is used to show a message to the user if a specific group of
       * redirects is empty.
       * @type {string}
       */
      emptyRedirects: "",

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
       * The expired redirects that are paginated.
       * @type {Array.<Redirect>}
       */
      paginatedExpiredRedirects: [],

      /**
       * The redirects of the broken links that are paginated.
       * @type {Array.<Redirect>}
       */
      paginatedRedirectsOfBrokenLinks: [],

      /**
       * The redirects of the shortened links that are paginated.
       * @type {Array.<Redirect>}
       */
      paginatedRedirectsOfShortenedLinks: [],

      /**
       * The invalid redirects that are paginated.
       * @type {Array.<Redirect>}
       */
      paginatedInvalidRedirects: [],

      /**
       * The resulted expired redirects after being filtered by the search term.
       * @type {Array.<Redirect>}
       */
      resultedExpiredRedirects: [],

      /**
       * The resulted redirects of the broken links after being filtered by the
       * search term.
       * @type {Array.<Redirect>}
       */
      resultedRedirectsOfBrokenLinks: [],

      /**
       * The resulted redirects of the shortened links after being filtered by
       * the search term.
       * @type {Array.<Redirect>}
       */
      resultedRedirectsOfShortenedLinks: [],

      /**
       * The resulted invalid redirects after being filtered by the search term.
       * @type {Array.<Redirect>}
       */
      resultedInvalidRedirects: [],

      /**
       * The search term/key that filters the redirects.
       * @type {string}
       */
      searchTerm: ""
    };
  },

  computed: {
    /**
     * Gets the heading icon.
     * @return {string} The heading icon.
     */
    headingIcon: function() {
      return "<i class='fa fa-list'></i>";
    },

    /**
     * Determines if the user can create.
     * @return {boolean} True if the user can create; false otherwise.
     */
    userCanCreate: function() {
      return this.permissions[0].user ? true : false;
    },

    /**
     * Determines if the user can edit.
     * @return {boolean} True if the user can edit; false otherwise.
     */
    userCanEdit: function() {
      return this.permissions[0].user ? true : false;
    }
  },

  methods: {
    /**
     * Updates the redirects by checking if each one has not been used for more
     * than six months.
     */
    checkForExpiredRedirects: function() {
      console.log("Checking for expired redirects.");

      this.turnOnLoadingWheels();
      let self = this; // "this" loses scope within Axios and setTimeout function.

      for (var i = 0; i < this.fetchedRedirectsOfBrokenLinks.length; i++) {
        if (Date.now() - Date.parse(this.fetchedRedirectsOfBrokenLinks[i].lastVisit) > 15552000000) {
          // There is 15552000000 milliseconds in six months.
          // If a redirect has not been used for more than six months, it is now expired.
          this.updateExpiredRedirect(this.fetchedRedirectsOfBrokenLinks[i]);
        }
      }

      for (var i = 0; i < this.fetchedRedirectsOfShortenedLinks.length; i++) {
        if (Date.now() - Date.parse(this.fetchedRedirectsOfShortenedLinks[i].lastVisit) > 15552000000) {
          // There is 15552000000 milliseconds in six months.
          // If a redirect has not been used for more than six months, it is now expired.
          this.updateExpiredRedirect(this.fetchedRedirectsOfShortenedLinks[i]);
        }
      }

      setTimeout(function() {
        self.fetchRedirects(); // Get the redirects again after marking the expired ones.
      }, 3000);
    },

    /**
     * Updates the redirects by checking if each toLink field is still a valid
     * URL.
     */
    checkForInvalidRedirects: function() {
      console.log("Checking for invalid redirects.");

      this.turnOnLoadingWheels();
      let self = this; // "this" loses scope within Axios.

      /* Ajax (Axios) Submission */
      axios({
        method: "put",
        url: "/api/redirects",
        data: []
      })
      .then(function(response) { // Success.
        self.fetchRedirects(); // Get the redirects again after updating them.
      })
      .catch(function(error) { // Failure.
        let errors = error.response.data;

        // Add any validation errors to the Vue validator error bag.
        errors.forEach(function(error) {
          let key = error.property_path;
          let message = error.message;
          self.$validator.errors.add(key, message);
        });

        self.turnOffLoadingWheels();
      })
    },

    /**
     * Deletes either the expired redirects or the invalid redirects specified
     * by the item type.
     * @param {string} itemType The item type of the redirects to be deleted.
     */
    deleteRedirects: function(itemType) {
      console.log("Deleting all " + itemType + " redirects.");

      this.turnOnLoadingWheels();
      this.emptyRedirects = "";
      var redirects = itemType == "expired" ? this.fetchedExpiredRedirects : this.fetchedInvalidRedirects;
      let self = this; // "this" loses scope within Axios and setTimeout function.

      if (redirects.length == 0) {
        this.emptyRedirects = itemType;
        this.turnOffLoadingWheels();

        // Remove the message after three seconds.
        setTimeout(function() {
          self.emptyRedirects = "";
        }, 3000);
      } else {
        for (var i = 0; i < redirects.length; i++) {
          /* Ajax (Axios) Submission */
          axios.delete("/api/redirects/" + redirects[i].id)
          .then(function(response) { // Success.

          })
          .catch(function(error) { // Failure.
            let errors = error.response.data;

            // Add any validation errors to the Vue validator error bag.
            errors.forEach(function(error) {
              let key = error.property_path;
              let message = error.message;
              self.$validator.errors.add(key, message);
            });
          });
        }

        setTimeout(function() {
          self.fetchRedirects(); // Get the redirects again after deleting the expired ones.
        }, 3000);
      }
    },

    /**
     * Gets the redirects.
     */
    fetchRedirects: function() {
      console.log("Fetching redirects.");

      this.turnOnLoadingWheels();
      this.fetchedRedirectsOfBrokenLinks = [];
      this.fetchedRedirectsOfShortenedLinks = [];
      this.fetchedExpiredRedirects = [];
      this.fetchedInvalidRedirects = [];
      let self = this; // "this" loses scope within Axios.

      /* Ajax (Axios) Submission */
      axios.get("/api/redirects")
      .then(function(response) { // Success.
        response.data.forEach(function(redirect) {
          // Filter the redirect into their respective categories based on the itemType field.
          switch (redirect.itemType) {
            case "redirect of broken link":
              self.fetchedRedirectsOfBrokenLinks.push(redirect);
              break;
            case "redirect of shortened link":
              self.fetchedRedirectsOfShortenedLinks.push(redirect);
              break;
            case "expired redirect of broken link":
            case "expired redirect of shortened link":
              self.fetchedExpiredRedirects.push(redirect);
              break;
            case "invalid redirect of broken link":
            case "invalid redirect of shortened link":
              self.fetchedInvalidRedirects.push(redirect);
              break;
          }
        });

        self.resultedRedirectsOfBrokenLinks = self.fetchedRedirectsOfBrokenLinks.slice();
        self.resultedRedirectsOfShortenedLinks = self.fetchedRedirectsOfShortenedLinks.slice();
        self.resultedExpiredRedirects = self.fetchedExpiredRedirects.slice();
        self.resultedInvalidRedirects = self.fetchedInvalidRedirects.slice();

        // Disable any loading flags for empty arrays.
        if (self.resultedRedirectsOfBrokenLinks.length == 0) {
          self.loadingRedirectsOfBrokenLinks = false;
        }

        if (self.resultedRedirectsOfShortenedLinks.length == 0) {
          self.loadingRedirectsOfShortenedLinks = false;
        }

        if (self.resultedExpiredRedirects.length == 0) {
          self.loadingExpiredRedirects = false;
        }

        if (self.resultedInvalidRedirects.length == 0) {
          self.loadingInvalidRedirects = false;
        }
      })
      .catch(function(error) { // Failure.
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

        self.turnOffLoadingWheels();
      });
    },

    /**
     * Filters the rediects based on the search term.
     */
    filterRedirects: function() {
      var filteredRedirectsOfBrokenLinks = [];
      var filteredRedirectsOfShortenedLinks = [];
      var filteredExpiredRedirects = [];
      var filteredInvalidRedirects = [];

      if (this.searchTerm.trim()) {
        for (var i = 0; i < this.fetchedRedirectsOfBrokenLinks.length; i++) {
          if (this.fetchedRedirectsOfBrokenLinks[i]["fromLink"].toLowerCase().indexOf(this.searchTerm.trim().toLowerCase()) != -1
            || this.fetchedRedirectsOfBrokenLinks[i]["toLink"].toLowerCase().indexOf(this.searchTerm.trim().toLowerCase()) != -1) {
            filteredRedirectsOfBrokenLinks.push(this.fetchedRedirectsOfBrokenLinks[i]);
          }
        }

        for (var i = 0; i < this.fetchedRedirectsOfShortenedLinks.length; i++) {
          if (this.fetchedRedirectsOfShortenedLinks[i]["fromLink"].toLowerCase().indexOf(this.searchTerm.trim().toLowerCase()) != -1
            || this.fetchedRedirectsOfShortenedLinks[i]["toLink"].toLowerCase().indexOf(this.searchTerm.trim().toLowerCase()) != -1) {
            filteredRedirectsOfShortenedLinks.push(this.fetchedRedirectsOfShortenedLinks[i]);
          }
        }

        for (var i = 0; i < this.fetchedExpiredRedirects.length; i++) {
          if (this.fetchedExpiredRedirects[i]["fromLink"].toLowerCase().indexOf(this.searchTerm.trim().toLowerCase()) != -1
            || this.fetchedExpiredRedirects[i]["toLink"].toLowerCase().indexOf(this.searchTerm.trim().toLowerCase()) != -1) {
            filteredExpiredRedirects.push(this.fetchedExpiredRedirects[i]);
          }
        }

        for (var i = 0; i < this.fetchedInvalidRedirects.length; i++) {
          if (this.fetchedInvalidRedirects[i]["fromLink"].toLowerCase().indexOf(this.searchTerm.trim().toLowerCase()) != -1
            || this.fetchedInvalidRedirects[i]["toLink"].toLowerCase().indexOf(this.searchTerm.trim().toLowerCase()) != -1) {
            filteredInvalidRedirects.push(this.fetchedInvalidRedirects[i]);
          }
        }
      }

      this.resultedRedirectsOfBrokenLinks = this.searchTerm.trim()
        ? filteredRedirectsOfBrokenLinks.slice() : this.fetchedRedirectsOfBrokenLinks.slice();
      this.resultedRedirectsOfShortenedLinks = this.searchTerm.trim()
        ? filteredRedirectsOfShortenedLinks.slice() : this.fetchedRedirectsOfShortenedLinks.slice();
      this.resultedExpiredRedirects = this.searchTerm.trim()
        ? filteredExpiredRedirects.slice() : this.fetchedExpiredRedirects.slice();
      this.resultedInvalidRedirects = this.searchTerm.trim()
        ? filteredInvalidRedirects.slice() : this.fetchedInvalidRedirects.slice();
    },

    /**
     * Formats the specified date to the "MMM DD, YYYY" form.
     * @param {string} date The specified date to be formatted.
     * @return {string} If the date is null, return "N/A"; the formatted date otherwise.
     */
    formatDate: function(date) {
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
      } else {
        return "N/A";
      }
    },

    /**
     * Obtains the fixed link by the specified link based if the link is an
     * Eastern Michigan University link.
     * @param {string} link The link to be fixed.
     * @return {string} The fixed link from the specified link.
     */
    getFixedLink: function(link) {
      return link.charAt(0) == "/" ? "https://www.emich.edu" + link : link;
    },

    /**
     * Updates the paginated expired redirects by the specified array of expired redirects.
     * @param {Array.<Redirect>} expiredRedirects The array of expired redirects used to update.
     */
    setPaginatedExpiredRedirects: function(expiredRedirects) {
      this.loadingExpiredRedirects = true; // Show the loading wheel.
      this.paginatedExpiredRedirects = expiredRedirects; // Set the paginated expired redirects returned from the child paginator components.
      this.loadingExpiredRedirects = false; // Turn off the loading wheel.
    },

    /**
     * Updates the paginated broken links by the specified array of broken links.
     * @param {Array.<Redirect>} redirectsOfBrokenLinks The array of broken links used to update.
     */
    setPaginatedRedirectsOfBrokenLinks: function(redirectsOfBrokenLinks) {
      this.loadingRedirectsOfBrokenLinks = true; // Show the loading wheel.
      this.paginatedRedirectsOfBrokenLinks = redirectsOfBrokenLinks; // Set the paginated broken links returned from the child paginator components.
      this.loadingRedirectsOfBrokenLinks = false; // Turn off the loading wheel.
    },

    /**
     * Updates the paginated shortened links by the specified array of shortened links.
     * @param {Array.<Redirect>} redirectsOfShortenedLinks The array of shortened links used to update.
     */
    setPaginatedRedirectsOfShortenedLinks: function(redirectsOfShortenedLinks) {
      this.loadingRedirectsOfShortenedLinks = true; // Show the loading wheel.
      this.paginatedRedirectsOfShortenedLinks = redirectsOfShortenedLinks; // Set the paginated shortened links returned from the child paginator components.
      this.loadingRedirectsOfShortenedLinks = false; // Turn off the loading wheel.
    },

    /**
     * Updates the paginated invalid redirects by the specified array of invalid redirects.
     * @param {Array.<Redirect>} invalidRedirects The array of invalid redirects used to update.
     */
    setPaginatedInvalidRedirects: function(invalidRedirects) {
      this.loadingInvalidRedirects = true; // Show the loading wheel.
      this.paginatedInvalidRedirects = invalidRedirects; // Set the paginated invalid redirects returned from the child paginator components.
      this.loadingInvalidRedirects = false; // Turn off the loading wheel.
    },

    /**
     * Sets the loading variables to false to hide all loading wheels.
     */
    turnOffLoadingWheels: function() {
      this.loadingRedirectsOfBrokenLinks = false;
      this.loadingRedirectsOfShortenedLinks = false;
      this.loadingExpiredRedirects = false;
      this.loadingInvalidRedirects = false;
    },

    /**
     * Sets the loading variables to true to show all loading wheels.
     */
    turnOnLoadingWheels: function() {
      this.loadingRedirectsOfBrokenLinks = true;
      this.loadingRedirectsOfShortenedLinks = true;
      this.loadingExpiredRedirects = true;
      this.loadingInvalidRedirects = true;
    },

    /**
     * Updates the specified redirect to an expired redirect.
     * @param {Redirect} redirect The redirect to be updated as an expired redirect.
     */
    updateExpiredRedirect: function(redirect) {
      let self = this; // "this" loses scope within Axios.

      /* Ajax (Axios) Submission */
      axios({
        method: "put",
        url: "/api/redirect",
        data: {
          id: redirect.id,
          fromLink: redirect.fromLink,
          toLink: redirect.toLink,
          itemType: "expired " + redirect.itemType
        }
      })
      .then(function(response) { // Success.

      })
      .catch(function(error) { // Failure.
        let errors = error.response.data;

        // Add any validation errors to the Vue validator error bag.
        errors.forEach(function(error) {
          let key = error.property_path;
          let message = error.message;
          self.$validator.errors.add(key, message);
        });

        self.turnOffLoadingWheels();
      });
    }
  },

  filters: {}
};
</script>
