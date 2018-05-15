<template>
    <div>
        <not-found v-if="is404 === true"></not-found>
        <div v-if="isDataLoaded === false">
            <p style="text-align: center"><img src="/images/loading.gif" alt="Loading..."/></p>
        </div>
        <div v-if="apiError.status" class="alert alert-danger fade show" role="alert">
            {{ apiError.message }}
        </div>
        <div v-if="isDeleted === true" class="alert alert-info fade show" role="alert">
            This request has been deleted. You will now be redirected to the requests list page.
        </div>
        <!-- MAIN AREA -->
        <div v-if="isDataLoaded === true && isDeleted === false && is404 === false">
            <heading v-if="itemExists">
                <span slot="title">{{ record.firstName }} {{ record.lastName }}'s {{ record.requestType }} request </span>
            </heading>
            <div class="btn-group" role="group" aria-label="form navigation buttons">
                <button v-if="itemExists && this.permissions[0].edit" type="button" class="btn btn-info pull-right"
                        @click="toggleEdit"><span v-html="lockIcon"></span></button>
            </div>
            <form class="form" @submit.prevent="checkForm">
                <select v-if="!itemExists" v-model="record.requestType">
                    <option value="photo">Photo Request</option>
                    <option value="video">Video Request</option>
                    <option value="graphic">Graphic Design Request</option>
                </select>
                <fieldset>
                    <!-- COMMON FIELDS -->
                    <legend>Requester Information</legend>
                    <div class="form-group">
                        <label>First Name *</label>
                        <input
                                data-vv-as="first name"
                                name="firstName"
                                type="text"
                                class="form-control"
                                :class="{ 'is-invalid': errors.has('firstName'), 'form-control-plaintext': !userCanEdit || !isEditMode }"
                                :readonly="!userCanEdit || !isEditMode"
                                v-model="record.firstName">
                        <div class="invalid-feedback">
                            {{ errors.first('firstName') }}
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Last Name *</label>
                        <input
                                v-validate="'required'"
                                data-vv-as="last name"
                                name="lastName"
                                type="text"
                                class="form-control"
                                :class="{ 'is-invalid': errors.has('lastName'), 'form-control-plaintext': !userCanEdit || !isEditMode }"
                                :readonly="!userCanEdit || !isEditMode"
                                v-model="record.lastName">
                        <div class="invalid-feedback">
                            {{ errors.first('lastName') }}
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Email *</label>
                        <input
                                name="email"
                                v-validate="'required|email'"
                                data-vv-as="email address"
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
                        <label>Department</label>
                        <input
                                name="department"
                                data-vv-as="department"
                                type="text"
                                class="form-control"
                                :class="{ 'is-invalid': errors.has('department'), 'form-control-plaintext': !userCanEdit || !isEditMode }"
                                :readonly="!userCanEdit || !isEditMode"
                                v-model="record.department">
                        <div class="invalid-feedback">
                            {{ errors.first('department') }}
                        </div>
                    </div>
                    <!--
                  <template v-if="userCanEdit && isEditMode">
                    <div class="form-group">
                      <label for="status">Status *</label>
                      <multiselect
                        v-validate="'required'"
                        data-vv-as="status"
                        v-model="record.status"
                        :options="statusOptions"
                        :multiple="false"
                        placeholder="What is the status of this person?"
                        label="status"
                        track-by="id"
                        id="status"
                        class="form-control"
                        style="padding:0"
                        name="status"
                        :class="{'is-invalid': errors.has('status') }"
                        >
                      </multiselect>
                      <div class="invalid-feedback">
                        {{ errors.first('status') }}
                      </div>
                    </div>
                  </template>
                  <template v-else>
                    <div class="form-group">
                      <label>Status *</label>
                      <input
                        name="status"
                        type="text"
                        class="form-control form-control-plaintext"
                        readonly
                        :value="record.status ? record.status.status: 'not set'">
                    </div>
                  </template>
                  <div class="form-group">
                    <p>I am available for: </p>
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" value="graphic" id="chk-graphic" :disabled="!isEditMode" v-model="record.assignableRequestTypes">
                      <label class="form-check-label" for="chk-graphic">
                        Graphic design
                      </label>
                    </div>
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" value="photo" id="chk-photo" :disabled="!isEditMode" v-model="record.assignableRequestTypes">
                      <label class="form-check-label" for="chk-photo">
                        Photo shoots
                      </label>
                    </div>
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" value="video" id="chk-video" :disabled="!isEditMode" v-model="record.assignableRequestTypes">
                      <label class="form-check-label" for="chk-video">
                        Video shoots
                      </label>
                    </div>
                  </div>
                  -->
                </fieldset>
                <fieldset>
                    <!-- TYPE-SPECIFIC FIELDS -->
                    <legend>{{ record.requestType }} request information</legend>
                    <!-- PHOTO SHOOT -->
                    <template v-if="record.requestType == 'photo'">
                        <div class="form-group">
                            <p>Type of photo shoot *</p>
                            <div class="form-check">
                                <input
                                        v-validate="'required'"
                                        data-vv-as="photo shoot type"
                                        id="radio-headshot"
                                        name="photoRequestType"
                                        type="radio"
                                        value="1"
                                        class="form-check-input"
                                        :class="{ 'is-invalid': errors.has('photoRequestType'), 'form-control-plaintext': !userCanEdit || !isEditMode }"
                                        :disabled="!isEditMode"
                                        v-model="record.photoRequestType.id">
                                <label class="form-check-label" for="radio-headshot">
                                    Headshot
                                </label>
                            </div>
                            <div class="form-check">
                                <input
                                        name="photoRequestType"
                                        type="radio"
                                        value="2"
                                        id="radio-group"
                                        class="form-check-input"
                                        :disabled="!isEditMode"
                                        v-model="record.photoRequestType.id">
                                <label class="form-check-label" for="radio-group">
                                    Group Shot
                                </label>
                            </div>
                            <div class="form-check">
                                <input
                                        name="photoRequestType"
                                        type="radio"
                                        value="3"
                                        id="radio-editorial"
                                        class="form-check-input"
                                        :disabled="!isEditMode"
                                        v-model="record.photoRequestType.id">
                                <label class="form-check-label" for="radio-editorial">
                                    Editorial
                                </label>
                            </div>
                            <div class="form-check">
                                <input
                                        name="photoRequestType"
                                        type="radio"
                                        value="4"
                                        id="radio-event"
                                        class="form-check-input"
                                        :disabled="!isEditMode"
                                        v-model="record.photoRequestType.id">
                                <label class="form-check-label" for="radio-event">
                                    Event
                                </label>
                            </div>
                        </div>
                        <div class="invalid-feedback">
                            {{ errors.first('photoRequestType') }}
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label>Starting date and time</label>
                                <div class="input-group">
                                    <flatpickr
                                            v-model="record.startTime"
                                            id="startTimePicker"
                                            :config="flatpickrStartTimeConfig"
                                            class="form-control"
                                            placeholder="Starting time"
                                            name="startingTime"
                                            @on-open="startTimeOpened"
                                            @on-change="startTimeChanged"
                                            >
                                    </flatpickr>
                                    <div class="input-group-btn">
                                        <button class="btn btn-default" type="button" title="Toggle" data-toggle>
                                            <i class="fa fa-calendar">
                                                <span aria-hidden="true" class="sr-only">Toggle</span>
                                            </i>
                                        </button>
                                        <button class="btn btn-default" type="button" title="Clear" data-clear>
                                            <i class="fa fa-times">
                                                <span aria-hidden="true" class="sr-only">Clear</span>
                                            </i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group col-md-6">
                                <label>Ending date and time</label>
                                <div class="input-group">
                                    <flatpickr
                                            v-model="record.endTime"
                                            id="endTimePicker"
                                            :config="flatpickrEndTimeConfig"
                                            class="form-control"
                                            placeholder="Ending time"
                                            name="endingTime"
                                            @on-open="endTimeOpened"
                                            @on-change="endTimeChanged"
                                            >
                                    </flatpickr>
                                    <div class="input-group-btn">
                                        <button class="btn btn-default" type="button" title="Toggle" data-toggle>
                                            <i class="fa fa-calendar">
                                                <span aria-hidden="true" class="sr-only">Toggle</span>
                                            </i>
                                        </button>
                                        <button class="btn btn-default" type="button" title="Clear" data-clear>
                                            <i class="fa fa-times">
                                                <span aria-hidden="true" class="sr-only">Clear</span>
                                            </i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <pre>Starting date/time is - {{record.startTime}}</pre>
                        <pre>Ending date/time is - {{record.endTime}}</pre>
                        <!-- HEADSHOT-SPECIFIC FIELDS -->
                    </template>
                    <template v-if="record.requestType == 'video'">

                    </template>
                    <template v-if="record.requestType == 'graphic'">

                    </template>
                </fieldset>
                <div v-if="this.$validator.errors.count() > 0" class="alert alert-danger fade show" role="alert">
                    You have <strong>{{ this.$validator.errors.count() }} error<span
                        v-if="this.$validator.errors.count() > 1">s</span></strong> in your submission:
                    <ul>
                        <li v-for="error in this.$validator.errors.all()">
                            <strong>{{ error }}</strong>
                        </li>
                    </ul>
                </div>
                <div v-if="success" class="alert alert-success fade show" role="alert">
                    {{ successMessage }}
                </div>
                <div v-if="isDeleteError === true" class="alert alert-danger fade show" role="alert">
                    There was an error deleting this request.
                </div>
                <!-- ACTION BUTTONS -->
                <div v-if="userCanEdit && isEditMode" aria-label="action buttons" class="mb-4">
                    <button class="btn btn-success" type="submit"><i class="fa fa-save fa-2x"></i></button>
                    <button v-if="itemExists && this.permissions[0].delete" type="button" class="btn btn-danger ml-4"
                            data-toggle="modal" data-target="#deleteModal"><i class="fa fa-trash fa-2x"></i></button>
                </div>
            </form>
        </div>
        <!-- DELETE ITEM MODAL -->
        <assignee-delete-modal
                :assignee="record"
                @itemDeleted="markItemDeleted"
                @itemDeleteError="markItemDeleteError"
        ></assignee-delete-modal>
    </div>
</template>
<style>
    .list-group-item, .list-group-item:hover {
        z-index: auto;
    }
</style>
<style src="vue-multiselect/dist/vue-multiselect.min.css"></style>
<script>
    import AssigneeDeleteModal from './AssigneeDeleteModal.vue'
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
            if (this.startMode == 'edit') {
                this.isEditMode = true
            }
            if (this.itemExists === false) {
                this.isDataLoaded = true
            } else {
                // fetch the existing record using the prop itemId
                this.fetchRequest(this.itemId)
            }
            this.fetchStatusOptions()
            this.setupDatePickers()
        },
        components: {AssigneeDeleteModal, Heading, Multiselect, NotFound, Flatpickr},
        props: {
            itemExists: {
                type: Boolean,
                required: true
            },
            itemId: {
                type: String,
                required: false
            },
            permissions: {
                type: Array,
                required: true
            },
            requestType: {
                type: String,
                required: true,
            },
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
                currentStatus: null,
                startTimePicker: null,
                endTimePicker: null,
                flatpickrStartTimeConfig: {
                    wrap: true, // set wrap to true only when using 'input-group'
                    enableTime: true,
                    altFormat: "m-d-Y h:i K", // format the user sees
                    altInput: true,
                    minDate: null,
                    dateFormat: "Y-m-d H:i:S", // format sumbitted to the API
                },
                flatpickrEndTimeConfig: {
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
                    requestType: '', // for testing only...this will be a property eventually
                },
                statusOptions: [],
                success: false,
                successMessage: '',
                isModalOpen: false,
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
                return this.itemExists && this.permissions[0].edit || !this.itemExists && this.permissions[0].create ? true : false
            }
        },
        methods: {
            afterSubmitSucceeds: function () {
                let self = this
                // New item has been submitted, go to edit
                if (!this.itemExists) {
                    this.success = true
                    this.successMessage = "Request created."
                    let newurl = '/multimediarequests/' + this.record.id
                    document.location = newurl
                } else {
                    this.success = true
                    this.successMessage = "Update successful."
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
                        self.apiError.status = 500
                        self.apiError.message = "Something went wrong that wasn't validation related."
                    });
            },
            fetchRequest(itemId) {
                let self = this
                axios.get('/api/multimediarequests/' + itemId)
                // success
                    .then(function (response) {
                        self.record = response.data
                        self.record.requestType = self.requestType
                        self.isDataLoaded = true
                    })
                    // fail
                    .catch(function (error) {
                        if (error.request.status == 404) {
                            self.is404 = true
                            self.isDataLoaded = true
                        }
                    })
            },
            fetchStatusOptions() {
                let self = this
                axios.get('/api/multimediaassignee/statuses')
                // success
                    .then(function (response) {
                        self.statusOptions = response.data
                    })
                    // fail
                    .catch(function (error) {
                        console.log("ERROR FETCHING STATUS OPTIONS!")
                    })
            },
            // Called from the @itemDeleted event emission from the Delete Modal
            markItemDeleted: function () {
                this.isDeleteError = false
                this.isDeleted = true
                setTimeout(function () {
                    // This record doesn't exist anymore, so send the user back to the assignees list page
                    window.location.replace('/multimediarequests/assignees')
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
            setupDatePickers:function() {
                var self = this;
                if (this.record.startTime === '') {
                    // this.flatpickrConfig.startDateMin = this.currentDate;
                    // this.dateObject.startDateDefault = null;
                    //
                    // this.dateObject.endDateMin = null;
                    // this.dateObject.endDateDefault = null;
                } else {
                    // this.flatpickrConfig.minDate = moment(this.record.startTime, "YYYY-MM-DD h:mm a").format("YYYY-MM-DD")
                    // this.dateObject.startDateDefault = this.record.start_date;
                    // this.dateObject.endDateMin = this.record.start_date;
                    // this.dateObject.endDateDefault = this.record.end_date;
                    // this.dateObject.startTimeDefault = this.record.start_time;
                    // this.dateObject.endTimeDefault = this.record.end_time;
                    // this.dateObject.regDateMin = this.record.start_date;
                    // this.dateObject.regDateDefault = this.record.reg_deadline;
                }
                // this.startTimePicker = flatpickr(document.getElementById("startTimePicker"), {
                //     defaultDate: this.currentDate.format("YYYY-MM-DD"),
                //     wrap: true, // set wrap to true only when using 'input-group'
                //     enableTime: true,
                //     altFormat: "m-d-Y h:i K",
                //     altInput: true,
                //     onChange(dateObject, dateString) {
                //         //self.endTimePicker.set("minDate", dateObject);
                //         //self.record.start_date = dateString;
                //         //self.startdatePicker.value = dateString;
                //     }
                //
                // });
                //
                // this.endTimePicker = flatpickr(document.getElementById("endTimePicker"), {
                //     defaultDate: this.currentDate.format("YYYY-MM-DD"),
                //     wrap: true, // set wrap to true only when using 'input-group'
                //     enableTime: true,
                //     altFormat: "m-d-Y h:i K",
                //     altInput: true,
                //     onChange(dateObject, dateString) {
                //         //self.startTimePicker.set("maxDate", dateObject);
                //         //self.record.end_date = dateString;
                //         //self.enddatePicker.value = dateString;
                //     }
                // });
            },
            startTimeOpened: function(dateObj, dateStr){
                // Must use this.$set to dynamically change the max date (https://vuejs.org/v2/guide/reactivity.html)
                this.$set(this.flatpickrEndTimeConfig, 'minDate', dateStr)
            },
            endTimeOpened: function(dateObj, dateStr){
                // Must use this.$set to dynamically change the max date (https://vuejs.org/v2/guide/reactivity.html)
                this.$set(this.flatpickrStartTimeConfig, 'maxDate', dateStr)
            },
            startTimeChanged: function(dateObj, dateStr){
                // Must use this.$set to dynamically change the max date (https://vuejs.org/v2/guide/reactivity.html)
                this.$set(this.flatpickrEndTimeConfig, 'minDate', dateStr)
            },
            endTimeChanged: function(dateObj, dateStr){
                // Must use this.$set to dynamically change the max date (https://vuejs.org/v2/guide/reactivity.html)
                this.$set(this.flatpickrStartTimeConfig, 'maxDate', dateStr)
            },
            // Submit the form via the API
            submitForm: function () {
                let self = this // 'this' loses scope within axios
                let method = (this.itemExists) ? 'put' : 'post'
                let route = (this.itemExists) ? '/api/multimediarequest' : '/api/multimediarequests'
                // AJAX (axios) submission
                axios({
                    method: method,
                    url: route,
                    data: self.record
                })
                // success
                    .then(function (response) {
                        self.record.id = response.data.id // set the item's ID
                        self.afterSubmitSucceeds()
                    })
                    // fail
                    .catch(function (error) {
                        let errors = error.response.data
                        console.log(error.response)
                        // Add any validation errors to the vee validator error bag
                        errors.forEach(function (error) {
                            let key = error.property_path
                            let message = error.message
                            self.$validator.errors.add(key, message)
                        })
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
            }
        }
    }
</script>
