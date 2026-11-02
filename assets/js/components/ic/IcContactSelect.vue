<template>
	<div>
		<VueMultiselect
			:id="inputId"
			:model-value="selectedContact"
			:options="contacts"
			:multiple="false"
			:disabled="disabled"
			:loading="isLoading"
			:custom-label="contactLabel"
			track-by="id"
			placeholder="Search contacts..."
			@update:modelValue="selectContact"
		>
		</VueMultiselect>
		<small v-if="loadFailed" class="text-danger"
			>There was an error loading the contacts.</small
		>
	</div>
</template>

<style src="vue-multiselect/dist/vue-multiselect.css"></style>

<script>
import VueMultiselect from "vue-multiselect"

export default {
	created() {
		this.fetchContacts()
	},

	components: {
		VueMultiselect
	},

	emits: ["update:modelValue", "update:displayName"],

	props: {
		disabled: {
			type: Boolean,
			default: false
		},

		/**
		 * The id for the search input, so a label can point at it.
		 */
		inputId: {
			type: String,
			required: false
		},

		/**
		 * The chosen contact's id, or null. A string when it comes from a raw SQL row.
		 */
		modelValue: {
			type: [Number, String],
			default: null
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
		 * The option for the chosen id.
		 * @return {Object|null}
		 */
		selectedContact: function () {
			let contactId = Number(this.modelValue)
			return this.contacts.find((c) => c.id === contactId) || null
		}
	},

	methods: {
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
		 * Gets the contacts for the picker.
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
		},

		/**
		 * Reports the choice. Clearing the picker passes null.
		 * @param {Object|null} contact The option.
		 */
		selectContact: function (contact) {
			this.$emit("update:modelValue", contact ? contact.id : null)
			this.$emit("update:displayName", contact ? contact.displayName : null)
		}
	}
}
</script>
