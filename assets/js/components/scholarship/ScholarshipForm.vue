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
			"{{ record.title }}" has been deleted. You will now be redirected to the
			list page.
		</div>

		<!-- Main Area -->
		<div v-if="isDataLoaded === true && isDeleted === false && is404 === false">
			<heading>
				<span v-if="!itemExists">Add New Scholarship</span>
				<span v-else>Scholarship: {{ record.title }}</span>
			</heading>
			<div class="btn-group" role="group" aria-label="form navigation buttons">
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
					@submit="submitScholarship"
					:validation-schema="scholarshipSchema"
				>
					<fieldset>
						<legend>Scholarship</legend>
						<div class="form-group">
							<label>Title <span class="red">*</span></label>
							<Field
								name="title"
								type="text"
								class="form-control"
								:class="{
									'is-invalid': errors.title,
									'form-control-plaintext': !userCanEdit || !isEditMode
								}"
								:readonly="!userCanEdit || !isEditMode"
								v-model="record.title"
								@update:modelValue="formDirty = true"
							>
							</Field>
							<div class="invalid-feedback">
								{{ errors.title }}
							</div>
						</div>
						<div class="form-group form-check">
							<input
								id="scholarshipActive"
								type="checkbox"
								class="form-check-input"
								:disabled="!userCanEdit || !isEditMode"
								v-model="record.active"
								@change="formDirty = true"
							/>
							<label class="form-check-label" for="scholarshipActive">
								Active
							</label>
							<small class="form-text text-muted">
								Only active scholarships are published to the public feed.
							</small>
						</div>
						<div class="form-group">
							<label>More information URL</label>
							<Field
								name="url"
								type="url"
								class="form-control"
								:class="{
									'is-invalid': errors.url,
									'form-control-plaintext': !userCanEdit || !isEditMode
								}"
								:readonly="!userCanEdit || !isEditMode"
								v-model="record.url"
								placeholder="https://..."
								@update:modelValue="formDirty = true"
							>
							</Field>
							<div class="invalid-feedback">
								{{ errors.url }}
							</div>
						</div>
						<div class="form-group">
							<label>Amount</label>
							<Field
								name="amount"
								type="text"
								class="form-control"
								:class="{
									'is-invalid': errors.amount,
									'form-control-plaintext': !userCanEdit || !isEditMode
								}"
								:readonly="!userCanEdit || !isEditMode"
								v-model="record.amount"
								placeholder="e.g. $1500-$2500, or tuition and fees"
								@update:modelValue="formDirty = true"
							>
							</Field>
							<div class="invalid-feedback">
								{{ errors.amount }}
							</div>
						</div>
					</fieldset>

					<fieldset>
						<legend>Dates</legend>
						<div class="form-group">
							<label>Apply by date</label>
							<Field
								name="applyDate"
								type="date"
								class="form-control"
								:class="{
									'is-invalid': errors.applyDate,
									'form-control-plaintext': !userCanEdit || !isEditMode
								}"
								:readonly="!userCanEdit || !isEditMode"
								v-model="record.applyDate"
								@update:modelValue="formDirty = true"
							>
							</Field>
							<div class="invalid-feedback">
								{{ errors.applyDate }}
							</div>
						</div>
						<div class="form-group">
							<label>Expiration date</label>
							<Field
								name="expDate"
								type="date"
								class="form-control"
								:class="{
									'is-invalid': errors.expDate,
									'form-control-plaintext': !userCanEdit || !isEditMode
								}"
								:readonly="!userCanEdit || !isEditMode"
								v-model="record.expDate"
								@update:modelValue="formDirty = true"
							>
							</Field>
							<div class="invalid-feedback">
								{{ errors.expDate }}
							</div>
						</div>
					</fieldset>

					<div
						v-if="success === true"
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
						There was an error deleting this scholarship.
					</div>

					<!-- Action Buttons -->
					<div
						v-if="userCanEdit && isEditMode"
						aria-label="action buttons"
						class="mb-4"
					>
						<p v-if="formDirty" class="red">You have unsaved changes.</p>
						<p v-if="isSaveFailed" class="red">Error saving this scholarship.</p>
						<button class="btn btn-success" type="submit">
							<i class="fa fa-save fa-2x"></i>
						</button>
						<button
							v-if="itemExists && userCanDelete"
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
		<scholarship-delete-modal
			v-if="itemExists"
			:scholarship="record"
			@itemDeleted="markItemDeleted"
			@itemDeleteError="markItemDeleteError"
		></scholarship-delete-modal>
	</div>
</template>

<style scoped></style>

<script>
import Heading from "../utils/Heading.vue"
import NotFound from "../utils/NotFound.vue"
import ScholarshipDeleteModal from "./ScholarshipDeleteModal.vue"
import { Field, Form as VeeForm } from "vee-validate"
import * as Yup from "yup"

const STATUS_SAVE_FAILED = 3

export default {
	created() {
		if (this.startMode == "edit") {
			this.isEditMode = true
		}

		if (this.itemExists === false) {
			this.isDataLoaded = true
		} else {
			this.fetchScholarship(this.itemId)
		}
	},

	components: {
		Heading,
		NotFound,
		ScholarshipDeleteModal,
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
			apiError: {
				message: null,
				status: null
			},
			currentStatus: null,
			is404: false,
			isDataLoaded: false,
			isDeleted: false,
			isDeleteError: false,
			isEditMode: false,
			
			record: {
				id: "",
				title: "",
				active: false,
				url: "",
				amount: "",
				applyDate: "",
				expDate: ""
			},
			
			formDirty: false,
			isSaveFailed: false,
			success: false,
			successMessage: ""
		}
	},

	computed: {
		lockIcon: function () {
			return this.isEditMode
				? "<i class='fa fa-unlock'></i>"
				: "<i class='fa fa-lock'></i>"
		},

		userCanEdit: function () {
			return this.permissions[0].edit ? true : false
		},

		userCanDelete: function () {
			return this.permissions[0].delete ? true : false
		},

		scholarshipSchema: function () {
			return Yup.object().shape({
				title: Yup.string()
					.required("A title is required.")
					.max(255, "Title must be 255 characters or less."),
				url: Yup.string()
					.url("Please enter a valid URL.")
					.max(255, "URL must be 255 characters or less.")
					.nullable(true),
				amount: Yup.string()
					.max(255, "Amount must be 255 characters or less.")
					.nullable(true),
				// An empty date input casts to Invalid Date, so convert it to null.
				applyDate: Yup.date().transform(this.emptyDateToNull).nullable(true),
				expDate: Yup.date().transform(this.emptyDateToNull).nullable(true)
			})
		}
	},

	methods: {
		afterSubmitSucceeds: function () {
			this.formDirty = false

			if (!this.itemExists) {
				this.success = true
				this.successMessage = "Scholarship created."
				document.location = "/scholarships/" + this.record.id + "/edit"
			} else {
				this.success = true
				this.successMessage = "Update successful."
			}
		},

		fetchScholarship: function (itemId) {
			let self = this

			axios
				.get("/api/scholarships/" + itemId)
				.then(function (response) {
					self.record = self.normalizeRecord(response.data)
					self.isDataLoaded = true
				})
				.catch(function (error) {
					if (error.request.status == 404) {
						self.is404 = true
						self.isDataLoaded = true
					}
				})
		},

		markItemDeleted: function () {
			this.isDeleteError = false
			this.isDeleted = true
			setTimeout(function () {
				window.location.replace("/scholarships")
			}, 2000)
		},

		markItemDeleteError: function () {
			this.isDeleteError = true
		},

		emptyDateToNull: function (value, originalValue) {
			return originalValue === "" ? null : value
		},

		normalizeRecord: function (record) {
			let dateFields = ["applyDate", "expDate"]

			dateFields.forEach(function (field) {
				record[field] = record[field] ? record[field].substring(0, 10) : ""
			})

			return record
		},

		submitScholarship: function () {
			let self = this // 'this' loses scope within axios
			self.currentStatus = null
			let method = this.itemExists ? "put" : "post"
			let route = this.itemExists
				? "/api/scholarships/" + this.record.id
				: "/api/scholarships/"

			axios({
				method: method,
				url: route,
				data: self.record
			})
				.then(function (response) {
					self.record.id = response.data.id
					self.isSaveFailed = false
					self.afterSubmitSucceeds()
				})
				.catch(function (error) {
					self.currentStatus = STATUS_SAVE_FAILED
					self.isSaveFailed = true
				})
		},

		toggleEdit: function () {
			this.isEditMode = !this.isEditMode
		}
	}
}
</script>
