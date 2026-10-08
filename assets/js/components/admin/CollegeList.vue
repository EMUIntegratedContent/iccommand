<template>
	<div>
		<heading>
			<span>Manage Colleges</span>
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
				Colleges are shared across IC Command and are tied to the
				<strong>Programs</strong> and <strong>Scholarships</strong> apps: their
				college dropdowns read from this list, and every department belongs to
				a college. A college can't be deleted while any department, program or
				scholarship still uses it.
			</p>
		</div>
		<div class="card">
			<div class="card-header">
				<div class="row">
					<div class="col-md-6">
						<h4>
							Colleges
							<span v-if="!loadingColleges" class="badge badge-primary ml-2">{{
								totalColleges
							}}</span>
						</h4>
					</div>
					<div class="col-md-6 text-right">
						<a href="/admin/colleges/create" class="btn btn-success">
							<i class="fa fa-plus"></i> Add College
						</a>
					</div>
				</div>
			</div>
			<div class="card-body">
				<div class="mb-3">
					<label for="collegeSearch" class="form-label">Search Colleges</label>
					<div class="position-relative">
						<input
							type="text"
							class="form-control"
							id="collegeSearch"
							v-model="searchTerm"
							placeholder="Search colleges"
							@keyup.enter="handleSearch"
							style="padding-right: 40px"
						/>
						<button
							type="button"
							class="btn btn-link position-absolute"
							style="right: 0; top: 0; height: 100%; border: none; padding: 0 12px"
							@click="handleSearch"
							:disabled="loadingColleges"
						>
							<i class="fa fa-search"></i>
						</button>
						<button
							v-if="searchTerm"
							type="button"
							class="btn btn-link position-absolute"
							style="right: 0; top: 0; height: 100%; border: none; margin: 0 40px"
							@click="clearSearch"
							:disabled="loadingColleges"
						>
							<i class="fa fa-remove"></i>
						</button>
					</div>
				</div>
				<div v-if="loadingColleges">
					<p style="text-align: center">
						<img src="/images/loading.gif" alt="Loading..." />
					</p>
				</div>
				<div v-else-if="colleges.length === 0" class="alert alert-info">
					No colleges found.
				</div>
				<div v-else class="table-responsive">
					<table class="table table-hover table-sm">
						<thead>
							<tr>
								<th scope="col">College</th>
								<th scope="col">Departments</th>
								<th scope="col">Programs</th>
								<th scope="col">Scholarships</th>
								<th scope="col">Actions</th>
							</tr>
						</thead>
						<tbody>
							<tr v-for="college in colleges" :key="college.id">
								<th scope="row">
									<a :href="'/admin/colleges/' + college.id + '/edit'">{{
										college.college
									}}</a>
								</th>
								<td>{{ college.department_count }}</td>
								<td>{{ college.program_count }}</td>
								<td>{{ college.scholarship_count }}</td>
								<td>
									<a :href="'/admin/colleges/' + college.id + '/edit'"
										><font-awesome-icon icon="fa-solid fa-pen-to-square"
									/></a>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
				<external-paginator
					v-show="!loadingColleges && colleges.length > 0"
					:items="colleges"
					:ext-curr-pg="currentPage"
					:ext-items-per-pg="itemsPerPage"
					:total-recs="totalColleges"
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
		this.fetchColleges()
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

			/**
			 * The colleges on the current page.
			 * @type {Array}
			 */
			colleges: [],

			currentPage: 1,
			itemsPerPage: 50,
			loadingColleges: true,
			searchTerm: "",
			totalColleges: 0
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
		 * Gets the current page of colleges with their usage counts.
		 */
		fetchColleges: function () {
			let self = this
			self.loadingColleges = true

			axios
				.get(
					"/api/admin/colleges?page=" +
						self.currentPage +
						"&limit=" +
						self.itemsPerPage +
						"&searchterm=" +
						encodeURIComponent(self.searchTerm)
				)
				.then(function (response) {
					// Success.
					self.colleges = response.data.colleges
					self.totalColleges = response.data.totalRows
					self.loadingColleges = false
				})
				.catch(function (error) {
					// Failure.
					self.apiError.status = error.response ? error.response.status : 500
					switch (self.apiError.status) {
						case 403:
							self.apiError.message =
								"You do not have sufficient privileges to retrieve colleges."
							break
						case 404:
							self.apiError.message = "Colleges were not found."
							break
						case 500:
							self.apiError.message = "An internal error occurred."
							break
						default:
							self.apiError.message = "An error occurred."
							break
					}
					self.loadingColleges = false
				})
		},

		handleItemsPerPageChanged: function (itemsPerPage) {
			this.itemsPerPage = itemsPerPage
			this.currentPage = 1
			this.fetchColleges()
		},

		handlePageChanged: function (currentPage) {
			this.currentPage = currentPage
			this.fetchColleges()
		},

		handleSearch: function () {
			this.currentPage = 1
			this.fetchColleges()
		}
	}
}
</script>
