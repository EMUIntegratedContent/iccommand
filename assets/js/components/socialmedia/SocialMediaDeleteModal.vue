<template>
	<!-- Modal -->
	<div id="deleteModal" class="modal" tabindex="-1" role="dialog">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Delete Entity</h5>
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
						Are you sure you want to delete "{{ entity.name }}"? Type the word
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
						Delete Entity
					</button>
				</div>
			</div>
		</div>
	</div>
</template>

<style scoped></style>

<script>
export default {
	props: ["entity"],

	data: function () {
		return {
			/**
			 * The confirmation of the user for the deletion of the entity.
			 * @type {string}
			 */
			deleteConfirm: null
		}
	},

	methods: {
		/**
		 * Deletes the entity.
		 */
		deleteItem: function () {
			let self = this

			// The word "delete" must be typed in modal.
			if (this.deleteConfirm == "delete") {
				// Reset the delete text.
				this.deleteConfirm = null

				axios
					.delete("/api/social-media/" + this.entity.id)
					.then(function (response) {
						// Success.
						self.itemDeleted()
					})
					.catch(function (error) {
						// Failure.
						self.itemDeleteError()
					})
			}
		},

		/**
		 * Emits an event to the parent telling it that the item has been deleted.
		 */
		itemDeleted: function () {
			this.$emit("itemDeleted")
		},

		/**
		 * Emits an event to the parent telling it that the item has not been deleted.
		 */
		itemDeleteError: function () {
			this.$emit("itemDeleteError")
		}
	}
}
</script>
