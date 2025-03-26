export type ViewMode = 'day' | 'week' | 'month' | 'year';

export type EventCategory = {
    id: string;
    name: string;
    color: string;
    icon?: string;
    quickTemplates?: {
        title: string;
        duration: number;
    }[];
};

export interface CalendarEvent {
  id: string;
  title: string;
  description?: string;
  start: Date;
  end: Date;
  category: EventCategory;
  location?: string;
}

export type UserPreferences = {
    defaultEventDuration: number;
    workingHours: {
        start: string;
        end: string;
    };
    breakDuration: number;
    preferredMeetingDays: number[];
    minimumBreakBetweenEvents: number;
    defaultView: ViewMode;
    quickEventTemplates: {
        title: string;
        duration: number;
        category: string;
    }[];
};