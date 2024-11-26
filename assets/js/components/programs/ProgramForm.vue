<template>
  <div>
    <not-found v-if="is404 === true"></not-found>
    <div v-if="isDataLoaded === false">
      <p style="text-align: center;"><img src="/images/loading.gif" alt="Loading..."/></p>
    </div>
    <div v-if="apiError.status" class="alert alert-danger fade show" role="alert">
      {{ apiError.message }}
    </div>
    <div v-if="isDeleted === true" class="alert alert-info fade show" role="alert">
      This program has been deleted. You will now be redirected to the programs list page.
    </div>

    <!-- Main Area -->
    <div v-if="isDataLoaded === true && isDeleted === false && is404 === false">
      <heading>
        Program Information
      </heading>
      <div class="btn-group" role="group" aria-label="form navigation buttons">
        <button v-if="progExists && permissions[0].user" type="button" class="btn btn-info pull-right"
                @click="toggleEdit"><span v-html="lockIcon"></span></button>
      </div>
      <div class="pt-2" id="programTabContent">
        <VeeForm class="form" v-slot="{ submitForm, errors, meta }" @submit="submitProgram"
                 :validation-schema="programSchema">
          <div class="form-group">
            <label>Program name <span class="red">*</span></label>
            <Field
                name="programName"
                type="text"
                class="form-control"
                :class="{'is-invalid': errors.program, 'form-control-plaintext': !userCanEdit || !isEditMode}"
                :readonly="!userCanEdit || !isEditMode"
                v-model="record.program">
            </Field>
            <div class="invalid-feedback">
              {{ errors.program }}
            </div>
          </div>
          <div class="form-group">
            <label>Program website</label>
            <Field
                name="programUrl"
                type="text"
                class="form-control"
                :class="{'is-invalid': errors.url, 'form-control-plaintext': !userCanEdit || !isEditMode}"
                :readonly="!userCanEdit || !isEditMode"
                v-model="record.url">
            </Field>
            <div class="invalid-feedback">
              {{ errors.url }}
            </div>
          </div>
          <div v-if="Object.keys(errors).length && isEditMode" class="alert alert-danger fade show" role="alert">
            Please fix all errors before submitting:
            <ul>
              <li v-for="error in errors">
                <strong>{{ error }}</strong>
              </li>
            </ul>
          </div>
          <div v-if="success" class="alert alert-success fade show" role="alert">
            {{ successMessage }}
          </div>
          <div v-if="isDeleteError === true" class="alert alert-danger fade show" role="alert">
            There was an error deleting this program.
          </div>
          <!-- Action Buttons -->
          <div v-if="userCanEdit && isEditMode" aria-label="action buttons" class="mb-4">
            <p v-if="isSaveFailed" class="red">Error saving this program. {{ apiError.message }}</p>
            <button type="submit" class="btn btn-success"><i class="fa fa-save fa-2x"></i></button>
            <button
                v-if="progExists && this.permissions[0].user"
                type="button"
                class="btn btn-danger ml-4"
                data-toggle="modal"
                data-target="#deleteModal"><i class="fa fa-trash fa-2x"></i></button>
          </div>
        </VeeForm>
      </div>
    </div>
    <!-- Delete Item Modal -->
    <program-delete-modal
        :program="record"
        @programDeleted="markProgramDeleted"
        @programDeleteError="markProgramDeleteError"
    >
    </program-delete-modal>
  </div>
</template>

<style>
.red {
  color: #FF0033;
}
</style>

<script>
import Heading from "../utils/Heading.vue"
import VueMultiselect from "vue-multiselect"
import NotFound from "../utils/NotFound.vue"
import ProgramDeleteModal from "./ProgramDeleteModal.vue"
import { ErrorMessage, Field, Form as VeeForm } from 'vee-validate'
import * as Yup from "yup";

const STATUS_INITIAL = 0
const STATUS_SAVE_FAILED = 1

export default {
  created () {
    // Detect if the form should be in edit mode from the start; default is false.
    if (this.startMode === "edit") {
      this.isEditMode = true;
    }

    if (this.progId) {
      this.fetchProgram(this.progId);
    }
  },

  components: { Heading, VueMultiselect, ProgramDeleteModal, NotFound, Field, VeeForm, ErrorMessage },

  props: {
    progExists: {
      type: Boolean,
      required: true
    },

    progId: {
      type: String,
      required: false
    },

    newForm: {
      default: false
    },

    permissions: {
      type: Array,
      required: true
    },

    startMode: {
      type: String,
      required: false
    }
  },

  data: function () {
    return {
      currentStatus: null,
      apiError: {
        message: null,
        status: null
      },
      is404: false,
      isDeleteError: false,
      isDataLoaded: false,
      isDeleted: false,
      isEditMode: false, // This is true if the forms are editable.
      record: {
        id: "",
        program: "",
        catalog: ""
      },
      success: false,
      successMessage: "",
    }
  },

  computed: {
    isStatusInitial () {
      return this.currentStatus === STATUS_INITIAL;
    },
    isSaveFailed () {
      return this.currentStatus === STATUS_SAVE_FAILED;
    },

    programSchema () {
      let yupObj = {
        programName: Yup.string().required().label('Program name '),
        // toLink: Yup.string().required().label('To link ')
      }
      return Yup.object(yupObj)
    },

    /**
     * Gets the heading icon.
     * @return {string} The heading icon.
     */
    headingIcon: function () {
      return "<i class='fa fa-map'></i>";
    },

    /**
     * Gets the string of "is-invalid".
     * @return {string} "is-invalid".
     */
    isInvalid: function () {
      return "is-invalid";
    },

    /**
     * Gets the appropriate lock icon based if the user is in edit mode.
     * @return {string} The lock icon being unlocked if the user is in edit
     * mode; the lock icon being locked otherwise.
     */
    lockIcon: function () {
      return this.isEditMode ? "<i class='fa fa-unlock'></i>" : "<i class='fa fa-lock'></i>";
    },

    /**
     * Determines if the user can edit.
     * @return {boolean} True if the user can edit; false otherwise.
     */
    userCanEdit: function () {
      return ((this.progExists && this.permissions[0].user)
          || (!this.progExists && this.permissions[0].user)) ? true : false;
    }
  },

  methods: {
    /**
     * Goes to edit mode after submitting the new program.
     */
    afterSubmitSucceeds: function () {
      // Since the new item has been submitted, go to edit mode.
      if (!this.progExists) {
        this.success = true;
        this.successMessage = "Program created.";
        document.location = "/programs/" + this.record.id;
      }
      else {
        this.success = true;
        this.successMessage = "Update successful.";
        setTimeout(() => {
          this.success = false;
          self.currentStatus = STATUS_INITIAL
        }, 3000);
      }
    },

    /**
     * Gets the program by using the specified ID.
     * @param {string} progId The ID of the program.
     */
    fetchProgram: function (progId) {
      const self = this
      axios.get("/api/programs/" + progId)
      .then(function (response) { // Success.
        self.record = response.data;
        self.isDataLoaded = true;
      })
      .catch(function (error) { // Failure.
        if (error.request.status == 404) {
          self.is404 = true;
          self.isDataLoaded = true;
        }
      });
    },

    /**
     * Gets called from the @programDeleted event emission from the delete Modal.
     */
    markProgramDeleted: function () {
      this.isDeleteError = false;
      this.isDeleted = true;
      setTimeout(function () {
        // This record doesn't exist anymore, so send the user back to the
        // programs list page.
        window.location.replace("/programs/list");
      }, 3000);
    },

    /**
     * Marks the item delete error; gets called if there is an error in deleting
     * an item.
     */
    markProgramDeleteError: function () {
      let self = this;
      this.isDeleted = false;
      this.isDeleteError = true;
      setTimeout(function () {
        self.isDeleteError = false;
      }, 5000);
    },

    /**
     * Submits the form via the API.
     */
    submitProgram: function () {
      this.currentStatus = null
      const method = this.progExists ? "put" : "post"
      const route = "/api/programs/"

      const self = this
      /* Ajax (Axios) Submission */
      axios({
        method: method,
        url: route,
        data: self.record
      })
      .then(function (response) { // Success.
        self.record = response.data; // This sets the program's ID.
        self.afterSubmitSucceeds();
      })
      .catch(function (error) { // Failure.
        self.currentStatus = STATUS_SAVE_FAILED
        self.apiError.status = error.response.status;
        self.apiError.message = error.response.data;
      });
    },

    /**
     * Sets the variable @isEditMode to the appropriate boolean value.
     */
    toggleEdit: function () {
      (this.isEditMode === true) ? this.isEditMode = false : this.isEditMode = true;
    }
  },

  filters: {}
};
</script>
