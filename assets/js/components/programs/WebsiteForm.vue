<template>
  <div>
    <not-found v-if="is404 === true"></not-found>
    <div v-if="isDataLoaded === false">
      <p style="text-align: center;"><img src="/images/loading.gif" alt="Loading..."/></p>
    </div>
    <div v-if="apiError.status" class="alert alert-danger fade show" role="alert">
      {{ apiError.message }}
    </div>
    <div v-if="isDeleted === true" class="alert alert-secondary fade show" role="alert">
      This website has been deleted. You will now be redirected to the websites list page.
    </div>

    <!-- Main Area -->
    <div v-if="isDataLoaded === true && isDeleted === false && is404 === false">
      <heading>
        Website Information
      </heading>
      <div class="btn-group" role="group" aria-label="form navigation buttons">
        <button v-if="siteExists && permissions[0].edit" type="button" class="btn btn-info pull-right"
                @click="toggleEdit"><span v-html="lockIcon"></span></button>
      </div>
      <div class="pt-2" id="websiteTabContent">
        <VeeForm class="form" v-slot="{ submitForm, errors, meta }" @submit="submitWebsite"
                 :validation-schema="websiteSchema">
          <div class="form-group">
            <label>Program website <span v-if="isEditMode" class="red">*</span></label>
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
          <div class="form-group">
            <label>Program name <span v-if="isEditMode" class="red">*</span></label>
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
            There was an error deleting this website.
          </div>
          <!-- Action Buttons -->
          <div v-if="userCanEdit && isEditMode" aria-label="action buttons" class="mb-4">
            <p v-if="isSaveFailed" class="red">Error saving this website. {{ apiError.message }}</p>
            <button type="submit" class="btn btn-success"><i class="fa fa-save fa-2x"></i></button>
            <button
                v-if="siteExists && this.permissions[0].delete"
                type="button"
                class="btn btn-danger ml-4"
                data-toggle="modal"
                data-target="#deleteModal"><i class="fa fa-trash fa-2x"></i></button>
          </div>
        </VeeForm>
      </div>
    </div>
<!--     Delete Item Modal -->
    <website-delete-modal
        :website="record"
        @websiteDeleted="markWebsiteDeleted"
        @websiteDeleteError="markWebsiteDeleteError"
    >
    </website-delete-modal>
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
import WebsiteDeleteModal from "./WebsiteDeleteModal.vue"
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

    if (this.websiteId) {
      this.fetchWebsite(this.websiteId);
    }
    // this.fetchAvailablePrograms();
  },

  components: { Heading, VueMultiselect, WebsiteDeleteModal, NotFound, Field, VeeForm, ErrorMessage },

  props: {
    siteExists: {
      type: Boolean,
      required: true
    },

    websiteId: {
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
        url: ""
      },
      success: false,
      successMessage: "",
      unaffiliatedProgs: []
    }
  },

  computed: {
    isStatusInitial () {
      return this.currentStatus === STATUS_INITIAL;
    },
    isSaveFailed () {
      return this.currentStatus === STATUS_SAVE_FAILED;
    },

    websiteSchema () {
      let yupObj = {
        programName: Yup.string().required().label('Program name '),
        programUrl: Yup.string().required().label('URL ')
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
      return ((this.siteExists && this.permissions[0].create)
          || (!this.siteExists && this.permissions[0].edit)) ? true : false;
    }
  },

  methods: {
    /**
     * Goes to edit mode after submitting the new program.
     */
    afterSubmitSucceeds: function () {
      // Since the new item has been submitted, go to edit mode.
      if (!this.siteExists) {
        this.success = true;
        this.successMessage = "Website created.";
        document.location = "/programs/websites/" + this.record.id;
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
     * Gets the website by using the specified ID.
     * @param {string} websiteId The ID of the website.
     */
    fetchWebsite: function (websiteId) {
      const self = this
      axios.get("/api/programs/websites/" + websiteId)
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

    // /**
    //  * Fetches programs that haven't been assigned a website.
    //  */
    // fetchAvailablePrograms: function () {
    //   const self = this
    //   axios.get("/api/programs/websites/unaffliated")
    //   .then(function (response) { // Success.
    //     self.unaffiliatedProgs = response.data;
    //   })
    //   .catch(function (error) { // Failure.
    //     self.apiError.status = error.response.status;
    //     self.apiError.message = error.response.data;
    //   });
    // },

    /**
     * Gets called from the @websiteDeleted event emission from the delete Modal.
     */
    markWebsiteDeleted: function () {
      this.isDeleteError = false;
      this.isDeleted = true;
      setTimeout(function () {
        // This record doesn't exist anymore, so send the user back to the
        // programs list page.
        window.location.replace("/programs/websites");
      }, 3000);
    },

    /**
     * Marks the item delete error; gets called if there is an error in deleting
     * an item.
     */
    markWebsiteDeleteError: function () {
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
    submitWebsite: function () {
      this.currentStatus = null
      const method = this.siteExists ? "put" : "post"
      const route = "/api/programs/websites/"

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
