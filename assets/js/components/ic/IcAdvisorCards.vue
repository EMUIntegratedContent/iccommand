<template>
	<div>
		<template v-if="editable">
			<div class="row">
				<div
					v-for="(advisorId, index) in advisorIds"
					:key="index"
					class="col-xs-12 col-sm-6 col-md-4 pl-4 pb-2"
				>
					<div class="card">
						<div class="card-header">
							{{ advisorName(advisorId) || "New advisor" }}
							<button
								type="button"
								class="close pull-right"
								title="Remove this advisor"
								@click="removeAdvisor(index)"
							>
								<span aria-hidden="true">&times;</span>
							</button>
						</div>
						<div class="card-body">
							<div class="form-group">
								<label :for="inputIdPrefix + '-' + index">Advisor *</label>
								<VueMultiselect
									:id="inputIdPrefix + '-' + index"
									:model-value="optionFor(advisorId)"
									:options="optionsFor(advisorId)"
									:multiple="false"
									:loading="isLoading"
									:custom-label="advisorLabel"
									track-by="id"
									placeholder="Search advisors..."
									@update:modelValue="selectAdvisor(index, $event)"
								>
								</VueMultiselect>
							</div>
						</div>
					</div>
				</div>
				<div class="col-xs-12 col-sm-6 col-md-4">
					<div class="card mapitem-add-aux hand" @click="addAdvisor">
						<div class="card-body">
							<i class="fa fa-plus fa-5x"></i><br />
							Add advisor
						</div>
					</div>
				</div>
			</div>
			<small v-if="loadFailed" class="text-danger"
				>There was an error loading the advisors.</small
			>
		</template>
		<template v-else>
			<ul v-if="chosenIds.length > 0">
				<li v-for="advisorId in chosenIds" :key="advisorId">
					{{ advisorName(advisorId) || "Advisor #" + advisorId }}
				</li>
			</ul>
			<p v-else>No advisors attributed.</p>
		</template>
	</div>
</template>

<style src="vue-multiselect/dist/vue-multiselect.css"></style>

<script>
import VueMultiselect from "vue-multiselect"

/**
 * The advisors of a program, scholarship or building: one card per advisor, with a picker
 * and a remove button, plus an "Add advisor" card (the building bathrooms design). Read-only
 * it is a plain list of names.
 *
 * v-model is the list of advisor ids. A card that has no advisor yet holds null; the forms
 * can send the list as is, because the API skips nulls and duplicates.
 */
export default {
	created() {
		this.fetchAdvisors()
	},

	components: {
		VueMultiselect
	},

	emits: ["update:modelValue"],

	props: {
		/**
		 * Whether the cards can be changed. False shows the list of names.
		 */
		editable: {
			type: Boolean,
			default: false
		},

		/**
		 * Prefix for the picker ids, so labels can point at them.
		 */
		inputIdPrefix: {
			type: String,
			default: "icAdvisor"
		},

		/**
		 * The chosen advisor ids.
		 */
		modelValue: {
			type: Array,
			default: () => []
		}
	},

	data: function () {
		return {
			/**
			 * The advisors to choose from (id, displayName, type, email).
			 * @type {Array}
			 */
			advisors: [],

			isLoading: true,
			loadFailed: false
		}
	},

	computed: {
		/**
		 * The ids as numbers, nulls kept for cards that are still empty.
		 * @return {Array<number|null>}
		 */
		advisorIds: function () {
			return (this.modelValue || []).map((id) =>
				id === null || id === "" ? null : Number(id)
			)
		},

		/**
		 * The ids of the advisors actually chosen.
		 * @return {number[]}
		 */
		chosenIds: function () {
			return this.advisorIds.filter((id) => id !== null)
		}
	},

	methods: {
		/**
		 * The name of a chosen advisor.
		 * @param {number|null} advisorId
		 * @return {string|null}
		 */
		advisorName: function (advisorId) {
			let advisor = this.advisors.find((c) => c.id === advisorId)
			return advisor ? advisor.displayName : null
		},

		/**
		 * The option text. Searching matches it too, so the email is searchable.
		 * @param {Object} advisor The option.
		 * @return {string}
		 */
		advisorLabel: function (advisor) {
			return advisor.email
				? advisor.displayName + " (" + advisor.email + ")"
				: advisor.displayName
		},

		/**
		 * The option for a card's id.
		 * @param {number|null} advisorId
		 * @return {Object|null}
		 */
		optionFor: function (advisorId) {
			return this.advisors.find((c) => c.id === advisorId) || null
		},

		/**
		 * The options for a card: every advisor not chosen on another card.
		 * @param {number|null} advisorId This card's id.
		 * @return {Array}
		 */
		optionsFor: function (advisorId) {
			return this.advisors.filter(
				(c) => c.id === advisorId || !this.chosenIds.includes(c.id)
			)
		},

		addAdvisor: function () {
			this.$emit("update:modelValue", [...this.advisorIds, null])
		},

		removeAdvisor: function (index) {
			let ids = [...this.advisorIds]
			ids.splice(index, 1)
			this.$emit("update:modelValue", ids)
		},

		/**
		 * Reports a card's choice. Clearing the picker leaves the card empty.
		 * @param {number} index The card.
		 * @param {Object|null} advisor The option.
		 */
		selectAdvisor: function (index, advisor) {
			let ids = [...this.advisorIds]
			ids[index] = advisor ? advisor.id : null
			this.$emit("update:modelValue", ids)
		},

		/**
		 * Gets the advisors for the pickers.
		 */
		fetchAdvisors: function () {
			let self = this

			axios
				.get("/api/ic/advisors/options")
				.then(function (response) {
					// Success.
					self.advisors = response.data
					self.isLoading = false
				})
				.catch(function () {
					// Failure.
					self.loadFailed = true
					self.isLoading = false
				})
		}
	}
}
</script>
