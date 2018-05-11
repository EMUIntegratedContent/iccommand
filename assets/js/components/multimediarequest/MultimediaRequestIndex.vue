<template>
  <div>
    <heading>
      <span slot="icon" v-html="headingIcon">{{ headingIcon }}</span>
      <span slot="title">Multimedia Requests</span>
    </heading>
    <div v-if="apiError.status" class="alert alert-danger fade show" role="alert">
      {{ apiError.message }}
    </div>
    <p>For open time slot scheduling:</p>
    <p>1: look for specific date in table</p>
    <p>If date FOUND, use those time slots (array data for that date)</p>
    <p>If date NOT FOUND, use generic times for that day of week.</p>
    <div class="row" style="overflow-x: scroll;">
      <div class="col-xs-12">
        <div class="btn-group" data-toggle="buttons">
          <label class="btn btn-primary" @click="toggleTimeSlot(slot)" v-for="slot in timeSlots">
            <input type="checkbox" autocomplete="off" />{{ slot }}
          </label>
        </div>
      </div>
    </div>
  </div>
</template>
<style scoped>
input[type='checkbox']{
  visibility: hidden;
}
</style>
<script>
    import Heading from '../utils/Heading.vue'
    import Paginator from '../utils/Paginator.vue'

    export default {
      created() {},
      mounted() {
        //this.fetchPhotoRequests()
      },
      components: {Heading, Paginator},
      props:{
        permissions: {
          type: Array,
          required: true
        },
      },
      data: function() {
        return {
          apiError: {
            message: null,
            status: null
          },
          day: {
            times: [

            ]
          },
          timeSlots: [
            '12:00 AM',
            '12:30 AM',
            '1:00 AM',
            '1:30 AM',
            '2:00 AM',
            '2:30 AM',
            '3:00 AM',
            '3:30 AM',
            '4:00 AM',
            '4:30 AM',
            '5:00 AM',
            '5:30 AM',
            '6:00 AM',
            '6:30 AM',
            '7:00 AM',
            '7:30 AM',
            '8:00 AM',
            '8:30 AM',
            '9:00 AM',
            '9:30 AM',
            '10:00 AM',
            '10:30 AM',
            '11:00 AM',
            '11:30 AM',
            '12:00 PM',
            '12:30 PM',
            '1:00 PM',
            '1:30 PM',
            '2:00 PM',
            '2:30 PM',
            '3:00 PM',
            '3:30 PM',
            '4:00 PM',
            '4:30 PM',
            '5:00 PM',
            '5:30 PM',
            '6:00 PM',
            '6:30 PM',
            '7:00 PM',
            '7:30 PM',
            '8:00 PM',
            '8:30 PM',
            '9:00 PM',
            '9:30 PM',
            '10:00 PM',
            '10:30 PM',
            '11:00 PM',
            '11:30 PM',
          ]
        }
      },
      computed: {
        headingIcon: function() {
          return '<i class="fa fa-list"></i>'
        },
        userCanCreate: function(){
          return this.permissions[0].create ? true : false
        },
        userCanEdit: function(){
          return this.permissions[0].edit ? true : false
        }
      },
      methods: {
        fetchPhotoRequests: function(){
          let self = this
          axios.get('/api/photorequests')
          // success
          .then(function (response) {

          })
          // fail
          .catch(function (error) {
            self.apiError.status = error.response.status
            switch(error.response.status){
              case 403:
                self.apiError.message = "You do not have sufficient privileges to retrieve map items."
                break
              case 404:
                self.apiError.message = "Map items were not found."
                break
              case 500:
                self.apiError.message = "An internal error occurred."
                break
              default:
                self.apiError.message = "An error occurred."
                break
            }
          })
        },
        toggleTimeSlot(slot){
          // time slot registered for this date, remove it
          if(this.day.times.indexOf(slot) > -1){
            console.log(slot + " removed")
            this.day.times.splice(this.day.times.indexOf(slot), 1)
          }
          // time slot NOT registered for this date, add it
          else{
            console.log(slot + " added")
            this.day.times.push(slot)
          }
        }
      },
      filters: {},
    }
</script>
