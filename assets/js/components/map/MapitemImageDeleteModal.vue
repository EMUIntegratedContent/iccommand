<template>
  <!-- Modal -->
  <div v-if="this.image" :id="'deleteImageModal-' + podIndex" class="modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Delete image {{ image.name }}</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <p>Are you sure you want to delete the image "{{ image.name }}"? Type the word <strong>"delete"</strong> to confirm.</p>
          <div class="form-group">
            <label for="deleteConfirm" class="sr-only" aria-hidden="true">Type "confirm" to delete</label>
            <input type="text" v-model="deleteConfirm" class="form-control" id="deleteConfirm"/>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" @click="imageDeleteCanceled">Cancel</button>
          <button type="button" class="btn btn-danger" data-dismiss="modal" @click="imageDeleteRequested" :disabled="deleteConfirm != 'delete'">Delete image</button>
        </div>
      </div>
    </div>
  </div>
</template>
<style scoped>

</style>
<script>
export default {
  directives: {},
  components: {},
  props: {
    image: {
        type: Object,
        required: false
    },
    podIndex: {
      default: 0
    }
  },
  data: function() {
    return {
      deleteConfirm: null,
    }
  },
  ready: function() {

  },
  computed: {

  },
  methods: {
    // Emit an event to the parent component telling it to delete the image
    imageDeleteRequested: function(){
      if(this.deleteConfirm == 'delete'){
        this.$emit('imageDeleteRequested')
        this.deleteConfirm = null // reset text field
      }
    },
    // restore original image info and close modal
    imageDeleteCanceled: function(){
        this.$emit('imageDeleteCanceled')
        this.deleteConfirm = null // reset text field
        $('#deleteImageModal-' + this.podIndex).modal('hide')
    }
  },
  filters: {

  },
  events: {

  },
  watch: {

  },
}
</script>
