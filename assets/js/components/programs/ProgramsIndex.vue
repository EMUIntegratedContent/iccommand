<template>
  <div class="row">
    <div class="col">
      <div class="app-hero">
        <h1 style="color: #ffffff; background: #222222; padding: 5px;">Catalog Programs Manager</h1>
        <img class="img-fluid" alt="Catalog Programs Manager" src="/images/programs-front.png"/>
      </div>
      <p>The catalog programs manager allows for the editing of program names, websites, etc., from the Acalog course catalog.</p>
    </div>
  </div>
</template>

<style></style>

<script>
import Heading from "../utils/Heading.vue";
import Paginator from "../utils/Paginator.vue";

export default {
  created() {},

  mounted() {
    console.log("Index mounted.");
  },

  components: {Heading, Paginator},

  props: {
    permissions: {
      type: Array,
      required: true
    }
  },

  data: function() { return {}; },

  computed: {},

  methods: {},

  filters: {}
}
</script>
