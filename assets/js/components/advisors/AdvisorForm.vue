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
				<span v-if="!itemExists">Add New Advisor</span>
				<span v-else>Advisor: {{ savedName }}</span>
			</heading>
			<div class="button-holder" role="group" aria-label="form navigation buttons">
				<a href="/advisors" class="btn btn-info pull-left"
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
					@submit="submitAdvisor"
					:validation-schema="advisorSchema"
				>
					<fieldset class="card mb-4" aria-labelledby="advisorInfoHeading">
						<div class="card-header d-flex justify-content-between align-items-center">
							<h5 id="advisorInfoHeading" class="mb-0">Advisor Information</h5>
							<small v-if="isEditMode" class="text-muted"><span class="red">*</span> Required field</small>
						</div>
						<div class="card-body">
							<h6 class="advisor-section-title">Identity</h6>
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
										:id="'advisorType' + option"
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
									<label class="form-check-label" :for="'advisorType' + option">{{
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
							<h6 class="advisor-section-title">Contact &amp; Web</h6>
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
							<h6 class="advisor-section-title">Campus Location</h6>
							<div class="form-group">
								<label for="advisorBuilding">Building</label>
								<template v-if="isEditMode">
									<VueMultiselect
										id="advisorBuilding"
										v-model="selectedBuilding"
										:options="buildings"
										:multiple="false"
										:loading="loadingBuildings"
										label="name"
										track-by="id"
										placeholder="Search buildings..."
										@update:modelValue="formDirty = true"
									>
									</VueMultiselect>
									<small v-if="buildingsError" class="text-danger">{{
										buildingsError
									}}</small>
								</template>
								<input
									v-else
									id="advisorBuilding"
									type="text"
									class="form-control form-control-plaintext"
									:value="record.building_name || 'None'"
									readonly
								/>
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
<!--							<div class="form-group">-->
<!--								<label>Forager URL</label>-->
<!--								<Field-->
<!--									name="foragerUrl"-->
<!--									type="url"-->
<!--									class="form-control"-->
<!--									:class="{-->
<!--										'is-invalid': errors.foragerUrl,-->
<!--										'form-control-plaintext': !isEditMode-->
<!--									}"-->
<!--									:readonly="!isEditMode"-->
<!--									v-model="record.forager_url"-->
<!--									@update:modelValue="formDirty = true"-->
<!--								>-->
<!--								</Field>-->
<!--								<div class="invalid-feedback">-->
<!--									{{ errors.foragerUrl }}-->
<!--								</div>-->
<!--							</div>-->
<!--							<div class="form-group">-->
<!--								<label>Image URL</label>-->
<!--								<Field-->
<!--									name="imageUrl"-->
<!--									type="url"-->
<!--									class="form-control"-->
<!--									:class="{-->
<!--										'is-invalid': errors.imageUrl,-->
<!--										'form-control-plaintext': !isEditMode-->
<!--									}"-->
<!--									:readonly="!isEditMode"-->
<!--									v-model="record.image_url"-->
<!--									@update:modelValue="formDirty = true"-->
<!--								>-->
<!--								</Field>-->
<!--								<div class="invalid-feedback">-->
<!--									{{ errors.imageUrl }}-->
<!--								</div>-->
<!--							</div>-->
						</div>
					</fieldset>

					<fieldset v-if="itemExists || canEditLinks" class="card mb-4" aria-labelledby="advisorUsageHeading">
						<div class="card-header">
							<h5 id="advisorUsageHeading" class="mb-0">Usage</h5>
						</div>
						<div class="card-body">
							<p v-if="canEditLinks" class="text-muted small">
								Manage the degrees, minors, and certificates{{
									scholarshipAdvisorsEnabled ? " and scholarships" : ""
								}} this advisor belongs to. Changes are saved with the rest of the form.
							</p>
							<ic-usage-lists
								:sections="usageSections"
								:editable="canEditLinks"
								:searchable="false"
								@add="addLink"
								@remove="removeLink"
							></ic-usage-lists>
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
					<div v-if="isSaveFailed" class="alert alert-danger" role="alert">
						<p class="mb-0" v-if="saveErrors.length === 0">
							Error saving this advisor.
						</p>
						<template v-else>
							<p class="mb-1">This advisor could not be saved:</p>
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
							<i class="fa fa-save"></i> Save
						</button>
						<button
							v-if="itemExists && userCanDelete"
							type="button"
							class="btn btn-danger ml-4"
							data-toggle="modal"
							data-target="#deleteModal"
							:disabled="isInUse"
							:title="isInUse ? deleteBlockedReason : 'Delete this advisor'"
						>
							<i class="fa fa-trash"></i> Delete
						</button>
						<small v-if="itemExists && userCanDelete && isInUse" class="text-muted ml-2">{{
							deleteBlockedReason
						}}</small>
					</div>
				</VeeForm>

				<!-- History, below everything else -->
				<fieldset v-if="itemExists" class="border-top pt-3 mt-4 mb-4">
					<legend class="h6 text-muted mb-2">History</legend>
					<p class="text-muted small mb-1">
						Created {{ formatHistoryDate(record.created) }} by {{ record.created_by }}
					</p>
					<p class="text-muted small mb-0">
						Last updated {{ formatHistoryDate(record.updated) }} by {{ record.updated_by }}
					</p>
				</fieldset>
			</div>
		</div>

		<!-- Delete Modal -->
		<ic-delete-modal
			v-if="itemExists"
			entity-label="Advisor"
			:item-name="savedName"
			:delete-url="'/api/advisors/' + record.id"
			@itemDeleted="markItemDeleted"
			@itemDeleteError="markItemDeleteError"
		></ic-delete-modal>
	</div>
</template>

<style src="vue-multiselect/dist/vue-multiselect.css"></style>

<style scoped>
.button-holder {
	height: 50px;
}
.red {
	color: #ff0033;
}
/* Underlined, upper-case titles splitting the Advisor Information card into sections. */
.advisor-section-title {
	font-size: 1.25rem;
	font-weight: 400;
	text-transform: uppercase;
	border-bottom: 1px solid #adb5bd;
	padding-bottom: 0.35rem;
	margin: 1.5rem 0 1rem;
}
.advisor-section-title:first-child {
	margin-top: 0;
}
</style>

<script>
import Heading from "../utils/Heading.vue"
import IcDeleteModal from "../ic/IcDeleteModal.vue"
import IcUsageLists from "../ic/IcUsageLists.vue"
import NotFound from "../utils/NotFound.vue"
import VueMultiselect from "vue-multiselect"
import { SCHOLARSHIP_ADVISORS_ENABLED } from "@/utils/featureFlags"
import { Field, Form as VeeForm } from "vee-validate"
import * as Yup from "yup"

export default {
	created() {
		// Detect if the form should be in edit mode from the start; default is false.
		if (this.startMode == "edit") {
			this.isEditMode = true
		}

		// The building picker and add searches only show in edit mode, so their lists load then.
		this.loadEditOptions()

		if (this.itemExists === false) {
			// Set up for a new advisor.
			this.isDataLoaded = true
		} else {
			// Fetch the existing record using the property itemId.
			this.fetchAdvisor(this.itemId)
		}
	},

	components: {
		Heading,
		IcDeleteModal,
		IcUsageLists,
		NotFound,
		Field,
		VeeForm,
		VueMultiselect
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
			 * The buildings the picker offers: {id, name}, sorted by name.
			 * @type {Array}
			 */
			buildings: [],
			buildingsError: null,
			loadingBuildings: false,

			/**
			 * Every program and scholarship, for the add searches (null while loading), in the
			 * same row shape as record.programs / record.scholarships.
			 * @type {Object}
			 */
			linkOptions: { programs: null, scholarships: null },
			linkOptionsErrors: { programs: null, scholarships: null },

			/**
			 * Whether the buildings and link options have been requested (once, on the first
			 * switch to edit mode).
			 * @type {boolean}
			 */
			editOptionsRequested: false,

			/**
			 * The reason the server refused to delete the advisor, if any.
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
			 * The advisor being created or updated, plus its usage counts and lists.
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
				building_id: null,
				building_name: null,
				url: "",
				forager_url: "",
				image_url: "",
				program_count: 0,
				scholarship_count: 0,
				programs: [],
				scholarships: []
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
			 * The advisor types (IcAdvisorType).
			 * @type {Array}
			 */
			typeOptions: ["Person", "Organization"]
		}
	},

	computed: {
		/**
		 * @return {boolean} True if scholarships show on this form (see utils/featureFlags.js).
		 */
		scholarshipAdvisorsEnabled: function () {
			return SCHOLARSHIP_ADVISORS_ENABLED
		},

		/**
		 * @return {boolean} True if the user can edit advisors.
		 */
		userCanEdit: function () {
			return this.permissions[0].edit ? true : false
		},

		/**
		 * @return {boolean} True if the user can delete advisors.
		 */
		userCanDelete: function () {
			return this.permissions[0].delete ? true : false
		},

		/**
		 * Why the delete button is disabled.
		 * @return {string}
		 */
		deleteBlockedReason: function () {
			return "Can't delete: records still use this advisor."
		},

		/**
		 * True while anything still points at the advisor (the server refuses the delete too).
		 * @return {boolean}
		 */
		isInUse: function () {
			return this.record.program_count > 0 || this.record.scholarship_count > 0
		},

		/**
		 * The picked building as a picker option; setting it stores only the id. Before the
		 * options load, the record's own id and name stand in.
		 * @return {Object|null}
		 */
		selectedBuilding: {
			get: function () {
				if (this.record.building_id === null) {
					return null
				}
				return (
					this.buildings.find((b) => b.id === this.record.building_id) || {
						id: this.record.building_id,
						name: this.record.building_name
					}
				)
			},
			set: function (building) {
				this.record.building_id = building ? building.id : null
				this.record.building_name = building ? building.name : null
			}
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
		 * @return {boolean} True if the usage lists can be changed (the edit role, in edit mode).
		 */
		canEditLinks: function () {
			return this.isEditMode && this.userCanEdit
		},

		/**
		 * linkOptions as add-search options ({id, name, note}); null while loading.
		 * @return {Object}
		 */
		linkOptionItems: function () {
			let items = {}
			for (let key in this.linkOptions) {
				let rows = this.linkOptions[key]
				items[key] = rows === null ? null : rows.map((row) => this.linkItem(key, row))
			}
			return items
		},

		/**
		 * The records using the advisor. Each links to the record's edit page or read-only page,
		 * whichever the user's program or scholarship roles allow, or is plain text without
		 * access. In edit mode each section can also add any program or scholarship.
		 * @return {Array}
		 */
		usageSections: function () {
			let self = this
			return [
				{
					key: "programs",
					label: "Programs",
					addPlaceholder: "Search all degrees and programs by name",
					urlPrefix: "/programs/",
					linkMode: self.permissions[0].programLinks
				},
				{
					key: "scholarships",
					label: "Scholarships",
					addPlaceholder: "Search all scholarships by name",
					urlPrefix: "/scholarships/",
					linkMode: self.permissions[0].scholarshipLinks
				}
			]
				.filter((section) => section.key !== "scholarships" || self.scholarshipAdvisorsEnabled)
				.map(function (section) {
					let urlFor = function (id) {
						if (section.linkMode === "edit") return section.urlPrefix + id + "/edit"
						if (section.linkMode === "show") return section.urlPrefix + id
						return null
					}
					return {
						key: section.key,
						label: section.label,
						addPlaceholder: section.addPlaceholder,
						items: self.record[section.key].map((row) => ({
							...self.linkItem(section.key, row),
							url: urlFor(row.id)
						})),
						addOptions: self.canEditLinks ? self.linkOptionItems[section.key] : undefined,
						addError: self.linkOptionsErrors[section.key]
					}
				})
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
		 * The validation schema for the form (mirrors the IcAdvisor asserts). Only the
		 * chosen type's name fields are on the form.
		 * @return {Object} The validation schema.
		 */
		advisorSchema: function () {
			let shape = {
				type: Yup.string().nullable().required("Choose an advisor type."),
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
				this.successMessage = "Advisor created."
				document.location = "/advisors/" + this.record.id + "/edit"
			} else {
				this.successMessage = "Update successful."
				this.fetchAdvisor(this.record.id)
				setTimeout(() => {
					this.success = false
				}, 3000)
			}
		},

		/**
		 * Gets the advisor (with its usage counts and lists) by the specified ID.
		 * @param {string} itemId The ID of the advisor.
		 */
		fetchAdvisor: function (itemId) {
			let self = this

			axios
				.get("/api/advisors/" + itemId)
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
						self.apiError.message = "There was an error loading this advisor."
					}
				})
		},

		/**
		 * Adds a program or scholarship from the add search to the list, in name order.
		 * @param {string} key "programs" or "scholarships"
		 * @param {Object} option The picked option ({id, name, note}).
		 */
		addLink: function (key, option) {
			let row = (this.linkOptions[key] || []).find((r) => r.id === option.id)
			if (!row || this.record[key].some((r) => r.id === row.id)) {
				return
			}
			let self = this
			this.record[key] = this.record[key].concat([row]).sort(function (a, b) {
				return (
					self.linkItem(key, a).name.localeCompare(self.linkItem(key, b).name, undefined, {
						sensitivity: "base"
					}) || String(a.catalog || "").localeCompare(String(b.catalog || "")) || a.id - b.id
				)
			})
			this.formDirty = true
		},

		/**
		 * Removes a program or scholarship from the list.
		 * @param {string} key "programs" or "scholarships"
		 * @param {number} id The record's id.
		 */
		removeLink: function (key, id) {
			this.record[key] = this.record[key].filter((r) => r.id !== id)
			this.formDirty = true
		},

		/**
		 * A program or scholarship row as a list item: its name, and a note with the catalog
		 * (programs) and whether it is inactive.
		 * @param {string} key "programs" or "scholarships"
		 * @param {Object} row A usage or option row.
		 * @return {Object} {id, name, note}
		 */
		linkItem: function (key, row) {
			let notes = []
			if (key === "programs" && row.catalog) {
				notes.push(row.catalog)
			}
			if (row.active === false) {
				notes.push("inactive")
			}
			return {
				id: row.id,
				name: (key === "programs" ? row.full_name : row.title) || "",
				note: notes.join(", ")
			}
		},

		/**
		 * Loads the building picker's and add searches' lists the first time the form is in
		 * edit mode for an editor, so read-only visits don't download them.
		 */
		loadEditOptions: function () {
			if (!this.canEditLinks || this.editOptionsRequested) {
				return
			}
			this.editOptionsRequested = true
			this.fetchBuildings()
			this.fetchLinkOptions()
		},

		/**
		 * Gets every program and scholarship for the add searches (scholarships only while
		 * they show on the form).
		 */
		fetchLinkOptions: function () {
			let self = this
			Object.keys(this.linkOptions)
				.filter((key) => key !== "scholarships" || self.scholarshipAdvisorsEnabled)
				.forEach(function (key) {
					axios
						.get("/api/advisors/" + key)
						.then(function (response) {
							self.linkOptions[key] = response.data
						})
						.catch(function () {
							self.linkOptions[key] = []
							self.linkOptionsErrors[key] = "There was an error loading the " + key + "."
						})
				})
		},

		/**
		 * Gets the buildings for the picker.
		 */
		fetchBuildings: function () {
			let self = this
			self.loadingBuildings = true

			axios
				.get("/api/advisors/buildings")
				.then(function (response) {
					self.buildings = response.data
				})
				.catch(function () {
					self.buildingsError = "There was an error loading the buildings."
				})
				.finally(function () {
					self.loadingBuildings = false
				})
		},

		/**
		 * Called from the @itemDeleted event emission from the Delete Modal.
		 */
		markItemDeleted: function () {
			this.deleteErrorMessage = null
			this.isDeleted = true
			setTimeout(function () {
				window.location.replace("/advisors")
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
		 * Submit the form via the API. The other type's name fields are cleared, so an advisor
		 * that changes type doesn't keep a name it no longer shows.
		 */
		submitAdvisor: function () {
			let self = this // 'this' loses scope within axios
			self.success = false
			let method = this.itemExists ? "put" : "post"
			let route = this.itemExists
				? "/api/advisors/" + this.record.id
				: "/api/advisors"

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
					buildingId: self.record.building_id,
					url: self.record.url,
					foragerUrl: self.record.forager_url,
					imageUrl: self.record.image_url,
					programIds: self.record.programs.map((p) => p.id),
					scholarshipIds: self.record.scholarships.map((s) => s.id)
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
			this.loadEditOptions()
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
