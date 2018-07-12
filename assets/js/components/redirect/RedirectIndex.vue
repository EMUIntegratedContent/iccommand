<template>
  <div>
    <heading>
      <span slot="title">Redirects</span>
    </heading>
    <p>The best app of the three applications.</p>
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
