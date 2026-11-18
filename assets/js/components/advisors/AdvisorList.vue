<template>
	<div>
		<heading>
			<span>Manage Advisors</span>
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
				<template v-if="scholarshipAdvisorsEnabled">
					Advisors are shared across IC Command: <strong>programs</strong> and
					<strong>scholarships</strong> can each have any number of them, chosen
					from this list on their own forms.
				</template>
				<template v-else>
					Advisors are shared across IC Command: <strong>programs</strong> can
					each have any number of them, chosen from this list on the program form.
				</template> An advisor's name, email, phone, URL
				and building name appear on the public API for those records. An
				advisor can't be deleted while anything still uses it.
			</p>
		</div>
		<div class="card">
			<div class="card-header">
				<div class="row">
					<div class="col-md-6">
						<h4>
							Advisors
							<span v-if="!loadingAdvisors" class="badge badge-primary ml-2">{{
								totalAdvisors
							}}</span>
						</h4>
					</div>
					<div class="col-md-6 text-right">
						<a v-if="userCanEdit" href="/advisors/create" class="btn btn-success">
							<i class="fa fa-plus"></i> Add Advisor
						</a>
					</div>
				</div>
			</div>
			<div class="card-body">
				<div class="mb-3">
					<label for="advisorSearch" class="form-label">Search Advisors</label>
					<div class="position-relative">
						<input
							type="text"
							class="form-control"
							id="advisorSearch"
							v-model="searchTerm"
							placeholder="Search names, organizations or emails"
							@keyup.enter="handleSearch"
							style="padding-right: 40px"
						/>
						<button
							type="button"
							class="btn btn-link position-absolute"
							style="right: 0; top: 0; height: 100%; border: none; padding: 0 12px"
							@click="handleSearch"
							:disabled="loadingAdvisors"
						>
							<i class="fa fa-search"></i>
						</button>
						<button
							v-if="searchTerm"
							type="button"
							class="btn btn-link position-absolute"
							style="right: 0; top: 0; height: 100%; border: none; margin: 0 40px"
							@click="clearSearch"
							:disabled="loadingAdvisors"
						>
							<i class="fa fa-remove"></i>
						</button>
					</div>
				</div>
				<div v-if="loadingAdvisors">
					<p style="text-align: center">
						<img src="/images/loading.gif" alt="Loading..." />
					</p>
				</div>
				<div v-else-if="advisors.length === 0" class="alert alert-info">
					No advisors found.
				</div>
				<div v-else class="table-responsive">
					<table class="table table-hover table-sm">
						<thead>
							<tr>
								<th scope="col">Name</th>
								<th scope="col">Email</th>
								<th scope="col">Phone</th>
								<th scope="col">Building</th>
								<th scope="col">Programs</th>
								<th v-if="scholarshipAdvisorsEnabled" scope="col">Scholarships</th>
								<th scope="col">Actions</th>
							</tr>
						</thead>
						<tbody>
							<tr v-for="advisor in advisors" :key="advisor.id">
								<th scope="row">
									<a :href="advisorUrl(advisor)">{{
										advisor.display_name
									}}</a>
								</th>
								<td>{{ advisor.email }}</td>
								<td>{{ advisor.phone }}</td>
								<td>{{ advisor.building_name }}</td>
								<td>{{ advisor.program_count }}</td>
								<td v-if="scholarshipAdvisorsEnabled">{{ advisor.scholarship_count }}</td>
								<td>
									<a v-if="userCanEdit" :href="'/advisors/' + advisor.id + '/edit'"
										><font-awesome-icon icon="fa-solid fa-pen-to-square"
									/></a>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
				<external-paginator
					v-show="!loadingAdvisors && advisors.length > 0"
					:items="advisors"
					:ext-curr-pg="currentPage"
					:ext-items-per-pg="itemsPerPage"
					:total-recs="totalAdvisors"
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
import { SCHOLARSHIP_ADVISORS_ENABLED } from "@/utils/featureFlags"

export default {
	created() {
		this.fetchAdvisors()
	},

	components: {
		Heading,
		ExternalPaginator
	},

	props: {
		permissions: {
			type: Array,
			required: true
		}
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
			 * The advisors on the current page.
			 * @type {Array}
			 */
			advisors: [],

			currentPage: 1,
			itemsPerPage: 50,
			loadingAdvisors: true,
			searchTerm: "",
			totalAdvisors: 0
		}
	},

	computed: {
		/**
		 * @return {boolean} True if scholarships show on this list (see utils/featureFlags.js).
		 */
		scholarshipAdvisorsEnabled: function () {
			return SCHOLARSHIP_ADVISORS_ENABLED
		},

		/**
		 * @return {boolean} True if the user can add and edit advisors.
		 */
		userCanEdit: function () {
			return this.permissions[0].edit ? true : false
		}
	},

	methods: {
		/**
		 * The page an advisor's name links to: the edit page for editors, else the read-only page.
		 * @param {Object} advisor
		 * @return {string}
		 */
		advisorUrl: function (advisor) {
			return "/advisors/" + advisor.id + (this.userCanEdit ? "/edit" : "")
		},

		/**
		 * Clears the search box and reloads the full list.
		 */
		clearSearch: function () {
			this.searchTerm = ""
			this.handleSearch()
		},

		/**
		 * Gets the current page of advisors with their usage counts.
		 */
		fetchAdvisors: function () {
			let self = this
			self.loadingAdvisors = true

			axios
				.get(
					"/api/advisors?page=" +
						self.currentPage +
						"&limit=" +
						self.itemsPerPage +
						"&searchterm=" +
						encodeURIComponent(self.searchTerm)
				)
				.then(function (response) {
					// Success.
					self.advisors = response.data.advisors
					self.totalAdvisors = response.data.totalRows
					self.loadingAdvisors = false
				})
				.catch(function (error) {
					// Failure.
					self.apiError.status = error.response ? error.response.status : 500
					switch (self.apiError.status) {
						case 403:
							self.apiError.message =
								"You do not have sufficient privileges to retrieve advisors."
							break
						case 404:
							self.apiError.message = "Advisors were not found."
							break
						case 500:
							self.apiError.message = "An internal error occurred."
							break
						default:
							self.apiError.message = "An error occurred."
							break
					}
					self.loadingAdvisors = false
				})
		},

		handleItemsPerPageChanged: function (itemsPerPage) {
			this.itemsPerPage = itemsPerPage
			this.currentPage = 1
			this.fetchAdvisors()
		},

		handlePageChanged: function (currentPage) {
			this.currentPage = currentPage
			this.fetchAdvisors()
		},

		handleSearch: function () {
			this.currentPage = 1
			this.fetchAdvisors()
		}
	}
}
</script>
