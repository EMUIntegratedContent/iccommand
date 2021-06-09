<template>
  <!-- Modal -->
  <div v-if="this.image" :id="'editImageModal-' + podIndex" class="modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Edit image information</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label for="name">Image name</label>
            <input type="text" v-model="image.name" class="form-control" id="name"/>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" @click="imageEditCanceled">Cancel</button>
          <button type="button" class="btn btn-success" data-dismiss="modal" @click="imageEditRequested" :disabled="image.name == ''">Update</button>
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
  mounted() {

  },
  computed: {

  },
  methods: {
      // Emit an event to the parent component telling it to delete the image
      imageEditRequested: function(){
          this.$emit('imageEditRequested')
      },
      // restore original image info and close modal
      imageEditCanceled: function(){
          this.$emit('imageEditCanceled')
          $('#editImageModal-' + this.podIndex).modal('hide')
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
