<template>
    <div>
        <not-found v-if="is404 === true"></not-found>
        <!-- <div v-if="isDataLoaded === false">
            <p style="text-align: center"><img src="/images/loading.gif" alt="Loading..."/></p>
        </div> -->
        <div v-if="isDeleted === true" class="alert alert-info fade show" role="alert">
            This request has been deleted. You will now be redirected to the requests list page.
        </div>
        <!-- MAIN AREA -->
        <div>
            <div class="btn-group" role="group" aria-label="form navigation buttons">
                <button v-if="this.permissions[0].edit" type="button" class="btn btn-info pull-right"
                        @click="toggleEdit"><span v-html="lockIcon"></span></button>
            </div>
            <form class="form" @submit.prevent="checkForm">
                <div class="row">
                    <div class="col-md-8">
                        <fieldset>
                            <!-- COMMON FIELDS -->
                            <legend>Department Information</legend>
                            <div class="form-group">
                                <label>Department</label>
                                <input
                                        data-vv-as="department"
                                        name="department"
                                        type="text"
                                        class="form-control"
                                        :class="{ 'is-invalid': errors.has('department'), 'form-control-plaintext': !userCanEdit || !isEditMode }"
                                        :readonly="!userCanEdit || !isEditMode"
                                        v-model="record.department">
                                <div class="invalid-feedback">
                                    {{ errors.first('department') }}
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Search Terms</label>
                                <input
                                        v-validate="'required'"
                                        data-vv-as="search_terms"
                                        name="search_terms"
                                        type="text"
                                        class="form-control"
                                        :class="{ 'is-invalid': errors.has('search_terms'), 'form-control-plaintext': !userCanEdit || !isEditMode }"
                                        :readonly="!userCanEdit || !isEditMode"
                                        v-model="record.search_terms">
                                <div class="invalid-feedback">
                                    {{ errors.first('search_terms') }}
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Map Building Name</label>
                                <input
                                        v-validate="'required'"
                                        data-vv-as="map_building_name"
                                        name="map_building_name"
                                        type="text"
                                        class="form-control"
                                        :class="{ 'is-invalid': errors.has('map_building_name'), 'form-control-plaintext': !userCanEdit || !isEditMode }"
                                        :readonly="!userCanEdit || !isEditMode"
                                        v-model="record.map_building_name">
                                <div class="invalid-feedback">
                                    {{ errors.first('map_building_name') }}
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Address 1</label>
                                <input
                                        name="address_1"
                                        data-vv-as="address_1"
                                        type="text"
                                        class="form-control"
                                        :class="{ 'is-invalid': errors.has('address_1'), 'form-control-plaintext': !userCanEdit || !isEditMode }"
                                        :readonly="!userCanEdit || !isEditMode"
                                        v-model="record.address_1">
                                <div class="invalid-feedback">
                                    {{ errors.first('address_1') }}
                                </div>
                            </div>
                             <div class="form-group">
                                <label>Address 2</label>
                                <input
                                        name="address_2"
                                        data-vv-as="address_2"
                                        type="text"
                                        class="form-control"
                                        :class="{ 'is-invalid': errors.has('address_2'), 'form-control-plaintext': !userCanEdit || !isEditMode }"
                                        :readonly="!userCanEdit || !isEditMode"
                                        v-model="record.address_2">
                                <div class="invalid-feedback">
                                    {{ errors.first('address_2') }}
                                </div>
                            </div>
                            <div class="form-group">
                                <label>City</label>
                                <input
                                        data-vv-as="city"
                                        name="city"
                                        type="text"
                                        class="form-control"
                                        :class="{ 'is-invalid': errors.has('city'), 'form-control-plaintext': !userCanEdit || !isEditMode }"
                                        :readonly="!userCanEdit || !isEditMode"
                                        v-model="record.city">
                                <div class="invalid-feedback">
                                    {{ errors.first('city') }}
                                </div>
                            </div>
                            <div class="form-group">
                                <label>State</label>
                                <input
                                        data-vv-as="state"
                                        name="state"
                                        type="text"
                                        class="form-control"
                                        :class="{ 'is-invalid': errors.has('state'), 'form-control-plaintext': !userCanEdit || !isEditMode }"
                                        :readonly="!userCanEdit || !isEditMode"
                                        v-model="record.state">
                                <div class="invalid-feedback">
                                    {{ errors.first('state') }}
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Zip</label>
                                <input
                                        data-vv-as="zip"
                                        name="zip"
                                        type="text"
                                        class="form-control"
                                        :class="{ 'is-invalid': errors.has('zip'), 'form-control-plaintext': !userCanEdit || !isEditMode }"
                                        :readonly="!userCanEdit || !isEditMode"
                                        v-model="record.zip">
                                <div class="invalid-feedback">
                                    {{ errors.first('zip') }}
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Phone</label>
                                <input
                                        name="phone"
                                        data-vv-as="phone number"
                                        type="text"
                                        class="form-control"
                                        :class="{ 'is-invalid': errors.has('phone'), 'form-control-plaintext': !userCanEdit || !isEditMode }"
                                        :readonly="!userCanEdit || !isEditMode"
                                        v-model="record.phone">
                                <div class="invalid-feedback">
                                    {{ errors.first('phone') }}
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input
                                        name="email"
                                        v-validate="'required|email'"
                                        data-vv-as="email"
                                        type="text"
                                        class="form-control"
                                        :class="{ 'is-invalid': errors.has('email'), 'form-control-plaintext': !userCanEdit || !isEditMode }"
                                        :readonly="!userCanEdit || !isEditMode"
                                        v-model="record.email">
                                <div class="invalid-feedback">
                                    {{ errors.first('email') }}
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Website</label>
                                <input
                                        name="website"
                                        data-vv-as="website"
                                        type="text"
                                        class="form-control"
                                        :class="{ 'is-invalid': errors.has('website'), 'form-control-plaintext': !userCanEdit || !isEditMode }"
                                        :readonly="!userCanEdit || !isEditMode"
                                        v-model="record.website">
                                <div class="invalid-feedback">
                                    {{ errors.first('website') }}
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Faculty List</label>
                                <input
                                        name="faculty_list"
                                        data-vv-as="faculty_list"
                                        type="text"
                                        class="form-control"
                                        :class="{ 'is-invalid': errors.has('faculty_list'), 'form-control-plaintext': !userCanEdit || !isEditMode }"
                                        :readonly="!userCanEdit || !isEditMode"
                                        v-model="record.faculty_list">
                                <div class="invalid-feedback">
                                    {{ errors.first('faculty_list') }}
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Staff List</label>
                                <input
                                        name="staff_list"
                                        data-vv-as="staff_list"
                                        type="text"
                                        class="form-control"
                                        :class="{ 'is-invalid': errors.has('staff_list'), 'form-control-plaintext': !userCanEdit || !isEditMode }"
                                        :readonly="!userCanEdit || !isEditMode"
                                        v-model="record.staff_list">
                                <div class="invalid-feedback">
                                    {{ errors.first('staff_list') }}
                                </div>
                            </div>
                        </fieldset>
                    </div><!-- /end .col-md-4 -->
                </div><!-- /end .row -->
                <!-- ERROR / SUCCESS NOTIFICATION AREA -->
                <!-- <div v-if="this.$validator.errors.count() > 0" class="alert alert-danger fade show" role="alert">
                    You have <strong>{{ this.$validator.errors.count() }} error<span
                        v-if="this.$validator.errors.count() > 1">s</span></strong> in your submission:
                    <ul>
                        <li v-for="error in this.$validator.errors.all()">
                            <strong>{{ error }}</strong>
                        </li>
                    </ul>
                </div>
                <div v-if="apiError.status" class="alert alert-danger fade show" role="alert">
                    {{ apiError.message }}
                </div>
                <div v-if="success" class="alert alert-success fade show" role="alert">
                    {{ successMessage }}
                </div>
                <div v-if="isDeleteError === true" class="alert alert-danger fade show" role="alert">
                    There was an error deleting this request.
                </div> -->
                <!-- ACTION BUTTONS -->
                <div v-if="userCanEdit && isEditMode" aria-label="action buttons" class="mb-4">
                    <button class="btn btn-success" type="submit"><i class="fa fa-save fa-2x"></i></button>
                </div>
            </form>
        </div>
        <!-- DELETE ITEM MODAL -->
        
    </div>
</template>
<style scoped>
    .status-container{
        background-color: #efefef;
        padding:10px;
    }
    .status-note-container{
        max-height: 400px;
        overflow-y: scroll;
    }
    .note-meta{
        font-size: 0.8rem;
    }
</style>
<style src="vue-multiselect/dist/vue-multiselect.min.css"></style>
<script>
    import Heading from '../utils/Heading.vue'
    import Multiselect from 'vue-multiselect'
    import NotFound from '../utils/NotFound.vue'
    import Flatpickr from 'vue-flatpickr-component'
    import 'flatpickr/dist/flatpickr.css'
    import moment from 'moment'

    export default {
        created() {
        },
        mounted() {
            // detect if the form should be in edit mode from the start (default is false)
            // if (this.startMode == 'edit') {
            //     this.isEditMode = true
            // }
            // this.fetchRequest(this.itemId)
            // this.fetchStatusOptions()
            // this.fetchAssignees()
            // this.fetchTimeSlots()
            // this.fetchPublicationTypes()
        },
        components: {Heading, Multiselect, NotFound, Flatpickr},
        props: {
            itemId: {
                type: String,
                required: false
            },
            permissions: {
                type: Array,
                required: true
            },
            // requestType: {
            //     type: String,
            //     required: true,
            // },
            startMode: {
                type: String,
                required: false
            },
            newForm: {
                default: false
            }
        },
        data: function () {
            return {
                apiError: {
                    message: null,
                    status: null
                },
                assignees: [],
                currentStatus: null,
                flatpickrCompletionDateConfig: {
                    wrap: true, // set wrap to true only when using 'input-group'
                    enableTime: false,
                    altFormat: "m-d-Y", // format the user sees
                    altInput: true,
                    minDate: null,
                    dateFormat: "Y-m-d", // format sumbitted to the API
                },
                flatpickrEndTimeConfig: {
                    wrap: true, // set wrap to true only when using 'input-group'
                    enableTime: true,
                    altFormat: "m-d-Y h:i K", // format the user sees
                    altInput: true,
                    minDate: null,
                    dateFormat: "Y-m-d H:i:S", // format sumbitted to the API
                },
                flatpickrStartTimeConfig: {
                    wrap: true, // set wrap to true only when using 'input-group'
                    enableTime: true,
                    altFormat: "m-d-Y h:i K", // format the user sees
                    altInput: true,
                    minDate: null,
                    dateFormat: "Y-m-d H:i:S", // format sumbitted to the API
                },
                is404: false,
                isDataLoaded: false,
                isDeleted: false,
                isDeleteError: false,
                isEditMode: false, // true = make forms editable
                publicationTypes: [],
                record: {
                    id: '',
                    assignee: null,
                    completionDate: null,
                    department: '',
                    description: '',
                    email: '',
                    endTime: null,
                    firstName: '',
                    intendedUse: '',
                    lastName: '',
                    location: '',
                    phone: '',
                    photoRequestType: {
                        id: null,
                    },
                    startTime: null,
                    status: 1, // 'new'
                    statusNotes: [],
                    requestType: '',
                },
                reminderEmailBody: '',
                reminderEmailStatus: {
                    isSent: false,
                    isError: false,
                },
                statusOptions: [],
                success: false,
                successMessage: '',
                timeSlots: [],
                isModalOpen: false,
                requestType: '',
            }
        },
        computed: {
            // are there any validation errors?
            haveErrors: function () {
                return this.$validator.errors.count() > 0 ? true : false
            },
            headingIcon: function () {

            },
            isInvalid: function () {
                return 'is-invalid'
            },
            // -end PHOTOS
            lockIcon: function () {
                return this.isEditMode ? '<i class="fa fa-unlock"></i>' : '<i class="fa fa-lock"></i>'
            },
            userCanEdit: function () {
                // An existing record can be edited by a user with edit permissions, a new record can be created by a user with create permissions
                return this.permissions[0].edit || this.permissions[0].create ? true : false
            },
            userCanEmail: function () {
                // An email can be sent by a user with email permissions
                return this.permissions[0].email
            }
        },
        methods: {
            addStatusNote: function(){
                this.record.statusNotes.push({
                    note: '',
                })
            },
            afterSubmitSucceeds: function () {
                let self = this

                this.fetchRequest(this.record.id) // fetch the updated request's information
                this.success = true
                this.successMessage = "Update successful."

                //  time slots need a human-readable date/time field
                if(self.requestType == 'headshot' && self.record.timeSlot){
                    self.concatTimeSlotData(self.record.timeSlot)
                }
                //  assignees need a human-readable first/last name field
                if(self.record.assignee) {
                    self.concatAssigneeName(self.record.assignee)
                }

                // remove the message after 3 seconds
                setTimeout(function () {
                    self.success = false
                }, 3000)
            },
            // Run prior to submitting
            checkForm: function () {
                let self = this
                this.$validator.validateAll()
                    .then((result) => {
                        // if all fields valid, submit the form
                        if (result) {
                            self.submitForm()
                            return
                        }
                    })
                    .catch((error) => {
                        console.log(error)
                        self.apiError.status = 500
                        self.apiError.message = "Something went wrong that wasn't validation related."
                    });
            },
            // Concatenate time slot date and time fields
            concatAssigneeName: function(assignee){
                let displayName = assignee.firstName + ' ' + assignee.lastName
                assignee.displayStr = displayName
            },
            // Concatenate time slot date and time fields
            concatTimeSlotData: function(timeSlot){
                let displayDate = moment(timeSlot.dateOfShoot).local().format("ddd, MMM D, YYYY") // uses moment.js library
                timeSlot.displayStr =  displayDate + " from " + timeSlot.startTime + " to " + timeSlot.endTime
            },
            endTimeChanged: function(dateObj, dateStr){
                // Must use this.$set to dynamically change the max date (https://vuejs.org/v2/guide/reactivity.html)
                this.$set(this.flatpickrStartTimeConfig, 'maxDate', dateStr)
            },
            endTimeOpened: function(dateObj, dateStr){
                // Must use this.$set to dynamically change the max date (https://vuejs.org/v2/guide/reactivity.html)
                this.$set(this.flatpickrStartTimeConfig, 'maxDate', dateStr)
            },
            fetchRequest(itemId) {
                let self = this

                self.isDataLoaded = false

                axios.get('/api/multimediarequests/' + itemId)
                // success
                    .then(function (response) {
                        self.record = response.data
                        //self.record.requestType = self.requestType
                        self.isDataLoaded = true

                        //  time slots need a human-readable date/time field
                        if(self.requestType == 'headshot' && self.record.timeSlot){
                            self.concatTimeSlotData(self.record.timeSlot)
                        }
                        //  assignees need a human-readable first/last name field
                        if(self.record.assignee) {
                            self.concatAssigneeName(self.record.assignee)
                        }
                    })
                    // fail
                    .catch(function (error) {
                        if (error.request.status == 404) {
                            self.is404 = true
                            self.isDataLoaded = true
                        }
                    })
            },
            fetchAssignees: function() {
                let self = this

                let url = '/api/multimediaassignees'
                if(this.requestType != ''){
                    url += '/' + this.requestType
                }
                axios.get(url)
                // success
                    .then(function (response) {
                        self.assignees = response.data
                        // Prepare a user-readable first/last name of all assignees
                        self.assignees.forEach(function(assignee){
                            self.concatAssigneeName(assignee)
                        })
                    })
                    // fail
                    .catch(function (error) {
                        console.log("ERROR FETCHING ASSIGNEES!")
                    })
            },
            fetchPublicationTypes: function() {
                let self = this

                let url = '/api/publicationrequest/types'
                axios.get(url)
                // success
                    .then(function (response) {
                        self.publicationTypes = response.data
                    })
                    // fail
                    .catch(function (error) {
                        console.log("ERROR FETCHING TIME SLOTS!")
                    })
            },
            fetchStatusOptions: function() {
                let self = this
                axios.get('/api/multimediarequest/statuses')
                // success
                    .then(function (response) {
                        self.statusOptions = response.data
                    })
                    // fail
                    .catch(function (error) {
                        console.log("ERROR FETCHING STATUS OPTIONS!")
                    })
            },
            fetchTimeSlots: function() {
                let self = this

                let url = '/api/multimediarequests/headshotdates/slots'
                axios.get(url)
                // success
                    .then(function (response) {
                        self.timeSlots = response.data
                        // Prepare a user-readable date/time of each slot
                        self.timeSlots.forEach(function(timeSlot){
                            self.concatTimeSlotData(timeSlot)
                        })
                    })
                    // fail
                    .catch(function (error) {
                        console.log("ERROR FETCHING TIME SLOTS!")
                    })
            },
            // Called from the @itemDeleted event emission from the Delete Modal
            markItemDeleted: function () {
                this.isDeleteError = false
                this.isDeleted = true
                setTimeout(function () {
                    // This record doesn't exist anymore, so send the user back to the assignees list page
                    window.location.replace('/multimediarequests')
                }, 3000)
            },
            markItemDeleteError: function () {
                let self = this
                this.isDeleted = false
                this.isDeleteError = true
                setTimeout(function () {
                    self.isDeleteError = false
                }, 5000)
            },
            removeStatusNote: function(note){
                this.record.statusNotes.splice(this.record.statusNotes.indexOf(note), 1)
            },
            startTimeChanged: function(dateObj, dateStr){
                // Must use this.$set to dynamically change the max date (https://vuejs.org/v2/guide/reactivity.html)
                this.$set(this.flatpickrEndTimeConfig, 'minDate', dateStr)
            },
            startTimeOpened: function(dateObj, dateStr){
                // Must use this.$set to dynamically change the max date (https://vuejs.org/v2/guide/reactivity.html)
                this.$set(this.flatpickrEndTimeConfig, 'minDate', dateStr)
            },
            sendAssigneeEmailNotification: function(){
                if(confirm("Are you sure you want to send this email?")){
                    let self = this
                    axios({
                        method: 'POST',
                        url: '/api/sendemail/multimediaassigneenotify',
                        data: {
                            recipient: self.record.assignee.email,
                            customBody: self.reminderEmailBody,
                            record: self.record,
                        }
                    })
                    // success
                        .then(function (response) {
                            // hide the email form until the page is reloaded to prevent spamming
                            self.reminderEmailStatus.isSent = true
                        })
                        // fail
                        .catch(function (error) {
                            self.reminderEmailStatus.isError = true
                        })
                }
            },
            // Submit the form via the API
            submitForm: function () {
                let self = this // 'this' loses scope within axios

                // AJAX (axios) submission
                axios({
                    method: 'PUT',
                    url: '/api/multimediarequest',
                    data: self.record
                })
                // success
                    .then(function (response) {
                        self.afterSubmitSucceeds()
                    })
                    // fail
                    .catch(function (error) {
                        let errors = error.response.data
                        // Add any validation errors to the vee validator error bag
                        if(errors.length > 0){
                            errors.forEach(function (error) {
                                let key = error.property_path
                                let message = error.message
                                self.$validator.errors.add(key, message)
                            })
                        }
                        self.apiError.status = error.response.status
                        switch(error.response.status){
                            case 403:
                                self.apiError.message = "You do not have sufficient privileges to retrieve multimedia requests."
                                break
                            case 404:
                                self.apiError.message = "Multimedia request was not found."
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
            toggleEdit: function () {
                this.isEditMode === true ? this.isEditMode = false : this.isEditMode = true
            }
        },
        filters: {
            capitalize: function (value) {
                if (!value) return ''
                value = value.toString()
                return value.charAt(0).toUpperCase() + value.slice(1)
            },
            commentDateFormat: function(dateStr){
                return moment(dateStr).format('M/D/YY h:mm a')
            },
            dateOnlyFormat: function(dateStr){
                return moment(dateStr).format('M/D/YY')
            }
        }
    }
</script>
