import { type ClassValue, clsx } from "clsx"
import { twMerge } from "tailwind-merge"

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs))
}

export interface Event {
  id: string
  title: string
  start: Date
  end: Date
  description?: string
  type?: 'work' | 'study' | 'exercise' | 'break' | 'other'
}

export function generateTimeSlots(events: Event[], userPreferences: string): Event[] {
  // This is a placeholder for the AI logic that will be implemented
  // The actual implementation will use the Ollama API to generate optimized time slots
  return events
}