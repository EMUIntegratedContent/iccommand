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
      <input name="searchTerm" type="text" class="form-control" placeholder="Search" v-model="searchTerm" @change="filterRedirects"/>
    </div>
    <br/>
    <div id="accordion">
      <div class="card">
        <div class="card-header" id="headingBrokenLinks">
          <h5 class="mb-0">
            <button class="btn btn-link" data-toggle="collapse" data-target="#collapseBrokenLinks" aria-expanded="true" aria-controls="collapseBrokenLinks">
              Broken Links
              <span v-if="!loadingBrokenLinks" class="badge badge-primary">{{ resultedBrokenLinks.length }}</span>
              <span v-else><i class="fa fa-spinner"></i></span>
            </button>
          </h5>
        </div>
        <div id="collapseBrokenLinks" class="collapse show" aria-labelledby="headingBrokenLinks" data-parent="#accordion">
          <div class="card-body">
            <div v-if="!loadingBrokenLinks" class="table-responsive">
              <table class="table table-hover table-sm">
                <thead>
                  <tr>
                    <th scope="col">Broken Links</th>
                    <th scope="col">Actual Links</th>
                    <th scope="col">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="redirect in paginatedBrokenLinks" class="redirectRow">
                    <td><a :href="'https://www.emich.edu' + redirect.fromLink" target="_blank">{{ redirect.fromLink }}</a></td>
                    <td><a :href="getFixedLink(redirect.toLink)" target="_blank">{{ redirect.toLink }}</a></td>
                    <td><a :href="'/redirects/' + redirect.id" v-if="userCanEdit"><i class="fa fa-eye"></i></a></td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div v-else>
              <p style="text-align: center"><img src="/images/loading.gif" alt="Loading..."/></p>
            </div>
            <paginator v-show="!loadingBrokenLinks" :items="resultedBrokenLinks" @itemsPerPageChanged="setPaginatedBrokenLinks"></paginator>
          </div>
        </div>
      </div>
      <div class="card">
        <div class="card-header" id="headingShortenedLinks">
          <h5 class="mb-0">
            <button class="btn btn-link collapsed" data-toggle="collapse" data-target="#collapseShortenedLinks" aria-expanded="false" aria-controls="collapseShortenedLinks">
              Shortened Links
              <span v-if="!loadingShortenedLinks" class="badge badge-primary">{{ resultedShortenedLinks.length }}</span>
              <span v-else><i class="fa fa-spinner"></i></span>
            </button>
          </h5>
        </div>
        <div id="collapseShortenedLinks" class="collapse" aria-labelledby="headingShortenedLinks" data-parent="#accordion">
          <div class="card-body">
            <div v-if="!loadingShortenedLinks" class="table-responsive">
              <table class="table table-hover table-sm">
                <thead>
                  <tr>
                    <th scope="col">Shortened/Vanity Links</th>
                    <th scope="col">Full Links</th>
                    <th scope="col">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="redirect in paginatedShortenedLinks" class="redirectRow" :id="redirect.id">
                    <td><a :href="'https://www.emich.edu' + redirect.fromLink" target="_blank">{{ redirect.fromLink }}</a></td>
                    <td><a :href="getFixedLink(redirect.toLink)" target="_blank">{{ redirect.toLink }}</a></td>
                    <td><a :href="'/redirects/' + redirect.id" v-if="userCanEdit"><i class="fa fa-eye"></i></a></td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div v-else>
              <p style="text-align: center"><img src="/images/loading.gif" alt="Loading..."/></p>
            </div>
            <paginator v-show="!loadingShortenedLinks" :items="resultedShortenedLinks" @itemsPerPageChanged="setPaginatedShortenedLinks"></paginator>
          </div>
        </div>
      </div>
    </div>
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

      /* *************************** Fetched Data *************************** */

      /**
       * The broken links that are fetched when this list is mounted.
       * @type {Array.<string>}
       */
      fetchedBrokenLinks: [],

      /**
       * The shortened links that are fatched when this list is mounted.
       * @type {Array.<string>}
       */
      fetchedShortenedLinks: [],

      /* ************************** Processing Data ************************* */

      /**
       * Is used to check if the list is loading the broken links.
       * @type {boolean}
       */
      loadingBrokenLinks: true,

      /**
       * Is used to check if the list is loading the shortened links.
       * @type {boolean}
       */
      loadingShortenedLinks: true,

      /**
       * The broken links that are paginated.
       * @type {Array.<string>}
       */
      paginatedBrokenLinks: [],

      /**
       * The shortened links that are paginated.
       * @type {Array.<string>}
       */
      paginatedShortenedLinks: [],

      /**
       * The resulted broken links after being filtered by the search term.
       * @type {Array.<string>}
       */
      resultedBrokenLinks: [],

      /**
       * The resulted shortened links after being filtered by the search term.
       * @type {Array.<string>}
       */
      resultedShortenedLinks: [],

      /**
       * The search term/key that filters the broken links and shortened links.
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
     * Fetches the redirects.
     */
    fetchRedirects: function() {
      let self = this;

      axios.get("/api/redirects")
      .then(function(response) { // Success.
        response.data.forEach(function(redirect) {
          // Filter the redirect into their respective categories based on the "itemType" field.
          switch (redirect.itemType) {
            case "broken link":
              self.fetchedBrokenLinks.push(redirect);
              break;
            case "shortened link":
              self.fetchedShortenedLinks.push(redirect);
              break;
          }
        });

        self.resultedBrokenLinks = self.fetchedBrokenLinks.slice();
        self.resultedShortenedLinks = self.fetchedShortenedLinks.slice();

        // Disable any loading flags for empty arrays.
        if (self.resultedBrokenLinks.length == 0) {
          self.loadingBrokenLinks = false;
        }

        if (self.resultedShortenedLinks.length == 0) {
          self.loadingShortenedLinks = false;
        }
      })
      .catch(function(error) { // Failure.
        self.apiError.status = error.response.status;

        switch(error.response.status) {
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

        self.loadingBrokenLinks = false;
        self.loadingShortenedLinks = false;
      });
    },

    /**
     * Filters the rediects based on the search term.
     */
    filterRedirects: function() {
      var filteredBrokenLinks = [];
      var filteredShortenedLinks = [];

      if (this.searchTerm.trim()) {
        for (var i = 0; i < this.fetchedBrokenLinks.length; i++) {
          if (this.fetchedBrokenLinks[i]["fromLink"].toLowerCase().indexOf(this.searchTerm.trim().toLowerCase()) != -1
            || this.fetchedBrokenLinks[i]["toLink"].toLowerCase().indexOf(this.searchTerm.trim().toLowerCase()) != -1) {
            filteredBrokenLinks.push(this.fetchedBrokenLinks[i]);
          }
        }

        for (var i = 0; i < this.fetchedShortenedLinks.length; i++) {
          if (this.fetchedShortenedLinks[i]["fromLink"].toLowerCase().indexOf(this.searchTerm.trim().toLowerCase()) != -1
            || this.fetchedShortenedLinks[i]["toLink"].toLowerCase().indexOf(this.searchTerm.trim().toLowerCase()) != -1) {
            filteredShortenedLinks.push(this.fetchedShortenedLinks[i]);
          }
        }
      }

      this.resultedBrokenLinks = this.searchTerm.trim() ? filteredBrokenLinks.slice() : this.fetchedBrokenLinks.slice();
      this.resultedShortenedLinks = this.searchTerm.trim() ? filteredShortenedLinks.slice() : this.fetchedShortenedLinks.slice();
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
     * Updates the paginated broken links by the specified array of broken links.
     * @param {array} brokenLinks The array of broken links used to update.
     */
    setPaginatedBrokenLinks(brokenLinks) {
      this.loadingBrokenLinks = true; // Show the loading wheel.
      this.paginatedBrokenLinks = brokenLinks; // Set the paginated broken links returned from the child paginator components.
      this.loadingBrokenLinks = false; // Turn off the loading wheel.
    },

    /**
     * Updates the paginated shortened links by the specified array of shortened links.
     * @param {array} shortenedLinks The array of shortened links used to update.
     */
    setPaginatedShortenedLinks(shortenedLinks) {
      this.loadingShortenedLinks = true; // Show the loading wheel.
      this.paginatedShortenedLinks = shortenedLinks; // Set the paginated shortened links returned from the child paginator components.
      this.loadingShortenedLinks = false; // Turn off the loading wheel.
    }
  },

  filters: {}
};
</script>
