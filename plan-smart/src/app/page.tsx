"use client";

import { Calendar } from "@/components/Calendar";
import { AIOptimizeButton } from "@/components/AIOptimizeButton";
import type { Event } from "@/lib/utils";
import { useState, useEffect } from "react";
import { Card } from "@tremor/react";
import { useToast } from "@/components/ui/toast-context";

interface StoredEvent extends Omit<Event, 'start' | 'end'> {
  start: string;
  end: string;
}

export default function Home() {
  const [events, setEvents] = useState<Event[]>([]);
  const { showToast } = useToast();

  // Load events from localStorage on mount
  useEffect(() => {
    try {
      const savedEvents = localStorage.getItem('calendar-events');
      if (savedEvents) {
        const parsed = JSON.parse(savedEvents) as StoredEvent[];
        const parsedEvents: Event[] = parsed.map((event) => ({
          ...event,
          start: new Date(event.start),
          end: new Date(event.end),
        }));
        setEvents(parsedEvents);
      }
    } catch (error) {
      console.error('Error loading events:', error);
      showToast('Error', 'Failed to load saved events');
    }
  }, [showToast]);

  // Save events to localStorage whenever they change
  useEffect(() => {
    try {
      localStorage.setItem('calendar-events', JSON.stringify(events));
    } catch (error) {
      console.error('Error saving events:', error);
      showToast('Error', 'Failed to save events');
    }
  }, [events, showToast]);

  const getEventTypeCount = (type: string) => {
    return events.filter((event) => event.type === type).length;
  };

  return (
    <main className="container mx-auto px-4 py-4">
      {/* Stats Cards */}
      <div className="flex justify-between items-center mb-6">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4 flex-1 mr-4">
          <Card className="p-4">
            <h3 className="text-sm font-medium text-gray-500">Work Events</h3>
            <p className="text-2xl font-bold text-indigo-600">{getEventTypeCount('work')}</p>
          </Card>
          <Card className="p-4">
            <h3 className="text-sm font-medium text-gray-500">Study Sessions</h3>
            <p className="text-2xl font-bold text-purple-600">{getEventTypeCount('study')}</p>
          </Card>
          <Card className="p-4">
            <h3 className="text-sm font-medium text-gray-500">Exercise</h3>
            <p className="text-2xl font-bold text-green-600">{getEventTypeCount('exercise')}</p>
          </Card>
          <Card className="p-4">
            <h3 className="text-sm font-medium text-gray-500">Breaks</h3>
            <p className="text-2xl font-bold text-orange-600">{getEventTypeCount('break')}</p>
          </Card>
        </div>
        <AIOptimizeButton onEventsUpdate={setEvents} currentEvents={events} />
      </div>

      {/* Calendar Section */}
      <div className="bg-white rounded-xl shadow-xl p-6 backdrop-blur-sm bg-opacity-90 min-h-[700px]">
        <Calendar events={events} onEventsUpdate={setEvents} />
      </div>
    </main>
  );
}
