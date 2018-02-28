<template>
  <!-- Modal -->
  <div id="deleteModal" class="modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Delete {{ mapitem.itemType }}</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <p>Are you sure you want to delete "{{ mapitem.name }}"? Type the word <strong>"delete"</strong> to confirm.</p>
          <div class="form-group">
            <label for="deleteConfirm" class="sr-only" aria-hidden="true">Type "confirm" to delete</label>
            <input type="text" v-model="deleteConfirm" class="form-control" id="deleteConfirm"/>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-danger" data-dismiss="modal" @click="delItem" :disabled="deleteConfirm != 'delete'">Delete {{ mapitem.itemType }}</button>
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
  props: ['mapitem'],
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
    delItem: function() {
      let self = this
      // word 'delete' must be typed in modal
      if(this.deleteConfirm == 'delete'){
        this.deleteConfirm = null; // reset delete text
        axios.delete('/api/mapitems/' + this.mapitem.id)
        .then(function(response) {
          self.itemDeleted() // fire delete event to parent
        })
        .catch(function(error) {
          self.itemDeleteError()
        })
      }
    },
    // Emit an event to the parent component telling it the item has been deleted
    itemDeleted: function(){
      this.$emit('itemDeleted')
    },
    // Emit an event to the parent component telling it the item has been deleted
    itemDeleteError: function(){
      this.$emit('itemDeleteError')
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
