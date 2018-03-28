<template>
  <div>
    <not-found v-if="is404 === true"></not-found>
    <div v-if="isDataLoaded === false">
      <p style="text-align: center"><img src="/images/loading.gif" alt="Loading..." /></p>
    </div>
    <div v-if="apiError.status" class="alert alert-danger fade show" role="alert">
      {{ apiError.message }}
    </div>
    <div v-if="isDeleted === true" class="alert alert-info alert-dismissible fade show" role="alert">
      {{ record.itemType | capitalize }} "{{ record.name }}" item has been deleted.
      <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
      </button>
    </div>
    <!-- MAIN AREA -->
    <div v-if="isDataLoaded === true && isDeleted === false && is404 === false">
      <heading>
        <span slot="icon" v-html="headingIcon">{{ headingIcon }}</span>
        <span v-if="!itemExists" slot="title">Step 2/2: Provide {{ record.itemType }} information</span>
        <span v-else slot="title">Map {{ record.itemType }}: {{ record.name }}</span>
      </heading>
      <p>
        <a href="/map/items" class="btn btn-info pull-left"><i class="fa fa-arrow-left"></i></a>
        <button v-if="itemExists && this.permissions[0].edit" type="button" class="btn btn-info pull-right" @click="toggleEdit"><span v-html="lockIcon"></span></button>
      </p>
      <!-- TABS -->
      <ul class="nav nav-tabs" id="myTab" role="tablist">
        <li class="nav-item">
          <a class="nav-link active" id="home-tab" data-toggle="tab" href="#information" role="tab" aria-controls="information" aria-selected="true">Information</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" id="profile-tab" data-toggle="tab" href="#photos" role="tab" aria-controls="photos" aria-selected="false">Photos</a>
        </li>
      </ul>
      <div class="tab-content" id="mapitemTabContent">
        <div class="tab-pane fade show active" id="information" role="tabpanel" aria-labelledby="information-tab">
          <form class="form" @submit.prevent="checkForm">
            <fieldset>
              <legend>Basic Information</legend>
              <div class="form-group">
                <label>Name *</label>
                <input
                  v-validate="'required'"
                  name="name"
                  type="text"
                  class="form-control"
                  :class="{ 'is-invalid': errors.has('name'), 'form-control-plaintext': !userCanEdit || !isEditMode }"
                  :readonly="!userCanEdit || !isEditMode"
                  v-model="record.name">
                <div class="invalid-feedback">
                  {{ errors.first('name') }}
                </div>
              </div>
              <div class="form-group">
                <label>Slug</label>
                <input
                  type="text"
                  class="form-control form-control-plaintext"
                  :class="{'is-invalid': errors.has('slug')}"
                  readonly
                  v-model="record.slug">
                <div class="invalid-feedback">
                  {{ errors.first('slug') }}
                </div>
              </div>
              <div class="form-group">
                <label>Description</label>
                <textarea
                  class="form-control"
                  name="description"
                  :class="{'is-invalid': errors.has('description'), 'form-control-plaintext': !userCanEdit || !isEditMode}"
                  :readonly="!userCanEdit || !isEditMode"
                  v-model="record.description">
                </textarea>
                <div class="invalid-feedback">
                  {{ errors.first('description') }}
                </div>
              </div>
            </fieldset>
            <!-- GOOGLE MAP (edit mode only!)-->
            <fieldset v-if="userCanEdit && isEditMode">
              <legend>Set {{ record.itemType }} coordinates</legend>
              <p>Click on the map at the desired location. Use the "Set Location" button to set a marker.</p>
              <div class="row">
                <div class="col-md-8">
                  <gmap-map
                    :center="center"
                    :map-type-id.sync="mapType"
                    :zoom="6"
                    style="width: 100%; height: 300px"
                    @click="setTempPosition"
                  >
                    <gmap-polygon :paths.sync="plPath" :editable="true" :options="{strokeColor:'#FF0000', fillColor:'#000000'}"></gmap-polygon>
                    <gmap-marker
                      :key="index"
                      v-for="(m, index) in markers"
                      :position="m.position"
                      :clickable="true"
                      :draggable="true"
                      @click="center=m.position"
                    ></gmap-marker>

                  </gmap-map>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label>Satellite Coordinates</label>
                    <input readonly class="form-control-plaintext" :value="record.latitudeSatellite + ', ' + record.longitudeSatellite">
                  </div>
                  <div class="form-group">
                    <label>Illustration Coordinates</label>
                    <input readonly class="form-control-plaintext" :value="record.latitudeIllustration + ', ' + record.longitudeIllustration">
                  </div>
                  <template v-if="tempLongitudeSatellite != null && tempLatitudeSatellite != null">
                    <button class="btn btn-success" type="button" @click="setLocation(tempLatitudeSatellite, tempLongitudeSatellite)">Set location</button>
                    <button class="btn btn-default" type="button" @click="clearTempLocation">Clear</button>
                  </template>
                </div>
              </div>
            </fieldset>
            <!-- BUILDING FIELDS -->
            <template v-if="record.itemType == 'building'">
              <fieldset>
                <legend class="sr-only">{{ record.itemType | capitalize }} specific fields</legend>
                <div class="form-group">
                  <label>Building hours</label>
                  <textarea
                    class="form-control"
                    name="hours"
                    :class="{'is-invalid': errors.has('description'), 'form-control-plaintext': !userCanEdit || !isEditMode}"
                    :readonly="!userCanEdit || !isEditMode"
                    v-model="record.hours">
                  </textarea>
                  <div class="invalid-feedback">
                    {{ errors.first('hours') }}
                  </div>
                </div>
                <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
                  <li class="nav-item">
                    <a class="nav-link active" id="pills-bathrooms-tab" data-toggle="pill" href="#pills-bathrooms" role="tab" aria-controls="pills-bathrooms" aria-selected="true">Bathrooms <span class="badge badge-light">{{ record.bathrooms.length }}</span></a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" id="pills-emergency-tab" data-toggle="pill" href="#pills-emergency" role="tab" aria-controls="pills-emergency" aria-selected="false">Emergency Devices <span class="badge badge-light">{{ record.emergencyDevices.length }}</span></a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" id="pills-exhibits-tab" data-toggle="pill" href="#pills-exhibits" role="tab" aria-controls="pills-exhibits" aria-selected="false">Exhibits <span class="badge badge-light">{{ record.exhibits.length }}</span></a>
                  </li>
                </ul>
                <div class="tab-content" id="pills-tabContent">
                  <div class="tab-pane fade show active" id="pills-bathrooms" role="tabpanel" aria-labelledby="pills-bathrooms-tab">
                    <template v-if="userCanEdit && isEditMode">
                      <div v-for="(bathroom, index) in record.bathrooms">
                        <div class="form-row">
                          <div class="form-group col-md-6">
                            <label for="bathroomLocation" class="sr-only">Location *</label>
                            <input
                              v-validate="'required'"
                              data-vv-as="location"
                              :name="'bathroom-location-' + index"
                              :class="{'is-invalid': errors.has('bathroom-location-' + index), 'form-control-plaintext': !userCanEdit || !isEditMode}" :readonly="!userCanEdit || !isEditMode"
                              v-model="bathroom.name"
                              type="text"
                              class="form-control"
                              id="bathroomLocation"
                              placeholder="Location">
                              <div class="invalid-feedback">
                                {{ errors.first('bathroom-location-' + index) }}
                              </div>
                          </div>
                          <div class="form-group col-md-4">
                            <div class="form-check">
                              <input v-model="bathroom.isGenderNeutral" class="form-check-input" type="checkbox" id="bathroomGenderNeutral">
                              <label class="form-check-label" for="bathroomGenderNeutral">
                                Gender Neutral
                              </label>
                            </div>
                          </div>
                          <div class="form-group col-md-2">
                            <button type="button" @click="removeSubitemFromBuilding('bathroom', index)" class="close"><span aria-hidden="true">&times;</span></button>
                          </div>
                        </div>
                      </div>
                      <button @click="addRecordSubitem('bathroom')" type="button" class="btn btn-sm btn-default">Add Bathroom</button>
                    </template>
                    <template v-else>
                      <div v-if="record.bathrooms.length > 0">
                        <ul>
                          <li v-for="bathroom in record.bathrooms">{{ bathroom.name }} <span v-if="bathroom.isGenderNeutral">(Gender neutral)</span></li>
                        </ul>
                      </div>
                    </template>
                  </div>
                  <div class="tab-pane fade" id="pills-emergency" role="tabpanel" aria-labelledby="pills-emergency-tab">
                    <template v-if="userCanEdit && isEditMode">
                      <div v-for="(emergency, index) in record.emergencyDevices">
                        <div class="form-row">
                          <div class="form-group col-md-7">
                            <label for="emergencyLocation" class="sr-only">Location *</label>
                            <input
                              v-validate="'required'"
                              data-vv-as="location"
                              v-model="emergency.name"
                              type="text"
                              class="form-control"
                              id="emergencyLocation"
                              placeholder="Location"
                              :name="'emergency-location-' + index"
                              :class="{'is-invalid': errors.has('emergency-location-' + index) }">
                            <div class="invalid-feedback">
                              {{ errors.first('emergency-location-' + index) }}
                            </div>
                          </div>
                          <div class="form-group col-md-3">
                            <label for="emergencyType" class="sr-only">Type *</label>
                            <multiselect
                              v-validate="'required'"
                              data-vv-as="device type"
                              v-model="record.emergencyDevices[index].type"
                              :options="emergencyTypes"
                              :multiple="false"
                              placeholder="Choose type"
                              label="name"
                              track-by="id"
                              class="form-control"
                              style="padding:0"
                              :name="'emergency-type-' + index"
                              :class="{'is-invalid': errors.has('emergency-type-' + index) }"
                              >
                            </multiselect>
                            <div class="invalid-feedback">
                              {{ errors.first('emergency-type-' + index) }}
                            </div>
                          </div>
                          <div class="form-group col-md-2">
                            <a v-if="emergency.id" target="_blank" :href="'/map/items/' + emergency.id"><i class="fa fa-pencil"></i></a>
                            <button type="button" @click="removeSubitemFromBuilding('emergency device', index)" class="close"><span aria-hidden="true">&times;</span></button>
                          </div>
                        </div>
                      </div>
                      <button @click="addRecordSubitem('emergency device')" type="button" class="btn btn-sm btn-default">Add Device</button>
                    </template>
                    <template v-else>
                      <div v-if="record.emergencyDevices.length > 0">
                        <ul>
                          <li v-for="emergency in record.emergencyDevices">{{ emergency.name }} {{ emergency.type.name }}</li>
                        </ul>
                      </div>
                    </template>
                  </div>
                  <div class="tab-pane fade" id="pills-exhibits" role="tabpanel" aria-labelledby="pills-exhibits-tab">
                    <template v-if="userCanEdit && isEditMode">
                      <div v-for="(exhibit, index) in record.exhibits">
                        <div class="form-row">
                          <div class="form-group col-md-4">
                            <label for="exhibitName" class="sr-only">Title *</label>
                            <input
                              v-validate="'required'"
                              data-vv-as="title"
                              v-model="exhibit.name"
                              type="text"
                              class="form-control"
                              id="exhibitName"
                              placeholder="Title"
                              :name="'exhibit-name-' + index"
                              :class="{'is-invalid': errors.has('exhibit-name-' + index) }">
                            <div class="invalid-feedback">
                              {{ errors.first('exhibit-name-' + index) }}
                            </div>
                          </div>
                          <div class="form-group col-md-3">
                            <label for="exhibitDescription" class="sr-only">Description *</label>
                            <textarea
                              v-validate="'required'"
                              data-vv-as="description"
                              id="exhibitDescription"
                              v-model="exhibit.description"
                              class="form-control"
                              :name="'exhibit-description-' + index"
                              :class="{'is-invalid': errors.has('exhibit-description-' + index) }"
                              placeholder="Describe the exhibit here...">
                            </textarea>
                            <div class="invalid-feedback">
                              {{ errors.first('exhibit-description-' + index) }}
                            </div>
                          </div>
                          <div class="form-group col-md-3">
                            <label for="exhibitType" class="sr-only">Type *</label>
                            <multiselect
                              v-validate="'required'"
                              data-vv-as="exhibit type"
                              v-model="record.exhibits[index].type"
                              :options="exhibitTypes"
                              :multiple="false"
                              placeholder="Choose type"
                              label="name"
                              track-by="id"
                              class="form-control"
                              style="padding:0"
                              :name="'exhibit-type' + index"
                              :class="{'is-invalid': errors.has('exhibit-type' + index) }"
                              >
                            </multiselect>
                            <div class="invalid-feedback">
                              {{ errors.first('exhibit-type' + index) }}
                            </div>
                          </div>
                          <div class="form-group col-md-2">
                            <a v-if="exhibit.id" target="_blank" :href="'/map/items/' + exhibit.id"><i class="fa fa-pencil"></i></a>
                            <button type="button" @click="removeSubitemFromBuilding('exhibit', index)" class="close"><span aria-hidden="true">&times;</span></button>
                          </div>
                        </div>
                      </div>
                      <button @click="addRecordSubitem('exhibit')" type="button" class="btn btn-sm btn-default">Add Exhibit</button>
                    </template>
                    <template v-else>
                      <div v-if="record.exhibits.length > 0">
                        <ul>
                          <li v-for="exhibit in record.exhibits">{{ exhibit.name }} {{ exhibit.description }} {{ exhibit.type.name }}</li>
                        </ul>
                      </div>
                    </template>
                  </div><!-- end .tab-pane -->
                </div><!-- end .tablist -->
                <template v-if="userCanEdit && isEditMode">
                  <div class="form-row">
                    <div class="form-group col-md-12">
                      <label for="buildingTypes" class="sr-only">Building Type(s)</label>
                      <multiselect
                        v-validate="'required'"
                        data-vv-as="building type"
                        v-model="record.buildingType"
                        :options="buildingTypes"
                        :multiple="false"
                        placeholder="What kind of building is this?"
                        label="name"
                        track-by="id"
                        id="buildingType"
                        class="form-control"
                        style="padding:0"
                        name="buildingType"
                        :class="{'is-invalid': errors.has('buildingType') }"
                        >
                      </multiselect>
                      <div class="invalid-feedback">
                        {{ errors.first('buildingType') }}
                      </div>
                    </div>
                  </div>
                </template>
                <template v-else>
                  <h5>Building Type</h5>
                  <p v-if="record.buildingType">{{ record.buildingType.name }}</p>
                  <p v-else>None</p>
                </template>
              </fieldset>
            </template>
            <!-- EMERGENCY DEVICE FIELDS -->
            <template v-if="record.itemType == 'emergency device'">
              <fieldset>
                <legend>{{ record.itemType | capitalize }} specific fields</legend>
                <div v-if="userCanEdit && isEditMode" class="form-row">
                  <div class="form-group col-md-8">
                    <label for="building">Building (can be blank)</label>
                    <multiselect
                      v-model="record.building"
                      :options="buildings"
                      :multiple="false"
                      placeholder="Choose building"
                      label="name"
                      track-by="id"
                      id="building"
                      class="form-control"
                      style="padding:0"
                      name="building"
                      :class="{'is-invalid': errors.has('building') }"
                      >
                    </multiselect>
                    <div class="invalid-feedback">
                      {{ errors.first('building') }}
                    </div>
                  </div>
                  <div class="form-group col-md-4">
                    <label for="deviceType">Type *</label>
                    <multiselect
                      v-validate="'required'"
                      data-vv-as="device type"
                      v-model="record.type"
                      :options="emergencyTypes"
                      :multiple="false"
                      placeholder="Choose type"
                      label="name"
                      track-by="id"
                      id="deviceType"
                      class="form-control"
                      style="padding:0"
                      :name="'emergency-type'"
                      :class="{'is-invalid': errors.has('emergency-type') }"
                      >
                    </multiselect>
                    <div class="invalid-feedback">
                      {{ errors.first('emergency-type') }}
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
                <legend>{{ record.itemType | capitalize }} specific fields</legend>
                <div v-if="userCanEdit && isEditMode" class="form-row">
                  <div class="form-group col-md-8">
                    <label for="building">Building (can be blank)</label>
                    <multiselect
                      v-model="record.building"
                      :options="buildings"
                      :multiple="false"
                      placeholder="Choose building"
                      label="name"
                      track-by="id"
                      id="building"
                      class="form-control"
                      style="padding:0"
                      name="building"
                      :class="{'is-invalid': errors.has('building') }"
                      >
                    </multiselect>
                    <div class="invalid-feedback">
                      {{ errors.first('building') }}
                    </div>
                  </div>
                  <div class="form-group col-md-4">
                    <label for="exhibitType">Type *</label>
                    <multiselect
                      v-validate="'required'"
                      data-vv-as="device type"
                      v-model="record.type"
                      :options="exhibitTypes"
                      :multiple="false"
                      placeholder="Choose type"
                      label="name"
                      track-by="id"
                      id="exhibitType"
                      class="form-control"
                      style="padding:0"
                      :name="'exhibit-type'"
                      :class="{'is-invalid': errors.has('exhibit-type') }"
                      >
                    </multiselect>
                    <div class="invalid-feedback">
                      {{ errors.first('exhibit-type') }}
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
                <legend>{{ record.itemType | capitalize }} specific fields</legend>
                <div v-if="userCanEdit && isEditMode" class="form-row">
                  <div class="form-group col-md-4">
                    <label>Lot hours</label>
                    <textarea
                      class="form-control"
                      name="hours"
                      :class="{'is-invalid': errors.has('description'), 'form-control-plaintext': !userCanEdit || !isEditMode}"
                      :readonly="!userCanEdit || !isEditMode"
                      v-model="record.hours">
                    </textarea>
                    <div class="invalid-feedback">
                      {{ errors.first('hours') }}
                    </div>
                  </div>
                  <div class="form-group col-md-2">
                    <div class="form-group">
                      <label>Spaces</label>
                      <input
                        name="spaces"
                        type="number"
                        min="0"
                        step="1"
                        class="form-control"
                        :class="{ 'is-invalid': errors.has('spaces'), 'form-control-plaintext': !userCanEdit || !isEditMode }"
                        :readonly="!userCanEdit || !isEditMode"
                        v-model="record.spaces">
                      <div class="invalid-feedback">
                        {{ errors.first('spaces') }}
                      </div>
                    </div>
                  </div>
                  <div class="form-group col-md-6">
                    <label>Lot type(s)</label>
                    <multiselect
                      v-model="record.parkingTypes"
                      :options="parkingTypes"
                      :multiple="true"
                      placeholder="Choose lot types"
                      label="name"
                      track-by="id"
                      id="parkingTypes"
                      class="form-control"
                      style="padding:0"
                      name="parkingTypes"
                      :class="{'is-invalid': errors.has('parkingTypes') }"
                      >
                    </multiselect>
                    <div class="invalid-feedback">
                      {{ errors.first('parkingTypes') }}
                    </div>
                  </div>
                </div>
                <div v-else>
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
                </div>
              </fieldset>
            </template>
            <div v-if="this.$validator.errors.count() > 0" class="alert alert-danger fade show" role="alert">
              You have <strong>{{ this.$validator.errors.count() }} error<span v-if="this.$validator.errors.count() > 1">s</span></strong> in your submission:
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
              There was an error deleting this item.
            </div>
            <!-- ACTION BUTTONS -->
            <div v-if="userCanEdit && isEditMode">
              <button class="btn btn-success spacer-top" type="submit">{{ itemExists ? 'Update ' + record.itemType : 'Create ' + record.itemType }}</button>
              <button v-if="itemExists && this.permissions[0].delete" type="button" class="btn btn-danger spacer-top" data-toggle="modal" data-target="#deleteModal">Delete {{ record.itemType }}</button>
            </div>
          </form><!-- /end form -->
        </div><!-- end .tab-pane #information -->
        <!-- IMAGE TAB -->
        <div class="tab-pane fade" id="photos" role="tabpanel" aria-labelledby="photos-tab">
          <div class="row">
            <div class="col-xs-12 col-md-6">
              <h3>{{ record.itemType | capitalize }} Images ({{ record.images.length }})</h3>
              <!-- Image order change buttons -->
              <template v-if="isImageOrderChanged">
                <button class="btn btn-success" @click="updateImageOrder()">Confirm</button>
                <button class="btn btn-default" @click="resetImageOrder()">Reset</button>
              </template>
              <div v-if="isImageOrderUpdated" class="alert alert-success fade show" role="alert">
                Image order has been updated
              </div>
              <div v-if="itemExists && userCanEdit && isEditMode">
                <!--<draggable v-model="record.images" :options="{}" @start="drag=true" @end="onDragEnd">-->
                <draggable v-model="record.images" @start="drag=true" @end="drag=false">
                  <image-thumbnail-pod
                          v-for="(image, index) in record.images"
                          :key="index"
                          :image="image"
                          v-model="record.images"
                          @imageDeleteRequested="spliceDeletedImage"
                  >
                  </image-thumbnail-pod>
                </draggable>
              </div>
            </div><!-- end .col-md-6 (images)-->
            <div class="col-xs-12 col-md-6">
              <h5>Primary Photo</h5>
              <template v-if="record.images.length > 0">
                <img id="primary-image" :src="record.images[0].subdir + '/' + record.images[0].path" :alt="record.images[0].name" />
                <p><a :href="record.images[0].subdir + '/' + record.images[0].path" target="_blank">Full Size</a></p>
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
                    <p><strong>Uploaded {{ uploadedFiles.length }} file(s).</strong> <a class="btn btn-default" href="javascript:void(0)" @click="resetUploadForm()">Upload more images</a></p>
                  </div>
                  <div v-else class="alert alert-warning" role="alert">
                    <p><strong>Uploaded {{ uploadedFiles.length }} file(s) with the following errors.</strong> <a class="btn btn-default" href="javascript:void(0)" @click="resetUploadForm()">Upload more images</a></p>
                    <ul>
                      <li v-for="uploadError in uploadErrors">{{ uploadError }}</li>
                    </ul>
                  </div>
                </div>
                <!--FAILED-->
                <div v-if="isUploadFailed" class="alert alert-danger" role="alert">
                  <p><strong>Upload failed.</strong> <a href="javascript:void(0)" @click="resetUploadForm()">Try again</a></p>
                  <pre v-for="uploadError in uploadErrors">{{ uploadErrors }}</pre>
                </div>
                <!-- UPLOAD FORM -->
                <form enctype="multipart/form-data" novalidate v-if="isUploadInitial || isUploadSaving">
                  <fieldset>
                    <legend>Upload Images</legend>
                    <div class="dropbox">
                      <input type="file" multiple name="uploadFiles[]" :disabled="isUploadSaving" @change="filesChange($event.target.name, $event.target.files); fileCount = $event.target.files.length" accept="image/*" class="input-file">
                        <p v-if="isUploadInitial">Drag your image(s) here to begin<br> or click to browse.</p>
                        <p v-if="isUploadSaving">Uploading {{ fileCount }} files...</p>
                    </div>
                  </fieldset>
                </form>
              </template>
              <template v-else>
                <div class="alert alert-info" role="alert">
                  <p v-if="!itemExists">You will be able to upload images once you create the {{ record.itemType }}</p>
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
  #primary-image{
    max-width:100%;
  }
  .list-group-item, .list-group-item:hover{
    z-index: auto;
  }
</style>
<style src="vue-multiselect/dist/vue-multiselect.min.css"></style>
<script>
  import MapitemDeleteModal from './MapitemDeleteModal.vue'
  import MapitemImageDeleteModal from './MapitemImageDeleteModal.vue'
  import MapitemImageEditModal from './MapitemImageEditModal.vue'
  import ImageThumbnailPod from './ImageThumbnailPod.vue'
  import Heading from '../utils/Heading.vue'
  import Multiselect from 'vue-multiselect'
  import NotFound from '../utils/NotFound.vue'
  import Draggable from 'vuedraggable'

  const STATUS_INITIAL = 0, STATUS_SAVING = 1, STATUS_SUCCESS = 2, STATUS_FAILED = 3;

  export default {
    created() {},
    mounted() {
      this.resetUploadForm()
      // detect if the form should be in edit mode from the start (default is false)
      if(this.startMode == 'edit'){
        this.isEditMode = true
      }
      if(this.itemExists === false){
        // Set the kind of map item being created via the itemType property from NewMapItemChoices
        this.isDataLoaded = true
        this.record.itemType = this.itemType
      } else {
        // fetch the existing record using the prop itemId
        this.fetchMapItem(this.itemId)
      }
      if(this.itemType == 'building'){
        this.fetchBuildingTypes()
      }
      if(this.itemType == 'building' || this.itemType == 'emergency device'){
        this.fetchEmergencyTypes()
      }
      if(this.itemType == 'building' || this.itemType == 'exhibit'){
        this.fetchExhibitTypes()
      }
      if(this.itemType == 'emergency device' || this.itemType == 'exhibit'){
        this.fetchBuildings()
      }
      if(this.itemType == 'parking'){
        this.fetchParkingTypes()
      }
    },
    components: {Heading, Multiselect, MapitemDeleteModal, MapitemImageDeleteModal, MapitemImageEditModal, ImageThumbnailPod, NotFound, Draggable},
    props:{
      itemType: {
        type: String,
        required: true // not required of existing items because type is already known
      },
      itemExists: {
        type: Boolean,
        required: true
      },
      itemId:{
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
      }
    },
    data: function() {
      return {
        /***** GOOGLE MAPS DOODLING *****/
        plPath: [
          {lat: 25.774, lng: -80.190},
          {lat: 18.466, lng: -66.118},
          {lat: 32.321, lng: -64.757},
          {lat: 25.774, lng: -80.190}
        ],
        mapType: 'terrain',
        /**** end GOOGLE MAPS DOODLING *****/
        apiError: {
          message: null,
          status: null
        },
        buildings: [], // for multiselect
        buildingTypes: [], // for multiselect
        center: {lat: 42.24782481187385, lng: -83.62301669499783}, // center coordinates for google map
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
        markers: [], // google map markers
        originalImageOrder: [], // when order of images is being re-arranged, put the initial images, in order, here
        parkingTypes: [], // for multiselect
        record: {
          id: '',
          bathrooms: [],
          building: null,
          description: '',
          emergencyDevices: [],
          exhibits: [],
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
          slug: '',
          spaces: 0,
          tags:[],
        },
        recordSlug: '',
        success: false,
        successMessage: '',
        tempLatitudeSatellite: null,
        tempLongitudeSatellite: null,
        uploadedFiles: [],
        uploadErrors: [],
      }
    },
    computed: {
      // are there any validation errors?
      haveErrors: function(){
        return this.$validator.errors.count() > 0 ? true : false
      },
      headingIcon: function() {
        switch(this.record.itemType){
          case 'building':
            return '<i class="fa fa-building"></i>'
          case 'bathroom':
            return '<i class="fa fa-male"></i><i class="fa fa-female"></i>'
          default:
            return '<i class="fa fa-map"></i>'
        }
      },
      imageDeleted: function(){
          return this.isImageDeleted ? 'image-deleted-border' : ''
      },
      isInvalid: function(){
        return 'is-invalid'
      },
      // PHOTOS
      isUploadInitial() {
        return this.currentStatus === STATUS_INITIAL;
      },
      isUploadSaving() {
        return this.currentStatus === STATUS_SAVING;
      },
      isUploadSuccess() {
        return this.currentStatus === STATUS_SUCCESS;
      },
      isUploadFailed() {
        return this.currentStatus === STATUS_FAILED;
      },
      // -end PHOTOS
      lockIcon: function(){
        return this.isEditMode ? '<i class="fa fa-unlock"></i>' : '<i class="fa fa-lock"></i>'
      },
      userCanEdit: function(){
        // An existing record can be edited by a user with edit permissions, a new record can be created by a user with create permissions
        return this.itemExists && this.permissions[0].edit || !this.itemExists && this.permissions[0].create ? true : false
      }
    },
    methods: {
      // e.g. add a new bathroom to a building
      addRecordSubitem: function(itemType){
        switch(itemType){
          case 'bathroom':
            this.record.bathrooms.push({
              name: '',
              itemType: 'bathroom',
              isGenderNeutral: false
            })
            break;
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
        }
      },
      afterSubmitSucceeds: function(){
        let self = this
        // New item has been submitted, go to edit
        if(!this.itemExists){
          this.success = true
          this.successMessage = "Item created."
          let newurl = '/map/items/' + this.record.id
          document.location = newurl
        } else {
          this.success = true
          this.successMessage = "Update successful."
        }
        // remove the message after 3 seconds
        setTimeout(function(){
            self.success = false
        }, 3000)
      },
      // Run prior to submitting
      checkForm: function(){
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
      clearMarkers: function(){
        this.markers = []
      },
      clearTempLocation: function(){
        this.tempLatitudeSatellite = null
        this.tempLongitudeSatellite = null
      },
      fetchBuildings(){
        let self = this
        axios.get('/api/mapbuildings')
        // success
        .then(function (response) {
          self.buildings = response.data
        })
        // fail
        .catch(function (error) {
          self.apiError.status = error.response.status
          switch(error.response.status){
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
      fetchBuildingTypes(){
        let self = this
        axios.get('/api/mapbuildingtypes')
        // success
        .then(function (response) {
          self.buildingTypes = response.data
        })
        // fail
        .catch(function (error) {
          switch(error.response.status){
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
      fetchEmergencyTypes(){
        let self = this
        axios.get('/api/mapemergencytypes')
        // success
        .then(function (response) {
          self.emergencyTypes = response.data
        })
        // fail
        .catch(function (error) {
          switch(error.response.status){
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
      fetchExhibitTypes(){
        let self = this
        axios.get('/api/mapexhibittypes')
        // success
        .then(function (response) {
          self.exhibitTypes = response.data
        })
        // fail
        .catch(function (error) {
          switch(error.response.status){
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
      fetchMapItem(itemId){
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
          if(error.request.status == 404){
            self.is404 = true
            self.isDataLoaded = true
          }
        })
      },
      fetchParkingTypes(){
        let self = this
        axios.get('/api/mapparkingtypes')
        // success
        .then(function (response) {
          self.parkingTypes = response.data
        })
        // fail
        .catch(function (error) {
          switch(error.response.status){
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
      filesChange(fieldName, fileList) {
        // handle file changes
        const formData = new FormData()
        if (!fileList.length) return
        // append the files to FormData
        Array
          .from(Array(fileList.length).keys())
          .map(x => {
            formData.append(fieldName, fileList[x], fileList[x].name)
          });
        // save it
        this.uploadImages(formData);
      },
      // Error checking for emergency devices
      isEmergencyError: function(index){
        let key = "emergencyDeviceIndex" + index
        // there was an error found for this key
        if(this.errors[key]){
          return true
        }
        return false
      },
      // Called from the @itemDeleted event emission from the Delete Modal
      markItemDeleted: function () {
        this.isDeleteError = false
        this.isDeleted = true
      },
      markItemDeleteError: function(){
        let self = this
        this.isDeleted = false
        this.isDeleteError = true
        setTimeout(function(){
            self.isDeleteError = false
        }, 5000)
      },
      // When a user has finished dragging (re-ordering) an image
      onDragEnd: function(evt){
        this.isImageOrderChanged = true
      },
      // use the map item's array index
      removeSubitemFromBuilding(type, index){
        switch(type){
          case 'bathroom':
            this.record.bathrooms.splice(index, 1)
            break
          case 'emergency device':
            this.record.emergencyDevices.splice(index, 1)
            break
          case 'exhibit':
            this.record.exhibits.splice(index, 1)
            break
        }
      },
      resetImageOrder: function(){
          this.record.images = this.originalImageOrder
          this.isImageOrderChanged = false
      },
      resetUploadForm: function() {
          // reset form to initial state
          this.currentStatus = STATUS_INITIAL
          this.uploadedFiles = [];
          this.uploadErrors = [];
      },
      setLocation: function(lat, lng){
        this.clearMarkers() // get rid of existing markers
        this.record.latitudeSatellite = lat
        this.record.longitudeSatellite = lng
        // make a new marker for these coordinates
        this.markers.push({
          position: {
            lat: lat,
            lng: lng,
          }
        })
        this.clearTempLocation()
      },
      // JSON.stringify the original image order in case a user wants to reset the drag and drop order
      setOriginalImages: function(imageArr){
        this.originalImageOrder = JSON.parse(JSON.stringify(imageArr))
      },
      setTempPosition: function(e){
        this.tempLatitudeSatellite = e.latLng.lat()
        this.tempLongitudeSatellite = e.latLng.lng()
      },
      // Called as a result of $emit from child component
      spliceDeletedImage: function(image){
        this.record.images.splice(this.record.images.indexOf(image) - 1, 1) // splice the deleted item from the record
        this.setOriginalImages(this.record.images)
      },
      // Submit the form via the API
      submitForm: function(){
        let self = this // 'this' loses scope within axios
        let method = (this.itemExists) ? 'put' : 'post'
        let route =  (this.itemExists) ? '/api/mapitem' : '/api/mapitems'
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
            errors.forEach(function(error){
              let key = error.property_path
              let message = error.message
              self.$validator.errors.add(key, message)
            })
          })
      },
      updateImageOrder: function(){
        let self = this
        // Get current images in order
        let imageIdsObj = {
          imageIds: []
        }
        this.record.images.forEach(function(image){
          imageIdsObj.imageIds.push(image.id)
        })
        axios.put('/api/mapitemimage/reorder', imageIdsObj)
          // success
          .then(function (response) {
            self.isImageOrderChanged = false
            // flash the success message for three seconds
            self.isImageOrderUpdated = true
            setTimeout(function(){
                self.isImageOrderUpdated = false
            }, 3000)
            self.setOriginalImages(self.record.images) // make these the new 'original' images, reflecting the proper order
          })
          // fail
          .catch(function (error) {
            switch(error.response.status){
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
      uploadImages: function(formData){
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
            response.data.processedImages.forEach(function(image){
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
            switch(error.response.status){
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
      toggleEdit: function(){
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
