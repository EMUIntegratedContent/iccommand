<template>
  <div class="row">
    <div class="col">
      <div class="app-hero">
        <h1 style="color: #ffffff; stroke: #000000">Redirects Application</h1>
        <img class="img-fluid" alt="Redirects Application" src="/images/redirect-front.png"/>
      </div>
      <p>This application stores all information pertaining to the URL redirections of broken links and vanity links. Users with full access of this app have the ability to modify, add, or delete redirects.</p>
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
