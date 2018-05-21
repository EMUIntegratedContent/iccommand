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
                                {{ timeSlot.startTime | amPmFormat }} - {{ timeSlot.endTime | amPmFormat }}
                                <button type="button" @click="removeTimeSlot(timeSlot)" class="close pull-right"><span aria-hidden="true">&times;</span></button>
                            </div>
                            <div class="card-body">
                                <!-- Start time -->
                                <div class="form-group">
                                    <label :for="'startTime-' + index">Start time *</label>
                                    <multiselect
                                            v-validate="'required'"
                                            data-vv-as="start time"
                                            v-model="timeSlot.startTime"
                                            :options="timeSlots"
                                            :multiple="false"
                                            placeholder="Start time"
                                            :id="'startTime-' + index"
                                            class="form-control"
                                            style="padding:0"
                                            :name="'startTime-' + index"
                                            :class="{'is-invalid': errors.has('startTime-' + index) }"
                                    >
                                    </multiselect>
                                    <div class="invalid-feedback">
                                        {{ errors.first('startTime-' + index) }}
                                    </div>
                                </div>
                                <!-- Start time -->
                                <div class="form-group">
                                    <label :for="'endTime-' + index">End time *</label>
                                    <multiselect
                                            v-validate="'required'"
                                            data-vv-as="end time"
                                            v-model="timeSlot.endTime"
                                            :options="timeSlots"
                                            :multiple="false"
                                            placeholder="End time"
                                            :id="'endTime-' + index"
                                            class="form-control"
                                            style="padding:0"
                                            :name="'endTime-' + index"
                                            :class="{'is-invalid': errors.has('endTime-' + index) }"
                                    >
                                    </multiselect>
                                    <div class="invalid-feedback">
                                        {{ errors.first('endTime-' + index) }}
                                    </div>
                                </div>
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
<style src="vue-multiselect/dist/vue-multiselect.min.css"></style>
<script>
    import moment from 'moment'
    import CalendarEventPicker from '../utils/CalendarEventPicker'
    import Multiselect from 'vue-multiselect'

    export default {
        components: {CalendarEventPicker, Multiselect},
        props: {

        },
        data: function () {
            return {
                isDataLoaded: true,
                now: moment(),
                selectedDate: moment(),
                timeSlots: [
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
            amPmFormat: function(dateStr){
                return moment(dateStr).format('h:mm a')
            }
        },
    };
</script>
