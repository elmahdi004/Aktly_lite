<template>
  <Popover v-model:open="open">
    <PopoverTrigger as-child>
      <Button
        variant="outline"
        :class="cn(
          'w-full justify-start text-left font-normal',
          !modelValue && 'text-muted-foreground',
        )"
      >
        <CalendarIcon class="mr-2 h-4 w-4" />
        {{ modelValue
          ? df.format(modelValue.toDate(getLocalTimeZone())) + ' ' +
            String(modelValue.hour ?? 0).padStart(2, '0') + ':' +
            String(modelValue.minute ?? 0).padStart(2, '0')
          : t('step3.dateTimePickerPlaceholder')
        }}
      </Button>
    </PopoverTrigger>

    <PopoverContent class="w-auto p-0 space-y-2">
      <Calendar
        v-model="selectedDate"
        initial-focus
        @update:modelValue="updateDate"
      />
      <div class="flex items-center justify-between px-3 pb-3">
        <label class="text-sm text-gray-600">{{ t('step3.timeLabel') }}</label>
        <input
        type="time"
        v-model="selectedTime"
        class="border rounded px-2 py-1 text-sm"
        @input="updateDate()"
      />

      </div>
    </PopoverContent>
  </Popover>
</template>

<script setup lang="ts">
import { cn } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { Calendar } from '@/components/ui/calendar'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import {
  DateFormatter,
  type DateValue,
  getLocalTimeZone,
  CalendarDateTime
} from '@internationalized/date'
import { CalendarIcon } from 'lucide-vue-next'
import { ref, watch, defineProps, defineEmits } from 'vue'
import { useI18n } from 'vue-i18n'

const { locale, t } = useI18n()

const localeMap: Record<string, string> = {
  fr: 'fr-FR',
  nl: 'nl-NL',
}

const df = new DateFormatter(localeMap[locale.value] || 'fr-FR', {
  dateStyle: 'long',
})

const open = ref(false)

const props = defineProps({
  modelValue: Object, // v-model
})
const emit = defineEmits(['update:modelValue'])

const selectedDate = ref(props.modelValue)
const selectedTime = ref('12:00')

// Quand la date change, on émet la valeur complète
const updateDate = (newDate?: DateValue) => {
  const [hours, minutes] = selectedTime.value.split(':').map(Number)

  // si aucune date sélectionnée, on ne fait rien
  const date = newDate ?? selectedDate.value
  if (!date) return

  selectedDate.value = new CalendarDateTime(
    date.year,
    date.month,
    date.day,
    hours,
    minutes
  )

  emit('update:modelValue', selectedDate.value)

  if (newDate) {
    open.value = false
  }
}


// Si la date vient du parent, on met aussi l’heure affichée à jour
watch(
  () => props.modelValue,
  (newValue) => {
    if (newValue && newValue.hour !== undefined) {
      selectedTime.value = `${String(newValue.hour).padStart(2, '0')}:${String(
        newValue.minute
      ).padStart(2, '0')}`
    }
    selectedDate.value = newValue
  },
  { immediate: true }
)
</script>
