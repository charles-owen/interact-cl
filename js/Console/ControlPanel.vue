<template>
  <div class="cl-interact-console">
    <div class="group">
    <p v-if="me !== null"><label><input type="checkbox" v-model="me.email" @change="change(me)"> Receive email on new Interacts!</label></p>
    </div>
    <h3 v-if="tas.length > 0 || others.length > 0">Other Staff</h3>
    <div class="group">
    <p v-for="user in tas" :key="user.user.id"><label><input type="checkbox" v-model="user.email" @change="change(user)"> {{user.user.displayName()}}</label></p>
    </div>
    <div class="group">
    <p v-for="user in others" :key="user.user.id"><label><input type="checkbox" v-model="user.email" @change="change(user)"> {{user.user.displayName()}}</label></p>
    </div>

  </div>
</template>

<script>
  import {Member} from 'course-cl/js/Members/Member';

  export default {
      data: function() {
          return {
              me: null,
              tas: [],
              others: [],
              user: null
          }
      },
      mounted() {
          Site.api.get('/api/interact/email', {})
              .then((response) => {
                  if (!response.hasError()) {
                      this.newResponse(response);
                  } else {
                      Site.toast(this, response);
                  }

              })
              .catch((error) => {
                  Site.toast(this, error);
              });
      },
      methods: {
          change(user) {
		          if(user.user.member.id === this.user.member.id) {
			          Site.api.post('/api/interact/email', {email: user.email})
				          .then((response) => {
					          if (!response.hasError()) {
						          this.newResponse(response);
					          } else {
						          Site.toast(this, response);
					          }

				          })
				          .catch((error) => {
					          Site.toast(this, error);
				          });
		          } else {
			          Site.api.post('/api/interact/email/' + user.user.member.id, {email: user.email})
				          .then((response) => {
					          if (!response.hasError()) {
						          this.newResponse(response);
					          } else {
						          Site.toast(this, response);
					          }

				          })
				          .catch((error) => {
					          Site.toast(this, error);
				          });
		          }
          },
          newResponse(response) {
              const data = response.getData('interact-email').attributes;
              const user = this.$store.state.user.user;
              this.user = user;

              this.me = null;
              this.tas = [];
              this.others = [];
              for(let staff of data) {
                  let staffUser = new Users.User(staff.user);
                  staff.user = staffUser;

                  if(staffUser.member.id === user.member.id) {
                      this.me = staff;
                  } else if(staffUser.atLeast(Member.TA)) {
                      this.tas.push(staff);
                  } else {
                      this.others.push(staff);
                  }
              }
          }
      }
  }

</script>