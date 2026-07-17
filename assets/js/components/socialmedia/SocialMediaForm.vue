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
			"{{ record.name }}" has been deleted. You will now be redirected to the
			list page.
		</div>

		<!-- Main Area -->
		<div v-if="isDataLoaded === true && isDeleted === false && is404 === false">
			<heading>
				<span v-if="!itemExists">Add New Entity</span>
				<span v-else>Entity: {{ record.name }}</span>
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
			<div class="pt-2">
				<VeeForm
					class="form"
					v-slot="{ errors, meta }"
					@submit="submitEntity"
					:validation-schema="socialMediaSchema"
				>
					<fieldset>
						<legend>Entity</legend>
						<div class="form-group">
							<label>Name <span class="red">*</span></label>
							<Field
								name="name"
								type="text"
								class="form-control"
								:class="{
									'is-invalid': errors.name,
									'form-control-plaintext': !userCanEdit || !isEditMode
								}"
								:readonly="!userCanEdit || !isEditMode"
								v-model="record.name"
								@update:modelValue="formDirty = true"
							>
							</Field>
							<div class="invalid-feedback">
								{{ errors.name }}
							</div>
						</div>
					</fieldset>

					<fieldset>
						<legend>Social Media Links</legend>
						<div class="form-group">
							<label><i class="fab fa-facebook"></i> Facebook URL</label>
							<Field
								name="facebook_url"
								type="url"
								class="form-control"
								:class="{
									'is-invalid': errors.facebook_url,
									'form-control-plaintext': !userCanEdit || !isEditMode
								}"
								:readonly="!userCanEdit || !isEditMode"
								v-model="record.facebook_url"
								placeholder="https://facebook.com/..."
								@update:modelValue="formDirty = true"
							>
							</Field>
							<div class="invalid-feedback">
								{{ errors.facebook_url }}
							</div>
						</div>
						<div class="form-group">
							<label><i class="fab fa-x-twitter"></i> X URL</label>
							<Field
								name="x_url"
								type="url"
								class="form-control"
								:class="{
									'is-invalid': errors.x_url,
									'form-control-plaintext': !userCanEdit || !isEditMode
								}"
								:readonly="!userCanEdit || !isEditMode"
								v-model="record.x_url"
								placeholder="https://x.com/..."
								@update:modelValue="formDirty = true"
							>
							</Field>
							<div class="invalid-feedback">
								{{ errors.x_url }}
							</div>
						</div>
						<div class="form-group">
							<label><i class="fab fa-youtube"></i> YouTube URL</label>
							<Field
								name="youtube_url"
								type="url"
								class="form-control"
								:class="{
									'is-invalid': errors.youtube_url,
									'form-control-plaintext': !userCanEdit || !isEditMode
								}"
								:readonly="!userCanEdit || !isEditMode"
								v-model="record.youtube_url"
								placeholder="https://youtube.com/..."
								@update:modelValue="formDirty = true"
							>
							</Field>
							<div class="invalid-feedback">
								{{ errors.youtube_url }}
							</div>
						</div>
						<div class="form-group">
							<label><i class="fab fa-instagram"></i> Instagram URL</label>
							<Field
								name="instagram_url"
								type="url"
								class="form-control"
								:class="{
									'is-invalid': errors.instagram_url,
									'form-control-plaintext': !userCanEdit || !isEditMode
								}"
								:readonly="!userCanEdit || !isEditMode"
								v-model="record.instagram_url"
								placeholder="https://instagram.com/..."
								@update:modelValue="formDirty = true"
							>
							</Field>
							<div class="invalid-feedback">
								{{ errors.instagram_url }}
							</div>
						</div>
						<div class="form-group">
							<label><i class="fab fa-linkedin"></i> LinkedIn URL</label>
							<Field
								name="linkedin_url"
								type="url"
								class="form-control"
								:class="{
									'is-invalid': errors.linkedin_url,
									'form-control-plaintext': !userCanEdit || !isEditMode
								}"
								:readonly="!userCanEdit || !isEditMode"
								v-model="record.linkedin_url"
								placeholder="https://linkedin.com/..."
								@update:modelValue="formDirty = true"
							>
							</Field>
							<div class="invalid-feedback">
								{{ errors.linkedin_url }}
							</div>
						</div>
						<div class="form-group">
							<label><i class="fab fa-tiktok"></i> TikTok URL</label>
							<Field
								name="tiktok_url"
								type="url"
								class="form-control"
								:class="{
									'is-invalid': errors.tiktok_url,
									'form-control-plaintext': !userCanEdit || !isEditMode
								}"
								:readonly="!userCanEdit || !isEditMode"
								v-model="record.tiktok_url"
								placeholder="https://tiktok.com/@..."
								@update:modelValue="formDirty = true"
							>
							</Field>
							<div class="invalid-feedback">
								{{ errors.tiktok_url }}
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
						There was an error deleting this entity.
					</div>

					<!-- Action Buttons -->
					<div
						v-if="userCanEdit && isEditMode"
						aria-label="action buttons"
						class="mb-4"
					>
						<p v-if="formDirty" class="red">You have unsaved changes.</p>
						<p v-if="isSaveFailed" class="red">Error saving this entity.</p>
						<button class="btn btn-success" type="submit">
							<i class="fa fa-save fa-2x"></i>
						</button>
						<button
							v-if="itemExists && permissions[0].user"
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
		<socialmedia-delete-modal
			v-if="itemExists"
			:entity="record"
			@itemDeleted="markItemDeleted"
			@itemDeleteError="markItemDeleteError"
		></socialmedia-delete-modal>
	</div>
</template>

<style scoped></style>

<script>
import Heading from "../utils/Heading.vue"
import SocialMediaDeleteModal from "./SocialMediaDeleteModal.vue"
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
			// Set up for a new entity.
			this.isDataLoaded = true
		} else {
			// Fetch the existing record using the property itemId.
			this.fetchEntity(this.itemId)
		}
	},

	components: {
		Heading,
		SocialMediaDeleteModal,
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
			 * Is used to check if there is an error in deleting the entity.
			 */
			isDeleteError: false,

			/**
			 * Is used to check if the data is fetched and loaded.
			 * @type {boolean}
			 */
			isDataLoaded: false,

			/**
			 * Is used to check if the entity is deleted.
			 * @type {boolean}
			 */
			isDeleted: false,

			/**
			 * Is used to check if the user is in edit mode.
			 * @type {boolean}
			 */
			isEditMode: false,

			/**
			 * The current entity to be updated or created.
			 * @type {Object}
			 */
			record: {
				id: "",
				name: "",
				facebook_url: "",
				x_url: "",
				youtube_url: "",
				instagram_url: "",
				linkedin_url: "",
				tiktok_url: ""
			},

			/**
			 * Is used to check if the create/update succeeded.
			 * @type {boolean}
			 */
			success: false,

			/**
			 * The success message.
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
			formDirty: false
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
		 * The validation schema for the form.
		 * @return {Object} The validation schema.
		 */
		socialMediaSchema: function () {
			return Yup.object().shape({
				name: Yup.string()
					.required("A name is required.")
					.max(255, "Name must be 255 characters or less."),
				facebook_url: Yup.string()
					.url("Please enter a valid URL.")
					.max(500, "URL must be 500 characters or less.")
					.nullable(true),
				x_url: Yup.string()
					.url("Please enter a valid URL.")
					.max(500, "URL must be 500 characters or less.")
					.nullable(true),
				youtube_url: Yup.string()
					.url("Please enter a valid URL.")
					.max(500, "URL must be 500 characters or less.")
					.nullable(true),
				instagram_url: Yup.string()
					.url("Please enter a valid URL.")
					.max(500, "URL must be 500 characters or less.")
					.nullable(true),
				linkedin_url: Yup.string()
					.url("Please enter a valid URL.")
					.max(500, "URL must be 500 characters or less.")
					.nullable(true),
				tiktok_url: Yup.string()
					.url("Please enter a valid URL.")
					.max(500, "URL must be 500 characters or less.")
					.nullable(true)
			})
		}
	},

	methods: {
		/**
		 * Goes to edit mode after submitting the new item.
		 */
		afterSubmitSucceeds: function () {
			this.formDirty = false

			if (!this.itemExists) {
				this.success = true
				this.successMessage = "Entity created."
				document.location = "/social-media/" + this.record.id + "/edit"
			} else {
				this.success = true
				this.successMessage = "Update successful."
			}
		},

		/**
		 * Gets the entity by the specified ID.
		 * @param {string} itemId The ID of the entity.
		 */
		fetchEntity: function (itemId) {
			let self = this

			axios
				.get("/api/social-media/" + itemId)
				.then(function (response) {
					// Success.
					self.record = response.data
					self.isDataLoaded = true
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
		 * Called from the @itemDeleted event emission from the Delete Modal.
		 */
		markItemDeleted: function () {
			this.isDeleteError = false
			this.isDeleted = true
			setTimeout(function () {
				window.location.replace("/social-media")
			}, 2000)
		},

		/**
		 * Called from the @itemDeleteError event emission from the Delete Modal.
		 */
		markItemDeleteError: function () {
			this.isDeleteError = true
		},

		/**
		 * Submit the form via the API.
		 */
		submitEntity: function () {
			let self = this // 'this' loses scope within axios
			self.currentStatus = null
			let method = this.itemExists ? "put" : "post"
			let route = "/api/social-media/"

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
			this.isEditMode = !this.isEditMode
		}
	}
}
</script>
