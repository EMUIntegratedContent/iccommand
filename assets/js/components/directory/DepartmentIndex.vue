<template>
	<div class="row">
		<div class="col">
			<div class="app-hero">
				<h1 style="color: #ffffff; background: #222222; padding: 5px">
					Department Directory
				</h1>
				<img
					class="img-fluid"
					alt="Directory Application"
					src="/images/directory-front.png"
					style="display: block; margin: 0 auto"
				/>
			</div>
			<p>
				This application manages all department information for the university
				directory. Users with full access of this app have the ability to
				modify, add, or delete application users.
			</p>
		</div>
	</div>
</template>

<style></style>

<script>
import Heading from "../utils/Heading.vue"
import Paginator from "../utils/Paginator.vue"

export default {
	created() {},

	mounted() {
		console.log("Department Index mounted.")
	},

	components: { Heading, Paginator },

	props: {
		permissions: {
			type: Array,
			required: true
		}
	},

	data: function () {
		return {}
	},

	computed: {},

	methods: {},

	filters: {}
}
</script>
