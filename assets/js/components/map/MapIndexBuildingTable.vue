<template>
  <div>
    <heading>
<!--      <span slot="icon" v-html="headingIcon"></span>-->
      <span>EMU Map Items</span>
    </heading>
    <div id="accordion">
      <div class="card">
        <div class="card-header" id="headingOne">
          <h5 class="mb-0">
            <button class="btn btn-link" data-toggle="collapse" data-target="#collapseBuildings" aria-expanded="true" aria-controls="collapseBuildings">
              Buildings
            </button>
          </h5>
        </div>
        <div id="collapseBuildings" class="collapse show" aria-labelledby="headingBuildings" data-parent="#accordion">
          <div class="card-body">
            <paginator :items="buildings" :itemsPerPage="itemsPerPage" :permissions="this.permissions"></paginator>
          </div>
        </div>
      </div>
      <div class="card">
        <div class="card-header" id="headingTwo">
          <h5 class="mb-0">
            <button class="btn btn-link collapsed" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
              Collapsible Group Item #2
            </button>
          </h5>
        </div>
        <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#accordion">
          <div class="card-body">
            Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry richardson ad squid. 3 wolf moon officia aute, non cupidatat skateboard dolor brunch. Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et. Nihil anim keffiyeh helvetica, craft beer labore wes anderson cred nesciunt sapiente ea proident. Ad vegan excepteur butcher vice lomo. Leggings occaecat craft beer farm-to-table, raw denim aesthetic synth nesciunt you probably haven't heard of them accusamus labore sustainable VHS.
          </div>
        </div>
      </div>
      <div class="card">
        <div class="card-header" id="headingThree">
          <h5 class="mb-0">
            <button class="btn btn-link collapsed" data-toggle="collapse" data-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
              Collapsible Group Item #3
            </button>
          </h5>
        </div>
        <div id="collapseThree" class="collapse" aria-labelledby="headingThree" data-parent="#accordion">
          <div class="card-body">
            Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry richardson ad squid. 3 wolf moon officia aute, non cupidatat skateboard dolor brunch. Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et. Nihil anim keffiyeh helvetica, craft beer labore wes anderson cred nesciunt sapiente ea proident. Ad vegan excepteur butcher vice lomo. Leggings occaecat craft beer farm-to-table, raw denim aesthetic synth nesciunt you probably haven't heard of them accusamus labore sustainable VHS.
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
    import Paginator from './Paginator.vue'

    export default {
      created() {},
      mounted() {
        this.fetchMapItems()
      },
      components: {Heading, Paginator},
      props:{
        permissions: {
          type: Array,
          required: true
        },
      },
      data: function() {
        return {
          buildings: [],
          currentPage: 1,
          itemsPerPage: 10,
          resultCount: 0,
        }
      },
      computed: {
        headingIcon: function() {
          return '<i class="fa fa-list"></i>'
        },
        userCanCreate: function(){
          return this.permissions[0].create ? true : false
        },
        userCanEdit: function(){
          return this.permissions[0].edit ? true : false
        }
      },
      methods: {
        fetchMapItems: function(){
          let self = this
          axios.get('/api/mapitems')
          // success
          .then(function (response) {
            response.data.forEach(function(item){
              switch(item.itemType){
                case 'building':
                  self.buildings.push(item)
                  break
              }
            })
          })
          // fail
          .catch(function (error) {
            console.log("COULDN'T GET MAP ITEMS!")
          })
        },
      },
      filters: {},
    }
</script>
