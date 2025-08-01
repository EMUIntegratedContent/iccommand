<template>
	<div>
		<heading>
			<span>Photo Requests</span>
		</heading>
		<div
			v-if="apiError.status"
			class="alert alert-danger fade show"
			role="alert"
		>
			{{ apiError.message }}
		</div>
		<div>
			<p>
				This is a list of all photo requests submitted via the
				<a
					href="https://www.emich.edu/communications/support-center/photo.php"
					target="_blank"
					>EMU Divcomm Website</a
				>.
			</p>
		</div>
		<div class="row">
			<div class="col-md-6">
				<label for="searchInput" class="sr-only">Search photo requests</label>
				<VueMultiselect
					:options="searchResults"
					:multiple="false"
					:clear-on-select="true"
					placeholder="Search requests by requester (type at least 3 characters)"
					label="displayName"
					track-by="id"
					id="searchInput"
					name="searchInput"
					@input="handleSearchInput"
					@select="handleRequestSelected"
				>
				</VueMultiselect>
			</div>
			<div class="col-md-3">
				<label for="statusFilter" class="sr-only">Filter by status</label>
				<select
					v-model="statusFilter"
					class="form-control"
					id="statusFilter"
					@change="handleStatusFilterChange"
				>
					<option value="">All Statuses</option>
					<option value="declined">Declined</option>
					<option value="complete">Complete</option>
					<option value="pending">Pending</option>
					<option value="WC">Waiting on Client</option>
					<option value="DG">Approval Required</option>
					<option value="IP">In Progress</option>
				</select>
			</div>
			<div class="col-md-3">
				<label for="categoryFilter" class="sr-only">Filter by category</label>
				<select
					v-model="categoryFilter"
					class="form-control"
					id="categoryFilter"
					@change="handleCategoryFilterChange"
				>
					<option value="">All Categories</option>
					<option
						v-for="category in categories"
						:key="category.category"
						:value="category.category"
					>
						{{ category.category }} ({{ category.count }})
					</option>
					<option
						v-if="
							categoryFilter &&
							!categories.find((c) => c.category === categoryFilter)
						"
						:value="categoryFilter"
					>
						{{ categoryFilter }} (0)
					</option>
				</select>
			</div>
		</div>
		<div v-if="!loadingPhotoRequests" class="table-responsive mt-2">
			<table class="table table-hover table-sm">
				<thead>
					<tr>
						<th scope="col">Name</th>
						<th scope="col">Shoot Type</th>
						<th scope="col">Department</th>
						<th scope="col">Status</th>
						<th scope="col">Assigned To</th>
						<th scope="col">Submitted</th>
						<th scope="col">Proposed Date</th>
						<th scope="col">Actions</th>
					</tr>
				</thead>
				<tbody>
					<tr
						v-for="request in photoRequests"
						:id="request.id"
						:key="`request-${request.id}`"
					>
						<td>{{ request.firstName }} {{ request.lastName }}</td>
						<td>
							{{
								request.shootType.charAt(0).toUpperCase() +
								request.shootType.slice(1)
							}}
						</td>
						<td>{{ request.department }}</td>
						<td>
							<span v-if="request.declined" class="badge badge-danger"
								>Declined</span
							>
							<span v-else-if="request.completed" class="badge badge-success"
								>Complete</span
							>
							<span
								v-else-if="request.status"
								:class="'badge ' + getStatusBadgeClass(request.status)"
								>{{ getStatusDisplay(request.status) }}</span
							>
							<span v-else class="badge badge-light">Pending</span>
						</td>
						<td>
							<span v-if="request.assignedToName">
								{{ request.assignedToName }}
							</span>
							<span v-else class="badge badge-light">---</span>
						</td>
						<td>{{ formatDate(request.submitted) }}</td>
						<td>{{ formatDate(request.shootDate) }}</td>
						<td>
							<a v-if="userCanEdit" :href="'/photorequests/' + request.id"
								><font-awesome-icon icon="fa-solid fa-pen-to-square"
							/></a>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<div v-else>
			<p style="text-align: center">
				<img src="/images/loading.gif" alt="Loading..." />
			</p>
		</div>
		<external-paginator
			v-show="!loadingPhotoRequests"
			:ext-curr-pg="currentPage"
			:ext-items-per-pg="itemsPerPage"
			:total-recs="totalPhotoRequests"
			:items="photoRequests"
			@itemsPerPageChanged="handleItemsPerPageChanged"
			@pageChanged="handlePageChanged"
		>
		</external-paginator>
	</div>
</template>
<style></style>
<script>
import Heading from "../utils/Heading.vue"
import ExternalPaginator from "../utils/ExternalPaginator.vue"
import VueMultiselect from "vue-multiselect"

export default {
	created() {
		this.fetchPhotoRequests()
		this.fetchCategories()
	},
	components: {
		Heading,
		ExternalPaginator,
		VueMultiselect
	},
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

			loadingPhotoRequests: true,
			searchTerm: "",
			photoRequests: [],

			//External Paginator Data
			currentPage: 1,
			itemsPerPage: 50,
			totalPhotoRequests: 0,

			// Search results
			searchResults: [],
			statusFilter: "",
			categoryFilter: "",
			categories: []
		}
	},

	computed: {
		headingIcon: function () {
			return "<i class='fa fa-camera'></i>"
		},
		userCanEdit: function () {
			return this.permissions[0].view ? true : false
		}
	},
	methods: {
		handlePageChanged: function (currentPage) {
			this.currentPage = currentPage
			this.fetchPhotoRequests()
		},
		handleItemsPerPageChanged: function (itemsPerPage) {
			this.itemsPerPage = itemsPerPage
			this.fetchPhotoRequests()
		},
		handleSearchInput: function (evt) {
			this.searchTerm = evt.target.value
			if (this.searchTerm.length > 2) {
				this.searchPhotoRequests()
			}
		},

		formatSearchDisplay: function (request) {
			const name = request.firstName + " " + request.lastName
			const date = request.shootDate
				? new Date(request.shootDate).toLocaleDateString()
				: "No date"
			return name + " - " + date
		},
		handleRequestSelected: function (evt) {
			if (this.userCanEdit) {
				window.location.href = "/photorequests/" + evt.id
			}
		},
		formatDate: function (dateString) {
			if (!dateString) return ""
			const date = new Date(dateString)
			return date.toLocaleDateString()
		},

		getStatusDisplay: function (status) {
			switch (status) {
				case "WC":
					return "Waiting on Client"
				case "IP":
					return "In Progress"
				case "DG":
					return "Approval Required"
				default:
					return status
			}
		},

		getStatusBadgeClass: function (status) {
			switch (status) {
				case "WC":
					return "badge-warning"
				case "IP":
					return "badge-info"
				case "DG":
					return "badge-danger"
				default:
					return "badge-secondary"
			}
		},
		fetchPhotoRequests: function () {
			let self = this // "this" loses scope within Axios.

			this.loadingPhotoRequests = true

			const params = {
				page: self.currentPage,
				limit: self.itemsPerPage
			}

			if (this.statusFilter) {
				params.status = this.statusFilter
			}

			if (this.categoryFilter) {
				params.category = this.categoryFilter
			}

			/* Ajax (Axios) Submission */
			axios
				.get("/api/photorequests/list", { params })
				.then(function (response) {
					// Success.
					self.totalPhotoRequests = response.data.totalRows
					self.photoRequests = response.data.photoRequests
				})
				.catch(function (error) {
					// Failure.
					self.apiError.status = error.response.status

					switch (error.response.status) {
						case 403:
							self.apiError.message =
								"You do not have sufficient privileges to retrieve photo requests."
							break
						case 404:
							self.apiError.message = "Photo requests were not found."
							break
						case 500:
							self.apiError.message = "An internal error occurred."
							break
						default:
							self.apiError.message = "An error occurred."
							break
					}
				})

			this.loadingPhotoRequests = false
		},

		handleStatusFilterChange: function () {
			this.currentPage = 1
			this.fetchPhotoRequests()
			this.fetchCategories()
		},
		handleCategoryFilterChange: function () {
			this.currentPage = 1
			this.fetchPhotoRequests()
			this.fetchCategories()
		},
		searchPhotoRequests: function () {
			let self = this // "this" loses scope within Axios.

			if (self.searchTerm.length > 2) {
				/* Ajax (Axios) Submission */
				axios
					.get(`/api/photorequests/search?searchterm=${self.searchTerm}`)
					.then(function (response) {
						// Success.
						// Format the search results to include a display name
						self.searchResults = response.data.map((item) => ({
							...item,
							displayName: `${item.firstName} ${item.lastName} - ${
								item.shootDate
									? new Date(item.shootDate).toLocaleDateString()
									: "No date"
							}`
						}))
					})
					.catch(function (error) {
						// Failure.
						self.apiError.status = error.response.status

						switch (error.response.status) {
							case 403:
								self.apiError.message =
									"You do not have sufficient privileges to search photo requests."
								break
							case 404:
								self.apiError.message = "Photo requests were not found."
								break
							case 500:
								self.apiError.message = "An internal error occurred."
								break
							default:
								self.apiError.message = "An error occurred."
								break
						}
					})
			}
		},
		fetchCategories: function () {
			let self = this // "this" loses scope within Axios.

			const params = {}
			if (this.statusFilter) {
				params.status = this.statusFilter
			}

			axios
				.get("/api/photorequests/categories", { params })
				.then(function (response) {
					self.categories = response.data
				})
				.catch(function (error) {
					self.apiError.status = error.response.status
					switch (error.response.status) {
						case 403:
							self.apiError.message =
								"You do not have sufficient privileges to retrieve categories."
							break
						case 404:
							self.apiError.message = "Categories were not found."
							break
						case 500:
							self.apiError.message = "An internal error occurred."
							break
						default:
							self.apiError.message = "An error occurred."
							break
					}
				})
		}
	}
}
</script>
