<template>
  <div>
    <heading>
      <span slot="icon" v-html="headingIcon">{{ headingIcon }}</span>
      <span slot="title">Redirects</span>
    </heading>
    <!--<div v-if="api.Error.status" class="alert alert-danger fade show" role="alert">
      {{ apiError.message }}
    </div>-->
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

    data: function() {
      return {
        apiError: {
          message: null,
          status: null
        }
      };
    },

    computed: {
      headingIcon: function() {
        return "<i class='fa fa-list'></i>";
      }
    },

    methods: {},

    filters: {}
  }
</script>
