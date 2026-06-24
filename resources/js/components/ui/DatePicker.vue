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
        {{ modelValue ? df.format(modelValue.toDate(getLocalTimeZone())) : t('step3.datePickerPlaceholder') }}
      </Button>
    </PopoverTrigger>
    <PopoverContent class="w-auto p-0">
      <Calendar v-model="selectedDate" initial-focus @update:modelValue="updateDate" />
    </PopoverContent>
  </Popover>
</template>

<script setup lang="ts">
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button'
import { Calendar } from '@/components/ui/calendar'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import { DateFormatter, type DateValue, getLocalTimeZone } from '@internationalized/date'
import { CalendarIcon } from 'lucide-vue-next'
import { ref, watch, defineProps, defineEmits } from 'vue'
import { useI18n } from "vue-i18n";
const { locale, t } = useI18n();

const localeMap: Record<string, string> = {
  fr: "fr-FR",
  nl: "nl-NL",
}

const df = new DateFormatter(localeMap[locale.value] || "fr-FR", {
  dateStyle: "long"
});
const open = ref(false);
const props = defineProps({
  modelValue: Object // Receiving v-model value
})

const emit = defineEmits(['update:modelValue'])

const selectedDate = ref(props.modelValue)

watch(selectedDate, (newValue) => {
  emit('update:modelValue', newValue)
})

const updateDate = (newDate: DateValue) => {
  selectedDate.value = newDate
  emit('update:modelValue', newDate)
  open.value = false // Close the popover after selecting a date
}
</script>