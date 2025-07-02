<template>
  <!-- Delete Modal -->
  <div id="deleteModal" class="modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Delete redirect</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <p>Are you sure you want to delete this redirect? Type the word <strong>"delete"</strong> to confirm.</p>
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
            @click="deleteItem"
            :disabled="deleteConfirm != 'delete'">Delete redirect</button>
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

  props: ["redirect"],

  data: function() {
    return {
      /**
       * The confirmation of the user for the deletion of the redirect.
       * @type {string}
       */
      deleteConfirm: null
    };
  },

  ready: function() {},

  computed: {},

  methods: {
    /**
     * Deletes the redirect item.
     */
    deleteItem: function() {
      let self = this;

      // The word "delete" must be typed in modal.
      if (this.deleteConfirm == "delete") {
        // Reset the delete text.
        this.deleteConfirm = null;

        axios.delete("/api/redirects/" + this.redirect.id)
        .then(function(response) { // Success.
          // Fire the delete event to the parent.
          self.itemDeleted();
        })
        .catch(function(error) { // Failure.
          self.itemDeleteError();
        });
      }
    },

    /**
     * Emits an event to the parent component telling it that the item has been deleted.
     */
    itemDeleted: function() {
      this.$emit("itemDeleted");
    },

    /**
     * Emits an event to the parent component telling it that the item has not been deleted.
     */
    itemDeleteError: function() {
      this.$emit("itemDeleteError");
    }
  },

  filters: {},

  events: {},

  watch: {}
}
</script>
