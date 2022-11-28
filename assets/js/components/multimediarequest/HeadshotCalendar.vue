<template>
    <div class="row">
        <!-- SIDEBAR calendar -->
        <div class="col-sm-12 col-md-4">
            <calendar-event-picker
                entity-name="headshots"
                @calendarDayClicked="fetchPhotoshootsForDate">
            </calendar-event-picker>
        </div>
        <!-- Scheduled headshot time slots for this date -->
        <div class="col-sm-12 col-md-8">
            <div v-if="isDataLoaded === false">
                <p style="text-align: center"><img src="/images/loading.gif" alt="Loading..."/></p>
            </div>
            <div v-else>
                <h1>Head shot time slots for {{ selectedDate.format('MMM D, YYYY') }}</h1>
                <div class="row">
                    <template v-if="timeSlots">
                        <headshot-timeslot-pod
                            v-for="(timeSlot, index) in timeSlots"
                            :time-slot="timeSlot"
                            :key="'timeslot-' + timeSlot.id"
                            :slot-index="index"
                            @addTimeSlot="placeNewTimeSlot"
                            @updateTimeSlot="updateTimeSlot"
                            @removeTimeSlot="removeTimeSlot">
                        </headshot-timeslot-pod>
                    </template>
                    <div v-show="!isTimeSlotAdded" class="card mapitem-add-aux col-xs-12 col-sm-6 col-md-4 pl-4 pb-2" @click="addTimeSlot">
                        <div class="card-body">
                            <i class="fa fa-plus fa-5x"></i><br />
                            Add time slot
                        </div>
                    </div>
                </div><!-- end .row -->
            </div>
        </div>
    </div>
</template>
<style scoped>

</style>
<style src="vue-multiselect/dist/vue-multiselect.css"></style>
<script>
    import moment from 'moment'
    import CalendarEventPicker from '../utils/CalendarEventPicker'
    import Multiselect from 'vue-multiselect'
    import HeadshotTimeslotPod from "./HeadshotTimeslotPod";

    export default {
        components: {HeadshotTimeslotPod, CalendarEventPicker, Multiselect},
        props: {

        },
        data: function () {
            return {
                isDataLoaded: true,
                isTimeSlotAdded: false,
                now: moment(),
                selectedDate: moment(),
                timeSlots: null,
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
          this.fetchPhotoshootsForDate(moment())
        },
        computed: {

        },
        methods: {
            addTimeSlot: function(){
                this.timeSlots.push({
                    id: -1,
                    dateOfShoot: this.selectedDate.format('YYYY-MM-DD'),
                    startTime: null,
                    endTime: null,
                })
                this.isTimeSlotAdded = true
            },
            fetchPhotoshootsForDate: function(moment){
                let self = this

                this.isDataLoaded = false // show loading wheel
                this.setSelectedDate(moment) // make the moment passed to this function the selected date

                let apiurl = '/api/multimediarequests/headshotdates/' + moment.format('YYYY') + '/' + moment.format('M') + '/' + moment.format('D')
                axios.get(apiurl)
                // success
                    .then(function (response) {
                        self.isDataLoaded = true // hide calendar wheel, show calendar
                        self.timeSlots = response.data
                    })
                    // fail
                    .catch(function (error) {
                        if (error.request.status == 404) {
                            self.is404 = true
                            self.isDataLoaded = true
                        }
                    })
            },
            placeNewTimeSlot: function(timeSlotObj){
                // replace the old time slot (at the appropriate index) with the new time slot
                this.timeSlots.splice(this.timeSlots.indexOf(timeSlotObj.oldSlot), 1, timeSlotObj.newSlot)
                this.rearrangeTimeSlotsByStartTime()

                // Allow user to add a new time slot
                this.isTimeSlotAdded = false

                // Emit an event so the calendar widget knows a time slot has been added
                this.$emit('eventQuantityChanged')
            },
            // times should always be shown with the earliest start time first
            rearrangeTimeSlotsByStartTime: function(){
                this.timeSlots.sort(function (a, b) {
                    // TUTORIAL: https://stackoverflow.com/questions/17064603/sort-string-array-containing-time-in-format-0900-am/17064665
                    return new Date('1970/01/01 ' + a.startTime) - new Date('1970/01/01 ' + b.startTime)
                })
            },
            removeTimeSlot: function(timeSlot){
                this.timeSlots.splice(this.timeSlots.indexOf(timeSlot), 1)

                // Allow user to add a new time slot, because the one that was deleted was a new one
                if(timeSlot.id < 0){
                    this.isTimeSlotAdded = false
                }

                // Emit an event so the calendar widget knows a time slot has been removed
                this.$emit('eventQuantityChanged')
            },
            setSelectedDate: function(moment){
                this.selectedDate = moment
            },
            updateTimeSlot: function(timeSlot){
                // Slot cannot be directly found by indexOf, because the updated object contains different values
                // Therefore, loop through until the slot's id is found, and then do indexOf
                for(let i = this.timeSlots.length - 1; i >= 0; i--){
                    if(this.timeSlots[i].id == timeSlot.id){
                        this.timeSlots[i] = timeSlot
                    }
                }
                this.rearrangeTimeSlotsByStartTime()
            }
        },
        watch: {},
        events: {},
        filters: {
            amPmFormat: function(dateStr){
                return moment(dateStr).format('h:mm a')
            }
        },
    };
</script>
