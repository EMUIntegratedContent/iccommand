<template>
	<div>
		<div v-if="!isEdit" class="alert alert-info fade show" role="alert">
			NOTE: The public interface for creating photo requests can be found on the
			<a
				href="https://www.emich.edu/communications/support-center/photo.php"
				target="_blank"
				>Divcomm Website</a
			>. This form is for staff and administrators ONLY. It is not accessible to
			public users.
		</div>
		<heading>
			<span>{{ isEdit ? "Edit Photo Request" : "New Photo Request" }}</span>
		</heading>
		<div class="btn-group" role="group" aria-label="form navigation buttons">
			<button
				v-if="isEdit && userCanEdit"
				type="button"
				class="btn btn-info pull-right"
				@click="toggleEdit"
			>
				<span v-html="lockIcon"></span>
			</button>
		</div>
		<div class="pt-2"></div>

		<VeeForm
			class="form"
			v-slot="{ submitForm, errors, meta }"
			@submit="submitForm"
			:validation-schema="photoRequestSchema"
		>
			<div class="row">
				<div class="col-md-6">
					<div class="form-group">
						<label for="shootType"
							>What type of request is this? <span class="red">*</span></label
						>
						<Field
							name="shootType"
							as="select"
							class="form-control"
							:class="{ 'is-invalid': errors.shootType }"
							v-model="formData.shootType"
							id="shootType"
							@update:modelValue="formDirty = true"
						>
							<option value="photoshoot">New Photo Shoot</option>
							<option value="archive">Existing Photo</option>
						</Field>
						<div class="invalid-feedback">
							{{ errors.shootType }}
						</div>
					</div>
				</div>
				<div class="col-md-6">
					<div class="form-group">
						<label for="firstName">First Name <span class="red">*</span></label>
						<Field
							name="firstName"
							type="text"
							class="form-control"
							:class="{ 'is-invalid': errors.firstName }"
							v-model="formData.firstName"
							id="firstName"
							@update:modelValue="formDirty = true"
						/>
						<div class="invalid-feedback">
							{{ errors.firstName }}
						</div>
					</div>
				</div>
			</div>

			<div class="row">
				<div class="col-md-6">
					<div class="form-group">
						<label for="lastName">Last Name <span class="red">*</span></label>
						<Field
							name="lastName"
							type="text"
							class="form-control"
							:class="{ 'is-invalid': errors.lastName }"
							v-model="formData.lastName"
							id="lastName"
							@update:modelValue="formDirty = true"
						/>
						<div class="invalid-feedback">
							{{ errors.lastName }}
						</div>
					</div>
				</div>
				<div class="col-md-6">
					<div class="form-group">
						<label for="email">Email <span class="red">*</span></label>
						<Field
							name="email"
							type="email"
							class="form-control"
							:class="{ 'is-invalid': errors.email }"
							v-model="formData.email"
							id="email"
							@update:modelValue="formDirty = true"
						/>
						<div class="invalid-feedback">
							{{ errors.email }}
						</div>
					</div>
				</div>
			</div>

			<div class="row">
				<div class="col-md-6">
					<div class="form-group">
						<label for="phone">Phone <span class="red">*</span></label>
						<Field
							name="phone"
							type="text"
							class="form-control"
							:class="{ 'is-invalid': errors.phone }"
							v-model="formData.phone"
							id="phone"
							@update:modelValue="formDirty = true"
						/>
						<div class="invalid-feedback">
							{{ errors.phone }}
						</div>
					</div>
				</div>
				<div class="col-md-6">
					<div class="form-group">
						<label for="department"
							>Department <span class="red">*</span></label
						>
						<Field
							name="department"
							type="text"
							class="form-control"
							:class="{ 'is-invalid': errors.department }"
							v-model="formData.department"
							id="department"
							@update:modelValue="formDirty = true"
						/>
						<div class="invalid-feedback">
							{{ errors.department }}
						</div>
					</div>
				</div>
			</div>

			<div class="row">
				<div class="col-md-6" v-if="photoTypeOptions.includes('Event')">
					<div class="form-group">
						<label for="shootName">Name of Event</label>
						<Field
							name="shootName"
							type="text"
							class="form-control"
							v-model="formData.shootName"
							id="shootName"
							@update:modelValue="formDirty = true"
						/>
					</div>
				</div>
				<div class="col-md-6">
					<div class="form-group" v-if="formData.shootType === 'photoshoot'">
						<label for="photoType">Type of Photoshoot</label>
						<div class="form-check">
							<input
								v-model="photoTypeOptions"
								type="checkbox"
								class="form-check-input"
								id="photoTypeEvent"
								value="Event"
								@update:modelValue="formDirty = true"
							/>
							<label class="form-check-label" for="photoTypeEvent">Event</label>
						</div>
						<div class="form-check">
							<input
								v-model="photoTypeOptions"
								type="checkbox"
								class="form-check-input"
								id="photoTypeHeadShot"
								value="Head Shot"
								@update:modelValue="formDirty = true"
							/>
							<label class="form-check-label" for="photoTypeHeadShot"
								>Head Shot</label
							>
						</div>
						<div class="form-check">
							<input
								v-model="photoTypeOptions"
								type="checkbox"
								class="form-check-input"
								id="photoTypeGroupShot"
								value="Group Shot"
								@update:modelValue="formDirty = true"
							/>
							<label class="form-check-label" for="photoTypeGroupShot"
								>Group Shot</label
							>
						</div>
						<div class="form-check">
							<input
								v-model="photoTypeOptions"
								type="checkbox"
								class="form-check-input"
								id="photoTypeEditorial"
								value="Editorial"
								@update:modelValue="formDirty = true"
							/>
							<label class="form-check-label" for="photoTypeEditorial"
								>Editorial</label
							>
						</div>
					</div>
				</div>
			</div>

			<div class="row">
				<div class="col-md-4">
					<div class="form-group">
						<label for="shootDate">Proposed Shoot Date</label>
						<Field
							name="shootDate"
							type="date"
							class="form-control"
							v-model="formData.shootDate"
							id="shootDate"
							@update:modelValue="formDirty = true"
						/>
					</div>
				</div>
				<div class="col-md-4">
					<div class="form-group">
						<label for="startTime">Start Time</label>
						<Field
							name="startTime"
							type="time"
							class="form-control"
							v-model="formData.startTime"
							id="startTime"
							@update:modelValue="formDirty = true"
						/>
					</div>
				</div>
				<div class="col-md-4">
					<div class="form-group">
						<label for="endTime">End Time</label>
						<Field
							name="endTime"
							type="time"
							class="form-control"
							v-model="formData.endTime"
							id="endTime"
							@update:modelValue="formDirty = true"
						/>
					</div>
				</div>
			</div>

			<div class="form-group" v-if="formData.shootType === 'photoshoot'">
				<label for="location">Location</label>
				<Field
					name="location"
					type="text"
					class="form-control"
					v-model="formData.location"
					id="location"
					@update:modelValue="formDirty = true"
				/>
			</div>

			<div v-if="formData.shootType === 'photoshoot'" class="form-group">
				<label for="description"
					>Shoot Description
					<small
						>(Provide all relevant information about the shoot here)</small
					></label
				>
				<Field
					name="description"
					as="textarea"
					class="form-control"
					v-model="formData.description"
					id="description"
					rows="3"
					@update:modelValue="formDirty = true"
				/>
			</div>

			<div v-if="formData.shootType === 'archive'" class="form-group">
				<label for="photoExplaination"
					>Provide details about the photo you're looking for.</label
				>
				<Field
					name="photoExplaination"
					as="textarea"
					class="form-control"
					v-model="formData.photoExplaination"
					id="photoExplaination"
					rows="3"
					@update:modelValue="formDirty = true"
				/>
			</div>

			<div class="form-group">
				<label>Intended Use</label>
				<div class="form-check">
					<input
						v-model="intendedUseOptions"
						type="checkbox"
						class="form-check-input"
						id="intendedUseWeb"
						value="Web"
						@update:modelValue="formDirty = true"
					/>
					<label class="form-check-label" for="intendedUseWeb">Web</label>
				</div>
				<div class="form-check">
					<input
						v-model="intendedUseOptions"
						type="checkbox"
						class="form-check-input"
						id="intendedUsePrint"
						value="Print"
						@update:modelValue="formDirty = true"
					/>
					<label class="form-check-label" for="intendedUsePrint">Print</label>
				</div>
			</div>

			<template v-if="intendedUseOptions.length > 0">
				<div class="form-group">
					<label for="forUse">Describe how photo(s) will be used</label>
					<Field
						name="forUse"
						as="textarea"
						class="form-control"
						v-model="formData.forUse"
						id="forUse"
						rows="3"
						@update:modelValue="formDirty = true"
					/>
				</div>

				<div class="form-group">
					<label for="designer"
						>Are you working with a designer? Please provide name and contact
						information.</label
					>
					<Field
						name="designer"
						type="text"
						class="form-control"
						v-model="formData.designer"
						id="designer"
						@update:modelValue="formDirty = true"
					/>
				</div>
			</template>

			<!-- Admin fields for editing -->
			<div v-if="isEdit && userCanEdit" class="card my-4">
				<div class="card-header">
					<h5 class="mb-0">Administrative Management</h5>
				</div>
				<div class="card-body">
					<div class="row">
						<div class="col-md-4">
							<div class="form-group">
								<label for="status">Intermediate Status</label>
								<Field
									name="status"
									as="select"
									class="form-control"
									v-model="formData.status"
									id="status"
									@update:modelValue="formDirty = true"
								>
									<option value="">Select Status</option>
									<option value="WC">Waiting on Client</option>
									<option value="IP">In Progress</option>
									<option value="DG">Approval Required</option>
								</Field>
							</div>
						</div>
						<div class="col-md-4">
							<div class="form-group">
								<label for="completed">Completed</label>
								<Field
									name="completed"
									as="select"
									class="form-control"
									v-model="formData.completed"
									id="completed"
									@update:modelValue="formDirty = true"
								>
									<option value="0">No</option>
									<option value="1">Yes</option>
								</Field>
							</div>
						</div>
						<div class="col-md-4">
							<div class="form-group">
								<label for="declined">Declined</label>
								<Field
									name="declined"
									as="select"
									class="form-control"
									v-model="formData.declined"
									id="declined"
									@update:modelValue="formDirty = true"
								>
									<option value="0">No</option>
									<option value="1">Yes</option>
								</Field>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-4">
							<div class="form-group">
								<label for="assignedTo">Assigned To</label>
								<Field
									name="assignedTo"
									as="select"
									class="form-control"
									v-model="formData.assignedTo"
									id="assignedTo"
									@update:modelValue="formDirty = true"
								>
									<option value="">Select User</option>
									<option v-for="user in users" :key="user.id" :value="user.id">
										{{ user.lastName }}, {{ user.firstName }} ({{
											user.username
										}})
									</option>
								</Field>
							</div>
						</div>
						<div class="col-md-4">
							<div class="form-group">
								<label for="url">URL</label>
								<Field
									name="url"
									type="url"
									class="form-control"
									v-model="formData.url"
									id="url"
									@update:modelValue="formDirty = true"
								/>
							</div>
						</div>
						<div class="col-md-4">
							<div class="form-group">
								<label for="category">Category</label>
								<Field
									name="category"
									type="text"
									class="form-control"
									v-model="formData.category"
									id="category"
									@update:modelValue="formDirty = true"
								/>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<div class="form-group">
								<label for="longStatus">Admin Note</label>
								<Field
									name="longStatus"
									as="textarea"
									class="form-control"
									v-model="formData.longStatus"
									id="longStatus"
									rows="2"
									@update:modelValue="formDirty = true"
								/>
							</div>
						</div>
						<div class="col-md-12">
							<div class="form-group">
								<label for="eventDesc">Event Description</label>
								<Field
									name="eventDesc"
									as="textarea"
									class="form-control"
									v-model="formData.eventDesc"
									id="eventDesc"
									rows="3"
									@update:modelValue="formDirty = true"
								/>
							</div>
						</div>
					</div>
				</div>
			</div>

			<div
				v-if="Object.keys(errors).length"
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
			<div v-if="formDirty" class="alert alert-info fade show" role="alert">
				You have unsaved changes.
			</div>
			<div
				v-if="isSaveFailed"
				class="alert alert-danger fade show"
				role="alert"
			>
				Error saving this photo request. {{ apiError.message }}
			</div>
			<div
				v-if="successMessage"
				class="alert alert-success fade show"
				role="alert"
			>
				{{ successMessage }}
			</div>
			<div class="form-group">
				<button type="submit" class="btn btn-primary" :disabled="submitting">
					<span v-if="submitting"
						><i class="fa fa-spinner fa-spin"></i> Saving...</span
					>
					<span v-else>{{ isEdit ? "Update" : "Submit" }} Photo Request</span>
				</button>
				<a href="/photorequests/list" class="btn btn-secondary ml-2">Cancel</a>
			</div>
		</VeeForm>
	</div>
</template>

<style>
.red {
	color: #ff0033;
}
</style>

<script>
import Heading from "../utils/Heading.vue"
import { ErrorMessage, Field, Form as VeeForm } from "vee-validate"
import * as Yup from "yup"

export default {
	components: {
		Heading,
		Field,
		VeeForm,
		ErrorMessage
	},
	props: {
		permissions: {
			type: Array,
			required: true
		},
		requestId: {
			type: String,
			default: null
		}
	},
	data: function () {
		return {
			currentStatus: null,
			apiError: {
				message: null,
				status: null
			},
			successMessage: null,
			submitting: false,
			isEdit: false, // new vs existing request
			isEditMode: false, // This is true if the forms are editable.
			users: [],
			intendedUseOptions: [],
			photoTypeOptions: [],
			formDirty: false,
			success: false,
			formData: {
				shootType: "photoshoot",
				firstName: "",
				lastName: "",
				email: "",
				phone: "",
				department: "",
				shootName: "",
				photoType: "",
				shootDate: "",
				startTime: "",
				endTime: "",
				location: "",
				description: "",
				photoExplaination: "",
				intendedUse: "",
				forUse: "",
				url: "",
				designer: "",
				category: "",
				assignedTo: null,
				declined: 0,
				completed: 0,
				longStatus: "",
				status: "",
				eventDesc: ""
			}
		}
	},

	computed: {
		isStatusInitial() {
			return this.currentStatus === 0
		},
		isSaveFailed() {
			return this.currentStatus === 1
		},
		photoRequestSchema() {
			let yupObj = {
				shootType: Yup.string().required().label("Shoot Type"),
				firstName: Yup.string().required().label("First Name"),
				lastName: Yup.string().required().label("Last Name"),
				email: Yup.string().email().required().label("Email"),
				phone: Yup.string().required().label("Phone"),
				department: Yup.string().required().label("Department")
			}
			return Yup.object(yupObj)
		},
		headingIcon: function () {
			return "<i class='fa fa-camera'></i>"
		},
		userCanEdit: function () {
			return this.permissions[0].edit ? true : false
		},
		/**
		 * Gets the appropriate lock icon based if the user is in edit mode.
		 * @return {string} The lock icon being unlocked if the user is in edit
		 * mode; the lock icon being locked otherwise.
		 */
		lockIcon: function () {
			return "<i class='fa fa-unlock'></i>"
		}
	},

	created() {
		this.currentStatus = 0
		this.fetchUsers()
		if (this.requestId) {
			this.isEdit = true
			this.fetchPhotoRequest()
		}
	},

	methods: {
		fetchUsers: function () {
			let self = this
			axios
				.get("/api/photorequests/users")
				.then(function (response) {
					self.users = response.data
				})
				.catch(function (error) {
					console.error("Failed to load users:", error)
				})
		},

		fetchPhotoRequest: function () {
			let self = this

			axios
				.get(`/api/photorequests/${this.requestId}`)
				.then(function (response) {
					const request = response.data
					self.formData = {
						shootType: request.shootType || "photoshoot",
						firstName: request.firstName || "",
						lastName: request.lastName || "",
						email: request.email || "",
						phone: request.phone || "",
						department: request.department || "",
						shootName: request.shootName || "",
						photoType: request.photoType || "",
						shootDate: request.shootDate ? request.shootDate.split("T")[0] : "",
						startTime: request.startTime
							? request.startTime.split("T")[1].substring(0, 5)
							: "",
						endTime: request.endTime
							? request.endTime.split("T")[1].substring(0, 5)
							: "",
						location: request.location || "",
						description: request.description || "",
						photoExplaination: request.photoExplaination || "",
						intendedUse: request.intendedUse || "",
						forUse: request.forUse || "",
						url: request.url || "",
						designer: request.designer || "",
						category: request.category || "",
						assignedTo: request.assignedTo ? request.assignedTo.id : null,
						declined: request.declined || 0,
						completed: request.completed || 0,
						longStatus: request.longStatus || "",
						status: request.status || "",
						eventDesc: request.eventDesc || ""
					}

					// Parse intendedUse for checkboxes
					if (request.intendedUse) {
						self.intendedUseOptions = request.intendedUse
							.split(", ")
							.filter((option) => ["Web", "Print"].includes(option))
					} else {
						self.intendedUseOptions = []
					}

					// Parse photoType for checkboxes
					if (request.photoType) {
						self.photoTypeOptions = request.photoType
							.split(", ")
							.filter((option) =>
								["Event", "Head Shot", "Group Shot", "Editorial"].includes(
									option
								)
							)
					} else {
						self.photoTypeOptions = []
					}
				})
				.catch(function (error) {
					self.apiError.status = error.response.status
					self.apiError.message = "Failed to load photo request."
				})
		},

		submitForm: function () {
			let self = this
			this.currentStatus = null
			this.submitting = true
			this.apiError.status = null
			this.successMessage = null

			// Convert intendedUseOptions array to string
			this.formData.intendedUse = this.intendedUseOptions.join(", ")

			// Convert photoTypeOptions array to string
			this.formData.photoType = this.photoTypeOptions.join(", ")

			const url = this.isEdit ? `/api/photorequests/` : `/api/photorequests/`
			const method = this.isEdit ? "put" : "post"

			if (this.isEdit) {
				this.formData.id = this.requestId
			}

			axios[method](url, this.formData)
				.then(function (response) {
					self.formDirty = false
					self.success = true
					self.successMessage = self.isEdit
						? "Photo request updated successfully!"
						: "Photo request submitted successfully!"
					if (!self.isEdit) {
						// Redirect to show page for new submissions
						document.location = "/photorequests/" + response.data.id
					} else {
						setTimeout(() => {
							self.success = false
							self.currentStatus = 0
						}, 3000)
					}
				})
				.catch(function (error) {
					self.currentStatus = 1
					self.apiError.status = error.response.status
					if (error.response.data) {
						self.apiError.message =
							"Validation errors occurred. Please check your input."
					} else {
						self.apiError.message =
							"An error occurred while saving the photo request."
					}
				})
				.finally(function () {
					self.submitting = false
				})
		},

		resetForm: function () {
			this.formData = {
				shootType: "photoshoot",
				firstName: "",
				lastName: "",
				email: "",
				phone: "",
				department: "",
				shootName: "",
				photoType: "",
				shootDate: "",
				startTime: "",
				endTime: "",
				location: "",
				description: "",
				photoExplaination: "",
				intendedUse: "",
				forUse: "",
				url: "",
				designer: "",
				category: "",
				assignedTo: null,
				declined: 0,
				completed: 0,
				longStatus: "",
				status: "",
				eventDesc: ""
			}
			this.intendedUseOptions = []
			this.photoTypeOptions = []
			this.formDirty = false
		},

		/**
		 * Sets the variable @isEditMode to the appropriate boolean value.
		 */
		toggleEdit: function () {
			window.location.href = "/photorequests/" + this.requestId
		}
	}
}
</script>
