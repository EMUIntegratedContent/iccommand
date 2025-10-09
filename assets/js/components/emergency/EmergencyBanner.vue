<template>
	<div>
		<heading>
			<span>Emergency Banner Management</span>
		</heading>

		<div
			v-if="apiError.status"
			class="alert alert-danger fade show"
			role="alert"
		>
			{{ apiError.message }}
		</div>

		<div
			v-if="successMessage"
			class="alert alert-success fade show"
			role="alert"
		>
			{{ successMessage }}
		</div>

		<VeeForm
			class="form"
			v-slot="{ submitForm, errors, meta }"
			@submit="submitForm"
			:validation-schema="bannerSchema"
		>
			<!-- Emergency Banner Section -->
			<div class="card my-4 border-warning">
				<div class="card-header bg-warning text-dark">
					<h5 class="mb-0">
						<i class="fa fa-bullhorn"></i> Emergency Banner Settings
					</h5>
				</div>
				<div class="card-body">
					<div class="form-group">
						<div
							v-if="formData.displayBanner"
							class="alert alert-warning mt-2"
							role="alert"
						>
							<i class="fa fa-exclamation-triangle"></i>&nbsp;
							<strong>EMERGENCY BANNER ENABLED:</strong> The emergency banner
							will now appear on all EMU webpages with the selected severity
							level and message.
						</div>
						<div class="form-check">
							<input
								v-model="formData.displayBanner"
								type="checkbox"
								class="form-check-input banner-checkbox"
								id="displayBanner"
								@update:modelValue="formDirty = true"
							/>
							<label class="form-check-label banner-label" for="displayBanner">
								<strong>Display Emergency Banner</strong>
							</label>
						</div>
						<small class="form-text text-muted" v-if="!formData.displayBanner">
							When enabled, the emergency banner will appear on all EMU webpages
						</small>
					</div>

					<div class="form-group">
						<label for="severity"
							>Severity Level <span class="red">*</span></label
						>
						<Field
							name="severity"
							as="select"
							class="form-control"
							:class="{ 'is-invalid': errors.severity }"
							v-model="formData.severity"
							id="severity"
							@update:modelValue="formDirty = true"
						>
							<option value="">Select Severity</option>
							<option value="notice">Notice (Green)</option>
							<option value="warning">Warning (Yellow)</option>
							<option value="danger">Danger (Red)</option>
						</Field>
						<div class="invalid-feedback">
							{{ errors.severity }}
						</div>
						<small class="form-text text-muted">
							Choose the appropriate severity level for the emergency banner
						</small>
					</div>

					<div class="form-group">
						<label for="bannerTitle">Banner Title</label>
						<Field
							name="bannerTitle"
							type="text"
							class="form-control"
							:class="{ 'is-invalid': errors.bannerTitle }"
							v-model="formData.bannerTitle"
							id="bannerTitle"
							placeholder="Enter banner title (optional)..."
							@update:modelValue="formDirty = true"
						/>
						<div class="invalid-feedback">
							{{ errors.bannerTitle }}
						</div>
						<small class="form-text text-muted">
							This is usually left blank.
						</small>
					</div>

					<div class="form-group">
						<label for="bannerMessage"
							>Banner Message <span class="red">*</span></label
						>
						<Field
							name="bannerMessage"
							as="textarea"
							class="form-control"
							:class="{ 'is-invalid': errors.bannerMessage }"
							v-model="formData.bannerMessage"
							id="bannerMessage"
							rows="3"
							placeholder="Enter the emergency message to display..."
							@update:modelValue="formDirty = true"
						/>
						<div class="invalid-feedback">
							{{ errors.bannerMessage }}
						</div>
						<small class="form-text text-muted">
							This message will be displayed in the emergency banner
						</small>
					</div>

					<!-- Banner Preview -->
					<div
						v-if="
							formData.displayBanner &&
							formData.severity &&
							formData.bannerMessage
						"
						class="mt-4"
					>
						<h6 class="text-dark mb-3">
							<i class="fa fa-eye"></i> Banner Preview
						</h6>
						<div
							class="alert alert-dismissible fade show"
							:class="{
								'alert-success': formData.severity === 'notice',
								'alert-warning': formData.severity === 'warning',
								'alert-danger': formData.severity === 'danger'
							}"
							role="alert"
						>
							<strong
								>{{ formData.severity.toUpperCase()
								}}{{
									formData.bannerTitle ? ": " + formData.bannerTitle : ""
								}}:</strong
							>
							{{ formData.bannerMessage }}
							<button
								type="button"
								class="btn-close"
								data-bs-dismiss="alert"
								aria-label="Close"
							></button>
						</div>
					</div>
				</div>
			</div>

			<!-- Force Emergency Page Section -->
			<div class="card my-4 border-danger">
				<div class="card-header bg-danger text-white">
					<h5 class="mb-0">
						<i class="fa fa-exclamation-triangle"></i> Critical Emergency
						Settings
					</h5>
				</div>
				<div class="card-body">
					<div class="form-group">
						<div
							v-if="formData.forceEmergencyPage"
							class="alert alert-danger mt-2"
							role="alert"
						>
							<i class="fa fa-exclamation-triangle"></i>&nbsp;
							<strong>CRITICAL EMERGENCY MODE ACTIVATED:</strong> ALL users will
							be redirected to the EMU Emergency Page. Normal website
							functionality is disabled.
						</div>
						<div class="form-check">
							<input
								v-model="formData.forceEmergencyPage"
								type="checkbox"
								class="form-check-input emergency-checkbox"
								id="forceEmergencyPage"
								@update:modelValue="formDirty = true"
							/>
							<label
								class="form-check-label emergency-label"
								for="forceEmergencyPage"
							>
								<strong class="text-danger"
									>Force Emergency Page Redirect</strong
								>
							</label>
						</div>
						<div
							v-if="!formData.forceEmergencyPage"
							class="alert alert-warning mt-3"
							role="alert"
						>
							<i class="fa fa-warning"></i>
							<strong>Warning:</strong> When enabled, ALL users will be
							redirected to the EMU Emergency Page. This should only be used
							during critical emergencies that require immediate attention.
						</div>
						<small class="form-text text-muted">
							This setting overrides normal website functionality and forces all
							visitors to see emergency information.
						</small>
					</div>

					<!-- Emergency Notices Management -->
					<!-- <div class="mt-4">
						<h6 class="text-danger mb-3">
							<i class="fa fa-list"></i> Emergency Notices
						</h6>
						<p class="text-muted mb-3">
							Add emergency notices that will be displayed on the forced
							emergency page.
						</p>

						<div
							v-if="formData.notices.length === 0"
							class="alert alert-info"
							role="alert"
						>
							<i class="fa fa-info-circle"></i>&nbsp;
							<strong>No emergency notices configured.</strong> Add notices
							below that will be displayed on the forced emergency page.
						</div>

						<div
							v-for="(notice, index) in formData.notices"
							:key="index"
							class="card mb-3"
						>
							<div class="card-body">
								<div class="row">
									<div class="col-md-10">
										<div class="form-group">
											<label :for="'notice-' + index"
												>Notice {{ index + 1 }}</label
											>
											<textarea
												:id="'notice-' + index"
												class="form-control"
												v-model="notice.notice"
												rows="3"
												placeholder="Enter emergency notice content..."
												@input="formDirty = true"
											></textarea>
										</div>
									</div>
									<div class="col-md-2 d-flex align-items-end">
										<button
											type="button"
											class="btn btn-outline-danger btn-sm"
											@click="removeNotice(index)"
										>
											<i class="fa fa-trash"></i> Remove
										</button>
									</div>
								</div>
							</div>
						</div>

						<button
							type="button"
							class="btn btn-outline-primary btn-sm"
							@click="addNotice"
						>
							<i class="fa fa-plus"></i> Add Notice
						</button>
					</div> -->
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

			<div class="form-group">
				<button type="submit" class="btn btn-primary" :disabled="submitting">
					<span v-if="submitting">
						<i class="fa fa-spinner fa-spin"></i> Saving...
					</span>
					<span v-else>Update Emergency Banner</span>
				</button>
				<button
					type="button"
					class="btn btn-secondary ml-2"
					@click="cancelChanges"
					:disabled="!formDirty"
				>
					Cancel Changes
				</button>
			</div>
		</VeeForm>
	</div>
</template>
<style>
.red {
	color: #ff0033;
}

.emergency-checkbox:checked {
	background-color: #dc3545;
	border-color: #dc3545;
}

.emergency-checkbox:focus {
	border-color: #dc3545;
	box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}

.emergency-label {
	font-size: 1.1em;
}

.border-danger {
	border-color: #dc3545 !important;
}

.bg-danger {
	background-color: #dc3545 !important;
}

.border-primary {
	border-color: #007bff !important;
}

.bg-primary {
	background-color: #007bff !important;
}

.border-warning {
	border-color: #ffc107 !important;
}

.bg-warning {
	background-color: #ffc107 !important;
}

.banner-checkbox:checked {
	background-color: #ffc107;
	border-color: #ffc107;
	transform: scale(1.1);
}

.banner-checkbox:focus {
	border-color: #ffc107;
	box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.25);
}

.banner-label {
	font-size: 1.1em;
	font-weight: bold;
}

.emergency-checkbox:checked {
	background-color: #dc3545;
	border-color: #dc3545;
	transform: scale(1.2);
	animation: pulse 1s infinite;
}

.emergency-checkbox:focus {
	border-color: #dc3545;
	box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}

@keyframes pulse {
	0% {
		box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7);
	}
	70% {
		box-shadow: 0 0 0 10px rgba(220, 53, 69, 0);
	}
	100% {
		box-shadow: 0 0 0 0 rgba(220, 53, 69, 0);
	}
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
		}
	},
	data: function () {
		return {
			apiError: {
				message: null,
				status: null
			},
			successMessage: null,
			submitting: false,
			formDirty: false,
			originalData: null,
			formData: {
				displayBanner: false,
				severity: "",
				forceEmergencyPage: false,
				bannerTitle: "",
				bannerMessage: "",
				notices: [{ id: null, notice: "" }]
			}
		}
	},

	computed: {
		headingIcon: function () {
			return "<i class='fa fa-exclamation-triangle'></i>"
		},
		userCanEdit: function () {
			return this.permissions[0].edit ? true : false
		},
		bannerSchema() {
			let yupObj = {}

			// Only require severity and message if banner is displayed
			if (this.formData.displayBanner) {
				yupObj.severity = Yup.string().required().label("Severity Level")
				yupObj.bannerMessage = Yup.string()
					.required()
					.min(10)
					.label("Banner Message")
			}

			return Yup.object(yupObj)
		}
	},

	created() {
		this.fetchBanner()
	},

	methods: {
		fetchBanner: function () {
			let self = this

			axios
				.get("/api/emergency/banner")
				.then(function (response) {
					// Success - populate form with existing data
					const banner = response.data
					const formData = {
						displayBanner: banner.displayBanner || false,
						severity: banner.severity || "",
						forceEmergencyPage: banner.forceEmergencyPage || false,
						bannerTitle: banner.bannerTitle || "",
						bannerMessage: banner.bannerMessage || "",
						notices:
							banner.notices && banner.notices.length > 0
								? banner.notices.map((notice) => ({
										id: notice.id,
										notice: notice.notice
								  }))
								: [{ id: null, notice: "" }]
					}

					// Store original data for cancel functionality
					self.originalData = JSON.parse(JSON.stringify(formData))
					self.formData = formData
					self.formDirty = false
				})
				.catch(function (error) {
					// Failure.
					self.apiError.status = error.response.status

					switch (error.response.status) {
						case 403:
							self.apiError.message =
								"You do not have sufficient privileges to edit the emergency banner."
							break
						case 404:
							self.apiError.message = "The emergency banner was not found."
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

		submitForm: function () {
			let self = this

			// Check if Force Emergency Page is being enabled
			if (this.formData.forceEmergencyPage) {
				// Show confirmation dialog
				const confirmed = confirm(
					"⚠️ CRITICAL EMERGENCY CONFIRMATION ⚠️\n\n" +
						"You are about to enable FORCE EMERGENCY PAGE mode.\n\n" +
						"This will:\n" +
						"• Redirect ALL users to the EMU Emergency Page\n" +
						"• Disable normal website functionality\n" +
						"• Override all other website features\n\n" +
						"Are you absolutely sure you want to proceed?\n\n" +
						"Click OK to enable Force Emergency Page mode.\n" +
						"Click Cancel to keep the current settings."
				)

				if (!confirmed) {
					// User cancelled, don't submit
					return
				}
			}

			this.submitting = true
			this.apiError.status = null
			this.successMessage = null

			// Prepare data for submission
			const submitData = {
				displayBanner: this.formData.displayBanner,
				severity: this.formData.severity,
				forceEmergencyPage: this.formData.forceEmergencyPage,
				bannerTitle: this.formData.bannerTitle,
				bannerMessage: this.formData.bannerMessage,
				notices: this.formData.forceEmergencyPage
					? this.formData.notices.filter(
							(notice) => notice.notice.trim() !== ""
					  )
					: []
			}

			axios
				.put("/api/emergency/banner", submitData)
				.then(function (response) {
					// Update original data to current form data after successful save
					self.originalData = JSON.parse(JSON.stringify(self.formData))
					self.formDirty = false

					if (submitData.forceEmergencyPage) {
						self.successMessage =
							"CRITICAL EMERGENCY MODE ACTIVATED! All users will now be redirected to the emergency page."
					} else {
						self.successMessage = "Emergency banner updated successfully!"
					}

					setTimeout(() => {
						self.successMessage = null
					}, 5000) // Longer timeout for critical messages
				})
				.catch(function (error) {
					self.apiError.status = error.response.status

					if (error.response.data && error.response.data.message) {
						self.apiError.message = error.response.data.message
					} else {
						switch (error.response.status) {
							case 403:
								self.apiError.message =
									"You do not have sufficient privileges to update the emergency banner."
								break
							case 404:
								self.apiError.message = "The emergency banner was not found."
								break
							case 500:
								self.apiError.message =
									"An internal error occurred while updating the banner."
								break
							default:
								self.apiError.message =
									"An error occurred while updating the emergency banner."
								break
						}
					}
				})
				.finally(function () {
					self.submitting = false
				})
		},

		cancelChanges: function () {
			if (this.originalData) {
				// Restore form to original state
				this.formData = JSON.parse(JSON.stringify(this.originalData))
				this.formDirty = false
				this.apiError.status = null
				this.successMessage = null
			}
		},

		addNotice: function () {
			this.formData.notices.push({ id: null, notice: "" })
			this.formDirty = true
		},

		removeNotice: function (index) {
			this.formData.notices.splice(index, 1)
			this.formDirty = true
		}
	}
}
</script>
