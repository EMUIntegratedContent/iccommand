<template>
  <div>
    <div class="row hover-cards">
      <div class="col-12">
        <heading>
          <span slot="icon" v-html="headingIcon">{{ headingIcon }}</span>
          <span slot="title">Step 1/2: Choose item type</span>
        </heading>
        <div class="row">
          <div class="col-xs-12 col-md-4">
            <div class="card" @click="setItem('building')">
              <div class="card-body">
                <h5 class="card-title">Building</h5>
                <i class="fa fa-building fa-4x"></i>
              </div>
            </div>
          </div>
          <div class="col-xs-12 col-md-4">
            <!--
            <div class="card" @click="setItem('bathroom')">
              <div class="card-body">
                <h5 class="card-title">Bathroom</h5>
                <i class="fa fa-male fa-4x"></i><i class="fa fa-female fa-4x"></i>
              </div>
            </div>
            -->
          </div>
          <div class="col-xs-12 col-md-4">
            <div class="card" @click="setItem('emergency')">
              <div class="card-body">
                <h5 class="card-title">Emergency Device</h5>
                <i class="fa fa-ambulance fa-4x"></i>
              </div>
            </div>
          </div>
        </div>
          <div class="row spacer-top">
              <div class="col-xs-12 col-md-4">
                  <div class="card" @click="setItem('parking')">
                      <div class="card-body">
                          <h5 class="card-title">Parking</h5>
                          <i class="fa fa-car fa-4x"></i>
                      </div>
                  </div>
              </div>
              <div class="col-xs-12 col-md-4">
                  <div class="card" @click="setItem('attraction')">
                      <div class="card-body">
                          <h5 class="card-title">Attraction</h5>
                          <i class="fa fa-paint-brush fa-4x"></i>
                      </div>
                  </div>
              </div>
              <div class="col-xs-12 col-md-4">
                  <div class="card" @click="setItem('bus')">
                      <div class="card-body">
                          <h5 class="card-title">Bus Stop</h5>
                          <i class="fa fa-bus fa-4x"></i>
                      </div>
                  </div>
              </div>
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
  import Heading from '../utils/Heading.vue'

  export default {
    mounted() {
      console.log('Choices mounted.')
    },
    components: {Heading},
    data: function() {
      return {
      }
    },
    computed:{
      headingIcon: function() {
        return '<i class="fa fa-map"></i>'
      },
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
