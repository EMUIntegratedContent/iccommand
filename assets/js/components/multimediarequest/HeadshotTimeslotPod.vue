<template>
    <div class="col-xs-12 col-sm-6 col-md-4 pl-4 pb-2">
        <div class="card" :class="{'alert-danger': isDeleted, 'alert-success': isUpdated}">
            <div class="card-header">
                {{ currentTimeSlot.startTime }} - {{ currentTimeSlot.endTime }}
                <button type="button" @click="openDeleteTimeslotModal" class="close pull-right"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="card-body">
                <div v-if="isUpdatedError || isDeletedError" class="alert alert-danger" role="alert">
                    <p>{{ actionType }} failed! Please try again.</p>
                </div>
                <!-- Start time -->
                <div class="form-group">
                    <label :for="'startTime-' + this.$vnode.key">Start time *</label>
                    <multiselect
                            v-validate="'required'"
                            data-vv-as="start time"
                            v-model="currentTimeSlot.startTime"
                            :options="timeSlotOptions"
                            :multiple="false"
                            placeholder="Start time"
                            :id="'startTime-' + this.$vnode.key"
                            class="form-control"
                            style="padding:0"
                            @input="isModified = true"
                            :name="'startTime-' + this.$vnode.key"
                            :class="{'is-invalid': errors.has('startTime-' + this.$vnode.key) }"
                    >
                    </multiselect>
                    <div class="invalid-feedback">
                        {{ errors.first('startTime-' + this.$vnode.key) }}
                    </div>
                </div>
                <!-- Start time -->
                <div class="form-group">
                    <label :for="'endTime-' + this.$vnode.key">End time *</label>
                    <multiselect
                            v-validate="'required'"
                            data-vv-as="end time"
                            v-model="currentTimeSlot.endTime"
                            :options="timeSlotOptions"
                            :multiple="false"
                            placeholder="End time"
                            :id="'endTime-' + this.$vnode.key"
                            class="form-control"
                            style="padding:0"
                            @input="isModified = true"
                            :name="'endTime-' + this.$vnode.key"
                            :class="{'is-invalid': errors.has('endTime-' + this.$vnode.key) }"
                    >
                    </multiselect>
                    <div class="invalid-feedback">
                        {{ errors.first('endTime-' + this.$vnode.key) }}
                    </div>
                </div>
            </div>
            <div v-if="isModified" class="card-footer">
                <button type="button" @click="checkForm" class="btn btn-sm btn-primary pull-left"><i class="fa fa-save"></i></button>
                <button type="button" @click="resetTimeSlot" class="btn btn-sm btn-default pull-right"><i class="fa fa-undo"></i></button>
            </div>
        </div>
        <!-- DELETE IMAGE MODAL -->
        <headshot-timeslot-delete-modal
                :podIndex="this.$vnode.key"
                :id="'deleteModal-' + this.$vnode.key"
                class="deleteModal"
                :timeSlot="this.currentTimeSlot"
                @timeslotDeleteRequested="deleteTimeSlot"
        ></headshot-timeslot-delete-modal>
    </div>
</template>
<style scoped>

</style>
<style src="vue-multiselect/dist/vue-multiselect.min.css"></style>
<script>
    import moment from 'moment'
    import Multiselect from 'vue-multiselect'
    import HeadshotTimeslotDeleteModal from './HeadshotTimeslotDeleteModal'

    export default {
        components: {Multiselect, HeadshotTimeslotDeleteModal},
        props: {
            timeSlot: {
                type: Object,
                required: true,
            }
        },
        data: function () {
            return {
                apiError: {
                    message: null,
                    status: null
                },
                currentTimeSlot: null,
                deleteTimeslotData: null,
                isDeleted: false,
                isDeletedError: false,
                isModified: false,
                isUpdated: false,
                isUpdatedError: false,
                now: moment(),
                originalSlotData: null,
                selectedDate: moment(),
                timeSlotOptions: [
                    '12:00 am','12:30 am', '1:00 am', '1:30 am', '2:00 am', '2:30 am', '3:00 am', '3:30 am',
                    '4:00 am', '4:30 am', '5:00 am', '5:30 am', '6:00 am', '6:30 am', '7:00 am', '7:30 am',
                    '8:00 am', '8:30 am', '9:00 am', '9:30 am', '10:00 am', '10:30 am', '11:00 am', '11:30 am',
                    '12:00 pm', '12:30 pm', '1:00 pm', '1:30 pm', '2:00 pm', '2:30 pm', '3:00 pm', '3:30 pm',
                    '4:00 pm', '4:30 pm', '5:00 pm', '5:30 pm', '6:00 pm', '6:30 pm', '7:00 pm', '7:30 pm',
                    '8:00 pm', '8:30 pm', '9:00 pm', '9:30 pm', '10:00 pm', '10:30 pm', '11:00 pm', '11:30 pm',
                ],
            }
        },
        created: function () {
            this.currentTimeSlot = this.timeSlot
            this.originalSlotData = JSON.parse(JSON.stringify(this.timeSlot))
        },
        mounted: function () {
        },
        computed: {
            actionType: function(){
                if(this.isUpdatedError){
                    return 'Update'
                }
                if(this.isDeletedError){
                    return 'Delete'
                }
                return ''
            }
        },
        methods: {
            // Run prior to submitting
            checkForm: function () {
                let self = this
                this.$validator.validateAll()
                    .then((result) => {
                        // if all fields valid, submit the form
                        if (result) {
                            self.submitTimeSlot()
                            return
                        }
                    })
                    .catch((error) => {
                        self.apiError.status = 500
                        self.apiError.message = "Something went wrong that wasn't validation related."
                    });
            },
            deleteTimeSlot: function(){
                let self = this
                axios.delete('/api/multimediarequests/' + this.currentTimeSlot.id + '/headshotdate')
                    .then(function(response) {
                        self.isDeleted = true
                        setTimeout(function(){
                            self.isDeleted = false
                            self.$emit('removeTimeSlot', this.currentTimeSlot)
                        }, 3000)
                    })
                    .catch(function(error) {
                        self.isDeletedError = true
                        setTimeout(function(){
                            self.isDeletedError = false
                        }, 5000)
                    })
            },
            openDeleteTimeslotModal: function(){
                this.deleteTimeslotData = JSON.parse(JSON.stringify(this.currentTimeSlot))
                $('#deleteModal-' + this.$vnode.key).modal('show')
            },
            // take the original time slot data and make it the current data
            resetTimeSlot: function(){
                this.isModified = false
                this.currentTimeSlot = JSON.parse(JSON.stringify(this.originalSlotData))
            },
            submitTimeSlot: function(){
                let self = this
                let method = (this.currentTimeSlot.id > 0) ? 'put' : 'post'  //new time will have a NEGATIVE placeholder ID (value set in parent component)
                let route = (this.currentTimeSlot.id > 0) ? '/api/multimediarequest/headshotdate' : '/api/multimediarequests/headshotdates'
                axios({
                    method: method,
                    url: route,
                    data: this.currentTimeSlot
                })
                // success
                    .then(function (response) {
                        self.currentTimeSlot = response.data // update the time slot with the returned data from the server
                        self.isUpdated = true
                        self.isModified = false
                        setTimeout(function(){
                            self.$emit('updateTimeSlot', self.currentTimeSlot)
                            self.isUpdated = false
                        }, 3000)
                    })
                    // fail
                    .catch(function (error) {
                        self.isUpdatedError = true
                        setTimeout(function(){
                            self.isUpdatedError = false
                        }, 5000)
                    })
            }
        },
        watch: {},
        events: {},
        filters: {},
    };
</script>
