<template>
	<div>
    <div class="row">
      <div class="col-sm-12">
        <h3 class="my-4">{{ modules[0].category }}</h3>
      </div>
      <template v-for="(module, i) in modules" :key="`${module.category}-mod-${i}`">
        <div
            class="card"
            :class="`${smDisplay} ${mdDisplay}`"
        >
          <div class="card-body">
            <h5 class="card-title">{{ module.title }}</h5>
            <p class="card-text"><span v-html="module.description"></span></p>
            <a :href="module.buttonLink" class="btn btn-primary">{{
                module.buttonText
              }}</a>
          </div>
        </div>
      </template>
    </div>
	</div>
</template>
<script>
export default {
	created() {},
	components: {},
	props: {
		modules: {
			type: Array,
			required: false
		},
    mdDisplay: {
      type: String,
      default: 'col-md-4'
    },
    smDisplay: {
      type: String,
      default: 'col-sm-6'
    }
	},
	data: function () {
		return {}
	},
	computed: {},
	methods: {},
}
</script>
<style lang="scss">
@import "../../../assets/css/variables";
//.emergency-card {
//  background: $danger-emu !important;
//  color: white !important;
//}
</style>
