<template>
    <div>
        <not-found v-if="is404 === true"></not-found>
        <div v-if="isDataLoaded === false">
            <p style="text-align: center"><img src="/images/loading.gif" alt="Loading..."/></p>
        </div>
        <div v-if="isDeleted === true" class="alert alert-info fade show" role="alert">
            This request has been deleted. You will now be redirected to the requests list page.
        </div>
        <!-- MAIN AREA -->
        <div v-if="isDataLoaded === true && isDeleted === false && is404 === false">
            <heading>
                <!-- NOTE: publication request changed (in name only) to 'marketing materials' 6/11/18 -->
                <span slot="title">{{ record.firstName }} {{ record.lastName }}'s {{ requestType == 'publication' ? 'marketing materials' : requestType }} request </span>
            </heading>
            <div class="btn-group" role="group" aria-label="form navigation buttons">
                <button v-if="this.permissions[0].edit" type="button" class="btn btn-info pull-right"
                        @click="toggleEdit"><span v-html="lockIcon"></span></button>
            </div>
            <form class="form" @submit.prevent="checkForm">
                <div class="row">
                    <div class="col-md-8">
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
                        </fieldset>
                        <fieldset>
                            <!-- TYPE-SPECIFIC FIELDS -->
                            <legend>{{ requestType | capitalize }} request information</legend>
                            <!-- HEADSHOT REQUEST FIELDS -->
                            <template v-if="requestType == 'headshot'">
                                <div class="row">
                                    <div class="form-group col-md-12">
                                        <label>Description</label>
                                        <textarea
                                                name="description"
                                                class="form-control"
                                                :class="{ 'is-invalid': errors.has('description'), 'form-control-plaintext': !userCanEdit || !isEditMode }"
                                                :readonly="!userCanEdit || !isEditMode"
                                                v-model="record.description">
                                            {{ record.description}}
                                        </textarea>
                                        <div class="invalid-feedback">
                                            {{ errors.first('description') }}
                                        </div>
                                    </div>
                                    <div class="form-group col-sm-12">
                                        <!-- Time slot select -->
                                        <div v-if="isEditMode" class="form-group">
                                            <label for="status">Time slot</label>
                                            <multiselect
                                                    data-vv-as="time slot"
                                                    v-model="record.timeSlot"
                                                    :options="timeSlots"
                                                    :multiple="false"
                                                    placeholder="When will the headshot be taken?"
                                                    label="displayStr"
                                                    track-by="id"
                                                    id="timeSlot"
                                                    class="form-control"
                                                    style="padding:0"
                                                    name="timeSlot"
                                                    :class="{'is-invalid': errors.has('timeSlot') }"
                                            >
                                            </multiselect>
                                            <div class="invalid-feedback">
                                                {{ errors.first('timeSlot') }}
                                            </div>
                                        </div>
                                        <div v-else>
                                            <p v-if="record.timeSlot != null">Time slot: {{ record.timeSlot.displayStr }}</p>
                                            <p v-else>No time slot selected.</p>
                                        </div>
                                    </div>
                                </div>
                            </template>
                            <!-- PHOTO REQUEST FIELDS -->
                            <template v-if="requestType == 'photo'">
                                <div class="row">
                                    <div class="form-group col-md-7">
                                        <label>Description *</label>
                                        <textarea
                                                v-validate="'required'"
                                                name="description"
                                                class="form-control"
                                                :class="{ 'is-invalid': errors.has('description'), 'form-control-plaintext': !userCanEdit || !isEditMode }"
                                                :readonly="!userCanEdit || !isEditMode"
                                                v-model="record.description">
                                            {{ record.description}}
                                        </textarea>
                                        <div class="invalid-feedback">
                                            {{ errors.first('description') }}
                                        </div>
                                    </div>
                                    <div class="form-group col-md-5">
                                        <label>Location</label>
                                        <input
                                                name="location"
                                                type="text"
                                                class="form-control"
                                                :class="{ 'is-invalid': errors.has('location'), 'form-control-plaintext': !userCanEdit || !isEditMode }"
                                                :readonly="!userCanEdit || !isEditMode"
                                                v-model="record.location">
                                        <div class="invalid-feedback">
                                            {{ errors.first('location') }}
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-sm-6">
                                        <!-- Vue will throw an error if photoRequestType isn't present, so check for its existence, too -->
                                        <template v-if="isEditMode && record.photoRequestType">
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
                                            <div class="invalid-feedback">
                                                {{ errors.first('photoRequestType') }}
                                            </div>
                                        </template>
                                        <template v-else>
                                            <p>Type of photo shoot: {{ record.photoRequestType.request_type }}</p>
                                        </template>
                                    </div>
                                    <div class="form-group col-sm-6">
                                        <template v-if="isEditMode">
                                            <p>Intended Use *</p>
                                            <div class="form-check">
                                                <input
                                                        v-validate="'required'"
                                                        data-vv-as="intended use"
                                                        id="radio-web"
                                                        name="intendedUse"
                                                        type="radio"
                                                        value="web"
                                                        class="form-check-input"
                                                        :class="{ 'is-invalid': errors.has('intendedUse'), 'form-control-plaintext': !userCanEdit || !isEditMode }"
                                                        v-model="record.intendedUse">
                                                <label class="form-check-label" for="radio-headshot">
                                                    Web
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input
                                                        name="intendedUse"
                                                        type="radio"
                                                        value="print"
                                                        id="radio-print"
                                                        class="form-check-input"
                                                        v-model="record.intendedUse">
                                                <label class="form-check-label" for="radio-group">
                                                    Print
                                                </label>
                                            </div>
                                            <div class="invalid-feedback">
                                                {{ errors.first('intendedUse') }}
                                            </div>
                                        </template>
                                        <template v-else>
                                            <p>Intended use: {{ record.intendedUse ? record.intendedUse : 'not specified'}}</p>
                                        </template>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-6">
                                        <label>Starting date and time</label>
                                        <div class="input-group" v-if="isEditMode">
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
                                            <div class="input-group-btn" >
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
                                        <div v-else>
                                            {{ record.startTime | commentDateFormat}}
                                        </div>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label>Ending date and time</label>
                                        <div class="input-group" v-if="isEditMode">
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
                                            <div class="input-group-btn" v-if="isEditMode">
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
                                        <div v-else>
                                            {{ record.endTime | commentDateFormat }}
                                        </div>
                                    </div>
                                </div>
                            </template>
                            <!-- VIDEO REQUEST FIELDS -->
                            <template v-if="requestType == 'video'">
                                <div class="form-group">
                                    <label>Description</label>
                                    <textarea
                                            name="description"
                                            class="form-control"
                                            :class="{ 'is-invalid': errors.has('description'), 'form-control-plaintext': !userCanEdit || !isEditMode }"
                                            :readonly="!userCanEdit || !isEditMode"
                                            v-model="record.description">
                                            {{ record.description }}
                                        </textarea>
                                    <div class="invalid-feedback">
                                        {{ errors.first('description') }}
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Desired completion date</label>
                                    <div class="input-group" v-if="isEditMode">
                                        <flatpickr
                                                v-validate="'required'"
                                                data-vv-as="completion date"
                                                v-model="record.completionDate"
                                                id="videoCompletionDate"
                                                :config="flatpickrCompletionDateConfig"
                                                class="form-control"
                                                placeholder="Desired completion date"
                                                name="videoCompletionDate"
                                        >
                                        </flatpickr>
                                        <div class="input-group-btn" >
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
                                    <div v-else>
                                        {{ record.completionDate | dateOnlyFormat }}
                                    </div>
                                </div>
                            </template>
                            <!-- PUBLICATION (name changed to MARKETING MATERIALS) REQUEST FIELDS -->
                            <template v-if="requestType == 'publication'">
                                <div class="form-group">
                                    <!-- Publication type select -->
                                    <div v-if="isEditMode" class="form-group">
                                        <label for="status">Materials type*</label>
                                        <multiselect
                                                v-validate="'required'"
                                                data-vv-as="publication type"
                                                v-model="record.publicationRequestType"
                                                :options="publicationTypes"
                                                :multiple="false"
                                                placeholder="What type of marketing materials are needed?"
                                                label="requestType"
                                                track-by="id"
                                                id="pubRequestType"
                                                class="form-control"
                                                style="padding:0"
                                                name="publicationRequestType"
                                                :class="{'is-invalid': errors.has('publicationRequestType') }"
                                        >
                                        </multiselect>
                                        <div class="invalid-feedback">
                                            {{ errors.first('publicationRequestType') }}
                                        </div>
                                    </div>
                                    <div v-else>
                                        <p v-if="record.publicationRequestType != null">Materials needed: {{ record.publicationRequestType.requestType }}</p>
                                        <p v-else>No material type selected.</p>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Description</label>
                                    <textarea
                                            name="description"
                                            class="form-control"
                                            :class="{ 'is-invalid': errors.has('description'), 'form-control-plaintext': !userCanEdit || !isEditMode }"
                                            :readonly="!userCanEdit || !isEditMode"
                                            v-model="record.description">
                                            {{ record.description }}
                                        </textarea>
                                    <div class="invalid-feedback">
                                        {{ errors.first('description') }}
                                    </div>
                                </div>
                                <div class="form-group">
                                    <template v-if="isEditMode">
                                        <p>Intended Use *</p>
                                        <div class="form-check">
                                            <input
                                                    v-validate="'required'"
                                                    data-vv-as="intended use"
                                                    id="publication-radio-web"
                                                    name="intendedUse"
                                                    type="radio"
                                                    value="web"
                                                    class="form-check-input"
                                                    :class="{ 'is-invalid': errors.has('intendedUse'), 'form-control-plaintext': !userCanEdit || !isEditMode }"
                                                    v-model="record.intendedUse">
                                            <label class="form-check-label" for="radio-headshot">
                                                Web
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input
                                                    name="intendedUse"
                                                    type="radio"
                                                    value="print"
                                                    id="publication-radio-print"
                                                    class="form-check-input"
                                                    v-model="record.intendedUse">
                                            <label class="form-check-label" for="radio-group">
                                                Print
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input
                                                    name="intendedUse"
                                                    type="radio"
                                                    value="print and web"
                                                    id="publication-radio-both"
                                                    class="form-check-input"
                                                    v-model="record.intendedUse">
                                            <label class="form-check-label" for="radio-group">
                                                Both
                                            </label>
                                        </div>
                                        <div class="invalid-feedback">
                                            {{ errors.first('intendedUse') }}
                                        </div>
                                    </template>
                                    <template v-else>
                                        <p>Intended use: {{ record.intendedUse ? record.intendedUse : 'not specified'}}</p>
                                    </template>
                                </div>
                                <div class="form-group">
                                    <template v-if="isEditMode">
                                        <div class="form-check">
                                            <input
                                                    data-vv-as="photography"
                                                    id="publication-photography"
                                                    name="isPhotographyRequired"
                                                    type="checkbox"
                                                    value="1"
                                                    class="form-check-input"
                                                    :class="{ 'is-invalid': errors.has('isPhotographyRequired'), 'form-control-plaintext': !userCanEdit || !isEditMode }"
                                                    v-model="record.isPhotographyRequired">
                                            <label class="form-check-label" for="radio-headshot">
                                                Photography required?
                                            </label>
                                        </div>
                                        <div class="invalid-feedback">
                                            {{ errors.first('isPhotographyRequired') }}
                                        </div>
                                    </template>
                                    <template v-else>
                                        <p>{{ record.isPhotographyRequired ? 'Photography required' : 'Photography not required'}}</p>
                                    </template>
                                </div>
                                <div class="form-group">
                                    <label>Desired completion date *</label>
                                    <div class="input-group" v-if="isEditMode">
                                        <flatpickr
                                                v-validate="'required'"
                                                data-vv-as="completion date"
                                                v-model="record.completionDate"
                                                id="publicationCompletionDate"
                                                :config="flatpickrCompletionDateConfig"
                                                class="form-control"
                                                placeholder="Desired completion date"
                                                name="publicationCompletionDate"
                                        >
                                        </flatpickr>
                                        <div class="input-group-btn" >
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
                                    <div v-else>
                                        {{ record.completionDate | dateOnlyFormat}}
                                    </div>
                                </div>
                            </template>
                        </fieldset>
                    </div><!-- /end .col-md-8 -->
                    <div class="col-md-4">
                        <fieldset>
                            <!-- STATUS FIELDS -->
                            <legend class="sr-only">Status Fields</legend>
                            <template v-if="userCanEdit && isEditMode">
                                <div class="row">
                                    <!-- Status select -->
                                    <div class="form-group col-sm-12 status-container">
                                        <label for="status">Status *</label>
                                        <multiselect
                                                v-validate="'required'"
                                                data-vv-as="status"
                                                v-model="record.status"
                                                :options="statusOptions"
                                                :multiple="false"
                                                placeholder="What is the status of this request?"
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

                                    <div class="col-sm-12 status-container">
                                        <!-- Assignee select -->
                                        <div class="form-group">
                                            <label for="status">Assigned to</label>
                                            <multiselect
                                                    data-vv-as="assignee"
                                                    v-model="record.assignee"
                                                    :options="assignees"
                                                    :multiple="false"
                                                    placeholder="Who will be fulfilling this request?"
                                                    label="displayStr"
                                                    track-by="id"
                                                    id="assignee"
                                                    class="form-control"
                                                    style="padding:0"
                                                    name="status"
                                                    :class="{'is-invalid': errors.has('assignee') }"
                                            >
                                            </multiselect>
                                            <div class="invalid-feedback">
                                                {{ errors.first('assignee') }}
                                            </div>
                                        </div>
                                    </div>
                                    <div v-if="userCanEmail" class="col-sm-12 status-container">
                                        <!-- Email Assignee (show only if somebody is assigned to this request, and an email wasn't sent already, and there was no send errors) -->
                                        <div v-if="record.assignee && !reminderEmailStatus.isSent && !reminderEmailStatus.isError">
                                            Before sending a notification, make sure the information for this request is accurate and saved.
                                            <div class="form-group">
                                                <textarea type="text" class="form-control" v-model="reminderEmailBody" :placeholder="'Optional custom message to ' + record.assignee.firstName"></textarea>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-info" @click="sendAssigneeEmailNotification">Notify {{ record.assignee.firstName }}</button>
                                        </div>
                                        <!-- Email success/error notifications -->
                                        <div v-if="reminderEmailStatus.isError" class="alert alert-danger fade show" role="alert">
                                            There was an error sending the email.
                                        </div>
                                        <div v-if="reminderEmailStatus.isSent" class="alert alert-success fade show" role="alert">
                                            Email was sent.
                                        </div>
                                    </div>
                                </div>
                                <div class="row pt-4">
                                    <div class="col-sm-12 status-container">
                                        <template v-if="record.statusNotes.length > 0">
                                            <p><strong>Notes about this request.</strong></p>
                                            <div class="status-note-container">
                                                <!-- Request notes (backend only) -->
                                                <aside v-for="(note, index) in record.statusNotes" class="status-note">
                                                    <!-- Existing notes cannot be edited, only removed -->
                                                    <template v-if="note.id">
                                                        <p>{{ note.note }}</p>
                                                        <p class="text-right note-meta">{{ note.created_by }} at {{ note.created | commentDateFormat }}  <button type="button" class="btn btn-sm btn-outline-danger" @click="removeStatusNote(note)">Discard</button></p>
                                                    </template>
                                                    <!-- New notes -->
                                                    <template v-else>
                                                        <textarea v-model="note.note" class="form-control" placeholder="What would you like to say?"></textarea>
                                                        <p class="text-right note-meta"><button type="button" class="btn btn-sm btn-outline-danger" @click="removeStatusNote(note)">Discard</button></p>
                                                    </template>
                                                </aside>
                                            </div>
                                        </template>
                                        <template v-else>
                                            <p>It doesn't look like there are any notes...</p>
                                        </template>
                                        <div class="card mapitem-add-aux pt-2" @click="addStatusNote">
                                            <div class="card-body">
                                                <i class="fa fa-plus fa-5x"></i><br />
                                                Add note
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                            <template v-else>
                                <div class="form-group">
                                    <p><strong>Status &ndash; </strong>{{ record.status != null ? record.status.status : 'not set' }}</p>
                                </div>
                                <div class="form-group">
                                    <p><strong>Assigned to &ndash; </strong>{{ record.assignee != null ? record.assignee.displayStr : 'nobody' }}</p>
                                </div>
                                <template v-if="record.statusNotes.length > 0">
                                    <p><strong>Notes about this request.</strong></p>
                                    <div class="status-note-container">
                                        <!-- Request notes (backend only) -->
                                        <aside v-for="(note, index) in record.statusNotes" class="status-note">
                                            <p>{{ note.note }}</p>
                                            <p class="text-right note-meta">{{ note.created_by }} at {{ note.created | commentDateFormat }}</p>
                                        </aside>
                                    </div>
                                </template>
                                <template v-else>
                                    <p>It doesn't look like there are any notes...</p>
                                </template>
                            </template>
                        </fieldset>
                    </div><!-- /end .col-md-4 -->
                </div><!-- /end .row -->
                <!-- ERROR / SUCCESS NOTIFICATION AREA -->
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
                    There was an error deleting this request.
                </div>
                <!-- ACTION BUTTONS -->
                <div v-if="userCanEdit && isEditMode" aria-label="action buttons" class="mb-4">
                    <button class="btn btn-success" type="submit"><i class="fa fa-save fa-2x"></i></button>
                    <button v-if="this.permissions[0].delete" type="button" class="btn btn-danger ml-4"
                            data-toggle="modal" data-target="#deleteModal"><i class="fa fa-trash fa-2x"></i></button>
                </div>
            </form>
        </div>
        <!-- DELETE ITEM MODAL -->
        <multimediarequest-delete-modal
                :request="record"
                @itemDeleted="markItemDeleted"
                @itemDeleteError="markItemDeleteError"
        ></multimediarequest-delete-modal>
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
    import MultimediarequestDeleteModal from './MultimediarequestDeleteModal.vue'
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
            this.fetchRequest(this.itemId)
            this.fetchStatusOptions()
            this.fetchAssignees()
            this.fetchTimeSlots()
            this.fetchPublicationTypes()
        },
        components: {MultimediarequestDeleteModal, Heading, Multiselect, NotFound, Flatpickr},
        props: {
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
