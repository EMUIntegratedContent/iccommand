<template>
	<div>
		<heading>
			<span>Social Media Links</span>
		</heading>

		<div class="row">
			<div class="col-md-12">
				<div class="card">
					<div class="card-header">
						<div class="row">
							<div class="col-md-6">
								<h4>Entities</h4>
							</div>
							<div class="col-md-6 text-right">
								<button
									v-if="userCanCreate"
									class="btn btn-success"
									@click="createNewEntity"
								>
									<i class="fa fa-plus"></i> Add Entity
								</button>
							</div>
						</div>
					</div>
					<div class="card-body">
						<div>
							<label for="searchSocialMedia" class="sr-only"
								>Search entities</label
							>
							<VueMultiselect
								:options="searchResults"
								:multiple="false"
								:clear-on-select="true"
								placeholder="Search entities (type at least 3 characters)"
								label="name"
								track-by="id"
								id="searchSocialMedia"
								class="form-control"
								style="padding: 0"
								name="searchSocialMedia"
								@input="handleSearchInput"
								@select="handleEntitySelected"
							>
							</VueMultiselect>
						</div>
						<div v-if="!loadingEntities" class="table-responsive">
							<table class="table table-striped">
								<thead>
									<tr>
										<th>Name</th>
										<th class="text-center">Facebook</th>
										<th class="text-center">X</th>
										<th class="text-center">YouTube</th>
										<th class="text-center">Instagram</th>
										<th class="text-center">LinkedIn</th>
										<th class="text-center">TikTok</th>
										<th v-if="userCanEdit">Actions</th>
									</tr>
								</thead>
								<tbody>
									<tr v-for="entity in entities" :key="entity.id">
										<td>
											<a
												v-if="userCanEdit"
												:href="'/social-media/' + entity.id + '/edit'"
												>{{ entity.name }}</a
											>
											<span v-else>{{ entity.name }}</span>
										</td>
										<td class="text-center">
											<a v-if="entity.facebook_url" :href="entity.facebook_url" target="_blank" rel="noopener">
												<i class="fa fa-check text-success"></i>
											</a>
											<span v-else class="text-muted">&mdash;</span>
										</td>
										<td class="text-center">
											<a v-if="entity.x_url" :href="entity.x_url" target="_blank" rel="noopener">
												<i class="fa fa-check text-success"></i>
											</a>
											<span v-else class="text-muted">&mdash;</span>
										</td>
										<td class="text-center">
											<a v-if="entity.youtube_url" :href="entity.youtube_url" target="_blank" rel="noopener">
												<i class="fa fa-check text-success"></i>
											</a>
											<span v-else class="text-muted">&mdash;</span>
										</td>
										<td class="text-center">
											<a v-if="entity.instagram_url" :href="entity.instagram_url" target="_blank" rel="noopener">
												<i class="fa fa-check text-success"></i>
											</a>
											<span v-else class="text-muted">&mdash;</span>
										</td>
										<td class="text-center">
											<a v-if="entity.linkedin_url" :href="entity.linkedin_url" target="_blank" rel="noopener">
												<i class="fa fa-check text-success"></i>
											</a>
											<span v-else class="text-muted">&mdash;</span>
										</td>
										<td class="text-center">
											<a v-if="entity.tiktok_url" :href="entity.tiktok_url" target="_blank" rel="noopener">
												<i class="fa fa-check text-success"></i>
											</a>
											<span v-else class="text-muted">&mdash;</span>
										</td>
										<td v-if="userCanEdit">
											<a :href="'/social-media/' + entity.id + '/edit'"
												><font-awesome-icon icon="fa-solid fa-pen-to-square" />
											</a>
										</td>
									</tr>
									<tr v-if="entities.length === 0">
										<td colspan="8" class="text-center text-muted">
											No entities found.
										</td>
									</tr>
								</tbody>
							</table>
						</div>
						<div v-else class="text-center">
							<img src="/images/loading.gif" alt="Loading..." />
						</div>
						<external-paginator
							v-show="!loadingEntities"
							:items="entities"
							:ext-curr-pg="currentPage"
							:ext-items-per-pg="itemsPerPage"
							:total-recs="totalEntities"
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
		this.fetchEntities()
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
			 * The social media entities that are fetched.
			 * @type {Array.<Object>}
			 */
			entities: [],

			/**
			 * The search results for entities.
			 * @type {Array.<Object>}
			 */
			searchResults: [],

			/**
			 * The search term for entities.
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
			 * The total number of entities.
			 * @type {number}
			 */
			totalEntities: 0,

			/**
			 * Is used to check if the entities are loading.
			 * @type {boolean}
			 */
			loadingEntities: false,

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
		 * Navigates to the create page.
		 */
		createNewEntity: function () {
			window.location.href = "/social-media/create"
		},

		/**
		 * When the search input is changed.
		 * @param evt
		 */
		handleSearchInput: function (evt) {
			this.searchTerm = evt.target.value
			if (this.searchTerm.length > 2) {
				this.searchEntities()
			}
		},

		/**
		 * When an entity is selected from the search results.
		 * @param evt
		 */
		handleEntitySelected: function (evt) {
			if (this.userCanEdit) {
				window.location.href = "/social-media/" + evt.id + "/edit"
			}
		},

		/**
		 * When paginator items per page is changed.
		 * @param itemsPerPage
		 */
		handleItemsPerPageChanged: function (itemsPerPage) {
			this.itemsPerPage = itemsPerPage
			this.fetchEntities()
		},

		/**
		 * When paginator page is changed.
		 * @param currentPage
		 */
		handlePageChanged: function (currentPage) {
			this.currentPage = currentPage
			this.fetchEntities()
		},

		/**
		 * Gets the entities.
		 */
		fetchEntities: function () {
			let self = this // "this" loses scope within Axios.

			this.loadingEntities = true
			this.entities = []

			/* Ajax (Axios) Submission */
			axios
				.get(
					`/api/social-media/list?page=${this.currentPage}&limit=${this.itemsPerPage}&search=${this.searchTerm}`
				)
				.then(function (response) {
					// Success.
					self.totalEntities = response.data.totalRows
					self.entities = response.data.socialMedia
				})
				.catch(function (error) {
					// Failure.
					self.apiError.status = error.response.status

					switch (error.response.status) {
						case 403:
							self.apiError.message =
								"You do not have sufficient privileges to retrieve entities."
							break
						case 404:
							self.apiError.message = "Entities were not found."
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
					self.loadingEntities = false
				})
		},

		/**
		 * Searches for entities by name.
		 */
		searchEntities: function () {
			let self = this

			axios
				.get(`/api/social-media/search?searchterm=${this.searchTerm}`)
				.then(function (response) {
					// Success.
					self.searchResults = response.data
				})
				.catch(function (error) {
					// Failure.
					console.log("Error searching social media entities:", error)
				})
		}
	}
}
</script>
