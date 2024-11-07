<template>
  <div>
    <div class="row">
      <div class="col-xs-12 col-sm-9 col-md-10">
        <ul class="pagination">
          <li :class="{disabled: (currentPage <= 1)}" class="page-item">
            <a href="#" @click.prevent="setPage(currentPage-1)" class="page-link" tabindex="-1">Previous</a>
          </li>
          <template v-if="totalPages > 9">
            <li v-if="startRange > 1" class="page-item">
              <a class="page-link" @click.prevent="previousBatch()" href="#">...</a>
            </li>
            <li v-for="pageNumber in currentRange" :class="{active: (pageNumber) == currentPage}"
                class="page-item">
              <a class="page-link" href="#" @click.prevent="setPage(pageNumber)">{{ pageNumber }} <span
                  v-if="(pageNumber) == currentPage" class="sr-only">(current)</span></a>
            </li>
            <li v-if="endRange < totalPages" class="page-item">
              <a class="page-link" href="#" @click.prevent="nextBatch()">...</a>
            </li>
            <li :class="{active: (totalPages) == currentPage}"
                class="page-item">
              <a class="page-link" href="#" @click.prevent="setPage(totalPages)">{{ totalPages }} <span
                  v-if="(totalPages) == currentPage" class="sr-only">(current)</span></a>
            </li>
          </template>
          <template v-else>
            <li v-for="pageNumber in totalPages" :class="{active: (pageNumber) == currentPage}" class="page-item">
              <a class="page-link" href="#" @click.prevent="setPage(pageNumber)">{{ pageNumber }} <span
                  v-if="(pageNumber) == currentPage" class="sr-only">(current)</span></a>
            </li>
          </template>
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
ul.pagination {
  display: flex;
  flex-wrap: wrap;
}
</style>
<script>
export default {
  created () {
    if (this.extCurrPg) {
      this.currentPage = this.extCurrPg
    }
    if (this.extItemsPerPg) {
      this.itemsPerPage = this.extItemsPerPg
    }
  },
  components: {},
  props: {
    items: {
      type: Array,
      required: true
    },
    extCurrPg: {
      type: Number,
      required: true
    },
    extItemsPerPg: {
      type: Number,
      required: true
    },
    totalRecs: {
      type: Number,
      required: true
    }
  },
  data: function () {
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
      startRange: 1,
      endRange: 7,
      currentRange: [1, 2, 3, 4, 5, 6, 7],
    }
  },
  computed: {
    totalPages: function () {
      return Math.ceil(this.totalRecs / this.itemsPerPage)
    },
    paginatedItems: function () {
      const startRecord = (this.currentPage - 1) * this.itemsPerPage

      let itemArray = []
      for (let i = 0; i < this.itemsPerPage; i++) {
        if (typeof this.items[startRecord + i] != 'undefined') {
          itemArray.push(this.items[startRecord + i])
        }
      }
      return itemArray
    }
  },
  methods: {
    // emits the event to the parent with the list of paginated items
    changeItemsPerPage: function () {
      this.$emit('itemsPerPageChanged', this.paginatedItems)
    },
    // When the 'next' '...' paginator button is clicked
    nextBatch: function () {
      this.startRange = this.startRange + 7
      // end range can't go higher than total pages
      if (this.endRange + 7 > this.totalPages) {
        this.endRange = this.totalPages
      }
      else {
        this.endRange = this.endRange + 7
      }
      this.resetRange()
    },
    // When the 'previous' '...' paginator button is clicked
    previousBatch: function () {
      // start range can't go lower than 1
      if (this.startRange - 7 < 1) {
        this.startRange = 1
      }
      else {
        this.startRange = this.startRange - 7
      }
      this.endRange = this.startRange + 7
      this.resetRange(true)
    },
    setPage: function (pageNumber) {
      if (pageNumber > 0 && pageNumber <= this.totalPages) {
        this.currentPage = pageNumber
      }

      // If the last page is selected...set the paginator all the way to the end
      if (pageNumber === this.totalPages) {
        this.startRange = this.totalPages - 7
        this.endRange = this.totalPages
        this.resetRange()
      }
    },
    // Reset the pagination range
    resetRange: function (isBackwards) {
      this.currentRange = []
      if (isBackwards) {
        for (var i = this.startRange; i <= this.endRange; i++) {
          if (i >= 1) {
            this.currentRange.push(i)
          }
        }
      }
      else {
        for (var i = this.startRange; i <= this.endRange; i++) {
          if (i < this.totalPages) {
            this.currentRange.push(i)
          }
        }
      }
    },
  },
  watch: {
    // Any time the paginatedItems computed property changes, run the function to emit those paginated items
    paginatedItems: function () {
      this.changeItemsPerPage()
    }
  },
}
</script>
