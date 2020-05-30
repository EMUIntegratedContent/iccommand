<template>
    <div>
        <heading>
            <span slot="icon" v-html="headingIcon">{{ headingIcon }}</span>
            <span slot="title">EMU Map Items</span>
        </heading>
        <!--<p v-if="userCanCreate"><a href="/map/items/create" class="btn btn-primary">New Map Item</a></p>-->
        <div v-if="apiError.status" class="alert alert-danger fade show" role="alert">
            {{ apiError.message }}
        </div>
        <div id="accordion">
            <div class="card">
                <div class="card-header" id="headingBuildings">
                    <h5 class="mb-0">
                        <button class="btn btn-link" data-toggle="collapse" data-target="#collapseBuildings"
                                aria-expanded="true" aria-controls="collapseBuildings">
                            Buildings
                            <span v-if="!loadingBuildings" class="badge badge-primary">{{ buildings.length }}</span>
                            <span v-else><i class="fa fa-spinner"></i></span>
                        </button>
                    </h5>
                </div>
                <div id="collapseBuildings" class="collapse show" aria-labelledby="headingBuildings"
                     data-parent="#accordion">
                    <div class="card-body">
                        <div v-if="!loadingBuildings" class="table-responsive">
                            <table class="table table-hover table-sm">
                                <thead>
                                <tr>
                                    <th scope="col">Building</th>
                                    <th scope="col">Bathrooms</th>
                                    <th scope="col">Dining</th>
                                    <th scope="col">Emergency Devices</th>
                                    <th scope="col">Exhibits</th>
                                    <th scope="col">Services</th>
                                    <th scope="col">Images</th>
                                    <th scope="col">Adm. Tour</th>
                                    <th scope="col">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="item in paginatedBuildings">
                                    <th scope="row">{{ item.name }}</th>
                                    <td>{{ item.bathrooms.length }}</td>
                                    <td>{{ item.diningOptions.length }}</td>
                                    <td>{{ item.emergencyDevices.length }}</td>
                                    <td>{{ item.exhibits.length }}</td>
                                    <td>{{ item.services.length }}</td>
                                    <td>{{ item.images.length }}</td>
                                    <td>{{ item.admissionsTour ? 'Y' : '-' }}</td>
                                    <td><a :href="'/map/items/' + item.id" v-if="userCanEdit"><i class="fa fa-eye"></i></a>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-else>
                            <p style="text-align: center"><img src="/images/loading.gif" alt="Loading..."/></p>
                        </div>
                        <paginator v-show="!loadingBuildings" :items="buildings"
                                   @itemsPerPageChanged="setPaginatedBuildings"></paginator>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header" id="headingBusses">
                    <h5 class="mb-0">
                        <button class="btn btn-link collapsed" data-toggle="collapse" data-target="#collapseBusses"
                                aria-expanded="false" aria-controls="collapseBusses">
                            Bus Stops
                            <span v-if="!loadingBusses" class="badge badge-primary">{{ busses.length }}</span>
                            <span v-else><i class="fa fa-spinner"></i></span>
                        </button>
                    </h5>
                </div>
                <div id="collapseBusses" class="collapse" aria-labelledby="headingBusses" data-parent="#accordion">
                    <div class="card-body">
                        <div v-if="!loadingBusses" class="table-responsive">
                            <table class="table table-hover table-sm">
                                <thead>
                                <tr>
                                    <th scope="col">Location</th>
                                    <th scope="col">Description</th>
                                    <th scope="col">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="item in paginatedBusses">
                                    <th scope="row">{{ item.name }}</th>
                                    <td>{{ item.description }}</td>
                                    <td><a :href="'/map/items/' + item.id" v-if="userCanEdit"><i class="fa fa-eye"></i></a>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-else>
                            <p style="text-align: center"><img src="/images/loading.gif" alt="Loading..."/></p>
                        </div>
                        <paginator v-show="!loadingBusses" :items="busses"
                                   @itemsPerPageChanged="setPaginatedBusses"></paginator>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header" id="headingEmergencyDevices">
                    <h5 class="mb-0">
                        <button class="btn btn-link collapsed" data-toggle="collapse"
                                data-target="#collapseEmergencyDevices" aria-expanded="false"
                                aria-controls="collapseEmergencyDevices">
                            Emergency Devices
                            <span v-if="!loadingEmergencyDevices" class="badge badge-primary">{{ emergencyDevices.length }}</span>
                            <span v-else><i class="fa fa-spinner"></i></span>
                        </button>
                    </h5>
                </div>
                <div id="collapseEmergencyDevices" class="collapse" aria-labelledby="headingEmergencyDevices"
                     data-parent="#accordion">
                    <div class="card-body">
                        <div v-if="!loadingEmergencyDevices" class="table-responsive">
                            <table class="table table-hover table-sm">
                                <thead>
                                <tr>
                                    <th scope="col">Device</th>
                                    <th scope="col">Type</th>
                                    <th scope="col">Building</th>
                                    <th scope="col">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="item in paginatedEmergencyDevices">
                                    <th scope="row">{{ item.name }}</th>
                                    <td>{{ item.type.name }}</td>
                                    <td>
                                        <span v-if="item.building"><a :href="'/map/item/' + item.building.id">{{ item.building.name }}</a></span>
                                        <span v-else>None</span>
                                    </td>
                                    <td><a :href="'/map/items/' + item.id" v-if="userCanEdit"><i class="fa fa-eye"></i></a>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-else>
                            <p style="text-align: center"><img src="/images/loading.gif" alt="Loading..."/></p>
                        </div>
                        <paginator v-show="!loadingEmergencyDevices" :items="emergencyDevices"
                                   @itemsPerPageChanged="setPaginatedEmergencyDevices"></paginator>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header" id="headingExhibits">
                    <h5 class="mb-0">
                        <button class="btn btn-link collapsed" data-toggle="collapse" data-target="#collapseExhibits"
                                aria-expanded="false" aria-controls="collapseExhibits">
                            Exhibits
                            <span v-if="!loadingExhibits" class="badge badge-primary">{{ exhibits.length }}</span>
                            <span v-else><i class="fa fa-spinner"></i></span>
                        </button>
                    </h5>
                </div>
                <div id="collapseExhibits" class="collapse" aria-labelledby="headingExhibits" data-parent="#accordion">
                    <div class="card-body">
                        <div v-if="!loadingExhibits" class="table-responsive">
                            <table class="table table-hover table-sm">
                                <thead>
                                <tr>
                                    <th scope="col">Title</th>
                                    <th scope="col">Type</th>
                                    <th scope="col">Description</th>
                                    <th scope="col">Building</th>
                                    <th scope="col">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="item in paginatedExhibits">
                                    <th scope="row">{{ item.name }}</th>
                                    <td>{{ item.type.name }}</td>
                                    <td>{{ item.description }}</td>
                                    <td>
                                        <span v-if="item.building"><a :href="'/map/item/' + item.building.id">{{ item.building.name }}</a></span>
                                        <span v-else>None</span>
                                    </td>
                                    <td><a :href="'/map/items/' + item.id" v-if="userCanEdit"><i class="fa fa-eye"></i></a>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-else>
                            <p style="text-align: center"><img src="/images/loading.gif" alt="Loading..."/></p>
                        </div>
                        <paginator v-show="!loadingExhibits" :items="exhibits"
                                   @itemsPerPageChanged="setPaginatedExhibits"></paginator>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header" id="headingParking">
                    <h5 class="mb-0">
                        <button class="btn btn-link collapsed" data-toggle="collapse" data-target="#collapseParking"
                                aria-expanded="false" aria-controls="collapseParking">
                            Parking Lots
                            <span v-if="!loadingParkingLots" class="badge badge-primary">{{ parkingLots.length }}</span>
                            <span v-else><i class="fa fa-spinner"></i></span>
                        </button>
                    </h5>
                </div>
                <div id="collapseParking" class="collapse" aria-labelledby="headingParking" data-parent="#accordion">
                    <div class="card-body">
                        <div v-if="!loadingParkingLots" class="table-responsive">
                            <table class="table table-hover table-sm">
                                <thead>
                                <tr>
                                    <th scope="col">Lot</th>
                                    <th scope="col">Hours</th>
                                    <th scope="col">Spaces</th>
                                    <th scope="col">Lot Types</th>
                                    <th scope="col">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="item in paginatedParkingLots">
                                    <th scope="row">{{ item.name }}</th>
                                    <td><span v-html="item.hours"></span></td>
                                    <td>{{ item.spaces }}</td>
                                    <td>
                                        <span v-for="type in item.parkingTypes"
                                              class="badge badge-dark">{{ type.name }}</span>
                                    </td>
                                    <td><a :href="'/map/items/' + item.id" v-if="userCanEdit"><i class="fa fa-eye"></i></a>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-else>
                            <p style="text-align: center"><img src="/images/loading.gif" alt="Loading..."/></p>
                        </div>
                        <paginator v-show="!loadingParkingLots" :items="parkingLots"
                                   @itemsPerPageChanged="setPaginatedParkingLots"></paginator>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<style>
</style>
<script>
    import Heading from '../utils/Heading.vue'
    import Paginator from '../utils/Paginator.vue'

    export default {
        created() {
        },
        mounted() {
            this.fetchMapItems()
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
                buildings: [],
                busses: [],
                emergencyDevices: [],
                exhibits: [],
                parkingLots: [],
                loadingBuildings: true,
                loadingBusses: true,
                loadingEmergencyDevices: true,
                loadingExhibits: true,
                loadingParkingLots: true,
                paginatedBuildings: null,
                paginatedBusses: null,
                paginatedEmergencyDevices: null,
                paginatedExhibits: null,
                paginatedParkingLots: null,
            }
        },
        computed: {
            headingIcon: function () {
                return '<i class="fa fa-list"></i>'
            },
            userCanCreate: function () {
                return this.permissions[0].create ? true : false
            },
            userCanEdit: function () {
                return this.permissions[0].edit ? true : false
            }
        },
        methods: {
            fetchMapItems: function () {
                let self = this
                axios.get('/api/mapitems')
                // success
                    .then(function (response) {
                        response.data.forEach(function (item) {
                            // Filter map items into their respective categories based on the "itemType" field
                            switch (item.itemType) {
                                case 'building':
                                    self.buildings.push(item)
                                    break
                                case 'bus':
                                    self.busses.push(item)
                                    break
                                case 'emergency device':
                                    self.emergencyDevices.push(item)
                                    break
                                case 'exhibit':
                                    self.exhibits.push(item)
                                    break
                                case 'parking':
                                    self.parkingLots.push(item)
                                    break
                            }
                        })
                        // disable any loading flags for empty arrays
                        if (self.buildings.length == 0) {
                            self.loadingBuildings = false
                        }
                        if (self.busses.length == 0) {
                            self.loadingBusses = false
                        }
                        if (self.emergencyDevices.length == 0) {
                            self.loadingEmergencyDevices = false
                        }
                        if (self.exhibits.length == 0) {
                            self.loadingExhibits = false
                        }
                        if (self.parkingLots.length == 0) {
                            self.loadingParkingLots = false
                        }
                    })
                    // fail
                    .catch(function (error) {
                        self.apiError.status = error.response.status
                        switch (error.response.status) {
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
                        self.loadingBuildings = false
                        self.loadingBusses = false
                        self.loadingEmergencyDevices = false
                        self.loadingExhibits = false
                        self.loadingParkingLots = false
                    })
            },
            setPaginatedBuildings(buildings) {
                this.loadingBuildings = true // show loading wheel
                this.paginatedBuildings = buildings // set paginated buildings returned from child paginator components
                this.loadingBuildings = false // turn off loading wheel
            },
            setPaginatedBusses(busses) {
                this.loadingBusses = true
                this.paginatedBusses = busses
                this.loadingBusses = false
            },
            setPaginatedEmergencyDevices(emergencyDevices) {
                this.loadingEmergencyDevices = true
                this.paginatedEmergencyDevices = emergencyDevices
                this.loadingEmergencyDevices = false
            },
            setPaginatedExhibits(exhibits) {
                this.loadingExhibits = true
                this.paginatedExhibits = exhibits
                this.loadingExhibits = false
            },
            setPaginatedParkingLots(parkingLots) {
                this.loadingParkingLots = true
                this.paginatedParkingLots = parkingLots
                this.loadingParkingLots = false
            }
        },
        filters: {},
    }
</script>
