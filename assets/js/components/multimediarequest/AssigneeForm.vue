<template>
    <div>
        <not-found v-if="is404 === true"></not-found>
        <div v-if="isDataLoaded === false">
            <p style="text-align: center"><img src="/images/loading.gif" alt="Loading..."/></p>
        </div>
        <div v-if="isDeleted === true" class="alert alert-info fade show" role="alert">
            This request assginee has been deleted. You will now be redirected to the asssignees list page.
        </div>
        <!-- MAIN AREA -->
        <div v-if="isDataLoaded === true && isDeleted === false && is404 === false">
            <heading v-if="itemExists">
                <span slot="title">{{ record.firstName }} {{ record.lastName }} </span>
            </heading>
            <div class="btn-group" role="group" aria-label="form navigation buttons">
                <button v-if="itemExists && this.permissions[0].edit" type="button" class="btn btn-info pull-right"
                        @click="toggleEdit"><span v-html="lockIcon"></span></button>
            </div>
            <form class="form" @submit.prevent="checkForm">
                <fieldset>
                    <legend>Assignee Information</legend>
                    <div class="form-group">
                        <label>First Name *</label>
                        <input
                                v-validate="'required'"
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
                            <input class="form-check-input" type="checkbox" value="graphic" id="chk-graphic"
                                   :disabled="!isEditMode" v-model="record.assignableRequestTypes">
                            <label class="form-check-label" for="chk-graphic">
                                Graphic design
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="photo" id="chk-photo"
                                   :disabled="!isEditMode" v-model="record.assignableRequestTypes">
                            <label class="form-check-label" for="chk-photo">
                                Photo shoots
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="video" id="chk-video"
                                   :disabled="!isEditMode" v-model="record.assignableRequestTypes">
                            <label class="form-check-label" for="chk-video">
                                Video shoots
                            </label>
                        </div>
                    </div>
                </fieldset>
                <!-- ERROR / SUCCESS NOTIFICATIONS -->
                <div v-if="this.$validator.errors.count() > 0" class="alert alert-danger fade show" role="alert">
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
                    There was an error deleting this assignee.
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
                this.fetchAssignee(this.itemId)
            }
            this.fetchStatusOptions()
        },
        components: {AssigneeDeleteModal, Heading, Multiselect, NotFound},
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
                is404: false,
                isDataLoaded: false,
                isDeleted: false,
                isDeleteError: false,
                isEditMode: false, // true = make forms editable
                record: {
                    id: '',
                    assginableRequestTypes: [],
                    email: '',
                    firstName: '',
                    lastName: '',
                    phone: '',
                    status: '',
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
                    this.successMessage = "Assginee created."
                    let newurl = '/multimediarequests/assignees/' + this.record.id
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
            fetchAssignee(itemId) {
                let self = this
                axios.get('/api/multimediaassignee/' + itemId)
                // success
                    .then(function (response) {
                        self.record = response.data
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
                axios.get('/api/multimediaassigneestatuses')
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
            // Submit the form via the API
            submitForm: function () {
                let self = this // 'this' loses scope within axios
                let method = (this.itemExists) ? 'put' : 'post'
                let route = (this.itemExists) ? '/api/multimediaassignee' : '/api/multimediaassignees'
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
                        // Add any validation errors to the vee validator error bag
                        if (errors.length > 0) {
                            errors.forEach(function (error) {
                                let key = error.property_path
                                let message = error.message
                                self.$validator.errors.add(key, message)
                            })
                        }
                        self.apiError.status = error.response.status
                        switch (error.response.status) {
                            case 403:
                                self.apiError.message = "You do not have sufficient privileges to retrieve multimedia assignees."
                                break
                            case 404:
                                self.apiError.message = "Assignee was not found."
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
            }
        }
    }
</script>
