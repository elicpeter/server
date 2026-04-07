<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { IAppstoreApp, IAppstoreExApp } from '../../apps.d.ts'

import { t } from '@nextcloud/l10n'
import { useElementSize } from '@vueuse/core'
import { computed, useTemplateRef } from 'vue'
import AppTableRow from './AppTableRow.vue'

defineProps<{
	apps: (IAppstoreApp | IAppstoreExApp)[]
}>()

const tableElement = useTemplateRef('table')
const { width: tableWidth } = useElementSize(tableElement)

const isNarrow = computed(() => tableWidth.value < 768)
</script>

<template>
	<table ref="table" :class="$style.appTable">
		<colgroup>
			<col :style="{ width: isNarrow ? '60%' : '45%' }">
			<col>
			<col v-if="!isNarrow" style="width: 15%">
			<col :style="{ width: isNarrow ? 'calc(3 * var(--default-grid-baseline) + 2 * var(--default-clickable-area))' : '25%' }">
		</colgroup>
		<thead hidden>
			<tr>
				<th>{{ t('appstore', 'App name') }}</th>
				<th>{{ t('appstore', 'Version') }}</th>
				<th v-if="!isNarrow">
					{{ t('appstore', 'Support level') }}
				</th>
				<th>{{ t('appstore', 'Actions') }}</th>
			</tr>
		</thead>
		<tbody>
			<AppTableRow
				v-for="app in apps"
				:key="app.id"
				:app
				:isNarrow />
		</tbody>
	</table>
</template>

<style module>
.appTable {
	table-layout: fixed;
	width: 100%;
}
</style>
