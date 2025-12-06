<template>
  <div>
    <heading>
      <span>Manage Keywords</span>
    </heading>
    <div v-if="apiError.status" class="alert alert-danger fade show" role="alert">
      {{ apiError.message }}
    </div>
    <div>
      <p>
        Create and manage keywords that can be assigned to programs. Keywords can be assigned to programs from the individual program forms.
      </p>
    </div>
    <div class="card">
      <div class="card-body">
        <h5 class="card-title">Create New Keyword</h5>
        <div class="form-group">
          <label for="newKeyword">Keyword Name</label>
          <input
            type="text"
            class="form-control"
            id="newKeyword"
            v-model="newKeywordName"
            placeholder="Enter keyword name"
            @keyup.enter="createKeyword"
          />
        </div>
        <button
          type="button"
          class="btn btn-primary"
          @click="createKeyword"
          :disabled="!newKeywordName || isCreating"
        >
          <span v-if="isCreating">Creating...</span>
          <span v-else>Create Keyword</span>
        </button>
      </div>
    </div>
    <div class="card mt-4">
      <div class="card-body">
        <h5 class="card-title">Existing Keywords</h5>
        <div v-if="loadingKeywords">
          <p style="text-align: center"><img src="/images/loading.gif" alt="Loading..."/></p>
        </div>
        <div v-else-if="keywords.length === 0" class="alert alert-info">
          No keywords found. Create your first keyword above.
        </div>
        <div v-else class="table-responsive">
          <table class="table table-hover table-sm">
            <thead>
              <tr>
                <th scope="col">Keyword</th>
                <th scope="col">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="keyword in keywords" :key="keyword.id">
                <td>{{ keyword.keyword }}</td>
                <td>
                  <button
                    v-if="userCanDelete"
                    type="button"
                    class="btn btn-danger btn-sm"
                    @click="deleteKeyword(keyword.id)"
                    :disabled="isDeleting === keyword.id"
                  >
                    <span v-if="isDeleting === keyword.id">Deleting...</span>
                    <span v-else><i class="fa fa-trash"></i> Delete</span>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<style></style>

<script>
import Heading from "../utils/Heading.vue";

export default {
  created() {
    this.fetchKeywords();
  },
  components: {
    Heading
  },
  props: {
    permissions: {
      type: Array,
      required: true
    }
  },
  data: function() {
    return {
      apiError: {
        message: null,
        status: null
      },
      loadingKeywords: true,
      keywords: [],
      newKeywordName: "",
      isCreating: false,
      isDeleting: null
    };
  },
  computed: {
    headingIcon: function() {
      return "<i class='fa fa-tags'></i>";
    },
    userCanDelete: function() {
      return this.permissions[0].delete ? true : false;
    },
    userCanCreate: function() {
      return this.permissions[0].create ? true : false;
    }
  },
  methods: {
    fetchKeywords: function() {
      let self = this;

      axios.get("/api/programs/keywords")
        .then(function(response) {
          self.keywords = response.data;
          self.loadingKeywords = false;
        })
        .catch(function(error) {
          self.apiError.status = error.response ? error.response.status : 500;
          switch (self.apiError.status) {
            case 403:
              self.apiError.message = "You do not have sufficient privileges to retrieve keywords.";
              break;
            case 404:
              self.apiError.message = "Keywords were not found.";
              break;
            case 500:
              self.apiError.message = "An internal error occurred.";
              break;
            default:
              self.apiError.message = "An error occurred.";
              break;
          }
          self.loadingKeywords = false;
        });
    },
    createKeyword: function() {
      if (!this.newKeywordName || this.isCreating) {
        return;
      }

      let self = this;
      this.isCreating = true;
      this.apiError.status = null;

      axios.post("/api/programs/keywords", {
        keyword: this.newKeywordName
      })
        .then(function(response) {
          self.keywords.push(response.data);
          self.newKeywordName = "";
          self.isCreating = false;
        })
        .catch(function(error) {
          self.apiError.status = error.response ? error.response.status : 500;
          switch (self.apiError.status) {
            case 403:
              self.apiError.message = "You do not have sufficient privileges to create keywords.";
              break;
            case 422:
              self.apiError.message = error.response.data || "Invalid keyword name.";
              break;
            case 500:
              self.apiError.message = "An internal error occurred.";
              break;
            default:
              self.apiError.message = "An error occurred.";
              break;
          }
          self.isCreating = false;
        });
    },
    deleteKeyword: function(id) {
      if (!confirm("Are you sure you want to delete this keyword? It will be removed from all programs.")) {
        return;
      }

      let self = this;
      this.isDeleting = id;
      this.apiError.status = null;

      axios.delete(`/api/programs/keywords/${id}`)
        .then(function() {
          self.keywords = self.keywords.filter(k => k.id !== id);
          self.isDeleting = null;
        })
        .catch(function(error) {
          self.apiError.status = error.response ? error.response.status : 500;
          switch (self.apiError.status) {
            case 403:
              self.apiError.message = "You do not have sufficient privileges to delete keywords.";
              break;
            case 404:
              self.apiError.message = "Keyword not found.";
              break;
            case 500:
              self.apiError.message = "An internal error occurred.";
              break;
            default:
              self.apiError.message = "An error occurred.";
              break;
          }
          self.isDeleting = null;
        });
    }
  }
};
</script>


