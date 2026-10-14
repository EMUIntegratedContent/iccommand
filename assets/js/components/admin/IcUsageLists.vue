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
				<template v-if="section.items.length > 0">
					<div class="mt-2 mb-3">
						<label :for="'usageSearch-' + section.key" class="form-label"
							>Search {{ section.label }}</label
						>
						<div class="position-relative">
							<input
								type="text"
								class="form-control"
								:id="'usageSearch-' + section.key"
								v-model="searchTerms[section.key]"
								:placeholder="'Search ' + section.label.toLowerCase()"
								style="padding-right: 40px"
								@keydown.enter.prevent
							/>
							<span
								class="position-absolute d-flex align-items-center text-muted"
								style="right: 0; top: 0; height: 100%; padding: 0 12px; pointer-events: none"
							>
								<i class="fa fa-search"></i>
							</span>
							<button
								v-if="searchTerms[section.key]"
								type="button"
								class="btn btn-link position-absolute"
								style="right: 0; top: 0; height: 100%; border: none; margin: 0 40px"
								@click="searchTerms[section.key] = ''"
								aria-label="Clear search"
							>
								<i class="fa fa-remove"></i>
							</button>
						</div>
					</div>
					<ul v-if="filteredItems(section).length > 0" class="list-group">
						<li
							v-for="item in filteredItems(section)"
							:key="item.id"
							class="list-group-item"
						>
							<a :href="item.url" target="_blank">{{ item.name }}</a>
							<small v-if="item.note" class="text-muted ml-2"
								>({{ item.note }})</small
							>
						</li>
					</ul>
					<p v-else class="text-muted mb-0">No matches.</p>
				</template>
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
			expanded: {},

			/**
			 * Each section's search box text, keyed by section key.
			 * @type {Object}
			 */
			searchTerms: {}
		}
	},

	methods: {
		/**
		 * The section's items whose name contains the search text (case-insensitive).
		 * @param {Object} section The section.
		 * @return {Array}
		 */
		filteredItems: function (section) {
			let term = (this.searchTerms[section.key] || "").trim().toLowerCase()
			if (term === "") {
				return section.items
			}
			return section.items.filter((item) => (item.name || "").toLowerCase().includes(term))
		},

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
