export interface AIPreferences {
  preferredStartTime: string;
  preferredEndTime: string;
  preferredBreakDuration: number;
  preferredSessionDuration: number;
  focusTimePreference: 'morning' | 'afternoon' | 'evening';
  breaksBetweenTasks: boolean;
  maximumMeetingsPerDay: number;
  preferredMeetingDuration: number;
  systemPrompt: string;
}

export interface OptimizerSuggestion {
  type: "move" | "split" | "break";
  description: string;
  changes: {
    eventId: string;
    newStartTime?: string;
    newEndTime?: string;
    newDate?: string;
  };
}

export interface OptimizerResponse {
  suggestions: OptimizerSuggestion[];
}