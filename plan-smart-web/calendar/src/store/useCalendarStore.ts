import { create } from 'zustand';
import { persist } from 'zustand/middleware';
import type { CalendarEvent, EventCategory, UserPreferences, ViewMode } from '@/types';

interface CalendarStore {
  events: CalendarEvent[];
  categories: EventCategory[];
  preferences: UserPreferences;
  selectedEventId: string | null;
  setEvents: (events: CalendarEvent[]) => void;
  addEvent: (event: Omit<CalendarEvent, 'id'>) => void;
  updateEvent: (id: string, event: Partial<CalendarEvent>) => void;
  deleteEvent: (id: string) => void;
  setCategories: (categories: EventCategory[]) => void;
  setPreferences: (preferences: Partial<UserPreferences>) => void;
  setSelectedEventId: (id: string | null) => void;
}

const defaultPreferences: UserPreferences = {
  defaultEventDuration: 30,
  workingHours: {
    start: '09:00',
    end: '17:00',
  },
  breakDuration: 15,
  preferredMeetingDays: [1, 2, 3, 4, 5], // Mon-Fri
  minimumBreakBetweenEvents: 15,
  defaultView: 'week',
  quickEventTemplates: [
    { title: 'Quick Meeting', duration: 30, category: 'work' },
    { title: 'Long Meeting', duration: 60, category: 'work' },
    { title: 'Break', duration: 15, category: 'personal' },
  ],
};

const defaultCategories: EventCategory[] = [
  {
    id: 'work',
    name: 'Work',
    color: '#0070f3',
    quickTemplates: [
      { title: 'Team Meeting', duration: 30 },
      { title: '1:1 Meeting', duration: 30 },
    ],
  },
  {
    id: 'personal',
    name: 'Personal',
    color: '#ff0080',
    quickTemplates: [
      { title: 'Coffee Break', duration: 15 },
      { title: 'Lunch', duration: 60 },
    ],
  },
  {
    id: 'focus',
    name: 'Focus',
    color: '#50e3c2',
    quickTemplates: [
      { title: 'Deep Work', duration: 90 },
      { title: 'Quick Task', duration: 25 },
    ],
  },
];

export const useCalendarStore = create<CalendarStore>()(
  persist(
    (set) => ({
      events: [],
      categories: defaultCategories,
      preferences: defaultPreferences,
      selectedEventId: null,

      setEvents: (events) => set({ events }),
      
      addEvent: (event) => set((state) => ({
        events: [...state.events, { ...event, id: crypto.randomUUID() }],
      })),
      
      updateEvent: (id, event) => set((state) => ({
        events: state.events.map((e) =>
          e.id === id ? { ...e, ...event } : e
        ),
      })),
      
      deleteEvent: (id) => set((state) => ({
        events: state.events.filter((e) => e.id !== id),
      })),
      
      setCategories: (categories) => set({ categories }),
      
      setPreferences: (preferences) => set((state) => ({
        preferences: { ...state.preferences, ...preferences },
      })),
      
      setSelectedEventId: (id) => set({ selectedEventId: id }),
    }),
    {
      name: 'calendar-storage', // unique name for localStorage key
      partialize: (state) => ({
        // Only persist these fields
        events: state.events,
        categories: state.categories,
        preferences: state.preferences,
      }),
    }
  )
);