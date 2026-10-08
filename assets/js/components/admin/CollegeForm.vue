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
			"{{ record.college }}" has been deleted. You will now be redirected to the
			list page.
		</div>

		<!-- Main Area -->
		<div v-if="isDataLoaded === true && isDeleted === false && is404 === false">
			<heading>
				<span v-if="!itemExists">Add New College</span>
				<span v-else>College: {{ savedName }}</span>
			</heading>
			<div class="button-holder" role="group" aria-label="form navigation buttons">
				<a href="/admin/colleges" class="btn btn-info pull-left"
					><i class="fa fa-arrow-left"></i
				></a>
				<button
					v-if="itemExists"
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
					@submit="submitCollege"
					:validation-schema="collegeSchema"
				>
					<fieldset>
						<legend>College</legend>
						<div class="form-group">
							<label>Name <span class="red" v-if="isEditMode">*</span></label>
							<Field
								name="college"
								type="text"
								class="form-control"
								:class="{
									'is-invalid': errors.college,
									'form-control-plaintext': !isEditMode
								}"
								:readonly="!isEditMode"
								v-model="record.college"
								@update:modelValue="formDirty = true"
							>
							</Field>
							<div class="invalid-feedback">
								{{ errors.college }}
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
					</fieldset>

					<fieldset v-if="itemExists">
						<legend>Usage</legend>
						<p>
							This college is used by
							<strong>{{ record.department_count }}</strong> department(s),
							<strong>{{ record.program_count }}</strong> program(s) and
							<strong>{{ record.scholarship_count }}</strong> scholarship(s).
							Changes here appear in the Programs and Scholarships apps.
						</p>
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
							Error saving this college.
						</p>
						<template v-else>
							<p class="mb-1">This college could not be saved:</p>
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
							v-if="itemExists"
							type="button"
							class="btn btn-danger ml-4"
							data-toggle="modal"
							data-target="#deleteModal"
							:disabled="isInUse"
							:title="isInUse ? deleteBlockedReason : 'Delete this college'"
						>
							<i class="fa fa-trash fa-2x"></i>
						</button>
						<small v-if="itemExists && isInUse" class="text-muted ml-2">{{
							deleteBlockedReason
						}}</small>
					</div>
				</VeeForm>
			</div>
		</div>

		<!-- Delete Modal -->
		<ic-delete-modal
			v-if="itemExists"
			entity-label="College"
			:item-name="savedName"
			:delete-url="'/api/admin/colleges/' + record.id"
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
import IcDeleteModal from "./IcDeleteModal.vue"
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
			// Set up for a new college.
			this.isDataLoaded = true
		} else {
			// Fetch the existing record using the property itemId.
			this.fetchCollege(this.itemId)
		}
	},

	components: {
		Heading,
		IcDeleteModal,
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
			 * The reason the server refused to delete the college, if any.
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
			 * The college being created or updated, plus its usage counts.
			 * @type {Object}
			 */
			record: {
				id: "",
				college: "",
				url: "",
				department_count: 0,
				program_count: 0,
				scholarship_count: 0
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
			successMessage: ""
		}
	},

	computed: {
		/**
		 * Why the delete button is disabled.
		 * @return {string}
		 */
		deleteBlockedReason: function () {
			return "Can't delete: departments, programs or scholarships still use this college."
		},

		/**
		 * True while anything still points at the college (the server refuses the delete too).
		 * @return {boolean}
		 */
		isInUse: function () {
			return (
				this.record.department_count > 0 ||
				this.record.program_count > 0 ||
				this.record.scholarship_count > 0
			)
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
		 * The validation schema for the form (mirrors the IcCollege asserts).
		 * @return {Object} The validation schema.
		 */
		collegeSchema: function () {
			return Yup.object().shape({
				college: Yup.string()
					.required("A college name is required.")
					.max(100, "College name must be 100 characters or less."),
				url: Yup.string()
					.url("Please enter a valid URL.")
					.max(100, "URL must be 100 characters or less.")
					.nullable(true)
			})
		}
	},

	methods: {
		/**
		 * Goes to the edit page after creating, or confirms the update.
		 */
		afterSubmitSucceeds: function () {
			this.formDirty = false
			this.savedName = this.record.college
			this.success = true

			if (!this.itemExists) {
				this.successMessage = "College created."
				document.location = "/admin/colleges/" + this.record.id + "/edit"
			} else {
				this.successMessage = "Update successful."
				setTimeout(() => {
					this.success = false
				}, 3000)
			}
		},

		/**
		 * Gets the college (with its usage counts) by the specified ID.
		 * @param {string} itemId The ID of the college.
		 */
		fetchCollege: function (itemId) {
			let self = this

			axios
				.get("/api/admin/colleges/" + itemId)
				.then(function (response) {
					// Success.
					self.record = response.data
					self.savedName = response.data.college
					self.isDataLoaded = true
				})
				.catch(function (error) {
					// Failure.
					self.isDataLoaded = true
					if (error.response && error.response.status == 404) {
						self.is404 = true
					} else {
						self.apiError.status = error.response ? error.response.status : 500
						self.apiError.message = "There was an error loading this college."
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
				window.location.replace("/admin/colleges")
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
		 * Submit the form via the API.
		 */
		submitCollege: function () {
			let self = this // 'this' loses scope within axios
			self.success = false
			let method = this.itemExists ? "put" : "post"
			let route = this.itemExists
				? "/api/admin/colleges/" + this.record.id
				: "/api/admin/colleges"

			axios({
				method: method,
				url: route,
				data: {
					college: self.record.college,
					url: self.record.url
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
