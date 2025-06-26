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
          <p>To upload the Daily Crime Log CSV file, click on the blue "Choose File" button below.
            A dialog box will appear. Please follow the prompts to complete the upload process.
          </p>
          <p>
            If you experience any issues, please submit a <a href="https://service.emich.edu/TDClient/30/Portal/Requests/ServiceDet?ID=26" target="_blank">Web Support Ticket</a>.
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





