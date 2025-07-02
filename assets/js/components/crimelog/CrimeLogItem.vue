<template>
  <div>
   <div v-if="this.permissions[0].user">
      <component
        :is="currentComponent"
        @openUploadModal="openUploadModal"
        :itemType="itemType" :itemExists="false"
        :permissions="this.permissions"
        :newForm="true" startMode="edit"></component>
      <div>
        <button id="btnSetComponent" @click="setComponent(component)">{{ component }}</button>
      </div>
    </div>
  </div>

  <!-- Upload Modal -->
  <div id="uploadModal"  class="modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Crime Log CSV Upload</h5>
          <button type="button" class="close" @click="resetUploader" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <form enctype="multipart/form-data" novalidate v-if="isUploadInitial">
          <div class="modal-body">
            <div class="form-group">
                <label>
                  Upload a CSV file:
                  <input type="file" name="uploadCsv" id="uploadCsv" class="form-control-file" accept="text/csv"
                         @change="onFileChange">
                  <span class="custom-file-control"></span>
                </label>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-default" @click="resetUploader">Cancel</button>
          </div>
        </form>

        <template v-if="isUploadSaving">
          <div class="modal-body">
            <p style="text-align: center"><img src="/images/loading.gif" alt="Loading..."/></p>
          </div>
        </template>

        <template v-if="isUploadSuccess">
          <div class="modal-body">
            <p>Success!</p>
            <p class="overflow-auto" style="max-height: 250px" v-html="processMessage"></p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-default" @click="resetUploader">Close</button>
          </div>
        </template>

        <template v-if="isUploadFailed">
          <div class="modal-body">
            <p class="text-danger">Failed</p>
            <p class="overflow-auto" style="max-height: 250px" v-html="uploadErrors"></p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-default" @click="resetUploader">Close</button>
          </div>
        </template>

      </div>
    </div>
  </div>
</template>

<style></style>

<script>
//import CrimeLogItemForm from "./CrimeLogItemForm.vue";

const STATUS_INITIAL = 0, STATUS_SAVING = 1, STATUS_SUCCESS = 2, STATUS_FAILED = 3;

export default {
  components: {
    //"crimelog-item-form": CrimeLogItemForm,
  },

  props: {
    permissions: {
      type: Array,
      required: true
    }
  },

  data: function() {
    return {
      /**
       * An array of the two components of creating a new crimelog.
       * @type {Array.<string>}
       */
      //componentsArray: ["crimelog-item-form"],

      /**
       * The component that the user is on currently.
       * @type {string}
       */
      currentComponent: "crimelog-item",

      /**
       * The item type of the crimelog.
       * @type {string}
       */
      itemType: "",
      currentStatus: null,
      processMessage: null,
      uploadErrors: []
    };
  },

  computed: {
     // CSV Upload
    isUploadInitial() {
        return this.currentStatus === STATUS_INITIAL || this.currentStatus === null
    },
    isUploadSaving() {
        return this.currentStatus === STATUS_SAVING
    },
    isUploadSuccess() {
        return this.currentStatus === STATUS_SUCCESS
    },
    isUploadFailed() {
        return this.currentStatus === STATUS_FAILED
    },
  },

  methods: {
    /**
     * Changes the component to the specified component.
     * @param {string} component The component to replace the current component.
     */
    setComponent: function(component) {
      this.currentComponent = component;
    },

    /**
     * Changes the item type to the specified type and change the form appropriately.
     * @param {string} itemType The item type to replace the current type.
     */
    setItemType: function(itemType) {
      this.itemType = itemType; // Set the item type to pass to the form.
      this.setComponent("crimelog-item"); // Change the form for the new crimelog item.
    },
    onFileChange: function(e) {
        let files = e.target.files || e.dataTransfer.files;
        if (!files.length)
            return;
        this.uploadCsv(files[0]);
    },
    uploadCsv: function(file) {
        this.currentStatus = STATUS_SAVING;

        let self = this; // 'this' loses scope within axios

        let formData = new FormData();
        formData.append('csv', file);

        // AJAX (axios) submission
        axios({
            method: 'post',
            headers: {
                'Content-Type': 'multipart/form-data'
            },
            url: 'api/crimelog/upload',
            data: formData
        })
        // success
            .then(function (response) {
                console.log(response);
                self.currentStatus = STATUS_SUCCESS;
                self.processMessage = response.data;
            })
            // fail
            .catch(function (error) {
                console.log(error);
                self.currentStatus = STATUS_FAILED;
                self.uploadErrors = error.message;
            })
    },
    resetUploader: function(){
        this.currentStatus = STATUS_INITIAL
        $('#uploadModal').modal('hide');
    },
    openUploadModal: function(){
        this.currentStatus = STATUS_INITIAL;
        $('#uploadModal').modal('show');
        $('#uploadCsv').click();
    }
  },

  events: {}
};
</script>
<style scoped>
#btnSetComponent {
  display:none;
}
</style>
