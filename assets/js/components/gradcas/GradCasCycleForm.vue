<template>
  <div>
    <div v-if="apiError.status" class="alert alert-danger fade show" role="alert">
      {{ apiError.message }}
    </div>
    <div v-if="successMessage" class="alert alert-success fade show" role="alert">
      {{ successMessage }}
    </div>
    <div v-if="isDeleted" class="alert alert-secondary fade show" role="alert">
      This cycle has been deleted. You will now be redirected.
    </div>

    <div v-if="!isDataLoaded">
      <p style="text-align: center"><img src="/images/loading.gif" alt="Loading..."/></p>
    </div>

    <div v-if="isDataLoaded && !isDeleted">
      <heading>
        <span v-if="!itemExists">Create New Cycle</span>
        <span v-else>Cycle Information</span>
      </heading>
      <div class="btn-group mb-3" role="group">
        <button
          v-if="itemExists && permissions[0].admin"
          type="button"
          class="btn btn-info"
          @click="toggleEdit"
        >
          <span v-html="lockIcon"></span>
        </button>
      </div>

      <form @submit.prevent="submitCycle">
        <fieldset>
          <div class="form-group">
            <label for="cycleName">Cycle Name *</label>
            <input
              type="text"
              id="cycleName"
              class="form-control"
              :class="{ 'form-control-plaintext': !isEditMode }"
              :readonly="!isEditMode"
              v-model="record.cycleName"
              placeholder="e.g., Summer '26, Fall '26, or Winter '27"
              required
            />
          </div>
        </fieldset>

        <div v-if="isEditMode" class="form-group mt-3">
          <button type="submit" class="btn btn-primary mr-2">
            {{ itemExists ? 'Update Cycle' : 'Create Cycle' }}
          </button>
          <button v-if="itemExists" type="button" class="btn btn-danger" @click="showDeleteModal = true">Delete</button>
        </div>
      </form>

      <div v-if="itemExists" class="mt-3 text-muted">
        <small>Created by {{ record.createdBy }} | Updated by {{ record.updatedBy }}</small>
      </div>
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
            <p>Are you sure you want to delete this cycle and all its links? Type the word <strong>"delete"</strong> to confirm.</p>
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
    if (this.itemExists) {
      this.fetchCycle();
    } else {
      this.isDataLoaded = true;
      this.isEditMode = true;
    }
  },
  components: { Heading },
  props: {
    permissions: { type: Array, required: true },
    itemExists: { type: Boolean, default: false },
    itemId: { type: String, default: null },
    startMode: { type: String, default: 'show' }
  },
  data() {
    return {
      apiError: { message: null, status: null },
      successMessage: null,
      isDataLoaded: false,
      isEditMode: this.startMode === 'edit',
      isDeleted: false,
      record: {
        id: null,
        cycleName: '',
        current: false,
        createdBy: '',
        updatedBy: ''
      },
      showDeleteModal: false,
      deleteConfirm: null
    };
  },
  computed: {
    lockIcon() {
      return this.isEditMode
        ? '<i class="fa fa-unlock"></i> Editing'
        : '<i class="fa fa-lock"></i> Locked';
    }
  },
  methods: {
    toggleEdit() {
      this.isEditMode = !this.isEditMode;
    },

    fetchCycle() {
      let self = this;
      axios.get('/api/gradcas/cycles/' + self.itemId)
        .then(function(response) {
          self.record = response.data;
          self.isDataLoaded = true;
        })
        .catch(function(error) {
          self.apiError.status = error.response ? error.response.status : 500;
          self.apiError.message = "Failed to load cycle.";
          self.isDataLoaded = true;
        });
    },

    submitCycle() {
      let self = this;
      self.apiError = { message: null, status: null };
      self.successMessage = null;

      let payload = { cycleName: self.record.cycleName };

      if (self.itemExists) {
        axios.put('/api/gradcas/cycles/' + self.itemId, payload)
          .then(function(response) {
            self.record = response.data;
            self.successMessage = 'Cycle updated successfully.';
            self.isEditMode = false;
          })
          .catch(function(error) {
            self.apiError.status = error.response ? error.response.status : 500;
            self.apiError.message = error.response ? error.response.data : 'Failed to update cycle.';
          });
      } else {
        axios.post('/api/gradcas/cycles', payload)
          .then(function(response) {
            self.successMessage = 'Cycle created successfully.';
            setTimeout(function() {
              window.location.href = '/gradcas/cycles';
            }, 1500);
          })
          .catch(function(error) {
            self.apiError.status = error.response ? error.response.status : 500;
            self.apiError.message = error.response ? error.response.data : 'Failed to create cycle.';
          });
      }
    },

    deleteCycle() {
      if (this.deleteConfirm !== 'delete') return;

      let self = this;
      self.showDeleteModal = false;

      axios.delete('/api/gradcas/cycles/' + self.itemId)
        .then(function() {
          self.isDeleted = true;
          setTimeout(function() {
            window.location.href = '/gradcas/cycles';
          }, 2000);
        })
        .catch(function(error) {
          self.apiError.status = error.response ? error.response.status : 500;
          self.apiError.message = "Failed to delete cycle.";
        });
    }
  }
};
</script>
