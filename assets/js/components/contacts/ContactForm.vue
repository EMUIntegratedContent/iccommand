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
			"{{ savedName }}" has been deleted. You will now be redirected to the
			list page.
		</div>

		<!-- Main Area -->
		<div v-if="isDataLoaded === true && isDeleted === false && is404 === false">
			<heading>
				<span v-if="!itemExists">Add New Contact</span>
				<span v-else>Contact: {{ savedName }}</span>
			</heading>
			<div class="button-holder" role="group" aria-label="form navigation buttons">
				<a href="/contacts" class="btn btn-info pull-left"
					><i class="fa fa-arrow-left"></i
				></a>
				<button
					v-if="itemExists && userCanEdit"
					type="button"
					class="btn btn-info pull-right"
					@click="toggleEdit"
				>
					<span v-html="lockIcon"></span>
				</button>
			</div>
			<div class="pt-2">
				<VeeForm
					class="form"
					v-slot="{ errors }"
					@submit="submitContact"
					:validation-schema="contactSchema"
				>
					<fieldset>
						<legend>Contact</legend>
						<div class="form-group">
							<label class="d-block"
								>Type <span class="red" v-if="isEditMode">*</span></label
							>
							<div
								v-for="option in typeOptions"
								:key="option"
								class="form-check form-check-inline"
							>
								<Field
									:id="'contactType' + option"
									name="type"
									type="radio"
									class="form-check-input"
									:class="{ 'is-invalid': errors.type }"
									:value="option"
									:disabled="!isEditMode"
									v-model="record.type"
									@update:modelValue="formDirty = true"
								>
								</Field>
								<label class="form-check-label" :for="'contactType' + option">{{
									option
								}}</label>
							</div>
							<div class="invalid-feedback d-block" v-if="errors.type">
								{{ errors.type }}
							</div>
						</div>
						<template v-if="isPerson">
							<div class="form-group">
								<label>First Name <span class="red" v-if="isEditMode">*</span></label>
								<Field
									name="firstName"
									type="text"
									class="form-control"
									:class="{
										'is-invalid': errors.firstName,
										'form-control-plaintext': !isEditMode
									}"
									:readonly="!isEditMode"
									v-model="record.first_name"
									@update:modelValue="formDirty = true"
								>
								</Field>
								<div class="invalid-feedback">
									{{ errors.firstName }}
								</div>
							</div>
							<div class="form-group">
								<label>Last Name <span class="red" v-if="isEditMode">*</span></label>
								<Field
									name="lastName"
									type="text"
									class="form-control"
									:class="{
										'is-invalid': errors.lastName,
										'form-control-plaintext': !isEditMode
									}"
									:readonly="!isEditMode"
									v-model="record.last_name"
									@update:modelValue="formDirty = true"
								>
								</Field>
								<div class="invalid-feedback">
									{{ errors.lastName }}
								</div>
							</div>
						</template>
						<template v-if="isOrganization">
							<div class="form-group">
								<label>Organization <span class="red" v-if="isEditMode">*</span></label>
								<Field
									name="organization"
									type="text"
									class="form-control"
									:class="{
										'is-invalid': errors.organization,
										'form-control-plaintext': !isEditMode
									}"
									:readonly="!isEditMode"
									v-model="record.organization"
									@update:modelValue="formDirty = true"
								>
								</Field>
								<div class="invalid-feedback">
									{{ errors.organization }}
								</div>
							</div>
						</template>
						<div class="form-group">
							<label>Email</label>
							<Field
								name="email"
								type="email"
								class="form-control"
								:class="{
									'is-invalid': errors.email,
									'form-control-plaintext': !isEditMode
								}"
								:readonly="!isEditMode"
								v-model="record.email"
								@update:modelValue="formDirty = true"
							>
							</Field>
							<div class="invalid-feedback">
								{{ errors.email }}
							</div>
						</div>
						<div class="form-group">
							<label>Phone</label>
							<Field
								name="phone"
								type="text"
								class="form-control"
								:class="{
									'is-invalid': errors.phone,
									'form-control-plaintext': !isEditMode
								}"
								:readonly="!isEditMode"
								v-model="record.phone"
								@update:modelValue="formDirty = true"
							>
							</Field>
							<div class="invalid-feedback">
								{{ errors.phone }}
							</div>
						</div>
						<div class="form-group">
							<label>Room</label>
							<Field
								name="room"
								type="text"
								class="form-control"
								:class="{
									'is-invalid': errors.room,
									'form-control-plaintext': !isEditMode
								}"
								:readonly="!isEditMode"
								v-model="record.room"
								@update:modelValue="formDirty = true"
							>
							</Field>
							<div class="invalid-feedback">
								{{ errors.room }}
							</div>
						</div>
						<div class="form-group">
							<label>Area</label>
							<Field
								name="area"
								type="text"
								class="form-control"
								:class="{
									'is-invalid': errors.area,
									'form-control-plaintext': !isEditMode
								}"
								:readonly="!isEditMode"
								v-model="record.area"
								@update:modelValue="formDirty = true"
							>
							</Field>
							<div class="invalid-feedback">
								{{ errors.area }}
							</div>
						</div>
						<div class="form-group">
							<label>URL</label>
							<Field
								name="url"
								type="url"
								class="form-control"
								:class="{
									'is-invalid': errors.url,
									'form-control-plaintext': !isEditMode
								}"
								:readonly="!isEditMode"
								v-model="record.url"
								placeholder="https://www.emich.edu/..."
								@update:modelValue="formDirty = true"
							>
							</Field>
							<div class="invalid-feedback">
								{{ errors.url }}
							</div>
						</div>
						<div class="form-group">
							<label>Forager URL</label>
							<Field
								name="foragerUrl"
								type="url"
								class="form-control"
								:class="{
									'is-invalid': errors.foragerUrl,
									'form-control-plaintext': !isEditMode
								}"
								:readonly="!isEditMode"
								v-model="record.forager_url"
								@update:modelValue="formDirty = true"
							>
							</Field>
							<div class="invalid-feedback">
								{{ errors.foragerUrl }}
							</div>
						</div>
						<div class="form-group">
							<label>Image URL</label>
							<Field
								name="imageUrl"
								type="url"
								class="form-control"
								:class="{
									'is-invalid': errors.imageUrl,
									'form-control-plaintext': !isEditMode
								}"
								:readonly="!isEditMode"
								v-model="record.image_url"
								@update:modelValue="formDirty = true"
							>
							</Field>
							<div class="invalid-feedback">
								{{ errors.imageUrl }}
							</div>
						</div>
					</fieldset>

					<fieldset v-if="itemExists">
						<legend>History</legend>
						<p class="mb-1">
							Created {{ formatHistoryDate(record.created) }} by {{ record.created_by }}
						</p>
						<p>Last updated {{ formatHistoryDate(record.updated) }} by {{ record.updated_by }}</p>
					</fieldset>

					<fieldset v-if="itemExists">
						<legend>Usage</legend>
						<ic-usage-lists :sections="usageSections"></ic-usage-lists>
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
					<div v-if="isSaveFailed" class="alert alert-danger" role="alert">
						<p class="mb-0" v-if="saveErrors.length === 0">
							Error saving this contact.
						</p>
						<template v-else>
							<p class="mb-1">This contact could not be saved:</p>
							<ul class="mb-0">
								<li v-for="message in saveErrors" :key="message">
									{{ message }}
								</li>
							</ul>
						</template>
					</div>
					<div
						v-if="deleteErrorMessage"
						class="alert alert-danger fade show"
						role="alert"
					>
						{{ deleteErrorMessage }}
					</div>

					<!-- Action Buttons -->
					<div v-if="isEditMode" aria-label="action buttons" class="mb-4">
						<p v-if="formDirty" class="red">You have unsaved changes.</p>
						<button class="btn btn-success" type="submit">
							<i class="fa fa-save fa-2x"></i>
						</button>
						<button
							v-if="itemExists && userCanDelete"
							type="button"
							class="btn btn-danger ml-4"
							data-toggle="modal"
							data-target="#deleteModal"
							:disabled="isInUse"
							:title="isInUse ? deleteBlockedReason : 'Delete this contact'"
						>
							<i class="fa fa-trash fa-2x"></i>
						</button>
						<small v-if="itemExists && userCanDelete && isInUse" class="text-muted ml-2">{{
							deleteBlockedReason
						}}</small>
					</div>
				</VeeForm>
			</div>
		</div>

		<!-- Delete Modal -->
		<ic-delete-modal
			v-if="itemExists"
			entity-label="Contact"
			:item-name="savedName"
			:delete-url="'/api/contacts/' + record.id"
			@itemDeleted="markItemDeleted"
			@itemDeleteError="markItemDeleteError"
		></ic-delete-modal>
	</div>
</template>

<style scoped>
.button-holder {
	height: 50px;
}
.red {
	color: #ff0033;
}
</style>

<script>
import Heading from "../utils/Heading.vue"
import IcDeleteModal from "../ic/IcDeleteModal.vue"
import IcUsageLists from "../ic/IcUsageLists.vue"
import NotFound from "../utils/NotFound.vue"
import { Field, Form as VeeForm } from "vee-validate"
import * as Yup from "yup"

export default {
	created() {
		// Detect if the form should be in edit mode from the start; default is false.
		if (this.startMode == "edit") {
			this.isEditMode = true
		}

		if (this.itemExists === false) {
			// Set up for a new contact.
			this.isDataLoaded = true
		} else {
			// Fetch the existing record using the property itemId.
			this.fetchContact(this.itemId)
		}
	},

	components: {
		Heading,
		IcDeleteModal,
		IcUsageLists,
		NotFound,
		Field,
		VeeForm
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

		startMode: {
			type: String,
			required: false
		},

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
			 * The reason the server refused to delete the contact, if any.
			 * @type {string|null}
			 */
			deleteErrorMessage: null,

			/**
			 * Is used to track if the form has been modified.
			 * @type {boolean}
			 */
			formDirty: false,

			is404: false,
			isDataLoaded: false,
			isDeleted: false,
			isEditMode: false,
			isSaveFailed: false,

			/**
			 * The contact being created or updated, plus its usage counts and lists.
			 * @type {Object}
			 */
			record: {
				id: "",
				type: "",
				last_name: "",
				first_name: "",
				organization: "",
				email: "",
				phone: "",
				room: "",
				area: "",
				url: "",
				forager_url: "",
				image_url: "",
				program_count: 0,
				scholarship_count: 0,
				building_count: 0,
				programs: [],
				scholarships: [],
				buildings: []
			},

			/**
			 * The name as last loaded/saved (the heading shouldn't change while typing).
			 * @type {string}
			 */
			savedName: "",

			/**
			 * Server-side validation messages from the last failed save.
			 * @type {Array}
			 */
			saveErrors: [],

			success: false,
			successMessage: "",

			/**
			 * The contact types (IcContactType).
			 * @type {Array}
			 */
			typeOptions: ["Person", "Organization"]
		}
	},

	computed: {
		/**
		 * @return {boolean} True if the user can edit contacts.
		 */
		userCanEdit: function () {
			return this.permissions[0].edit ? true : false
		},

		/**
		 * @return {boolean} True if the user can delete contacts.
		 */
		userCanDelete: function () {
			return this.permissions[0].delete ? true : false
		},

		/**
		 * Why the delete button is disabled.
		 * @return {string}
		 */
		deleteBlockedReason: function () {
			return "Can't delete: records still use this contact."
		},

		/**
		 * True while anything still points at the contact (the server refuses the delete too).
		 * @return {boolean}
		 */
		isInUse: function () {
			return (
				this.record.program_count > 0 ||
				this.record.scholarship_count > 0 ||
				this.record.building_count > 0
			)
		},

		/**
		 * @return {boolean}
		 */
		isOrganization: function () {
			return this.record.type === "Organization"
		},

		/**
		 * @return {boolean}
		 */
		isPerson: function () {
			return this.record.type === "Person"
		},

		/**
		 * The records using the contact, linked to their edit pages.
		 * @return {Array}
		 */
		usageSections: function () {
			return [
				{
					key: "programs",
					label: "Programs",
					items: this.record.programs.map((p) => ({
						id: p.id,
						name: p.full_name,
						note: p.catalog,
						url: "/programs/" + p.id + "/edit"
					}))
				},
				{
					key: "scholarships",
					label: "Scholarships",
					items: this.record.scholarships.map((s) => ({
						id: s.id,
						name: s.title,
						url: "/scholarships/" + s.id + "/edit"
					}))
				},
				{
					key: "buildings",
					label: "Buildings",
					items: this.record.buildings.map((b) => ({
						id: b.id,
						name: b.name,
						url: "/map/items/" + b.id + "/edit"
					}))
				}
			]
		},

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
		 * The validation schema for the form (mirrors the IcContact asserts). Only the
		 * chosen type's name fields are on the form.
		 * @return {Object} The validation schema.
		 */
		contactSchema: function () {
			let shape = {
				type: Yup.string().nullable().required("Choose a contact type."),
				email: Yup.string()
					.nullable()
					.email("Please enter a valid email address.")
					.max(255, "Email must be 255 characters or less."),
				phone: Yup.string()
					.nullable()
					.max(50, "Phone must be 50 characters or less."),
				room: Yup.string()
					.nullable()
					.max(255, "Room must be 255 characters or less."),
				area: Yup.string()
					.nullable()
					.max(255, "Area must be 255 characters or less."),
				url: Yup.string()
					.nullable()
					.url("Please enter a valid URL.")
					.max(1024, "URL must be 1024 characters or less."),
				foragerUrl: Yup.string()
					.nullable()
					.url("Please enter a valid Forager URL.")
					.max(1024, "Forager URL must be 1024 characters or less."),
				imageUrl: Yup.string()
					.nullable()
					.url("Please enter a valid image URL.")
					.max(1024, "Image URL must be 1024 characters or less.")
			}

			if (this.isPerson) {
				shape.lastName = Yup.string()
					.nullable()
					.required("A last name is required.")
					.max(100, "Last name must be 100 characters or less.")
				shape.firstName = Yup.string()
					.nullable()
					.required("A first name is required.")
					.max(100, "First name must be 100 characters or less.")
			}
			if (this.isOrganization) {
				shape.organization = Yup.string()
					.nullable()
					.required("An organization name is required.")
					.max(255, "Organization must be 255 characters or less.")
			}

			return Yup.object().shape(shape)
		}
	},

	methods: {
		/**
		 * Formats a timestamp like PHP's "n/j/y @ h:i a", e.g. 11/9/26 @ 09:05 am.
		 */
		formatHistoryDate: function (value) {
			if (!value) return ""
			const date = new Date(value)
			if (isNaN(date.getTime())) return value
			const pad = (n) => String(n).padStart(2, "0")
			const hours = date.getHours()
			const hour12 = hours % 12 || 12
			const meridiem = hours < 12 ? "am" : "pm"
			return `${date.getMonth() + 1}/${date.getDate()}/${pad(date.getFullYear() % 100)} @ ${pad(hour12)}:${pad(date.getMinutes())} ${meridiem}`
		},

		/**
		 * Goes to the edit page after creating, or confirms the update and reloads the record
		 * so the name and history stay current.
		 */
		afterSubmitSucceeds: function () {
			this.formDirty = false
			this.success = true

			if (!this.itemExists) {
				this.successMessage = "Contact created."
				document.location = "/contacts/" + this.record.id + "/edit"
			} else {
				this.successMessage = "Update successful."
				this.fetchContact(this.record.id)
				setTimeout(() => {
					this.success = false
				}, 3000)
			}
		},

		/**
		 * Gets the contact (with its usage counts and lists) by the specified ID.
		 * @param {string} itemId The ID of the contact.
		 */
		fetchContact: function (itemId) {
			let self = this

			axios
				.get("/api/contacts/" + itemId)
				.then(function (response) {
					// Success.
					self.record = response.data
					self.savedName = response.data.display_name
					self.isDataLoaded = true
				})
				.catch(function (error) {
					// Failure.
					self.isDataLoaded = true
					if (error.response && error.response.status == 404) {
						self.is404 = true
					} else {
						self.apiError.status = error.response ? error.response.status : 500
						self.apiError.message = "There was an error loading this contact."
					}
				})
		},

		/**
		 * Called from the @itemDeleted event emission from the Delete Modal.
		 */
		markItemDeleted: function () {
			this.deleteErrorMessage = null
			this.isDeleted = true
			setTimeout(function () {
				window.location.replace("/contacts")
			}, 2000)
		},

		/**
		 * Called from the @itemDeleteError event emission from the Delete Modal.
		 * @param {string} message Why the delete failed.
		 */
		markItemDeleteError: function (message) {
			this.deleteErrorMessage = message
		},

		/**
		 * Submit the form via the API. The other type's name fields are cleared, so a contact
		 * that changes type doesn't keep a name it no longer shows.
		 */
		submitContact: function () {
			let self = this // 'this' loses scope within axios
			self.success = false
			let method = this.itemExists ? "put" : "post"
			let route = this.itemExists
				? "/api/contacts/" + this.record.id
				: "/api/contacts"

			axios({
				method: method,
				url: route,
				data: {
					type: self.record.type,
					lastName: self.isPerson ? self.record.last_name : null,
					firstName: self.isPerson ? self.record.first_name : null,
					organization: self.isOrganization ? self.record.organization : null,
					email: self.record.email,
					phone: self.record.phone,
					room: self.record.room,
					area: self.record.area,
					url: self.record.url,
					foragerUrl: self.record.forager_url,
					imageUrl: self.record.image_url
				}
			})
				.then(function (response) {
					// Success.
					self.record.id = response.data.id // set the item's ID
					self.isSaveFailed = false
					self.saveErrors = []
					self.afterSubmitSucceeds()
				})
				.catch(function (error) {
					// Failure.
					self.isSaveFailed = true
					self.saveErrors = self.violationMessages(error)
				})
		},

		/**
		 * Toggles the edit mode.
		 */
		toggleEdit: function () {
			this.isEditMode = !this.isEditMode
		},

		/**
		 * Turns a 422 response's violations into readable messages.
		 * @param {Object} error The axios error.
		 * @return {Array} The messages (empty if the response had none).
		 */
		violationMessages: function (error) {
			let violations =
				error.response && error.response.data && error.response.data.violations
			if (!violations) {
				return []
			}
			return violations.map(function (violation) {
				return violation.title
			})
		}
	}
}
</script>
