<template>
	<div>
		<heading>
			<span>Scholarships</span>
		</heading>

		<div class="row">
			<div class="col-md-12">
				<div class="card">
					<div class="card-header">
						<div class="row">
							<div class="col-md-6">
								<h4>Scholarships</h4>
							</div>
							<div class="col-md-6 text-right">
								<button
									v-if="userCanCreate"
									class="btn btn-success"
									@click="createNewScholarship"
								>
									<i class="fa fa-plus"></i> Add Scholarship
								</button>
							</div>
						</div>
					</div>
					<div class="card-body">
						<div>
							<label for="searchScholarships" class="sr-only"
								>Search scholarships</label
							>
							<VueMultiselect
								:options="searchResults"
								:multiple="false"
								:clear-on-select="true"
								placeholder="Search scholarships by title or keyword (type at least 3 characters)"
								label="title"
								track-by="id"
								id="searchScholarships"
								class="form-control"
								style="padding: 0"
								name="searchScholarships"
								@input="handleSearchInput"
								@select="handleScholarshipSelected"
							>
							</VueMultiselect>
						</div>
						<div v-if="!loadingScholarships" class="table-responsive">
							<table class="table table-striped">
								<thead>
									<tr>
										<th>Title</th>
										<th>Apply by</th>
										<th>Expires</th>
										<th class="text-center">Active</th>
										<th v-if="userCanEdit">Actions</th>
									</tr>
								</thead>
								<tbody>
									<tr v-for="scholarship in scholarships" :key="scholarship.id">
										<td>
											<a
												v-if="userCanEdit"
												:href="'/scholarships/' + scholarship.id + '/edit'"
												>{{ scholarship.title }}</a
											>
											<a v-else :href="'/scholarships/' + scholarship.id">{{
												scholarship.title
											}}</a>
										</td>
										<td>{{ formatDate(scholarship.applyDate) }}</td>
										<td :class="{ 'text-danger': isExpired(scholarship) }">
											{{ formatDate(scholarship.expDate) }}
										</td>
										<td class="text-center">
											<span v-if="scholarship.active" class="badge badge-success"
												>Yes</span
											>
											<span v-else class="badge badge-secondary">No</span>
										</td>
										<td v-if="userCanEdit">
											<a :href="'/scholarships/' + scholarship.id + '/edit'"
												><font-awesome-icon icon="fa-solid fa-pen-to-square" />
											</a>
										</td>
									</tr>
									<tr v-if="scholarships.length === 0">
										<td colspan="5" class="text-center text-muted">
											No scholarships found.
										</td>
									</tr>
								</tbody>
							</table>
						</div>
						<div v-else class="text-center">
							<img src="/images/loading.gif" alt="Loading..." />
						</div>
						<external-paginator
							v-show="!loadingScholarships"
							:items="scholarships"
							:ext-curr-pg="currentPage"
							:ext-items-per-pg="itemsPerPage"
							:total-recs="totalScholarships"
							@itemsPerPageChanged="handleItemsPerPageChanged"
							@pageChanged="handlePageChanged"
						></external-paginator>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<style></style>

<script>
import Heading from "../utils/Heading.vue"
import ExternalPaginator from "../utils/ExternalPaginator.vue"
import VueMultiselect from "vue-multiselect"

export default {
	created() {
		this.fetchScholarships()
	},

	components: { Heading, ExternalPaginator, VueMultiselect },

	props: {
		permissions: {
			type: Array,
			required: true
		}
	},

	data: function () {
		return {
			apiError: {
				message: null,
				status: null
			},
			loadingScholarships: false,
			scholarships: [],
			// Search data
			searchResults: [],
			searchTerm: "",
			// Pagination data
			currentPage: 1,
			itemsPerPage: 50,
			totalScholarships: 0
		}
	},

	computed: {
		userCanCreate: function () {
			return this.permissions[0].create ? true : false
		},

		userCanEdit: function () {
			return this.permissions[0].edit ? true : false
		}
	},

	methods: {
		createNewScholarship: function () {
			window.location.href = "/scholarships/create"
		},

		formatDate: function (dateString) {
			if (!dateString) {
				return "—"
			}

			let date = new Date(dateString)

			if (isNaN(date.getTime())) {
				return "—"
			}

			return date.toLocaleDateString("en-US")
		},

		isExpired: function (scholarship) {
			if (!scholarship.expDate) {
				return false
			}

			let expires = new Date(scholarship.expDate)

			return !isNaN(expires.getTime()) && expires < new Date()
		},

		handleSearchInput: function (evt) {
			this.searchTerm = evt.target.value
			if (this.searchTerm.length > 2) {
				this.searchScholarships()
			}
		},

		handleScholarshipSelected: function (evt) {
			if (this.userCanEdit) {
				window.location.href = "/scholarships/" + evt.id + "/edit"
			} else {
				window.location.href = "/scholarships/" + evt.id
			}
		},

		handleItemsPerPageChanged: function (itemsPerPage) {
			this.itemsPerPage = itemsPerPage
			this.fetchScholarships()
		},

		handlePageChanged: function (currentPage) {
			this.currentPage = currentPage
			this.fetchScholarships()
		},

		fetchScholarships: function () {
			let self = this // "this" loses scope within Axios.

			this.loadingScholarships = true
			this.scholarships = []

			axios
				.get(
					`/api/scholarships/list?page=${this.currentPage}&limit=${this.itemsPerPage}`
				)
				.then(function (response) {
					self.totalScholarships = response.data.totalRows
					self.scholarships = response.data.scholarships
				})
				.catch(function (error) {
					self.apiError.status = error.response.status

					switch (error.response.status) {
						case 403:
							self.apiError.message =
								"You do not have sufficient privileges to retrieve scholarships."
							break
						case 404:
							self.apiError.message = "Scholarships were not found."
							break
						case 500:
							self.apiError.message = "An internal error occurred."
							break
						default:
							self.apiError.message = "An error occurred."
							break
					}
				})
				.finally(function () {
					self.loadingScholarships = false
				})
		},

		searchScholarships: function () {
			let self = this

			axios
				.get(`/api/scholarships/search?searchterm=${this.searchTerm}`)
				.then(function (response) {
					self.searchResults = response.data
				})
				.catch(function (error) {
					console.log("Error searching scholarships:", error)
				})
		}
	}
}
</script>
