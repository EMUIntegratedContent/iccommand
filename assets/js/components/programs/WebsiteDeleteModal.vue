<template>
  <!-- Delete Modal -->
  <div id="deleteModal" class="modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Delete website</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <p>Are you sure you want to delete this website? Type the word <strong>"delete"</strong> to confirm.</p>
          <div class="form-group">
            <label for="form-group" class="sr-only" aria-hidden="true">Type "confirm" to delete.</label>
            <input type="text" v-model="deleteConfirm" class="form-control" id="deleteConfirm"/>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
          <button
            type="button"
            class="btn btn-danger"
            data-dismiss="modal"
            @click="deleteWebsite"
            :disabled="deleteConfirm != 'delete'">Delete website</button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped></style>

<script>
export default {
  props: ["website"],

  data: function() {
    return {
      /**
       * The confirmation of the user for the deletion of the website.
       * @type {string}
       */
      deleteConfirm: null
    };
  },
  methods: {
    /**
     * Deletes the website.
     */
    deleteWebsite: function() {
      let self = this;

      // The word "delete" must be typed in modal.
      if (this.deleteConfirm === "delete") {
        // Reset the delete text.
        this.deleteConfirm = null;

        axios.delete("/api/programs/websites/" + this.website.id)
        .then(function() { // Success.
          // Fire the delete event to the parent.
          self.websiteDeleted();
        })
        .catch(function() { // Failure.
          self.websiteDeleteError();
        });
      }
    },

    /**
     * Emits an event to the parent component telling it that the website has been deleted.
     */
    websiteDeleted: function() {
      this.$emit("websiteDeleted");
    },

    /**
     * Emits an event to the parent component telling it that the website has not been deleted.
     */
    websiteDeleteError: function() {
      this.$emit("websiteDeleteError");
    }
  }
}
</script>
