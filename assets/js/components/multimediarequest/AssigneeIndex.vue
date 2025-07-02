<template>
    <div>
        <heading>
<!--            <span slot="icon" v-html="headingIcon"></span>-->
            <span>Multimedia Request Assignees</span>
        </heading>
        <p><a href="/multimediarequests/assignees/create" class="btn btn-info">New Assignee</a></p>
        <div v-if="apiError.status" class="alert alert-danger fade show" role="alert">
            {{ apiError.message }}
        </div>
        <div v-if="!loadingAssignees" class="table-responsive">
            <table class="table table-hover table-sm">
                <thead>
                <tr>
                    <th scope="col">
                        <span class="col-sortable"
                              @click="sortBy('lastName')">Last name</span>
                        <i v-if="sortKey == 'lastName' && reverseSort == false"
                           class="fa fa-sort-up"></i>
                        <i v-if="sortKey == 'lastName' && reverseSort == true"
                           class="fa fa-sort-down"></i>
                    </th>
                    <th scope="col">
                        <span class="col-sortable"
                              @click="sortBy('firstName')">First name</span>
                        <i v-if="sortKey == 'firstName' && reverseSort == false"
                           class="fa fa-sort-up"></i>
                        <i v-if="sortKey == 'firstName' && reverseSort == true"
                           class="fa fa-sort-down"></i>
                    </th>
                    <th scope="col">
                        <span class="col-sortable"
                              @click="sortBy('email')">Email</span>
                        <i v-if="sortKey == 'email' && reverseSort == false"
                           class="fa fa-sort-up"></i>
                        <i v-if="sortKey == 'email' && reverseSort == true"
                           class="fa fa-sort-down"></i>
                    </th>
                    <th scope="col">
                        <span class="col-sortable"
                              @click="sortBy('status.status')">Status</span>
                        <i v-if="sortKey == 'status.status' && reverseSort == false"
                           class="fa fa-sort-up"></i>
                        <i v-if="sortKey == 'status.status' && reverseSort == true"
                           class="fa fa-sort-down"></i>
                    </th>
                    <th scope="col">Actions</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="assignee in orderedAssignees">
                    <th scope="row">{{ assignee.lastName}}</th>
                    <td>{{ assignee.firstName }}</td>
                    <td>{{ assignee.email }}</td>
                    <td>{{ assignee.status ? assignee.status.status : '' }}</td>
                    <td><a :href="'/multimediarequests/assignees/' + assignee.id"><font-awesome-icon icon="fa-solid fa-pen-to-square" /></a></td>
                </tr>
                </tbody>
            </table>
        </div>
        <div v-else>
            <p style="text-align: center"><img src="/images/loading.gif" alt="Loading..."/></p>
        </div>
        <paginator v-show="!loadingAssignees" :items="assignees"
                   @itemsPerPageChanged="setPaginatedAssignees"></paginator>
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
            this.fetchAssignees()
        },
        components: {Heading, Paginator},
        props: {},
        data: function () {
            return {
                apiError: {
                    message: null,
                    status: null
                },
                assignees: [],
                loadingAssignees: true,
                paginatedAssignees: null,
                reverseSort: false,
                sortKey: 'lastName',
            }
        },
        computed: {
            headingIcon: function () {
                return '<i class="fa fa-user"></i>'
            },
            orderedAssignees: function () {
                return this.reverseSort ? _.orderBy(this.paginatedAssignees, this.sortKey.toLowerCase()).reverse() : _.orderBy(this.paginatedAssignees, this.sortKey.toLowerCase())
            },
        },
        methods: {
            fetchAssignees: function () {
                let self = this
                axios.get('/api/multimediaassignees')
                // success
                    .then(function (response) {
                        self.assignees = response.data
                    })
                    // fail
                    .catch(function (error) {
                        self.apiError.status = error.response.status
                        switch (error.response.status) {
                            case 403:
                                self.apiError.message = "You do not have sufficient privileges to retrieve assignees."
                                break
                            case 404:
                                self.apiError.message = "Users were not found."
                                break
                            case 500:
                                self.apiError.message = "An internal error occurred."
                                break
                            default:
                                self.apiError.message = "An error occurred."
                                break
                        }
                        self.loadingAssignees = false
                    })
            },
            setPaginatedAssignees: function (assignees) {
                this.loadingAssignees = true // show loading wheel
                this.paginatedAssignees = assignees // set paginated assignees returned from child paginator components
                this.loadingAssignees = false // turn off loading wheel
            },
            /**
             * Sort columns by a key
             * @param sortKey (a STRING representation of the key to be sorted by...LODASH will know what to do with it)
             */
            sortBy: function (sortKey) {
                this.reverseSort = (this.sortKey == sortKey) ? !this.reverseSort : false;
                this.sortKey = sortKey;
            },
        },
        filters: {},
    }
</script>
