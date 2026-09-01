<template>
  <div>
    <heading>
      <span>Manage Organizations</span>
    </heading>
    <div v-if="apiError.status" class="alert alert-danger fade show" role="alert">
      {{ apiError.message }}
    </div>
    <div>
      <p>
        Create and manage organizations that can be assigned to scholarships. Organizations can be assigned to scholarships from the
        individual scholarship forms.
      </p>
    </div>
    <div class="row">
      <div v-if="userCanCreate" class="col-md-6 mb-3">
        <div class="card h-100">
          <div class="card-body">
            <h5 class="card-title">Create New Organization</h5>
            <div class="form-group">
              <label for="newOrganization">Organization Name</label>
              <input type="text" class="form-control" id="newOrganization" v-model="newOrganizationName"
                placeholder="Enter organization name" @keyup.enter="createOrganization" />
            </div>
            <button type="button" class="btn btn-primary" @click="createOrganization" :disabled="!newOrganizationName || isCreating">
              <span v-if="isCreating">Creating...</span>
              <span v-else>Create Organization</span>
            </button>
          </div>
        </div>
      </div>
      <div v-if="userCanCreate" class="col-md-6 mb-3">
        <div class="card h-100">
          <div class="card-body">
            <h5 class="card-title">Bulk Upload Organizations</h5>
            <div class="text-muted mb-2">
              Upload a CSV with columns <code>organization</code> (required) and
              <code>scholarship_id</code> (optional). Existing organizations are skipped. <br><br>
              <p><a href="/bulk_scholarship_organization_template.csv" download>Download Sample Template [CSV]</a>.</p>
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
          Existing Organizations
          <span v-if="!loadingOrganizations" class="badge badge-primary ml-2">{{ totalOrganizations }}</span>
          <span v-else class="ml-2"><i class="fa fa-spinner"></i></span>
        </h5>
        <div v-if="!loadingOrganizations" class="mb-3">
          <label for="organizationSearch" class="form-label">Search Organizations</label>
          <div class="position-relative">
            <input type="text" class="form-control" id="organizationSearch" v-model="organizationSearchTerm"
              placeholder="Search organizations" @keyup.enter="handleOrganizationSearch"
              style="padding-right: 40px;" />
            <button type="button" class="btn btn-link position-absolute"
              style="right: 0; top: 0; height: 100%; border: none; padding: 0 12px;" @click="handleOrganizationSearch"
              :disabled="loadingOrganizations">
              <i class="fa fa-search"></i>
            </button>
            <button v-if="organizationSearchTerm" type="button" class="btn btn-link position-absolute"
                    style="right: 0; top: 0; height: 100%; border: none; margin: 0 40px;" @click="organizationSearchTerm = ''; handleOrganizationSearch()"
                    :disabled="loadingOrganizations">
              <i class="fa fa-remove"></i>
            </button>
          </div>
        </div>
        <div v-if="loadingOrganizations">
          <p style="text-align: center"><img src="/images/loading.gif" alt="Loading..." /></p>
        </div>
        <div v-else-if="organizations.length === 0" class="alert alert-info">
          No organizations found. Create your first organization above.
        </div>
        <div v-else class="table-responsive">
          <table class="table table-hover table-sm">
            <thead>
              <tr>
                <th scope="col" style="width: 30px;"></th>
                <th scope="col">Organization</th>
                <th scope="col">Linked Scholarships</th>
                <th scope="col">Actions</th>
              </tr>
            </thead>
            <tbody>
              <template v-for="organization in organizations" :key="organization.id">
                <tr>
                  <td>
                    <button type="button" class="btn btn-sm btn-link p-0" @click="toggleOrganizationExpanded(organization.id)"
                      :aria-expanded="expandedOrganizations[organization.id] ? 'true' : 'false'">
                      <i :class="expandedOrganizations[organization.id] ? 'fa fa-chevron-down' : 'fa fa-chevron-right'"></i>
                    </button>
                  </td>
                  <td>{{ organization.organization }}</td>
                  <td>
                    <span v-if="organizationScholarships[organization.id]">
                      {{ organizationScholarships[organization.id].length }} scholarship(s)
                    </span>
                    <span v-else>
                      {{ organization.scholarship_count || 0 }} scholarship(s)
                    </span>
                  </td>
                  <td>
                    <button v-if="userCanDelete" type="button" class="btn btn-danger btn-sm"
                      @click="deleteOrganization(organization.id)" :disabled="isDeleting === organization.id">
                      <span v-if="isDeleting === organization.id">Deleting...</span>
                      <span v-else><i class="fa fa-trash"></i> Delete</span>
                    </button>
                  </td>
                </tr>
                <tr v-if="expandedOrganizations[organization.id]">
                  <td colspan="4" class="p-3 bg-light">
                    <div v-if="loadingScholarships[organization.id]" class="text-center">
                      <img src="/images/loading.gif" alt="Loading..." />
                      <div class="mt-2 text-muted">Loading scholarships (expecting {{ organization.scholarship_count || 0 }})</div>
                    </div>
                    <div v-else>
                      <div v-if="userCanEdit" class="mb-3">
                        <label :for="'scholarshipSearch-' + organization.id" class="form-label">Link Scholarship to Organization</label>
                        <VueMultiselect :id="'scholarshipSearch-' + organization.id"
                          :options="scholarshipSearchResults[organization.id] || []" :multiple="false" :clear-on-select="true"
                          placeholder="Search scholarships (type at least 2 characters)" label="title" track-by="id"
                          :searchable="true" :internal-search="false"
                          @search-change="handleScholarshipSearchInput(organization.id, $event)"
                          @select="handleScholarshipSelected(organization.id, $event)">
                        </VueMultiselect>
                      </div>
                      <div v-if="organizationScholarships[organization.id] && organizationScholarships[organization.id].length > 0">
                        <h6>Linked Scholarships:</h6>
                        <ul class="list-group">
                          <li v-for="scholarship in organizationScholarships[organization.id]" :key="scholarship.id"
                            class="list-group-item d-flex justify-content-between align-items-center">
                            <span><strong>{{ scholarship.title }}</strong></span>
                            <button v-if="userCanEdit" type="button" class="btn btn-sm btn-outline-danger"
                              @click="unlinkScholarship(organization.id, scholarship.id)"
                              :disabled="unlinkingScholarships[organization.id + '-' + scholarship.id]">
                              <span v-if="unlinkingScholarships[organization.id + '-' + scholarship.id]">Unlinking...</span>
                              <span v-else><i class="fa fa-unlink"></i> Unlink</span>
                            </button>
                          </li>
                        </ul>
                      </div>
                      <div v-else class="alert alert-info mb-0">
                        No scholarships linked to this organization.
                      </div>
                    </div>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
        <external-paginator v-show="!loadingOrganizations && organizations.length > 0" :ext-curr-pg="organizationsCurrentPage"
          :ext-items-per-pg="organizationsItemsPerPage" :total-recs="totalOrganizations" :items="organizations"
          @itemsPerPageChanged="handleOrganizationsItemsPerPageChanged" @pageChanged="handleOrganizationsPageChanged">
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
      loadingOrganizations: true,
      organizations: [],
      newOrganizationName: "",
      isCreating: false,
      isDeleting: null,
      expandedOrganizations: {},
      organizationScholarships: {},
      loadingScholarships: {},
      scholarshipSearchResults: {},
      scholarshipSearchTerms: {},
      searchTimeouts: {},
      unlinkingScholarships: {},
      // Pagination data
      organizationsCurrentPage: 1,
      organizationsItemsPerPage: 50,
      totalOrganizations: 0,
      // Search data
      organizationSearchTerm: '',
      // Bulk CSV upload: 0=initial, 1=saving, 2=success, 3=failed
      csvFile: null,
      uploadStatus: 0,
      uploadMessage: ''
    };
  },
  mounted() {
    this.fetchOrganizations();
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
    fetchOrganizations: function () {
      this.loadingOrganizations = true;
      let url = `/api/scholarships/organizations?page=${this.organizationsCurrentPage}&limit=${this.organizationsItemsPerPage}&searchterm=${encodeURIComponent(this.organizationSearchTerm)}`;

      axios.get(url)
        .then((response) => {
          if (response.data.organizations && response.data.totalRows !== undefined) {
            this.organizations = response.data.organizations;
            this.totalOrganizations = response.data.totalRows;
          } else {
            this.organizations = Array.isArray(response.data) ? response.data : [];
            this.totalOrganizations = this.organizations.length;
          }
          this.loadingOrganizations = false;
        })
        .catch((error) => {
          this.apiError.status = error.response ? error.response.status : 500;
          switch (this.apiError.status) {
            case 403:
              this.apiError.message = "You do not have sufficient privileges to retrieve organizations.";
              break;
            case 404:
              this.apiError.message = "Organizations were not found.";
              break;
            case 500:
              this.apiError.message = "An internal error occurred.";
              break;
            default:
              this.apiError.message = "An error occurred.";
              break;
          }
          this.loadingOrganizations = false;
        });
    },
    createOrganization: function () {
      if (!this.newOrganizationName || this.isCreating) {
        return;
      }

      this.isCreating = true;
      this.apiError.status = null;

      axios.post("/api/scholarships/organizations", {
        organization: this.newOrganizationName
      },
        {
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          }
        })
        .then((response) => {
          this.newOrganizationName = "";
          this.isCreating = false;
          this.fetchOrganizations();
        })
        .catch((error) => {
          this.apiError.status = error.response ? error.response.status : 500;
          switch (this.apiError.status) {
            case 403:
              this.apiError.message = "You do not have sufficient privileges to create organizations.";
              break;
            case 422:
              this.apiError.message = error.response.data || "Invalid organization name.";
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

      axios.post('/api/scholarships/organizations/upload', formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
        .then((response) => {
          this.uploadStatus = 2;
          this.uploadMessage = response.data;
          this.csvFile = null;
          if (this.$refs.csvInput) {
            this.$refs.csvInput.value = '';
          }
          this.fetchOrganizations();
        })
        .catch((error) => {
          this.uploadStatus = 3;
          const status = error.response ? error.response.status : 500;
          if (status === 403) {
            this.uploadMessage = "You do not have sufficient privileges to upload organizations.";
          } else if (status === 422) {
            this.uploadMessage = (error.response && error.response.data) || "Invalid CSV file.";
          } else {
            this.uploadMessage = "An error occurred during upload.";
          }
        });
    },
    deleteOrganization: function (id) {
      if (!confirm("Are you sure you want to delete this organization? It will be removed from all scholarships.")) {
        return;
      }

      this.isDeleting = id;
      this.apiError.status = null;

      axios.delete(`/api/scholarships/organizations/${id}`)
        .then(() => {
          this.isDeleting = null;
          delete this.expandedOrganizations[id];
          delete this.organizationScholarships[id];
          delete this.loadingScholarships[id];
          const remainingOnPage = this.organizations.length - 1;
          if (remainingOnPage === 0 && this.organizationsCurrentPage > 1) {
            this.organizationsCurrentPage = this.organizationsCurrentPage - 1;
          }
          this.fetchOrganizations();
        })
        .catch((error) => {
          this.apiError.status = error.response ? error.response.status : 500;
          switch (this.apiError.status) {
            case 403:
              this.apiError.message = "You do not have sufficient privileges to delete organizations.";
              break;
            case 404:
              this.apiError.message = "Organization not found.";
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
    toggleOrganizationExpanded: function (organizationId) {
      let current = !!this.expandedOrganizations[organizationId];
      this.expandedOrganizations[organizationId] = !current;
      if (this.expandedOrganizations[organizationId] && !this.organizationScholarships[organizationId]) {
        this.fetchOrganizationScholarships(organizationId);
      }
    },
    fetchOrganizationScholarships: function (organizationId) {
      this.loadingScholarships[organizationId] = true;

      axios.get(`/api/scholarships/organizations/${organizationId}/scholarships`)
        .then((response) => {
          this.organizationScholarships[organizationId] = response.data;

          const organization = this.organizations.find(k => k.id === organizationId);
          if (organization) {
            organization.scholarship_count = Array.isArray(response.data) ? response.data.length : 0;
          }

          this.loadingScholarships[organizationId] = false;
        })
        .catch((error) => {
          this.apiError.status = error.response ? error.response.status : 500;
          switch (this.apiError.status) {
            case 403:
              this.apiError.message = "You do not have sufficient privileges to retrieve scholarships.";
              break;
            case 404:
              this.apiError.message = "Organization not found.";
              break;
            case 500:
              this.apiError.message = "An internal error occurred.";
              break;
            default:
              this.apiError.message = "An error occurred.";
              break;
          }
          this.loadingScholarships[organizationId] = false;
        });
    },
    handleScholarshipSearchInput: function (organizationId, searchTerm) {
      this.scholarshipSearchTerms[organizationId] = searchTerm || '';

      if (this.searchTimeouts[organizationId]) {
        clearTimeout(this.searchTimeouts[organizationId]);
      }

      if (!searchTerm || searchTerm.length < 2) {
        this.scholarshipSearchResults[organizationId] = [];
        return;
      }

      this.searchTimeouts[organizationId] = setTimeout(() => {
        this.searchScholarships(organizationId, searchTerm);
      }, 500);
    },
    searchScholarships: function (organizationId, searchTerm) {
      axios.get(`/api/scholarships/search?searchterm=${encodeURIComponent(searchTerm)}`)
        .then((response) => {
          let scholarships = Array.isArray(response.data) ? response.data : [];
          let linkedIds = (this.organizationScholarships[organizationId] || []).map(s => s.id);
          this.scholarshipSearchResults[organizationId] = scholarships.filter(s => s && s.id && !linkedIds.includes(s.id));
        })
        .catch((error) => {
          this.apiError.status = error.response ? error.response.status : 500;
          this.apiError.message = "Error searching scholarships.";
          this.scholarshipSearchResults[organizationId] = [];
        });
    },
    handleScholarshipSelected: function (organizationId, scholarship) {
      if (!scholarship || !scholarship.id) {
        return;
      }
      this.linkScholarship(organizationId, scholarship.id);
    },
    linkScholarship: function (organizationId, scholarshipId) {
      this.apiError.status = null;

      axios.post(`/api/scholarships/organizations/${organizationId}/scholarships`,
        `scholarship_id=${scholarshipId}`,
        {
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          }
        })
        .then(() => {
          this.fetchOrganizationScholarships(organizationId);
          this.scholarshipSearchResults[organizationId] = [];
          this.scholarshipSearchTerms[organizationId] = '';
        })
        .catch((error) => {
          this.apiError.status = error.response ? error.response.status : 500;
          switch (this.apiError.status) {
            case 403:
              this.apiError.message = "You do not have sufficient privileges to link scholarships.";
              break;
            case 404:
              this.apiError.message = error.response.data || "Organization or scholarship not found.";
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
    unlinkScholarship: function (organizationId, scholarshipId) {
      if (!confirm("Are you sure you want to unlink this scholarship from the organization?")) {
        return;
      }

      let key = organizationId + '-' + scholarshipId;
      this.unlinkingScholarships[key] = true;
      this.apiError.status = null;

      axios.delete(`/api/scholarships/organizations/${organizationId}/scholarships/${scholarshipId}`)
        .then(() => {
          this.fetchOrganizationScholarships(organizationId);
          this.unlinkingScholarships[key] = false;
        })
        .catch((error) => {
          this.apiError.status = error.response ? error.response.status : 500;
          switch (this.apiError.status) {
            case 403:
              this.apiError.message = "You do not have sufficient privileges to unlink scholarships.";
              break;
            case 404:
              this.apiError.message = error.response.data || "Organization or scholarship not found.";
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
    handleOrganizationsPageChanged: function (currentPage) {
      this.organizationsCurrentPage = currentPage;
      this.fetchOrganizations();
    },
    handleOrganizationsItemsPerPageChanged: function (itemsPerPage) {
      this.organizationsItemsPerPage = itemsPerPage;
      this.organizationsCurrentPage = 1;
      this.fetchOrganizations();
    },
    handleOrganizationSearch: function () {
      this.organizationsCurrentPage = 1;
      this.fetchOrganizations();
      document.getElementById('organizationSearch').focus();
    }
  }
};
</script>
