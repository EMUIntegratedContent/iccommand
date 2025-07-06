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
      This redirect has been deleted. You will now be redirected to the redirect list page.
    </div>

    <!-- Main Area -->
    <div v-if="isDataLoaded === true && isDeleted === false && is404 === false">
      <heading>
        <span v-if="!itemExists">Step 2/2: Provide redirect information</span>
        <span v-else>Existing Redirect Information</span>
      </heading>
      <div class="btn-group" role="group" aria-label="form navigation buttons">
        <button v-if="newForm" class="btn btn-info pull-left" @click="goBack()"><i class="fa fa-chevron-circle-left"></i> Step 1</button>
        <button v-if="itemExists && permissions[0].user" type="button" class="btn btn-info pull-right" @click="toggleEdit"><span v-html="lockIcon"></span></button>
      </div>
      <div class="pt-2" id="redirectItemTabContent">
        <VeeForm class="form" v-slot="{ submitForm, errors, meta }" @submit="submitRedirect" :validation-schema="redirectSchema">
          <fieldset>
            <legend>Basic Information</legend>
            <div class="form-group">
              <template v-if="record.itemType == 'redirect of broken link'">
                <label>Broken Link *</label>
              </template>
              <template v-if="record.itemType == 'redirect of shortened link'">
                <label>Shortened/Vanity Link *</label>
              </template>
              <Field
                  name="fromLink"
                  type="text"
                  class="form-control"
                  :class="{'is-invalid': errors.fromLink, 'form-control-plaintext': !userCanEdit || !isEditMode}"
                  :readonly="!userCanEdit || !isEditMode"
                  v-model="record.fromLink">
              </Field>
              <div class="invalid-feedback">
                {{ errors.fromLink }}
              </div>
            </div>
            <div class="form-group">
              <template v-if="record.itemType == 'redirect of broken link'">
                <label>Actual Link *</label>
              </template>
              <template v-if="record.itemType == 'redirect of shortened link'">
                <label>Full Link *</label>
              </template>
              <Field
                  name="toLink"
                  type="text"
                  class="form-control"
                  :class="{'is-invalid': errors.toLink, 'form-control-plaintext': !userCanEdit || !isEditMode}"
                  :readonly="!userCanEdit || !isEditMode"
                  v-model="record.toLink">
              </Field>
              <div class="invalid-feedback">
                {{ errors.toLink }}
              </div>
            </div>
          </fieldset>
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
            There was an error deleting this item.
          </div>
          <!-- Action Buttons -->
          <div v-if="userCanEdit && isEditMode" aria-label="action buttons" class="mb-4">
            <p v-if="isSaveFailed" class="red">Error saving this redirect. {{ apiError.message }}</p>
            <button type="submit" class="btn btn-success"><i class="fa fa-save fa-2x"></i></button>
            <button
              v-if="itemExists && this.permissions[0].user"
              type="button"
              class="btn btn-danger ml-4"
              data-toggle="modal"
              data-target="#deleteModal"><i class="fa fa-trash fa-2x"></i></button>
          </div>
        </VeeForm>
        <!-- Recommended Section for Redirects of Broken Links -->
        <div v-if="!itemExists && record.itemType == 'redirect of broken link'" class="table-responsive">
          <legend>Recommended</legend>
          <table class="table table-hover table-sm">
            <thead>
              <tr>
                <th scope="col">Broken Links</th>
                <th scope="col">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="uncaught in uncaughtsInRecommended" :id="uncaught.id">
                <td>{{ uncaught.link }}</td>
                <td><i class="fa fa-plus-square pointer fa-2x pr-4 green" @click="setFromLink(uncaught.link)"></i> <i class="fa fa-times-circle pointer fa-2x red" @click="removeFromRecommended(uncaught)"></i></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <!-- Delete Item Modal -->
    <redirect-item-delete-modal
      :redirect="record"
      @itemDeleted="markItemDeleted"
      @itemDeleteError="markItemDeleteError"
    >
    </redirect-item-delete-modal>
  </div>
</template>

<style>
.pointer {
  cursor: pointer;
}
.green{
    color:#4A6086;
}
.red{
    color:#FF0033;
}
</style>

<script>
import Heading from "../utils/Heading.vue"
import VueMultiselect from "vue-multiselect"
import NotFound from "../utils/NotFound.vue"
import RedirectItemDeleteModal from "./RedirectItemDeleteModal.vue"
import { Field, Form as VeeForm, ErrorMessage } from 'vee-validate'
import * as Yup from "yup";
const STATUS_INITIAL = 0
const STATUS_SAVE_FAILED = 1

export default {
  created() {
    // Detect if the form should be in edit mode from the start; default is false.
    if (this.startMode == "edit") {
      this.isEditMode = true;
    }

    if (this.itemExists === false) {
      // Set the kind of redirect item being created via the itemType property from NewRedirectItemChoices.
      this.isDataLoaded = true;
      this.record.itemType = this.itemType;
      this.fetchUncaughts();
    } else {
      // Fetch the existing record using the property itemId.
      this.fetchRedirect(this.itemId);
    }
  },

  components: {Heading, VueMultiselect, RedirectItemDeleteModal, NotFound, Field, VeeForm, ErrorMessage},

  props: {
    itemExists: {
      type: Boolean,
      required: true
    },

    itemId:{
      type: String,
      required: false
    },

    itemType: {
      type: String,
      required: true // Not required of existing items because the type is already known.
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

  data: function() {
    return {
      currentStatus: null,
      /* **************************** Error Data **************************** */

      /**
       * The error for the API controller consists of a message and a status.
       * @type {Object}
       */
      apiError: {
        message: null,
        status: null
      },

      /**
       * Is used to check if the request status is 404.
       * @type {boolean}
       */
      is404: false,

      /**
       * Is used to check if there is an error in deleting the redirect.
       */
      isDeleteError: false,

      /**
       * Is used to check if the invalid error is already showing.
       * @type {boolean}
       */
      isUnvalidErrorShowing: false,

      /* *************************** Fetched Data *************************** */

      /**
       * The uncaught items that are fetched for the new redirects of broken
       * links.
       * @type {Array.<Uncaught>}
       */
      fetchedUncaughts: [],

      /**
       * Is used to check if the data is fetched and loaded.
       * @type {boolean}
       */
      isDataLoaded: false,

      /* ************************** Processing Data ************************* */

      /**
       * Is used to check if the redirect is deleted.
       * @type {boolean}
       */
      isDeleted: false,

      /**
       * Is used to check if the user is in edit mode.
       * @type {boolean}
       */
      isEditMode: false, // This is true if the forms are editable.

      /**
       * The current redirect to be update upon or created.
       * @type {Object}
       */
      record: {
        id: "",
        fromLink: "",
        toLink: "",
        itemType: ""
      },

      /**
       * Is used to check if it is successful to make or update the redirect.
       * @type {boolean}
       */
      success: false,

      /**
       * The message of the successful update or creation of the redirect.
       * @type {string}
       */
      successMessage: "",

      /**
       * The uncaught items that can be in the recommended section.
       * @type {Array.<Uncaught>}
       */
      uncaughtsInRecommended: [],
    }
  },

  computed: {
    isStatusInitial () {
      return this.currentStatus === STATUS_INITIAL;
    },
    isSaveFailed () {
      return this.currentStatus === STATUS_SAVE_FAILED;
    },
    redirectSchema() {
      let yupObj = {
        fromLink: Yup.string().required().label('From link '),
        toLink: Yup.string().required().label('To link ')
      }
      return Yup.object(yupObj)
    },

    /**
     * Gets the heading icon.
     * @return {string} The heading icon.
     */
    headingIcon: function() {
      return "<i class='fa fa-map'></i>";
    },

    /**
     * Gets the string of "is-invalid".
     * @return {string} "is-invalid".
     */
    isInvalid: function() {
      return "is-invalid";
    },

    /**
     * Gets the appropriate lock icon based if the user is in edit mode.
     * @return {string} The lock icon being unlocked if the user is in edit
     * mode; the lock icon being locked otherwise.
     */
    lockIcon: function() {
      return this.isEditMode ? "<i class='fa fa-unlock'></i>" : "<i class='fa fa-lock'></i>";
    },

    /**
     * Determines if the user can edit.
     * @return {boolean} True if the user can edit; false otherwise.
     */
    userCanEdit: function() {
      return ((this.itemExists && this.permissions[0].user)
        || (!this.itemExists && this.permissions[0].user)) ? true : false;
    }
  },

  methods: {
    /**
     * Goes to edit mode after submitting the new item.
     */
    afterSubmitSucceeds: function() {
      let self = this;

      // Since the new item has been submitted, go to edit mode.
      if (!this.itemExists) {
        this.success = true;
        this.successMessage = "Item created.";
        let newurl = "/redirects/" + this.record.id;
        document.location = newurl;
      } else {
        this.success = true;
        this.successMessage = "Update successful.";
      }
    },

    /**
     * Deletes the uncaught item specified by the ID.
     * @param {number} id The ID of the uncaught item to be deleted.
     */
    deleteUncaught: function(id) {
      let self = this; // "this" loses scope within Axios.
      self.currentStatus = null
      axios.delete("/api/uncaughts/" + id)
      .then(function(response) { // Success.

      })
      .catch(function(error) { // Failure.
        self.currentStatus = STATUS_SAVE_FAILED
      });
    },

    /**
     * Gets the redirect by using the specified ID.
     * @param {string} itemId The ID of the redirect.
     */
    fetchRedirect: function(itemId) {
      let self = this;

      axios.get("/api/redirects/" + itemId)
      .then(function(response) { // Success.
        self.record = response.data;
        self.isDataLoaded = true;
      })
      .catch(function(error) { // Failure.
        if(error.request.status == 404) {
          self.is404 = true;
          self.isDataLoaded = true;
        }
      });
    },

    /**
     * Gets the uncaught items.
     */
    fetchUncaughts: function() {
      let self = this;
      this.fetchedUncaughts = [];

      axios.get("/api/uncaughts/")
      .then(function(response) { // Success.
        response.data.forEach(function(uncaught) {
          self.fetchedUncaughts.push(uncaught);
        });

        self.uncaughtsInRecommended = self.fetchedUncaughts.slice(0, 5);
      })
      .catch(function(error) { // Failure.
        self.apiError.status = error.response.status;

        switch (error.response.status) {
          case 403:
            self.apiError.message = "You do not have sufficient privileges to retrieve uncaught items.";
            break;
          case 404:
            self.apiError.message = "Uncaught items were not found.";
            break;
          case 500:
            self.apiError.message = "An internal error occurred.";
            break;
          default:
            self.apiError.message = "An error occurred.";
            break;
        }
      });
    },

    /**
     * Emits an event to the parent component telling to go back to the last
     * screen; this applies to new item creation.
     */
    goBack: function() {
      this.$emit("goBackStep1");
    },

    /**
     * Gets called from the @itemDeleted event emission from the delete Modal.
     */
    markItemDeleted: function() {
      this.isDeleteError = false;
      this.isDeleted = true;
      setTimeout(function() {
        // This record doesn't exist anymore, so send the user back to the
        // redirect items list page.
        window.location.replace("/redirects/list");
      }, 3000);
    },

    /**
     * Marks the item delete error; gets called if there is an error in deleting
     * an item.
     */
    markItemDeleteError: function() {
      let self = this;
      this.isDeleted = false;
      this.isDeleteError = true;
      setTimeout(function() {
        self.isDeleteError = false;
      }, 5000);
    },

    /**
     * Removes the specified uncaught item from the recommended list by marking
     * its isRecommended field to false.
     * @param {Uncaught} uncaught The uncaught item to be removed from the
     * recommended list.
     */
    removeFromRecommended: function(uncaught) {
      let self = this; // "this" loses scope within Axios.
      self.currentStatus = null
      /* Ajax (Axios) Submission */
      axios({
        method: "put",
        url: "/api/uncaughts/",
        data: {
          id: uncaught.id,
          isRecommended: false,
          link: uncaught.link,
          visits: uncaught.visits
        }
      })
      .then(function(response) { // Success.
        self.fetchUncaughts(); // Get the uncaught items again after this update.
      })
      .catch(function(error) { // Failure.
        self.currentStatus = STATUS_SAVE_FAILED
      });
    },

    /**
     * Updates the record's fromLink to the specified link.
     * @param {string} fromLink The fromLink of the record.
     */
    setFromLink(fromLink) {
      this.record.fromLink = fromLink;
    },

    /**
     * Submits the form via the API.
     */
    submitRedirect: function() {
      let self = this; // "this" loses scope within Axios.
      self.currentStatus = null
      let method = this.itemExists ? "put" : "post";
      let route = "/api/redirects/";
      let errors = []

      /* Ajax (Axios) Submission */
      axios({
        method: method,
        url: route,
        data: self.record
      })
      .then(function(response) { // Success.
        self.record.id = response.data.id; // This sets the redirect's ID.

        // If there is an uncaught item that has the same link as this record's fromLink, delete it.
        let id = -1;

        for (let i = 0; i < self.fetchedUncaughts.length; i++) {
          if (self.fetchedUncaughts[i].link == self.record.fromLink) {
            id = self.fetchedUncaughts[i].id;
            break;
          }
        }

        if (id != -1) {
          self.deleteUncaught(id);
        }

        self.afterSubmitSucceeds();
      })
      .catch(function(error) { // Failure.
        self.currentStatus = STATUS_SAVE_FAILED
        self.apiError.status = error.response.status;
        self.apiError.message = error.response.data;
      });
    },

    /**
     * Sets the variable @isEditMode to the appropriate boolean value.
     */
    toggleEdit: function() {
      (this.isEditMode === true) ? this.isEditMode = false : this.isEditMode = true;
    }
  },

  filters: {}
};
</script>
