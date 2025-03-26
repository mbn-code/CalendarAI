"use client";

import { useState, useEffect } from "react";

// Helper functions for date manipulation
const getWeekDays = (date: Date) => {
  const day = date.getDay();
  const diff = date.getDate() - day + (day === 0 ? -6 : 1); // Adjust for Sunday
  const monday = new Date(date.setDate(diff));
  
  const weekDays = [];
  for (let i = 0; i < 7; i++) {
    const nextDay = new Date(monday);
    nextDay.setDate(monday.getDate() + i);
    weekDays.push(nextDay);
  }
  
  return weekDays;
};

// Fix the formatDateToISO function to handle undefined
const formatDateToISO = (date: Date | undefined): string => {
  if (!date) return '';
  return date.toISOString().split('T')[0];
};

// Event type definition
type Event = {
  id: string;
  title: string;
  description: string;
  date: string;
  startTime: string;
  endTime: string;
  color: string;
  category?: string;
  locked?: boolean;  // New property
};

type ViewMode = 'week' | 'day' | 'month';
type Category = {
  id: string;
  name: string;
  color: string;
};

type AIPreferences = {
  preferredStartTime: string;
  preferredEndTime: string;
  preferredBreakDuration: number;
  preferredSessionDuration: number;
  focusTimePreference: 'morning' | 'afternoon' | 'evening';
  breaksBetweenTasks: boolean;
  maximumMeetingsPerDay: number;
  preferredMeetingDuration: number;
  systemPrompt: string;
};

export default function HomePage() {
  const [currentDate, setCurrentDate] = useState<Date>(new Date());
  const [weekDays, setWeekDays] = useState<Date[]>([]);
  const [events, setEvents] = useState<Event[]>([]);
  const [showEventModal, setShowEventModal] = useState(false);
  const [selectedDay, setSelectedDay] = useState<Date | null>(null);
  const [selectedEvent, setSelectedEvent] = useState<Event | null>(null);
  const [showEventDetails, setShowEventDetails] = useState(false);
  const [viewMode, setViewMode] = useState<ViewMode>('week');
  const [sidebarOpen, setSidebarOpen] = useState(true);
  const [searchQuery, setSearchQuery] = useState('');
  const [categories, setCategories] = useState<Category[]>([
    { id: '1', name: 'Work', color: '#0070f3' },
    { id: '2', name: 'Personal', color: '#ff0080' },
    { id: '3', name: 'Important', color: '#50e3c2' },
  ]);
  const [selectedCategories, setSelectedCategories] = useState<string[]>([]);
  const [notifications, setNotifications] = useState<string[]>([]);
  const [newEvent, setNewEvent] = useState<Omit<Event, 'id'>>({
    title: '',
    description: '',
    date: '',
    startTime: '09:00',
    endTime: '10:00',
    color: '#000000',
    category: '',
  });
  const [showAIPreferences, setShowAIPreferences] = useState(false);
  // Fix type safety for AI preferences initial state
  const [aiPreferences, setAIPreferences] = useState<AIPreferences>(() => {
    if (typeof window !== 'undefined') {
      const saved = localStorage.getItem('calendar-ai-preferences');
      if (saved) {
        return JSON.parse(saved) as AIPreferences;
      }
    }
    return {
      preferredStartTime: '09:00',
      preferredEndTime: '17:00',
      preferredBreakDuration: 15,
      preferredSessionDuration: 45,
      focusTimePreference: 'morning' as const,
      breaksBetweenTasks: true,
      maximumMeetingsPerDay: 4,
      preferredMeetingDuration: 30,
      systemPrompt: `I am your personal calendar assistant. When optimizing schedules:
- I prefer to work from {preferredStartTime} to {preferredEndTime}
- I am most focused in the {focusTimePreference}
- I need {preferredBreakDuration} minute breaks between tasks
- My ideal work session is {preferredSessionDuration} minutes
- I prefer no more than {maximumMeetingsPerDay} meetings per day
- My ideal meeting length is {preferredMeetingDuration} minutes
Please help me maintain a balanced and productive schedule while respecting these preferences.`,
    };
  });
  const [showOptimizer, setShowOptimizer] = useState(false);
  // Update optimizer suggestion types
  interface OptimizerSuggestion {
    type: 'merge' | 'reschedule';
    suggestedChanges?: {
      eventId: string;
      newDate?: string;
      newStartTime?: string;
      newEndTime?: string;
    };
    explanation: string;
  }
  
  const [optimizerSuggestions, setOptimizerSuggestions] = useState<OptimizerSuggestion[]>([]);

  // Colors for events - using Vercel color scheme (monochromatic with accents)
  const eventColors = [
    { value: '#000000', label: 'Black', class: 'bg-black' },
    { value: '#666666', label: 'Gray', class: 'bg-gray-500' },
    { value: '#ff0080', label: 'Pink', class: 'bg-pink-600' }, // Vercel accent pink
    { value: '#0070f3', label: 'Blue', class: 'bg-blue-600' }, // Vercel accent blue
    { value: '#50e3c2', label: 'Cyan', class: 'bg-cyan-400' }, // Vercel accent cyan
  ];

  // Load data from localStorage on mount
  useEffect(() => {
    // Load events
    const savedEvents = localStorage.getItem('calendar-events');
    if (savedEvents) {
      setEvents(JSON.parse(savedEvents) as Event[]);
    }

    // Load categories
    const savedCategories = localStorage.getItem('calendar-categories');
    if (savedCategories) {
      setCategories(JSON.parse(savedCategories) as Category[]);
    }

    // Load preferences
    const savedViewMode = localStorage.getItem('calendar-view-mode');
    if (savedViewMode) {
      setViewMode(savedViewMode as ViewMode);
    }

    const savedSelectedCategories = localStorage.getItem('calendar-selected-categories');
    if (savedSelectedCategories) {
      setSelectedCategories(JSON.parse(savedSelectedCategories) as string[]);
    }
  }, []);

  // Save events to localStorage when they change
  useEffect(() => {
    localStorage.setItem('calendar-events', JSON.stringify(events));
  }, [events]);

  // Save categories to localStorage when they change
  useEffect(() => {
    localStorage.setItem('calendar-categories', JSON.stringify(categories));
  }, [categories]);

  // Save preferences to localStorage when they change
  useEffect(() => {
    localStorage.setItem('calendar-view-mode', viewMode);
  }, [viewMode]);

  useEffect(() => {
    localStorage.setItem('calendar-selected-categories', JSON.stringify(selectedCategories));
  }, [selectedCategories]);

  // Save AI preferences when they change
  useEffect(() => {
    localStorage.setItem('calendar-ai-preferences', JSON.stringify(aiPreferences));
  }, [aiPreferences]);

  // Check if it's first time using AI optimizer
  useEffect(() => {
    const hasUsedOptimizer = localStorage.getItem('calendar-has-used-optimizer');
    if (!hasUsedOptimizer) {
      setShowAIPreferences(true);
    }
  }, []);

  // Initialize week days on mount and when current date changes
  useEffect(() => {
    setWeekDays(getWeekDays(new Date(currentDate)));
  }, [currentDate]);

  // Handle navigation to previous/next week
  const goToPreviousWeek = () => {
    const newDate = new Date(currentDate);
    newDate.setDate(newDate.getDate() - 7);
    setCurrentDate(newDate);
  };

  const goToNextWeek = () => {
    const newDate = new Date(currentDate);
    newDate.setDate(newDate.getDate() + 7);
    setCurrentDate(newDate);
  };

  const goToToday = () => {
    setCurrentDate(new Date());
  };

  // Handle opening the event modal
  const handleDayClick = (day: Date) => {
    setSelectedDay(day);
    setNewEvent({
      ...newEvent,
      date: formatDateToISO(day),
    });
    setShowEventModal(true);
  };

  // Handle saving a new event
  const handleSaveEvent = () => {
    if (!newEvent.title) return;
    
    if (selectedEvent) {
      // Edit existing event
      setEvents(events.map(e => 
        e.id === selectedEvent.id ? { ...newEvent, id: selectedEvent.id } : e
      ));
      setSelectedEvent(null);
    } else {
      // Create new event
      const event: Event = {
        id: Date.now().toString(),
        ...newEvent,
      };
      setEvents([...events, event]);
    }
    
    setShowEventModal(false);
    setNewEvent({
      title: '',
      description: '',
      date: '',
      startTime: '09:00',
      endTime: '10:00',
      color: '#000000',
      category: '',
    });
    setNotifications([...notifications, 'Event saved successfully']);
  };

  // Filter events for a specific day
  const getEventsForDay = (day: Date) => {
    return events.filter(
      (event) => event.date === formatDateToISO(day)
    );
  };

  // Format day for header display
  const formatDayHeader = (date: Date) => {
    return new Intl.DateTimeFormat('en-US', { weekday: 'short' }).format(date);
  };

  // Format date for display
  const formatDate = (date: Date) => {
    return new Intl.DateTimeFormat('en-US', { day: 'numeric' }).format(date);
  };

  // Check if date is today
  const isToday = (date: Date) => {
    const today = new Date();
    return date.getDate() === today.getDate() &&
      date.getMonth() === today.getMonth() &&
      date.getFullYear() === today.getFullYear();
  };

  // Event handlers
  const handleEventClick = (event: Event) => {
    setSelectedEvent(event);
    setShowEventDetails(true);
  };

  const handleDeleteEvent = (eventId: string) => {
    setEvents(events.filter(e => e.id !== eventId));
    setShowEventDetails(false);
    setNotifications([...notifications, 'Event deleted successfully']);
  };

  const handleEditEvent = (event: Event) => {
    setNewEvent(event);
    setShowEventModal(true);
    setShowEventDetails(false);
  };

  // Filter events based on search and categories
  const filteredEvents = events.filter(event => {
    const matchesSearch = searchQuery === '' || 
      event.title.toLowerCase().includes(searchQuery.toLowerCase()) ||
      event.description.toLowerCase().includes(searchQuery.toLowerCase());
    
    const matchesCategory = selectedCategories.length === 0 || 
      (event.category && selectedCategories.includes(event.category));
    
    return matchesSearch && matchesCategory;
  });

  // AI Optimization Functions
  const optimizeSchedule = async () => {
    try {
      // Get all events for the current week, excluding locked events
      const weekEvents = weekDays.flatMap(day => 
        events
          .filter(event => event.date === formatDateToISO(day))
          .filter(event => !event.locked) // Skip locked events
      );

      // Format the system prompt with actual values
      const formattedSystemPrompt = (aiPreferences.systemPrompt || '')
        .replace('{preferredStartTime}', aiPreferences?.preferredStartTime || '09:00')
        .replace('{preferredEndTime}', aiPreferences?.preferredEndTime || '17:00')
        .replace('{focusTimePreference}', aiPreferences?.focusTimePreference || 'morning')
        .replace('{preferredBreakDuration}', (aiPreferences?.preferredBreakDuration || 15).toString())
        .replace('{preferredSessionDuration}', (aiPreferences?.preferredSessionDuration || 45).toString())
        .replace('{maximumMeetingsPerDay}', (aiPreferences?.maximumMeetingsPerDay || 4).toString())
        .replace('{preferredMeetingDuration}', (aiPreferences?.preferredMeetingDuration || 30).toString());

      // Add note about locked events to the system prompt
      const systemPromptWithLockNote = `${formattedSystemPrompt}\nNote: Some events are locked and should not be modified. Only suggest changes for unlocked events.`;

      // Call the Ollama API
      const response = await fetch('/api/ollama', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          events: weekEvents, // Only sending unlocked events
          preferences: aiPreferences,
          systemPrompt: systemPromptWithLockNote,
        }),
      });

      if (!response.ok) {
        throw new Error('Failed to get AI suggestions');
      }

      interface OptimizerResponse {
        suggestions: Array<{
          type: 'move' | 'split' | 'merge' | 'break';
          description: string;
          originalEvent?: Event;
          suggestedChanges: Partial<Event>;
        }>;
      }

      const data = (await response.json()) as OptimizerResponse;
      setOptimizerSuggestions(data.suggestions ?? []);
      setShowOptimizer(true);
    } catch (error) {
      console.error('Failed to optimize schedule:', error);
      setNotifications([...notifications, 'Failed to get AI suggestions. Please try again.']);
    }
  };

  const applyOptimizationSuggestion = (suggestion: OptimizerSuggestion) => {
    if (!suggestion.suggestedChanges) {
      console.warn('No suggested changes in the optimization suggestion');
      return;
    }
  
    const { eventId, newDate, newStartTime, newEndTime } = suggestion.suggestedChanges;
    if (!eventId) {
      console.warn('No eventId in the suggested changes');
      return;
    }
  
    setWeekDays((prevWeekDays) => {
      return prevWeekDays.map((day) => {
        const updatedEvents = day.events.map((event) => {
          if (event.id === eventId) {
            return {
              ...event,
              date: newDate || event.date,
              startTime: newStartTime || event.startTime,
              endTime: newEndTime || event.endTime,
            };
          }
          return event;
        });
  
        return {
          ...day,
          events: updatedEvents,
        };
      });
    });
  };

  const generateTestData = () => {
    if (weekDays.length < 5) {
      setNotifications([...notifications, 'Error: Calendar week not properly initialized']);
      return;
    }
  
    const testEvents: Omit<Event, 'id'>[] = [
      {
        title: "Morning Stand-up",
        description: "Daily team sync",
        date: formatDateToISO(weekDays[1]),
        startTime: "09:00",
        endTime: "09:30",
        color: "#0070f3",
        category: "Work"
      },
      {
        title: "Focus Coding Time",
        description: "Deep work session",
        date: formatDateToISO(weekDays[1]),
        startTime: "10:00",
        endTime: "12:00",
        color: "#50e3c2",
        category: "Work"
      },
      {
        title: "Lunch Break",
        description: "Time to recharge",
        date: formatDateToISO(weekDays[2]),
        startTime: "12:00",
        endTime: "13:00",
        color: "#ff0080",
        category: "Personal"
      },
      {
        title: "Client Meeting",
        description: "Project review",
        date: formatDateToISO(weekDays[2]),
        startTime: "14:00",
        endTime: "15:30",
        color: "#0070f3",
        category: "Work"
      },
      {
        title: "Gym Session",
        description: "Weekly workout",
        date: formatDateToISO(weekDays[3]),
        startTime: "07:00",
        endTime: "08:00",
        color: "#ff0080",
        category: "Personal"
      },
      {
        title: "Team Planning",
        description: "Sprint planning session",
        date: formatDateToISO(weekDays[3]),
        startTime: "10:00",
        endTime: "11:30",
        color: "#0070f3",
        category: "Work"
      },
      {
        title: "Code Review",
        description: "Review PRs",
        date: formatDateToISO(weekDays[4]),
        startTime: "15:00",
        endTime: "16:30",
        color: "#0070f3",
        category: "Work"
      },
      {
        title: "Late Meeting",
        description: "Cross-timezone sync",
        date: formatDateToISO(weekDays[4]),
        startTime: "16:30",
        endTime: "17:30",
        color: "#0070f3",
        category: "Work"
      }
    ];
  
    // Clear existing events and add test events
    const newEvents = testEvents.map(event => ({
      ...event,
      id: Date.now().toString() + Math.random().toString(36).substr(2, 9)
    }));
    
    setEvents(newEvents);
    setNotifications([...notifications, 'Test data generated successfully']);
    
    // Fix setTimeout with async function
    void setTimeout(() => void optimizeSchedule(), 500);
  };

  return (
    <div className="min-h-screen bg-white text-black dark:bg-black dark:text-white">
      {/* Notifications */}
      <div className="fixed top-4 right-4 z-50 space-y-2">
        {notifications.map((notification, index) => (
          <div 
            key={index}
            className="bg-black text-white dark:bg-white dark:text-black px-4 py-2 rounded-lg shadow-lg text-sm font-medium animate-fade-in-down"
            onClick={() => setNotifications(notifications.filter((_, i) => i !== index))}
          >
            {notification}
          </div>
        ))}
      </div>

      {/* Top Navigation */}
      <header className="border-b border-gray-100 dark:border-gray-800">
        <div className="max-w-screen-xl mx-auto px-4 py-4 sm:px-6 lg:px-8">
          <div className="flex flex-col space-y-4 sm:space-y-0 sm:flex-row sm:items-center sm:justify-between">
            <div className="flex items-center">
              <button
                onClick={() => setSidebarOpen(!sidebarOpen)}
                className="p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors mr-4"
              >
                <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
                </svg>
              </button>
              <h1 className="text-xl font-semibold flex items-center">
                WeekPlanner
                <span className="ml-2 text-[10px] uppercase font-medium px-1.5 py-0.5 rounded-full bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200">
                  Calendar
                </span>
              </h1>
            </div>

            <div className="flex items-center space-x-4">
              <div className="relative">
                <input
                  type="text"
                  placeholder="Search events..."
                  className="w-64 px-4 py-2 rounded-md border border-gray-200 dark:border-gray-700 bg-white dark:bg-black focus:outline-none focus:ring-1 focus:ring-black dark:focus:ring-white text-sm"
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                />
                <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5 absolute right-3 top-2.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
              </div>

              <div className="flex items-center space-x-2 bg-gray-100 dark:bg-gray-800 rounded-md p-1">
                <button
                  onClick={() => setViewMode('day')}
                  className={`px-3 py-1 rounded text-sm font-medium ${
                    viewMode === 'day' ? 'bg-white dark:bg-black shadow-sm' : ''
                  }`}
                >
                  Day
                </button>
                <button
                  onClick={() => setViewMode('week')}
                  className={`px-3 py-1 rounded text-sm font-medium ${
                    viewMode === 'week' ? 'bg-white dark:bg-black shadow-sm' : ''
                  }`}
                >
                  Week
                </button>
                <button
                  onClick={() => setViewMode('month')}
                  className={`px-3 py-1 rounded text-sm font-medium ${
                    viewMode === 'month' ? 'bg-white dark:bg-black shadow-sm' : ''
                  }`}
                >
                  Month
                </button>
              </div>

              <div className="flex space-x-4">
                <button
                  onClick={() => {
                    setSelectedEvent(null);
                    setShowEventModal(true);
                  }}
                  className="bg-black text-white dark:bg-white dark:text-black px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-800 dark:hover:bg-gray-200 transition-colors"
                >
                  + Add Event
                </button>
                <button
                  onClick={optimizeSchedule}
                  className="px-4 py-2 text-sm font-medium rounded-md transition-colors bg-purple-600 text-white hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500"
                >
                  Optimize Schedule
                </button>
                <button
                  onClick={generateTestData}
                  className="px-4 py-2 text-sm font-medium rounded-md transition-colors bg-green-600 text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                >
                  Generate Test Data
                </button>
              </div>
            </div>
          </div>

          <div className="flex items-center space-x-4 mt-4">
            <button 
              onClick={goToToday}
              className="px-3 py-1.5 text-sm font-medium rounded-md transition-colors bg-black text-white hover:bg-gray-800 dark:bg-white dark:text-black dark:hover:bg-gray-200"
            >
              Today
            </button>
            <div className="flex items-center space-x-2">
              <button 
                onClick={goToPreviousWeek}
                className="p-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                aria-label="Previous"
              >
                <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                  <path fillRule="evenodd" d="M12.707 5.293a1 1 010 1.414L9.414 10l3.293 3.293a1 1 01-1.414 1.414l-4-4a1 1 010-1.414l4-4a1 1 011.414 0z" clipRule="evenodd" />
                </svg>
              </button>
              <span className="text-sm font-medium">
                {weekDays.length > 0 && new Intl.DateTimeFormat('en-US', { month: 'long', year: 'numeric' }).format(weekDays[0])}
              </span>
              <button 
                onClick={goToNextWeek}
                className="p-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                aria-label="Next"
              >
                <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                  <path fillRule="evenodd" d="M7.293 14.707a1 1 010-1.414L10.586 10 7.293 6.707a1 1 011.414-1.414l4 4a1 1 010 1.414l-4 4a1 1 01-1.414 0z" clipRule="evenodd" />
                </svg>
              </button>
            </div>
          </div>
        </div>
      </header>

      <div className="flex">
        {/* Sidebar */}
        {sidebarOpen && (
          <aside className="w-64 border-r border-gray-100 dark:border-gray-800 h-[calc(100vh-theme(spacing.32))] p-4">
            <div className="space-y-6">
              <div>
                <h2 className="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">
                  Categories
                </h2>
                <div className="space-y-2">
                  {categories.map(category => (
                    <label 
                      key={category.id}
                      className="flex items-center space-x-3 text-sm cursor-pointer"
                    >
                      <input
                        type="checkbox"
                        className="rounded border-gray-300 text-black focus:ring-black dark:border-gray-600 dark:focus:ring-white"
                        checked={selectedCategories.includes(category.id)}
                        onChange={(e) => {
                          if (e.target.checked) {
                            setSelectedCategories([...selectedCategories, category.id]);
                          } else {
                            setSelectedCategories(selectedCategories.filter(id => id !== category.id));
                          }
                        }}
                      />
                      <span className="flex items-center">
                        <span 
                          className="w-3 h-3 rounded-full mr-2"
                          style={{ backgroundColor: category.color }}
                        />
                        {category.name}
                      </span>
                    </label>
                  ))}
                </div>
              </div>

              <div>
                <h2 className="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">
                  Upcoming Events
                </h2>
                <div className="space-y-2">
                  {filteredEvents
                    .filter(event => new Date(event.date) >= new Date())
                    .sort((a, b) => new Date(a.date).getTime() - new Date(b.date).getTime())
                    .slice(0, 5)
                    .map(event => (
                      <button
                        key={event.id}
                        onClick={() => handleEventClick(event)}
                        className="w-full text-left p-2 rounded-md hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                      >
                        <div className="flex items-center">
                          <div 
                            className="w-2 h-2 rounded-full mr-2"
                            style={{ backgroundColor: event.color }}
                          />
                          <div>
                            <div className="font-medium text-sm truncate">{event.title}</div>
                            <div className="text-xs text-gray-500 dark:text-gray-400">
                              {new Date(event.date).toLocaleDateString()} {event.startTime}
                            </div>
                          </div>
                        </div>
                      </button>
                    ))}
                </div>
              </div>
            </div>
          </aside>
        )}

        {/* Main Calendar Content */}
        <main className={`flex-1 ${sidebarOpen ? 'pl-4' : ''} pr-4 py-6`}>
          {/* Calendar grid - Vercel minimal design */}
          <div className="rounded-lg overflow-hidden border border-gray-100 dark:border-gray-800">
            {/* Day headers */}
            <div className="grid grid-cols-7">
              {weekDays.map((day, index) => (
                <div key={index} className="py-3 text-center">
                  <div className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{formatDayHeader(day)}</div>
                  <div className={`text-base font-medium mt-1 w-8 h-8 flex items-center justify-center mx-auto ${
                    isToday(day) 
                    ? 'bg-black text-white rounded-full dark:bg-white dark:text-black' 
                    : 'text-black dark:text-white'
                  }`}>
                    {formatDate(day)}
                  </div>
                </div>
              ))}
            </div>

            {/* Calendar cells */}
            <div className="grid grid-cols-7 h-[600px] border-t border-gray-100 dark:border-gray-800">
              {weekDays.map((day, dayIndex) => (
                <div 
                  key={dayIndex} 
                  className={`border-r border-b border-gray-100 dark:border-gray-800 px-2 py-2 relative ${dayIndex === 6 ? 'border-r-0' : ''} 
                    ${isToday(day) ? 'bg-gray-50 dark:bg-gray-900' : ''}
                    hover:bg-gray-50 dark:hover:bg-gray-900 transition-colors duration-200 cursor-pointer`}
                  onClick={() => handleDayClick(day)}
                >
                  {/* Events for this day */}
                  <div className="space-y-1 mt-2">
                    {getEventsForDay(day).map((event) => (
                      <div
                        key={event.id}
                        className="px-2 py-1.5 rounded-md text-white text-xs truncate"
                        style={{ backgroundColor: event.color }}
                        onClick={() => handleEventClick(event)}
                      >
                        <div className="font-medium">{event.startTime} - {event.title}</div>
                      </div>
                    ))}
                  </div>
                </div>
              ))}
            </div>
          </div>
        </main>

        {/* Event Details Panel */}
        {showEventDetails && selectedEvent && (
          <aside className="w-80 border-l border-gray-100 dark:border-gray-800 h-[calc(100vh-theme(spacing.32))] p-4">
            <div className="flex justify-between items-start mb-4">
              <h2 className="text-lg font-medium">{selectedEvent.title}</h2>
              <button
                onClick={() => setShowEventDetails(false)}
                className="p-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
              >
                <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                  <path fillRule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 111.414 1.414L11.414 10l4.293 4.293a1 1 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 01-1.414-1.414L8.586 10 4.293 5.707a1 1 010-1.414z" clipRule="evenodd" />
                </svg>
              </button>
            </div>

            <div className="space-y-4">
              <div>
                <div className="text-sm text-gray-500 dark:text-gray-400">Date & Time</div>
                <div className="mt-1 font-medium">
                  {new Date(selectedEvent.date).toLocaleDateString('en-US', { 
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                  })}
                </div>
                <div className="text-sm">
                  {selectedEvent.startTime} - {selectedEvent.endTime}
                </div>
              </div>

              {selectedEvent.description && (
                <div>
                  <div className="text-sm text-gray-500 dark:text-gray-400">Description</div>
                  <div className="mt-1">{selectedEvent.description}</div>
                </div>
              )}

              {selectedEvent.category && (
                <div>
                  <div className="text-sm text-gray-500 dark:text-gray-400">Category</div>
                  <div className="mt-1 flex items-center">
                    <span 
                      className="w-3 h-3 rounded-full mr-2"
                      style={{ backgroundColor: categories.find(c => c.id === selectedEvent.category)?.color }}
                    />
                    {categories.find(c => c.id === selectedEvent.category)?.name}
                  </div>
                </div>
              )}

              <div className="flex space-x-2 pt-4">
                <button
                  onClick={() => handleEditEvent(selectedEvent)}
                  className="flex-1 px-4 py-2 bg-black text-white dark:bg-white dark:text-black text-sm font-medium rounded-md hover:bg-gray-800 dark:hover:bg-gray-200 transition-colors"
                >
                  Edit
                </button>
                <button
                  onClick={() => handleDeleteEvent(selectedEvent.id)}
                  className="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-700 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-900 transition-colors"
                >
                  Delete
                </button>
              </div>
            </div>
          </aside>
        )}
      </div>

      {/* Event Modal - Vercel style with minimalist design */}
      {showEventModal && (
        <div className="fixed inset-0 z-10 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
          <div className="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div className="fixed inset-0 bg-black bg-opacity-40 transition-opacity" aria-hidden="true"></div>

            <span className="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div className="inline-block align-bottom bg-white dark:bg-black rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
              <div className="px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div className="sm:flex sm:items-start">
                  <div className="mt-3 text-center sm:mt-0 sm:text-left w-full">
                    <h3 className="text-lg leading-6 font-medium">
                      {selectedDay && (
                        <>New Event: {new Intl.DateTimeFormat('en-US', { weekday: 'long', month: 'long', day: 'numeric' }).format(selectedDay)}</>
                      )}
                    </h3>
                    <div className="mt-6 space-y-5">
                      <div>
                        <label htmlFor="title" className="block text-sm font-medium text-gray-700 dark:text-gray-300">Event Title</label>
                        <input
                          type="text"
                          id="title"
                          className="mt-1 block w-full border border-gray-200 dark:border-gray-700 rounded-md py-2 px-3 bg-white dark:bg-black text-black dark:text-white focus:outline-none focus:ring-1 focus:ring-black dark:focus:ring-white text-sm"
                          value={newEvent.title}
                          onChange={(e) => setNewEvent({ ...newEvent, title: e.target.value })}
                          placeholder="Add title"
                        />
                      </div>
                      <div>
                        <label htmlFor="description" className="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                        <textarea
                          id="description"
                          rows={3}
                          className="mt-1 block w-full border border-gray-200 dark:border-gray-700 rounded-md py-2 px-3 bg-white dark:bg-black text-black dark:text-white focus:outline-none focus:ring-1 focus:ring-black dark:focus:ring-white text-sm"
                          value={newEvent.description}
                          onChange={(e) => setNewEvent({ ...newEvent, description: e.target.value })}
                          placeholder="Add description"
                        />
                      </div>
                      <div className="grid grid-cols-2 gap-4">
                        <div>
                          <label htmlFor="startTime" className="block text-sm font-medium text-gray-700 dark:text-gray-300">Start Time</label>
                          <input
                            type="time"
                            id="startTime"
                            className="mt-1 block w-full border border-gray-200 dark:border-gray-700 rounded-md py-2 px-3 bg-white dark:bg-black text-black dark:text-white focus:outline-none focus:ring-1 focus:ring-black dark:focus:ring-white text-sm"
                            value={newEvent.startTime}
                            onChange={(e) => setNewEvent({ ...newEvent, startTime: e.target.value })}
                          />
                        </div>
                        <div>
                          <label htmlFor="endTime" className="block text-sm font-medium text-gray-700 dark:text-gray-300">End Time</label>
                          <input
                            type="time"
                            id="endTime"
                            className="mt-1 block w-full border border-gray-200 dark:border-gray-700 rounded-md py-2 px-3 bg-white dark:bg-black text-black dark:text-white focus:outline-none focus:ring-1 focus:ring-black dark:focus:ring-white text-sm"
                            value={newEvent.endTime}
                            onChange={(e) => setNewEvent({ ...newEvent, endTime: e.target.value })}
                          />
                        </div>
                      </div>
                      <div>
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Color</label>
                        <div className="mt-2 flex space-x-3">
                          {eventColors.map((color) => (
                            <div
                              key={color.value}
                              className={`h-6 w-6 rounded-full cursor-pointer transition-transform ${
                                newEvent.color === color.value ? 'ring-2 ring-offset-2 ring-gray-400 dark:ring-gray-600' : ''
                              } hover:scale-110`}
                              style={{ backgroundColor: color.value }}
                              onClick={() => setNewEvent({ ...newEvent, color: color.value })}
                              title={color.label}
                            />
                          ))}
                        </div>
                      </div>
                      <div>
                        <label htmlFor="category" className="block text-sm font-medium text-gray-700 dark:text-gray-300">Category</label>
                        <select
                          id="category"
                          className="mt-1 block w-full border border-gray-200 dark:border-gray-700 rounded-md py-2 px-3 bg-white dark:bg-black text-black dark:text-white focus:outline-none focus:ring-1 focus:ring-black dark:focus:ring-white text-sm"
                          value={newEvent.category}
                          onChange={(e) => setNewEvent({ ...newEvent, category: e.target.value })}
                        >
                          <option value="">Select category</option>
                          {categories.map(category => (
                            <option key={category.id} value={category.id}>{category.name}</option>
                          ))}
                        </select>
                      </div>
                      <div className="mt-4">
                        <div className="flex items-center">
                          <input
                            type="checkbox"
                            id="locked"
                            className="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded"
                            checked={newEvent.locked ?? false}
                            onChange={(e) => setNewEvent({ ...newEvent, locked: e.target.checked })}
                          />
                          <label htmlFor="locked" className="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                            Lock event (prevent AI from modifying)
                          </label>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div className="px-4 py-5 sm:px-6 flex flex-col-reverse sm:flex-row-reverse sm:justify-between border-t border-gray-200 dark:border-gray-800">
                <div className="flex space-x-2 mt-3 sm:mt-0">
                  <button
                    type="button"
                    className="flex-1 sm:flex-none px-4 py-2 bg-black text-white text-sm font-medium rounded-md hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-black transition-colors dark:bg-white dark:text-black dark:hover:bg-gray-200 dark:focus:ring-white"
                    onClick={handleSaveEvent}
                  >
                    Save Event
                  </button>
                  <button
                    type="button"
                    className="flex-1 sm:flex-none px-4 py-2 border border-gray-300 dark:border-gray-700 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-black hover:bg-gray-50 dark:hover:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-black dark:focus:ring-white transition-colors"
                    onClick={() => setShowEventModal(false)}
                  >
                    Cancel
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* AI Preferences Modal */}
      {showAIPreferences && (
        <div className="fixed inset-0 z-10 overflow-y-auto">
          <div className="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div className="fixed inset-0 bg-black bg-opacity-40 transition-opacity"></div>

            <span className="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <div className="inline-block align-bottom bg-white dark:bg-black rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
              <div className="px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <h3 className="text-lg leading-6 font-medium mb-4">AI Schedule Optimizer Preferences</h3>
                <div className="space-y-4">
                  <div className="grid grid-cols-2 gap-4">
                    <div>
                      <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Preferred Start Time
                      </label>
                      <input
                        type="time"
                        value={aiPreferences.preferredStartTime}
                        onChange={(e) => setAIPreferences({...aiPreferences, preferredStartTime: e.target.value})}
                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500"
                      />
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Preferred End Time
                      </label>
                      <input
                        type="time"
                        value={aiPreferences.preferredEndTime}
                        onChange={(e) => setAIPreferences({...aiPreferences, preferredEndTime: e.target.value})}
                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500"
                      />
                    </div>
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                      Focus Time Preference
                    </label>
                    <select
                      value={aiPreferences.focusTimePreference}
                      onChange={(e) => setAIPreferences({
                        ...aiPreferences, 
                        focusTimePreference: e.target.value as AIPreferences['focusTimePreference']
                      })}
                      className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500"
                    >
                      <option value="morning">Morning</option>
                      <option value="afternoon">Afternoon</option>
                      <option value="evening">Evening</option>
                    </select>
                  </div>

                  <div className="grid grid-cols-2 gap-4">
                    <div>
                      <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Break Duration (minutes)
                      </label>
                      <input
                        type="number"
                        value={aiPreferences.preferredBreakDuration}
                        onChange={(e) => setAIPreferences({
                          ...aiPreferences, 
                          preferredBreakDuration: parseInt(e.target.value)
                        })}
                        min="5"
                        max="60"
                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500"
                      />
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Session Duration (minutes)
                      </label>
                      <input
                        type="number"
                        value={aiPreferences.preferredSessionDuration}
                        onChange={(e) => setAIPreferences({
                          ...aiPreferences, 
                          preferredSessionDuration: parseInt(e.target.value)
                        })}
                        min="15"
                        max="180"
                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500"
                      />
                    </div>
                  </div>

                  <div className="grid grid-cols-2 gap-4">
                    <div>
                      <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Max Meetings Per Day
                      </label>
                      <input
                        type="number"
                        value={aiPreferences.maximumMeetingsPerDay}
                        onChange={(e) => setAIPreferences({
                          ...aiPreferences, 
                          maximumMeetingsPerDay: parseInt(e.target.value)
                        })}
                        min="1"
                        max="10"
                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500"
                      />
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Preferred Meeting Length (minutes)
                      </label>
                      <input
                        type="number"
                        value={aiPreferences.preferredMeetingDuration}
                        onChange={(e) => setAIPreferences({
                          ...aiPreferences, 
                          preferredMeetingDuration: parseInt(e.target.value)
                        })}
                        min="15"
                        max="180"
                        step="15"
                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500"
                      />
                    </div>
                  </div>

                  <div className="flex items-center">
                    <input
                      type="checkbox"
                      checked={aiPreferences.breaksBetweenTasks}
                      onChange={(e) => setAIPreferences({
                        ...aiPreferences, 
                        breaksBetweenTasks: e.target.checked
                      })}
                      className="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded"
                    />
                    <label className="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                      Add breaks between tasks
                    </label>
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                      AI Assistant Personality
                    </label>
                    <textarea
                      value={aiPreferences.systemPrompt}
                      onChange={(e) => setAIPreferences({
                        ...aiPreferences,
                        systemPrompt: e.target.value
                      })}
                      rows={6}
                      className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500"
                      placeholder="Describe how you want the AI to assist you..."
                    />
                    <p className="mt-1 text-sm text-gray-500">
                      This prompt helps the AI understand your preferences and personality. You can modify it to better suit your needs.
                    </p>
                  </div>
                </div>
              </div>
              
              <div className="px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse bg-gray-50 dark:bg-gray-900">
                <button
                  type="button"
                  onClick={() => {
                    setShowAIPreferences(false);
                    localStorage.setItem('calendar-has-used-optimizer', 'true');
                  }}
                  className="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-purple-600 text-base font-medium text-white hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 sm:ml-3 sm:w-auto sm:text-sm"
                >
                  Save Preferences
                </button>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* AI Optimizer Suggestions Modal */}
      {showOptimizer && (
        <div className="fixed inset-0 z-10 overflow-y-auto">
          <div className="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div className="fixed inset-0 bg-black bg-opacity-40 transition-opacity"></div>

            <span className="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <div className="inline-block align-bottom bg-white dark:bg-black rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
              <div className="px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div className="sm:flex sm:items-start">
                  <div className="mt-3 text-center sm:mt-0 sm:text-left w-full">
                    <h3 className="text-lg leading-6 font-medium mb-4">Schedule Optimization Suggestions</h3>
                    
                    {optimizerSuggestions.length === 0 ? (
                      <p className="text-sm text-gray-500 dark:text-gray-400">
                        No suggestions available. Your schedule looks optimized!
                      </p>
                    ) : (
                      <div className="space-y-4">
                        {optimizerSuggestions.map((suggestion, index) => (
                          <div 
                            key={index}
                            className="p-4 rounded-lg border border-gray-200 dark:border-gray-700"
                          >
                            <p className="text-sm mb-3">{suggestion.description}</p>
                            <div className="flex justify-end space-x-2">
                              <button
                                onClick={() => applyOptimizationSuggestion(suggestion)}
                                className="px-3 py-1 text-sm font-medium rounded-md bg-purple-600 text-white hover:bg-purple-700"
                              >
                                Apply
                              </button>
                              <button
                                onClick={() => setOptimizerSuggestions(
                                  optimizerSuggestions.filter((_, i) => i !== index)
                                )}
                                className="px-3 py-1 text-sm font-medium rounded-md border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-900"
                              >
                                Ignore
                              </button>
                            </div>
                          </div>
                        ))}
                      </div>
                    )}
                  </div>
                </div>
              </div>
              
              <div className="px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse bg-gray-50 dark:bg-gray-900">
                <button
                  type="button"
                  onClick={() => setShowOptimizer(false)}
                  className="w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-black text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 sm:ml-3 sm:w-auto sm:text-sm"
                >
                  Close
                </button>
                <button
                  type="button"
                  onClick={() => setShowAIPreferences(true)}
                  className="mt-3 sm:mt-0 w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-purple-600 text-base font-medium text-white hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 sm:ml-3 sm:w-auto sm:text-sm"
                >
                  Adjust Preferences
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
