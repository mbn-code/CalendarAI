import { useMemo } from 'react';
import { useCalendarStore } from '@/store/useCalendarStore';
import { format, parseISO } from 'date-fns';
import { Button } from "@/components/ui/button";
import { Card } from "@/components/ui/card";
import { ScrollArea } from "@/components/ui/scroll-area";
import { EventPreview } from './EventPreview';
import type { CalendarEvent } from '@/types';

interface ViewProps {
  date: Date;
  onDateClick: (date: Date) => void;
}

const HOURS = Array.from({ length: 24 }, (_, i) => i);

// Shared components for consistency
const ViewContainer = ({ children }: { children: React.ReactNode }) => (
  <Card className="h-full overflow-hidden border bg-card">
    <ScrollArea className="h-full">
      {children}
    </ScrollArea>
  </Card>
);

const EventBlock = ({ event, className }: { event: CalendarEvent; className?: string }) => {
  const { deleteEvent } = useCalendarStore();
  return (
    <EventPreview event={event} onDelete={() => deleteEvent(event.id)}>
      <div
        className={`px-2 py-1 rounded-md text-xs truncate border ${className}`}
        style={{ 
          backgroundColor: event.category.color + '10',
          borderColor: event.category.color + '40',
          color: event.category.color,
        }}
      >
        {format(event.start, 'HH:mm')} - {event.title}
      </div>
    </EventPreview>
  );
};

const DateCell = ({ 
  date, 
  isToday, 
  isCurrentPeriod = true,
  events = [],
  onClick,
}: { 
  date: Date; 
  isToday: boolean;
  isCurrentPeriod?: boolean;
  events?: any[];
  onClick: () => void;
}) => (
  <div
    onClick={onClick}
    className={`min-h-[100px] p-2 border-b border-r relative transition-colors
      ${isCurrentPeriod ? 'bg-background' : 'bg-muted/50'}
      hover:bg-accent/50 cursor-pointer`}
  >
    <div className="flex justify-between items-center">
      <Button
        variant={isToday ? "default" : "ghost"}
        size="sm"
        className={`h-8 w-8 p-0 font-normal ${!isToday && 'hover:bg-transparent'}`}
        onClick={onClick}
      >
        {format(date, 'd')}
      </Button>
      {isCurrentPeriod && events.length > 0 && (
        <span className="text-xs font-medium text-muted-foreground">
          {events.length} event{events.length !== 1 ? 's' : ''}
        </span>
      )}
    </div>
    {isCurrentPeriod && (
      <div className="mt-2 space-y-1">
        {events.map((event, index) => (
          <EventBlock key={event.id || index} event={event} />
        ))}
      </div>
    )}
  </div>
);

const TimeGrid = ({ children }: { children: React.ReactNode }) => (
  <div className="relative" style={{ height: `${HOURS.length * 60}px` }}>
    {HOURS.map((hour) => (
      <div
        key={hour}
        className="absolute w-full border-t border-border"
        style={{ top: `${hour * 60}px`, height: '60px' }}
      >
        <span className="sticky left-0 -mt-2 -ml-12 w-12 pr-2 text-right text-xs text-muted-foreground">
          {hour.toString().padStart(2, '0')}:00
        </span>
      </div>
    ))}
    {children}
  </div>
);

export function DayView({ date, onDateClick }: ViewProps) {
  const { events, deleteEvent } = useCalendarStore();
  
  // Parse event dates before using them
  const parsedEvents = useMemo(() => 
    events.map(event => ({
      ...event,
      start: parseDate(event.start),
      end: parseDate(event.end)
    })),
    [events]
  );

  const todayEvents = parsedEvents.filter(event => 
    format(event.start, 'yyyy-MM-dd') === format(date, 'yyyy-MM-dd')
  );

  return (
    <ViewContainer>
      <div className="sticky top-0 z-10 bg-background border-b px-4 py-2">
        <div className="text-sm text-muted-foreground">
          {format(date, 'EEEE')}
        </div>
        <div className="text-lg font-semibold">
          {format(date, 'MMMM d, yyyy')}
        </div>
      </div>
      <div className="relative min-h-full p-4">
        <TimeGrid>
          {todayEvents.map((event) => (
            <EventPreview key={event.id} event={event} onDelete={() => deleteEvent(event.id)}>
              <div
                className="absolute left-16 right-4 rounded-md border text-sm"
                style={{
                  top: `${getMinutesSinceMidnight(event.start)}px`,
                  height: `${getEventDurationInMinutes(event.start, event.end)}px`,
                  backgroundColor: event.category.color + '10',
                  borderColor: event.category.color + '40',
                }}
              >
                <div className="p-2">
                  <div className="font-medium" style={{ color: event.category.color }}>
                    {event.title}
                  </div>
                  <div className="text-xs text-muted-foreground">
                    {format(event.start, 'HH:mm')} - {format(event.end, 'HH:mm')}
                  </div>
                </div>
              </div>
            </EventPreview>
          ))}
        </TimeGrid>
      </div>
    </ViewContainer>
  );
}

// Helper function to safely parse dates
const parseDate = (date: string | Date): Date => {
  if (date instanceof Date) return date;
  try {
    return parseISO(date);
  } catch (e) {
    return new Date();
  }
};

export function WeekView({ date, onDateClick }: ViewProps) {
  const { events, deleteEvent } = useCalendarStore();
  const weekDays = useMemo(() => {
    const days = [];
    const start = startOfWeek(date);
    for (let i = 0; i < 7; i++) {
      const day = addDays(start, i);
      days.push(day);
    }
    return days;
  }, [date]);

  // Parse event dates before using them
  const parsedEvents = useMemo(() => 
    events.map(event => ({
      ...event,
      start: parseDate(event.start),
      end: parseDate(event.end)
    })),
    [events]
  );

  return (
    <ViewContainer>
      <div className="grid grid-cols-7 border-b">
        {weekDays.map((day, i) => (
          <div key={i} className="sticky top-0 z-10 bg-background p-2 text-center border-r">
            <div className="text-xs text-muted-foreground">{format(day, 'EEE')}</div>
            <div className={`inline-flex h-8 w-8 items-center justify-center rounded-full text-sm mt-1
              ${isToday(day) ? 'bg-primary text-primary-foreground' : ''}`}>
              {format(day, 'd')}
            </div>
          </div>
        ))}
      </div>
      <div className="grid grid-cols-7">
        {weekDays.map((day, dayIndex) => (
          <div key={dayIndex} className="border-r">
            <TimeGrid>
              {parsedEvents
                .filter(event => format(event.start, 'yyyy-MM-dd') === format(day, 'yyyy-MM-dd'))
                .map((event) => (
                  <EventPreview key={event.id} event={event} onDelete={() => deleteEvent(event.id)}>
                    <div
                      className="absolute left-1 right-1 rounded-md border text-xs cursor-pointer"
                      style={{
                        top: `${getMinutesSinceMidnight(event.start)}px`,
                        height: `${getEventDurationInMinutes(event.start, event.end)}px`,
                        backgroundColor: event.category.color + '10',
                        borderColor: event.category.color + '40',
                      }}
                    >
                      <div className="p-1 truncate">
                        <div className="font-medium" style={{ color: event.category.color }}>
                          {event.title}
                        </div>
                        <div className="text-[10px] text-muted-foreground">
                          {format(event.start, 'HH:mm')}
                        </div>
                      </div>
                    </div>
                  </EventPreview>
                ))}
            </TimeGrid>
          </div>
        ))}
      </div>
    </ViewContainer>
  );
}

export function MonthView({ date, onDateClick }: ViewProps) {
  const { events } = useCalendarStore();
  
  // Parse event dates before using them
  const parsedEvents = useMemo(() => 
    events.map(event => ({
      ...event,
      start: parseDate(event.start),
      end: parseDate(event.end)
    })),
    [events]
  );
  
  const monthDays = useMemo(() => {
    return getMonthView(date);
  }, [date]);

  const DateCell = ({ 
    date, 
    isToday, 
    isCurrentPeriod = true,
    events = [],
    onClick,
  }: { 
    date: Date; 
    isToday: boolean;
    isCurrentPeriod?: boolean;
    events: CalendarEvent[];
    onClick: () => void;
  }) => (
    <div
      onClick={onClick}
      className={`min-h-[100px] p-2 border-b border-r relative transition-colors
        ${isCurrentPeriod ? 'bg-background' : 'bg-muted/50'}
        hover:bg-accent/50 cursor-pointer`}
    >
      <div className="flex justify-between items-center">
        <Button
          variant={isToday ? "default" : "ghost"}
          size="sm"
          className={`h-8 w-8 p-0 font-normal ${!isToday && 'hover:bg-transparent'}`}
          onClick={onClick}
        >
          {format(date, 'd')}
        </Button>
        {isCurrentPeriod && events.length > 0 && (
          <span className="text-xs font-medium text-muted-foreground">
            {events.length} event{events.length !== 1 ? 's' : ''}
          </span>
        )}
      </div>
      {isCurrentPeriod && (
        <div className="mt-2 space-y-1">
          {events.map((event) => (
            <EventBlock key={event.id} event={event} />
          ))}
        </div>
      )}
    </div>
  );

  return (
    <ViewContainer>
      <div className="grid grid-cols-7 border-b">
        {['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].map((day) => (
          <div key={day} className="p-2 text-center text-sm font-medium text-muted-foreground">
            {day}
          </div>
        ))}
      </div>
      <div className="grid grid-cols-7">
        {monthDays.map(({ date: day, isCurrentMonth }) => (
          <DateCell
            key={day.toISOString()}
            date={day}
            isToday={isToday(day)}
            isCurrentPeriod={isCurrentMonth}
            events={parsedEvents.filter(event => 
              format(event.start, 'yyyy-MM-dd') === format(day, 'yyyy-MM-dd')
            )}
            onClick={() => onDateClick(day)}
          />
        ))}
      </div>
    </ViewContainer>
  );
}

export function YearView({ date, onDateClick }: ViewProps) {
  const { events } = useCalendarStore();
  const months = useMemo(() => {
    return Array.from({ length: 12 }, (_, i) => {
      const monthDate = new Date(date.getFullYear(), i, 1);
      return {
        date: monthDate,
        events: events.filter(event => 
          event.start.getMonth() === i && 
          event.start.getFullYear() === date.getFullYear()
        ),
      };
    });
  }, [date, events]);

  return (
    <ViewContainer>
      <div className="grid grid-cols-3 lg:grid-cols-4 gap-4 p-4">
        {months.map(({ date: monthDate, events }) => (
          <div
            key={monthDate.getMonth()}
            className="border rounded-lg overflow-hidden hover:shadow-lg transition-shadow"
            onClick={() => onDateClick(monthDate)}
          >
            <div className="bg-muted p-2 border-b">
              <div className="font-medium text-center">
                {format(monthDate, 'MMMM')}
              </div>
              {events.length > 0 && (
                <div className="text-xs text-center text-muted-foreground mt-1">
                  {events.length} event{events.length !== 1 ? 's' : ''}
                </div>
              )}
            </div>
            <div className="p-2">
              <div className="grid grid-cols-7 gap-px">
                {getMonthView(monthDate).map(({ date: day, isCurrentMonth }) => (
                  <div
                    key={day.toISOString()}
                    className={`aspect-square text-center text-xs ${
                      isCurrentMonth 
                        ? 'text-foreground' 
                        : 'text-muted-foreground'
                    } ${
                      isToday(day) 
                        ? 'bg-primary text-primary-foreground rounded-full' 
                        : ''
                    }`}
                  >
                    {format(day, 'd')}
                  </div>
                ))}
              </div>
            </div>
          </div>
        ))}
      </div>
    </ViewContainer>
  );
}

// Helper functions
function getMinutesSinceMidnight(date: Date): number {
  return date.getHours() * 60 + date.getMinutes();
}

function getEventDurationInMinutes(start: Date, end: Date): number {
  return (end.getTime() - start.getTime()) / (1000 * 60);
}

function isToday(date: Date): boolean {
  const today = new Date();
  return (
    date.getDate() === today.getDate() &&
    date.getMonth() === today.getMonth() &&
    date.getFullYear() === today.getFullYear()
  );
}

import { startOfWeek, addDays, startOfMonth, endOfMonth, addMonths } from 'date-fns';

function getMonthView(date: Date) {
  const start = startOfWeek(startOfMonth(date));
  const end = endOfMonth(date);
  const days = [];

  let current = start;
  while (current <= endOfMonth(addMonths(date, 1))) {
    days.push({
      date: current,
      isCurrentMonth: current.getMonth() === date.getMonth(),
    });
    current = addDays(current, 1);
  }

  return days;
}