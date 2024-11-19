<template>
  <!-- Delete Modal -->
  <div id="deleteModal" class="modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Delete program</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <p>Are you sure you want to delete this program? Type the wrong <strong>"delete"</strong> to confirm.</p>
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
            @click="deleteProgram"
            :disabled="deleteConfirm != 'delete'">Delete program</button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped></style>

<script>
export default {
  directives: {},

  components: {},

  props: ["program"],

  data: function() {
    return {
      /**
       * The confirmation of the user for the deletion of the program.
       * @type {string}
       */
      deleteConfirm: null
    };
  },

  ready: function() {},

  computed: {},

  methods: {
    /**
     * Deletes the program.
     */
    deleteProgram: function() {
      let self = this;

      // The word "delete" must be typed in modal.
      if (this.deleteConfirm === "delete") {
        // Reset the delete text.
        this.deleteConfirm = null;

        axios.delete("/api/programs/" + this.program.id)
        .then(function(response) { // Success.
          // Fire the delete event to the parent.
          self.programDeleted();
        })
        .catch(function(error) { // Failure.
          self.programDeleteError();
        });
      }
    },

    /**
     * Emits an event to the parent component telling it that the program has been deleted.
     */
    programDeleted: function() {
      this.$emit("programDeleted");
    },

    /**
     * Emits an event to the parent component telling it that the program has not been deleted.
     */
    programDeleteError: function() {
      this.$emit("programDeleteError");
    }
  }
}
</script>
