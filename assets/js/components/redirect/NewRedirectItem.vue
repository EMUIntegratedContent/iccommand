<template>
  <div>
    <div v-if="this.permissions[0].user">
      <div
        :is="currentComponent"
        @redirectItemChosen="setItemType"
        @goBackStep1="setComponent(previousStep)"
        :itemType="itemType" :itemExists="false"
        :permissions="this.permissions"
        :newForm="true" startMode="edit"></div>
      <div v-show="!currentComponent" v-for="component in componentsArray">
        <button @click="setComponent(component)">{{ component }}</button>
      </div>
    </div>
    <div v-else class="alert alert-danger alert-dismissible fade show" role="alert">
      You do not have create privileges for redirect items.
    </div>
  </div>
</template>

<style></style>

<script>
import NewRedirectItemChoices from "./NewRedirectItemChoices.vue";
import RedirectItemForm from "./RedirectItemForm.vue";

export default {
  mounted() {
    console.log("Component mounted.");
  },

  components: {
    "new-redirect-item-choices": NewRedirectItemChoices,
    "redirect-item-form": RedirectItemForm,
  },

  props: {
    permissions: {
      type: Array,
      required: true
    }
  },

  data: function() {
    return {
      /**
       * An array of the two components of creating a new redirect.
       * @type {Array.<string>}
       */
      componentsArray: ["new-redirect-item-choices", "redirect-item-form"],

      /**
       * The component that the user is on currently.
       * @type {string}
       */
      currentComponent: "new-redirect-item-choices",

      /**
       * The item type of the redirect.
       * @type {string}
       */
      itemType: ""
    };
  },

  computed: {
    /**
     * Determines what the next step in the new item creation is if there is any.
     * @return {string} The next possible step.
     */
    nextStep: function() {
      switch (this.currentComponent) {
        case "new-redirect-item-choices":
        return "redirect-item-form";
        default:
        return null;
      }
    },

    /**
     * Determines what the previous step in the new item creation is if there is any.
     * @return {string} The previous possible step.
     */
    previousStep: function() {
      switch (this.currentComponent) {
        case "redirect-item-form":
        return "new-redirect-item-choices";
        default:
        return null;
      }
    }
  },

  methods: {
    /**
     * Changes the component to the specified component.
     * @param {string} component The component to replace the current component.
     */
    setComponent: function(component) {
      this.currentComponent = component;
    },

    /**
     * Changes the item type to the specified type and change the form appropriately.
     * @param {string} itemType The item type to replace the current type.
     */
    setItemType: function(itemType) {
      this.itemType = itemType; // Set the item type to pass to the form.
      this.setComponent("redirect-item-form"); // Change the form for the new redirect item.
    }
  },

  events: {}
};
</script>
