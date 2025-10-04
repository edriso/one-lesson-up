<template>
    <div class="space-y-2">
        <Label for="timezone">Timezone</Label>

        <!-- Auto-detection confirmation -->
        <div
            v-if="showAutoDetection"
            class="rounded-md border border-blue-200 bg-blue-50 p-3"
        >
            <div class="flex items-start space-x-3">
                <div class="flex-shrink-0">
                    <svg
                        class="h-5 w-5 text-blue-400"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                            clip-rule="evenodd"
                        />
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-sm font-medium text-blue-800">
                        We detected your timezone
                    </h3>
                    <p class="mt-1 text-sm text-blue-700">
                        Current time: <strong>{{ currentTime }}</strong
                        ><br />
                        Timezone: <strong>{{ detectedTimezoneLabel }}</strong>
                    </p>
                    <div class="mt-3 flex space-x-2">
                        <Button
                            type="button"
                            size="sm"
                            @click="confirmAutoDetection"
                            class="bg-blue-600 hover:bg-blue-700"
                        >
                            Use This Timezone
                        </Button>
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            @click="showManualSelection"
                            class="border-blue-300 text-blue-700 hover:bg-blue-50"
                        >
                            Choose Different
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Manual timezone selection -->
        <div v-else>
            <Select
                name="timezone"
                v-model="selectedTimezone"
                :disabled="!canUpdateTimezone"
            >
                <SelectTrigger class="w-full">
                    <SelectValue placeholder="Select your timezone" />
                </SelectTrigger>
                <SelectContent class="max-h-80">
                    <!-- Search input -->
                    <div class="border-b p-2">
                        <Input
                            v-model="searchTerm"
                            placeholder="Search timezones..."
                            class="h-8"
                            :disabled="!canUpdateTimezone"
                            @input="handleSearch"
                        />
                    </div>

                    <!-- Timezone options -->
                    <div class="max-h-60 overflow-y-auto">
                        <template
                            v-for="group in filteredGroups"
                            :key="group.name"
                        >
                            <div v-if="group.timezones.length > 0" class="py-1">
                                <div
                                    class="px-2 py-1 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                                >
                                    {{ group.name }}
                                </div>
                                <SelectItem
                                    v-for="timezone in group.timezones"
                                    :key="timezone.value"
                                    :value="timezone.value"
                                    class="flex items-center justify-between px-2 py-2"
                                >
                                    <div class="flex flex-col">
                                        <span class="font-medium">{{
                                            timezone.label
                                        }}</span>
                                        <span
                                            class="text-sm text-muted-foreground"
                                            >{{ timezone.city }},
                                            {{ timezone.country }}</span
                                        >
                                    </div>
                                    <div
                                        class="ml-2 text-xs text-muted-foreground"
                                    >
                                        {{ timezone.offset }}
                                    </div>
                                </SelectItem>
                            </div>
                        </template>

                        <!-- No results -->
                        <div
                            v-if="filteredTimezones.length === 0"
                            class="p-4 text-center text-muted-foreground"
                        >
                            No timezones found matching "{{ searchTerm }}"
                        </div>
                    </div>
                </SelectContent>
            </Select>

            <!-- Current time display -->
            <div
                v-if="selectedTimezone && currentTime"
                class="mt-2 text-sm text-muted-foreground"
            >
                Current time: <strong>{{ currentTime }}</strong>
            </div>

            <!-- 30-day restriction notice -->
            <Alert v-if="!canUpdateTimezone" variant="destructive" class="mt-2">
                <Info class="h-4 w-4" />
                <AlertDescription>
                    <span v-if="nextUpdateDate">
                        Timezone can be updated again on {{ nextUpdateDate }}.
                    </span>
                    <span v-else>
                        Timezone can only be updated once every 30 days.
                    </span>
                </AlertDescription>
            </Alert>

            <p v-else class="mt-1 text-xs text-muted-foreground">
                Your timezone affects when you can earn time bonuses for
                completing lessons.
                <strong>Note:</strong> You can only update your timezone once
                every 30 days.
            </p>
        </div>

        <InputError :message="errors.timezone" />
    </div>
</template>

<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    detectUserTimezone,
    filterTimezones,
    findTimezoneOption,
    getCurrentTimeInTimezone,
    getTimezoneGroups,
} from '@/lib/timezones';
import { Info } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';

interface Props {
    modelValue?: string;
    disabled?: boolean;
    canUpdateTimezone?: boolean;
    timezoneUpdatedAt?: string;
    errors?: Record<string, string>;
}

const props = withDefaults(defineProps<Props>(), {
    canUpdateTimezone: true,
    errors: () => ({}),
});

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

// State
const selectedTimezone = ref(props.modelValue || 'UTC');
const searchTerm = ref('');
const showAutoDetection = ref(false);
const detectedTimezone = ref('');

// Computed
const currentTime = computed(() => {
    if (!selectedTimezone.value) return '';
    return getCurrentTimeInTimezone(selectedTimezone.value);
});

const detectedTimezoneLabel = computed(() => {
    const option = findTimezoneOption(detectedTimezone.value);
    return option ? `${option.label} (${option.city})` : detectedTimezone.value;
});

const nextUpdateDate = computed(() => {
    if (!props.timezoneUpdatedAt) return '';
    const updateDate = new Date(props.timezoneUpdatedAt);
    updateDate.setDate(updateDate.getDate() + 30);
    return updateDate.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
});

const filteredTimezones = computed(() => {
    return filterTimezones(searchTerm.value);
});

const filteredGroups = computed(() => {
    const groups = getTimezoneGroups();
    return groups.map((groupName) => ({
        name: groupName,
        timezones: filteredTimezones.value.filter(
            (tz) => tz.group === groupName,
        ),
    }));
});

// Methods
const handleSearch = () => {
    // Search is handled by computed property
};

const confirmAutoDetection = () => {
    selectedTimezone.value = detectedTimezone.value;
    emit('update:modelValue', detectedTimezone.value);
    showAutoDetection.value = false;
};

const showManualSelection = () => {
    showAutoDetection.value = false;
};

// Watch for changes
watch(selectedTimezone, (newValue) => {
    emit('update:modelValue', newValue);
});

watch(
    () => props.modelValue,
    (newValue) => {
        if (newValue) {
            selectedTimezone.value = newValue;
        }
    },
);

// Auto-detection on mount
onMounted(() => {
    if (props.canUpdateTimezone && !props.modelValue) {
        detectedTimezone.value = detectUserTimezone();
        const detectedOption = findTimezoneOption(detectedTimezone.value);

        if (detectedOption) {
            showAutoDetection.value = true;
        } else {
            // If we can't find the detected timezone in our list, use UTC
            selectedTimezone.value = 'UTC';
        }
    }
});
</script>
