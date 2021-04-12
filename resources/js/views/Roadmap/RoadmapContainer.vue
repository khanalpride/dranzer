<template>
    <row>
        <column :offset="map.length ? 2 : 3" :size="map.length ? 8 : 6">
            <content-card :heading="roadmapHeading">
                <row>
                    <column offset="1" size="10">
                        <el-timeline v-if="map.length">
                            <el-timeline-item v-for="item in map"
                                              :key="item.title"
                                              :type="getItemTypeClass(item)"
                                              class="tailed"
                                              hide-timestamp>
                                <row>
                                    <column push5>
                                        <p class="text-info">
                                            <span class="bold" v-html="item.title"/>
                                            <span
                                                    v-if="item.inProgress" class="m-l-5">
                                                <code class="status-indicator text-primary padding-4">In Progress</code>
                                            </span>
                                            <span v-if="item.deployed" class="m-l-5">
                                                <code class="status-indicator text-green padding-4">Deployed</code>
                                            </span>
                                            <span v-if="!item.inProgress && !item.deployed" class="m-l-5">
                                                <code class="status-indicator text-danger padding-4">Planned</code>
                                            </span>
                                            <span v-if="item.eta" v-tooltip.right.10 class="m-l-5"
                                                  content="Planned deployment">
                                                <code class="status-indicator text-green padding-4">
                                                    {{ item.eta }}
                                                </code>
                                            </span>
                                        </p>
                                        <p v-if="item.desc" class="text-info hint-text m-l-20"
                                           style="padding-right: 170px;" v-html="item.desc"/>
                                    </column>
                                </row>
                            </el-timeline-item>
                        </el-timeline>
                        <p v-else class="text-center text-primary">
                            <i class="fa fa-info"/> There are no active items in the roadmap at this time.
                        </p>
                    </column>
                </row>
            </content-card>
        </column>
    </row>
</template>

<script>
import Row from '@/components/Layout/Grid/Row';
import ContentCard from '@/components/Cards/ContentCard';
import Column from '@/components/Layout/Grid/Column';
import mutations from '@/mixins/mutations';

export default {
  name: 'Roadmap',
  components: {
    Column,
    ContentCard,
    Row,
  },
  mixins: [mutations],
  data() {
    return {
      loading: false,

      map: [
        {
          title: 'Property Return Types',
          desc: 'The minimum PHP version was set to 7.3 when the development started but is 7.4'
            + ' now so add return type to all generated properties.',
          eta: 'September 2021',
        },
        {
          title: 'PHP Expression Builder',
          desc: 'Add the ability to build and expand any PHP expression anywhere.',
          eta: 'September 2021',
        },
        {
          title: 'Controller Statements',
          desc: 'Allow controllers to generate mailable, notifications, jobs and event statements.'
            + ' In addition, allow multiple statements of the same type to be added.',
          eta: 'September 2021',
        },
        {
          title: 'Controller Presets',
          desc: 'Add 50 controller presets.',
          eta: 'September 2021',
        },
        {
          title: '3rd Party Composer and NPM Packages',
          desc: 'Add the ability to install and integrate commonly used composer and npm packages.',
          eta: 'September 2021',
        },
        {
          title: 'Custom Layouts',
          desc: 'Add the ability to add multiple custom layouts and add enhancements to the partial extraction and compilation processes.',
          eta: 'September 2021',
        },
        {
          title: 'Orchid Admin Panel',
          desc: 'Add support for all field types and allow grouping fields into panels.',
          eta: 'September 2021',
        },
      ],
    };
  },
  computed: {
    planned() {
      return this.map.filter((item) => !item.inProgress && !item.deployed);
    },
    roadmapHeading() {
      return this.map.length ? `Roadmap (${this.planned.length} Planned Enhancements)` : 'Roadmap';
    },
  },
  methods: {
    getItemTypeClass(item) {
      if (item.deployed) {
        return 'success';
      }

      if (item.inProgress) {
        return 'primary';
      }

      return 'info';
    },
  },
};
</script>

<style scoped>
.padding-4 {
    padding: 4px 4px 4px 4px !important;
}

.status-indicator.text-primary:hover {
    background: lightskyblue !important;
    color: #2b2828 !important;
}

.status-indicator.text-green:hover {
    background: lightgreen !important;
    color: #2b2828 !important;
}

.status-indicator.text-danger:hover {
    background: #ff0000 !important;
    color: #ffffff !important;
}
</style>
