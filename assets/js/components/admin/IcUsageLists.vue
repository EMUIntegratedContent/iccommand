<template>
	<div>
		<div v-for="section in sections" :key="section.key" class="border rounded mb-3">
			<button
				type="button"
				class="btn btn-link d-block w-100 text-left px-3 py-2 text-dark text-decoration-none"
				@click="toggleSection(section.key)"
				:aria-expanded="expanded[section.key] ? 'true' : 'false'"
			>
				<i
					:class="
						expanded[section.key] ? 'fa fa-chevron-down' : 'fa fa-chevron-right'
					"
				></i>
				<h6 class="d-inline ml-1">{{ section.label }}</h6>
				<span class="badge badge-primary ml-2">{{ section.items.length }}</span>
			</button>
			<div v-if="expanded[section.key]" class="px-3 pb-3">
				<ul v-if="section.items.length > 0" class="list-group mt-2">
					<li v-for="item in section.items" :key="item.id" class="list-group-item">
						<a :href="item.url" target="_blank">{{ item.name }}</a>
						<small v-if="item.note" class="text-muted ml-2"
							>({{ item.note }})</small
						>
					</li>
				</ul>
				<div v-else class="alert alert-info mt-2 mb-0">None.</div>
			</div>
		</div>
	</div>
</template>

<style scoped></style>

<script>
export default {
	props: {
		/**
		 * One collapsible list per kind of record that uses the college/department:
		 * [{ key, label, items: [{ id, name, note, url }] }].
		 */
		sections: {
			type: Array,
			required: true
		}
	},

	data: function () {
		return {
			/**
			 * Which sections are open, keyed by section key; all start collapsed.
			 * @type {Object}
			 */
			expanded: {}
		}
	},

	methods: {
		/**
		 * Opens or closes a section's list.
		 * @param {string} key The section key.
		 */
		toggleSection: function (key) {
			this.expanded[key] = !this.expanded[key]
		}
	}
}
</script>
