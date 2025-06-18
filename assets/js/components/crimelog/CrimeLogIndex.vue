<template>
  <div>
    <heading>
      <span slot="title">DPS Upload Crimelog</span>
    </heading>
    <div class="row hover-cards">
    <div class="col-sm-6 mt-2">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Bulk Upload</h5>
          <p><a href="/bulk_crimelog_template.csv" target="_blank" class="text-info">Download Sample Template [CSV]</a><br>Please download the sample download template to see the allowable column fields for the CSV file.
          </p>
          <button type="button" class="btn btn-primary" @click="openUploadModal">Choose File</button>
        </div>
      </div>
    </div>
    </div>
      <CrimeLogItem :permissions="this.permissions"></CrimeLogItem>
  </div>
</template>

<script>
import Heading from "../utils/Heading.vue";
import CrimeLogItem from "./CrimeLogItem.vue";

export default {
  created() {},

  mounted() {
    console.log("Index mounted.");
  },

 components: {Heading, CrimeLogItem},

  props: {
   permissions: {
      type: Array,
      required: true
    }
  },

 data: function() { 
  return {
     permissions: this.permissions
    };
 },

 computed: {},

 methods: {
      openUploadModal: function(){
        $('#uploadModal').modal('show');
    },
  },

  filters: {}
}
</script>





