<template>
  <div>
    <heading>
      <!--            <span slot="icon" v-html="headingIcon"></span>-->
      <span>Multimedia Requests</span>
    </heading>
    <div v-if="apiError.status" class="alert alert-danger fade show" role="alert">
      {{ apiError.message }}
    </div>
    <div id="accordion">
      <div class="card mb-4">
        <div class="card-header" id="headingHeadshotRequests">
          <h5 class="mb-0">
            <button class="btn btn-link" data-toggle="collapse" data-target="#collapseHeadshotRequests"
                    aria-expanded="true" aria-controls="collapseHeadshotRequests">
              Headshot Requests
              <span v-if="!loadingHeadshotRequests" class="badge badge-primary">{{ headshotRequests.length }}</span>
              <span v-else><i class="fa fa-spinner"></i></span>
            </button>
          </h5>
        </div>
        <div id="collapseHeadshotRequests" class="collapse show" aria-labelledby="headingHeadshotRequests"
             data-parent="#accordion">
          <div class="card-body">
            <div v-if="!loadingHeadshotRequests" class="table-responsive">
              <!-- FILTER BY STATUS -->
              <div class="row">
                <div class="col-xs-12">
                  <span class="pl-4 pr-2">Filters</span>
                  <div class="btn-group" data-toggle="buttons">
                    <label v-for="status in requestStatuses" class="btn btn-primary"
                           :class="{ active : headshotRequestStatuses.includes(status.statusSlug) }">
                      <input type="checkbox" autocomplete="off"
                             @click="toggleFilter(headshotRequestStatuses, status.statusSlug)"
                             :checked="headshotRequestStatuses.includes(status.statusSlug)"/>{{
                        status.status
                      }}
                    </label>
                  </div>
                </div>
              </div>
              <!-- RESULTS TABLE -->
              <table class="table table-hover table-sm">
                <thead>
                <tr>
                  <th scope="col">
                    <span class="col-sortable" @click="sortBy(sortHeadshotsRequests, 'created')">Submission Date</span>
                    <i v-if="sortHeadshotsRequests.sortKey == 'created' && sortHeadshotsRequests.reverse == false"
                       class="fa fa-sort-up"></i>
                    <i v-if="sortHeadshotsRequests.sortKey == 'created' && sortHeadshotsRequests.reverse == true"
                       class="fa fa-sort-down"></i>
                  </th>
                  <th scope="col">
                                        <span class="col-sortable"
                                              @click="sortBy(sortHeadshotsRequests, 'status.statusSlug')">Status</span>
                    <i v-if="sortHeadshotsRequests.sortKey == 'status.statusSlug' && sortHeadshotsRequests.reverse == false"
                       class="fa fa-sort-up"></i>
                    <i v-if="sortHeadshotsRequests.sortKey == 'status.statusSlug' && sortHeadshotsRequests.reverse == true"
                       class="fa fa-sort-down"></i>
                  </th>
                  <th scope="col">
                                        <span class="col-sortable"
                                              @click="sortBy(sortHeadshotsRequests, 'lastName')">Client</span>
                    <i v-if="sortHeadshotsRequests.sortKey == 'lastName' && sortHeadshotsRequests.reverse == false"
                       class="fa fa-sort-up"></i>
                    <i v-if="sortHeadshotsRequests.sortKey == 'lastName' && sortHeadshotsRequests.reverse == true"
                       class="fa fa-sort-down"></i>
                  </th>
                  <th scope="col">
                                        <span class="col-sortable"
                                              @click="sortBy(sortHeadshotsRequests, 'timeSlot.dateOfShoot')">Time Slot</span>
                    <i v-if="sortHeadshotsRequests.sortKey == 'timeSlot.dateOfShoot' && sortHeadshotsRequests.reverse == false"
                       class="fa fa-sort-up"></i>
                    <i v-if="sortHeadshotsRequests.sortKey == 'timeSlot.dateOfShoot' && sortHeadshotsRequests.reverse == true"
                       class="fa fa-sort-down"></i>
                  </th>
                  <th scope="col">
                                        <span class="col-sortable"
                                              @click="sortBy(sortHeadshotsRequests, 'assignee.lastName')">Assignee</span>
                    <i v-if="sortHeadshotsRequests.sortKey == 'assignee.lastName' && sortHeadshotsRequests.reverse == false"
                       class="fa fa-sort-up"></i>
                    <i v-if="sortHeadshotsRequests.sortKey == 'assignee.lastName' && sortHeadshotsRequests.reverse == true"
                       class="fa fa-sort-down"></i>
                  </th>
                  <th scope="col">Actions</th>
                </tr>
                </thead>
                <tbody>
                <template v-for="item in orderedHeadshotRequests">
                  <!-- only show items that meet the current filter restrictions -->
                  <tr v-if="headshotRequestStatuses.includes(item.status.statusSlug)">
                    <td scope="row">{{ dateOnlyFormat(item.created) }}</td>
                    <td>{{ item.status.status }}</td>
                    <td>{{ item.firstName }} {{ item.lastName }}</td>
                    <td>{{ dateOnlyFormat(item.timeSlot.dateOfShoot) }} {{
                        timeFormat(item.timeSlot.startTime)
                      }} - {{ timeFormat(item.timeSlot.endTime) }}
                    </td>
                    <td>{{
                        item.assignee ? item.assignee.firstName + ' ' + item.assignee.lastName :
                            'unassigned'
                      }}
                    </td>
                    <td><a :href="'/multimediarequests/' + item.id" v-if="userCanEdit"><font-awesome-icon icon="fa-solid fa-pen-to-square" /></a></td>
                  </tr>
                </template>
                </tbody>
              </table>
            </div>
            <div v-else>
              <p style="text-align: center"><img src="/images/loading.gif" alt="Loading..."/></p>
            </div>
            <paginator v-show="!loadingHeadshotRequests" :items="headshotRequests"
                       @itemsPerPageChanged="setPaginatedHeadshotRequests"></paginator>
          </div>
        </div>
      </div>
      <div class="card mb-4">
        <div class="card-header" id="headingPhotoRequests">
          <h5 class="mb-0">
            <button class="btn btn-link" data-toggle="collapse" data-target="#collapsePhotoRequests"
                    aria-expanded="true" aria-controls="collapsePhotoRequests">
              Photo Shoot Requests
              <span v-if="!loadingPhotoshootRequests" class="badge badge-primary">{{ photoshootRequests.length }}</span>
              <span v-else><i class="fa fa-spinner"></i></span>
            </button>
          </h5>
        </div>
        <div id="collapsePhotoRequests" class="collapse show" aria-labelledby="headingPhotoRequests"
             data-parent="#accordion">
          <div class="card-body">
            <div v-if="!loadingPhotoshootRequests" class="table-responsive">
              <!-- FILTER BY STATUS -->
              <div class="row">
                <div class="col-xs-12">
                  <span class="pl-4 pr-2">Filters</span>
                  <div class="btn-group" data-toggle="buttons">
                    <label v-for="status in requestStatuses" class="btn btn-primary"
                           :class="{ active : photoshootRequestStatuses.includes(status.statusSlug) }">
                      <input type="checkbox" autocomplete="off"
                             :checked="photoshootRequestStatuses.includes(status.statusSlug)"
                             @click="toggleFilter(photoshootRequestStatuses, status.statusSlug)"
                      />{{
                        status.status
                      }}
                    </label>
                  </div>
                </div>
              </div>
              <!-- RESULTS TABLE -->
              <table class="table table-hover table-sm">
                <thead>
                <tr>
                  <th scope="col">
                    <span class="col-sortable" @click="sortBy(sortPhotoshootRequests, 'created')">Submission Date</span>
                    <i v-if="sortPhotoshootRequests.sortKey == 'created' && sortPhotoshootRequests.reverse == false"
                       class="fa fa-sort-up"></i>
                    <i v-if="sortPhotoshootRequests.sortKey == 'created' && sortPhotoshootRequests.reverse == true"
                       class="fa fa-sort-down"></i>
                  </th>
                  <th scope="col">
                                        <span class="col-sortable"
                                              @click="sortBy(sortPhotoshootRequests, 'status.statusSlug')">Status</span>
                    <i v-if="sortPhotoshootRequests.sortKey == 'status.statusSlug' && sortPhotoshootRequests.reverse == false"
                       class="fa fa-sort-up"></i>
                    <i v-if="sortPhotoshootRequests.sortKey == 'status.statusSlug' && sortPhotoshootRequests.reverse == true"
                       class="fa fa-sort-down"></i>
                  </th>
                  <th scope="col">
                                        <span class="col-sortable"
                                              @click="sortBy(sortPhotoshootRequests, 'lastName')">Client</span>
                    <i v-if="sortPhotoshootRequests.sortKey == 'lastName' && sortPhotoshootRequests.reverse == false"
                       class="fa fa-sort-up"></i>
                    <i v-if="sortPhotoshootRequests.sortKey == 'lastName' && sortPhotoshootRequests.reverse == true"
                       class="fa fa-sort-down"></i>
                  </th>
                  <th scope="col">
                                        <span class="col-sortable"
                                              @click="sortBy(sortPhotoshootRequests, 'startTime')">Date of Shoot</span>
                    <i v-if="sortPhotoshootRequests.sortKey == 'startTime' && sortPhotoshootRequests.reverse == false"
                       class="fa fa-sort-up"></i>
                    <i v-if="sortPhotoshootRequests.sortKey == 'startTime' && sortPhotoshootRequests.reverse == true"
                       class="fa fa-sort-down"></i>
                  </th>
                  <th scope="col">
                                        <span class="col-sortable"
                                              @click="sortBy(sortPhotoshootRequests, 'assignee.lastName')">Assignee</span>
                    <i v-if="sortPhotoshootRequests.sortKey == 'assignee.lastName' && sortPhotoshootRequests.reverse == false"
                       class="fa fa-sort-up"></i>
                    <i v-if="sortPhotoshootRequests.sortKey == 'assignee.lastName' && sortPhotoshootRequests.reverse == true"
                       class="fa fa-sort-down"></i>
                  </th>
                  <th scope="col">Actions</th>
                </tr>
                </thead>
                <tbody>
                <template v-for="item in orderedPhotoshootRequests">
                  <!-- only show items that meet the current filter restrictions -->
                  <tr v-if="photoshootRequestStatuses.includes(item.status.statusSlug)">
                    <td scope="row">{{ dateOnlyFormat(item.created) }}</td>
                    <td>{{ item.status.status }}</td>
                    <td>{{ item.firstName }} {{ item.lastName }}</td>
                    <td>{{ dateOnlyFormat(item.startTime) }}</td>
                    <td>{{
                        item.assignee ? item.assignee.firstName + ' ' + item.assignee.lastName :
                            'unassigned'
                      }}
                    </td>
                    <td><a :href="'/multimediarequests/' + item.id" v-if="userCanEdit"><font-awesome-icon icon="fa-solid fa-pen-to-square" /></a></td>
                  </tr>
                </template>
                </tbody>
              </table>
            </div>
            <div v-else>
              <p style="text-align: center"><img src="/images/loading.gif" alt="Loading..."/></p>
            </div>
            <paginator v-show="!loadingPhotoshootRequests" :items="photoshootRequests"
                       @itemsPerPageChanged="setPaginatedPhotoshootRequests"></paginator>
          </div>
        </div>
      </div>
      <div class="card mb-4">
        <div class="card-header" id="headingVideoRequests">
          <h5 class="mb-0">
            <button class="btn btn-link" data-toggle="collapse" data-target="#collapseVideoRequests"
                    aria-expanded="true" aria-controls="collapseVideoRequests">
              Video Requests
              <span v-if="!loadingVideoRequests" class="badge badge-primary">{{ videoshootRequests.length }}</span>
              <span v-else><i class="fa fa-spinner"></i></span>
            </button>
          </h5>
        </div>
        <div id="collapseVideoRequests" class="collapse show" aria-labelledby="headingVideoRequests"
             data-parent="#accordion">
          <div class="card-body">
            <div v-if="!loadingVideoRequests" class="table-responsive">
              <!-- FILTER BY STATUS -->
              <div class="row">
                <div class="col-xs-12">
                  <span class="pl-4 pr-2">Filters</span>
                  <div class="btn-group" data-toggle="buttons">
                    <label v-for="status in requestStatuses" class="btn btn-primary"
                           :class="{ active : videoRequestStatuses.includes(status.statusSlug) }">
                      <input type="checkbox" autocomplete="off"
                             :checked="videoRequestStatuses.includes(status.statusSlug)"
                             @click="toggleFilter(videoRequestStatuses, status.statusSlug)"
                      />{{
                        status.status
                      }}
                    </label>
                  </div>
                </div>
              </div>
              <!-- RESULTS TABLE -->
              <table class="table table-hover table-sm">
                <thead>
                <tr>
                  <th scope="col">
                    <span class="col-sortable" @click="sortBy(sortVideoRequests, 'created')">Submission Date</span>
                    <i v-if="sortVideoRequests.sortKey == 'created' && sortVideoRequests.reverse == false"
                       class="fa fa-sort-up"></i>
                    <i v-if="sortVideoRequests.sortKey == 'created' && sortVideoRequests.reverse == true"
                       class="fa fa-sort-down"></i>
                  </th>
                  <th scope="col">
                                        <span class="col-sortable"
                                              @click="sortBy(sortVideoRequests, 'status.statusSlug')">Status</span>
                    <i v-if="sortVideoRequests.sortKey == 'status.statusSlug' && sortVideoRequests.reverse == false"
                       class="fa fa-sort-up"></i>
                    <i v-if="sortVideoRequests.sortKey == 'status.statusSlug' && sortVideoRequests.reverse == true"
                       class="fa fa-sort-down"></i>
                  </th>
                  <th scope="col">
                                        <span class="col-sortable"
                                              @click="sortBy(sortVideoRequests, 'lastName')">Client</span>
                    <i v-if="sortVideoRequests.sortKey == 'lastName' && sortVideoRequests.reverse == false"
                       class="fa fa-sort-up"></i>
                    <i v-if="sortVideoRequests.sortKey == 'lastName' && sortVideoRequests.reverse == true"
                       class="fa fa-sort-down"></i>
                  </th>
                  <th scope="col">
                    <span class="col-sortable" @click="sortBy(sortVideoRequests, 'completionDate')">Due Date</span>
                    <i v-if="sortVideoRequests.sortKey == 'completionDate' && sortVideoRequests.reverse == false"
                       class="fa fa-sort-up"></i>
                    <i v-if="sortVideoRequests.sortKey == 'completionDate' && sortVideoRequests.reverse == true"
                       class="fa fa-sort-down"></i>
                  </th>
                  <th scope="col">
                                        <span class="col-sortable"
                                              @click="sortBy(sortVideoRequests, 'assignee.lastName')">Assignee</span>
                    <i v-if="sortVideoRequests.sortKey == 'assignee.lastName' && sortVideoRequests.reverse == false"
                       class="fa fa-sort-up"></i>
                    <i v-if="sortVideoRequests.sortKey == 'assignee.lastName' && sortVideoRequests.reverse == true"
                       class="fa fa-sort-down"></i>
                  </th>
                  <th scope="col">Actions</th>
                </tr>
                </thead>
                <tbody>
                <template v-for="item in orderedVideoshootRequests">
                  <!-- only show items that meet the current filter restrictions -->
                  <tr v-if="videoRequestStatuses.includes(item.status.statusSlug)">
                    <td scope="row">{{ dateOnlyFormat(item.created) }}</td>
                    <td>{{ item.status.status }}</td>
                    <td>{{ item.firstName }} {{ item.lastName }}</td>
                    <td>{{ dateOnlyFormat(item.completionDate) }}</td>
                    <td>{{
                        item.assignee ? item.assignee.firstName + ' ' + item.assignee.lastName :
                            'unassigned'
                      }}
                    </td>
                    <td><a :href="'/multimediarequests/' + item.id" v-if="userCanEdit"><font-awesome-icon icon="fa-solid fa-pen-to-square" /></a></td>
                  </tr>
                </template>
                </tbody>
              </table>
            </div>
            <div v-else>
              <p style="text-align: center"><img src="/images/loading.gif" alt="Loading..."/></p>
            </div>
            <paginator v-show="!loadingVideoRequests" :items="videoshootRequests"
                       @itemsPerPageChanged="setPaginatedVideoRequests"></paginator>
          </div>
        </div>
      </div>
      <div class="card mb-4">
        <div class="card-header" id="headingPublicationRequests">
          <h5 class="mb-0">
            <button class="btn btn-link" data-toggle="collapse" data-target="#collapsePublicationRequests"
                    aria-expanded="true" aria-controls="collapsePublicationRequests">
              Marketing Materials Requests
              <span v-if="!loadingPublicationRequests" class="badge badge-primary">{{
                  publicationRequests.length
                }}</span>
              <span v-else><i class="fa fa-spinner"></i></span>
            </button>
          </h5>
        </div>
        <div id="collapsePublicationRequests" class="collapse show" aria-labelledby="headingPublicationRequests"
             data-parent="#accordion">
          <div class="card-body">
            <div v-if="!loadingPublicationRequests" class="table-responsive">
              <!-- FILTER BY STATUS -->
              <div class="row">
                <div class="col-xs-12">
                  <span class="pl-4 pr-2">Filters</span>
                  <div class="btn-group" data-toggle="buttons">
                    <label v-for="status in requestStatuses" class="btn btn-primary"
                           :class="{ active : publicationRequestStatuses.includes(status.statusSlug) }">
                      <input type="checkbox" autocomplete="off"
                             :checked="publicationRequestStatuses.includes(status.statusSlug)"
                             @click="toggleFilter(publicationRequestStatuses, status.statusSlug)"
                      />{{
                        status.status
                      }}
                    </label>
                  </div>
                </div>
              </div>
              <!-- RESULTS TABLE -->
              <table class="table table-hover table-sm">
                <thead>
                <tr>
                  <th scope="col">
                    <span class="col-sortable"
                          @click="sortBy(sortPublicationRequests, 'created')">Submission Date</span>
                    <i v-if="sortPublicationRequests.sortKey == 'created' && sortPublicationRequests.reverse == false"
                       class="fa fa-sort-up"></i>
                    <i v-if="sortPublicationRequests.sortKey == 'created' && sortPublicationRequests.reverse == true"
                       class="fa fa-sort-down"></i>
                  </th>
                  <th scope="col">
                                        <span class="col-sortable"
                                              @click="sortBy(sortPublicationRequests, 'status.statusSlug')">Status</span>
                    <i v-if="sortPublicationRequests.sortKey == 'status.statusSlug' && sortPublicationRequests.reverse == false"
                       class="fa fa-sort-up"></i>
                    <i v-if="sortPublicationRequests.sortKey == 'status.statusSlug' && sortPublicationRequests.reverse == true"
                       class="fa fa-sort-down"></i>
                  </th>
                  <th scope="col">
                                        <span class="col-sortable"
                                              @click="sortBy(sortPublicationRequests, 'lastName')">Client</span>
                    <i v-if="sortPublicationRequests.sortKey == 'lastName' && sortPublicationRequests.reverse == false"
                       class="fa fa-sort-up"></i>
                    <i v-if="sortPublicationRequests.sortKey == 'lastName' && sortPublicationRequests.reverse == true"
                       class="fa fa-sort-down"></i>
                  </th>
                  <th scope="col">
                                        <span class="col-sortable"
                                              @click="sortBy(sortPublicationRequests, 'completionDate')">Due Date</span>
                    <i v-if="sortPublicationRequests.sortKey == 'completionDate' && sortPublicationRequests.reverse == false"
                       class="fa fa-sort-up"></i>
                    <i v-if="sortPublicationRequests.sortKey == 'completionDate' && sortPublicationRequests.reverse == true"
                       class="fa fa-sort-down"></i>
                  </th>
                  <th scope="col">
                                        <span class="col-sortable"
                                              @click="sortBy(sortPublicationRequests, 'assignee.lastName')">Assignee</span>
                    <i v-if="sortPublicationRequests.sortKey == 'assignee.lastName' && sortPublicationRequests.reverse == false"
                       class="fa fa-sort-up"></i>
                    <i v-if="sortPublicationRequests.sortKey == 'assignee.lastName' && sortPublicationRequests.reverse == true"
                       class="fa fa-sort-down"></i>
                  </th>
                  <th scope="col">Actions</th>
                </tr>
                </thead>
                <tbody>
                <template v-for="item in orderedPublicationRequests">
                  <!-- only show items that meet the current filter restrictions -->
                  <tr v-if="publicationRequestStatuses.includes(item.status.statusSlug)">
                    <td scope="row">{{ dateOnlyFormat(item.created) }}</td>
                    <td>{{ item.status.status }}</td>
                    <td>{{ item.firstName }} {{ item.lastName }}</td>
                    <td>{{ dateOnlyFormat(item.completionDate) }}</td>
                    <td>{{
                        item.assignee ? item.assignee.firstName + ' ' + item.assignee.lastName :
                            'unassigned'
                      }}
                    </td>
                    <td><a :href="'/multimediarequests/' + item.id" v-if="userCanEdit"><font-awesome-icon icon="fa-solid fa-pen-to-square" /></a></td>
                  </tr>
                </template>
                </tbody>
              </table>
            </div>
            <div v-else>
              <p style="text-align: center"><img src="/images/loading.gif" alt="Loading..."/></p>
            </div>
            <paginator v-show="!loadingPublicationRequests" :items="publicationRequests"
                       @itemsPerPageChanged="setPaginatedPublicationRequests"></paginator>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<style scoped>
input[type='checkbox'] {
  visibility: hidden;
}
</style>
<script>
import Heading from '../utils/Heading.vue'
import Paginator from '../utils/Paginator.vue'
import moment from 'moment'

export default {
  created() {
  },
  mounted() {
    this.fetchStatusOptions()
    this.fetchMultimediaRequests()
  },
  components: {Heading, Paginator},
  props: {
    permissions: {
      type: Array,
      required: true
    },
  },
  data: function () {
    return {
      apiError: {
        message: null,
        status: null
      },
      // Loading indicators for each request type
      loadingPublicationRequests: true,
      loadingHeadshotRequests: true,
      loadingPhotoshootRequests: true,
      loadingVideoRequests: true,
      // Table column sorting
      sortPublicationRequests: {
        sortKey: 'created',
        reverse: false,
      },
      sortHeadshotsRequests: {
        sortKey: 'created',
        reverse: false,
      },
      sortPhotoshootRequests: {
        sortKey: 'created',
        reverse: false,
      },
      sortVideoRequests: {
        sortKey: 'created',
        reverse: false,
      },
      // Request statuses
      requestStatuses: [],
      publicationRequestStatuses: [],
      headshotRequestStatuses: [],
      photoshootRequestStatuses: [],
      videoRequestStatuses: [],
      // List of each request type
      publicationRequests: [],
      headshotRequests: [],
      photoshootRequests: [],
      videoshootRequests: [],
      // Paginated list of each request type
      paginatedPublicationRequests: null,
      paginatedHeadshotRequests: null,
      paginatedPhotoshootRequests: null,
      paginatedVideoRequests: null,
    }
  },
  computed: {
    headingIcon: function () {
      return '<i class="fa fa-list"></i>'
    },
    orderedPublicationRequests: function () {
      // Uses LODASH to order columns (https://vuejs.org/v2/guide/migration.html#Replacing-the-orderBy-Filter)
      // "order the paginated video requests by the sort key as defined in the sortVideoRequests data object"
      // Dates are ordered by earliest first, but we want latest first. So watch for date cases and reverse the logic
      if (this.sortPublicationRequests.sortKey == "created" || this.sortPublicationRequests.sortKey == "completionDate") {
        return this.sortPublicationRequests.reverse ? _.orderBy(this.paginatedPublicationRequests, this.sortPublicationRequests.sortKey) : _.orderBy(this.paginatedPublicationRequests, this.sortPublicationRequests.sortKey).reverse()
      }

      return this.sortPublicationRequests.reverse ? _.orderBy(this.paginatedPublicationRequests, this.sortPublicationRequests.sortKey).reverse() : _.orderBy(this.paginatedPublicationRequests, this.sortPublicationRequests.sortKey)
    },
    orderedHeadshotRequests: function () {
      if (this.sortHeadshotsRequests.sortKey == "created" || this.sortHeadshotsRequests.sortKey == "timeSlot.dateOfShoot") {
        return this.sortHeadshotsRequests.reverse ? _.orderBy(this.paginatedHeadshotRequests, this.sortHeadshotsRequests.sortKey) : _.orderBy(this.paginatedHeadshotRequests, this.sortHeadshotsRequests.sortKey).reverse()
      }

      return this.sortHeadshotsRequests.reverse ? _.orderBy(this.paginatedHeadshotRequests, this.sortHeadshotsRequests.sortKey).reverse() : _.orderBy(this.paginatedHeadshotRequests, this.sortHeadshotsRequests.sortKey)
    },
    orderedPhotoshootRequests: function () {
      if (this.sortPhotoshootRequests.sortKey == "created" || this.sortPhotoshootRequests.sortKey == "startTime") {
        return this.sortPhotoshootRequests.reverse ? _.orderBy(this.paginatedPhotoshootRequests, this.sortPhotoshootRequests.sortKey) : _.orderBy(this.paginatedPhotoshootRequests, this.sortPhotoshootRequests.sortKey).reverse()
      }

      return this.sortPhotoshootRequests.reverse ? _.orderBy(this.paginatedPhotoshootRequests, this.sortPhotoshootRequests.sortKey).reverse() : _.orderBy(this.paginatedPhotoshootRequests, this.sortPhotoshootRequests.sortKey)
    },
    orderedVideoshootRequests: function () {
      if (this.sortVideoRequests.sortKey == "created" || this.sortVideoRequests.sortKey == "completionDate") {
        return this.sortVideoRequests.reverse ? _.orderBy(this.paginatedVideoRequests, this.sortVideoRequests.sortKey) : _.orderBy(this.paginatedVideoRequests, this.sortVideoRequests.sortKey).reverse()
      }

      return this.sortVideoRequests.reverse ? _.orderBy(this.paginatedVideoRequests, this.sortVideoRequests.sortKey).reverse() : _.orderBy(this.paginatedVideoRequests, this.sortVideoRequests.sortKey)
    },
    userCanCreate: function () {
      return this.permissions[0].create ? true : false
    },
    userCanEdit: function () {
      return this.permissions[0].edit ? true : false
    }
  },

  methods: {
    timeFormat(timestr) {
      return moment(timestr, 'YYYY-MM-DD HH:mm:ss').format('h:mm a')
    },
    dateOnlyFormat: function (dateStr) {
      return moment(dateStr).format('M/D/YY')
    },
    fetchMultimediaRequests: function () {
      let self = this
      axios.get('/api/multimediarequests/list')
          // success
          .then(function (response) {
            self.records = response.data
            response.data.forEach(function (record) {
              switch (record.requestType) {
                case 'publication':
                  self.publicationRequests.push(record)
                  break
                case 'headshot':
                  self.headshotRequests.push(record)
                  break
                case 'photo':
                  self.photoshootRequests.push(record)
                  break
                case 'video':
                  self.videoshootRequests.push(record)
                  break
              }
            })
            self.loadingHeadshotRequests = false
            self.loadingPhotoshootRequests = false
            self.loadingVideoRequests = false
            self.loadingPublicationRequests = false
          })
          // fail
          .catch(function (error) {
            self.apiError.status = error.response.status
            switch (error.response.status) {
              case 403:
                self.apiError.message = "You do not have sufficient privileges to retrieve multimedia requests."
                break
              case 404:
                self.apiError.message = "Multimedia requests were not found."
                break
              case 500:
                self.apiError.message = "An internal error occurred."
                break
              default:
                self.apiError.message = "An error occurred."
                break
            }
            self.loadingHeadshotRequests = false
            self.loadingPhotoshootRequests = false
            self.loadingVideoRequests = false
            self.loadingPublicationRequests = false
          })
    },
    fetchStatusOptions: function () {
      let self = this
      axios.get('/api/multimediarequests/statuses')
          // success
          .then(function (response) {
            // We only want the 'statusSlug' field, not the entire object
            self.requestStatuses = response.data

            // collect status slugs
            let statusSlugs = []
            self.requestStatuses.forEach(function (status) {
              statusSlugs.push(status.statusSlug)
            })

            // save a COPY of all status slugs for each request type
            self.publicationRequestStatuses = JSON.parse(JSON.stringify(statusSlugs))
            self.headshotRequestStatuses = JSON.parse(JSON.stringify(statusSlugs))
            self.photoshootRequestStatuses = JSON.parse(JSON.stringify(statusSlugs))
            self.videoRequestStatuses = JSON.parse(JSON.stringify(statusSlugs))
          })
          // fail
          .catch(function (error) {
            console.log("ERROR FETCHING STATUS OPTIONS!")
          })
    },
    setPaginatedHeadshotRequests(headshotRequests) {
      this.loadingHeadshotRequests = true // show loading wheel
      this.paginatedHeadshotRequests = headshotRequests // set paginated headshot requests returned from child paginator components
      this.loadingHeadshotRequests = false // turn off loading wheel
    },
    setPaginatedPhotoshootRequests(photoshootRequests) {
      this.loadingPhotoshootRequests = true
      this.paginatedPhotoshootRequests = photoshootRequests
      this.loadingPhotoshootRequests = false
    },
    setPaginatedVideoRequests(videoRequests) {
      this.loadingVideoRequests = true
      this.paginatedVideoRequests = videoRequests
      this.loadingVideoRequests = false
    },
    setPaginatedPublicationRequests(publicationRequests) {
      this.loadingPublicationRequests = true
      this.paginatedPublicationRequests = publicationRequests
      this.loadingPublicationRequests = false
    },
    /**
     * Sort columns by a key
     * @param sortObj (e.g. sortVideoRequests, which contains a sortKey and reverse property)
     * @param sortKey (a STRING representation of the key to be sorted by...LODASH will know what to do with it)
     */
    sortBy: function (sortObj, sortKey) {
      sortObj.reverse = (sortObj.sortKey == sortKey) ? !sortObj.reverse : false;
      sortObj.sortKey = sortKey;
    },
    /**
     * Adds and removes request types from the given array
     * @param requestStatusArray   The copy of possible statuses for the request type (e.g. video request)
     * @param status (e.g. 'new', 'done', etc.)
     */
    toggleFilter: function (requestStatusArray, status) {
      if (requestStatusArray.includes(status)) {
        requestStatusArray.splice(requestStatusArray.indexOf(status), 1) // remove the status
      } else {
        requestStatusArray.push(status)// add the status
      }
    },
  }
}
</script>
