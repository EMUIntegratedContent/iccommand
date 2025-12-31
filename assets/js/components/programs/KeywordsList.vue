<template>
  <div>
    <heading>
      <span>Manage Keywords</span>
    </heading>
    <div v-if="apiError.status" class="alert alert-danger fade show" role="alert">
      {{ apiError.message }}
    </div>
    <div>
      <p>
        Create and manage keywords that can be assigned to programs. Keywords can be assigned to programs from the
        individual program forms.
      </p>
    </div>
    <div class="card">
      <div class="card-body">
        <h5 class="card-title">Create New Keyword</h5>
        <div class="form-group">
          <label for="newKeyword">Keyword Name</label>
          <input type="text" class="form-control" id="newKeyword" v-model="newKeywordName"
            placeholder="Enter keyword name" @keyup.enter="createKeyword" />
        </div>
        <button type="button" class="btn btn-primary" @click="createKeyword" :disabled="!newKeywordName || isCreating">
          <span v-if="isCreating">Creating...</span>
          <span v-else>Create Keyword</span>
        </button>
      </div>
    </div>
    <div class="card mt-4">
      <div class="card-body">
        <h5 class="card-title">
          Existing Keywords
          <span v-if="!loadingKeywords" class="badge badge-primary ml-2">{{ totalKeywords }}</span>
          <span v-else class="ml-2"><i class="fa fa-spinner"></i></span>
        </h5>
        <div v-if="!loadingKeywords" class="mb-3">
          <label for="keywordSearch" class="form-label">Search Keywords</label>
          <div class="position-relative">
            <input type="text" class="form-control" id="keywordSearch" v-model="keywordSearchTerm"
              placeholder="Search keywords" @keyup.enter="handleKeywordSearch"
              style="padding-right: 40px;" />
            <button type="button" class="btn btn-link position-absolute"
              style="right: 0; top: 0; height: 100%; border: none; padding: 0 12px;" @click="handleKeywordSearch"
              :disabled="loadingKeywords">
              <i class="fa fa-search"></i>
            </button>
            <button v-if="keywordSearchTerm" type="button" class="btn btn-link position-absolute"
                    style="right: 0; top: 0; height: 100%; border: none; margin: 0 40px;" @click="keywordSearchTerm = ''; handleKeywordSearch()"
                    :disabled="loadingKeywords">
              <i class="fa fa-remove"></i>
            </button>
          </div>
        </div>
        <div v-if="loadingKeywords">
          <p style="text-align: center"><img src="/images/loading.gif" alt="Loading..." /></p>
        </div>
        <div v-else-if="keywords.length === 0" class="alert alert-info">
          No keywords found. Create your first keyword above.
        </div>
        <div v-else class="table-responsive">
          <table class="table table-hover table-sm">
            <thead>
              <tr>
                <th scope="col" style="width: 30px;"></th>
                <th scope="col">Keyword</th>
                <th scope="col">Linked Programs</th>
                <th scope="col">Actions</th>
              </tr>
            </thead>
            <tbody>
              <template v-for="keyword in keywords" :key="keyword.id">
                <tr>
                  <td>
                    <button type="button" class="btn btn-sm btn-link p-0" @click="toggleKeywordExpanded(keyword.id)"
                      :aria-expanded="expandedKeywords[keyword.id] ? 'true' : 'false'">
                      <i :class="expandedKeywords[keyword.id] ? 'fa fa-chevron-down' : 'fa fa-chevron-right'"></i>
                    </button>
                  </td>
                  <td>{{ keyword.keyword }}</td>
                  <td>
                    <span v-if="keywordPrograms[keyword.id]">
                      {{ keywordPrograms[keyword.id].length }} program(s)
                    </span>
                    <span v-else>
                      {{ keyword.program_count || 0 }} program(s)
                    </span>
                  </td>
                  <td>
                    <button v-if="userCanDelete" type="button" class="btn btn-danger btn-sm"
                      @click="deleteKeyword(keyword.id)" :disabled="isDeleting === keyword.id">
                      <span v-if="isDeleting === keyword.id">Deleting...</span>
                      <span v-else><i class="fa fa-trash"></i> Delete</span>
                    </button>
                  </td>
                </tr>
                <tr v-if="expandedKeywords[keyword.id]">
                  <td colspan="4" class="p-3 bg-light">
                    <div v-if="loadingPrograms[keyword.id]" class="text-center">
                      <img src="/images/loading.gif" alt="Loading..." />
                      <div class="mt-2 text-muted">Loading programs (expecting {{ keyword.program_count || 0 }})</div>
                    </div>
                    <div v-else>
                      <div v-if="userCanEdit" class="mb-3">
                        <label :for="'programSearch-' + keyword.id" class="form-label">Link Program to Keyword</label>
                        <VueMultiselect :id="'programSearch-' + keyword.id"
                          :options="programSearchResults[keyword.id] || []" :multiple="false" :clear-on-select="true"
                          placeholder="Search programs (type at least 3 characters)" label="fullName" track-by="id"
                          :searchable="true" :internal-search="false"
                          @search-change="handleProgramSearchInput(keyword.id, $event)"
                          @select="handleProgramSelected(keyword.id, $event)">
                        </VueMultiselect>
                      </div>
                      <div v-if="keywordPrograms[keyword.id] && keywordPrograms[keyword.id].length > 0">
                        <h6>Linked Programs:</h6>
                        <ul class="list-group">
                          <li v-for="program in keywordPrograms[keyword.id]" :key="program.id"
                            class="list-group-item d-flex justify-content-between align-items-center">
                            <span>
                              <strong>{{ program.full_name }}</strong>
                              <small class="text-muted ml-2">({{ program.catalog }})</small>
                            </span>
                            <button v-if="userCanEdit" type="button" class="btn btn-sm btn-outline-danger"
                              @click="unlinkProgram(keyword.id, program.id)"
                              :disabled="unlinkingPrograms[keyword.id + '-' + program.id]">
                              <span v-if="unlinkingPrograms[keyword.id + '-' + program.id]">Unlinking...</span>
                              <span v-else><i class="fa fa-unlink"></i> Unlink</span>
                            </button>
                          </li>
                        </ul>
                      </div>
                      <div v-else class="alert alert-info mb-0">
                        No programs linked to this keyword.
                      </div>
                    </div>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
        <external-paginator v-show="!loadingKeywords && keywords.length > 0" :ext-curr-pg="keywordsCurrentPage"
          :ext-items-per-pg="keywordsItemsPerPage" :total-recs="totalKeywords" :items="keywords"
          @itemsPerPageChanged="handleKeywordsItemsPerPageChanged" @pageChanged="handleKeywordsPageChanged">
        </external-paginator>
      </div>
    </div>
  </div>
</template>

<style src="vue-multiselect/dist/vue-multiselect.css"></style>

<script>
import Heading from "../utils/Heading.vue";
import ExternalPaginator from "../utils/ExternalPaginator.vue";
import VueMultiselect from 'vue-multiselect';

export default {
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
      loadingKeywords: true,
      keywords: [],
      newKeywordName: "",
      isCreating: false,
      isDeleting: null,
      expandedKeywords: {},
      keywordPrograms: {},
      loadingPrograms: {},
      programSearchResults: {},
      programSearchTerms: {},
      searchTimeouts: {},
      unlinkingPrograms: {},
      // Pagination data
      keywordsCurrentPage: 1,
      keywordsItemsPerPage: 50,
      totalKeywords: 0,
      // Search data
      keywordSearchTerm: ''
    };
  },
  mounted() {
    this.fetchKeywords();
  },
  computed: {
    headingIcon: function () {
      return "<i class='fa fa-tags'></i>";
    },
    userCanDelete: function () {
      return !!(this.permissions && this.permissions[0] && this.permissions[0].delete);
    },
    userCanCreate: function () {
      return !!(this.permissions && this.permissions[0] && this.permissions[0].create);
    },
    userCanEdit: function () {
      return !!(this.permissions && this.permissions[0] && this.permissions[0].edit);
    }
  },
  methods: {
    fetchKeywords: function () {
      this.loadingKeywords = true;
      let url = `/api/programs/keywords?page=${this.keywordsCurrentPage}&limit=${this.keywordsItemsPerPage}&searchterm=${encodeURIComponent(this.keywordSearchTerm)}`;

      axios.get(url)
        .then((response) => {
          if (response.data.keywords && response.data.totalRows !== undefined) {
            // Paginated response
            this.keywords = response.data.keywords;
            this.totalKeywords = response.data.totalRows;
          } else {
            // Fallback for non-paginated response (backward compatibility)
            this.keywords = Array.isArray(response.data) ? response.data : [];
            this.totalKeywords = this.keywords.length;
          }
          this.loadingKeywords = false;
        })
        .catch((error) => {
          this.apiError.status = error.response ? error.response.status : 500;
          switch (this.apiError.status) {
            case 403:
              this.apiError.message = "You do not have sufficient privileges to retrieve keywords.";
              break;
            case 404:
              this.apiError.message = "Keywords were not found.";
              break;
            case 500:
              this.apiError.message = "An internal error occurred.";
              break;
            default:
              this.apiError.message = "An error occurred.";
              break;
          }
          this.loadingKeywords = false;
        });
    },
    createKeyword: function () {
      if (!this.newKeywordName || this.isCreating) {
        return;
      }

      this.isCreating = true;
      this.apiError.status = null;

      axios.post("/api/programs/keywords", {
        keyword: this.newKeywordName
      })
        .then((response) => {
          this.newKeywordName = "";
          this.isCreating = false;
          // Refresh the keywords list to show the new keyword
          this.fetchKeywords();
        })
        .catch((error) => {
          this.apiError.status = error.response ? error.response.status : 500;
          switch (this.apiError.status) {
            case 403:
              this.apiError.message = "You do not have sufficient privileges to create keywords.";
              break;
            case 422:
              this.apiError.message = error.response.data || "Invalid keyword name.";
              break;
            case 500:
              this.apiError.message = "An internal error occurred.";
              break;
            default:
              this.apiError.message = "An error occurred.";
              break;
          }
          this.isCreating = false;
        });
    },
    deleteKeyword: function (id) {
      if (!confirm("Are you sure you want to delete this keyword? It will be removed from all programs.")) {
        return;
      }

      this.isDeleting = id;
      this.apiError.status = null;

      axios.delete(`/api/programs/keywords/${id}`)
        .then(() => {
          this.isDeleting = null;
          // Clean up expanded state and programs data
          delete this.expandedKeywords[id];
          delete this.keywordPrograms[id];
          delete this.loadingPrograms[id];
          // Check if current page becomes empty and adjust if needed
          const remainingOnPage = this.keywords.length - 1;
          if (remainingOnPage === 0 && this.keywordsCurrentPage > 1) {
            this.keywordsCurrentPage = this.keywordsCurrentPage - 1;
          }
          // Refresh the keywords list
          this.fetchKeywords();
        })
        .catch((error) => {
          this.apiError.status = error.response ? error.response.status : 500;
          switch (this.apiError.status) {
            case 403:
              this.apiError.message = "You do not have sufficient privileges to delete keywords.";
              break;
            case 404:
              this.apiError.message = "Keyword not found.";
              break;
            case 500:
              this.apiError.message = "An internal error occurred.";
              break;
            default:
              this.apiError.message = "An error occurred.";
              break;
          }
          this.isDeleting = null;
        });
    },
    toggleKeywordExpanded: function (keywordId) {
      // Toggle expanded state using Vue reactivity helpers
      let current = !!this.expandedKeywords[keywordId];
      this.expandedKeywords[keywordId] = !current;
      if (this.expandedKeywords[keywordId] && !this.keywordPrograms[keywordId]) {
        this.fetchKeywordPrograms(keywordId);
      }
    },
    fetchKeywordPrograms: function (keywordId) {
      this.loadingPrograms[keywordId] = true;

      axios.get(`/api/programs/keywords/${keywordId}/programs`)
        .then((response) => {
          this.keywordPrograms[keywordId] = response.data;

          // keep keyword.program_count in sync with authoritative list
          const keyword = this.keywords.find(k => k.id === keywordId);
          if (keyword) {
            keyword.program_count = Array.isArray(response.data) ? response.data.length : 0;
          }

          this.loadingPrograms[keywordId] = false;
        })
        .catch((error) => {
          this.apiError.status = error.response ? error.response.status : 500;
          switch (this.apiError.status) {
            case 403:
              this.apiError.message = "You do not have sufficient privileges to retrieve programs.";
              break;
            case 404:
              this.apiError.message = "Keyword not found.";
              break;
            case 500:
              this.apiError.message = "An internal error occurred.";
              break;
            default:
              this.apiError.message = "An error occurred.";
              break;
          }
          this.loadingPrograms[keywordId] = false;
        });
    },
    handleProgramSearchInput: function (keywordId, searchTerm) {
      // VueMultiselect @search-change passes the search term directly as a string
      this.programSearchTerms[keywordId] = searchTerm || '';

      // Clear existing timeout
      if (this.searchTimeouts[keywordId]) {
        clearTimeout(this.searchTimeouts[keywordId]);
      }

      // Only search if term is at least 3 characters
      if (!searchTerm || searchTerm.length < 3) {
        this.programSearchResults[keywordId] = [];
        return;
      }

      // Debounce search
      this.searchTimeouts[keywordId] = setTimeout(() => {
        this.searchPrograms(keywordId, searchTerm);
      }, 500);
    },
    searchPrograms: function (keywordId, searchTerm) {

      // Search both catalogs
      Promise.all([
        axios.get(`/api/programs/search?searchterm=${encodeURIComponent(searchTerm)}&catalog=undergraduate`),
        axios.get(`/api/programs/search?searchterm=${encodeURIComponent(searchTerm)}&catalog=graduate`)
      ])
        .then((responses) => {
          let allPrograms = [];
          responses.forEach((response) => {
            if (response.data) {
              // Handle both array and single object responses
              let programs = Array.isArray(response.data) ? response.data : [response.data];
              allPrograms = allPrograms.concat(programs);
            }
          });
          // Filter out programs already linked to this keyword
          let linkedProgramIds = (this.keywordPrograms[keywordId] || []).map(p => p.id);
          allPrograms = allPrograms.filter(p => p && p.id && !linkedProgramIds.includes(p.id));
          this.programSearchResults[keywordId] = allPrograms;
        })
        .catch((error) => {
          this.apiError.status = error.response ? error.response.status : 500;
          this.apiError.message = "Error searching programs.";
          this.programSearchResults[keywordId] = [];
        });
    },
    handleProgramSelected: function (keywordId, program) {
      if (!program || !program.id) {
        return;
      }
      this.linkProgram(keywordId, program.id);
    },
    linkProgram: function (keywordId, programId) {
      this.apiError.status = null;

      axios.post(`/api/programs/keywords/${keywordId}/programs`, {
        program_id: programId
      })
        .then(() => {
          // Refresh the programs list for this keyword
          this.fetchKeywordPrograms(keywordId);
          // Clear search
          this.programSearchResults[keywordId] = [];
          this.programSearchTerms[keywordId] = '';
        })
        .catch((error) => {
          this.apiError.status = error.response ? error.response.status : 500;
          switch (this.apiError.status) {
            case 403:
              this.apiError.message = "You do not have sufficient privileges to link programs.";
              break;
            case 404:
              this.apiError.message = error.response.data || "Keyword or program not found.";
              break;
            case 422:
              this.apiError.message = error.response.data || "Invalid request.";
              break;
            case 500:
              this.apiError.message = "An internal error occurred.";
              break;
            default:
              this.apiError.message = "An error occurred.";
              break;
          }
        });
    },
    unlinkProgram: function (keywordId, programId) {
      if (!confirm("Are you sure you want to unlink this program from the keyword?")) {
        return;
      }

      let key = keywordId + '-' + programId;
      this.unlinkingPrograms[key] = true;
      this.apiError.status = null;

      axios.delete(`/api/programs/keywords/${keywordId}/programs/${programId}`)
        .then(() => {
          // Refresh the programs list for this keyword
          this.fetchKeywordPrograms(keywordId);
          this.unlinkingPrograms[key] = false;
        })
        .catch((error) => {
          this.apiError.status = error.response ? error.response.status : 500;
          switch (this.apiError.status) {
            case 403:
              this.apiError.message = "You do not have sufficient privileges to unlink programs.";
              break;
            case 404:
              this.apiError.message = error.response.data || "Keyword or program not found.";
              break;
            case 500:
              this.apiError.message = "An internal error occurred.";
              break;
            default:
              this.apiError.message = "An error occurred.";
              break;
          }
          this.unlinkingPrograms[key] = false;
        });
    },
    handleKeywordsPageChanged: function (currentPage) {
      this.keywordsCurrentPage = currentPage;
      this.fetchKeywords();
    },
    handleKeywordsItemsPerPageChanged: function (itemsPerPage) {
      this.keywordsItemsPerPage = itemsPerPage;
      this.keywordsCurrentPage = 1; // Reset to first page when changing items per page
      this.fetchKeywords();
    },
    handleKeywordSearch: function () {
      // Reset to first page when searching
      this.keywordsCurrentPage = 1;
      // Fetch keywords with current search term
      this.fetchKeywords();
      // Set focus back to the keyword search input
      document.getElementById('keywordSearch').focus();
    }
  }
};
</script>
