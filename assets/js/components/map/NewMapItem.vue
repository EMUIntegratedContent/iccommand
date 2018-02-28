<template>
  <div>
    <div v-if="this.permissions[0].create" id="toReplace">
      <template v-if="previousStep">
        <button class="btn btn-info" @click="swapComponent(previousStep)"><i class="fa fa-chevron-circle-left"></i> Previous</button>
      </template>
      <!-- :is = a magic vue word -->
      <div :is="currentComponent" @mapItemChosen="setItemType" :itemType="itemType" :itemExists="false" :permissions="this.permissions" startMode="edit"></div>
      <div v-show="!currentComponent" v-for="component in componentsArray">
        <button @click="swapComponent(component)">{{component}}</button>
      </div>
    </div>
    <div v-else class="alert alert-danger alert-dismissible fade show" role="alert">
      You do not have create privileges for map items.
    </div>
  </div>
</template>

<style>
.card{
  text-align: center;
}
</style>

<script>
  import NewMapItemChoices from './NewMapItemChoices.vue'
  import MapItemForm from './MapItemForm.vue'
  export default {
    mounted() {
      console.log('Component mounted.')
    },
    components: {
      'new-map-item-choices': NewMapItemChoices,
      'map-item-form': MapItemForm,
    },
    props:{
      permissions: {
        type: Array,
        required: true
      },
    },
    computed:{
      // determine what the next step in the new item creation is, if any
      nextStep: function(){
        switch(this.currentComponent){
          case 'new-map-item-choices':
            return 'map-item-form'
          default:
            return null
        }
      },
      // determine what the previous step in the new item creation is, if any
      previousStep: function(){
        switch(this.currentComponent){
          case 'map-item-form':
            return 'new-map-item-choices'
          default:
            return null
        }
      }
    },
    data: function() {
      return {
        currentComponent: 'new-map-item-choices',
        componentsArray: ['new-map-item-choices', 'map-item-form'],
        itemType: '',
      }
    },
    methods: {
      swapComponent: function(component){
        this.currentComponent = component
      },
      setItemType: function(itemType){
        this.itemType = itemType // set the item type to pass to the form
        this.swapComponent('map-item-form') // change to the form for a new map item
      }
    },
    events: {
    }
  }
</script>
