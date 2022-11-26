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
      {{ capitalize(record.itemType) }} "{{ record.name }}" item has been deleted. You will now be redirected to
      the map items list page.
    </div>
    <!-- MAIN AREA -->
    <div v-if="isDataLoaded === true && isDeleted === false && is404 === false">
      <heading>
        <span slot="icon" v-html="headingIcon"></span>
        <span v-if="!itemExists">Step 2/2: Provide {{ record.itemType }} information</span>
        <span v-else>Map {{ record.itemType }}: {{ record.name }}</span>
      </heading>
      <div class="btn-group" role="group" aria-label="form navigation buttons">
        <button v-if="newForm" class="btn btn-info pull-left" @click="goBack"><i
            class="fa fa-chevron-circle-left"></i> Step 1
        </button>
        <button v-if="itemExists && permissions[0].edit" type="button" class="btn btn-info pull-right"
                @click="toggleEdit"><span v-html="lockIcon"></span></button>
      </div>
      <!-- TABS -->
      <ul class="nav nav-tabs" id="map-form-tab-list" role="tablist">
        <li class="nav-item">
          <a class="nav-link active" id="home-tab" data-toggle="tab" href="#information" role="tab"
             aria-controls="information" aria-selected="true">Information</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" id="profile-tab" data-toggle="tab" href="#photos" role="tab"
             aria-controls="photos" aria-selected="false">Photos ({{ record.images.length }})</a>
        </li>
      </ul>
      <div class="tab-content pt-2" id="mapitemTabContent">
        <div class="tab-pane fade show active" id="information" role="tabpanel"
             aria-labelledby="information-tab">
          <VeeForm class="form" v-slot="{ submitForm, errors, meta }" @submit="checkForm" :validation-schema="mapItemSchema">
            <form @submit="submitForm">
            <fieldset>
              <legend>Basic Information</legend>
              <div class="form-group">
                <label>Name *</label>
                <Field
                    name="name"
                    type="text"
                    class="form-control"
                    :class="{'is-invalid': errors.name }"
                    :readonly="!userCanEdit || !isEditMode"
                    v-model="record.name">
                </Field>
                <div class="invalid-feedback">
                  {{ errors.name }}
                </div>
              </div>
              <div class="form-group">
                <label>Slug</label>
                <Field
                    name="slug"
                    type="text"
                    class="form-control form-control-plaintext"
                    :class="{'is-invalid': errors.slug }"
                    readonly
                    v-model="record.slug"
                >
                </Field>
                <div class="invalid-feedback">
                  {{ errors.slug }}
                </div>
              </div>
              <div class="form-group">
                <label>Alias (does this map item have a nickname?)</label>
                <Field
                    name="alias"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': errors.slug, 'form-control-plaintext': !userCanEdit || !isEditMode }"
                    :readonly="!userCanEdit || !isEditMode"
                    v-model="record.alias"
                >
                </Field>
                <div class="invalid-feedback">
                  {{ errors.alias }}
                </div>
              </div>
              <div class="form-group">
                <label for="record-descr-textarea">Description</label>
                <textarea
                    v-model="record.description"
                    class="form-control"
                    id="record-descr-textarea"
                    :class="{'is-invalid': errors.description, 'form-control-plaintext': !userCanEdit || !isEditMode}"
                />
                <div class="invalid-feedback">
                  {{ errors.description }}
                </div>
              </div>
            </fieldset>
            <!-- GOOGLE MAP (edit mode only!)-->
            <fieldset v-if="userCanEdit && isEditMode">
              <legend>Set {{ record.itemType }} coordinates</legend>
              <p>Click on the map at the desired location. Use the "Set Location" button to set a
                marker.</p>
              <div class="row">
                <div class="col-md-12">
                  <google-map
                      name="campusmap"
                      :latitudeSatellite="parseFloat(record.latitudeSatellite)"
                      :longitudeSatellite="parseFloat(record.longitudeSatellite)"
                      :latitudeIllustration="parseFloat(record.latitudeIllustration)"
                      :longitudeIllustration="parseFloat(record.longitudeIllustration)"
                      @illustrationMarkerUpdated="setIllustratedLocation"
                      @satelliteMarkerUpdated="setSatelliteLocation">
                  </google-map>
                </div>
              </div>
            </fieldset>
            <!-- BUILDING FIELDS -->
            <template v-if="record.itemType == 'building'">
              <fieldset>
                <legend>Additional Information</legend>
                <div class="form-group">
                  <label>Address</label>
                  <Field
                      name="address"
                      type="text"
                      class="form-control"
                      :class="{ 'is-invalid': errors.address, 'form-control-plaintext': !userCanEdit || !isEditMode }"
                      :readonly="!userCanEdit || !isEditMode"
                      v-model="record.address"
                  >
                  </Field>
                  <div class="invalid-feedback">
                    {{ errors.address }}
                  </div>
                </div>
                <div class="form-group">
                  <h5>Building Hours</h5>
                  <template v-if="!userCanEdit || !isEditMode">
                    <span v-html="record.hours"></span>
                  </template>
                  <template v-else>
                    <div :class="{'is-invalid-ckeditor': errors.hours}">
                      <ckeditor
                          v-model="record.hours"
                          :editor="editor"
                          :config="ckConfig"
                          name="hours"
                      >
                      </ckeditor>
                    </div>
                    <div v-if="errors.hours" class="invalid-feedback-ckeditor">
                      {{ errors.hours }}
                    </div>
                  </template>
                </div>
                <template v-if="userCanEdit && isEditMode">
                  <div class="form-row">
                    <div class="form-group col-md-12">
                      <label for="buildingType">Building Type *</label>
                      <Field
                          name="buildingType"
                          v-slot="{ field }"
                          v-model="record.buildingType"
                      >
                        <VueMultiselect
                            data-vv-as="building type"
                            v-model="record.buildingType"
                            v-bind="field"
                            :options="buildingTypes"
                            :multiple="false"
                            placeholder="What kind of building is this?"
                            label="name"
                            track-by="id"
                            style="padding:0"
                            id="buildingType"
                            :class="{'is-invalid': errors.buildingType }"
                        >
                        </VueMultiselect>
                      </Field>
                      <div class="invalid-feedback">
                        {{ errors.buildingType }}
                      </div>
                    </div>
                  </div>
                </template>
                <template v-else>
                  <h5>Building Type *</h5>
                  <p v-if="record.buildingType">{{ record.buildingType.name }}</p>
                  <p v-else>None</p>
                </template>
                <template v-if="userCanEdit && isEditMode">
                  <div class="form-row">
                    <div class="form-group col-md-12">
                      <label class="form-check-label">
                        Include in Admissions Tour (Set the position the building will appear in the Tour)
                      </label>
                      <input
                          v-model="record.admissionsTour"
                          class="form-control"
                          type="number"
                          id="admissionsTour"
                      />
                    </div>
                  </div>
                </template>
                <template v-else>
                  <p v-if="record.admissionsTour" style="color: blue">**Included in Admissions Tour**</p>
                </template>
                <!-- PILLS FOR AUXILARY -->
                <div class="row mb-4">
                  <div class="col">
                    <ul class="nav nav-tabs mb-3" id="pills-tab" role="tablist">
                      <li class="nav-item">
                        <a class="nav-link active" id="pills-bathrooms-tab" data-toggle="pill"
                           href="#pills-bathrooms" role="tab" aria-controls="pills-bathrooms"
                           aria-selected="true">Bathrooms <span class="badge badge-light">{{
                            record.bathrooms.length
                           }}</span></a>
                      </li>
                      <li class="nav-item">
                        <a class="nav-link" id="pills-dining-tab" data-toggle="pill"
                           href="#pills-dining" role="tab" aria-controls="pills-dining"
                           aria-selected="true">Dining <span class="badge badge-light">{{
                            record.diningOptions.length
                            }}</span></a>
                      </li>
                      <li class="nav-item">
                        <a class="nav-link" id="pills-emergency-tab" data-toggle="pill"
                           href="#pills-emergency" role="tab" aria-controls="pills-emergency"
                           aria-selected="false">Emergency Devices <span
                            class="badge badge-light">{{ record.emergencyDevices.length }}</span></a>
                      </li>
                      <li class="nav-item">
                        <a class="nav-link" id="pills-exhibits-tab" data-toggle="pill"
                           href="#pills-exhibits" role="tab" aria-controls="pills-exhibits"
                           aria-selected="false">Exhibits <span class="badge badge-light">{{
                            record.exhibits.length
                            }}</span></a>
                      </li>
                      <li class="nav-item">
                        <a class="nav-link" id="pills-services-tab" data-toggle="pill"
                           href="#pills-services" role="tab" aria-controls="pills-services"
                           aria-selected="false">Services <span class="badge badge-light">{{
                            record.services.length }}</span></a>
                      </li>
                      <li class="nav-item">
                        <a class="nav-link" id="pills-dispensers-tab" data-toggle="pill"
                           href="#pills-dispensers" role="tab" aria-controls="pills-dispensers"
                           aria-selected="false">Cycle Dispensers <span
                            class="badge badge-light">{{ record.dispensers.length }}</span></a>
                      </li>
                    </ul>
                    <div class="tab-content" id="pills-tabContent">
                      <div class="tab-pane fade show active pl-4" id="pills-bathrooms"
                           role="tabpanel" aria-labelledby="pills-bathrooms-tab">
                        <template v-if="userCanEdit && isEditMode">
                          <div class="row">
                            <div v-for="(bathroom, index) in record.bathrooms"
                                 class="col-xs-12 col-sm-6 col-md-4 pl-4 pb-2">
                              <div class="card">
                                <div class="card-header">
                                  {{ bathroom.name }}
                                  <button type="button"
                                          @click="removeSubitemFromBuilding('bathroom', index)"
                                          class="close pull-right"><span
                                      aria-hidden="true">&times;</span></button>
                                </div>
                                <div class="card-body">
                                  <div class="form-group">
                                    <label :for="'bathroom-' + index">Location *</label>
                                    <Field
                                        data-vv-as="location"
                                        :id="'bathroom-' + index"
                                        :name="'bathroom-location-' + index"
                                        :readonly="!userCanEdit || !isEditMode"
                                        v-model="bathroom.name"
                                        type="text"
                                        class="form-control"
                                        :class="{'is-invalid': errors['bathroom-location-' + index], 'form-control-plaintext': !userCanEdit || !isEditMode}"
                                        placeholder="Location"
                                    >
                                    </Field>
                                    <div class="invalid-feedback">
                                      {{ errors['bathroom-location-' + index] }}
                                    </div>
                                  </div>
                                  <div class="form-group">
                                    <div class="form-check">
                                      <input type="checkbox"
                                        :id="'bathroomGenderNeutral-' + index"
                                        v-model="bathroom.isGenderNeutral"
                                        color="success" /> Gender neutral
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div><!-- end foreach bathrooms -->
                            <div class="col-xs-12 col-sm-6 col-md-4">
                              <div class="card mapitem-add-aux"
                                   @click="addRecordSubitem('bathroom')">
                                <div class="card-body">
                                  <i class="fa fa-plus fa-5x"></i><br/>
                                  Add bathroom
                                </div>
                              </div>
                            </div>
                          </div>
                        </template>
                        <template v-else>
                          <div v-if="record.bathrooms.length > 0">
                            <ul>
                              <li v-for="bathroom in record.bathrooms">{{ bathroom.name }}
                                <span v-if="bathroom.isGenderNeutral">(Gender neutral)</span>
                              </li>
                            </ul>
                          </div>
                          <div v-else>
                            <p>No bathrooms attributed to this building.</p>
                          </div>
                        </template>
                      </div>
                      <div class="tab-pane fade pl-4" id="pills-dining" role="tabpanel"
                           aria-labelledby="pills-dining-tab">
                        <template v-if="userCanEdit && isEditMode">
                          <div class="row">
                            <div v-for="(dining, index) in record.diningOptions"
                                 class="col-xs-12 col-sm-6 col-md-4 pl-4 pb-2">
                              <div class="card">
                                <div class="card-header">
                                  {{ dining.name }}
                                  <button type="button"
                                          @click="removeSubitemFromBuilding('dining', index)"
                                          class="close pull-right"><span
                                      aria-hidden="true">&times;</span></button>
                                </div>
                                <div class="card-body">
                                  <div class="form-group">
                                    <label>Name *</label>
                                    <Field
                                        data-vv-as="dining option name"
                                        :name="'dining-name-' + index"
                                        :readonly="!userCanEdit || !isEditMode"
                                        v-model="dining.name"
                                        type="text"
                                        class="form-control"
                                        :class="{'is-invalid': errors['dining-name-' + index]}"
                                        placeholder="Dining option name"
                                    >
                                    </Field>
                                    <div class="invalid-feedback">
                                      {{ errors['dining-name-' + index] }}
                                    </div>
                                  </div>
                                  <div class="form-group">
                                    <label>Hours</label>
                                    <textarea
                                        data-vv-as="hours"
                                        class="form-control"
                                        name="hours"
                                        :class="{'is-invalid': errors['dining-hours-' + index], 'form-control-plaintext': !userCanEdit || !isEditMode}"
                                        :readonly="!userCanEdit || !isEditMode"
                                        v-model="dining.hours"
                                        placeholder="Hours go here...">
                                    </textarea>
                                    <div class="invalid-feedback">
                                      {{ errors['dining-hours-' + index] }}
                                    </div>
                                  </div>
                                  <div class="form-group">
                                    <label>Description</label>
                                    <textarea
                                        data-vv-as="description"
                                        v-model="dining.description"
                                        class="form-control"
                                        :name="'dining-description-' + index"
                                        :class="{'is-invalid': errors['dining-description-' + index] }"
                                        placeholder="Describe this dining option here...">
                                    </textarea>
                                    <div class="invalid-feedback">
                                      {{ errors['dining-description-' + index] }}
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div><!-- end foreach diningOptions -->
                            <div class="col-xs-12 col-sm-6 col-md-4">
                              <div class="card mapitem-add-aux"
                                   @click="addRecordSubitem('dining')">
                                <div class="card-body">
                                  <i class="fa fa-plus fa-5x"></i><br/>
                                  Add dining option
                                </div>
                              </div>
                            </div>
                          </div>
                        </template>
                        <template v-else>
                          <div v-if="record.diningOptions.length > 0">
                            <ul>
                              <li v-for="dining in record.diningOptions">{{
                                  dining.name }}
                              </li>
                            </ul>
                          </div>
                          <div v-else>
                            <p>No dining options attributed to this building.</p>
                          </div>
                        </template>
                      </div>
                      <div class="tab-pane fade pl-4" id="pills-emergency" role="tabpanel"
                           aria-labelledby="pills-emergency-tab">
                        <template v-if="userCanEdit && isEditMode">
                          <div class="row">
                            <div v-for="(emergency, index) in record.emergencyDevices"
                                 class="col-xs-12 col-sm-6 col-md-4 pl-4 pb-2">
                              <div class="card">
                                <div class="card-header">
                                  {{ emergency.name }}
                                  <button type="button"
                                          @click="removeSubitemFromBuilding('emergency device', index)"
                                          class="close pull-right"><span
                                      aria-hidden="true">&times;</span></button>
                                </div>
                                <div class="card-body">
                                  <div class="form-group">
                                    <label :for="'emergencyLocation-' + index">Location
                                      *</label>
                                    <Field
                                        data-vv-as="location"
                                        :name="'emergency-location-' + index"
                                        :id="'emergencyLocation-' + index"
                                        v-model="emergency.name"
                                        type="text"
                                        class="form-control"
                                        :class="{'is-invalid': errors['emergency-location-' + index]}"
                                        placeholder="Location"
                                    >
                                    </Field>
                                    <div class="invalid-feedback">
                                      {{ errors['emergency-location-' + index] }}
                                    </div>
                                  </div>
                                  <div class="form-group">
                                    <label :for="'emergencyType-' + index"
                                           class="sr-only">Type *</label>
                                    <Field
                                        :name="'emergency-type-' + index"
                                        v-slot="{ field }"
                                        v-model="record.emergencyDevices[index].type"
                                    >
                                      <VueMultiselect
                                          data-vv-as="device type"
                                          v-model="record.emergencyDevices[index].type"
                                          v-bind="field"
                                          :options="emergencyTypes"
                                          :multiple="false"
                                          placeholder="Choose type *"
                                          label="name"
                                          track-by="id"
                                          style="padding:0"
                                          :id="'emergencyType-' + index"
                                          :class="{'is-invalid': errors['emergency-type-' + index] }"
                                      >
                                      </VueMultiselect>
                                    </Field>
                                    <div class="invalid-feedback">
                                      {{ errors['emergency-type-' + index] }}
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div><!-- end foreach emergency device -->
                            <div class="col-xs-12 col-sm-6 col-md-4">
                              <div class="card mapitem-add-aux"
                                   @click="addRecordSubitem('emergency device')">
                                <div class="card-body">
                                  <i class="fa fa-plus fa-5x"></i><br/>
                                  Add emergency device
                                </div>
                              </div>
                            </div>
                          </div>
                        </template>
                        <template v-else>
                          <div v-if="record.emergencyDevices.length > 0">
                            <ul>
                              <li v-for="emergency in record.emergencyDevices">{{
                                  emergency.name
                                }} – {{ emergency.type.name }}
                              </li>
                            </ul>
                          </div>
                          <div v-else>
                            <p>No emergency devices attributed to this building.</p>
                          </div>
                        </template>
                      </div>
                      <div class="tab-pane fade pl-4" id="pills-exhibits" role="tabpanel"
                           aria-labelledby="pills-exhibits-tab">
                        <template v-if="userCanEdit && isEditMode">
                          <div class="row">
                            <div v-for="(exhibit, index) in record.exhibits"
                                 class="col-xs-12 col-sm-6 col-md-4 pl-4 pb-2">
                              <div class="card">
                                <div class="card-header">
                                  {{ exhibit.name }}
                                  <button type="button"
                                          @click="removeSubitemFromBuilding('exhibit', index)"
                                          class="close pull-right"><span
                                      aria-hidden="true">&times;</span></button>
                                </div>
                                <div class="card-body">
                                  <div class="form-group">
                                    <label :for="'exhibitName-' + index">Title *</label>
                                    <Field
                                        data-vv-as="title"
                                        :name="'exhibit-name-' + index"
                                        :id="'exhibitName-' + index"
                                        v-model="exhibit.name"
                                        type="text"
                                        class="form-control"
                                        :class="{'is-invalid': errors['exhibit-name-' + index]}"
                                        placeholder="Location"
                                    >
                                    </Field>
                                    <div class="invalid-feedback">
                                      {{ errors['exhibit-name-' + index] }}
                                    </div>
                                  </div>
                                  <div class="form-group">
                                    <label :for="'exhibitDescription-' + index"
                                           class="sr-only">Description</label>
                                    <Field
                                        :name="'exhibit-description-' + index"
                                        v-slot="{ field }"
                                        v-model="exhibit.description"
                                    >
                                      <textarea
                                          data-vv-as="description"
                                          v-model="exhibit.description"
                                          v-bind="field"
                                          placeholder="Describe the exhibit here..."
                                          class="form-control"
                                          :id="'emergencyType-' + index"
                                          :class="{'is-invalid': errors['exhibit-description-' + index] }"
                                      >
                                      </textarea>
                                    </Field>
                                    <div class="invalid-feedback">
                                      {{ errors['exhibit-description-' + index] }}
                                    </div>
                                  </div>
                                  <div class="form-group">
                                    <label :for="'exhibitType-' + index"
                                           class="sr-only">Type *</label>
                                    <Field
                                        :name="'exhibit-type-' + index"
                                        v-slot="{ field }"
                                        v-model="record.exhibits[index].type"
                                    >
                                      <VueMultiselect
                                          data-vv-as="device type"
                                          v-model="record.exhibits[index].type"
                                          v-bind="field"
                                          :options="exhibitTypes"
                                          :multiple="false"
                                          placeholder="Choose type *"
                                          label="name"
                                          track-by="id"
                                          style="padding:0"
                                          :id="'exhibitType-' + index"
                                          :class="{'is-invalid': errors['exhibit-type-' + index] }"
                                      >
                                      </VueMultiselect>
                                    </Field>
                                    <div class="invalid-feedback">
                                      {{ errors['exhibit-type-' + index] }}
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div><!-- end foreach exhibit -->
                            <div class="col-xs-12 col-sm-6 col-md-4">
                              <div class="card mapitem-add-aux"
                                   @click="addRecordSubitem('exhibit')">
                                <div class="card-body">
                                  <i class="fa fa-plus fa-5x"></i><br/>
                                  Add exhibit
                                </div>
                              </div>
                            </div>
                          </div>
                        </template>
                        <template v-else>
                          <div v-if="record.exhibits.length > 0">
                            <ul>
                              <li v-for="exhibit in record.exhibits">{{ exhibit.name }} –
                                {{ exhibit.type.name }}
                              </li>
                            </ul>
                          </div>
                          <div v-else>
                            <p>No exhibits attributed to this building.</p>
                          </div>
                        </template>
                      </div><!-- end .tab-pane -->
                      <div class="tab-pane fade pl-4" id="pills-services" role="tabpanel"
                           aria-labelledby="pills-services-tab">
                        <template v-if="userCanEdit && isEditMode">
                          <div class="row">
                            <div v-for="(service, index) in record.services"
                                 class="col-xs-12 col-sm-6 col-md-4 pl-4 pb-2">
                              <div class="card">
                                <div class="card-header">
                                  {{ service.name }}
                                  <button type="button"
                                          @click="removeSubitemFromBuilding('service', index)"
                                          class="close pull-right"><span
                                      aria-hidden="true">&times;</span></button>
                                </div>
                                <div class="card-body">
                                  <div class="form-group">
                                    <label :for="'serviceName-' + index">Service/Name
                                      *</label>
                                    <Field
                                        data-vv-as="title"
                                        :name="'service-name-' + index"
                                        :id="'serviceName-' + index"
                                        v-model="service.name"
                                        type="text"
                                        class="form-control"
                                        :class="{'is-invalid': errors['service-name-' + index]}"
                                        placeholder="Service/Name"
                                    >
                                    </Field>
                                    <div class="invalid-feedback">
                                      {{ errors['service-name-' + index] }}
                                    </div>
                                  </div>
                                  <div class="form-group">
                                    <label :for="'serviceDescription-' + index"
                                           class="sr-only">Description</label>
                                    <Field
                                        :name="'service-description-' + index"
                                        v-slot="{ field }"
                                        v-model="service.description"
                                    >
                                      <textarea
                                          data-vv-as="description"
                                          v-model="service.description"
                                          v-bind="field"
                                          placeholder="Describe the service here..."
                                          class="form-control"
                                          :id="'serviceDescription-' + index"
                                          :class="{'is-invalid': errors['service-description-' + index] }"
                                      >
                                      </textarea>
                                    </Field>
                                    <div class="invalid-feedback">
                                      {{ errors['service-description-' + index] }}
                                    </div>
                                  </div>
                                  <div class="form-group">
                                    <label :for="'serviceType-' + index"
                                           class="sr-only">Type *</label>
                                    <Field
                                        :name="'service-type-' + index"
                                        v-slot="{ field }"
                                        v-model="record.services[index].type"
                                    >
                                      <VueMultiselect
                                          data-vv-as="device type"
                                          v-model="record.services[index].type"
                                          v-bind="field"
                                          :options="serviceTypes"
                                          :multiple="false"
                                          placeholder="Choose type *"
                                          label="name"
                                          track-by="id"
                                          style="padding:0"
                                          :id="'serviceType-' + index"
                                          :class="{'is-invalid': errors['service-type-' + index] }"
                                      >
                                      </VueMultiselect>
                                    </Field>
                                    <div class="invalid-feedback">
                                      {{ errors['service-type-' + index] }}
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div><!-- end foreach exhibit -->
                            <div class="col-xs-12 col-sm-6 col-md-4">
                              <div class="card mapitem-add-aux"
                                   @click="addRecordSubitem('service')">
                                <div class="card-body">
                                  <i class="fa fa-plus fa-5x"></i><br/>
                                  Add service
                                </div>
                              </div>
                            </div>
                          </div>
                        </template>
                        <template v-else>
                          <div v-if="record.services.length > 0">
                            <ul>
                              <li v-for="service in record.services">{{ service.name }} –
                                {{ service.type.name }}
                              </li>
                            </ul>
                          </div>
                          <div v-else>
                            <p>No services attributed to this building.</p>
                          </div>
                        </template>
                      </div><!-- end .tab-pane -->


                      <div class="tab-pane fade pl-4" id="pills-dispensers" role="tabpanel"
                           aria-labelledby="pills-dispensers-tab">
                        <template v-if="userCanEdit && isEditMode">
                          <div class="row">
                            <div v-for="(dispenser, index) in record.dispensers"
                                 class="col-xs-12 col-sm-6 col-md-4 pl-4 pb-2">
                              <div class="card">
                                <div class="card-header">
                                  {{ dispenser.name }}
                                  <button type="button"
                                          @click="removeSubitemFromBuilding('dispenser', index)"
                                          class="close pull-right"><span
                                      aria-hidden="true">&times;</span></button>
                                </div>
                                <div class="card-body">
                                  <div class="form-group">
                                    <label :for="'dispenserName-' + index">Location
                                      *</label>
                                    <Field
                                        data-vv-as="title"
                                        :name="'dispenser-name-' + index"
                                        :id="'dispenserName-' + index"
                                        v-model="dispenser.name"
                                        type="text"
                                        class="form-control"
                                        :class="{'is-invalid': errors['dispenser-name-' + index]}"
                                        placeholder="Location of Dispenser"
                                    >
                                    </Field>
                                    <div class="invalid-feedback">
                                      {{ errors['dispenser-name-' + index] }}
                                    </div>
                                  </div>
                                  <div class="form-group">
                                    <label :for="'dispenserDescription-' + index"
                                           class="sr-only">Description *</label>
                                    <Field
                                        :name="'dispenser-description-' + index"
                                        v-slot="{ field }"
                                        v-model="dispenser.description"
                                    >
                                      <textarea
                                          data-vv-as="description"
                                          v-model="dispenser.description"
                                          v-bind="field"
                                          placeholder="Other details here... *"
                                          class="form-control"
                                          :id="'dispenserDescription-' + index"
                                          :class="{'is-invalid': errors['dispenser-description-' + index] }"
                                      >
                                      </textarea>
                                    </Field>
                                    <div class="invalid-feedback">
                                      {{ errors['dispenser-description-' + index] }}
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div><!-- end foreach dispenser -->
                            <div class="col-xs-12 col-sm-6 col-md-4">
                              <div class="card mapitem-add-aux"
                                   @click="addRecordSubitem('dispenser')">
                                <div class="card-body">
                                  <i class="fa fa-plus fa-5x"></i><br/>
                                  Add dispenser
                                </div>
                              </div>
                            </div>
                          </div>
                        </template>
                        <template v-else>
                          <div v-if="record.dispensers.length > 0">
                            <ul>
                              <li v-for="(dispenser, i) in record.dispensers" :key="'dispenser-'+i">{{ dispenser.name }}
                              </li>
                            </ul>
                          </div>
                          <div v-else>
                            <p>No cycle dispensers attributed to this building.</p>
                          </div>
                        </template>
                      </div><!-- end .tab-pane -->
                    </div><!-- end .tablist -->
                  </div>
                </div><!-- end auxuilary pills -->
              </fieldset>
            </template>
            <!-- EMERGENCY DEVICE FIELDS -->
            <template v-if="record.itemType == 'emergency device'">
              <fieldset>
                <legend>{{ capitalize(record.itemType) }} specific fields</legend>
                <div v-if="userCanEdit && isEditMode" class="form-row">
                  <div class="form-group col-md-8">
                    <label for="building">Building (can be blank)</label>
                    <VueMultiselect
                        v-model="record.building"
                        :options="buildings"
                        :multiple="false"
                        placeholder="Choose building"
                        label="name"
                        track-by="id"
                        class="form-control"
                        style="padding:0"
                        name="building"
                        id="building"
                        :class="{'is-invalid': errors.building }"
                    >
                    </VueMultiselect>
                    <div class="invalid-feedback">
                      {{ errors.building }}
                    </div>
                  </div>
                  <div class="form-group col-md-4">
                    <label for="deviceType">Type *</label>
                    <Field
                        name="emergency-type"
                        v-slot="{ field }"
                        v-model="record.type"
                    >
                      <VueMultiselect
                          data-vv-as="device type"
                          v-model="record.type"
                          :options="emergencyTypes"
                          :multiple="false"
                          placeholder="Choose type *"
                          label="name"
                          track-by="id"
                          class="form-control"
                          style="padding:0"
                          id="deviceType"
                          :class="{'is-invalid': errors['emergency-type'] }"
                      >
                      </VueMultiselect>
                    </Field>
                    <div class="invalid-feedback">
                      {{ errors['emergency-type'] }}
                    </div>
                  </div>
                </div>
                <div v-else>
                  <p v-if="record.building">This device is in {{ record.building.name }}</p>
                  <p v-else>This devices is located outside.</p>
                  <p>Device type: {{ record.type.name }}</p>
                </div>
              </fieldset>
            </template>
            <!-- EXHIBIT FIELDS -->
            <template v-if="record.itemType == 'exhibit'">
              <fieldset>
                <legend>{{ capitalize(record.itemType) }} specific fields</legend>
                <div v-if="userCanEdit && isEditMode" class="form-row">
                  <div class="form-group col-md-8">
                    {{ record.building }}
                    <label for="building">Building (can be blank)</label>
                    <VueMultiselect
                        v-model="record.building"
                        :options="buildings"
                        :multiple="false"
                        placeholder="Choose building"
                        label="name"
                        track-by="id"
                        class="form-control"
                        style="padding:0"
                        name="building"
                        id="building"
                        :class="{'is-invalid': errors.building }"
                    >
                    </VueMultiselect>
                    <div class="invalid-feedback">
                      {{ errors.building }}
                    </div>
                  </div>
                  <div class="form-group col-md-4">
                    <label for="exhibitType">Type *</label>
                    <Field
                        name="exhibit-type"
                        v-slot="{ field }"
                        v-model="record.type"
                    >
                      <VueMultiselect
                          data-vv-as="exhibit type"
                          v-model="record.type"
                          :options="exhibitTypes"
                          :multiple="false"
                          placeholder="Choose type *"
                          label="name"
                          track-by="id"
                          class="form-control"
                          style="padding:0"
                          id="exhibitType"
                          :class="{'is-invalid': errors['exhibit-type'] }"
                      >
                      </VueMultiselect>
                    </Field>
                    <div class="invalid-feedback">
                      {{ errors['exhibit-type'] }}
                    </div>
                  </div>
                </div>
                <div v-else>
                  <p v-if="record.building">This exhibit is in {{ record.building.name }}</p>
                  <p v-else>This exhibit is located outside.</p>
                  <p>Exhibit type: {{ record.type.name }}</p>
                </div>
              </fieldset>
            </template>
            <!-- PARKING FIELDS -->
            <template v-if="record.itemType == 'parking'">
              <fieldset>
                <legend>{{ capitalize(record.itemType) }} specific fields</legend>
                <template v-if="userCanEdit && isEditMode" class="form-row">
                  <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Lot hours</label>
                        <textarea
                                class="form-control"
                                name="hours"
                                :class="{'is-invalid': errors.description, 'form-control-plaintext': !userCanEdit || !isEditMode}"
                                :readonly="!userCanEdit || !isEditMode"
                                v-model="record.hours">
                        </textarea>
                        <div class="invalid-feedback">
                            {{ errors.hours }}
                        </div>
                    </div>
                    <div class="form-group col-md-6">
                      <label>Lot hours</label>
                      <template v-if="!userCanEdit || !isEditMode">
                        <span v-html="record.hours"></span>
                      </template>
                      <template v-else>
                        <div :class="{'is-invalid-ckeditor': errors.hours }">
                          <ckeditor
                              v-model="record.hours"
                              :config="ckConfig"
                              name="hours"
                          >
                          </ckeditor>
                        </div>
                        <div v-if="errors.hours" class="invalid-feedback-ckeditor">
                          {{ errors.hours }}
                        </div>
                      </template>
                    </div>
                    <div class="form-group col-md-6">
                      <div class="form-group">
                        <label>Spaces</label>
                        <input
                            name="spaces"
                            type="number"
                            min="0"
                            step="1"
                            class="form-control"
                            :class="{ 'is-invalid': errors.spaces, 'form-control-plaintext': !userCanEdit || !isEditMode }"
                            :readonly="!userCanEdit || !isEditMode"
                            v-model="record.spaces">
                        <div class="invalid-feedback">
                          {{ errors.spaces }}
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="form-row">
                    <div class="form-group col-md-6">
                      <label>Lot type(s)</label>
                      <VueMultiselect
                          v-model="record.parkingTypes"
                          :options="parkingTypes"
                          :multiple="true"
                          placeholder="Choose lot types"
                          label="name"
                          track-by="id"
                          class="form-control"
                          style="padding:0"
                          name="building"
                          id="parkingTypes"
                          :class="{'is-invalid': errors.parkingTypes }"
                      >
                      </VueMultiselect>
                      <div class="invalid-feedback">
                        {{ errors.parkingTypes }}
                      </div>
                    </div>
                    <div class="form-group col-md-6">
                      <div class="form-check">
                        <input v-model="record.hasHandicapSpaces" class="form-check-input"
                               type="checkbox" id="hasHandicapSpaces">
                        <label class="form-check-label" for="hasHandicapSpaces">
                          Handicap parking
                        </label>
                      </div>
                    </div>
                  </div>
                </template>
                <template v-else>
                  <div class="row">
                    <div class="col-md-4">
                      Lot Type(s):
                      <ul v-if="record.parkingTypes.length > 0">
                        <li v-for="lotType in record.parkingTypes">{{ lotType.name }}</li>
                      </ul>
                    </div>
                    <div class="col-md-4">
                      Hours: {{ record.hours }}
                    </div>
                    <div class="col-md-4">
                      Parking Spaces: {{ record.spaces }}
                    </div>
                  </div>
                </template>
              </fieldset>
            </template>
            <div v-if="Object.keys(errors).length && isEditMode" class="alert alert-danger fade show"
                 role="alert">
              Please fix all errors before submitting:
              <ul>
                <li v-for="error in errors">
                  <strong>{{ error }}</strong>
                </li>
              </ul>
            </div>
            <div v-if="success" class="alert alert-success fade show" role="alert">
              {{ successMessage }}
            </div>
            <div v-if="isDeleteError === true" class="alert alert-danger fade show" role="alert">
              There was an error deleting this item.
            </div>
            <!-- ACTION BUTTONS -->
            <div v-if="userCanEdit && isEditMode" aria-label="action buttons" class="mb-4">
              <button class="btn btn-success" type="submit"><i class="fa fa-save fa-2x"></i></button>
              <button v-if="itemExists && this.permissions[0].delete" type="button"
                      class="btn btn-danger ml-4" data-toggle="modal" data-target="#deleteModal"><i
                  class="fa fa-trash fa-2x"></i></button>
            </div>
            </form>
          </VeeForm><!-- /end form -->
        </div><!-- end .tab-pane #information -->
        <!-- IMAGE TAB -->
        <div class="tab-pane fade" id="photos" role="tabpanel" aria-labelledby="photos-tab">
          <div class="row">
            <div class="col-xs-12 col-md-6">
              <h3>{{ capitalize(record.itemType) }} Images ({{ record.images.length }})</h3>
              <!-- Image order change buttons -->
              <template v-if="isImageOrderChanged">
                <button class="btn btn-success" @click="updateImageOrder()">Confirm</button>
                <button class="btn btn-default" @click="resetImageOrder()">Reset</button>
              </template>
              <div v-if="isImageOrderUpdated" class="alert alert-success fade show" role="alert">
                Image order has been updated
              </div>
              <template v-if="itemExists && userCanEdit && isEditMode">
                <draggable v-model="record.images" item-key="images" :options="{'disabled':isModalOpen}"
                           @start="drag=true" @end="onDragEnd">
                  <template v-slot:item="{ element, index }">
                    <image-thumbnail-pod
                      :key="'image-thumb-' + index"
                      :image="element"
                      :slot-no="index"
                      v-model="record.images"
                      :isEditMode="true"
                      :userCanDelete="permissions[0].delete"
                      @imageDeleteRequested="spliceDeletedImage"
                      @imageRenamed="renameImage"
                    >
                    </image-thumbnail-pod>
                  </template>
                </draggable>
              </template>
              <template v-else>
                <image-thumbnail-pod
                    v-for="(image, index) in record.images"
                    :key="'image-thumb-' + index"
                    :image="image"
                    :slot-no="index"
                    v-model="record.images"
                    :isEditMode="false"
                >
                </image-thumbnail-pod>
              </template>
            </div><!-- end .col-md-6 (images)-->
            <div class="col-xs-12 col-md-6">
              <h5>Primary Photo</h5>
              <template v-if="record.images.length > 0">
                <img id="primary-image" :src="record.images[0].subdir + '/' + record.images[0].path"
                     :alt="record.images[0].name"/>
                <p><a :href="record.images[0].subdir + '/' + record.images[0].path" target="_blank">Full
                  Size</a></p>
              </template>
              <template v-else>
                <p>No photos have been uploaded yet.</p>
              </template>
            </div><!-- end .col-md-6 (images)-->
          </div><!-- end .row (images) -->
          <!--IMAGE UPLOAD-->
          <div class="row">
            <div class="col-xs-12 col-md-12">
              <template v-if="itemExists && userCanEdit && isEditMode">
                <!--SUCCESS-->
                <div v-if="isUploadSuccess">
                  <div v-if="uploadErrors.length == 0" class="alert alert-success" role="alert">
                    <p><strong>Uploaded {{ uploadedFiles.length }} file(s).</strong> <a
                        class="btn btn-default" href="javascript:void(0)"
                        @click="resetUploadForm()">Upload more images</a></p>
                  </div>
                  <div v-else class="alert alert-warning" role="alert">
                    <p><strong>Uploaded {{ uploadedFiles.length }} file(s) with the following
                      errors.</strong> <a class="btn btn-default" href="javascript:void(0)"
                                          @click="resetUploadForm()">Upload more images</a></p>
                    <ul>
                      <li v-for="uploadError in uploadErrors">{{ uploadError }}</li>
                    </ul>
                  </div>
                </div>
                <!--FAILED-->
                <div v-if="isUploadFailed" class="alert alert-danger" role="alert">
                  <p><strong>Upload failed.</strong> <a href="javascript:void(0)"
                                                        @click="resetUploadForm()">Try again</a></p>
                  <pre v-for="uploadError in uploadErrors">{{ uploadErrors }}</pre>
                </div>
                <!-- UPLOAD FORM -->
                <form enctype="multipart/form-data" novalidate
                      v-if="permissions[0].imageUpload && (isUploadInitial || isUploadSaving)">
                  <fieldset>
                    <legend>Upload Images</legend>
                    <div class="dropbox">
                      <input type="file" multiple name="uploadFiles[]" :disabled="isUploadSaving"
                             @change="filesChange($event.target.name, $event.target.files); fileCount = $event.target.files.length"
                             accept="image/*" class="input-file">
                      <p v-if="isUploadInitial">Drag your image(s) here to begin<br> or click to
                        browse.</p>
                      <p v-if="isUploadSaving">Uploading {{ fileCount }} files...</p>
                    </div>
                  </fieldset>
                </form>
              </template>
              <template v-else>
                <div class="alert alert-info" role="alert">
                  <p v-if="!itemExists">You will be able to upload images once you create the {{
                      record.itemType }}</p>
                  <p v-if="!userCanEdit">You do not have sufficient privileges to upload images</p>
                  <p v-if="!isEditMode">Photo upload only available in edit mode</p>
                </div>
              </template>
            </div><!-- end .col-xs-12 (uploads) -->
          </div><!-- end .row (uploads) -->
        </div><!-- end .tab-pane #photos -->
      </div><!-- end .tab-content -->
    </div>
    <!-- DELETE ITEM MODAL -->
    <mapitem-delete-modal
        :mapitem="record"
        @itemDeleted="markItemDeleted"
        @itemDeleteError="markItemDeleteError"
    ></mapitem-delete-modal>
  </div>
</template>
<style>
.dropbox {
  outline: 2px dashed grey; /* the dash box */
  outline-offset: -10px;
  background: lightcyan;
  color: dimgray;
  padding: 10px 10px;
  min-height: 200px; /* minimum height */
  position: relative;
  cursor: pointer;
}

.input-file {
  opacity: 0; /* invisible but it's there! */
  width: 100%;
  height: 200px;
  position: absolute;
  cursor: pointer;
}

.dropbox:hover {
  background: lightblue; /* when mouse over to the drop zone, change color */
}

.dropbox p {
  font-size: 1.2em;
  text-align: center;
  padding: 50px 0;
}

#primary-image {
  max-width: 100%;
}

.list-group-item, .list-group-item:hover {
  z-index: auto;
}

.is-invalid-ckeditor {
  border: 1px solid red;
}

.invalid-feedback-ckeditor {
  width: 100%;
  margin-top: 0.25rem;
  font-size: 80%;
  color: #dc3545;
}
</style>
<style src="vue-multiselect/dist/vue-multiselect.css"></style>
<script>
import MapitemDeleteModal from './MapitemDeleteModal.vue'
import MapitemImageDeleteModal from './MapitemImageDeleteModal.vue'
import MapitemImageEditModal from './MapitemImageEditModal.vue'
import ImageThumbnailPod from './ImageThumbnailPod.vue'
import Heading from '../utils/Heading.vue'
import VueMultiselect from 'vue-multiselect'
import NotFound from '../utils/NotFound.vue'
import Draggable from 'vuedraggable'
import GoogleMap from './GoogleMap.vue'
import ClassicEditor from '@ckeditor/ckeditor5-build-classic';
import { Field, Form as VeeForm, ErrorMessage } from 'vee-validate'
import * as Yup from 'yup'

const STATUS_INITIAL = 0, STATUS_SAVING = 1, STATUS_SUCCESS = 2, STATUS_FAILED = 3

export default {
  created () {
    document.addEventListener('click', this.toggleDragEnable) // for photo sorting

    this.resetUploadForm()

    // detect if the form should be in edit mode from the start (default is false)
    if (this.startMode == 'edit') {
      this.isEditMode = true
    }
    if (this.itemExists === false) {
      // Set the kind of map item being created via the itemType property from NewMapItemChoices
      this.isDataLoaded = true
      this.record.itemType = this.itemType
    }
    else {
      // fetch the existing record using the prop itemId
      this.fetchMapItem(this.itemId)
    }
    if (this.itemType == 'building') {
      this.fetchBuildingTypes()
      this.fetchServiceTypes()
    }
    if (this.itemType == 'building' || this.itemType == 'emergency device') {
      this.fetchEmergencyTypes()
    }
    if (this.itemType == 'building' || this.itemType == 'exhibit') {
      this.fetchExhibitTypes()
    }
    if (this.itemType == 'emergency device' || this.itemType == 'exhibit') {
      this.fetchBuildings()
    }
    if (this.itemType == 'parking') {
      this.fetchParkingTypes()
    }
  },
  components: {
    Heading,
    VueMultiselect,
    MapitemDeleteModal,
    MapitemImageDeleteModal,
    MapitemImageEditModal,
    ImageThumbnailPod,
    NotFound,
    Draggable,
    GoogleMap,
    Field,
    VeeForm,
    ErrorMessage,
    Yup
  },
  props: {
    itemType: {
      type: String,
      required: true // not required of existing items because type is already known
    },
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
      buildings: [], // for multiselect
      buildingTypes: [], // for multiselect
      ckConfig: {
        toolbar: ['Bold', 'Italic', 'Undo', 'Redo', 'NumberedList', 'BulletedList', 'Link'],
        height: 250
      },
      editor: ClassicEditor,
      currentStatus: null,
      emergencyTypes: [], // for multiselect
      exhibitTypes: [], // for multiselect
      is404: false,
      isDataLoaded: false,
      isDeleted: false,
      isDeleteError: false,
      isEditMode: false, // true = make forms editable
      isImageOrderChanged: false,
      isImageOrderUpdated: false,
      originalImageOrder: [], // when order of images is being re-arranged, put the initial images, in order, here
      parkingTypes: [], // for multiselect
      record: {
        id: '',
        address: '',
        admissionsTour: 0,
        alias: '',
        bathrooms: [],
        building: null,
        description: '',
        diningOptions: [],
        dispensers: [],
        emergencyDevices: [],
        exhibits: [],
        hasHandicapSpaces: false,
        hours: '',
        isGenderNeutral: false,
        images: [],
        itemType: '',
        latitudeSatellite: null,
        longitudeSatellite: null,
        latitudeIllustration: null,
        longitudeIllustration: null,
        name: '',
        parkingTypes: [],
        services: [],
        serviceTypes: [],
        slug: '',
        spaces: 0,
        tags: [],
      },
      recordSlug: '',
      serviceTypes: [], // for multiselect
      success: false,
      successMessage: '',
      tempLatitudeSatellite: null,
      tempLongitudeSatellite: null,
      uploadedFiles: [],
      uploadErrors: [],
      isModalOpen: false,
    }
  },
  computed: {
    mapItemSchema() {
      let yupObj = {
        name: Yup.string().required(),
        buildingType: Yup.object().required().label('Building type '),
      }
      if(this.record.itemType == 'parking') {
        yupObj['parkingTypes'] = Yup.object().required().label('Parking type ')
      }
      if(this.record.itemType == 'building') {
        yupObj['buildingType'] = Yup.object().required().label('Building type ')
      }
      if(this.record.itemType == 'emergency device') {
        yupObj['emergency-type'] = Yup.object().required().label('Device type ')
      }
      if(this.record.itemType == 'exhibit') {
        yupObj['exhibit-type'] = Yup.object().required().label('Exhibit type ')
      }
      const bathrooms = this.record.bathrooms
      for(let i = 0; i < bathrooms.length; i++) {
        bathrooms.forEach(() => {
          yupObj['bathroom-location-' + i] = Yup.string().required().label('Bathroom #' + (i+1) + ' location ')
        })
      }
      const diningOptions = this.record.diningOptions
      for(let i = 0; i < diningOptions.length; i++) {
        diningOptions.forEach(() => {
          yupObj['dining-name-' + i] = Yup.string().required().label('Dining option #' + (i+1) + ' name ')
        })
      }
      const emergencyDevices = this.record.emergencyDevices
      for(let i = 0; i < emergencyDevices.length; i++) {
        emergencyDevices.forEach(() => {
          yupObj['emergency-location-' + i] = Yup.string().required().label('Emergency device #' + (i+1) + ' location ')
          yupObj['emergency-type-' + i] = Yup.object().required().label('Emergency device #' + (i+1) + ' type ')
        })
      }
      const exhibits = this.record.exhibits
      for(let i = 0; i < exhibits.length; i++) {
        exhibits.forEach(() => {
          yupObj['exhibit-name-' + i] = Yup.string().required().label('Exhibit #' + (i+1) + ' name ')
          yupObj['exhibit-type-' + i] = Yup.object().required().label('Exhibit #' + (i+1) + ' type ')
        })
      }
      const services = this.record.services
      for(let i = 0; i < services.length; i++) {
        services.forEach(() => {
          yupObj['service-name-' + i] = Yup.string().required().label('Service #' + (i+1) + ' name ')
          yupObj['service-type-' + i] = Yup.object().required().label('Service #' + (i+1) + ' type ')
        })
      }
      const dispensers = this.record.dispensers
      for(let i = 0; i < dispensers.length; i++) {
        dispensers.forEach(() => {
          yupObj['dispenser-name-' + i] = Yup.string().required().label('Dispenser #' + (i+1) + ' name ')
          yupObj['dispenser-description-' + i] = Yup.string().required().label('Dispenser #' + (i+1) + ' description ')
        })
      }
      return Yup.object(yupObj)
    },
    headingIcon: function () {
      switch (this.record.itemType) {
        case 'building':
          return '<i class="fa fa-building"></i>'
        case 'bathroom':
          return '<i class="fa fa-male"></i><i class="fa fa-female"></i>'
        default:
          return '<i class="fa fa-map"></i>'
      }
    },
    imageDeleted: function () {
      return this.isImageDeleted ? 'image-deleted-border' : ''
    },
    isInvalid: function () {
      return 'is-invalid'
    },
    // PHOTOS
    isUploadInitial () {
      return this.currentStatus === STATUS_INITIAL;
    },
    isUploadSaving () {
      return this.currentStatus === STATUS_SAVING;
    },
    isUploadSuccess () {
      return this.currentStatus === STATUS_SUCCESS;
    },
    isUploadFailed () {
      return this.currentStatus === STATUS_FAILED;
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
    capitalize: function (value) {
      if (!value) {
        return ''
      }
      value = value.toString()
      return value.charAt(0).toUpperCase() + value.slice(1)
    },
    // e.g. add a new bathroom to a building
    addRecordSubitem: function (itemType) {
      switch (itemType) {
        case 'bathroom':
          this.record.bathrooms.push({
            name: '',
            itemType: 'bathroom',
            isGenderNeutral: false
          })
          break;
        case 'dining':
          this.record.diningOptions.push({
            name: '',
            hours: '',
            description: ''
          })
          break
        case 'emergency device':
          this.record.emergencyDevices.push({
            name: '',
            emergencyDeviceType: null,
          })
          break;
        case 'exhibit':
          this.record.exhibits.push({
            name: '',
            description: '',
            exhibitType: null,
          })
          break;
        case 'service':
          this.record.services.push({
            name: '',
            description: '',
            serviceType: null,
          })
          break;
        case 'dispenser':
          this.record.dispensers.push({
            name: '',
            description: ''
          })
          break;
      }
    },
    afterSubmitSucceeds: function () {
      let self = this
      // New item has been submitted, go to edit
      if (!this.itemExists) {
        this.success = true
        this.successMessage = "Item created."
        let newurl = '/map/items/' + this.record.id
        document.location = newurl
      }
      else {
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
      // this.$validator.validateAll()
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
    fetchBuildings () {
      let self = this
      axios.get('/api/mapbuildings')
      // success
      .then(function (response) {
        self.buildings = response.data
      })
      // fail
      .catch(function (error) {
        self.apiError.status = error.response.status
        switch (error.response.status) {
          case 403:
            self.apiError.message = "You do not have sufficient privileges to retrieve buildings."
            break
          case 404:
            self.apiError.message = "Buildings were not found."
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
    fetchBuildingTypes () {
      const self = this
      axios.get('/api/mapbuildingtypes')
      // success
      .then(function (response) {
        self.buildingTypes = response.data
      })
      // fail
      .catch(function (error) {
        switch (error.response.status) {
          case 403:
            self.apiError.message = "You do not have sufficient privileges to retrieve building types."
            break
          case 404:
            self.apiError.message = "Buildings types were not found."
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
    fetchEmergencyTypes () {
      let self = this
      axios.get('/api/mapemergencytypes')
      // success
      .then(function (response) {
        self.emergencyTypes = response.data
      })
      // fail
      .catch(function (error) {
        switch (error.response.status) {
          case 403:
            self.apiError.message = "You do not have sufficient privileges to retrieve emergency types."
            break
          case 404:
            self.apiError.message = "Emergency types were not found."
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
    fetchExhibitTypes () {
      let self = this
      axios.get('/api/mapexhibittypes')
      // success
      .then(function (response) {
        self.exhibitTypes = response.data
      })
      // fail
      .catch(function (error) {
        switch (error.response.status) {
          case 403:
            self.apiError.message = "You do not have sufficient privileges to retrieve exhibit types."
            break
          case 404:
            self.apiError.message = "Exhibit types were not found."
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
    fetchMapItem (itemId) {
      let self = this
      axios.get('/api/mapitems/' + itemId)
      // success
      .then(function (response) {
        self.record = response.data
        self.isDataLoaded = true
        self.setOriginalImages(self.record.images)
      })
      // fail
      .catch(function (error) {
        if (error.request.status == 404) {
          self.is404 = true
          self.isDataLoaded = true
        }
      })
    },
    fetchParkingTypes () {
      let self = this
      axios.get('/api/mapparkingtypes')
      // success
      .then(function (response) {
        self.parkingTypes = response.data
      })
      // fail
      .catch(function (error) {
        switch (error.response.status) {
          case 403:
            self.apiError.message = "You do not have sufficient privileges to retrieve parking types."
            break
          case 404:
            self.apiError.message = "Parking types were not found."
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
    fetchServiceTypes () {
      let self = this
      axios.get('/api/mapservicetypes')
      // success
      .then(function (response) {
        self.serviceTypes = response.data
      })
      // fail
      .catch(function (error) {
        switch (error.response.status) {
          case 403:
            self.apiError.message = "You do not have sufficient privileges to retrieve service types."
            break
          case 404:
            self.apiError.message = "Service types were not found."
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
    filesChange (fieldName, fileList) {
      // handle file changes
      const formData = new FormData()
      if (!fileList.length) {
        return
      }
      // append the files to FormData
      Array
      .from(Array(fileList.length).keys())
      .map(x => {
        formData.append(fieldName, fileList[x], fileList[x].name)
      });
      // save it
      this.uploadImages(formData);
    },
    // Emit an event to the parent component telling to go back to the last screen (applies to new item creation)
    goBack: function () {
      this.$emit('goBackStep1')
    },
    // Error checking for emergency devices
    isEmergencyError: function (index) {
      let key = "emergencyDeviceIndex" + index
      // there was an error found for this key
      if (this.errors[key]) {
        return true
      }
      return false
    },
    // Called from the @itemDeleted event emission from the Delete Modal
    markItemDeleted: function () {
      this.isDeleteError = false
      this.isDeleted = true
      setTimeout(function () {
        // This record doesn't exist anymore, so send the user back to the map items list page
        window.location.replace('/map/items')
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
    // When a user has finished dragging (re-ordering) an image
    onDragEnd: function (evt) {
      this.isImageOrderChanged = true
    },
    // use the map item's array index
    removeSubitemFromBuilding (type, index) {
      switch (type) {
        case 'bathroom':
          this.record.bathrooms.splice(index, 1)
          break
        case 'dining':
          this.record.diningOptions.splice(index, 1)
          break
        case 'emergency device':
          this.record.emergencyDevices.splice(index, 1)
          break
        case 'exhibit':
          this.record.exhibits.splice(index, 1)
          break
        case 'service':
          this.record.services.splice(index, 1)
          break
        case 'dispenser':
          this.record.dispensers.splice(index, 1)
          break
      }
    },
    // // Called as a result of $emit from child component
    renameImage: function (image) {
      for (let i = this.record.images.length - 1; i >= 0; i--) {
        if (this.record.images[i].id == image.id) {
          this.record.images[i].name = image.name
        }
      }
    },
    resetImageOrder: function () {
      this.record.images = this.originalImageOrder
      this.isImageOrderChanged = false
    },
    resetUploadForm: function () {
      // reset form to initial state
      this.currentStatus = STATUS_INITIAL
      this.uploadedFiles = [];
      this.uploadErrors = [];
    },
    setIllustratedLocation: function (position) {
      if (position) {
        this.record.latitudeIllustration = position.lat()
        this.record.longitudeIllustration = position.lng()
      }
      else {
        this.record.latitudeIllustration = null
        this.record.longitudeIllustration = null
      }
    },
    // JSON.stringify the original image order in case a user wants to reset the drag and drop order
    setOriginalImages: function (imageArr) {
      this.originalImageOrder = JSON.parse(JSON.stringify(imageArr))
    },
    setSatelliteLocation: function (position) {
      if (position) {
        this.record.latitudeSatellite = position.lat()
        this.record.longitudeSatellite = position.lng()
      }
      else {
        this.record.latitudeSatellite = null
        this.record.longitudeSatellite = null
      }
    },
    // Called as a result of $emit from child component
    spliceDeletedImage: function (image) {
      for (let i = this.record.images.length - 1; i >= 0; i--) {
        if (this.record.images[i].id == image.id) {
          this.record.images.splice(i, 1) // splice the deleted item from the record
        }
      }
    },
    // Submit the form via the API
    submitForm: function () {
      let self = this // 'this' loses scope within axios
      let method = (this.itemExists) ? 'put' : 'post'
      let route = (this.itemExists) ? '/api/mapitem' : '/api/mapitems'
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
        errors.forEach(function (error) {
          console.log(error)
          // let key = error.property_path
          // let message = error.message
          // self.$validator.errors.add(key, message)
        })
      })
    },
    updateImageOrder: function () {
      let self = this
      // Get current images in order
      let imageIdsObj = {
        imageIds: []
      }
      this.record.images.forEach(function (image) {
        imageIdsObj.imageIds.push(image.id)
      })
      axios.put('/api/mapitemimages/reorder', imageIdsObj)
      // success
      .then(function (response) {
        self.isImageOrderChanged = false
        // flash the success message for three seconds
        self.isImageOrderUpdated = true
        setTimeout(function () {
          self.isImageOrderUpdated = false
        }, 3000)
        self.setOriginalImages(self.record.images) // make these the new 'original' images, reflecting the proper order
      })
      // fail
      .catch(function (error) {
        switch (error.response.status) {
          case 403:
            self.apiError.message = "You do not have sufficient privileges to reorder images."
            break
          case 404:
            self.apiError.message = "Images were not found."
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
    uploadImages: function (formData) {
      let self = this
      this.currentStatus = STATUS_SAVING
      // append map item ID to form data
      formData.append('mapitem_id', this.record.id)

      // AJAX (axios) submission
      axios.post('/api/mapitemimages/uploads', formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      })
      // success
      .then(function (response) {
        // add the new images to the record's array of images
        response.data.processedImages.forEach(function (image) {
          self.record.images.push(image)
        })
        self.setOriginalImages(self.record.images) // set original image order to reflect new image
        self.uploadedFiles = [].concat(response.data.processedImages)
        self.uploadErrors = response.data.errors
        self.currentStatus = STATUS_SUCCESS
      })
      // fail
      .catch(function (error) {
        self.currentStatus = STATUS_FAILED
        switch (error.response.status) {
          case 403:
            self.apiError.message = "You do not have sufficient privileges to upload images."
            break
          case 404:
            self.apiError.message = "Images were not found."
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
    },
    toggleDragEnable: function () {
      $('.deleteImageModal').hasClass('show') || $('.editImageModal').hasClass('show') ? this.isModalOpen = true : this.isModalOpen = false;
    }
  }
}
</script>
