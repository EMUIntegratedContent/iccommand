<template>
  <h2 class="page-heading">
    <slot>Title goes here!!</slot>
  </h2>
</template>
<style lang="scss">
@import "../../../css/app";

.page-heading {
  padding: 10px;
  background: $primary;
  color: #ffffff;
}
</style>
<script>
export default {
  created () {
  }
}
</script>
