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
      This redirect has been deleted. You will now be redirected to the redirect list page.
    </div>

    <!-- **************************** Main Area **************************** -->
    <div v-if="isDataLoaded === true && isDeleted === false && is404 === false">
      <heading>
        <span v-if="!itemExists" slot="title">Step 2/2: Provide redirect information</span>
        <span v-else slot="title">Existing Redirect Information</span>
      </heading>
      <div class="btn-group" role="group" aria-label="form navigation buttons">
        <button v-if="newForm" class="btn btn-info pull-left" @click="goBack()"><i class="fa fa-chevron-circle-left"></i> Step 1</button>
        <button v-if="itemExists && this.permissions[0].user" type="button" class="btn btn-info pull-right" @click="toggleEdit"><span v-html="lockIcon"></span></button>
      </div>
      <div class="pt-2" id="redirectItemTabContent">
        <form class="form" @submit.prevent="checkForm">
          <fieldset>
            <legend>Basic Information</legend>
            <div class="form-group">
              <template v-if="record.itemType == 'broken link'">
                <label>Broken Link *</label>
              </template>
              <template v-if="record.itemType == 'shortened link'">
                <label>Shortened/Vanity Link *</label>
              </template>
              <input
                v-validate="'required'"
                name="fromLink"
                type="text"
                class="form-control"
                :class="{'is-invalid': errors.has('fromLink'), 'form-control-plaintext': !userCanEdit || !isEditMode}"
                :readonly="!userCanEdit || !isEditMode"
                v-model="record.fromLink"/>
              <div class="invalid-feedback">
                {{ errors.first("fromLink") }}
              </div>
            </div>
            <div class="form-group">
              <template v-if="record.itemType == 'broken link'">
                <label>Actual Link *</label>
              </template>
              <template v-if="record.itemType == 'shortened link'">
                <label>Full Link *</label>
              </template>
              <input
                v-validate="'required'"
                name="toLink"
                type="text"
                class="form-control"
                :class="{'is-invalid': errors.has('fromLink'), 'form-control-plaintext': !userCanEdit || !isEditMode}"
                :readonly="!userCanEdit || !isEditMode"
                v-model="record.toLink"/>
              <div class="invalid-feedback">
                {{ errors.first("toLink") }}
              </div>
            </div>
          </fieldset>
          <div v-if="this.$validator.errors.count() > 0" class="alert alert-danger fade show" role="alert">
            You have <strong>{{ this.$validator.errors.count() }} error<span v-if="this.$validator.errors.count() > 1">s</span></strong> in your submission:
            <ul>
              <li v-for="error in this.$validator.errors.all()">
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
          <!--
            <div class="thumbnail-container">
              <div class="thumbnail">
                <iframe src="http://www.emich.edu/flute" frameborder="0"></iframe>
              </div>
            </div>
          -->
          <!-- Action Buttons -->
          <div v-if="userCanEdit && isEditMode" aria-label="action buttons" class="mb-4">
            <button class="btn btn-success" type="submit"><i class="fa fa-save fa-2x"></i></button>
            <button v-if="itemExists && this.permissions[0].user" type="button" class="btn btn-danger ml-4" data-toggle="modal" data-target="#deleteModal"><i class="fa fa-trash fa-2x"></i></button>
          </div>
        </form>
      </div>
    </div>

    <!-- ************************ Delete Item Modal ************************ -->
    <redirect-item-delete-modal
      :redirect="record"
      @itemDeleted="markItemDeleted"
      @itemDeleteError="markItemDeleteError"></redirect-item-delete-modal>
  </div>
</template>

<style>
/*
.thumbnail iframe {
  width: 1440px;
  height: 900px;
}

.thumbnail {
  position: relative;
  -ms-zoom: 0.25;
  -moz-transform: scale(0.25);
  -moz-transform-origin: 0 0;
  -o-transform: scale(0.25);
  -o-transform-origin: 0 0;
  -webkit-transform: scale(0.25);
  -webkit-transform-origin: 0 0;
}

.thumbnail:after {
  content: "";
  display: block;
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
}

.thumbnail-container {
  width: calc(1440px * 0.25);
  height: calc(900px * 0.25);
  display: inline-block;
  overflow: hidden;
  position: relative;
}
*/
</style>

<script>
import Draggable from "vuedraggable";
import Heading from "../utils/Heading.vue";
import Multiselect from "vue-multiselect";
import NotFound from "../utils/NotFound.vue";
import RedirectItemDeleteModal from "./RedirectItemDeleteModal.vue";

const STATUS_INITIAL = 0;

export default {
  created() {},

  mounted() {
    document.addEventListener("click", this.toggleDragEnable);
    this.resetUploadForm();

    // Detect if the form should be in edit mode from the start; default is false.
    if (this.startMode == "edit") {
      this.isEditMode = true;
    }

    if (this.itemExists === false) {
      // Set the kind of redirect item being created via the itemType property from NewRedirectItemChoices.
      this.isDataLoaded = true;
      this.record.itemType = this.itemType;
    } else {
      // Fetch the existing record using the property itemId.
      this.fetchRedirectItem(this.itemId);
    }

    console.log("Redirect form mounted.");
  },

  components: {Heading, Multiselect, RedirectItemDeleteModal, NotFound, Draggable},

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
      apiError: {
        message: null,
        status: null
      },
      currentStatus: null,
      is404: false,
      isDataLoaded: false,
      isDeleted: false,
      isDeleteError: false,
      isEditMode: false, // This is true if the forms are editable.
      isModalOpen: false,
      record: {
        id: "",
        fromLink: "",
        toLink: "",
        itemType: "",
      },
      success: false,
      successMessage: "",
    }
  },

  computed: {
    /**
     * Determines if there are errors.
     * @return {boolean} True if there are errors; false otherwise.
     */
    haveErrors: function() {
      return (this.$validator.errors.count() > 0) ? true : false;
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

      // Remove the message after three seconds.
      setTimeout(function() {
        self.success = false
      }, 3000);
    },

    /**
     * Checks the form; this will run prior to submitting.
     */
    checkForm: function() {
      let self = this;
      this.$validator.validateAll()
      .then((result) => { // Success.
        // If all fields are valid, submit the form.
        if (result) {
          self.submitForm();
          return;
        }
      })
      .catch((error) => { // Failure.
        self.apiError.status = 500;
        self.apiError.message = "Something went wrong that wasn't validation related.";
      });
    },

    /**
     * Gets the redirect item by using the specified id.
     * @param {string} itemId The id of the redirect item.
     */
    fetchRedirectItem(itemId) {
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
     * Resets the form to the initial state.
     */
    resetUploadForm: function() {
      this.currentStatus = STATUS_INITIAL;
      this.uploadedFiles = [];
      this.uploadErrors = [];
    },

    /**
     * Submits the form via the API.
     */
    submitForm: function() {
      let self = this; // "this" loses scope within axios.
      let method = this.itemExists ? "put" : "post";
      let route =  this.itemExists ? "/api/redirect" : "/api/redirects";

      /* Ajax (Axios) Submission */
      axios({
        method: method,
        url: route,
        data: self.record
      })
      .then(function(response) { // Success.
        self.record.id = response.data.id; // This sets the item's ID.
        self.afterSubmitSucceeds();
      })
      .catch(function(error) { // Failure.
        let errors = error.response.data;

        // Add any validation errors to the vee validator error bag.
        errors.forEach(function(error) {
          let key = error.property_path;
          let message = error.message;
          self.$validator.errors.add(key, message);
        });
      });
    },

    /**
     * Sets the variable @isEditMode to the appropriate boolean value.
     */
    toggleEdit: function() {
      (this.isEditMode === true) ? this.isEditMode = false : this.isEditMode = true;
    },
  },

  filters: {}
};
</script>
