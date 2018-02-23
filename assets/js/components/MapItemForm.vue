<template>
  <div>
    <heading>
      <span slot="icon" v-html="headingIcon">{{ headingIcon }}</span>
      <span slot="title">New {{ record.itemType }}</span>
    </heading>
    <div class="row">
      <div class="col-8">
        <form>
          <div class="form-group">
            <label>Title</label>
            <input type="text" class="form-control" v-model="record.title">
          </div>
          <div class="form-group">
            <label>Latitude</label>
            <input type="number" step="any" class="form-control" v-model="record.latitude">
          </div>
          <div class="form-group">
            <label>Longitude</label>
            <input type="number" step="any" class="form-control" v-model="record.longitude">
          </div>
          <button class="btn btn-success" type="button" @click="submitForm">{{ newForm ? 'Create ' + record.itemType : 'Update ' + record.itemType }}</button>
        </form>
      </div>
      <div class="col-4">
        Tags
        <multiselect
          v-model="record.tags"
          :options="tags"
          :multiple="true"
          placeholder="Choose tags"
          label="tag"
          track-by="id"
          >
        </multiselect>
      </div>
    </div>
  </div>
</template>

<style>

</style>
<style src="vue-multiselect/dist/vue-multiselect.min.css"></style>

<script>
  import Heading from './HeadingComponent.vue'
  import Multiselect from 'vue-multiselect'
  export default {
    mounted() {
      console.log('Choices mounted.')
      this.fetchTags()
    },
    components: {Heading, Multiselect},
    props:{
      record: {
        type: Object,
        required: true,
      }
    },
    data: function() {
      return {
        newForm: true,
        // the list of potential tags for this map item
        tags:[
          {
              id: 4,
              tag: 'bob',
          },
          {
              id: 5,
              tag: 'tom',
          },
          {
              id: 6,
              tag: 'rob',
          },
        ]
      }
    },
    computed: {
      headingIcon: function() {
        switch(this.record.itemType){
          case 'building':
            return '<i class="fa fa-building fa-3x"></i>'
          case 'bathroom':
            return '<i class="fa fa-male fa-3x"></i><i class="fa fa-female fa-3x"></i>'
          default:
            return false
        }
      },
    },
    methods: {
      fetchTags: function(){
        console.log("fetching tags")
      },
      // Submit the form via the API
      submitForm: function(){
        /*
        axios.post('/api/mapitems', this.record).then((res) => {
            console.log(res.data)
        });
        */
        axios.get('/api/map/items', this.record).then((res) => {
            console.log(res.data)
        });
      }
    }
  }
</script>
