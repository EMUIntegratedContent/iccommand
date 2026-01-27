<template>
	<div>
		<not-found v-if="is404"></not-found>
		<div v-if="!isDataLoaded">
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
			class="alert alert-secondary fade show"
			role="alert"
		>
			This program has been deleted. You will now be redirected to the programs
			list page.
		</div>

		<!-- Main Area -->
		<div v-if="isDataLoaded === true && isDeleted === false && is404 === false">
			<heading> Program Information </heading>
			<div class="btn-group" role="group" aria-label="form navigation buttons">
				<button
					v-if="progExists && userCanEdit"
					type="button"
					class="btn btn-info pull-right"
					@click="toggleEdit"
				>
					<span v-html="lockIcon"></span>
				</button>
			</div>
			<div class="pt-2" id="programTabContent">
				<VeeForm
					class="form"
					v-slot="{ submitForm, errors, meta }"
					@submit="submitProgram"
					:validation-schema="programSchema"
				>
					<div class="form-group">
						<label
							>Full program name ("Program" column on Degrees & Programs public
							page) <span class="red" v-if="isEditMode">*</span></label
						>
						<Field
							name="progFullName"
							type="text"
							class="form-control"
							:class="{
								'is-invalid': errors.progFullName,
								'form-control-plaintext': !userCanEdit || !isEditMode
							}"
							:readonly="!userCanEdit || !isEditMode"
							v-model="record.full_name"
							@update:modelValue="formDirty = true"
						>
						</Field>
						<div class="invalid-feedback">
							{{ errors.progFullName }}
						</div>
					</div>
					<div class="form-group">
						<label
							>Program name (connects program to website URL)
							<span class="red" v-if="isEditMode">*</span></label
						>
						<Field
							name="progName"
							type="text"
							class="form-control"
							:class="{
								'is-invalid': errors.progName,
								'form-control-plaintext': !userCanEdit || !isEditMode
							}"
							:readonly="!userCanEdit || !isEditMode"
							v-model="record.program"
							@update:modelValue="formDirty = true"
						>
						</Field>
						<div class="invalid-feedback">
							{{ errors.progName }}
						</div>
					</div>
					<div>
						<template v-if="isEditMode">
							<Field name="progCatalog" type="hidden" v-model="record.catalog">
								<!-- for validation purposes only -->
							</Field>
							<label for="selectcatalog"
								>Catalog <span class="red">*</span></label
							>
							<VueMultiselect
								v-model="record.catalog"
								:options="['undergraduate', 'graduate']"
								:multiple="false"
								:clear-on-select="true"
								placeholder="Select catalog"
								id="selectcatalog"
								class="form-control"
								style="padding: 0"
								name="selectcatalog"
								:class="{ 'is-invalid': errors.progCatalog }"
								@update:modelValue="formDirty = true"
							>
							</VueMultiselect>
							<div class="invalid-feedback">
								{{ errors.progCatalog }}
							</div>
						</template>
						<template v-else>
							<div class="form-group">
								<label>Catalog</label>
								<Field
									v-model="record.catalog"
									name="catalog"
									type="text"
									class="form-control"
									:class="{
										'is-invalid': errors.catalog,
										'form-control-plaintext': !userCanEdit || !isEditMode
									}"
									:readonly="true"
								>
								</Field>
							</div>
						</template>
					</div>
					<div>
						<template v-if="isEditMode">
							<Field
								name="progCollege"
								type="hidden"
								v-model="record.college_id"
							>
								<!-- for validation purposes only -->
							</Field>
							<label for="selectclg" class="mt-2"
								>College <span class="red">*</span></label
							>
							<VueMultiselect
								v-model="selectedCollege"
								:options="colleges"
								:multiple="false"
								:clear-on-select="true"
								placeholder="Select college"
								label="college"
								id="selectclg"
								class="form-control"
								:class="{ 'is-invalid': errors.progCollege }"
								style="padding: 0"
								name="selectclg"
								@update:modelValue="formDirty = true"
							>
							</VueMultiselect>
							<div class="invalid-feedback">
								{{ errors.progCollege }}
							</div>
						</template>
						<template v-else>
							<div class="form-group">
								<label>College</label>
								<Field
									v-if="selectedCollege"
									v-model="selectedCollege.college"
									name="college"
									type="text"
									class="form-control"
									:class="{
										'is-invalid': errors.college_id,
										'form-control-plaintext': !userCanEdit || !isEditMode
									}"
									:readonly="true"
								>
								</Field>
							</div>
						</template>
					</div>
					<div>
						<template v-if="isEditMode">
							<Field
								name="progDept"
								type="hidden"
								v-model="record.department_id"
							>
								<!-- for validation purposes only -->
							</Field>
							<label for="selectdept" class="mt-2"
								>Department <span class="red">*</span></label
							>
							<VueMultiselect
								v-model="selectedDepartment"
								:options="departments"
								:multiple="false"
								:clear-on-select="true"
								placeholder="Select department"
								label="department"
								id="selectdept"
								class="form-control"
								:class="{ 'is-invalid': errors.progDept }"
								style="padding: 0"
								name="selectdept"
								@update:modelValue="formDirty = true"
							>
							</VueMultiselect>
							<div class="invalid-feedback">
								{{ errors.progDept }}
							</div>
						</template>
						<template v-else>
							<div class="form-group">
								<label>Department</label>
								<Field
									v-if="selectedDepartment"
									v-model="selectedDepartment.department"
									name="department"
									type="text"
									class="form-control"
									:class="{
										'is-invalid': errors.department_id,
										'form-control-plaintext': !userCanEdit || !isEditMode
									}"
									:readonly="true"
								>
								</Field>
							</div>
						</template>
					</div>
					<div>
						<template v-if="isEditMode">
							<Field name="progType" type="hidden" v-model="record.type_id">
								<!-- for validation purposes only -->
							</Field>
							<label for="selectprogtype" class="mt-2"
								>Program Type <span class="red">*</span></label
							>
							<VueMultiselect
								v-model="selectedProgType"
								:options="progTypes"
								:multiple="false"
								:clear-on-select="true"
								placeholder="Select program type"
								label="type"
								id="selectprogtype"
								class="form-control"
								:class="{ 'is-invalid': errors.progDept }"
								style="padding: 0"
								name="selectprogtype"
								@update:modelValue="formDirty = true"
							>
							</VueMultiselect>
							<div class="invalid-feedback">
								{{ errors.progType }}
							</div>
						</template>
						<template v-else>
							<div class="form-group">
								<label>Program Type</label>
								<Field
									v-if="selectedProgType"
									v-model="selectedProgType.type"
									name="progtype"
									type="text"
									class="form-control"
									:class="{
										'is-invalid': errors.type_id,
										'form-control-plaintext': !userCanEdit || !isEditMode
									}"
									:readonly="true"
								>
								</Field>
							</div>
						</template>
					</div>
					<div>
						<template v-if="isEditMode">
							<Field name="progDegree" type="hidden" v-model="record.degree_id">
								<!-- for validation purposes only -->
							</Field>
							<label for="selectdegree" class="mt-2"
								>Degree Classification <span class="red">*</span></label
							>
							<VueMultiselect
								v-model="selectedDegree"
								:options="degrees"
								:multiple="false"
								:clear-on-select="true"
								placeholder="Select degree classification"
								label="degree"
								id="selectdegree"
								class="form-control"
								:class="{ 'is-invalid': errors.progDegree }"
								style="padding: 0"
								name="selectdegree"
								@update:modelValue="formDirty = true"
							>
							</VueMultiselect>
							<div class="invalid-feedback">
								{{ errors.progDegree }}
							</div>
						</template>
						<template v-else>
							<div class="form-group">
								<label>Degree Classification</label>
								<Field
									v-if="selectedDegree"
									v-model="selectedDegree.degree"
									name="degree"
									type="text"
									class="form-control"
									:class="{
										'is-invalid': errors.degree_id,
										'form-control-plaintext': !userCanEdit || !isEditMode
									}"
									:readonly="true"
								>
								</Field>
							</div>
						</template>
					</div>
					<div>
						<template v-if="isEditMode">
							<Field name="progMode" type="hidden" v-model="record.delivery_ids">
								<!-- for validation purposes only -->
							</Field>
							<label for="selectmode" class="mt-2"
								>Mode <span class="red">*</span></label
							>
							<VueMultiselect
								v-model="selectedMode"
								track-by="id"
								:options="modes"
								:multiple="true"
								:clear-on-select="true"
								placeholder="Select mode"
								label="mode"
								id="selectmode"
								class="form-control"
								:class="{ 'is-invalid': errors.progMode }"
								style="padding: 0"
								name="selectmode"
								@update:modelValue="formDirty = true"
							>
							</VueMultiselect>
							<div class="invalid-feedback">
								{{ errors.progMode }}
							</div>
						</template>
						<template v-else>
							<div class="form-group">
								<label>Mode</label>
								<input
									v-if="selectedMode && selectedMode.length"
									type="text"
									:value="modeDisplay"
									class="form-control"
									:class="{ 'is-invalid': errors.delivery_ids, 'form-control-plaintext': !userCanEdit || !isEditMode }"
									readonly
								/>
							</div>
						</template>
					</div>
					<div class="form-group">
						<label class="mt-2">Website</label>
						<Field
							name="programUrl"
							type="text"
							class="form-control"
							:class="{
								'is-invalid': errors.url,
								'form-control-plaintext': !userCanEdit || !isEditMode
							}"
							:readonly="!userCanEdit || !isEditMode"
							v-model="record.url"
							@update:modelValue="formDirty = true"
						>
						</Field>
						<div class="invalid-feedback">
							{{ errors.url }}
						</div>
					</div>
					<div>
						<template v-if="isEditMode">
							<label for="selectkeywords" class="mt-2">Keywords</label>
							<VueMultiselect
								v-model="selectedKeywords"
								track-by="id"
								:options="keywords"
								:multiple="true"
								:clear-on-select="true"
								placeholder="Select keywords"
								label="keyword"
								id="selectkeywords"
								class="form-control"
								style="padding: 0"
								name="selectkeywords"
								@update:modelValue="formDirty = true"
							>
							</VueMultiselect>
						</template>
						<template v-else>
							<div class="form-group">
								<label>Keywords</label>
								<input
									v-if="selectedKeywords && selectedKeywords.length"
									type="text"
									:value="keywordDisplay"
									class="form-control form-control-plaintext"
									readonly
								/>
								<input
									v-else
									type="text"
									value=""
									class="form-control form-control-plaintext"
									readonly
								/>
							</div>
						</template>
					</div>
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
						There was an error deleting this program.
					</div>
					<!-- Action Buttons -->
					<div
						v-if="userCanEdit && isEditMode"
						aria-label="action buttons"
						class="my-4"
					>
						<p v-if="formDirty" class="red">You have unsaved changes.</p>
						<p v-if="isSaveFailed" class="red">
							Error saving this program. {{ apiError.message }}
						</p>
						<button type="submit" class="btn btn-success">
							<i class="fa fa-save fa-2x"></i>
						</button>
						<button
							v-if="progExists && this.permissions[0].delete"
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
		<!-- Delete Item Modal -->
		<program-delete-modal
			:program="record"
			@programDeleted="markProgramDeleted"
			@programDeleteError="markProgramDeleteError"
		>
		</program-delete-modal>
	</div>
</template>

<style>
.red {
	color: #ff0033;
}
</style>

<script>
import Heading from "../utils/Heading.vue"
import VueMultiselect from "vue-multiselect"
import NotFound from "../utils/NotFound.vue"
import ProgramDeleteModal from "./ProgramDeleteModal.vue"
import { ErrorMessage, Field, Form as VeeForm } from "vee-validate"
import * as Yup from "yup"

const STATUS_INITIAL = 0
const STATUS_SAVE_FAILED = 1

export default {
	created() {
		// Detect if the form should be in edit mode from the start; default is false.
		if (this.startMode === "edit") {
			this.isEditMode = true
		}

		if (this.progId) {
			this.fetchProgram(this.progId)
		} else {
			this.currentStatus = STATUS_INITIAL
			this.isDataLoaded = true
		}
		this.fetchColleges()
		this.fetchDepts()
		this.fetchProgTypes()
		this.fetchDegrees()
		this.fetchKeywords()
	},

	components: {
		Heading,
		VueMultiselect,
		ProgramDeleteModal,
		NotFound,
		Field,
		VeeForm,
		ErrorMessage
	},

	props: {
		progExists: {
			type: Boolean,
			required: true
		},

		progId: {
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
			apiError: {
				message: null,
				status: null
			},
			colleges: [],
			degrees: [],
			departments: [],
			keywords: [],
			is404: false,
			isDeleteError: false,
			isDataLoaded: false,
			isDeleted: false,
			isEditMode: false, // This is true if the forms are editable.
			modes: [
				{ id: 1, mode: "In-Person/Hybrid" },
				{ id: 2, mode: "Online" },
				{ id: 3, mode: "Hyflex" }
			],
			progTypes: [],
			record: {
				id: 0,
				full_name: null,
				program: null,
				catalog: null,
				department_id: null,
				// store as array of ids for multiple modes
				delivery_ids: [],
				// store as array of ids for keywords
				keyword_ids: [],
				type_id: null,
				degree_id: null
			},
			formDirty: false,
			success: false,
			successMessage: ""
		}
	},

	computed: {
		isStatusInitial() {
			return this.currentStatus === STATUS_INITIAL
		},
		isSaveFailed() {
			return this.currentStatus === STATUS_SAVE_FAILED
		},
		programSchema() {
			let yupObj = {
				progFullName: Yup.string().required().label("Program full name "),
				progName: Yup.string().required().label("Program name "),
				progCatalog: Yup.string().required().label("Catalog "),
				progCollege: Yup.number().required().label("College "),
				progDept: Yup.number().required().label("Department "),
				progType: Yup.number().required().label("Program Type "),
				progDegree: Yup.number().required().label("Degree Classification "),
				progMode: Yup.array().of(Yup.number()).min(1).required().label("Mode ")
			}
			return Yup.object(yupObj)
		},
		/**
		 * Gets the heading icon.
		 * @return {string} The heading icon.
		 */
		headingIcon: function () {
			return "<i class='fa fa-map'></i>"
		},

		/**
		 * Gets the appropriate lock icon based if the user is in edit mode.
		 * @return {string} The lock icon being unlocked if the user is in edit
		 * mode; the lock icon being locked otherwise.
		 */
		lockIcon: function () {
			return this.isEditMode
				? "<i class='fa fa-unlock'></i>"
				: "<i class='fa fa-lock'></i>"
		},

		/**
		 * Determines if the user can edit.
		 * @return {boolean} True if the user can edit; false otherwise.
		 */
		userCanEdit: function () {
			return (this.progExists && this.permissions[0].edit) ||
				(!this.progExists && this.permissions[0].create)
				? true
				: false
		},
		// for the multiselect since it can't bind directly to the record.college_id without the full object
		selectedCollege: {
			get() {
				return (
					this.colleges.find((d) => d.id === this.record.college_id) || null
				)
			},
			set(newValue) {
				this.record.college_id = newValue ? newValue.id : null
			}
		},
		// for the multiselect since it can't bind directly to the record.department_id without the full object
		selectedDepartment: {
			get() {
				return (
					this.departments.find(
						(dept) => dept.id === this.record.department_id
					) || null
				)
			},
			set(newValue) {
				this.record.department_id = newValue ? newValue.id : null
			}
		},
		// for the multiselect since it can't bind directly to the record.delivery_ids without the full object
		selectedMode: {
			get() {
				if (!this.record.delivery_ids || !Array.isArray(this.record.delivery_ids)) return []
				return this.modes.filter((mode) => this.record.delivery_ids.includes(mode.id))
			},
			set(newValues) {
				// newValues is an array of mode objects (or null). Store as array of ids.
				if (!newValues) {
					this.record.delivery_ids = []
					return
				}
				this.record.delivery_ids = newValues.map((m) => m.id)
			}
		},
		modeDisplay: {
			get() {
				return this.selectedMode && this.selectedMode.length
					? this.selectedMode.map((m) => m.mode).join(', ')
					: ''
			},
			set(value) {
				// v-model requires a setter. Non-edit mode is readonly, but some
				// form components still attempt to write the value. Provide a
				// tolerant setter that maps a comma-separated string back to
				// delivery_ids where possible, or clears the array if empty.
				if (!value || (typeof value === 'string' && value.trim() === '')) {
					this.record.delivery_ids = []
					return
				}
				if (typeof value === 'string') {
					const names = value
						.split(',')
						.map((s) => s.trim())
						.filter(Boolean)
					// map names back to ids when possible
					const ids = this.modes
						.filter((m) => names.includes(m.mode))
						.map((m) => m.id)
					this.record.delivery_ids = ids
				}
			}
		},
		// for the multiselect since it can't bind directly to the record.keyword_ids without the full object
		selectedKeywords: {
			get() {
				if (!this.record.keyword_ids || !Array.isArray(this.record.keyword_ids)) return []
				return this.keywords.filter((keyword) => this.record.keyword_ids.includes(keyword.id))
			},
			set(newValues) {
				// newValues is an array of keyword objects (or null). Store as array of ids.
				if (!newValues) {
					this.record.keyword_ids = []
					return
				}
				this.record.keyword_ids = newValues.map((k) => k.id)
			}
		},
		keywordDisplay: {
			get() {
				return this.selectedKeywords && this.selectedKeywords.length
					? this.selectedKeywords.map((k) => k.keyword).join(', ')
					: ''
			}
		},
		// for the multiselect since it can't bind directly to the record.type_id without the full object
		selectedProgType: {
			get() {
				return (
					this.progTypes.find((mode) => mode.id === this.record.type_id) || null
				)
			},
			set(newValue) {
				this.record.type_id = newValue ? newValue.id : null
			}
		},
		// for the multiselect since it can't bind directly to the record.degree_id without the full object
		selectedDegree: {
			get() {
				return this.degrees.find((d) => d.id === this.record.degree_id) || null
			},
			set(newValue) {
				this.record.degree_id = newValue ? newValue.id : null
			}
		}
	},

	methods: {
		/**
		 * Goes to edit mode after submitting the new program.
		 */
		afterSubmitSucceeds: function () {
			this.formDirty = false
			// Since the new item has been submitted, go to edit mode.
			if (!this.progExists) {
				this.success = true
				this.successMessage = "Program created."
				document.location = "/programs/" + this.record.id
			} else {
				this.success = true
				this.successMessage = "Update successful."
				setTimeout(() => {
					this.success = false
					self.currentStatus = STATUS_INITIAL
				}, 3000)
			}
		},

		/**
		 * Gets the program by using the specified ID.
		 * @param {string} progId The ID of the program.
		 */
		fetchProgram: function (progId) {
			const self = this
			axios
				.get("/api/programs/" + progId)
				.then(function (response) {
					// Success.
					// Normalize delivery_ids to array of ids for multi-select support
					let structuredResponse = response.data
					structuredResponse.delivery_ids = self.formatDeliveryIds(response.data.delivery_ids)
					structuredResponse.keyword_ids = self.formatKeywordIds(response.data.keyword_ids)
					self.record = structuredResponse
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
		 * Get the list of colleges
		 */
		fetchColleges: function () {
			const self = this
			axios
				.get("/api/programs/colleges")
				.then(function (response) {
					// Success.
					self.colleges = response.data
				})
				.catch(function (error) {
					// Failure.
					console.log(error)
				})
		},

		/**
		 * Get the list of departments
		 */
		fetchDepts: function () {
			const self = this
			axios
				.get("/api/programs/departments")
				.then(function (response) {
					// Success.
					self.departments = response.data
				})
				.catch(function (error) {
					// Failure.
					console.log(error)
				})
		},

		/**
		 * Get the list of program types
		 */
		fetchProgTypes: function () {
			const self = this
			axios
				.get("/api/programs/types")
				.then(function (response) {
					// Success.
					self.progTypes = response.data
				})
				.catch(function (error) {
					// Failure.
					console.log(error)
				})
		},

		/**
		 * Get the list of degrees
		 */
		fetchDegrees: function () {
			const self = this
			axios
				.get("/api/programs/degrees")
				.then(function (response) {
					// Success.
					self.degrees = response.data
				})
				.catch(function (error) {
					// Failure.
					console.log(error)
				})
		},

		/**
		 * Get the list of keywords
		 */
		fetchKeywords: function () {
			const self = this
			axios
				.get("/api/programs/keywords")
				.then(function (response) {
					// Success.
					self.keywords = response.data.keywords
				})
				.catch(function (error) {
					// Failure.
					console.log(error)
				})
		},

		formatKeywordIds: function (keywordIds) {
			if (!keywordIds) return []
			return keywordIds
				.split(',')
				.map((keyword_id) => Number(keyword_id.trim()))
		},

		formatDeliveryIds: function (deliveryIds) {
			if (!deliveryIds) return []
			return deliveryIds
				.split(',')
				.map((delivery_id) => Number(delivery_id.trim()))
		},

		/**
		 * Gets called from the @programDeleted event emission from the delete Modal.
		 */
		markProgramDeleted: function () {
			this.isDeleteError = false
			this.isDeleted = true
			setTimeout(function () {
				// This record doesn't exist anymore, so send the user back to the
				// programs list page.
				window.location.replace("/programs/list")
			}, 3000)
		},

		/**
		 * Marks the item delete error; gets called if there is an error in deleting
		 * an item.
		 */
		markProgramDeleteError: function () {
			let self = this
			this.isDeleted = false
			this.isDeleteError = true
			setTimeout(function () {
				self.isDeleteError = false
			}, 5000)
		},

		/**
		 * Submits the form via the API.
		 */
		submitProgram: function () {
			this.currentStatus = null
			const method = this.progExists ? "put" : "post"
			const route = "/api/programs/"

			const self = this
			/* Ajax (Axios) Submission */
			axios({
				method: method,
				url: route,
				data: self.record
			},
			{
				headers: {
					"Content-Type": "application/x-www-form-urlencoded"
				}
			}
			)
				.then(function (response) {
					// Success.
					let structuredResponse = response.data;
					structuredResponse.delivery_ids = self.formatDeliveryIds(response.data.delivery_ids)
					structuredResponse.keyword_ids = self.formatKeywordIds(response.data.keyword_ids)
					self.record = structuredResponse // This sets the program's ID.
					self.afterSubmitSucceeds()
				})
				.catch(function (error) {
					// Failure.
					self.currentStatus = STATUS_SAVE_FAILED
					self.apiError.status = error.response.status
					self.apiError.message = error.response.data
				})
		},

		/**
		 * Sets the variable @isEditMode to the appropriate boolean value.
		 */
		toggleEdit: function () {
			this.isEditMode === true
				? (this.isEditMode = false)
				: (this.isEditMode = true)
		}
	}
}
</script>
