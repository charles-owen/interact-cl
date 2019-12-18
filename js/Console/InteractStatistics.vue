<template>
    <div class="content">
        <div class="full">
            <p class="center small">There were {{interactions}} interactions and {{discussions}} discussions</p>
        </div>
        <table class="small">
            <tr>
                <th @click.prevent="setSortBy(SortKey.User)" style="cursor:pointer"><img v-if="sortKey===SortKey.User" :src="root + '/vendor/cl/site/img/check16.png'">
                    User ID</th>
                <th @click.prevent="setSortBy(SortKey.Name)" style="cursor:pointer"><img v-if="sortKey===SortKey.Name" :src="root + '/vendor/cl/site/img/check16.png'">
                    Name</th>
                <th @click.prevent="setSortBy(SortKey.Email)" style="cursor:pointer"><img v-if="sortKey===SortKey.Email" :src="root + '/vendor/cl/site/img/check16.png'">
                    Email</th>
                <th @click.prevent="setSortBy(SortKey.Role)" style="cursor:pointer"><img v-if="sortKey===SortKey.Role" :src="root + '/vendor/cl/site/img/check16.png'">
                    Role</th>
                <th @click.prevent="setSortBy(SortKey.Interactions)" style="cursor:pointer"><img v-if="sortKey===SortKey.Interactions" :src="root + '/vendor/cl/site/img/check16.png'">
                    Interactions</th>
                <th @click.prevent="setSortBy(SortKey.Discussions)" style="cursor:pointer"><img v-if="sortKey===SortKey.Discussions" :src="root + '/vendor/cl/site/img/check16.png'">
                    Discussions</th>
            </tr>
            <tr v-for="statistic in statistics" >
                <td>{{statistic.user}}</td>
                <td>{{statistic.name}}</td>
                <td>{{statistic.email}}</td>
                <td>{{roleName(statistic.role)}}</td>
                <td>{{statistic.interactions}}</td>
                <td>{{statistic.discussions}}</td>
            </tr>
        </table>

    </div>
</template>

<script>
    const ConsoleComponentBase = Site.ConsoleComponentBase;

    const SortKey = {
        User: 1,
        Name: 2,
        Email: 3,
        Interactions: 4,
        Discussions: 5,
        Role: 6
    }

    /**
     * The Interact statistics page
     * /cl/console/interact/statistics
     * @constructor InteractStatisticsVue
     */
    export default {
        extends: ConsoleComponentBase,
        data: function() {
            return {
                statistics: [],
                sortKey: SortKey.Interactions,
                SortKey: SortKey,
                interactions: 0,
                discussions: 0
            }
        },
        mounted() {
            this.$parent.setTitle(': Interact Statistics');

            this.$site.api.get('/api/interact/statistics', {})
                .then((response) => {
                    if (response.hasError()) {
                        this.$site.toast(this, response);
                        return;
                    }

                    const data = response.getData('interact_statistics');
                    this.statistics = data.attributes;
                    for(const statistics of this.statistics) {
                        this.interactions += +statistics.interactions;
                        this.discussions += +statistics.discussions;
                    }

                    this.sort();
                })
                .catch((error) => {
                    this.$site.toast(this, error);
                });
        },
        methods: {
            sort() {
                let sorter = (a, b) => {
                    return b.interactions - a.interactions;
                };

                function compare(a, b) {
                    if (a > b) {
                        return -1;
                    }
                    if (b > a) {
                        return 1;
                    }
                    return 0;
                }

                switch(this.sortKey) {
                    case SortKey.User:
                        sorter = (a, b) => {
                            return compare(b.user, a.user);
                        };
                        break;

                    case SortKey.Name:
                        sorter = (a, b) => {
                            return compare(b.name, a.name);
                        };
                        break;

                    case SortKey.Email:
                        sorter = (a, b) => {
                            return compare(b.email, a.email);
                        };
                        break;

                    case SortKey.Interactions:
                        sorter = (a, b) => {
                            const ret = b.interactions - a.interactions;
                            if(ret !== 0) {
                                return ret;
                            }

                            return b.discussions - a.discussions;
                        };
                        break;

                    case SortKey.Discussions:
                        sorter = (a, b) => {
                            const ret = b.discussions - a.discussions;
                            if(ret !== 0) {
                                return ret;
                            }

                            return b.interactions - a.interactions;
                        };
                        break;

                    case SortKey.Role:
                        sorter = (a, b) => {
                            return this.rolePriority(b.role) - this.rolePriority(a.role);
                        };
                        break;
                }

                this.statistics.sort(sorter);
            },
            setSortBy(sorter) {
                this.sortKey = sorter;
                this.sort();
            },
            roleName(role) {
                return this.user.roleToName(role, true);
            },
            rolePriority(role) {
                const roles = this.user.getRoles();
                if(roles.hasOwnProperty(role)) {
                    return roles[role].priority;
                }

                return 0;
            }
        }
    }
</script>