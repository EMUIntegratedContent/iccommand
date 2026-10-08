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
				<span v-if="!itemExists">Add New Department</span>
				<span v-else>Department: {{ savedName }}</span>
			</heading>
			<div class="button-holder" role="group" aria-label="form navigation buttons">
				<a href="/admin/departments" class="btn btn-info pull-left"
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
					@submit="submitDepartment"
					:validation-schema="departmentSchema"
				>
					<fieldset>
						<legend>Department</legend>
						<div class="form-group">
							<label>Name <span class="red" v-if="isEditMode">*</span></label>
							<Field
								name="department"
								type="text"
								class="form-control"
								:class="{
									'is-invalid': errors.department,
									'form-control-plaintext': !isEditMode
								}"
								:readonly="!isEditMode"
								v-model="record.department"
								@update:modelValue="formDirty = true"
							>
							</Field>
							<div class="invalid-feedback">
								{{ errors.department }}
							</div>
						</div>
						<div class="form-group">
							<label for="departmentCollege"
								>College <span class="red" v-if="isEditMode">*</span></label
							>
							<Field
								id="departmentCollege"
								name="collegeId"
								as="select"
								class="form-control"
								:class="{ 'is-invalid': errors.collegeId }"
								:disabled="!isEditMode"
								v-model="record.college_id"
								@update:modelValue="formDirty = true"
							>
								<option value="">Select College...</option>
								<option v-for="c in colleges" :key="c.id" :value="c.id">
									{{ c.college }}
								</option>
							</Field>
							<div class="invalid-feedback">
								{{ errors.collegeId }}
							</div>
						</div>
					</fieldset>

					<fieldset v-if="itemExists">
						<legend>Usage</legend>
						<p>
							This department is used by
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
							Error saving this department.
						</p>
						<template v-else>
							<p class="mb-1">This department could not be saved:</p>
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
							:title="isInUse ? deleteBlockedReason : 'Delete this department'"
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
			entity-label="Department"
			:item-name="savedName"
			:delete-url="'/api/admin/departments/' + record.id"
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

		this.fetchColleges()

		if (this.itemExists === false) {
			// Set up for a new department.
			this.isDataLoaded = true
		} else {
			// Fetch the existing record using the property itemId.
			this.fetchDepartment(this.itemId)
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
			 * The colleges (id + college) for the college picker.
			 * @type {Array}
			 */
			colleges: [],

			/**
			 * The reason the server refused to delete the department, if any.
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
			 * The department being created or updated, plus its college and usage counts.
			 * @type {Object}
			 */
			record: {
				id: "",
				department: "",
				college_id: "",
				college: "",
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
			return "Can't delete: programs or scholarships still use this department."
		},

		/**
		 * True while anything still points at the department (the server refuses the delete too).
		 * @return {boolean}
		 */
		isInUse: function () {
			return this.record.program_count > 0 || this.record.scholarship_count > 0
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
		 * The validation schema for the form (mirrors the IcDepartment asserts).
		 * @return {Object} The validation schema.
		 */
		departmentSchema: function () {
			return Yup.object().shape({
				department: Yup.string()
					.required("A department name is required.")
					.max(255, "Department name must be 255 characters or less."),
				collegeId: Yup.number()
					.typeError("Choose a college.")
					.required("Choose a college.")
			})
		}
	},

	methods: {
		/**
		 * Goes to the edit page after creating, or confirms the update.
		 */
		afterSubmitSucceeds: function () {
			this.formDirty = false
			this.savedName = this.record.department
			this.success = true

			if (!this.itemExists) {
				this.successMessage = "Department created."
				document.location = "/admin/departments/" + this.record.id + "/edit"
			} else {
				this.successMessage = "Update successful."
				setTimeout(() => {
					this.success = false
				}, 3000)
			}
		},

		/**
		 * Gets the colleges for the college picker.
		 */
		fetchColleges: function () {
			let self = this

			axios
				.get("/api/admin/colleges/dropdown")
				.then(function (response) {
					// Success.
					self.colleges = response.data
				})
				.catch(function (error) {
					// Failure.
					self.apiError.status = error.response ? error.response.status : 500
					self.apiError.message = "There was an error loading the colleges."
				})
		},

		/**
		 * Gets the department (with its college and usage counts) by the specified ID.
		 * @param {string} itemId The ID of the department.
		 */
		fetchDepartment: function (itemId) {
			let self = this

			axios
				.get("/api/admin/departments/" + itemId)
				.then(function (response) {
					// Success.
					self.record = response.data
					self.savedName = response.data.department
					self.isDataLoaded = true
				})
				.catch(function (error) {
					// Failure.
					self.isDataLoaded = true
					if (error.response && error.response.status == 404) {
						self.is404 = true
					} else {
						self.apiError.status = error.response ? error.response.status : 500
						self.apiError.message = "There was an error loading this department."
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
				window.location.replace("/admin/departments")
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
		submitDepartment: function () {
			let self = this // 'this' loses scope within axios
			self.success = false
			let method = this.itemExists ? "put" : "post"
			let route = this.itemExists
				? "/api/admin/departments/" + this.record.id
				: "/api/admin/departments"

			axios({
				method: method,
				url: route,
				data: {
					department: self.record.department,
					collegeId: self.record.college_id
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
