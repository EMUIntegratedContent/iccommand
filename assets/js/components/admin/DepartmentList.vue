<template>
	<div>
		<heading>
			<span>Manage Departments</span>
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
				Departments are shared across IC Command and are tied to the
				<strong>Programs</strong> and <strong>Scholarships</strong> apps: their
				department dropdowns read from this list. Each department belongs to a
				college. A department can't be deleted while any program or scholarship
				still uses it.
			</p>
		</div>
		<div class="card">
			<div class="card-header">
				<div class="row">
					<div class="col-md-6">
						<h4>
							Departments
							<span v-if="!loadingDepartments" class="badge badge-primary ml-2">{{
								totalDepartments
							}}</span>
						</h4>
					</div>
					<div class="col-md-6 text-right">
						<a href="/admin/departments/create" class="btn btn-success">
							<i class="fa fa-plus"></i> Add Department
						</a>
					</div>
				</div>
			</div>
			<div class="card-body">
				<div class="mb-3">
					<label for="departmentSearch" class="form-label"
						>Search Departments</label
					>
					<div class="position-relative">
						<input
							type="text"
							class="form-control"
							id="departmentSearch"
							v-model="searchTerm"
							placeholder="Search departments or colleges"
							@keyup.enter="handleSearch"
							style="padding-right: 40px"
						/>
						<button
							type="button"
							class="btn btn-link position-absolute"
							style="right: 0; top: 0; height: 100%; border: none; padding: 0 12px"
							@click="handleSearch"
							:disabled="loadingDepartments"
						>
							<i class="fa fa-search"></i>
						</button>
						<button
							v-if="searchTerm"
							type="button"
							class="btn btn-link position-absolute"
							style="right: 0; top: 0; height: 100%; border: none; margin: 0 40px"
							@click="clearSearch"
							:disabled="loadingDepartments"
						>
							<i class="fa fa-remove"></i>
						</button>
					</div>
				</div>
				<div v-if="loadingDepartments">
					<p style="text-align: center">
						<img src="/images/loading.gif" alt="Loading..." />
					</p>
				</div>
				<div v-else-if="departments.length === 0" class="alert alert-info">
					No departments found.
				</div>
				<div v-else class="table-responsive">
					<table class="table table-hover table-sm">
						<thead>
							<tr>
								<th scope="col">Department</th>
								<th scope="col">College</th>
								<th scope="col">Programs</th>
								<th scope="col">Scholarships</th>
								<th scope="col">Actions</th>
							</tr>
						</thead>
						<tbody>
							<tr v-for="department in departments" :key="department.id">
								<th scope="row">
									<a :href="'/admin/departments/' + department.id + '/edit'">{{
										department.department
									}}</a>
								</th>
								<td>
									<a :href="'/admin/colleges/' + department.college_id + '/edit'">{{
										department.college
									}}</a>
								</td>
								<td>{{ department.program_count }}</td>
								<td>{{ department.scholarship_count }}</td>
								<td>
									<a :href="'/admin/departments/' + department.id + '/edit'"
										><font-awesome-icon icon="fa-solid fa-pen-to-square"
									/></a>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
				<external-paginator
					v-show="!loadingDepartments && departments.length > 0"
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
</template>

<script>
import Heading from "../utils/Heading.vue"
import ExternalPaginator from "../utils/ExternalPaginator.vue"

export default {
	created() {
		this.fetchDepartments()
	},

	components: {
		Heading,
		ExternalPaginator
	},

	data: function () {
		return {
			/**
			 * The error for the API controller consists of a message and a status.
			 * @type {Object}
			 */
			apiError: {
				message: null,
				status: null
			},

			currentPage: 1,

			/**
			 * The departments on the current page.
			 * @type {Array}
			 */
			departments: [],

			itemsPerPage: 50,
			loadingDepartments: true,
			searchTerm: "",
			totalDepartments: 0
		}
	},

	methods: {
		/**
		 * Clears the search box and reloads the full list.
		 */
		clearSearch: function () {
			this.searchTerm = ""
			this.handleSearch()
		},

		/**
		 * Gets the current page of departments with their colleges and usage counts.
		 */
		fetchDepartments: function () {
			let self = this
			self.loadingDepartments = true

			axios
				.get(
					"/api/admin/departments?page=" +
						self.currentPage +
						"&limit=" +
						self.itemsPerPage +
						"&searchterm=" +
						encodeURIComponent(self.searchTerm)
				)
				.then(function (response) {
					// Success.
					self.departments = response.data.departments
					self.totalDepartments = response.data.totalRows
					self.loadingDepartments = false
				})
				.catch(function (error) {
					// Failure.
					self.apiError.status = error.response ? error.response.status : 500
					switch (self.apiError.status) {
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
					self.loadingDepartments = false
				})
		},

		handleItemsPerPageChanged: function (itemsPerPage) {
			this.itemsPerPage = itemsPerPage
			this.currentPage = 1
			this.fetchDepartments()
		},

		handlePageChanged: function (currentPage) {
			this.currentPage = currentPage
			this.fetchDepartments()
		},

		handleSearch: function () {
			this.currentPage = 1
			this.fetchDepartments()
		}
	}
}
</script>
