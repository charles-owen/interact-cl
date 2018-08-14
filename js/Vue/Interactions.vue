<template>
  <div class="cl-interactions">
    <interact-item v-for="item in interactions" :item="item" :key="item.id"></interact-item>
  </div>
</template>

<script>
  import InteractItemVue from './InteractItem.vue';

  export default {
      props: ['interactions'],
      components: {
          interactItem: InteractItemVue
      },
      mounted() {
          console.log(this.interactions);
      }
  }
</script>