<template>
  <div class="col-xs-12 col-sm-6 col-md-4 pl-4 pb-2">
    <div class="card" :class="{'alert-danger': isDeleted, 'alert-success': isUpdated}">
      <div class="card-header">
        {{ currentTimeSlot.startTime }} - {{ currentTimeSlot.endTime }}
        <button type="button" @click="openDeleteTimeslotModal" class="close pull-right"><span
            aria-hidden="true">&times;</span></button>
      </div>
      <div class="card-body">
        <div v-if="isUpdatedError || isDeletedError" class="alert alert-danger" role="alert">
          <p>{{ actionType }} failed! Please try again.</p>
        </div>
        <!-- Start time -->
        <div class="form-group">
          <label :for="'startTime-' + slotIndex">Start time *</label>
          <VueMultiselect
              v-model="currentTimeSlot.startTime"
              :options="timeSlotOptions"
              :multiple="false"
              placeholder="Start time *"
              style="padding:0"
              class="form-control"
              :id="'startTime-' + slotIndex"
              :name="'startTime-' + slotIndex"
              @select="isModified = true"
          >
          </VueMultiselect>
        </div>
        <!-- Start time -->
        <div class="form-group">
          <label :for="'endTime-' + this.slotIndex">End time *</label>
          <VueMultiselect
              v-model="currentTimeSlot.endTime"
              :options="timeSlotOptions"
              :multiple="false"
              placeholder="End time"
              style="padding:0"
              class="form-control"
              :id="'endTime-' + slotIndex"
              :name="'endTime-' + slotIndex"
              @select="isModified = true"
          >
          </VueMultiselect>
        </div>
      </div>
      <div v-if="isModified" class="card-footer">
        <button type="button" @click="submitTimeSlot" class="btn btn-sm btn-primary pull-left"><i
            class="fa fa-save"></i></button>
        <button type="button" @click="resetTimeSlot" class="btn btn-sm btn-default pull-right"><i
            class="fa fa-undo"></i></button>
      </div>
    </div>
    <!-- DELETE IMAGE MODAL -->
    <headshot-timeslot-delete-modal
        :podIndex="this.slotIndex"
        :id="'deleteModal-' + this.slotIndex"
        class="deleteModal"
        :timeSlot="this.currentTimeSlot"
        @timeslotDeleteRequested="deleteTimeSlot"
    />
  </div>
</template>
<style scoped>

</style>
<style src="vue-multiselect/dist/vue-multiselect.css"></style>
<script>
import moment from 'moment'
import VueMultiselect from "vue-multiselect"
import HeadshotTimeslotDeleteModal from './HeadshotTimeslotDeleteModal'
import {timeSlotsDaytimeArr} from '../../utils/timeSlots' // a custom file that holds an array of time slots

export default {
  components: {VueMultiselect, HeadshotTimeslotDeleteModal},
  props: {
    timeSlot: {
      type: Object,
      required: true,
    },
    slotIndex: {
      type: Number,
      required: true
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
      timeSlotOptions: timeSlotsDaytimeArr,
    }
  },
  created: function () {
    this.currentTimeSlot = this.timeSlot
    this.originalSlotData = JSON.parse(JSON.stringify(this.timeSlot))
  },
  computed: {
    actionType: function () {
      if (this.isUpdatedError) {
        return 'Update'
      }
      if (this.isDeletedError) {
        return 'Delete'
      }
      return ''
    }
  },
  methods: {
    deleteTimeSlot: function () {
      let self = this

      axios.delete('/api/multimediarequests/headshotdates/' + this.currentTimeSlot.id)
          .then(function (response) {
            self.isDeleted = true
            setTimeout(function () {
              self.isDeleted = false
              self.$emit('removeTimeSlot', self.currentTimeSlot)
              $('#deleteModal-' + this.slotIndex).modal('hide')
            }, 2000)
          })
          .catch(function (error) {
            self.isDeletedError = true
            setTimeout(function () {
              self.isDeletedError = false
            }, 5000)
          })
    },
    openDeleteTimeslotModal: function () {
      // time slots with an id less than 0 are new. When these are clicked for deletion, just delete the time slot without calling the modal
      if (this.currentTimeSlot.id < 0) {
        this.$emit('removeTimeSlot', this.currentTimeSlot)
      } else {
        this.deleteTimeslotData = JSON.parse(JSON.stringify(this.currentTimeSlot))
        $('#deleteModal-' + this.slotIndex).modal('show')
      }
    },
    // take the original time slot data and make it the current data
    resetTimeSlot: function () {
      this.isModified = false
      this.currentTimeSlot = JSON.parse(JSON.stringify(this.originalSlotData))
    },
    submitTimeSlot: function () {
      let self = this
      let timeSlotBeforeSubmit = this.currentTimeSlot
      let method = (timeSlotBeforeSubmit.id > 0) ? 'put' : 'post'  //new time will have a NEGATIVE placeholder ID (value set in parent component)
      let route = '/api/multimediarequests/headshotdates'
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
            setTimeout(function () {
              if (timeSlotBeforeSubmit.id > 0) {
                // emit update
                self.$emit('updateTimeSlot', self.currentTimeSlot)
              } else {
                // emit add
                self.$emit('addTimeSlot', {
                  'oldSlot': timeSlotBeforeSubmit,
                  'newSlot': self.currentTimeSlot
                })
              }
              self.isUpdated = false
            }, 3000)
          })
          // fail
          .catch(function (error) {
            self.isUpdatedError = true
            setTimeout(function () {
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
