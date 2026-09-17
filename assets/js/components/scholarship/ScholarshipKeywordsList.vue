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
        Create and manage keywords that can be assigned to scholarships. Keywords can be assigned to scholarships from the
        individual scholarship forms.
      </p>
    </div>
    <div class="row">
      <div v-if="userCanCreate" class="col-md-6 mb-3">
        <div class="card h-100">
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
      </div>
      <div v-if="userCanCreate" class="col-md-6 mb-3">
        <div class="card h-100">
          <div class="card-body">
            <h5 class="card-title">Bulk Upload Keywords</h5>
            <div class="text-muted mb-2">
              Upload a CSV with columns <code>keyword</code> (required) and
              <code>scholarship_id</code> (optional). Existing keywords are skipped. <br><br>
              <p><a href="/bulk_scholarship_keyword_template.csv" download>Download Sample Template [CSV]</a>.</p>
            </div>
            <div v-if="uploadStatus === 1" style="text-align: center">
              <img src="/images/loading.gif" alt="Uploading..." />
            </div>
            <div v-else>
              <div class="form-group">
                <input type="file" class="form-control-file" accept=".csv" ref="csvInput"
                  @change="onCsvFileChange" />
              </div>
              <button type="button" class="btn btn-primary" @click="uploadCsv" :disabled="!csvFile">
                Upload CSV
              </button>
            </div>
            <div v-if="uploadStatus === 2" class="alert alert-success mt-3" v-html="uploadMessage"></div>
            <div v-if="uploadStatus === 3" class="alert alert-danger mt-3">{{ uploadMessage }}</div>
          </div>
        </div>
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
                <th scope="col">Linked Scholarships</th>
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
                    <span v-if="keywordScholarships[keyword.id]">
                      {{ keywordScholarships[keyword.id].length }} scholarship(s)
                    </span>
                    <span v-else>
                      {{ keyword.scholarship_count || 0 }} scholarship(s)
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
                    <div v-if="loadingScholarships[keyword.id]" class="text-center">
                      <img src="/images/loading.gif" alt="Loading..." />
                      <div class="mt-2 text-muted">Loading scholarships (expecting {{ keyword.scholarship_count || 0 }})</div>
                    </div>
                    <div v-else>
                      <div v-if="userCanEdit" class="mb-3">
                        <label :for="'scholarshipSearch-' + keyword.id" class="form-label">Link Scholarship to Keyword</label>
                        <VueMultiselect :id="'scholarshipSearch-' + keyword.id"
                          :options="scholarshipSearchResults[keyword.id] || []" :multiple="false" :clear-on-select="true"
                          placeholder="Search scholarships (type at least 2 characters)" label="title" track-by="id"
                          :searchable="true" :internal-search="false"
                          @search-change="handleScholarshipSearchInput(keyword.id, $event)"
                          @select="handleScholarshipSelected(keyword.id, $event)">
                        </VueMultiselect>
                      </div>
                      <div v-if="keywordScholarships[keyword.id] && keywordScholarships[keyword.id].length > 0">
                        <h6>Linked Scholarships:</h6>
                        <ul class="list-group">
                          <li v-for="scholarship in keywordScholarships[keyword.id]" :key="scholarship.id"
                            class="list-group-item d-flex justify-content-between align-items-center">
                            <span><strong>{{ scholarship.title }}</strong></span>
                            <button v-if="userCanEdit" type="button" class="btn btn-sm btn-outline-danger"
                              @click="unlinkScholarship(keyword.id, scholarship.id)"
                              :disabled="unlinkingScholarships[keyword.id + '-' + scholarship.id]">
                              <span v-if="unlinkingScholarships[keyword.id + '-' + scholarship.id]">Unlinking...</span>
                              <span v-else><i class="fa fa-unlink"></i> Unlink</span>
                            </button>
                          </li>
                        </ul>
                      </div>
                      <div v-else class="alert alert-info mb-0">
                        No scholarships linked to this keyword.
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
      keywordScholarships: {},
      loadingScholarships: {},
      scholarshipSearchResults: {},
      scholarshipSearchTerms: {},
      searchTimeouts: {},
      unlinkingScholarships: {},
      // Pagination data
      keywordsCurrentPage: 1,
      keywordsItemsPerPage: 50,
      totalKeywords: 0,
      // Search data
      keywordSearchTerm: '',
      // Bulk CSV upload: 0=initial, 1=saving, 2=success, 3=failed
      csvFile: null,
      uploadStatus: 0,
      uploadMessage: ''
    };
  },
  mounted() {
    this.fetchKeywords();
  },
  computed: {
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
      let url = `/api/scholarships/keywords?page=${this.keywordsCurrentPage}&limit=${this.keywordsItemsPerPage}&searchterm=${encodeURIComponent(this.keywordSearchTerm)}`;

      axios.get(url)
        .then((response) => {
          if (response.data.keywords && response.data.totalRows !== undefined) {
            this.keywords = response.data.keywords;
            this.totalKeywords = response.data.totalRows;
          } else {
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

      axios.post("/api/scholarships/keywords", {
        keyword: this.newKeywordName
      },
        {
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          }
        })
        .then((response) => {
          this.newKeywordName = "";
          this.isCreating = false;
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
    onCsvFileChange: function (e) {
      const files = e.target.files || e.dataTransfer.files;
      this.csvFile = files && files.length ? files[0] : null;
      if (this.uploadStatus === 2 || this.uploadStatus === 3) {
        this.uploadStatus = 0;
        this.uploadMessage = '';
      }
    },
    uploadCsv: function () {
      if (!this.csvFile) {
        return;
      }

      this.uploadStatus = 1;
      this.apiError.status = null;

      let formData = new FormData();
      formData.append('csv', this.csvFile);

      axios.post('/api/scholarships/keywords/upload', formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
        .then((response) => {
          this.uploadStatus = 2;
          this.uploadMessage = response.data;
          this.csvFile = null;
          if (this.$refs.csvInput) {
            this.$refs.csvInput.value = '';
          }
          this.fetchKeywords();
        })
        .catch((error) => {
          this.uploadStatus = 3;
          const status = error.response ? error.response.status : 500;
          if (status === 403) {
            this.uploadMessage = "You do not have sufficient privileges to upload keywords.";
          } else if (status === 422) {
            this.uploadMessage = (error.response && error.response.data) || "Invalid CSV file.";
          } else {
            this.uploadMessage = "An error occurred during upload.";
          }
        });
    },
    deleteKeyword: function (id) {
      if (!confirm("Are you sure you want to delete this keyword? It will be removed from all scholarships.")) {
        return;
      }

      this.isDeleting = id;
      this.apiError.status = null;

      axios.delete(`/api/scholarships/keywords/${id}`)
        .then(() => {
          this.isDeleting = null;
          delete this.expandedKeywords[id];
          delete this.keywordScholarships[id];
          delete this.loadingScholarships[id];
          const remainingOnPage = this.keywords.length - 1;
          if (remainingOnPage === 0 && this.keywordsCurrentPage > 1) {
            this.keywordsCurrentPage = this.keywordsCurrentPage - 1;
          }
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
      let current = !!this.expandedKeywords[keywordId];
      this.expandedKeywords[keywordId] = !current;
      if (this.expandedKeywords[keywordId] && !this.keywordScholarships[keywordId]) {
        this.fetchKeywordScholarships(keywordId);
      }
    },
    fetchKeywordScholarships: function (keywordId) {
      this.loadingScholarships[keywordId] = true;

      axios.get(`/api/scholarships/keywords/${keywordId}/scholarships`)
        .then((response) => {
          this.keywordScholarships[keywordId] = response.data;

          const keyword = this.keywords.find(k => k.id === keywordId);
          if (keyword) {
            keyword.scholarship_count = Array.isArray(response.data) ? response.data.length : 0;
          }

          this.loadingScholarships[keywordId] = false;
        })
        .catch((error) => {
          this.apiError.status = error.response ? error.response.status : 500;
          switch (this.apiError.status) {
            case 403:
              this.apiError.message = "You do not have sufficient privileges to retrieve scholarships.";
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
          this.loadingScholarships[keywordId] = false;
        });
    },
    handleScholarshipSearchInput: function (keywordId, searchTerm) {
      this.scholarshipSearchTerms[keywordId] = searchTerm || '';

      if (this.searchTimeouts[keywordId]) {
        clearTimeout(this.searchTimeouts[keywordId]);
      }

      if (!searchTerm || searchTerm.length < 2) {
        this.scholarshipSearchResults[keywordId] = [];
        return;
      }

      this.searchTimeouts[keywordId] = setTimeout(() => {
        this.searchScholarships(keywordId, searchTerm);
      }, 500);
    },
    searchScholarships: function (keywordId, searchTerm) {
      axios.get(`/api/scholarships/search?searchterm=${encodeURIComponent(searchTerm)}`)
        .then((response) => {
          let scholarships = Array.isArray(response.data) ? response.data : [];
          let linkedIds = (this.keywordScholarships[keywordId] || []).map(s => s.id);
          this.scholarshipSearchResults[keywordId] = scholarships.filter(s => s && s.id && !linkedIds.includes(s.id));
        })
        .catch((error) => {
          this.apiError.status = error.response ? error.response.status : 500;
          this.apiError.message = "Error searching scholarships.";
          this.scholarshipSearchResults[keywordId] = [];
        });
    },
    handleScholarshipSelected: function (keywordId, scholarship) {
      if (!scholarship || !scholarship.id) {
        return;
      }
      this.linkScholarship(keywordId, scholarship.id);
    },
    linkScholarship: function (keywordId, scholarshipId) {
      this.apiError.status = null;

      axios.post(`/api/scholarships/keywords/${keywordId}/scholarships`,
        `scholarship_id=${scholarshipId}`,
        {
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          }
        })
        .then(() => {
          this.fetchKeywordScholarships(keywordId);
          this.scholarshipSearchResults[keywordId] = [];
          this.scholarshipSearchTerms[keywordId] = '';
        })
        .catch((error) => {
          this.apiError.status = error.response ? error.response.status : 500;
          switch (this.apiError.status) {
            case 403:
              this.apiError.message = "You do not have sufficient privileges to link scholarships.";
              break;
            case 404:
              this.apiError.message = error.response.data || "Keyword or scholarship not found.";
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
    unlinkScholarship: function (keywordId, scholarshipId) {
      if (!confirm("Are you sure you want to unlink this scholarship from the keyword?")) {
        return;
      }

      let key = keywordId + '-' + scholarshipId;
      this.unlinkingScholarships[key] = true;
      this.apiError.status = null;

      axios.delete(`/api/scholarships/keywords/${keywordId}/scholarships/${scholarshipId}`)
        .then(() => {
          this.fetchKeywordScholarships(keywordId);
          this.unlinkingScholarships[key] = false;
        })
        .catch((error) => {
          this.apiError.status = error.response ? error.response.status : 500;
          switch (this.apiError.status) {
            case 403:
              this.apiError.message = "You do not have sufficient privileges to unlink scholarships.";
              break;
            case 404:
              this.apiError.message = error.response.data || "Keyword or scholarship not found.";
              break;
            case 500:
              this.apiError.message = "An internal error occurred.";
              break;
            default:
              this.apiError.message = "An error occurred.";
              break;
          }
          this.unlinkingScholarships[key] = false;
        });
    },
    handleKeywordsPageChanged: function (currentPage) {
      this.keywordsCurrentPage = currentPage;
      this.fetchKeywords();
    },
    handleKeywordsItemsPerPageChanged: function (itemsPerPage) {
      this.keywordsItemsPerPage = itemsPerPage;
      this.keywordsCurrentPage = 1;
      this.fetchKeywords();
    },
    handleKeywordSearch: function () {
      this.keywordsCurrentPage = 1;
      this.fetchKeywords();
      document.getElementById('keywordSearch').focus();
    }
  }
};
</script>
