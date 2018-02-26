<template>
  <div class="row">
    <div class="col-4">
      <div class="card" @click="setItem('building')">
        <div class="card-body">
          <h5 class="card-title">Building</h5>
          <i class="fa fa-building fa-5x"></i>
          <p class="card-text">An academic or administrative building.</p>
        </div>
      </div>
    </div>
    <div class="col-4">
      <div class="card" @click="setItem('bathroom')">
        <div class="card-body">
          <h5 class="card-title">Bathroom</h5>
          <i class="fa fa-male fa-5x"></i><i class="fa fa-female fa-5x"></i>
          <p class="card-text">A potty!.</p>
        </div>
      </div>
    </div>
  </div>
</template>

<style>
.card{
  text-align: center;
}

</style>

<script>
  export default {
    mounted() {
      console.log('Choices mounted.')
    },
    components: {},
    data: function() {
      return {
      }
    },
    methods: {
      // Emit an event to the parent component telling it which new item was chosen
      setItem: function(itemType){
        // Vue 2 has done away with $dispatch in favor of $emit
        this.$emit('mapItemChosen', itemType)
      }
    }
  }
</script>
