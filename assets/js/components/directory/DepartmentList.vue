<template>
	<div>
		<heading>
			<span slot="icon" v-html="headingIcon"></span>
			<span>Department Directory</span>
		</heading>

		<div class="row">
			<div class="col-md-12">
				<div class="card">
					<div class="card-header">
						<div class="row">
							<div class="col-md-6">
								<h4>Departments</h4>
							</div>
							<div class="col-md-6 text-right">
								<button
									v-if="userCanCreate"
									class="btn btn-success"
									@click="createNewDepartment"
								>
									<i class="fa fa-plus"></i> Add Department
								</button>
							</div>
						</div>
					</div>
					<div class="card-body">
						<div>
							<label for="searchDepartments" class="sr-only"
								>Search departments</label
							>
							<VueMultiselect
								:options="searchResults"
								:multiple="false"
								:clear-on-select="true"
								placeholder="Search departments (type at least 3 characters)"
								label="department"
								track-by="id"
								id="searchDepartments"
								class="form-control"
								style="padding: 0"
								name="searchDepartments"
								@input="handleSearchInput"
								@select="handleDepartmentSelected"
							>
							</VueMultiselect>
						</div>
						<div v-if="!loadingDepartments" class="table-responsive">
							<table class="table table-striped">
								<thead>
									<tr>
										<th>Department Name</th>
										<th>Building</th>
										<th>Phone</th>
										<th>Email</th>
										<th>Website</th>
										<th v-if="userCanEdit">Actions</th>
									</tr>
								</thead>
								<tbody>
									<tr v-for="department in departments" :key="department.id">
										<td>
											<a
												v-if="userCanEdit"
												:href="'/directory/' + department.id"
												>{{ department.department }}</a
											>
											<span v-else>{{ department.department }}</span>
										</td>
										<td>
											{{ department.buildingName || "---" }}
										</td>
										<td>{{ department.phone || "---" }}</td>
										<td>{{ department.email || "---" }}</td>
										<td>
											<span v-if="department.website">
												<a :href="department.website" target="_blank">
													Website
												</a>
											</span>
											<span v-else>---</span>
										</td>

										<td v-if="userCanEdit">
											<a
												v-if="userCanEdit"
												:href="'/directory/' + department.id"
												><font-awesome-icon icon="fa-solid fa-pen-to-square" />
											</a>
										</td>
									</tr>
								</tbody>
							</table>
						</div>
						<div v-else class="text-center">
							<img src="/images/loading.gif" alt="Loading..." />
						</div>
						<external-paginator
							v-show="!loadingDepartments"
							:items="departments"
							:ext-curr-pg="currentPage"
							:ext-items-per-pg="itemsPerPage"
							:total-recs="totalDepartments"
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
		this.fetchDepartments()
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
			/**
			 * The departments that are fetched.
			 * @type {Array.<Department>}
			 */
			departments: [],

			/**
			 * The search results for departments.
			 * @type {Array.<Department>}
			 */
			searchResults: [],

			/**
			 * The search term for departments.
			 * @type {string}
			 */
			searchTerm: "",

			/**
			 * The current page for pagination.
			 * @type {number}
			 */
			currentPage: 1,

			/**
			 * The items per page for pagination.
			 * @type {number}
			 */
			itemsPerPage: 50,

			/**
			 * The total number of departments.
			 * @type {number}
			 */
			totalDepartments: 0,

			/**
			 * Is used to check if the departments are loading.
			 * @type {boolean}
			 */
			loadingDepartments: false,

			/**
			 * The error for the API controller consists of a message and a status.
			 * @type {Object}
			 */
			apiError: {
				message: null,
				status: null
			}
		}
	},

	computed: {
		/**
		 * Gets the heading icon.
		 * @return {string} The heading icon.
		 */
		headingIcon: function () {
			return "<i class='fa fa-building'></i>"
		},

		/**
		 * Checks if the user can create.
		 * @return {boolean} True if the user can create.
		 */
		userCanCreate: function () {
			return this.permissions[0].user ? true : false
		},

		/**
		 * Checks if the user can edit.
		 * @return {boolean} True if the user can edit.
		 */
		userCanEdit: function () {
			return this.permissions[0].user ? true : false
		}
	},

	methods: {
		/**
		 * Creates a new department.
		 */
		createNewDepartment: function () {
			window.location.href = "/directory/create"
		},

		/**
		 * When the departments search input is changed.
		 * @param evt
		 */
		handleSearchInput: function (evt) {
			this.searchTerm = evt.target.value
			if (this.searchTerm.length > 2) {
				this.searchDepartments()
			}
		},

		/**
		 * When a department is selected from the search results
		 * @param evt
		 */
		handleDepartmentSelected: function (evt) {
			if (this.userCanEdit) {
				window.location.href = "/directory/" + evt.id
			}
		},

		/**
		 * When paginator items per page is changed.
		 * @param itemsPerPage
		 */
		handleItemsPerPageChanged: function (itemsPerPage) {
			this.itemsPerPage = itemsPerPage
			this.fetchDepartments()
		},

		/**
		 * When paginator page is changed.
		 * @param currentPage
		 */
		handlePageChanged: function (currentPage) {
			this.currentPage = currentPage
			this.fetchDepartments()
		},

		/**
		 * Gets the departments.
		 */
		fetchDepartments: function () {
			let self = this // "this" loses scope within Axios.

			this.loadingDepartments = true
			this.departments = []

			/* Ajax (Axios) Submission */
			axios
				.get(
					`/api/directory/list?page=${this.currentPage}&limit=${this.itemsPerPage}&search=${this.searchTerm}`
				)
				.then(function (response) {
					// Success.
					self.totalDepartments = response.data.totalRows
					self.departments = response.data.departments
				})
				.catch(function (error) {
					// Failure.
					self.apiError.status = error.response.status

					switch (error.response.status) {
						case 403:
							self.apiError.message =
								"You do not have sufficient privileges to retrieve departments."
							break
						case 404:
							self.apiError.message = "Departments were not found."
							break
						case 500:
							self.apiError.message = "An internal error occurred."
							break
						default:
							self.apiError.message = "An error occurred."
							break
					}
				})

			self.loadingDepartments = false
		},

		/**
		 * Searches for departments.
		 */
		searchDepartments: function () {
			let self = this

			axios
				.get(`/api/directory/search?searchterm=${this.searchTerm}`)
				.then(function (response) {
					// Success.
					self.searchResults = response.data
				})
				.catch(function (error) {
					// Failure.
					console.log("Error searching department directory:", error)
				})
		}
	}
}
</script>
