<template>
	<div>
		<not-found v-if="is404 === true"></not-found>
		<div v-if="isDataLoaded === false">
			<p style="text-align: center">
				<img src="/images/loading.gif" alt="Loading..." />
			</p>
		</div>
		<div
			v-if="apiError.status"
			class="alert alert-danger fade show"
			role="alert"
		>
			{{ apiError.message }}
		</div>
		<div
			v-if="isDeleted === true"
			class="alert alert-info fade show"
			role="alert"
		>
			Department "{{ record.department }}" has been deleted. You will now be
			redirected to the department list page.
		</div>

		<!-- Main Area -->
		<div v-if="isDataLoaded === true && isDeleted === false && is404 === false">
			<heading>
				<span v-if="!itemExists">Create New Department</span>
				<span v-else>Department: {{ record.department }}</span>
			</heading>
			<div class="btn-group" role="group" aria-label="form navigation buttons">
				<button
					v-if="itemExists && permissions[0].user"
					type="button"
					class="btn btn-info pull-right"
					@click="toggleEdit"
				>
					<span v-html="lockIcon"></span>
				</button>
			</div>
			<div class="pt-2" id="departmentTabContent">
				<VeeForm
					class="form"
					v-slot="{ submitForm, errors, meta }"
					@submit="submitDepartment"
					:validation-schema="departmentSchema"
				>
					<fieldset>
						<legend>Basic Information</legend>
						<div class="form-group">
							<label>Department Name <span class="red">*</span></label>
							<Field
								name="department"
								type="text"
								class="form-control"
								:class="{
									'is-invalid': errors.department,
									'form-control-plaintext': !userCanEdit || !isEditMode
								}"
								:readonly="!userCanEdit || !isEditMode"
								v-model="record.department"
								@update:modelValue="formDirty = true"
							>
							</Field>
							<div class="invalid-feedback">
								{{ errors.department }}
							</div>
						</div>
						<div class="form-group">
							<label>Search Terms</label>
							<div
								v-if="!userCanEdit || !isEditMode"
								class="search-terms-display"
							>
								<span v-if="searchTermsArray.length === 0" class="text-muted"
									>No search terms</span
								>
								<span
									v-for="(term, index) in searchTermsArray"
									:key="index"
									class="badge badge-secondary mr-1 mb-1"
								>
									{{ term }}
								</span>
							</div>
							<div v-else class="search-terms-input">
								<div class="search-terms-badges mb-2">
									<span
										v-for="(term, index) in searchTermsArray"
										:key="index"
										class="badge badge-primary mr-1 mb-1"
									>
										{{ term }}
										<button
											type="button"
											class="close ml-1"
											@click="removeSearchTerm(index)"
											aria-label="Remove search term"
										>
											<span aria-hidden="true">&times;</span>
										</button>
									</span>
								</div>
								<div class="input-group">
									<input
										type="text"
										class="form-control"
										:class="{ 'is-invalid': errors.searchTerms }"
										v-model="newSearchTerm"
										@keydown.enter.prevent="addSearchTerm"
										placeholder="Type a search term and press Enter to add"
									/>
									<div class="input-group-append">
										<button
											type="button"
											class="btn btn-outline-secondary"
											@click="addSearchTerm"
											:disabled="!newSearchTerm.trim()"
										>
											Add
										</button>
									</div>
								</div>
								<div class="invalid-feedback">
									{{ errors.searchTerms }}
								</div>
								<small class="form-text text-muted">
									Press Enter to add a search term. Click the × to remove terms.
								</small>
							</div>
						</div>

						<div class="form-group">
							<template v-if="isEditMode">
								<Field
									name="mapBuilding"
									type="hidden"
									v-model="record.mapBuilding"
								>
									<!-- for validation purposes only -->
								</Field>
								<label for="selectbldg" class="mt-2">Associated Building</label>
								<VueMultiselect
									v-model="selectedBuilding"
									:options="buildings"
									:multiple="false"
									:clear-on-select="true"
									placeholder="Select a building"
									label="name"
									track-by="id"
									id="selectbldg"
									:disabled="!userCanEdit || !isEditMode"
									:class="{ 'is-invalid': errors.mapBuilding }"
									class="form-control"
									style="padding: 0"
									@update:modelValue="formDirty = true"
								>
								</VueMultiselect>
								<div class="invalid-feedback">
									{{ errors.mapBuilding }}
								</div>
							</template>
							<template v-else>
								<div class="form-group">
									<label>Associated Building</label>
									<Field
										v-if="selectedBuilding"
										v-model="selectedBuilding.name"
										name="building"
										type="text"
										class="form-control"
										:class="{
											'is-invalid': errors.mapBuilding,
											'form-control-plaintext': !userCanEdit || !isEditMode
										}"
										:readonly="true"
									>
									</Field>
									<p v-else>None</p>
								</div>
							</template>
						</div>
					</fieldset>

					<fieldset>
						<legend>Address Information</legend>
						<div class="form-group">
							<label>Address Line 1</label>
							<Field
								name="address1"
								type="text"
								class="form-control"
								:class="{
									'is-invalid': errors.address1,
									'form-control-plaintext': !userCanEdit || !isEditMode
								}"
								:readonly="!userCanEdit || !isEditMode"
								v-model="record.address1"
								@update:modelValue="formDirty = true"
							>
							</Field>
							<div class="invalid-feedback">
								{{ errors.address1 }}
							</div>
						</div>
						<div class="form-group">
							<label>Address Line 2</label>
							<Field
								name="address2"
								type="text"
								class="form-control"
								:class="{
									'is-invalid': errors.address2,
									'form-control-plaintext': !userCanEdit || !isEditMode
								}"
								:readonly="!userCanEdit || !isEditMode"
								v-model="record.address2"
								@update:modelValue="formDirty = true"
							>
							</Field>
							<div class="invalid-feedback">
								{{ errors.address2 }}
							</div>
						</div>
						<div class="row">
							<div class="col-md-4">
								<div class="form-group">
									<label>City</label>
									<Field
										name="city"
										type="text"
										class="form-control"
										:class="{
											'is-invalid': errors.city,
											'form-control-plaintext': !userCanEdit || !isEditMode
										}"
										:readonly="!userCanEdit || !isEditMode"
										v-model="record.city"
										@update:modelValue="formDirty = true"
									>
									</Field>
									<div class="invalid-feedback">
										{{ errors.city }}
									</div>
								</div>
							</div>
							<div class="col-md-4">
								<div class="form-group">
									<label>State</label>
									<Field
										name="state"
										type="text"
										class="form-control"
										:class="{
											'is-invalid': errors.state,
											'form-control-plaintext': !userCanEdit || !isEditMode
										}"
										:readonly="!userCanEdit || !isEditMode"
										v-model="record.state"
										maxlength="2"
										@update:modelValue="formDirty = true"
									>
									</Field>
									<div class="invalid-feedback">
										{{ errors.state }}
									</div>
								</div>
							</div>
							<div class="col-md-4">
								<div class="form-group">
									<label>ZIP Code</label>
									<Field
										name="zip"
										type="text"
										class="form-control"
										:class="{
											'is-invalid': errors.zip,
											'form-control-plaintext': !userCanEdit || !isEditMode
										}"
										:readonly="!userCanEdit || !isEditMode"
										v-model="record.zip"
										maxlength="10"
										@update:modelValue="formDirty = true"
									>
									</Field>
									<div class="invalid-feedback">
										{{ errors.zip }}
									</div>
								</div>
							</div>
						</div>
						<div class="form-group">
							<div class="form-check">
								<input
									type="checkbox"
									class="form-check-input"
									:disabled="!userCanEdit || !isEditMode"
									v-model="record.onCampus"
									id="onCampus"
									@change="formDirty = true"
								/>
								<label class="form-check-label" for="onCampus">
									On Campus
								</label>
							</div>
						</div>
					</fieldset>

					<fieldset>
						<legend>Contact Information</legend>
						<div class="row">
							<div class="col-md-6">
								<div class="form-group">
									<label>Phone</label>
									<Field
										name="phone"
										type="text"
										class="form-control"
										:class="{
											'is-invalid': errors.phone,
											'form-control-plaintext': !userCanEdit || !isEditMode
										}"
										:readonly="!userCanEdit || !isEditMode"
										v-model="record.phone"
										@update:modelValue="formDirty = true"
									>
									</Field>
									<div class="invalid-feedback">
										{{ errors.phone }}
									</div>
								</div>
							</div>
							<div class="col-md-6">
								<div class="form-group">
									<label>Alternate Phone</label>
									<Field
										name="phoneAlt"
										type="text"
										class="form-control"
										:class="{
											'is-invalid': errors.phoneAlt,
											'form-control-plaintext': !userCanEdit || !isEditMode
										}"
										:readonly="!userCanEdit || !isEditMode"
										v-model="record.phoneAlt"
										@update:modelValue="formDirty = true"
									>
									</Field>
									<div class="invalid-feedback">
										{{ errors.phoneAlt }}
									</div>
								</div>
							</div>
						</div>
						<div class="form-group">
							<label>Fax</label>
							<Field
								name="fax"
								type="text"
								class="form-control"
								:class="{
									'is-invalid': errors.fax,
									'form-control-plaintext': !userCanEdit || !isEditMode
								}"
								:readonly="!userCanEdit || !isEditMode"
								v-model="record.fax"
								@update:modelValue="formDirty = true"
							>
							</Field>
							<div class="invalid-feedback">
								{{ errors.fax }}
							</div>
						</div>
						<div class="form-group">
							<label>Email</label>
							<Field
								name="email"
								type="email"
								class="form-control"
								:class="{
									'is-invalid': errors.email,
									'form-control-plaintext': !userCanEdit || !isEditMode
								}"
								:readonly="!userCanEdit || !isEditMode"
								v-model="record.email"
								@update:modelValue="formDirty = true"
							>
							</Field>
							<div class="invalid-feedback">
								{{ errors.email }}
							</div>
						</div>
						<div class="form-group">
							<label>Website</label>
							<Field
								name="website"
								type="url"
								class="form-control"
								:class="{
									'is-invalid': errors.website,
									'form-control-plaintext': !userCanEdit || !isEditMode
								}"
								:readonly="!userCanEdit || !isEditMode"
								v-model="record.website"
								@update:modelValue="formDirty = true"
							>
							</Field>
							<div class="invalid-feedback">
								{{ errors.website }}
							</div>
						</div>
						<div class="form-group">
							<label>Faculty List URL</label>
							<Field
								name="facultyList"
								type="url"
								class="form-control"
								:class="{
									'is-invalid': errors.facultyList,
									'form-control-plaintext': !userCanEdit || !isEditMode
								}"
								:readonly="!userCanEdit || !isEditMode"
								v-model="record.facultyList"
								@update:modelValue="formDirty = true"
							>
							</Field>
							<div class="invalid-feedback">
								{{ errors.facultyList }}
							</div>
						</div>
						<div class="form-group">
							<label>Staff List URL</label>
							<Field
								name="staffList"
								type="url"
								class="form-control"
								:class="{
									'is-invalid': errors.staffList,
									'form-control-plaintext': !userCanEdit || !isEditMode
								}"
								:readonly="!userCanEdit || !isEditMode"
								v-model="record.staffList"
								@update:modelValue="formDirty = true"
							>
							</Field>
							<div class="invalid-feedback">
								{{ errors.staffList }}
							</div>
						</div>
					</fieldset>

					<div
						v-if="Object.keys(errors).length && isEditMode"
						class="alert alert-danger fade show"
						role="alert"
					>
						Please fix all errors before submitting:
						<ul>
							<li v-for="error in errors">
								<strong>{{ error }}</strong>
							</li>
						</ul>
					</div>
					<div
						v-if="success"
						class="alert alert-success fade show"
						role="alert"
					>
						{{ successMessage }}
					</div>
					<div
						v-if="isDeleteError === true"
						class="alert alert-danger fade show"
						role="alert"
					>
						There was an error deleting this department.
					</div>

					<!-- Action Buttons -->
					<div
						v-if="userCanEdit && isEditMode"
						aria-label="action buttons"
						class="mb-4"
					>
						<p v-if="formDirty" class="red">You have unsaved changes.</p>
						<p v-if="isSaveFailed" class="red">Error saving this department.</p>
						<button class="btn btn-success" type="submit">
							<i class="fa fa-save fa-2x"></i>
						</button>
						<button
							v-if="itemExists && this.permissions[0].admin"
							type="button"
							class="btn btn-danger ml-4"
							data-toggle="modal"
							data-target="#deleteModal"
						>
							<i class="fa fa-trash fa-2x"></i>
						</button>
					</div>
				</VeeForm>
			</div>
		</div>

		<!-- Delete Modal -->
		<department-delete-modal
			v-if="itemExists"
			:department="record"
			@itemDeleted="markItemDeleted"
			@itemDeleteError="markItemDeleteError"
		></department-delete-modal>
	</div>
</template>

<style scoped>
.search-terms-badges .badge {
	font-size: 0.875rem;
	padding: 0.375rem 0.75rem;
}

.search-terms-badges .badge .close {
	font-size: 1rem;
	line-height: 1;
	color: inherit;
	opacity: 0.7;
	background: none;
	border: none;
	padding: 0;
	margin-left: 0.25rem;
}

.search-terms-badges .badge .close:hover {
	opacity: 1;
}

.search-terms-display .badge {
	font-size: 0.875rem;
	padding: 0.375rem 0.75rem;
	background-color: #6c757d;
}
</style>

<script>
import Heading from "../utils/Heading.vue"
import VueMultiselect from "vue-multiselect"
import DepartmentDeleteModal from "./DepartmentDeleteModal.vue"
import NotFound from "../utils/NotFound.vue"
import { Field, Form as VeeForm } from "vee-validate"
import * as Yup from "yup"

const STATUS_SAVE_FAILED = 3

export default {
	created() {
		// Detect if the form should be in edit mode from the start; default is false.
		if (this.startMode == "edit") {
			this.isEditMode = true
		}

		if (this.itemExists === false) {
			// Set up for new department
			this.isDataLoaded = true
		} else {
			// Fetch the existing record using the property itemId.
			this.fetchDepartment(this.itemId)
		}

		// Fetch buildings for the dropdown
		this.fetchBuildings()
	},

	components: {
		Heading,
		VueMultiselect,
		DepartmentDeleteModal,
		NotFound,
		Field,
		VeeForm,
		Yup
	},

	props: {
		itemExists: {
			type: Boolean,
			required: true
		},

		itemId: {
			type: String,
			required: false
		},

		newForm: {
			default: false
		},

		permissions: {
			type: Array,
			required: true
		},

		startMode: {
			type: String,
			required: false
		}
	},

	data: function () {
		return {
			currentStatus: null,
			/* **************************** Error Data **************************** */

			/**
			 * The error for the API controller consists of a message and a status.
			 * @type {Object}
			 */
			apiError: {
				message: null,
				status: null
			},

			/**
			 * Is used to check if the request status is 404.
			 * @type {boolean}
			 */
			is404: false,

			/**
			 * Is used to check if there is an error in deleting the department.
			 */
			isDeleteError: false,

			/* *************************** Fetched Data *************************** */

			/**
			 * The buildings that are fetched for the dropdown.
			 * @type {Array.<MapBuilding>}
			 */
			buildings: [],

			/**
			 * Is used to check if the data is fetched and loaded.
			 * @type {boolean}
			 */
			isDataLoaded: false,

			/* ************************** Processing Data ************************* */

			/**
			 * Is used to check if the department is deleted.
			 * @type {boolean}
			 */
			isDeleted: false,

			/**
			 * Is used to check if the user is in edit mode.
			 * @type {boolean}
			 */
			isEditMode: false, // This is true if the forms are editable.

			/**
			 * The current department to be updated upon or created.
			 * @type {Object}
			 */
			record: {
				id: "",
				department: "",
				searchTerms: "",
				mapBuilding: null,
				address1: "",
				address2: "",
				city: "",
				state: "",
				zip: "",
				onCampus: true,
				phone: "",
				phoneAlt: "",
				fax: "",
				email: "",
				website: "",
				facultyList: "",
				staffList: ""
			},

			/**
			 * Is used to check if it is successful to make or update the department.
			 * @type {boolean}
			 */
			success: false,

			/**
			 * The message of the successful update or creation of the department.
			 * @type {string}
			 */
			successMessage: "",

			/**
			 * Is used to check if the save failed.
			 * @type {boolean}
			 */
			isSaveFailed: false,

			/**
			 * Is used to track if the form has been modified.
			 * @type {boolean}
			 */
			formDirty: false,

			/**
			 * Temporary input for new search terms
			 * @type {string}
			 */
			newSearchTerm: ""
		}
	},

	computed: {
		/**
		 * Gets the lock icon.
		 * @return {string} The lock icon.
		 */
		lockIcon: function () {
			return this.isEditMode
				? "<i class='fa fa-unlock'></i>"
				: "<i class='fa fa-lock'></i>"
		},

		/**
		 * Checks if the user can edit.
		 * @return {boolean} True if the user can edit.
		 */
		userCanEdit: function () {
			return this.permissions[0].user ? true : false
		},

		/**
		 * The validation schema for the department form.
		 * @return {Object} The validation schema.
		 */
		departmentSchema: function () {
			return Yup.object().shape({
				department: Yup.string()
					.required("Department name is required.")
					.max(150, "Department name must be 150 characters or less."),
				searchTerms: Yup.string().max(
					1000,
					"Search terms must be 1000 characters or less."
				).nullable(true),
				// mapBuilding: Yup.number().required().label("Associated Building"),
				address1: Yup.string().max(
					255,
					"Address must be 255 characters or less."
				).nullable(true),
				address2: Yup.string().max(
					255,
					"Address must be 255 characters or less."
				).nullable(true),
				city: Yup.string().max(100, "City must be 100 characters or less.").nullable(true),
				state: Yup.string().max(2, "State must be 2 characters or less.").nullable(true),
				zip: Yup.string().max(10, "ZIP code must be 10 characters or less.").nullable(true),
				phone: Yup.string().max(20, "Phone must be 20 characters or less.").nullable(true),
				phoneAlt: Yup.string().max(20, "Phone must be 20 characters or less.").nullable(true),
				fax: Yup.string().max(20, "Fax must be 20 characters or less.").nullable(true),
				email: Yup.string()
					.email("Please enter a valid email address.")
					.max(100, "Email must be 100 characters or less.").nullable(true),
				website: Yup.string()
					.url("Please enter a valid URL.")
					.max(255, "Website must be 255 characters or less.").nullable(true),
				facultyList: Yup.string()
					.url("Please enter a valid URL.")
					.max(255, "Faculty list URL must be 255 characters or less.").nullable(true),
				staffList: Yup.string()
					.url("Please enter a valid URL.")
					.max(255, "Staff list URL must be 255 characters or less.").nullable(true)
			})
		},
		// for the multiselect since it can't bind directly to the record.mapBuilding without the full object
		selectedBuilding: {
			get() {
				return (
					this.buildings.find((bldg) => bldg.id === this.record.mapBuilding) ||
					null
				)
			},
			set(newValue) {
				this.record.mapBuilding = newValue ? newValue.id : null
			}
		},

		/**
		 * Converts the searchTerms string to an array for display using @@ separator
		 */
		searchTermsArray: {
			get() {
				if (!this.record.searchTerms) return []
				return this.record.searchTerms
					.split("@@")
					.map((term) => term.trim())
					.filter((term) => term.length > 0)
			},
			set(value) {
				this.record.searchTerms = value.join("@@")
			}
		}
	},

	methods: {
		/**
		 * Goes to edit mode after submitting the new item.
		 */
		afterSubmitSucceeds: function () {
			this.formDirty = false
			let self = this

			// Since the new item has been submitted, go to edit mode.
			if (!this.itemExists) {
				this.success = true
				this.successMessage = "Department created."
				let newurl = "/directory/" + this.record.id
				document.location = newurl
			} else {
				this.success = true
				this.successMessage = "Update successful."
			}
		},

		/**
		 * Gets the buildings.
		 */
		fetchBuildings: function () {
			let self = this
			axios
				.get("/api/mapbuildings")
				.then(function (response) {
					// Success.
					self.buildings = response.data
				})
				.catch(function (error) {
					// Failure.
					self.apiError.status = error.response.status
					switch (error.response.status) {
						case 403:
							self.apiError.message =
								"You do not have sufficient privileges to retrieve buildings."
							break
						case 404:
							self.apiError.message = "Buildings were not found."
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

		/**
		 * Gets the department by using the specified ID.
		 * @param {string} itemId The ID of the department.
		 */
		fetchDepartment: function (itemId) {
			let self = this

			axios
				.get("/api/directory/" + itemId)
				.then(function (response) {
					// Success.
					self.record = response.data
					self.isDataLoaded = true
					// Initialize search terms array from the fetched record
					if (self.record.searchTerms) {
						self.searchTermsArray = self.record.searchTerms
							.split("@@")
							.map((term) => term.trim())
							.filter((term) => term.length > 0)
					}
				})
				.catch(function (error) {
					// Failure.
					if (error.request.status == 404) {
						self.is404 = true
						self.isDataLoaded = true
					}
				})
		},

		/**
		 * Emit an event to the parent component telling to go back to the last screen (applies to new item creation)
		 */
		goBack: function () {
			this.$emit("goBackStep1")
		},

		/**
		 * Called from the @itemDeleted event emission from the Delete Modal
		 */
		markItemDeleted: function () {
			this.isDeleteError = false
			this.isDeleted = true
			setTimeout(function () {
				// This record doesn't exist anymore, so send the user back to the department list page
				window.location.replace("/directory/list")
			}, 3000)
		},

		/**
		 * Called from the @itemDeleteError event emission from the Delete Modal
		 */
		markItemDeleteError: function () {
			this.isDeleteError = true
		},

		/**
		 * Submit the form via the API
		 */
		submitDepartment: function () {
			let self = this // 'this' loses scope within axios
			self.currentStatus = null
			let method = this.itemExists ? "put" : "post"
			let route = "/api/directory/"

			// AJAX (axios) submission
			axios({
				method: method,
				url: route,
				data: self.record
			})
				.then(function (response) {
					// Success.
					self.record.id = response.data.id // set the item's ID
					self.afterSubmitSucceeds()
				})
				.catch(function (error) {
					// Failure.
					self.currentStatus = STATUS_SAVE_FAILED
					self.isSaveFailed = true
				})
		},

		/**
		 * Toggles the edit mode.
		 */
		toggleEdit: function () {
			this.isEditMode === true
				? (this.isEditMode = false)
				: (this.isEditMode = true)
		},

		/**
		 * Adds a new search term to the array (lowercase)
		 */
		addSearchTerm() {
			const term = this.newSearchTerm.trim().toLowerCase()
			if (term && !this.searchTermsArray.includes(term)) {
				this.searchTermsArray = [...this.searchTermsArray, term]
				this.formDirty = true
			}
			this.newSearchTerm = ""
		},

		/**
		 * Removes a search term from the array
		 */
		removeSearchTerm(index) {
			const newArray = [...this.searchTermsArray]
			newArray.splice(index, 1)
			this.searchTermsArray = newArray
			this.formDirty = true
		}
	}
}
</script>
