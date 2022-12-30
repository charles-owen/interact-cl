<template>
  <div class="cl-interaction">
    <mask-vue :mask="mask">Communicating with server...</mask-vue>
    <div v-if="!editing">
      <cl-menu v-if="(staff || self)">
        <a><img :src="root + '/cl/img/menubars.png'"></a>
        <ul>
          <li v-if="!closed" @click.stop="editMe"><a @click.prevent.stop="editMe"><img :src="root + '/cl/img/pen16.png'"> Edit</a></li>
          <li @click.stop="deleteMe"><a @click.prevent.stop="deleteMe"><img :src="root + '/cl/img/x.png'"> Delete</a></li>
          <li v-if="data.gradingLink !== undefined && interaction.assign !== 'general'"><a :href="root + data.gradingLink + '/' + interaction.assign + '/' + interaction.memberid"
                 target="INTERACT_GRADING"><img :src="root + '/vendor/cl/interact/img/grading.png'"> Grading page</a></li>
          <li v-if="resolvable && !closed" @click.stop="resolved"><a  @click.prevent.stop="resolved"><img :src="root + '/cl/img/check16.png'"> Resolved</a></li>
          <li v-if="!resolvable && !closed" @click.stop="unresolved"><a  @click.prevent.stop="unresolved"><img :src="root + '/cl/img/x.png'"> Unresolved</a></li>
          <li v-if="!closed && escalatable" @click.stop="escalate"><a  @click.prevent.stop="escalate"><img :src="root + '/vendor/cl/interact/img/up.png'"> Escalate</a></li>
          <li v-if="!closed && staff" @click.stop="closeMe"><a  @click.prevent.stop="closeMe"><img :src="root + '/vendor/cl/interact/img/close.png'"> Close Interaction</a></li>
          <!-- <li><a><img :src="root + '/vendor/cl/interact/img/close.png'"> Close discussion</a></li> -->
        </ul>
      </cl-menu>
        <h3 class="cl-interaction-heading">
          <span><button v-if="staff && resolvable" class="cl-resolve" @click.prevent.stop="resolved">resolved</button> {{date}}<br>
  <button v-if="interaction.following !== NEVERFOLLOW" class="cl-follow" @click.prevent="follow()">
    <img v-if="interaction.following === FOLLOWING" :src="root + '/vendor/cl/interact/img/following.png'" alt="Following Interaction" height="16" width="92">
    <img v-if="interaction.following === NOTFOLLOWING" :src="root + '/vendor/cl/interact/img/follow.png'" alt="Follow Interaction" height="16" width="92">
  </button> @{{interaction.id}}</span>
          <img v-if="interaction.pin" :src="root + '/vendor/cl/interact/img/pin25.png'" alt="Pinned" width="17" height="25">
          <img v-if="interaction.type === announce" :src="root + '/vendor/cl/interact/img/megaphone25.png'" alt="Annoucement" width="29" height="26">
          {{interaction.summary}}
        </h3>
        <p class="cl-attribution">{{interactionLabel}} {{interaction.by}} / <span v-html="interaction.attribution"></span>
        </p>
        <div ref="message" v-html="displayMessage"></div>
        <div class="cl-history" v-for="history in interaction.history" :key="history.time">{{showHistory(history)}}</div>
    </div>
    <div v-else>
      <h3 class="cl-interaction-heading">Editing Interaction</h3>
      <interaction-form :data="data" :interaction="interaction" @submit="submit" @cancel="cancel"></interaction-form>
    </div>
  </div>
</template>

<script>
    import Dialog from 'dialog-cl';
    import {Interaction} from '../Models/Interaction';
    import InteractionFormVue from './InteractionForm.vue';

    const Member = Site.Member;

    export default {
        props: ['interaction', 'data'],
      emits: ['select', 'deleted', 'reloaded'],
        data: function () {
            return {
                root: Site.root,
                interactionLabel: '',
                announce: Interaction.ANNOUNCEMENT,
                date: '',
                user: this.$store.state.user.user,
                staff: false,
                self: false,
                editing: false,
                mask: false,
                displayMessage: '',
                closed: false,

                FOLLOWING: Interaction.FOLLOWING,
                NOTFOLLOWING: Interaction.NOTFOLLOWING,
                NEVERFOLLOW: Interaction.NEVERFOLLOW
            }
        },
        computed: {
            resolvable() {
                return this.interaction.state === Interaction.PENDING || this.interaction.state === Interaction.ANSWERED;
            },
            escalatable() {
                return this.staff && this.interaction.type === Interaction.QUESTION && this.interaction.escalated === undefined;
            }
        },
        watch: {
            interaction() {
                this.editing = false;
                this.take();
            }
        },
        components: {
            clMenu: Site.MenuVue,
            interactionForm: InteractionFormVue,
            maskVue: Site.MaskVue
        },
        mounted() {
            this.staff = this.user.atLeast(Member.STAFF);
            this.take();
        },
        methods: {
            take() {
                this.self = this.interaction.by === 'Me';
                this.closed = this.interaction.state === Interaction.CLOSED;

                if (this.interaction.private) {
                    if (this.interaction.type === Interaction.ANNOUNCEMENT) {
                        this.interactionLabel = 'Private announcement by';
                    } else {
                        this.interactionLabel = 'Private question by';
                    }
                } else {
                    if (this.interaction.type === Interaction.ANNOUNCEMENT) {
                        this.interactionLabel = 'Announcement by';
                    } else {
                        this.interactionLabel = 'Question by';
                    }
                }

                this.date = this.$site.TimeFormatter.relativeUNIX(this.interaction.created, null, 'ddd, M-DD-YYYY h:mm:ssa');

                //
                // Find the @ links and wrap them in an a tag
                //
                const re = /@([0-9]+)/g;
                this.displayMessage = this.interaction.message.replace(re, '<a class="cl-interact-link" data-value="$1">$&</a>')

                this.$nextTick(function () {
                    const elements = this.$refs.message.querySelectorAll('a.cl-interact-link');
                    for (let element of elements) {
                        element.onclick = (event) => {
                            this.$emit('select', event.target.dataset.value);
                        }
                    }

                });
            },
            deleteMe() {
                new Dialog.MessageBox('Are you sure?', 'Are you sure you want to delete this interaction?',
                    Dialog.MessageBox.OKCANCEL, () => {
                        this.$site.api.post('/api/interact/interaction/' + this.interaction.id + '/delete', {})
                            .then((response) => {
                                if (!response.hasError()) {
                                    this.$emit('deleted', this.interaction);
                                } else {
                                    this.$site.toast(this, response);
                                }

                            })
                            .catch((error) => {
                                this.$site.toast(this, error);
                            });
                    });

            },
            closeMe() {
                new Dialog.MessageBox('Are you sure?', 'Are you sure you want to close this interaction?',
                    Dialog.MessageBox.OKCANCEL, () => {
                        this.$site.api.post('/api/interact/interaction/' + this.interaction.id + '/close', {})
                            .then((response) => {
                                if (!response.hasError()) {
                                    this.editing = false;
                                    const interaction = new Interaction(response.getData('interaction').attributes);
                                    this.$emit('reloaded', interaction);
                                } else {
                                    this.$site.toast(this, response);
                                }

                            })
                            .catch((error) => {
                                this.$site.toast(this, error);
                            });
                    });
            },
            editMe() {
                this.editing = true;
            },
            cancel() {
                this.editing = false;
            },
            resolved() {
                this.$site.api.post('/api/interact/interaction/' + this.interaction.id + '/resolved', {})
                    .then((response) => {
                        if (!response.hasError()) {
                            this.editing = false;
                            const interaction = new Interaction(response.getData('interaction').attributes);
                            this.$emit('reloaded', interaction);
                        } else {
                            this.$site.toast(this, response);
                        }

                    })
                    .catch((error) => {
                        this.$site.toast(this, error);
                    });
            },
            unresolved() {
                this.$site.api.post('/api/interact/interaction/' + this.interaction.id + '/unresolved', {})
                    .then((response) => {
                        if (!response.hasError()) {
                            this.editing = false;
                            const interaction = new Interaction(response.getData('interaction').attributes);
                            this.$emit('reloaded', interaction);
                        } else {
                            this.$site.toast(this, response);
                        }

                    })
                    .catch((error) => {
                        this.$site.toast(this, error);
                    });
            },
            escalate() {
                this.$site.api.post('/api/interact/interaction/' + this.interaction.id + '/escalate', {})
                    .then((response) => {
                        if (!response.hasError()) {
                            this.editing = false;
                            const interaction = new Interaction(response.getData('interaction').attributes);
                            this.$emit('reloaded', interaction);
                        } else {
                            this.$site.toast(this, response);
                        }

                    })
                    .catch((error) => {
                        this.$site.toast(this, error);
                    });
            },
            submit(data) {
                this.mask = true;

                this.$site.api.post('/api/interact/interaction/' + this.interaction.id + '/edit', data)
                    .then((response) => {
                        this.mask = false;

                        if (!response.hasError()) {
                            this.editing = false;
                            const interaction = new Interaction(response.getData('interaction').attributes);
                            this.$emit('reloaded', interaction);
                        } else {
                            this.$site.toast(this, response);
                        }

                    })
                    .catch((error) => {
                        this.mask = false;
                        this.$site.toast(this, error);
                    });
            },
            showHistory(history) {
                let msg = null;
                switch (history.op) {
                    case 'edit':
                        msg = 'Edited';
                        break;

                    case 'closed':
                        msg = 'Closed';
                        break;

                    case 'deleted':
                        msg = 'Deleted';
                        break;
                }

                if (msg !== null) {
                    const time = this.$site.TimeFormatter.relativeUNIX(history.time, null, 'ddd, M-DD-YYYY h:mm:ssa');
                    return `${msg} ${time} by ${history.by}`;
                }
            },
            follow() {
                this.$site.api.post('/api/interact/interaction/' + this.interaction.id + '/follow', {})
                    .then((response) => {
                        if (!response.hasError()) {
                            const interaction = new Interaction(response.getData('interaction').attributes);
                            this.$emit('reloaded', interaction);
                        } else {
                            this.$site.toast(this, response);
                        }

                    })
                    .catch((error) => {
                        this.$site.toast(this, error);
                    });

            }
        }
    }
</script>