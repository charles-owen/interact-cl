<template>
  <div class="cl-interact">
    <h2 class="banner"><button class="start" @click.prevent="click"><img :src="root + '/vendor/cl/interact/img/logo16.png'" width="16" height="16"> Interact!</button>
      <span class="info">64 questions, 1 announcement</span>
      <span class="message"></span></h2>
  </div>
</template>

<script>
  export default {
      data: function() {
          return {
              root: Site.root
          }
      },
      methods: {
          click() {
              this.$emit('open');
          }
      }
  }
</script>