<template>
  <div class="cl-interact-console">
    <ul>
      <li><router-link to="console/interact/statistics">Statistics</router-link></li>
      <li v-if="answerer !== null"><a :href="root + '/cl/vinnie'">Vinnie Vue</a></li>
    </ul>
    <div class="cl-group">
    <p v-if="me !== null"><label>
      <input type="checkbox" v-model="me.email" @change="change(me, 'email')"> Receive email on new Interacts!</label></p>
      <p v-if="me !== null"><label>
        <input type="checkbox" v-model="me.escalate" @change="change(me, 'escalate')"> Receive email on escalations.</label></p>
    </div>
    <h3 v-if="tas.length > 0 || others.length > 0">Other Staff</h3>
    <div class="cl-group">
    <p v-for="user in tas" :key="user.user.id"><label>
      <input type="checkbox" v-model="user.email" @change="change(user, 'email')">
      <input type="checkbox" v-model="user.escalate" @change="change(user, 'escalate')">
      {{user.user.displayName()}}
    </label></p>
    </div>
    <div class="cl-group">
    <p v-for="user in others" :key="user.user.id"><label>
      <input type="checkbox" v-model="user.email" @change="change(user, 'email')">
      <input type="checkbox" v-model="user.escalate" @change="change(user, 'escalate')">
      {{user.user.displayName()}}</label></p>
    </div>

  </div>
</template>

<script>
  const ConsoleComponentBase = Site.ConsoleComponentBase;

  export default {
    'extends': ConsoleComponentBase,
    data: function () {
      return {
        me: null,
        tas: [],
        others: [],
        answerer: null
      }
    },
    mounted() {
      this.$site.api.get('/api/interact/cp', {})
              .then((response) => {
                if (!response.hasError()) {
                  this.newResponse(response);
                } else {
                  this.$site.toast(this, response);
                }

              })
              .catch((error) => {
                this.$site.toast(this, error);
              });
    },
    methods: {
      change(user, type) {
        if (user.user.member.id === this.user.member.id) {
          this.$site.api.post('/api/interact/email',
                  {email: user.email, escalate: user.escalate})
                  .then((response) => {
                    if (!response.hasError()) {
                      this.newResponse(response);
                    } else {
                      this.$site.toast(this, response);
                    }

                  })
                  .catch((error) => {
                    this.$site.toast(this, error);
                  });
        } else {
          this.$site.api.post('/api/interact/email/' + user.user.member.id,
                  {email: user.email, escalate: user.escalate})
                  .then((response) => {
                    if (!response.hasError()) {
                      this.newResponse(response);
                    } else {
                      this.$site.toast(this, response);
                    }

                  })
                  .catch((error) => {
                    this.$site.toast(this, error);
                  });
        }
      },
      newResponse(response) {
        const data = response.getData('interact-email').attributes;

        this.me = null;
        this.tas = [];
        this.others = [];
        for (let staff of data) {
          let staffUser = new Site.User(staff.user);
          staff.user = staffUser;

          if (staffUser.member.id === this.user.member.id) {
            this.me = staff;
          } else if (staffUser.atLeast(this.$site.Member.TA)) {
            this.tas.push(staff);
          } else {
            this.others.push(staff);
          }
        }

        const answerer = response.getData('interact-answerer');
        this.answerer = answerer !== null ? answerer.attributes : null;
      }
    }
  }

</script>