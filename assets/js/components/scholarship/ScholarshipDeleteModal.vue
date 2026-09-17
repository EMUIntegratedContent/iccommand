<template>
	<!-- Modal -->
	<div id="deleteModal" class="modal" tabindex="-1" role="dialog">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Delete Scholarship</h5>
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
						Are you sure you want to delete "{{ scholarship.title }}"? Type the
						word <strong>"delete"</strong> to confirm.
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
						Delete Scholarship
					</button>
				</div>
			</div>
		</div>
	</div>
</template>

<style scoped></style>

<script>
export default {
	props: ["scholarship"],

	data: function () {
		return {
			deleteConfirm: null
		}
	},

	methods: {
		deleteItem: function () {
			let self = this

			if (this.deleteConfirm == "delete") {
				this.deleteConfirm = null

				axios
					.delete("/api/scholarships/" + this.scholarship.id)
					.then(function (response) {
						self.itemDeleted()
					})
					.catch(function (error) {
						self.itemDeleteError()
					})
			}
		},

		itemDeleted: function () {
			this.$emit("itemDeleted")
		},

		itemDeleteError: function () {
			this.$emit("itemDeleteError")
		}
	}
}
</script>
