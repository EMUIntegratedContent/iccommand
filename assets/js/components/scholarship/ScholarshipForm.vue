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
					<fieldset class="card mb-4" aria-labelledby="scholarshipBasicHeading">
						<div class="card-header d-flex justify-content-between align-items-center">
							<h5 id="scholarshipBasicHeading" class="mb-0">Basic Information</h5>
							<small class="text-muted"><span class="red">*</span> Required field</small>
						</div>
						<div class="card-body">
							<div class="form-group">
								<label>Scholarship Title <span class="red">*</span></label>
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
							<div class="row">
								<div class="col-md-6">
									<div class="form-group">
										<label>Award Amount / Value</label>
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
											placeholder="e.g., $1,500 - $2,500 or Tuition &amp; Fees"
											@update:modelValue="formDirty = true"
										>
										</Field>
										<div class="invalid-feedback">
											{{ errors.amount }}
										</div>
									</div>
								</div>
								<div class="col-md-6">
									<div class="form-group">
										<label>Application Link / URL <span class="red">*</span></label>
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
										<small class="form-text text-muted">
											Target link for the student "Application Information" button.
										</small>
									</div>
								</div>
							</div>
							<div class="form-group">
								<label class="mt-2">Overview / Summary <span class="red">*</span></label>
								<template v-if="!userCanEdit || !isEditMode">
									<div v-if="!record.overview">---</div>
									<div v-else v-html="record.overview"></div>
								</template>
								<template v-else>
									<Field name="overview" v-slot="{ errorMessage }" v-model="overviewModel">
										<div :class="{ 'is-invalid-ckeditor': errorMessage }">
											<ckeditor
												:model-value="overviewModel"
												:editor="editor"
												:config="ckConfig"
												name="scholarshipOverview"
												@update:modelValue="updateRichText('overview', $event)"
											>
											</ckeditor>
										</div>
										<div v-if="errorMessage" class="invalid-feedback-ckeditor">
											{{ errorMessage }}
										</div>
									</Field>
								</template>
							</div>
							<div class="form-group">
								<div class="form-check form-check-inline">
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
										<small class="text-muted ml-1">(Publish to public feed)</small>
									</label>
								</div>
								<div class="form-check form-check-inline">
									<input
										id="scholarshipCatchAll"
										type="checkbox"
										class="form-check-input"
										:disabled="!userCanEdit || !isEditMode"
										v-model="record.catchAll"
										@change="formDirty = true"
									/>
									<label class="form-check-label" for="scholarshipCatchAll">
										Catch-all Scholarship
										<small class="text-muted ml-1">(General scholarship not tied to specific criteria)</small>
									</label>
								</div>
							</div>
						</div>
					</fieldset>

					<fieldset class="card mb-4" aria-labelledby="scholarshipEligibilityHeading">
						<div class="card-header">
							<h5 id="scholarshipEligibilityHeading" class="mb-0">Eligibility &amp; Criteria</h5>
						</div>
						<div class="card-body">
							<div class="row">
								<div class="col-md-4">
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
								</div>
								<div class="col-md-4">
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
								</div>
								<div class="col-md-4">
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
								</div>
							</div>
							<div class="form-group">
								<label for="scholarshipStandingClass">Class Standing</label>
								<VueMultiselect
									id="scholarshipStandingClass"
									:options="options.classStanding"
									:multiple="true"
									:close-on-select="false"
									:disabled="!userCanEdit || !isEditMode"
									placeholder="No restriction (All Standings Eligible)"
									v-model="standingClassModel"
									@update:modelValue="formDirty = true"
								>
								</VueMultiselect>
								<small class="form-text text-muted">
									Leaving empty represents no restriction.
								</small>
							</div>
							<div class="form-group">
								<label>Enrollment Requirement</label>
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
									placeholder="e.g., Full-time, 6+ credit hours, or specific course"
									@update:modelValue="formDirty = true"
								>
								</Field>
								<div class="invalid-feedback">
									{{ errors.enrollment }}
								</div>
							</div>
							<hr />
							<div class="row">
								<div class="col-md-6">
									<div class="form-group">
										<label>Available to Transfer Students</label>
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
								</div>
								<div class="col-md-6">
									<div class="form-group">
										<label>Housing Requirement</label>
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
								</div>
							</div>
							<div class="form-group">
								<div class="form-check form-check-inline">
									<input
										id="scholarshipIsFafsa"
										type="checkbox"
										class="form-check-input"
										:disabled="!userCanEdit || !isEditMode"
										v-model="record.isFafsa"
										@change="formDirty = true"
									/>
									<label class="form-check-label" for="scholarshipIsFafsa">
										Requires a FAFSA
									</label>
								</div>
								<div class="form-check form-check-inline">
									<input
										id="scholarshipIsParent"
										type="checkbox"
										class="form-check-input"
										:disabled="!userCanEdit || !isEditMode"
										v-model="record.isParent"
										@change="formDirty = true"
									/>
									<label class="form-check-label" for="scholarshipIsParent">
										Must be a parent
									</label>
								</div>
								<div class="form-check form-check-inline">
									<input
										id="scholarshipIsBilingual"
										type="checkbox"
										class="form-check-input"
										:disabled="!userCanEdit || !isEditMode"
										v-model="record.isBilingual"
										@change="formDirty = true"
									/>
									<label class="form-check-label" for="scholarshipIsBilingual">
										Must be bilingual
									</label>
								</div>
							</div>
						</div>
					</fieldset>

					<fieldset class="card mb-4" aria-labelledby="scholarshipLocationHeading">
						<div class="card-header">
							<h5 id="scholarshipLocationHeading" class="mb-0">Location Restrictions</h5>
						</div>
						<div class="card-body">
							<div class="row">
								<div class="col-md-6 col-lg-3">
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
								</div>
								<div class="col-md-6 col-lg-3">
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
											placeholder="e.g., Washtenaw"
											@update:modelValue="formDirty = true"
										>
										</Field>
										<div class="invalid-feedback">
											{{ errors.county }}
										</div>
									</div>
								</div>
								<div class="col-md-6 col-lg-3">
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
											placeholder="e.g., Ypsilanti"
											@update:modelValue="formDirty = true"
										>
										</Field>
										<div class="invalid-feedback">
											{{ errors.city }}
										</div>
									</div>
								</div>
								<div class="col-md-6 col-lg-3">
									<div class="form-group">
										<label>High School</label>
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
											placeholder="Specific High School"
											@update:modelValue="formDirty = true"
										>
										</Field>
										<div class="invalid-feedback">
											{{ errors.highSchool }}
										</div>
									</div>
								</div>
							</div>
						</div>
					</fieldset>

					<fieldset class="card mb-4" aria-labelledby="scholarshipAffiliationsHeading">
						<div class="card-header">
							<h5 id="scholarshipAffiliationsHeading" class="mb-0">Affiliations &amp; Programs</h5>
						</div>
						<div class="card-body">
							<div class="row">
								<div class="col-md-6">
									<div class="form-group">
										<label for="scholarshipCollege">Awarding College</label>
										<select
											id="scholarshipCollege"
											class="form-control"
											:disabled="!userCanEdit || !isEditMode"
											v-model="record.collegeId"
											@change="formDirty = true"
										>
											<option value="">None</option>
											<option v-for="c in colleges" :key="c.id" :value="c.id">
												{{ c.college }}
											</option>
										</select>
									</div>
								</div>
								<div class="col-md-6">
									<div class="form-group">
										<label for="scholarshipDepartment">Awarding Department</label>
										<select
											id="scholarshipDepartment"
											class="form-control"
											:disabled="!userCanEdit || !isEditMode"
											v-model="record.departmentId"
											@change="formDirty = true"
										>
											<option value="">None</option>
											<option v-for="d in departments" :key="d.id" :value="d.id">
												{{ d.department }}
											</option>
										</select>
									</div>
								</div>
							</div>
							<div class="form-group">
								<label for="scholarshipPrograms">Related Programs / Majors</label>
								<VueMultiselect
									id="scholarshipPrograms"
									:options="programOptions"
									:multiple="true"
									:close-on-select="false"
									:disabled="!userCanEdit || !isEditMode"
									label="full_name"
									track-by="id"
									placeholder="Search and select majors (e.g. Computer Science, Nursing)"
									v-model="selectedProgramsModel"
									@update:modelValue="formDirty = true"
								>
								</VueMultiselect>
							</div>
							<div
								v-if="record.programLinks && record.programLinks.length"
								class="form-group"
							>
								<table class="table table-sm">
									<thead>
										<tr>
											<th>Program</th>
											<th>Notes</th>
										</tr>
									</thead>
									<tbody>
										<tr v-for="link in record.programLinks" :key="link.programId">
											<td class="align-middle">
												{{ programName(link.programId) }}
											</td>
											<td>
												<input
													type="text"
													class="form-control"
													:class="{
														'form-control-plaintext': !userCanEdit || !isEditMode
													}"
													:readonly="!userCanEdit || !isEditMode"
													maxlength="255"
													v-model="link.notes"
													@input="formDirty = true"
												/>
											</td>
										</tr>
									</tbody>
								</table>
							</div>
							<div class="row">
								<div class="col-md-6">
									<div class="form-group">
										<label for="scholarshipOrganizations">Organizations, Club, Fraternity, Sorority, etc.</label>
										<VueMultiselect
											id="scholarshipOrganizations"
											:options="organizationOptions"
											:multiple="true"
											:close-on-select="false"
											:disabled="!userCanEdit || !isEditMode"
											label="organization"
											track-by="id"
											placeholder="Search organizations"
											v-model="selectedOrganizationsModel"
											@update:modelValue="formDirty = true"
										>
										</VueMultiselect>
										<small class="form-text text-muted">
											Manage list under <a href="/scholarships/organizations">Manage Organizations</a>.
										</small>
									</div>
								</div>
								<div class="col-md-6">
									<div class="form-group">
										<label for="scholarshipKeywords">Keywords / Tags</label>
										<VueMultiselect
											id="scholarshipKeywords"
											:options="keywordOptions"
											:multiple="true"
											:close-on-select="false"
											:disabled="!userCanEdit || !isEditMode"
											label="keyword"
											track-by="id"
											placeholder="Search keywords"
											v-model="selectedKeywordsModel"
											@update:modelValue="formDirty = true"
										>
										</VueMultiselect>
										<small class="form-text text-muted">
											Manage list under <a href="/scholarships/keywords">Manage Keywords</a>.
										</small>
									</div>
								</div>
							</div>
						</div>
					</fieldset>

					<fieldset class="card mb-4" aria-labelledby="scholarshipDatesHeading">
						<div class="card-header">
							<h5 id="scholarshipDatesHeading" class="mb-0">Dates &amp; Instructions</h5>
						</div>
						<div class="card-body">
							<div class="row">
								<div class="col-md-6">
									<div class="form-group">
										<label>Apply By Date <small class="text-muted">(Optional)</small></label>
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
								</div>
								<div class="col-md-6">
									<div class="form-group">
										<label>Posting Expiration Date <small class="text-muted">(Optional)</small></label>
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
								</div>
							</div>
							<div>
								<label class="mt-2">Application Procedure</label>
								<template v-if="!userCanEdit || !isEditMode">
									<div v-if="!record.appProc">---</div>
									<div v-else v-html="record.appProc"></div>
								</template>
								<template v-else>
									<ckeditor
										:model-value="appProcModel"
										:editor="editor"
										:config="ckConfig"
										name="scholarshipAppProc"
										@update:modelValue="updateRichText('appProc', $event)"
									>
									</ckeditor>
								</template>
							</div>
							<div>
								<label class="mt-2">Contact Information <span class="red">*</span></label>
								<template v-if="!userCanEdit || !isEditMode">
									<div v-if="!record.contact">---</div>
									<div v-else v-html="record.contact"></div>
								</template>
								<template v-else>
									<Field name="contact" v-slot="{ errorMessage }" v-model="contactModel">
										<div :class="{ 'is-invalid-ckeditor': errorMessage }">
											<ckeditor
												:model-value="contactModel"
												:editor="editor"
												:config="ckConfig"
												name="scholarshipContact"
												@update:modelValue="updateRichText('contact', $event)"
											>
											</ckeditor>
										</div>
										<div v-if="errorMessage" class="invalid-feedback-ckeditor">
											{{ errorMessage }}
										</div>
									</Field>
								</template>
							</div>
							<div>
								<label class="mt-2">Additional Information <span class="red">*</span></label>
								<template v-if="!userCanEdit || !isEditMode">
									<div v-if="!record.description">---</div>
									<div v-else v-html="record.description"></div>
								</template>
								<template v-else>
									<Field name="description" v-slot="{ errorMessage }" v-model="descriptionModel">
										<div :class="{ 'is-invalid-ckeditor': errorMessage }">
											<ckeditor
												:model-value="descriptionModel"
												:editor="editor"
												:config="ckConfig"
												name="scholarshipDescription"
												@update:modelValue="updateRichText('description', $event)"
											>
											</ckeditor>
										</div>
										<div v-if="errorMessage" class="invalid-feedback-ckeditor">
											{{ errorMessage }}
										</div>
									</Field>
								</template>
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
							<li v-for="(error, field) in errors" :key="field">
								<strong>{{ error }}</strong>
							</li>
						</ul>
					</div>
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
						<div v-if="isSaveFailed" class="alert alert-danger" role="alert">
							<p class="mb-0" v-if="saveErrors.length === 0">
								Error saving this scholarship.
							</p>
							<template v-else>
								<p class="mb-1">This scholarship could not be saved:</p>
								<ul class="mb-0">
									<li v-for="message in saveErrors" :key="message">
										{{ message }}
									</li>
								</ul>
							</template>
						</div>
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

// Editor markup with no text in it (e.g. "<p>&nbsp;</p>") counts as empty, as it does in the API.
function hasRichText(html) {
	return (html || "").replace(/<[^>]*>/g, "").replace(/&nbsp;/g, " ").trim() !== ""
}

export default {
	created() {
		if (this.startMode == "edit") {
			this.isEditMode = true
		}

		this.fetchOptions()
		this.fetchLookup("/api/scholarships/colleges", "colleges")
		this.fetchLookup("/api/scholarships/departments", "departments")
		this.fetchLookup("/api/scholarships/programs", "programs")
		this.fetchLookup("/api/scholarships/keyword-options", "keywordOptions")
		this.fetchLookup("/api/scholarships/organization-options", "organizationOptions")

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

			colleges: [],
			departments: [],
			programs: [],
			keywordOptions: [],
			organizationOptions: [],

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
				overview: "",
				active: true,
				catchAll: false,
				url: "",
				amount: "",
				applyDate: "",
				expDate: "",
				gender: "",
				ethnicity: "",
				isFafsa: false,
				isParent: false,
				isBilingual: false,
				gpa: "",
				standingClass: "",
				enrollment: "",
				transfer: "Both",
				housing: "No",
				county: "",
				city: "",
				state: "",
				highSchool: "",
				keyword_ids: [],
				organization_ids: [],
				contact: "",
				appProc: "",
				description: "",
				collegeId: "",
				departmentId: "",
				programLinks: []
			},
			
			formDirty: false,
			isSaveFailed: false,
			saveErrors: [],
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
		overviewModel: {
			get() {
				return this.record.overview || ""
			},
			set(value) {
				this.record.overview = value
			}
		},

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

		// Only active programs can be newly linked, but programs this scholarship is already
		// linked to stay in the options so an on-hold link isn't silently dropped on save.
		programOptions() {
			let ids = (this.record.programLinks || []).map((l) => Number(l.programId))
			return this.programs.filter(
				(p) => Number(p.is_active) === 1 || ids.indexOf(Number(p.id)) !== -1
			)
		},

		// The API returns links as programLinks/programId but expects program_links/program_id
		// back, so the picker works off the read shape and submit converts it.
		selectedProgramsModel: {
			get() {
				let ids = (this.record.programLinks || []).map((l) => Number(l.programId))
				return this.programs.filter((p) => ids.indexOf(Number(p.id)) !== -1)
			},
			set(value) {
				let notes = {}
				;(this.record.programLinks || []).forEach(function (l) {
					notes[l.programId] = l.notes
				})
				this.record.programLinks = value.map((p) => ({
					programId: p.id,
					notes: notes[p.id] || null
				}))
			}
		},

		// Keywords/organizations are managed entities; the picker works off id arrays.
		selectedKeywordsModel: {
			get() {
				return this.keywordOptions.filter((o) => this.record.keyword_ids.includes(o.id))
			},
			set(value) {
				this.record.keyword_ids = value.map((o) => o.id)
			}
		},

		selectedOrganizationsModel: {
			get() {
				return this.organizationOptions.filter((o) => this.record.organization_ids.includes(o.id))
			},
			set(value) {
				this.record.organization_ids = value.map((o) => o.id)
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
					.required("An application link is required.")
					.url("Please enter a valid URL.")
					.max(255, "URL must be 255 characters or less.")
					.nullable(true),
				overview: Yup.string()
					.test("rich-text-required", "An overview is required.", hasRichText),
				contact: Yup.string()
					.test("rich-text-required", "Contact information is required.", hasRichText),
				description: Yup.string()
					.test("rich-text-required", "Additional information is required.", hasRichText),
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

		// A failed save returns Symfony's validation format, so pull the messages out of it.
		violationMessages: function (error) {
			let violations = error.response &&
				error.response.data &&
				error.response.data.violations

			if (!violations) {
				return []
			}

			return violations.map(function (violation) {
				return violation.propertyPath
					? violation.propertyPath + ": " + violation.title
					: violation.title
			})
		},

		programName: function (programId) {
			let program = this.programs.find((p) => Number(p.id) === Number(programId))
			return program ? program.full_name : "Program " + programId
		},

		fetchLookup: function (url, target) {
			let self = this

			axios
				.get(url)
				.then(function (response) {
					self[target] = response.data
				})
				.catch(function (error) {
					console.log("Error fetching " + target + ":", error)
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

			// The API returns keywords/organizations as [{id, name}]; the pickers work off id arrays.
			record.keyword_ids = Array.isArray(record.keywords) ? record.keywords.map((k) => k.id) : []
			record.organization_ids = Array.isArray(record.organizations) ? record.organizations.map((o) => o.id) : []

			return record
		},

		submitScholarship: function () {
			let self = this // 'this' loses scope within axios
			self.currentStatus = null
			let method = this.itemExists ? "put" : "post"
			let route = this.itemExists
				? "/api/scholarships/" + this.record.id
				: "/api/scholarships/"

			let payload = Object.assign({}, self.record, {
				program_links: (self.record.programLinks || []).map((l) => ({
					program_id: l.programId,
					notes: l.notes
				})),
				keyword_ids: self.record.keyword_ids || [],
				organization_ids: self.record.organization_ids || []
			})
			// The API reads keywords/organizations back as arrays of objects; only the id
			// arrays are writable, so drop the read-shape keys from the payload.
			delete payload.keywords
			delete payload.organizations

			axios({
				method: method,
				url: route,
				data: payload
			})
				.then(function (response) {
					self.record.id = response.data.id
					self.isSaveFailed = false
					self.saveErrors = []
					self.afterSubmitSucceeds()
				})
				.catch(function (error) {
					self.currentStatus = STATUS_SAVE_FAILED
					self.isSaveFailed = true
					self.saveErrors = self.violationMessages(error)
				})
		},

		// CKEditor reports a change when it loads an empty field, so only flag the form
		// when the editor's content differs from the record.
		updateRichText: function (field, value) {
			if (value !== (this.record[field] || "")) {
				this.record[field] = value
				this.formDirty = true
			}
		},

		toggleEdit: function () {
			this.isEditMode = !this.isEditMode
		}
	}
}
</script>
