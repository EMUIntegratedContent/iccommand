<template>
	<!-- Modal -->
	<div id="deleteModal" class="modal" tabindex="-1" role="dialog">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Delete {{ entityLabel }}</h5>
					<button
						type="button"
						class="close"
						data-dismiss="modal"
						aria-label="Close"
					>
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<p>
						Are you sure you want to delete "{{ itemName }}"? Type the word
						<strong>"delete"</strong> to confirm.
					</p>
					<div class="form-group">
						<label for="deleteConfirm" class="sr-only" aria-hidden="true"
							>Type "delete" to confirm</label
						>
						<input
							type="text"
							v-model="deleteConfirm"
							class="form-control"
							id="deleteConfirm"
						/>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal">
						Cancel
					</button>
					<button
						type="button"
						class="btn btn-danger"
						data-dismiss="modal"
						@click="deleteItem"
						:disabled="deleteConfirm != 'delete'"
					>
						Delete {{ entityLabel }}
					</button>
				</div>
			</div>
		</div>
	</div>
</template>

<style scoped></style>

<script>
export default {
	props: {
		/**
		 * The API URL that deletes the item, e.g. /api/admin/colleges/5.
		 */
		deleteUrl: {
			type: String,
			required: true
		},

		/**
		 * The kind of item, e.g. "College".
		 */
		entityLabel: {
			type: String,
			required: true
		},

		/**
		 * The name of the item being deleted.
		 */
		itemName: {
			type: String,
			required: true
		}
	},

	data: function () {
		return {
			/**
			 * The confirmation of the user for the deletion of the item.
			 * @type {string}
			 */
			deleteConfirm: null
		}
	},

	methods: {
		/**
		 * Deletes the item.
		 */
		deleteItem: function () {
			let self = this

			// The word "delete" must be typed in modal.
			if (this.deleteConfirm == "delete") {
				// The delete text is reset once the request finishes, not here: clearing it now
				// disables this button before the click reaches Bootstrap's data-dismiss
				// handler, which then ignores it and leaves the modal open.
				axios
					.delete(this.deleteUrl)
					.then(function (response) {
						// Success.
						self.deleteConfirm = null
						self.$emit("itemDeleted")
					})
					.catch(function (error) {
						// Failure. A 409 carries the reason the item is still in use.
						self.deleteConfirm = null
						let data = error.response ? error.response.data : null
						self.$emit(
							"itemDeleteError",
							data && data.message
								? data.message
								: "There was an error deleting this " +
										self.entityLabel.toLowerCase() +
										"."
						)
					})
			}
		}
	}
}
</script>
