<template>
  <row>
    <column :size="size" :offset="offset" v-if="loading">
      <p>Restoring Module...</p>
      <indeterminate-progress-bar />
    </column>
    <column :size="size" :offset="offset" v-else>
      <row>
        <column>
          <draggable v-model="ent"
                     :move="onEntityDragging"
                     @change="onDragged($event)" v-if="draggable">
              <template v-for="entity in ent">
                <slot v-bind="entity"></slot>
              </template>
          </draggable>
          <div v-else>
            <template v-for="entity in ent">
              <slot v-bind="entity"></slot>
            </template>
          </div>
        </column>
        <column :push5="entities && entities.length > 0" :centered="isEmptyContainer" v-if="!disableAdd">
          <simple-button color-class="primary" @click="$emit('add')">
            <i class="fa fa-plus"/>
            <span v-if="isEmptyContainer && !noAddLabel" v-html="addButtonLabel" />
          </simple-button>
        </column>
      </row>
    </column>
  </row>
</template>

<script>
import Draggable from 'vuedraggable';

import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import IndeterminateProgressBar from '@/components/Progress/IndeterminateProgressBar';
import SimpleButton from '@/components/Forms/Buttons/SimpleButton';

export default {
  name: 'ModuleEntityContainer',
  props: {
    entities: Array,
    loading: Boolean,
    draggable: Boolean,
    disableAdd: Boolean,
    checkMoveCallback: Function,
    noAddLabel: Boolean,
    addButtonLabel: {
      type: String,
      default: 'Add',
    },
    size: {
      type: [String, Number],
      default: 12,
    },
    offset: {
      type: [String, Number],
      default: 0,
    },
  },
  components: {
    SimpleButton,
    Draggable,
    IndeterminateProgressBar,
    Column,
    Row,
  },
  data() {
    return {
      ent: this.entities || [],
    };
  },
  computed: {
    isEmptyContainer() {
      return !this.entities || !this.entities.length;
    },
  },
  watch: {
    entities: {
      handler(v) {
        this.ent = v || [];
      },
    },
  },
  methods: {
    onDragged(event) {
      const keys = Object.keys(event);

      if (!keys.length || keys[0] !== 'moved') {
        return;
      }

      const { element } = event.moved;
      const { newIndex } = event.moved;

      if (!element || newIndex < 0) {
        return;
      }

      const entityIndex = this.ent.findIndex((e) => e.id === element.id);

      if (entityIndex < 0) {
        return;
      }

      this.ent[entityIndex].index = entityIndex;
      this.$emit('drag-end', this.ent);
    },

    onEntityDragging(e) {
      return this.draggable && (this.checkMoveCallback ? this.checkMoveCallback(e) : true);
    },
  },
};
</script>

<style scoped>

</style>
