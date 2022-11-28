<template>
  <div>
    <heading>
<!--      <span slot="icon" v-html="headingIcon"></span>-->
      <span>Step 1/2: Choose item type</span>
    </heading>
    <div class="row hover-cards">
      <div class="col-sm-6">
        <div class="card">
          <div class="card-body">
            <h5 class="card-title">Building</h5>
            <p class="card-text">With supporting text below as a natural lead-in to additional content.</p>
            <button type="button" class="btn btn-primary" @click="setItem('building')">New building item</button>
          </div>
        </div>
      </div>
      <div class="col-sm-6">
        <div class="card">
          <div class="card-body">
            <h5 class="card-title">Parking</h5>
            <p class="card-text">With supporting text below as a natural lead-in to additional content.</p>
            <button type="button" class="btn btn-warning" @click="setItem('parking')">New parking lot</button>
          </div>
        </div>
      </div>
      <div class="col-sm-6">
        <div class="card">
          <div class="card-body">
            <h5 class="card-title">Emergency Device</h5>
            <p class="card-text">With supporting text below as a natural lead-in to additional content.</p>
            <button type="button" class="btn btn-danger" @click="setItem('emergency device')">New emergency device
            </button>
          </div>
        </div>
      </div>
      <div class="col-sm-6">
        <div class="card">
          <div class="card-body">
            <h5 class="card-title">Bus Stop</h5>
            <p class="card-text">With supporting text below as a natural lead-in to additional content.</p>
            <button type="button" class="btn btn-secondary" @click="setItem('bus')">New bus stop</button>
          </div>
        </div>
      </div>
      <div class="col-sm-6">
        <div class="card">
          <div class="card-body">
            <h5 class="card-title">Exhibit</h5>
            <p class="card-text">With supporting text below as a natural lead-in to additional content.</p>
            <button type="button" class="btn btn-success" @click="setItem('exhibit')">New exhibit</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style>

</style>

<script>
import Heading from '../utils/Heading.vue'

export default {
  components: {Heading},
  data: function () {
    return {}
  },
  computed: {
    headingIcon: function () {
      return '<i class="fa fa-map"></i>'
    },
  },
  methods: {
    // Emit an event to the parent component telling it which new item was chosen
    setItem: function (itemType) {
      // Vue 2 has done away with $dispatch in favor of $emit
      this.$emit('mapItemChosen', itemType)
    }
  }
}
</script>
