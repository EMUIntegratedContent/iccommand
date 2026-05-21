<template>
	<div class="mt-4">
		<heading>GradCAS Links</heading>
		<div v-if="loading">
			<p style="text-align: center">
				<img src="/images/loading.gif" alt="Loading..." />
			</p>
		</div>
		<div v-else-if="hidden"></div>
		<div v-else-if="links.length === 0">
			<p>
				No GradCAS links for this program.
				<a href="/gradcas">Go to GradCAS Links</a>
			</p>
		</div>
		<table v-else class="table table-sm">
			<thead>
				<tr>
					<th>Cycle</th>
					<th>Degree Name</th>
					<th>Link</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<tr v-for="link in links" :key="link.id">
					<td>
						{{ link.cycle.cycleName }}
						<span
							v-if="link.cycle.current"
							class="badge badge-success ml-1"
						>Current</span>
					</td>
					<td>{{ link.degreeName }}</td>
					<td>
						<a
							:href="link.link"
							target="_blank"
							rel="noopener"
							title="Open application link"
						>Open Link</a>
						<button
							class="btn btn-sm btn-outline-secondary ml-2"
							@click="copyLink(link)"
						>
							{{ copiedId === link.id ? "Copied!" : "Copy Link" }}
						</button>
					</td>
					<td>
						<a
							v-if="userCanEdit"
							:href="'/gradcas/link/' + link.id + '/edit'"
							title="Edit this GradCAS link"
						>
							<font-awesome-icon icon="fa-solid fa-pen-to-square" />
						</a>
					</td>
				</tr>
			</tbody>
		</table>
	</div>
</template>

<script>
import Heading from "../utils/Heading.vue"

export default {
	components: { Heading },
	props: {
		programId: {
			type: Number,
			required: true
		},
		userCanEdit: {
			type: Boolean,
			default: false
		}
	},
	data() {
		return {
			links: [],
			loading: true,
			hidden: false,
			copiedId: null
		}
	},
	created() {
		this.fetchLinks()
	},
	methods: {
		fetchLinks() {
			let self = this
			axios
				.get("/api/gradcas/links/by-program/" + this.programId)
				.then(function (response) {
					self.links = response.data
					self.loading = false
				})
				.catch(function (error) {
					self.loading = false
					if (error.response && error.response.status === 403) {
						self.hidden = true
					}
				})
		},
		copyLink(link) {
			navigator.clipboard.writeText(link.link)
			this.copiedId = link.id
			setTimeout(() => {
				if (this.copiedId === link.id) {
					this.copiedId = null
				}
			}, 2000)
		}
	}
}
</script>
