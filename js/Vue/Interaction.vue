<template>
  <div class="cl-interaction">
    <div class="interact-present" id="ip352">
      <div class="interact-content">
        <h3 class="interaction-heading">
          <div class="cl-menu"><a><img :src="root + '/vendor/cl/site/img/menubars.png'"></a>
            <ul>
              <li><a class="edit" href="javascript:;"><img :src="root + '/vendor/cl/site/img/pen16.png'"></a> <a
                class="edit" href="javascript:;">Edit</a></li>
              <li><a class="delete" href="javascript:;"><img :src="root + '/vendor/cl/site/img/x.png'"></a> <a
                class="delete" href="javascript:;">Delete</a></li>
              <li><a class="grading" href="/cbowen/cse335/lib/grading/assignmentgrader.php?tag=step1&amp;id=190"
                     target="INTERACT_GRADING"><img :src="root + '/vendor/cl/interact/img/grading.png'"></a> <a
                class="grading" href="/cbowen/cse335/lib/grading/assignmentgrader.php?tag=step1&amp;id=190"
                target="INTERACT_GRADING">Grading page</a></li>
              <li><a class="close" href="javascript:;"><img :src="root + '/vendor/cl/interact/img/close.png'"></a> <a
                class="close" href="javascript:;">Close discussion</a></li>
            </ul>
          </div>
          <span>Sat, 9-16-2017 5:21pm<br>
<button class="follow"><img :src="root + '/vendor/cl/interact/img/following.png'" alt="Following" height="16"
                            width="92"></button> @352</span>Error Help
        </h3>
        <p class="link">Private question by Hassan Sajid Tarar / <a
          href="/cbowen/cse335/lib/step/stepsection.php?step=step1&amp;section=errors" target="INTERACT_STEP">Step 1/Dealing with Errors</a>
        </p>
        <div class="content"><p>
          In CSE335 menu, I am not able to click the Error Help tab. Is something wrong with how I configured? I am working from my windows laptop.</p>
        </div>
      </div>
    </div>
    <h3 class="discussions-banner">DISCUSSION</h3>
    <div class="discussions">
      <div class="discuss" id="d885">
        <div class="interact-content">
          <h4 class="discuss-heading">
            <div class="cl-menu"><a href="javascript:;"><img :src="root + '/vendor/cl/site/img/menubars.png'"></a>
              <ul>
                <li><a class="edit" href="javascript:;"><img :src="root + '/vendor/cl/site/img/pen16.png'"></a> <a
                  class="edit" href="javascript:;">Edit</a></li>
                <li><a class="delete" href="javascript:;"><img :src="root + '/vendor/cl/site/img/x.png'"></a> <a
                  class="delete" href="javascript:;">Delete</a></li>
                <li><a class="endorse" href="javascript:;"><img
                  :src="root + '/vendor/cl/interact/img/check16.png'"></a> <a class="endorse" href="javascript:;">Endorse</a>
                </li>
              </ul>
            </div>
            <span class="time">Sat, 9-16-2017 5:45pm</span>Me
          </h4>
          <div class="content"><p>Did you connect? You have to select an error to get error help.</p>
          </div>
        </div>
      </div>
    </div>
    <div class="discuss-form">
      <div class="interact-content"><h4 class="discuss-heading">Contribute to the discussion</h4>
        <form id="discussion_contribute_form"><input type="hidden" name="cmd" value="post">
          <input type="hidden" name="interactid" value="352">
          <p><textarea name="discuss" id="discuss_contribute" style="visibility: hidden; display: none;"></textarea></p>
          <p><input type="submit" value="Post"></p></form>
      </div>
      <p class="inter-active"></p></div>
  </div>
</template>

<script>

  export default {
      data: function() {
          return {
              root: Site.root
          }
      }
  }

</script>


