<template>
    <div>
        <heading>
            <span slot="icon" v-html="headingIcon">{{ headingIcon }}</span>
            <span slot="title">Multimedia Requests</span>
        </heading>
        <div v-if="apiError.status" class="alert alert-danger fade show" role="alert">
            {{ apiError.message }}
        </div>
        <div class="row">
            <div class="col-xs-12">
                <p>Multimedia Request Filter</p>
                <div class="btn-group" data-toggle="buttons">
                    <label class="btn btn-primary" :class="{ active : showRequests.includes('headshots') }" @click="toggleFilter('headshots')">
                        <input type="checkbox" autocomplete="off" :checked="showRequests.includes('headshots')" />Headshots
                    </label>
                    <label class="btn btn-primary" :class="{ active : showRequests.includes('photoshoots') }" @click="toggleFilter('photoshoots')">
                        <input type="checkbox" autocomplete="off" :checked="showRequests.includes('photoshoots')" />Photo Shoots
                    </label>
                    <label class="btn btn-primary" :class="{ active : showRequests.includes('videos') }" @click="toggleFilter('videos')">
                        <input type="checkbox" autocomplete="off" :checked="showRequests.includes('videos')" />Video Shoots
                    </label>
                    <label class="btn btn-primary" :class="{ active : showRequests.includes('graphics') }" @click="toggleFilter('graphics')">
                        <input type="checkbox" autocomplete="off" :checked="showRequests.includes('graphics')" />Graphic Design
                    </label>
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

    export default {
        created() {
        },
        mounted() {
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
                showRequests: [
                    'headshots',
                    'photoshoots',
                    'videos',
                    'graphics',
                ]
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
            fetchMultimediaRequests: function () {
                let self = this
                axios.get('/api/multimediarequests')
                // success
                    .then(function (response) {

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
                    })
            },
            // Adds and removes request types from the showRequests array
            toggleFilter: function(filter){
                if(this.showRequests.includes(filter)){
                    console.log(this.showRequests.indexOf(filter))
                    this.showRequests.slice(this.showRequests.indexOf(filter), 1) // remove the filter
                } else {
                    this.showRequests.push(filter)// add the filter
                }
            },
        },
        filters: {},
    }
</script>
