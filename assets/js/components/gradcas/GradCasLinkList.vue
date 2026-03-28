<template>
  <div>
    <heading>
      <span>GradCAS Application Links</span>
    </heading>
    <div v-if="apiError.status" class="alert alert-danger fade show" role="alert">
      {{ apiError.message }}
    </div>

    <!-- Cycle Selector -->
    <div class="form-group row mb-3">
      <label for="cycleSelect" class="col-sm-2 col-form-label"><strong>Cycle:</strong></label>
      <div class="col-sm-4">
        <select id="cycleSelect" class="form-control" v-model="selectedCycleId" @change="handleCycleChange">
          <option v-for="cycle in cycles" :key="cycle.id" :value="cycle.id">
            {{ cycle.cycleName }}{{ cycle.current ? ' (current)' : '' }}
          </option>
        </select>
      </div>
      <div class="col-sm-6 d-flex align-items-center">
        <a v-if="userCanEdit" :href="'/gradcas/link/create/' + selectedCycleId" class="btn btn-success mr-2">Add Link</a>
        <button v-if="userIsAdmin" class="btn btn-outline-secondary" @click="showUploadModal = true">CSV Upload</button>
      </div>
    </div>

    <!-- Search -->
    <div class="mb-3">
      <label for="linkSearch" class="sr-only">Search links</label>
      <VueMultiselect
        :options="searchResults"
        :multiple="false"
        :clear-on-select="true"
        placeholder="Search links (type at least 3 characters)"
        label="degreeName"
        track-by="id"
        id="linkSearch"
        class="form-control"
        style="padding:0"
        name="linkSearch"
        @input="handleSearchInput"
        @select="handleLinkSelected"
      />
    </div>

    <!-- Links Table -->
    <div v-if="!loading" class="table-responsive">
      <table class="table table-hover table-sm">
        <thead>
          <tr>
            <th scope="col">Degree/Program</th>
            <th scope="col">Application Link</th>
            <th scope="col">Created By</th>
            <th scope="col">Updated By</th>
            <th scope="col">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="link in links" :key="link.id">
            <td>{{ link.degreeName }}</td>
            <td>
              <a :href="link.link" target="_blank" title="Open application link">
                {{ truncateLink(link.link) }}
              </a>
            </td>
            <td>{{ link.createdBy }}</td>
            <td>{{ link.updatedBy }}</td>
            <td>
              <a v-if="userCanEdit" :href="'/gradcas/link/' + link.id + '/edit'">
                <font-awesome-icon icon="fa-solid fa-pen-to-square" />
              </a>
            </td>
          </tr>
        </tbody>
      </table>
      <p v-if="links.length === 0" class="text-muted text-center">No links found for this cycle.</p>
    </div>
    <div v-else>
      <p style="text-align: center"><img src="/images/loading.gif" alt="Loading..."/></p>
    </div>

    <external-paginator
      v-show="!loading"
      :ext-curr-pg="currentPage"
      :ext-items-per-pg="itemsPerPage"
      :total-recs="totalLinks"
      :items="links"
      @itemsPerPageChanged="handleItemsPerPageChanged"
      @pageChanged="handlePageChanged"
    />

    <!-- CSV Upload Modal -->
    <div v-if="showUploadModal" class="modal d-block" tabindex="-1" role="dialog" style="background: rgba(0,0,0,0.5)">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">CSV Upload - Links for {{ selectedCycleName }}</h5>
            <button type="button" class="close" @click="showUploadModal = false" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <p>Upload a CSV file with columns: <code>degree_name</code>, <code>link</code></p>
            <div class="form-group">
              <input type="file" class="form-control-file" accept=".csv" ref="csvFile" />
            </div>
            <div v-if="uploadStatus === 'success'" class="alert alert-success">{{ uploadMessage }}</div>
            <div v-if="uploadStatus === 'failed'" class="alert alert-danger">{{ uploadMessage }}</div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-default" @click="showUploadModal = false">Cancel</button>
            <button type="button" class="btn btn-primary" @click="uploadCsv" :disabled="uploadStatus === 'saving'">
              {{ uploadStatus === 'saving' ? 'Uploading...' : 'Upload' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import Heading from "../utils/Heading.vue";
import ExternalPaginator from "../utils/ExternalPaginator.vue";
import VueMultiselect from 'vue-multiselect'

export default {
  created() {
    this.fetchCycles();
  },
  components: { Heading, ExternalPaginator, VueMultiselect },
  props: {
    permissions: {
      type: Array,
      required: true
    },
    currentCycleId: {
      type: Number,
      default: 0
    }
  },
  data() {
    return {
      apiError: { message: null, status: null },
      cycles: [],
      links: [],
      loading: true,
      selectedCycleId: this.currentCycleId,
      currentPage: 1,
      itemsPerPage: 20,
      totalLinks: 0,
      searchResults: [],
      showUploadModal: false,
      uploadStatus: 'initial',
      uploadMessage: ''
    };
  },
  computed: {
    userCanEdit() {
      return this.permissions[0].edit;
    },
    userIsAdmin() {
      return this.permissions[0].admin;
    },
    selectedCycleName() {
      const cycle = this.cycles.find(c => c.id === this.selectedCycleId);
      return cycle ? cycle.cycleName : '';
    }
  },
  methods: {
    fetchCycles() {
      let self = this;
      axios.get('/api/gradcas/cycles')
        .then(function(response) {
          self.cycles = response.data;
          if (self.selectedCycleId === 0 && self.cycles.length > 0) {
            // Find current cycle or use first
            const current = self.cycles.find(c => c.current);
            self.selectedCycleId = current ? current.id : self.cycles[0].id;
          }
          self.fetchLinks();
        })
        .catch(function(error) {
          self.apiError.status = error.response ? error.response.status : 500;
          self.apiError.message = "Failed to load cycles.";
        });
    },

    fetchLinks() {
      let self = this;
      self.loading = true;
      self.links = [];

      axios.get(`/api/gradcas/links/${self.selectedCycleId}?page=${self.currentPage}&limit=${self.itemsPerPage}`)
        .then(function(response) {
          self.links = response.data.links;
          self.totalLinks = response.data.totalRows;
          self.loading = false;
        })
        .catch(function(error) {
          self.apiError.status = error.response ? error.response.status : 500;
          self.apiError.message = "Failed to load links.";
          self.loading = false;
        });
    },

    handleCycleChange() {
      this.currentPage = 1;
      this.fetchLinks();
    },

    handleSearchInput(evt) {
      let searchTerm = evt.target.value;
      if (searchTerm.length > 2) {
        let self = this;
        axios.get(`/api/gradcas/links/${self.selectedCycleId}/search?searchterm=${searchTerm}`)
          .then(function(response) {
            self.searchResults = response.data;
          })
          .catch(function() {});
      }
    },

    handleLinkSelected(evt) {
      if (this.userCanEdit) {
        window.location.href = '/gradcas/link/' + evt.id + '/edit';
      }
    },

    handleItemsPerPageChanged(itemsPerPage) {
      this.itemsPerPage = itemsPerPage;
      this.fetchLinks();
    },

    handlePageChanged(currentPage) {
      this.currentPage = currentPage;
      this.fetchLinks();
    },

    truncateLink(link) {
      return link.length > 60 ? link.substring(0, 60) + '...' : link;
    },

    uploadCsv() {
      let self = this;
      let file = this.$refs.csvFile.files[0];
      if (!file) return;

      self.uploadStatus = 'saving';
      let formData = new FormData();
      formData.append('csv', file);
      formData.append('cycleId', self.selectedCycleId);

      axios.post('/api/gradcas/links/upload', formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
        .then(function(response) {
          self.uploadStatus = 'success';
          self.uploadMessage = response.data;
          self.fetchLinks();
        })
        .catch(function(error) {
          self.uploadStatus = 'failed';
          self.uploadMessage = error.response ? error.response.data : 'Upload failed.';
        });
    }
  }
};
</script>
