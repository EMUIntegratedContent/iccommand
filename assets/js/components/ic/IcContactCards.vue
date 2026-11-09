<template>
	<div>
		<template v-if="editable">
			<div class="row">
				<div
					v-for="(contactId, index) in contactIds"
					:key="index"
					class="col-xs-12 col-sm-6 col-md-4 pl-4 pb-2"
				>
					<div class="card">
						<div class="card-header">
							{{ contactName(contactId) || "New contact" }}
							<button
								type="button"
								class="close pull-right"
								title="Remove this contact"
								@click="removeContact(index)"
							>
								<span aria-hidden="true">&times;</span>
							</button>
						</div>
						<div class="card-body">
							<div class="form-group">
								<label :for="inputIdPrefix + '-' + index">Contact *</label>
								<VueMultiselect
									:id="inputIdPrefix + '-' + index"
									:model-value="optionFor(contactId)"
									:options="optionsFor(contactId)"
									:multiple="false"
									:loading="isLoading"
									:custom-label="contactLabel"
									track-by="id"
									placeholder="Search contacts..."
									@update:modelValue="selectContact(index, $event)"
								>
								</VueMultiselect>
							</div>
						</div>
					</div>
				</div>
				<div class="col-xs-12 col-sm-6 col-md-4">
					<div class="card mapitem-add-aux hand" @click="addContact">
						<div class="card-body">
							<i class="fa fa-plus fa-5x"></i><br />
							Add contact
						</div>
					</div>
				</div>
			</div>
			<small v-if="loadFailed" class="text-danger"
				>There was an error loading the contacts.</small
			>
		</template>
		<template v-else>
			<ul v-if="chosenIds.length > 0">
				<li v-for="contactId in chosenIds" :key="contactId">
					{{ contactName(contactId) || "Contact #" + contactId }}
				</li>
			</ul>
			<p v-else>No contacts attributed.</p>
		</template>
	</div>
</template>

<style src="vue-multiselect/dist/vue-multiselect.css"></style>

<script>
import VueMultiselect from "vue-multiselect"

/**
 * The contacts of a program, scholarship or building: one card per contact, with a picker
 * and a remove button, plus an "Add contact" card (the building bathrooms design). Read-only
 * it is a plain list of names.
 *
 * v-model is the list of contact ids. A card that has no contact yet holds null; the forms
 * can send the list as is, because the API skips nulls and duplicates.
 */
export default {
	created() {
		this.fetchContacts()
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
			default: "icContact"
		},

		/**
		 * The chosen contact ids.
		 */
		modelValue: {
			type: Array,
			default: () => []
		}
	},

	data: function () {
		return {
			/**
			 * The contacts to choose from (id, displayName, type, email).
			 * @type {Array}
			 */
			contacts: [],

			isLoading: true,
			loadFailed: false
		}
	},

	computed: {
		/**
		 * The ids as numbers, nulls kept for cards that are still empty.
		 * @return {Array<number|null>}
		 */
		contactIds: function () {
			return (this.modelValue || []).map((id) =>
				id === null || id === "" ? null : Number(id)
			)
		},

		/**
		 * The ids of the contacts actually chosen.
		 * @return {number[]}
		 */
		chosenIds: function () {
			return this.contactIds.filter((id) => id !== null)
		}
	},

	methods: {
		/**
		 * The name of a chosen contact.
		 * @param {number|null} contactId
		 * @return {string|null}
		 */
		contactName: function (contactId) {
			let contact = this.contacts.find((c) => c.id === contactId)
			return contact ? contact.displayName : null
		},

		/**
		 * The option text. Searching matches it too, so the email is searchable.
		 * @param {Object} contact The option.
		 * @return {string}
		 */
		contactLabel: function (contact) {
			return contact.email
				? contact.displayName + " (" + contact.email + ")"
				: contact.displayName
		},

		/**
		 * The option for a card's id.
		 * @param {number|null} contactId
		 * @return {Object|null}
		 */
		optionFor: function (contactId) {
			return this.contacts.find((c) => c.id === contactId) || null
		},

		/**
		 * The options for a card: every contact not chosen on another card.
		 * @param {number|null} contactId This card's id.
		 * @return {Array}
		 */
		optionsFor: function (contactId) {
			return this.contacts.filter(
				(c) => c.id === contactId || !this.chosenIds.includes(c.id)
			)
		},

		addContact: function () {
			this.$emit("update:modelValue", [...this.contactIds, null])
		},

		removeContact: function (index) {
			let ids = [...this.contactIds]
			ids.splice(index, 1)
			this.$emit("update:modelValue", ids)
		},

		/**
		 * Reports a card's choice. Clearing the picker leaves the card empty.
		 * @param {number} index The card.
		 * @param {Object|null} contact The option.
		 */
		selectContact: function (index, contact) {
			let ids = [...this.contactIds]
			ids[index] = contact ? contact.id : null
			this.$emit("update:modelValue", ids)
		},

		/**
		 * Gets the contacts for the pickers.
		 */
		fetchContacts: function () {
			let self = this

			axios
				.get("/api/ic/contacts/options")
				.then(function (response) {
					// Success.
					self.contacts = response.data
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
