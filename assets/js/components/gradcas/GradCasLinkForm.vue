<template>
  <div>
    <div v-if="apiError.status" class="alert alert-danger fade show" role="alert">
      {{ apiError.message }}
    </div>
    <div v-if="successMessage" class="alert alert-success fade show" role="alert">
      {{ successMessage }}
    </div>
    <div v-if="isDeleted" class="alert alert-secondary fade show" role="alert">
      This link has been deleted. You will now be redirected.
    </div>

    <div v-if="!isDataLoaded">
      <p style="text-align: center"><img src="/images/loading.gif" alt="Loading..."/></p>
    </div>

    <div v-if="isDataLoaded && !isDeleted">
      <heading>
        <span v-if="!itemExists">Create New Link</span>
        <span v-else>Link Information</span>
      </heading>
      <div class="btn-group mb-3" role="group">
        <button
          v-if="itemExists && permissions[0].edit"
          type="button"
          class="btn btn-info"
          @click="toggleEdit"
        >
          <span v-html="lockIcon"></span>
        </button>
      </div>

      <form @submit.prevent="submitLink">
        <fieldset>
          <legend>Link Details</legend>

          <!-- Program Picker -->
          <div class="form-group">
            <label>Program (optional - select to auto-fill degree name)</label>
            <VueMultiselect
              v-if="isEditMode"
              :options="programs"
              :multiple="false"
              :clear-on-select="false"
              :close-on-select="true"
              placeholder="Search graduate programs..."
              label="full_name"
              track-by="id"
              class="form-control"
              style="padding:0"
              @select="handleProgramSelected"
              @remove="handleProgramRemoved"
            />
            <p v-if="!isEditMode && record.programId" class="form-control-plaintext text-muted">
              Program ID: {{ record.programId }}
            </p>
          </div>

          <!-- Degree Name -->
          <div class="form-group">
            <label for="degreeName">Degree/Program Name *</label>
            <input
              type="text"
              id="degreeName"
              class="form-control"
              :class="{ 'form-control-plaintext': !isEditMode }"
              :readonly="!isEditMode"
              v-model="record.degreeName"
              placeholder="e.g., Accounting [M.S.]"
              required
            />
          </div>

          <!-- Link -->
          <div class="form-group">
            <label for="link">Application Link *</label>
            <input
              type="url"
              id="link"
              class="form-control"
              :class="{ 'form-control-plaintext': !isEditMode }"
              :readonly="!isEditMode"
              v-model="record.link"
              placeholder="https://..."
              required
            />
          </div>
        </fieldset>

        <div v-if="isEditMode" class="form-group mt-3">
          <button type="submit" class="btn btn-primary mr-2">
            {{ itemExists ? 'Update Link' : 'Create Link' }}
          </button>
          <button v-if="itemExists" type="button" class="btn btn-danger" @click="showDeleteModal = true">Delete</button>
        </div>
      </form>

      <div v-if="itemExists" class="mt-3 text-muted">
        <small>Created by {{ record.createdBy }} | Updated by {{ record.updatedBy }}</small>
      </div>
    </div>

    <!-- Delete Modal -->
    <gradcas-link-delete-modal
      v-if="showDeleteModal"
      :link="record"
      @itemDeleted="handleDeleted"
      @itemDeleteError="handleDeleteError"
      @close="showDeleteModal = false"
    />
  </div>
</template>

<script>
import Heading from "../utils/Heading.vue";
import VueMultiselect from 'vue-multiselect';

export default {
  created() {
    if (this.itemExists) {
      this.fetchLink();
    } else {
      this.isDataLoaded = true;
      this.isEditMode = true;
    }
    this.fetchPrograms();
  },
  components: { Heading, VueMultiselect },
  props: {
    permissions: { type: Array, required: true },
    itemExists: { type: Boolean, default: false },
    itemId: { type: String, default: null },
    cycleId: { type: String, default: null },
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
        degreeName: '',
        link: '',
        programId: null,
        createdBy: '',
        updatedBy: '',
        cycle: null
      },
      programs: [],
      showDeleteModal: false
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

    fetchLink() {
      let self = this;
      axios.get('/api/gradcas/link/' + self.itemId)
        .then(function(response) {
          self.record = response.data;
          self.isDataLoaded = true;
        })
        .catch(function(error) {
          self.apiError.status = error.response ? error.response.status : 500;
          self.apiError.message = "Failed to load link.";
          self.isDataLoaded = true;
        });
    },

    fetchPrograms() {
      let self = this;
      axios.get('/api/gradcas/programs')
        .then(function(response) {
          self.programs = response.data;
        })
        .catch(function() {});
    },

    handleProgramSelected(program) {
      this.record.programId = program.id;
      this.record.degreeName = program.full_name;
    },

    handleProgramRemoved() {
      this.record.programId = null;
    },

    submitLink() {
      let self = this;
      self.apiError = { message: null, status: null };
      self.successMessage = null;

      let payload = {
        degreeName: self.record.degreeName,
        link: self.record.link,
        programId: self.record.programId
      };

      if (self.itemExists) {
        axios.put('/api/gradcas/links/' + self.itemId, payload)
          .then(function(response) {
            self.record = response.data;
            self.successMessage = 'Link updated successfully.';
            self.isEditMode = false;
          })
          .catch(function(error) {
            self.apiError.status = error.response ? error.response.status : 500;
            self.apiError.message = error.response ? error.response.data : 'Failed to update link.';
          });
      } else {
        payload.cycleId = parseInt(self.cycleId);
        axios.post('/api/gradcas/links', payload)
          .then(function() {
            self.successMessage = 'Link created successfully.';
            setTimeout(function() {
              window.location.href = '/gradcas';
            }, 1500);
          })
          .catch(function(error) {
            self.apiError.status = error.response ? error.response.status : 500;
            self.apiError.message = error.response ? error.response.data : 'Failed to create link.';
          });
      }
    },

    handleDeleted() {
      this.isDeleted = true;
      this.showDeleteModal = false;
      let self = this;
      setTimeout(function() {
        window.location.href = '/gradcas';
      }, 2000);
    },

    handleDeleteError() {
      this.showDeleteModal = false;
      this.apiError.status = 500;
      this.apiError.message = "Failed to delete link.";
    }
  }
};
</script>
