<template>
  <div>
    <heading>
      <span>Manage GradCAS Cycles</span>
    </heading>
    <div v-if="apiError.status" class="alert alert-danger fade show" role="alert">
      {{ apiError.message }}
    </div>
    <div v-if="successMessage" class="alert alert-success fade show" role="alert">
      {{ successMessage }}
    </div>

    <div class="mb-3">
      <a href="/gradcas/cycle/create" class="btn btn-success">Create New Cycle</a>
    </div>

    <div v-if="!loading" class="table-responsive">
      <table class="table table-hover table-sm">
        <thead>
          <tr>
            <th scope="col">Cycle Name</th>
            <th scope="col">Links</th>
            <th scope="col">Current</th>
            <th scope="col">Created By</th>
            <th scope="col">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="cycle in cycles" :key="cycle.id">
            <td>{{ cycle.cycleName }}</td>
            <td><span class="badge badge-primary">{{ cycle.linkCount }}</span></td>
            <td>
              <div class="custom-control custom-switch">
                <input
                  type="checkbox"
                  class="custom-control-input"
                  :id="'current-' + cycle.id"
                  :checked="cycle.current"
                  @change="toggleCurrent(cycle)"
                />
                <label class="custom-control-label" :for="'current-' + cycle.id">
                  {{ cycle.current ? 'Yes' : 'No' }}
                </label>
              </div>
            </td>
            <td>{{ cycle.createdBy }}</td>
            <td>
              <a :href="'/gradcas/cycle/' + cycle.id + '/edit'" class="btn btn-sm btn-outline-primary mr-1">Edit</a>
              <button class="btn btn-sm btn-outline-danger" @click="confirmDelete(cycle)">Delete</button>
            </td>
          </tr>
        </tbody>
      </table>
      <p v-if="cycles.length === 0" class="text-muted text-center">No cycles found.</p>
    </div>
    <div v-else>
      <p style="text-align: center"><img src="/images/loading.gif" alt="Loading..."/></p>
    </div>

    <!-- Delete Modal -->
    <div v-if="showDeleteModal" class="modal d-block" tabindex="-1" role="dialog" style="background: rgba(0,0,0,0.5)">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Delete cycle</h5>
            <button type="button" class="close" @click="showDeleteModal = false" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <p>Are you sure you want to delete <strong>"{{ cycleToDelete ? cycleToDelete.cycleName : '' }}"</strong> and all its links? Type the word <strong>"delete"</strong> to confirm.</p>
            <div class="form-group">
              <input type="text" v-model="deleteConfirm" class="form-control" />
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-default" @click="showDeleteModal = false">Cancel</button>
            <button
              type="button"
              class="btn btn-danger"
              @click="deleteCycle"
              :disabled="deleteConfirm !== 'delete'"
            >Delete cycle</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import Heading from "../utils/Heading.vue";

export default {
  created() {
    this.fetchCycles();
  },
  components: { Heading },
  props: {
    permissions: {
      type: Array,
      required: true
    }
  },
  data() {
    return {
      apiError: { message: null, status: null },
      successMessage: null,
      cycles: [],
      loading: true,
      showDeleteModal: false,
      cycleToDelete: null,
      deleteConfirm: null
    };
  },
  methods: {
    fetchCycles() {
      let self = this;
      self.loading = true;

      axios.get('/api/gradcas/cycles')
        .then(function(response) {
          self.cycles = response.data;
          self.loading = false;
        })
        .catch(function(error) {
          self.apiError.status = error.response ? error.response.status : 500;
          self.apiError.message = "Failed to load cycles.";
          self.loading = false;
        });
    },

    toggleCurrent(cycle) {
      let self = this;
      self.successMessage = null;
      self.apiError = { message: null, status: null };

      axios.put('/api/gradcas/cycles/' + cycle.id + '/current')
        .then(function() {
          self.successMessage = '"' + cycle.cycleName + '" has been set as the current cycle.';
          self.fetchCycles();
        })
        .catch(function(error) {
          self.apiError.status = error.response ? error.response.status : 500;
          self.apiError.message = "Failed to update current cycle.";
          self.fetchCycles();
        });
    },

    confirmDelete(cycle) {
      this.cycleToDelete = cycle;
      this.deleteConfirm = null;
      this.showDeleteModal = true;
    },

    deleteCycle() {
      if (this.deleteConfirm !== 'delete') return;

      let self = this;
      self.showDeleteModal = false;

      axios.delete('/api/gradcas/cycles/' + self.cycleToDelete.id)
        .then(function() {
          self.successMessage = 'Cycle has been deleted.';
          self.cycleToDelete = null;
          self.deleteConfirm = null;
          self.fetchCycles();
        })
        .catch(function(error) {
          self.apiError.status = error.response ? error.response.status : 500;
          self.apiError.message = "Failed to delete cycle.";
        });
    }
  }
};
</script>
