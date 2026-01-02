<template>
  <div class="row">
    <div class="col">
      <div class="app-hero">
        <h1 style="color: #ffffff; background: #222222; padding: 5px;">Degrees & Programs Manager</h1>
        <img class="img-fluid" alt="Catalog Programs Manager" src="/images/programs-front.png"/>
      </div>
      <p>The degrees and programs manager allows for the editing of program names, marketing website URLs, delivery modes, etc., that display at <a href="https://www.emich.edu/degrees" target="_blank">emich.edu/degrees</a>.</p>
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
