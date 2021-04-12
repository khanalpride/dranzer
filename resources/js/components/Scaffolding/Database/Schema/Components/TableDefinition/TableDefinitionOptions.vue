<template>
  <div>
    <row>
      <column offset="1">
        <pg-check-box no-margin
                      label="Lowercase"
                      :value="options.forceLowercase"
                      @change="$emit('option-toggled', {name: 'forceLowercase', checked: $event})" />
        <pg-check-box no-margin
                      label="Automatically Create Foreign Keys"
                      :value="options.automaticForeignKeys"
                      @change="$emit('option-toggled', {name: 'automaticForeignKeys', checked: $event})"
                      v-if="blueprintCount > 0" />
        <separator />
      </column>
    </row>
    <row>
      <column push5 size="10" offset="1">
        <pg-check-box
          no-margin
          label="id"
          color-class="primary"
          :disabled="isUserTable"
          :value="hasIdColumn"
          @change="onIdColumnToggled"
        />
        <pg-check-box
          no-margin
          label="uuid"
          color-class="primary"
          :value="hasUUIDColumn"
          @change="onUUIDColumnToggled"
        />
        <pg-check-box
          no-margin
          :disabled="!canAddUserIdColumn"
          label="user_id"
          color-class="primary"
          :value="hasUserIdColumn"
          @change="onUserIdColumnToggled"
        />
        <pg-check-box
          no-margin
          label="created_at"
          color-class="complete"
          class="m-l-55"
          :value="hasTimestampColumn('created_at')"
          :disabled="isUserTable"
          @change="onTimestampColumnToggled('created_at', $event)"
        />
        <pg-check-box
          no-margin
          label="updated_at"
          color-class="complete"
          :value="hasTimestampColumn('updated_at')"
          :disabled="isUserTable"
          @change="onTimestampColumnToggled('updated_at', $event)"
        />
        <pg-check-box
          no-margin
          label="deleted_at"
          color-class="danger"
          :disabled="options.softDelete"
          :value="hasTimestampColumn('deleted_at')"
          @change="onTimestampColumnToggled('deleted_at', $event)"
        />
      </column>
    </row>
  </div>
</template>

<script>
import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import PgCheckBox from '@/components/Forms/Checkbox/PgCheckBox';
import Separator from '@/components/Layout/Separator';

export default {
  name: 'TableDefinitionOptions',
  props: {
    columns: Array,
    canAddUserIdColumn: Boolean,
    isUserTable: Boolean,
    blueprintCount: Number,
    options: {},
  },
  components: {
    Separator, PgCheckBox, Column, Row,
  },
  computed: {
    hasIdColumn() {
      return this.columns.find((column) => (column.name || '').toLowerCase() === 'id');
    },
    hasUUIDColumn() {
      return this.columns.find((column) => (column.name || '').toLowerCase() === 'uuid');
    },

    // TODO: Also check for user_id foreign key.
    hasUserIdColumn() {
      const userIdColumn = this.columns.find(
        (column) => (column.name || '').toLowerCase() === 'user_id'
          && column.type === 'unsignedBigInteger'
          && column.attributes.us,
      );

      return userIdColumn !== undefined;
    },
  },
  methods: {
    hasTimestampColumn(columnName) {
      return this.columns.find((column) => column.name === columnName && column.type === 'timestamp');
    },

    broadcastColumnToggled(columnName, checked) {
      this.$emit('column-toggled', { columnName, checked });
    },

    onIdColumnToggled(checked) {
      this.broadcastColumnToggled('id', checked);
    },

    onUUIDColumnToggled(checked) {
      this.broadcastColumnToggled('uuid', checked);
    },

    onUserIdColumnToggled(checked) {
      this.broadcastColumnToggled('user_id', checked);
    },

    onTimestampColumnToggled(columnName, checked) {
      this.broadcastColumnToggled(columnName, checked);
    },
  },
};
</script>

<style scoped>

</style>
