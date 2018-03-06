<template>
  <!--
  <div class="pod-container">
    <img :src="thumbnailURL + this.image.path" :alt="this.image.name" />
  </div>
-->
  <li class="list-group-item">
    <div class="row">
        <div class="col-sm-9">
          <h6 class="box-title" v-if="!isNameEdit">{{this.image.name}} <span v-if="this.deleteURL" @click="editName"><i class="fa fa-pencil" aria-hidden="true"></i></span></h6>
          <input v-if="isNameEdit" v-model="this.image.name" type="text" />
        </div><!-- /.col-md-12 -->
        <div class="col-sm-3">
          <button type="button" class="btn btn-sm btn-danger pull-right" @click="emitImageRemove(this.image)"><i class="fa fa-times" aria-hidden="true"></i></button>
        </div><!-- /.col-md-12 -->
    </div><!-- /.row -->
    <div class="row">
      <div class="col-sm-12">
        <img :src="thumbnailURL + this.image.path" :alt="this.image.name" class="rounded" />
      </div>
    </div>
  </li><!-- /li -->
</template>

<style>
.pod-container{
  width: 100%;
  min-height: 120px;
  border-radius: 15px;
  border: 2px solid black;
  padding: 5px;
}

</style>

<script>
  export default {
    mounted() {
      console.log('Image mounted.')
    },
    components: {},
    props:{
      deleteURL: {
        type: String,
        required: false,
      },
      thumbnailURL: {
        type: String,
        required: true
      },
      image: {
        type: Object,
        required: true
      },
    },
    data: function() {
      return {
        isNameEdit: false
      }
    },
    methods: {
      // Emit an event to the parent component telling it which new item was chosen
      editName: function(){
        this.isNameEdit = true
      }
    }
  }
</script>
