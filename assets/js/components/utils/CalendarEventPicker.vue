<template>
  <div id="calendar-wrap">
    <header class="input-group">
      <div class="input-group-prepend">
        <button id="month-prev" @click.prevent="changeMonth('prev')" class="btn btn-sm btn-default"><img
                src="/images/green-calendar-arrow-before.png" alt="arrow"></button>
      </div>
      <input type="text" class="form-control text-center calendar-datetext" disabled readonly
             :value="calendarHeadingDate.format('MMM') + ' ' + calendarHeadingDate.format('YYYY')">
      <div class="input-group-append">
        <button id="month-next" @click.prevent="changeMonth('next')" class="btn btn-sm btn-default"><img
                src="/images/green-calendar-arrow-after.png" alt="arrow"></button>
      </div>
    </header>
    <div id="calendar">
      <template v-if="!isDataLoaded">
        <p style="text-align: center"><img src="/images/loading.gif" alt="Loading..."/></p>
      </template>
      <template v-else>
        <ul class="weekdays">
          <li>Sun</li>
          <li>Mon</li>
          <li>Tue</li>
          <li>Wed</li>
          <li>Thu</li>
          <li>Fri</li>
          <li>Sat</li>
        </ul>
        <ul class="days" v-for="week in calDaysArray">
          <li
                  v-for="day in week"
                  class="day"
                  :class="[
                                {'istoday': dateIsToday(day.day) },
                                {'active': dateIsSelected(day.day) },
                            ]"
                  @click.prevent="emitDayClicked(day.day)">
            <div class="date"
                 :class="[
                                   {'yes-events': day.hasevents == yesEventClass },
                                   {'no-events': day.hasevents == noEventClass },
                                ]">
              {{ day.day | removex }}
            </div>
          </li>
        </ul>
      </template>
    </div><!-- /. calendar -->
  </div><!-- /. wrap -->
</template>
<style scoped>
  /* Style from: https://responsivedesign.is/patterns/calendar/ */
  header {
    text-align: center;
  }

  #calendar {
    width: 100%;
  }

  #calendar a {
    color: #8e352e;
    text-decoration: none;
  }

  #calendar ul {
    list-style: none;
    padding: 0;
    margin: 0;
    width: 100%;
  }

  #calendar li {
    display: block;
    float: left;
    width: 14.342%;
    padding: 5px;
    box-sizing: border-box;
    border: 1px solid #ccc;
    margin-right: -1px;
    margin-bottom: -1px;
  }

  .calendar-datetext {
    border: transparent !important;
    background-color: transparent !important;
  }

  #calendar ul.weekdays {
    height: 40px;
    background: #2e456d;
  }

  #calendar ul.weekdays li {
    text-align: center;
    text-transform: uppercase;
    line-height: 20px;
    border: none !important;
    padding: 10px 6px;
    color: #fff;
    font-size: 13px;
  }

  #calendar .days li {
    height: 50px;
  }

  #calendar .days li.istoday {
    background: #f9fbe0;
  }

  #calendar .days li.active {
    background: #fbddcf;
  }

  #calendar .days li:hover {
    background: #d3d3d3;
  }

  #calendar .date {
    text-align: center;
    margin-bottom: 5px;
    padding: 4px;
    width: 20px;
    border-radius: 50%;
    float: right;
    cursor: pointer;
  }

  #calendar .date.yes-events {
    background: #e15915;
    color: #fff;
  }

  #calendar .date.active {
    background: #2e456d;
    color: #fff;
  }

  /* ============================
                  Mobile Responsiveness
     ============================*/

  @media (max-width: 768px) {

    #calendar .weekdays, #calendar .other-month {
      display: none;
    }

    #calendar li {
      height: auto !important;
      border: 1px solid #ededed;
      width: 100%;
      padding: 10px;
      margin-bottom: -1px;
    }

    #calendar .date {
      float: none;
    }
  }
</style>

<script>
    import moment from 'moment'

    export default {
        components: {},
        props: {
            entityName: {
                type: String,
                required: true
            }
        },
        data: function () {
            return {
                calDaysArray: {
                    week1: [],
                    week2: [],
                    week3: [],
                    week4: [],
                    week5: [],
                    week6: [],
                },
                calendarHeadingDate: moment(),
                isDataLoaded: true,
                noEventClass: 'no-events',
                now: moment(),
                selectedDate: moment(),
                yesEventClass: 'yes-events',
            }
        },
        created: function () {

        },
        mounted: function () {
            // get the calendar for the current day
            this.fetchCalendarMonth(this.now)
        },
        computed: {},
        methods: {
            // Determine if the day passed to the function is the one selected by the user
            dateIsSelected: function(day){
                let calHeadingMonth = this.calendarHeadingDate.format('M')
                let concatMonthDay = calHeadingMonth + ' ' + day

                return concatMonthDay == this.selectedDate.format('M D')
            },
            // Determine if the day showing on the calendar is today
            dateIsToday: function(day){
                let calHeadingMonth = this.calendarHeadingDate.format('M')
                let concatMonthDay = calHeadingMonth + ' ' + day

                return concatMonthDay == this.now.format('M D')
            },
            emitDayClicked: function (day) {
                // Don't fetch dates that aren't part of the current month
                if (day[0] == 'x') {
                    return
                }

                // Determine the date the user clicked on the calendar by taking the clanedar's heading date and the date number clicked
                this.selectedDate = moment(this.calendarHeadingDate).date(day) // clones the calendarHeadingDate
                this.$emit('calendarDayClicked', this.selectedDate)
            },
            changeMonth: function (monthkey) {
                if (monthkey == 'prev') {
                    this.calendarHeadingDate.subtract(1, 'month')
                } else {
                    this.calendarHeadingDate.add(1, 'month')
                }
                this.fetchCalendarMonth(this.calendarHeadingDate);
            },
            fetchCalendarMonth: function (moment) {
                let self = this

                this.isDataLoaded = false // show loading wheel

                let apiurl = '/api/calendar/headshots/' + moment.format('YYYY') + '/' + moment.format('M')
                axios.get(apiurl)
                // success
                    .then(function (response) {
                        // reset calendar dates
                        self.calDaysArray.week1 = []
                        self.calDaysArray.week2 = []
                        self.calDaysArray.week3 = []
                        self.calDaysArray.week4 = []
                        self.calDaysArray.week5 = []
                        self.calDaysArray.week6 = []

                        // fill in calendar dates
                        for (let i = 0; i < response.data.calDaysArray.length; i++) {
                            if (i < 7) {
                                self.calDaysArray.week1.push(response.data.calDaysArray[i])
                            } else if (i >= 7 && i < 14) {
                                self.calDaysArray.week2.push(response.data.calDaysArray[i])
                            } else if (i >= 14 && i < 21) {
                                self.calDaysArray.week3.push(response.data.calDaysArray[i])
                            } else if (i >= 21 && i < 28) {
                                self.calDaysArray.week4.push(response.data.calDaysArray[i])
                            } else if (i >= 28 && i < 35) {
                                self.calDaysArray.week5.push(response.data.calDaysArray[i])
                            } else {
                                self.calDaysArray.week6.push(response.data.calDaysArray[i])
                            }
                        }

                        self.isDataLoaded = true // hide calendar wheel, show calendar
                    })
                    // fail
                    .catch(function (error) {
                        if (error.request.status == 404) {
                            self.is404 = true
                            self.isDataLoaded = true
                        }
                    })
            },
        },
        watch: {},
        events: {},
        filters: {
            removex: function (value) {
                return (value[0] == 'x') ? '_' : value;
            }
        },
    };
</script>
