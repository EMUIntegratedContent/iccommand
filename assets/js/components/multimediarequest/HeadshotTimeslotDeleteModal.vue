<template>
    <!-- Modal -->
    <div v-if="this.timeSlot" :id="'deleteImageModal-' + podIndex" class="modal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete time slot</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the time slot from {{ this.timeSlot.startTime }} to {{ this.timeSlot.endTime }}? Type the word <strong>"delete"</strong> to confirm.</p>
                    <div class="form-group">
                        <label for="deleteConfirm" class="sr-only" aria-hidden="true">Type "confirm" to delete</label>
                        <input type="text" v-model="deleteConfirm" class="form-control" id="deleteConfirm"/>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" @click="timeslotDeleteCanceled">Cancel</button>
                    <button type="button" class="btn btn-danger" data-dismiss="modal" @click="timeslotDeleteRequested"
                            :disabled="deleteConfirm != 'delete'">Delete image
                    </button>
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
            timeSlot: {
                type: Object,
                required: false
            },
            podIndex: {
                default: 0
            }
        },
        data: function () {
            return {
                deleteConfirm: null,
            }
        },
        ready: function () {

        },
        computed: {},
        methods: {
            // Emit an event to the parent component telling it to delete the time slot
            timeslotDeleteRequested: function () {
                if (this.deleteConfirm == 'delete') {
                    this.$emit('timeslotDeleteRequested')
                    this.deleteConfirm = null // reset text field
                }
            },
            // restore original image info and close modal
            timeslotDeleteCanceled: function () {
                this.deleteConfirm = null // reset text field
                $('#deleteModal-' + this.podIndex).modal('hide')
            }
        },
        filters: {},
        events: {},
        watch: {},
    }
</script>
