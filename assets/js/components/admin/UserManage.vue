<template>
	<div>
		<not-found v-if="is404 === true"></not-found>
		<div v-if="isDataLoaded === false">
			<p style="text-align: center">
				<img src="/images/loading.gif" alt="Loading..." />
			</p>
		</div>
		<!--<p><a href="/register" class="btn btn-primary">New User</a></p>-->
		<div
			v-if="apiError.status"
			class="alert alert-danger fade show"
			role="alert"
		>
			{{ apiError.message }}
		</div>
		<!-- MAIN AREA -->
		<div v-if="isDataLoaded === true && isDeleted === false && is404 === false">
			<heading>
				<span slot="title">Configure user {{ username }}</span>
			</heading>
			<div class="button-holder" role="group">
				<a href="/admin/users" class="btn btn-info pull-left"
					><i class="fa fa-arrow-left"></i
				></a>
				<button
					type="button"
					class="btn btn-info pull-right"
					@click="toggleEdit"
				>
					<span v-html="lockIcon"></span>
				</button>
			</div>

			<VeeForm
				class="form"
				v-slot="{ submitForm, errors, meta }"
				@submit="submitForm"
				:validation-schema="userSchema"
			>
				<fieldset>
					<legend>Basic Information</legend>
					<div class="form-group row">
						<div class="col-sm-6">
							<div class="form-group">
								<label
									>First Name
									<span class="red" v-if="isEditMode">*</span></label
								>
								<Field
									v-model="user.firstName"
									name="firstName"
									type="text"
									class="form-control"
									:class="{
										'is-invalid': errors.firstName,
										'form-control-plaintext': !isEditMode
									}"
									:readonly="!isEditMode"
								>
								</Field>
								<div class="invalid-feedback">
									{{ errors.firstName }}
								</div>
							</div>
						</div>
						<div class="col-sm-6">
							<div class="form-group">
								<label
									>Last Name <span class="red" v-if="isEditMode">*</span></label
								>
								<Field
									v-model="user.lastName"
									name="lastName"
									type="text"
									class="form-control"
									:class="{
										'is-invalid': errors.lastName,
										'form-control-plaintext': !isEditMode
									}"
									:readonly="!isEditMode"
								>
								</Field>
								<div class="invalid-feedback">
									{{ errors.lastName }}
								</div>
							</div>
						</div>
					</div>
					<div class="form-group row">
						<div class="col-sm-6">
							<label>Job Title</label>
							<Field
								v-model="user.jobTitle"
								name="jobTitle"
								type="text"
								class="form-control"
								:class="{
									'is-invalid': errors.jobTitle,
									'form-control-plaintext': !isEditMode
								}"
								:readonly="!isEditMode"
							>
							</Field>
							<div class="invalid-feedback">
								{{ errors.jobTitle }}
							</div>
						</div>
						<div class="col-sm-3">
							<label>Department</label>
							<Field
								v-model="user.department"
								name="department"
								type="text"
								class="form-control"
								:class="{
									'is-invalid': errors.department,
									'form-control-plaintext': !isEditMode
								}"
								:readonly="!isEditMode"
							>
							</Field>
							<div class="invalid-feedback">
								{{ errors.department }}
							</div>
						</div>
						<div class="col-sm-3">
							<label>Phone Number</label>
							<Field
								v-model="user.phone"
								name="phone"
								type="text"
								class="form-control"
								:class="{
									'is-invalid': errors.phone,
									'form-control-plaintext': !isEditMode
								}"
								:readonly="!isEditMode"
							>
							</Field>
							<div class="invalid-feedback">
								{{ errors.phone }}
							</div>
						</div>
					</div>
					<div class="form-group">
						<input
							type="checkbox"
							v-model="user.enabled"
							:disabled="username == loggedInUser || !isEditMode"
							true-value="1"
							false-value="0"
						/>
						Enable User
						{{
							username == loggedInUser && isEditMode
								? "(you cannot disable your own account)"
								: ""
						}}
					</div>
				</fieldset>
				<fieldset>
					<legend>User Roles</legend>
					<div class="row">
						<div class="col-xs-12 col-sm-6 col-md-4 pl-4 pb-2">
							<div class="card card-accent card-accent-red">
								<div class="card-header">Administrative</div>
								<div class="card-body">
									<p v-if="username == loggedInUser">
										You cannot change your own administrative settings.
									</p>
									<template
										v-for="role in rolesAdmin"
										:key="'admin-role-' + role"
									>
										<input
											type="checkbox"
											v-model="user.roles"
											:disabled="username == loggedInUser || !isEditMode"
											:value="role"
										/>
										{{ role }} <br />
									</template>
								</div>
							</div>
						</div>
						<div class="col-xs-12 col-sm-6 col-md-4 pl-4 pb-2">
							<div class="card card-accent card-accent-green">
								<div class="card-header">Campus Map</div>
								<div class="card-body">
									<template v-for="role in rolesMap" :key="'user-map-' + role">
										<input
											type="checkbox"
											v-model="user.roles"
											:disabled="!isEditMode"
											:value="role"
										/>
										{{ role }} <br />
									</template>
								</div>
							</div>
						</div>
						<div class="col-xs-12 col-sm-6 col-md-4 pl-4 pb-2">
							<div class="card card-accent card-accent-green">
								<div class="card-header">Redirect</div>
								<div class="card-body">
									<template
										v-for="role in rolesRedirect"
										:key="'user-redirect-' + role"
									>
										<input
											type="checkbox"
											v-model="user.roles"
											:disabled="!isEditMode"
											:value="role"
										/>
										{{ role }} <br />
									</template>
								</div>
							</div>
						</div>
            <div class="col-xs-12 col-sm-6 col-md-4 pl-4 pb-2">
							<div class="card card-accent card-accent-green">
								<div class="card-header">Programs</div>
								<div class="card-body">
									<template
										v-for="role in rolesPrograms"
										:key="'user-programs-' + role"
									>
										<input
											type="checkbox"
											v-model="user.roles"
											:disabled="!isEditMode"
											:value="role"
										/>
										{{ role }} <br />
									</template>
								</div>
							</div>
						</div>
            <div class="col-xs-12 col-sm-6 col-md-4 pl-4 pb-2">
							<div class="card card-accent card-accent-green">
								<div class="card-header">Crime Log</div>
								<div class="card-body">
									<template
										v-for="role in rolesCrimeLog"
										:key="'user-crimelog-' + role"
									>
										<input
											type="checkbox"
											v-model="user.roles"
											:disabled="!isEditMode"
											:value="role"
										/>
										{{ role }} <br />
									</template>
								</div>
							</div>
						</div>
            <div class="col-xs-12 col-sm-6 col-md-4 pl-4 pb-2">
							<div class="card card-accent card-accent-green">
								<div class="card-header">Departments</div>
								<div class="card-body">
									<template
										v-for="role in rolesDepartments"
										:key="'user-departments-' + role"
									>
										<input
											type="checkbox"
											v-model="user.roles"
											:disabled="!isEditMode"
											:value="role"
										/>
										{{ role }} <br />
									</template>
								</div>
							</div>
						</div>
            <div class="col-xs-12 col-sm-6 col-md-4 pl-4 pb-2">
							<div class="card card-accent card-accent-green">
								<div class="card-header">Photo Request</div>
								<div class="card-body">
									<template
										v-for="role in rolesPhoto"
										:key="'user-photo-' + role"
									>
										<input
											type="checkbox"
											v-model="user.roles"
											:disabled="!isEditMode"
											:value="role"
										/>
										{{ role }} <br />
									</template>
								</div>
							</div>
						</div>
					</div>
					<!-- VALIDATION AND SUCCESS/ERROR MESSAGES -->
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
						There was an error deleting this item.
					</div>
					<!-- ACTION BUTTONS -->
					<button
						v-if="isEditMode"
						class="btn btn-success spacer-top"
						type="submit"
					>
						{{ "Update " + username }}
					</button>
				</fieldset>
			</VeeForm>
		</div>
	</div>
</template>
<style>
.button-holder {
	height: 50px;
}
.red {
	color: #ff0033;
}
</style>
<script>
import Heading from "../utils/Heading.vue"
import NotFound from "../utils/NotFound.vue"
import { ErrorMessage, Field, Form as VeeForm } from "vee-validate"
import * as Yup from "yup"

export default {
	created() {},
	mounted() {
		this.fetchUser()
		this.fetchRoles()
	},
	components: { Heading, NotFound, Field, VeeForm, ErrorMessage },
	props: {
		username: {
			type: String,
			required: true
		},
		loggedInUser: {
			type: String,
			required: true
		}
	},
	data: function () {
		return {
			apiError: {
				message: null,
				status: null
			},
			is404: false,
			isDataLoaded: false,
			isDeleted: false,
			isDeleteError: false,
			isEditMode: false, // true = make forms editable
			roles: [],
			rolesAdmin: [],
			rolesMap: [],
			rolesRedirect: [],
			rolesPrograms: [],
			rolesCrimeLog: [],
			rolesDepartments: [],
			rolesPhoto: [],
			success: false,
			successMessage: "",
			user: {
				id: "",
				firstName: "",
				lastName: "",
				jobTitle: "",
				department: "",
				phone: "",
				enabled: 1,
				roles: []
			}
		}
	},
	computed: {
		// are there any validation errors?
		haveErrors: function () {
			return this.$validator.errors.count() > 0 ? true : false
		},
		headingIcon: function () {
			return '<i class="fa fa-user"></i>'
		},
		lockIcon: function () {
			return this.isEditMode
				? '<i class="fa fa-unlock"></i>'
				: '<i class="fa fa-lock"></i>'
		},
		userSchema() {
			let yupObj = {
				firstName: Yup.string().required().label("First name"),
				lastName: Yup.string().required().label("Last name"),
				jobTitle: Yup.string().label("Job title").nullable(true),
				department: Yup.string().label("Department").nullable(true),
				phone: Yup.string().max(16).label("Phone number").nullable(true)
			}
			return Yup.object(yupObj)
		}
	},
	methods: {
		afterSubmitSucceeds: function () {
			let self = this
			this.success = true
			this.successMessage = "Update successful."
			// remove the message after 3 seconds
			setTimeout(function () {
				self.success = false
			}, 3000)
		},

		fetchRoles: function () {
			let self = this
			axios
				.get("/api/admin/roles")
				// success
				.then(function (response) {
					self.roles = response.data
					// Filter the various roles by their application type
					// Roles are defined in the pattern ROLE_APPNAME in the backend
					for (let key in response.data) {
						if (key.startsWith("ROLE_GLOBAL_ADMIN")) {
							self.rolesAdmin.push(key)
						}
						if (key.startsWith("ROLE_MAP_")) {
							self.rolesMap.push(key)
						}
						if (key.startsWith("ROLE_REDIRECT_")) {
							self.rolesRedirect.push(key)
						}
						if (key.startsWith("ROLE_PROGRAMS_")) {
							self.rolesPrograms.push(key)
						}
						if (key.startsWith("ROLE_CRIMELOG_")) {
							self.rolesCrimeLog.push(key)
						}
						if (key.startsWith("ROLE_DEPARTMENTS_")) {
							self.rolesDepartments.push(key)
						}
						if (key.startsWith("ROLE_PHOTO_")) {
							self.rolesPhoto.push(key)
						}
					}
				})
				// fail
				.catch(function (error) {
					self.apiError.status = error.response.status
					switch (error.response.status) {
						case 403:
							self.apiError.message =
								"You do not have sufficient privileges to retrieve roles."
							break
						case 404:
							self.apiError.message = "Roles not found."
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
		fetchUser: function () {
			let self = this
			axios
				.get("/api/admin/users/" + this.username)
				// success
				.then(function (response) {
					self.user = response.data
					self.isDataLoaded = true
				})
				// fail
				.catch(function (error) {
					self.apiError.status = error.response.status
					switch (error.response.status) {
						case 403:
							self.apiError.message =
								"You do not have sufficient privileges to retrieve users."
							break
						case 404:
							self.apiError.message = "User was not found."
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
		// Submit the form via the API
		submitForm: function () {
			let self = this // 'this' loses scope within axios
			// AJAX (axios) submission
			axios({
				method: "put",
				url: "/api/admin/users/" + this.username,
				data: self.user
			})
				// success
				.then(function (response) {
					self.afterSubmitSucceeds()
				})
				// fail
				.catch(function (error) {
					let errors = error.response.data
					// Add any validation errors to the vee validator error bag
					errors.forEach(function (error) {
						let key = error.property_path
						let message = error.message
						self.$validator.errors.add(key, message)
					})
				})
		},
		toggleEdit: function () {
			this.isEditMode === true
				? (this.isEditMode = false)
				: (this.isEditMode = true)
		},
		userHasRole: function (role) {
			return this.user.roles.includes(role) ? true : false
		}
	},
	filters: {}
}
</script>
