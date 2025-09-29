<template>
	<div>
		<heading>
			<span>Emergency Banner</span>
		</heading>
		<div
			v-if="apiError.status"
			class="alert alert-danger fade show"
			role="alert"
		>
			{{ apiError.message }}
		</div>
		<div>
			<p>
				
				{{ banner }}
			</p>
		</div>
		<div class="row">
			<div class="col-xs-12">
				Banner info here	
			</div>
		</div>

	</div>
</template>
<style></style>
<script>
import Heading from "../utils/Heading.vue"
import VueMultiselect from "vue-multiselect"

export default {
	created() {
		this.fetchPhotoRequests()
		this.fetchCategories()
	},
	components: {
		Heading,
		VueMultiselect
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
			loadingBanner: false,
			banner: null
		}
	},

	computed: {
		headingIcon: function () {
			return "<i class='fa fa-alert'></i>"
		},
		userCanEdit: function () {
			return this.permissions[0].view ? true : false
		}
	},
	methods: {
		fetchBanner: function () {
			let self = this // "this" loses scope within Axios.

			this.loadingBanner = true

			const params = {}


			/* Ajax (Axios) Submission */
			axios
				.get("/api/emergency/banner", { params })
				.then(function (response) {
					// Success.
					self.banner = response.data
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

			this.loadingBanner = false
		}
	}
}
</script>
