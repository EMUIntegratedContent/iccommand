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
				<!-- Add: search every record of this kind and click one to add it. -->
				<div v-if="canAdd(section)" class="mt-2 mb-3">
					<label :for="'usageAdd-' + section.key" class="form-label"
						>Add {{ section.label }}</label
					>
					<div class="position-relative">
						<input
							type="text"
							class="form-control"
							:id="'usageAdd-' + section.key"
							v-model="addTerms[section.key]"
							:placeholder="section.addPlaceholder || 'Search all ' + section.label.toLowerCase() + ' by name'"
							:disabled="section.addOptions === null"
							autocomplete="off"
							style="padding-right: 40px"
							@keydown.enter.prevent
							@keydown.esc="addTerms[section.key] = ''"
						/>
						<span
							class="position-absolute d-flex align-items-center text-muted"
							style="right: 0; top: 0; height: 100%; padding: 0 12px; pointer-events: none"
						>
							<i class="fa fa-search"></i>
						</span>
					</div>
					<small v-if="section.addOptions === null" class="text-muted">Loading…</small>
					<small v-if="section.addError" class="text-danger">{{ section.addError }}</small>
					<div v-if="addTerm(section) !== ''" class="list-group mt-1">
						<button
							v-for="option in addMatches(section)"
							:key="option.id"
							type="button"
							class="list-group-item list-group-item-action"
							@click="addItem(section, option)"
						>
							<i class="fa fa-plus text-success mr-2" aria-hidden="true"></i>{{ option.name }}
							<small v-if="option.note" class="text-muted ml-1">({{ option.note }})</small>
						</button>
						<div v-if="addMatches(section).length === 0" class="list-group-item text-muted">
							No {{ section.label.toLowerCase() }} match that name, or they're all
							in the list already.
						</div>
						<div
							v-else-if="addMatchCount(section) > addLimit"
							class="list-group-item text-muted small"
						>
							Showing {{ addLimit }} of {{ addMatchCount(section) }}. Keep typing to narrow
							it down.
						</div>
					</div>
				</div>

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
							class="list-group-item d-flex justify-content-between align-items-center"
						>
							<span>
								<a :href="item.url" target="_blank">{{ item.name }}</a>
								<small v-if="item.note" class="text-muted ml-2"
									>({{ item.note }})</small
								>
							</span>
							<button
								v-if="editable"
								type="button"
								class="btn btn-link text-danger p-0 ml-3"
								:title="'Remove ' + item.name"
								:aria-label="'Remove ' + item.name"
								@click="$emit('remove', section.key, item.id)"
							>
								<i class="fa fa-trash"></i>
							</button>
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
	emits: ["add", "remove"],

	props: {
		/**
		 * One collapsible list per kind of record that uses the college, department or advisor:
		 * [{ key, label, items: [{ id, name, note, url }] }].
		 *
		 * With editable, a section can also offer every record of its kind to add:
		 * addOptions [{ id, name, note }] (null while loading), and optionally addPlaceholder
		 * and addError.
		 */
		sections: {
			type: Array,
			required: true
		},

		/**
		 * Shows the add search (for sections with addOptions) and a remove button per item.
		 * The parent owns the lists: it gets add(sectionKey, option) and remove(sectionKey, id)
		 * and updates the section's items.
		 */
		editable: {
			type: Boolean,
			default: false
		}
	},

	data: function () {
		return {
			/**
			 * The most add-search results shown at once.
			 * @type {number}
			 */
			addLimit: 10,

			/**
			 * Each section's add-search text, keyed by section key.
			 * @type {Object}
			 */
			addTerms: {},

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
		 * The add-search results: options not in the list already whose name contains the text
		 * (case-insensitive), at most addLimit.
		 * @param {Object} section The section.
		 * @return {Array}
		 */
		addMatches: function (section) {
			return this.allAddMatches(section).slice(0, this.addLimit)
		},

		/**
		 * @param {Object} section The section.
		 * @return {number} How many options match, before the limit.
		 */
		addMatchCount: function (section) {
			return this.allAddMatches(section).length
		},

		/**
		 * @param {Object} section The section.
		 * @return {Array}
		 */
		allAddMatches: function (section) {
			let term = this.addTerm(section).toLowerCase()
			if (term === "" || !section.addOptions) {
				return []
			}
			let listed = new Set(section.items.map((item) => item.id))
			return section.addOptions.filter(
				(option) => !listed.has(option.id) && (option.name || "").toLowerCase().includes(term)
			)
		},

		/**
		 * Hands the picked option to the parent and clears the search for the next one.
		 * @param {Object} section The section.
		 * @param {Object} option The option.
		 */
		addItem: function (section, option) {
			this.$emit("add", section.key, option)
			this.addTerms[section.key] = ""
		},

		/**
		 * @param {Object} section The section.
		 * @return {string} The section's add-search text, trimmed.
		 */
		addTerm: function (section) {
			return (this.addTerms[section.key] || "").trim()
		},

		/**
		 * @param {Object} section The section.
		 * @return {boolean} True if the section shows the add search.
		 */
		canAdd: function (section) {
			return this.editable && section.addOptions !== undefined
		},

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
