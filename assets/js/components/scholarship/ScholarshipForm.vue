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
						<legend>Eligibility</legend>
						<div class="form-group">
							<label for="scholarshipGender">Gender</label>
							<select
								id="scholarshipGender"
								class="form-control"
								:disabled="!userCanEdit || !isEditMode"
								v-model="record.gender"
								@change="formDirty = true"
							>
								<option value="">No restriction</option>
								<option v-for="opt in options.gender" :key="opt" :value="opt">
									{{ opt }}
								</option>
							</select>
						</div>
						<div class="form-group">
							<label for="scholarshipEthnicity">Ethnicity</label>
							<select
								id="scholarshipEthnicity"
								class="form-control"
								:disabled="!userCanEdit || !isEditMode"
								v-model="record.ethnicity"
								@change="formDirty = true"
							>
								<option value="">No restriction</option>
								<option v-for="opt in options.ethnicity" :key="opt" :value="opt">
									{{ opt }}
								</option>
							</select>
						</div>
						<div class="form-group">
							<label for="scholarshipGpa">Minimum GPA</label>
							<select
								id="scholarshipGpa"
								class="form-control"
								:disabled="!userCanEdit || !isEditMode"
								v-model="record.gpa"
								@change="formDirty = true"
							>
								<option value="">No restriction</option>
								<option v-for="opt in options.gpa" :key="opt" :value="opt">
									{{ opt }}
								</option>
							</select>
						</div>
						<div class="form-group">
							<label for="scholarshipStandingClass">Class standing</label>
							<VueMultiselect
								id="scholarshipStandingClass"
								:options="options.classStanding"
								:multiple="true"
								:close-on-select="false"
								:disabled="!userCanEdit || !isEditMode"
								placeholder="No restriction"
								v-model="standingClassModel"
								@update:modelValue="formDirty = true"
							>
							</VueMultiselect>
						</div>
						<div class="form-group">
							<label>Enrollment requirement</label>
							<Field
								name="enrollment"
								type="text"
								class="form-control"
								:class="{
									'is-invalid': errors.enrollment,
									'form-control-plaintext': !userCanEdit || !isEditMode
								}"
								:readonly="!userCanEdit || !isEditMode"
								v-model="record.enrollment"
								placeholder="e.g. full time"
								@update:modelValue="formDirty = true"
							>
							</Field>
							<div class="invalid-feedback">
								{{ errors.enrollment }}
							</div>
						</div>
						<div class="form-group">
							<label>Available to transfer students</label>
							<div>
								<div
									v-for="opt in options.transfer"
									:key="opt"
									class="form-check form-check-inline"
								>
									<input
										:id="'scholarshipTransfer' + opt"
										type="radio"
										class="form-check-input"
										:value="opt"
										:disabled="!userCanEdit || !isEditMode"
										v-model="record.transfer"
										@change="formDirty = true"
									/>
									<label class="form-check-label" :for="'scholarshipTransfer' + opt">
										{{ opt }}
									</label>
								</div>
							</div>
						</div>
						<div class="form-group">
							<label>Housing requirement</label>
							<div>
								<div
									v-for="opt in options.housing"
									:key="opt"
									class="form-check form-check-inline"
								>
									<input
										:id="'scholarshipHousing' + opt"
										type="radio"
										class="form-check-input"
										:value="opt"
										:disabled="!userCanEdit || !isEditMode"
										v-model="record.housing"
										@change="formDirty = true"
									/>
									<label class="form-check-label" :for="'scholarshipHousing' + opt">
										{{ opt }}
									</label>
								</div>
							</div>
						</div>
					</fieldset>

					<fieldset>
						<legend>Location</legend>
						<div class="form-group">
							<label>County</label>
							<Field
								name="county"
								type="text"
								class="form-control"
								:class="{
									'is-invalid': errors.county,
									'form-control-plaintext': !userCanEdit || !isEditMode
								}"
								:readonly="!userCanEdit || !isEditMode"
								v-model="record.county"
								@update:modelValue="formDirty = true"
							>
							</Field>
							<div class="invalid-feedback">
								{{ errors.county }}
							</div>
						</div>
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
						<div class="form-group">
							<label for="scholarshipState">State</label>
							<select
								id="scholarshipState"
								class="form-control"
								:disabled="!userCanEdit || !isEditMode"
								v-model="record.state"
								@change="formDirty = true"
							>
								<option value="">No restriction</option>
								<option v-for="opt in options.state" :key="opt" :value="opt">
									{{ opt }}
								</option>
							</select>
						</div>
						<div class="form-group">
							<label>High school</label>
							<Field
								name="highSchool"
								type="text"
								class="form-control"
								:class="{
									'is-invalid': errors.highSchool,
									'form-control-plaintext': !userCanEdit || !isEditMode
								}"
								:readonly="!userCanEdit || !isEditMode"
								v-model="record.highSchool"
								@update:modelValue="formDirty = true"
							>
							</Field>
							<div class="invalid-feedback">
								{{ errors.highSchool }}
							</div>
						</div>
					</fieldset>

					<fieldset>
						<legend>Affiliations and keywords</legend>
						<div class="form-group">
							<label>Organizations, club, fraternity, sorority, etc.</label>
							<Field
								name="organizations"
								type="text"
								class="form-control"
								:class="{
									'is-invalid': errors.organizations,
									'form-control-plaintext': !userCanEdit || !isEditMode
								}"
								:readonly="!userCanEdit || !isEditMode"
								v-model="record.organizations"
								@update:modelValue="formDirty = true"
							>
							</Field>
							<div class="invalid-feedback">
								{{ errors.organizations }}
							</div>
						</div>
						<div class="form-group">
							<label>Keywords</label>
							<Field
								name="keywords"
								as="textarea"
								rows="3"
								class="form-control"
								:class="{
									'is-invalid': errors.keywords,
									'form-control-plaintext': !userCanEdit || !isEditMode
								}"
								:readonly="!userCanEdit || !isEditMode"
								v-model="record.keywords"
								placeholder="Separate keywords with commas"
								@update:modelValue="formDirty = true"
							>
							</Field>
							<div class="invalid-feedback">
								{{ errors.keywords }}
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

					<fieldset>
						<legend>Details</legend>
						<div>
							<label class="mt-2">Contact information</label>
							<template v-if="!userCanEdit || !isEditMode">
								<div v-if="!record.contact">---</div>
								<div v-else v-html="record.contact"></div>
							</template>
							<template v-else>
								<ckeditor
									v-model="contactModel"
									:editor="editor"
									:config="ckConfig"
									name="scholarshipContact"
									@update:modelValue="formDirty = true"
								>
								</ckeditor>
							</template>
						</div>
						<div>
							<label class="mt-2">Application procedure</label>
							<template v-if="!userCanEdit || !isEditMode">
								<div v-if="!record.appProc">---</div>
								<div v-else v-html="record.appProc"></div>
							</template>
							<template v-else>
								<ckeditor
									v-model="appProcModel"
									:editor="editor"
									:config="ckConfig"
									name="scholarshipAppProc"
									@update:modelValue="formDirty = true"
								>
								</ckeditor>
							</template>
						</div>
						<div>
							<label class="mt-2">Additional information</label>
							<template v-if="!userCanEdit || !isEditMode">
								<div v-if="!record.description">---</div>
								<div v-else v-html="record.description"></div>
							</template>
							<template v-else>
								<ckeditor
									v-model="descriptionModel"
									:editor="editor"
									:config="ckConfig"
									name="scholarshipDescription"
									@update:modelValue="formDirty = true"
								>
								</ckeditor>
							</template>
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
import ClassicEditor from "@ckeditor/ckeditor5-build-classic"
import VueMultiselect from "vue-multiselect"
import { Field, Form as VeeForm } from "vee-validate"
import * as Yup from "yup"

const STATUS_SAVE_FAILED = 3

export default {
	created() {
		if (this.startMode == "edit") {
			this.isEditMode = true
		}

		this.fetchOptions()

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
		VueMultiselect,
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
			
			editor: ClassicEditor,
			ckConfig: {
				toolbar: [
					"Bold",
					"Italic",
					"Undo",
					"Redo",
					"NumberedList",
					"BulletedList",
					"Link"
				]
			},

			options: {
				gender: [],
				ethnicity: [],
				gpa: [],
				classStanding: [],
				housing: [],
				transfer: [],
				state: []
			},

			record: {
				id: "",
				title: "",
				active: false,
				url: "",
				amount: "",
				applyDate: "",
				expDate: "",
				gender: "",
				ethnicity: "",
				gpa: "",
				standingClass: "",
				enrollment: "",
				transfer: "",
				housing: "",
				county: "",
				city: "",
				state: "",
				highSchool: "",
				organizations: "",
				keywords: "",
				contact: "",
				appProc: "",
				description: ""
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

		// CKEditor throws on null, so empty rich text has to reach it as a string.
		contactModel: {
			get() {
				return this.record.contact || ""
			},
			set(value) {
				this.record.contact = value
			}
		},

		appProcModel: {
			get() {
				return this.record.appProc || ""
			},
			set(value) {
				this.record.appProc = value
			}
		},

		descriptionModel: {
			get() {
				return this.record.description || ""
			},
			set(value) {
				this.record.description = value
			}
		},

		// Class standing is multi-select but stored comma separated.
		standingClassModel: {
			get() {
				return this.record.standingClass
					? this.record.standingClass.split(",").map((s) => s.trim())
					: []
			},
			set(value) {
				this.record.standingClass = value.length ? value.join(", ") : ""
			}
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
				enrollment: Yup.string()
					.max(255, "Enrollment requirement must be 255 characters or less.")
					.nullable(true),
				county: Yup.string()
					.max(160, "County must be 160 characters or less.")
					.nullable(true),
				city: Yup.string()
					.max(160, "City must be 160 characters or less.")
					.nullable(true),
				highSchool: Yup.string()
					.max(255, "High school must be 255 characters or less.")
					.nullable(true),
				organizations: Yup.string()
					.max(255, "Organizations must be 255 characters or less.")
					.nullable(true),
				keywords: Yup.string()
					.max(255, "Keywords must be 255 characters or less.")
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

		fetchOptions: function () {
			let self = this

			axios
				.get("/api/scholarships/options")
				.then(function (response) {
					self.options = response.data
				})
				.catch(function (error) {
					console.log("Error fetching scholarship options:", error)
				})
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
