<template>
  <div class="cl-interact-item" :id="'q' + item.id"><h3><a>{{item.summary}}</a></h3>
    <p class="link">@{{item.id}} {{item.discussions}}<span>9/16/2017</span></p>
    <p>{{item.summarized}}</p>
    <p class="tag"><a href="/cbowen/cse335/lib/step/stepsection.php?step=step1&amp;section=errors"
                      target="INTERACT_STEP">{{item.attribution}}</a></p></div>
</template>

<script>

  export default {
      props: ['item']
  }
</script>