<template>
	<div>
		<heading>
			<span>Manage Contacts</span>
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
				Contacts are shared across IC Command: <strong>programs</strong>,
				<strong>scholarships</strong> and <strong>buildings</strong> can each
				have any number of them, chosen from this list on their own forms. A
				contact's name, email, phone and URL appear on the public API for those
				records. A contact can't be deleted while anything still uses it.
			</p>
		</div>
		<div class="card">
			<div class="card-header">
				<div class="row">
					<div class="col-md-6">
						<h4>
							Contacts
							<span v-if="!loadingContacts" class="badge badge-primary ml-2">{{
								totalContacts
							}}</span>
						</h4>
					</div>
					<div class="col-md-6 text-right">
						<a v-if="userCanEdit" href="/contacts/create" class="btn btn-success">
							<i class="fa fa-plus"></i> Add Contact
						</a>
					</div>
				</div>
			</div>
			<div class="card-body">
				<div class="mb-3">
					<label for="contactSearch" class="form-label">Search Contacts</label>
					<div class="position-relative">
						<input
							type="text"
							class="form-control"
							id="contactSearch"
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
							:disabled="loadingContacts"
						>
							<i class="fa fa-search"></i>
						</button>
						<button
							v-if="searchTerm"
							type="button"
							class="btn btn-link position-absolute"
							style="right: 0; top: 0; height: 100%; border: none; margin: 0 40px"
							@click="clearSearch"
							:disabled="loadingContacts"
						>
							<i class="fa fa-remove"></i>
						</button>
					</div>
				</div>
				<div v-if="loadingContacts">
					<p style="text-align: center">
						<img src="/images/loading.gif" alt="Loading..." />
					</p>
				</div>
				<div v-else-if="contacts.length === 0" class="alert alert-info">
					No contacts found.
				</div>
				<div v-else class="table-responsive">
					<table class="table table-hover table-sm">
						<thead>
							<tr>
								<th scope="col">Name</th>
								<th scope="col">Type</th>
								<th scope="col">Email</th>
								<th scope="col">Phone</th>
								<th scope="col">Programs</th>
								<th scope="col">Scholarships</th>
								<th scope="col">Buildings</th>
								<th scope="col">Actions</th>
							</tr>
						</thead>
						<tbody>
							<tr v-for="contact in contacts" :key="contact.id">
								<th scope="row">
									<a :href="contactUrl(contact)">{{
										contact.display_name
									}}</a>
								</th>
								<td>{{ contact.type }}</td>
								<td>{{ contact.email }}</td>
								<td>{{ contact.phone }}</td>
								<td>{{ contact.program_count }}</td>
								<td>{{ contact.scholarship_count }}</td>
								<td>{{ contact.building_count }}</td>
								<td>
									<a v-if="userCanEdit" :href="'/contacts/' + contact.id + '/edit'"
										><font-awesome-icon icon="fa-solid fa-pen-to-square"
									/></a>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
				<external-paginator
					v-show="!loadingContacts && contacts.length > 0"
					:items="contacts"
					:ext-curr-pg="currentPage"
					:ext-items-per-pg="itemsPerPage"
					:total-recs="totalContacts"
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
		this.fetchContacts()
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
			 * The contacts on the current page.
			 * @type {Array}
			 */
			contacts: [],

			currentPage: 1,
			itemsPerPage: 50,
			loadingContacts: true,
			searchTerm: "",
			totalContacts: 0
		}
	},

	computed: {
		/**
		 * @return {boolean} True if the user can add and edit contacts.
		 */
		userCanEdit: function () {
			return this.permissions[0].edit ? true : false
		}
	},

	methods: {
		/**
		 * The page a contact's name links to: the edit page for editors, else the read-only page.
		 * @param {Object} contact
		 * @return {string}
		 */
		contactUrl: function (contact) {
			return "/contacts/" + contact.id + (this.userCanEdit ? "/edit" : "")
		},

		/**
		 * Clears the search box and reloads the full list.
		 */
		clearSearch: function () {
			this.searchTerm = ""
			this.handleSearch()
		},

		/**
		 * Gets the current page of contacts with their usage counts.
		 */
		fetchContacts: function () {
			let self = this
			self.loadingContacts = true

			axios
				.get(
					"/api/contacts?page=" +
						self.currentPage +
						"&limit=" +
						self.itemsPerPage +
						"&searchterm=" +
						encodeURIComponent(self.searchTerm)
				)
				.then(function (response) {
					// Success.
					self.contacts = response.data.contacts
					self.totalContacts = response.data.totalRows
					self.loadingContacts = false
				})
				.catch(function (error) {
					// Failure.
					self.apiError.status = error.response ? error.response.status : 500
					switch (self.apiError.status) {
						case 403:
							self.apiError.message =
								"You do not have sufficient privileges to retrieve contacts."
							break
						case 404:
							self.apiError.message = "Contacts were not found."
							break
						case 500:
							self.apiError.message = "An internal error occurred."
							break
						default:
							self.apiError.message = "An error occurred."
							break
					}
					self.loadingContacts = false
				})
		},

		handleItemsPerPageChanged: function (itemsPerPage) {
			this.itemsPerPage = itemsPerPage
			this.currentPage = 1
			this.fetchContacts()
		},

		handlePageChanged: function (currentPage) {
			this.currentPage = currentPage
			this.fetchContacts()
		},

		handleSearch: function () {
			this.currentPage = 1
			this.fetchContacts()
		}
	}
}
</script>
