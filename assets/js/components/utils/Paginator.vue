<template>
  <div>
    <div class="row">
      <div class="col-xs-12 col-sm-9 col-md-10">
        <ul class="pagination">
          <li :class="{disabled: (currentPage <= 1)}" class="page-item">
            <a href="#" @click.prevent="setPage(currentPage-1)" class="page-link" tabindex="-1">Previous</a>
          </li>
          <li v-for="pageNumber in totalPages" :class="{active: (pageNumber) == currentPage}" class="page-item">
            <a class="page-link" href="#" @click.prevent="setPage(pageNumber)">{{ pageNumber }} <span v-if="(pageNumber) == currentPage" class="sr-only">(current)</span></a>
          </li>
          <li :class="{disabled: (currentPage == totalPages)}" class="page-item">
            <a class="page-link" @click.prevent="setPage(currentPage+1)" href="#">Next</a>
          </li>
        </ul>
      </div>
      <div class="col-xs-12 col-sm-3 col-md-2">
        <select class="form-control" v-model="itemsPerPage" @change="changeItemsPerPage">
          <option v-for="option in itemsPerPageOptions" v-bind:value="option.value">
            {{ option.text }}
          </option>
        </select>
      </div>
    </div>
  </div>
</template>
<style>
</style>
<script>
    export default {
      created() {},
      mounted() {},
      components: {},
      props:{
        items: {
          type: Array,
          required: true
        },
      },
      data: function() {
        return {
          currentPage: 1,
          itemsPerPage: 10,
          itemsPerPageOptions: [
            { text: 1, value: 1 },
            { text: 5, value: 5 },
            { text: 10, value: 10 },
            { text: 25, value: 25 },
            { text: 40, value: 40 },
            { text: 50, value: 50 },
            { text: 75, value: 75 },
            { text: 100, value: 100 },
            { text: 200, value: 200 },
          ],
        }
      },
      computed: {
        totalPages: function() {
          return Math.ceil(this.items.length / this.itemsPerPage)
        },
        paginatedItems: function(){
          const startRecord = (this.currentPage -1) * this.itemsPerPage

          let itemArray = []
          for(let i = 0; i < this.itemsPerPage; i++){
            if(typeof this.items[startRecord + i] != 'undefined') {
              itemArray.push(this.items[startRecord + i])
            }
          }
          return itemArray
        },
      },
      methods: {
        // emits the event to the parent with the list of paginated items
        changeItemsPerPage: function(){
          this.$emit('itemsPerPageChanged', this.paginatedItems)
        },
        setPage: function(pageNumber) {
          if(pageNumber > 0 && pageNumber <= this.totalPages){
            this.currentPage = pageNumber
          }
        },
      },
      filters: {
      },
      watch: {
        // Any time the paginatedItems computed property changes, run the function to emit those paginated items
        paginatedItems: function(){
          this.changeItemsPerPage()
        }
      },
    }
</script>
