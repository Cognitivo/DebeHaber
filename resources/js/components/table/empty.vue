<template>
  <div style="background-color: white">
    <b-img fluid center src="/img/no-data.gif"/>
    <h4>No Transactions</h4>
  </div>
</template>
<script>
export default {
  props: ["isCreated"],
  data: () => ({}),
  computed: {
    formURL: function() {
      return this.$route.name.replace("List", "Form");
    }
  }
};
</script>
