<template>
  <div>
    <heading>
      <span slot="title">Step 1/2: Choose redirect type</span>
    </heading>
    <div class="row hover-cards">
      <div class="col-sm-6">
        <div class="card">
          <div class="card-body">
            <h5 class="card-title">Redirecting a Broken Link</h5>
            <p class="card-text">Point a broken link to a working URL.</p>
            <button type="button" class="btn btn-primary" @click="setItem('redirect of broken link')">Create</button>
          </div>
        </div>
      </div>
      <div class="col-sm-6">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Link Shortening</h5>
          <p class="card-text">Make a "vanity URL" – a shortened link of an otherwise longer URL.</p>
          <button type="button" class="btn btn-secondary" @click="setItem('redirect of shortened link')">Create</button>
        </div>
      </div>
    </div>
    <div class="col-sm-6 mt-2">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Bulk Upload</h5>
          <p><a href="/bulk_redirect_template.csv" target="_blank" class="text-info">Download Sample Template [CSV]</a><br>Note that the options for the "item_type" column are:
            <ul>
              <li>redirect of broken link <small> (best for site migration)</small></li>
              <li>redirect of shortened link</li>
              <li>invalid redirect of broken link</li>
              <li>invalid redirect of shortened link</li>
            </ul>
          </p>
          <button type="button" class="btn btn-primary" @click="openUploadModal">Choose File</button>
        </div>
      </div>
    </div>
    </div>
  </div>
</template>

<style></style>

<script>
import Heading from "../utils/Heading.vue";

export default {
  components: {Heading},

  data: function() { return {}; },

  computed: {
    /**
     * Gets the heading icon.
     * @return {string} The heading icon.
     */
    headingIcon: function() {
      return "<i class='fa fa-map'></i>";
    }
  },

  methods: {
    /**
     * Changes the item with the specified item type.
     * @param {string} itemType The item type of the new item.
     */
    setItem: function(itemType) {
      // Vue 2 has done away with $dispatch in favor of $emit.
      this.$emit("redirectItemChosen", itemType);
    },

    openUploadModal: function() {
      this.$emit("openUploadModal");
    },
  }
}
</script>
