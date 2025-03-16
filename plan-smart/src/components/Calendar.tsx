"use client";

import { Calendar as BigCalendar, dateFnsLocalizer } from 'react-big-calendar';
import { format, parse, startOfWeek, getDay } from 'date-fns';
import type { Locale } from 'date-fns';
import { enUS } from 'date-fns/locale';
import 'react-big-calendar/lib/css/react-big-calendar.css';
import { v4 as uuidv4 } from 'uuid';
import type { Event } from '@/lib/utils';
import { useToast } from '@/components/ui/toast-context';
import { Button } from './ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from './ui/dialog';
import { useState } from 'react';

const locales = {
  'en-US': enUS,
} satisfies Record<string, Locale>;

const localizer = dateFnsLocalizer({
  format,
  parse,
  startOfWeek,
  getDay,
  locales,
});

const eventStyleGetter = (event: Event) => {
  const style = {
    backgroundColor: '#3182ce',
    borderRadius: '5px',
    opacity: 0.8,
    color: 'white' as const,
    border: '0',
    display: 'block' as const,
  };

  switch (event.type) {
    case 'work':
      style.backgroundColor = '#3182ce';
      break;
    case 'study':
      style.backgroundColor = '#805ad5';
      break;
    case 'exercise':
      style.backgroundColor = '#48bb78';
      break;
    case 'break':
      style.backgroundColor = '#ed8936';
      break;
    case 'other':
      style.backgroundColor = '#718096';
      break;
  }

  return { style };
};

interface CalendarProps {
  events: Event[];
  onEventsUpdate: (events: Event[]) => void;
}

export function Calendar({ events, onEventsUpdate }: CalendarProps) {
  const { showToast } = useToast();
  const [selectedEvent, setSelectedEvent] = useState<Event | null>(null);
  const [showEventDialog, setShowEventDialog] = useState(false);

  const handleSelect = ({ start, end }) => {
    const types = ['work', 'study', 'exercise', 'break', 'other'] as const;
    const title = prompt('Enter event title:');
    
    if (title) {
      const type = prompt(`Enter event type (${types.join(', ')}):`) as Event['type'];
      const newEvent = {
        id: uuidv4(),
        title,
        start,
        end,
        type: types.includes(type) ? type : 'other'
      };
      
      onEventsUpdate([...events, newEvent]);
      showToast('Event Added', 'New event has been added to your calendar');
    }
  };

  const handleEventClick = (event: Event) => {
    setSelectedEvent(event);
    setShowEventDialog(true);
  };

  const handleDeleteEvent = () => {
    if (selectedEvent) {
      onEventsUpdate(events.filter(event => event.id !== selectedEvent.id));
      setShowEventDialog(false);
      showToast('Event Deleted', 'The event has been removed from your calendar');
    }
  };

  return (
    <>
      <div className="h-[600px] w-full rounded-lg border bg-background p-4 shadow-lg">
        <BigCalendar
          localizer={localizer}
          events={events}
          startAccessor="start"
          endAccessor="end"
          style={{ height: '100%' }}
          selectable
          onSelectSlot={handleSelect}
          onSelectEvent={handleEventClick}
          eventPropGetter={eventStyleGetter}
          views={['month', 'week', 'day']}
          defaultView="week"
          className="rounded-md shadow-inner bg-white p-2"
        />
      </div>

      <Dialog open={showEventDialog} onOpenChange={setShowEventDialog}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>{selectedEvent?.title}</DialogTitle>
            <DialogDescription>
              {format(selectedEvent?.start ?? new Date(), 'PPp')} -{' '}
              {format(selectedEvent?.end ?? new Date(), 'PPp')}
            </DialogDescription>
          </DialogHeader>
          <div className="flex justify-between items-center mt-4">
            <div className="px-2 py-1 rounded text-sm capitalize bg-gray-100">
              {selectedEvent?.type}
            </div>
            <Button
              variant="destructive"
              onClick={handleDeleteEvent}
            >
              Delete Event
            </Button>
          </div>
        </DialogContent>
      </Dialog>
    </>
  );
}