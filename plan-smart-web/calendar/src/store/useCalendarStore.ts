import { create } from 'zustand';
import { persist } from 'zustand/middleware';
import type { CalendarEvent, EventCategory, UserPreferences, ViewMode } from '@/types';

// Helper to deduplicate events with the same ID
const ensureUniqueIds = (events: CalendarEvent[]): CalendarEvent[] => {
  const uniqueEvents = new Map<string, CalendarEvent>();
  
  events.forEach(event => {
    if (!uniqueEvents.has(event.id)) {
      uniqueEvents.set(event.id, event);
    } else {
      // If duplicate ID found, create new ID for the event
      uniqueEvents.set(crypto.randomUUID(), {
        ...event,
        id: crypto.randomUUID()
      });
    }
  });
  
  return Array.from(uniqueEvents.values());
};

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
  populateWithMockEvents: () => void;
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

const studentCategories: EventCategory[] = [
  {
    id: 'class',
    name: 'Classes',
    color: '#0070f3', // blue
    quickTemplates: [
      { title: 'Lecture', duration: 90 },
      { title: 'Lab', duration: 120 },
    ],
  },
  {
    id: 'study',
    name: 'Study',
    color: '#50e3c2', // teal
    quickTemplates: [
      { title: 'Homework', duration: 60 },
      { title: 'Reading', duration: 45 },
    ],
  },
  {
    id: 'personal',
    name: 'Personal',
    color: '#ff0080', // pink
    quickTemplates: [
      { title: 'Gym', duration: 60 },
      { title: 'Meal', duration: 45 },
    ],
  },
  {
    id: 'work',
    name: 'Work',
    color: '#f5a623', // orange
    quickTemplates: [
      { title: 'Part-time Job', duration: 180 },
      { title: 'Internship', duration: 240 },
    ],
  },
  {
    id: 'social',
    name: 'Social',
    color: '#7928ca', // purple
    quickTemplates: [
      { title: 'Club Meeting', duration: 60 },
      { title: 'Hangout', duration: 120 },
    ],
  },
];

const generateMockEvents = () => {
  const now = new Date();
  const startOfMonth = new Date(now.getFullYear(), now.getMonth(), 1);
  const endOfMonth = new Date(now.getFullYear(), now.getMonth() + 1, 0);
  
  const mockEvents: Omit<CalendarEvent, 'id'>[] = [];
  
  // Generate 20 random events for the current month
  for (let i = 0; i < 20; i++) {
    const startDate = new Date(
      startOfMonth.getTime() + Math.random() * (endOfMonth.getTime() - startOfMonth.getTime())
    );
    
    // Round to nearest 30 minutes
    startDate.setMinutes(Math.round(startDate.getMinutes() / 30) * 30);
    startDate.setSeconds(0);
    startDate.setMilliseconds(0);
    
    const endDate = new Date(startDate.getTime() + (30 + Math.floor(Math.random() * 4) * 30) * 60000);
    
    mockEvents.push({
      title: `Mock Event ${i + 1}`,
      start: startDate.toISOString(),
      end: endDate.toISOString(),
      description: `This is a mock event ${i + 1}`,
      category: Math.random() > 0.5 ? 'work' : 'personal',
    });
  }
  
  return mockEvents;
};

export const useCalendarStore = create<CalendarStore>()(
  persist(
    (set) => ({
      events: [],
      categories: studentCategories,
      preferences: defaultPreferences,
      selectedEventId: null,

      setEvents: (events) => set({ events: ensureUniqueIds(events) }),
      
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

      populateWithMockEvents: () => {
        // Get current month
        const now = new Date();
        const currentMonth = now.getMonth();
        const currentYear = now.getFullYear();
        const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
        
        const mockEvents: Omit<CalendarEvent, 'id'>[] = [];
        
        // Class schedule (weekdays)
        for (let day = 1; day <= daysInMonth; day++) {
          const date = new Date(currentYear, currentMonth, day);
          const dayOfWeek = date.getDay(); // 0 = Sunday, 1 = Monday, etc.
          
          // Skip weekends for regular classes
          if (dayOfWeek >= 1 && dayOfWeek <= 5) {
            // Morning class (Mon, Wed, Fri)
            if ([1, 3, 5].includes(dayOfWeek)) {
              mockEvents.push({
                title: 'CS101: Introduction to Programming',
                start: new Date(currentYear, currentMonth, day, 9, 0),
                end: new Date(currentYear, currentMonth, day, 10, 30),
                description: 'Lecture on fundamentals of programming concepts',
                category: 'class',
                location: 'Engineering Building, Room 301'
              });
              
              // Study time after class
              mockEvents.push({
                title: 'CS101 Homework',
                start: new Date(currentYear, currentMonth, day, 11, 0),
                end: new Date(currentYear, currentMonth, day, 12, 30),
                description: 'Complete programming assignments',
                category: 'study'
              });
            }
            
            // Afternoon classes (Tue, Thu)
            if ([2, 4].includes(dayOfWeek)) {
              mockEvents.push({
                title: 'MATH201: Calculus II',
                start: new Date(currentYear, currentMonth, day, 13, 0),
                end: new Date(currentYear, currentMonth, day, 14, 30),
                description: 'Lecture on integral calculus techniques',
                category: 'class',
                location: 'Science Center, Room 204'
              });
              
              // Lab session (Thursday only)
              if (dayOfWeek === 4) {
                mockEvents.push({
                  title: 'CS101 Lab Session',
                  start: new Date(currentYear, currentMonth, day, 15, 0),
                  end: new Date(currentYear, currentMonth, day, 17, 0),
                  description: 'Practical coding exercises and lab work',
                  category: 'class',
                  location: 'Computer Lab, Room 105'
                });
              }
            }
            
            // Daily lunch
            mockEvents.push({
              title: 'Lunch',
              start: new Date(currentYear, currentMonth, day, 12, 30),
              end: new Date(currentYear, currentMonth, day, 13, 15),
              description: 'Lunch break',
              category: 'personal',
              location: 'Student Center'
            });
          }
          
          // Evening activities
          
          // Monday - Club meeting
          if (dayOfWeek === 1) {
            mockEvents.push({
              title: 'Robotics Club',
              start: new Date(currentYear, currentMonth, day, 18, 0),
              end: new Date(currentYear, currentMonth, day, 19, 30),
              description: 'Weekly robotics club meeting',
              category: 'social',
              location: 'Engineering Commons'
            });
          }
          
          // Tuesday - Study group
          if (dayOfWeek === 2) {
            mockEvents.push({
              title: 'Study Group',
              start: new Date(currentYear, currentMonth, day, 19, 0),
              end: new Date(currentYear, currentMonth, day, 21, 0),
              description: 'Group study for upcoming exams',
              category: 'study',
              location: 'Library, Study Room 3'
            });
          }
          
          // Wednesday - Part-time job
          if (dayOfWeek === 3) {
            mockEvents.push({
              title: 'Shift at Campus Bookstore',
              start: new Date(currentYear, currentMonth, day, 16, 0),
              end: new Date(currentYear, currentMonth, day, 20, 0),
              description: 'Weekly work shift',
              category: 'work',
              location: 'Campus Bookstore'
            });
          }
          
          // Thursday - Gym
          if (dayOfWeek === 4) {
            mockEvents.push({
              title: 'Gym Workout',
              start: new Date(currentYear, currentMonth, day, 18, 30),
              end: new Date(currentYear, currentMonth, day, 20, 0),
              description: 'Weekly fitness routine',
              category: 'personal',
              location: 'Campus Rec Center'
            });
          }
          
          // Friday - Social
          if (dayOfWeek === 5) {
            mockEvents.push({
              title: 'Movie Night with Friends',
              start: new Date(currentYear, currentMonth, day, 19, 0),
              end: new Date(currentYear, currentMonth, day, 22, 0),
              description: 'Weekly hangout with friends',
              category: 'social'
            });
          }
          
          // Weekend activities
          if (dayOfWeek === 0 || dayOfWeek === 6) {
            // Saturday - Part-time job (longer shift)
            if (dayOfWeek === 6) {
              mockEvents.push({
                title: 'Shift at Campus Bookstore',
                start: new Date(currentYear, currentMonth, day, 10, 0),
                end: new Date(currentYear, currentMonth, day, 16, 0),
                description: 'Weekend work shift',
                category: 'work',
                location: 'Campus Bookstore'
              });
              
              // Saturday evening social
              mockEvents.push({
                title: 'Dinner with Friends',
                start: new Date(currentYear, currentMonth, day, 18, 0),
                end: new Date(currentYear, currentMonth, day, 20, 0),
                description: 'Dinner out with friends',
                category: 'social',
                location: 'Downtown Pizza Place'
              });
            }
            
            // Sunday - Study day
            if (dayOfWeek === 0) {
              mockEvents.push({
                title: 'Weekly Study Planning',
                start: new Date(currentYear, currentMonth, day, 10, 0),
                end: new Date(currentYear, currentMonth, day, 11, 0),
                description: 'Plan study schedule for the week',
                category: 'study'
              });
              
              mockEvents.push({
                title: 'Math Homework',
                start: new Date(currentYear, currentMonth, day, 14, 0),
                end: new Date(currentYear, currentMonth, day, 16, 0),
                description: 'Complete calculus problem sets',
                category: 'study'
              });
              
              mockEvents.push({
                title: 'Call Family',
                start: new Date(currentYear, currentMonth, day, 18, 0),
                end: new Date(currentYear, currentMonth, day, 19, 0),
                description: 'Weekly call with parents',
                category: 'personal'
              });
            }
          }
          
          // Add special events
          
          // Mid-term exams (around the middle of the month)
          if (day === 15 || day === 16) {
            mockEvents.push({
              title: day === 15 ? 'CS101 Midterm Exam' : 'MATH201 Midterm Exam',
              start: new Date(currentYear, currentMonth, day, 14, 0),
              end: new Date(currentYear, currentMonth, day, 16, 0),
              description: 'Midterm examination',
              category: 'class',
              location: 'Exam Hall'
            });
          }
          
          // Project deadline at end of month
          if (day === daysInMonth - 2) {
            mockEvents.push({
              title: 'CS101 Project Submission',
              start: new Date(currentYear, currentMonth, day, 23, 59),
              end: new Date(currentYear, currentMonth, day, 23, 59),
              description: 'Final project submission deadline',
              category: 'class'
            });
          }
        }
        
        // Convert all events to have proper IDs
        const eventsWithIds = mockEvents.map(event => ({
          ...event,
          id: crypto.randomUUID(),
          start: event.start instanceof Date ? event.start : new Date(event.start),
          end: event.end instanceof Date ? event.end : new Date(event.end)
        }));
        
        set((state) => ({
          events: ensureUniqueIds([...state.events, ...eventsWithIds]),
          categories: studentCategories
        }));
      },
    }),
    {
      name: 'calendar-storage', // unique name for localStorage key
      partialize: (state) => ({
        // Only persist these fields
        events: state.events,
        categories: state.categories,
        preferences: state.preferences,
      }),
      onRehydrateStorage: (state) => {
        return (_, rehydratedState) => {
          if (rehydratedState && rehydratedState.events) {
            // Ensure all events have unique IDs when loading from storage
            rehydratedState.events = ensureUniqueIds(rehydratedState.events);
          }
        };
      }
    }
  )
);