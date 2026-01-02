<template>
	<div>
		<heading>
			<span>Photo Request Details</span>
		</heading>
		<div class="btn-group" role="group" aria-label="form navigation buttons">
			<button
				v-if="userCanEdit"
				type="button"
				class="btn btn-info pull-right"
				@click="toggleEdit"
			>
				<span v-html="lockIcon"></span>
			</button>
		</div>
		<div class="pt-2"></div>
		<div
			v-if="apiError.status"
			class="alert alert-danger fade show"
			role="alert"
		>
			{{ apiError.message }}
		</div>

		<div v-if="loading" class="text-center">
			<img src="/images/loading.gif" alt="Loading..." />
		</div>

		<div v-else-if="photoRequest" class="row">
			<div class="col-md-8">
				<div class="card">
					<div class="card-header">
						<h5 class="mb-0">Request Information</h5>
					</div>
					<div class="card-body">
						<div class="row">
							<div class="col-md-6">
								<strong>Name</strong>
                <p>{{ photoRequest.firstName }}
                  {{ photoRequest.lastName }}</p>
							</div>
							<div class="col-md-6">
								<strong>Email</strong>
                <p>{{ photoRequest.email }}</p>
							</div>
						</div>
						<div class="row mt-2">
							<div class="col-md-6">
								<strong>Phone</strong>
                <p>{{ photoRequest.phone }}</p>
							</div>
							<div class="col-md-6">
								<strong>Department</strong>
                <p>{{ photoRequest.department }}</p>
							</div>
						</div>
						<div class="row mt-2">
							<div class="col-md-6">
								<strong>Type of request</strong>
                <p>{{ getShootTypeDisplay(photoRequest.shootType) }}</p>
							</div>
							<div
								class="col-md-6"
								v-if="
									photoRequest.shootType === 'photoshoot' &&
									photoRequest.photoType &&
									photoRequest.photoType.includes('Event')
								"
							>
								<strong>Name of Event</strong>
								<p>{{ photoRequest.shootName || "---" }}</p>
							</div>
						</div>
						<div class="row mt-2">
							<div
								class="col-md-6"
								v-if="photoRequest.shootType === 'photoshoot'"
							>
								<strong>Type of Photoshoot</strong>
                <p>{{ photoRequest.photoType || "---" }}</p>
							</div>
							<div
								class="col-md-6"
								v-if="photoRequest.shootType === 'photoshoot'"
							>
								<strong>Location</strong>
                <p>{{ photoRequest.location || "---" }}</p>
							</div>
						</div>
						<div class="row mt-2">
							<div class="col-md-4">
								<strong>Proposed Shoot Date</strong>
                <p>{{ formatDate(photoRequest.shootDate) }}</p>
							</div>
							<div class="col-md-4">
								<strong>Start Time</strong>
                <p>{{ formatTime(photoRequest.startTime) }}</p>
							</div>
							<div class="col-md-4">
								<strong>End Time:</strong>
                <p>{{ formatTime(photoRequest.endTime) }}</p>
							</div>
						</div>
					</div>
				</div>

				<div class="card mt-3">
					<div class="card-header">
						<h5 class="mb-0">Description & Details</h5>
					</div>
					<div class="card-body">
						<div
							v-if="
								photoRequest.description &&
								photoRequest.shootType === 'photoshoot'
							"
						>
							<strong>Shoot Description</strong>
							<p>{{ photoRequest.description }}</p>
						</div>
						<div v-if="photoRequest.shootType === 'archive'">
							<strong
								>Provide details about the photo you're looking for:</strong
							>
							<p>{{ photoRequest.photoExplaination || "---" }}</p>
						</div>
						<div>
							<strong>Intended Use</strong>
							<p>{{ photoRequest.intendedUse || "---" }}</p>
						</div>
						<div v-if="photoRequest.intendedUse != ''">
							<strong>Describe how photo(s) will be used</strong>
							<p>{{ photoRequest.forUse }}</p>
						</div>
						<div v-if="photoRequest.intendedUse != ''">
							<strong>Designer Info</strong>
							<p>{{ photoRequest.designer }}</p>
						</div>
					</div>
				</div>
			</div>

			<div class="col-md-4">
				<div class="card">
					<div class="card-header">
						<h5 class="mb-0">Status & Management</h5>
					</div>
					<div class="card-body">
						<div class="mb-3">
							<strong>Status:</strong>
							<span v-if="photoRequest.declined" class="badge badge-danger ml-2"
								>Declined</span
							>
							<span
								v-else-if="photoRequest.completed"
								class="badge badge-success ml-2"
								>Complete</span
							>
							<span
								v-else-if="photoRequest.status"
								:class="
									'badge ' + getStatusBadgeClass(photoRequest.status) + ' ml-2'
								"
								>{{ getStatusDisplay(photoRequest.status) }}</span
							>
							<span v-else class="badge badge-light ml-2">Pending</span>
						</div>

						<div class="mb-3">
							<strong>Assigned To: </strong>
							<span v-if="photoRequest.assignedToName">
								{{ photoRequest.assignedToName }}
							</span>
							<span v-else>---</span>
						</div>

						<div class="mb-3">
							<strong>Submitted:</strong>
							<div>{{ formatDateTime(photoRequest.submitted) }}</div>
						</div>

						<div v-if="photoRequest.eventDesc" class="mb-3">
							<strong>Event Description:</strong>
							<p class="mb-0">{{ photoRequest.eventDesc }}</p>
						</div>

						<div class="mb-3">
							<strong>URL: </strong>
							<a
								v-if="photoRequest.url"
								:href="photoRequest.url"
								target="_blank"
								>{{ photoRequest.url }}</a
							>
							<span v-else>---</span>
						</div>

						<div class="mb-3">
							<strong>Category:</strong> {{ photoRequest.category || "---" }}
						</div>

						<div class="admin-note-container">
							<strong>Admin Note:</strong>
							<p class="mb-0">{{ photoRequest.longStatus || "---" }}</p>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
import Heading from "../utils/Heading.vue"

export default {
	components: {
		Heading
	},
	props: {
		permissions: {
			type: Array,
			required: true
		},
		requestId: {
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
			loading: true,
			photoRequest: null,
			isEditMode: false // This is true if the forms are editable.
		}
	},

	computed: {
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
			return "<i class='fa fa-lock'></i>"
		}
	},

	created() {
		this.fetchPhotoRequest()
	},

	methods: {
		fetchPhotoRequest: function () {
			let self = this

			axios
				.get(`/api/photorequests/${this.requestId}`)
				.then(function (response) {
					self.photoRequest = response.data
				})
				.catch(function (error) {
					self.apiError.status = error.response.status
					switch (error.response.status) {
						case 404:
							self.apiError.message = "Photo request not found."
							break
						case 403:
							self.apiError.message =
								"You do not have permission to view this photo request."
							break
						default:
							self.apiError.message =
								"An error occurred while loading the photo request."
							break
					}
				})
				.finally(function () {
					self.loading = false
				})
		},

		formatDate: function (dateString) {
			if (!dateString) return "N/A"
			const date = new Date(dateString)
			return date.toLocaleString('en-us', {  weekday: 'long' }) + ", " + date.toLocaleDateString()
		},

		formatTime: function (timeString) {
			if (!timeString) return "N/A"
			const time = new Date(timeString)
			return time.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" })
		},

		formatDateTime: function (dateTimeString) {
			if (!dateTimeString) return "N/A"
			const dateTime = new Date(dateTimeString)
			return dateTime.toLocaleString()
		},

		getStatusDisplay: function (status) {
			switch (status) {
				case "WC":
					return "Waiting on Client"
				case "IP":
					return "In Progress"
				case "DG":
					return "Approval Required"
				default:
					return status
			}
		},

		getStatusBadgeClass: function (status) {
			switch (status) {
				case "WC":
					return "badge-warning"
				case "IP":
					return "badge-info"
				case "DG":
					return "badge-danger"
				default:
					return "badge-secondary"
			}
		},

		getShootTypeDisplay: function (shootType) {
			switch (shootType) {
				case "photoshoot":
					return "New Photo Shoot"
				case "archive":
					return "Existing Photo"
				default:
					return shootType
			}
		},

		/**
		 * Sets the variable @isEditMode to the appropriate boolean value.
		 */
		toggleEdit: function () {
			window.location.href = "/photorequests/" + this.requestId + "/edit"
		}
	}
}
</script>

<style lang="scss" scoped>
@import "../../../css/_variables.scss";
.admin-note-container {
	margin-top: 1rem;
	background-color: $primary-light;
	color: white;
	padding: 1rem;
}
</style>
