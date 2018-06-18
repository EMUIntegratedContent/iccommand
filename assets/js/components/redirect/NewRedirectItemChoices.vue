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
            <p class="card-text">I like cheese and crackers.</p>
            <button type="button" class="btn btn-primary" @click="setItem('broken link')">New broken link redirect item</button>
          </div>
        </div>
      </div>
      <div class="col-sm-6">
        <div class="card">
          <div class="card-body">
            <h5 class="card-title">Link Shortening</h5>
            <p class="card-text">I like crackers and cheese.</p>
            <button type="button" class="btn btn-secondary" @click="setItem('shortened link')">New shortened link item</button>
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
    mounted() {
      console.log("Choices mounted.");
    },

    components: {
      Heading
    },

    data: function() { return {}; },

    computed: {
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
      }
    }
  }
</script>
