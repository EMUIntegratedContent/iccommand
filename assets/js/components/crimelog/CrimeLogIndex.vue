<template>
  <div>
    <heading>
      <span slot="title">DPS Upload Crimelog</span>
    </heading>
    <div class="row hover-cards">
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
  created() {},

  mounted() {
    console.log("Index mounted.");
  },

  components: {Heading},

  props: {
    permissions: {
      type: Array,
      required: true
    }
  },

  data: function() { return {}; },

  computed: {},

 methods: {
    openUploadModal: function() {
      this.$emit("openUploadModal");
    },
  },

  filters: {}
}
</script>




