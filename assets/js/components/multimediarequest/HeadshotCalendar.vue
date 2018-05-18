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
                    <div v-for="(timeSlot, index) in timeSlots" class="col-xs-12 col-sm-6 col-md-4 pl-4 pb-2">
                        <div class="card">
                            <div class="card-header">
                                {{ timeSlot.startTime }} - {{ timeSlot.endTime }}
                                <button type="button" @click="removeTimeSlot(timeSlot)" class="close pull-right"><span aria-hidden="true">&times;</span></button>
                            </div>
                            <div class="card-body">
                                {{ timeSlot.startTime }} - {{ timeSlot.endTime }}
                                <!--<div class="form-group">
                                    <label :for="'timeslot-' + index">Start Time *</label>
                                    <input
                                            v-validate="'required'"
                                            data-vv-as="location"
                                            :id="'timeslot-' + index"
                                            :name="'timeslot' + index"
                                            :class="{'is-invalid': errors.has('timeslot-' + index), 'form-control-plaintext': !userCanEdit || !isEditMode}" :readonly="!userCanEdit || !isEditMode"
                                            v-model="timeslot.startTime"
                                            type="text"
                                            class="form-control"
                                            placeholder="Location">
                                    <div class="invalid-feedback">
                                        {{ errors.first('bathroom-location-' + index) }}
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="form-check">
                                        <p-check :id="'bathroomGenderNeutral-' + index" class="p-switch p-slim" v-model="bathroom.isGenderNeutral" color="success">Gender neutral</p-check>
                                    </div>
                                </div>-->
                            </div>
                        </div>
                    </div><!-- end foreach timeSlot -->
                </div>
            </div>
        </div>
    </div>
</template>
<style scoped>

</style>

<script>
    import moment from 'moment'
    import CalendarEventPicker from '../utils/CalendarEventPicker'

    export default {
        components: {CalendarEventPicker},
        props: {

        },
        data: function () {
            return {
                isDataLoaded: true,
                now: moment(),
                selectedDate: moment(),
                timeSlots: [],
            }
        },
        created: function () {

        },
        mounted: function () {
            this.fetchPhotoshootsForDate(moment())
        },
        computed: {},
        methods: {
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
            removeTimeSlot: function(timeSlot){
                this.timeSlots.splice(this.timeSlots.indexOf(timeSlot), 1)
            },
            setSelectedDate: function(moment){
                this.selectedDate = moment
            }
        },
        watch: {},
        events: {},
        filters: {

        },
    };
</script>
