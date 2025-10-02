<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
// Using native select elements instead of custom Select component
import { Calendar, ChevronLeft, ChevronRight } from 'lucide-vue-next';

interface CalendarDay {
  date: string;
  activities_count: number;
  points_earned: number;
  lessons_completed: number;
}

interface Props {
  calendar_data?: CalendarDay[];
  week_starts_on?: number; // 0=Sunday, 1=Monday
}

const props = withDefaults(defineProps<Props>(), {
  calendar_data: () => [],
  week_starts_on: 0,
});

const currentView = ref<'week' | 'month' | 'year'>('month');
const currentDate = ref(new Date());

// Get week start preference from localStorage or user preference
const weekStart = ref(props.week_starts_on);

onMounted(() => {
  const savedWeekStart = localStorage.getItem('week_starts_on');
  if (savedWeekStart !== null) {
    weekStart.value = parseInt(savedWeekStart);
  }
});

const updateWeekStart = (value: number) => {
  weekStart.value = value;
  localStorage.setItem('week_starts_on', value.toString());
};

// Generate calendar data for different views
// const generateCalendarData = () => { // Unused function
//   const data: { [key: string]: CalendarDay } = {};
//   
//   props.calendar_data.forEach(day => {
//     data[day.date] = day;
//   });
//   
//   return data;
// };

// const calendarData = computed(() => generateCalendarData()); // Unused variable

// Generate calendar grid based on current view
const calendarGrid = computed(() => {
  const today = new Date();
  // const data = calendarData.value; // Unused variable
  
  if (currentView.value === 'week') {
    return generateWeekGrid(today);
  } else if (currentView.value === 'month') {
    return generateMonthGrid(currentDate.value);
  } else {
    return generateYearGrid(currentDate.value);
  }
});

const generateWeekGrid = (date: Date) => {
  const startOfWeek = new Date(date);
  const dayOfWeek = startOfWeek.getDay();
  const startDay = weekStart.value;
  
  // Calculate the start of the week based on user preference
  const daysToSubtract = (dayOfWeek - startDay + 7) % 7;
  startOfWeek.setDate(startOfWeek.getDate() - daysToSubtract);
  
  const days = [];
  const dayHeaders = weekStart.value === 0 
    ? ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
    : ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
  
  for (let i = 0; i < 7; i++) {
    const currentDay = new Date(startOfWeek);
    currentDay.setDate(startOfWeek.getDate() + i);
    const dateString = currentDay.toISOString().split('T')[0];
    const dayData = data[dateString];
    
    days.push({
      date: dateString,
      day: currentDay.getDate(),
      month: currentDay.getMonth(),
      year: currentDay.getFullYear(),
      isToday: currentDay.toDateString() === today.toDateString(),
      activities_count: dayData?.activities_count || 0,
      points_earned: dayData?.points_earned || 0,
      lessons_completed: dayData?.lessons_completed || 0,
      header: dayHeaders[i]
    });
  }
  
  return { days, headers: dayHeaders };
};

const generateMonthGrid = (date: Date) => {
  const year = date.getFullYear();
  const month = date.getMonth();
  const firstDay = new Date(year, month, 1);
  // const lastDay = new Date(year, month + 1, 0); // Unused variable
  const startDay = weekStart.value;
  
  const days = [];
  const dayHeaders = weekStart.value === 0 
    ? ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
    : ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
  
  // Calculate the start of the calendar grid
  const firstDayOfWeek = firstDay.getDay();
  const daysToSubtract = (firstDayOfWeek - startDay + 7) % 7;
  const gridStart = new Date(firstDay);
  gridStart.setDate(firstDay.getDate() - daysToSubtract);
  
  // Generate 42 days (6 weeks) to fill the grid
  for (let i = 0; i < 42; i++) {
    const currentDay = new Date(gridStart);
    currentDay.setDate(gridStart.getDate() + i);
    const dateString = currentDay.toISOString().split('T')[0];
    const dayData = data[dateString];
    
    days.push({
      date: dateString,
      day: currentDay.getDate(),
      month: currentDay.getMonth(),
      year: currentDay.getFullYear(),
      isCurrentMonth: currentDay.getMonth() === month,
      isToday: currentDay.toDateString() === new Date().toDateString(),
      activities_count: dayData?.activities_count || 0,
      points_earned: dayData?.points_earned || 0,
      lessons_completed: dayData?.lessons_completed || 0
    });
  }
  
  return { days, headers: dayHeaders };
};

const generateYearGrid = (date: Date) => {
  const year = date.getFullYear();
  const months = [];
  
  for (let month = 0; month < 12; month++) {
    const monthDate = new Date(year, month, 1);
    const monthGrid = generateMonthGrid(monthDate);
    months.push({
      month: month,
      monthName: monthDate.toLocaleDateString('en-US', { month: 'long' }),
      days: monthGrid.days.slice(0, 35) // Only show 5 weeks for year view
    });
  }
  
  return { months };
};

const getActivityIntensity = (activitiesCount: number) => {
  if (activitiesCount === 0) return 'bg-gray-100 dark:bg-gray-800';
  if (activitiesCount <= 2) return 'bg-green-200 dark:bg-green-900';
  if (activitiesCount <= 5) return 'bg-green-300 dark:bg-green-800';
  if (activitiesCount <= 10) return 'bg-green-400 dark:bg-green-700';
  return 'bg-green-500 dark:bg-green-600';
};

const navigateCalendar = (direction: 'prev' | 'next') => {
  const newDate = new Date(currentDate.value);
  
  if (currentView.value === 'week') {
    newDate.setDate(newDate.getDate() + (direction === 'next' ? 7 : -7));
  } else if (currentView.value === 'month') {
    newDate.setMonth(newDate.getMonth() + (direction === 'next' ? 1 : -1));
  } else {
    newDate.setFullYear(newDate.getFullYear() + (direction === 'next' ? 1 : -1));
  }
  
  currentDate.value = newDate;
};

const goToToday = () => {
  currentDate.value = new Date();
};

const formatDateRange = () => {
  if (currentView.value === 'week') {
    const start = new Date(currentDate.value);
    const dayOfWeek = start.getDay();
    const startDay = weekStart.value;
    const daysToSubtract = (dayOfWeek - startDay + 7) % 7;
    start.setDate(start.getDate() - daysToSubtract);
    
    const end = new Date(start);
    end.setDate(start.getDate() + 6);
    
    return `${start.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} - ${end.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}`;
  } else if (currentView.value === 'month') {
    return currentDate.value.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
  } else {
    return currentDate.value.getFullYear().toString();
  }
};
</script>

<template>
  <Card>
    <CardHeader>
      <div class="flex items-center justify-between">
        <div>
          <CardTitle class="flex items-center gap-2">
            <Calendar class="h-5 w-5 text-primary" />
            Learning Calendar
          </CardTitle>
          <CardDescription>
            Your learning activity over time
          </CardDescription>
        </div>
        
        <div class="flex items-center gap-2">
          <select v-model="currentView" class="w-24 px-3 py-2 border border-gray-300 rounded-md bg-white text-sm">
            <option value="week">Week</option>
            <option value="month">Month</option>
            <option value="year">Year</option>
          </select>
          
          <select :value="weekStart" @change="updateWeekStart(parseInt(($event.target as HTMLSelectElement).value))" class="w-32 px-3 py-2 border border-gray-300 rounded-md bg-white text-sm">
            <option :value="0">Start: Sunday</option>
            <option :value="1">Start: Monday</option>
          </select>
        </div>
      </div>
    </CardHeader>
    
    <CardContent>
      <div class="space-y-4">
        <!-- Navigation -->
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <Button variant="outline" size="sm" @click="navigateCalendar('prev')">
              <ChevronLeft class="h-4 w-4" />
            </Button>
            <Button variant="outline" size="sm" @click="navigateCalendar('next')">
              <ChevronRight class="h-4 w-4" />
            </Button>
            <Button variant="outline" size="sm" @click="goToToday">
              Today
            </Button>
          </div>
          
          <h3 class="text-lg font-semibold">{{ formatDateRange() }}</h3>
        </div>
        
        <!-- Calendar Grid -->
        <div v-if="currentView === 'week'" class="space-y-2">
          <!-- Week view -->
          <div class="grid grid-cols-7 gap-1">
            <div v-for="header in calendarGrid.headers" :key="header" 
                 class="text-center text-sm font-medium text-muted-foreground py-2">
              {{ header }}
            </div>
            <div v-for="day in calendarGrid.days" :key="day.date"
                 class="aspect-square flex items-center justify-center text-sm rounded border"
                 :class="[
                   getActivityIntensity(day.activities_count),
                   day.isToday ? 'ring-2 ring-primary' : '',
                   'hover:ring-1 hover:ring-primary/50'
                 ]">
              <span class="text-xs font-medium">{{ day.day }}</span>
            </div>
          </div>
        </div>
        
        <div v-else-if="currentView === 'month'" class="space-y-2">
          <!-- Month view -->
          <div class="grid grid-cols-7 gap-1">
            <div v-for="header in calendarGrid.headers" :key="header" 
                 class="text-center text-sm font-medium text-muted-foreground py-2">
              {{ header }}
            </div>
            <div v-for="day in calendarGrid.days" :key="day.date"
                 class="aspect-square flex items-center justify-center text-sm rounded border"
                 :class="[
                   getActivityIntensity(day.activities_count),
                   day.isCurrentMonth ? '' : 'opacity-30',
                   day.isToday ? 'ring-2 ring-primary' : '',
                   'hover:ring-1 hover:ring-primary/50'
                 ]">
              <span class="text-xs font-medium">{{ day.day }}</span>
            </div>
          </div>
        </div>
        
        <div v-else class="space-y-4">
          <!-- Year view -->
          <div class="grid grid-cols-3 gap-4">
            <div v-for="month in calendarGrid.months" :key="month.month" class="space-y-2">
              <h4 class="text-sm font-medium text-center">{{ month.monthName }}</h4>
              <div class="grid grid-cols-7 gap-1">
                <div v-for="header in ['S', 'M', 'T', 'W', 'T', 'F', 'S']" :key="header" 
                     class="text-center text-xs font-medium text-muted-foreground">
                  {{ header }}
                </div>
                <div v-for="day in month.days" :key="day.date"
                     class="aspect-square flex items-center justify-center text-xs rounded"
                     :class="[
                       getActivityIntensity(day.activities_count),
                       day.isCurrentMonth ? '' : 'opacity-30',
                       day.isToday ? 'ring-1 ring-primary' : ''
                     ]">
                  <span class="text-xs">{{ day.day }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Legend -->
        <div class="flex items-center justify-center gap-4 text-sm text-muted-foreground">
          <span>Less</span>
          <div class="flex gap-1">
            <div class="w-3 h-3 rounded bg-gray-100 dark:bg-gray-800"></div>
            <div class="w-3 h-3 rounded bg-green-200 dark:bg-green-900"></div>
            <div class="w-3 h-3 rounded bg-green-300 dark:bg-green-800"></div>
            <div class="w-3 h-3 rounded bg-green-400 dark:bg-green-700"></div>
            <div class="w-3 h-3 rounded bg-green-500 dark:bg-green-600"></div>
          </div>
          <span>More</span>
        </div>
      </div>
    </CardContent>
  </Card>
</template>
