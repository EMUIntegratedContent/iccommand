<template>
  <h2 class="page-heading">
    <slot name="icon">
      icon goes here
    </slot>
    <slot name="title">Title goes here</slot>
  </h2>
</template>
<style>
  .page-heading{
    border: 2px solid #2e456d;
    padding: 10px;
    background: #2e456d;
    color: #ffffff;
  }
</style>
<script>
    export default {
        mounted() {}
    }
</script>
