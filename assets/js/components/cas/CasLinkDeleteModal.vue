<template>
  <div class="modal d-block" tabindex="-1" role="dialog" style="background: rgba(0,0,0,0.5)">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Delete link</h5>
          <button type="button" class="close" @click="$emit('close')" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <p>Are you sure you want to delete this link? Type the word <strong>"delete"</strong> to confirm.</p>
          <div class="form-group">
            <input type="text" v-model="deleteConfirm" class="form-control" />
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" @click="$emit('close')">Cancel</button>
          <button
            type="button"
            class="btn btn-danger"
            @click="deleteItem"
            :disabled="deleteConfirm !== 'delete'"
          >Delete link</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: ["link"],
  data() {
    return {
      deleteConfirm: null
    };
  },
  methods: {
    deleteItem() {
      if (this.deleteConfirm !== "delete") return;

      let self = this;
      this.deleteConfirm = null;

      axios.delete("/api/gradcas/links/" + this.link.id)
        .then(function() {
          self.$emit("itemDeleted");
        })
        .catch(function() {
          self.$emit("itemDeleteError");
        });
    }
  }
};
</script>
